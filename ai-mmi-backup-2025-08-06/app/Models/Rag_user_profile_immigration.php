<?php
namespace App\Models;

use Exception;
use Illuminate\Support\Facades\DB;

class Rag_user_profile_immigration extends BaseModel {

    public function saveAnswers($session_id, $visa_type_id, $answers) {
        error_log("=== Rag_user_profile_immigration::saveAnswers DEBUG ===");
        error_log("Session ID: " . var_export($session_id, true));
        error_log("Visa Type ID: " . var_export($visa_type_id, true));
        error_log("Answers type: " . gettype($answers));
        error_log("Answers content: " . var_export($answers, true));

        if (empty($session_id) || empty($visa_type_id) || empty($answers)) {
            error_log("VALIDATION FAILED - Missing required parameters:");
            error_log("- Session ID empty: " . (empty($session_id) ? 'YES' : 'NO'));
            error_log("- Visa Type ID empty: " . (empty($visa_type_id) ? 'YES' : 'NO'));
            error_log("- Answers empty: " . (empty($answers) ? 'YES' : 'NO'));
            return false;
        }

        // Prepare the data for insertion/update
        $profile_data = [
            'session_id' => $session_id,
            'visa_type_id' => $visa_type_id,
            'other_criteria' => json_encode($answers)
        ];

        error_log("Prepared profile data: " . var_export($profile_data, true));

        // Extract common fields if they exist in answers
        if (isset($answers['education_level'])) {
            $profile_data['education_level'] = $answers['education_level'];
        }
        if (isset($answers['age'])) {
            $profile_data['age'] = (int)$answers['age'];
        }
        if (isset($answers['job_offer'])) {
            $profile_data['job_offer'] = $answers['job_offer'] ? 1 : 0;
        }
        if (isset($answers['enrollment'])) {
            $profile_data['enrollment'] = $answers['enrollment'] ? 1 : 0;
        }
        if (isset($answers['country'])) {
            $profile_data['country'] = $answers['country'];
        }

        // Use direct SQL since BaseModel expects columns that don't exist
        error_log("Attempting to save with direct SQL...");
        try {
            // Check if record exists
            $existing = DB::select(
                "SELECT id FROM app_rag_user_profile_immigration WHERE session_id = ? AND visa_type_id = ?",
                [$session_id, $visa_type_id]
            );

            error_log("Existing record check: " . var_export($existing, true));

            if (!empty($existing)) {
                // Update existing record
                error_log("Updating existing record...");
                $result = DB::update(
                    "UPDATE app_rag_user_profile_immigration SET other_criteria = ?, education_level = ?, age = ?, job_offer = ?, enrollment = ?, country = ? WHERE session_id = ? AND visa_type_id = ?",
                    [
                        $profile_data['other_criteria'],
                        $profile_data['education_level'] ?? null,
                        $profile_data['age'] ?? null,
                        $profile_data['job_offer'] ?? null,
                        $profile_data['enrollment'] ?? null,
                        $profile_data['country'] ?? null,
                        $session_id,
                        $visa_type_id
                    ]
                );
                error_log("Update result: " . var_export($result, true));
                return $result > 0;
            } else {
                // Insert new record
                error_log("Inserting new record...");
                $result = DB::insert(
                    "INSERT INTO app_rag_user_profile_immigration (session_id, visa_type_id, other_criteria, education_level, age, job_offer, enrollment, country) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $session_id,
                        $visa_type_id,
                        $profile_data['other_criteria'],
                        $profile_data['education_level'] ?? null,
                        $profile_data['age'] ?? null,
                        $profile_data['job_offer'] ?? null,
                        $profile_data['enrollment'] ?? null,
                        $profile_data['country'] ?? null
                    ]
                );
                error_log("Insert result: " . var_export($result, true));
                return $result;
            }

        } catch (Exception $e) {
            error_log("Database error in saveAnswers: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function getProfileBySession($session_id, $visa_type_id = null) {
        $where_conditions = [
            'name' => 'session_id',
            'operate' => '=',
            'value' => $session_id
        ];

        if ($visa_type_id) {
            $this->setWhere($where_conditions)->setWhere([
                'name' => 'visa_type_id',
                'operate' => '=',
                'value' => $visa_type_id
            ]);
        } else {
            $this->setWhere($where_conditions);
        }

        return $this->queryOneData('app_rag_user_profile_immigration');
    }

    public function deleteProfileBySession($session_id) {
        return $this->setWhere([
            'name' => 'session_id',
            'operate' => '=',
            'value' => $session_id
        ])->queryDeleteData('app_rag_user_profile_immigration');
    }
}