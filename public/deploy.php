<?php

ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);


// The secret token you configure in GitHub Webhook settings
// You MUST change this to a secure random string and update your GitHub webhook settings
$secret = 'saimashahad12081002';

// Allow viewing the deployment log easily via GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $logFile = __DIR__ . '/../deploy.log';
    if (file_exists($logFile)) {
        header('Content-Type: text/plain; charset=utf-8');
        echo file_get_contents($logFile);
    } else {
        echo "No deploy.log found yet.";
    }
    exit;
}

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

// Prevent script abortion and remove timeout limits
ignore_user_abort(true);
set_time_limit(600);

// Send immediate response to GitHub so webhook doesn't timeout (> 10s)
http_response_code(200);
header('Content-Type: text/plain');
header('Connection: close');
$msg = "Deployment initiated successfully in background.\n";
header('Content-Length: ' . strlen($msg));
echo $msg;

// Flush buffer to release GitHub webhook client immediately
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    flush();
}

// Execute deployment script and log output
$cmd = 'cd ' . escapeshellarg(__DIR__ . '/..') . ' && ./deploy.sh > deploy.log 2>&1';
shell_exec($cmd);

