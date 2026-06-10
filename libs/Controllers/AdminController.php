<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace CMS\Controllers;

class AdminController extends BaseController
{
    public function handleAdminIndex(): void
    {
        require __DIR__ . '/../../admin/index.php';
    }

    public function handleAdminDashboard(): void
    {
        require __DIR__ . '/../../admin/dashboard.php';
    }

    public function handleAdminLogin(): void
    {
        require __DIR__ . '/../../admin/login.php';
    }

    public function handleAdminLogout(): void
    {
        require __DIR__ . '/../../admin/logout.php';
    }

    public function handleAdminPages(): void
    {
        require __DIR__ . '/../../admin/pages.php';
    }

    public function handleAdminBlog(): void
    {
        require __DIR__ . '/../../admin/blog.php';
    }

    public function handleAdminProducts(): void
    {
        require __DIR__ . '/../../admin/products.php';
    }

    public function handleAdminMessages(): void
    {
        require __DIR__ . '/../../admin/messages.php';
    }

    public function handleAdminSettings(): void
    {
        require __DIR__ . '/../../admin/settings.php';
    }

    public function handleAdminThemes(): void
    {
        require __DIR__ . '/../../admin/themes.php';
    }

    public function handleAdminUsers(): void
    {
        require __DIR__ . '/../../admin/users.php';
    }

    public function handleAdminSignature(): void
    {
        require __DIR__ . '/../../admin/signature.php';
    }

    public function handleAdminDocuments(): void
    {
        require __DIR__ . '/../../admin/documents.php';
    }

    public function handleAdminFiles(): void
    {
        require __DIR__ . '/../../admin/files.php';
    }
}
