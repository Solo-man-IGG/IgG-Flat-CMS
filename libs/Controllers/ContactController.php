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

class ContactController extends BaseController
{
    public function handleContact(): void
    {
        $menuItems = $this->menuManager->getTemplateData();

        $settings = $this->loadSettings();
        $siteTitle = $settings['site_title'] ?? 'My Site';

        $auth = new Auth($this->fileHandler);
        $mailer = new Mailer($this->fileHandler);
        $contactHandler = new ContactHandler($this->fileHandler, $mailer);

        $success = '';
        $error = '';
        $csrfField = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $_POST['csrf_token'] ?? '';

            if (!$auth->validateCsrfToken($csrfToken)) {
                $error = 'CSRF 驗證失敗，請重新整理頁面後再試。';
            } else {
                // Rate limiting: at most one message per 30 seconds
                $lastSubmit = $_SESSION['last_contact_submit'] ?? 0;
                if (time() - $lastSubmit < 30) {
                    $error = '請勿頻繁送出訊息，請稍後再試。';
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
                        $error = '發送訊息失敗，請稍後再試。';
                    }
                }
            }
        }

        $csrfField = $auth->getCsrfField();

        require __DIR__ . '/../../templates/default/contact.php';
    }
}
