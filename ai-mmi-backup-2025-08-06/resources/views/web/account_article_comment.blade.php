<?php
if (!function_exists('commentAvatarUrl')) {
function commentAvatarUrl($avatar = '') {
    $avatar = trim((string)$avatar);
    if ($avatar === '') return '';
    if (stripos($avatar, 'asset/') === 0 || preg_match('/^https?:\/\//i', $avatar)) {
        return $avatar;
    }
    if (file_exists(public_path('upload/member_avatar/'.$avatar))) {
        return 'upload/member_avatar/'.$avatar;
    }
    if (file_exists(public_path('upload/member_logo/'.$avatar))) {
        return 'upload/member_logo/'.$avatar;
    }
    return $avatar;
}
}

if(!empty($_page_data['reply'])) { foreach ($_page_data['reply'] as $comment) {
$isAi = (int)($comment['status'] ?? 0) === 2 || strcasecmp($comment['alias_name'] ?? '', 'AI-mmi') === 0;
$avatarUrl = commentAvatarUrl($comment['avatar'] ?? '');
if ($isAi) {
    $avatarUrl = 'asset/image/logo-mmi.png';
} elseif ($avatarUrl === '') {
    $avatarUrl = 'asset/image/icon-member.png';
}
$name = $isAi ? 'AI-mmi' : ($comment['alias_name'] ?? 'User');
$profileUrl = $isAi ? '#' : ($_page_base_url.'/account/posts?uid='.$comment['member_id']);
?>
<div class="replier <?php echo $isAi ? 'ai-reply' : ''; ?>" data-comment-id="<?php echo $comment['id'];?>" data-status="<?php echo (int)($comment['status'] ?? 0); ?>" data-is-ai="<?php echo $isAi ? 1 : 0; ?>">
    <div class="avatar">
        <a href="<?php echo $profileUrl; ?>">
            <img src="asset/image/icon-member.png" alt="icon-member"/>
            <?php if(!empty($avatarUrl)) { ?>
            <div style="background-image:url('<?php echo $avatarUrl; ?>')"></div>
            <?php } ?>
        </a>
    </div>
    <div class="name">
        <div>
            <a href="<?php echo $profileUrl; ?>"><?php echo $name; ?></a>
            <?php if($isAi) { ?><span class="badge">Assistant</span><?php } ?>
        </div>
        <div class="hours">
            <?php echo time2Units(abs(strtotime($comment['created_at'])-strtotime(date('Y-m-d H:i:s'))), $_current_lang_index); ?> &#x2022; <img src="asset/image/icon-earth.png" alt="icon-earth" width="16"/>
        </div>
        <div class="message"><?php echo nl2br($comment['comment_content']); ?></div>
    </div>
</div>
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
