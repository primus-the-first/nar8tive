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
 * Check for HIGH CONFIDENCE spam keywords using WORD BOUNDARY matching.
 * This ensures we match whole words/phrases, not substrings.
 * Example: "seo services" matches "need seo services?" but NOT "video services"
 * 
 * @param string $content The content to check
 * @param array $keywords Array of high-confidence spam keywords
 * @return array Array of matched keywords (empty if none found)
 */
function check_high_confidence_spam($content, $keywords) {
    $matches = [];
    $content_lower = strtolower($content);
    
    foreach ($keywords as $keyword) {
        $keyword_lower = strtolower($keyword);
        // Use word boundary regex for exact phrase matching
        $pattern = '/\b' . preg_quote($keyword_lower, '/') . '\b/i';
        if (preg_match($pattern, $content_lower)) {
            $matches[] = $keyword;
        }
    }
    
    return $matches;
}

/**
 * Check for LOW CONFIDENCE spam keywords using SUBSTRING matching.
 * These are soft flags that count toward the threshold.
 * 
 * @param string $content The content to check
 * @param array $keywords Array of low-confidence spam keywords
 * @return array Array of matched keywords (empty if none found)
 */
function check_low_confidence_spam($content, $keywords) {
    $matches = [];
    $content_lower = strtolower($content);
    
    foreach ($keywords as $keyword) {
        if (strpos($content_lower, strtolower($keyword)) !== false) {
            $matches[] = $keyword;
        }
    }
    
    return $matches;
}

/**
 * Generate a pseudonymized identifier for logging (GDPR/PII compliant)
 * Uses SHA-256 hash with a salt to prevent reverse lookup
 * 
 * @param string $email The email to hash
 * @return string First 12 characters of the hash for brevity
 */
function get_submission_id($email) {
    $salt = 'nr8iv_spam_log_2024'; // Static salt for consistent hashing
    return substr(hash('sha256', $salt . strtolower($email)), 0, 12);
}

/**
 * Check if content contains HTML tags or HTML-encoded entities.
 * Bot submissions often inject raw or encoded HTML like <a href="...">, &lt;a&gt;, etc.
 * 
 * @param string $content The content to check
 * @return bool|string False if clean, or the matched pattern description if HTML detected
 */
function contains_html($content) {
    // Check for raw HTML tags
    if (preg_match('/<[a-z][^>]*>/i', $content)) {
        return 'raw HTML tag';
    }
    // Check for HTML-encoded tags (e.g., &lt;a href=&quot;...&quot;&gt;)
    if (preg_match('/&lt;[a-z]/i', $content) || preg_match('/&amp;lt;/i', $content)) {
        return 'encoded HTML entity';
    }
    // Check for href= or src= attributes (even without full tags)
    if (preg_match('/href\s*=|src\s*=/i', $content)) {
        return 'HTML attribute (href/src)';
    }
    return false;
}

/**
 * Check if content contains URLs (http://, https://, www.)
 * Legitimate form submissions rarely contain raw URLs in name/title fields.
 * 
 * @param string $content The content to check
 * @return bool|string False if clean, or the matched URL pattern if detected
 */
function contains_url($content) {
    if (preg_match('/(https?:\/\/|www\.)[^\s]+/i', $content)) {
        return 'URL detected';
    }
    return false;
}

/**
 * Check if name contains Cyrillic script characters (Russian, Ukrainian, etc.)
 * Only targets Cyrillic — does NOT flag Arabic, Chinese, Japanese, Korean, Greek, etc.
 * 
 * @param string $name The name to check
 * @return bool True if Cyrillic characters are detected
 */
function contains_cyrillic($name) {
    // Unicode range \x{0400}-\x{04FF} = Cyrillic block only
    return (bool)preg_match('/[\x{0400}-\x{04FF}]/u', $name);
}

/**
 * Check if an email domain is in the blocked list.
 * 
 * @param string $email The full email address
 * @param array $blocked_domains Array of blocked domain strings
 * @return bool|string False if clean, or the matched domain
 */
function is_blocked_domain($email, $blocked_domains) {
    $domain = strtolower(substr(strrchr($email, '@'), 1));
    foreach ($blocked_domains as $blocked) {
        if ($domain === strtolower($blocked)) {
            return $blocked;
        }
    }
    return false;
}

/**
 * Check if text fields contain gibberish / are too short to be meaningful.
 * Bot submissions often use random character strings like "toc2kc", "hfp9ky".
 * 
 * @param array $fields Array of text field values to check
 * @param int $min_length Minimum expected length for meaningful content
 * @return bool True if ALL fields are suspiciously short/gibberish
 */
function is_gibberish($fields, $min_length = 10) {
    $all_short = true;
    foreach ($fields as $field) {
        if (!empty($field) && strlen(trim($field)) >= $min_length) {
            $all_short = false;
            break;
        }
    }
    return $all_short;
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

// Also grab the RAW (unsanitized) POST data for HTML/URL detection
// because sanitize_input runs htmlspecialchars which encodes < to &lt;
$raw_name = isset($_POST['name']) ? trim($_POST['name']) : '';
$raw_description = isset($_POST['description']) ? trim($_POST['description']) : '';
$raw_logline = isset($_POST['logline']) ? trim($_POST['logline']) : '';
$raw_script_title = isset($_POST['script_title']) ? trim($_POST['script_title']) : '';

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
    
    // Generate pseudonymized ID for logging (no PII)
    $submission_id = get_submission_id($email);
    
    // Helper: silently reject and exit
    $silent_reject = function($reason, $detail) use ($submission_id, $form_type, $config) {
        error_log("Spam blocked [{$reason}] | ID: {$submission_id} | Detail: {$detail} | Form: {$form_type}");
        http_response_code(200);
        $msg = $config['spam_rejection_message'] ?? 'Thank you for your message! We will get back to you soon.';
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    };
    
    // ------------------------------------------
    // LAYER 1: BOT PATTERN DETECTION
    // ------------------------------------------
    
    // Check both raw AND sanitized content for HTML (catches encoded entities too)
    $all_raw_content = $raw_name . ' ' . $raw_description . ' ' . $raw_logline . ' ' . $raw_script_title;
    $all_sanitized_content = $name . ' ' . $description . ' ' . $logline . ' ' . $script_title;
    
    if (!empty($config['block_html_in_fields'])) {
        $html_match = contains_html($all_raw_content);
        if (!$html_match) {
            $html_match = contains_html($all_sanitized_content);
        }
        if ($html_match) {
            $silent_reject('BOT-HTML', $html_match);
        }
    }
    
    if (!empty($config['block_urls_in_fields'])) {
        // Check URLs in name, title, logline (but NOT description, where URLs may be legitimate)
        $url_fields = $raw_name . ' ' . $raw_logline . ' ' . $raw_script_title;
        $url_match = contains_url($url_fields);
        if ($url_match) {
            $silent_reject('BOT-URL', $url_match);
        }
    }
    
    if (!empty($config['block_cyrillic_name'])) {
        if (contains_cyrillic($raw_name)) {
            $silent_reject('BOT-CYRILLIC', 'Cyrillic characters in name');
        }
    }
    
    // ------------------------------------------
    // LAYER 2: BLOCKED EMAIL DOMAINS
    // ------------------------------------------
    $blocked_domains = $config['blocked_email_domains'] ?? [];
    if (!empty($blocked_domains)) {
        $blocked_domain = is_blocked_domain($email, $blocked_domains);
        if ($blocked_domain !== false) {
            $silent_reject('BLOCKED-DOMAIN', $blocked_domain);
        }
    }
    
    // ------------------------------------------
    // LAYER 3: GIBBERISH / BOT CONTENT DETECTION
    // ------------------------------------------
    $min_len = $config['min_meaningful_field_length'] ?? 10;
    
    // For writer form: check script_title and logline
    // For client form: check description
    if ($form_type === 'writer') {
        if (is_gibberish([$script_title, $logline], $min_len)) {
            $silent_reject('BOT-GIBBERISH', 'All text fields below minimum length');
        }
    } else {
        if (is_gibberish([$description], $min_len)) {
            $silent_reject('BOT-GIBBERISH', 'Description below minimum length');
        }
    }
    
    // ------------------------------------------
    // LAYER 4: HIGH CONFIDENCE KEYWORD CHECK
    // ------------------------------------------
    $high_confidence_keywords = $config['spam_keywords'] ?? [];
    $content_to_check = implode(' ', [$name, $description, $logline, $script_title, $project_type]);
    
    $high_matches = check_high_confidence_spam($content_to_check, $high_confidence_keywords);
    
    if (!empty($high_matches)) {
        $silent_reject('HIGH-KEYWORD', implode(', ', $high_matches));
    }
    
    // ------------------------------------------
    // LAYER 5: LOW CONFIDENCE KEYWORD CHECK (threshold-based)
    // ------------------------------------------
    $low_confidence_keywords = $config['spam_keywords_low_confidence'] ?? [];
    $minimum_matches = $config['spam_minimum_matches'] ?? 2;
    
    $low_matches = check_low_confidence_spam($content_to_check, $low_confidence_keywords);
    
    if (count($low_matches) >= $minimum_matches) {
        $silent_reject('LOW-THRESHOLD', count($low_matches) . "/{$minimum_matches} matches: " . implode(', ', $low_matches));
    } elseif (!empty($low_matches)) {
        // Below threshold - log for review but allow through
        error_log("Spam flagged [LOW-REVIEW] | ID: {$submission_id} | Matches: " . count($low_matches) . "/{$minimum_matches} | Keywords: " . implode(', ', $low_matches) . " | Form: {$form_type} | Status: ALLOWED");
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
