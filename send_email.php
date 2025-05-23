<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Helper function to format POST data as HTML table
function format_post_data($data) {
    $html = '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
    foreach ($data as $key => $value) {
        $html .= '<tr>';
        $html .= '<td style="background-color: #f2f2f2; font-weight: bold;">' . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . '</td>';
        if (is_array($value)) {
            $html .= '<td>' . htmlspecialchars(json_encode($value)) . '</td>';
        } else {
            $html .= '<td>' . htmlspecialchars($value) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</table>';
    return $html;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_email = "info@salmanshar.com";
    $from_email = "info@salmanshar.com";
    $user_email = filter_var($_POST['contact_email'], FILTER_VALIDATE_EMAIL);
    $name = htmlspecialchars($_POST['contact_name']);
    $subject = htmlspecialchars($_POST['contact_subject']);
    $message = htmlspecialchars($_POST['contact_message']);

    // Validate user email
    if (!$user_email) {
        echo "<script>alert('Please enter a valid email address.');window.location.href='contact.html';</script>";
        exit;
    }

    // Send to admin
    $admin_subject = "New Contact Form Submission - Salmanshar Law Firm";
    $admin_body = "<html><body>";
    $admin_body .= "<h2>Contact Form Submission</h2>";
    $admin_body .= format_post_data([
        'Name' => $name,
        'Email' => $user_email,
        'Subject' => $subject,
        'Message' => $message
    ]);
    $admin_body .= "</body></html>";
    $admin_headers = "From: $from_email\r\n";
    $admin_headers .= "Reply-To: $user_email\r\n";
    $admin_headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $admin_sent = mail($admin_email, $admin_subject, $admin_body, $admin_headers);

    // Confirmation to user
    $user_sent = false;
    if ($user_email && $name && $subject && $message) {
        $user_subject = "Thank You for Contacting Salmanshar Law Firm";
        $user_body = "Dear $name,\n\n";
        $user_body .= "Thank you for contacting Salmanshar Law Firm. We have received your message:\n";
        $user_body .= "--------------------------\n";
        $user_body .= "$message\n";
        $user_body .= "--------------------------\n\n";
        $user_body .= "We will get back to you soon.\n\n";
        $user_body .= "Best regards,\nSalmanshar Law Firm\n";
        $user_headers = "From: $from_email\r\n";
        $user_headers .= "Reply-To: $from_email\r\n";
        $user_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $user_sent = mail($user_email, $user_subject, $user_body, $user_headers);
    }

    // Log errors if sending fails
    if (!$admin_sent || !$user_sent) {
        file_put_contents(__DIR__ . '/mail-errors.log', date('Y-m-d H:i:s') . " - Admin: $admin_sent, User: $user_sent, Email: $user_email\n", FILE_APPEND);
    }

    if ($admin_sent) {
        echo "<script>alert('Thank you for contacting us. Please check your email for confirmation.');window.location.href='contact.html';</script>";
    } else {
        echo "<script>alert('Failed to send email. Please try again later.');window.location.href='contact.html';</script>";
    }
} else {
    header("Location: contact.html");
    exit();
}
?>