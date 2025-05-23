<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

    // 1. Send to admin
    $admin_subject = "New Contact Form Submission - Salmanshar Law Firm";
    $admin_body = "You have received a new contact form submission:\n\n";
    $admin_body .= "Name: $name\n";
    $admin_body .= "Email: $user_email\n";
    $admin_body .= "Subject: $subject\n";
    $admin_body .= "Message:\n$message\n";
    $admin_headers = "From: $from_email\r\n";
    $admin_headers .= "Reply-To: $user_email\r\n";

    $admin_sent = mail($admin_email, $admin_subject, $admin_body, $admin_headers);

    // 2. Confirmation to user
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

    $user_sent = mail($user_email, $user_subject, $user_body, $user_headers);

    // 3. Notification to admin (optional, but you requested it)
    // This can be the same as the admin email above, or you can add a second admin/notification email if needed.
    // For now, we'll just send the same notification again for demonstration.
    // Remove/comment the following block if you only want one admin email.
    /*
    $notify_subject = "Notification: New Contact Form Submission";
    $notify_body = "A new contact form has been submitted by $name <$user_email>.\n\nSubject: $subject\n\n$message";
    $notify_headers = "From: $from_email\r\n";
    $notify_headers .= "Reply-To: $user_email\r\n";
    mail($admin_email, $notify_subject, $notify_body, $notify_headers);
    */

    if ($admin_sent && $user_sent) {
        echo "<script>alert('Thank you for contacting us. Please check your email for confirmation.');window.location.href='contact.html';</script>";
    } else {
        echo "<script>alert('Failed to send email. Please try again later.');window.location.href='contact.html';</script>";
    }
} else {
    header("Location: contact.html");
    exit();
}
?>