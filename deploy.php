<?php
/**
 * Git Deployment Script for Namecheap
 * 
 * This script handles automatic deployment from a Git repository (GitHub/GitLab/Bitbucket).
 * Set this file's URL as your webhook endpoint in your Git provider.
 * 
 * Usage:
 * 1. Upload this file to your Namecheap hosting
 * 2. Set the webhook URL in your Git repository settings
 * 3. Configure the secret key below for security
 */

// ============================================
// CONFIGURATION - MODIFY THESE VALUES
// ============================================

// Your webhook secret key (set this in your Git provider's webhook settings)
// For GitHub: Repository Settings > Webhooks > Secret
$secret_key = 'Aeternus'; // Change this to a strong, random string

// Branch to deploy (usually 'main' or 'master')
$branch = 'main';

// Path to your repository on the server (usually the document root)
$repo_path = __DIR__;

// Log file path
$log_file = __DIR__ . '/deploy.log';

// Enable logging
$enable_logging = true;

// ============================================
// DO NOT MODIFY BELOW THIS LINE
// ============================================

/**
 * Log a message to the deploy log file
 */
function deploy_log($message) {
    global $log_file, $enable_logging;
    
    if (!$enable_logging) return;
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] {$message}" . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Verify the webhook signature (GitHub format)
 */
function verify_signature($payload, $signature, $secret) {
    if (empty($signature)) {
        return false;
    }
    
    // GitHub sends signature as 'sha256=...'
    $parts = explode('=', $signature);
    if (count($parts) !== 2) {
        return false;
    }
    
    $algo = $parts[0];
    $hash = $parts[1];
    
    $expected = hash_hmac($algo, $payload, $secret);
    
    return hash_equals($expected, $hash);
}

/**
 * Execute shell command and return result
 */
function execute_command($command, $cwd = null) {
    $descriptors = [
        0 => ['pipe', 'r'],  // stdin
        1 => ['pipe', 'w'],  // stdout
        2 => ['pipe', 'w'],  // stderr
    ];
    
    $process = proc_open($command, $descriptors, $pipes, $cwd);
    
    if (!is_resource($process)) {
        return ['success' => false, 'output' => 'Failed to execute command'];
    }
    
    fclose($pipes[0]);
    
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    
    fclose($pipes[1]);
    fclose($pipes[2]);
    
    $return_code = proc_close($process);
    
    return [
        'success' => $return_code === 0,
        'output' => trim($stdout . "\n" . $stderr),
        'code' => $return_code
    ];
}

// Start deployment process
deploy_log('====== Deployment request received ======');
deploy_log('Request IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    deploy_log('ERROR: Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    die('Method not allowed');
}

// Get the payload
$payload = file_get_contents('php://input');

if (empty($payload)) {
    deploy_log('ERROR: Empty payload received');
    http_response_code(400);
    die('Empty payload');
}

// Verify signature if secret is set
if ($secret_key !== 'YOUR_SECRET_KEY_HERE' && !empty($secret_key)) {
    // Try GitHub signature format first
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';
    
    // Try GitLab token format
    $gitlab_token = $_SERVER['HTTP_X_GITLAB_TOKEN'] ?? '';
    
    // Try Bitbucket (has different verification)
    
    if (!empty($signature)) {
        // GitHub verification
        if (!verify_signature($payload, $signature, $secret_key)) {
            deploy_log('ERROR: Invalid GitHub signature');
            http_response_code(403);
            die('Invalid signature');
        }
        deploy_log('GitHub signature verified successfully');
    } elseif (!empty($gitlab_token)) {
        // GitLab verification
        if (!hash_equals($secret_key, $gitlab_token)) {
            deploy_log('ERROR: Invalid GitLab token');
            http_response_code(403);
            die('Invalid token');
        }
        deploy_log('GitLab token verified successfully');
    } else {
        deploy_log('WARNING: No signature/token provided, but secret is configured');
        // You can choose to reject or allow this
        // http_response_code(403);
        // die('Signature required');
    }
} else {
    deploy_log('WARNING: No secret key configured - webhook is not secured!');
}

// Parse the payload
$data = json_decode($payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    deploy_log('ERROR: Invalid JSON payload');
    http_response_code(400);
    die('Invalid JSON');
}

// Check if this is a push to the correct branch
$ref = $data['ref'] ?? '';
$pushed_branch = str_replace('refs/heads/', '', $ref);

if (!empty($branch) && $pushed_branch !== $branch) {
    deploy_log("Ignoring push to branch: {$pushed_branch} (configured: {$branch})");
    http_response_code(200);
    die("Ignoring push to branch: {$pushed_branch}");
}

deploy_log("Deploying branch: {$pushed_branch}");

// Change to repository directory
if (!chdir($repo_path)) {
    deploy_log("ERROR: Failed to change directory to: {$repo_path}");
    http_response_code(500);
    die('Failed to change directory');
}

deploy_log("Working directory: {$repo_path}");

// Execute git commands
$commands = [
    'git fetch origin',
    "git reset --hard origin/{$branch}",
    'git pull origin ' . $branch,
];

$success = true;
$output = [];

foreach ($commands as $command) {
    deploy_log("Executing: {$command}");
    $result = execute_command($command, $repo_path);
    
    $output[] = [
        'command' => $command,
        'success' => $result['success'],
        'output' => $result['output']
    ];
    
    deploy_log("Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED'));
    if (!empty($result['output'])) {
        deploy_log("Output: " . $result['output']);
    }
    
    if (!$result['success']) {
        $success = false;
        break;
    }
}

// Optional: Run composer install if composer.json exists
if ($success && file_exists($repo_path . '/composer.json')) {
    deploy_log("Executing: composer install");
    $result = execute_command('composer install --no-dev --optimize-autoloader', $repo_path);
    $output[] = [
        'command' => 'composer install',
        'success' => $result['success'],
        'output' => $result['output']
    ];
    
    if (!$result['success']) {
        deploy_log("WARNING: Composer install failed - this may be expected on some hosts");
    }
}

// Send response
if ($success) {
    deploy_log('====== Deployment completed successfully ======');
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Deployment completed successfully',
        'commands' => $output
    ]);
} else {
    deploy_log('====== Deployment failed ======');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Deployment failed',
        'commands' => $output
    ]);
}
