<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Request;

class Eligibility_Check extends WebController {
    public function index() {
        // Set page meta
        $this->pageMeta([
            'title' => 'Eligibility Check - Study Abroad',
            'description' => 'Check your eligibility for studying abroad'
        ]);
        
        return $this->pageData([
            'countries' => [
                'Australia', 'Canada', 'UK', 'US', 'New Zealand', 'Ireland', 
                'Portugal', 'Spain', 'Germany', 'Netherlands', 'Japan', 
                'China mainland', 'Taiwan', 'Singapore', 'Korea', 'Hong Kong', 
                'Macau', 'Malaysia', 'Thailand', 'Others'
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
        ])->pageView('eligibility_check');
    }
    
    public function assess() {
        $data = Request::all();
        
        // Process the eligibility check
        // This will be handled by AI assessment
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
