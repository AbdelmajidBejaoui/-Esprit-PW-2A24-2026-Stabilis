<?php

require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/MailService.php';
require_once __DIR__ . '/SimpleSmtpMailer.php';

class StockAlertService {
    private $mailService;
    private $simpleSmtp;
    private $config;
    private $pdo;

    public function __construct($pdo, $mailConfig = null) {
        $this->pdo = $pdo;
        $this->config = $mailConfig ?? require __DIR__ . '/../config/mail.php';
        $this->mailService = new MailService($this->config);

        if (!empty($this->config['smtp'])) {
            $this->simpleSmtp = new SimpleSmtpMailer(
                $this->config['smtp']['host'] ?? 'smtp.gmail.com',
                $this->config['smtp']['port'] ?? 587,
                $this->config['smtp']['username'] ?? '',
                $this->config['smtp']['password'] ?? '',
                $this->config['smtp']['secure'] ?? 'tls'
            );
        }
    }

    public function getLowStockProducts($threshold = null) {
        $threshold = $threshold ?? $this->config['alert_threshold'] ?? 3;
        $stmt = $this->pdo->prepare('SELECT * FROM produits WHERE stock <= ? ORDER BY stock ASC');
        $stmt->execute([$threshold]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sendLowStockAlert($recipient = null, $threshold = null) {
        $recipient = $recipient ?? $this->config['alert_recipient'];
        $threshold = $threshold ?? $this->config['alert_threshold'] ?? 3;
        $products = $this->getLowStockProducts($threshold);

        if (empty($products)) {
            return ['success' => false, 'message' => 'Aucun produit avec stock faible'];
        }

        $tracker = $this->loadAlertTracker();
        $toNotify = [];

        foreach ($products as $product) {
            $productId = (int)$product['id'];
            $stock = (int)$product['stock'];
            if (!isset($tracker[$productId]) || $tracker[$productId] !== $stock) {
                $toNotify[] = $product;
            }
        }

        if (empty($toNotify)) {
            return ['success' => false, 'message' => 'Aucun changement depuis la derniere alerte'];
        }

        $subject = 'Alerte stock faible - Stabilis';
        $body = $this->buildStockAlertEmail($toNotify, $threshold);
        $sent = $this->sendHtml($recipient, $subject, $body, 'Stabilis - Stock');

        if ($sent) {
            foreach ($toNotify as $product) {
                $tracker[(int)$product['id']] = (int)$product['stock'];
            }
            $this->saveAlertTracker($tracker);
            return ['success' => true, 'message' => 'Alerte stock envoyee avec succes', 'products_count' => count($toNotify)];
        }

        return ['success' => false, 'message' => 'Erreur lors de l envoi de l alerte'];
    }

    public function sendTestEmail($recipient = null) {
        $recipient = $recipient ?? $this->config['alert_recipient'];
        $subject = 'Email de test - Stabilis Backoffice';
        $body = $this->buildSystemEmail(
            'Email de test',
            'Ceci est un email de test envoye depuis le tableau de bord Stabilis.',
            [
                ['label' => 'Date/Heure', 'value' => date('d/m/Y H:i:s')],
                ['label' => 'Serveur', 'value' => $_SERVER['SERVER_NAME'] ?? 'N/A'],
                ['label' => 'PHP', 'value' => phpversion()],
                ['label' => 'Destinataire', 'value' => $recipient]
            ]
        );

        $sent = $this->sendHtml($recipient, $subject, $body, 'Stabilis');

        return [
            'success' => $sent,
            'message' => $sent ? 'Email de test envoye avec succes' : 'Erreur lors de l envoi de l email de test',
            'recipient' => $recipient
        ];
    }

    private function buildStockAlertEmail($products, $threshold) {
        $rows = [];
        foreach ($products as $product) {
            $rows[] = [
                'label' => $product['nom'],
                'value' => $product['stock'] . ' unite(s) restantes - ' . $product['categorie'] . ' - ' . number_format((float)$product['prix'], 2, ',', ' ') . ' EUR'
            ];
        }

        return $this->buildSystemEmail(
            'Alerte stock faible',
            'Un ou plusieurs produits sont presque termines. Seuil actuel: ' . $threshold . ' unite(s).',
            $rows,
            'Merci de reapprovisionner ces references rapidement pour garder le FrontOffice, le dashboard et les PDF coherents.'
        );
    }

    private function buildSystemEmail($title, $intro, $rows, $note = '') {
        $items = '';
        foreach ($rows as $row) {
            $items .= '<tr><td style="padding:12px;border-bottom:1px solid #EDEDE9;color:#6E6E68;font-size:12px;text-transform:uppercase;letter-spacing:.4px;">' . htmlspecialchars($row['label']) . '</td><td style="padding:12px;border-bottom:1px solid #EDEDE9;color:#2B2D2A;font-weight:600;">' . htmlspecialchars($row['value']) . '</td></tr>';
        }

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;background:#F8F9F6;font-family:Inter,Segoe UI,Arial,sans-serif;color:#2B2D2A;">'
            . '<div style="max-width:680px;margin:20px auto;background:#fff;border:1px solid #EDEDE9;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(26,77,58,.08);">'
            . '<div style="background:#1A4D3A;color:#fff;padding:34px 30px;border-bottom:5px solid #C6A15B;"><div style="font-size:28px;font-weight:700;">Stabilis</div><div style="margin-top:6px;color:#D4E6DE;">' . htmlspecialchars($title) . '</div></div>'
            . '<div style="padding:30px;"><p style="margin:0 0 22px;line-height:1.6;">' . htmlspecialchars($intro) . '</p><table style="width:100%;border-collapse:collapse;background:#FCFCFA;border:1px solid #EDEDE9;">' . $items . '</table>'
            . ($note !== '' ? '<div style="margin-top:22px;background:#E8F0E9;border-left:4px solid #3A6B4B;border-radius:12px;padding:16px;color:#1A4D3A;font-weight:600;">' . htmlspecialchars($note) . '</div>' : '')
            . '</div><div style="background:#FCFCFA;border-top:1px solid #EDEDE9;padding:22px;text-align:center;color:#7A7A72;font-size:12px;">Stabilis - Gestion des stocks</div></div></body></html>';
    }

    private function sendHtml($recipient, $subject, $body, $fromName) {
        if ($this->simpleSmtp && ($this->config['method'] ?? '') === 'smtp') {
            $sent = $this->simpleSmtp->send(
                $recipient,
                $subject,
                $body,
                $this->config['from_email'] ?? 'stabilisatyourservice@gmail.com',
                $fromName,
                true
            );
            if ($sent) {
                return true;
            }
        }

        return $this->mailService->send(
            $recipient,
            $subject,
            $body,
            $this->config['from_email'] ?? 'stabilisatyourservice@gmail.com',
            $fromName
        );
    }

    private function getTrackerPath() {
        $storageDir = __DIR__ . '/../storage';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        return $storageDir . '/stock_alert_tracker.json';
    }

    private function loadAlertTracker() {
        $path = $this->getTrackerPath();
        if (!file_exists($path)) {
            return [];
        }
        $data = json_decode(file_get_contents($path), true);
        return is_array($data) ? $data : [];
    }

    private function saveAlertTracker(array $tracker) {
        file_put_contents($this->getTrackerPath(), json_encode($tracker, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
?>
