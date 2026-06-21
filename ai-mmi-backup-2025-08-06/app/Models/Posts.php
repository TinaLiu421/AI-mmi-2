<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Posts extends BaseModel {
    protected $_member_table = 'member';
    protected $_posts_table = 'member_posts';
    protected $_has_sector_column = null;

    public function __construct($data) {
        parent::__construct($data);
    }

    public function getAll($options = []) {
        $subquery_like = DB::table($this->_posts_table.'_like')->where([
            ['status', '>', 0],
        ])->groupBy('posts_id')->select([
            'posts_id AS like_posts_id',
            DB::raw('COUNT(*) AS `total_like`')
        ]);
        
        $subquery_content = DB::table($this->_posts_table.'_comment')->where([
            ['status', '>', 0],
        ])->groupBy('posts_id')->select([
            'posts_id AS comment_posts_id',
            DB::raw('COUNT(*) AS `total_comment`')
        ]);
        
        $query = DB::table($this->_posts_table)->where($this->_posts_table.'.status', '>', 0);
        $query->leftJoin($this->_member_table, $this->_posts_table.'.member_id', '=', $this->_member_table.'.id');
        $query->leftJoinSub($subquery_like, 'like', function ($join) {
            $join->on($this->_posts_table.'.id', '=', 'like.like_posts_id');
        });
        $query->leftJoinSub($subquery_content, 'comment', function ($join) {
            $join->on($this->_posts_table.'.id', '=', 'comment.comment_posts_id');
        });
        
        $query->where($this->_member_table.'.status', '>', 0);
        $member_ids = (!empty($options) && !empty($options['member_ids']) && is_array($options['member_ids']))
            ? array_values(array_unique(array_filter(array_map('intval', $options['member_ids']))))
            : [];
        if (!empty($member_ids)) {
            $query->whereIn($this->_posts_table.'.member_id', $member_ids);
        } else {
            $member_id = (!empty($options) && !empty($options['member_id'])) ? (int)$options['member_id'] : 0;
            if(!empty($member_id)) {
                $query->where($this->_posts_table.'.member_id', '=', $member_id);
            }
        }
        
        $keywords = (!empty($options) && !empty($options['keywords']))?$options['keywords']:'';
        if(!empty($keywords)) {
            $query->where($this->_posts_table.'.content', 'LIKE', '%'.$this->specialChars($keywords).'%');
        }
        
        if(!empty($options['show_type'])) {
            $query->where($this->_posts_table.'.category_type', '=', (int)$options['show_type']);
        }
        
        if(!empty($options['show_lang'])) {
            $show_lang = $options['show_lang'];
            $query->where(function($query) use ($show_lang) {
                $query->where($this->_posts_table.'.category_lang', '=', (int)$show_lang);
                $query->orWhere($this->_posts_table.'.category_lang', '=', 0);
            });
        }
        
        if(!empty($options['show_country'])) {
            $query->whereIn($this->_posts_table.'.category_country', [0, (int)$options['show_country']]);
        }

        // filter by sector (migration / study)
        if (!empty($options['sector']) && $this->hasSectorColumn()) {
            $query->where($this->_posts_table.'.sector', '=', (string)$options['sector']);
        }

        // exclude posts that are currently in the spotlight
        // Exception: posts by these accounts always appear in both featured and regular feeds
        if(!empty($options['exclude_featured'])) {
            $now = now()->toDateTimeString();
            $always_show_emails = ['admin@wealthskey.com', 'info@ai-mmi.com', 'info@mbi-au.com'];
            $member_table = $this->_member_table;
            $posts_table  = $this->_posts_table;
            $query->where(function($q) use ($now, $always_show_emails, $member_table, $posts_table) {
                $q->whereIn($member_table.'.email', $always_show_emails)
                  ->orWhere(function($q2) use ($now, $posts_table) {
                      $q2->whereNull($posts_table.'.featured_until')
                         ->orWhere($posts_table.'.featured_until', '<=', $now);
                  });
            });
        }
        
        if(!empty($options['show_page_size'])) {
            $this->setPageSize((int)$options['show_page_size']);
        }
        
        $selectFields = [
            $this->_posts_table.'.id',
            $this->_posts_table.'.category_type',
            $this->_posts_table.'.category_lang',
            $this->_posts_table.'.category_country',
            $this->_posts_table.'.title',
            $this->_posts_table.'.content',
            $this->_posts_table.'.photo',
            $this->_posts_table.'.youtube_url',
            $this->_posts_table.'.highlight',
            $this->_posts_table.'.featured_until',
            $this->_posts_table.'.created_at',
            $this->_posts_table.'.member_id',
            $this->_member_table.'.avatar',
            $this->_member_table.'.alias_name',
            'like.total_like',
            'comment.total_comment',
        ];

        if ($this->hasSectorColumn()) {
            $selectFields[] = $this->_posts_table.'.sector';
        } else {
            $selectFields[] = DB::raw("'study' AS sector");
        }

        $query->select($selectFields);
        
        if(!empty($options['show_highlight'])) {
            $query->orderBy($this->_posts_table.'.highlight', 'DESC');
        }
        
        $query->orderBy($this->_posts_table.'.created_at', 'DESC');

        $result = $query->paginate($this->_page_size);
        $this->setPagination($result->total());
        return 
        [
            'data' => $this->revisedData($result->getCollection()->map(function($items) {
                    $data = [];
                    foreach ($items as $item_key => $item_value) {
                        $data[$item_key] = $item_value;
                    }
                    return $data;
                })->toArray(), true),
            'pagination' => $this->_pagination
        ];
    }
    
    public function getByID($posts_id = 0) {
        $subquery_like = DB::table($this->_posts_table.'_like')->where([
            ['status', '>', 0],
        ])->groupBy('posts_id')->select([
            'posts_id AS like_posts_id',
            DB::raw('COUNT(*) AS `total_like`')
        ]);
        
        $subquery_content = DB::table($this->_posts_table.'_comment')->where([
            ['status', '>', 0],
        ])->groupBy('posts_id')->select([
            'posts_id AS comment_posts_id',
            DB::raw('COUNT(*) AS `total_comment`')
        ]);
        
        $query = DB::table($this->_posts_table)->where($this->_posts_table.'.status', '>', 0);
        $query->leftJoin($this->_member_table, $this->_posts_table.'.member_id', '=', $this->_member_table.'.id');
        $query->leftJoinSub($subquery_like, 'like', function ($join) {
            $join->on($this->_posts_table.'.id', '=', 'like.like_posts_id');
        });
        $query->leftJoinSub($subquery_content, 'comment', function ($join) {
            $join->on($this->_posts_table.'.id', '=', 'comment.comment_posts_id');
        });
        $query->where($this->_member_table.'.status', '>', 0);
        $query->where($this->_posts_table.'.id', '=', (int)$posts_id);
        
        $selectFields = [
            $this->_posts_table.'.id',
            $this->_posts_table.'.category_type',
            $this->_posts_table.'.category_lang',
            $this->_posts_table.'.category_country',
            $this->_posts_table.'.title',
            $this->_posts_table.'.content',
            $this->_posts_table.'.photo',
            $this->_posts_table.'.youtube_url',
            $this->_posts_table.'.highlight',
            $this->_posts_table.'.created_at',
            $this->_posts_table.'.member_id',
            $this->_member_table.'.avatar',
            $this->_member_table.'.alias_name',
            'like.total_like',
            'comment.total_comment',
        ];

        if ($this->hasSectorColumn()) {
            $selectFields[] = $this->_posts_table.'.sector';
        } else {
            $selectFields[] = DB::raw("'study' AS sector");
        }

        $query->select($selectFields);
        
        $query->orderBy($this->_posts_table.'.id', 'DESC');

        $result = $this->revisedData($query->get()->map(function($items) {
            $data = [];
            foreach ($items as $item_key => $item_value) {
                $data[$item_key] = $item_value;
            }
            return $data;
        })->toArray(), true);
        
        return reset($result);
    }
    
    public function doSave($data = [], $posts_id = 0) {
        // try to upload photo
        if(!empty($file = \Illuminate\Support\Facades\Request::file('mypostsphoto'))) {
            // get file info
            $file_ori_name = $file->getClientOriginalName();
            $file_extension = $file->getClientOriginalExtension();
            $file_size = $file->getSize();
            $file_name = md5(uniqid(rand())).'.'. strtolower($file_extension);

            // upload folder
            $location = ('upload/member_posts');
            if(!file_exists(public_path($location))){
                @mkdir(public_path($location), 0755, true);
            }

            // move & resize
            if($file->move(public_path($location), $file_name)) {
                if(file_exists(public_path($location.'/'.$file_name))) {
                    \Intervention\Image\Facades\Image::make(public_path($location.'/'.$file_name))->resize(1200, 1200, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })->save(public_path($location.'/'.$file_name));
                }
                $data['photo'] = $file_name;
            }
        }

        // Generate a job-post template image if no photo was provided
        $is_job_post = !empty($data['is_job_post']) && (string)$data['is_job_post'] === '1';
        if ($is_job_post && empty($data['photo'])) {
            $job_title = isset($data['title']) ? (string)$data['title'] : 'Job Opportunity';
            $generated = $this->generateJobPostTemplate($job_title);
            if ($generated) {
                $data['photo'] = $generated;
            }
        }
        unset($data['is_job_post']);

        unset($data['posts_id']);

        if (!$this->hasSectorColumn()) {
            unset($data['sector']);
        }

        return (((int)$data['member_id'] > 0)?$this->setWhere(
        [
            ['id', '=', (int)$posts_id],
            ['member_id', '=', (int)$data['member_id']]
        ])->queryInsertData($this->_posts_table, $data):false);
    }

    /**
     * Generate a professional job-post template image using GD.
     * Returns the saved filename on success, empty string on failure.
     */
    private function generateJobPostTemplate(string $title): string {
        if (!function_exists('imagecreatetruecolor')) {
            return '';
        }

        $width  = 1200;
        $height = 630;

        $img = imagecreatetruecolor($width, $height);
        if (!$img) return '';

        // ── Background gradient: dark navy (#0b2d6f) → vivid blue (#1d4ed8) ──
        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / ($height - 1);
            $r = (int)(11  + (29  - 11)  * $ratio);
            $g = (int)(45  + (78  - 45)  * $ratio);
            $b = (int)(111 + (216 - 111) * $ratio);
            $line_color = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, $width - 1, $y, $line_color);
        }

        // ── Decorative diagonal stripe accent (top-right area) ──
        $stripe = imagecolorallocatealpha($img, 255, 255, 255, 118); // ~7% opacity
        for ($i = 0; $i < 6; $i++) {
            $ox = 900 + $i * 40;
            $pts = [$ox, 0, $ox + 200, 0, $ox + 200 - $height, $height, $ox - $height, $height];
            if (count($pts) === 8) {
                imagefilledpolygon($img, $pts, 4, $stripe);
            }
        }

        // ── Horizontal rule ──
        $rule = imagecolorallocatealpha($img, 255, 255, 255, 90);
        imagesetthickness($img, 2);
        imageline($img, 60, 160, $width - 60, 160, $rule);
        imageline($img, 60, $height - 100, $width - 60, $height - 100, $rule);
        imagesetthickness($img, 1);

        // ── Colour palette ──
        $white      = imagecolorallocate($img, 255, 255, 255);
        $pale       = imagecolorallocatealpha($img, 255, 255, 255, 60);  // ~53% opacity
        $gold       = imagecolorallocate($img, 255, 200, 50);

        // ── Font paths (try bundled font first, then common system paths) ──
        $font_candidates = [
            base_path('app/Libraries/pdf/ttfonts/DejaVuSansCondensed-Bold.ttf'),
            '/usr/share/fonts/truetype/dejavu/DejaVuSansCondensed-Bold.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
            '/Library/Fonts/Arial Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        ];
        $font = '';
        foreach ($font_candidates as $candidate) {
            if (file_exists($candidate)) {
                $font = $candidate;
                break;
            }
        }

        // ── Label: "JOB OPPORTUNITY" ──
        if ($font) {
            $label = 'JOB OPPORTUNITY';
            imagettftext($img, 17, 0, 62, 110, $pale, $font, $label);

            // ── Branding dot ──
            imagefilledellipse($img, 49, 96, 14, 14, $gold);
        } else {
            // Fallback: built-in GD font
            imagestring($img, 5, 62, 90, 'JOB OPPORTUNITY', $white);
        }

        // ── Job title (auto-size + word-wrap) ──
        if ($font) {
            $title = trim($title);
            if ($title === '') $title = 'Job Opportunity';

            // Determine font size: start at 68, shrink until it fits one line or wrap
            $max_width   = $width - 120;
            $font_size   = 68;
            $min_size    = 28;
            $wrapped     = [];

            while ($font_size >= $min_size) {
                // Try single line
                $bbox = imagettfbbox($font_size, 0, $font, $title);
                $text_w = abs($bbox[2] - $bbox[0]);
                if ($text_w <= $max_width) {
                    $wrapped = [$title];
                    break;
                }
                // Try word-wrapping into 2 lines
                $words   = explode(' ', $title);
                $line1   = '';
                $line2   = '';
                $split   = false;
                $tmp     = '';
                foreach ($words as $word) {
                    $test = $tmp === '' ? $word : $tmp . ' ' . $word;
                    $b    = imagettfbbox($font_size, 0, $font, $test);
                    if (abs($b[2] - $b[0]) > $max_width && $tmp !== '') {
                        $line1 = $tmp;
                        $line2 = implode(' ', array_slice($words, count(explode(' ', $tmp))));
                        $split = true;
                        break;
                    }
                    $tmp = $test;
                }
                if ($split) {
                    // Check line2 width
                    $b2 = imagettfbbox($font_size, 0, $font, $line2);
                    if (abs($b2[2] - $b2[0]) <= $max_width) {
                        $wrapped = [$line1, $line2];
                        break;
                    }
                }
                $font_size -= 4;
            }
            if (empty($wrapped)) $wrapped = [$title];

            // Vertical centering between the two rules
            $text_zone_top    = 175;
            $text_zone_bottom = $height - 115;
            $text_zone_h      = $text_zone_bottom - $text_zone_top;

            $line_h = (int)($font_size * 1.25);
            $block_h = count($wrapped) * $line_h;
            $start_y = $text_zone_top + (int)(($text_zone_h - $block_h) / 2) + $font_size;

            foreach ($wrapped as $i => $line) {
                $bbox = imagettfbbox($font_size, 0, $font, $line);
                $lw   = abs($bbox[2] - $bbox[0]);
                $lx   = (int)(($width - $lw) / 2);
                $ly   = $start_y + $i * $line_h;
                imagettftext($img, $font_size, 0, $lx, $ly, $white, $font, $line);
            }
        } else {
            // GD fallback
            $lines = str_split($title, 30);
            $y0 = 240;
            foreach ($lines as $line) {
                imagestring($img, 5, 60, $y0, $line, $white);
                $y0 += 20;
            }
        }

        // ── Footer branding ──
        if ($font) {
            $brand = 'AI-mmi  |  ai-mmi.com';
            $bbox  = imagettfbbox(14, 0, $font, $brand);
            $bw    = abs($bbox[2] - $bbox[0]);
            imagettftext($img, 14, 0, $width - $bw - 60, $height - 30, $pale, $font, $brand);
        } else {
            imagestring($img, 3, 60, $height - 30, 'AI-mmi | ai-mmi.com', $pale);
        }

        // ── Save ──
        $location  = 'upload/member_posts';
        $full_dir  = public_path($location);
        if (!file_exists($full_dir)) {
            @mkdir($full_dir, 0755, true);
        }

        $file_name = 'job_' . md5(uniqid((string)rand(), true)) . '.jpg';
        $saved = imagejpeg($img, $full_dir . '/' . $file_name, 92);
        imagedestroy($img);

        return $saved ? $file_name : '';
    }

    protected function hasSectorColumn(): bool {
        if ($this->_has_sector_column !== null) {
            return $this->_has_sector_column;
        }

        try {
            $this->_has_sector_column = Schema::hasColumn($this->_posts_table, 'sector');
        } catch (\Throwable $e) {
            $this->_has_sector_column = false;
        }

        return $this->_has_sector_column;
    }
    
    
    public function doDelete($posts_id = 0) {
        if(!empty($posts_id)) {
            if(is_numeric($posts_id)) {
                $posts_id = [$posts_id];
            }
            else if(is_string($posts_id)){
                $posts_id = explode(',', $posts_id);
            }
            else {
                $posts_id = (array)$posts_id;
            }
        }
        
        return $this->queryTransaction(function($posts_id) {
            $this->setWhere(
            [
                'name'      =>  'id', 
                'operate'   =>  'in', 
                'value'     =>  $posts_id
            ])->queryDeleteData($this->_posts_table);
            
            $this->setWhere(
            [
                'name'      =>  'posts_id', 
                'operate'   =>  'in', 
                'value'     =>  $posts_id
            ])->queryDeleteData($this->_posts_table.'_comment');
            
            return true;
        }, $posts_id);
    }
    
    public function deleteSelfPost($posts_id = 0, $member_id = 0) {
        return $this->setWhere(
        [
            [
                'name'      =>  'id', 
                'operate'   =>  'in', 
                'value'     =>  $posts_id
            ],
            [
                'name'      =>  'member_id', 
                'operate'   =>  '=', 
                'value'     =>  $member_id
            ]
        ])->queryDeleteData($this->_posts_table);
    }
    
    public function doHighlight($posts_id = 0) {
        $target_post = $this->getByID($posts_id);
        $new_highlight = 0;
        if(empty($target_post['highlight'])) {
            $new_highlight = 1;
        }
        
        return $this->setWhere(
        [
            'name'      =>  'id', 
            'operate'   =>  '=', 
            'value'     =>  $posts_id
        ])->queryUpdateData($this->_posts_table, ['highlight' => $new_highlight]);
    }

    /**
     * Return currently active featured posts (featured_until > NOW()). No slot limit.
     */
    public function getFeatured($limit = 50) {
        $now = now()->toDateTimeString();

        $query = DB::table($this->_posts_table)
            ->where($this->_posts_table.'.status', '>', 0)
            ->whereNotNull($this->_posts_table.'.featured_until')
            ->where($this->_posts_table.'.featured_until', '>', $now)
            ->leftJoin($this->_member_table, $this->_posts_table.'.member_id', '=', $this->_member_table.'.id')
            ->where($this->_member_table.'.status', '>', 0)
            ->orderBy($this->_posts_table.'.featured_until', 'ASC')
            ->limit((int)$limit)
            ->select([
                $this->_posts_table.'.id',
                $this->_posts_table.'.title',
                $this->_posts_table.'.content',
                $this->_posts_table.'.photo',
                $this->_posts_table.'.youtube_url',
                $this->_posts_table.'.sector',
                $this->_posts_table.'.featured_until',
                $this->_posts_table.'.created_at',
                $this->_posts_table.'.member_id',
                $this->_member_table.'.alias_name',
                $this->_member_table.'.avatar',
            ]);

        $rows = $query->get();
        if ($rows->isEmpty()) {
            return [];
        }
        return $this->revisedData($rows->map(function($item) {
            $data = [];
            foreach ($item as $k => $v) { $data[$k] = $v; }
            return $data;
        })->toArray(), true);
    }

    /**
     * Fallback featured source when no active spotlight records exist.
     *
     * Priority:
     * 1) recently expired featured posts
     * 2) highlighted posts
     */
    public function getFeaturedFallback($limit = 10, $recentDays = 180) {
        $now = now()->toDateTimeString();
        $cutoff = now()->subDays((int)$recentDays)->toDateTimeString();

        $query = DB::table($this->_posts_table)
            ->where($this->_posts_table.'.status', '>', 0)
            ->leftJoin($this->_member_table, $this->_posts_table.'.member_id', '=', $this->_member_table.'.id')
            ->where($this->_member_table.'.status', '>', 0)
            ->where(function ($q) use ($now, $cutoff) {
                $q->where(function ($q2) use ($now, $cutoff) {
                    $q2->whereNotNull($this->_posts_table.'.featured_until')
                        ->where($this->_posts_table.'.featured_until', '<=', $now)
                        ->where($this->_posts_table.'.featured_until', '>=', $cutoff);
                })->orWhere($this->_posts_table.'.highlight', '>', 0);
            })
            ->orderByRaw('CASE WHEN '.$this->_db_prefix.$this->_posts_table.'.featured_until IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy($this->_posts_table.'.featured_until', 'DESC')
            ->orderBy($this->_posts_table.'.created_at', 'DESC')
            ->limit((int)$limit)
            ->select([
                $this->_posts_table.'.id',
                $this->_posts_table.'.title',
                $this->_posts_table.'.content',
                $this->_posts_table.'.photo',
                $this->_posts_table.'.youtube_url',
                $this->_posts_table.'.sector',
                $this->_posts_table.'.featured_until',
                $this->_posts_table.'.created_at',
                $this->_posts_table.'.member_id',
                $this->_member_table.'.alias_name',
                $this->_member_table.'.avatar',
            ]);

        $rows = $query->get();
        if ($rows->isEmpty()) {
            return [];
        }

        return $this->revisedData($rows->map(function($item) {
            $data = [];
            foreach ($item as $k => $v) { $data[$k] = $v; }
            return $data;
        })->toArray(), true);
    }

    /**
     * Toggle featured status: if not featured → set featured_until = +7 days.
     * If already featured → clear it (NULL).
     * Admin can also pass a specific end date.
     */
    public function doFeature($posts_id = 0, $end_date = null) {
        $posts_id = (int)$posts_id;
        if ($posts_id <= 0) {
            return false;
        }

        $post = DB::table($this->_posts_table)->where('id', $posts_id)->first();
        if (!$post) {
            return false;
        }

        $now = now();
        $currently_featured = !empty($post->featured_until) && $post->featured_until > $now->toDateTimeString();

        if ($currently_featured) {
            // unfeature — use direct DB update so NULL is stored properly
            return DB::table($this->_posts_table)->where('id', $posts_id)->update(['featured_until' => null]);
        } else {
            // feature for 7 days (or custom date)
            if (!empty($end_date)) {
                $new_until = date('Y-m-d H:i:s', strtotime($end_date));
            } else {
                $new_until = $now->addDays(7)->toDateTimeString();
            }
            return DB::table($this->_posts_table)->where('id', $posts_id)->update(['featured_until' => $new_until]);
        }
    }

    public function changeLike($posts_id = 0, $member_id = 0) {
        DB::beginTransaction();
        try {
            $temp_total = $this->setWhere(
            [
                [
                    'name'      =>  'posts_id', 
                    'operate'   =>  '=', 
                    'value'     =>  (int)$posts_id
                ],
                [
                    'name'      =>  'member_id', 
                    'operate'   =>  '=', 
                    'value'     =>  (int)$member_id
                ]
            ])->queryListTotal($this->_posts_table.'_like');
            
            // disable all first
            $this->setWhere(
            [
                [
                    'name'      =>  'posts_id', 
                    'operate'   =>  '=', 
                    'value'     =>  (int)$posts_id
                ],
                [
                    'name'      =>  'member_id', 
                    'operate'   =>  '=', 
                    'value'     =>  (int)$member_id
                ]
            ])->queryDeleteData($this->_posts_table.'_like');
            
            if(empty($temp_total)) {
                $this->setWhere(
                [
                    [
                        'name'      =>  'posts_id', 
                        'operate'   =>  '=', 
                        'value'     =>  (int)$posts_id
                    ],
                    [
                        'name'      =>  'member_id', 
                        'operate'   =>  '=', 
                        'value'     =>  (int)$member_id
                    ]
                ])->queryInsertData($this->_posts_table.'_like', 
                [
                    'posts_id'  =>  (int)$posts_id,
                    'member_id' =>  (int)$member_id,
                    'status'    =>  1
                ], true);
            }
            
            DB::commit();
            return true;
        }
        catch (Exception $e) {
            $this->setResultMessage($this->pLang('query_error'), 500);
            DB::rollBack();
            throw $e;
        }
    }
    
    public function getTotalLike($posts_id = 0) {
        return $this->setWhere(
        [
            [
                'name'      =>  'posts_id', 
                'operate'   =>  '=', 
                'value'     =>  (int)$posts_id
            ]
        ])->queryListTotal($this->_posts_table.'_like');
    }
    
    public function getAllComment($posts_id = 0) {
        $query = DB::table($this->_posts_table)->where($this->_posts_table.'.status', '>', 0);
        $query->Join($this->_posts_table.'_comment', $this->_posts_table.'.id', '=', $this->_posts_table.'_comment.posts_id');
        $query->Join($this->_member_table, $this->_posts_table.'_comment.member_id', '=', $this->_member_table.'.id');
        
        $query->where($this->_posts_table.'.id', '=', (int)$posts_id);
        $query->where($this->_posts_table.'_comment.status', '>', 0);
        $query->where($this->_member_table.'.status', '>', 0);
        
        $commentTable = DB::getTablePrefix().$this->_posts_table.'_comment';
        $memberTable  = DB::getTablePrefix().$this->_member_table;

        $query->select([
            $this->_posts_table.'_comment.id',
            $this->_member_table.'.id AS member_id',
            DB::raw("CASE WHEN {$commentTable}.status = 2 THEN 'asset/image/logo-mmi.png' ELSE {$memberTable}.avatar END AS avatar"),
            DB::raw("CASE WHEN {$commentTable}.status = 2 THEN 'AI-mmi' ELSE {$memberTable}.alias_name END AS alias_name"),
            $this->_posts_table.'_comment.content AS comment_content',
            DB::raw("{$commentTable}.status"),
            $this->_posts_table.'_comment.created_at'
        ]);
        
        $query->orderBy($this->_posts_table.'_comment.id', 'DESC');
        
        $result = $this->revisedData($query->get()->map(function($items) {
            $data = [];
            foreach ($items as $item_key => $item_value) {
                $data[$item_key] = $item_value;
            }
            return $data;
        })->toArray(), true);

        // Reorder so AI replies (status = 2) always sit directly under the latest user question.
        $ordered = [];
        $pendingAnswers = [];

        foreach ($result as $row) {
            if ((int)($row['status'] ?? 0) === 2) {
                $pendingAnswers[] = $row;
                continue;
            }

            $ordered[] = $row;

            if (!empty($pendingAnswers)) {
                // append oldest pending first to preserve chronological feel
                foreach (array_reverse($pendingAnswers) as $ans) {
                    $ordered[] = $ans;
                }
                $pendingAnswers = [];
            }
        }

        // leftover AI-only rows (if any) should still render
        if (!empty($pendingAnswers)) {
            foreach (array_reverse($pendingAnswers) as $ans) {
                $ordered[] = $ans;
            }
        }

        return $ordered;
    }
    
    public function saveComment($data = []) {
        return $this->queryInsertData($this->_posts_table.'_comment', $data);
    }
    
    public function doDeleteSub($posts_comment_id = 0) {
        if(!empty($posts_comment_id)) {
            if(is_numeric($posts_comment_id)) {
                $posts_comment_id = [$posts_comment_id];
            }
            else if(is_string($posts_comment_id)){
                $posts_comment_id = explode(',', $posts_comment_id);
            }
            else {
                $posts_comment_id = (array)$posts_comment_id;
            }
        }
        
        return $this->setWhere(
        [
            'name'      =>  'id', 
            'operate'   =>  'in', 
            'value'     =>  $posts_comment_id
        ])->queryDeleteData($this->_posts_table.'_comment');
    }
    
    public function getTotalComment($posts_id = 0) {
        return $this->setWhere(
        [
            [
                'name'      =>  'posts_id', 
                'operate'   =>  '=', 
                'value'     =>  (int)$posts_id
            ]
        ])->queryListTotal($this->_posts_table.'_comment');
    }
}
