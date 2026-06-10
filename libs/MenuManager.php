<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace CMS;

use Symfony\Component\Yaml\Yaml;

class MenuManager
{
    private FileHandler $fileHandler;
    private array $menuConfig;
    
    public function __construct(FileHandler $fileHandler)
    {
        $this->fileHandler = $fileHandler;
        $this->menuConfig = $this->loadMenuConfig();
    }
    
    /**
     * Load menu configuration from menu.yaml
     */
    private function loadMenuConfig(): array
    {
        try {
            $yamlContent = $this->fileHandler->read('content/config/menu.yaml');
            $config = Yaml::parse($yamlContent);
            return $config['items'] ?? [];
        } catch (\Exception $e) {
            // Return default menu if config not found
            return [
                ['type' => 'blog', 'label' => '部落格', 'menu_num' => 1, 'enabled' => true],
                ['type' => 'products', 'label' => '產品', 'menu_num' => 2, 'enabled' => true],
            ];
        }
    }
    
    /**
     * Get menu items sorted by menu_num
     * 
     * @return array
     */
    public function getMenuItems(): array
    {
        $items = array_filter($this->menuConfig, function($item) {
            return $item['enabled'] ?? false;
        });
        
        // Sort by menu_num
        usort($items, function($a, $b) {
            $numA = $a['menu_num'] ?? 999;
            $numB = $b['menu_num'] ?? 999;
            return $numA <=> $numB;
        });
        
        return $items;
    }
    
    /**
     * Get menu items as array for template use
     * 
     * @return array
     */
    public function getTemplateData(): array
    {
        return $this->getMenuItems();
    }
    
    /**
     * Check if a menu type is enabled
     * 
     * @param string $type
     * @return bool
     */
    public function isEnabled(string $type): bool
    {
        foreach ($this->menuConfig as $item) {
            if (($item['type'] ?? '') === $type) {
                return $item['enabled'] ?? false;
            }
        }
        return false;
    }
    
    /**
     * Get menu label for a type
     * 
     * @param string $type
     * @return string
     */
    public function getLabel(string $type): string
    {
        foreach ($this->menuConfig as $item) {
            if (($item['type'] ?? '') === $type) {
                return $item['label'] ?? $type;
            }
        }
        return $type;
    }
    
    /**
     * Reload menu configuration (useful after updates)
     */
    public function reload(): void
    {
        $this->menuConfig = $this->loadMenuConfig();
    }
    
    /**
     * Save menu configuration
     * 
     * @param array $items
     * @return bool
     */
    public function saveMenu(array $items): bool
    {
        try {
            $yamlContent = Yaml::dump(['items' => $items], 4, 2);
            $this->fileHandler->write('content/config/menu.yaml', $yamlContent);
            $this->reload();
            return true;
        } catch (\Exception $e) {
            error_log('Menu save error: ' . $e->getMessage());
            return false;
        }
    }
}
