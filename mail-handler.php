<?php
// Enable error reporting for debugging (remove or comment out in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set the recipient email
$admin_email = "info@salmanshar.com";

// PHPMailer includes and use statements (move to top)
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Helper function to sanitize input
function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Prevent any output before headers
ob_start();

// Helper function to log errors
function log_mail_error($msg) {
    file_put_contents(__DIR__ . '/mail-errors.log', date('Y-m-d H:i:s') . " - $msg\n", FILE_APPEND);
}

// Helper function to format POST data as HTML table
function format_post_data($data) {
    $html = '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
    foreach ($data as $key => $value) {
        if (in_array($key, ['contact_message', 'newsletter_email', 'contact_name', 'contact_email', 'contact_subject'])) continue;
        $html .= '<tr>';
        $html .= '<td style="background-color: #f2f2f2; font-weight: bold;">' . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . '</td>';
        $html .= '<td>' . htmlspecialchars($value) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';
    return $html;
}

// Check if it's a newsletter or contact form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Newsletter form
    if (isset($_POST['newsletter_email'])) {
        $user_email = filter_var(clean_input($_POST['newsletter_email']), FILTER_VALIDATE_EMAIL);
        if ($user_email) {
            // Email to admin
            $subject = "New Newsletter Subscription";
            $message = '<html><body>';
            $message .= '<h2>Newsletter Subscription</h2>';
            $message .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
            $message .= '<tr><td style="background-color: #f2f2f2; font-weight: bold;">Email</td><td>' . htmlspecialchars($user_email) . '</td></tr>';
            $message .= '</table>';
            $message .= '</body></html>';
            $headers = "From: $admin_email\r\nReply-To: $user_email\r\nContent-Type: text/html; charset=UTF-8\r\n";
            $admin_sent = mail($admin_email, $subject, $message, $headers);

            // Confirmation to user
            $user_subject = "Thank you for subscribing!";
            $user_message = '<html><body>';
            $user_message .= '<h2>Thank you for subscribing to our newsletter at Salmanshar Law Firm.</h2>';
            $user_message .= '<h4>Your submitted data:</h4>';
            $user_message .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
            $user_message .= '<tr><td style="background-color: #f2f2f2; font-weight: bold;">Email</td><td>' . htmlspecialchars($user_email) . '</td></tr>';
            $user_message .= '</table>';
            $user_message .= '</body></html>';
            $user_headers = "From: $admin_email\r\nContent-Type: text/html; charset=UTF-8\r\n";
            $user_sent = mail($user_email, $user_subject, $user_message, $user_headers);

            if ($admin_sent && $user_sent) {
                ob_end_clean();
                if (!headers_sent()) {
                    header("Location: thank-you.html");
                    exit();
                } else {
                    echo "<script>window.location.href='thank-you.html';</script>";
                    exit();
                }
            } else {
                log_mail_error("Newsletter mail failed. Admin: $admin_sent, User: $user_sent, Email: $user_email");
                ob_end_clean();
                if (!headers_sent()) {
                    header("Location: error.html");
                    exit();
                } else {
                    echo "<script>window.location.href='error.html';</script>";
                    exit();
                }
            }
        } else {
            log_mail_error("Invalid newsletter email: " . $_POST['newsletter_email']);
            ob_end_clean();
            if (!headers_sent()) {
                header("Location: error.html");
                exit();
            } else {
                echo "<script>window.location.href='error.html';</script>";
                exit();
            }
        }
    }
    // Contact form
    elseif (isset($_POST['contact_name']) && isset($_POST['contact_email'])) {
        $name = clean_input($_POST['contact_name']);
        $email = filter_var(clean_input($_POST['contact_email']), FILTER_VALIDATE_EMAIL);
        $subject = clean_input($_POST['contact_subject']);
        $message_text = clean_input($_POST['contact_message']);

        if ($name && $email && $subject && $message_text) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'your_gmail@gmail.com';
                $mail->Password = 'your_gmail_app_password';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                // Email to admin
                $admin_subject = "New Contact Form Submission";
                $admin_message = '<html><body>';
                $admin_message .= '<h2>Contact Form Submission</h2>';
                $admin_message .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
                $admin_message .= '<tr><td style="background-color: #f2f2f2; font-weight: bold;">Name</td><td>' . htmlspecialchars($name) . '</td></tr>';
                $admin_message .= '<tr><td style="background-color: #f2f2f2; font-weight: bold;">Email</td><td>' . htmlspecialchars($email) . '</td></tr>';
                $admin_message .= '<tr><td style="background-color: #f2f2f2; font-weight: bold;">Subject</td><td>' . htmlspecialchars($subject) . '</td></tr>';
                $admin_message .= '<tr><td style="background-color: #f2f2f2; font-weight: bold;">Message</td><td>' . nl2br(htmlspecialchars($message_text)) . '</td></tr>';
                $admin_message .= '</table>';
                $admin_message .= format_post_data($_POST);
                $admin_message .= '</body></html>';
                $mail->setFrom('your_gmail@gmail.com', 'Salmanshar Law Firm');
                $mail->addAddress($admin_email);
                $mail->Subject = $admin_subject;
                $mail->isHTML(true);
                $mail->Body = $admin_message;
                $admin_sent = $mail->send();

                // Confirmation to user
                $user_subject = "Thank you for contacting Salmanshar Law Firm";
                $user_message = '<html><body>';
                $user_message .= '<h2>Thank you for contacting Salmanshar Law Firm</h2>';
                $user_message .= '<h4>Your submitted data:</h4>';
                $user_message .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
                $user_message .= '<tr><td style="background-color: #f2f2f2; font-weight: bold;">Name</td><td>' . htmlspecialchars($name) . '</td></tr>';
                $user_message .= '<tr><td style="background-color: #f2f2f2; font-weight: bold;">Email</td><td>' . htmlspecialchars($email) . '</td></tr>';
                $user_message .= '<tr><td style="background-color: #f2f2f2; font-weight: bold;">Subject</td><td>' . htmlspecialchars($subject) . '</td></tr>';
                $user_message .= '<tr><td style="background-color: #f2f2f2; font-weight: bold;">Message</td><td>' . nl2br(htmlspecialchars($message_text)) . '</td></tr>';
                $user_message .= '</table>';
                $user_message .= '</body></html>';
                $mail->clearAddresses();
                $mail->addAddress($email);
                $mail->Subject = $user_subject;
                $mail->Body = $user_message;
                $user_sent = $mail->send();

                if ($admin_sent && $user_sent) {
                    ob_end_clean();
                    if (!headers_sent()) {
                        header("Location: thank-you.html");
                        exit();
                    } else {
                        echo "<script>window.location.href='thank-you.html';</script>";
                        exit();
                    }
                } else {
                    log_mail_error("Contact mail failed. Admin: $admin_sent, User: $user_sent, Email: $email");
                    ob_end_clean();
                    if (!headers_sent()) {
                        header("Location: error.html");
                        exit();
                    } else {
                        echo "<script>window.location.href='error.html';</script>";
                        exit();
                    }
                }
            } catch (Exception $e) {
                log_mail_error("PHPMailer Error: " . $mail->ErrorInfo);
                ob_end_clean();
                if (!headers_sent()) {
                    header("Location: error.html");
                    exit();
                } else {
                    echo "<script>window.location.href='error.html';</script>";
                    exit();
                }
            }
        } else {
            log_mail_error("Invalid contact form data: " . json_encode($_POST));
            ob_end_clean();
            if (!headers_sent()) {
                header("Location: error.html");
                exit();
            } else {
                echo "<script>window.location.href='error.html';</script>";
                exit();
            }
        }
    }
} else {
    // Friendly message for direct access or GET requests
    ob_end_clean();
    header('Content-Type: text/html; charset=UTF-8');
    http_response_code(405);
    echo '<!DOCTYPE html><html><head><title>405 Method Not Allowed</title></head><body style="font-family:sans-serif;text-align:center;margin-top:100px;"><h1>405 Method Not Allowed</h1><p>This page is for form submissions only.</p></body></html>';
    exit();
}
log_mail_error("Invalid request or direct access.");
ob_end_clean();
if (!headers_sent()) {
    header("Location: error.html");
    exit();
} else {
    echo "<script>window.location.href='error.html';</script>";
    exit();
}
?>
