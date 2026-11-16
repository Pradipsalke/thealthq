<?php
$pageTitle = 'Dashboard Overview';
$activePage = 'dashboard';

require_once __DIR__ . '/includes/header.php';

$db = getDB();

$insightsCount = (int)$db->query("SELECT COUNT(*) FROM insights WHERE is_active = 1")->fetchColumn();
$videosCount = (int)$db->query("SELECT COUNT(*) FROM videos WHERE is_active = 1")->fetchColumn();
$uploadedVideosCount = (int)$db->query("SELECT COUNT(*) FROM videos WHERE video_type = 'upload'")->fetchColumn();
$enquiriesCount = (int)$db->query("SELECT COUNT(*) FROM enquiries")->fetchColumn();
$newEnquiriesCount = (int)$db->query("SELECT COUNT(*) FROM enquiries WHERE status = 'new'")->fetchColumn();

// Fetch recent insights
$recentInsights = $db->query("SELECT * FROM insights ORDER BY display_order ASC, id ASC LIMIT 5")->fetchAll();

// Fetch recent videos
$recentVideos = $db->query("SELECT * FROM videos ORDER BY display_order ASC, id ASC LIMIT 5")->fetchAll();

// Fetch recent enquiries
$recentEnquiries = $db->query("SELECT * FROM enquiries ORDER BY id DESC LIMIT 5")->fetchAll();
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

    <a href="enquiries.php" class="stat-card" style="text-decoration: none; color: inherit;">
        <div class="stat-info">
            <h3>Newsletter Enquiries</h3>
            <div style="display: flex; align-items: baseline; gap: 8px;">
                <div class="stat-number"><?= $enquiriesCount ?></div>
                <?php if ($newEnquiriesCount > 0): ?>
                    <span style="font-size: 12px; font-weight: 700; background: #FEF3C7; color: #92400E; padding: 2px 8px; border-radius: 999px;"><?= $newEnquiriesCount ?> new</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="stat-icon" style="background: #EDE9FE; color: #7C3AED;">
            <i class="fa-solid fa-envelope-open-text"></i>
        </div>
    </a>
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
    <a href="enquiries.php" class="btn-secondary">
        <i class="fa-solid fa-envelope-open-text" style="color: #005B94;"></i>
        <span>Manage Enquiries</span>
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
                    <th style="width: 80px;" class="sortable" data-sort="order" data-sort-type="number" title="Click to sort by order">
                        <span class="th-sort-content">
                            <span>Order</span>
                            <span class="th-sort-icon"><i class="fa-solid fa-sort"></i></span>
                        </span>
                    </th>
                    <th class="sortable" data-sort="title" data-sort-type="text" title="Click to sort by title">
                        <span class="th-sort-content">
                            <span>Title & Category</span>
                            <span class="th-sort-icon"><i class="fa-solid fa-sort"></i></span>
                        </span>
                    </th>
                    <th class="sortable" data-sort="headline" data-sort-type="text" title="Click to sort by headline">
                        <span class="th-sort-content">
                            <span>Headline</span>
                            <span class="th-sort-icon"><i class="fa-solid fa-sort"></i></span>
                        </span>
                    </th>
                    <th>Link Target</th>
                    <th style="width: 100px;" class="sortable" data-sort="status" data-sort-type="number" title="Click to sort by status">
                        <span class="th-sort-content">
                            <span>Status</span>
                            <span class="th-sort-icon"><i class="fa-solid fa-sort"></i></span>
                        </span>
                    </th>
                    <th style="width: 100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentInsights)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #64748B; padding: 24px;">No insights found. Click "Add New Insight" to create one.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentInsights as $item): ?>
                        <?php 
                        $isExt = ($item['insight_type'] === 'external' || !empty($item['is_external']));
                        $viewUrl = $isExt ? $item['link_url'] : ('../' . $item['link_url']);
                        ?>
                        <tr>
                            <td data-sort-val="<?= $item['display_order'] ?>">
                                <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 32px; padding: 0 8px; border-radius: 8px; background: #E6F3FA; color: #005B94; font-weight: 800; font-size: 13px;">#<?= $item['display_order'] ?></span>
                            </td>
                            <td data-sort-val="<?= htmlspecialchars($item['title']) ?>">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="display: inline-block; width: 14px; height: 14px; border-radius: 3px; background: <?= htmlspecialchars($item['banner_bg']) ?>; border: 1px solid rgba(0,0,0,0.1);"></span>
                                    <div>
                                        <div style="font-weight: 700;"><?= htmlspecialchars($item['title']) ?></div>
                                        <div style="font-size: 12px; color: #64748B;"><?= htmlspecialchars($item['category']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td data-sort-val="<?= htmlspecialchars($item['headline']) ?>" style="max-width: 320px;"><?= htmlspecialchars($item['headline']) ?></td>
                            <td>
                                <a href="<?= htmlspecialchars($viewUrl) ?>" target="_blank" rel="noopener" style="color: #005B94; font-size: 13px; text-decoration: none;">
                                    <?= htmlspecialchars($item['link_text'] ?? ($isExt ? 'View external ↗' : 'View article →')) ?>
                                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 11px;"></i>
                                </a>
                            </td>
                            <td data-sort-val="<?= $item['is_active'] ?>">
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
                    <th style="width: 80px;" class="sortable" data-sort="order" data-sort-type="number" title="Click to sort by order">
                        <span class="th-sort-content">
                            <span>Order</span>
                            <span class="th-sort-icon"><i class="fa-solid fa-sort"></i></span>
                        </span>
                    </th>
                    <th class="sortable" data-sort="type" data-sort-type="text" title="Click to sort by source">
                        <span class="th-sort-content">
                            <span>Type</span>
                            <span class="th-sort-icon"><i class="fa-solid fa-sort"></i></span>
                        </span>
                    </th>
                    <th class="sortable" data-sort="title" data-sort-type="text" title="Click to sort by title">
                        <span class="th-sort-content">
                            <span>Title & Speaker</span>
                            <span class="th-sort-icon"><i class="fa-solid fa-sort"></i></span>
                        </span>
                    </th>
                    <th class="sortable" data-sort="badge" data-sort-type="text" title="Click to sort by badge">
                        <span class="th-sort-content">
                            <span>Badge / Series</span>
                            <span class="th-sort-icon"><i class="fa-solid fa-sort"></i></span>
                        </span>
                    </th>
                    <th style="width: 100px;" class="sortable" data-sort="status" data-sort-type="number" title="Click to sort by status">
                        <span class="th-sort-content">
                            <span>Status</span>
                            <span class="th-sort-icon"><i class="fa-solid fa-sort"></i></span>
                        </span>
                    </th>
                    <th style="width: 100px;">Actions</th>
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
                            <td data-sort-val="<?= $item['display_order'] ?>">
                                <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 32px; padding: 0 8px; border-radius: 8px; background: #E6F3FA; color: #005B94; font-weight: 800; font-size: 13px;">#<?= $item['display_order'] ?></span>
                            </td>
                            <td data-sort-val="<?= $item['video_type'] === 'upload' ? 'Upload' : 'YouTube' ?>">
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
                            <td data-sort-val="<?= htmlspecialchars($item['title']) ?>">
                                <div style="font-weight: 700;"><?= htmlspecialchars($item['title']) ?></div>
                                <div style="font-size: 12px; color: #64748B;"><?= htmlspecialchars($item['meta_info']) ?></div>
                            </td>
                            <td data-sort-val="<?= htmlspecialchars($item['badge']) ?>"><span style="font-size: 13px; font-weight: 600; color: #005B94;"><?= htmlspecialchars($item['badge']) ?></span></td>
                            <td data-sort-val="<?= $item['is_active'] ?>">
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

<div class="admin-card">
    <div class="admin-card-header">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div class="admin-card-title">Recent Newsletter Enquiries</div>
            <?php if ($newEnquiriesCount > 0): ?>
                <span class="badge-status new"><?= $newEnquiriesCount ?> new</span>
            <?php endif; ?>
        </div>
        <a href="enquiries.php" class="btn-secondary" style="font-size: 13px;">Manage All Enquiries (<?= $enquiriesCount ?>) →</a>
    </div>
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 70px;" class="sortable" data-sort="id" data-sort-type="number" title="Click to sort by ID">
                        <span class="th-sort-content">
                            <span>#</span>
                            <span class="th-sort-icon"><i class="fa-solid fa-sort"></i></span>
                        </span>
                    </th>
                    <th class="sortable" data-sort="email" data-sort-type="text" title="Click to sort by email">
                        <span class="th-sort-content">
                            <span>Subscriber / Work Email</span>
                            <span class="th-sort-icon"><i class="fa-solid fa-sort"></i></span>
                        </span>
                    </th>
                    <th style="width: 140px;" class="sortable" data-sort="source" data-sort-type="text" title="Click to sort by source">
                        <span class="th-sort-content">
                            <span>Source</span>
                            <span class="th-sort-icon"><i class="fa-solid fa-sort"></i></span>
                        </span>
                    </th>
                    <th style="width: 130px;" class="sortable" data-sort="status" data-sort-type="text" title="Click to sort by status">
                        <span class="th-sort-content">
                            <span>Status</span>
                            <span class="th-sort-icon"><i class="fa-solid fa-sort"></i></span>
                        </span>
                    </th>
                    <th style="width: 180px;" class="sortable" data-sort="date" data-sort-type="text" title="Click to sort by date">
                        <span class="th-sort-content">
                            <span>Date Received</span>
                            <span class="th-sort-icon"><i class="fa-solid fa-sort"></i></span>
                        </span>
                    </th>
                    <th style="width: 100px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentEnquiries)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #64748B; padding: 24px;">No newsletter enquiries yet. Submissions from the website will appear here.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentEnquiries as $enq): ?>
                        <tr>
                            <td data-sort-val="<?= $enq['id'] ?>">
                                <span style="font-weight: 700; font-size: 13px; color: #64748B;">#<?= $enq['id'] ?></span>
                            </td>
                            <td data-sort-val="<?= htmlspecialchars($enq['email']) ?>">
                                <div style="font-weight: 700;"><?= htmlspecialchars($enq['email']) ?></div>
                                <?php if (!empty($enq['name'])): ?>
                                    <div style="font-size: 12px; color: #64748B;"><?= htmlspecialchars($enq['name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td data-sort-val="<?= htmlspecialchars($enq['source'] ?? 'Newsletter') ?>">
                                <span class="badge-status source"><?= htmlspecialchars($enq['source'] ?? 'Newsletter') ?></span>
                            </td>
                            <td data-sort-val="<?= $enq['status'] ?>">
                                <span class="badge-status <?= htmlspecialchars($enq['status']) ?>">
                                    <i class="fa-solid fa-circle" style="font-size: 7px;"></i>
                                    <?= ucfirst($enq['status']) ?>
                                </span>
                            </td>
                            <td data-sort-val="<?= $enq['created_at'] ?>">
                                <div style="font-size: 13px; font-weight: 600;"><?= date('M d, Y', strtotime($enq['created_at'])) ?></div>
                                <div style="font-size: 11px; color: #94A3B8;"><?= date('h:i A', strtotime($enq['created_at'])) ?></div>
                            </td>
                            <td style="text-align: right;">
                                <div class="action-buttons" style="justify-content: flex-end;">
                                    <a href="enquiries.php?search=<?= urlencode($enq['email']) ?>" class="btn-icon" title="View in Enquiries">
                                        <i class="fa-solid fa-arrow-right"></i>
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

