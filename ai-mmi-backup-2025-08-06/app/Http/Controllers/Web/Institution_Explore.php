<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Institution_Explore — lets students (type=1) and guests browse institution profiles.
 *
 * Routes (auto via RouteMapping):
 *   GET  /{lang}/institution_explore        → index()   — browse institutions
 */
class Institution_Explore extends WebController
{
    // No login guard — anyone can browse institution profiles.

    private function _hasTable(string $table): bool
    {
        try { return Schema::hasTable($table); } catch (\Exception $e) { return false; }
    }

    private function _hasColumn(string $table, string $column): bool
    {
        try { return Schema::hasTable($table) && Schema::hasColumn($table, $column); } catch (\Exception $e) { return false; }
    }

    // -------------------------------------------------------
    // GET /{lang}/institution_explore — browse institutions grid
    // -------------------------------------------------------
    public function index()
    {
        $search   = trim(request()->query('q', ''));
        $category = trim(request()->query('category', ''));
        $validCategories = ['university', 'vocational', 'highschool'];
        if (!in_array($category, $validCategories, true)) {
            $category = '';
        }

        $hasCoursesJson = $this->_hasColumn('institution_profiles', 'courses_json');
        $hasPrograms    = $this->_hasColumn('institution_profiles', 'programs');
        $hasCategory    = $this->_hasColumn('institution_profiles', 'institution_category');

        $select = [
            'ip.id',
            'ip.member_id',
            'ip.institute_name',
            'ip.summary',
            'ip.website_url',
            'm.avatar',
            'm.alias_name',
        ];
        if ($hasCoursesJson) $select[] = 'ip.courses_json';
        if ($hasPrograms)    $select[] = 'ip.programs';
        if ($hasCategory)    $select[] = 'ip.institution_category';

        $query = DB::table('institution_profiles as ip')
            ->join('member as m', 'm.id', '=', 'ip.member_id')
            ->where('ip.status', 1)
            ->whereNull('ip.deleted_at')
            ->where('m.status', '>', 0)
            ->whereNotNull('ip.institute_name')
            ->where('ip.institute_name', '!=', '')
            ->select($select);

        if (!empty($search)) {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('ip.institute_name', 'like', $like)
                  ->orWhere('m.alias_name', 'like', $like)
                  ->orWhere('ip.summary', 'like', $like);
            });
        }

        if (!empty($category) && $hasCategory) {
            $query->where('ip.institution_category', $category);
        }

        $pfx = DB::getTablePrefix();
        $institutions = $query
            ->orderByRaw("CASE WHEN {$pfx}ip.summary IS NOT NULL AND {$pfx}ip.summary != '' THEN 0 ELSE 1 END")
            ->orderBy('ip.id', 'asc')
            ->paginate(12)
            ->appends(request()->only(['q', 'category']));

        // Decode course count for each institution
        $institutions->getCollection()->transform(function ($row) use ($hasCoursesJson, $hasPrograms) {
            $arr = (array)$row;
            $arr['courses_count'] = $this->_countCourses($arr, $hasCoursesJson, $hasPrograms);
            $arr['location']      = $this->_extractLocation($arr, $hasCoursesJson, $hasPrograms);
            return $arr;
        });

        return $this->pageData([
            'institutions' => $institutions,
            'search'       => $search,
            'category'     => $category,
            'total'        => $institutions->total(),
        ])->pageView('institution_explore');
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    private function _countCourses(array $row, bool $hasCoursesJson, bool $hasPrograms): int
    {
        if ($hasCoursesJson && !empty($row['courses_json'])) {
            $d = json_decode($row['courses_json'], true);
            if (is_array($d)) {
                if (isset($d['courses']) && is_array($d['courses'])) $d = $d['courses'];
                if (is_array($d)) return count($d);
            }
        }
        if ($hasPrograms && !empty($row['programs'])) {
            $raw = (string)$row['programs'];
            $prefix = 'COURSES_JSON:';
            if (strpos($raw, $prefix) === 0) {
                $raw = substr($raw, strlen($prefix));
            }
            $d = json_decode($raw, true);
            if (is_array($d)) {
                if (isset($d['courses']) && is_array($d['courses'])) $d = $d['courses'];
                if (is_array($d)) return count($d);
            }
        }
        return 0;
    }

    private function _extractLocation(array $row, bool $hasCoursesJson, bool $hasPrograms): string
    {
        $courses = $this->_getFirstCourse($row, $hasCoursesJson, $hasPrograms);
        if (!empty($courses)) {
            $city    = trim($courses['city']    ?? $courses['location'] ?? '');
            $country = trim($courses['country'] ?? '');
            $parts   = array_filter([$city, $country]);
            if (!empty($parts)) return implode(', ', $parts);
        }
        return '';
    }

    private function _getFirstCourse(array $row, bool $hasCoursesJson, bool $hasPrograms): array
    {
        if ($hasCoursesJson && !empty($row['courses_json'])) {
            $d = json_decode($row['courses_json'], true);
            if (is_array($d)) {
                if (isset($d['courses']) && is_array($d['courses'])) $d = $d['courses'];
                if (is_array($d) && !empty($d[0]) && is_array($d[0])) return $d[0];
            }
        }
        if ($hasPrograms && !empty($row['programs'])) {
            $raw = (string)$row['programs'];
            $prefix = 'COURSES_JSON:';
            if (strpos($raw, $prefix) === 0) $raw = substr($raw, strlen($prefix));
            $d = json_decode($raw, true);
            if (is_array($d)) {
                if (isset($d['courses']) && is_array($d['courses'])) $d = $d['courses'];
                if (is_array($d) && !empty($d[0]) && is_array($d[0])) return $d[0];
            }
        }
        return [];
    }
}
