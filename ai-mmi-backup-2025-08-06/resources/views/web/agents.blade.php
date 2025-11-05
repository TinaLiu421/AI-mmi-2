@extends('web.common')
@section('content')
<div class="inner-panel">
    <h1 class="title"><?php echo $_page_lang['list_agents']; ?></h1>
    <div class="underline"></div>
    <div class="clearboth"></div>

    <?php if(!empty($_page_data['destinations'])) { ?>
    <div class="list">
        <?php foreach($_page_data['destinations'] as $destination) {
            if(empty($destination['agents'])) { continue; }
            $hasLink = !empty($destination['url']);
            $flag = !empty($destination['photo_flag']) ? $destination['photo_flag'] : '';
        ?>
        <div class="country">
            <div class="name">
                <?php if($hasLink) { ?><a href="<?php echo $destination['url']; ?>"><?php } ?>
                    <?php if($flag !== '') { ?><img src="<?php echo $flag; ?>"><?php } ?>
                    <span><?php echo $destination['label']; ?></span>
                <?php if($hasLink) { ?></a><?php } ?>
            </div>
            <div class="agents">
                <?php foreach ($destination['agents'] as $agent) { ?>
                    <div class="related">
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
                                <tr>
                                    <td><strong><?php echo $_page_lang['account.telephone']; ?>:</strong> </td>
                                    <td>&nbsp;&nbsp;</td>
                                    <td>
                                        <?php echo ($agent['telephone_code'].' '.$agent['telephone_num']); ?>
                                        <?php
                                        $phone_code = preg_replace('/^(\+)(.*)/i', '$2', $agent['telephone_code']);
                                        $whatsapp_number = str_replace([' ', '-', '(', ')'], '', $phone_code . $agent['telephone_num']);
                                        ?>
                                        <a href="https://wa.me/<?php echo $whatsapp_number; ?>" target="_blank" title="Contact via WhatsApp" style="margin-left: 10px;">
                                            <i class="fa fa-whatsapp" style="color: #25D366; font-size: 24px;"></i>
                                        </a>
                                    </td>
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
    </div>
    <?php } ?>
</div>
@endsection
