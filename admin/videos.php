<?php
$pageTitle = 'Manage Videos';
$activePage = 'videos';

require_once __DIR__ . '/auth.php';
requireAdminLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$uploadVideoDir = __DIR__ . '/../uploads/videos/';
$uploadThumbDir = __DIR__ . '/../uploads/thumbnails/';

if (!is_dir($uploadVideoDir)) {
    mkdir($uploadVideoDir, 0755, true);
}
if (!is_dir($uploadThumbDir)) {
    mkdir($uploadThumbDir, 0755, true);
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['post_action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($token)) {
        setFlash('error', 'Security check failed. Please try again.');
        header('Location: videos.php');
        exit;
    }

    if ($postAction === 'save') {
        $editId = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $badge = trim($_POST['badge'] ?? '');
        $metaInfo = trim($_POST['meta_info'] ?? '');
        $videoType = $_POST['video_type'] ?? 'youtube';
        $videoUrl = trim($_POST['video_url'] ?? '');
        $bannerBg = trim($_POST['banner_bg'] ?? '#005B94');
        $badgeColor = trim($_POST['badge_color'] ?? '#FFD200');
        $buttonText = trim($_POST['button_text'] ?? 'Watch video →');
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($title)) {
            setFlash('error', 'Please enter a video title.');
            header('Location: videos.php' . ($editId > 0 ? "?action=edit&id=$editId" : '?action=add'));
            exit;
        }

        // Existing paths if editing
        $existingVideoPath = '';
        $existingThumbPath = '';
        if ($editId > 0) {
            $existing = $db->prepare("SELECT video_type, video_path, thumbnail_path FROM videos WHERE id = ?");
            $existing->execute([$editId]);
            $currRow = $existing->fetch();
            if ($currRow) {
                $existingVideoPath = $currRow['video_path'];
                $existingThumbPath = $currRow['thumbnail_path'];
            }
        }

        $finalVideoPath = $existingVideoPath;
        $finalThumbPath = $existingThumbPath;

        // Process Video Upload
        if ($videoType === 'upload') {
            if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['video_file'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['mp4', 'webm', 'ogg', 'mov'];

                if (!in_array($ext, $allowed)) {
                    setFlash('error', 'Invalid video file type. Allowed: MP4, WebM, OGG, MOV.');
                    header('Location: videos.php' . ($editId > 0 ? "?action=edit&id=$editId" : '?action=add'));
                    exit;
                }

                $filename = 'video_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = $uploadVideoDir . $filename;

                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    // Remove old video file if exists and local
                    if (!empty($existingVideoPath) && strpos($existingVideoPath, 'uploads/videos/') !== false) {
                        $oldFile = __DIR__ . '/../' . $existingVideoPath;
                        if (file_exists($oldFile)) @unlink($oldFile);
                    }
                    $finalVideoPath = 'uploads/videos/' . $filename;
                } else {
                    setFlash('error', 'Failed to upload video file. Check server upload limits.');
                    header('Location: videos.php' . ($editId > 0 ? "?action=edit&id=$editId" : '?action=add'));
                    exit;
                }
            } elseif (empty($finalVideoPath)) {
                setFlash('error', 'Please select a video file to upload.');
                header('Location: videos.php' . ($editId > 0 ? "?action=edit&id=$editId" : '?action=add'));
                exit;
            }
        } else {
            // External / YouTube URL
            if (empty($videoUrl)) {
                setFlash('error', 'Please enter a valid YouTube or video URL.');
                header('Location: videos.php' . ($editId > 0 ? "?action=edit&id=$editId" : '?action=add'));
                exit;
            }
            $finalVideoPath = $videoUrl;
        }

        // Process Thumbnail Upload
        if (isset($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] === UPLOAD_ERR_OK) {
            $thumbFile = $_FILES['thumbnail_file'];
            $thumbExt = strtolower(pathinfo($thumbFile['name'], PATHINFO_EXTENSION));
            $allowedThumbs = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (!in_array($thumbExt, $allowedThumbs)) {
                setFlash('error', 'Invalid thumbnail format. Allowed: JPG, PNG, WebP.');
                header('Location: videos.php' . ($editId > 0 ? "?action=edit&id=$editId" : '?action=add'));
                exit;
            }

            $thumbName = 'thumb_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $thumbExt;
            $thumbDest = $uploadThumbDir . $thumbName;

            if (move_uploaded_file($thumbFile['tmp_name'], $thumbDest)) {
                if (!empty($existingThumbPath) && strpos($existingThumbPath, 'uploads/thumbnails/') !== false) {
                    $oldThumb = __DIR__ . '/../' . $existingThumbPath;
                    if (file_exists($oldThumb)) @unlink($oldThumb);
                }
                $finalThumbPath = 'uploads/thumbnails/' . $thumbName;
            }
        }

        // Handle Thumbnail Removal if requested
        if (isset($_POST['remove_thumbnail']) && $_POST['remove_thumbnail'] === '1') {
            if (!empty($finalThumbPath) && strpos($finalThumbPath, 'uploads/thumbnails/') !== false) {
                $oldThumb = __DIR__ . '/../' . $finalThumbPath;
                if (file_exists($oldThumb)) @unlink($oldThumb);
            }
            $finalThumbPath = '';
        }

        if ($editId > 0) {
            $stmt = $db->prepare("UPDATE videos SET title = ?, badge = ?, meta_info = ?, video_type = ?, video_path = ?, thumbnail_path = ?, banner_bg = ?, badge_color = ?, button_text = ?, display_order = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$title, $badge, $metaInfo, $videoType, $finalVideoPath, $finalThumbPath, $bannerBg, $badgeColor, $buttonText, $displayOrder, $isActive, $editId]);
            setFlash('success', 'Video updated successfully.');
        } else {
            if ($displayOrder <= 0) {
                $maxOrder = (int)$db->query("SELECT MAX(display_order) FROM videos")->fetchColumn();
                $displayOrder = $maxOrder + 1;
            }
            $stmt = $db->prepare("INSERT INTO videos (title, badge, meta_info, video_type, video_path, thumbnail_path, banner_bg, badge_color, button_text, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $badge, $metaInfo, $videoType, $finalVideoPath, $finalThumbPath, $bannerBg, $badgeColor, $buttonText, $displayOrder, $isActive]);
            setFlash('success', 'Video published successfully.');
        }
        header('Location: videos.php');
        exit;
    }

    if ($postAction === 'delete') {
        $delId = (int)($_POST['delete_id'] ?? 0);
        if ($delId > 0) {
            $stmt = $db->prepare("SELECT video_type, video_path, thumbnail_path FROM videos WHERE id = ?");
            $stmt->execute([$delId]);
            $item = $stmt->fetch();
            if ($item) {
                if ($item['video_type'] === 'upload' && !empty($item['video_path'])) {
                    $vFile = __DIR__ . '/../' . $item['video_path'];
                    if (file_exists($vFile)) @unlink($vFile);
                }
                if (!empty($item['thumbnail_path']) && strpos($item['thumbnail_path'], 'uploads/thumbnails/') !== false) {
                    $tFile = __DIR__ . '/../' . $item['thumbnail_path'];
                    if (file_exists($tFile)) @unlink($tFile);
                }
                $db->prepare("DELETE FROM videos WHERE id = ?")->execute([$delId]);
                setFlash('success', 'Video deleted successfully.');
            }
        }
        header('Location: videos.php');
        exit;
    }

    if ($postAction === 'toggle_status') {
        $toggleId = (int)($_POST['toggle_id'] ?? 0);
        if ($toggleId > 0) {
            $stmt = $db->prepare("UPDATE videos SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?");
            $stmt->execute([$toggleId]);
            setFlash('success', 'Status toggled successfully.');
        }
        header('Location: videos.php');
        exit;
    }

    if ($postAction === 'move') {
        $moveId = (int)($_POST['move_id'] ?? 0);
        $direction = $_POST['direction'] ?? '';
        
        $current = $db->prepare("SELECT id, display_order FROM videos WHERE id = ?");
        $current->execute([$moveId]);
        $currItem = $current->fetch();

        if ($currItem) {
            $currOrder = (int)$currItem['display_order'];
            if ($direction === 'up') {
                $other = $db->prepare("SELECT id, display_order FROM videos WHERE display_order < ? ORDER BY display_order DESC LIMIT 1");
                $other->execute([$currOrder]);
            } else {
                $other = $db->prepare("SELECT id, display_order FROM videos WHERE display_order > ? ORDER BY display_order ASC LIMIT 1");
                $other->execute([$currOrder]);
            }
            $otherItem = $other->fetch();

            if ($otherItem) {
                $db->prepare("UPDATE videos SET display_order = ? WHERE id = ?")->execute([$otherItem['display_order'], $currItem['id']]);
                $db->prepare("UPDATE videos SET display_order = ? WHERE id = ?")->execute([$currOrder, $otherItem['id']]);
                setFlash('success', 'Video order updated.');
            }
        }
        header('Location: videos.php');
        exit;
    }
}

// Edit item prep
$editItem = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
    if (!$editItem) {
        setFlash('error', 'Video not found.');
        header('Location: videos.php');
        exit;
    }
}

// Fetch all videos
$videos = $db->query("SELECT * FROM videos ORDER BY display_order ASC, id ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- Add / Edit Video Form -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div class="admin-card-title"><?= $action === 'edit' ? 'Edit Video' : 'Upload / Add Video' ?></div>
            <a href="videos.php" class="btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Cancel & Back</span>
            </a>
        </div>
        <div class="modal-body">
            <form method="POST" action="videos.php" enctype="multipart/form-data" id="video-upload-form">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="post_action" value="save">
                <input type="hidden" name="id" value="<?= $editItem['id'] ?? 0 ?>">

                <!-- Video Source Selection -->
                <div class="form-group" style="margin-bottom: 24px;">
                    <label style="font-size: 14px; font-weight: 700; margin-bottom: 10px;">Video Source</label>
                    <div style="display: flex; gap: 20px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
                            <input type="radio" name="video_type" value="upload" <?= ($editItem['video_type'] ?? '') === 'upload' ? 'checked' : '' ?> onchange="toggleVideoSource('upload')">
                            <i class="fa-solid fa-cloud-arrow-up" style="color: #005B94;"></i> Upload Video File (MP4, WebM)
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
                            <input type="radio" name="video_type" value="youtube" <?= ($editItem['video_type'] ?? 'youtube') !== 'upload' ? 'checked' : '' ?> onchange="toggleVideoSource('youtube')">
                            <i class="fa-brands fa-youtube" style="color: #DC2626;"></i> YouTube / External URL
                        </label>
                    </div>
                </div>

                <!-- Video File Upload Area -->
                <div id="video-upload-area" style="display: <?= ($editItem['video_type'] ?? '') === 'upload' ? 'block' : 'none' ?>; margin-bottom: 24px;">
                    <label style="font-weight: 600; font-size: 13px; margin-bottom: 8px; display: block;">Video File *</label>
                    <div class="upload-dropzone" onclick="document.getElementById('video_file_input').click()">
                        <div class="upload-icon"><i class="fa-solid fa-file-video"></i></div>
                        <div class="upload-text" id="video-upload-label">Click to select video or drag and drop here</div>
                        <div class="upload-subtext">Supported formats: MP4, WebM, OGG, MOV (Up to 256MB)</div>
                        <input type="file" id="video_file_input" name="video_file" accept="video/mp4,video/webm,video/ogg,video/quicktime" style="display: none;" onchange="handleVideoFileSelected(this)">
                    </div>
                    <?php if (!empty($editItem['video_path']) && $editItem['video_type'] === 'upload'): ?>
                        <div style="margin-top: 10px; font-size: 13px; color: #005B94; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-circle-check" style="color: #10B981;"></i> Current video: <code><?= htmlspecialchars($editItem['video_path']) ?></code>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- YouTube URL Input -->
                <div id="video-url-area" style="display: <?= ($editItem['video_type'] ?? 'youtube') !== 'upload' ? 'block' : 'none' ?>; margin-bottom: 20px;">
                    <div class="form-group">
                        <label for="video_url">YouTube or Video URL *</label>
                        <div class="input-wrapper" style="position: relative;">
                            <input type="url" id="video_url" name="video_url" class="form-control" placeholder="https://www.youtube.com/watch?v=..." value="<?= htmlspecialchars(($editItem['video_type'] ?? '') !== 'upload' ? ($editItem['video_path'] ?? '') : '') ?>">
                        </div>
                        <div class="form-hint">Supports regular YouTube links (watch?v=...), short links (youtu.be/...), or direct video stream links.</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="title">Video Title *</label>
                    <input type="text" id="title" name="title" class="form-control" required placeholder="e.g. Building the GCC of Tomorrow: Culture, Technology and Strategy" value="<?= htmlspecialchars($editItem['title'] ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="badge">Badge / Series / Channel</label>
                        <input type="text" id="badge" name="badge" class="form-control" placeholder="e.g. Podcast · CXOTalks or Interview · IIM Raipur" value="<?= htmlspecialchars($editItem['badge'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="meta_info">Speaker / Production Details</label>
                        <input type="text" id="meta_info" name="meta_info" class="form-control" placeholder="e.g. NSquare Xperts · Nilesh Biniwale" value="<?= htmlspecialchars($editItem['meta_info'] ?? '') ?>">
                    </div>
                </div>

                <!-- Thumbnail Upload -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Custom Thumbnail (Optional)</label>
                    <div style="display: flex; gap: 16px; align-items: flex-start; flex-wrap: wrap;">
                        <div class="upload-dropzone" style="flex: 1; padding: 20px;" onclick="document.getElementById('thumbnail_file_input').click()">
                            <i class="fa-solid fa-image" style="font-size: 24px; color: #005B94; margin-bottom: 6px;"></i>
                            <div class="upload-text" id="thumb-upload-label" style="font-size: 13px;">Upload Thumbnail Image</div>
                            <div class="upload-subtext">JPG, PNG, WebP (Aspect ratio approx 16:9 recommended)</div>
                            <input type="file" id="thumbnail_file_input" name="thumbnail_file" accept="image/*" style="display: none;" onchange="handleThumbFileSelected(this)">
                        </div>

                        <?php if (!empty($editItem['thumbnail_path'])): ?>
                            <div style="width: 150px; text-align: center;">
                                <img src="../<?= htmlspecialchars($editItem['thumbnail_path']) ?>" alt="Thumbnail preview" style="width: 100%; height: 90px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border);">
                                <label style="display: block; font-size: 12px; color: #DC2626; margin-top: 6px; cursor: pointer;">
                                    <input type="checkbox" name="remove_thumbnail" value="1"> Remove thumbnail
                                </label>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="form-hint">If no custom thumbnail is uploaded, the card uses the branded color banner with the yellow play icon.</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Banner Theme Color (Fallback)</label>
                        <div class="color-picker-group">
                            <input type="color" id="banner_bg" name="banner_bg" class="color-picker-input" value="<?= htmlspecialchars($editItem['banner_bg'] ?? '#005B94') ?>">
                            <select class="form-control" onchange="document.getElementById('banner_bg').value = this.value;">
                                <option value="#005B94" <?= ($editItem['banner_bg'] ?? '#005B94') === '#005B94' ? 'selected' : '' ?>>Deep Blue (#005B94)</option>
                                <option value="#0077B3" <?= ($editItem['banner_bg'] ?? '') === '#0077B3' ? 'selected' : '' ?>>Cerulean (#0077B3)</option>
                                <option value="#1E2229" <?= ($editItem['banner_bg'] ?? '') === '#1E2229' ? 'selected' : '' ?>>Charcoal (#1E2229)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="button_text">Button Link Text</label>
                        <input type="text" id="button_text" name="button_text" class="form-control" placeholder="Watch video →" value="<?= htmlspecialchars($editItem['button_text'] ?? 'Watch video →') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" id="display_order" name="display_order" class="form-control" value="<?= htmlspecialchars($editItem['display_order'] ?? count($videos) + 1) ?>">
                    </div>
                    <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 28px;">
                        <input type="checkbox" id="is_active" name="is_active" value="1" <?= ($editItem['is_active'] ?? 1) == 1 ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                        <label for="is_active" style="margin: 0; cursor: pointer; font-weight: 600;">Active (visible on website carousel)</label>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border);">
                    <a href="videos.php" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-accent" style="padding: 12px 28px;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Save & Publish Video</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function toggleVideoSource(type) {
        document.getElementById('video-upload-area').style.display = type === 'upload' ? 'block' : 'none';
        document.getElementById('video-url-area').style.display = type === 'youtube' ? 'block' : 'none';
        
        const btnText = document.getElementById('button_text');
        if (type === 'upload' && (!btnText.value || btnText.value.includes('YouTube'))) {
            btnText.value = 'Watch video →';
        } else if (type === 'youtube' && (!btnText.value || btnText.value === 'Watch video →')) {
            btnText.value = 'Watch on YouTube →';
        }
    }

    function handleVideoFileSelected(input) {
        if (input.files && input.files[0]) {
            const f = input.files[0];
            const sizeMb = (f.size / (1024 * 1024)).toFixed(1);
            document.getElementById('video-upload-label').innerHTML = `<strong style="color: #005B94;">${f.name}</strong> (${sizeMb} MB ready to upload)`;
        }
    }

    function handleThumbFileSelected(input) {
        if (input.files && input.files[0]) {
            const f = input.files[0];
            document.getElementById('thumb-upload-label').innerHTML = `<strong style="color: #005B94;">${f.name}</strong> selected`;
        }
    }
    </script>
<?php else: ?>
    <!-- List of Videos -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <div class="admin-card-title">All Videos (<?= count($videos) ?>)</div>
                <div style="font-size: 13px; color: var(--slate); margin-top: 4px;">Manage video cards displayed in the "Watch and listen" carousel section.</div>
            </div>
            <a href="videos.php?action=add" class="btn-accent">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                <span>Upload / Add Video</span>
            </a>
        </div>
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Order</th>
                        <th>Preview</th>
                        <th>Title & Speaker</th>
                        <th>Source / Type</th>
                        <th>Badge / Show</th>
                        <th>Status</th>
                        <th style="width: 180px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($videos)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #64748B; padding: 32px;">No videos uploaded yet. Click "+ Upload / Add Video" to get started!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($videos as $index => $item): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <form method="POST" action="videos.php" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="post_action" value="move">
                                            <input type="hidden" name="move_id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="direction" value="up">
                                            <button type="submit" class="btn-icon" style="width: 26px; height: 26px;" <?= $index === 0 ? 'disabled style="opacity: 0.3;"' : '' ?> title="Move Up">
                                                <i class="fa-solid fa-chevron-up" style="font-size: 10px;"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="videos.php" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="post_action" value="move">
                                            <input type="hidden" name="move_id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="direction" value="down">
                                            <button type="submit" class="btn-icon" style="width: 26px; height: 26px;" <?= $index === count($videos) - 1 ? 'disabled style="opacity: 0.3;"' : '' ?> title="Move Down">
                                                <i class="fa-solid fa-chevron-down" style="font-size: 10px;"></i>
                                            </button>
                                        </form>
                                        <span style="font-weight: 700; margin-left: 4px;"><?= $item['display_order'] ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div onclick="previewVideo('<?= htmlspecialchars($item['video_type']) ?>', '<?= htmlspecialchars($item['video_type'] === 'upload' ? '../' . $item['video_path'] : $item['video_path']) ?>', '<?= addslashes(htmlspecialchars($item['title'])) ?>')" style="width: 110px; height: 60px; border-radius: 6px; <?= !empty($item['thumbnail_path']) ? "background-image: url('../" . htmlspecialchars($item['thumbnail_path']) . "'); background-size: cover; background-position: center;" : "background: " . htmlspecialchars($item['banner_bg']) . ";" ?> display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" title="Click to play preview">
                                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #FFD200; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,0.2);">
                                            <i class="fa-solid fa-play" style="color: #1E2229; font-size: 12px; margin-left: 2px;"></i>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; font-size: 14px; max-width: 280px;"><?= htmlspecialchars($item['title']) ?></div>
                                    <div style="font-size: 12px; color: #64748B; margin-top: 3px;"><?= htmlspecialchars($item['meta_info']) ?></div>
                                </td>
                                <td>
                                    <?php if ($item['video_type'] === 'upload'): ?>
                                        <span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 6px; background: #D1FAE5; color: #065F46; font-size: 12px; font-weight: 600;">
                                            <i class="fa-solid fa-cloud-arrow-up"></i> Uploaded MP4
                                        </span>
                                    <?php else: ?>
                                        <span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 6px; background: #FEE2E2; color: #991B1B; font-size: 12px; font-weight: 600;">
                                            <i class="fa-brands fa-youtube"></i> YouTube
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-size: 13px; font-weight: 600; color: #005B94;"><?= htmlspecialchars($item['badge']) ?></span>
                                </td>
                                <td>
                                    <form method="POST" action="videos.php" style="display: inline;">
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
                                        <button type="button" class="btn-icon" title="Play Preview" onclick="previewVideo('<?= htmlspecialchars($item['video_type']) ?>', '<?= htmlspecialchars($item['video_type'] === 'upload' ? '../' . $item['video_path'] : $item['video_path']) ?>', '<?= addslashes(htmlspecialchars($item['title'])) ?>')">
                                            <i class="fa-solid fa-play"></i>
                                        </button>
                                        <a href="videos.php?action=edit&id=<?= $item['id'] ?>" class="btn-icon" title="Edit">
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

    <!-- Admin Video Preview Lightbox Modal -->
    <div class="modal-overlay" id="admin-video-modal">
        <div class="modal-dialog" style="max-width: 800px; background: #000; border-radius: 12px; overflow: hidden;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 18px; background: #111827; color: #FFF;">
                <span id="preview-video-title" style="font-weight: 700; font-size: 14px;">Video Preview</span>
                <button type="button" onclick="closeVideoPreview()" style="background: none; border: none; color: #FFF; font-size: 20px; cursor: pointer;">&times;</button>
            </div>
            <div id="preview-video-container" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; background: #000;"></div>
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
                <p style="font-size: 13px; color: #64748B; margin-top: 8px;">If this is an uploaded video file, it will be safely removed from disk.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <form method="POST" action="videos.php" id="delete-form" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="post_action" value="delete">
                    <input type="hidden" name="delete_id" id="delete-item-id" value="">
                    <button type="submit" class="btn-danger">
                        <i class="fa-solid fa-trash-can"></i>
                        <span>Delete Video</span>
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

    function previewVideo(type, path, title) {
        document.getElementById('preview-video-title').textContent = title;
        const container = document.getElementById('preview-video-container');
        container.innerHTML = '';

        if (type === 'upload' || path.match(/\.(mp4|webm|ogg|mov)$/i)) {
            const v = document.createElement('video');
            v.src = path;
            v.controls = true;
            v.autoplay = true;
            v.style.position = 'absolute';
            v.style.top = '0';
            v.style.left = '0';
            v.style.width = '100%';
            v.style.height = '100%';
            container.appendChild(v);
        } else {
            const match = path.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/);
            if (match) {
                const ifr = document.createElement('iframe');
                ifr.src = `https://www.youtube-nocookie.com/embed/${match[1]}?autoplay=1&rel=0`;
                ifr.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
                ifr.allowFullscreen = true;
                ifr.style.position = 'absolute';
                ifr.style.top = '0';
                ifr.style.left = '0';
                ifr.style.width = '100%';
                ifr.style.height = '100%';
                ifr.style.border = '0';
                container.appendChild(ifr);
            } else {
                window.open(path, '_blank');
                return;
            }
        }
        document.getElementById('admin-video-modal').classList.add('active');
    }

    function closeVideoPreview() {
        const modal = document.getElementById('admin-video-modal');
        modal.classList.remove('active');
        document.getElementById('preview-video-container').innerHTML = '';
    }

    document.getElementById('admin-video-modal')?.addEventListener('click', function(e) {
        if (e.target === this) closeVideoPreview();
    });
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

