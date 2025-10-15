<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Conversation Flow Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the conversation flow rules for the chatbot.
    | Flows help guide users through natural conversations while promoting
    | services and upgrades at appropriate moments.
    |
    */

    'immigration' => [
        // NOTE: Automatic upgrade prompts every 5 messages are handled in ConversationFlowService.php
        // No duplicate triggers needed here
        'triggers' => [],
        'contextual_prompts' => []
    ],

    'study' => [
        // NOTE: Study mode also uses automatic every-5-messages upgrade prompts
        'triggers' => [],
        'contextual_prompts' => []
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Settings
    |--------------------------------------------------------------------------
    */
    'settings' => [
        // How often to show promotional messages (every X messages)
        'promotion_frequency' => 5,

        // Cooldown between same type of prompts (in messages)
        'prompt_cooldown' => 10,

        // Track user interactions
        'track_interactions' => true,

        // Maximum prompts per session (increased to show more throughout long conversations)
        'max_prompts_per_session' => 10,
    ]
];
