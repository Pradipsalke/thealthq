<?php
$pageTitle = 'Newsletter Enquiries & Subscribers';
$activePage = 'enquiries';

require_once __DIR__ . '/auth.php';
requireAdminLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';

// Handle Export to CSV
if ($action === 'export') {
    $search = trim($_GET['search'] ?? '');
    $filterStatus = trim($_GET['status'] ?? '');

    $where = [];
    $params = [];
    if (!empty($search)) {
        $where[] = "(email LIKE ? OR name LIKE ? OR source LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if (!empty($filterStatus) && in_array($filterStatus, ['new', 'contacted', 'archived'])) {
        $where[] = "status = ?";
        $params[] = $filterStatus;
    }
    $whereSql = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";

    $stmt = $db->prepare("SELECT id, email, name, source, status, notes, ip_address, created_at, updated_at FROM enquiries $whereSql ORDER BY id DESC");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="the-alt-hq-enquiries-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    // UTF-8 BOM for Microsoft Excel
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID', 'Email Address', 'Name', 'Source', 'Status', 'Notes', 'IP Address', 'Subscribed At', 'Updated At']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'],
            $r['email'],
            $r['name'] ?? '',
            $r['source'] ?? 'Website Newsletter',
            ucfirst($r['status']),
            $r['notes'] ?? '',
            $r['ip_address'] ?? '',
            $r['created_at'],
            $r['updated_at']
        ]);
    }
    fclose($out);
    exit;
}

// Handle POST actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        setFlash('error', 'Security check failed. Please try again.');
        header('Location: enquiries');
        exit;
    }

    $postAction = $_POST['post_action'] ?? '';

    // Action 1: Delete Single Enquiry
    if ($postAction === 'delete') {
        $deleteId = (int)($_POST['id'] ?? 0);
        if ($deleteId > 0) {
            $stmt = $db->prepare("DELETE FROM enquiries WHERE id = ?");
            $stmt->execute([$deleteId]);
            setFlash('success', 'Enquiry #' . $deleteId . ' has been deleted.');
        }
        header('Location: enquiries');
        exit;
    }

    // Action 2: Update Status
    if ($postAction === 'update_status') {
        $targetId = (int)($_POST['id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        if ($targetId > 0 && in_array($newStatus, ['new', 'contacted', 'archived'])) {
            $stmt = $db->prepare("UPDATE enquiries SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $targetId]);
            setFlash('success', 'Status updated to "' . ucfirst($newStatus) . '".');
        }
        header('Location: enquiries');
        exit;
    }

    // Action 3: Update Notes
    if ($postAction === 'update_notes') {
        $targetId = (int)($_POST['id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        if ($targetId > 0) {
            $stmt = $db->prepare("UPDATE enquiries SET notes = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$notes, $targetId]);
            setFlash('success', 'Notes saved successfully.');
        }
        header('Location: enquiries');
        exit;
    }

    // Action 4: Bulk Actions
    if ($postAction === 'bulk_action') {
        $bulkActionType = $_POST['bulk_action_type'] ?? '';
        $selectedIds = $_POST['selected_ids'] ?? [];

        if (empty($selectedIds) || !is_array($selectedIds)) {
            setFlash('error', 'No enquiries were selected.');
            header('Location: enquiries');
            exit;
        }

        $cleanIds = array_map('intval', $selectedIds);
        $cleanIds = array_filter($cleanIds, fn($id) => $id > 0);

        if (!empty($cleanIds)) {
            $inPlaceholders = implode(',', array_fill(0, count($cleanIds), '?'));

            if ($bulkActionType === 'mark_contacted') {
                $stmt = $db->prepare("UPDATE enquiries SET status = 'contacted', updated_at = NOW() WHERE id IN ($inPlaceholders)");
                $stmt->execute($cleanIds);
                setFlash('success', count($cleanIds) . ' enquiries marked as Contacted.');
            } elseif ($bulkActionType === 'mark_new') {
                $stmt = $db->prepare("UPDATE enquiries SET status = 'new', updated_at = NOW() WHERE id IN ($inPlaceholders)");
                $stmt->execute($cleanIds);
                setFlash('success', count($cleanIds) . ' enquiries marked as New.');
            } elseif ($bulkActionType === 'mark_archived') {
                $stmt = $db->prepare("UPDATE enquiries SET status = 'archived', updated_at = NOW() WHERE id IN ($inPlaceholders)");
                $stmt->execute($cleanIds);
                setFlash('success', count($cleanIds) . ' enquiries archived.');
            } elseif ($bulkActionType === 'delete') {
                $stmt = $db->prepare("DELETE FROM enquiries WHERE id IN ($inPlaceholders)");
                $stmt->execute($cleanIds);
                setFlash('success', count($cleanIds) . ' enquiries deleted permanently.');
            }
        }

        header('Location: enquiries');
        exit;
    }
}

// Statistics counts
$totalCount = (int)$db->query("SELECT COUNT(*) FROM enquiries")->fetchColumn();
$newCount = (int)$db->query("SELECT COUNT(*) FROM enquiries WHERE status = 'new'")->fetchColumn();
$contactedCount = (int)$db->query("SELECT COUNT(*) FROM enquiries WHERE status = 'contacted'")->fetchColumn();
$archivedCount = (int)$db->query("SELECT COUNT(*) FROM enquiries WHERE status = 'archived'")->fetchColumn();

// Query Filters
$search = trim($_GET['search'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$where = [];
$params = [];
if (!empty($search)) {
    $where[] = "(email LIKE ? OR name LIKE ? OR source LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($filterStatus) && in_array($filterStatus, ['new', 'contacted', 'archived'])) {
    $where[] = "status = ?";
    $params[] = $filterStatus;
}
$whereSql = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";

$stmt = $db->prepare("SELECT * FROM enquiries $whereSql ORDER BY id DESC");
$stmt->execute($params);
$enquiries = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Enquiries Header & Stats Bar -->
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
    <div>
        <h2 style="font-family: 'Montserrat', sans-serif; font-weight: 800; font-size: 24px; color: var(--charcoal); margin: 0 0 4px 0;">Newsletter Enquiries & Subscribers</h2>
        <div style="font-size: 13px; color: var(--slate);">Review subscriber signups and manage reader enquiries stored directly in your database.</div>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="enquiries?action=export<?= !empty($filterStatus) ? '&status=' . urlencode($filterStatus) : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="btn-secondary" title="Export current list to CSV">
            <i class="fa-solid fa-file-csv" style="color: #059669;"></i>
            <span>Export to CSV</span>
        </a>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid" style="margin-bottom: 24px;">
    <div class="stat-card">
        <div class="stat-info">
            <h3>Total Submissions</h3>
            <div class="stat-number"><?= $totalCount ?></div>
        </div>
        <div class="stat-icon" style="background: #E6F3FA; color: #005B94;">
            <i class="fa-solid fa-envelopes-bulk"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <h3>New / Uncontacted</h3>
            <div class="stat-number" style="color: #D97706;"><?= $newCount ?></div>
        </div>
        <div class="stat-icon" style="background: #FEF3C7; color: #D97706;">
            <i class="fa-solid fa-envelope"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <h3>Contacted</h3>
            <div class="stat-number" style="color: #059669;"><?= $contactedCount ?></div>
        </div>
        <div class="stat-icon" style="background: #D1FAE5; color: #059669;">
            <i class="fa-solid fa-circle-check"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <h3>Archived</h3>
            <div class="stat-number" style="color: #64748B;"><?= $archivedCount ?></div>
        </div>
        <div class="stat-icon" style="background: #F1F5F9; color: #64748B;">
            <i class="fa-solid fa-box-archive"></i>
        </div>
    </div>
</div>

<!-- Filter Bar & Search -->
<div class="admin-card" style="margin-bottom: 24px; padding: 18px 24px;">
    <form method="GET" action="enquiries" style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
        <!-- Search -->
        <div style="flex: 1; min-width: 220px; position: relative;">
            <input type="text" name="search" class="form-control" placeholder="Search by email address..." value="<?= htmlspecialchars($search) ?>" style="padding-left: 36px; padding-top: 9px; padding-bottom: 9px;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 12px; color: #94A3B8;"></i>
        </div>

        <!-- Filter Status -->
        <div style="min-width: 170px;">
            <select name="status" class="form-control" style="padding-top: 9px; padding-bottom: 9px;" onchange="this.form.submit()">
                <option value="">All Statuses (<?= $totalCount ?>)</option>
                <option value="new" <?= $filterStatus === 'new' ? 'selected' : '' ?>>New (<?= $newCount ?>)</option>
                <option value="contacted" <?= $filterStatus === 'contacted' ? 'selected' : '' ?>>Contacted (<?= $contactedCount ?>)</option>
                <option value="archived" <?= $filterStatus === 'archived' ? 'selected' : '' ?>>Archived (<?= $archivedCount ?>)</option>
            </select>
        </div>

        <button type="submit" class="btn-primary" style="padding: 9px 18px;">
            <i class="fa-solid fa-filter"></i>
            <span>Filter</span>
        </button>

        <?php if (!empty($search) || !empty($filterStatus)): ?>
            <a href="enquiries" class="btn-secondary" style="padding: 9px 14px;" title="Clear search and filters">
                <i class="fa-solid fa-xmark"></i>
                <span>Reset</span>
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Main Enquiries Table Card -->
<div class="admin-card">
    <div class="admin-card-header">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div class="admin-card-title">Enquiry Submissions</div>
            <span style="font-size: 13px; color: var(--slate); font-weight: 500;">(<?= count($enquiries) ?> records)</span>
        </div>

        <!-- Bulk Action Bar (Hidden by default, shown when checkboxes are checked) -->
        <form method="POST" action="enquiries" id="bulk-action-form" style="display: none; align-items: center; gap: 10px;">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="post_action" value="bulk_action">
            <span id="selected-count-label" style="font-size: 13px; font-weight: 700; color: #005B94;">0 selected</span>
            <select name="bulk_action_type" class="form-control" style="padding: 6px 12px; font-size: 13px; width: auto;" required>
                <option value="">Choose Bulk Action...</option>
                <option value="mark_contacted">Mark as Contacted</option>
                <option value="mark_new">Mark as New</option>
                <option value="mark_archived">Mark as Archived</option>
                <option value="delete">Delete Selected</option>
            </select>
            <button type="submit" class="btn-primary" style="padding: 6px 14px; font-size: 13px;">Apply</button>
        </form>
    </div>

    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;">
                        <input type="checkbox" id="check-all" style="cursor: pointer; width: 16px; height: 16px;" title="Select all rows">
                    </th>
                    <th style="width: 60px;" class="sortable" data-sort="id" data-sort-type="number" title="Click to sort by ID">
                        <span class="th-sort-content">
                            <span>#</span>
                            <span class="th-sort-icon"><i class="fa-solid fa-sort"></i></span>
                        </span>
                    </th>
                    <th class="sortable" data-sort="email" data-sort-type="text" title="Click to sort by Email">
                        <span class="th-sort-content">
                            <span>Subscriber / Work Email</span>
                            <span class="th-sort-icon"><i class="fa-solid fa-sort"></i></span>
                        </span>
                    </th>
                    <th style="width: 140px;" class="sortable" data-sort="source" data-sort-type="text" title="Click to sort by Source">
                        <span class="th-sort-content">
                            <span>Source</span>
                            <span class="th-sort-icon"><i class="fa-solid fa-sort"></i></span>
                        </span>
                    </th>
                    <th style="width: 130px;" class="sortable" data-sort="status" data-sort-type="text" title="Click to sort by Status">
                        <span class="th-sort-content">
                            <span>Status</span>
                            <span class="th-sort-icon"><i class="fa-solid fa-sort"></i></span>
                        </span>
                    </th>
                    <th style="width: 180px;" class="sortable" data-sort="date" data-sort-type="text" title="Click to sort by Date">
                        <span class="th-sort-content">
                            <span>Subscribed On</span>
                            <span class="th-sort-icon"><i class="fa-solid fa-sort"></i></span>
                        </span>
                    </th>
                    <th>Notes</th>
                    <th style="width: 140px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($enquiries)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #64748B; padding: 48px 20px;">
                            <div style="font-size: 36px; margin-bottom: 12px; opacity: 0.5;">
                                <i class="fa-regular fa-envelope-open"></i>
                            </div>
                            <div style="font-size: 16px; font-weight: 700; color: var(--charcoal); margin-bottom: 4px;">No enquiries found</div>
                            <div style="font-size: 13px;">
                                <?= (!empty($search) || !empty($filterStatus)) ? 'Try adjusting your search query or status filter.' : 'When visitors subscribe to the newsletter on the website, they will appear here automatically.' ?>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($enquiries as $row): ?>
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" class="row-checkbox" form="bulk-action-form" name="selected_ids[]" value="<?= $row['id'] ?>" style="cursor: pointer; width: 16px; height: 16px;">
                            </td>
                            <td data-sort-val="<?= $row['id'] ?>">
                                <span style="font-weight: 700; font-size: 13px; color: #64748B;">#<?= $row['id'] ?></span>
                            </td>
                            <td data-sort-val="<?= htmlspecialchars($row['email']) ?>">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="font-weight: 700; color: var(--charcoal); font-size: 14px;"><?= htmlspecialchars($row['email']) ?></div>
                                    <button type="button" class="btn-copy-email" onclick="copyEmailToClipboard('<?= htmlspecialchars($row['email'], ENT_QUOTES) ?>', this)" title="Copy email address" style="background: none; border: none; cursor: pointer; color: #94A3B8; font-size: 12px; padding: 2px 4px; border-radius: 4px;">
                                        <i class="fa-regular fa-copy"></i>
                                    </button>
                                </div>
                                <?php if (!empty($row['name'])): ?>
                                    <div style="font-size: 12px; color: #64748B; margin-top: 2px;"><i class="fa-regular fa-user" style="font-size: 10px;"></i> <?= htmlspecialchars($row['name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td data-sort-val="<?= htmlspecialchars($row['source'] ?? 'Newsletter') ?>">
                                <span class="badge-status source">
                                    <i class="fa-regular fa-paper-plane" style="font-size: 10px;"></i>
                                    <?= htmlspecialchars($row['source'] ?? 'Newsletter') ?>
                                </span>
                            </td>
                            <td data-sort-val="<?= $row['status'] ?>">
                                <form method="POST" action="enquiries" style="margin: 0;">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="post_action" value="update_status">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <select name="status" class="form-control" onchange="this.form.submit()" style="padding: 4px 8px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer; <?= $row['status'] === 'new' ? 'background: #FEF3C7; color: #92400E; border-color: #FCD34D;' : ($row['status'] === 'contacted' ? 'background: #D1FAE5; color: #065F46; border-color: #6EE7B7;' : 'background: #F1F5F9; color: #64748B; border-color: #E2E8F0;') ?>">
                                        <option value="new" <?= $row['status'] === 'new' ? 'selected' : '' ?>>● New</option>
                                        <option value="contacted" <?= $row['status'] === 'contacted' ? 'selected' : '' ?>>● Contacted</option>
                                        <option value="archived" <?= $row['status'] === 'archived' ? 'selected' : '' ?>>● Archived</option>
                                    </select>
                                </form>
                            </td>
                            <td data-sort-val="<?= $row['created_at'] ?>">
                                <div style="font-size: 13px; font-weight: 600; color: var(--charcoal);"><?= date('M d, Y', strtotime($row['created_at'])) ?></div>
                                <div style="font-size: 11px; color: #94A3B8;"><?= date('h:i A', strtotime($row['created_at'])) ?></div>
                            </td>
                            <td style="max-width: 200px;">
                                <?php if (!empty($row['notes'])): ?>
                                    <div style="font-size: 12px; color: var(--charcoal); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($row['notes']) ?>">
                                        <?= htmlspecialchars($row['notes']) ?>
                                    </div>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: #CBD5E1; font-style: italic;">No notes</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="action-buttons" style="justify-content: flex-end;">
                                    <a href="mailto:<?= htmlspecialchars($row['email']) ?>?subject=<?= urlencode('Regarding The ALT HQ: GCC Beyond The Obvious') ?>" class="btn-icon" title="Send email to subscriber" target="_blank" style="color: #005B94;">
                                        <i class="fa-solid fa-reply"></i>
                                    </a>
                                    <button type="button" class="btn-icon" title="View / Edit Notes" onclick="openNotesModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['email'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['notes'] ?? '', ENT_QUOTES) ?>')">
                                        <i class="fa-regular fa-note-sticky"></i>
                                    </button>
                                    <button type="button" class="btn-icon" title="Delete enquiry" style="color: #DC2626;" onclick="confirmDeleteEnquiry(<?= $row['id'] ?>, '<?= htmlspecialchars($row['email'], ENT_QUOTES) ?>')">
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

<!-- Modal 1: Edit Notes Modal -->
<div class="modal-overlay" id="notesModal">
    <div class="modal-dialog" style="max-width: 480px;">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" style="font-size: 18px; margin-bottom: 2px;">Enquiry Notes</h3>
                <div style="font-size: 12px; color: var(--slate);" id="notesModalSubtitle"></div>
            </div>
            <button type="button" class="modal-close" onclick="closeNotesModal()">&times;</button>
        </div>
        <form method="POST" action="enquiries">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="post_action" value="update_notes">
            <input type="hidden" name="id" id="notesModalId">
            <div class="modal-body">
                <div class="form-group">
                    <label for="notesText">Internal Follow-up Notes</label>
                    <textarea name="notes" id="notesText" class="form-control" rows="5" placeholder="Add follow-up notes, phone call logs, or meeting notes here..."></textarea>
                    <div class="form-hint">Visible only to administrators.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeNotesModal()">Cancel</button>
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save Notes</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-dialog" style="max-width: 440px;">
        <div class="modal-header">
            <h3 class="modal-title" style="color: #DC2626;"><i class="fa-solid fa-triangle-exclamation"></i> Delete Enquiry</h3>
            <button type="button" class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <form method="POST" action="enquiries">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="post_action" value="delete">
            <input type="hidden" name="id" id="deleteEnquiryId">
            <div class="modal-body">
                <p style="font-size: 14px; line-height: 1.5; color: var(--charcoal); margin-bottom: 12px;">
                    Are you sure you want to permanently delete the enquiry record for:
                </p>
                <div style="font-weight: 700; color: #005B94; background: #F1F5F9; padding: 10px 14px; border-radius: 6px; word-break: break-all;" id="deleteEnquiryEmail"></div>
                <p style="font-size: 13px; color: #DC2626; margin-top: 12px;">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" class="btn-danger">
                    <i class="fa-solid fa-trash-can"></i>
                    <span>Delete Record</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Select All & Bulk Actions
const checkAll = document.getElementById('check-all');
const rowCheckboxes = document.querySelectorAll('.row-checkbox');
const bulkForm = document.getElementById('bulk-action-form');
const selectedCountLabel = document.getElementById('selected-count-label');

if (checkAll) {
    checkAll.addEventListener('change', function() {
        rowCheckboxes.forEach(cb => cb.checked = checkAll.checked);
        updateBulkBar();
    });
}

rowCheckboxes.forEach(cb => {
    cb.addEventListener('change', updateBulkBar);
});

function updateBulkBar() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    const count = checked.length;
    if (bulkForm) {
        if (count > 0) {
            bulkForm.style.display = 'flex';
            selectedCountLabel.textContent = count + ' selected';
        } else {
            bulkForm.style.display = 'none';
        }
    }
    if (checkAll && count === 0) {
        checkAll.checked = false;
        checkAll.indeterminate = false;
    } else if (checkAll && count === rowCheckboxes.length) {
        checkAll.checked = true;
        checkAll.indeterminate = false;
    } else if (checkAll && count > 0) {
        checkAll.indeterminate = true;
    }
}

// Copy email to clipboard
function copyEmailToClipboard(email, btn) {
    navigator.clipboard.writeText(email).then(() => {
        const icon = btn.querySelector('i');
        const origClass = icon.className;
        icon.className = 'fa-solid fa-check';
        btn.style.color = '#059669';
        setTimeout(() => {
            icon.className = origClass;
            btn.style.color = '#94A3B8';
        }, 1500);
    });
}

// Notes Modal
function openNotesModal(id, email, notes) {
    document.getElementById('notesModalId').value = id;
    document.getElementById('notesModalSubtitle').textContent = 'Subscriber: ' + email;
    document.getElementById('notesText').value = notes || '';
    document.getElementById('notesModal').classList.add('active');
}

function closeNotesModal() {
    document.getElementById('notesModal').classList.remove('active');
}

// Delete Modal
function confirmDeleteEnquiry(id, email) {
    document.getElementById('deleteEnquiryId').value = id;
    document.getElementById('deleteEnquiryEmail').textContent = email;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

// Close modals on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeNotesModal();
        closeDeleteModal();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
