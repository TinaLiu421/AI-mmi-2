@extends('admin.common')
@section('content')
<?php 
$target_posts = $_page_data['target_posts']; 
$list_posts_comment = $_page_data['list_posts_comment'];
?>
<div class="inner-panel">

    <div class="widget thin fixed-top">
        <div class="controls right">
            <button id="delete-my-posts" type="button" class="btn btn-red" data-id="<?php echo $target_posts['id'];?>">
                <i class="fa fa-trash"></i>
                <span><?php echo $_page_lang['delete']; ?></span>
            </button>
            
            <button id="edit-my-posts" type="button" class="btn btn-green" data-id="<?php echo $target_posts['id'];?>">
                <i class="fa fa-pencil"></i>
                <span><?php echo $_page_lang['edit']; ?></span>
            </button>
        </div>
        <div class="clearboth"></div>
    </div>
    
    <div class="details-list">
        <div class="posts">
            <div class="total" style="color:#002065;">
                <i class="fa fa-thumbs-up"></i> <?php echo number_format((int)$target_posts['total_like']); ?> | <i class="fa fa-comment"></i> <?php echo number_format((int)$target_posts['total_comment']); ?>
            </div>
            <div class="author">
                <div class="avatar">
                    <a href="<?php echo $_page_base_url.'/account/posts?uid='.$target_posts['member_id']; ?>">
                        <img src="asset/image/icon-member.png" alt="icon-member"/>
                        <?php if(!empty($target_posts['avatar'])){ ?>
                        <?php if(file_exists('upload/member_avatar/'.$target_posts['avatar'])) { ?>
                        <div style="background-image:url('<?php echo 'upload/member_avatar/'.$target_posts['avatar']; ?>')"></div>
                        <?php } else { ?>
                        <div style="background-image:url('<?php echo 'upload/member_logo/'.$target_posts['avatar']; ?>')"></div>
                        <?php } ?>
                        <?php } ?>
                    </a>
                </div>
                <div class="name">
                    <div><a href="<?php echo $_page_base_url.'/account/posts?uid='.$target_posts['member_id']; ?>"><?php echo $target_posts['alias_name']; ?></a></div>
                    <div class="hours">
                        <?php echo date('d/m/Y', strtotime($target_posts['created_at'])); ?> &#x2022; <img src="asset/image/icon-earth.png" alt="icon-earth" width="16"/>
                    </div>
                </div>
            </div>
            <div class="brief">
                <div>
                    <?php if(!empty($target_posts['title'])) { ?>
                    <h3><?php echo nl2br($target_posts['title']); ?></h3>
                    <?php } ?>
                    
                    <p><?php echo nl2br($target_posts['content']); ?></p>

                    <?php if(!empty($target_posts['photo']) && file_exists('upload/member_posts/'.$target_posts['photo'])) { ?>
                    <p>&nbsp;</p>
                    <p style="text-align:center;"><img src="<?php echo 'upload/member_posts/'.$target_posts['photo'];?>"></p>
                    <?php } ?>

                    <?php if(!empty($target_posts['youtube_url'])) { ?>
                    <p>&nbsp;</p>
                    <div>
                        <div class="iweb-responsive" data-width="1268" data-height="713">
                            <iframe class="youtube-video" style="width:100%;height:100%;" src="<?php echo getYoutubeEmbedUrl($target_posts['youtube_url']); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                        </div>
                    </div>
                    <?php } ?>
                    <div class="clearboth"></div>
                </div>
            </div>
            
            <div class="pdt"><?php echo $target_posts['created_at']; ?></div>
            <div class="clearboth"></div>
        </div>
        
        <?php if(!empty($list_posts_comment)) { foreach ($list_posts_comment as $sub_key => $sub_data) { ?>
        <div class="posts">
            <a class="remove-posts-post" data-id="<?php echo $sub_data['id']; ?>"><i class="fa fa-times"></i></a>
            <div class="author">
                <div class="avatar">
                    <a href="<?php echo $_page_base_url.'/account/posts?uid='.$sub_data['member_id']; ?>">
                        <img src="asset/image/icon-member.png" alt="icon-member"/>
                        <?php if(!empty($sub_data['avatar'])){ ?>
                        <?php if(file_exists('upload/member_avatar/'.$sub_data['avatar'])) { ?>
                        <div style="background-image:url('<?php echo 'upload/member_avatar/'.$sub_data['avatar']; ?>')"></div>
                        <?php } else { ?>
                        <div style="background-image:url('<?php echo 'upload/member_logo/'.$sub_data['avatar']; ?>')"></div>
                        <?php } ?>
                        <?php } ?>
                    </a>
                </div>
                <div class="name">
                    <div><a href="<?php echo $_page_base_url.'/account/posts?uid='.$sub_data['member_id']; ?>"><?php echo $sub_data['alias_name']; ?></a></div>
                    <div class="hours">
                        <?php echo date('d/m/Y', strtotime($sub_data['created_at'])); ?> &#x2022; <img src="asset/image/icon-earth.png" alt="icon-earth" width="16"/>
                    </div>
                </div>
            </div>
            <div class="brief">
                <?php echo nl2br($sub_data['comment_content']); ?>
                <div class="clearboth"></div>
            </div>
            <div class="pdt"><?php echo $sub_data['created_at']; ?></div>
            <div class="clearboth"></div>
        </div>
        <?php }} ?>
        
        <?php if(!empty($list_posts_comment['pagination']['total_page']) && $list_posts_comment['pagination']['total_page'] > 1) { ?>
        <div><div class="mypage bottom" data-totalpage="<?php echo $list_posts_comment['pagination']['total_page']; ?>"></div></div>
        <script>iweb.pagination('div.mypage');</script>
        <?php } ?>
    </div>
</div>
@endsection

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

if (!function_exists('getYoutubeEmbedUrl')) {
function getYoutubeEmbedUrl($url) {
     $shortUrlRegex = '/youtu.be\/([a-zA-Z0-9_-]+)\??/i';
     $longUrlRegex = '/youtube.com\/((?:embed)|(?:watch))((?:\?v\=)|(?:\/))([a-zA-Z0-9_-]+)/i';

    if (preg_match($longUrlRegex, $url, $matches)) {
        $youtube_id = $matches[count($matches) - 1];
    }

    if (preg_match($shortUrlRegex, $url, $matches)) {
        $youtube_id = $matches[count($matches) - 1];
    }
    return 'https://www.youtube.com/embed/' . $youtube_id ;
}
}
?>