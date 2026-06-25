<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Institution_Partner extends WebController {

    public function __construct($data) {
        parent::__construct($data);
    }

    public function index() {
        // Handle form POST submission
        $this->pageAction(function() {
            $institution_name    = $this->toPlainText($this->postParamValue('institution_name'));
            $institution_type    = $this->toPlainText($this->postParamValue('institution_type'));
            $country             = $this->toPlainText($this->postParamValue('country'));
            $contact_person      = $this->toPlainText($this->postParamValue('contact_person'));
            $email               = $this->toPlainText($this->postParamValue('email'));
            $phone               = $this->toPlainText($this->postParamValue('phone'));
            $website             = $this->toPlainText($this->postParamValue('website'));
            $intl_students       = $this->toPlainText($this->postParamValue('intl_students'));
            $message             = $this->toPlainText($this->postParamValue('message'));

            // Validate required fields
            $errors = [];
            if (empty($institution_name)) $errors[] = 'Institution name is required.';
            if (empty($contact_person))   $errors[] = 'Contact person name is required.';
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'A valid email address is required.';
            }
            if (empty($phone)) $errors[] = 'Phone number is required.';
            if (empty($message)) $errors[] = 'Partnership enquiry message is required.';

            if (!empty($errors)) {
                $this->pageResult(['status' => 422, 'errors' => $errors]);
                return;
            }

            $subject = $this->_today_datetime . ' - Institution Partnership Enquiry: ' . $institution_name;

            $body  = '<h3 style="color:#002065;">Institution Partnership Enquiry</h3>';
            $body .= '<p><strong>Institution Name:</strong><br/>' . htmlspecialchars($institution_name, ENT_QUOTES) . '</p>';
            $body .= '<p><strong>Institution Type:</strong><br/>' . htmlspecialchars($institution_type, ENT_QUOTES) . '</p>';
            $body .= '<p><strong>Country:</strong><br/>' . htmlspecialchars($country, ENT_QUOTES) . '</p>';
            $body .= '<p><strong>Contact Person:</strong><br/>' . htmlspecialchars($contact_person, ENT_QUOTES) . '</p>';
            $body .= '<p><strong>Email:</strong><br/>' . htmlspecialchars($email, ENT_QUOTES) . '</p>';
            $body .= '<p><strong>Phone:</strong><br/>' . htmlspecialchars($phone, ENT_QUOTES) . '</p>';
            $body .= '<p><strong>Website:</strong><br/>' . htmlspecialchars($website, ENT_QUOTES) . '</p>';
            $body .= '<p><strong>No. of International Students (approx.):</strong><br/>' . htmlspecialchars($intl_students, ENT_QUOTES) . '</p>';
            $body .= '<p><strong>Partnership Message:</strong><br/>' . nl2br(htmlspecialchars($message, ENT_QUOTES)) . '</p>';

            // Save to DB
            \Illuminate\Support\Facades\DB::table('institution_partner_inquiries')->insert([
                'institution_name' => $institution_name,
                'institution_type' => $institution_type,
                'country'          => $country,
                'intl_students'    => $intl_students,
                'website'          => $website,
                'contact_person'   => $contact_person,
                'email'            => $email,
                'phone'            => $phone,
                'message'          => $message,
                'status'           => 'new',
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            // Send to AI-mmi team
            $this->sendEmail(['info@ai-mmi.com'], $subject, $body);

            // Send confirmation to institution
            $conf_subject = 'AI-mmi – We received your partnership enquiry';
            $conf_body  = '<h3 style="color:#002065;">Thank you for your interest, ' . htmlspecialchars($contact_person, ENT_QUOTES) . '!</h3>';
            $conf_body .= '<p>We have received your partnership enquiry from <strong>' . htmlspecialchars($institution_name, ENT_QUOTES) . '</strong> and our team will be in touch shortly.</p>';
            $conf_body .= '<p>In the meantime, feel free to explore our programmes at <a href="https://ai-mmi.com">ai-mmi.com</a>.</p>';
            $conf_body .= '<p>Best regards,<br/>The AI-mmi Team</p>';
            $this->sendEmail([$email], $conf_subject, $conf_body);

            $this->pageResult(['status' => 200, 'message' => 'success']);
        });

        return $this->pageData([])->pageView();
    }
}
