<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ConversationFlowService
{
    protected $flows;
    protected $settings;
    protected $memberId;
    protected $sessionKey;

    public function __construct($memberId)
    {
        $this->flows = config('conversation_flows');
        $this->settings = $this->flows['settings'] ?? [];
        $this->memberId = $memberId;
        $this->sessionKey = "conversation_flow_{$memberId}";
    }

    /**
     * Analyze the conversation and determine if a flow should be triggered
     *
     * @param string $userQuestion The current user question
     * @param string $aiResponse The AI's response
     * @param array $userProfile User profile data (subscription status, etc.)
     * @return array|null Flow response or null if no flow triggered
     */
    public function analyzeAndTrigger($userQuestion, $aiResponse, $userProfile = [])
    {
        // Increment session message count
        $sessionMessageCount = $this->incrementSessionMessageCount();

        // Get current session state
        $sessionState = $this->getSessionState();

        // Check subscription tier
        // DB codes: free, all_ai, hybrid, premium, vip
        // Display names: Free, AI Smart Plan, Hybrid Expert Plan, Premium Confidence Plan, VIP Global Partner Plan
        $subscriptionTier = $userProfile['subscription_tier'] ?? 'free';

        // Get conversation statistics
        $stats = $this->getConversationStats();

        // Check if user is dismissing a previous promo
        // If they just said "no thanks" etc, acknowledge it and don't show another promo
        $lastTrigger = $sessionState['last_trigger_type'] ?? null;
        if ($lastTrigger && $this->isDismissingPromo($userQuestion)) {
            // User dismissed the promo - acknowledge gracefully
            // Return a friendly message prompting them to continue with their real question
            return [
                'type' => 'dismissal_acknowledgment',
                'message' => 'No problem! Feel free to ask me anything about immigration or study abroad whenever you\'re ready.',
                'actions' => []
            ];
        }

        // Check for positive responses to previous flow prompts
        // Now enabled for ALL tiers - if user explicitly shows upgrade interest, respond immediately
        // The improved isPositiveResponse() filters out false positives (generic "yes"/"okay")
        if ($lastTrigger && $this->isPositiveResponse($userQuestion)) {
            // User showed EXPLICIT interest in the last prompt - show upgrade info immediately
            // This bypasses all limits since user explicitly expressed interest
            return $this->createUpgradePrompt($subscriptionTier, $stats['message_count'], $userQuestion);
        }

        // PRIORITY: Detect human consultation needs for All AI Plan users
        // This triggers Hybrid Plan promotion when user needs personalized guidance
        // NO LIMIT: Intelligent scoring-based triggers don't need artificial limits
        // since they only trigger on genuine detected needs
        if ($subscriptionTier === 'all_ai') {
            $needsHumanConsultation = $this->detectsNeedForHumanConsultation($userQuestion, $sessionState, $stats);

            if ($needsHumanConsultation) {
                $lastHybridTrigger = $sessionState['last_triggers']['hybrid_expert_consultation'] ?? 0;
                // Cooldown of 7 messages to avoid spam but be responsive
                if (($stats['message_count'] - $lastHybridTrigger) >= 7) {
                    return $this->createHumanConsultationPrompt($stats['message_count'], $userQuestion);
                }
            }
        }

        // PRIORITY: Detect final validation needs for Hybrid Plan users
        // This triggers Premium Plan promotion when user is ready to submit
        // NO LIMIT: Intelligent scoring-based triggers only fire on genuine detected needs
        if ($subscriptionTier === 'hybrid') {
            $needsFinalValidation = $this->detectsNeedForFinalValidation($userQuestion, $sessionState, $stats);

            if ($needsFinalValidation) {
                $lastConfidenceTrigger = $sessionState['last_triggers']['premium_confidence_validation'] ?? 0;
                // Cooldown of 7 messages to avoid spam but be responsive
                if (($stats['message_count'] - $lastConfidenceTrigger) >= 7) {
                    return $this->createFinalValidationPrompt($stats['message_count'], $userQuestion);
                }
            }
        }

        // PRIORITY: Detect full-service support needs for Premium Plan users
        // This triggers VIP Plan promotion when user wants hands-on help
        // NO LIMIT: Intelligent scoring-based triggers only fire on genuine detected needs
        if ($subscriptionTier === 'premium') {
            $needsFullService = $this->detectsNeedForFullService($userQuestion, $sessionState, $stats);

            if ($needsFullService) {
                $lastVipTrigger = $sessionState['last_triggers']['vip_global_full_service'] ?? 0;
                // Cooldown of 7 messages to avoid spam but be responsive
                if (($stats['message_count'] - $lastVipTrigger) >= 7) {
                    return $this->createFullServicePrompt($stats['message_count'], $userQuestion);
                }
            }
        }

        // Check for study application assistance (only for free and All AI Plan users)
        // Triggers when user needs help applying to educational institutions or wants application support
        if ($subscriptionTier === 'free' || $subscriptionTier === 'all_ai') {
            $questionLower = mb_strtolower($userQuestion);

            // Education institution keywords
            $educationKeywords = [
                'university', 'college', 'school', 'tafe', 'institute',
                'scholarship', 'bachelor', 'master', 'phd', 'undergraduate', 'postgraduate',
                'course', 'program', 'degree', 'student visa', 'study abroad'
            ];

            // Help/assistance request keywords
            $helpKeywords = [
                'help me apply', 'help with application', 'apply for me', 'can you apply',
                'need help applying', 'assist with application', 'guide me through',
                'help me find', 'help me get into', 'can you help me apply',
                'someone to apply', 'assist me with', 'support with application',
                'application support', 'application assistance', 'application help'
            ];

            // Check if question mentions education + asking for help with application
            $hasEducationKeyword = $this->matchesKeywords($questionLower, $educationKeywords);
            $needsApplicationHelp = $this->matchesKeywords($questionLower, $helpKeywords);

            // Trigger if BOTH conditions met: education context + asking for application help
            if ($hasEducationKeyword && $needsApplicationHelp) {
                // Check cooldown to avoid showing too frequently
                $lastStudyTrigger = $sessionState['last_triggers']['study_assistance'] ?? 0;

                // Longer cooldown (10 messages) - no per-session limit since it's contextual
                if (($stats['message_count'] - $lastStudyTrigger) >= 10) {
                    return $this->createStudyAssistancePrompt();
                }
            }
        }

        // For FREE users: prompt every 5 messages
        // BUT skip if it's just a simple greeting (hi, hello, etc.) - those are conversation starters
        // ALSO require at least 3 messages in the current session to avoid showing promo immediately
        if ($subscriptionTier === 'free' && $stats['message_count'] % 5 === 0 && $stats['message_count'] >= 5) {
            // Don't show promo on simple greetings
            if ($this->isSimpleGreeting($userQuestion)) {
                return null;
            }

            // Don't show promo in the first few messages of the session (feels pushy)
            // Wait until after message 3 (so shows on message 4+)
            if ($sessionMessageCount <= 3) {
                return null;
            }

            $lastAnyTrigger = !empty($sessionState['last_triggers']) ? max($sessionState['last_triggers']) : 0;
            if (($stats['message_count'] - $lastAnyTrigger) >= 3) {
                return $this->createUpgradePrompt($subscriptionTier, $stats['message_count'], $userQuestion, true);
            }
        }

        return null;
    }

    /**
     * Check if user question matches any keywords
     */
    protected function matchesKeywords($question, $keywords)
    {
        $questionLower = mb_strtolower($question);
        foreach ($keywords as $keyword) {
            if (mb_strpos($questionLower, mb_strtolower($keyword)) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if message is just a simple greeting (not a real question)
     * Don't show promos on greetings - they're conversation starters, not engagement points
     */
    protected function isSimpleGreeting($question)
    {
        $trimmed = trim(mb_strtolower($question));

        // Remove punctuation for matching
        $normalized = preg_replace('/[!?.,:;]/', '', $trimmed);

        $greetings = [
            'hi', 'hello', 'hey', 'hola', 'hii', 'heya', 'howdy',
            'hi there', 'hello there', 'hey there',
            'good morning', 'good afternoon', 'good evening',
            'greetings', 'whats up', "what's up", 'sup',
            'yo', 'hiya'
        ];

        return in_array($normalized, $greetings);
    }

    /**
     * Check if user is dismissing a promo (saying no thanks, not interested, etc.)
     * If true, we should acknowledge it gracefully and not confuse the chatbot
     */
    protected function isDismissingPromo($question)
    {
        $questionLower = mb_strtolower(trim($question));

        $dismissalPhrases = [
            'no', 'nope', 'not interested', 'no thanks', 'not now', 'maybe later',
            'not right now', 'skip', 'ignore', 'later', 'pass', 'decline',
            'no need', 'don\'t want', 'don\'t need', 'not for me',
            'i\'m good', 'all set', 'no thank you', 'no thx'
        ];

        // Check if the entire message is just a dismissal (not part of a longer question)
        foreach ($dismissalPhrases as $phrase) {
            if ($questionLower === $phrase || $questionLower === $phrase . '!') {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user response indicates positive interest in UPGRADE/PROMO
     * Must be explicit to avoid false positives from general conversation
     */
    protected function isPositiveResponse($question)
    {
        $questionLower = mb_strtolower(trim($question));

        // FIRST: Check for negative/dismissive responses
        $negativeKeywords = [
            'no', 'nope', 'not interested', 'no thanks', 'not now', 'maybe later',
            'not right now', 'skip', 'ignore', 'later', 'pass', 'decline',
            'no need', 'already have', 'don\'t want', 'don\'t need',
            'not for me', 'i\'m good', 'all set', 'no thank you'
        ];

        foreach ($negativeKeywords as $keyword) {
            if (mb_strpos($questionLower, mb_strtolower($keyword)) !== false) {
                return false; // Explicit rejection
            }
        }

        // SECOND: Check for EXPLICIT upgrade interest (must mention upgrade/plan/pricing context)
        $explicitInterestPhrases = [
            // Direct upgrade interest
            'tell me more about the plan', 'tell me more about upgrade', 'tell me about the plan',
            'interested in upgrade', 'interested in the plan', 'want to upgrade',
            'sounds good, tell me more', 'yes tell me more', 'yes, tell me more',
            'yes please tell me', 'yes i want', 'yes i\'m interested',

            // Pricing inquiries (EXPLICIT interest)
            'how much', 'what\'s the price', 'what is the price', 'how much does it cost',
            'what does it cost', 'pricing', 'price details', 'cost details',

            // Plan-specific interest
            'what do i get', 'what does the plan', 'what features', 'what\'s included',
            'show me the plan', 'tell me about expert', 'tell me about consultation',

            // Explicit yes with context
            'yes upgrade', 'yes i want to upgrade', 'yes interested', 'yes show me'
        ];

        foreach ($explicitInterestPhrases as $phrase) {
            if (mb_strpos($questionLower, mb_strtolower($phrase)) !== false) {
                return true; // Clear upgrade interest
            }
        }

        // THIRD: Single-word "yes/okay" should be IGNORED (too generic, likely answering chatbot)
        $trimmedQuestion = trim($questionLower);
        $singleWordGeneric = ['yes', 'yeah', 'yep', 'ok', 'okay', 'sure', 'great', 'thanks', 'thank you'];

        if (in_array($trimmedQuestion, $singleWordGeneric)) {
            return false; // Too generic - likely answering previous chatbot question
        }

        // If we reach here: no explicit interest detected
        return false;
    }

    /**
     * Create upgrade prompt based on subscription tier
     *
     * @param string $currentTier Current subscription tier
     * @param int $messageCount Current message count
     * @param string $userQuestion User's question
     * @param bool $isGeneric Whether this is a generic prompt (counts toward generic limit)
     */
    protected function createUpgradePrompt($currentTier, $messageCount, $userQuestion, $isGeneric = false)
    {
        $this->incrementPromptCount($isGeneric);
        $this->recordTrigger('upgrade_prompt_' . $currentTier);

        $message = '';
        $actions = [];
        $nextTier = '';

        switch ($currentTier) {
            case 'free':
                $promoMessages = [
            "By the way, you can get unlimited AI guidance and the latest updates. Let me know if you'd like to learn more!",
            "I personally recommend upgrading — it provides an in-depth review of your information and delivers clear, accurate guidance every time.",
            "I can share much more detailed information with an upgrade. It includes:<br><br>🧠 Unlimited AI guidance 📋 DIY tools for eligibility checks and document preparation 🔄 Regular updates on policy changes<br><br>It's a great option if you like taking charge and doing things yourself!",
            "Many users find upgrading super helpful once they're ready to take the next step — it offers more detailed guidance and saves a lot of time going back and forth.",
            "Whenever you're ready to explore more, upgrading is the best way to get unlimited expert guidance and stay fully up to date. It's designed to make your journey smoother from start to finish."
        ];

            // Cycle through the messages based on message count
            $index = $messageCount % count($promoMessages);
            $message = $promoMessages[$index];

            $nextTier = 'AI Smart Plan';
            $actions = [
                ['label' => 'Upgrade to AI Smart Plan ($79)', 'url' => '/upgrade', 'style' => 'primary'],
            ];
            break;

            case 'all_ai':
                $promoMessages = [
                    "Great question! With the Hybrid Plan, you can get a 2-hour consultation with a licensed migration agent who'll provide personalized guidance.",
                    "I'm glad you're interested! The Hybrid Plan combines AI support with real expert consultation — perfect for getting professional validation.",
                    "Absolutely! The Hybrid Plan gives you everything you have now, plus 2 hours with a registered migration professional for personalized advice."
                ];
                $index = $messageCount % count($promoMessages);
                $message = $promoMessages[$index];
                $nextTier = 'Hybrid Expert Plan';
                $actions = [
                    ['label' => 'Get Expert Consultation ($199)', 'url' => '/upgrade', 'style' => 'primary'],
                ];
                break;

            case 'hybrid':
                $promoMessages = [
                    "Great to hear you're interested! The Premium Plan includes a final expert review before submission — so you can be 100% confident.",
                    "Perfect timing! The Premium Plan gives you everything in Hybrid, plus a comprehensive pre-submission validation by a licensed expert.",
                    "I'm glad you asked! The Premium Plan ensures your application is flawless with a detailed final check before you submit."
                ];
                $index = $messageCount % count($promoMessages);
                $message = $promoMessages[$index];
                $nextTier = 'Premium Confidence Plan';
                $actions = [
                    ['label' => 'Get Final Expert Review ($699)', 'url' => '/upgrade', 'style' => 'primary'],
                ];
                break;

            case 'premium':
                $promoMessages = [
                    "Excellent! The VIP Plan provides full hands-on support — a licensed agent handles your entire application from start to finish.",
                    "Great choice! The VIP Plan gives you complete professional management with ongoing support throughout your entire journey.",
                    "Perfect! The VIP Plan means you can relax — a dedicated migration professional manages everything for you."
                ];
                $index = $messageCount % count($promoMessages);
                $message = $promoMessages[$index];
                $nextTier = 'VIP Global Partner Plan';
                $actions = [
                    ['label' => 'Get Full VIP Support ($999)', 'url' => '/upgrade', 'style' => 'primary'],
                ];
                break;

            case 'vip':
                // VIP users are already at top tier - show appreciation message
                $message = "You're already on our VIP Plan — you have access to all our premium features! How can I help you today?";
                $nextTier = 'VIP (Current)';
                $actions = []; // No upgrade button for VIP
                break;

            default:
                // Fallback for unknown tiers
                $message = "Let me know if you'd like to learn more about our plans!";
                $nextTier = 'Unknown';
                $actions = [
                    ['label' => 'View Plans', 'url' => '/upgrade', 'style' => 'primary'],
                ];
                break;
        }

        return [
            'type' => 'tier_upgrade',
            'message' => $message,
            'actions' => $actions,
            'trigger_name' => 'upgrade_to_' . strtolower(str_replace(' ', '_', $nextTier))
        ];
    }

    /**
     * Detect if user needs human consultation or personalized feedback
     * This is a sophisticated analysis to trigger Hybrid Plan promotion
     *
     * @param string $userQuestion The current user question
     * @param array $sessionState Current session state
     * @param array $stats Conversation statistics
     * @return bool True if human consultation is recommended
     */
    protected function detectsNeedForHumanConsultation($userQuestion, $sessionState, $stats)
    {
        $questionLower = mb_strtolower($userQuestion);
        $score = 0;

        // 1. UNCERTAINTY SIGNALS - User is unsure and needs validation
        $uncertaintyKeywords = [
            'not sure', 'unsure', 'confused', 'confusing', 'uncertain',
            'don\'t know', 'don\'t understand', 'unclear', 'complicated',
            'is this right', 'is this correct', 'am i right', 'is it okay',
            'should i', 'would it be', 'do you think', 'what if',
            'worried', 'concern', 'afraid', 'risky', 'safe',
            'guarantee', 'certain', 'confirm', 'make sure'
        ];
        if ($this->matchesKeywords($questionLower, $uncertaintyKeywords)) {
            $score += 3;
        }

        // 2. DOCUMENT REVIEW REQUESTS - Wants expert eyes on documents
        $documentReviewKeywords = [
            'check my', 'review my', 'look at my', 'verify my',
            'validate', 'assess my', 'evaluate my', 'examine my',
            'correct my', 'feedback on my', 'opinion on my',
            'documents', 'application', 'form', 'statement',
            'letter', 'cv', 'resume', 'evidence', 'proof'
        ];
        if ($this->matchesKeywords($questionLower, $documentReviewKeywords)) {
            $score += 4;
        }

        // 3. PROFESSIONAL VALIDATION NEEDS - Wants expert confirmation
        $validationKeywords = [
            'expert', 'professional', 'agent', 'lawyer', 'solicitor',
            'registered', 'qualified', 'licensed', 'certified',
            'speak to someone', 'talk to someone', 'consult',
            'second opinion', 'human help', 'real person',
            'personalized', 'my case', 'my situation', 'my circumstances'
        ];
        if ($this->matchesKeywords($questionLower, $validationKeywords)) {
            $score += 4;
        }

        // 4. COMPLEXITY INDICATORS - Dealing with complex scenarios
        $complexityKeywords = [
            'but', 'however', 'although', 'except', 'special case',
            'unique situation', 'multiple', 'both', 'either',
            'depends', 'varies', 'different', 'exception',
            'edge case', 'unusual', 'rare', 'specific to',
            'criminal record', 'health condition', 'refusal', 'rejected',
            'appeal', 'character requirement', 'waiver'
        ];
        if ($this->matchesKeywords($questionLower, $complexityKeywords)) {
            $score += 2;
        }

        // 5. URGENCY AND STRESS MARKERS - Time pressure or anxiety
        $urgencyKeywords = [
            'urgent', 'asap', 'quickly', 'deadline', 'running out',
            'expire', 'expiring', 'last minute', 'time sensitive',
            'important', 'critical', 'must', 'need to',
            'stressed', 'anxious', 'nervous', 'panic'
        ];
        if ($this->matchesKeywords($questionLower, $urgencyKeywords)) {
            $score += 2;
        }

        // 6. DECISION-MAKING HELP - Needs guidance on choices
        $decisionKeywords = [
            'which visa', 'what option', 'better to', 'best way',
            'recommend', 'suggestion', 'advice', 'guide me',
            'help me decide', 'choose between', 'which one',
            'pros and cons', 'comparison', 'difference between'
        ];
        if ($this->matchesKeywords($questionLower, $decisionKeywords)) {
            $score += 2;
        }

        // 7. FINANCIAL INVESTMENT CONTEXT - High stakes decision
        $financialKeywords = [
            'invest', 'expensive', 'money', 'cost', 'fee',
            'price', 'afford', 'worth it', 'value',
            'save money', 'budget', 'financial'
        ];
        if ($this->matchesKeywords($questionLower, $financialKeywords)) {
            $score += 1;
        }

        // 8. REPEATED TOPIC DETECTION - User keeps asking about same thing
        // This suggests they need deeper, personalized help
        if ($this->isRepeatedTopic($userQuestion, $sessionState)) {
            $score += 3; // Significant boost for repeated questions
        }

        // Store topic for future detection
        $sessionState = $this->storeQuestionTopic($userQuestion, $sessionState);
        $this->updateSessionState($sessionState);

        // 9. LONG QUESTIONS - Detailed questions often need personalized attention
        $wordCount = str_word_count($userQuestion);
        if ($wordCount > 30) {
            $score += 2;
        }

        // 10. QUESTION MARKS - Multiple questions suggest complexity
        $questionMarkCount = substr_count($userQuestion, '?');
        if ($questionMarkCount >= 2) {
            $score += 1;
        }

        // Threshold: Score of 5 or more triggers human consultation prompt
        // This balanced threshold ensures we catch genuine needs without over-triggering
        return $score >= 5;
    }

    /**
     * Create human consultation prompt for Hybrid Plan
     * Specifically highlights the 2-hour consultation and personalized feedback
     */
    protected function createHumanConsultationPrompt($messageCount, $userQuestion)
    {
        $this->incrementPromptCount();
        $this->recordTrigger('hybrid_expert_consultation');

        // Context-aware messages based on detected needs
        $questionLower = mb_strtolower($userQuestion);

        // Choose message based on question context
        $promoMessages = [];

        // If asking about documents/review
        if ($this->matchesKeywords($questionLower, ['document', 'check', 'review', 'look at', 'verify'])) {
            $promoMessages = [
                "I can see you're looking for detailed feedback on your documents. A registered migration agent can personally review your materials in a 2-hour consultation and provide specific recommendations.",
                "Want professional eyes on your documents? You can get a 2-hour session with a licensed expert who'll review everything and give you personalized feedback on what to improve.",
            ];
        }
        // If expressing uncertainty or concern
        elseif ($this->matchesKeywords($questionLower, ['not sure', 'unsure', 'confused', 'worried', 'concerned', 'right'])) {
            $promoMessages = [
                "I understand this can feel overwhelming. You can get direct access to a registered migration agent for 2 hours — they'll answer all your questions and give you the confidence you need.",
                "When you need that extra reassurance, a 2-hour consultation with a licensed professional can address your specific concerns and provide personalized guidance.",
            ];
        }
        // If asking about complex or specific situations
        elseif ($this->matchesKeywords($questionLower, ['my case', 'my situation', 'specific', 'unique', 'special', 'different'])) {
            $promoMessages = [
                "Your situation sounds unique and deserves personalized attention. A 2-hour consultation with a registered agent can analyze your specific case and provide tailored recommendations.",
                "For cases like yours, personalized expert feedback makes all the difference. You can get 2 hours with a licensed migration professional who understands the nuances of your situation.",
            ];
        }
        // Default messages for general consultation needs
        else {
            $promoMessages = [
                "Looking for expert reassurance? You can combine AI insights with real human expertise — a 2-hour consultation with a registered migration agent provides personalized feedback.",
                "Ready to get professional validation? You can get everything in your current plan plus a 2-hour session with a licensed expert for personalized guidance and peace of mind.",
                "Sometimes you need human expertise. A 2-hour consultation with a registered migration agent or lawyer can review your case and provide personalized recommendations.",
            ];
        }

        // Select message (rotate if we have multiple)
        $index = $messageCount % count($promoMessages);
        $message = $promoMessages[$index];

        $actions = [
            ['label' => 'Get Expert Consultation ($199)', 'url' => '/upgrade', 'style' => 'primary'],
        ];

        return [
            'type' => 'hybrid_expert_consultation',
            'message' => $message,
            'actions' => $actions,
            'trigger_name' => 'hybrid_expert_consultation'
        ];
    }

    /**
     * Detect if Hybrid Expert user needs final validation before submission
     * This triggers Premium Confidence Plan promotion
     *
     * @param string $userQuestion The current user question
     * @param array $sessionState Current session state
     * @param array $stats Conversation statistics
     * @return bool True if final validation is recommended
     */
    protected function detectsNeedForFinalValidation($userQuestion, $sessionState, $stats)
    {
        $questionLower = mb_strtolower($userQuestion);
        $score = 0;

        // 1. PRE-SUBMISSION SIGNALS - User is close to submitting (HIGH WEIGHT)
        $submissionKeywords = [
            'submit', 'submitting', 'submission', 'lodge', 'lodging',
            'apply', 'applying', 'send', 'sending', 'file',
            'ready to', 'about to', 'going to submit', 'planning to submit',
            'before i submit', 'before submission', 'prior to submission',
            'final check', 'last check', 'one more time',
            'almost done', 'nearly finished', 'finishing up'
        ];
        if ($this->matchesKeywords($questionLower, $submissionKeywords)) {
            $score += 5; // Very strong signal - they're at submission stage
        }

        // 2. VALIDATION ANXIETY - Wants confirmation everything is correct (HIGH WEIGHT)
        $validationKeywords = [
            'everything correct', 'everything right', 'all correct', 'all right',
            'double check', 'triple check', 'verify everything', 'final review',
            'make sure', 'confirm', 'validate', 'check again',
            'is this complete', 'have i missed', 'anything missing',
            'covered everything', 'forgot anything', 'overlooked',
            'peace of mind', 'reassurance', 'confidence', 'certain'
        ];
        if ($this->matchesKeywords($questionLower, $validationKeywords)) {
            $score += 4;
        }

        // 3. HIGH-STAKES CONCERN - Worried about rejection/consequences (MEDIUM WEIGHT)
        $stakesKeywords = [
            'rejection', 'reject', 'refused', 'refuse', 'denial', 'deny',
            'mistake', 'error', 'wrong', 'incorrect', 'miss something',
            'screw up', 'mess up', 'ruin', 'fail', 'failure',
            'can\'t afford', 'too important', 'critical', 'crucial',
            'one shot', 'one chance', 'last chance', 'nervous', 'anxious'
        ];
        if ($this->matchesKeywords($questionLower, $stakesKeywords)) {
            $score += 3;
        }

        // 4. COMPLETENESS CHECKS - Questions about having everything (MEDIUM WEIGHT)
        $completenessKeywords = [
            'everything i need', 'all documents', 'complete application',
            'all requirements', 'all the requirements', 'sufficient',
            'enough', 'adequate', 'meets requirements', 'complies',
            'checklist', 'requirements list', 'what else'
        ];
        if ($this->matchesKeywords($questionLower, $completenessKeywords)) {
            $score += 3;
        }

        // 5. PROFESSIONAL FINAL REVIEW REQUEST - Wants expert eyes (HIGH WEIGHT)
        $finalReviewKeywords = [
            'final review', 'final check', 'last review', 'pre-submission review',
            'before i submit', 'expert review', 'professional review',
            'licensed review', 'qualified review', 'independent review',
            'second pair of eyes', 'fresh eyes', 'expert validation'
        ];
        if ($this->matchesKeywords($questionLower, $finalReviewKeywords)) {
            $score += 5; // Very strong signal
        }

        // 6. QUALITY ASSURANCE LANGUAGE - Perfectionist tendencies (MEDIUM WEIGHT)
        $qualityKeywords = [
            'perfect', 'flawless', 'impeccable', 'thorough', 'comprehensive',
            'polished', 'professional', 'best possible', 'highest quality',
            'top quality', 'meticulous', 'detailed', 'precise', 'accurate'
        ];
        if ($this->matchesKeywords($questionLower, $qualityKeywords)) {
            $score += 2;
        }

        // 7. TIMELINE INDICATORS - Near deadline (MEDIUM WEIGHT)
        $timelineKeywords = [
            'deadline', 'due date', 'expires', 'expiring', 'running out',
            'time limit', 'by when', 'how soon', 'when should',
            'this week', 'next week', 'few days', 'tomorrow'
        ];
        if ($this->matchesKeywords($questionLower, $timelineKeywords)) {
            $score += 2;
        }

        // 8. DIY COMPLETION INDICATORS - They've done the work (LOW WEIGHT)
        $diyKeywords = [
            'i\'ve prepared', 'i\'ve completed', 'i\'ve filled', 'i\'ve done',
            'finished preparing', 'ready', 'prepared everything', 'completed',
            'my application', 'my documents', 'my forms'
        ];
        if ($this->matchesKeywords($questionLower, $diyKeywords)) {
            $score += 2;
        }

        // 9. REPEATED FINAL CHECKS - User keeps asking validation questions
        if ($this->isRepeatedTopic($userQuestion, $sessionState)) {
            $score += 2; // Boost for repeated validation questions
        }

        // Store topic for future detection
        $sessionState = $this->storeQuestionTopic($userQuestion, $sessionState);
        $this->updateSessionState($sessionState);

        // 10. QUESTION COMPLEXITY - Detailed final questions (LOW WEIGHT)
        $wordCount = str_word_count($userQuestion);
        if ($wordCount > 25) {
            $score += 1;
        }

        // Threshold: Score of 5 or more triggers Premium Confidence prompt
        // Higher threshold than Hybrid Expert because this is a later-stage need
        return $score >= 5;
    }

    /**
     * Create final validation prompt for Premium Confidence Plan
     * Specifically highlights the pre-submission expert review
     */
    protected function createFinalValidationPrompt($messageCount, $userQuestion)
    {
        $this->incrementPromptCount();
        $this->recordTrigger('premium_confidence_validation');

        // Context-aware messages based on detected needs
        $questionLower = mb_strtolower($userQuestion);

        // Choose message based on question context
        $promoMessages = [];

        // If asking about submission/pre-submission
        if ($this->matchesKeywords($questionLower, ['submit', 'submitting', 'submission', 'lodge', 'ready to', 'about to'])) {
            $promoMessages = [
                "You've done great work preparing everything! 💼 Get a final expert review before you hit submit — so you can be 100% confident your application is flawless.",
                "Almost there! Before you submit, get a comprehensive final review by a licensed expert who'll catch any issues and give you detailed recommendations.",
                "Ready to submit? Ensure every detail is perfect with a thorough pre-submission review by a registered migration professional.",
            ];
        }
        // If expressing validation anxiety or wanting confirmation
        elseif ($this->matchesKeywords($questionLower, ['everything correct', 'make sure', 'double check', 'peace of mind', 'confirm', 'certain'])) {
            $promoMessages = [
                "I understand you want that final peace of mind. A licensed expert can review everything and confirm your application is submission-ready.",
                "For complete confidence, get a detailed pre-submission review where an expert validates every document and requirement before you submit.",
                "Want absolute certainty? Get a comprehensive final check by a registered agent — they'll make sure everything is correct and complete.",
            ];
        }
        // If worried about rejection or mistakes
        elseif ($this->matchesKeywords($questionLower, ['reject', 'mistake', 'error', 'wrong', 'miss', 'fail', 'nervous', 'anxious'])) {
            $promoMessages = [
                "It's natural to be concerned at this stage. A thorough final review by a licensed expert can eliminate that worry by catching any potential issues before submission.",
                "Don't let small mistakes derail your application. Get an expert pre-submission review to identify and fix any issues before they become problems.",
                "You've worked too hard to risk rejection over details. A professional final review can ensure everything meets requirements perfectly.",
            ];
        }
        // If asking about completeness
        elseif ($this->matchesKeywords($questionLower, ['everything i need', 'complete', 'all requirements', 'missing', 'forgot', 'checklist'])) {
            $promoMessages = [
                "Want to be certain you've covered everything? Get a complete application review where an expert checks every requirement and document.",
                "Ensure nothing is missed — a licensed professional can verify you have all required documents and everything is complete before submission.",
                "For a comprehensive completeness check, expert validation of your entire application can ensure every requirement is met.",
            ];
        }
        // Default messages for general final validation needs
        else {
            $promoMessages = [
                "You've done the groundwork — now let's make sure it's flawless. 💼 Get a final expert review before you hit submit.",
                "A final review by a licensed expert means you can submit your application with total confidence.",
                "Almost there! Ensure every document and detail is checked by a professional before you send it off.",
            ];
        }

        // Select message (rotate if we have multiple)
        $index = $messageCount % count($promoMessages);
        $message = $promoMessages[$index];

        $actions = [
            ['label' => 'Get Final Expert Review ($699)', 'url' => '/upgrade', 'style' => 'primary'],
        ];

        return [
            'type' => 'premium_confidence_validation',
            'message' => $message,
            'actions' => $actions,
            'trigger_name' => 'premium_confidence_validation'
        ];
    }

    /**
     * Detect if Premium Confidence user needs full-service support
     * This triggers VIP Global Partner Plan promotion
     *
     * @param string $userQuestion The current user question
     * @param array $sessionState Current session state
     * @param array $stats Conversation statistics
     * @return bool True if full-service support is recommended
     */
    protected function detectsNeedForFullService($userQuestion, $sessionState, $stats)
    {
        $questionLower = mb_strtolower($userQuestion);
        $score = 0;

        // 1. FULL-SERVICE REQUESTS - User explicitly wants someone to handle it (VERY HIGH WEIGHT)
        $fullServiceKeywords = [
            'do it for me', 'do this for me', 'handle everything', 'handle it all',
            'take care of everything', 'full service', 'full support',
            'do the application', 'apply for me', 'complete it for me',
            'someone else to do', 'professional to handle', 'agent to do',
            'you do it', 'can you apply', 'can you handle', 'can you complete'
        ];
        if ($this->matchesKeywords($questionLower, $fullServiceKeywords)) {
            $score += 6; // Highest weight - direct request for full service
        }

        // 2. DIY OVERWHELM - Too complicated/time-consuming (HIGH WEIGHT)
        $overwhelmKeywords = [
            'too complicated', 'too complex', 'too confusing', 'too difficult',
            'too much work', 'overwhelming', 'overwhelmed', 'too hard',
            'can\'t handle', 'struggling with', 'difficult to manage',
            'over my head', 'beyond me', 'too technical', 'don\'t understand'
        ];
        if ($this->matchesKeywords($questionLower, $overwhelmKeywords)) {
            $score += 5;
        }

        // 3. TIME CONSTRAINTS - No time to do it themselves (HIGH WEIGHT)
        $timeConstraintKeywords = [
            'no time', 'don\'t have time', 'too busy', 'very busy',
            'limited time', 'time constraint', 'running out of time',
            'need it done quickly', 'need it fast', 'urgent',
            'can\'t spend time', 'work full time', 'too many things'
        ];
        if ($this->matchesKeywords($questionLower, $timeConstraintKeywords)) {
            $score += 4;
        }

        // 4. ONGOING SUPPORT NEEDS - Wants continuous guidance (MEDIUM WEIGHT)
        $ongoingSupportKeywords = [
            'ongoing support', 'continuous support', 'follow-up', 'follow up',
            'throughout the process', 'entire process', 'start to finish',
            'from beginning to end', 'every step', 'the whole way',
            'regular updates', 'keep me informed', 'stay in touch',
            'check in', 'monitor progress', 'track status'
        ];
        if ($this->matchesKeywords($questionLower, $ongoingSupportKeywords)) {
            $score += 4;
        }

        // 5. HANDS-OFF PREFERENCE - Wants professional to manage (MEDIUM WEIGHT)
        $handsOffKeywords = [
            'hands-off', 'hands off', 'passive role', 'just tell me what to sign',
            'manage it', 'oversee it', 'coordinate', 'organize',
            'take the lead', 'be in charge', 'handle the details',
            'professional management', 'expert handling'
        ];
        if ($this->matchesKeywords($questionLower, $handsOffKeywords)) {
            $score += 3;
        }

        // 6. ELIGIBLE VISA TYPES MENTIONED - VIP available for specific visas (MEDIUM WEIGHT)
        $eligibleVisaKeywords = [
            'student visa', 'student', 'study visa', 'education visa',
            'graduate work', 'graduate visa', 'post-study work', '485 visa',
            'working holiday', 'work and holiday', 'whv', '417 visa', '462 visa',
            'tourist visa', 'visitor visa', 'travel visa', '600 visa',
            'family visa', 'partner visa', 'spouse visa', 'parent visa',
            'prospective marriage', 'de facto'
        ];
        if ($this->matchesKeywords($questionLower, $eligibleVisaKeywords)) {
            $score += 3;
        }

        // 7. RISK AVERSION - Wants to minimize mistakes (MEDIUM WEIGHT)
        $riskAversionKeywords = [
            'minimize risk', 'reduce risk', 'avoid mistakes', 'prevent errors',
            'safest option', 'most secure', 'guaranteed', 'ensure success',
            'highest chance', 'best chance', 'maximize chances',
            'leave nothing to chance', 'no room for error'
        ];
        if ($this->matchesKeywords($questionLower, $riskAversionKeywords)) {
            $score += 3;
        }

        // 8. DELEGATION LANGUAGE - Wants to delegate responsibility (LOW-MEDIUM WEIGHT)
        $delegationKeywords = [
            'leave it to', 'trust you to', 'rely on you', 'depend on',
            'in your hands', 'expert to manage', 'professional to oversee',
            'someone experienced', 'let the expert', 'agent handles'
        ];
        if ($this->matchesKeywords($questionLower, $delegationKeywords)) {
            $score += 2;
        }

        // 9. STRESS/ANXIETY ABOUT DIY - Worried about doing it themselves (LOW-MEDIUM WEIGHT)
        $diyAnxietyKeywords = [
            'worried i\'ll mess up', 'afraid to do it wrong', 'scared to apply',
            'nervous about applying', 'anxious about process',
            'stressed about doing', 'concerned i\'ll make mistakes'
        ];
        if ($this->matchesKeywords($questionLower, $diyAnxietyKeywords)) {
            $score += 2;
        }

        // 10. PREMIUM/QUALITY LANGUAGE - Wants best service (LOW WEIGHT)
        $premiumKeywords = [
            'best service', 'premium service', 'top service', 'highest level',
            'white glove', 'concierge', 'vip', 'all-inclusive',
            'comprehensive service', 'complete package', 'everything included'
        ];
        if ($this->matchesKeywords($questionLower, $premiumKeywords)) {
            $score += 2;
        }

        // 11. REPEATED COMPLEXITY QUESTIONS - User keeps struggling
        if ($this->isRepeatedTopic($userQuestion, $sessionState)) {
            $score += 2; // Boost for repeated questions showing ongoing struggle
        }

        // Store topic for future detection
        $sessionState = $this->storeQuestionTopic($userQuestion, $sessionState);
        $this->updateSessionState($sessionState);

        // 12. QUESTION LENGTH - Detailed questions showing complexity (LOW WEIGHT)
        $wordCount = str_word_count($userQuestion);
        if ($wordCount > 30) {
            $score += 1;
        }

        // Threshold: Score of 5 or more triggers VIP Global prompt
        // Similar threshold to other intelligent triggers
        return $score >= 5;
    }

    /**
     * Create full-service prompt for VIP Global Partner Plan
     * Specifically highlights the hands-on support and professional handling
     */
    protected function createFullServicePrompt($messageCount, $userQuestion)
    {
        $this->incrementPromptCount();
        $this->recordTrigger('vip_global_full_service');

        // Context-aware messages based on detected needs
        $questionLower = mb_strtolower($userQuestion);

        // Choose message based on question context
        $promoMessages = [];

        // If explicitly requesting full service or delegation
        if ($this->matchesKeywords($questionLower, ['do it for me', 'handle everything', 'full service', 'take care', 'can you do', 'can you handle'])) {
            $promoMessages = [
                "Absolutely! 🌏 A licensed migration agent can handle your entire application from start to finish. You just provide the information, and we'll do the rest.",
                "Yes, we can handle everything for you. Get full professional management of your application by a registered agent, with continuous support throughout the process.",
                "Get complete hands-on support where a licensed professional manages your application from start to finish. You can sit back and relax.",
            ];
        }
        // If expressing overwhelm or time constraints
        elseif ($this->matchesKeywords($questionLower, ['too complicated', 'overwhelm', 'no time', 'too busy', 'too much work', 'too hard'])) {
            $promoMessages = [
                "I completely understand — visa applications can be overwhelming. Get full professional handling by a licensed agent who manages everything for you and takes all that stress away.",
                "If it feels like too much, a registered migration professional can handle the entire process while you focus on other priorities.",
                "You don't have to do this alone. Get complete hands-on support from a licensed expert who'll manage your application from beginning to end.",
            ];
        }
        // If asking about ongoing support or follow-up
        elseif ($this->matchesKeywords($questionLower, ['ongoing', 'continuous', 'follow-up', 'start to finish', 'entire process', 'throughout'])) {
            $promoMessages = [
                "For continuous support throughout your journey, get a dedicated licensed agent who stays with you from start to finish, with regular follow-ups and personalized guidance.",
                "Get ongoing professional support from a registered migration agent who guides you through every step and provides continuous follow-ups.",
                "Get a dedicated licensed professional who manages your case from beginning to end with continuous communication and support.",
            ];
        }
        // If mentioning eligible visa types (student, work holiday, etc.)
        elseif ($this->matchesKeywords($questionLower, ['student visa', 'graduate work', 'working holiday', 'tourist visa', 'family visa', 'partner visa'])) {
            $promoMessages = [
                "Great news — full VIP support is available for your visa type! Get full guidance and support from a licensed migration agent who handles everything from start to finish.",
                "For your visa category, get complete professional support from a registered agent, including full application management and continuous follow-ups.",
                "Your visa type is covered with full hands-on support from a licensed professional who'll manage your entire application and provide ongoing guidance.",
            ];
        }
        // Default messages for general full-service needs
        else {
            $promoMessages = [
                "Want full hands-on support? Get a licensed expert from start to finish — you won't have to worry about a thing.",
                "Looking for complete guidance? Get full professional support from a registered agent, plus ongoing updates and follow-ups.",
                "For all-inclusive help, get personal, full-time guidance from migration professionals who handle everything for you.",
            ];
        }

        // Select message (rotate if we have multiple)
        $index = $messageCount % count($promoMessages);
        $message = $promoMessages[$index];

        $actions = [
            ['label' => 'Get Full VIP Support ($999)', 'url' => '/upgrade', 'style' => 'primary'],
        ];

        return [
            'type' => 'vip_global_full_service',
            'message' => $message,
            'actions' => $actions,
            'trigger_name' => 'vip_global_full_service'
        ];
    }

    /**
     * Create study assistance prompt for study-related questions
     */
    protected function createStudyAssistancePrompt()
    {
        $this->incrementPromptCount();
        $this->recordTrigger('study_assistance');

        // Increment study-specific counter
        $state = $this->getSessionState();
        $state['study_prompt_count'] = ($state['study_prompt_count'] ?? 0) + 1;
        $this->updateSessionState($state);

        $message = "If you'd like, we can look into the program and apply for you! Our team can help you find the right course, prepare your application, and guide you through the entire process.";

        $actions = [
            ['label' => 'Get Application Help', 'url' => '/apply', 'style' => 'primary'],
        ];

        return [
            'type' => 'study_assistance',
            'message' => $message,
            'actions' => $actions,
            'trigger_name' => 'study_assistance'
        ];
    }

    /**
     * Get conversation statistics
     */
    protected function getConversationStats()
    {
        $count = DB::table('chat_log')
            ->where('member_id', $this->memberId)
            ->where('type', 'ask')
            ->count();

        return [
            'message_count' => $count,
            'total_messages' => $count * 2 // Ask + reply
        ];
    }

    /**
     * Get session state
     */
    protected function getSessionState()
    {
        return Session::get($this->sessionKey, [
            'prompt_count' => 0,
            'last_triggers' => [],
            'session_message_count' => 0  // Track messages in current session
        ]);
    }

    /**
     * Increment session message count
     */
    protected function incrementSessionMessageCount()
    {
        $state = $this->getSessionState();
        $state['session_message_count'] = ($state['session_message_count'] ?? 0) + 1;
        $this->updateSessionState($state);
        return $state['session_message_count'];
    }

    /**
     * Update session state
     */
    protected function updateSessionState($state)
    {
        Session::put($this->sessionKey, $state);
    }

    /**
     * Increment prompt count
     *
     * @param bool $isGeneric If true, increments generic_prompt_count; otherwise just total count
     */
    protected function incrementPromptCount($isGeneric = false)
    {
        $state = $this->getSessionState();
        $state['prompt_count']++;

        // Track generic prompts separately for limit enforcement
        if ($isGeneric) {
            $state['generic_prompt_count'] = ($state['generic_prompt_count'] ?? 0) + 1;
        }

        $this->updateSessionState($state);
    }

    /**
     * Record that a trigger was shown
     */
    protected function recordTrigger($triggerName)
    {
        $state = $this->getSessionState();
        $stats = $this->getConversationStats();
        $state['last_triggers'][$triggerName] = $stats['message_count'];
        $state['last_trigger_type'] = $triggerName; // Remember last trigger type for follow-ups
        $this->updateSessionState($state);
    }

    /**
     * Format flow response for frontend - matches natural dialog style
     */
    public function formatForFrontend($flowResponse)
    {
        if (empty($flowResponse)) {
            return null;
        }

        // Generate HTML that matches the natural chat dialog style
        $html = '<div class="dialog reply no-avatar">';
        $html .= '<div class="txt">';
        $html .= '<p>' . $flowResponse['message'] . '</p>';

        if (!empty($flowResponse['actions'])) {
            $html .= '<div class="ai-actions" style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">';
            foreach ($flowResponse['actions'] as $action) {
                $style = $action['style'] ?? 'primary';

                // Style buttons to match the chat interface
                if ($style === 'primary') {
                    $buttonStyle = 'display: inline-block; background: #012069; color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold; transition: background 0.3s ease; box-shadow: 0 2px 8px rgba(1, 32, 105, 0.3);';
                } else {
                    $buttonStyle = 'display: inline-block; background: #f1f5f9; color: #475569; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.3s ease; border: 1px solid #cbd5e1;';
                }

                if (isset($action['url'])) {
                    $html .= sprintf(
                        '<a class="ai-btn" href="%s" style="%s">%s</a>',
                        $action['url'],
                        $buttonStyle,
                        $action['label']
                    );
                } elseif (isset($action['action'])) {
                    $html .= sprintf(
                        '<button class="ai-btn" data-action="%s" style="%s; cursor: pointer; border: none;">%s</button>',
                        $action['action'],
                        $buttonStyle,
                        $action['label']
                    );
                }
            }
            $html .= '</div>';
        }

        $html .= '</div>'; // Close .txt
        $html .= '</div>'; // Close .dialog
        $html .= '<div class="clearboth"></div>';

        return $html;
    }

    /**
     * Reset session state (useful for testing or new conversations)
     */
    public function resetSession()
    {
        Session::forget($this->sessionKey);
    }

    /**
     * Store question topic for repeated question detection
     * Helps detect when users keep asking about the same topic
     *
     * @param string $userQuestion The user's question
     * @param array $sessionState Current session state
     * @return array Updated session state with topic stored
     */
    protected function storeQuestionTopic($userQuestion, $sessionState)
    {
        $questionLower = mb_strtolower($userQuestion);
        $topicHash = md5(preg_replace('/[^a-z0-9]/', '', $questionLower));
        $recentTopics = $sessionState['recent_topics'] ?? [];

        $recentTopics[] = $topicHash;
        $recentTopics = array_slice($recentTopics, -5); // Keep last 5 topics
        $sessionState['recent_topics'] = $recentTopics;

        return $sessionState;
    }

    /**
     * Check if a question topic was recently asked
     *
     * @param string $userQuestion The user's question
     * @param array $sessionState Current session state
     * @return bool True if this topic was asked recently
     */
    protected function isRepeatedTopic($userQuestion, $sessionState)
    {
        $questionLower = mb_strtolower($userQuestion);
        $topicHash = md5(preg_replace('/[^a-z0-9]/', '', $questionLower));
        $recentTopics = $sessionState['recent_topics'] ?? [];

        return in_array($topicHash, $recentTopics);
    }

    /**
     * Get system instruction enhancement based on flow context
     * This adds context to the Gemini prompt to make responses flow-aware
     */
    public function getSystemInstructionEnhancement($stats)
    {
        $enhancement = "\n\n[Conversation Context]";
        $enhancement .= "\nMessage count: " . ($stats['message_count'] ?? 0);
        $enhancement .= "\n\nKeep responses concise and natural. Subtly guide users toward relevant services when appropriate.";

        return $enhancement;
    }
}
