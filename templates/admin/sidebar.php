    <aside class="admin-sidebar" id="adminSidebar">
        <h2><?php echo __('admin.sidebar.heading'); ?></h2>
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="<?php echo __('admin.sidebar.toggle_label'); ?>">☰</button>
        <ul>
            <li><a href="/admin/dashboard" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>"><?php echo __('admin.sidebar.dashboard'); ?></a></li>
            <li><a href="/admin/pages" class="<?php echo $currentPage === 'pages' ? 'active' : ''; ?>"><?php echo __('admin.sidebar.pages'); ?></a></li>
            <li><a href="/admin/blog" class="<?php echo $currentPage === 'blog' ? 'active' : ''; ?>"><?php echo __('admin.sidebar.blog'); ?></a></li>
            <li><a href="/admin/signature" class="<?php echo $currentPage === 'signature' ? 'active' : ''; ?>"><?php echo __('admin.sidebar.signature'); ?></a></li>
            <li><a href="/admin/products" class="<?php echo $currentPage === 'products' ? 'active' : ''; ?>"><?php echo __('admin.sidebar.products'); ?></a></li>
            <li><a href="/admin/files" class="<?php echo $currentPage === 'files' ? 'active' : ''; ?>"><?php echo __('admin.sidebar.files'); ?></a></li>
            <li><a href="/admin/messages" class="<?php echo $currentPage === 'messages' ? 'active' : ''; ?>"><?php echo __('admin.sidebar.messages'); ?></a></li>
            <li><a href="/admin/documents" class="<?php echo $currentPage === 'documents' ? 'active' : ''; ?>"><?php echo __('admin.sidebar.documents'); ?></a></li>
            <li><a href="/admin/themes" class="<?php echo $currentPage === 'themes' ? 'active' : ''; ?>"><?php echo __('admin.sidebar.themes'); ?></a></li>
            <li><a href="/admin/users" class="<?php echo $currentPage === 'users' ? 'active' : ''; ?>"><?php echo __('admin.sidebar.users'); ?></a></li>
            <li><a href="/admin/settings" class="<?php echo $currentPage === 'settings' ? 'active' : ''; ?>"><?php echo __('admin.sidebar.settings'); ?></a></li>
            <li><a href="/admin/language" class="<?php echo $currentPage === 'language' ? 'active' : ''; ?>"><?php echo __('admin.sidebar.language'); ?></a></li>
            <li><a href="/admin/logout" style="color: #f87171;"><?php echo __('admin.sidebar.logout'); ?></a></li>
        </ul>
    </aside>
