@extends('web.common')
@section('content')
<div class="banner">
    <div class="desktop">
        <?php if(!empty($_page_data['details']['media_files']['banner_'.$_current_lang_index])) { 
            foreach ($_page_data['details']['media_files']['banner_'.$_current_lang_index] as $banner) {
            ?>
        <img src="<?php echo $banner['url']; ?>" alt="<?php echo $banner['file_name']; ?>"/>
        <?php }} ?>
        <div class="countries"><!--
            --><div class="iweb-responsive" data-width="1300" data-height="245">
                <h2><?php echo (!empty($_page_data['target_country']))?$_page_data['target_country']['title']:''; ?></h2>
                <select class="switch-country" data-virtual="1" data-default="<?php echo $_page_lang['please_select_country']; ?>">
                    <option value=""><?php echo $_page_lang['please_select_country']; ?></option>
                    <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                    <option value="<?php echo $country_id; ?>"><?php echo $country; ?></option>
                    <?php }} ?>
                </select>
            </div><!--
        --></div>
    </div>
    <div class="mobile">
        <?php if(!empty($_page_data['details']['media_files']['mobile_banner_'.$_current_lang_index])) { 
            foreach ($_page_data['details']['media_files']['mobile_banner_'.$_current_lang_index] as $banner) {
            ?>
        <img src="<?php echo $banner['url']; ?>" alt="<?php echo $banner['file_name']; ?>"/>
        <?php }} ?>
        <div class="countries"><!--
            --><div class="iweb-responsive" data-width="800" data-height="400">
                <h2><?php echo (!empty($_page_data['target_country']))?$_page_data['target_country']['title']:''; ?></h2>
                <select class="switch-country" data-virtual="1" data-default="<?php echo $_page_lang['please_select_country']; ?>">
                    <option value=""><?php echo $_page_lang['please_select_country']; ?></option>
                    <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                    <option value="<?php echo $country_id; ?>"><?php echo $country; ?></option>
                    <?php }} ?>
                </select>
            </div><!--
        --></div>
    </div>
</div>

<div class="inner-panel">
    <h1 class="title"><?php echo $_page_lang['visa_options']; ?></h1>
    <div class="underline"></div>
    <div class="clearboth"></div>
    
    <?php if(!empty($_page_data['details']['content'])) { ?>
    <div>&nbsp;</div>
    <div>&nbsp;</div>
    <div class="iweb-editor">
        <?php echo $_page_data['details']['content']; ?>
        <div class="clearboth"></div>
    </div>
    <?php } ?>
   
    <?php if(!empty($_page_data['list'])) { ?>
    <div class="list"><div><!--
        <?php foreach ($_page_data['list'] as $key => $data) { ?>
        --><div class="block">
            <div>
                <div class="photo">
                    <img src="<?php echo $data['photo_url']; ?>">
                    <div><div class="title"><?php echo $data['title']; ?></div></div>
                </div>
                <?php if(!empty($data['child_node'])) { ?>
                <div>
                    <ul>
                        <?php foreach ($data['child_node'] as $child) { ?>
                        <li>
                            <a href="<?php echo $_page_base_url.'/visa_options/details/'.$child['id']; ?>"><?php echo $child['title']; ?></a>
                        </li>
                        <?php } ?>
                    </ul>
                </div>
                <?php } ?>
            </div>
        </div><!--
        <?php } ?>
    --></div></div>
    <?php } ?>
    
    <?php if(!empty($_page_data['list_news'])) { ?>
    <div class="news">
        <h1 class="title"><?php echo $_page_lang['posts.category_type_1']; ?></h1>
        <div class="underline"></div>
        <div class="clearboth"></div>
        <div class="slider">
            <?php if(count($_page_data['list_news']) > 6) { ?>
            <div class="controls">
                <a class="prev-item-set" data-index="1" href="#"><i></i></a>
                <a class="next-item-set" data-index="1" href="#"><i></i></a>
            </div>
            <?php } ?>
            <div class="item-slider">
                <?php 
                foreach ($_page_data['list_news'] as $news_key => $news) { 
                    if($news_key == 0 || ($news_key%6) == 0) {
                        echo '<div class="group"><div>';
                    }
                    echo '<div class="block">'; 
                    ?>
                    <a class="link" href="<?php echo $news['url']; ?>">
                        <div class="photo">
                            <img src="<?php echo $news['thumbnail']; ?>" alt=""/>
                            <?php if(empty($news['photo']) && !empty($news['youtube_url'])) { ?>
                            <iframe src="<?php echo $news['youtube_url']; ?>"></iframe>
                            <?php } ?>
                        </div>
                        <div class="title">
                            <?php echo $news['title']; ?>
                        </div>
                    </a>
                    <?php 
                    echo '</div>'; 
                    if(($news_key+1) == count($_page_data['list_news']) || (($news_key+1)%6) == 0) {
                        echo '</div></div>';
                    }
                } ?>
            </div>
        </div>
    </div>
    <?php } ?>

    <?php if(!empty($_page_data['list_events'])) { ?>
    <div class="events">
        <h1 class="title"><?php echo $_page_lang['posts.category_type_2']; ?></h1>
        <div class="underline"></div>
        <div class="clearboth"></div>
        <div class="slider">
            <?php if(count($_page_data['list_events']) > 6) { ?>
            <div class="controls">
                <a class="prev-item-set" data-index="1" href="#"><i></i></a>
                <a class="next-item-set" data-index="1" href="#"><i></i></a>
            </div>
            <?php } ?>
            <div class="item-slider">
                <?php 
                foreach ($_page_data['list_events'] as $events_key => $events) { 
                    if($events_key == 0 || ($events_key%6) == 0) {
                        echo '<div class="group"><div>';
                    }
                    echo '<div class="block">'; 
                    ?>
                    <a class="link" href="<?php echo $events['url']; ?>">
                        <div class="photo">
                            <img src="<?php echo $events['thumbnail']; ?>" alt=""/>
                            <?php if(empty($events['photo']) && !empty($events['youtube_url'])) { ?>
                            <iframe src="<?php echo $events['youtube_url']; ?>"></iframe>
                            <?php } ?>
                        </div>
                        <div class="title">
                            <?php echo $events['title']; ?>
                        </div>
                    </a>
                    <?php 
                    echo '</div>'; 
                    if(($events_key+1) == count($_page_data['list_events']) || (($events_key+1)%6) == 0) {
                        echo '</div></div>';
                    }
                } ?>
            </div>
        </div>
    </div>
    <?php } ?>
    
    <?php if(!empty($_page_data['agents'])) { ?>

    <div class="agents">
        <h1 class="title"><?php echo $_page_lang['list_agents']; ?></h1>
        <div class="underline"></div>
        <div class="clearboth"></div>
        <div class="list">
            <?php foreach ($_page_data['agents'] as $agent) { ?>
            <div class="related2">
                <div class="author">
                    <div class="avatar">
                        <img src="asset/image/icon-member.png" alt="icon-member"/>
                        <?php if(!empty($agent['avatar'])){ ?>
                        <?php if(file_exists('upload/member_avatar/'.$agent['avatar'])) { ?>
                        <div style="background-image:url('<?php echo 'upload/member_avatar/'.$agent['avatar']; ?>')"></div>
                        <?php } else { ?>
                        <div style="background-image:url('<?php echo 'upload/member_logo/'.$agent['avatar']; ?>')"></div>
                        <?php } ?>
                        <?php } ?>
                    </div>
                </div><div class="info">
                    <table>
                        <tr>
                            <td><strong><?php echo $_page_lang['account.company_name']; ?>:</strong> </td>
                            <td>&nbsp;&nbsp;</td>
                            <td><?php echo $agent['alias_name']; ?></td>
                        </tr>

                        <?php if((int)$agent['agent_registration_country'] == $_page_data['target_country_id']) { ?>
                        <?php if(!empty($agent['agent_full_name'])) { ?>
                        <tr>
                            <td><strong><?php echo $_page_lang['account.agent_name']; ?>:</strong> </td>
                            <td>&nbsp;&nbsp;</td>
                            <td><?php echo $agent['agent_full_name']; ?></td>
                        </tr>
                        <?php } ?>
                        <?php if(!empty($agent['agent_registration_num'])) { ?>
                        <tr>
                            <td><strong><?php echo $_page_lang['account.registration_num']; ?>:</strong> </td>
                            <td>&nbsp;&nbsp;</td>
                            <td><?php echo $agent['agent_registration_num']; ?></td>
                        </tr>
                        <?php } ?>
                        <?php } ?>
                        <tr>
                            <td><strong><?php echo $_page_lang['account.telephone']; ?>:</strong> </td>
                            <td>&nbsp;&nbsp;</td>
                            <td><?php echo ($agent['telephone_code'].' '.$agent['telephone_num']); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php echo $_page_lang['account.email']; ?>:</strong> </td>
                            <td>&nbsp;&nbsp;</td>
                            <td><?php echo $agent['email']; ?></td>
                        </tr>

                        <?php if(!empty($agent['company_website'])) { ?>
                        <tr>
                            <td><strong><?php echo $_page_lang['account.company_website']; ?>:</strong> </td>
                            <td>&nbsp;&nbsp;</td>
                            <td><?php echo $agent['company_website']; ?></td>
                        </tr>
                        <?php } ?>
                    </table>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
    
    <?php if(!empty($_page_data['service_providers'])) { ?>
    <div class="service-providers">
        <h1 class="title"><?php echo $_page_lang['list_service_providers']; ?></h1>
        <div class="underline"></div>
        <div class="clearboth"></div>
        <div class="related">
            <div><!--
                <?php foreach ($_page_data['service_providers'] as $parent) { foreach ($parent['child_node'] as $service_provider) { ?>
                --><div class="author">
                    <a href="<?php echo $_page_base_url.'/account/profile?uid='.$service_provider['id']; ?>">
                        <div class="avatar">
                            <img src="asset/image/icon-member.png" alt="icon-member"/>
                            <?php if(!empty($service_provider['avatar'])){ ?>
                            <?php if(file_exists('upload/member_avatar/'.$service_provider['avatar'])) { ?>
                            <div style="background-image:url('<?php echo 'upload/member_avatar/'.$service_provider['avatar']; ?>')"></div>
                            <?php } else { ?>
                            <div style="background-image:url('<?php echo 'upload/member_logo/'.$service_provider['avatar']; ?>')"></div>
                            <?php } ?>
                            <?php } ?>
                        </div>
                        <div class="name">
                            <?php echo $service_provider['alias_name']; ?>
                        </div>
                    </a>
                </div><!--
                <?php }} ?>
            --></div>
        </div>
    </div>
    <?php } ?>
</div>
@endsection