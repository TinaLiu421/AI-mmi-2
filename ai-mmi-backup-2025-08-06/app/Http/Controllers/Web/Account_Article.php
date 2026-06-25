<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class Account_Article extends WebController {
    
    protected $_posts_model = null;
    
    public function __construct($data) {
        parent::__construct($data);
        
        // load model
        $this->_posts_model = $this->loadModel('posts');
    }
    
    public function index() {
        $target_member_id = $this->getParamValue('mid');
        $sector_filter    = request()->input('sector', ''); // 'migration', 'study', or ''
        $debug_enabled    = (string)request()->input('debug_posts', '') === '1';
        $debug_data       = [
            'target_member_id' => (int)$target_member_id,
            'sector' => (string)$sector_filter,
        ];
        // only exclude featured posts in the general feed (mid=0), not on a member's own profile
        $exclude_featured = empty($target_member_id);
        if(!empty($target_member_id)) {
            // Member profile page: show all languages, no lang filter
            $opts = ['member_id' => $target_member_id];
            if ($sector_filter) $opts['sector'] = $sector_filter;
            $list_posts = $this->_posts_model->getAll($opts);
            $debug_data['direct_count'] = isset($list_posts['data']) ? count($list_posts['data']) : 0;
            // Fallback without sector if empty
            if (empty($list_posts['data']) && $sector_filter) {
                $list_posts = $this->_posts_model->getAll(['member_id' => $target_member_id]);
                $debug_data['direct_no_sector_count'] = isset($list_posts['data']) ? count($list_posts['data']) : 0;
            }

            // No fallback to related/peer institutions: show only this member's own posts.
        }
        else {
            // Home feed: English-only, no fallback to other languages
            $opts = ['member_id' => $target_member_id, 'show_lang' => 1, 'exclude_featured' => true];
            if ($sector_filter) $opts['sector'] = $sector_filter;
            $list_posts = $this->_posts_model->getAll($opts);
            $debug_data['home_feed_count'] = isset($list_posts['data']) ? count($list_posts['data']) : 0;
        }

        // load view
        $view = $this->pageData(
        [
            'show_current_member'   =>  $this->_current_member,
            'list_posts'            =>  $list_posts
        ])->pageView('', false, false);

        if ($debug_enabled) {
            $debug_json = json_encode($debug_data, JSON_UNESCAPED_SLASHES);
            $view .= "\n<!-- posts_debug: " . htmlspecialchars((string)$debug_json, ENT_QUOTES, 'UTF-8') . " -->";
        }

        return $view;
    }

    /**
     * Return institution-linked member IDs by matching broader organization identity.
     *
     * We intentionally use multiple soft signals because production data may differ
     * between posting and profile-owning accounts (alias/company/domain variations).
     */
    private function getRelatedInstitutionMemberIds(int $target_member_id): array {
        if ($target_member_id <= 0) {
            return [];
        }

        $targetMember = DB::table('member')
            ->where('id', $target_member_id)
            ->first(['id', 'alias_name', 'full_name', 'email']);

        $targetDetails = DB::table('member_details')
            ->where('member_id', $target_member_id)
            ->first(['company_name', 'company_website']);

        $targetInstitutionProfile = DB::table('institution_profiles')
            ->where('member_id', $target_member_id)
            ->first(['institute_name', 'website_url']);

        if (!$targetMember && !$targetDetails && !$targetInstitutionProfile) {
            return [];
        }

        $targetNames = $this->buildIdentityNames([
            $targetMember->alias_name ?? '',
            $targetMember->full_name ?? '',
            $targetDetails->company_name ?? '',
            $targetInstitutionProfile->institute_name ?? '',
        ], [
            $targetDetails->company_website ?? '',
            $targetInstitutionProfile->website_url ?? '',
            $targetMember->email ?? '',
        ]);

        $targetDomains = $this->buildIdentityDomains([
            $targetDetails->company_website ?? '',
            $targetInstitutionProfile->website_url ?? '',
            $targetMember->email ?? '',
        ]);

        if (empty($targetNames) && empty($targetDomains)) {
            return [$target_member_id];
        }

        $candidates = DB::table('member as m')
            ->leftJoin('member_details as md', 'md.member_id', '=', 'm.id')
            ->leftJoin('institution_profiles as ip', 'ip.member_id', '=', 'm.id')
            ->where('m.status', '>', 0)
            ->whereNull('m.deleted_at')
            ->select(
                'm.id',
                'm.alias_name',
                'm.full_name',
                'm.email',
                'md.company_name',
                'md.company_website',
                'ip.institute_name',
                'ip.website_url'
            )
            ->get();

        $matchedIds = [];
        foreach ($candidates as $row) {
            $rowNames = $this->buildIdentityNames([
                $row->alias_name ?? '',
                $row->full_name ?? '',
                $row->company_name ?? '',
                $row->institute_name ?? '',
            ], [
                $row->company_website ?? '',
                $row->website_url ?? '',
                $row->email ?? '',
            ]);

            $rowDomains = $this->buildIdentityDomains([
                $row->company_website ?? '',
                $row->website_url ?? '',
                $row->email ?? '',
            ]);

            $nameMatched = !empty(array_intersect($targetNames, $rowNames))
                || $this->hasOverlappingIdentityName($targetNames, $rowNames);
            $domainMatched = !empty(array_intersect($targetDomains, $rowDomains));

            if ($nameMatched || $domainMatched) {
                $matchedIds[] = (int)$row->id;
            }
        }

        $matchedIds[] = $target_member_id;
        $matchedIds = array_values(array_unique(array_filter($matchedIds)));

        return $matchedIds;
    }

    private function buildIdentityNames(array $rawNames, array $rawDomains = []): array {
        $identityNames = [];

        foreach ($rawNames as $rawName) {
            $rawName = trim((string)$rawName);
            if ($rawName === '') {
                continue;
            }

            $normalized = $this->normalizeIdentityValue($rawName);
            if ($normalized !== '') {
                $identityNames[] = $normalized;
            }

            $acronym = $this->buildIdentityAcronym($rawName);
            if ($acronym !== '') {
                $identityNames[] = $acronym;
            }
        }

        foreach ($rawDomains as $rawDomain) {
            $domainLabel = $this->extractDomainLabel($rawDomain);
            if ($domainLabel !== '') {
                $identityNames[] = $domainLabel;
            }
        }

        return array_values(array_filter(array_unique($identityNames)));
    }

    private function buildIdentityDomains(array $rawDomains): array {
        $domains = [];
        foreach ($rawDomains as $rawDomain) {
            $domain = $this->extractDomain($rawDomain);
            if ($domain !== '') {
                $domains[] = $domain;
            }
        }

        return array_values(array_filter(array_unique($domains)));
    }

    private function buildIdentityAcronym(string $value): string {
        $tokens = preg_split('/[^a-z0-9]+/i', strtolower(trim($value)));
        $tokens = array_values(array_filter($tokens));
        if (empty($tokens)) {
            return '';
        }

        $countrySuffixes = [
            'australia' => 'au',
            'zealand' => 'nz',
            'kingdom' => 'uk',
            'states' => 'us',
        ];

        $acronym = '';
        foreach ($tokens as $token) {
            if (isset($countrySuffixes[$token])) {
                $acronym .= $countrySuffixes[$token];
                continue;
            }

            $acronym .= substr($token, 0, 1);
        }

        return $this->normalizeIdentityValue($acronym);
    }

    private function extractDomainLabel($value): string {
        $domain = $this->extractDomain($value);
        if ($domain === '') {
            return '';
        }

        $parts = explode('.', $domain);
        if (empty($parts)) {
            return '';
        }

        return $this->normalizeIdentityValue((string)$parts[0]);
    }

    private function hasOverlappingIdentityName(array $leftNames, array $rightNames): bool {
        foreach ($leftNames as $leftName) {
            foreach ($rightNames as $rightName) {
                if ($leftName === '' || $rightName === '') {
                    continue;
                }

                if (strlen($leftName) < 6 || strlen($rightName) < 6) {
                    continue;
                }

                if (strpos($leftName, $rightName) !== false || strpos($rightName, $leftName) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    private function normalizeIdentityValue($value): string {
        $value = strtolower(trim((string)$value));
        if ($value === '') return '';

        $value = preg_replace('/https?:\/\//', '', $value);
        $value = preg_replace('/^www\./', '', $value);
        $value = preg_replace('/[^a-z0-9]+/', '', $value);

        return (string)$value;
    }

    private function extractDomain($value): string {
        $value = strtolower(trim((string)$value));
        if ($value === '') return '';

        if (strpos($value, '@') !== false) {
            $parts = explode('@', $value);
            $value = end($parts) ?: '';
        }

        if ($value === '') return '';

        if (strpos($value, 'http://') !== 0 && strpos($value, 'https://') !== 0) {
            $value = 'http://' . $value;
        }

        $host = parse_url($value, PHP_URL_HOST);
        $host = strtolower((string)$host);
        $host = preg_replace('/^www\./', '', $host);

        return (string)$host;
    }

    private function isEducationInstitutionMember(int $member_id): bool {
        if ($member_id <= 0) return false;

        $row = DB::table('member as m')
            ->leftJoin('member_details as md', 'md.member_id', '=', 'm.id')
            ->where('m.id', $member_id)
            ->select('m.type', 'md.institution_type')
            ->first();

        if (!$row) return false;

        return ((int)($row->type ?? 0) === 3) && ((int)($row->institution_type ?? 0) === 2);
    }

    private function getPeerInstitutionMemberIds(int $exclude_member_id): array {
        $postStats = DB::table('member_posts')
            ->where('status', '>', 0)
            ->groupBy('member_id')
            ->select('member_id', DB::raw('MAX(created_at) AS last_post_at'));

        $rows = DB::table('member as m')
            ->join('member_details as md', 'md.member_id', '=', 'm.id')
            ->joinSub($postStats, 'ps', function ($join) {
                $join->on('ps.member_id', '=', 'm.id');
            })
            ->where('m.status', '>', 0)
            ->whereNull('m.deleted_at')
            ->where('m.type', '=', 3)
            ->where('md.institution_type', '=', 2)
            ->where('m.id', '!=', $exclude_member_id)
            ->orderByDesc('ps.last_post_at')
            ->limit(20)
            ->pluck('m.id')
            ->toArray();

        return array_values(array_map('intval', $rows));
    }
    
    public function fullcontent($id = 0) {
        $page_data = $this->_posts_model->getByID($id);
        if(!empty($page_data)) {
            echo nl2br($page_data['content']);
        }
    }


    public function ticklike() {
        // post event
        $this->pageAction(function() {
            if(!empty($this->_current_member)) {
                if($this->_posts_model->changeLike($this->postParamValue('posts_id', 0), $this->_current_member['id'])) {
                    $this->pageResult(
                    [
                        'status'    =>  200,
                        'total'     =>  number_format($this->_posts_model->getTotalLike($this->postParamValue('posts_id', 0)))
                    ]);
                }
                else {
                    $this->pageResult(
                    [
                        'status'    =>  $this->_posts_model->getResultCode(),
                        'message'   =>  $this->_posts_model->getResultMessage()
                    ]);
                }
            }
            else {
                $this->pageResult(
                [
                    'status'    =>  403,
                    'message'   =>  $this->_page_lang['account.permission_denied'],
                    'url'       =>  $this->toURL('account_login')
                ]);
            }
        });
    }
    
    public function comment($id = 0) {
        // post event
        $this->pageAction(function() {
            if(!empty($this->_current_member)) {
                // do checking
                $validator = Validator::make($this->_page_post_data, 
                [
                    'content'   =>  'required'
                ]);

                $this->_page_post_data['member_id'] = $this->_current_member['id'];
                if(!$validator->fails()) {
                    if($this->_posts_model->saveComment($this->_page_post_data)) {
                        $this->pageResult(
                        [
                            'status'    =>  200,
                            'total'     =>  number_format($this->_posts_model->getTotalComment($this->postParamValue('posts_id', 0)))
                        ]);
                    }
                    else {
                        $this->pageResult(
                        [
                            'status'    =>  $this->_posts_model->getResultCode(),
                            'message'   =>  $this->_posts_model->getResultMessage()
                        ]);
                    }
                }
                else {
                    $this->pageResult(
                    [
                        'status'    =>  400,
                        'message'   =>  $this->_page_lang['bad_request']
                    ]);
                }
            }
            else {
                $this->pageResult(
                [
                    'status'    =>  403,
                    'message'   =>  $this->_page_lang['account.permission_denied'],
                    'url'       =>  $this->toURL('account_login')
                ]);
            }
        });
        
        // load view
        return $this->pageData(
        [
            'reply' =>  $this->_posts_model->getAllComment($id)
        ])->pageView('', false, false);
    }
}