<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\DB;

class SubscriptionPlans extends AdminController
{
    public function index()
    {
        $this->pageTitle('Subscription Plans');

        $list_data = DB::table('subscription_plans')
            ->orderBy('display_order', 'asc')
            ->get()
            ->toArray();

        foreach ($list_data as $key => $plan) {
            $list_data[$key]->features = json_decode($plan->features, true);
        }

        $this->pageListData($list_data);
        return $this->pageView();
    }

    public function form($subscription_plan_id = 0)
    {
        $this->pageFormData(function() use ($subscription_plan_id) {
            if ($subscription_plan_id > 0) {
                $plan = DB::table('subscription_plans')->where('id', $subscription_plan_id)->first();

                if (!$plan) {
                    $this->setResultMessage('Plan not found', 404);
                    return false;
                }

                // Parse features JSON
                $plan->features = json_decode($plan->features, true);

                return [
                    'subscription_plan_id' => $subscription_plan_id,
                    'plan' => $plan
                ];
            }

            return ['subscription_plan_id' => 0];
        }, $subscription_plan_id);

        return $this->pageView();
    }

    public function save($subscription_plan_id = 0)
    {
        $this->pageAction(function() use ($subscription_plan_id) {
            $data = $this->_page_post_data;

            // Build features array
            $features = [
                'migration_questions_limit' => (int)($data['migration_questions_limit'] ?? 0),
                'education_questions_limit' => (int)($data['education_questions_limit'] ?? -1),
                'ai_consultation' => !empty($data['ai_consultation']),
                'human_agent_hours' => (int)($data['human_agent_hours'] ?? 0),
                'validation_check' => !empty($data['validation_check']),
                'full_service' => !empty($data['full_service']),
                'allowed_visa_types' => $data['allowed_visa_types'] ?? [],
                'program_applications' => (int)($data['program_applications'] ?? 0),
            ];

            $saveData = [
                'name' => $data['name'],
                'slug' => $data['slug'],
                'service_type' => $data['service_type'],
                'price' => $data['price'],
                'currency' => $data['currency'] ?? 'USD',
                'duration_months' => (int)$data['duration_months'],
                'stripe_price_id' => $data['stripe_price_id'] ?? null,
                'stripe_product_id' => $data['stripe_product_id'] ?? null,
                'features' => json_encode($features),
                'display_order' => (int)$data['display_order'],
                'is_active' => !empty($data['is_active']),
                'description' => $data['description'] ?? '',
                'updated_at' => now(),
            ];

            if ($subscription_plan_id > 0) {
                // Update
                DB::table('subscription_plans')
                    ->where('id', $subscription_plan_id)
                    ->update($saveData);

                $this->pageResult([
                    'status' => 200,
                    'message' => 'Plan updated successfully'
                ]);
            } else {
                // Insert
                $saveData['created_at'] = now();
                $newId = DB::table('subscription_plans')->insertGetId($saveData);

                $this->pageResult([
                    'status' => 200,
                    'message' => 'Plan created successfully',
                    'id' => $newId
                ]);
            }
        });
    }

    public function delete()
    {
        $this->pageAction(function() {
            $ids = $this->postParamValue('id');

            if (empty($ids)) {
                $this->setResultMessage('No plan selected', 400);
                return false;
            }

            if (!is_array($ids)) {
                $ids = [$ids];
            }

            // Don't delete free plan
            $freeplan = DB::table('subscription_plans')->where('slug', 'free')->first();
            if ($freePlan && in_array($freePlan->id, $ids)) {
                $this->setResultMessage('Cannot delete free plan', 403);
                return false;
            }

            DB::table('subscription_plans')->whereIn('id', $ids)->delete();

            $this->pageResult([
                'status' => 200,
                'message' => 'Plans deleted successfully'
            ]);
        });
    }
}