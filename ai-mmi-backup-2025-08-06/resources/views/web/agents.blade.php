@extends('web.common')
@section('content')
<div class="inner-panel">
    <h1 class="title"><?php echo $_page_lang['list_agents']; ?></h1>
    <div class="underline"></div>
    <div class="clearboth"></div>

    <?php if(!empty($_page_data['visa_countries'])) { ?>
    <div class="list">
        <?php foreach($_page_data['visa_countries'] as $vc) {
            $total = 0;
            if(!empty($_page_data['list'])) {  foreach ($_page_data['list'] as $agent) { 
                if((int)$agent['agent_registration_country'] == $vc['id'] || (!empty($agent['countries_serving']) && in_array($vc['id'], $agent['countries_serving']))) {
                    $total++;
                }
            }}
            if($total == 0) {
                continue;
            }
            ?>
        <div class="country">
            <div class="name">
                <a href="<?php echo $vc['url']; ?>">
                    <img src="<?php echo $vc['photo_flag']; ?>">
                    <span><?php echo $vc['title']; ?></span>
                </a>
            </div>
            <?php if(!empty($_page_data['list'])) { ?>
            <div class="agents">
                <?php foreach ($_page_data['list'] as $agent) { 
                    if(!((int)$agent['agent_registration_country'] == $vc['id'] || (!empty($agent['countries_serving']) && in_array($vc['id'], $agent['countries_serving'])))) {
                        continue;
                    }
                    ?>
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
                                
                                <?php if((int)$agent['agent_registration_country'] == $vc['id']) { ?>
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
            <?php } ?>
        </div>
        <?php } ?>
    </div>
    <?php } ?>
</div>
@endsection