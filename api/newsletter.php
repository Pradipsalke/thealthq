<?php
/**
 * API: Newsletter / Enquiry Subscription
 * Handles newsletter subscription submissions and stores them in MySQL enquiries table.
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Method not allowed. Only POST requests are accepted.'
    ]);
    exit;
}

require_once __DIR__ . '/../config/db.php';

// Parse input (supports JSON payload as well as standard form POST)
$rawInput = file_get_contents('php://input');
$data = [];

if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $data = $decoded;
    }
}

if (empty($data)) {
    $data = $_POST;
}

$email = trim($data['email'] ?? '');
$name = trim($data['name'] ?? '');
$source = trim($data['source'] ?? 'Website Newsletter');
$message = trim($data['message'] ?? '');

// Validation
if (empty($email)) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Please enter your email address.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Please enter a valid email address.'
    ]);
    exit;
}

$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
$userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

try {
    $db = getDB();

    // Rate Limiting: Max 5 submissions per IP in 10 minutes
    if (!empty($ipAddress)) {
        $rateStmt = $db->prepare("SELECT COUNT(*) FROM enquiries WHERE ip_address = ? AND created_at > (NOW() - INTERVAL 10 MINUTE)");
        $rateStmt->execute([$ipAddress]);
        $recentCount = (int)$rateStmt->fetchColumn();
        if ($recentCount >= 10) {
            http_response_code(429);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Too many submissions. Please wait a few minutes before trying again.'
            ]);
            exit;
        }
    }

    // Check if email already exists
    $checkStmt = $db->prepare("SELECT id, status FROM enquiries WHERE email = ? LIMIT 1");
    $checkStmt->execute([$email]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update updated_at and re-activate if archived
        if ($existing['status'] === 'archived') {
            $upStmt = $db->prepare("UPDATE enquiries SET status = 'new', updated_at = NOW() WHERE id = ?");
            $upStmt->execute([$existing['id']]);
        } else {
            $upStmt = $db->prepare("UPDATE enquiries SET updated_at = NOW() WHERE id = ?");
            $upStmt->execute([$existing['id']]);
        }

        echo json_encode([
            'status'  => 'success',
            'message' => 'Thank you! You are already subscribed to The ALT HQ newsletter.'
        ]);
        exit;
    }

    // Insert new enquiry / newsletter subscription
    $insertStmt = $db->prepare("INSERT INTO enquiries (email, name, source, status, notes, ip_address, user_agent) VALUES (?, ?, ?, 'new', ?, ?, ?)");
    $insertStmt->execute([
        $email,
        !empty($name) ? $name : null,
        $source ?: 'Website Newsletter',
        !empty($message) ? $message : null,
        $ipAddress,
        $userAgent
    ]);

    echo json_encode([
        'status'  => 'success',
        'message' => 'Thank you! You have been subscribed to The ALT HQ newsletter.'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'A database error occurred. Please try again later.'
    ]);
}
