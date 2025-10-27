@extends('web.common')
@section('content')
<?php $_show_current_member = $_page_data['show_current_member']; ?>
<?php $_show_current_member_details = (!empty($_page_data['current_member_details']))?$_page_data['current_member_details']:[]; ?>
<?php $_show_current_member_agent = (!empty($_page_data['current_member_agent']))?$_page_data['current_member_agent']:[]; ?>
<?php $_show_current_member_lawfirm = (!empty($_page_data['current_member_lawfirm']))?$_page_data['current_member_lawfirm']:[]; ?>
<div class="inner-panel full">
    <?php if(!empty($_show_current_member['coverphoto']) && file_exists('upload/member_coverphoto/'.$_show_current_member['coverphoto'])) { ?>
    <div class="banner" style="background-image:url('<?php echo 'upload/member_coverphoto/'.$_show_current_member['coverphoto']; ?>')"></div>
    <?php } else { ?>
    <div class="banner" style="display:none;"></div>
    <?php } ?>
    <div class="basic">
        <div class="photo">
            <?php if(file_exists('upload/member_avatar/'.$_show_current_member['avatar'])) { ?>
            <div class="avatar" style="background-image:url('<?php echo 'upload/member_avatar/'.$_show_current_member['avatar']; ?>')"></div>
            <?php } else if(file_exists('upload/member_logo/'.$_show_current_member['avatar'])) { ?>
            <div class="avatar" style="background-image:url('<?php echo 'upload/member_logo/'.$_show_current_member['avatar']; ?>')"></div>
            <?php } else { ?>
            <img src="asset/image/icon-member.png" alt="icon-member"/>
            <?php } ?>
            <?php if(empty($_page_data['is_readonly'])) { ?>
            <a id="myavatar" class="camera"><i class="fa fa-camera"></i></a>
            <?php } ?>
        </div>
        <div class="name">
            <div class="alias">
                <div class="readonly">
                    <span><?php echo $_show_current_member['alias_name']; ?></span>
                </div>
                <?php if(empty($_page_data['is_readonly'])) { ?>
                <a><img src="asset/image/icon-edit.png"></a>
                <?php } ?>
            </div>
            <div class="total-followers">0 followers</div>
        </div>
        <div class="clearboth"></div>
        <div class="tab">
            <a class="posts selected"><?php echo $_page_lang['tab_posts']; ?></a>
            <a class="about" href="<?php echo $_page_base_url.'/account/profile'.((!empty($_page_get_data['uid']))?'?uid='.$_page_get_data['uid']:''); ?>"><?php echo $_page_lang['tab_about']; ?></a>
        </div>
    </div>
    
    <div class="tab-details blank">
        <div class="intro">
            <div class="title">
                <?php echo $_page_lang['intro']; ?>
                <?php if(empty($_page_data['is_readonly'])) { ?>
                <a href="<?php echo $_page_base_url.'/account/profile'; ?>"><img src="asset/image/icon-edit.png"></a>
                <?php } ?>
            </div>
            <div class="info">
                <?php if(!empty($_show_current_member_details['company_website'])) { ?>
                <div class="row">
                    <label><img src="asset/image/icon-lang-gray.png" alt="icon-lang-gray"><?php echo $_page_lang['account.company_website']; ?></label>
                    <span><?php echo $_show_current_member_details['company_website']; ?></span>
                </div>
                <?php } ?>
                
                <?php if(!empty($_show_current_member_details['company_address'])) { ?>
                <div class="row">
                    <label><i class="fa fa-map-marker"></i><?php echo $_page_lang['account.company_address']; ?></label>
                    <span><?php echo nl2br($_show_current_member_details['company_address']); ?></span>
                </div>
                <?php } ?>
                
                <?php if(!empty($_show_current_member['telephone_num'])) { ?>
                <div class="row">
                    <label><i class="fa fa-phone"></i><?php echo $_page_lang['account.telephone']; ?></label>
                    <span>
                        (<?php echo preg_replace('/^(\+)(.*)/i', '$2', $_show_current_member['telephone_code']); ?>)<?php echo $_show_current_member['telephone_num']; ?>
                        <?php
                        $phone_code = preg_replace('/^(\+)(.*)/i', '$2', $_show_current_member['telephone_code']);
                        $whatsapp_number = str_replace([' ', '-', '(', ')'], '', $phone_code . $_show_current_member['telephone_num']);
                        ?>
                        <a href="https://wa.me/<?php echo $whatsapp_number; ?>" target="_blank" title="Contact via WhatsApp" style="margin-left: 10px;">
                            <i class="fa fa-whatsapp" style="color: #25D366; font-size: 24px;"></i>
                        </a>
                    </span>
                </div>
                <?php } ?>
                
                <?php if(!empty($_show_current_member_agent)) { 
                    $agent_num = [];
                    $agent_country = [];
                    foreach ($_show_current_member_agent as $agent) {
                        $agent_num[] = $agent['registration_num'];
                        $agent_country[] = ((!empty($_page_options['countries'][$agent['registration_country']]))?$_page_options['countries'][$agent['registration_country']]:'');
                    }
                    ?>
                    <div class="row">
                        <label><?php echo $_page_lang['account.group_agent_num']; ?></label>
                        <span><?php echo implode(', ', array_unique(array_filter($agent_num))); ?></span>
                    </div>
                
                    <div class="row">
                        <label><?php echo $_page_lang['account.group_agent_country']; ?></label>
                        <span><?php echo implode(', ', array_unique(array_filter($agent_country))); ?></span>
                    </div>
                <?php } ?>
            </div>
        </div>
        
        <div class="mypost">
            <?php if(empty($_page_data['is_readonly'])) { ?>
            <div class="publish">
                <div class="title">
                    <i class="fa fa-pencil-square-o"></i>
                    <span><?php echo $_page_lang['posts.start']; ?></span>
                </div>
                <div class="media">
                    <a id="publish-photo">
                        <i class="fa fa-picture-o"></i>
                        <span><?php echo $_page_lang['posts.photo']; ?></span>
                    </a>
                    <a id="publish-video">
                        <i class="fa fa-youtube-play"></i>
                        <span><?php echo $_page_lang['posts.video']; ?></span>
                    </a>
                </div>
            </div>
            <div class="clearboth"></div>
            <?php } ?>
            
            <div class="article-list" data-mid="<?php echo $_show_current_member['id']; ?>"></div>
        </div>
        
        <div class="clearboth"></div>
    </div>
</div>

<?php if(empty($_page_data['is_readonly'])) { ?>
<div id="hide-extra-form" style="display:none;">
    <div class="form" style="margin-top:0px;">
        <form id="account-alias-form" method="post" action="<?php echo $_page_base_url.'/account/myalias'; ?> " enctype="multipart/form-data">
            <div>@csrf</div>
            
            <div class="required"><span style="color:red;">*</span> <?php echo $_page_lang['required']; ?></div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="mycoverphoto"><?php echo $_page_lang['account.add_cover_photo']; ?></label>
                <div class="coverphoto-file">
                    <div class="upload">
                        <i class="fa fa-camera"></i>
                    </div>
                    <div class="preview">
                        <?php if(!empty($_show_current_member_details['coverphoto'])) { ?>
                        <img src="<?php echo 'upload/member_coverphoto/'.$_show_current_member_details['coverphoto']; ?>">
                        <?php } ?>
                    </div>
                    <div class="select">
                        <input type="file" id="mycoverphoto" name="mycoverphoto" accept="image/*">
                    </div>
                </div>
            </div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="alias_name"><?php echo $_page_lang['account.alias_name']; ?> <span style="color:red;">*</span></label>
                <input type="text" id="alias_name" name="alias_name" placeholder="<?php echo $_page_lang['account.enter_alias_name']; ?>" value="<?php echo $_show_current_member['alias_name']; ?>" data-validation="required">
            </div>
            <div class="clearboth"></div>

            <div class="action">
                <button type="submit" class="btn btn-save"><?php echo $_page_lang['btn.save']; ?></button>
                <div class="clearboth"></div>
            </div>
        </form>
    </div>
</div>
<?php } ?>
@endsection