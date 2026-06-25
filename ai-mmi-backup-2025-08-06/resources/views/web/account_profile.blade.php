@extends('web.common')
@section('content')
<?php $_show_current_member = $_page_data['show_current_member']; ?>
<?php $_show_current_member_details = (!empty($_page_data['current_member_details']))?$_page_data['current_member_details']:[]; ?>
<?php $_show_current_member_details = array_merge([
    'logo'=>'','company_name'=>'','company_type'=>'','company_website'=>'','company_address'=>'',
    'registered_agent'=>0,'registered_lawfirm'=>0,'services'=>'','services_country'=>'',
    'registered_business_country'=>'','registered_business_name'=>'','registered_business_number'=>'',
    'institution_type'=>0,'coverphoto'=>'',
], $_show_current_member_details); ?>
<?php $_show_current_member_agent = (!empty($_page_data['current_member_agent']))?$_page_data['current_member_agent']:[]; ?>
<?php $_show_current_member_lawfirm = (!empty($_page_data['current_member_lawfirm']))?$_page_data['current_member_lawfirm']:[]; ?>
<?php $_show_current_member_business_license = (!empty($_page_data['current_member_business_license']))?$_page_data['current_member_business_license']:[]; ?>
<?php $_institution_profile = $_page_data['institution_profile'] ?? null; ?>
<?php $_is_edu_institution = !empty($_institution_profile) || (int)($_show_current_member_details['institution_type'] ?? 0) === 2; ?>
<?php $_profile_posts_mid = (int)($_show_current_member['id'] ?? 0); ?>
<?php
// ── Overview renderer: breaks wall-of-text into ~3-sentence paragraphs ──
if (!function_exists('_renderOverviewHtml')) {
    function _renderOverviewHtml($text) {
        $text = trim($text);
        if (empty($text)) return '';
        // Split at sentence boundaries: lowercase/digit/bracket → `. ` → uppercase
        $sentences = preg_split('/(?<=[a-z\d\)])\.\s+(?=[A-Z])/', $text);
        if (count($sentences) <= 3) {
            return '<p class="ov-para">'.nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8')).'</p>';
        }
        $html = '';
        foreach (array_chunk($sentences, 3) as $i => $chunk) {
            $para = implode('. ', $chunk);
            // Re-add trailing period if stripped mid-sentence
            if (!preg_match('/[.!?]\s*$/', $para)) $para .= '.';
            $html .= '<p class="ov-para">'.htmlspecialchars($para, ENT_QUOTES, 'UTF-8').'</p>';
        }
        return $html;
    }
}
// ── Key Dates renderer (used in both readonly and owner-preview branches) ──
if (!function_exists('_renderKeyDatesHtml')) {
    function _renderKeyDatesHtml($raw) {
        if (empty($raw)) return '';
        $blocks = [];
        foreach (preg_split('/\n\s*\n/', $raw) as $blk) {
            $blk = trim($blk);
            if ($blk === '') continue;
            $ls = explode("\n", $blk, 2);
            $t = rtrim(trim($ls[0] ?? ''), ':');
            $items = [];
            foreach (explode("\n", trim($ls[1] ?? '')) as $l) {
                $l = trim($l);
                if ($l !== '') { $l = preg_replace('/^[-\x{2022}*]\s*/u', '', $l); if ($l !== '') $items[] = $l; }
            }
            if ($t) $blocks[] = ['title' => $t, 'items' => $items];
        }
        if (empty($blocks)) return '';
        $map = function($t) {
            $tl = strtolower($t);
            if (strpos($tl,'intake')  !== false) return ['🗓','#1a73e8','#e8f0fe'];
            if (strpos($tl,'term')    !== false || strpos($tl,'start') !== false) return ['📚','#0d9488','#d1fae5'];
            if (strpos($tl,'enrol')   !== false || strpos($tl,'deadline') !== false) return ['⏰','#dc2626','#fee2e2'];
            if (strpos($tl,'scholar') !== false) return ['🎓','#7c3aed','#ede9fe'];
            if (strpos($tl,'appli')   !== false) return ['📋','#ea580c','#fff7ed'];
            return ['📅','#374151','#f3f4f6'];
        };
        $o = '<div class="kd-blocks-grid">';
        foreach ($blocks as $b) {
            [$ic, $cl, $bg] = $map($b['title']);
            $o .= '<div class="kd-block" style="border-top:3px solid '.$cl.';background:'.$bg.';">';
            $o .= '<div class="kd-block-header"><span class="kd-block-icon">'.$ic.'</span>';
            $o .= '<span class="kd-block-title" style="color:'.$cl.';">'.htmlspecialchars(ucwords(strtolower($b['title'])),ENT_QUOTES,'UTF-8').'</span></div>';
            if (!empty($b['items'])) {
                $o .= '<ul class="kd-list">';
                foreach ($b['items'] as $item) $o .= '<li class="kd-item">'.htmlspecialchars($item,ENT_QUOTES,'UTF-8').'</li>';
                $o .= '</ul>';
            }
            $o .= '</div>';
        }
        $o .= '</div>';
        return $o;
    }
}

if (!function_exists('_extractInstitutionCourses')) {
    function _extractInstitutionCourses(array $profile): array {
        $raw = (string)($profile['courses_json'] ?? '');

        if ($raw === '' && !empty($profile['programs'])) {
            $raw = (string)$profile['programs'];
            $prefix = '__AIMMI_COURSES_JSON__:';
            if (strpos($raw, $prefix) === 0) {
                $raw = substr($raw, strlen($prefix));
            }
        }

        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        if (isset($decoded['courses']) && is_array($decoded['courses'])) {
            $decoded = $decoded['courses'];
        }

        if (array_keys($decoded) !== range(0, count($decoded) - 1)) {
            return [];
        }

        return $decoded;
    }
}

if (!function_exists('_extractInstitutionGallery')) {
    function _extractInstitutionGallery(array $profile, int $memberId): array {
        $gallery = [];
        $raw = (string)($profile['gallery_json'] ?? '');

        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $file) {
                    $file = basename((string)$file);
                    if ($file !== '' && preg_match('/^[a-zA-Z0-9_\-.]+$/', $file)) {
                        $gallery[] = $file;
                    }
                }
            }
        }

        if (empty($gallery) && $memberId > 0) {
            $fallbackPath = public_path('upload/inst_gallery/_gallery_member_' . $memberId . '.json');
            if (file_exists($fallbackPath)) {
                $fallbackRaw = @file_get_contents($fallbackPath);
                $fallbackDecoded = json_decode((string)$fallbackRaw, true);
                if (is_array($fallbackDecoded)) {
                    foreach ($fallbackDecoded as $file) {
                        $file = basename((string)$file);
                        if ($file !== '' && preg_match('/^[a-zA-Z0-9_\-.]+$/', $file)) {
                            $gallery[] = $file;
                        }
                    }
                }
            }
        }

        return array_values(array_unique($gallery));
    }
}
?>
<div class="inner-panel full">
    <?php if(!empty($_show_current_member['coverphoto']) && file_exists('upload/member_coverphoto/'.$_show_current_member['coverphoto'])) { ?>
    <div class="banner" style="background-image:url('<?php echo 'upload/member_coverphoto/'.$_show_current_member['coverphoto']; ?>')"></div>
    <?php } else { ?>
    <div class="banner" style="display:none;"></div>
    <?php } ?>
    <div class="basic<?php echo $_is_edu_institution ? ' edu-inst' : ''; ?>">
        <div class="photo">
            <img src="asset/image/icon-member.png" alt="icon-member"/>
            <?php if(file_exists('upload/member_avatar/'.$_show_current_member['avatar'])) { ?>
            <div class="avatar" style="background-image:url('<?php echo 'upload/member_avatar/'.$_show_current_member['avatar']; ?>')"></div>
            <?php } else { ?>
            <div class="avatar" style="background-image:url('<?php echo 'upload/member_logo/'.$_show_current_member['avatar']; ?>');background-size:contain;background-color:#fff;<?php if($_is_edu_institution) echo 'border-radius:8px;'; ?>"></div>
            <?php } ?>
            <?php if(empty($_page_data['is_readonly'])) { ?>
            <a id="myavatar" class="camera"><i class="fa fa-camera"></i></a>
            <?php } ?>
        </div>
        <div class="name">
            <div class="alias">
                <div class="readonly">
                    <span><?php
                        $_disp_name = $_show_current_member['alias_name'];
                        if($_is_edu_institution) $_disp_name = preg_replace('/\band\b/i', '&', $_disp_name);
                        echo htmlspecialchars($_disp_name, ENT_QUOTES, 'UTF-8');
                    ?></span>
                </div>
                <?php if(empty($_page_data['is_readonly'])) { ?>
                <a><img src="asset/image/icon-edit.png"></a>
                <?php } ?>
            </div>
            {{-- followers hidden --}}
        </div>
        <div class="clearboth"></div>
        <div class="tab">
            <?php if((int)$_show_current_member['type'] > 1 && !$_is_edu_institution) { ?>
            <a class="posts" href="<?php echo $_page_base_url.'/account/posts'.((!empty($_page_get_data['uid']))?'?uid='.$_page_get_data['uid']:''); ?>"><?php echo $_page_lang['tab_posts']; ?></a>
            <?php } ?>
            <a class="about selected"><?php echo $_page_lang['tab_about']; ?></a>
            <?php if($_is_edu_institution): ?>
            <?php $_uid_qs = (!empty($_page_get_data['uid'])) ? '?uid='.$_page_get_data['uid'] : ''; ?>
            <a class="edu-tab" href="<?php echo $_page_base_url.'/account/students_matched'.$_uid_qs; ?>">Students Matched</a>
            <a class="edu-tab" href="<?php echo $_page_base_url.'/account/students_applied'.$_uid_qs; ?>">Students Applied</a>
            <a class="edu-tab" href="<?php echo $_page_base_url.'/account/students_accepted'.$_uid_qs; ?>">Students Accepted</a>
            <?php endif; ?>
            <?php if(empty($_page_data['is_readonly']) && in_array((int)$_show_current_member['type'], [2, 3])): ?>
            <a class="spotlight" href="<?php echo $_page_base_url.'/account/spotlight'; ?>">⭐ Spotlight</a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if(empty($_page_data['is_readonly'])) { ?>
    <div class="tab-details">
        <div class="form">
            <form id="account-profile-form" method="post" enctype="multipart/form-data">
                <div>@csrf</div>
                
                <div class="ac-type">
                    <?php echo $_page_lang['account.ac_type_'.$_show_current_member['type']]; ?>
                </div>

                <div class="subscription-info">
                    <div class="subscription-name">
                        <strong>Subscription:</strong> <?php echo !empty($_show_current_member['subscription_name']) ? $_show_current_member['subscription_name'] : 'Free Plan'; ?>
                    </div>
                    <div class="subscription-expiry">
                        <strong>Expires:</strong> <?php echo !empty($_show_current_member['subscription_expiry']) ? date('M d, Y', strtotime($_show_current_member['subscription_expiry'])) : 'N/A'; ?>
                    </div>
                    <?php
                    $canCancel = !empty($_show_current_member['subscription_stripe_sub_id'])
                        && in_array($_show_current_member['subscription_plan_code'] ?? '', ['all_ai','hybrid'])
                        && empty($_show_current_member['subscription_cancel_at_period_end']);
                    $alreadyCancelling = !empty($_show_current_member['subscription_stripe_sub_id'])
                        && in_array($_show_current_member['subscription_plan_code'] ?? '', ['all_ai','hybrid'])
                        && !empty($_show_current_member['subscription_cancel_at_period_end']);
                    ?>
                    <?php if($alreadyCancelling): ?>
                    <div class="subscription-cancel-note" style="margin-top:6px;font-size:13px;color:#e07b00;">
                        ⚠ Auto-renewal cancelled — your plan stays active until <?php echo !empty($_show_current_member['subscription_expiry']) ? date('M d, Y', strtotime($_show_current_member['subscription_expiry'])) : 'expiry'; ?>.
                    </div>
                    <?php elseif($canCancel): ?>
                    <div style="margin-top:8px;">
                        <a href="javascript:void(0);" id="cancel-sub-trigger"
                           style="font-size:12px;color:#999;text-decoration:underline;cursor:pointer;">
                            Cancel auto-renewal
                        </a>
                    </div>
                    <div id="cancel-sub-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
                        <div style="background:#fff;border-radius:10px;padding:32px 28px;max-width:420px;width:90%;text-align:center;box-shadow:0 4px 24px rgba(0,0,0,0.18);">
                            <h3 style="margin:0 0 12px;font-size:18px;">Cancel auto-renewal?</h3>
                            <p style="font-size:14px;color:#555;margin:0 0 20px;">
                                Your <strong><?php echo htmlspecialchars($_show_current_member['subscription_name'] ?? ''); ?></strong> will remain active until
                                <strong><?php echo !empty($_show_current_member['subscription_expiry']) ? date('M d, Y', strtotime($_show_current_member['subscription_expiry'])) : 'expiry'; ?></strong>,
                                then it will not renew. You can re-subscribe at any time.
                            </p>
                            <div style="display:flex;gap:12px;justify-content:center;">
                                <button id="cancel-sub-confirm" style="background:#c0392b;color:#fff;border:none;padding:10px 24px;border-radius:6px;cursor:pointer;font-size:14px;">Yes, cancel renewal</button>
                                <button id="cancel-sub-dismiss" style="background:#eee;color:#333;border:none;padding:10px 24px;border-radius:6px;cursor:pointer;font-size:14px;">Keep my plan</button>
                            </div>
                            <p id="cancel-sub-status" style="margin:12px 0 0;font-size:13px;color:#555;display:none;"></p>
                        </div>
                    </div>
                    <script>
                    (function(){
                        var trigger  = document.getElementById('cancel-sub-trigger');
                        var modal    = document.getElementById('cancel-sub-modal');
                        var confirm  = document.getElementById('cancel-sub-confirm');
                        var dismiss  = document.getElementById('cancel-sub-dismiss');
                        var status   = document.getElementById('cancel-sub-status');
                        if(!trigger) return;
                        trigger.addEventListener('click', function(){ modal.style.display='flex'; });
                        dismiss.addEventListener('click', function(){ modal.style.display='none'; });
                        modal.addEventListener('click', function(e){ if(e.target===modal) modal.style.display='none'; });
                        confirm.addEventListener('click', function(){
                            confirm.disabled = true;
                            confirm.textContent = 'Cancelling…';
                            status.style.display = 'block';
                            status.textContent = 'Processing…';
                            var csrfToken = (typeof _token !== 'undefined' && _token) ? _token : (document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '');
                            fetch('/upgrade/cancel-renewal', {
                                method: 'POST',
                                headers: {'Content-Type':'application/json','X-CSRF-TOKEN': csrfToken},
                                credentials: 'same-origin'
                            })
                            .then(function(r){ return r.json(); })
                            .then(function(data){
                                if(data.ok){
                                    status.style.color='#27ae60';
                                    status.textContent = 'Done! Your plan will not renew after ' + (data.expiry || 'expiry') + '.';
                                    setTimeout(function(){ location.reload(); }, 2000);
                                } else {
                                    status.style.color='#c0392b';
                                    status.textContent = data.message || 'Something went wrong. Please try again.';
                                    confirm.disabled = false;
                                    confirm.textContent = 'Yes, cancel renewal';
                                }
                            })
                            .catch(function(){
                                status.style.color='#c0392b';
                                status.textContent = 'Network error. Please try again.';
                                confirm.disabled = false;
                                confirm.textContent = 'Yes, cancel renewal';
                            });
                        });
                    })();
                    </script>
                    <?php endif; ?>
                </div>

                <?php if(empty($_show_current_member['remark'])) { ?>
                <div class="further-tips">
                    <i class="fa fa-info-circle"></i>
                    <?php echo $_page_lang['account.further_info_1']; ?>
                </div>
                <?php } ?>
                
                <div class="clearboth"></div>
                
                <div class="further-title">
                    <h1><?php echo $_page_lang['tab_about']; ?></h1>
                    <textarea id="further-content" name="remark"><?php echo $_show_current_member['remark']; ?></textarea>
                </div>
                <div class="clearboth"></div>
  
                <div class="required"><span style="color:red;">*</span> <?php echo $_page_lang['required']; ?></div>
                <div class="clearboth"></div>

                <?php if(in_array((int)$_show_current_member['type'], [2, 3])) { ?>
                <?php if($_is_edu_institution) { ?>
                <div class="group-title"><u>Institution Information</u></div>

                <div class="row">
                    <label for="company_name">Institution Name <span style="color:red;">*</span></label>
                    <input type="text" id="company_name" name="company_name" placeholder="Enter institution name" value="<?php echo htmlspecialchars($_show_current_member_details['company_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-validation="required">
                </div>
                <div class="clearboth"></div>

                <div class="row">
                    <label for="company_website">Institution Website <span style="color:red;">*</span></label>
                    <input type="text" id="company_website" name="company_website" placeholder="https://" value="<?php echo htmlspecialchars($_show_current_member_details['company_website'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-validation="required">
                </div>
                <div class="clearboth"></div>

                <div style="background:#eaf4ff; border:1px solid #b3d7ff; border-radius:10px; padding:24px; margin:18px 0; text-align:center;">
                    <div style="font-size:2em; margin-bottom:8px;">&#x1F393;</div>
                    <h3 style="margin:0 0 8px; color:#1a73e8; font-size:1.1em;">AI-Powered Institution Profile</h3>
                    <p style="margin:0 0 16px; color:#555; font-size:0.95em;">Let AI extract your programmes, admission requirements, tuition fees, and more directly from your website. Students will use this to match with your institution.</p>
                    <a href="<?php echo $_page_base_url; ?>/institution_hub_profile" style="display:inline-block; background:#1a73e8; color:#fff; padding:10px 26px; border-radius:6px; text-decoration:none; font-weight:bold;">Manage AI Profile &rarr;</a>
                </div>
                <div>&nbsp;</div>
                <?php } else { ?>
                <div class="group-title"><u><?php echo $_page_lang['account.company_info']; ?></u></div>

                <div class="row">
                    <label for="logo"><?php echo $_page_lang['account.logo']; ?></label>
                    <div class="logo-file">
                        <img src="asset/image/default_logo.jpg" alt="default_logo">
                        <div class="preview"><!--
                            <?php if(!empty($_show_current_member_details['logo'])) { ?>
                            --><img src="<?php echo 'upload/member_logo/'.$_show_current_member_details['logo']; ?>"><!--
                            <?php } ?>
                        --></div>
                        <div class="select">
                            <input type="file" id="mylogo" name="mylogo" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml,.svg">
                        </div>
                    </div>
                </div>
                <div class="clearboth"></div>

                <div class="row">
                    <label for="company_name"><?php echo ((int)$_show_current_member['type'] == 2)?$_page_lang['account.company_name']:$_page_lang['account.company_name_2']; ?> <span style="color:red;">*</span></label>
                    <input type="text" id="company_name" name="company_name" placeholder="<?php echo $_page_lang['account.enter_company_name']; ?>" value="<?php echo htmlspecialchars($_show_current_member_details['company_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-validation="required">
                </div>
                <div class="clearboth"></div>

                <?php if((int)$_show_current_member['type'] == 3) { ?>
                <div class="row">
                    <label for="company_type"><?php echo $_page_lang['account.company_type']; ?> <span style="color:red;">*</span></label>
                    <select id="company_type" name="company_type" data-validation="required">
                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                        <?php if(!empty($_page_options['organization_type'])) { foreach ($_page_options['organization_type'] as $organization_type_id => $organization_type) { ?>
                        <option value="<?php echo $organization_type_id; ?>"<?php echo (($_show_current_member_details['company_type'] ?? '')==$organization_type_id)?' selected':'';?>><?php echo $organization_type; ?></option>
                        <?php }} ?>
                    </select>
                </div>
                <div class="clearboth"></div>
                <?php } ?>

                <div class="row">
                    <label for="company_website"><?php echo $_page_lang['account.company_website']; ?> <span style="color:red;">*</span></label>
                    <input type="text" id="company_website" name="company_website" placeholder="<?php echo $_page_lang['account.enter_company_website']; ?>" value="<?php echo htmlspecialchars($_show_current_member_details['company_website'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-validation="required">
                </div>
                <div class="clearboth"></div>

                <div class="row">
                    <label for="company_address"><?php echo $_page_lang['account.company_address']; ?> <span style="color:red;">*</span></label>
                    <textarea id="company_address" name="company_address" rows="3" placeholder="<?php echo $_page_lang['account.enter_company_address']; ?>" data-validation="required"><?php echo htmlspecialchars($_show_current_member_details['company_address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="clearboth"></div>

                <?php if((int)$_show_current_member['type'] == 2) { ?>
                
                <div class="row">
                    <label for="countries_serving"><?php echo $_page_lang['account.countries_serving']; ?></label>
                    <?php
                    $destinationsServingList = !empty($_page_options['destinations_serving']) ? $_page_options['destinations_serving'] : [];
                    $selectedDestinations = [];
                    if(!empty($_show_current_member['countries_serving']) && is_array($_show_current_member['countries_serving'])) {
                        $selectedDestinations = array_map('strval', $_show_current_member['countries_serving']);
                    }
                    ?>
                    <select id="countries_serving" name="countries_serving[]" multiple>
                        <?php foreach ($destinationsServingList as $destId => $destLabel) {
                            $optionValue = (string)$destId;
                            $isSelected = in_array($optionValue, $selectedDestinations, true);
                        ?>
                        <option value="<?php echo htmlspecialchars($optionValue, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isSelected ? ' selected' : ''; ?>><?php echo htmlspecialchars($destLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="clearboth"></div>
                
                <div class="child-agent">
                    <div class="row">
                        <label for="registered_agent"><?php echo $_page_lang['account.has_registered_agent']; ?> <span style="color:red;">*</span></label>
                        <div class="iweb-radiobox-set"  data-showtips="false">
                            <input type="radio" id="registered_agent_yes" name="registered_agent" value="1" <?php echo ((int)($_show_current_member_details['registered_agent'] ?? 0)==1)?' checked':'';?>>
                            <label for="registered_agent_yes"><?php echo $_page_lang['account.yes']; ?></label>

                            <input type="radio" id="registered_agent_no" name="registered_agent" value="0" <?php echo ((int)($_show_current_member_details['registered_agent'] ?? 0)==0)?' checked':'';?>>
                            <label for="registered_agent_no"><?php echo $_page_lang['account.no']; ?></label>
                        </div>
                    </div>
                    <div class="clearboth"></div>

                    <div class="list<?php echo ((int)($_show_current_member_details['registered_agent'] ?? 0)==0)?' disabled':'';?>">
                        <div class="items">
                            <div class="block hidden">
                                <input type="hidden"  name="agent_id[]" value="0" disabled>

                                <div class="num"><?php echo $_page_lang['account.registered_agent']; ?><span> - 0</span></div>

                                <div class="row left">
                                    <label for="first_name"><?php echo $_page_lang['account.first_name']; ?> <span style="color:red;">*</span></label>
                                    <input type="text"  name="agent_first_name[]" placeholder="<?php echo $_page_lang['account.enter_first_name']; ?>" disabled>
                                </div>
                                <div class="row right">
                                    <label for="last_name"><?php echo $_page_lang['account.last_name']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="agent_last_name[]" placeholder="<?php echo $_page_lang['account.enter_last_name']; ?>" disabled>
                                </div>
                                <div class="clearboth"></div>

                                <div class="row left">
                                    <label for="registration_country"><?php echo $_page_lang['account.registration_country']; ?> <span style="color:red;">*</span></label>
                                    <select name="agent_registration_country[]" disabled>
                                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                        <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                        <option value="<?php echo $country_id; ?>"><?php echo $country; ?></option>
                                        <?php }} ?>
                                    </select>
                                </div>
                                <div class="row right">
                                    <label for="registration_num"><?php echo $_page_lang['account.registration_num']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="agent_registration_num[]" placeholder="<?php echo $_page_lang['account.enter_registration_num']; ?>" disabled>
                                </div>
                                <div class="clearboth"></div>
                            </div>

                            <?php if(!empty($_show_current_member_agent)) { foreach ($_show_current_member_agent as $agent_key => $agent) { ?>
                            <div class="block">
                                <a class="remove-agent-block"><i class="fa fa-times"></i></a>

                                <input type="hidden"  name="agent_id[]" value="<?php echo (int)$agent['id']; ?>">

                                <div class="num"><?php echo $_page_lang['account.registered_agent']; ?><span> - <?php echo $agent_key+1;?></span></div>

                                <div class="row left">
                                    <label for="first_name"><?php echo $_page_lang['account.first_name']; ?> <span style="color:red;">*</span></label>
                                    <input type="text"  name="agent_first_name[]" placeholder="<?php echo $_page_lang['account.enter_first_name']; ?>" value="<?php echo $agent['first_name']; ?>">
                                </div>
                                <div class="row right">
                                    <label for="last_name"><?php echo $_page_lang['account.last_name']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="agent_last_name[]" placeholder="<?php echo $_page_lang['account.enter_last_name']; ?>" value="<?php echo $agent['last_name']; ?>">
                                </div>
                                <div class="clearboth"></div>

                                <div class="row left">
                                    <label for="registration_country"><?php echo $_page_lang['account.registration_country']; ?> <span style="color:red;">*</span></label>
                                    <select name="agent_registration_country[]">
                                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                        <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                        <option value="<?php echo $country_id; ?>"<?php echo ($agent['registration_country']==$country_id)?' selected':'';?>><?php echo $country; ?></option>
                                        <?php }} ?>
                                    </select>
                                </div>
                                <div class="row right">
                                    <label for="registration_num"><?php echo $_page_lang['account.registration_num']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="agent_registration_num[]" placeholder="<?php echo $_page_lang['account.enter_registration_num']; ?>" value="<?php echo $agent['registration_num']; ?>">
                                </div>
                                <div class="clearboth"></div>
                            </div>
                            <?php }} else { ?>
                            <div class="block">
                                <a class="remove-agent-block"><i class="fa fa-times"></i></a>

                                <input type="hidden"  name="agent_id[]" value="0">

                                <div class="num"><?php echo $_page_lang['account.registered_agent']; ?><span> - 1</span></div>

                                <div class="row left">
                                    <label for="first_name"><?php echo $_page_lang['account.first_name']; ?> <span style="color:red;">*</span></label>
                                    <input type="text"  name="agent_first_name[]" placeholder="<?php echo $_page_lang['account.enter_first_name']; ?>">
                                </div>
                                <div class="row right">
                                    <label for="last_name"><?php echo $_page_lang['account.last_name']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="agent_last_name[]" placeholder="<?php echo $_page_lang['account.enter_last_name']; ?>">
                                </div>
                                <div class="clearboth"></div>

                                <div class="row left">
                                    <label for="registration_country"><?php echo $_page_lang['account.registration_country']; ?> <span style="color:red;">*</span></label>
                                    <select name="agent_registration_country[]">
                                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                        <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                        <option value="<?php echo $country_id; ?>"><?php echo $country; ?></option>
                                        <?php }} ?>
                                    </select>
                                </div>
                                <div class="row right">
                                    <label for="registration_num"><?php echo $_page_lang['account.registration_num']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="agent_registration_num[]" placeholder="<?php echo $_page_lang['account.enter_registration_num']; ?>">
                                </div>
                                <div class="clearboth"></div>
                            </div>
                            <?php } ?>
                        </div>
                        <div class="row">
                            <a class="add-agent-block" href="#">
                                <span><?php echo $_page_lang['account.add_registered_agent']; ?></span>
                                <i class="fa fa-plus-circle"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="child-lawfirm">
                    <div class="row">
                        <label for="registered_lawfirm"><?php echo $_page_lang['account.has_registered_lawfirm']; ?> <span style="color:red;">*</span></label>
                        <div class="iweb-radiobox-set"  data-showtips="false">
                            <input type="radio" id="registered_lawfirm_yes" name="registered_lawfirm" value="1" <?php echo ((int)$_show_current_member_details['registered_lawfirm']==1)?' checked':'';?>>
                            <label for="registered_lawfirm_yes"><?php echo $_page_lang['account.yes']; ?></label>

                            <input type="radio" id="registered_lawfirm_no" name="registered_lawfirm" value="0" <?php echo ((int)$_show_current_member_details['registered_lawfirm']==0)?' checked':'';?>>
                            <label for="registered_lawfirm_no"><?php echo $_page_lang['account.no']; ?></label>
                        </div>
                    </div>
                    <div class="clearboth"></div>

                    <div class="list<?php echo ((int)$_show_current_member_details['registered_lawfirm']==0)?' disabled':'';?>">
                        <div class="items">
                            <div class="block hidden">
                                <input type="hidden"  name="lawfirm_id[]" value="0" disabled>

                                <div class="num"><?php echo $_page_lang['account.registered_lawfirm']; ?><span> - 0</span></div>

                                <div class="row">
                                    <label for="name"><?php echo $_page_lang['account.name']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="lawfirm_name[]" placeholder="<?php echo $_page_lang['account.enter_name']; ?>" disabled>
                                </div>
                                <div class="clearboth"></div>

                                <div class="row left">
                                    <label for="registration_country"><?php echo $_page_lang['account.registration_country']; ?> <span style="color:red;">*</span></label>
                                    <select name="lawfirm_registration_country[]" disabled>
                                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                        <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                        <option value="<?php echo $country_id; ?>"><?php echo $country; ?></option>
                                        <?php }} ?>
                                    </select>
                                </div>
                                <div class="row right">
                                    <label for="registration_num"><?php echo $_page_lang['account.registration_num']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="lawfirm_registration_num[]" placeholder="<?php echo $_page_lang['account.enter_registration_num']; ?>" disabled>
                                </div>
                                <div class="clearboth"></div>
                            </div>

                            <?php if(!empty($_show_current_member_lawfirm)) { foreach ($_show_current_member_lawfirm as $lawfirm_key => $lawfirm) { ?>
                            <div class="block">
                                <a class="remove-lawfirm-block"><i class="fa fa-times"></i></a>

                                <input type="hidden"  name="lawfirm_id[]" value="<?php echo (int)$lawfirm['id']; ?>">

                                <div class="num"><?php echo $_page_lang['account.registered_lawfirm']; ?><span> - <?php echo $lawfirm_key+1; ?></span></div>

                                <div class="row">
                                    <label for="name"><?php echo $_page_lang['account.name']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="lawfirm_name[]" placeholder="<?php echo $_page_lang['account.enter_name']; ?>" value="<?php echo $lawfirm['full_name']; ?>" data-validation="required">
                                </div>
                                <div class="clearboth"></div>

                                <div class="row left">
                                    <label for="registration_country"><?php echo $_page_lang['account.registration_country']; ?> <span style="color:red;">*</span></label>
                                    <select name="lawfirm_registration_country[]">
                                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                        <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                        <option value="<?php echo $country_id; ?>"<?php echo ($lawfirm['registration_country']==$country_id)?' selected':'';?>><?php echo $country; ?></option>
                                        <?php }} ?>
                                    </select>
                                </div>
                                <div class="row right">
                                    <label for="registration_num"><?php echo $_page_lang['account.registration_num']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="lawfirm_registration_num[]" placeholder="<?php echo $_page_lang['account.enter_registration_num']; ?>" value="<?php echo $lawfirm['registration_num']; ?>">
                                </div>
                                <div class="clearboth"></div>
                            </div>
                            <?php }} else { ?>
                            <div class="block">
                                <a class="remove-lawfirm-block"><i class="fa fa-times"></i></a>

                                <input type="hidden"  name="lawfirm_id[]" value="0">

                                <div class="num"><?php echo $_page_lang['account.registered_lawfirm']; ?><span> - 1</span></div>

                                <div class="row">
                                    <label for="name"><?php echo $_page_lang['account.name']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="lawfirm_name[]" placeholder="<?php echo $_page_lang['account.enter_name']; ?>">
                                </div>
                                <div class="clearboth"></div>

                                <div class="row left">
                                    <label for="registration_country"><?php echo $_page_lang['account.registration_country']; ?> <span style="color:red;">*</span></label>
                                    <select name="lawfirm_registration_country[]">
                                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                        <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                        <option value="<?php echo $country_id; ?>"><?php echo $country; ?></option>
                                        <?php }} ?>
                                    </select>
                                </div>
                                <div class="row right">
                                    <label for="registration_num"><?php echo $_page_lang['account.registration_num']; ?> <span style="color:red;">*</span></label>
                                    <input type="text" name="lawfirm_registration_num[]" placeholder="<?php echo $_page_lang['account.enter_registration_num']; ?>">
                                </div>
                                <div class="clearboth"></div>
                            </div>
                            <?php } ?>
                        </div>
                        <div class="row">
                            <a class="add-lawfirm-block">
                                <span><?php echo $_page_lang['account.add_registered_lawfirm']; ?></span>
                                <i class="fa fa-plus-circle"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <?php } else { ?>
                <div class="row">
                    <label for="services"><?php echo $_page_lang['account.services']; ?></label>
                    <input type="text" id="services" name="services" placeholder="<?php echo $_page_lang['account.enter_services']; ?>" value="<?php echo $_show_current_member_details['services']; ?>">
                </div>
                <div class="clearboth"></div>

                <div class="row">
                    <label for="services_country"><?php echo $_page_lang['account.services_country']; ?> <span style="color:red;">*</span></label>
                    <?php
                    $selected_countries = [];
                    if(!empty($_show_current_member_details['services_country'])) {
                        $decoded = json_decode($_show_current_member_details['services_country'], true);
                        $selected_countries = is_array($decoded) ? $decoded : [];
                    }
                    ?>
                    <select id="services_country" name="services_country[]" multiple="multiple" data-validation="required">
                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                        <option value="all">All Countries</option>
                        <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                        <option value="<?php echo $country_id; ?>"<?php echo (in_array($country_id, $selected_countries))?' selected':'';?>><?php echo $country; ?></option>
                        <?php }} ?>
                    </select>
                </div>
                <div class="clearboth"></div>

                <div>&nbsp;</div>

                <div class="group-title"><u><?php echo $_page_lang['account.business_registration']; ?></u> <span style="font-size: 0.8em; font-weight: normal;">(<?php echo $_page_lang['if_applicable']; ?>)</span></div>

                <div class="row">
                    <label for="registered_business_country"><?php echo $_page_lang['account.business_registration_country']; ?></label>
                    <select id="registered_business_country" name="registered_business_country">
                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                        <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                        <option value="<?php echo $country_id; ?>"<?php echo ($_show_current_member_details['registered_business_country']==$country_id)?' selected':'';?>><?php echo $country; ?></option>
                        <?php }} ?>
                    </select>
                </div>
                <div class="clearboth"></div>

                <div class="row">
                    <label for="registered_business_name"><?php echo $_page_lang['account.business_registration_name']; ?></label>
                    <input type="text" id="registered_business_name" name="registered_business_name" placeholder="<?php echo $_page_lang['account.enter_business_registration_name']; ?>" value="<?php echo $_show_current_member_details['registered_business_name']; ?>">
                </div>
                <div class="clearboth"></div>

                <div class="row">
                    <label for="registered_business_number"><?php echo $_page_lang['account.business_registration_number']; ?></label>
                    <input type="text" id="registered_business_number" name="registered_business_number" placeholder="<?php echo $_page_lang['account.enter_business_registration_number']; ?>" value="<?php echo $_show_current_member_details['registered_business_number']; ?>">
                </div>
                <div class="clearboth"></div>

                <div>&nbsp;</div>

                <div class="group-title"><u><?php echo $_page_lang['account.business_license']; ?></u> <span style="font-size: 0.8em; font-weight: normal;">(<?php echo $_page_lang['if_applicable']; ?>)</span></div>

                <div class="child-business-license">
                    <div class="items">
                        <div class="block hidden" style="display:none;">
                            <a class="remove-license-block"><i class="fa fa-times"></i></a>

                            <input type="hidden" name="license_id[]" value="0" disabled>

                            <div class="num"><?php echo $_page_lang['account.business_license']; ?><span> - 0</span></div>

                            <div class="row left">
                                <label for="license_country"><?php echo $_page_lang['account.business_license_country']; ?></label>
                                <select name="license_country[]" disabled>
                                    <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                    <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                    <option value="<?php echo $country_id; ?>"><?php echo $country; ?></option>
                                    <?php }} ?>
                                </select>
                            </div>
                            <div class="row right">
                                <label for="issuing_authority"><?php echo $_page_lang['account.issuing_authority']; ?></label>
                                <input type="text" name="issuing_authority[]" placeholder="<?php echo $_page_lang['account.enter_issuing_authority']; ?>" disabled>
                            </div>
                            <div class="clearboth"></div>

                            <div class="row left">
                                <label for="type_of_registration"><?php echo $_page_lang['account.type_of_registration']; ?></label>
                                <input type="text" name="type_of_registration[]" placeholder="<?php echo $_page_lang['account.enter_type_of_registration']; ?>" disabled>
                            </div>
                            <div class="row right">
                                <label for="registration_number"><?php echo $_page_lang['account.business_license_number']; ?></label>
                                <input type="text" name="registration_number[]" placeholder="<?php echo $_page_lang['account.enter_business_license_number']; ?>" disabled>
                            </div>
                            <div class="clearboth"></div>
                        </div>

                        <?php if(!empty($_show_current_member_business_license)) { foreach ($_show_current_member_business_license as $license_key => $license) { ?>
                        <div class="block">
                            <a class="remove-license-block"><i class="fa fa-times"></i></a>

                            <input type="hidden" name="license_id[]" value="<?php echo (int)$license['id']; ?>">

                            <div class="num"><?php echo $_page_lang['account.business_license']; ?><span> - <?php echo $license_key+1;?></span></div>

                            <div class="row left">
                                <label for="license_country"><?php echo $_page_lang['account.business_license_country']; ?></label>
                                <select name="license_country[]">
                                    <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                    <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                    <option value="<?php echo $country_id; ?>"<?php echo ($license['license_country']==$country_id)?' selected':'';?>><?php echo $country; ?></option>
                                    <?php }} ?>
                                </select>
                            </div>
                            <div class="row right">
                                <label for="issuing_authority"><?php echo $_page_lang['account.issuing_authority']; ?></label>
                                <input type="text" name="issuing_authority[]" placeholder="<?php echo $_page_lang['account.enter_issuing_authority']; ?>" value="<?php echo $license['issuing_authority']; ?>">
                            </div>
                            <div class="clearboth"></div>

                            <div class="row left">
                                <label for="type_of_registration"><?php echo $_page_lang['account.type_of_registration']; ?></label>
                                <input type="text" name="type_of_registration[]" placeholder="<?php echo $_page_lang['account.enter_type_of_registration']; ?>" value="<?php echo $license['type_of_registration']; ?>">
                            </div>
                            <div class="row right">
                                <label for="registration_number"><?php echo $_page_lang['account.business_license_number']; ?></label>
                                <input type="text" name="registration_number[]" placeholder="<?php echo $_page_lang['account.enter_business_license_number']; ?>" value="<?php echo $license['registration_number']; ?>">
                            </div>
                            <div class="clearboth"></div>
                        </div>
                        <?php } } else { ?>
                        <div class="block">
                            <a class="remove-license-block"><i class="fa fa-times"></i></a>

                            <input type="hidden" name="license_id[]" value="0">

                            <div class="num"><?php echo $_page_lang['account.business_license']; ?><span> - 1</span></div>

                            <div class="row left">
                                <label for="license_country"><?php echo $_page_lang['account.business_license_country']; ?></label>
                                <select name="license_country[]">
                                    <option value=""><?php echo $_page_lang['please_select']; ?></option>
                                    <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                                    <option value="<?php echo $country_id; ?>"><?php echo $country; ?></option>
                                    <?php }} ?>
                                </select>
                            </div>
                            <div class="row right">
                                <label for="issuing_authority"><?php echo $_page_lang['account.issuing_authority']; ?></label>
                                <input type="text" name="issuing_authority[]" placeholder="<?php echo $_page_lang['account.enter_issuing_authority']; ?>">
                            </div>
                            <div class="clearboth"></div>

                            <div class="row left">
                                <label for="type_of_registration"><?php echo $_page_lang['account.type_of_registration']; ?></label>
                                <input type="text" name="type_of_registration[]" placeholder="<?php echo $_page_lang['account.enter_type_of_registration']; ?>">
                            </div>
                            <div class="row right">
                                <label for="registration_number"><?php echo $_page_lang['account.business_license_number']; ?></label>
                                <input type="text" name="registration_number[]" placeholder="<?php echo $_page_lang['account.enter_business_license_number']; ?>">
                            </div>
                            <div class="clearboth"></div>
                        </div>
                        <?php } ?>
                    </div>
                    <div class="row">
                        <a class="add-license-block" href="#">
                            <span><?php echo $_page_lang['account.add_business_license']; ?></span>
                            <i class="fa fa-plus-circle"></i>
                        </a>
                    </div>
                </div>

                <?php } ?>

                <div>&nbsp;</div>
                <?php } ?>
                <?php } // end else (migration SP, not education institution) ?>

                <div class="group-title"><u><?php echo $_page_lang['account.contact_info']; ?></u></div>

                <div class="row left">
                    <label for="first_name"><?php echo $_page_lang['account.first_name']; ?> <span style="color:red;">*</span></label>
                    <input type="text" id="first_name" name="first_name" placeholder="<?php echo $_page_lang['account.enter_first_name']; ?>" value="<?php echo $_show_current_member['first_name']; ?>" data-validation="required">
                </div>
                <div class="row right">
                    <label for="last_name"><?php echo $_page_lang['account.last_name']; ?> <span style="color:red;">*</span></label>
                    <input type="text" id="last_name" name="last_name" placeholder="<?php echo $_page_lang['account.enter_last_name']; ?>" value="<?php echo $_show_current_member['last_name']; ?>" data-validation="required">
                </div>
                <div class="clearboth"></div>

                <div class="row">
                    <label for="email"><?php echo $_page_lang['account.email']; ?> <span style="color:red;">*</span></label>
                    <input type="text" id="email" name="email" placeholder="<?php echo $_page_lang['account.enter_email']; ?>" value="<?php echo $_show_current_member['email']; ?>" data-validation="required|email">
                </div>
                <div class="clearboth"></div>

                <?php if(in_array((int)$_show_current_member['type'], [2, 3])) { ?>
                <div class="row">
                    <label for="telephone"><?php echo $_page_lang['account.telephone']; ?> <span style="color:red;">*</span></label>
                    <table class="telephone">
                        <tr>
                            <td><input type="text" id="telephone_code" name="telephone_code" placeholder="+852" value="+<?php echo preg_replace('/^(\+)(.*)/i', '$2', $_show_current_member['telephone_code']); ?>" data-validation="required"></td>
                            <td><input type="text" id="telephone_num" name="telephone_num" placeholder="<?php echo $_page_lang['account.enter_telephone']; ?>" value="<?php echo $_show_current_member['telephone_num']; ?>" data-validation="required"></td>
                        </tr>
                    </table>
                </div>
                <div class="clearboth"></div>
                <?php } ?>

                <div class="row">
                    <label for="password"><?php echo $_page_lang['account.password']; ?> <small style="color:red;">(<?php echo $_page_lang['password_blank']; ?>)</small></label>
                    <input type="password" id="password" name="password" placeholder="<?php echo $_page_lang['account.enter_password']; ?>" value="" data-validation="password">
                </div>
                <div class="clearboth"></div>

                <div class="row">
                    <label for="repeat_password"><?php echo $_page_lang['account.re_password']; ?></label>
                    <input type="password" id="repeat_password" name="repeat_password" placeholder="<?php echo $_page_lang['account.enter_password']; ?>" value="" data-validation="password">
                </div>
                <div class="clearboth"></div>

                <?php if(in_array((int)$_show_current_member['type'], [1])) { ?>
                <div>&nbsp;</div>

                <div class="group-title"><u><?php echo $_page_lang['choose_your_preference']; ?></u></div>

                <div class="row">
                    <label for="migration_destination"><?php echo $_page_lang['account.migration_destination']; ?></label>
                    <select id="migration_destination" name="migration_destination">
                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                        <?php if(!empty($_page_options['countries'])) { foreach ($_page_options['countries'] as $country_id => $country) { ?>
                        <option value="<?php echo $country_id; ?>"<?php echo ($_show_current_member['migration_destination']==$country_id)?' selected':'';?>><?php echo $country; ?></option>
                        <?php }} ?>
                    </select>
                </div>
                <div class="clearboth"></div>

                <div class="row">
                    <label for="interested_visa"><?php echo $_page_lang['account.interested_visa']; ?> <span style="color:red;">*</span></label>
                    <select id="interested_visa" name="interested_visa" data-validation="required">
                        <option value=""><?php echo $_page_lang['please_select']; ?></option>
                       <?php if(!empty($_page_options['interest_visas'])) { foreach ($_page_options['interest_visas'] as $interest_visas_id => $interest_visas) { ?>
                        <option value="<?php echo $interest_visas_id; ?>"<?php echo ($_show_current_member['interested_visa']==$interest_visas_id)?' selected':'';?>><?php echo $interest_visas; ?></option>
                        <?php }} ?>
                    </select>
                </div>
                <div class="clearboth"></div>

                <div class="row">
                    <label for="interested_topic"><?php echo $_page_lang['account.interested_topic']; ?> <span style="color:red;">*</span></label>
                    <div class="iweb-checkbox-set">
                        <?php if(!empty($_page_options['interest_topics'])) { foreach ($_page_options['interest_topics'] as $interest_topics_id => $interest_topics) { ?>
                        <input type="checkbox" id="interested_topic_<?php echo $interest_topics_id; ?>" name="interested_topic[]" value="<?php echo $interest_topics_id; ?>"<?php echo (is_array($_show_current_member['interested_topic']) && in_array($interest_topics_id, $_show_current_member['interested_topic']))?' checked':'';?> data-validation="required">
                        <label for="interested_topic_<?php echo $interest_topics_id; ?>"><?php echo $interest_topics; ?></label>
                        <?php }} ?>
                    </div>
                </div>
                <div class="clearboth"></div>
                <?php } ?>

                <div class="action">
                    <button type="submit" class="btn btn-save"><?php echo $_page_lang['btn.save']; ?></button>
                    <div class="clearboth"></div>
                </div>
            </form>
            <?php if($_is_edu_institution && !empty($_institution_profile)): ?>
            <?php
            $_oip = (array)$_institution_profile;
            // Parse structured courses for preview
            $_oip_courses = _extractInstitutionCourses($_oip);
            $_oip_has_preview = !empty($_oip['summary']) || count($_oip_courses) > 0 || !empty($_oip['key_dates']);
            ?>
            <?php if($_oip_has_preview): ?>
            <div class="edu-profile-view" style="margin-top:28px;">
                <div style="font-weight:700;font-size:1em;color:#1a1a2e;margin-bottom:14px;border-bottom:2px solid #e4e8f0;padding-bottom:8px;">Your Institution Profile Preview</div>

                <?php
                $_oip_logo = '';
                if (!empty($_show_current_member['avatar'])) {
                    if (file_exists('upload/member_logo/'.$_show_current_member['avatar'])) {
                        $_oip_logo = 'upload/member_logo/'.$_show_current_member['avatar'];
                    }
                }
                $_oip_instname = htmlspecialchars($_oip['institute_name'] ?? ($_show_current_member_details['company_name'] ?? ''), ENT_QUOTES, 'UTF-8');
                ?>
                <?php if($_oip_instname): ?>
                <div class="edu-profile-header" style="margin-bottom:16px;">
                    <?php if($_oip_logo): ?>
                    <img src="<?php echo $_oip_logo; ?>" alt="logo" class="edu-profile-logo">
                    <?php endif; ?>
                    <div class="edu-profile-header-text">
                        <h2 class="edu-profile-inst-name"><?php echo $_oip_instname; ?></h2>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(!empty($_oip['summary'])): ?>
                <div class="edu-course-card" id="oprev-s-summary" style="margin-bottom:10px;">
                    <div class="edu-course-card-header" onclick="toggleOPrev('summary')">
                        <div class="edu-course-card-main"><div class="edu-course-name">About This Institution</div></div>
                        <span class="edu-course-toggle" id="oprev-tog-summary">&#x25B2;</span>
                    </div>
                    <div class="edu-course-details" id="oprev-det-summary">
                        <div class="edu-course-tab-body">
                            <?php echo _renderOverviewHtml($_oip['summary']); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(count($_oip_courses) > 0): ?>
                <div class="edu-profile-section">
                    <div class="edu-profile-section-title">Courses Offered <span style="font-size:0.78em;font-weight:500;color:#888;">(<?php echo count($_oip_courses); ?> course<?php echo count($_oip_courses) !== 1 ? 's' : ''; ?>)</span></div>
                    <div class="edu-courses-list">
                    <?php foreach($_oip_courses as $_oci => $_oc):
                    $_ocn  = htmlspecialchars($_oc['name'] ?? '', ENT_QUOTES, 'UTF-8');
                    if (!$_ocn) continue;
                    $_occ  = htmlspecialchars($_oc['code']     ?? '', ENT_QUOTES, 'UTF-8');
                    $_occr = htmlspecialchars($_oc['cricos']   ?? '', ENT_QUOTES, 'UTF-8');
                    $_ocd  = htmlspecialchars($_oc['delivery'] ?? '', ENT_QUOTES, 'UTF-8');
                    $_ocdu = htmlspecialchars($_oc['duration'] ?? '', ENT_QUOTES, 'UTF-8');
                    $_oce  = htmlspecialchars($_oc['entry']    ?? '', ENT_QUOTES, 'UTF-8');
                    $_ocov = $_oc['overview'] ?? '';
                    $_ocra = trim($_oc['req_academic']  ?? '');
                    $_ocri = trim($_oc['req_ielts']     ?? '');
                    $_ocrp = trim($_oc['req_pte']       ?? '');
                    $_ocrt = trim($_oc['req_toefl']     ?? '');
                    $_ocrc = trim($_oc['req_cambridge'] ?? '');
                    $_ocrd = trim($_oc['req_duolingo']  ?? '');
                    $_ocrdc= trim($_oc['req_documents'] ?? '');
                    $_ocrn = trim($_oc['req_notes']     ?? '');
                    $_ocft = trim($_oc['fee_tuition']   ?? '');
                    $_ocfa = trim($_oc['fee_application']?? '');
                    $_ocfo = trim($_oc['fee_oshc']      ?? '');
                    $_ocfl = trim($_oc['fee_living']    ?? '');
                    $_ocfn = trim($_oc['fee_notes']     ?? '');
                    $_ocsc = $_oc['scholarships'] ?? '';
                    $_ocrq = $_ocra || $_ocri || $_ocrp || $_ocrt || $_ocrc || $_ocrd || $_ocrdc || $_ocrn;
                    $_ocfees = $_ocft || $_ocfa || $_ocfo || $_ocfl || $_ocfn;
                    $_ochasdet = $_ocov || $_ocrq || $_ocfees || $_ocsc;
                    $_octab = $_ocov ? 'overview' : ($_ocrq ? 'requirements' : ($_ocfees ? 'fees' : 'scholarships'));
                    ?>
                    <div class="edu-course-card" id="oprev-c-<?php echo $_oci; ?>">
                        <div class="edu-course-card-header" onclick="toggleOPrevC(<?php echo $_oci; ?>)">
                            <div class="edu-course-card-main">
                                <div class="edu-course-name"><?php echo $_ocn; ?></div>
                                <div class="edu-course-pills">
                                    <?php if($_occ): ?><span class="edu-course-pill edu-pill-code"><?php echo $_occ; ?></span><?php endif; ?>
                                    <?php if($_occr): ?><span class="edu-course-pill edu-pill-cricos">CRICOS <?php echo $_occr; ?></span><?php endif; ?>
                                    <?php if($_ocdu): ?><span class="edu-course-pill edu-pill-duration">&#x23F1; <?php echo $_ocdu; ?></span><?php endif; ?>
                                    <?php if($_ocd): ?><span class="edu-course-pill edu-pill-delivery">&#x1F4CD; <?php echo $_ocd; ?></span><?php endif; ?>
                                    <?php if($_oce): ?><span class="edu-course-pill edu-pill-entry">&#x1F4C5; <?php echo $_oce; ?></span><?php endif; ?>
                                    <?php if($_ocft): ?><span class="edu-course-pill" style="background:#fff8e1;color:#7a4000;border:1px solid #ffd54f;">&#x1F4B0; <?php echo htmlspecialchars($_ocft, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                                </div>
                            </div>
                            <?php if($_ochasdet): ?><span class="edu-course-toggle" id="oprev-ctog-<?php echo $_oci; ?>">&#x25BC;</span><?php endif; ?>
                        </div>
                        <?php if($_ochasdet): ?>
                        <div class="edu-course-details" id="oprev-cdet-<?php echo $_oci; ?>" style="display:none;">
                            <div class="edu-course-tabs-nav" id="oprev-cnav-<?php echo $_oci; ?>">
                                <?php if($_ocov): ?><button type="button" class="edu-course-tab-btn<?php echo $_octab==='overview' ? ' active' : ''; ?>" onclick="switchOPrevC(<?php echo $_oci; ?>,'overview')">What to Expect</button><?php endif; ?>
                                <?php if($_ocrq): ?><button type="button" class="edu-course-tab-btn<?php echo $_octab==='requirements' ? ' active' : ''; ?>" onclick="switchOPrevC(<?php echo $_oci; ?>,'requirements')">Requirements</button><?php endif; ?>
                                <?php if($_ocfees): ?><button type="button" class="edu-course-tab-btn<?php echo $_octab==='fees' ? ' active' : ''; ?>" onclick="switchOPrevC(<?php echo $_oci; ?>,'fees')">Fees</button><?php endif; ?>
                                <?php if($_ocsc): ?><button type="button" class="edu-course-tab-btn<?php echo $_octab==='scholarships' ? ' active' : ''; ?>" onclick="switchOPrevC(<?php echo $_oci; ?>,'scholarships')">Scholarships</button><?php endif; ?>
                            </div>
                            <div class="edu-course-tab-body">
                                <?php if($_ocov): ?>
                                <div id="oprev-ct-<?php echo $_oci; ?>-overview" class="edu-course-tab-content"<?php echo $_octab!=='overview' ? ' style="display:none;"' : ''; ?>>
                                    <?php if(strlen($_ocov) > 400): ?>
                                    <div class="ov-wrap">
                                        <div class="ov-body" id="oprev-ov-<?php echo $_oci; ?>">
                                            <?php echo _renderOverviewHtml($_ocov); ?>
                                        </div>
                                        <div class="ov-fade"></div>
                                    </div>
                                    <button class="ov-toggle" onclick="toggleOv('oprev-ov-<?php echo $_oci; ?>', this)">Read more &#x25BE;</button>
                                    <?php else: ?>
                                    <?php echo _renderOverviewHtml($_ocov); ?>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <?php if($_ocrq): ?>
                                <div id="oprev-ct-<?php echo $_oci; ?>-requirements" class="edu-course-tab-content"<?php echo $_octab!=='requirements' ? ' style="display:none;"' : ''; ?>>
                                    <div class="pub-req-section">
                                        <?php if($_ocra): ?><div class="pub-req-row"><div class="pub-req-label">Academic Requirement</div><div class="pub-req-value"><?php echo htmlspecialchars($_ocra, ENT_QUOTES, 'UTF-8'); ?></div></div><?php endif; ?>
                                        <?php if($_ocri || $_ocrp || $_ocrt || $_ocrc || $_ocrd): ?>
                                        <div class="pub-req-row">
                                            <div class="pub-req-label">English Proficiency</div>
                                            <div class="pub-english-scores">
                                                <?php if($_ocri): ?><div class="pub-score-badge"><span class="pub-score-badge-name">IELTS</span><span class="pub-score-badge-val"><?php echo htmlspecialchars($_ocri, ENT_QUOTES, 'UTF-8'); ?></span></div><?php endif; ?>
                                                <?php if($_ocrp): ?><div class="pub-score-badge"><span class="pub-score-badge-name">PTE</span><span class="pub-score-badge-val"><?php echo htmlspecialchars($_ocrp, ENT_QUOTES, 'UTF-8'); ?></span></div><?php endif; ?>
                                                <?php if($_ocrt): ?><div class="pub-score-badge"><span class="pub-score-badge-name">TOEFL iBT</span><span class="pub-score-badge-val"><?php echo htmlspecialchars($_ocrt, ENT_QUOTES, 'UTF-8'); ?></span></div><?php endif; ?>
                                                <?php if($_ocrc): ?><div class="pub-score-badge"><span class="pub-score-badge-name">Cambridge</span><span class="pub-score-badge-val"><?php echo htmlspecialchars($_ocrc, ENT_QUOTES, 'UTF-8'); ?></span></div><?php endif; ?>
                                                <?php if($_ocrd): ?><div class="pub-score-badge"><span class="pub-score-badge-name">Duolingo</span><span class="pub-score-badge-val"><?php echo htmlspecialchars($_ocrd, ENT_QUOTES, 'UTF-8'); ?></span></div><?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if($_ocrdc): ?><div class="pub-req-row"><div class="pub-req-label">Required Documents</div><div class="pub-req-value"><?php echo nl2br(htmlspecialchars($_ocrdc, ENT_QUOTES, 'UTF-8')); ?></div></div><?php endif; ?>
                                        <?php if($_ocrn): ?><div class="pub-req-row"><div class="pub-req-label">Other Requirements</div><div class="pub-req-value"><?php echo nl2br(htmlspecialchars($_ocrn, ENT_QUOTES, 'UTF-8')); ?></div></div><?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if($_ocfees): ?>
                                <div id="oprev-ct-<?php echo $_oci; ?>-fees" class="edu-course-tab-content"<?php echo $_octab!=='fees' ? ' style="display:none;"' : ''; ?>>
                                    <?php if($_ocft): ?><div class="pub-tuition-highlight"><div class="pub-tuition-label">Annual Tuition (International)</div><div class="pub-tuition-value"><?php echo htmlspecialchars($_ocft, ENT_QUOTES, 'UTF-8'); ?></div></div><?php endif; ?>
                                    <?php if($_ocfa || $_ocfo || $_ocfl): ?>
                                    <div class="pub-fees-grid" style="margin-top:10px;">
                                        <?php if($_ocfa): ?><div class="pub-fee-item"><div class="pub-fee-label">Application Fee</div><div class="pub-fee-value"><?php echo htmlspecialchars($_ocfa, ENT_QUOTES, 'UTF-8'); ?></div></div><?php endif; ?>
                                        <?php if($_ocfo): ?><div class="pub-fee-item"><div class="pub-fee-label">OSHC / year</div><div class="pub-fee-value"><?php echo htmlspecialchars($_ocfo, ENT_QUOTES, 'UTF-8'); ?></div></div><?php endif; ?>
                                        <?php if($_ocfl): ?><div class="pub-fee-item" style="grid-column:1/-1;"><div class="pub-fee-label">Estimated Living Cost / year</div><div class="pub-fee-value"><?php echo htmlspecialchars($_ocfl, ENT_QUOTES, 'UTF-8'); ?></div></div><?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if($_ocfn): ?><div class="pub-req-row" style="margin-top:10px;"><div class="pub-req-label">Payment Notes</div><div class="pub-req-value"><?php echo nl2br(htmlspecialchars($_ocfn, ENT_QUOTES, 'UTF-8')); ?></div></div><?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <?php if($_ocsc): ?>
                                <div id="oprev-ct-<?php echo $_oci; ?>-scholarships" class="edu-course-tab-content"<?php echo $_octab!=='scholarships' ? ' style="display:none;"' : ''; ?>>
                                    <div class="edu-profile-text"><?php echo nl2br(htmlspecialchars($_ocsc, ENT_QUOTES, 'UTF-8')); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(!empty($_oip['key_dates'])): ?>
                <div class="edu-course-card" id="oprev-s-keydates" style="margin-bottom:10px;">
                    <div class="edu-course-card-header" onclick="toggleOPrev('keydates')">
                        <div class="edu-course-card-main"><div class="edu-course-name">&#x1F4C6; Key Dates &amp; Intake Information</div></div>
                        <span class="edu-course-toggle" id="oprev-tog-keydates">&#x25B2;</span>
                    </div>
                    <div class="edu-course-details" id="oprev-det-keydates">
                        <div class="edu-course-tab-body" style="padding:14px 16px;">
                            <?php echo _renderKeyDatesHtml($_oip['key_dates']); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <script>
            function toggleOv(bodyId, btn) {
                var body = document.getElementById(bodyId);
                if (!body) return;
                var expanded = body.classList.toggle('expanded');
                btn.innerHTML = expanded ? 'Show less &#x25B4;' : 'Read more &#x25BE;';
            }
            function toggleOPrev(key) {
                var det = document.getElementById('oprev-det-' + key);
                var tog = document.getElementById('oprev-tog-' + key);
                if (!det) return;
                var open = det.style.display !== 'none';
                det.style.display = open ? 'none' : 'block';
                if (tog) tog.innerHTML = open ? '&#x25BC;' : '&#x25B2;';
            }
            function toggleOPrevC(idx) {
                var det = document.getElementById('oprev-cdet-' + idx);
                var tog = document.getElementById('oprev-ctog-' + idx);
                if (!det) return;
                var open = det.style.display !== 'none';
                det.style.display = open ? 'none' : 'block';
                if (tog) tog.innerHTML = open ? '&#x25BC;' : '&#x25B2;';
            }
            function switchOPrevC(cidx, tabKey) {
                var tabs = ['overview','requirements','fees','scholarships'];
                tabs.forEach(function(t) {
                    var content = document.getElementById('oprev-ct-' + cidx + '-' + t);
                    var btn = document.querySelector('#oprev-cnav-' + cidx + ' .edu-course-tab-btn[onclick*="\'' + t + '\'"]');
                    if (content) content.style.display = (t === tabKey) ? 'block' : 'none';
                    if (btn) btn.classList.toggle('active', t === tabKey);
                });
            }
            </script>
            <?php endif; ?>
            <?php endif; ?>
            <div class="edu-about-posts" style="margin-top:28px;">
                <div class="edu-about-posts-title">Posts</div>
                <?php if($_is_edu_institution): ?>
                <div class="mypost">
                    <?php if(empty($_page_data['is_readonly'])): ?>
                    <div class="publish">
                        <div class="title"><i class="fa fa-pencil-square-o"></i><span><?php echo $_page_lang['posts.start']; ?></span></div>
                        <div class="media">
                            <a id="publish-photo"><i class="fa fa-picture-o"></i><span><?php echo $_page_lang['posts.photo']; ?></span></a>
                            <a id="publish-video"><i class="fa fa-youtube-play"></i><span><?php echo $_page_lang['posts.video']; ?></span></a>
                        </div>
                    </div>
                    <div class="clearboth"></div>
                    <?php endif; ?>
                    <div class="article-list" data-mid="<?php echo $_profile_posts_mid; ?>"></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php } else { ?>
    <div class="tab-details">
        <div class="form">
            
            <?php if(!$_is_edu_institution) { ?>
            <div class="ac-type">
                <?php echo $_page_lang['account.ac_type_'.$_show_current_member['type']]; ?>
            </div>

            <div class="subscription-info">
                <div class="subscription-name">
                    <strong>Subscription:</strong> <?php echo !empty($_show_current_member['subscription_name']) ? $_show_current_member['subscription_name'] : 'Free Plan'; ?>
                </div>
                <div class="subscription-expiry">
                    <strong>Expires:</strong> <?php echo !empty($_show_current_member['subscription_expiry']) ? date('M d, Y', strtotime($_show_current_member['subscription_expiry'])) : 'N/A'; ?>
                </div>
            </div>

            <div class="clearboth"></div>

            <div class="further-title">
                <h1><?php echo $_page_lang['tab_about']; ?></h1>
                <div class="input-value"><?php echo nl2br($_show_current_member['remark']); ?></div>
            </div>
            <div class="clearboth"></div>
            <?php } ?>

            <?php if(in_array((int)$_show_current_member['type'], [2, 3])) { ?>
            <?php if($_is_edu_institution) { ?>
            <?php
            $_ip = !empty($_institution_profile) ? (array)$_institution_profile : [];
            $_ip_name    = htmlspecialchars($_ip['institute_name'] ?? ($_show_current_member_details['company_name'] ?? ''), ENT_QUOTES, 'UTF-8');
            $_ip_website = htmlspecialchars($_ip['website_url'] ?? ($_show_current_member_details['company_website'] ?? ''), ENT_QUOTES, 'UTF-8');
            $_ip_summary   = $_ip['summary']    ?? '';
            $_ip_dates     = $_ip['key_dates']   ?? '';
            // Parse courses JSON
            $_ip_courses = _extractInstitutionCourses($_ip);
            $hasContent = !empty($_ip_summary) || count($_ip_courses) > 0;
            ?>
            <?php
            // Extract gallery photos
            $_ip_gallery = [];
            foreach (_extractInstitutionGallery($_ip, (int)($_show_current_member['id'] ?? 0)) as $_gf) {
                if (file_exists(public_path('upload/inst_gallery/' . $_gf))) {
                    $_ip_gallery[] = $_gf;
                }
            }
            // Logo path
            $_apb_logo = '';
            if (!empty($_show_current_member['avatar'])) {
                $_lp = 'upload/member_logo/' . $_show_current_member['avatar'];
                if (file_exists($_lp)) { $_apb_logo = $_lp; }
            }
            // Tabs visibility
            $_apb_show_overview  = !empty($_ip_summary);
            $_apb_show_courses   = count($_ip_courses) > 0;
            $_apb_show_keydates  = !empty($_ip_dates);
            $_apb_first_tab      = $_apb_show_overview ? 'overview' : ($_apb_show_courses ? 'courses' : 'keydates');
            // Extract RTO/CRICOS from summary
            $_apb_rto = ''; $_apb_cricos = '';
            if ($_ip_summary) {
                if (preg_match('/\bRTO[#:\s]+([0-9]+)/i', $_ip_summary, $_m)) { $_apb_rto = $_m[1]; }
                if (preg_match('/\bCRICOS[#:\s]+([0-9A-Z]{4,10})/i', $_ip_summary, $_m)) { $_apb_cricos = $_m[1]; }
            }
            // Tuition range
            $_apb_min_tuition = null; $_apb_max_tuition = null;
            foreach ($_ip_courses as $_c) {
                $_raw = trim($_c['fee_tuition'] ?? '');
                if ($_raw) {
                    preg_match_all('/[0-9][0-9,]+/', $_raw, $_nums);
                    foreach ($_nums[0] as $_n) {
                        $_v = (int) str_replace(',', '', $_n);
                        if ($_v > 500) {
                            if ($_apb_min_tuition === null || $_v < $_apb_min_tuition) $_apb_min_tuition = $_v;
                            if ($_apb_max_tuition === null || $_v > $_apb_max_tuition) $_apb_max_tuition = $_v;
                        }
                    }
                }
            }
            $_apb_tuition_str = '';
            if ($_apb_min_tuition !== null) {
                $_apb_tuition_str = 'A$' . number_format($_apb_min_tuition);
                if ($_apb_max_tuition !== null && $_apb_max_tuition !== $_apb_min_tuition) {
                    $_apb_tuition_str .= ' – A$' . number_format($_apb_max_tuition);
                }
                $_apb_tuition_str .= ' / year';
            }
            ?>
            <div class="apb-profile-page">

                <!-- HERO BANNER -->
                <?php
                // Hero always uses the default campus background (set by admin via CSS).
                // An institution's own cover photo can override it, but gallery photos never override the hero.
                $_apb_cover = '';
                if (!empty($_show_current_member['coverphoto'])) {
                    $_cp = 'upload/member_coverphoto/' . $_show_current_member['coverphoto'];
                    if (file_exists(public_path($_cp))) { $_apb_cover = $_cp; }
                }
                ?>
                <div class="apb-hero<?php echo $_apb_cover ? ' apb-hero-photo' : ''; ?>"<?php echo $_apb_cover ? ' style="background-image:url(\'' . htmlspecialchars($_apb_cover,ENT_QUOTES,'UTF-8') . '\')"' : ''; ?>><div class="apb-hero-overlay"></div></div>

                <!-- INSTITUTION HEADER BAR -->
                <div class="apb-inst-bar">
                    <?php if ($_apb_logo): ?>
                    <div class="apb-logo-wrap">
                        <img src="<?php echo htmlspecialchars($_apb_logo, ENT_QUOTES); ?>" alt="logo" class="apb-logo-img">
                    </div>
                    <?php endif; ?>
                    <div class="apb-inst-info">
                        <h1 class="apb-inst-name"><?php echo $_ip_name; ?></h1>
                        <div class="apb-inst-meta">
                            <?php if ($_apb_cricos): ?><span class="apb-inst-pill">CRICOS <?php echo htmlspecialchars($_apb_cricos, ENT_QUOTES); ?></span><?php endif; ?>
                            <?php if ($_apb_rto): ?><span class="apb-inst-pill">RTO <?php echo htmlspecialchars($_apb_rto, ENT_QUOTES); ?></span><?php endif; ?>
                            <?php if ($_apb_show_courses): ?><span class="apb-inst-pill apb-pill-courses"><?php echo count($_ip_courses); ?> Course<?php echo count($_ip_courses) !== 1 ? 's' : ''; ?></span><?php endif; ?>
                        </div>
                    </div>
                    <div class="apb-inst-cta">
                        <a href="<?php echo $_page_base_url; ?>/apply?institution=<?php echo urlencode($_ip_name); ?>" class="apb-apply-btn">Apply Now</a>
                    </div>
                </div>

                <!-- PHOTO GALLERY -->
                <?php if (!empty($_ip_gallery)): ?>
                <div class="apb-gallery">
                    <?php $_galleryCount = count($_ip_gallery); ?>
                    <?php if ($_galleryCount > 1): ?>
                    <button type="button" class="apb-gallery-nav apb-gallery-nav-prev" id="apb-gallery-prev" aria-label="Previous photo">&#x2039;</button>
                    <?php endif; ?>
                    <div class="apb-gallery-slider" id="apb-gallery-slider">
                        <?php foreach ($_ip_gallery as $_gpi => $_gpf): ?>
                        <div class="apb-gallery-slide">
                            <img src="upload/inst_gallery/<?php echo htmlspecialchars($_gpf, ENT_QUOTES); ?>" alt="Campus photo <?php echo $_gpi + 1; ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($_galleryCount > 1): ?>
                    <button type="button" class="apb-gallery-nav apb-gallery-nav-next" id="apb-gallery-next" aria-label="Next photo">&#x203A;</button>
                    <div class="apb-gallery-count" id="apb-gallery-count">1 / <?php echo $_galleryCount; ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- TAB NAVIGATION -->
                <?php if ($hasContent): ?>
                <div class="apb-tab-nav" id="apb-tab-nav">
                    <?php if ($_apb_show_overview): ?>
                    <button class="apb-tab-btn<?php echo $_apb_first_tab === 'overview' ? ' active' : ''; ?>" onclick="apbSwitchTab('overview')">Overview</button>
                    <?php endif; ?>
                    <?php if ($_apb_show_courses): ?>
                    <button class="apb-tab-btn<?php echo $_apb_first_tab === 'courses' ? ' active' : ''; ?>" onclick="apbSwitchTab('courses')">
                        Courses <span class="apb-tab-count"><?php echo count($_ip_courses); ?></span>
                    </button>
                    <?php endif; ?>
                    <?php if ($_apb_show_keydates): ?>
                    <button class="apb-tab-btn<?php echo $_apb_first_tab === 'keydates' ? ' active' : ''; ?>" onclick="apbSwitchTab('keydates')">Key Dates</button>
                    <?php endif; ?>
                </div>

                <!-- TAB CONTENT -->
                <div class="apb-tab-content">

                    <!-- OVERVIEW TAB -->
                    <?php if ($_apb_show_overview): ?>
                    <div id="apb-pane-overview" class="apb-pane<?php echo $_apb_first_tab === 'overview' ? '' : ' apb-pane-hidden'; ?>">
                        <div class="apb-two-col">
                            <div class="apb-main-col">
                                <div class="apb-content-card">
                                    <h3 class="apb-card-title">About This Institution</h3>
                                    <?php if (strlen($_ip_summary) > 600): ?>
                                    <div class="ov-wrap">
                                        <div class="ov-body" id="apb-ov-summary"><?php echo _renderOverviewHtml($_ip_summary); ?></div>
                                        <div class="ov-fade"></div>
                                    </div>
                                    <button class="ov-toggle" onclick="toggleOv('apb-ov-summary', this)">Read more &#x25BE;</button>
                                    <?php else: ?>
                                    <?php echo _renderOverviewHtml($_ip_summary); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="apb-sidebar-col">
                                <div class="apb-sidebar-card">
                                    <h4 class="apb-sidebar-title">Institution Details</h4>
                                    <?php if ($_apb_rto): ?><div class="apb-detail-row"><span class="apb-detail-label">RTO Number</span><span class="apb-detail-val"><?php echo htmlspecialchars($_apb_rto, ENT_QUOTES); ?></span></div><?php endif; ?>
                                    <?php if ($_apb_cricos): ?><div class="apb-detail-row"><span class="apb-detail-label">CRICOS Code</span><span class="apb-detail-val"><?php echo htmlspecialchars($_apb_cricos, ENT_QUOTES); ?></span></div><?php endif; ?>
                                    <?php if ($_apb_show_courses): ?><div class="apb-detail-row"><span class="apb-detail-label">Courses</span><span class="apb-detail-val"><?php echo count($_ip_courses); ?></span></div><?php endif; ?>
                                </div>
                                <?php if ($_apb_tuition_str): ?>
                                <div class="apb-sidebar-card" style="margin-top:12px;">
                                    <h4 class="apb-sidebar-title">Estimated Tuition</h4>
                                    <div class="apb-tuition-badge"><?php echo htmlspecialchars($_apb_tuition_str, ENT_QUOTES); ?></div>
                                    <p style="font-size:0.8em;color:#888;margin:6px 0 0;">Across all courses. Individual fees vary.</p>
                                </div>
                                <?php endif; ?>
                                <div class="apb-sidebar-card apb-sidebar-apply" style="margin-top:12px;">
                                    <p style="font-size:0.88em;color:#555;margin:0 0 10px;">Interested in studying here?</p>
                                    <a href="<?php echo $_page_base_url; ?>/apply?institution=<?php echo urlencode($_ip_name); ?>" class="apb-apply-btn-full">Apply Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- COURSES TAB -->
                    <?php if ($_apb_show_courses): ?>
                    <div id="apb-pane-courses" class="apb-pane<?php echo $_apb_first_tab === 'courses' ? '' : ' apb-pane-hidden'; ?>">
                        <?php
                        $_apb_levels = ['All'];
                        foreach ($_ip_courses as $_c) {
                            $_nm = $_c['name'] ?? '';
                            if (preg_match('/\b(master|mba|msc|phd|doctor|graduate)/i', $_nm)) $_lev = 'Postgraduate';
                            elseif (preg_match('/\badvanced diploma/i', $_nm)) $_lev = 'Advanced Diploma';
                            elseif (preg_match('/\bdiploma/i', $_nm)) $_lev = 'Diploma';
                            elseif (preg_match('/\bcertificate/i', $_nm)) $_lev = 'Certificate';
                            elseif (preg_match('/\bbachelor/i', $_nm)) $_lev = 'Bachelor';
                            else $_lev = 'Other';
                            if (!in_array($_lev, $_apb_levels)) $_apb_levels[] = $_lev;
                        }
                        ?>
                        <?php if (count($_apb_levels) > 2): ?>
                        <div class="apb-filter-bar">
                            <?php foreach ($_apb_levels as $_lv): ?>
                            <button class="apb-filter-pill<?php echo $_lv === 'All' ? ' active' : ''; ?>" onclick="apbFilterCourses(this,'<?php echo htmlspecialchars($_lv, ENT_QUOTES); ?>')"><?php echo htmlspecialchars($_lv, ENT_QUOTES); ?></button>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <div class="apb-program-list" id="apb-program-list">
                        <?php foreach ($_ip_courses as $cidx => $course): ?>
                        <?php
                        $cName        = htmlspecialchars($course['name'] ?? '', ENT_QUOTES, 'UTF-8');
                        $cCode        = htmlspecialchars($course['code'] ?? '', ENT_QUOTES, 'UTF-8');
                        $cCricos      = htmlspecialchars($course['cricos'] ?? '', ENT_QUOTES, 'UTF-8');
                        $cDelivery    = htmlspecialchars($course['delivery'] ?? '', ENT_QUOTES, 'UTF-8');
                        $cDuration    = htmlspecialchars($course['duration'] ?? '', ENT_QUOTES, 'UTF-8');
                        $cEntry       = htmlspecialchars($course['entry'] ?? '', ENT_QUOTES, 'UTF-8');
                        $cOverview    = $course['overview'] ?? '';
                        $cReqAcademic  = trim($course['req_academic'] ?? '');
                        $cReqIelts     = trim($course['req_ielts'] ?? '');
                        $cReqPte       = trim($course['req_pte'] ?? '');
                        $cReqToefl     = trim($course['req_toefl'] ?? '');
                        $cReqCambridge = trim($course['req_cambridge'] ?? '');
                        $cReqDuolingo  = trim($course['req_duolingo'] ?? '');
                        $cReqDocs      = trim($course['req_documents'] ?? '');
                        $cReqNotes     = trim($course['req_notes'] ?? '');
                        $cFeeTuition    = trim($course['fee_tuition'] ?? '');
                        $cFeeApp        = trim($course['fee_application'] ?? '');
                        $cFeeOshc       = trim($course['fee_oshc'] ?? '');
                        $cFeeLiving     = trim($course['fee_living'] ?? '');
                        $cFeeNotes      = trim($course['fee_notes'] ?? '');
                        $cScholar       = $course['scholarships'] ?? '';
                        $hasReqs    = $cReqAcademic || $cReqIelts || $cReqPte || $cReqToefl || $cReqCambridge || $cReqDuolingo || $cReqDocs || $cReqNotes;
                        $hasFees    = $cFeeTuition || $cFeeApp || $cFeeOshc || $cFeeLiving || $cFeeNotes;
                        $hasDetails = $cOverview || $hasReqs || $hasFees || $cScholar;
                        if (!$cName) continue;
                        $cNm = $course['name'] ?? '';
                        if (preg_match('/\b(master|mba|msc|phd|doctor|graduate)/i', $cNm)) $cLevel = 'Postgraduate';
                        elseif (preg_match('/\badvanced diploma/i', $cNm)) $cLevel = 'Advanced Diploma';
                        elseif (preg_match('/\bdiploma/i', $cNm)) $cLevel = 'Diploma';
                        elseif (preg_match('/\bcertificate/i', $cNm)) $cLevel = 'Certificate';
                        elseif (preg_match('/\bbachelor/i', $cNm)) $cLevel = 'Bachelor';
                        else $cLevel = 'Other';
                        $cIcon = ($cLevel === 'Postgraduate') ? '🎓'
                               : ($cLevel === 'Bachelor' ? '🏛️'
                               : ($cLevel === 'Diploma' || $cLevel === 'Advanced Diploma' ? '📋'
                               : ($cLevel === 'Certificate' ? '📜' : '📚')));
                        $firstTab = $cOverview ? 'overview' : ($hasReqs ? 'requirements' : ($hasFees ? 'fees' : 'scholarships'));
                        ?>
                        <div class="apb-program-card" data-level="<?php echo htmlspecialchars($cLevel, ENT_QUOTES); ?>" id="apb-program-<?php echo $cidx; ?>">
                            <div class="apb-program-card-top">
                                <div class="apb-program-icon"><?php echo $cIcon; ?></div>
                                <div class="apb-program-info">
                                    <div class="apb-program-name"><?php echo $cName; ?></div>
                                    <div class="apb-program-subinfo">
                                        <?php if ($cCode): ?><span class="apb-prog-tag"><?php echo $cCode; ?></span><?php endif; ?>
                                        <?php if ($cCricos): ?><span class="apb-prog-tag">CRICOS <?php echo $cCricos; ?></span><?php endif; ?>
                                        <?php if ($cDelivery): ?><span class="apb-prog-tag">📍 <?php echo $cDelivery; ?></span><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php if ($cEntry || $cDuration || $cFeeTuition || $cFeeApp): ?>
                            <div class="apb-program-stats">
                                <?php if ($cEntry): ?><div class="apb-stat-item"><div class="apb-stat-label">Earliest Intake</div><div class="apb-stat-val"><?php echo $cEntry; ?></div></div><?php endif; ?>
                                <?php if ($cDuration): ?><div class="apb-stat-item"><div class="apb-stat-label">Duration</div><div class="apb-stat-val"><?php echo $cDuration; ?></div></div><?php endif; ?>
                                <?php if ($cFeeTuition): ?><div class="apb-stat-item"><div class="apb-stat-label">Gross Tuition</div><div class="apb-stat-val apb-stat-tuition"><?php echo htmlspecialchars($cFeeTuition, ENT_QUOTES, 'UTF-8'); ?></div></div><?php endif; ?>
                                <?php if ($cFeeApp): ?><div class="apb-stat-item"><div class="apb-stat-label">Application Fee</div><div class="apb-stat-val"><?php echo htmlspecialchars($cFeeApp, ENT_QUOTES, 'UTF-8'); ?></div></div><?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($hasDetails): ?>
                            <div class="apb-program-footer">
                                <button class="apb-view-details-btn" id="apb-prog-tog-<?php echo $cidx; ?>" onclick="apbToggleProgram(<?php echo $cidx; ?>)">
                                    View Program Details <span class="apb-tog-arrow">&#x25BC;</span>
                                </button>
                            </div>
                            <div class="apb-program-details" id="apb-prog-det-<?php echo $cidx; ?>" style="display:none;">
                                <div class="edu-course-tabs-nav" id="apb-prog-nav-<?php echo $cidx; ?>">
                                    <?php if($cOverview): ?><button type="button" class="edu-course-tab-btn<?php echo $firstTab==='overview' ? ' active' : ''; ?>" onclick="switchPubTab(<?php echo $cidx; ?>,'overview')">What to Expect</button><?php endif; ?>
                                    <?php if($hasReqs): ?><button type="button" class="edu-course-tab-btn<?php echo $firstTab==='requirements' ? ' active' : ''; ?>" onclick="switchPubTab(<?php echo $cidx; ?>,'requirements')">Requirements</button><?php endif; ?>
                                    <?php if($hasFees): ?><button type="button" class="edu-course-tab-btn<?php echo $firstTab==='fees' ? ' active' : ''; ?>" onclick="switchPubTab(<?php echo $cidx; ?>,'fees')">Fees</button><?php endif; ?>
                                    <?php if($cScholar): ?><button type="button" class="edu-course-tab-btn<?php echo $firstTab==='scholarships' ? ' active' : ''; ?>" onclick="switchPubTab(<?php echo $cidx; ?>,'scholarships')">Scholarships</button><?php endif; ?>
                                </div>
                                <div class="edu-course-tab-body">
                                    <?php if($cOverview): ?>
                                    <div id="pub-tab-<?php echo $cidx; ?>-overview" class="edu-course-tab-content"<?php echo $firstTab!=='overview' ? ' style="display:none;"' : ''; ?>>
                                        <?php if(strlen($cOverview) > 400): ?>
                                        <div class="ov-wrap"><div class="ov-body" id="pub-ov-<?php echo $cidx; ?>"><?php echo _renderOverviewHtml($cOverview); ?></div><div class="ov-fade"></div></div>
                                        <button class="ov-toggle" onclick="toggleOv('pub-ov-<?php echo $cidx; ?>',this)">Read more &#x25BE;</button>
                                        <?php else: ?><?php echo _renderOverviewHtml($cOverview); ?><?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if($hasReqs): ?>
                                    <div id="pub-tab-<?php echo $cidx; ?>-requirements" class="edu-course-tab-content"<?php echo $firstTab!=='requirements' ? ' style="display:none;"' : ''; ?>>
                                        <div class="pub-req-section">
                                            <?php if($cReqAcademic): ?><div class="pub-req-row"><div class="pub-req-label">Academic Requirement</div><div class="pub-req-value"><?php echo htmlspecialchars($cReqAcademic, ENT_QUOTES, 'UTF-8'); ?></div></div><?php endif; ?>
                                            <?php if($cReqIelts||$cReqPte||$cReqToefl||$cReqCambridge||$cReqDuolingo): ?>
                                            <div class="pub-req-row"><div class="pub-req-label">English Proficiency</div>
                                            <div class="pub-english-scores">
                                                <?php if($cReqIelts): ?><div class="pub-score-badge"><span class="pub-score-badge-name">IELTS</span><span class="pub-score-badge-val"><?php echo htmlspecialchars($cReqIelts, ENT_QUOTES, 'UTF-8'); ?></span></div><?php endif; ?>
                                                <?php if($cReqPte): ?><div class="pub-score-badge"><span class="pub-score-badge-name">PTE</span><span class="pub-score-badge-val"><?php echo htmlspecialchars($cReqPte, ENT_QUOTES, 'UTF-8'); ?></span></div><?php endif; ?>
                                                <?php if($cReqToefl): ?><div class="pub-score-badge"><span class="pub-score-badge-name">TOEFL iBT</span><span class="pub-score-badge-val"><?php echo htmlspecialchars($cReqToefl, ENT_QUOTES, 'UTF-8'); ?></span></div><?php endif; ?>
                                                <?php if($cReqCambridge): ?><div class="pub-score-badge"><span class="pub-score-badge-name">Cambridge</span><span class="pub-score-badge-val"><?php echo htmlspecialchars($cReqCambridge, ENT_QUOTES, 'UTF-8'); ?></span></div><?php endif; ?>
                                                <?php if($cReqDuolingo): ?><div class="pub-score-badge"><span class="pub-score-badge-name">Duolingo</span><span class="pub-score-badge-val"><?php echo htmlspecialchars($cReqDuolingo, ENT_QUOTES, 'UTF-8'); ?></span></div><?php endif; ?>
                                            </div></div>
                                            <?php endif; ?>
                                            <?php if($cReqDocs): ?><div class="pub-req-row"><div class="pub-req-label">Required Documents</div><div class="pub-req-value"><?php echo nl2br(htmlspecialchars($cReqDocs, ENT_QUOTES, 'UTF-8')); ?></div></div><?php endif; ?>
                                            <?php if($cReqNotes): ?><div class="pub-req-row"><div class="pub-req-label">Other Requirements</div><div class="pub-req-value"><?php echo nl2br(htmlspecialchars($cReqNotes, ENT_QUOTES, 'UTF-8')); ?></div></div><?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if($hasFees): ?>
                                    <div id="pub-tab-<?php echo $cidx; ?>-fees" class="edu-course-tab-content"<?php echo $firstTab!=='fees' ? ' style="display:none;"' : ''; ?>>
                                        <?php if($cFeeTuition): ?><div class="pub-tuition-highlight"><div class="pub-tuition-label">Annual Tuition (International)</div><div class="pub-tuition-value"><?php echo htmlspecialchars($cFeeTuition, ENT_QUOTES, 'UTF-8'); ?></div></div><?php endif; ?>
                                        <?php if($cFeeApp||$cFeeOshc||$cFeeLiving): ?>
                                        <div class="pub-fees-grid" style="margin-top:10px;">
                                            <?php if($cFeeApp): ?><div class="pub-fee-item"><div class="pub-fee-label">Application Fee</div><div class="pub-fee-value"><?php echo htmlspecialchars($cFeeApp, ENT_QUOTES, 'UTF-8'); ?></div></div><?php endif; ?>
                                            <?php if($cFeeOshc): ?><div class="pub-fee-item"><div class="pub-fee-label">OSHC / year</div><div class="pub-fee-value"><?php echo htmlspecialchars($cFeeOshc, ENT_QUOTES, 'UTF-8'); ?></div></div><?php endif; ?>
                                            <?php if($cFeeLiving): ?><div class="pub-fee-item" style="grid-column:1/-1;"><div class="pub-fee-label">Estimated Living Cost / year</div><div class="pub-fee-value"><?php echo htmlspecialchars($cFeeLiving, ENT_QUOTES, 'UTF-8'); ?></div></div><?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if($cFeeNotes): ?><div class="pub-req-row" style="margin-top:10px;"><div class="pub-req-label">Payment Notes</div><div class="pub-req-value"><?php echo nl2br(htmlspecialchars($cFeeNotes, ENT_QUOTES, 'UTF-8')); ?></div></div><?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if($cScholar): ?>
                                    <div id="pub-tab-<?php echo $cidx; ?>-scholarships" class="edu-course-tab-content"<?php echo $firstTab!=='scholarships' ? ' style="display:none;"' : ''; ?>>
                                        <div class="edu-profile-text"><?php echo nl2br(htmlspecialchars($cScholar, ENT_QUOTES, 'UTF-8')); ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- KEY DATES TAB -->
                    <?php if ($_apb_show_keydates): ?>
                    <div id="apb-pane-keydates" class="apb-pane<?php echo $_apb_first_tab === 'keydates' ? '' : ' apb-pane-hidden'; ?>">
                        <div class="apb-content-card">
                            <?php echo _renderKeyDatesHtml($_ip_dates); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                </div><!-- /.apb-tab-content -->

                <?php else: ?>
                <div class="apb-empty-state"><i class="fa fa-info-circle"></i> No profile data available yet.</div>
                <?php endif; ?>

            </div><!-- /.apb-profile-page -->

            <script>
            function toggleOv(bodyId, btn) {
                var body = document.getElementById(bodyId);
                if (!body) return;
                var expanded = body.classList.toggle('expanded');
                btn.innerHTML = expanded ? 'Show less &#x25B4;' : 'Read more &#x25BE;';
            }
            function apbSwitchTab(tabKey) {
                var tabs = ['overview','courses','keydates'];
                tabs.forEach(function(t) {
                    var pane = document.getElementById('apb-pane-' + t);
                    if (pane) pane.classList.toggle('apb-pane-hidden', t !== tabKey);
                    var btn = document.querySelector('.apb-tab-btn[onclick*="\'' + t + '\'"]');
                    if (btn) btn.classList.toggle('active', t === tabKey);
                });
            }
            function apbToggleProgram(idx) {
                var det = document.getElementById('apb-prog-det-' + idx);
                var tog = document.getElementById('apb-prog-tog-' + idx);
                if (!det) return;
                var open = det.style.display !== 'none';
                det.style.display = open ? 'none' : 'block';
                if (tog) {
                    var arrow = tog.querySelector('.apb-tog-arrow');
                    if (arrow) arrow.innerHTML = open ? '&#x25BC;' : '&#x25B2;';
                    tog.classList.toggle('active', !open);
                }
            }
            function apbFilterCourses(btn, level) {
                document.querySelectorAll('.apb-filter-pill').forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');
                document.querySelectorAll('.apb-program-card').forEach(function(card) {
                    card.style.display = (level === 'All' || card.dataset.level === level) ? '' : 'none';
                });
            }
            function switchPubTab(cidx, tabKey) {
                var tabs = ['overview','requirements','fees','scholarships'];
                tabs.forEach(function(t) {
                    var content = document.getElementById('pub-tab-' + cidx + '-' + t);
                    var btn = document.querySelector('#apb-prog-nav-' + cidx + ' .edu-course-tab-btn[onclick*="\'' + t + '\'"]');
                    if (content) content.style.display = (t === tabKey) ? 'block' : 'none';
                    if (btn) btn.classList.toggle('active', t === tabKey);
                });
            }
            (function() {
                var nav = document.getElementById('apb-tab-nav');
                if (!nav) return;
                var origTop = nav.getBoundingClientRect().top + window.pageYOffset;
                window.addEventListener('scroll', function() {
                    nav.classList.toggle('apb-nav-sticky', window.pageYOffset > origTop - 56);
                }, { passive: true });
            })();

            (function() {
                var slider = document.getElementById('apb-gallery-slider');
                if (!slider) return;

                var prevBtn = document.getElementById('apb-gallery-prev');
                var nextBtn = document.getElementById('apb-gallery-next');
                var countEl = document.getElementById('apb-gallery-count');
                var total = slider.querySelectorAll('.apb-gallery-slide').length;

                function currentIndex() {
                    if (!slider.clientWidth) return 0;
                    return Math.round(slider.scrollLeft / slider.clientWidth);
                }

                function updateCounter() {
                    if (!countEl) return;
                    countEl.textContent = (currentIndex() + 1) + ' / ' + total;
                }

                function slideBy(dir) {
                    slider.scrollBy({ left: dir * slider.clientWidth, behavior: 'smooth' });
                }

                if (prevBtn) prevBtn.addEventListener('click', function() { slideBy(-1); });
                if (nextBtn) nextBtn.addEventListener('click', function() { slideBy(1); });

                slider.addEventListener('scroll', function() {
                    window.requestAnimationFrame(updateCounter);
                }, { passive: true });

                window.addEventListener('resize', updateCounter);
                updateCounter();
            })();
            </script>
            <div class="edu-about-posts">
                <div class="edu-about-posts-title">Posts</div>
                <div class="article-list" data-mid="<?php echo $_profile_posts_mid; ?>"></div>
            </div>

            <?php } else { ?>
            <div class="group-title"><u><?php echo $_page_lang['account.company_info']; ?></u></div>

            <div class="row">
                <label for="logo"><?php echo $_page_lang['account.logo']; ?></label>
                <div class="logo-file">
                    <img src="asset/image/default_logo.jpg" alt="default_logo">
                    <div class="preview"><!--
                        <?php if(!empty($_show_current_member_details['logo'])) { ?>
                        --><img src="<?php echo 'upload/member_logo/'.$_show_current_member_details['logo']; ?>"><!--
                        <?php } ?>
                    --></div>
                </div>
            </div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="company_name"><?php echo ((int)$_show_current_member['type'] == 2)?$_page_lang['account.company_name']:$_page_lang['account.company_name_2']; ?></label>
                <div class="input-value"><?php echo $_show_current_member_details['company_name']; ?></div>
            </div>
            <div class="clearboth"></div>

            <?php if((int)$_show_current_member['type'] == 3) { ?>
            <div class="row">
                <label for="company_type"><?php echo $_page_lang['account.company_type']; ?></label>
                <div class="input-value">
                    <?php echo (!empty($_page_options['organization_type'][$_show_current_member_details['company_type']]))?$_page_options['organization_type'][$_show_current_member_details['company_type']]:''; ?>
                </div>
            </div>
            <div class="clearboth"></div>
            <?php } ?>

            <div class="row">
                <label for="company_address"><?php echo $_page_lang['account.company_address']; ?></label>
                <div class="input-value"><?php echo nl2br($_show_current_member_details['company_address']); ?></div>
            </div>
            <div class="clearboth"></div>

            <?php if((int)$_show_current_member['type'] == 2) { ?>
            <div class="child-agent">
                <div class="row">
                    <label for="registered_agent"><?php echo $_page_lang['account.has_registered_agent']; ?></label>
                    <div class="iweb-radiobox-set"  data-showtips="false">
                        <input type="radio" id="registered_agent_yes" name="registered_agent" value="1" <?php echo ((int)$_show_current_member_details['registered_agent']==1)?' checked':'';?> disabled>
                        <label for="registered_agent_yes"><?php echo $_page_lang['account.yes']; ?></label>

                        <input type="radio" id="registered_agent_no" name="registered_agent" value="0" <?php echo ((int)$_show_current_member_details['registered_agent']==0)?' checked':'';?> disabled>
                        <label for="registered_agent_no"><?php echo $_page_lang['account.no']; ?></label>
                    </div>
                </div>
                <div class="clearboth"></div>

                <div class="list<?php echo ((int)$_show_current_member_details['registered_agent']==0)?' disabled':'';?>">
                    <div class="items">
                        <?php if(!empty($_show_current_member_agent)) { foreach ($_show_current_member_agent as $agent_key => $agent) { ?>
                        <div class="block">
                            <div class="num"><?php echo $_page_lang['account.registered_agent']; ?><span> - <?php echo $agent_key+1;?></span></div>

                            <div class="row left">
                                <label for="first_name"><?php echo $_page_lang['account.first_name']; ?></label>
                                <div class="input-value"><?php echo $agent['first_name']; ?></div>
                            </div>
                            <div class="row right">
                                <label for="last_name"><?php echo $_page_lang['account.last_name']; ?></label>
                                <div class="input-value"><?php echo $agent['last_name']; ?></div>
                            </div>
                            <div class="clearboth"></div>

                            <div class="row left">
                                <label for="registration_country"><?php echo $_page_lang['account.registration_country']; ?></label>
                                <div class="input-value">
                                    <?php echo (!empty($_page_options['countries'][$agent['registration_country']]))?$_page_options['countries'][$agent['registration_country']]:''; ?>
                                </div>
                            </div>
                            <div class="row right">
                                <label for="registration_num"><?php echo $_page_lang['account.registration_num']; ?></label>
                                <div class="input-value"><?php echo $agent['registration_num']; ?></div>
                            </div>
                            <div class="clearboth"></div>
                        </div>
                        <?php }} ?>
                    </div>
                </div>
            </div>

            <div class="child-lawfirm">
                <div class="row">
                    <label for="registered_lawfirm"><?php echo $_page_lang['account.has_registered_lawfirm']; ?></label>
                    <div class="iweb-radiobox-set"  data-showtips="false">
                        <input type="radio" id="registered_lawfirm_yes" name="registered_lawfirm" value="1" <?php echo ((int)$_show_current_member_details['registered_lawfirm']==1)?' checked':'';?> disabled>
                        <label for="registered_lawfirm_yes"><?php echo $_page_lang['account.yes']; ?></label>

                        <input type="radio" id="registered_lawfirm_no" name="registered_lawfirm" value="0" <?php echo ((int)$_show_current_member_details['registered_lawfirm']==0)?' checked':'';?> disabled>
                        <label for="registered_lawfirm_no"><?php echo $_page_lang['account.no']; ?></label>
                    </div>
                </div>
                <div class="clearboth"></div>

                <div class="list<?php echo ((int)$_show_current_member_details['registered_lawfirm']==0)?' disabled':'';?>">
                    <div class="items">
                        <?php if(!empty($_show_current_member_lawfirm)) { foreach ($_show_current_member_lawfirm as $lawfirm_key => $lawfirm) { ?>
                        <div class="block">
                            <div class="num"><?php echo $_page_lang['account.registered_lawfirm']; ?><span> - <?php echo $lawfirm_key+1; ?></span></div>

                            <div class="row">
                                <label for="name"><?php echo $_page_lang['account.name']; ?></label>
                                <div class="input-value"><?php echo $lawfirm['full_name']; ?></div>
                            </div>
                            <div class="clearboth"></div>

                            <div class="row left">
                                <label for="registration_country"><?php echo $_page_lang['account.registration_country']; ?></label>
                                <div class="input-value">
                                    <?php echo (!empty($_page_options['countries'][$lawfirm['registration_country']]))?$_page_options['countries'][$lawfirm['registration_country']]:''; ?>
                                </div>
                            </div>
                            <div class="row right">
                                <label for="registration_num"><?php echo $_page_lang['account.registration_num']; ?></label>
                                <div class="input-value"><?php echo $lawfirm['registration_num']; ?></div>
                            </div>
                            <div class="clearboth"></div>
                        </div>
                        <?php }} ?>
                    </div>
                </div>
            </div>

            <?php } else { ?>
            <div class="row">
                <label for="services"><?php echo $_page_lang['account.services']; ?></label>
                <div class="input-value"><?php echo $_show_current_member_details['services']; ?></div>
            </div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="services_country"><?php echo $_page_lang['account.services_country']; ?></label>
                <div class="input-value">
                    <?php
                    $services_countries = [];
                    if(!empty($_show_current_member_details['services_country'])) {
                        $decoded = json_decode($_show_current_member_details['services_country'], true);
                        if(is_array($decoded)) {
                            foreach($decoded as $country_code) {
                                if(!empty($_page_options['countries'][$country_code])) {
                                    $services_countries[] = $_page_options['countries'][$country_code];
                                }
                            }
                        }
                    }
                    echo !empty($services_countries) ? implode(', ', $services_countries) : '';
                    ?>
                </div>
            </div>
            <div class="clearboth"></div>

            <div>&nbsp;</div>

            <div class="group-title"><u><?php echo $_page_lang['account.business_registration']; ?></u></div>

            <div class="row">
                <label for="registered_business_country"><?php echo $_page_lang['account.business_registration_country']; ?></label>
                <div class="input-value">
                    <?php echo (!empty($_page_options['countries'][$_show_current_member_details['registered_business_country']]))?$_page_options['countries'][$_show_current_member_details['registered_business_country']]:''; ?>
                </div>
            </div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="registered_business_name"><?php echo $_page_lang['account.business_registration_name']; ?></label>
                <div class="input-value"><?php echo $_show_current_member_details['registered_business_name']; ?></div>
            </div>
            <div class="clearboth"></div>

            <div>&nbsp;</div>

            <div class="group-title"><u><?php echo $_page_lang['account.business_license']; ?></u> <span style="font-size: 0.8em; font-weight: normal;">(<?php echo $_page_lang['if_applicable']; ?>)</span></div>

            <div class="child-business-license">
                <div class="items">
                    <?php if(!empty($_show_current_member_business_license)) { foreach ($_show_current_member_business_license as $license_key => $license) { ?>
                    <div class="block">
                        <div class="num"><?php echo $_page_lang['account.business_license']; ?><span> - <?php echo $license_key+1;?></span></div>

                        <div class="row left">
                            <label for="license_country"><?php echo $_page_lang['account.business_license_country']; ?></label>
                            <div class="input-value">
                                <?php echo (!empty($_page_options['countries'][$license['license_country']]))?$_page_options['countries'][$license['license_country']]:''; ?>
                            </div>
                        </div>
                        <div class="row right">
                            <label for="issuing_authority"><?php echo $_page_lang['account.issuing_authority']; ?></label>
                            <div class="input-value"><?php echo $license['issuing_authority']; ?></div>
                        </div>
                        <div class="clearboth"></div>
                    </div>
                    <?php }} ?>
                </div>
            </div>
            <?php } ?>

            <div>&nbsp;</div>
            <?php } ?>
            <?php } // end else (migration SP, not education institution) ?>

            <div class="group-title"><u><?php echo $_page_lang['account.contact_info']; ?></u></div>

            <div class="row left">
                <label for="first_name"><?php echo $_page_lang['account.first_name']; ?></label>
                <div class="input-value"><?php echo $_show_current_member['first_name']; ?></div>
             </div>
            <div class="row right">
                <label for="last_name"><?php echo $_page_lang['account.last_name']; ?></label>
                <div class="input-value"><?php echo $_show_current_member['last_name']; ?></div>
            </div>
            <div class="clearboth"></div>

            <?php if((int)$_show_current_member['type'] == 1): ?>
            <div class="row">
                <label for="email"><?php echo $_page_lang['account.email']; ?></label>
                <div class="input-value"><?php echo $_show_current_member['email']; ?></div>
            </div>
            <div class="clearboth"></div>
            <?php endif; ?>


            <?php if(in_array((int)$_show_current_member['type'], [1])) { ?>
            <div>&nbsp;</div>

            <div class="group-title"><u><?php echo $_page_lang['choose_your_preference']; ?></u></div>

            <div class="row">
                <label for="migration_destination"><?php echo $_page_lang['account.migration_destination']; ?></label>
                <div class="input-value">
                    <?php echo (!empty($_page_options['countries'][$_show_current_member['migration_destination']]))?$_page_options['countries'][$_show_current_member['migration_destination']]:''; ?>
                </div>
            </div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="interested_visa"><?php echo $_page_lang['account.interested_visa']; ?></label>
                <div class="input-value">
                    <?php echo (!empty($_page_options['interest_visas'][$_show_current_member['interested_visa']]))?$_page_options['interest_visas'][$_show_current_member['interested_visa']]:''; ?>
                </div>
            </div>
            <div class="clearboth"></div>

            <div class="row">
                <label for="interested_topic"><?php echo $_page_lang['account.interested_topic']; ?></label>
                <div class="iweb-checkbox-set">
                    <?php if(!empty($_page_options['interest_topics'])) { foreach ($_page_options['interest_topics'] as $interest_topics_id => $interest_topics) { ?>
                    <input type="checkbox" id="interested_topic_<?php echo $interest_topics_id; ?>" name="interested_topic[]" value="<?php echo $interest_topics_id; ?>"<?php echo (is_array($_show_current_member['interested_topic']) && in_array($interest_topics_id, $_show_current_member['interested_topic']))?' checked':'';?> disabled>
                    <label for="interested_topic_<?php echo $interest_topics_id; ?>"><?php echo $interest_topics; ?></label>
                    <?php }} ?>
                </div>
            </div>
            <div class="clearboth"></div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>

    <?php
    // Education institution counters & profile link
    $_institution_profile = !empty($_page_data['institution_profile']) ? $_page_data['institution_profile'] : null;
    if (!empty($_institution_profile)) {
    ?>
    <div class="edu-institution-panel">
        <div class="edu-panel-header">
            <span class="edu-panel-title">Education Institution</span>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <?php if (!empty($_institution_profile['id'])): ?>
                <a href="<?php echo $_page_base_url.'/institution_hub_profile/pub_view/'.(int)$_institution_profile['id']; ?>" class="edu-panel-link" target="_blank" style="background:#1a73e8;color:#fff;border-color:#1a73e8;">&#128065; View Public Profile</a>
                <?php endif; ?>
                <?php if(empty($_page_data['is_readonly'])) { ?>
                <a href="<?php echo $_page_base_url.'/institution_hub_profile'; ?>" class="edu-panel-link">Manage Profile</a>
                <?php } ?>
            </div>
        </div>
        <div class="edu-counters-row">
            <div class="edu-counter-card">
                <div class="edu-counter-num"><?php echo (int)($_institution_profile['students_matched'] ?? 0); ?></div>
                <div class="edu-counter-label">Students Matched</div>
            </div>
            <div class="edu-counter-card">
                <div class="edu-counter-num"><?php echo (int)($_institution_profile['students_applied'] ?? 0); ?></div>
                <div class="edu-counter-label">Students Applied</div>
            </div>
            <div class="edu-counter-card">
                <div class="edu-counter-num"><?php echo (int)($_institution_profile['students_accepted'] ?? 0); ?></div>
                <div class="edu-counter-label">Students Accepted</div>
            </div>
        </div>
    </div>
    <style>
    .edu-institution-panel {
        background: #f7f9fc;
        border: 1.5px solid #dde3ed;
        border-radius: 10px;
        padding: 22px 24px;
        margin: 20px 0 10px 0;
    }
    .edu-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    .edu-panel-title {
        font-weight: 700;
        font-size: 0.95em;
        color: #1a1a2e;
    }
    .edu-panel-link {
        font-size: 0.85em;
        color: #1a73e8;
        text-decoration: none;
        border: 1.5px solid #1a73e8;
        padding: 5px 12px;
        border-radius: 6px;
        transition: background 0.2s;
    }
    .edu-panel-link:hover { background: #f0f6ff; }
    .edu-counters-row {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
    }
    .edu-counter-card {
        flex: 1;
        min-width: 120px;
        background: #fff;
        border: 1.5px solid #e4e8f0;
        border-radius: 8px;
        padding: 16px 12px;
        text-align: center;
    }
    .edu-counter-num {
        font-size: 1.8em;
        font-weight: 800;
        color: #1a73e8;
    }
    .edu-counter-label {
        font-size: 0.78em;
        color: #666;
        margin-top: 4px;
    }
    /* Education institution public profile view */
    .edu-profile-view {
        margin: 20px 0 0 0;
    }
    .edu-profile-header {
        display: flex;
        align-items: center;
        gap: 18px;
        margin-bottom: 20px;
        padding: 18px;
        background: #f7f9fc;
        border-radius: 10px;
        border: 1.5px solid #e4e8f0;
    }
    .edu-profile-logo {
        width: 72px;
        height: 72px;
        object-fit: contain;
        border-radius: 8px;
        background: #fff;
        padding: 4px;
        border: 1px solid #e4e8f0;
        flex-shrink: 0;
    }
    .edu-profile-header-text { flex: 1; min-width: 0; }
    .edu-profile-inst-name {
        font-size: 1.25em;
        font-weight: 700;
        color: #1a1a2e;
        margin: 0 0 6px 0;
        word-break: break-word;
    }
    .edu-profile-website {
        font-size: 0.88em;
        color: #1a73e8;
        text-decoration: none;
        word-break: break-all;
    }
    .edu-profile-website:hover { text-decoration: underline; }
    .edu-profile-section {
        margin-bottom: 20px;
        padding: 16px 18px;
        background: #fff;
        border: 1.5px solid #e4e8f0;
        border-radius: 10px;
    }
    .edu-profile-section-title {
        font-weight: 700;
        font-size: 0.88em;
        color: #1a73e8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 10px;
    }
    .edu-profile-text {
        color: #333;
        font-size: 0.95em;
        line-height: 1.7;
    }
    .edu-profile-pretext {
        color: #333;
        font-size: 0.9em;
        line-height: 1.7;
        white-space: pre-line;
    }
    .edu-about-posts {
        margin-top: 28px;
    }
    .edu-about-posts-title {
        font-weight: 700;
        font-size: 1em;
        color: #1a1a2e;
        margin-bottom: 14px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e4e8f0;
    }
    </style>
    <?php } ?>

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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-fill business registration country when country of operation changes
    const countrySelect = document.getElementById('country');
    const registeredBusinessCountrySelect = document.getElementById('registered_business_country');

    if (countrySelect && registeredBusinessCountrySelect) {
        countrySelect.addEventListener('change', function() {
            const selectedCountry = this.value;
            if (selectedCountry) {
                registeredBusinessCountrySelect.value = selectedCountry;
            }
        });
    }

    // Handle "All Countries" option using jQuery
    if (jQuery && jQuery('#services_country').length) {
        const $select = jQuery('#services_country');

        $select.on('change', function() {
            const $this = jQuery(this);
            const allCountryValues = [];

            // Get all country values (not empty, not "all")
            $this.find('option').each(function() {
                const val = jQuery(this).val();
                if (val !== '' && val !== 'all') {
                    allCountryValues.push(val);
                }
            });

            // Get currently selected values
            const selected = $this.val() || [];

            // Check if "all" was clicked
            const allIsSelected = selected.includes('all');

            if (allIsSelected) {
                // Check if all countries were already selected
                const allCountriesWereSelected = allCountryValues.length > 0 && allCountryValues.every(val => selected.includes(val));

                if (allCountriesWereSelected) {
                    // If all were already selected, deselect all
                    console.log('All clicked when all selected - deselecting all');
                    $this.find('option').prop('selected', false);
                } else {
                    // Otherwise select all individual countries
                    console.log('All clicked - selecting all countries');
                    $this.find('option').prop('selected', function() {
                        const val = jQuery(this).val();
                        return val !== '' && val !== 'all';
                    });
                }
            } else {
                // Check if all countries are selected
                const allCountriesSelected = allCountryValues.length > 0 && allCountryValues.every(val => selected.includes(val));

                if (allCountriesSelected) {
                    // Automatically check "all"
                    console.log('All countries selected - checking all option');
                    $this.find('option[value="all"]').prop('selected', true);
                }
            }
        });

        // Before form submission, remove "all" from the selected values
        jQuery('#account-profile-form').on('submit', function() {
            $select.find('option[value="all"]').prop('selected', false);
        });
    }

});
</script>

<script>
(function() {
    function loadProfilePostsFallback() {
        if (typeof window.jQuery === 'undefined' || typeof window._page_base_url === 'undefined') {
            return;
        }

        jQuery('div.article-list[data-mid]').each(function() {
            var container = jQuery(this);
            if (container.data('loading') || container.data('done')) {
                return;
            }
            if (jQuery.trim(container.html()) !== '') {
                return;
            }

            var mid = parseInt(container.data('mid') || 0, 10);
            if (!mid) {
                container.data('done', true);
                return;
            }

            var sector = container.data('sector') || '';
            var page = parseInt(container.data('page') || 1, 10);
            var url = window._page_base_url + '/account_article?mid=' + mid + '&page=' + page;
            if (sector) {
                url += '&sector=' + encodeURIComponent(sector);
            }

            container.data('loading', true);
            jQuery.get(url)
                .done(function(html) {
                    if (jQuery.trim(String(html || '')) !== '') {
                        container.append(html);
                        container.data('page', page + 1);
                        if (typeof window.iweb !== 'undefined' && typeof window.iweb.responsive === 'function') {
                            window.iweb.responsive();
                        }
                    } else {
                        container.data('done', true);
                        // Hide the entire Posts section if nothing was loaded on the first page
                        if (page === 1) {
                            var postsSection = container.closest('.edu-about-posts');
                            if (postsSection.length) { postsSection.hide(); }
                        }
                    }
                })
                .always(function() {
                    container.data('loading', false);
                });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadProfilePostsFallback);
    } else {
        loadProfilePostsFallback();
    }

    setTimeout(loadProfilePostsFallback, 600);
})();
</script>

@endsection
