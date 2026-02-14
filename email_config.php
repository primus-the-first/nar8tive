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
    
    // Enable/disable spam filtering
    'spam_filter_enabled' => true,
    
    /**
     * MATCHING BEHAVIOR:
     * - All matching is CASE-INSENSITIVE
     * - High confidence keywords use WORD BOUNDARY matching (whole words/phrases only)
     *   Example: "seo services" matches "need seo services?" but NOT "video services"
     * - Low confidence keywords use SUBSTRING matching (partial matches allowed)
     *   Example: "website" in low confidence would match "mywebsite" - use carefully
     * 
     * AUTO-REJECT LOGIC (checked in order):
     * 1. BOT PATTERN DETECTION: HTML tags, URLs, or Cyrillic script in name = auto-reject
     * 2. BLOCKED EMAIL DOMAINS: Sender email from blocked domain = auto-reject
     * 3. GIBBERISH DETECTION: Name, title, or logline too short/random = auto-reject
     * 4. HIGH CONFIDENCE KEYWORDS: ANY match = auto-reject
     * 5. LOW CONFIDENCE KEYWORDS: matches >= threshold = auto-reject
     */
    
    // Minimum low-confidence keyword matches required to auto-reject
    // Set higher to be more lenient, lower to be stricter
    'spam_minimum_matches' => 2,
    
    // ==============================================
    // BOT PATTERN DETECTION (runs before keyword checks)
    // ==============================================
    
    // Block form submissions containing HTML tags (e.g. <a href="...">)
    'block_html_in_fields' => true,
    
    // Block form submissions containing URLs in name/title/logline fields
    'block_urls_in_fields' => true,
    
    // Block form submissions containing Cyrillic script characters in name
    // (targets Russian/Ukrainian spam bots; does NOT block Arabic, Chinese, Japanese, etc.)
    'block_cyrillic_name' => true,
    
    // Block form submissions containing messaging platform links (Telegram, WhatsApp)
    // in ANY field including description. Legitimate clients don't include t.me/ or wa.me/ links.
    'block_messaging_links' => true,
    
    // Minimum length for meaningful text fields (script_title, logline, description)
    // Submissions with ALL text fields shorter than this are likely bots
    'min_meaningful_field_length' => 10,
    
    // ==============================================
    // BLOCKED EMAIL DOMAINS
    // ==============================================
    // Emails from these domains are always rejected
    'blocked_email_domains' => [
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
    // Emails starting with these prefixes are always rejected.
    // Bots commonly use "no.reply", "noreply", "no-reply" style senders.
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
        'feedback form',
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
    // Only auto-reject if matches >= spam_minimum_matches threshold
    'spam_keywords_low_confidence' => [
        // These could be legitimate project requests
        'website redesign',
        'website audit',
        'free website audit',
        'web development services',
        'web design services',
        'improve your website',
        'website optimization',
        'site optimization',
        
        // Marketing terms that could be legitimate
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
        
        // Generic sales phrases
        'special offer',
        'limited time offer',
        'act now',
        'affordable rates',
        'competitive pricing',
        'free consultation',
        'free quote',
        
        // Ads-related (could be legitimate project type)
        'google ads',
        'facebook ads',
        'paid advertising',
        'ad campaign',
    ],
    
    // Custom rejection message for spam
    'spam_rejection_message' => 'Your message could not be sent. Please contact us directly if this is a legitimate inquiry.',
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
