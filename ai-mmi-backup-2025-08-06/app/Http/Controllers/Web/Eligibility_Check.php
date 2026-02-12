<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;

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
        
        // Build the assessment prompt from the form data
        $prompt = $this->buildAssessmentPrompt($data);
        
        // Prepare test results as JSON
        $testResults = null;
        if (!empty($data['test_results'])) {
            $testResults = json_encode($data['test_results']);
        }
        
        // Prepare countries as JSON
        $countries = null;
        if (!empty($data['countries'])) {
            $countries = is_array($data['countries']) ? json_encode($data['countries']) : json_encode([$data['countries']]);
        }
        
        // Insert into database (member_id is optional - null for guests)
        DB::table('study_eligibility_assessments')->insert([
            'member_id' => $this->_current_member['id'] ?? null, // Allow null for guest assessments
            'countries' => $countries,
            'nationality' => $data['nationality'] ?? null,
            'residency' => $data['residency'] ?? null,
            'age' => $data['age'] ?? null,
            'education_level' => $data['education_level'] ?? null,
            'english_test_completed' => $data['english_test_completed'] ?? null,
            'test_results' => $testResults,
            'study_level' => $data['study_level'] ?? null,
            'field_of_study' => $data['field_of_study'] ?? null,
            'budget' => $data['budget'] ?? null,
            'ai_assessment' => null, // Will be filled after AI response
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Redirect to the study profile comparison page with auto-generate flag
        $lang = $this->_mapping_data['lang_code'] ?? 'en';
        return redirect('/' . $lang . '/study_profile_comparison')->with('auto_generate', true)->with('eligibility_assessment', ['prompt' => $prompt]);
    }
    
    private function buildAssessmentPrompt($data)
    {
        $prompt = "I need you to assess my eligibility for studying abroad based on the following information:\n\n";
        
        if (!empty($data['countries'])) {
            $countries = is_array($data['countries']) ? $data['countries'] : [$data['countries']];
            $prompt .= "Countries I'm interested in: " . implode(', ', $countries) . "\n";
        }
        
        if (!empty($data['nationality'])) {
            $prompt .= "My nationality: " . $data['nationality'] . "\n";
        }
        
        if (!empty($data['residency'])) {
            $prompt .= "Current country of residency: " . $data['residency'] . "\n";
        }
        
        if (!empty($data['age'])) {
            $prompt .= "My age: " . $data['age'] . "\n";
        }
        
        if (!empty($data['education_level'])) {
            $prompt .= "My education level: " . $data['education_level'] . "\n";
        }
        
        if (!empty($data['english_test_completed'])) {
            $prompt .= "English test completed: " . $data['english_test_completed'] . "\n";
            
            if ($data['english_test_completed'] === 'Yes' && !empty($data['test_results'])) {
                $prompt .= "Test results: ";
                $results = [];
                foreach ($data['test_results'] as $test => $score) {
                    if (!empty($score)) {
                        $results[] = str_replace('_', ' ', $test) . ': ' . $score;
                    }
                }
                $prompt .= implode(', ', $results) . "\n";
            }
        }
        
        if (!empty($data['study_level'])) {
            $prompt .= "Intended study level: " . $data['study_level'] . "\n";
        }
        
        if (!empty($data['field_of_study'])) {
            $prompt .= "Field of study: " . $data['field_of_study'] . "\n";
        }
        
        if (!empty($data['budget'])) {
            $prompt .= "Budget: " . $data['budget'] . "\n";
        }
        
        $prompt .= "\nPlease provide a detailed eligibility assessment including:\n";
        $prompt .= "1. Overall eligibility status for each selected country\n";
        $prompt .= "2. Specific visa and admission requirements I need to meet\n";
        $prompt .= "3. Any potential challenges or areas of concern\n";
        $prompt .= "4. Recommendations for improving my eligibility\n";
        $prompt .= "5. Suggested next steps\n";
        
        return $prompt;
    }
}
