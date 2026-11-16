<?php
$pageTitle = 'Edit Profile & Security';
$activePage = 'profile';

require_once __DIR__ . '/auth.php';
requireAdminLogin();

$db = getDB();
$adminId = (int)$_SESSION['admin_id'];

// Fetch current admin user
$stmt = $db->prepare("SELECT id, username, name, password_hash, created_at FROM admin_users WHERE id = ?");
$stmt->execute([$adminId]);
$adminUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$adminUser) {
    header('Location: logout.php');
    exit;
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        setFlash('error', 'Security check failed. Please try again.');
        header('Location: profile.php');
        exit;
    }

    $postAction = $_POST['post_action'] ?? '';

    // Action 1: Update Name / Profile
    if ($postAction === 'update_profile') {
        $name = trim($_POST['name'] ?? '');

        if (empty($name)) {
            setFlash('error', 'Please enter your display name.');
        } else {
            $stmt = $db->prepare("UPDATE admin_users SET name = ? WHERE id = ?");
            $stmt->execute([$name, $adminId]);

            // Update session display name
            $_SESSION['admin_name'] = $name;

            setFlash('success', 'Profile name updated successfully.');
        }
        header('Location: profile.php');
        exit;
    }

    // Action 2: Change Password
    if ($postAction === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            setFlash('error', 'Please fill in all password fields.');
        } elseif (!password_verify($currentPassword, $adminUser['password_hash'])) {
            setFlash('error', 'The current password you entered is incorrect. Please try again.');
        } elseif (strlen($newPassword) < 6) {
            setFlash('error', 'New password must be at least 6 characters long.');
        } elseif ($newPassword !== $confirmPassword) {
            setFlash('error', 'New password and confirmation do not match.');
        } else {
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$newHash, $adminId]);

            setFlash('success', 'Your password has been changed successfully.');
        }
        header('Location: profile.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 900px; margin: 0 auto;">

    <!-- Top Info Bar -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
            <h2 style="font-family: 'Montserrat', sans-serif; font-weight: 800; font-size: 24px; color: var(--charcoal); margin: 0 0 4px 0;">Account Profile & Security</h2>
            <div style="font-size: 13px; color: var(--slate);">Manage your administrator display name and change your password.</div>
        </div>
        <a href="index.php" class="btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Back to Dashboard</span>
        </a>
    </div>

    <!-- Grid: Profile Info & Password Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px;">

        <!-- Card 1: Edit Profile Name -->
        <div class="admin-card" style="margin-bottom: 0;">
            <div class="admin-card-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: #E6F3FA; color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 16px;">
                        <i class="fa-solid fa-user-pen"></i>
                    </div>
                    <div>
                        <div class="admin-card-title">Profile Information</div>
                        <div style="font-size: 12px; color: var(--slate); margin-top: 2px;">Your public administrator identity</div>
                    </div>
                </div>
            </div>
            <div class="modal-body">
                <form method="POST" action="profile.php">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="post_action" value="update_profile">

                    <div class="form-group">
                        <label for="username">Username (Sign-in Handle)</label>
                        <div style="position: relative;">
                            <input type="text" id="username" class="form-control" value="<?= htmlspecialchars($adminUser['username']) ?>" disabled style="background: #F8FAFC; color: #64748B; cursor: not-allowed; padding-left: 38px;">
                            <i class="fa-regular fa-user" style="position: absolute; left: 14px; top: 14px; color: #94A3B8;"></i>
                        </div>
                        <div class="form-hint">The username is permanent and cannot be modified.</div>
                    </div>

                    <div class="form-group">
                        <label for="name">Display Name *</label>
                        <div style="position: relative;">
                            <input type="text" id="name" name="name" class="form-control" required value="<?= htmlspecialchars($adminUser['name']) ?>" placeholder="e.g. Administrator or Your Name" style="padding-left: 38px;">
                            <i class="fa-solid fa-signature" style="position: absolute; left: 14px; top: 14px; color: #94A3B8;"></i>
                        </div>
                        <div class="form-hint">Displayed in the top navigation bar and author greetings.</div>
                    </div>

                    <div class="form-group" style="margin-bottom: 24px;">
                        <label>Account Role</label>
                        <div style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: #F8FAFC; border-radius: 8px; border: 1px solid var(--border);">
                            <span class="badge-status active">
                                <i class="fa-solid fa-shield-halved"></i> Super Administrator
                            </span>
                            <span style="font-size: 12px; color: var(--slate); margin-left: auto;">Member since <?= date('M Y', strtotime($adminUser['created_at'])) ?></span>
                        </div>
                    </div>

                    <div style="padding-top: 16px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save Profile Name</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Card 2: Change Password -->
        <div class="admin-card" style="margin-bottom: 0;">
            <div class="admin-card-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <div>
                        <div class="admin-card-title">Change Password</div>
                        <div style="font-size: 12px; color: var(--slate); margin-top: 2px;">Update your login credentials</div>
                    </div>
                </div>
            </div>
            <div class="modal-body">
                <form method="POST" action="profile.php" id="password-form">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="post_action" value="change_password">

                    <div class="form-group">
                        <label for="current_password">Current Password *</label>
                        <div style="position: relative;">
                            <input type="password" id="current_password" name="current_password" class="form-control" required placeholder="Enter current password" style="padding-left: 38px; padding-right: 40px;">
                            <i class="fa-solid fa-lock" style="position: absolute; left: 14px; top: 14px; color: #94A3B8;"></i>
                            <button type="button" class="btn-toggle-pass" onclick="togglePassVisibility('current_password', this)" style="position: absolute; right: 10px; top: 11px; background: none; border: none; cursor: pointer; color: #94A3B8; padding: 4px;">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password *</label>
                        <div style="position: relative;">
                            <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6" placeholder="Enter new password (min. 6 characters)" style="padding-left: 38px; padding-right: 40px;">
                            <i class="fa-solid fa-key" style="position: absolute; left: 14px; top: 14px; color: #94A3B8;"></i>
                            <button type="button" class="btn-toggle-pass" onclick="togglePassVisibility('new_password', this)" style="position: absolute; right: 10px; top: 11px; background: none; border: none; cursor: pointer; color: #94A3B8; padding: 4px;">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-hint">Must contain at least 6 characters.</div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password *</label>
                        <div style="position: relative;">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6" placeholder="Repeat new password" style="padding-left: 38px; padding-right: 40px;">
                            <i class="fa-solid fa-shield-halved" style="position: absolute; left: 14px; top: 14px; color: #94A3B8;"></i>
                            <button type="button" class="btn-toggle-pass" onclick="togglePassVisibility('confirm_password', this)" style="position: absolute; right: 10px; top: 11px; background: none; border: none; cursor: pointer; color: #94A3B8; padding: 4px;">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div style="padding-top: 16px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn-accent" style="padding: 10px 20px;">
                            <i class="fa-solid fa-check"></i>
                            <span>Update Password</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

</div>

<script>
function togglePassVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (!input || !icon) return;

    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-regular fa-eye-slash';
        btn.style.color = '#005B94';
    } else {
        input.type = 'password';
        icon.className = 'fa-regular fa-eye';
        btn.style.color = '#94A3B8';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
