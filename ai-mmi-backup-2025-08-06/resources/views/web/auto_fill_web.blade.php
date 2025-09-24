@extends('web.common')
@section('content')
<div class="inner-panel">
    <h1 class="title">IRCC Webform</h1>
    <div class="underline"></div>
    <div class="clearboth"></div>

    <div class="form">
        <form id="autofill-web-form" method="post" data-showProcessing="0">
            <div>@csrf</div>
            
            <div class="row">
                <label for="type_of_application">Type of application/enquiry <span style="color:red;">*</span></label>
                <input type="text" id="type_of_application" name="type_of_application" value="<?php echo (!empty($_page_data['details'][0]))?$_page_data['details'][0]:''; ?>" data-validation="required">
                <?php /*
                <select id="type_of_application" name="type_of_application" data-validation="required">
                    <option value="">-- Select from the list --  </option>
                    <option value="4">Electronic Travel Authorization</option>
                    <option value="8">Technical difficulties</option>
                    <option value="9">Change of contact information</option>
                    <option value="40">Use a representatives or release personal information</option>
                    <option value="11">Withdrawal of application</option>
                    <option value="12">Replacement documents, Amendments to documents and Verification of Status documents</option>
                    <option value="41">Citizenship</option>
                    <option value="68">Certificate of Identity/Refugee Travel Document</option>
                    <option value="42">Permanent Resident Card</option>
                    <option value="43">Sponsorship</option>
                    <option value="44">Temporary Residence (applied online) </option>
                    <option value="45">Temporary Residence (applied by mail)</option>
                    <option value="67">International Experience Canada</option>
                    <option value="46">In-Canada Permanent Residence</option>
                    <option value="5">Permanent Residence (applied online)</option>
                    <option value="47">Permanent Residence (applied by mail)</option>
                    <option value="19">Request urgent processing of renewal or replacement card and have already applied</option>
                </select>
                 * 
                 */
                ?>
            </div>
            <div class="clearboth"></div>
            
            <div>&nbsp;</div>
            <h3><u>Applicant Information</u></h3>
            <div class="clearboth"></div>
            
            <div class="row left">
                <label for="applicant_family_name">Family name <span style="color:red;">*</span></label>
                <input type="text" id="applicant_family_name" name="applicant_family_name" value="<?php echo (!empty($_page_data['details'][1]))?$_page_data['details'][1]:''; ?>" data-validation="required">
            </div>
 
            <div class="row right">
                <label for="applicant_given_name">Given name <span style="color:red;">*</span></label>
                <input type="text" id="applicant_given_name" name="applicant_given_name" value="<?php echo (!empty($_page_data['details'][2]))?$_page_data['details'][2]:''; ?>" data-validation="required">
            </div>
            <div class="clearboth"></div>
            
            <div class="row left">
                <label for="applicant_email">Email</label>
                <input type="text" id="applicant_email" name="applicant_email" value="">
            </div>
 
            <div class="row right">
                <label for="applicant_birth_date">Date of birth <span style="color:red;">*</span></label>
                <input type="text" id="applicant_birth_date" name="applicant_birth_date" value="" data-validation="required">
            </div>
            <div class="clearboth"></div>
            
            <div class="row left">
                <label for="applicant_birth_country">Country of birth <span style="color:red;">*</span></label>
                <input type="text" id="applicant_birth_country" name="applicant_birth_country" value="" data-validation="required">
            </div>
 
            <div class="row right">
                <label for="applicant_client_id_number">Client ID number (UCI)</label>
                <input type="text" id="applicant_client_id_number" name="applicant_client_id_number" value="">
            </div>
            <div class="clearboth"></div>
            
            <div class="row left">
                <label for="applicant_application_number">Application number</label>
                <input type="text" id="applicant_application_number" name="applicant_application_number" value="">
            </div>
 
            <div class="row right">
                <label for="applicant_telephone_number">Telephone number</label>
                <input type="text" id="applicant_telephone_number" name="applicant_telephone_number" value="">
            </div>
            <div class="clearboth"></div>
            
            <div class="row left">
                <label for="applicant_mobile_number">Mobile number</label>
                <input type="text" id="applicant_mobile_number" name="applicant_mobile_number" value="<?php echo (!empty($_page_data['details'][3]))?$_page_data['details'][3]:''; ?>">
            </div>
            
            <div class="clearboth"></div>
            
            <div>&nbsp;</div>
            <h3><u>Enquirer Information</u></h3>
            <div class="clearboth"></div>
            
            
            <div class="row">
                <label for="relationship_to_applicant">Relationship to applicant <span style="color:red;">*</span></label>
                <select id="relationship_to_applicant" name="relationship_to_applicant" data-validation="required">
                    <option value="">-- Select from the list --</option>
                    <option value="1" selected>Applicant</option>
                    <option value="2">Representative</option>
                    <option value="3">Sponsor</option>
                </select>
            </div>
            <div class="clearboth"></div>
            
            <div class="row left">
                <label for="enquirer_family_name">Family name <span style="color:red;">*</span></label>
                <input type="text" id="enquirer_family_name" name="enquirer_family_name" value="<?php echo (!empty($_page_data['details'][1]))?$_page_data['details'][1]:''; ?>" data-validation="required">
            </div>
 
            <div class="row right">
                <label for="enquirer_given_name">Given name <span style="color:red;">*</span></label>
                <input type="text" id="enquirer_given_name" name="enquirer_given_name" value="<?php echo (!empty($_page_data['details'][2]))?$_page_data['details'][2]:''; ?>" data-validation="required">
            </div>
            <div class="clearboth"></div>
            
            <div class="row left">
                <label for="enquirer_email">Email <span style="color:red;">*</span></label>
                <input type="text" id="enquirer_email" name="enquirer_email" value="<?php echo (!empty($_page_data['details'][4]))?$_page_data['details'][4]:''; ?>" data-validation="required|email">
            </div>
            
            <div class="row right">
                <label for="enquirer_consultant_iccrc_number">Consultant ICCRC number</label>
                <input type="text" id="enquirer_consultant_iccrc_number" name="enquirer_consultant_iccrc_number" value="">
            </div>
            
            <div class="clearboth"></div>
 
            <div class="row left">
                <label for="enquirer_telephone_number">Telephone number</label>
                <input type="text" id="enquirer_telephone_number" name="enquirer_telephone_number" value="">
            </div>
       
            <div class="row right">
                <label for="enquirer_mobile_number">Mobile number</label>
                <input type="text" id="enquirer_mobile_number" name="enquirer_mobile_number" value="<?php echo (!empty($_page_data['details'][3]))?$_page_data['details'][3]:''; ?>">
            </div>
            
            <div class="clearboth"></div>
            
            
            <div>&nbsp;</div>
            <h3><u>Consent and Disclaimer</u></h3>
            <div class="clearboth"></div>
            
            <div class="row">
                <p>
                By supplying your email address (in your enquiry or previously in your application), you have initiated an email communication with IRCC. By this action, you have authorized IRCC to use the email address provided by you for communication with you including the transmission of personal information on your file/case. When you supply your email address to IRCC, it is also understood that you are aware that this channel may not be a secure channel. IRCC is not liable for the electronic disclosure of personal information to a third party where IRCC has taken reasonable means to ensure the identity of the party. IRCC is also not liable for the misuse of this information by a third party.
                <br/>
                <br/>
                Protect your personal information: IRCC takes the confidentiality of your information seriously as we use sophisticated security techniques to protect your privacy. Clients should be careful to protect their application number, client ID number, date of birth and all other personal identification information. As these personal identifiers may allow individuals to receive information about their files from IRCC via email, clients should not share this information with unauthorized third parties.
                </p>
            </div>

            <div class="next" style="text-align:right;">
                <div class="row">
                    <button type="submit" class="btn"><?php echo $_page_lang['btn.submit']; ?></button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection