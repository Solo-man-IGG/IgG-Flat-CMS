<?php

/**
 * IgG Flat CMS - Lightweight Flat-File CMS
 * 璦閣內容管理系統
 * 開發者：SoloMan / 璦閣數位科技
 * @copyright 2026  IgG Flat CMS Authors
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace CMS;

class ContactHandler
{
    private FileHandler $fileHandler;
    private ?Mailer $mailer;
    
    public function __construct(FileHandler $fileHandler, ?Mailer $mailer = null)
    {
        $this->fileHandler = $fileHandler;
        $this->mailer = $mailer;
    }

    
    /**
     * Save a contact message
     * 
     * @param array $data Message data (name, email, subject, message)
     * @return array|false Saved message data on success, false on failure
     */
    public function saveMessage(array $data): array|false
    {
        try {
            // Validate required fields
            if (empty($data['name']) || empty($data['email']) || empty($data['subject']) || empty($data['message'])) {
                throw new \InvalidArgumentException('Missing required fields');
            }
            
            // Validate email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('Invalid email address');
            }
            
            // Sanitize data
            $messageData = [
                'id' => uniqid(),
                'name' => $this->sanitizeString($data['name']),
                'email' => $this->sanitizeString($data['email']),
                'subject' => $this->sanitizeString($data['subject']),
                'message' => $this->sanitizeString($data['message']),
                'created_at' => date('Y-m-d H:i:s'),
                'replied' => false,
                'replied_at' => null,
            ];
            
            // Generate filename based on timestamp
            $filename = 'message_' . date('YmdHis') . '_' . uniqid() . '.json';
            $filepath = 'content/messages/' . $filename;
            
            // Save message
            $this->fileHandler->write($filepath, json_encode($messageData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            return $messageData;
        } catch (\Exception $e) {
            error_log('ContactHandler save error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all messages
     * 
     * @return array List of messages
     */
    public function getAllMessages(): array
    {
        try {
            $files = $this->fileHandler->listFiles('content/messages', 'json');
            $messages = [];
            
            foreach ($files as $file) {
                $path = 'content/messages/' . $file;
                $content = $this->fileHandler->read($path);
                $message = json_decode($content, true);
                
                if ($message) {
                    $message['filename'] = $file;
                    $messages[] = $message;
                }
            }
            
            // Sort by created_at (newest first)
            usort($messages, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            return $messages;
        } catch (\Exception $e) {
            error_log('ContactHandler getMessages error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get a single message by filename
     * 
     * @param string $filename
     * @return array|null Message data or null if not found
     */
    public function getMessage(string $filename): ?array
    {
        try {
            $path = 'content/messages/' . $filename;
            $content = $this->fileHandler->read($path);
            $message = json_decode($content, true);
            
            if ($message) {
                $message['filename'] = $filename;
                return $message;
            }
            
            return null;
        } catch (\Exception $e) {
            error_log('ContactHandler getMessage error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Mark a message as replied
     * 
     * @param string $filename
     * @return bool True if successful
     */
    public function markAsReplied(string $filename): bool
    {
        try {
            $message = $this->getMessage($filename);
            
            if (!$message) {
                return false;
            }
            
            $message['replied'] = true;
            $message['replied_at'] = date('Y-m-d H:i:s');
            
            $path = 'content/messages/' . $filename;
            $this->fileHandler->write($path, json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            return true;
        } catch (\Exception $e) {
            error_log('ContactHandler markAsReplied error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a message
     * 
     * @param string $filename
     * @return bool True if successful
     */
    public function deleteMessage(string $filename): bool
    {
        try {
            $path = 'content/messages/' . $filename;
            $this->fileHandler->delete($path);
            return true;
        } catch (\Exception $e) {
            error_log('ContactHandler deleteMessage error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sanitize a string
     * 
     * @param string $string
     * @return string
     */
    private function sanitizeString(string $string): string
    {
        return strip_tags(trim($string));
    }
}
