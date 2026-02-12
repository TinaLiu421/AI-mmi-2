<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;

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
    
    public function assess() {
        $data = Request::all();
        
        // Handle CV file upload if present
        $cvFilePath = null;
        if (Request::hasFile('cv')) {
            $file = Request::file('cv');
            $filename = time() . '_' . $file->getClientOriginalName();
            $cvFilePath = $file->storeAs('cvs', $filename, 'public');
        }
        
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
        
        // Prepare visa types as JSON
        $visaTypes = null;
        if (!empty($data['visa_type'])) {
            $visaTypes = is_array($data['visa_type']) ? json_encode($data['visa_type']) : json_encode([$data['visa_type']]);
        }
        
        // Insert into database (member_id is optional - null for guest assessments)
        DB::table('migration_eligibility_assessments')->insert([
            'member_id' => $this->_current_member['id'] ?? null, // Allow null for guest assessments
            'countries' => $countries,
            'visa_types' => $visaTypes,
            'nationality' => $data['nationality'] ?? null,
            'residency' => $data['residency'] ?? null,
            'age' => $data['age'] ?? null,
            'education_level' => $data['education_level'] ?? null,
            'english_test_completed' => $data['english_test_completed'] ?? null,
            'test_results' => $testResults,
            'occupation' => $data['occupation'] ?? null,
            'total_work_experience' => $data['work_experience'] ?? null,
            'occupation_work_experience' => $data['occupation_work_experience'] ?? null,
            'destination_work_experience' => $data['destination_work_experience'] ?? null,
            'destination_work_years' => $data['destination_work_years'] ?? null,
            'job_offer' => $data['job_offer'] ?? null,
            'outstanding_achievements' => $data['outstanding_achievements'] ?? null,
            'achievements_details' => $data['achievements_details'] ?? null,
            'cv_file_path' => $cvFilePath,
            'ai_assessment' => null, // Will be filled after AI response
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Redirect to the migration profile comparison page with auto-generate flag
        $lang = $this->_mapping_data['lang_code'] ?? 'en';
        return redirect('/' . $lang . '/migration_profile_comparison')->with('auto_generate', true);
    }
    
    private function buildAssessmentPrompt($data)
    {
        $prompt = "I need you to assess my eligibility for migration based on the following information:\n\n";
        
        if (!empty($data['countries'])) {
            $countries = is_array($data['countries']) ? $data['countries'] : [$data['countries']];
            $prompt .= "Preferred countries: " . implode(', ', $countries) . "\n";
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
        
        if (!empty($data['occupation'])) {
            $prompt .= "Occupation: " . $data['occupation'] . "\n";
        }
        
        if (!empty($data['work_experience'])) {
            $prompt .= "Work experience: " . $data['work_experience'] . " years\n";
        }
        
        if (!empty($data['visa_type'])) {
            $visaTypes = is_array($data['visa_type']) ? $data['visa_type'] : [$data['visa_type']];
            $prompt .= "Interested visa types: " . implode(', ', $visaTypes) . "\n";
        }
        
        if (!empty($data['marital_status'])) {
            $prompt .= "Marital status: " . $data['marital_status'] . "\n";
        }
        
        if (!empty($data['dependents'])) {
            $prompt .= "Number of dependents: " . $data['dependents'] . "\n";
        }
        
        if (!empty($data['achievements'])) {
            $prompt .= "Professional achievements/awards: " . $data['achievements'] . "\n";
        }
        
        $prompt .= "\nPlease provide a detailed migration eligibility assessment including:\n";
        $prompt .= "1. Overall eligibility status for each selected country\n";
        $prompt .= "2. Specific visa options and requirements\n";
        $prompt .= "3. Points assessment (if applicable)\n";
        $prompt .= "4. Any potential challenges or areas of concern\n";
        $prompt .= "5. Recommendations for improving my eligibility\n";
        $prompt .= "6. Suggested next steps and timeline\n";
        
        return $prompt;
    }
}
