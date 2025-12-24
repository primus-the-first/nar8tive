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
