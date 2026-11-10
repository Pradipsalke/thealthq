<?php
$pageTitle = 'Dashboard Overview';
$activePage = 'dashboard';

require_once __DIR__ . '/includes/header.php';

$db = getDB();

$insightsCount = (int)$db->query("SELECT COUNT(*) FROM insights WHERE is_active = 1")->fetchColumn();
$videosCount = (int)$db->query("SELECT COUNT(*) FROM videos WHERE is_active = 1")->fetchColumn();
$uploadedVideosCount = (int)$db->query("SELECT COUNT(*) FROM videos WHERE video_type = 'upload'")->fetchColumn();

// Fetch recent insights
$recentInsights = $db->query("SELECT * FROM insights ORDER BY display_order ASC, id ASC LIMIT 5")->fetchAll();

// Fetch recent videos
$recentVideos = $db->query("SELECT * FROM videos ORDER BY display_order ASC, id ASC LIMIT 5")->fetchAll();
?>

<!-- Statistics Overview -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <h3>Active Insights</h3>
            <div class="stat-number"><?= $insightsCount ?></div>
        </div>
        <div class="stat-icon" style="background: #E6F3FA; color: #005B94;">
            <i class="fa-solid fa-newspaper"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <h3>Active Videos</h3>
            <div class="stat-number"><?= $videosCount ?></div>
        </div>
        <div class="stat-icon" style="background: #FEF3C7; color: #D97706;">
            <i class="fa-solid fa-video"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <h3>Directly Uploaded</h3>
            <div class="stat-number"><?= $uploadedVideosCount ?></div>
        </div>
        <div class="stat-icon" style="background: #D1FAE5; color: #059669;">
            <i class="fa-solid fa-cloud-arrow-up"></i>
        </div>
    </div>
</div>

<!-- Quick Action Shortcuts -->
<div style="display: flex; gap: 14px; margin-bottom: 28px; flex-wrap: wrap;">
    <a href="insights.php?action=add" class="btn-primary">
        <i class="fa-solid fa-plus"></i>
        <span>Add New Insight</span>
    </a>
    <a href="videos.php?action=add" class="btn-accent">
        <i class="fa-solid fa-cloud-arrow-up"></i>
        <span>Upload / Add Video</span>
    </a>
</div>

<!-- Carousel Content Management Tables -->
<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">Insights Carousel Content</div>
        <a href="insights.php" class="btn-secondary" style="font-size: 13px;">Manage All Insights →</a>
    </div>
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 60px;">Order</th>
                    <th>Title & Category</th>
                    <th>Headline</th>
                    <th>Link Target</th>
                    <th>Status</th>
                    <th style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentInsights)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #64748B; padding: 24px;">No insights found. Click "Add New Insight" to create one.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentInsights as $item): ?>
                        <tr>
                            <td><span style="font-weight: 700; color: #005B94;">#<?= $item['display_order'] ?></span></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="display: inline-block; width: 14px; height: 14px; border-radius: 3px; background: <?= htmlspecialchars($item['banner_bg']) ?>; border: 1px solid rgba(0,0,0,0.1);"></span>
                                    <div>
                                        <div style="font-weight: 700;"><?= htmlspecialchars($item['title']) ?></div>
                                        <div style="font-size: 12px; color: #64748B;"><?= htmlspecialchars($item['category']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="max-width: 320px;"><?= htmlspecialchars($item['headline']) ?></td>
                            <td>
                                <a href="<?= htmlspecialchars($item['link_url']) ?>" target="_blank" style="color: #005B94; font-size: 13px; text-decoration: none;">
                                    <?= htmlspecialchars($item['link_text']) ?>
                                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 11px;"></i>
                                </a>
                            </td>
                            <td>
                                <span class="badge-status <?= $item['is_active'] ? 'active' : 'inactive' ?>">
                                    <i class="fa-solid fa-circle" style="font-size: 7px;"></i>
                                    <?= $item['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="insights.php?action=edit&id=<?= $item['id'] ?>" class="btn-icon" title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">Videos Carousel Content</div>
        <a href="videos.php" class="btn-secondary" style="font-size: 13px;">Manage All Videos →</a>
    </div>
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 60px;">Order</th>
                    <th>Type</th>
                    <th>Title & Speaker</th>
                    <th>Badge / Series</th>
                    <th>Status</th>
                    <th style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentVideos)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #64748B; padding: 24px;">No videos found. Click "Upload / Add Video" to upload one.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentVideos as $item): ?>
                        <tr>
                            <td><span style="font-weight: 700; color: #005B94;">#<?= $item['display_order'] ?></span></td>
                            <td>
                                <?php if ($item['video_type'] === 'upload'): ?>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 6px; background: #D1FAE5; color: #065F46; font-size: 12px; font-weight: 600;">
                                        <i class="fa-solid fa-file-video"></i> Uploaded
                                    </span>
                                <?php else: ?>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 6px; background: #FEE2E2; color: #991B1B; font-size: 12px; font-weight: 600;">
                                        <i class="fa-brands fa-youtube"></i> YouTube
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: 700;"><?= htmlspecialchars($item['title']) ?></div>
                                <div style="font-size: 12px; color: #64748B;"><?= htmlspecialchars($item['meta_info']) ?></div>
                            </td>
                            <td><span style="font-size: 13px; font-weight: 600; color: #005B94;"><?= htmlspecialchars($item['badge']) ?></span></td>
                            <td>
                                <span class="badge-status <?= $item['is_active'] ? 'active' : 'inactive' ?>">
                                    <i class="fa-solid fa-circle" style="font-size: 7px;"></i>
                                    <?= $item['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="videos.php?action=edit&id=<?= $item['id'] ?>" class="btn-icon" title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

