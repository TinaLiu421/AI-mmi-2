<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Institution_By_Country — Shows institutions filtered by country.
 *
 * Routes (auto via RouteMapping):
 *   GET  /{lang}/institution_by_country?country=australia
 */
class Institution_By_Country extends WebController
{
    /** Supported countries: slug => display name */
    private const COUNTRIES = [
        'australia'   => ['name' => 'Australia',    'flag' => '🇦🇺'],
        'new-zealand' => ['name' => 'New Zealand',  'flag' => '🇳🇿'],
        'us'          => ['name' => 'United States', 'flag' => '🇺🇸'],
        'canada'      => ['name' => 'Canada',        'flag' => '🇨🇦'],
        'uk'          => ['name' => 'United Kingdom','flag' => '🇬🇧'],
        'hong-kong'   => ['name' => 'Hong Kong',     'flag' => '🇭🇰'],
    ];

    /** Map display country name variants to canonical form stored in DB */
    private const COUNTRY_DB_MAP = [
        'australia'   => ['Australia'],
        'new-zealand' => ['New Zealand'],
        'us'          => ['United States', 'USA', 'US'],
        'canada'      => ['Canada'],
        'uk'          => ['United Kingdom', 'UK', 'England', 'Scotland', 'Wales'],
        'hong-kong'   => ['Hong Kong', 'Hong Kong SAR'],
    ];

    private function _hasTable(string $table): bool
    {
        try { return Schema::hasTable($table); } catch (\Exception $e) { return false; }
    }

    private function _hasColumn(string $table, string $column): bool
    {
        try { return Schema::hasTable($table) && Schema::hasColumn($table, $column); } catch (\Exception $e) { return false; }
    }

    // -------------------------------------------------------
    // GET /{lang}/institution_by_country?country=slug
    // -------------------------------------------------------
    public function index()
    {
        $countrySlug = strtolower(trim(request()->query('country', '')));

        // Validate slug
        if (empty($countrySlug) || !array_key_exists($countrySlug, self::COUNTRIES)) {
            // Invalid/missing country — redirect to study_plans
            $lang = $this->_current_lang_code ?? 'en';
            return redirect('/' . $lang . '/study_plans');
        }

        $countryInfo   = self::COUNTRIES[$countrySlug];
        $dbVariants    = self::COUNTRY_DB_MAP[$countrySlug] ?? [$countryInfo['name']];

        $hasCoursesJson = $this->_hasColumn('institution_profiles', 'courses_json');
        $hasPrograms    = $this->_hasColumn('institution_profiles', 'programs');
        $hasCountryCol  = $this->_hasColumn('institution_profiles', 'country');

        $select = [
            'ip.id',
            'ip.member_id',
            'ip.institute_name',
            'ip.summary',
            'ip.website_url',
            'm.avatar',
            'm.alias_name',
        ];
        if ($hasCountryCol)  $select[] = 'ip.country';
        if ($hasCoursesJson) $select[] = 'ip.courses_json';
        if ($hasPrograms)    $select[] = 'ip.programs';

        $query = DB::table('institution_profiles as ip')
            ->join('member as m', 'm.id', '=', 'ip.member_id')
            ->where('ip.status', 1)
            ->whereNull('ip.deleted_at')
            ->where('m.status', '>', 0)
            ->whereNotNull('ip.institute_name')
            ->where('ip.institute_name', '!=', '')
            ->select($select);

        // Filter by country column if it exists, else fall back to name matching in institute_name
        if ($hasCountryCol) {
            $pfx = DB::getTablePrefix();
            $query->where(function ($q) use ($dbVariants, $pfx) {
                foreach ($dbVariants as $variant) {
                    $q->orWhereRaw('LOWER(' . $pfx . 'ip.country) = ?', [strtolower($variant)]);
                }
            });
        } else {
            // Fallback: match country keywords in institute_name
            $query->where(function ($q) use ($dbVariants) {
                foreach ($dbVariants as $variant) {
                    $q->orWhere('ip.institute_name', 'like', '%' . $variant . '%');
                }
            });
        }

        $institutions = $query
            ->orderBy('ip.id', 'asc')
            ->get()
            ->map(function ($row) use ($hasCoursesJson, $hasPrograms) {
                $arr = (array) $row;
                $arr['courses_count'] = $this->_countCourses($arr, $hasCoursesJson, $hasPrograms);
                return $arr;
            })
            ->all();

        return $this->pageData([
            'country_slug' => $countrySlug,
            'country_name' => $countryInfo['name'],
            'country_flag' => $countryInfo['flag'],
            'institutions' => $institutions,
            'has_results'  => !empty($institutions),
        ])->pageView('institution_by_country');
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
            $raw = (string) $row['programs'];
            $prefix = 'COURSES_JSON:';
            if (strpos($raw, $prefix) === 0) $raw = substr($raw, strlen($prefix));
            $d = json_decode($raw, true);
            if (is_array($d)) {
                if (isset($d['courses']) && is_array($d['courses'])) $d = $d['courses'];
                if (is_array($d)) return count($d);
            }
        }
        return 0;
    }
}
