<aside class="admin-sidebar" id="admin-sidebar">
    <div class="sidebar-brand">
        <img src="../assets/wordmark.png" alt="The ALT HQ">
        <div class="sidebar-brand-title">Admin Hub</div>
    </div>

    <div class="sidebar-menu">
        <div class="menu-label">Main</div>
        <a href="index.php" class="menu-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>

        <div class="menu-label">Manage Content</div>
        <a href="insights.php" class="menu-item <?= $activePage === 'insights' ? 'active' : '' ?>">
            <i class="fa-solid fa-newspaper"></i>
            <span>Insights Articles</span>
        </a>

        <a href="videos.php" class="menu-item <?= $activePage === 'videos' ? 'active' : '' ?>">
            <i class="fa-solid fa-video"></i>
            <span>Videos & Media</span>
        </a>

        <div class="menu-label">Navigation</div>
        <a href="../index.php" target="_blank" class="menu-item">
            <i class="fa-solid fa-globe"></i>
            <span>Visit Live Website</span>
        </a>
    </div>

    <div class="sidebar-footer">
        <div><strong>The ALT HQ</strong> v1.0</div>
        <div style="font-size: 11px; opacity: 0.75; margin-top: 2px;">Dynamic Carousel CMS</div>
    </div>
</aside>

