<?php
/**
 * Email Sending Backend for NR8iv AFRICA Contact Form
 * Uses PHPMailer with SMTP for reliable email delivery
 */

// Set proper headers for JSON response
header('Content-Type: application/json');

// Enable error reporting but don't display errors (log them instead)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Prevent direct access
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if vendor autoload exists
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Email system not properly installed. Please contact the administrator.',
        'debug' => 'PHPMailer not installed. Run: composer require phpmailer/phpmailer'
    ]);
    exit;
}

// Load Composer's autoloader
require __DIR__ . '/vendor/autoload.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check if config file exists
if (!file_exists(__DIR__ . '/email_config.php')) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Email system not configured. Please contact the administrator.',
        'debug' => 'email_config.php not found'
    ]);
    exit;
}

// Load email configuration
$config = require __DIR__ . '/email_config.php';

// Validate configuration
$required_fields = ['smtp_host', 'smtp_username', 'smtp_password', 'from_email', 'recipient_email'];
foreach ($required_fields as $field) {
    if (empty($config[$field]) || 
        in_array($config[$field], ['your-email@gmail.com', 'your-app-password-here'])) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Email system not configured properly. Please contact the administrator.',
            'debug' => "Configuration field '$field' needs to be set in email_config.php. Visit test_email.php to check your configuration."
        ]);
        exit;
    }
}

// Sanitize and validate input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Check if content contains spam keywords
 * @param string $content The content to check
 * @param array $keywords Array of spam keywords to check against
 * @return bool|string Returns false if no spam found, or the matched keyword if spam detected
 */
function check_for_spam($content, $keywords) {
    $content_lower = strtolower($content);
    foreach ($keywords as $keyword) {
        if (strpos($content_lower, strtolower($keyword)) !== false) {
            return $keyword; // Return the matched keyword
        }
    }
    return false;
}

// Get form data
$form_type = isset($_POST['form_type']) ? sanitize_input($_POST['form_type']) : 'client';
$name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
$email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';

// Form-specific fields
$project_type = isset($_POST['project_type']) ? sanitize_input($_POST['project_type']) : '';
$description = isset($_POST['description']) ? sanitize_input($_POST['description']) : '';

// Writer form fields
$script_type = isset($_POST['script_type']) ? sanitize_input($_POST['script_type']) : '';
$script_title = isset($_POST['script_title']) ? sanitize_input($_POST['script_title']) : '';
$logline = isset($_POST['logline']) ? sanitize_input($_POST['logline']) : '';

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!validate_email($email)) {
    $errors[] = 'Invalid email format';
}

// Form-specific validation
if ($form_type === 'writer') {
    if (empty($script_type)) {
        $errors[] = 'Script type is required';
    }
    if (empty($script_title)) {
        $errors[] = 'Script title is required';
    }
    if (empty($logline)) {
        $errors[] = 'Logline/synopsis is required';
    }
} else {
    // Client form validation
    if (empty($project_type) || $project_type === 'Select project type') {
        $errors[] = 'Project type is required';
    }
    if (empty($description)) {
        $errors[] = 'Project description is required';
    }
}

// Return errors if validation fails
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// ==============================================
// SPAM FILTER CHECK
// ==============================================
if (!empty($config['spam_filter_enabled']) && $config['spam_filter_enabled'] === true) {
    $spam_keywords = $config['spam_keywords'] ?? [];
    
    if (!empty($spam_keywords)) {
        // Combine all text fields to check for spam
        $content_to_check = implode(' ', [
            $name,
            $description,
            $logline,
            $script_title,
            $project_type
        ]);
        
        $matched_keyword = check_for_spam($content_to_check, $spam_keywords);
        
        if ($matched_keyword !== false) {
            // Log the spam attempt for monitoring
            error_log("Spam email blocked. Matched keyword: '{$matched_keyword}' | Email: {$email} | Name: {$name}");
            
            // Return a generic message (don't reveal spam was detected)
            http_response_code(200); // Return 200 so spammers think it succeeded
            $rejection_message = $config['spam_rejection_message'] ?? 'Thank you for your message! We will get back to you soon.';
            echo json_encode(['success' => true, 'message' => $rejection_message]);
            exit;
        }
    }
}

// Create PHPMailer instance
$mail = new PHPMailer(true);

try {
    // ==========================================
    // SMTP SERVER SETTINGS
    // ==========================================
    $mail->isSMTP();
    $mail->Host       = $config['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp_username'];
    $mail->Password   = $config['smtp_password'];
    $mail->SMTPSecure = $config['smtp_encryption'];
    $mail->Port       = $config['smtp_port'];
    
    // Set debug level (0 = off, 1 = client, 2 = client and server)
    $mail->SMTPDebug  = $config['smtp_debug'];
    
    // ==========================================
    // EMAIL SETTINGS
    // ==========================================
    
    // Sender (your configured email)
    $mail->setFrom($config['from_email'], $config['from_name']);
    
    // Recipient (where the form submission goes)
    $mail->addAddress($config['recipient_email'], $config['recipient_name']);
    
    // Reply-To (the person who filled the form)
    $mail->addReplyTo($email, $name);
    
    // ==========================================
    // EMAIL CONTENT
    // ==========================================
    
    $mail->isHTML(true);
    
    // Embed the logo - REMOVED to prevent attachment issue
    // Using hosted URL instead
    
    $mail->CharSet = 'UTF-8';
    
    // Set subject and content based on form type
    if ($form_type === 'writer') {
        $mail->Subject = $config['subject_prefix'] . ' - Script Submission: ' . $script_title;
        
        // Writer form email body
        $mail->Body = "
        <html>
        <head>
            <style>
                /* Reset & Basics */
                body { margin: 0; padding: 0; background-color: #fdfbf7; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; }
                table { border-spacing: 0; width: 100%; }
                td { padding: 0; }
                img { border: 0; }
                
                /* Main Container */
                .wrapper { width: 100%; table-layout: fixed; background-color: #fdfbf7; padding-bottom: 40px; }
                .main-content { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 600px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
                
                /* Header */
                .header { background: linear-gradient(135deg, #2d8b84 0%, #3da59d 100%); padding: 40px 0; text-align: center; }
                .header-title { color: #ffffff; font-size: 24px; font-weight: bold; margin-top: 15px; margin-bottom: 0; font-family: Georgia, serif; letter-spacing: 0.5px; }
                .header-subtitle { color: rgba(255,255,255,0.85); font-size: 14px; margin-top: 5px; }
                
                /* Body */
                .body-section { padding: 40px 40px; }
                .field-row { margin-bottom: 25px; border-bottom: 1px solid #f0f0f0; padding-bottom: 15px; }
                .field-row:last-child { border-bottom: none; margin-bottom: 0; }
                .field-label { font-size: 12px; color: #888888; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; margin-bottom: 5px; display: block; }
                .field-value { font-size: 16px; color: #333333; line-height: 1.5; font-weight: 500; }
                .field-value a { color: #2d8b84; text-decoration: none; }
                
                /* Footer */
                .footer { background-color: #f5f1eb; padding: 25px; text-align: center; color: #666666; font-size: 12px; border-top: 1px solid #e5e5e5; }
                .footer p { margin: 5px 0; }
            </style>
        </head>
        <body>
            <div class='wrapper'>
                <center>
                    <div class='main-content'>
                        <!-- Header -->
                        <div class='header'>
                            <!-- Logo from hosted URL -->
                            <img src='https://www.nr8ivafrica.com/assets/logo.jpg' alt='THE NR8iv AFRICA' style='height: 70px; display: block; margin: 0 auto; border-radius: 4px;'>
                            <h1 class='header-title'>✨ New Script Submission</h1>
                            <p class='header-subtitle'>A writer wants to sell their script</p>
                        </div>
                        
                        <!-- Content -->
                        <div class='body-section'>
                            <div class='field-row'>
                                <span class='field-label'>Writer Name</span>
                                <div class='field-value'>" . htmlspecialchars($name) . "</div>
                            </div>
                            
                            <div class='field-row'>
                                <span class='field-label'>Email Address</span>
                                <div class='field-value'><a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></div>
                            </div>
                            
                            <div class='field-row'>
                                <span class='field-label'>Script Type</span>
                                <div class='field-value'>" . ucfirst(str_replace('_', ' ', htmlspecialchars($script_type))) . "</div>
                            </div>
                            
                            <div class='field-row'>
                                <span class='field-label'>Script Title</span>
                                <div class='field-value' style='font-style: italic; font-size: 18px;'>" . htmlspecialchars($script_title) . "</div>
                            </div>
                            
                            <div class='field-row'>
                                <span class='field-label'>Logline / Synopsis</span>
                                <div class='field-value'>" . nl2br(htmlspecialchars($logline)) . "</div>
                            </div>
                        </div>
                        
                        <!-- Footer -->
                        <div class='footer'>
                            <p><strong>THE NR8iv AFRICA</strong></p>
                            <p>Script & Story Sales Division</p>
                            <p style='margin-top: 15px; opacity: 0.7;'>Received: " . date('F j, Y, g:i a') . "</p>
                        </div>
                    </div>
                </center>
            </div>
        </body>
        </html>
        ";
        
        // Plain text alternative for writer form
        $mail->AltBody = "
        New Script Submission - THE NR8iv AFRICA
        
        Writer Name: $name
        Email Address: $email
        Script Type: " . ucfirst(str_replace('_', ' ', $script_type)) . "
        Script Title: $script_title
        
        Logline / Synopsis:
        $logline
        
        ---
        This email was sent from THE NR8iv AFRICA Script Sales form
        Received on: " . date('F j, Y, g:i a') . "
        ";
    } else {
        // Client form (original)
        $mail->Subject = $config['subject_prefix'] . ' - ' . ucfirst(str_replace('_', ' ', $project_type));
        
        // HTML email body (Redesigned with website colors)
        $mail->Body = "
        <html>
        <head>
            <style>
                /* Reset & Basics */
                body { margin: 0; padding: 0; background-color: #fdfbf7; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; }
                table { border-spacing: 0; width: 100%; }
                td { padding: 0; }
                img { border: 0; }
                
                /* Main Container */
                .wrapper { width: 100%; table-layout: fixed; background-color: #fdfbf7; padding-bottom: 40px; }
                .main-content { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 600px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
                
                /* Header */
                .header { background-color: #2d8b84; padding: 40px 0; text-align: center; }
                .header-title { color: #ffffff; font-size: 24px; font-weight: bold; margin-top: 15px; margin-bottom: 0; font-family: Georgia, serif; letter-spacing: 0.5px; }
                
                /* Body */
                .body-section { padding: 40px 40px; }
                .field-row { margin-bottom: 25px; border-bottom: 1px solid #f0f0f0; padding-bottom: 15px; }
                .field-row:last-child { border-bottom: none; margin-bottom: 0; }
                .field-label { font-size: 12px; color: #888888; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; margin-bottom: 5px; display: block; }
                .field-value { font-size: 16px; color: #333333; line-height: 1.5; font-weight: 500; }
                .field-value a { color: #2d8b84; text-decoration: none; }
                
                /* Footer */
                .footer { background-color: #f5f1eb; padding: 25px; text-align: center; color: #666666; font-size: 12px; border-top: 1px solid #e5e5e5; }
                .footer p { margin: 5px 0; }
            </style>
        </head>
        <body>
            <div class='wrapper'>
                <center>
                    <div class='main-content'>
                        <!-- Header -->
                        <div class='header'>
                            <!-- Logo from hosted URL -->
                            <img src='https://www.nr8ivafrica.com/assets/logo.jpg' alt='THE NR8iv AFRICA' style='height: 70px; display: block; margin: 0 auto; border-radius: 4px;'>
                            <h1 class='header-title'>New Project Request</h1>
                        </div>
                        
                        <!-- Content -->
                        <div class='body-section'>
                            <div class='field-row'>
                                <span class='field-label'>Client Name</span>
                                <div class='field-value'>" . htmlspecialchars($name) . "</div>
                            </div>
                            
                            <div class='field-row'>
                                <span class='field-label'>Email Address</span>
                                <div class='field-value'><a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></div>
                            </div>
                            
                            <div class='field-row'>
                                <span class='field-label'>Project Type</span>
                                <div class='field-value'>" . ucfirst(str_replace('_', ' ', htmlspecialchars($project_type))) . "</div>
                            </div>
                            
                            <div class='field-row'>
                                <span class='field-label'>Project Description</span>
                                <div class='field-value'>" . nl2br(htmlspecialchars($description)) . "</div>
                            </div>
                        </div>
                        
                        <!-- Footer -->
                        <div class='footer'>
                            <p><strong>THE NR8iv AFRICA</strong></p>
                            <p>Transforming Ideas into Powerful Words</p>
                            <p style='margin-top: 15px; opacity: 0.7;'>Received: " . date('F j, Y, g:i a') . "</p>
                        </div>
                    </div>
                </center>
            </div>
        </body>
        </html>
        ";
        
        // Plain text alternative for email clients that don't support HTML
        $mail->AltBody = "
        New Project Request - THE NR8iv AFRICA
        
        Client Name: $name
        Email Address: $email
        Project Type: " . ucfirst(str_replace('_', ' ', $project_type)) . "
        
        Project Description:
        $description
        
        ---
        This email was sent from THE NR8iv AFRICA contact form
        Received on: " . date('F j, Y, g:i a') . "
        ";
    }
    
    // ==========================================
    // SEND EMAIL
    // ==========================================
    
    $mail->send();
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your message! We will get back to you soon.'
    ]);
    
} catch (Exception $e) {
    // Error response
    http_response_code(500);
    
    // Log the error for debugging
    error_log("Email sending failed: {$mail->ErrorInfo}");
    
    echo json_encode([
        'success' => false,
        'message' => 'Sorry, there was an error sending your message. Please try again later.',
        // Include debug info only in development
        'debug' => isset($config['smtp_debug']) && $config['smtp_debug'] > 0 ? $mail->ErrorInfo : null
    ]);
}
?>
