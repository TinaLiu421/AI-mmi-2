<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Study extends WebController {
    private const PROGRAMS_COURSE_JSON_PREFIX = '__AIMMI_COURSES_JSON__:';

    private function decodeCoursesPayload($raw): array {
        if (!is_string($raw)) {
            return [];
        }

        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        if (isset($decoded['courses']) && is_array($decoded['courses'])) {
            $decoded = $decoded['courses'];
        }

        if (array_keys($decoded) !== range(0, count($decoded) - 1)) {
            return [];
        }

        $courses = [];
        foreach ($decoded as $course) {
            if (is_array($course) && !empty($course)) {
                $courses[] = $course;
            }
        }

        return $courses;
    }

    private function extractCoursesFromRow(array $row): array {
        $courses = $this->decodeCoursesPayload($row['courses_json'] ?? '');
        if (!empty($courses)) {
            return $courses;
        }

        $programsRaw = (string)($row['programs'] ?? '');
        if ($programsRaw === '') {
            return [];
        }

        if (strpos($programsRaw, self::PROGRAMS_COURSE_JSON_PREFIX) === 0) {
            $programsRaw = substr($programsRaw, strlen(self::PROGRAMS_COURSE_JSON_PREFIX));
        }

        return $this->decodeCoursesPayload($programsRaw);
    }

    public function index() {
        // Redirect to the new Study Plans landing page
        $baseLang = !empty($this->_mapping_data['current_lang_web']) ? $this->_mapping_data['current_lang_web'] : 'en';
        $appUrl = config('app.url', '');
        $sourceDir = config('app.source_dir', '');
        $prefix = rtrim($appUrl . '/' . ltrim($sourceDir, '/'), '/') . '/' . $baseLang . '/study_plans';
        header('Location: ' . $prefix);
        exit();

        // Set page meta
        $this->pageMeta([
            'title' => 'Study Abroad Guidance',
            'description' => 'Get personalized assistance with your study abroad journey'
        ]);

        $hasCoursesJson = Schema::hasColumn('institution_profiles', 'courses_json');
        $hasPrograms = Schema::hasColumn('institution_profiles', 'programs');
        $institutions = [];
        $selectColumns = ['ip.id', 'ip.member_id', 'ip.institute_name', 'ip.summary', 'm.avatar', 'm.alias_name'];

        if ($hasCoursesJson) {
            $selectColumns[] = 'ip.courses_json';
        }
        if ($hasPrograms) {
            $selectColumns[] = 'ip.programs';
        }

        if ($hasCoursesJson || $hasPrograms) {
            $rows = DB::table('institution_profiles as ip')
                ->join('member as m', 'm.id', '=', 'ip.member_id')
                ->where('ip.status', 1)
                ->whereNull('ip.deleted_at')
                ->select($selectColumns)
                ->orderBy('ip.id', 'asc')
                ->get();

            $institutions = $rows->map(function($row) {
                    $arr = (array)$row;
                    $courses = $this->extractCoursesFromRow($arr);
                    $arr['courses_json'] = !empty($courses) ? json_encode($courses) : '';
                    return $arr;
                })
                ->filter(function($arr) {
                    return !empty($arr['courses_json']) && $arr['courses_json'] !== '[]';
                })
                ->values()
                ->toArray();
        }

        return $this->pageData([
            'page_data'    => [],
            'institutions' => $institutions,
        ])->pageView('study');
    }
}
