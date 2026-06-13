<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace CMS\Controllers;

use CMS\Auth;
use CMS\ContactHandler;
use CMS\Mailer;
use CMS\MenuManager;

class ContactController extends BaseController
{
    public function handleContact(): void
    {
        $menuManager = new MenuManager($this->fileHandler);
        $menuItems = $menuManager->getTemplateData();

        $settings = $this->loadSettings();
        $siteTitle = $settings['site_title'] ?? 'My Site';
        $siteSlogan = $settings['site_slogan'] ?? '';

        $auth = new Auth($this->fileHandler);
        $mailer = new Mailer($this->fileHandler);
        $contactHandler = new ContactHandler($this->fileHandler, $mailer);

        $success = '';
        $error = '';
        $csrfField = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $_POST['csrf_token'] ?? '';

            if (!$auth->validateCsrfToken($csrfToken)) {
                $error = __('contact.error.csrf');
            } else {
                // Rate limiting: at most one message per 30 seconds
                $lastSubmit = $_SESSION['last_contact_submit'] ?? 0;
                if (time() - $lastSubmit < 30) {
                    $error = __('contact.error.rate_limit');
                } else {
                    $data = [
                        'name' => $_POST['name'] ?? '',
                        'email' => $_POST['email'] ?? '',
                        'subject' => $_POST['subject'] ?? '',
                        'message' => $_POST['message'] ?? '',
                    ];

                    $saved = $contactHandler->saveMessage($data);
                    if ($saved !== false) {
                        $_SESSION['last_contact_submit'] = time();

                        if ($mailer->isConfigured()) {
                            $mailer->sendContactNotification($saved);
                        }

                        header('Location: /');
                        exit;
                    } else {
                        $error = __('contact.error.send_failed');
                    }
                }
            }
        }

        $csrfField = $auth->getCsrfField();

        require __DIR__ . '/../../templates/default/contact.php';
    }
}
