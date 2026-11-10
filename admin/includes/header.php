<?php
require_once __DIR__ . '/../auth.php';
requireAdminLogin();

$flash = getFlash();
$csrfToken = generateCsrfToken();
$activePage = $activePage ?? 'dashboard';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin Dashboard') ?> | The ALT HQ</title>
    <link rel="icon" href="../assets/seal.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<?php require_once __DIR__ . '/sidebar.php'; ?>

<div class="admin-main">
    <header class="admin-topbar">
        <div class="topbar-left">
            <button type="button" class="menu-toggle-btn" id="sidebar-toggle" aria-label="Toggle Navigation">
                <i class="fa-solid fa-bars"></i>
            </button>
            <h1 class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
        </div>
        <div class="topbar-right">
            <a href="../index.php" target="_blank" class="btn-view-site">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                <span>View Website</span>
            </a>
            <div class="user-badge">
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)) ?></div>
                <span class="user-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>
            </div>
            <a href="logout.php" class="btn-icon" title="Log Out" style="color: #EF4444;">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
    </header>

    <div class="admin-content">
        <?php if ($flash): ?>
            <div class="flash-alert <?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
                <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <span><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>

