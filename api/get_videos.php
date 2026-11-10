<?php
/**
 * API: Get Videos
 * Returns list of active videos for the carousel
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/db.php';

try {
    $db = getDB();
    $stmt = $db->query("SELECT id, title, badge, meta_info, video_type, video_path, thumbnail_path, banner_bg, badge_color, button_text, display_order FROM videos WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
    $videos = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'count'  => count($videos),
        'data'   => $videos
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Failed to load videos: ' . $e->getMessage()
    ]);
}

