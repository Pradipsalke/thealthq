<?php
$pageTitle = 'Manage Insights';
$activePage = 'insights';

require_once __DIR__ . '/auth.php';
requireAdminLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Helper function to generate clean URL slug
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'article-' . time() : $text;
}

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
        $insightType = $_POST['insight_type'] ?? 'internal';
        if (!in_array($insightType, ['internal', 'external'])) {
            $insightType = 'internal';
        }

        $title = trim($_POST['title'] ?? '');
        $topBadge = trim($_POST['top_badge'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $headline = trim($_POST['headline'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $bannerBg = trim($_POST['banner_bg'] ?? '#005B94');
        $bannerTextColor = trim($_POST['banner_text_color'] ?? '#FFFFFF');
        $badgeColor = trim($_POST['badge_color'] ?? '#FFD200');
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        // Existing row if editing
        $existing = null;
        if ($editId > 0) {
            $stmt = $db->prepare("SELECT * FROM insights WHERE id = ?");
            $stmt->execute([$editId]);
            $existing = $stmt->fetch();
        }

        if ($insightType === 'external') {
            // External Article
            $linkUrl = trim($_POST['external_url'] ?? '');
            $linkText = trim($_POST['link_text'] ?? 'Read on website ↗');
            if (empty($linkText)) $linkText = 'Read on website ↗';
            $isExternal = 1;

            if (empty($title) || empty($headline) || empty($linkUrl)) {
                setFlash('error', 'Please fill in all required fields (Title, Card Headline, and External URL).');
                header('Location: insights.php' . ($editId > 0 ? "?action=edit&id=$editId" : '?action=add'));
                exit;
            }

            if (!preg_match('~^https?://~i', $linkUrl)) {
                setFlash('error', 'External URL must start with http:// or https://');
                header('Location: insights.php' . ($editId > 0 ? "?action=edit&id=$editId" : '?action=add'));
                exit;
            }

            $slug = !empty($existing['slug']) ? $existing['slug'] : slugify($title);
            $subtitle = trim($_POST['subtitle'] ?? '');
            $featuredImage = $existing['featured_image'] ?? '';
            $authorName = trim($_POST['author_name'] ?? 'Nilesh Kulkarni');
            $authorRole = trim($_POST['author_role'] ?? 'Co-author, The ALT HQ');
            $authorPhoto = trim($_POST['author_photo'] ?? 'assets/author-kulkarni.jpg');
            $authorBio = trim($_POST['author_bio'] ?? '');
            $authorLinkedin = trim($_POST['author_linkedin'] ?? '');
            $readTime = trim($_POST['read_time'] ?? 'External article');
            $publishedDate = !empty($_POST['published_date']) ? $_POST['published_date'] : date('Y-m-d');
            $content = null;
            $seoTitle = trim($_POST['seo_title'] ?? $title);
            $seoDescription = trim($_POST['seo_description'] ?? $headline);

        } else {
            // Internal Insight / Blog
            $isExternal = 0;
            $subtitle = trim($_POST['subtitle'] ?? '');
            $linkText = trim($_POST['link_text'] ?? 'Read the article →');
            if (empty($linkText)) $linkText = 'Read the article →';
            $authorName = trim($_POST['author_name'] ?? 'Nilesh Biniwale');
            $authorRole = trim($_POST['author_role'] ?? 'Managing Director, Pattern  ·  Co-author, The ALT HQ');
            $authorPhoto = trim($_POST['author_photo'] ?? 'assets/author-biniwale.jpg');
            $authorBio = trim($_POST['author_bio'] ?? '');
            $authorLinkedin = trim($_POST['author_linkedin'] ?? 'https://www.linkedin.com/in/nileshbiniwale/');
            $readTime = trim($_POST['read_time'] ?? '6 min read');
            $publishedDate = !empty($_POST['published_date']) ? $_POST['published_date'] : date('Y-m-d');
            $content = trim($_POST['content'] ?? '');
            $seoTitle = trim($_POST['seo_title'] ?? '');
            $seoDescription = trim($_POST['seo_description'] ?? '');

            // Slug Handling
            $slugInput = trim($_POST['slug'] ?? '');
            $slug = slugify(!empty($slugInput) ? $slugInput : $title);

            // Ensure slug uniqueness
            $slugCheck = $db->prepare("SELECT id FROM insights WHERE slug = ? AND id != ?");
            $slugCheck->execute([$slug, $editId]);
            if ($slugCheck->fetch()) {
                $slug = $slug . '-' . time();
            }

            $linkUrl = 'insight.php?slug=' . $slug;

            // Handle Featured Image Upload or Path
            $featuredImage = trim($_POST['featured_image'] ?? ($existing['featured_image'] ?? ''));
            if (isset($_FILES['featured_image_file']) && $_FILES['featured_image_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['featured_image_file'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (in_array($ext, $allowed)) {
                    $newFileName = 'featured_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $destPath = __DIR__ . '/../uploads/insights/' . $newFileName;
                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        $featuredImage = 'uploads/insights/' . $newFileName;
                    }
                }
            }

            // Handle Author Photo Upload
            if (isset($_FILES['author_photo_file']) && $_FILES['author_photo_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['author_photo_file'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (in_array($ext, $allowed)) {
                    $newFileName = 'author_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $destPath = __DIR__ . '/../uploads/insights/' . $newFileName;
                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        $authorPhoto = 'uploads/insights/' . $newFileName;
                    }
                }
            }

            if (empty($title) || empty($headline) || empty($content)) {
                setFlash('error', 'Please fill in all required fields (Title, Card Headline, and Article Body Content).');
                header('Location: insights.php' . ($editId > 0 ? "?action=edit&id=$editId" : '?action=add'));
                exit;
            }
        }

        // Database Save / Update
        if ($editId > 0) {
            $stmt = $db->prepare("UPDATE insights SET 
                insight_type = ?, slug = ?, title = ?, subtitle = ?, featured_image = ?,
                author_name = ?, author_role = ?, author_photo = ?, author_bio = ?, author_linkedin = ?,
                read_time = ?, published_date = ?, top_badge = ?, category = ?, headline = ?,
                description = ?, link_url = ?, link_text = ?, is_external = ?, banner_bg = ?,
                banner_text_color = ?, badge_color = ?, content = ?, seo_title = ?, seo_description = ?,
                display_order = ?, is_active = ?
                WHERE id = ?");
            $stmt->execute([
                $insightType, $slug, $title, $subtitle, $featuredImage,
                $authorName, $authorRole, $authorPhoto, $authorBio, $authorLinkedin,
                $readTime, $publishedDate, $topBadge, $category, $headline,
                $description, $linkUrl, $linkText, $isExternal, $bannerBg,
                $bannerTextColor, $badgeColor, $content, $seoTitle, $seoDescription,
                $displayOrder, $isActive, $editId
            ]);
            setFlash('success', 'Insight updated successfully.');
        } else {
            if ($displayOrder <= 0) {
                $maxOrder = (int)$db->query("SELECT MAX(display_order) FROM insights")->fetchColumn();
                $displayOrder = $maxOrder + 1;
            }
            $stmt = $db->prepare("INSERT INTO insights (
                insight_type, slug, title, subtitle, featured_image,
                author_name, author_role, author_photo, author_bio, author_linkedin,
                read_time, published_date, top_badge, category, headline,
                description, link_url, link_text, is_external, banner_bg,
                banner_text_color, badge_color, content, seo_title, seo_description,
                display_order, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $insightType, $slug, $title, $subtitle, $featuredImage,
                $authorName, $authorRole, $authorPhoto, $authorBio, $authorLinkedin,
                $readTime, $publishedDate, $topBadge, $category, $headline,
                $description, $linkUrl, $linkText, $isExternal, $bannerBg,
                $bannerTextColor, $badgeColor, $content, $seoTitle, $seoDescription,
                $displayOrder, $isActive
            ]);
            setFlash('success', 'New insight created successfully.');
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
            setFlash('success', 'Status updated.');
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

// Server-side sorting whitelist
$allowedSorts = [
    'order'    => 'display_order',
    'title'    => 'title',
    'type'     => 'insight_type',
    'category' => 'category',
    'headline' => 'headline',
    'status'   => 'is_active'
];
$sort = $_GET['sort'] ?? 'order';
$dir = strtolower($_GET['dir'] ?? 'asc');
if (!array_key_exists($sort, $allowedSorts)) {
    $sort = 'order';
}
if (!in_array($dir, ['asc', 'desc'])) {
    $dir = 'asc';
}
$orderCol = $allowedSorts[$sort];
$orderDir = strtoupper($dir);

// Fetch all insights
$insights = $db->query("SELECT * FROM insights ORDER BY {$orderCol} {$orderDir}, id ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <?php
    $currentType = $editItem['insight_type'] ?? 'internal';
    ?>
    <!-- Add / Edit Form -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <div class="admin-card-title"><?= $action === 'edit' ? 'Edit Insight' : 'Add New Insight' ?></div>
                <div style="font-size: 13px; color: var(--slate); margin-top: 4px;">Configure carousel presentation and dynamic blog post details.</div>
            </div>
            <a href="insights.php" class="btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Cancel & Back</span>
            </a>
        </div>
        <div class="modal-body">
            <form method="POST" action="insights.php" id="insight-form" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="post_action" value="save">
                <input type="hidden" name="id" value="<?= $editItem['id'] ?? 0 ?>">

                <!-- Type Selector Segmented Radio Cards -->
                <div class="form-group">
                    <label style="font-size: 14px; font-weight: 700; margin-bottom: 10px;">Select Insight Type *</label>
                    <div class="type-selector-grid">
                        <label class="type-option-card <?= $currentType === 'internal' ? 'selected' : '' ?>" id="card-type-internal" onclick="selectInsightType('internal')">
                            <input type="radio" name="insight_type" value="internal" <?= $currentType === 'internal' ? 'checked' : '' ?> onchange="selectInsightType('internal')">
                            <div class="type-option-info">
                                <h4><i class="fa-solid fa-newspaper" style="color: var(--primary); margin-right: 6px;"></i> Internal Insight / Blog</h4>
                                <p>Creates a full-page article on this website using the existing design template. Clicking the card opens the internal detail page.</p>
                            </div>
                        </label>
                        <label class="type-option-card <?= $currentType === 'external' ? 'selected' : '' ?>" id="card-type-external" onclick="selectInsightType('external')">
                            <input type="radio" name="insight_type" value="external" <?= $currentType === 'external' ? 'checked' : '' ?> onchange="selectInsightType('external')">
                            <div class="type-option-info">
                                <h4><i class="fa-solid fa-arrow-up-right-from-square" style="color: #D97706; margin-right: 6px;"></i> External Article / Link</h4>
                                <p>Points to an external publication, guest article or partner site (e.g. Thoughtworks Perspectives, Forbes). Clicking the card redirects directly.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- SECTION 1: Carousel Card Display (Applies to Both Types) -->
                <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    <div style="font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 16px; color: var(--charcoal); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-id-card" style="color: var(--primary);"></i>
                        <span>1. Carousel Card Presentation</span>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="title">Card Title (Upper Banner Title) *</label>
                            <input type="text" id="title" name="title" class="form-control" required placeholder="e.g. Pune’s Next Leap" value="<?= htmlspecialchars($editItem['title'] ?? '') ?>" oninput="syncSlugAndSeo()">
                            <div class="form-hint">Displayed in large bold font inside the colored carousel card banner.</div>
                        </div>
                        <div class="form-group">
                            <label for="top_badge">Top Banner Badge / Perspective</label>
                            <input type="text" id="top_badge" name="top_badge" class="form-control" placeholder="e.g. Pune GCC  |  Leadership perspective" value="<?= htmlspecialchars($editItem['top_badge'] ?? '') ?>">
                            <div class="form-hint">Small uppercase badge at the top of the colored banner.</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="category">Category Subhead</label>
                            <input type="text" id="category" name="category" class="form-control" placeholder="e.g. GCC Ecosystem" value="<?= htmlspecialchars($editItem['category'] ?? '') ?>">
                            <div class="form-hint">Shown in blue uppercase text above the headline.</div>
                        </div>
                        <div class="form-group">
                            <label for="display_order">Display Order in Carousel</label>
                            <input type="number" id="display_order" name="display_order" class="form-control" value="<?= htmlspecialchars($editItem['display_order'] ?? count($insights) + 1) ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="headline">Card Headline *</label>
                        <input type="text" id="headline" name="headline" class="form-control" required placeholder="e.g. GCC leaders and government building a global innovation hub" value="<?= htmlspecialchars($editItem['headline'] ?? '') ?>">
                        <div class="form-hint">Main headline shown in the white body of the carousel card.</div>
                    </div>

                    <div class="form-group">
                        <label for="description">Card Description (Author & Read Time Summary)</label>
                        <input type="text" id="description" name="description" class="form-control" placeholder="e.g. Nilesh Biniwale  ·  6 min read" value="<?= htmlspecialchars($editItem['description'] ?? '') ?>">
                        <div class="form-hint">Displayed as slate-colored meta info at the bottom of the card.</div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Card Banner Theme Color</label>
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
                            <div style="display: flex; gap: 16px; align-items: center; margin-top: 4px;">
                                <div>
                                    <span style="font-size: 12px; color: #64748B;">Text:</span>
                                    <input type="color" id="banner_text_color" name="banner_text_color" class="color-picker-input" value="<?= htmlspecialchars($editItem['banner_text_color'] ?? '#FFFFFF') ?>">
                                </div>
                                <div>
                                    <span style="font-size: 12px; color: #64748B;">Badge:</span>
                                    <input type="color" id="badge_color" name="badge_color" class="color-picker-input" value="<?= htmlspecialchars($editItem['badge_color'] ?? '#FFD200') ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2A: External Link Details (Only for External Type) -->
                <div id="section-external" style="display: <?= $currentType === 'external' ? 'block' : 'none' ?>; background: #FFFBEB; border: 1px solid #FCD34D; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    <div style="font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 16px; color: #92400E; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        <span>2. External Publication Link</span>
                    </div>

                    <div class="form-group">
                        <label for="external_url" style="color: #92400E;">Destination External URL *</label>
                        <input type="url" id="external_url" name="external_url" class="form-control" placeholder="https://www.thoughtworks.com/perspectives/..." value="<?= htmlspecialchars(($currentType === 'external') ? ($editItem['link_url'] ?? '') : '') ?>">
                        <div class="form-hint">Users who click this card on the homepage carousel will be redirected directly to this URL in a new tab.</div>
                    </div>

                    <div class="form-group">
                        <label for="link_text_ext">Button / Action Text</label>
                        <input type="text" id="link_text_ext" name="link_text" class="form-control" placeholder="Read on thoughtworks.com ↗" value="<?= htmlspecialchars($editItem['link_text'] ?? 'Read on website ↗') ?>">
                    </div>
                </div>

                <!-- SECTION 2B: Internal Detail Page Blog Management (Only for Internal Type) -->
                <div id="section-internal" style="display: <?= $currentType === 'internal' ? 'block' : 'none' ?>;">
                    
                    <!-- URL & Subtitle -->
                    <div style="background: #FFFFFF; border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                        <div style="font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 16px; color: var(--charcoal); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-link" style="color: var(--primary);"></i>
                            <span>2. Article Detail Header & URL Slug</span>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="slug">URL Slug *</label>
                                <div style="display: flex; gap: 8px;">
                                    <input type="text" id="slug" name="slug" class="form-control" placeholder="punes-next-leap" value="<?= htmlspecialchars($editItem['slug'] ?? '') ?>">
                                    <button type="button" class="btn-secondary" onclick="generateSlugFromTitle()" title="Auto-generate slug from title">
                                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                                    </button>
                                </div>
                                <div class="form-hint">Page address: <code>insight.php?slug=<span id="slug-preview"><?= htmlspecialchars($editItem['slug'] ?? 'your-slug') ?></span></code></div>
                            </div>

                            <div class="form-group">
                                <label for="subtitle">Article Subtitle</label>
                                <input type="text" id="subtitle" name="subtitle" class="form-control" placeholder="e.g. GCC Leaders and Government Building a Global Innovation Hub" value="<?= htmlspecialchars($editItem['subtitle'] ?? '') ?>">
                                <div class="form-hint">Displayed under the main article title on the full page banner.</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="read_time">Reading Time Estimate</label>
                                <input type="text" id="read_time" name="read_time" class="form-control" placeholder="e.g. 6 min read" value="<?= htmlspecialchars($editItem['read_time'] ?? '6 min read') ?>">
                            </div>
                            <div class="form-group">
                                <label for="published_date">Publication Date</label>
                                <input type="date" id="published_date" name="published_date" class="form-control" value="<?= htmlspecialchars($editItem['published_date'] ?? date('Y-m-d')) ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="link_text_int">Carousel Button Text</label>
                            <input type="text" id="link_text_int" name="link_text_int" class="form-control" placeholder="Read the article →" value="<?= htmlspecialchars($editItem['link_text'] ?? 'Read the article →') ?>" oninput="document.getElementById('link_text_ext').value = this.value;">
                        </div>
                    </div>

                    <!-- Author Information -->
                    <div style="background: #FFFFFF; border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <div style="font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 16px; color: var(--charcoal); display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-user-pen" style="color: var(--primary);"></i>
                                <span>3. Author Details & Bottom Bio Box</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 12px; font-weight: 600; color: var(--slate);">Quick Preset:</span>
                                <select class="form-control" style="width: auto; height: 36px; font-size: 13px;" onchange="applyAuthorPreset(this.value)">
                                    <option value="">-- Choose Author Preset --</option>
                                    <option value="biniwale">Nilesh Biniwale</option>
                                    <option value="kulkarni">Nilesh Kulkarni</option>
                                    <option value="both">Both Nilesh Biniwale & Nilesh Kulkarni</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="author_name">Author Name *</label>
                                <input type="text" id="author_name" name="author_name" class="form-control" value="<?= htmlspecialchars($editItem['author_name'] ?? 'Nilesh Biniwale') ?>">
                            </div>
                            <div class="form-group">
                                <label for="author_role">Author Role / Designation</label>
                                <input type="text" id="author_role" name="author_role" class="form-control" value="<?= htmlspecialchars($editItem['author_role'] ?? 'Managing Director, Pattern  ·  Co-author, The ALT HQ') ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="author_photo">Author Photo URL or Path</label>
                                <input type="text" id="author_photo" name="author_photo" class="form-control" value="<?= htmlspecialchars($editItem['author_photo'] ?? 'assets/author-biniwale.jpg') ?>">
                                <div style="margin-top: 6px;">
                                    <span style="font-size: 12px; color: var(--slate);">Or upload new photo:</span>
                                    <input type="file" name="author_photo_file" accept="image/*" class="form-control" style="height: auto; padding: 6px 12px; margin-top: 4px;">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="author_linkedin">Author LinkedIn URL</label>
                                <input type="url" id="author_linkedin" name="author_linkedin" class="form-control" value="<?= htmlspecialchars($editItem['author_linkedin'] ?? 'https://www.linkedin.com/in/nileshbiniwale/') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="author_bio">Author Bio (for detail page bottom box)</label>
                            <textarea id="author_bio" name="author_bio" class="form-control" rows="3"><?= htmlspecialchars($editItem['author_bio'] ?? 'Managing Director of Pattern India and co-author of The ALT HQ. He built Pattern’s GCC from scratch to a team of almost 1,000.') ?></textarea>
                            <div class="form-hint">Displayed in the author card at the end of the full article.</div>
                        </div>
                    </div>

                    <!-- Featured Image -->
                    <div style="background: #FFFFFF; border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                        <div style="font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 16px; color: var(--charcoal); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-image" style="color: var(--primary);"></i>
                            <span>4. Featured Article Image (Optional)</span>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="featured_image">Featured Image URL or Path</label>
                                <input type="text" id="featured_image" name="featured_image" class="form-control" placeholder="e.g. assets/shapes-diagram.jpg" value="<?= htmlspecialchars($editItem['featured_image'] ?? '') ?>">
                                <div class="form-hint">Displayed prominently at the top of the article body.</div>
                            </div>
                            <div class="form-group">
                                <label>Upload Featured Image File</label>
                                <input type="file" name="featured_image_file" accept="image/*" class="form-control" style="height: auto; padding: 8px 12px;">
                                <div class="form-hint">Accepted formats: JPG, PNG, WebP, GIF.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Rich Content Editor -->
                    <div style="background: #FFFFFF; border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                        <div style="font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 16px; color: var(--charcoal); margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-file-lines" style="color: var(--primary);"></i>
                                <span>5. Full Article Body Content *</span>
                            </div>
                            <button type="button" class="btn-secondary" style="font-size: 12px; padding: 6px 12px;" onclick="toggleEditorPreview()">
                                <i class="fa-solid fa-eye"></i>
                                <span id="preview-toggle-text">Preview Article Body</span>
                            </button>
                        </div>
                        <div class="form-hint" style="margin-bottom: 12px;">Full HTML content is rendered on the detail page using the website typography (Montserrat headings & Inter body text). Use the quick format buttons below:</div>

                        <!-- Rich Toolbar -->
                        <div class="rich-toolbar">
                            <button type="button" class="toolbar-btn" onclick="insertTag('p')" title="Paragraph"><i class="fa-solid fa-paragraph"></i> Paragraph</button>
                            <button type="button" class="toolbar-btn" onclick="insertTag('h2')" title="Section Heading"><i class="fa-solid fa-heading"></i> Section H2</button>
                            <button type="button" class="toolbar-btn" onclick="insertTag('h3')" title="Subheading"><i class="fa-solid fa-heading" style="font-size: 10px;"></i> Subhead H3</button>
                            <button type="button" class="toolbar-btn" onclick="insertTag('strong')" title="Bold"><i class="fa-solid fa-bold"></i> Bold</button>
                            <button type="button" class="toolbar-btn" onclick="insertTag('em')" title="Italic"><i class="fa-solid fa-italic"></i> Italic</button>
                            <button type="button" class="toolbar-btn" onclick="insertQuoteSnippet()" title="Insert Pull Quote Box"><i class="fa-solid fa-quote-left"></i> Pull Quote</button>
                            <button type="button" class="toolbar-btn" onclick="insertListSnippet()" title="Bullet List"><i class="fa-solid fa-list-ul"></i> Bullet List</button>
                            <button type="button" class="toolbar-btn" onclick="insertImageSnippet()" title="Image with Caption"><i class="fa-regular fa-image"></i> Insert Image</button>
                        </div>

                        <textarea id="content" name="content" class="form-control editor-textarea" rows="16" placeholder="<p>Write your article paragraphs here...</p>"><?= htmlspecialchars($editItem['content'] ?? '') ?></textarea>
                        
                        <!-- Live Preview Container (hidden by default) -->
                        <div id="content-preview-container" style="display: none; border: 1px solid var(--border); border-radius: 8px; padding: 24px; background: #FFFFFF; margin-top: 12px;">
                            <div style="font-size: 12px; font-weight: 700; color: var(--slate); text-transform: uppercase; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">Live Content Preview</div>
                            <div id="content-preview" class="art-body" style="font-family: 'Inter', sans-serif;"></div>
                        </div>
                    </div>

                    <!-- SEO Details -->
                    <div style="background: #FFFFFF; border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                        <div style="font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 16px; color: var(--charcoal); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-magnifying-glass" style="color: var(--primary);"></i>
                            <span>6. SEO & Social Meta Details</span>
                        </div>

                        <div class="form-group">
                            <label for="seo_title">SEO Page Title</label>
                            <input type="text" id="seo_title" name="seo_title" class="form-control" placeholder="e.g. Pune’s Next Leap | The ALT HQ" value="<?= htmlspecialchars($editItem['seo_title'] ?? '') ?>">
                            <div class="form-hint">Defaults to Article Title if left blank.</div>
                        </div>

                        <div class="form-group">
                            <label for="seo_description">SEO Meta Description</label>
                            <textarea id="seo_description" name="seo_description" class="form-control" rows="2" placeholder="e.g. Nilesh Biniwale on how GCC leaders and government can build Pune into a global innovation hub."><?= htmlspecialchars($editItem['seo_description'] ?? '') ?></textarea>
                            <div class="form-hint">Used for search engines and social media previews.</div>
                        </div>
                    </div>

                </div>

                <!-- Active Status -->
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding: 14px 18px; background: #F8FAFC; border-radius: 8px; border: 1px solid var(--border);">
                    <input type="checkbox" id="is_active" name="is_active" value="1" <?= ($editItem['is_active'] ?? 1) == 1 ? 'checked' : '' ?> style="width: 20px; height: 20px; cursor: pointer;">
                    <div>
                        <label for="is_active" style="margin: 0; font-weight: 700; cursor: pointer; color: var(--charcoal);">Publish Immediately</label>
                        <div style="font-size: 12px; color: var(--slate);">If checked, this card will appear in the homepage carousel and its detail page will be live.</div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div style="display: flex; justify-content: flex-end; gap: 12px; padding-top: 20px; border-top: 1px solid var(--border);">
                    <a href="insights.php" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary" style="padding: 12px 28px; font-size: 15px;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span><?= $action === 'edit' ? 'Update Insight' : 'Publish Insight' ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function selectInsightType(type) {
        document.querySelectorAll('input[name="insight_type"]').forEach(r => {
            r.checked = (r.value === type);
        });

        const cardInt = document.getElementById('card-type-internal');
        const cardExt = document.getElementById('card-type-external');
        const secInt = document.getElementById('section-internal');
        const secExt = document.getElementById('section-external');
        const extUrl = document.getElementById('external_url');
        const content = document.getElementById('content');

        if (type === 'external') {
            cardExt.classList.add('selected');
            cardInt.classList.remove('selected');
            secExt.style.display = 'block';
            secInt.style.display = 'none';
            if (extUrl) extUrl.required = true;
            if (content) content.required = false;
        } else {
            cardInt.classList.add('selected');
            cardExt.classList.remove('selected');
            secInt.style.display = 'block';
            secExt.style.display = 'none';
            if (extUrl) extUrl.required = false;
            if (content) content.required = true;
        }
    }

    function syncSlugAndSeo() {
        const titleVal = document.getElementById('title').value;
        const slugInput = document.getElementById('slug');
        if (slugInput && !slugInput.dataset.manualEdit) {
            const generated = slugify(titleVal);
            slugInput.value = generated;
            const preview = document.getElementById('slug-preview');
            if (preview) preview.textContent = generated;
        }
        const seoTitle = document.getElementById('seo_title');
        if (seoTitle && !seoTitle.dataset.manualEdit) {
            seoTitle.value = titleVal;
        }
    }

    function generateSlugFromTitle() {
        const titleVal = document.getElementById('title').value;
        const slugInput = document.getElementById('slug');
        if (slugInput) {
            slugInput.value = slugify(titleVal);
            slugInput.dataset.manualEdit = '1';
            const preview = document.getElementById('slug-preview');
            if (preview) preview.textContent = slugInput.value;
        }
    }

    document.getElementById('slug')?.addEventListener('input', function() {
        this.dataset.manualEdit = '1';
        const preview = document.getElementById('slug-preview');
        if (preview) preview.textContent = this.value;
    });

    document.getElementById('seo_title')?.addEventListener('input', function() {
        this.dataset.manualEdit = '1';
    });

    function slugify(text) {
        return text.toString().toLowerCase().trim()
            .replace(/\s+/g, '-')
            .replace(/[^\w\-]+/g, '')
            .replace(/\-\-+/g, '-')
            .replace(/^-+/, '')
            .replace(/-+$/, '');
    }

    function applyAuthorPreset(preset) {
        if (preset === 'biniwale') {
            document.getElementById('author_name').value = 'Nilesh Biniwale';
            document.getElementById('author_role').value = 'Managing Director, Pattern  ·  Co-author, The ALT HQ';
            document.getElementById('author_photo').value = 'assets/author-biniwale.jpg';
            document.getElementById('author_bio').value = 'Managing Director of Pattern India and co-author of The ALT HQ. He built Pattern’s GCC from scratch to a team of almost 1,000.';
            document.getElementById('author_linkedin').value = 'https://www.linkedin.com/in/nileshbiniwale/';
        } else if (preset === 'kulkarni') {
            document.getElementById('author_name').value = 'Nilesh Kulkarni';
            document.getElementById('author_role').value = 'Co-author, The ALT HQ';
            document.getElementById('author_photo').value = 'assets/author-kulkarni.jpg';
            document.getElementById('author_bio').value = 'Co-author of The ALT HQ and senior technology leader with over two decades in global engineering and consulting.';
            document.getElementById('author_linkedin').value = 'https://www.linkedin.com/in/nileshrk/';
        } else if (preset === 'both') {
            document.getElementById('author_name').value = 'Nilesh Biniwale and Nilesh Kulkarni';
            document.getElementById('author_role').value = 'Authors of The ALT HQ';
            document.getElementById('author_photo').value = 'assets/author-biniwale.jpg';
            document.getElementById('author_bio').value = 'Authors of The ALT HQ: GCC Beyond The Obvious (Diamond Publications).';
            document.getElementById('author_linkedin').value = 'https://www.linkedin.com/in/nileshbiniwale/';
        }
    }

    function insertTag(tag) {
        const textarea = document.getElementById('content');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selected = textarea.value.substring(start, end);
        const replacement = `<${tag}>${selected || 'Text here'}</${tag}>`;
        textarea.setRangeText(replacement, start, end, 'select');
        textarea.focus();
    }

    function insertQuoteSnippet() {
        const textarea = document.getElementById('content');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selected = textarea.value.substring(start, end) || 'Enter your important quote here...';
        const snippet = `\n<figure style="margin: 36px 0 40px; padding: 28px 32px; border-radius: 14px; background: #F8FAFC; border: 1px solid #E2E8F0; border-top: 4px solid #FFD200">\n<blockquote style="margin: 0; font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 22px; line-height: 1.45; color: #005B94">${selected}</blockquote>\n</figure>\n`;
        textarea.setRangeText(snippet, start, end, 'end');
        textarea.focus();
    }

    function insertListSnippet() {
        const textarea = document.getElementById('content');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const snippet = `\n<ul style="margin: 0 0 22px; padding-left: 22px; font-size: 18px; line-height: 1.7; color: #1E2229">\n  <li style="margin-bottom: 6px">Point one</li>\n  <li style="margin-bottom: 6px">Point two</li>\n  <li style="margin-bottom: 6px">Point three</li>\n</ul>\n`;
        textarea.setRangeText(snippet, start, end, 'end');
        textarea.focus();
    }

    function insertImageSnippet() {
        const url = prompt('Enter image URL or relative path:', 'assets/shapes-diagram.jpg');
        if (!url) return;
        const caption = prompt('Enter image caption (optional):', '');
        const textarea = document.getElementById('content');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const snippet = `\n<figure style="margin: 36px 0 40px">\n<img src="${url}" alt="${caption}" style="width: 100%; height: auto; display: block; border-radius: 12px; border: 1px solid #E2E8F0">\n${caption ? `<figcaption style="margin-top: 12px; font-size: 14px; line-height: 1.5; color: #4A5568">${caption}</figcaption>\n` : ''}</figure>\n`;
        textarea.setRangeText(snippet, start, end, 'end');
        textarea.focus();
    }

    function toggleEditorPreview() {
        const container = document.getElementById('content-preview-container');
        const preview = document.getElementById('content-preview');
        const textarea = document.getElementById('content');
        const btnText = document.getElementById('preview-toggle-text');

        if (container.style.display === 'none') {
            preview.innerHTML = textarea.value;
            container.style.display = 'block';
            btnText.textContent = 'Hide Preview';
        } else {
            container.style.display = 'none';
            btnText.textContent = 'Preview Article Body';
        }
    }

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
<?php else: ?>
    <!-- List of Insights -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <div class="admin-card-title">All Insights Articles (<?= count($insights) ?>)</div>
                <div style="font-size: 13px; color: var(--slate); margin-top: 4px;">Click any table heading to sort instantly, or use the controls below.</div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <div class="table-sort-control">
                    <i class="fa-solid fa-arrow-down-a-z" style="color: var(--primary);"></i>
                    <span style="font-size: 12px; font-weight: 700; color: var(--slate); text-transform: uppercase;">Sort:</span>
                    <select class="table-sort-select" onchange="triggerHeadingSort(this.value, this)" title="Choose sorting order">
                        <option value="order-asc" <?= ($sort === 'order' && $dir === 'asc') ? 'selected' : '' ?>>Order (1 → 9)</option>
                        <option value="order-desc" <?= ($sort === 'order' && $dir === 'desc') ? 'selected' : '' ?>>Order (9 → 1)</option>
                        <option value="title-asc" <?= ($sort === 'title' && $dir === 'asc') ? 'selected' : '' ?>>Title (A → Z)</option>
                        <option value="title-desc" <?= ($sort === 'title' && $dir === 'desc') ? 'selected' : '' ?>>Title (Z → A)</option>
                        <option value="type-asc" <?= ($sort === 'type' && $dir === 'asc') ? 'selected' : '' ?>>Type (Internal / External)</option>
                        <option value="status-desc" <?= ($sort === 'status' && $dir === 'desc') ? 'selected' : '' ?>>Active Status First</option>
                    </select>
                </div>
                <a href="insights.php?action=add" class="btn-primary">
                    <i class="fa-solid fa-plus"></i>
                    <span>Add New Insight</span>
                </a>
            </div>
        </div>
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 100px;" class="sortable <?= $sort === 'order' ? 'sort-' . $dir : '' ?>" data-sort="order" data-sort-type="number" title="Click to sort by carousel order">
                            <span class="th-sort-content">
                                <span>Order</span>
                                <span class="th-sort-icon">
                                    <i class="fa-solid <?= $sort === 'order' ? ($dir === 'asc' ? 'fa-arrow-up-wide-short' : 'fa-arrow-down-wide-short') : 'fa-sort' ?>"></i>
                                </span>
                            </span>
                        </th>
                        <th style="width: 150px;">Banner Card</th>
                        <th class="sortable <?= $sort === 'type' ? 'sort-' . $dir : '' ?>" data-sort="type" data-sort-type="text" title="Click to sort by article type">
                            <span class="th-sort-content">
                                <span>Type</span>
                                <span class="th-sort-icon">
                                    <i class="fa-solid <?= $sort === 'type' ? ($dir === 'asc' ? 'fa-arrow-up-wide-short' : 'fa-arrow-down-wide-short') : 'fa-sort' ?>"></i>
                                </span>
                            </span>
                        </th>
                        <th class="sortable <?= $sort === 'title' ? 'sort-' . $dir : '' ?>" data-sort="title" data-sort-type="text" title="Click to sort by title">
                            <span class="th-sort-content">
                                <span>Title & Destination</span>
                                <span class="th-sort-icon">
                                    <i class="fa-solid <?= $sort === 'title' ? ($dir === 'asc' ? 'fa-arrow-up-wide-short' : 'fa-arrow-down-wide-short') : 'fa-sort' ?>"></i>
                                </span>
                            </span>
                        </th>
                        <th class="sortable <?= $sort === 'headline' ? 'sort-' . $dir : '' ?>" data-sort="headline" data-sort-type="text" title="Click to sort by headline">
                            <span class="th-sort-content">
                                <span>Headline</span>
                                <span class="th-sort-icon">
                                    <i class="fa-solid <?= $sort === 'headline' ? ($dir === 'asc' ? 'fa-arrow-up-wide-short' : 'fa-arrow-down-wide-short') : 'fa-sort' ?>"></i>
                                </span>
                            </span>
                        </th>
                        <th style="width: 110px;" class="sortable <?= $sort === 'status' ? 'sort-' . $dir : '' ?>" data-sort="status" data-sort-type="number" title="Click to sort by status">
                            <span class="th-sort-content">
                                <span>Status</span>
                                <span class="th-sort-icon">
                                    <i class="fa-solid <?= $sort === 'status' ? ($dir === 'asc' ? 'fa-arrow-up-wide-short' : 'fa-arrow-down-wide-short') : 'fa-sort' ?>"></i>
                                </span>
                            </span>
                        </th>
                        <th style="width: 140px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($insights)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #64748B; padding: 32px;">No insights yet. Click "+ Add New Insight" to publish your first article!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($insights as $index => $item): ?>
                            <?php 
                            $isExt = ($item['insight_type'] === 'external' || !empty($item['is_external']));
                            $viewUrl = $isExt ? $item['link_url'] : ('../' . $item['link_url']);
                            ?>
                            <tr>
                                <td data-sort-val="<?= $item['display_order'] ?>">
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
                                    <div style="width: 140px; height: 52px; border-radius: 6px; background: <?= htmlspecialchars($item['banner_bg']) ?>; color: <?= htmlspecialchars($item['banner_text_color']) ?>; padding: 6px 10px; display: flex; flex-direction: column; justify-content: flex-end; overflow: hidden; border: 1px solid rgba(0,0,0,0.1);">
                                        <span style="font-size: 8px; font-weight: 600; text-transform: uppercase; color: <?= htmlspecialchars($item['badge_color']) ?>; white-space: nowrap; text-overflow: ellipsis; overflow: hidden;"><?= htmlspecialchars($item['top_badge']) ?></span>
                                        <span style="font-family: 'Montserrat', sans-serif; font-weight: 800; font-size: 11px; line-height: 1.1; white-space: nowrap; text-overflow: ellipsis; overflow: hidden;"><?= htmlspecialchars($item['title']) ?></span>
                                    </div>
                                </td>
                                <td data-sort-val="<?= $isExt ? 'External' : 'Internal' ?>">
                                    <?php if ($isExt): ?>
                                        <span class="badge-type external">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i> External
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-type internal">
                                            <i class="fa-solid fa-newspaper"></i> Internal
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td data-sort-val="<?= htmlspecialchars($item['title']) ?>">
                                    <div style="font-weight: 700; font-size: 15px; color: var(--charcoal);"><?= htmlspecialchars($item['title']) ?></div>
                                    <div style="display: flex; align-items: center; gap: 8px; margin-top: 3px;">
                                        <span style="font-size: 12px; font-weight: 600; color: #005B94;"><?= htmlspecialchars($item['category']) ?></span>
                                        <span style="color: #CBD5E1;">·</span>
                                        <a href="<?= htmlspecialchars($viewUrl) ?>" target="_blank" rel="noopener" style="font-size: 12px; font-weight: 600; color: var(--slate); text-decoration: none;">
                                            View <?= $isExt ? 'External ↗' : 'Article ↗' ?>
                                        </a>
                                    </div>
                                </td>
                                <td data-sort-val="<?= htmlspecialchars($item['headline']) ?>" style="max-width: 300px;">
                                    <div style="line-height: 1.35; font-size: 13px;"><?= htmlspecialchars($item['headline']) ?></div>
                                    <div style="font-size: 11px; color: #64748B; margin-top: 3px;"><?= htmlspecialchars($item['description']) ?></div>
                                </td>
                                <td data-sort-val="<?= $item['is_active'] ?>">
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
                <p style="font-size: 13px; color: #64748B; margin-top: 8px;">This will remove the article from the website carousel and unpublished the detail page.</p>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
