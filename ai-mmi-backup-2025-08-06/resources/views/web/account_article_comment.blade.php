<?php if(!empty($_page_data['reply'])) { foreach ($_page_data['reply'] as $comment) { ?>
<div class="replier" data-comment-id="<?php echo $comment['id'];?>">
    <div class="avatar">
        <a href="<?php echo $_page_base_url.'/account/posts?uid='.$comment['member_id']; ?>">
            <img src="asset/image/icon-member.png" alt="icon-member"/>
            <?php if(!empty($comment['avatar'])){ ?>
            <?php if(file_exists('upload/member_avatar/'.$comment['avatar'])) { ?>
            <div style="background-image:url('<?php echo 'upload/member_avatar/'.$comment['avatar']; ?>')"></div>
            <?php } else { ?>
            <div style="background-image:url('<?php echo 'upload/member_logo/'.$comment['avatar']; ?>')"></div>
            <?php } ?>
            <?php } ?>
        </a>
    </div>
    <div class="name">
        <div><a href="<?php echo $_page_base_url.'/account/posts?uid='.$comment['member_id']; ?>"><?php echo $comment['alias_name']; ?></a></div>
        <div class="hours">
            <?php echo time2Units(abs(strtotime($comment['created_at'])-strtotime(date('Y-m-d H:i:s'))), $_current_lang_index); ?> &#x2022; <img src="asset/image/icon-earth.png" alt="icon-earth" width="16"/>
        </div>
        <div class="message"><?php echo nl2br($comment['comment_content']); ?></div>
        <div class="comment-action">
            <!--<a href="javascript:void(0);" class="do-reply" data-id="<?php echo $comment['id'];?>">Reply</a>-->
            <a href="javascript:void(0);" class="do-delete" data-id="<?php echo $comment['id'];?>">Delete</a>
        </div>
    </div>
</div>
<?php }} ?>

<?php
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
?>