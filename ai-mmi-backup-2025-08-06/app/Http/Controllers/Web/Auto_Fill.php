<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Auto_Fill extends WebController {
    
    public function __construct($data) {
        parent::__construct($data);
    }
    
    public function index() {
        // post
        $this->pageAction(function() {
            $myanswers = $this->getSession('myanswers');
            if(empty($myanswers)) {
                $myanswers = [];
            }
            
            $jump_to_next = true;
            if($jump_to_next) {
                $fid = $this->getParamValue('fid', 0);
                
                $myanswers[] = $this->postParamValue('inquiry');
                $next = $this->default_questions($fid, (count($myanswers)+1));

                $this->setSession(['myanswers' => $myanswers]);
                
                if(!empty($this->_current_member)) {
                    $member_owner_name = $this->_current_member['alias_name'];
                    $member_owner_avatar = 'asset/image/icon-member.png';
                    if(!empty($this->_current_member['avatar'])) {
                        if(file_exists('upload/member_avatar/'.$this->_current_member['avatar'])) {
                            $member_owner_avatar = 'upload/member_avatar/'.$this->_current_member['avatar'];
                        }
                        else {
                            $member_owner_avatar = 'upload/member_logo/'.$this->_current_member['avatar'];
                        }
                    }
                }
                else {
                    $member_owner_name = 'Guest';
                    $member_owner_avatar = 'asset/image/icon-member.png';
                }
                $ai_owner_name = 'AI-mmi';
                $ai_owner_avatar = 'asset/image/logo-mmi.png';
                
                if(!empty($next)) {
                    $this->pageResult([
                        'status'    =>  200,
                        'message'   =>  nl2br($this->postParamValue('inquiry')),
                        'next'      =>  $next,
                        'member_owner_name' => $member_owner_name,
                        'member_owner_avatar' => $member_owner_avatar,
                        'ai_owner_name' => $ai_owner_name,
                        'ai_owner_avatar' => $ai_owner_avatar,
                    ]);
                }
                else {
                    $auto_fill_token = md5(uniqid(rand()));
                    $new_link = $this->toURL(['auto_fill',(($fid==1)?'web':'pdf')]).'?token='.$auto_fill_token;
                    $this->pageResult([
                        'status'    =>  200,
                        'message'   =>  nl2br($this->postParamValue('inquiry')),
                        'done'      => str_replace('{link}', $new_link, $this->_page_lang['auto_fill_link']),
                        'member_owner_name' => $member_owner_name,
                        'member_owner_avatar' => $member_owner_avatar,
                        'ai_owner_name' => $ai_owner_name,
                        'ai_owner_avatar' => $ai_owner_avatar,
                    ]);
                }
            }
            else {
                $this->pageResult([
                    'status'    =>  404,
                    'message'   =>  $this->_page_lang['auto_fill_please_select']
                ]);
            }
        });
        
        $this->delSession('myanswers');
        $page_data = ['title' => $this->_page_lang['auto_fill'], 'content' => ''];
        $page_data['content'].= ('<p>'.$this->_page_lang['auto_fill_content'].'</p>');
        $page_data['content'].= ('<p>&nbsp;</p>');
        $page_data['content'].= ('<ul>');
        $page_data['content'].= ('<li><p><a href="'.($this->toURL('auto_fill').'?fid=1').'">'.$this->_page_lang['auto_fill_form_1'].'</a></p></li>');
        $page_data['content'].= ('<li><p><a href="'.($this->toURL('auto_fill').'?fid=2').'">'.$this->_page_lang['auto_fill_form_2'].'</a></p></li>');
        $page_data['content'].= ('</ul>');


        $fid = $this->getParamValue('fid', 0);
        // set meta
        $this->pageMeta(
        [
            'title'         =>  (!empty($page_data['meta_title']))?$page_data['meta_title']:$page_data['title'],
        ]);
        
        return $this->pageData(
        [
            'current_fid'   =>  $fid,
            'questions'     =>  (!empty($fid))?$this->default_questions($fid):false,
            'details'       =>  $page_data
        ])->pageView();
    }
    
    public function web() {
        // post
        $this->pageAction(function() {
            $type_options = [
                '0' => '',
                '4' => 'Electronic Travel Authorization',
                '8' => 'Technical difficulties',
                '9' => 'Change of contact information',
                '40' => 'Use a representatives or release personal information',
                '11' => 'Withdrawal of application',
                '12' => 'Replacement documents, Amendments to documents and Verification of Status documents',
                '41' => 'Citizenship',
                '68' => 'Certificate of Identity/Refugee Travel Document',
                '42' => 'Permanent Resident Card',
                '43' => 'Sponsorship',
                '44' => 'Temporary Residence (applied online) ',
                '45' => 'Temporary Residence (applied by mail)',
                '67' => 'International Experience Canada',
                '46' => 'In-Canada Permanent Residence',
                '5' => 'Permanent Residence (applied online)',
                '47' => 'Permanent Residence (applied by mail)',
                '19' => 'Request urgent processing of renewal or replacement card and have already applied',
            ];
            
            $relationship_options = [
                0   =>  '',
                1   =>  'Applicant',
                2   =>  'Representative',
                3   =>  'Sponsor'
            ];
            
            $subject = $this->_today_datetime.' - IRCC Webform';
            $body = '';
            
            //$body.= '<p><strong>Type Of Application/Enquiry:</strong><br/>'.$type_options[$this->postParamValue('type_of_application', 0)].'</p>';
            $body.= '<p><strong>Type Of Application/Enquiry:</strong><br/>'.$this->postParamValue('type_of_application', '').'</p>';
            
            $body.= '<hr/>';
            $body.= '<p><strong><u>Applicant Information</u></strong></p>';
            $body.= '<p><strong>Family name:</strong><br/>'.$this->postParamValue('applicant_family_name').'</p>';
            $body.= '<p><strong>Given name:</strong><br/>'.$this->postParamValue('applicant_given_name').'</p>';
            $body.= '<p><strong>Email:</strong><br/>'.$this->postParamValue('applicant_email').'</p>';
            $body.= '<p><strong>Date of birth:</strong><br/>'.$this->postParamValue('applicant_birth_date').'</p>';
            $body.= '<p><strong>Country of birth:</strong><br/>'.$this->postParamValue('applicant_birth_country').'</p>';
            $body.= '<p><strong>Client ID number (UCI):</strong><br/>'.$this->postParamValue('applicant_client_id_number').'</p>';
            $body.= '<p><strong>Application number:</strong><br/>'.$this->postParamValue('applicant_application_number').'</p>';
            $body.= '<p><strong>Telephone number:</strong><br/>'.$this->postParamValue('applicant_telephone_number').'</p>';
            $body.= '<p><strong>Mobile number:</strong><br/>'.$this->postParamValue('applicant_mobile_number').'</p>';
            
            
            $body.= '<hr/>';
            $body.= '<p><strong><u>Enquirer Information</u></strong></p>';
            $body.= '<p><strong>Relationship to applicant:</strong><br/>'.$relationship_options[$this->postParamValue('relationship_to_applicant', 0)].'</p>';
            $body.= '<p><strong>Family name:</strong><br/>'.$this->postParamValue('enquirer_family_name').'</p>';
            $body.= '<p><strong>Given name:</strong><br/>'.$this->postParamValue('enquirer_given_name').'</p>';
            $body.= '<p><strong>Email:</strong><br/>'.$this->postParamValue('enquirer_email').'</p>';
            $body.= '<p><strong>Telephone number:</strong><br/>'.$this->postParamValue('enquirer_telephone_number').'</p>';
            $body.= '<p><strong>Mobile number:</strong><br/>'.$this->postParamValue('enquirer_mobile_number').'</p>';
            $body.= '<p><strong>Consultant ICCRC number:</strong><br/>'.$this->postParamValue('enquirer_consultant_iccrc_number').'</p>';
            
            $recipient_autofill = $this->_setting_model->getByName('recipient_autofill');
            $recipient_autofill = explode(PHP_EOL, str_ireplace(';', PHP_EOL, $recipient_autofill));
            $this->sendEmail($recipient_autofill, $subject, $body);
            $this->pageResult([
                'status'    =>  200,
                'message'   =>  $this->_page_lang['auto_fill_thanks'],
                'url'       =>  $this->toURL('auto_fill')
            ]);
        });
        
        return $this->pageData(
        [
            'details'   =>  $this->getSession('myanswers')
        ])->pageView();
    }
    
    public function pdf() {
        $myanswers = $this->getSession('myanswers');

        $html = '<div><img src="asset/image/pdf/ircc-logo-txt.png" width="340"></div>';
        $html.= '<div><h3 style="margin:0px;padding:20px 0px 10px 64px;">SUPPLEMENTARY INFORMATION<br/>YOUR TRAVELS</h3></div>';
        $html.= '<div style="font-size:12px;"><strong>The principal applicant must complete this form.</strong></div>';
        $html.= '<div style="font-size:12px;margin-top:10px;"><strong>If you need more space for any section, please add lines to the form by pressing the+ button. If you apply on paper, print out an additional page containing the appropriate section, complete it and submit it along with your application. Print your name and the form\'s title on the additional sheet.</strong></div>';

        $html.= '<table style="width:100%;margin-top:10px">';
            $html.= '<tr>';
                $html.= '<td style="width:420px">';
                    $html.= '<div style="font-size:12px;"><strong>1 - Your full name</strong></div>';
                    $html.= '<table style="width:100%;margin-top:5px;">';
                        $html.= '<tr><td class="border padding" style="height:70px;">Family name (as shown on passport/travel document)<br/><div style="font-size:20px;">'.((!empty($myanswers[0]))?$myanswers[0]:'').'</div><td></tr>';
                        $html.= '<tr><td><td></tr>';
                        $html.= '<tr><td class="border padding" style="height:70px;">Given name(s) (as shown on passport/travel document)<br/><div style="font-size:20px;">'.((!empty($myanswers[1]))?$myanswers[1]:'').'</div><td></tr>';
                    $html.= '</table>';
                $html.= '</td>';
                $html.= '<td style="width:20px;"></td>';
                $html.= '<td>';
                    $html.= '<table style="width:100%;margin-top:5px;">';
                        $html.= '<tr><td class="border padding center" style="font-size:14px;height:40px;">FOR OFFICE USE ONLY<td></tr>';
                        $html.= '<tr><td class="border padding" style="height:120px;"><td></tr>';
                    $html.= '</table>';
                $html.= '</td>';
            $html.= '</tr>';
        $html.= '</table>';
        
        $html.= '<div style="font-size:12px;margin-top:10px;">2 - List all trips you and your family members aged 18 or over (if applicable) have taken outside your country of origin or of residence in the last ten years (or since your 18th birthday if this was less than ten years ago). Include all trips: tourism, business, training, etc. If you or your family member did not travel outside of your country of origin or of residence during this period, check "did not travel". For example:</div>';
        $html.= '<table style="width:100%;margin-top:15px">';
            $html.= '<tr>';
                $html.= '<td class="border padding center" width="12%"><strong>From</strong><br/>YYYY-MM</td>';
                $html.= '<td class="border padding center" width="12%"><strong>To</strong><br/>YYYY-MM</td>';
                $html.= '<td class="border padding center" width="12%"><strong>Duration</td>';
                $html.= '<td class="border padding center"><strong>Destination</strong><br/>(City and country)</td>';
                $html.= '<td class="border padding center"><strong>Purpose of travel</strong></td>';
                $html.= '<td class="border padding center"><strong>Provide details</strong><br/>(if applicable)</td>';
            $html.= '</tr>';
            $html.= '<tr>';
                $html.= '<td class="border padding center">2020-04</td>';
                $html.= '<td class="border padding center">2020-04</td>';
                $html.= '<td class="border padding center">6 days</td>';
                $html.= '<td class="border padding center">Madrid, Spain</td>';
                $html.= '<td class="border padding center">Tourism</td>';
                $html.= '<td class="border padding center">Guided tour, Sightseeing</td>';
            $html.= '</tr>';
        $html.= '</table>';
        
        
        $html.= '<table style="width:100%;margin-top:5px">';
            $html.= '<tr>';
                $html.= '<td class="padding" colspan="5" style="padding-left:0px;vertical-align:middle;"><strong>a) You</strong></td>';
                $html.= '<td class="padding center"><table><tr><td class="center"><span style="font-size:20px;">&#9744;</span></td><td>&nbsp;</td><td class="center"><strong>did not travel</strong></td></tr></table></td>';
            $html.= '</tr>';
            $html.= '<tr>';
                $html.= '<td class="border padding center" width="12%"><strong>From</strong><br/>YYYY-MM</td>';
                $html.= '<td class="border padding center" width="12%"><strong>To</strong><br/>YYYY-MM</td>';
                $html.= '<td class="border padding center" width="12%"><strong>Duration</td>';
                $html.= '<td class="border padding center"><strong>Destination</strong><br/>(City and country)</td>';
                $html.= '<td class="border padding center"><strong>Purpose of travel</strong></td>';
                $html.= '<td class="border padding center"><strong>Provide details</strong><br/>(if applicable)</td>';
            $html.= '</tr>';
            
            $html.= '<tr>';
                $html.= '<td class="border padding center">'.((!empty($myanswers[2]))?$myanswers[2]:'').'</td>';
                $html.= '<td class="border padding center">'.((!empty($myanswers[3]))?$myanswers[3]:'').'</td>';
                $html.= '<td class="border padding center">'.((!empty($myanswers[4]))?($myanswers[4].' days'):'').'</td>';
                $html.= '<td class="border padding center">'.((!empty($myanswers[5]))?$myanswers[5]:'').'</td>';
                $html.= '<td class="border padding center">'.((!empty($myanswers[6]))?$myanswers[6]:'').'</td>';
                $html.= '<td class="border padding center">&nbsp;</td>';
            $html.= '</tr>';
            
            for($k=1;$k<=3;$k++) {
            $html.= '<tr>';
                $html.= '<td class="border padding center">&nbsp;</td>';
                $html.= '<td class="border padding center">&nbsp;</td>';
                $html.= '<td class="border padding center">&nbsp;</td>';
                $html.= '<td class="border padding center">&nbsp;</td>';
                $html.= '<td class="border padding center">&nbsp;</td>';
                $html.= '<td class="border padding center">&nbsp;</td>';
            $html.= '</tr>';
            }
        $html.= '</table>';
        
        $html.= '<table style="width:100%;margin-top:15px">';
            $html.= '<tr>';
                $html.= '<td class="padding" colspan="5" style="padding-left:0px;vertical-align:middle;"><strong>b) Your spouse or common-law partner (if not a Canadian citizen or permanent resident of Canada).</strong>';
                    $html.= '<table style="width:100%;margin-top:5px;">';
                        $html.= '<tr><td class="border padding" style="height:60px;">Family name and given names (as shown on passport/travel document)<td></tr>';
                    $html.= '</table>';
                $html.= '</td>';
                $html.= '<td class="padding center"><table><tr><td class="center"><span style="font-size:20px;">&#9744;</span></td><td>&nbsp;</td><td class="center"><strong>did not travel</strong></td></tr></table></td>';
            $html.= '</tr>';
            $html.= '<tr>';
                $html.= '<td class="border padding center" width="12%"><strong>From</strong><br/>YYYY-MM</td>';
                $html.= '<td class="border padding center" width="12%"><strong>To</strong><br/>YYYY-MM</td>';
                $html.= '<td class="border padding center" width="12%"><strong>Duration</td>';
                $html.= '<td class="border padding center"><strong>Destination</strong><br/>(City and country)</td>';
                $html.= '<td class="border padding center"><strong>Purpose of travel</strong></td>';
                $html.= '<td class="border padding center"><strong>Provide details</strong><br/>(if applicable)</td>';
            $html.= '</tr>';
            
            $within_travel = strtolower((!empty($myanswers[7]))?$myanswers[7]:'');
            if($within_travel == 'yes' || $within_travel == '是') {
                $html.= '<tr>';
                    $html.= '<td class="border padding center">'.((!empty($myanswers[2]))?$myanswers[2]:'').'</td>';
                    $html.= '<td class="border padding center">'.((!empty($myanswers[3]))?$myanswers[3]:'').'</td>';
                    $html.= '<td class="border padding center">'.((!empty($myanswers[4]))?($myanswers[4].' days'):'').'</td>';
                    $html.= '<td class="border padding center">'.((!empty($myanswers[5]))?$myanswers[5]:'').'</td>';
                    $html.= '<td class="border padding center">'.((!empty($myanswers[6]))?$myanswers[6]:'').'</td>';
                    $html.= '<td class="border padding center">&nbsp;</td>';
                $html.= '</tr>';
            }
            else {
                $html.= '<tr>';
                    $html.= '<td class="border padding center">&nbsp;</td>';
                    $html.= '<td class="border padding center">&nbsp;</td>';
                    $html.= '<td class="border padding center">&nbsp;</td>';
                    $html.= '<td class="border padding center">&nbsp;</td>';
                    $html.= '<td class="border padding center">&nbsp;</td>';
                    $html.= '<td class="border padding center">&nbsp;</td>';
                $html.= '</tr>';
            }
            
            for($k=1;$k<=3;$k++) {
            $html.= '<tr>';
                $html.= '<td class="border padding center">&nbsp;</td>';
                $html.= '<td class="border padding center">&nbsp;</td>';
                $html.= '<td class="border padding center">&nbsp;</td>';
                $html.= '<td class="border padding center">&nbsp;</td>';
                $html.= '<td class="border padding center">&nbsp;</td>';
                $html.= '<td class="border padding center">&nbsp;</td>';
            $html.= '</tr>';
            }
        $html.= '</table>';
        
        $html.= '<table style="width:100%;margin-top:15px">';
            $html.= '<tr>';
                $html.= '<td class="padding" colspan="5" style="padding-left:0px;vertical-align:middle;"><strong>c) Your <span style="color:blue;"><u>dependent child</u></span> 18 years old or older</strong>';
                    $html.= '<table style="width:100%;margin-top:5px;">';
                        $html.= '<tr><td class="border padding" style="height:60px;">Family name and given names (as shown on passport/travel document)<td></tr>';
                    $html.= '</table>';
                $html.= '</td>';
                $html.= '<td class="padding center"><table><tr><td class="center"><span style="font-size:20px;">&#9744;</span></td><td>&nbsp;</td><td class="center"><strong>did not travel</strong></td></tr></table></td>';
            $html.= '</tr>';
            $html.= '<tr>';
                $html.= '<td class="border padding center" width="12%"><strong>From</strong><br/>YYYY-MM</td>';
                $html.= '<td class="border padding center" width="12%"><strong>To</strong><br/>YYYY-MM</td>';
                $html.= '<td class="border padding center" width="12%"><strong>Duration</td>';
                $html.= '<td class="border padding center"><strong>Destination</strong><br/>(City and country)</td>';
                $html.= '<td class="border padding center"><strong>Purpose of travel</strong></td>';
                $html.= '<td class="border padding center"><strong>Provide details</strong><br/>(if applicable)</td>';
            $html.= '</tr>';
            
            $within_travel = strtolower((!empty($myanswers[8]))?$myanswers[8]:'');
            if($within_travel == 'yes' || $within_travel == '是') {
                $html.= '<tr>';
                    $html.= '<td class="border padding center">'.((!empty($myanswers[2]))?$myanswers[2]:'').'</td>';
                    $html.= '<td class="border padding center">'.((!empty($myanswers[3]))?$myanswers[3]:'').'</td>';
                    $html.= '<td class="border padding center">'.((!empty($myanswers[4]))?($myanswers[4].' days'):'').'</td>';
                    $html.= '<td class="border padding center">'.((!empty($myanswers[5]))?$myanswers[5]:'').'</td>';
                    $html.= '<td class="border padding center">'.((!empty($myanswers[6]))?$myanswers[6]:'').'</td>';
                    $html.= '<td class="border padding center">&nbsp;</td>';
                $html.= '</tr>';
            }
            else {
                $html.= '<tr>';
                    $html.= '<td class="border padding center">&nbsp;</td>';
                    $html.= '<td class="border padding center">&nbsp;</td>';
                    $html.= '<td class="border padding center">&nbsp;</td>';
                    $html.= '<td class="border padding center">&nbsp;</td>';
                    $html.= '<td class="border padding center">&nbsp;</td>';
                    $html.= '<td class="border padding center">&nbsp;</td>';
                $html.= '</tr>';
            }
            
            for($k=1;$k<=3;$k++) {
            $html.= '<tr>';
                $html.= '<td class="border padding center">&nbsp;</td>';
                $html.= '<td class="border padding center">&nbsp;</td>';
                $html.= '<td class="border padding center">&nbsp;</td>';
                $html.= '<td class="border padding center">&nbsp;</td>';
                $html.= '<td class="border padding center">&nbsp;</td>';
                $html.= '<td class="border padding center">&nbsp;</td>';
            $html.= '</tr>';
            }
        $html.= '</table>';
        
        $html.= '<table style="width:100%;margin-top:15px">';
            $html.= '<tr>';
                $html.= '<td class="border padding">';
                $html.= 'I certify that the information contained on this document is complete, accurate and factual. I also realize that once this document has been completed and signed that it will form part of my Immigration Record and will be used to verify my family details on future applications.';
                    $html.= '<table style="width:100%;margin-top:15px;">';
                        $html.= '<tr><td width="70">Signature:</td><td class="border-bottom padding"></td><td width="20"></td><td width="120">Date (YYYY-MM-DD):</td><td width="150" class="border-bottom padding"></td></tr>';
                    $html.= '</table>';
                $html.= '</td>';
            $html.= '</tr>';
        $html.= '</table>';
        
        $html.= '<table style="width:100%;margin-top:15px">';
            $html.= '<tr>';
                $html.= '<td class="border padding">';
                $html.= 'Personal information provided on this form is collected by Immigration, Refugees, and Citizenship Canada (IRCC) under the authority of the Immigration and Refugee Protection Act (IRPA). The personal information will be used for the purpose of processing an application. The personal information provided may be disclosed to other federal government institutions, law enforcement bodies, non-governmental organizations, provincial/territorial governments and foreign governments for the purpose of validating identity, admissibility and eligibility.'; 
                $html.= '<br/><br/>';
                $html.= 'Personal information may also be used for other purposes including research, statistics, program and policy evaluation, internal audit, risk management, subsequent program eligibility, strategy development and reporting.';
                $html.= '<br/><br/>';
                $html.= 'Failure to complete the form in full may result in a delay or the application not being processed. The Privacy Act gives individuals the right of access to, protection, and correction of their personal information. If you are not satisfied with the manner in which IRCC handles your personal information, you may exercise your right to file a complaint to the <span style="color:blue;"><u>Office of the Privacy Commissioner of Canada</u></span>. The collection, use, disclosure and retention of your personal information is further described in IRCC\'s Personal Information Bank - IRCC PPU 042.';
                $html.= '</td>';
            $html.= '</tr>';
        $html.= '</table>';
        
        
        require_once app_path('Libraries/pdf/mpdf.php');
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 10,
            'margin_bottom' => 15,
            'margin_header' => 5,
            'margin_footer' => 5
        ]);
        $mpdf->autoLangToFont = true;
        $mpdf->autoScriptToLang = true;
        $mpdf->useSubstitutions = true;
        
        // css 
        $stylesheet = '<style>';
        $stylesheet.= '* { font-family: Arial, "Microsoft JhengHei", 微軟正黑體, "Heiti TC", "PMingLiU", 新細明體; box-sizing: border-box; outline: none; padding: 0px; margin: 0px; color: #211e1e;}';
        $stylesheet.= 'table { border-collapse:collapse; }';
        $stylesheet.= 'table td { font-size:11px; padding: 2px 0px; margin: 0px; border: 0px; vertical-align: top; line-height: 1.25; }';
        $stylesheet.= 'table td.border { border: 1px solid #000; }';
        $stylesheet.= 'table td.border-left { border-left: 1px solid #000; }';
        $stylesheet.= 'table td.border-right { border-right: 1px solid #000; }';
        $stylesheet.= 'table td.border-top { border-top: 1px solid #000; }';
        $stylesheet.= 'table td.border-bottom { border-bottom: 1px solid #000; }';
        $stylesheet.= 'table td.padding { padding: 8px; }';
        $stylesheet.= 'table td.padding-left { padding-left: 8px; }';
        $stylesheet.= 'table td.padding-right { padding-right: 8px; }';
        $stylesheet.= 'table td.padding-top { padding-top: 8px; }';
        $stylesheet.= 'table td.padding-bottom { padding-bottom: 8px; }';
        $stylesheet.= 'table td.center { text-align:center; vertical-align: middle; }';
        $stylesheet.= 'table td.bold { font-weight:bold; }';
        $stylesheet.= 'table td.nowrap { white-space: nowrap; }';
        $stylesheet.= '</style>';
        
       
        $mpdf->WriteHTML($stylesheet,\Mpdf\HTMLParserMode::HEADER_CSS);
        $mpdf->SetHTMLHeader('<div style="font-size:11px;text-align:right;">page {PAGENO} of {nbpg}</div>');
        $mpdf->SetHTMLFooter('<table width="100%"><tr><td style="vertical-align:bottom;">IMM 5562 (02-2023) E</td><td style="text-align:right;"><img src="asset/image/pdf/ircc-logo.png" width="120"></td></tr></table>');
        $mpdf->WriteHTML($html);
        $mpdf->SetTitle(strtoupper('SUPPLEMENTARY-INFORMATION-YOUR-TRAVELS'));
        $mpdf->Output(strtoupper('SUPPLEMENTARY-INFORMATION-YOUR-TRAVELS').'.pdf', 'I');
    }

    protected function default_questions($fid = 1, $index = '') {
        $fid = max(1, (int)$fid);
        
        $questions = [];
        if($fid == 1) {
            $questions[1] = [
                1   =>  
                [
                    'title'     =>  'What’s your type of application/enquiry?',
                    'answers'   =>  [
                        4 => 'Electronic Travel Authorization',
                        8 => 'Technical difficulties',
                        9 => 'Change of contact information',
                        40 => 'Use a representatives or release personal information',
                        11 => 'Withdrawal of application',
                        12 => 'Replacement documents, Amendments to documents and Verification of Status documents',
                        41 => 'Citizenship',
                        68 => 'Certificate of Identity/Refugee Travel Document',
                        42 => 'Permanent Resident Card',
                        43 => 'Sponsorship',
                        44 => 'Temporary Residence (applied online) ',
                        45 => 'Temporary Residence (applied by mail)',
                        67 => 'International Experience Canada',
                        46 => 'In-Canada Permanent Residence',
                        5 => 'Permanent Residence (applied online)',
                        47 => 'Permanent Residence (applied by mail)',
                        19 => 'Request urgent processing of renewal or replacement card and have already applied', 
                    ]
                ],
                2   =>  
                [
                    'title'     =>  'What’s your family name?',
                    'answers'   =>  ''
                ],
                3   =>  
                [
                    'title'     =>  'What’s know your given name?',
                    'answers'   =>  ''
                ],
                4   =>  
                [
                    'title'     =>  'What’s your mobile number (please include country code)?',
                    'answers'   =>  ''
                ],
                5   =>  
                [
                    'title'     =>  'What’s your email address?',
                    'answers'   =>  ''
                ]
            ];

            $questions[2] = [
                1   =>  
                [
                    'title'     =>  '您的申請/查詢類型是什麼？',
                    'answers'   =>  [
                        4 => '電子旅行授權',
                        8 => '技術困難',
                        9 => '聯絡資訊變更',
                        40 => '使用代表或發布個人資訊',
                        11 => '撤回申請',
                        12 => '取代檔案、檔案修改和狀態檔案驗證',
                        41 => '公民身份',
                        68 => '身分證明/難民旅行證件',
                        42 => '永久居民卡',
                        43 => '贊助',
                        44 => '臨時居留（線上申請）',
                        45 => '臨時居留（透過郵寄申請）',
                        67 => '加拿大國際經驗',
                        46 => '加拿大永久居留權',
                        5 => '永久居留權（線上申請）',
                        47 => '永久居留權（透過郵寄申請）',
                        19 => '請求緊急處理更新或更換卡片並已申請',
                    ]
                ],
                2   =>  
                [
                    'title'     =>  '您姓是什麼？',
                    'answers'   =>  ''
                ],
                3   =>  
                [
                    'title'     =>  '您名是什麼？',
                    'answers'   =>  ''
                ],
                4   =>  
                [
                    'title'     =>  '您的手機號碼是多少（請包含國家代碼）？',
                    'answers'   =>  ''
                ],
                5   =>  
                [
                    'title'     =>  '您的電子郵件地址是什麼？',
                    'answers'   =>  ''
                ],
            ];

            $questions[3] = [
                1   =>  
                [
                    'title'     =>  '您姓是什麽？',
                    'answers'   =>  ''
                ],
                2   =>  
                [
                    'title'     =>  '您名是什麽？',
                    'answers'   =>  ''
                ],
                3   =>  
                [
                    'title'     =>  '您的手机号码是多少（请包含国家代码）？',
                    'answers'   =>  ''
                ],
                4   =>  
                [
                    'title'     =>  '您的电子邮件地址是什麽？',
                    'answers'   =>  ''
                ],
            ];
        }
        else if($fid == 2) {
            $questions[1] = [
                1   =>  
                [
                    'title'     =>  'What’s your family name?',
                    'answers'   =>  ''
                ],
                2   =>  
                [
                    'title'     =>  'What’s know your given name?',
                    'answers'   =>  ''
                ],
                3   =>  
                [
                    'title'     =>  'Please tell us your travel history, base on records from you passport',
                    'subtitle'  =>  'Travel start date (YYYY-MM)',
                    'answers'   =>  ''
                ],
                4   =>  
                [
                    'title'     =>  'Travel end date (YYYY-MM)',
                    'answers'   =>  ''
                ],
                5   =>  
                [
                    'title'     =>  'Duration (Days)',
                    'answers'   =>  ''
                ],
                6   =>  
                [
                    'title'     =>  'Destination (City And Country)',
                    'answers'   =>  ''
                ],
                7   =>  
                [
                    'title'     =>  'Purpose of travel',
                    'answers'   =>  
                    [
                        'Business', 
                        'Tourism',
                        'Training', 
                        'Studies',
                        'Visiting family', 
                        'Visiting friend(s)',
                        'Official/Diplomatic visit', 
                        'Medical reasons',
                        'Transit', 
                        'Cultural/Sports',
                        'Other',
                    ]
                ],
                8   =>  
                [
                    'title'     =>  'Did your spouse (if any) travel with you? ',
                    'answers'   =>  ['No', 'Yes']
                ],
                9   =>  
                [
                    'title'     =>  'Did your dependent child 18 years old or older travel with you?',
                    'answers'   =>  ['No', 'Yes']
                ]
            ];

            $questions[2] = [
                1   =>  
                [
                    'title'     =>  '您姓是什麼？',
                    'answers'   =>  ''
                ],
                2   =>  
                [
                    'title'     =>  '您名是什麼？',
                    'answers'   =>  ''
                ],
                3   =>  
                [
                    'title'     =>  '請根據您護照上的記錄告訴我們您的旅行歷史',
                    'subtitle'  =>  '旅行開始日期 (YYYY-MM)',
                    'answers'   =>  ''
                ],
                4   =>  
                [
                    'title'     =>  '旅行結束日期 (YYYY-MM)',
                    'answers'   =>  ''
                ],
                5   =>  
                [
                    'title'     =>  '天數',
                    'answers'   =>  ''
                ],
                6   =>  
                [
                    'title'     =>  '目的地（城市和國家）',
                    'answers'   =>  ''
                ],
                7   =>  
                [
                    'title'     =>  '旅行目的',
                    'answers'   =>  
                    [
                        '商業',
                        '旅遊',
                        '訓練',
                        '學習',
                        '拜訪家人',
                        '拜訪朋友',
                        '正式/外交訪問',
                        '醫療原因',
                        '過境',
                        '文化/體育',
                        '其他',
                    ]
                ],
                8   =>  
                [
                    'title'     =>  '您的配偶（如果有）與您一起旅行嗎？',
                    'answers'   =>  ['否', '是']
                ],
                9   =>  
                [
                    'title'     =>  '您的18歲或以上的受扶養子女是否與您一起旅行？',
                    'answers'   =>  ['否', '是']
                ]
            ];

            $questions[3] = [
                1   =>  
                [
                    'title'     =>  '您姓是什麽？',
                    'answers'   =>  ''
                ],
                2   =>  
                [
                    'title'     =>  '您名是什麽？',
                    'answers'   =>  ''
                ],
                3   =>  
                [
                    'title'     =>  '请根据您护照上的记录告诉我们您的旅行历史',
                    'subtitle'  =>  '旅行开始日期 (YYYY-MM)',
                    'answers'   =>  ''
                ],
                4   =>  
                [
                    'title'     =>  '旅行结束日期 (YYYY-MM)',
                    'answers'   =>  ''
                ],
                5   =>  
                [
                    'title'     =>  '天数',
                    'answers'   =>  ''
                ],
                6   =>  
                [
                    'title'     =>  '目的地（城市和国家）',
                    'answers'   =>  ''
                ],
                7   =>  
                [
                    'title'     =>  '旅行目的',
                    'answers'   =>  
                    [
                        '商业',
                        '旅游',
                        '训练',
                        '学习',
                        '拜访家人',
                        '拜访朋友',
                        '正式/外交访问',
                        '医疗原因',
                        '过境',
                        '文化/体育',
                        '其他',
                    ]
                ],
                8   =>  
                [
                    'title'     =>  '您的配偶（如果有）与您一起旅行吗？',
                    'answers'   =>  ['否', '是']
                ],
                9   =>  
                [
                    'title'     =>  '您的18岁或以上的受扶养子女是否与您一起旅行？',
                    'answers'   =>  ['否', '是']
                ]
            ];
        }
        
        
        return (!empty($index))?((!empty($questions[$this->_current_lang_index][$index]))?$questions[$this->_current_lang_index][$index]:''):$questions[$this->_current_lang_index];
    }
}