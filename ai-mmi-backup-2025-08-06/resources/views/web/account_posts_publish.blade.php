<?php $_show_current_member = $_page_data['show_current_member']; ?>
<?php 
$_show_posts = array_merge([
    'id'                =>  0,
    'category_type'     =>  1,
    'category_lang'     =>  0,
    'category_country'  =>  0,
    'title'             =>  '',
    'content'           =>  '',
    'photo'             =>  '',
    'youtube_url'       =>  '',
], ((!empty($_page_data['posts']))?$_page_data['posts']:[])); 
?>
<div class="form" style="margin-top:0px;background:#fff;">
    <form id="account-publish-form" method="post" action="<?php echo $_page_base_url.'/account/posts_publish'; ?> " enctype="multipart/form-data">
        <div>@csrf</div>
        <div><input type="hidden" id="posts_id" name="posts_id" value="<?php echo $_show_posts['id']; ?>"></div>
        
        <?php if(!empty($_show_posts['id'])) { ?>
        <a class="remove-my-post" data-id="<?php echo $_show_posts['id']; ?>"><i class="fa fa-trash"></i></a>
        <?php } ?>
        <div class="title"><?php echo $_page_lang['posts.create']; ?></div>
        
        <div class="fullscreen"><a><i class="fa fa-expand"></i><i class="fa fa-compress"></i></a></div>

        <div class="details">
            <div class="category">
                <div class="photo">
                    <img src="asset/image/icon-member.png" alt="icon-member"/>
                    <?php if(file_exists('upload/member_avatar/'.$_show_current_member['avatar'])) { ?>
                    <div class="avatar" style="background-image:url('<?php echo 'upload/member_avatar/'.$_show_current_member['avatar']; ?>')"></div>
                    <?php } else { ?>
                    <div class="avatar" style="background-image:url('<?php echo 'upload/member_logo/'.$_show_current_member['avatar']; ?>')"></div>
                    <?php } ?>
                </div>
                
                <div class="choose">
                    <div class="name"><?php echo $_show_current_member['alias_name']; ?></div>
                    <div class="clearboth"></div>

                    <div class="dropdown">
                        <select id="category_type" name="category_type">
                            <option value="1"<?php echo ($_show_posts['category_type']==1)?'selected':''; ?>><?php echo $_page_lang['posts.category_type_1']; ?></option>
                            <option value="2"<?php echo ($_show_posts['category_type']==2)?'selected':''; ?>><?php echo $_page_lang['posts.category_type_2']; ?></option>
                        </select>
                    </div>

                    <div class="dropdown">
                        <select id="category_lang" name="category_lang" data-validation="required">
                            <option value=""><?php echo $_page_lang['language']; ?></option>
                            <option value="1"<?php echo ($_show_posts['category_lang']==1)?'selected':''; ?>>English</option>
                            <option value="2"<?php echo ($_show_posts['category_lang']==2)?'selected':''; ?>>繁體中文</option>
                            <option value="3"<?php echo ($_show_posts['category_lang']==3)?'selected':''; ?>>简体中文</option>
                        </select>
                    </div>

                    <div class="dropdown">
                        <select id="category_country" name="category_country" data-validation="required">
                            <option value=""><?php echo $_page_lang['country']; ?></option>
                            <option value="0"<?php echo ($_show_posts['category_country']==0)?'selected':''; ?>><?php echo $_page_lang['all_country']; ?></option>
                            <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                            <option value="<?php echo $country_id; ?>"<?php echo ($_show_posts['category_country']==$country_id)?'selected':''; ?>><?php echo $country; ?></option>
                            <?php }} ?>
                        </select>
                    </div>
                </div>
                
                <div class="clearboth"></div>
            </div>
            
            <div class="row">
                <input type="text" id="title" name="title" value="<?php echo $_show_posts['title']; ?>" placeholder="<?php echo $_page_lang['posts.enter_subject']; ?>" data-validation="required">
            </div>

            <div class="row">
                <textarea id="content" name="content" placeholder="<?php echo $_page_lang['posts.enter_content']; ?>" data-validation="required"><?php echo $_show_posts['content']; ?></textarea>
            </div>
            <div class="clearboth"></div>

            <div class="row border">
                <div class="media-title"><?php echo $_page_lang['posts.add_to_post']; ?></div>
                <div class="media">
                    <a id="show-publish-photo">
                        <i class="fa fa-picture-o"></i>
                        <span><?php echo $_page_lang['posts.photo']; ?></span>
                    </a>
                    <a id="show-publish-video">
                        <i class="fa fa-youtube-play"></i>
                        <span><?php echo $_page_lang['posts.video']; ?></span>
                    </a>
                </div>
                <div class="upload-photo">
                    <div class="postsphoto-file">
                        <div class="upload">
                            <i class="fa fa-camera"></i>
                        </div>
                        <div class="preview"><!--
                            <?php if(!empty($_show_posts['photo']) && file_exists('upload/member_posts/'.$_show_posts['photo'])) { ?>
                            --><img src="<?php echo 'upload/member_posts/'.$_show_posts['photo'];?>"><!--
                            <?php } ?>
                        --></div>
                        <div class="select">
                            <input type="file" id="mypostsphoto" name="mypostsphoto" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="upload-video">
                    <div>
                        <input type="text" id="youtube_url" name="youtube_url" placeholder="<?php echo $_page_lang['posts.youtube_url']; ?>" value="<?php echo $_show_posts['youtube_url']; ?>">
                    </div>
                </div>
                <div class="clearboth"></div>
            </div>
            <div class="clearboth"></div>

            <div class="action">
                <button type="submit" class="btn btn-save"><?php echo $_page_lang['btn.publish']; ?></button>
                <div class="clearboth"></div>
            </div>
        </div>

        <div class="clearboth"></div>
    </form>
</div>