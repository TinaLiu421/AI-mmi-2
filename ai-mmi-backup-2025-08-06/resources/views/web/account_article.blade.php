<?php $_show_current_member = $_page_data['show_current_member']; ?>
<?php $_is_home_feed = isset($_page_get_data['mid']) && (int)($_page_get_data['mid'] ?? -1) === 0; ?>
<?php
if (!function_exists('getYoutubeEmbedUrl')) {
function getYoutubeEmbedUrl($url) {
    $shortUrlRegex = '/youtu.be\/([a-zA-Z0-9_-]+)\??/i';
    $longUrlRegex = '/youtube.com\/((?:embed)|(?:watch))((?:\?v\=)|(?:\/))([a-zA-Z0-9_-]+)/i';

    $youtube_id = '';
    if (preg_match($longUrlRegex, (string)$url, $matches)) {
        $youtube_id = $matches[count($matches) - 1];
    }
    if ($youtube_id === '' && preg_match($shortUrlRegex, (string)$url, $matches)) {
        $youtube_id = $matches[count($matches) - 1];
    }

    return 'https://www.youtube.com/embed/' . $youtube_id;
}
}

// Strips markdown syntax and returns clean plain text suitable for card excerpts.
// Handles: **bold**, __underline__, *italic*, _italic_, ==highlight==,
//          ## headings, - / * / • bullet lists, 1. numbered lists, --- rules.
if (!function_exists('mdPlainText')) {
function mdPlainText($md, $maxLen = 0) {
    $s = (string)($md ?? '');
    // Bold / underline: **text** __text__  — replace with " text " so adjacent tokens stay separated
    $s = preg_replace('/\*\*(.+?)\*\*/su', ' $1 ', $s);
    $s = preg_replace('/__(.+?)__/su',     ' $1 ', $s);
    // Italic: *text*  _text_  (only single markers)
    $s = preg_replace('/(?<!\*)\*(?!\*)([^*\n]+?)(?<!\*)\*(?!\*)/u', ' $1 ', $s);
    $s = preg_replace('/(?<!_)_(?!_)([^_\n]+?)(?<!_)_(?!_)/u',      ' $1 ', $s);
    // Highlight: ==text==
    $s = preg_replace('/==(.+?)==/su', ' $1 ', $s);
    // Headings: # ## ### …
    $s = preg_replace('/^#{1,6}[ \t]+/mu', '', $s);
    // Horizontal rules: --- *** ===
    $s = preg_replace('/^[-=*]{3,}[ \t]*$/mu', '', $s);
    // Bullet list markers: -  *  +  •
    $s = preg_replace('/^[ \t]*[-*+•][ \t]+/mu', '', $s);
    // Numbered list markers: 1.  2.  …
    $s = preg_replace('/^[ \t]*\d+\.[ \t]+/mu', '', $s);
    // Strip any residual HTML
    $s = strip_tags($s);
    // Collapse whitespace (newlines → space)
    $s = preg_replace('/[\r\n\t]+/', ' ', $s);
    $s = preg_replace('/[ ]{2,}/', ' ', $s);
    $s = trim($s);
    if ($maxLen > 0 && mb_strlen($s) > $maxLen) {
        $s = mb_substr($s, 0, $maxLen);
    }
    return $s;
}
}
?>
<?php if(!empty($_page_data['list_posts']['data'])) { foreach ($_page_data['list_posts']['data'] as $posts) { 
    $postSector = (!empty($posts['sector']) && in_array($posts['sector'], ['study', 'migration'], true)) ? $posts['sector'] : 'study';
    $postActionLabel = ($postSector === 'migration')
        ? ($_page_lang['chat_robot.talk_to_ai_mmi'] ?? 'Talk to AI-mmi')
        : ($_page_lang['apply'] ?? 'Apply Now !');
    $postActionUrl = ($postSector === 'migration') ? ($_page_base_url.'/agent_chat') : ($_page_base_url.'/apply');
    if (!empty($posts['title'])) {
        if (stripos($posts['title'], 'NZ as Business Investor') !== false) {
            $_postPresetMsg = 'Hi, I am interested in moving to NZ as a business investor. Can you let me know more details?';
        } elseif (stripos($posts['title'], 'New Zealand') !== false) {
            $_postPresetMsg = 'How can I migrate to New Zealand as a business investor?';
        } else {
            $_postPresetMsg = '';
        }
    } else {
        $_postPresetMsg = '';
    }
?>
<?php if ($_is_home_feed): ?>
<?php
$card_url   = $_page_base_url . '/posts/details/' . $posts['id'];
$card_thumb = null;
if (!empty($posts['photo']) && file_exists(public_path('upload/member_posts/'.$posts['photo']))) {
    $card_thumb = 'upload/member_posts/' . $posts['photo'];
} elseif (!empty($posts['youtube_url'])) {
    $yt_long  = '/youtube\.com\/((?:embed)|(?:watch)|(?:shorts))((?:\?v=)|(?:\\/))([a-zA-Z0-9_-]+)/i';
    $yt_short = '/youtu\.be\\/([a-zA-Z0-9_-]+)/i';
    $yt_id = '';
    if (preg_match($yt_long, $posts['youtube_url'], $ym))  $yt_id = $ym[count($ym)-1];
    elseif (preg_match($yt_short, $posts['youtube_url'], $ym)) $yt_id = $ym[1];
    $card_thumb = !empty($yt_id) ? 'https://img.youtube.com/vi/'.$yt_id.'/hqdefault.jpg' : null;
}
$card_type_label = (int)($posts['category_type'] ?? 1) === 2 ? 'Event' : 'News';
$card_title      = !empty($posts['title']) ? $posts['title'] : mdPlainText($posts['content'] ?? '', 60);
$card_excerpt    = mdPlainText($posts['content'] ?? '', 180);
?>
<div class="post home-post-card">
    <a class="home-post-card-thumb-wrap" href="<?php echo htmlspecialchars($card_url, ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (!empty($card_thumb)): ?>
        <img class="home-post-card-thumb-img" src="<?php echo htmlspecialchars($card_thumb, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($card_title, ENT_QUOTES, 'UTF-8'); ?>"/>
        <?php else: ?>
        <div class="home-post-card-thumb-placeholder"></div>
        <?php endif; ?>
        <?php if ((int)($posts['category_type'] ?? 1) === 2): ?><span class="home-post-card-badge"><?php echo $card_type_label; ?></span><?php endif; ?>
    </a>
    <div class="home-post-card-body">
        <div class="home-post-card-author-row">
            <div class="home-post-card-avatar">
                <a href="<?php echo $_page_base_url.'/account/posts?uid='.$posts['member_id']; ?>">
                    <img src="asset/image/icon-member.png" alt=""/>
                    <?php if (!empty($posts['avatar'])): ?>
                    <?php if (file_exists(public_path('upload/member_avatar/'.$posts['avatar']))): ?>
                    <div style="background-image:url('<?php echo 'upload/member_avatar/'.$posts['avatar']; ?>')"></div>
                    <?php elseif (file_exists(public_path('upload/member_logo/'.$posts['avatar']))): ?>
                    <div style="background-image:url('<?php echo 'upload/member_logo/'.$posts['avatar']; ?>')"></div>
                    <?php endif; ?>
                    <?php endif; ?>
                </a>
            </div>
            <div class="home-post-card-author-info">
                <a class="home-post-card-author-name" href="<?php echo $_page_base_url.'/account/posts?uid='.$posts['member_id']; ?>"><?php echo htmlspecialchars($posts['alias_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a>
                <span class="home-post-card-date"><?php echo date('d M Y', strtotime($posts['created_at'])); ?></span>
            </div>
            <?php if (!empty($_show_current_member) && $_show_current_member['id'] == $posts['member_id']): ?>
            <a class="home-post-card-edit-btn" id="edit-publish-posts" href="#" data-id="<?php echo $posts['id']; ?>"><i class="fa fa-pencil-square-o"></i></a>
            <?php endif; ?>
        </div>
        <a class="home-post-card-title-link" href="<?php echo htmlspecialchars($card_url, ENT_QUOTES, 'UTF-8'); ?>">
            <h3 class="home-post-card-title"><?php echo htmlspecialchars($card_title, ENT_QUOTES, 'UTF-8'); ?></h3>
        </a>
        <p class="home-post-card-excerpt"><?php echo htmlspecialchars($card_excerpt, ENT_QUOTES, 'UTF-8'); ?></p>
        <div class="home-post-card-footer">
            <a class="home-post-card-action do-toapply" data-id="<?php echo $posts['id']; ?>" data-post-title="<?php echo htmlspecialchars($card_title ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-post-summary="<?php echo htmlspecialchars($card_excerpt ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-action-url="<?php echo $postActionUrl; ?>" data-sector="<?php echo $postSector; ?>"<?php if ($_postPresetMsg) echo ' data-preset-msg="'.htmlspecialchars($_postPresetMsg, ENT_QUOTES, 'UTF-8').'"'; ?> href="javascript:void(0);"><?php echo $postActionLabel; ?></a>
            <?php if (!empty($_current_member) && ((int)($_current_member['type'] ?? 0) !== 3 || strpos(mb_strtolower(trim($_current_member['email'] ?? ''), 'UTF-8'), '@wealthskey.com') !== false)): ?>
            <a class="home-post-card-action do-post-talk-agent" href="javascript:void(0);">Talk to Registered Agent</a>
            <?php endif; ?>
            <?php if (!empty($posts['total_like']) && (int)$posts['total_like'] > 0): ?>
            <span class="home-post-card-likes"><i class="fa fa-heart"></i> <?php echo number_format((int)$posts['total_like']); ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php else: ?>
<div class="post">
    <div>
        <div class="author">
            <div class="avatar">
                <a href="<?php echo $_page_base_url.'/account/posts?uid='.$posts['member_id']; ?>">
                    <img src="asset/image/icon-member.png" alt="icon-member"/>
                    <?php if(!empty($posts['avatar'])){ ?>
                    <?php if(file_exists(public_path('upload/member_avatar/'.$posts['avatar']))) { ?>
                    <div style="background-image:url('<?php echo 'upload/member_avatar/'.$posts['avatar']; ?>')"></div>
                    <?php } elseif(file_exists(public_path('upload/member_logo/'.$posts['avatar']))) { ?>
                    <div style="background-image:url('<?php echo 'upload/member_logo/'.$posts['avatar']; ?>')"></div>
                    <?php } ?>
                    <?php } ?>
                </a>
            </div>
            <div class="name">
                <div><a href="<?php echo $_page_base_url.'/account/posts?uid='.$posts['member_id']; ?>"><strong><?php echo $posts['alias_name']; ?></strong></a></div>
                <div class="hours">
                    <?php echo date('d/m/Y', strtotime($posts['created_at'])); ?> &#x2022; <img src="asset/image/icon-earth.png" alt="icon-earth" width="16"/>
                </div>
            </div>
        </div>
        
        <?php if(!empty($_show_current_member) && $_show_current_member['id'] == $posts['member_id']) { ?>
        <div class="follow">
            <a id="edit-publish-posts" href="#" data-id="<?php echo $posts['id']; ?>"><i class="fa fa-pencil-square-o"></i></a>
        </div>
        <?php } else { ?>
        <div class="follow" style="display:none;">
            <a href="#">+ <?php echo $_page_lang['follow']; ?></a>
        </div>
        <?php } ?>
        
        <div class="clearboth"></div>

        <div class="details">
            <?php if(!empty($posts['title'])) { ?>
            <h3><?php echo nl2br($posts['title']); ?></h3>
            <?php } ?>
            <div class="iweb-editor">
                <div class="article-short-content">
                    <?php
                    $rawContent   = $posts['content'] ?? '';
                    $plainExcerpt = mdPlainText($rawContent, 240);
                    $isTruncated  = mb_strlen(mdPlainText($rawContent)) > 240;
                    ?>
                    <span class="post-card-excerpt"><?php echo htmlspecialchars($plainExcerpt, ENT_QUOTES, 'UTF-8'); ?><?php echo $isTruncated ? '…' : ''; ?></span>
                    <?php if ($isTruncated): ?>
                    <a class="load-fullcontent" data-id="<?php echo $posts['id']; ?>" style="color:blue;white-space:nowrap;"><u><?php echo $_page_lang['btn.read_more']; ?></u></a>
                    <?php endif; ?>
                </div>

                <?php if(!empty($posts['photo']) && file_exists('upload/member_posts/'.$posts['photo'])) { ?>
                <p style="text-align:center;"><img src="<?php echo 'upload/member_posts/'.$posts['photo'];?>"></p>
                <?php } ?>

                <?php if(!empty($posts['youtube_url'])) { ?>
                <div>
                    <div class="iweb-responsive" data-width="1268" data-height="713">
                        <iframe class="youtube-video" src="<?php echo getYoutubeEmbedUrl($posts['youtube_url']); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                    </div>
                </div>
                <?php } ?>
                <div class="clearboth"></div>
            </div>
        </div>
        <div class="clearboth"></div>

        <div class="summary">
            <div class="actions">
                <a class="do-toapply" data-id="<?php echo $posts['id']; ?>" data-post-title="<?php echo htmlspecialchars($posts['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-post-summary="<?php echo htmlspecialchars(strip_tags((string)($posts['content'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" data-action-url="<?php echo $postActionUrl; ?>" data-sector="<?php echo $postSector; ?>"<?php if ($_postPresetMsg) echo ' data-preset-msg="'.htmlspecialchars($_postPresetMsg, ENT_QUOTES, 'UTF-8').'"'; ?>>
                    <img src="asset/image/icon-apply.png" alt="icon-action"/>
                    <span><?php echo $postActionLabel; ?></span>
                </a>
                <?php if (!empty($_current_member) && ((int)($_current_member['type'] ?? 0) !== 3 || strpos(mb_strtolower(trim($_current_member['email'] ?? ''), 'UTF-8'), '@wealthskey.com') !== false)): ?>
                <a class="do-post-talk-agent" href="javascript:void(0);">
                    <span>Talk to Registered Agent</span>
                </a>
                <?php endif; ?>
                <?php if (!empty($posts['total_like']) && (int)$posts['total_like'] > 0): ?>
                <span class="post-like-count"><i class="fa fa-heart"></i> <?php echo number_format((int)$posts['total_like']); ?></span>
                <?php endif; ?>
                <a class="do-share">
                    <img src="asset/image/icon-share.png" alt="icon-share"/>
                    <span><?php echo $_page_lang['share']; ?></span>
                </a>
                <div class="shareto">
                    <div>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($_page_base_url.'/posts/details/'.$posts['id']); ?>" target="_blank">
                            <img src="asset/image/icon-facebook-48.png" alt="icon-facebook">
                        </a>
                        <a href="https://wa.me/?text=<?php echo urlencode($_page_base_url.'/posts/details/'.$posts['id']); ?>" target="_blank">
                            <img src="asset/image/icon-whatsapp-48.png" alt="icon-whatsapp">
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php }} ?>

<?php
if (!function_exists('time2Units')) {
function time2Units($time, $lang = 1) {
    $year = floor($time / 60 / 60 / 24 / 365);
    $time -= $year * 60 * 60 * 24 * 365;
    $month = floor($time / 60 / 60 / 24 / 30);
    $time -= $month * 60 * 60 * 24 * 30;
    $week = floor($time / 60 / 60 / 24 / 7);
    $time -= $week * 60 * 60 * 24 * 7;
    $day = floor($time / 60 / 60 / 24);
    $time -= $day * 60 * 60 * 24;
    $hour = floor($time / 60 / 60);
    $time -= $hour * 60 * 60;
    $minute = floor($time / 60);
    $time -= $minute * 60;
    $second = max(1, $time);
    $elapse = '';
    if($lang == 2) {
        $unitArr = [
            '年' => 'year',
            '月' => 'month',
            '周' => 'week',
            '天' => 'day',
            '时' => 'hour',
            '分' => 'minute',
            '秒' => 'second',
        ];
    }
    else if($lang == 3) {
        $unitArr = [
            '年' => 'year',
            '月' => 'month',
            '周' => 'week',
            '天' => 'day',
            '时' => 'hour',
            '分' => 'minute',
            '秒' => 'second',
        ];
    }
    else {
        $unitArr = [
            'Y' => 'year',
            'M' => 'month',
            'W' => 'week',
            'D' => 'day',
            'H' => 'hour',
            'Min' => 'minute',
            'Sec' => 'second',
        ];
    }
    foreach ($unitArr as $cn => $u) {
        if ($$u > 0) {
            $elapse = $$u . $cn;
            break;
        }
    }
    return $elapse;
}
}

?>
