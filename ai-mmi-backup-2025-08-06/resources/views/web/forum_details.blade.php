@extends('web.common')
@section('content')
<?php 
$target_forum = $_page_data['target_forum']; 
$list_forum_comment = $_page_data['list_forum_comment']; 
?>
<div class="inner-panel">
    <h1 class="title">
        <?php echo (!empty($_page_options['country'][$target_forum['forum_country']]))?('['.$_page_options['country'][$target_forum['forum_country']].']'):''; ?>
        <?php echo $target_forum['forum_topic']; ?>
    </h1>
    <div class="underline"></div>
    <div class="clearboth"></div>
    
    <div class="details-list">
        <div class="post">
            <div class="total" style="color:#002065;">
                <i class="fa fa-eye"></i> <?php echo number_format($target_forum['total_view']); ?> | <i class="fa fa-comment"></i> <?php echo number_format($target_forum['total_comment']); ?>
            </div>
            <div class="author">
                <div class="avatar">
                    <a href="<?php echo $_page_base_url.'/account/posts?uid='.$target_forum['member_id']; ?>">
                        <img src="asset/image/icon-member.png" alt="icon-member"/>
                        <?php if(!empty($target_forum['author_avatar'])){ ?>
                        <?php if(file_exists('upload/member_avatar/'.$target_forum['author_avatar'])) { ?>
                        <div style="background-image:url('<?php echo 'upload/member_avatar/'.$target_forum['author_avatar']; ?>')"></div>
                        <?php } else { ?>
                        <div style="background-image:url('<?php echo 'upload/member_logo/'.$target_forum['author_avatar']; ?>')"></div>
                        <?php } ?>
                        <?php } ?>
                    </a>
                </div>
                <div class="name">
                    <div><a href="<?php echo $_page_base_url.'/account/posts?uid='.$target_forum['member_id']; ?>"><?php echo $target_forum['author_name']; ?></a></div>
                    <div class="hours">
                        <?php echo time2Units(abs(strtotime($target_forum['first_comment_at'])-strtotime(date('Y-m-d H:i:s'))), $_current_lang_index); ?> &#x2022; <img src="asset/image/icon-earth.png" alt="icon-earth" width="16"/>
                    </div>
                </div>
            </div>
            <div class="brief">
                <?php echo nl2br($target_forum['forum_content']); ?>
                <div class="clearboth"></div>
            </div>
            <div class="pdt"><?php echo $target_forum['first_comment_at']; ?></div>
            <div class="clearboth"></div>
        </div>
        
        <?php if(!empty($list_forum_comment['data'])) { foreach ($list_forum_comment['data'] as $sub_key => $sub_data) { ?>
        <div class="post">
            <div class="author">
                <div class="avatar">
                    <a href="<?php echo $_page_base_url.'/account/posts?uid='.$sub_data['member_id']; ?>">
                        <img src="asset/image/icon-member.png" alt="icon-member"/>
                        <?php if(!empty($sub_data['publisher_avatar'])){ ?>
                        <?php if(file_exists('upload/member_avatar/'.$sub_data['publisher_avatar'])) { ?>
                        <div style="background-image:url('<?php echo 'upload/member_avatar/'.$sub_data['publisher_avatar']; ?>')"></div>
                        <?php } else { ?>
                        <div style="background-image:url('<?php echo 'upload/member_logo/'.$sub_data['publisher_avatar']; ?>')"></div>
                        <?php } ?>
                        <?php } ?>
                    </a>
                </div>
                <div class="name">
                    <div><a href="<?php echo $_page_base_url.'/account/posts?uid='.$sub_data['member_id']; ?>"><?php echo $sub_data['publisher_name']; ?></a></div>
                    <div class="hours">
                        <?php echo time2Units(abs(strtotime($sub_data['last_comment_at'])-strtotime(date('Y-m-d H:i:s'))), $_current_lang_index); ?> &#x2022; <img src="asset/image/icon-earth.png" alt="icon-earth" width="16"/>
                    </div>
                </div>
            </div>
            <div class="brief">
                <?php echo nl2br($sub_data['forum_content']); ?>
                <div class="clearboth"></div>
            </div>
            <div class="pdt"><?php echo $sub_data['last_comment_at']; ?></div>
            <div class="clearboth"></div>
        </div>
        <?php }} ?>
        
        <?php if(!empty($list_forum_comment['pagination']['total_page']) && $list_forum_comment['pagination']['total_page'] > 1) { ?>
        <div><div class="mypage bottom" data-totalpage="<?php echo $list_forum_comment['pagination']['total_page']; ?>"></div></div>
        <?php } ?>
    </div>

    <?php if(!empty($_current_member)) { ?>
    <div class="published">
        <div class="form" style="margin-top:0px;">
            <form id="published-form" method="post">
                <div>@csrf</div>
                <input type="hidden" id="forum_id" name="forum_id" value="<?php echo ($target_forum['id']); ?>" data-validation="required">
                <div class="row" style="margin-top:0px;">
                    <label><?php echo $_page_lang['forum_content']; ?></label>
                    <textarea id="forum_content" name="forum_content" data-validation="required"></textarea>
                </div>
                <div class="row">
                    <button type="submit" class="btn"><?php echo $_page_lang['btn.pubish']; ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php } ?>
</div>
@endsection

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