<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Migration_Eligibility extends WebController {
    public function index() {
        $this->pageMeta([
            'title' => 'Migration Eligibility Check',
            'description' => 'Check your eligibility for migration'
        ]);
        
        return $this->pageData([
            'countries' => [
                'Australia', 'Canada', 'UK', 'US', 'New Zealand', 'Ireland', 
                'Portugal', 'Spain', 'Germany', 'Netherlands', 'Japan', 
                'China mainland', 'Taiwan', 'Singapore', 'Korea', 'Hong Kong', 
                'Macau', 'Malaysia', 'Thailand', 'Others'
            ],
            'visa_types' => [
                'Skilled immigrant visas',
                'Start Up / Entrepreneurship Visas',
                'Student visas',
                'Family visas',
                'Employment visas (sponsorship required)',
                'Working holiday visas',
                'Investor visas (or business visas)',
                'Retirement visas (passive income visas)',
                'Refugee visas',
                'Other types of visa'
            ],
            'education_levels' => [
                'Secondary or below',
                'Diploma or Associate Degree',
                'Bachelor',
                'Master',
                'Doctoral'
            ],
            'english_tests' => [
                'IELTS',
                'TOEFL',
                'PTE',
                'Cambridge C1 Advanced',
                'Canada CELPIP',
                'Others'
            ]
        ])->pageView('migration_eligibility');
    }
}
