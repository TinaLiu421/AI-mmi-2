<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Conversation Flow Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the conversation flow rules for the unified chatbot.
    | Flows help guide users through natural conversations while promoting
    | services and upgrades at appropriate moments.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Global Settings
    |--------------------------------------------------------------------------
    */
    'settings' => [
        // Maximum GENERIC prompts per session (prevents overwhelming users)
        // NOTE: These limits apply ONLY to generic periodic/keyword prompts
        // Intelligent scoring-based triggers (Hybrid Expert, Study Assistance) have NO LIMIT
        // since they only trigger on genuine detected needs
        'max_prompts_per_session' => 10,  // For free users (generic periodic prompts)
        'max_prompts_per_session_paid' => 4,  // For paid users (generic keyword-triggered prompts)
    ],

    /*
    |--------------------------------------------------------------------------
    | Hybrid Expert Plan Trigger Rules
    |--------------------------------------------------------------------------
    |
    | These rules define when to intelligently trigger Hybrid Expert Plan
    | promotion for AI Smart Plan users who need human consultation.
    |
    | The detection system uses a scoring mechanism with a threshold of 5 points.
    | Multiple signals can combine to trigger the promotion.
    |
    */
    'hybrid_expert_triggers' => [
        // COOLDOWN: Minimum messages between triggers
        'cooldown_messages' => 7,

        // SCORING THRESHOLD: Total score needed to trigger
        'score_threshold' => 5,

        // KEYWORD CATEGORIES AND THEIR SCORES
        'uncertainty_signals' => [
            'score' => 3,
            'keywords' => [
                'not sure', 'unsure', 'confused', 'confusing', 'uncertain',
                'don\'t know', 'don\'t understand', 'unclear', 'complicated',
                'is this right', 'is this correct', 'am i right', 'is it okay',
                'should i', 'would it be', 'do you think', 'what if',
                'worried', 'concern', 'afraid', 'risky', 'safe',
                'guarantee', 'certain', 'confirm', 'make sure'
            ]
        ],

        'document_review_requests' => [
            'score' => 4,
            'keywords' => [
                'check my', 'review my', 'look at my', 'verify my',
                'validate', 'assess my', 'evaluate my', 'examine my',
                'correct my', 'feedback on my', 'opinion on my',
                'documents', 'application', 'form', 'statement',
                'letter', 'cv', 'resume', 'evidence', 'proof'
            ]
        ],

        'professional_validation_needs' => [
            'score' => 4,
            'keywords' => [
                'expert', 'professional', 'agent', 'lawyer', 'solicitor',
                'registered', 'qualified', 'licensed', 'certified',
                'speak to someone', 'talk to someone', 'consult',
                'second opinion', 'human help', 'real person',
                'personalized', 'my case', 'my situation', 'my circumstances'
            ]
        ],

        'complexity_indicators' => [
            'score' => 2,
            'keywords' => [
                'but', 'however', 'although', 'except', 'special case',
                'unique situation', 'multiple', 'both', 'either',
                'depends', 'varies', 'different', 'exception',
                'edge case', 'unusual', 'rare', 'specific to',
                'criminal record', 'health condition', 'refusal', 'rejected',
                'appeal', 'character requirement', 'waiver'
            ]
        ],

        'urgency_stress_markers' => [
            'score' => 2,
            'keywords' => [
                'urgent', 'asap', 'quickly', 'deadline', 'running out',
                'expire', 'expiring', 'last minute', 'time sensitive',
                'important', 'critical', 'must', 'need to',
                'stressed', 'anxious', 'nervous', 'panic'
            ]
        ],

        'decision_making_help' => [
            'score' => 2,
            'keywords' => [
                'which visa', 'what option', 'better to', 'best way',
                'recommend', 'suggestion', 'advice', 'guide me',
                'help me decide', 'choose between', 'which one',
                'pros and cons', 'comparison', 'difference between'
            ]
        ],

        'financial_investment_context' => [
            'score' => 1,
            'keywords' => [
                'invest', 'expensive', 'money', 'cost', 'fee',
                'price', 'afford', 'worth it', 'value',
                'save money', 'budget', 'financial'
            ]
        ],

        // BEHAVIORAL SIGNALS
        'behavioral_triggers' => [
            'repeated_topic' => 3,          // Score when user asks similar question again
            'long_question' => 2,           // Score when question exceeds 30 words
            'multiple_questions' => 1,      // Score when 2+ question marks in message
        ],

        // CONTEXT-AWARE MESSAGING
        // Different messages are shown based on detected user intent
        'context_categories' => [
            'document_review' => ['document', 'check', 'review', 'look at', 'verify'],
            'uncertainty' => ['not sure', 'unsure', 'confused', 'worried', 'concerned', 'right'],
            'complex_case' => ['my case', 'my situation', 'specific', 'unique', 'special', 'different'],
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Premium Confidence Plan Trigger Rules
    |--------------------------------------------------------------------------
    |
    | These rules define when to trigger Premium Confidence Plan promotion
    | for Hybrid Expert users who are ready to submit and need final validation.
    |
    | The detection system uses a scoring mechanism with a threshold of 5 points.
    | Focus is on pre-submission stage and validation anxiety.
    |
    */
    'premium_confidence_triggers' => [
        // COOLDOWN: Minimum messages between triggers
        'cooldown_messages' => 7,

        // SCORING THRESHOLD: Total score needed to trigger
        'score_threshold' => 5,

        // KEYWORD CATEGORIES AND THEIR SCORES
        'pre_submission_signals' => [
            'score' => 5,
            'keywords' => [
                'submit', 'submitting', 'submission', 'lodge', 'lodging',
                'apply', 'applying', 'send', 'sending', 'file',
                'ready to', 'about to', 'going to submit', 'planning to submit',
                'before i submit', 'before submission', 'prior to submission',
                'final check', 'last check', 'one more time',
                'almost done', 'nearly finished', 'finishing up'
            ]
        ],

        'validation_anxiety' => [
            'score' => 4,
            'keywords' => [
                'everything correct', 'everything right', 'all correct', 'all right',
                'double check', 'triple check', 'verify everything', 'final review',
                'make sure', 'confirm', 'validate', 'check again',
                'is this complete', 'have i missed', 'anything missing',
                'covered everything', 'forgot anything', 'overlooked',
                'peace of mind', 'reassurance', 'confidence', 'certain'
            ]
        ],

        'high_stakes_concern' => [
            'score' => 3,
            'keywords' => [
                'rejection', 'reject', 'refused', 'refuse', 'denial', 'deny',
                'mistake', 'error', 'wrong', 'incorrect', 'miss something',
                'screw up', 'mess up', 'ruin', 'fail', 'failure',
                'can\'t afford', 'too important', 'critical', 'crucial',
                'one shot', 'one chance', 'last chance', 'nervous', 'anxious'
            ]
        ],

        'completeness_checks' => [
            'score' => 3,
            'keywords' => [
                'everything i need', 'all documents', 'complete application',
                'all requirements', 'all the requirements', 'sufficient',
                'enough', 'adequate', 'meets requirements', 'complies',
                'checklist', 'requirements list', 'what else'
            ]
        ],

        'final_review_requests' => [
            'score' => 5,
            'keywords' => [
                'final review', 'final check', 'last review', 'pre-submission review',
                'before i submit', 'expert review', 'professional review',
                'licensed review', 'qualified review', 'independent review',
                'second pair of eyes', 'fresh eyes', 'expert validation'
            ]
        ],

        'quality_assurance' => [
            'score' => 2,
            'keywords' => [
                'perfect', 'flawless', 'impeccable', 'thorough', 'comprehensive',
                'polished', 'professional', 'best possible', 'highest quality',
                'top quality', 'meticulous', 'detailed', 'precise', 'accurate'
            ]
        ],

        'timeline_indicators' => [
            'score' => 2,
            'keywords' => [
                'deadline', 'due date', 'expires', 'expiring', 'running out',
                'time limit', 'by when', 'how soon', 'when should',
                'this week', 'next week', 'few days', 'tomorrow'
            ]
        ],

        'diy_completion' => [
            'score' => 2,
            'keywords' => [
                'i\'ve prepared', 'i\'ve completed', 'i\'ve filled', 'i\'ve done',
                'finished preparing', 'ready', 'prepared everything', 'completed',
                'my application', 'my documents', 'my forms'
            ]
        ],

        // BEHAVIORAL SIGNALS
        'behavioral_triggers' => [
            'repeated_validation' => 2,     // Score when user asks similar validation question again
            'long_question' => 1,           // Score when question exceeds 25 words
        ],

        // CONTEXT-AWARE MESSAGING
        'context_categories' => [
            'submission_stage' => ['submit', 'submitting', 'submission', 'lodge', 'ready to', 'about to'],
            'validation_needs' => ['everything correct', 'make sure', 'double check', 'peace of mind', 'confirm', 'certain'],
            'rejection_fear' => ['reject', 'mistake', 'error', 'wrong', 'miss', 'fail', 'nervous', 'anxious'],
            'completeness' => ['everything i need', 'complete', 'all requirements', 'missing', 'forgot', 'checklist'],
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | VIP Global Partner Plan Trigger Rules
    |--------------------------------------------------------------------------
    |
    | These rules define when to trigger VIP Global Partner Plan promotion
    | for Premium Confidence users who want full-service professional handling.
    |
    | The detection system uses a scoring mechanism with a threshold of 5 points.
    | Focus is on DIY overwhelm, delegation needs, and desire for hands-on support.
    |
    */
    'vip_global_triggers' => [
        // COOLDOWN: Minimum messages between triggers
        'cooldown_messages' => 7,

        // SCORING THRESHOLD: Total score needed to trigger
        'score_threshold' => 5,

        // KEYWORD CATEGORIES AND THEIR SCORES
        'full_service_requests' => [
            'score' => 6,
            'keywords' => [
                'do it for me', 'do this for me', 'handle everything', 'handle it all',
                'take care of everything', 'full service', 'full support',
                'do the application', 'apply for me', 'complete it for me',
                'someone else to do', 'professional to handle', 'agent to do',
                'you do it', 'can you apply', 'can you handle', 'can you complete'
            ]
        ],

        'diy_overwhelm' => [
            'score' => 5,
            'keywords' => [
                'too complicated', 'too complex', 'too confusing', 'too difficult',
                'too much work', 'overwhelming', 'overwhelmed', 'too hard',
                'can\'t handle', 'struggling with', 'difficult to manage',
                'over my head', 'beyond me', 'too technical', 'don\'t understand'
            ]
        ],

        'time_constraints' => [
            'score' => 4,
            'keywords' => [
                'no time', 'don\'t have time', 'too busy', 'very busy',
                'limited time', 'time constraint', 'running out of time',
                'need it done quickly', 'need it fast', 'urgent',
                'can\'t spend time', 'work full time', 'too many things'
            ]
        ],

        'ongoing_support_needs' => [
            'score' => 4,
            'keywords' => [
                'ongoing support', 'continuous support', 'follow-up', 'follow up',
                'throughout the process', 'entire process', 'start to finish',
                'from beginning to end', 'every step', 'the whole way',
                'regular updates', 'keep me informed', 'stay in touch',
                'check in', 'monitor progress', 'track status'
            ]
        ],

        'hands_off_preference' => [
            'score' => 3,
            'keywords' => [
                'hands-off', 'hands off', 'passive role', 'just tell me what to sign',
                'manage it', 'oversee it', 'coordinate', 'organize',
                'take the lead', 'be in charge', 'handle the details',
                'professional management', 'expert handling'
            ]
        ],

        'eligible_visa_types' => [
            'score' => 3,
            'keywords' => [
                'student visa', 'student', 'study visa', 'education visa',
                'graduate work', 'graduate visa', 'post-study work', '485 visa',
                'working holiday', 'work and holiday', 'whv', '417 visa', '462 visa',
                'tourist visa', 'visitor visa', 'travel visa', '600 visa',
                'family visa', 'partner visa', 'spouse visa', 'parent visa',
                'prospective marriage', 'de facto'
            ]
        ],

        'risk_aversion' => [
            'score' => 3,
            'keywords' => [
                'minimize risk', 'reduce risk', 'avoid mistakes', 'prevent errors',
                'safest option', 'most secure', 'guaranteed', 'ensure success',
                'highest chance', 'best chance', 'maximize chances',
                'leave nothing to chance', 'no room for error'
            ]
        ],

        'delegation_language' => [
            'score' => 2,
            'keywords' => [
                'leave it to', 'trust you to', 'rely on you', 'depend on',
                'in your hands', 'expert to manage', 'professional to oversee',
                'someone experienced', 'let the expert', 'agent handles'
            ]
        ],

        'diy_anxiety' => [
            'score' => 2,
            'keywords' => [
                'worried i\'ll mess up', 'afraid to do it wrong', 'scared to apply',
                'nervous about applying', 'anxious about process',
                'stressed about doing', 'concerned i\'ll make mistakes'
            ]
        ],

        'premium_service' => [
            'score' => 2,
            'keywords' => [
                'best service', 'premium service', 'top service', 'highest level',
                'white glove', 'concierge', 'vip', 'all-inclusive',
                'comprehensive service', 'complete package', 'everything included'
            ]
        ],

        // BEHAVIORAL SIGNALS
        'behavioral_triggers' => [
            'repeated_struggle' => 2,       // Score when user asks similar complex question again
            'long_question' => 1,           // Score when question exceeds 30 words
        ],

        // CONTEXT-AWARE MESSAGING
        'context_categories' => [
            'explicit_request' => ['do it for me', 'handle everything', 'full service', 'take care', 'can you do', 'can you handle'],
            'overwhelm' => ['too complicated', 'overwhelm', 'no time', 'too busy', 'too much work', 'too hard'],
            'ongoing_support' => ['ongoing', 'continuous', 'follow-up', 'start to finish', 'entire process', 'throughout'],
            'eligible_visas' => ['student visa', 'graduate work', 'working holiday', 'tourist visa', 'family visa', 'partner visa'],
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Study Assistance Trigger Rules
    |--------------------------------------------------------------------------
    */
    'study_assistance_triggers' => [
        'cooldown_messages' => 10,
        'max_prompts_per_session' => 2,
        'keywords' => [
            'scholarship', 'university', 'college', 'course', 'program',
            'degree', 'bachelor', 'master', 'phd', 'student visa',
            'study', 'admission', 'enrollment', 'enroll', 'apply', 'application'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Upgrade Prompt Trigger Rules
    |--------------------------------------------------------------------------
    */
    'upgrade_triggers' => [
        'free_users' => [
            'trigger_type' => 'periodic',
            'interval_messages' => 5,       // Prompt every 5 messages
            'min_cooldown' => 3,            // At least 3 messages after last trigger
        ],
        'paid_users' => [
            'trigger_type' => 'keyword_only',
            'cooldown_messages' => 5,
            'keywords' => [
                'upgrade', 'help', 'premium', 'expert', 'agent', 'lawyer',
                'consultation', 'review', 'professional',
                'ai smart', 'ai smart plan', 'hybrid', 'hybrid expert',
                'hybrid expert plan', 'confidence', 'premium confidence',
                'premium confidence plan', 'vip', 'vip global',
                'vip global partner', 'subscription', 'plan'
            ]
        ]
    ]
];
