<?php
namespace App\Models;

class Rag_visa_criteria extends BaseModel {

    public function getCriteriaByVisa($visa_type_id) {
        if (empty($visa_type_id)) {
            return [];
        }

        // Get all criteria for the specified visa type
        $criteria = $this->setWhere([
            'name' => 'visa_type_id',
            'operate' => '=',
            'value' => $visa_type_id
        ])->setOrder(['weight_score_desc'])->queryListData('rag_visa_criteria', false);

        if (empty($criteria)) {
            return [];
        }

        // Format the response
        $result = [];
        foreach ($criteria as $criterion) {
            $acceptable_values = null;
            if (!empty($criterion['acceptable_values'])) {
                // Handle JSON decode for acceptable values
                $acceptable_values = json_decode($criterion['acceptable_values'], true);
            }

            $result[] = [
                'id' => $criterion['id'],
                'criteria_type' => $criterion['criteria_type'],
                'criteria_name' => $criterion['criteria_name'],
                'description' => $criterion['description'] ?? '',
                'is_mandatory' => (bool)$criterion['is_mandatory'],
                'weight_score' => (int)$criterion['weight_score'],
                'minimum_value' => $criterion['minimum_value'],
                'maximum_value' => $criterion['maximum_value'],
                'acceptable_values' => $acceptable_values ? json_encode($acceptable_values) : null
            ];
        }

        return $result;
    }

    public function getAllCriteria() {
        // Get all criteria (useful for reference)
        return $this->setOrder(['visa_type_id_asc', 'weight_score_desc'])->queryListData('rag_visa_criteria', false);
    }
}