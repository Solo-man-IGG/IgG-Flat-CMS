    <aside class="admin-sidebar">
        <h2>後台管理</h2>
        <ul>
            <li><a href="/admin/dashboard" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">儀表板</a></li>
            <li><a href="/admin/pages" class="<?php echo $currentPage === 'pages' ? 'active' : ''; ?>">頁面管理</a></li>
            <li><a href="/admin/blog" class="<?php echo $currentPage === 'blog' ? 'active' : ''; ?>">文章管理</a></li>
            <li><a href="/admin/signature" class="<?php echo $currentPage === 'signature' ? 'active' : ''; ?>">文章簽名</a></li>
            <li><a href="/admin/products" class="<?php echo $currentPage === 'products' ? 'active' : ''; ?>">產品管理</a></li>
            <li><a href="/admin/files" class="<?php echo $currentPage === 'files' ? 'active' : ''; ?>">檔案管理</a></li>
            <li><a href="/admin/messages" class="<?php echo $currentPage === 'messages' ? 'active' : ''; ?>">留言管理</a></li>
            <li><a href="/admin/documents" class="<?php echo $currentPage === 'documents' ? 'active' : ''; ?>">內部文件</a></li>
            <li><a href="/admin/themes" class="<?php echo $currentPage === 'themes' ? 'active' : ''; ?>">主題管理</a></li>
            <li><a href="/admin/users" class="<?php echo $currentPage === 'users' ? 'active' : ''; ?>">使用者管理</a></li>
            <li><a href="/admin/settings" class="<?php echo $currentPage === 'settings' ? 'active' : ''; ?>">系統設定</a></li>
            <li><a href="/admin/logout" style="color: #f87171;">登出</a></li>
        </ul>
    </aside>
