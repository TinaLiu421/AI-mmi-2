<?php $posts = $_page_data['details']; ?>
@extends('web.common')
@section('content')
<div class="inner-panel">
    <div class="article-list">
        <div class="post">
            <div>
                <div class="author">
                    <div class="avatar">
                        <a href="<?php echo $_page_base_url.'/account/posts?uid='.$posts['member_id']; ?>">
                            <img src="asset/image/icon-member.png" alt="icon-member"/>
                            <?php if(!empty($posts['avatar'])){ ?>
                            <?php if(file_exists('upload/member_avatar/'.$posts['avatar'])) { ?>
                            <div style="background-image:url('<?php echo 'upload/member_avatar/'.$posts['avatar']; ?>')"></div>
                            <?php } else { ?>
                            <div style="background-image:url('<?php echo 'upload/member_logo/'.$posts['avatar']; ?>')"></div>
                            <?php } ?>
                            <?php } ?>
                        </a>
                    </div>
                    <div class="name">
                        <div><a href="<?php echo $_page_base_url.'/account/posts?uid='.$posts['member_id']; ?>"><?php echo $posts['alias_name']; ?></a></div>
                        <div class="hours">
                            <?php echo date('d/m/Y', strtotime($posts['created_at'])); ?> &#x2022; <img src="asset/image/icon-earth.png" alt="icon-earth" width="16"/>
                        </div>
                    </div>
                </div>

                <?php if(!empty($_current_member) && $_current_member['id'] == $posts['member_id']) { ?>
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
                    <div>&nbsp;</div>
                    <?php } ?>
                    <div class="iweb-editor">
                        <p><?php echo nl2br($posts['content']); ?></p>

                        <?php if(!empty($posts['photo']) && file_exists('upload/member_posts/'.$posts['photo'])) { ?>
                        <p>&nbsp;</p>
                        <p style="text-align:center;"><img src="<?php echo 'upload/member_posts/'.$posts['photo'];?>"></p>
                        <?php } ?>

                        <?php if(!empty($posts['youtube_url'])) { ?>
                        <p>&nbsp;</p>
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
                    <div class="total">
                        <div class="like">
                            <img src="asset/image/icon-like-blue.png" alt="icon-like-blue"/>
                            <span><?php echo number_format((int)$posts['total_like']); ?></span>
                        </div>
                        <div class="comment">
                            <?php echo str_replace('{num}', '<span>'.number_format((int)$posts['total_comment']).'</span>', $_page_lang['total_comments']); ?>
                        </div>
                        <div class="clearboth"></div>
                    </div>
                    <div class="actions">
                        <a class="do-like" data-id="<?php echo $posts['id']; ?>">
                            <img src="asset/image/icon-like.png" alt="icon-like"/>
                            <span><?php echo $_page_lang['like']; ?></span>
                        </a>
                        <a class="do-comment" data-id="<?php echo $posts['id']; ?>">
                            <img src="asset/image/icon-comment.png" alt="icon-comment"/>
                            <span><?php echo $_page_lang['comment']; ?></span>
                        </a>
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
                <div class="clearboth"></div>

                <div class="leavecomment">
                    <div>
                        <div class="reply">
                        </div>
                        <textarea name="message" placeholder="<?php echo $_page_lang['account.enter_comment']; ?>"></textarea>
                        <button type="button" class="btn-send-comment" data-id="<?php echo $posts['id']; ?>"><img src="asset/image/icon-send.png" alt="icon-send"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
?>