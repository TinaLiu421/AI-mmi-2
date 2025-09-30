<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Member;

class CheckSubscriptionFeature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $feature  Feature to check (e.g., 'ai_consultation', 'human_agent_hours', 'validation_check')
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $feature = null)
    {
        $member = $request->attributes->get('current_member') ?? session('current_member');

        if (!$member) {
            return redirect()->route('account_login')->with('error', 'Please login to continue');
        }

        if ($feature) {
            $memberModel = new Member([]);
            $hasAccess = $memberModel->hasFeatureAccess($member['id'], $feature);

            if (!$hasAccess) {
                return redirect('/upgrade')->with('error', 'Please upgrade your plan to access this feature');
            }
        }

        return $next($request);
    }
}