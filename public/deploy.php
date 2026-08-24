<?php

// The secret token you configure in GitHub Webhook settings
// You MUST change this to a secure random string and update your GitHub webhook settings
$secret = 'saimashahad12081002';

// Ensure the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Method Not Allowed");
}

// Get the payload and signature
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// Validate the GitHub signature
if (!empty($secret) && !empty($signature)) {
    $hash = 'sha256=' . hash_hmac('sha256', $payload, $secret, false);
    if (!hash_equals($hash, $signature)) {
        http_response_code(403);
        die("Invalid Signature");
    }
}

// If validation passes, execute the deployment script
// The deploy.sh is located one directory above the public directory
$output = shell_exec('cd .. && ./deploy.sh 2>&1');

// Output the result for the webhook response (GitHub will log this)
echo "<pre>$output</pre>";
