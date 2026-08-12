<?php
/**
 * Email Configuration File
 * 
 * IMPORTANT: Keep this file secure and DO NOT commit to public repositories.
 * Add 'email_config.php' to your .gitignore file.
 */

return [
    // ==============================================
    // EMAIL SETTINGS
    // ==============================================
    
    // Recipient email - where contact form submissions will be sent
    'recipient_email' => 'nr8ivafrica@gmail.com',
    'recipient_name' => 'THE NR8iv AFRICA',
    
    // ==============================================
    // SMTP SERVER SETTINGS
    // ==============================================
    
    // SMTP Host (e.g., smtp.gmail.com, smtp.sendgrid.net, smtp.mailgun.org)
    'smtp_host' => 'smtp.gmail.com',
    
    // SMTP Port (587 for TLS, 465 for SSL)
    'smtp_port' => 587,
    
    // SMTP Encryption (tls or ssl)
    'smtp_encryption' => 'tls',
    
    // ==============================================
    // SMTP AUTHENTICATION
    // ==============================================
    
    // SMTP Username (your email address)
    'smtp_username' => 'anthonybonney13@gmail.com',
    
    // SMTP Password or App Password
    // For Gmail: Use an App Password (https://myaccount.google.com/apppasswords)
    // For other providers: Use your regular password or API key
    'smtp_password' => 'vlspxydvohrcoamt',
    
    // ==============================================
    // FROM EMAIL SETTINGS
    // ==============================================
    
    // The email address that will appear as the sender
    'from_email' => 'nr8ivafrica@gmail.com',
    'from_name' => 'THE NR8iv AFRICA',
    
    // ==============================================
    // EMAIL SUBJECT PREFIX
    // ==============================================
    
    'subject_prefix' => 'New Project Request - THE NR8iv AFRICA',
    
    // ==============================================
    // DEBUGGING
    // ==============================================
    
    // Enable SMTP debug output (0 = off, 1 = client, 2 = client and server)
    // Set to 0 in production
    'smtp_debug' => 0,
    
    // ==============================================
    // SPAM FILTER SETTINGS
    // ==============================================
    
    // ==============================================
    // SPAM FILTER SETTINGS
    // ==============================================
    
    // Enable/disable spam filtering
    'spam_filter_enabled' => true,
    
    // Honeypot field settings (invisible form inputs that trap bots)
    'honeypot_enabled' => true,
    'honeypot_fields' => ['website_url', 'phone_secondary', 'website'],
    
    // Time-based validation settings (blocks bots submitting under threshold seconds)
    'time_validation_enabled' => true,
    'min_submission_time_seconds' => 4, // Minimum seconds a human takes to fill form
    
    // Rate limiting settings (per IP address)
    'rate_limit_enabled' => true,
    'rate_limit_max_submissions' => 3, // Max allowed submissions
    'rate_limit_period_seconds' => 900, // Per 15 minutes (900 seconds)
    
    // Block non-Latin scripts (Cyrillic, Georgian, etc. where English/French is expected)
    'block_cyrillic_name' => true,
    'block_georgian_script' => true, // Blocks Georgian text like "მინდოდა..."
    
    /**
     * MATCHING BEHAVIOR:
     * - All matching is CASE-INSENSITIVE
     * - High confidence keywords use WORD BOUNDARY matching (whole words/phrases only)
     * - Low confidence keywords use SUBSTRING matching (partial matches allowed)
     * 
     * AUTO-REJECT LOGIC (checked in order):
     * 1. HONEYPOT: Any data in hidden honeypot fields = auto-reject
     * 2. TIME-BASED VALIDATION: Submission under 4s or missing timestamp = auto-reject
     * 3. RATE LIMITING: Exceeding 3 submissions per IP in 15 mins = auto-reject
     * 4. BOT PATTERN DETECTION: HTML tags, URLs in title/name, Cyrillic/Georgian script = auto-reject
     * 5. BLOCKED EMAIL DOMAINS & SPOOFING: Disposable/fake/spoofed domain = auto-reject
     * 6. GIBBERISH DETECTION: Name, title, or logline too short/random = auto-reject
     * 7. HIGH CONFIDENCE KEYWORDS: ANY match = auto-reject
     * 8. LOW CONFIDENCE KEYWORDS: matches >= threshold = auto-reject
     */
    
    // Minimum low-confidence keyword matches required to auto-reject
    'spam_minimum_matches' => 2,
    
    // ==============================================
    // BOT PATTERN DETECTION (runs before keyword checks)
    // ==============================================
    
    // Block form submissions containing HTML tags (e.g. <a href="...">)
    'block_html_in_fields' => true,
    
    // Block form submissions containing URLs in name/title/logline fields
    'block_urls_in_fields' => true,
    
    // Block form submissions containing messaging platform links (Telegram, WhatsApp)
    'block_messaging_links' => true,
    
    // Minimum length for meaningful text fields (script_title, logline, description)
    'min_meaningful_field_length' => 10,
    
    // ==============================================
    // BLOCKED EMAIL DOMAINS
    // ==============================================
    // Emails from these domains (or spoofed variants) are always rejected
    'blocked_email_domains' => [
        'search-nr8ivafrica.com',
        'indexhelp.pro',
        'indexhelp.net',
        'mailbox.in.ua',
        'tempmail.com',
        'throwaway.email',
        'guerrillamail.com',
        'guerrillamail.de',
        'sharklasers.com',
        'grr.la',
        'guerrillamailblock.com',
        'yopmail.com',
        'yopmail.fr',
        'mailinator.com',
        'trashmail.com',
        'trashmail.me',
        'dispostable.com',
        'maildrop.cc',
        'fakeinbox.com',
        'temp-mail.org',
        'tempail.com',
        'mohmal.com',
        'getnada.com',
    ],
    
    // ==============================================
    // BLOCKED EMAIL PREFIXES
    // ==============================================
    'blocked_email_prefixes' => [
        'no.reply',
        'noreply',
        'no-reply',
        'do-not-reply',
        'donotreply',
        'do.not.reply',
    ],
    
    // HIGH CONFIDENCE: Auto-reject on ANY match (word boundary matching)
    // These are clearly spam-only phrases unlikely in legitimate inquiries
    'spam_keywords' => [
        // Search index / SEO scam phrases
        'search index',
        'search results',
        'google\'s search index',
        'google search index',
        'indexhelp.pro',
        'indexhelp',
        'add nr8ivafrica.com now',
        'display in online search results',
        'displayed in online search results',
        'index help',

        // SEO-specific spam phrases
        'seo services',
        'seo optimization',
        'seo company',
        'seo agency',
        'seo expert',
        'seo specialist',
        'search engine optimization',
        'search engine ranking',
        'google ranking',
        'first page of google',
        'rank on google',
        'organic traffic',
        'keyword ranking',
        'backlink',
        'link building',
        
        // Clear spam opener phrases
        'i noticed your website',
        'i came across your website',
        'i found your website',
        'i was looking at your website',
        'i visited your site',
        'i was browsing your site',
        'looking at your business',
        'perfect candidate for',
        
        // Spam marketing phrases
        'guaranteed results',
        'quick results',
        'no obligation',
        'interested in our services',
        'would you be interested',
        
        // Lead generation spam
        'lead generation',
        'generate more leads',
        'qualified leads',
        'b2b leads',
        'cold outreach',
        
        // Social media spam
        'smm services',
        'instagram followers',
        
        // PPC spam
        'ppc services',
        
        // Phishing / Payment scam phrases
        'payment available',
        'confirm your operation',
        'confirm your payment',
        'claim your payment',
        'unclaimed funds',
        'wire transfer',
        'bitcoin payment',
        'cryptocurrency payment',
        'lottery winner',
        'you have been selected',
        'inheritance fund',
        'urgent response needed',
        'verify your account',
        'account suspended',
        'click here to confirm',
        'dear beneficiary',
        'dear winner',
        
        // Mass-mailing / Spam service advertisements
        'send a letter',
        'dispatch up to',
        'messages in your behalf',
        'messages on your behalf',
        'communication forms',
        'contact form blasting',
        'contact form marketing',
        'contact form messages',
        'million messages',
        'bulk email',
        'bulk mailing',
        'mass mailing',
        'mass email',
        'we only use chat for communication',
        'this offer is automatically generated',
        'classified as spam',
        'less of a chance of being',
    ],
    
    // LOW CONFIDENCE: Soft flags - may appear in legitimate project requests
    'spam_keywords_low_confidence' => [
        'website redesign',
        'website audit',
        'free website audit',
        'web development services',
        'web design services',
        'improve your website',
        'website optimization',
        'site optimization',
        'boost your traffic',
        'increase your traffic',
        'grow your business online',
        'digital marketing services',
        'digital marketing agency',
        'online marketing',
        'internet marketing',
        'social media marketing',
        'facebook marketing',
        'social media presence',
        'email marketing',
        'email list',
        'special offer',
        'limited time offer',
        'act now',
        'affordable rates',
        'competitive pricing',
        'free consultation',
        'free quote',
        'google ads',
        'facebook ads',
        'paid advertising',
        'ad campaign',
        'feedback form',
    ],
    
    // Custom rejection message for spam
    'spam_rejection_message' => 'Thank you for your message! We will get back to you soon.',

];

/*
 * ==============================================
 * SETUP INSTRUCTIONS
 * ==============================================
 * 
 * 1. GMAIL SETUP:
 *    - Go to https://myaccount.google.com/apppasswords
 *    - Create an App Password for "Mail"
 *    - Use that password in 'smtp_password' field
 *    - Set smtp_host to 'smtp.gmail.com'
 *    - Set smtp_port to 587
 *    - Set smtp_encryption to 'tls'
 * 
 * 2. SENDGRID SETUP:
 *    - Create a SendGrid account
 *    - Generate an API key
 *    - Set smtp_host to 'smtp.sendgrid.net'
 *    - Set smtp_username to 'apikey'
 *    - Set smtp_password to your API key
 *    - Set smtp_port to 587
 *    - Set smtp_encryption to 'tls'
 * 
 * 3. MAILGUN SETUP:
 *    - Create a Mailgun account
 *    - Get your SMTP credentials from the domain settings
 *    - Set smtp_host to 'smtp.mailgun.org'
 *    - Set smtp_username to your Mailgun SMTP username
 *    - Set smtp_password to your Mailgun SMTP password
 *    - Set smtp_port to 587
 *    - Set smtp_encryption to 'tls'
 * 
 * 4. OTHER PROVIDERS:
 *    - Check your email provider's SMTP settings
 *    - Update the configuration accordingly
 */
?>
