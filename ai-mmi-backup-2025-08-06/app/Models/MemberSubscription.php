<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MemberSubscription extends Model
{
    protected $table = 'member_subscriptions';

    protected $fillable = [
        'member_id',
        'subscription_plan_id',
        'payment_id',
        'stripe_subscription_id',
        'stripe_customer_id',
        'status',
        'started_at',
        'expires_at',
        'canceled_at',
        'migration_questions_used',
        'education_questions_used',
        'human_agent_hours_used',
        'program_applications_used',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'canceled_at' => 'datetime',
        'metadata' => 'array',
        'human_agent_hours_used' => 'decimal:2',
    ];

    /**
     * Check if subscription is active
     */
    public function isActive()
    {
        if (!in_array($this->status, ['active', 'trialing'])) {
            return false;
        }

        // Check expiration
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if can ask question in a mode
     */
    public function canAskQuestion($mode = 'migration')
    {
        if (!$this->isActive()) {
            return false;
        }

        $plan = $this->plan;
        if (!$plan) {
            return false;
        }

        $limit = $plan->getQuestionLimit($mode);
        $used = $mode === 'migration'
            ? $this->migration_questions_used
            : $this->education_questions_used;

        return $used < $limit;
    }

    /**
     * Get remaining questions for a mode
     */
    public function getRemainingQuestions($mode = 'migration')
    {
        $plan = $this->plan;
        if (!$plan) {
            return 0;
        }

        $limit = $plan->getQuestionLimit($mode);
        $used = $mode === 'migration'
            ? $this->migration_questions_used
            : $this->education_questions_used;

        if ($limit === PHP_INT_MAX) {
            return -1; // Unlimited
        }

        return max(0, $limit - $used);
    }

    /**
     * Increment question usage
     */
    public function incrementQuestionUsage($mode = 'migration')
    {
        $field = $mode === 'migration'
            ? 'migration_questions_used'
            : 'education_questions_used';

        $this->increment($field);
        return $this;
    }

    /**
     * Check if can use human agent hours
     */
    public function canUseHumanAgent($hoursNeeded = 0)
    {
        if (!$this->isActive()) {
            return false;
        }

        $plan = $this->plan;
        if (!$plan) {
            return false;
        }

        $limit = $plan->getHumanAgentHours();
        $used = $this->human_agent_hours_used;

        return ($used + $hoursNeeded) <= $limit;
    }

    /**
     * Get remaining human agent hours
     */
    public function getRemainingHumanAgentHours()
    {
        $plan = $this->plan;
        if (!$plan) {
            return 0;
        }

        $limit = $plan->getHumanAgentHours();
        $used = $this->human_agent_hours_used;

        if ($limit === PHP_INT_MAX) {
            return -1; // Unlimited
        }

        return max(0, $limit - $used);
    }

    /**
     * Add human agent hours usage
     */
    public function addHumanAgentHours($hours)
    {
        $this->human_agent_hours_used += $hours;
        $this->save();
        return $this;
    }

    /**
     * Check if has AI consultation access
     */
    public function hasAiConsultation()
    {
        return $this->isActive() && $this->plan && $this->plan->hasAiConsultation();
    }

    /**
     * Check if has validation check
     */
    public function hasValidationCheck()
    {
        return $this->isActive() && $this->plan && $this->plan->hasValidationCheck();
    }

    /**
     * Check if has full service
     */
    public function hasFullService()
    {
        return $this->isActive() && $this->plan && $this->plan->hasFullService();
    }

    /**
     * Check if visa type is allowed
     */
    public function isVisaTypeAllowed($visaType)
    {
        return $this->isActive() && $this->plan && $this->plan->isVisaTypeAllowed($visaType);
    }

    /**
     * Get days until expiration
     */
    public function getDaysUntilExpiration()
    {
        if (!$this->expires_at) {
            return null; // No expiration
        }

        return max(0, Carbon::now()->diffInDays($this->expires_at, false));
    }

    /**
     * Check if expiring soon (within 7 days)
     */
    public function isExpiringSoon($days = 7)
    {
        $daysLeft = $this->getDaysUntilExpiration();
        return $daysLeft !== null && $daysLeft <= $days && $daysLeft > 0;
    }

    /**
     * Relationships
     */
    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}