<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $table = 'subscription_plans';

    protected $fillable = [
        'name',
        'slug',
        'service_type',
        'price',
        'currency',
        'duration_months',
        'stripe_price_id',
        'stripe_product_id',
        'features',
        'display_order',
        'is_active',
        'description',
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get feature value
     */
    public function getFeature($key, $default = null)
    {
        return $this->features[$key] ?? $default;
    }

    /**
     * Check if feature is enabled
     */
    public function hasFeature($key)
    {
        return !empty($this->features[$key]);
    }

    /**
     * Check if plan allows unlimited of a resource
     */
    public function isUnlimited($key)
    {
        $value = $this->getFeature($key, 0);
        return $value === -1 || $value === null;
    }

    /**
     * Get question limit for a mode
     */
    public function getQuestionLimit($mode = 'migration')
    {
        $key = $mode . '_questions_limit';
        $limit = $this->getFeature($key, 0);
        return $limit === -1 ? PHP_INT_MAX : $limit;
    }

    /**
     * Get human agent hours limit
     */
    public function getHumanAgentHours()
    {
        $hours = $this->getFeature('human_agent_hours', 0);
        return $hours === -1 ? PHP_INT_MAX : $hours;
    }

    /**
     * Check if AI consultation is allowed
     */
    public function hasAiConsultation()
    {
        return $this->getFeature('ai_consultation', false) === true;
    }

    /**
     * Check if validation check is included
     */
    public function hasValidationCheck()
    {
        return $this->getFeature('validation_check', false) === true;
    }

    /**
     * Check if full service is included
     */
    public function hasFullService()
    {
        return $this->getFeature('full_service', false) === true;
    }

    /**
     * Get allowed visa types
     */
    public function getAllowedVisaTypes()
    {
        return $this->getFeature('allowed_visa_types', []);
    }

    /**
     * Check if visa type is allowed
     */
    public function isVisaTypeAllowed($visaType)
    {
        $allowed = $this->getAllowedVisaTypes();
        return empty($allowed) || in_array($visaType, $allowed);
    }

    /**
     * Get program applications limit
     */
    public function getProgramApplicationsLimit()
    {
        $limit = $this->getFeature('program_applications', 0);
        return $limit === -1 ? PHP_INT_MAX : $limit;
    }

    /**
     * Static: Get free plan
     */
    public static function getFreePlan()
    {
        return self::where('slug', 'free')->where('is_active', true)->first();
    }

    /**
     * Static: Get plan by Stripe price ID
     */
    public static function findByStripePriceId($priceId)
    {
        return self::where('stripe_price_id', $priceId)->where('is_active', true)->first();
    }

    /**
     * Static: Get plan by Stripe product ID
     */
    public static function findByStripeProductId($productId)
    {
        return self::where('stripe_product_id', $productId)->where('is_active', true)->first();
    }

    /**
     * Static: Get all active plans
     */
    public static function getActivePlans($serviceType = null)
    {
        $query = self::where('is_active', true)->orderBy('display_order', 'asc');

        if ($serviceType) {
            $query->where('service_type', $serviceType);
        }

        return $query->get();
    }

    /**
     * Relationships
     */
    public function subscriptions()
    {
        return $this->hasMany(MemberSubscription::class, 'subscription_plan_id');
    }
}