<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ConversationFlowService
{
    protected $flows;
    protected $settings;
    protected $memberId;
    protected $chatMode;
    protected $sessionKey;

    public function __construct($memberId, $chatMode = 'immigration')
    {
        $this->flows = config('conversation_flows');
        $this->settings = $this->flows['settings'] ?? [];
        $this->memberId = $memberId;
        $this->chatMode = $chatMode;
        $this->sessionKey = "conversation_flow_{$memberId}_{$chatMode}";
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
        // Get current session state
        $sessionState = $this->getSessionState();

        // Check if we've reached max prompts for this session
        if ($sessionState['prompt_count'] >= ($this->settings['max_prompts_per_session'] ?? 10)) {
            return null;
        }

        // Get conversation statistics
        $stats = $this->getConversationStats();

        // Check subscription tier
        $subscriptionTier = $userProfile['subscription_tier'] ?? 'free'; // free, ai_smart, hybrid_expert, premium_confidence, vip_global

        // Check for study-related keywords (only for free and AI Smart Plan users)
        if ($subscriptionTier === 'free' || $subscriptionTier === 'ai_smart') {
            $studyKeywords = [
                'scholarship',
                'university',
                'college',
                'course',
                'program',
                'degree',
                'bachelor',
                'master',
                'phd',
                'student visa',
                'study',
                'admission',
                'enrollment',
                'enroll',
                'apply',
                'application'
            ];
            $hasStudyKeyword = $this->matchesKeywords($userQuestion, $studyKeywords);

            if ($hasStudyKeyword) {
                // Check cooldown to avoid showing too frequently
                $lastStudyTrigger = $sessionState['last_triggers']['study_assistance'] ?? 0;
                if (($stats['message_count'] - $lastStudyTrigger) >= 5) {
                    return $this->createStudyAssistancePrompt();
                }
            }
        }

        // For FREE users: prompt every 5 messages
        if ($subscriptionTier === 'free' && $stats['message_count'] % 5 === 0 && $stats['message_count'] >= 5) {
            $lastAnyTrigger = !empty($sessionState['last_triggers']) ? max($sessionState['last_triggers']) : 0;
            if (($stats['message_count'] - $lastAnyTrigger) >= 3) {
                return $this->createUpgradePrompt($subscriptionTier, $stats['message_count'], $userQuestion);
            }
        }

        // For PAID users (lower tiers): ONLY trigger when keywords are mentioned
        if ($subscriptionTier !== 'free' && $subscriptionTier !== 'vip_global') {
            // Check for upgrade keywords including plan names
            $upgradeKeywords = [
                'upgrade',
                'help',
                'premium',
                'expert',
                'agent',
                'lawyer',
                'consultation',
                'review',
                'professional',
                'ai smart',
                'ai smart plan',
                'hybrid',
                'hybrid expert',
                'hybrid expert plan',
                'confidence',
                'premium confidence',
                'premium confidence plan',
                'vip',
                'vip global',
                'vip global partner',
                'subscription',
                'plan'
            ];
            $hasUpgradeKeyword = $this->matchesKeywords($userQuestion, $upgradeKeywords);

            // Trigger ONLY when upgrade keywords are mentioned (not periodic)
            if ($hasUpgradeKeyword) {
                $lastAnyTrigger = !empty($sessionState['last_triggers']) ? max($sessionState['last_triggers']) : 0;
                // Cooldown of 5 messages to prevent spam
                if (($stats['message_count'] - $lastAnyTrigger) >= 5) {
                    return $this->createUpgradePrompt($subscriptionTier, $stats['message_count'], $userQuestion);
                }
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
     * Create upgrade prompt based on subscription tier
     */
    protected function createUpgradePrompt($currentTier, $messageCount, $userQuestion)
    {
        $this->incrementPromptCount();
        $this->recordTrigger('upgrade_prompt_' . $currentTier);

        $message = '';
        $actions = [];
        $nextTier = '';

        switch ($currentTier) {
            case 'free':
                $promoMessages = [
            "By the way, with <strong>AI Smart Plan ($79/6 months)</strong>, you get unlimited AI guidance and the latest updates. Let me know if you'd like to learn more!",
            "I personally recommend the <strong>AI Smart Plan ($79 for 6 months)</strong> — it provides an in-depth review of your information and delivers clear, accurate guidance every time.",
            "I can share much more detailed information with the <strong>AI Smart Plan ($79 for 6 months)</strong>. It includes:<br><br>🧠 Unlimited AI guidance 📋 DIY tools for eligibility checks and document preparation 🔄 Regular updates on policy changes<br><br>It’s a great option if you like taking charge and doing things yourself!",
            "Many users find the <strong>AI Smart Plan</strong> super helpful once they’re ready to take the next step — it offers more detailed guidance and saves a lot of time going back and forth.",
            "Whenever you’re ready to explore more, the <strong>AI Smart Plan ($79 for 6 months)</strong> is the best way to get unlimited expert guidance and stay fully up to date. It’s designed to make your journey smoother from start to finish."
        ];

            // Cycle through the messages based on message count
            $index = $messageCount % count($promoMessages);
            $message = $promoMessages[$index];

            $nextTier = 'AI Smart Plan';
            $actions = [
                ['label' => 'Upgrade to AI Smart Plan ($79)', 'url' => '/upgrade', 'style' => 'primary'],
            ];
            break;

            case 'ai_smart':
            // AI Smart → Hybrid Expert ($199)
            $promoMessages = [
                "Looking for extra reassurance? The <strong>Hybrid Expert Plan ($199)</strong> combines AI insights with real human expertise — so you can make decisions with full confidence.",
                "Ready to take it up a notch? Upgrade to the <strong>Hybrid Expert Plan ($199)</strong> — it includes everything from the AI Smart Plan plus a 2-hour consultation with a registered migration expert.",
                "Sometimes it helps to have a professional double-check your progress. With the <strong>Hybrid Expert Plan ($199)</strong>, you’ll get AI speed and human accuracy — the perfect balance.",
                "If you’d like expert eyes on your documents, the <strong>Hybrid Expert Plan ($199)</strong> gives you a 2-hour session with a licensed migration agent or lawyer — along with all your AI Smart benefits.",
                "For the best of both worlds, try the <strong>Hybrid Expert Plan ($199)</strong>. You’ll keep all the AI Smart features and add expert validation, feedback, and peace of mind."
            ];

            $index = $messageCount % count($promoMessages);
            $message = $promoMessages[$index];

            $nextTier = 'Hybrid Expert Plan';
            $actions = [
                ['label' => 'Upgrade to Hybrid Expert ($199)', 'url' => '/upgrade', 'style' => 'primary'],
            ];
            break;

            case 'hybrid_expert':
                // Hybrid Expert → Premium Confidence ($699)
                $promoMessages = [
                "You’ve done the groundwork — now let’s make sure it’s flawless. 💼 The <strong>Premium Confidence Plan ($699)</strong> gives you a final expert review before you hit submit.",
                "Almost there! The <strong>Premium Confidence Plan ($699)</strong> includes a final review by a licensed expert, so you can submit your application with total confidence.",
                "If you’re close to submitting, the <strong>Premium Confidence Plan ($699)</strong> ensures every document and detail is checked by a professional before you send it off.",
                "You’ve done the smart work — now let’s add that final expert layer. <strong>Premium Confidence Plan ($699)</strong> gives you a complete pre-submission review and expert feedback.",
                "Want peace of mind before submission? The <strong>Premium Confidence Plan ($699)</strong> covers a detailed expert review to make sure your application is solid and ready to go."
            ];

            $index = $messageCount % count($promoMessages);
            $message = $promoMessages[$index];

            $nextTier = 'Premium Confidence Plan';
            $actions = [
                ['label' => 'Upgrade to Premium Confidence ($699)', 'url' => '/upgrade', 'style' => 'primary'],
            ];
            break;

            case 'premium_confidence':
                // Premium Confidence → VIP Global Partner ($999)
                $promoMessages = [
                "Want full hands-on support? The <strong>VIP Global Partner Plan ($999)</strong> gives you a licensed expert from start to finish — you won’t have to worry about a thing.",
                "Looking for complete guidance? The <strong>VIP Global Partner Plan ($999)</strong> includes full professional support from a registered agent, plus ongoing updates and follow-ups.",
                "If you want to leave nothing to chance, the <strong>VIP Global Partner Plan ($999)</strong> gives you personal, full-time guidance from migration professionals.",
                "This is the ultimate peace-of-mind plan — <strong>VIP Global Partner ($999)</strong> offers full expert support and continuous follow-ups for a smooth journey.",
                "Need all-inclusive help? <strong>VIP Global Partner Plan ($999)</strong> is your all-in-one solution with full expert handling for student, work, or family visas."
            ];  
                $index = $messageCount % count($promoMessages);
                $message = $promoMessages[$index];

                $nextTier = 'VIP Global Partner Plan';
                $actions = [
                    ['label' => 'Upgrade to VIP Global ($999)', 'url' => '/account_registration', 'style' => 'primary'],
                ];
                break;

            case 'vip_global':
                // Already on highest tier - no upgrade needed
                return null;
        }

        return [
            'type' => 'tier_upgrade',
            'message' => $message,
            'actions' => $actions,
            'trigger_name' => 'upgrade_to_' . strtolower(str_replace(' ', '_', $nextTier))
        ];
    }

    /**
     * Create study assistance prompt for study-related questions
     */
    protected function createStudyAssistancePrompt()
    {
        $this->incrementPromptCount();
        $this->recordTrigger('study_assistance');

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
            ->where('chat_mode', $this->chatMode)
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
            'last_triggers' => []
        ]);
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
     */
    protected function incrementPromptCount()
    {
        $state = $this->getSessionState();
        $state['prompt_count']++;
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
     * Get system instruction enhancement based on flow context
     * This adds context to the Gemini prompt to make responses flow-aware
     */
    public function getSystemInstructionEnhancement($stats)
    {
        $enhancement = "\n\n[Conversation Context]";
        $enhancement .= "\nMessage count: " . ($stats['message_count'] ?? 0);

        // Add flow-aware instructions
        $modeFlows = $this->flows[$this->chatMode] ?? [];
        $contextualPrompts = $modeFlows['contextual_prompts'] ?? [];

        if (!empty($contextualPrompts)) {
            $enhancement .= "\n\nWhen responding, be aware that you can suggest these services:";
            foreach ($contextualPrompts as $promptName => $prompt) {
                $keywords = implode(', ', $prompt['keywords'] ?? []);
                $enhancement .= "\n- For topics about [{$keywords}]: " . ($prompt['message'] ?? '');
            }
        }

        $enhancement .= "\n\nKeep responses concise and natural. Subtly guide users toward relevant services when appropriate.";

        return $enhancement;
    }
}
