<aside class="admin-sidebar" id="admin-sidebar">
    <div class="sidebar-brand">
        <img src="../assets/wordmark.png" alt="The ALT HQ">
        <div class="sidebar-brand-title">Admin Hub</div>
    </div>

    <div class="sidebar-menu">
        <div class="menu-label">Main</div>
        <a href="./" class="menu-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>

        <div class="menu-label">Manage Content</div>
        <a href="insights" class="menu-item <?= $activePage === 'insights' ? 'active' : '' ?>">
            <i class="fa-solid fa-newspaper"></i>
            <span>Insights Articles</span>
        </a>

        <a href="videos" class="menu-item <?= $activePage === 'videos' ? 'active' : '' ?>">
            <i class="fa-solid fa-video"></i>
            <span>Videos & Media</span>
        </a>

        <div class="menu-label">Audience & Leads</div>
        <a href="enquiries" class="menu-item <?= ($activePage ?? '') === 'enquiries' ? 'active' : '' ?>">
            <i class="fa-solid fa-envelope-open-text"></i>
            <span>Newsletter Enquiries</span>
            <?php
            try {
                $newEnquiryCount = (int)getDB()->query("SELECT COUNT(*) FROM enquiries WHERE status = 'new'")->fetchColumn();
                if ($newEnquiryCount > 0): ?>
                    <span style="margin-left: auto; background: #FFD200; color: #1E2229; font-size: 11px; font-weight: 800; padding: 2px 7px; border-radius: 999px;"><?= $newEnquiryCount ?></span>
                <?php endif;
            } catch (Exception $e) {}
            ?>
        </a>

        <div class="menu-label">Account</div>
        <a href="profile" class="menu-item <?= ($activePage ?? '') === 'profile' ? 'active' : '' ?>">
            <i class="fa-solid fa-user-gear"></i>
            <span>Profile & Security</span>
        </a>

        <div class="menu-label">Navigation</div>
        <a href="../" target="_blank" class="menu-item">
            <i class="fa-solid fa-globe"></i>
            <span>Visit Live Website</span>
        </a>
    </div>

    <div class="sidebar-footer">
        <div><strong>The ALT HQ</strong> v1.0</div>
        <div style="font-size: 11px; opacity: 0.75; margin-top: 2px;">Dynamic Carousel CMS</div>
    </div>
</aside>

