<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace CMS;

use CMS\Controllers\AdminController;
use CMS\Controllers\BlogController;
use CMS\Controllers\ContactController;
use CMS\Controllers\PageController;
use CMS\Controllers\ProductController;
use Symfony\Component\Yaml\Yaml;

class Router
{
    private FileHandler $fileHandler;
    private array $menuConfig;
    private array $routes;

    private PageController $pageController;
    private BlogController $blogController;
    private ProductController $productController;
    private ContactController $contactController;
    private AdminController $adminController;

    public function __construct(FileHandler $fileHandler)
    {
        $this->fileHandler = $fileHandler;
        $this->menuConfig = $this->loadMenuConfig();
        $this->routes = $this->buildRoutes();

        $this->pageController = new PageController($fileHandler);
        $this->blogController = new BlogController($fileHandler);
        $this->productController = new ProductController($fileHandler);
        $this->contactController = new ContactController($fileHandler);
        $this->adminController = new AdminController($fileHandler);
    }

    private function loadMenuConfig(): array
    {
        try {
            $yamlContent = $this->fileHandler->read('content/config/menu.yaml');
            $config = Yaml::parse($yamlContent);
            return $config['items'] ?? [];
        } catch (\Exception $e) {
            return [
                ['type' => 'blog', 'label' => '部落格', 'menu_num' => 1, 'enabled' => true],
                ['type' => 'products', 'label' => '產品', 'menu_num' => 2, 'enabled' => true],
            ];
        }
    }

    private function buildRoutes(): array
    {
        $routes = [];

        $routes['/^\/$/'] = 'home';
        $routes['/^\/contact$/'] = 'contact';
        $routes['/^\/pages\/([a-z0-9-]+)$/'] = 'page';

        foreach ($this->menuConfig as $item) {
            if (!($item['enabled'] ?? false)) {
                continue;
            }

            $type = $item['type'] ?? '';

            if (strpos($type, 'page:') === 0) {
                $slug = substr($type, 5);
                $routes['/^\/pages\/' . preg_quote($slug, '/') . '$/'] = 'page';
                continue;
            }

            switch ($type) {
                case 'blog':
                    $routes['/^\/blog$/'] = 'blog_list';
                    $routes['/^\/blog\/([a-z0-9-]+)$/'] = 'blog_post';
                    break;
                case 'products':
                    $routes['/^\/products$/'] = 'products_list';
                    $routes['/^\/products\/([a-z0-9-]+)$/'] = 'product';
                    break;
                case 'pages':
                    $routes['/^\/pages\/([a-z0-9-]+)$/'] = 'page';
                    foreach ($item['pages'] ?? [] as $pageSlug) {
                        $routes['/^\/' . preg_quote($pageSlug, '/') . '$/'] = 'page_' . $pageSlug;
                    }
                    break;
            }
        }

        $routes['/^\/admin$/'] = 'admin_index';
        $routes['/^\/admin\/dashboard$/'] = 'admin_dashboard';
        $routes['/^\/admin\/login$/'] = 'admin_login';
        $routes['/^\/admin\/logout$/'] = 'admin_logout';
        $routes['/^\/admin\/pages$/'] = 'admin_pages';
        $routes['/^\/admin\/blog$/'] = 'admin_blog';
        $routes['/^\/admin\/signature$/'] = 'admin_signature';
        $routes['/^\/admin\/products$/'] = 'admin_products';
        $routes['/^\/admin\/messages$/'] = 'admin_messages';
        $routes['/^\/admin\/settings$/'] = 'admin_settings';
        $routes['/^\/admin\/themes$/'] = 'admin_themes';
        $routes['/^\/admin\/users$/'] = 'admin_users';
        $routes['/^\/admin\/files$/'] = 'admin_files';
        $routes['/^\/admin\/documents$/'] = 'admin_documents';

        return $routes;
    }

    public function route(string $uri): void
    {
        $uri = rtrim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        }

        foreach ($this->routes as $pattern => $handler) {
            if (preg_match($pattern, $uri, $matches)) {
                $params = array_slice($matches, 1);
                $this->dispatch($handler, $params);
                return;
            }
        }

        $this->pageController->notFound();
    }

    private function dispatch(string $handler, array $params = []): void
    {
        match ($handler) {
            'home' => $this->pageController->handleHome(),
            'contact' => $this->contactController->handleContact(),
            'blog_list' => $this->blogController->handleBlogList(),
            'blog_post' => $this->blogController->handleBlogPost($params[0] ?? ''),
            'products_list' => $this->productController->handleProductsList(),
            'product' => $this->productController->handleProduct($params[0] ?? ''),
            'page' => $this->pageController->handlePage($params[0] ?? ''),
            'admin_index' => $this->adminController->handleAdminIndex(),
            'admin_dashboard' => $this->adminController->handleAdminDashboard(),
            'admin_login' => $this->adminController->handleAdminLogin(),
            'admin_logout' => $this->adminController->handleAdminLogout(),
            'admin_pages' => $this->adminController->handleAdminPages(),
            'admin_blog' => $this->adminController->handleAdminBlog(),
            'admin_products' => $this->adminController->handleAdminProducts(),
            'admin_messages' => $this->adminController->handleAdminMessages(),
            'admin_settings' => $this->adminController->handleAdminSettings(),
            'admin_themes' => $this->adminController->handleAdminThemes(),
            'admin_users' => $this->adminController->handleAdminUsers(),
            'admin_signature' => $this->adminController->handleAdminSignature(),
            'admin_documents' => $this->adminController->handleAdminDocuments(),
            'admin_files' => $this->adminController->handleAdminFiles(),
            default => $this->pageController->notFound(),
        };
    }
}
