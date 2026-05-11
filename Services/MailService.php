<?php

require_once __DIR__ . '/SimpleSmtpMailer.php';

class MailService {
    private $config;
    private $lastTransport = 'none';
    
    public function __construct(array $mailConfig) {
        $this->config = $mailConfig;
    }

    public function send($to, $subject, $body, $fromEmail = null, $fromName = null) {
        $this->lastTransport = 'none';
        $fromEmail = $fromEmail ?? $this->config['from_email'];
        $fromName = $fromName ?? $this->config['from_name'];

        $method = $this->config['method'] ?? 'log';

        switch ($method) {
            case 'log':
                return $this->sendViaLog($to, $subject, $body, $fromEmail, $fromName);
            case 'smtp':
                return $this->sendViaSMTP($to, $subject, $body, $fromEmail, $fromName);
            case 'php':
                return $this->sendViaPHP($to, $subject, $body, $fromEmail, $fromName);
            default:
                return $this->sendViaLog($to, $subject, $body, $fromEmail, $fromName);
        }
    }

    public function getLastTransport() {
        return $this->lastTransport;
    }

    private function sendViaLog($to, $subject, $body, $fromEmail, $fromName) {
        $this->lastTransport = 'log';
        $logsDir = __DIR__ . '/../storage/mail_logs';
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $filename = $logsDir . '/emails_' . date('Y-m-d') . '.log';
        
        $logEntry = "\n" . str_repeat('=', 80) . "\n";
        $logEntry .= "[{$timestamp}] EMAIL LOG ENTRY\n";
        $logEntry .= str_repeat('=', 80) . "\n";
        $logEntry .= "TO: {$to}\n";
        $logEntry .= "FROM: {$fromName} <{$fromEmail}>\n";
        $logEntry .= "SUBJECT: {$subject}\n";
        $logEntry .= str_repeat('-', 80) . "\n";
        $logEntry .= "{$body}\n";
        $logEntry .= str_repeat('=', 80) . "\n";

        return file_put_contents($filename, $logEntry, FILE_APPEND) !== false;
    }

    private function sendViaPHP($to, $subject, $body, $fromEmail, $fromName) {
        $this->lastTransport = 'php';
        $contentType = stripos($body, '<html') !== false ? 'text/html' : 'text/plain';
        $headers = [];
        $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
        $headers[] = 'Content-Type: ' . $contentType . '; charset=UTF-8';
        $headers[] = 'X-Mailer: PHP/' . phpversion();

        return @mail($to, $subject, $body, implode("\r\n", $headers));
    }

    private function sendViaSMTP($to, $subject, $body, $fromEmail, $fromName) {
        $smtp = $this->config['smtp'] ?? [];
        
        
        if (empty($smtp['username']) || empty($smtp['password'])) {
            return $this->sendViaLog($to, $subject, $body, $fromEmail, $fromName);
        }

        try {
            $mailer = new SimpleSmtpMailer(
                $smtp['host'] ?? 'smtp.gmail.com',
                $smtp['port'] ?? 587,
                $smtp['username'],
                $smtp['password'],
                $smtp['secure'] ?? 'tls'
            );

            $sent = $mailer->send($to, $subject, $body, $fromEmail, $fromName, stripos($body, '<html') !== false);
            if ($sent) {
                $this->lastTransport = 'smtp';
                return true;
            }

            error_log("SMTP: Real send failed for $to. A local log copy will be written.");
            $this->sendViaLog($to, $subject, $body, $fromEmail, $fromName);
            return false;

        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            $this->sendViaLog($to, $subject, $body, $fromEmail, $fromName);
            return false;
        }
    }
}
