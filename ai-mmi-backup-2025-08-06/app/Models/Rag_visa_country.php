<?php
namespace App\Models;

class Rag_visa_country extends BaseModel {

    public function getVisasByCountry($country) {
        if (empty($country)) {
            return [];
        }

        // First get visa country records matching the country name
        $visa_countries = $this->setWhere([
            'name' => 'country_name',
            'operate' => 'like',
            'value' => $country
        ])->setWhere([
            'name' => 'is_active',
            'operate' => '=',
            'value' => 1
        ])->queryListData('rag_visa_country', false);

        if (empty($visa_countries)) {
            return [];
        }

        // Get visa type IDs
        $visa_type_ids = array_column($visa_countries, 'visa_type_id');

        // Now get the actual visa types with details
        $visa_types = $this->setWhere([
            'name' => 'id',
            'operate' => 'in',
            'value' => $visa_type_ids
        ])->setWhere([
            'name' => 'is_active',
            'operate' => '=',
            'value' => 1
        ])->queryListData('rag_visa_types', false);

        // Format the response to match what the frontend expects
        $result = [];
        foreach ($visa_types as $visa_type) {
            $result[] = [
                'id' => $visa_type['id'],
                'title' => $visa_type['visa_name'],
                'code' => $visa_type['visa_code'],
                'category' => $visa_type['category'],
                'description' => $visa_type['description'] ?? '',
                'processing_time_min' => $visa_type['processing_time_min'],
                'processing_time_max' => $visa_type['processing_time_max'],
                'cost_usd' => $visa_type['cost_usd']
            ];
        }

        return $result;
    }
}