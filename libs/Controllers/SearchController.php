<?php

namespace CMS\Controllers;

use CMS\MenuManager;
use CMS\Search;

class SearchController extends BaseController
{
    public function handleSearch(): void
    {
        $menuManager = new MenuManager($this->fileHandler);
        $menuItems = $menuManager->getTemplateData();

        $settings = $this->loadSettings();
        $siteTitle = $settings['site_title'] ?? 'My Site';
        $siteSlogan = $settings['site_slogan'] ?? '';

        $query = $_GET['q'] ?? '';
        $results = [];

        if ($query) {
            $search = new Search($this->fileHandler);
            $results = $search->search($query);
        }

        require __DIR__ . '/../../templates/default/search.php';
    }
}
