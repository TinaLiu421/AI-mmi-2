<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

/**
 * Token_Guide — public explainer page for the AI-mmi token system.
 * Accessible to both guests and logged-in users (no auth required).
 *
 * Route: GET /{lang}/token_guide
 */
class Token_Guide extends WebController
{
    public function index()
    {
        $this->pageMeta([
            'title'       => 'How Credits Work — AI-mmi',
            'description' => 'Earn free AI-mmi credits by signing up, logging in daily, and completing your profile. Use them to power your AI study and migration journey.',
        ]);

        return $this->pageData([])->pageView();
    }
}
