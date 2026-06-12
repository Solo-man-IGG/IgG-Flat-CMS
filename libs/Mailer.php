<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace CMS;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private FileHandler $fileHandler;
    private array $settings;
    private string $lastError = '';
    
    public function __construct(FileHandler $fileHandler)
    {
        $this->fileHandler = $fileHandler;
        $this->settings = $this->loadSettings();
    }
    
    /**
     * Get last error message
     * 
     * @return string
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }
    
    /**
     * Load mail settings from config
     */
    private function loadSettings(): array
    {
        try {
            $settingsJson = $this->fileHandler->read('content/config/settings.json');
            $settings = json_decode($settingsJson, true);
            
            return [
                'host' => $settings['mail_host'] ?? '',
                'port' => $settings['mail_port'] ?? '587',
                'username' => $settings['mail_username'] ?? '',
                'password' => getenv('MAIL_PASSWORD') ?: ($settings['mail_password'] ?? ''),
                'from' => $settings['mail_from'] ?? '',
                'from_name' => $settings['mail_from_name'] ?? '',
                'site_title' => $settings['site_title'] ?? 'IgG Flat CMS - Lightweight Flat-File CMS',
            ];
        } catch (\Exception $e) {
            return [
                'host' => '',
                'port' => '587',
                'username' => '',
                'password' => '',
                'from' => '',
                'from_name' => '',
                'site_title' => 'IgG Flat CMS - Lightweight Flat-File CMS',
            ];
        }
    }
    
    /**
     * Reload settings
     */
    public function reloadSettings(): void
    {
        $this->settings = $this->loadSettings();
    }
    
    /**
     * Send an email
     * 
     * @param string $to Recipient email
     * @param string $toName Recipient name
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param string $altBody Alternative body (plain text)
     * @return bool True if successful
     */
    public function send(string $to, string $toName, string $subject, string $body, string $altBody = ''): bool
    {
        try {
            $mail = new PHPMailer(true);
            
            // UTF-8 encoding for Chinese characters
            mb_internal_encoding('UTF-8');
            $mail->CharSet = 'UTF-8';
            
            // Server settings
            if (!empty($this->settings['host'])) {
                $mail->isSMTP();
                $mail->Host = $this->settings['host'];
                $mail->SMTPAuth = !empty($this->settings['username']);
                $mail->Username = $this->settings['username'];
                $mail->Password = $this->settings['password'];
                
                $port = intval($this->settings['port']);
                $mail->Port = $port;

                // 自動判斷加密模式
                if ($port === 465) {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } else {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                }
                
                // Enable debugging in development
                // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            } else {
                // Use local sendmail if SMTP not configured
                $mail->isSendmail();
            }
            
            // Recipients
            $mail->setFrom($this->settings['from'] ?? 'noreply@example.com', $this->settings['from_name'] ?? 'My Site');
            $mail->addAddress($to, $toName);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = $altBody ?: strip_tags($body);
            
            // Send email
            $mail->send();
            
            $this->lastError = '';
            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log('Mailer error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send a contact form notification
     * 
     * @param array $data Contact form data
     * @return bool True if successful
     */
    public function sendContactNotification(array $data): bool
    {
        $to = $this->settings['from'] ?? 'admin@example.com';
        $toName = $this->settings['from_name'] ?? 'Admin';
        $subject = __('mailer.contact_notification.subject', $this->settings['site_title'], $data['subject']);
        
        $body = $this->generateContactEmailBody($data);
        
        return $this->send($to, $toName, $subject, $body);
    }
    
    /**
     * Send a reply to a contact message
     * 
     * @param string $to Recipient email
     * @param string $toName Recipient name
     * @param string $replyBody Reply message
     * @return bool True if successful
     */
    public function sendReply(string $to, string $toName, string $replyBody): bool
    {
        $subject = __('mailer.reply.subject', $this->settings['site_title']);
        
        $body = $this->generateReplyEmailBody($toName, $replyBody);
        
        return $this->send($to, $toName, $subject, $body);
    }
    
    /**
     * Generate email body for contact notification
     */
    private function generateContactEmailBody(array $data): string
    {
        $body = '<!DOCTYPE html>
<html lang="' . __('lang.attr') . '">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . __('mailer.contact_notification.email_title') . '</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2563eb;">' . __('mailer.contact_notification.heading') . '</h2>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">' . __('mailer.contact_notification.name_label') . '</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">' . htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">' . __('mailer.contact_notification.email_label') . '</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><a href="mailto:' . htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8') . '</a></td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">' . __('mailer.contact_notification.subject_label') . '</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">' . htmlspecialchars($data['subject'], ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">' . __('mailer.contact_notification.time_label') . '</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">' . htmlspecialchars($data['created_at'] ?? date('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
        </table>
        
        <div style="margin-top: 20px; padding: 15px; background-color: #f9fafb; border-left: 4px solid #2563eb;">
            ' . __('mailer.contact_notification.message_label') . '
            <p style="margin: 10px 0; white-space: pre-wrap;">' . htmlspecialchars($data['message'], ENT_QUOTES, 'UTF-8') . '</p>
        </div>
        
        ' . __('mailer.contact_notification.footer') . '
    </div>
</body>
</html>';
        
        return $body;
    }
    
    /**
     * Generate email body for reply
     */
    private function generateReplyEmailBody(string $toName, string $replyBody): string
    {
        $body = '<!DOCTYPE html>
<html lang="' . __('lang.attr') . '">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . __('mailer.reply.email_title') . '</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2563eb;">' . __('mailer.reply.heading') . '</h2>
        
        ' . __('mailer.reply.greeting', htmlspecialchars($toName, ENT_QUOTES, 'UTF-8')) . '
        
        <div style="margin: 20px 0; padding: 15px; background-color: #f9fafb; border-left: 4px solid #2563eb;">
            <p style="margin: 0; white-space: pre-wrap;">' . htmlspecialchars($replyBody, ENT_QUOTES, 'UTF-8') . '</p>
        </div>
        
        ' . __('mailer.reply.footer_contact') . '
        
        ' . __('mailer.reply.footer') . '
    </div>
</body>
</html>';
        
        return $body;
    }
    
    /**
     * Check if mailer is configured
     * 
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->settings['host']) && !empty($this->settings['username']);
    }
}
