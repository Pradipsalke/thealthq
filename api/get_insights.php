<?php
/**
 * API: Get Insights
 * Returns list of active insights for the carousel
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/db.php';

try {
    $db = getDB();
    $stmt = $db->query("SELECT id, insight_type, slug, title, top_badge, category, headline, description, link_url, link_text, is_external, banner_bg, banner_text_color, badge_color, display_order FROM insights WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
    $insights = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'count'  => count($insights),
        'data'   => $insights
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Failed to load insights: ' . $e->getMessage()
    ]);
}
