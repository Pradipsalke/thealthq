<?php
$pageTitle = 'Manage Insights';
$activePage = 'insights';

require_once __DIR__ . '/auth.php';
requireAdminLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle POST actions (Save / Update / Delete / Toggle / Reorder)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['post_action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($token)) {
        setFlash('error', 'Security check failed. Please try again.');
        header('Location: insights.php');
        exit;
    }

    if ($postAction === 'save') {
        $editId = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $topBadge = trim($_POST['top_badge'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $headline = trim($_POST['headline'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $linkUrl = trim($_POST['link_url'] ?? '');
        $linkText = trim($_POST['link_text'] ?? 'Read the article →');
        $isExternal = isset($_POST['is_external']) ? 1 : 0;
        $bannerBg = trim($_POST['banner_bg'] ?? '#005B94');
        $bannerTextColor = trim($_POST['banner_text_color'] ?? '#FFFFFF');
        $badgeColor = trim($_POST['badge_color'] ?? '#FFD200');
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($title) || empty($headline) || empty($linkUrl)) {
            setFlash('error', 'Please fill in all required fields (Title, Headline, and Link URL).');
            header('Location: insights.php' . ($editId > 0 ? "?action=edit&id=$editId" : '?action=add'));
            exit;
        }

        if ($editId > 0) {
            $stmt = $db->prepare("UPDATE insights SET title = ?, top_badge = ?, category = ?, headline = ?, description = ?, link_url = ?, link_text = ?, is_external = ?, banner_bg = ?, banner_text_color = ?, badge_color = ?, display_order = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$title, $topBadge, $category, $headline, $description, $linkUrl, $linkText, $isExternal, $bannerBg, $bannerTextColor, $badgeColor, $displayOrder, $isActive, $editId]);
            setFlash('success', 'Insight updated successfully.');
        } else {
            if ($displayOrder <= 0) {
                $maxOrder = (int)$db->query("SELECT MAX(display_order) FROM insights")->fetchColumn();
                $displayOrder = $maxOrder + 1;
            }
            $stmt = $db->prepare("INSERT INTO insights (title, top_badge, category, headline, description, link_url, link_text, is_external, banner_bg, banner_text_color, badge_color, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $topBadge, $category, $headline, $description, $linkUrl, $linkText, $isExternal, $bannerBg, $bannerTextColor, $badgeColor, $displayOrder, $isActive]);
            setFlash('success', 'New insight added successfully.');
        }
        header('Location: insights.php');
        exit;
    }

    if ($postAction === 'delete') {
        $delId = (int)($_POST['delete_id'] ?? 0);
        if ($delId > 0) {
            $stmt = $db->prepare("DELETE FROM insights WHERE id = ?");
            $stmt->execute([$delId]);
            setFlash('success', 'Insight deleted successfully.');
        }
        header('Location: insights.php');
        exit;
    }

    if ($postAction === 'toggle_status') {
        $toggleId = (int)($_POST['toggle_id'] ?? 0);
        if ($toggleId > 0) {
            $stmt = $db->prepare("UPDATE insights SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?");
            $stmt->execute([$toggleId]);
            setFlash('success', 'Status toggled successfully.');
        }
        header('Location: insights.php');
        exit;
    }

    if ($postAction === 'move') {
        $moveId = (int)($_POST['move_id'] ?? 0);
        $direction = $_POST['direction'] ?? '';
        
        $current = $db->prepare("SELECT id, display_order FROM insights WHERE id = ?");
        $current->execute([$moveId]);
        $currItem = $current->fetch();

        if ($currItem) {
            $currOrder = (int)$currItem['display_order'];
            if ($direction === 'up') {
                $other = $db->prepare("SELECT id, display_order FROM insights WHERE display_order < ? ORDER BY display_order DESC LIMIT 1");
                $other->execute([$currOrder]);
            } else {
                $other = $db->prepare("SELECT id, display_order FROM insights WHERE display_order > ? ORDER BY display_order ASC LIMIT 1");
                $other->execute([$currOrder]);
            }
            $otherItem = $other->fetch();

            if ($otherItem) {
                $db->prepare("UPDATE insights SET display_order = ? WHERE id = ?")->execute([$otherItem['display_order'], $currItem['id']]);
                $db->prepare("UPDATE insights SET display_order = ? WHERE id = ?")->execute([$currOrder, $otherItem['id']]);
                setFlash('success', 'Order updated.');
            }
        }
        header('Location: insights.php');
        exit;
    }
}

// Prepare Edit Data if requested
$editItem = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM insights WHERE id = ?");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
    if (!$editItem) {
        setFlash('error', 'Insight not found.');
        header('Location: insights.php');
        exit;
    }
}

// Fetch all insights
$insights = $db->query("SELECT * FROM insights ORDER BY display_order ASC, id ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- Add / Edit Form -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div class="admin-card-title"><?= $action === 'edit' ? 'Edit Insight' : 'Add New Insight' ?></div>
            <a href="insights.php" class="btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Cancel & Back</span>
            </a>
        </div>
        <div class="modal-body">
            <form method="POST" action="insights.php" id="insight-form">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="post_action" value="save">
                <input type="hidden" name="id" value="<?= $editItem['id'] ?? 0 ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Title on Banner *</label>
                        <input type="text" id="title" name="title" class="form-control" required placeholder="e.g. Pune’s Next Leap" value="<?= htmlspecialchars($editItem['title'] ?? '') ?>">
                        <div class="form-hint">Displayed in large bold font inside the colored upper banner.</div>
                    </div>
                    <div class="form-group">
                        <label for="top_badge">Top Banner Badge / Perspective</label>
                        <input type="text" id="top_badge" name="top_badge" class="form-control" placeholder="e.g. Leadership perspective" value="<?= htmlspecialchars($editItem['top_badge'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category Subhead</label>
                        <input type="text" id="category" name="category" class="form-control" placeholder="e.g. GCC Ecosystem" value="<?= htmlspecialchars($editItem['category'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" id="display_order" name="display_order" class="form-control" value="<?= htmlspecialchars($editItem['display_order'] ?? count($insights) + 1) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="headline">Card Headline *</label>
                    <input type="text" id="headline" name="headline" class="form-control" required placeholder="e.g. GCC leaders and government building a global innovation hub" value="<?= htmlspecialchars($editItem['headline'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="description">Author / Reading Time / Description</label>
                    <textarea id="description" name="description" class="form-control" placeholder="e.g. Nilesh Biniwale · 6 min read"><?= htmlspecialchars($editItem['description'] ?? '') ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="link_url">Link URL *</label>
                        <input type="text" id="link_url" name="link_url" class="form-control" required placeholder="e.g. insights/punes-next-leap.html or https://..." value="<?= htmlspecialchars($editItem['link_url'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="link_text">Button / Link Text</label>
                        <input type="text" id="link_text" name="link_text" class="form-control" placeholder="e.g. Read the article →" value="<?= htmlspecialchars($editItem['link_text'] ?? 'Read the article →') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Banner Theme Color</label>
                        <div class="color-picker-group">
                            <input type="color" id="banner_bg" name="banner_bg" class="color-picker-input" value="<?= htmlspecialchars($editItem['banner_bg'] ?? '#005B94') ?>">
                            <select class="form-control" onchange="document.getElementById('banner_bg').value = this.value; updateBannerColors();">
                                <option value="#005B94" <?= ($editItem['banner_bg'] ?? '#005B94') === '#005B94' ? 'selected' : '' ?>>Deep Blue (#005B94)</option>
                                <option value="#1E2229" <?= ($editItem['banner_bg'] ?? '') === '#1E2229' ? 'selected' : '' ?>>Charcoal (#1E2229)</option>
                                <option value="#FFD200" <?= ($editItem['banner_bg'] ?? '') === '#FFD200' ? 'selected' : '' ?>>Sunflower Yellow (#FFD200)</option>
                                <option value="#0077B3" <?= ($editItem['banner_bg'] ?? '') === '#0077B3' ? 'selected' : '' ?>>Cerulean Accent (#0077B3)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Banner Text & Badge Colors</label>
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <div>
                                <span style="font-size: 11px; color: #64748B;">Text:</span>
                                <input type="color" id="banner_text_color" name="banner_text_color" class="color-picker-input" value="<?= htmlspecialchars($editItem['banner_text_color'] ?? '#FFFFFF') ?>">
                            </div>
                            <div>
                                <span style="font-size: 11px; color: #64748B;">Badge:</span>
                                <input type="color" id="badge_color" name="badge_color" class="color-picker-input" value="<?= htmlspecialchars($editItem['badge_color'] ?? '#FFD200') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                        <input type="checkbox" id="is_external" name="is_external" value="1" <?= (!empty($editItem['is_external'])) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                        <label for="is_external" style="margin: 0; cursor: pointer;">Open link in new tab (external article)</label>
                    </div>

                    <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                        <input type="checkbox" id="is_active" name="is_active" value="1" <?= ($editItem['is_active'] ?? 1) == 1 ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                        <label for="is_active" style="margin: 0; cursor: pointer;">Active (visible on website carousel)</label>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border);">
                    <a href="insights.php" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Save Insight</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- List of Insights -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <div class="admin-card-title">All Insights Articles (<?= count($insights) ?>)</div>
                <div style="font-size: 13px; color: var(--slate); margin-top: 4px;">Items appear in the frontend carousel in the order listed below.</div>
            </div>
            <a href="insights.php?action=add" class="btn-primary">
                <i class="fa-solid fa-plus"></i>
                <span>Add New Insight</span>
            </a>
        </div>
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Order</th>
                        <th>Banner Preview</th>
                        <th>Title & Category</th>
                        <th>Headline</th>
                        <th>Status</th>
                        <th style="width: 160px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($insights)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #64748B; padding: 32px;">No insights yet. Click "+ Add New Insight" to publish your first article card!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($insights as $index => $item): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <form method="POST" action="insights.php" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="post_action" value="move">
                                            <input type="hidden" name="move_id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="direction" value="up">
                                            <button type="submit" class="btn-icon" style="width: 26px; height: 26px;" <?= $index === 0 ? 'disabled style="opacity: 0.3;"' : '' ?> title="Move Up">
                                                <i class="fa-solid fa-chevron-up" style="font-size: 10px;"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="insights.php" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="post_action" value="move">
                                            <input type="hidden" name="move_id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="direction" value="down">
                                            <button type="submit" class="btn-icon" style="width: 26px; height: 26px;" <?= $index === count($insights) - 1 ? 'disabled style="opacity: 0.3;"' : '' ?> title="Move Down">
                                                <i class="fa-solid fa-chevron-down" style="font-size: 10px;"></i>
                                            </button>
                                        </form>
                                        <span style="font-weight: 700; margin-left: 4px;"><?= $item['display_order'] ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div style="width: 140px; height: 50px; border-radius: 6px; background: <?= htmlspecialchars($item['banner_bg']) ?>; color: <?= htmlspecialchars($item['banner_text_color']) ?>; padding: 6px 10px; display: flex; flex-direction: column; justify-content: flex-end; overflow: hidden; border: 1px solid rgba(0,0,0,0.1);">
                                        <span style="font-size: 8px; font-weight: 600; text-transform: uppercase; color: <?= htmlspecialchars($item['badge_color']) ?>; white-space: nowrap; text-overflow: ellipsis; overflow: hidden;"><?= htmlspecialchars($item['top_badge']) ?></span>
                                        <span style="font-family: 'Montserrat', sans-serif; font-weight: 800; font-size: 11px; line-height: 1.1; white-space: nowrap; text-overflow: ellipsis; overflow: hidden;"><?= htmlspecialchars($item['title']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; font-size: 15px;"><?= htmlspecialchars($item['title']) ?></div>
                                    <span style="font-size: 12px; font-weight: 600; color: #005B94;"><?= htmlspecialchars($item['category']) ?></span>
                                </td>
                                <td style="max-width: 320px;">
                                    <div style="line-height: 1.4;"><?= htmlspecialchars($item['headline']) ?></div>
                                    <div style="font-size: 12px; color: #64748B; margin-top: 4px;"><?= htmlspecialchars($item['description']) ?></div>
                                </td>
                                <td>
                                    <form method="POST" action="insights.php" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="post_action" value="toggle_status">
                                        <input type="hidden" name="toggle_id" value="<?= $item['id'] ?>">
                                        <button type="submit" style="background: none; border: none; cursor: pointer; padding: 0;">
                                            <span class="badge-status <?= $item['is_active'] ? 'active' : 'inactive' ?>">
                                                <i class="fa-solid fa-circle" style="font-size: 7px;"></i>
                                                <?= $item['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </button>
                                    </form>
                                </td>
                                <td style="text-align: right;">
                                    <div class="action-buttons" style="justify-content: flex-end;">
                                        <a href="insights.php?action=edit&id=<?= $item['id'] ?>" class="btn-icon" title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <button type="button" class="btn-icon" style="color: #DC2626;" title="Delete" onclick="confirmDelete(<?= $item['id'] ?>, '<?= addslashes(htmlspecialchars($item['title'])) ?>')">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="delete-modal">
        <div class="modal-dialog" style="max-width: 440px;">
            <div class="modal-header">
                <div class="modal-title">Confirm Deletion</div>
                <button type="button" class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="delete-item-title"></strong>?</p>
                <p style="font-size: 13px; color: #64748B; margin-top: 8px;">This will remove the card from the frontend carousel.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <form method="POST" action="insights.php" id="delete-form" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="post_action" value="delete">
                    <input type="hidden" name="delete_id" id="delete-item-id" value="">
                    <button type="submit" class="btn-danger">
                        <i class="fa-solid fa-trash-can"></i>
                        <span>Delete</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
    function confirmDelete(id, title) {
        document.getElementById('delete-item-id').value = id;
        document.getElementById('delete-item-title').textContent = title;
        document.getElementById('delete-modal').classList.add('active');
    }
    function closeDeleteModal() {
        document.getElementById('delete-modal').classList.remove('active');
    }
    </script>
<?php endif; ?>

<script>
function updateBannerColors() {
    const bg = document.getElementById('banner_bg').value;
    if (bg.toLowerCase() === '#ffd200') {
        document.getElementById('banner_text_color').value = '#1E2229';
        document.getElementById('badge_color').value = '#1E2229';
    } else {
        document.getElementById('banner_text_color').value = '#FFFFFF';
        document.getElementById('badge_color').value = '#FFD200';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

