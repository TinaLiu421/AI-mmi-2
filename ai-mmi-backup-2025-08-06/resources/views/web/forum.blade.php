@extends('web.common')
@section('content')
<?php $target_country_id = (int)((!empty($_page_get_data['country']))?$_page_get_data['country']:0); ?>
<div class="inner-panel">
    <h1 class="title"><?php echo $_page_lang['forum']; ?></h1>
    <div class="underline"></div>
    <div class="clearboth"></div>
    
    <div class="cgroup">
        <ul>
            <li><a href="<?php echo $_page_base_url.'/forum'; ?>"<?php echo ($target_country_id==0)?' class="selected"':''; ?>><?php echo $_page_lang['all_options']; ?></a></li>
            <?php if(!empty($_page_options['country'])) { foreach ($_page_options['country'] as $country_id => $country_name) { ?>
            <li><a href="<?php echo $_page_base_url.'/forum?country='.$country_id; ?>"<?php echo ($target_country_id==$country_id)?' class="selected"':''; ?>><?php echo $country_name; ?></a></li>
            <?php }} ?>
        </ul>
        <div class="clearboth"></div>
    </div>
    
    <div class="topics">
        <div class="search">
            <form method="get">
                <?php if(!empty($_page_get_data['country'])) { ?>
                <input type="hidden" name="country" value="<?php echo $_page_get_data['country']; ?>">
                <?php } ?>
                <input type="text" name="keywords" value="<?php echo (!empty($_page_get_data['keywords']))?$_page_get_data['keywords']:''; ?>">
                <button type="submit" class="btn"><?php echo $_page_lang['btn.search']; ?></button>
            </form>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th><?php echo $_page_lang['forum_topic']; ?></th>
                    <th><?php echo $_page_lang['forum_author']; ?></th>
                    <th><?php echo $_page_lang['forum_last_dt']; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($_page_data['list_forum']['data'])) { foreach ($_page_data['list_forum']['data'] as $data) { ?>
                <tr>
                    <td>
                        <a href="<?php echo $_page_base_url.'/forum/details/'.$data['id']; ?>" target="_blank">
                            <span style="color:#002065;"><?php echo (!empty($_page_options['country'][$data['forum_country']]))?('['.$_page_options['country'][$data['forum_country']].']'):''; ?></span>
                            <?php echo $data['forum_topic']; ?>
                        </a>
                    </td>
                    <td>
                        <div><?php echo $data['author_name']; ?></div>
                        <div style="color:#002065;"><i class="fa fa-eye"></i> <?php echo number_format($data['total_view']); ?> | <i class="fa fa-comment"></i> <?php echo number_format($data['total_comment']); ?></div>
                    </td>
                    <td>
                        <div><?php echo time2Units(abs(strtotime($data['last_comment_at'])-strtotime(date('Y-m-d H:i:s'))), $_current_lang_index); ?></div>
                        <div><?php echo $data['publisher_name']; ?></div>
                    </td>
                </tr>
                <?php }} else { ?>
                <tr>
                    <td colspan="3" style="text-align:center;"><?php echo $_page_lang['forum_empty']; ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        
        <?php if(!empty($_page_data['list_forum']['pagination']['total_page']) && $_page_data['list_forum']['pagination']['total_page'] > 1) { ?>
        <div><div class="mypage bottom" data-totalpage="<?php echo $_page_data['list_forum']['pagination']['total_page']; ?>"></div></div>
        <?php } ?>
    </div>

    <?php if(!empty($_current_member)) { ?>
    <div class="published">
        <div class="form" style="margin-top:0px;">
            <form id="published-form" method="post">
                <div>@csrf</div>
                <div class="row" style="margin-top:0px;">
                    <label for="forum_country"><?php echo $_page_lang['country']; ?></label>
                    <select id="forum_country" name="forum_country" data-validation="required">
                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                        <?php if(!empty($_page_options['country'])) { foreach ($_page_options['country'] as $country_id => $country_name) { ?>
                        <option value="<?php echo $country_id; ?>"><?php echo $country_name; ?></option>
                        <?php }} ?>
                    </select>
                </div>
                <div class="row">
                    <label><?php echo $_page_lang['forum_topic']; ?></label>
                    <input type="text" id="forum_topic" name="forum_topic" data-validation="required">
                </div>
                <div class="row">
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