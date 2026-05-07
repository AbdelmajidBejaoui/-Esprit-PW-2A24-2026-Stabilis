<?php



require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/SimpleSmtpMailer.php';
require_once __DIR__ . '/MailService.php';
require_once __DIR__ . '/InvoicePDFExporter.php';

class InvoiceService {
    private $simpleSmtp;
    private $mailService;
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

    
    public function sendInvoiceEmail($orderData, $products) {
        if (empty($orderData['email'])) {
            error_log("Invoice: No email provided");
            return false;
        }

        $subject = "Votre facture Stabilis - Commande du " . date('d/m/Y H:i', strtotime($orderData['date_commande'] ?? 'now'));
        $body = $this->buildInvoiceEmail($orderData, $products);
        $pdf = InvoicePDFExporter::generateInvoice($orderData, $products);
        $pdfName = 'Facture_Stabilis_' . ($orderData['id'] ?? date('YmdHis')) . '.pdf';
        $this->saveInvoiceCopy($pdfName, $pdf);

        
        if ($this->simpleSmtp) {
            $result = $this->simpleSmtp->sendWithAttachment(
                $orderData['email'],
                $subject,
                $body,
                $this->config['from_email'] ?? 'stabilisatyourservice@gmail.com',
                $this->config['from_name'] ?? 'Stabilis - Facture',
                $pdfName,
                $pdf
            );

            if ($result) {
                error_log("Invoice email sent successfully to: " . $orderData['email']);
                return true;
            }

            error_log("Invoice attachment send failed for: " . $orderData['email'] . ". Retrying styled email without attachment.");
        }

        if ($this->sendStyledFallback($orderData['email'], $subject, $body, 'Stabilis - Facture')) {
            error_log("Invoice fallback styled email sent successfully to: " . $orderData['email']);
            return true;
        }

        error_log("Failed to send invoice email to: " . $orderData['email']);
        return false;
    }

    
    public function sendPreOrderInvoiceEmail($orderData, $products) {
        $orderData['statut'] = $orderData['statut'] ?? 'pre-order';
        $orderData['is_pre_order'] = true;

        if (empty($orderData['email'])) {
            error_log("Pre-order invoice: No email provided");
            return false;
        }

        $subject = "Votre facture de pre-commande Stabilis - " . date('d/m/Y H:i', strtotime($orderData['date_commande'] ?? 'now'));
        $body = $this->buildInvoiceEmail($orderData, $products);
        $pdf = InvoicePDFExporter::generateInvoice($orderData, $products);
        $pdfName = 'Facture_Precommande_Stabilis_' . ($orderData['id'] ?? date('YmdHis')) . '.pdf';
        $this->saveInvoiceCopy($pdfName, $pdf);

        if ($this->simpleSmtp) {
            $result = $this->simpleSmtp->sendWithAttachment(
                $orderData['email'],
                $subject,
                $body,
                $this->config['from_email'] ?? 'stabilisatyourservice@gmail.com',
                $this->config['from_name'] ?? 'Stabilis - Pre-commande',
                $pdfName,
                $pdf
            );

            if ($result) {
                error_log("Pre-order invoice email sent successfully to: " . $orderData['email']);
                return true;
            }

            error_log("Pre-order invoice attachment send failed for: " . $orderData['email'] . ". Retrying styled email without attachment.");
        }

        if ($this->sendStyledFallback($orderData['email'], $subject, $body, 'Stabilis - Pre-commande')) {
            error_log("Pre-order invoice fallback styled email sent successfully to: " . $orderData['email']);
            return true;
        }

        error_log("Failed to send pre-order invoice email to: " . $orderData['email']);
        return false;
    }

    private function sendStyledFallback($to, $subject, $body, $fromName) {
        if ($this->simpleSmtp) {
            $sent = $this->simpleSmtp->send(
                $to,
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
            $to,
            $subject,
            $body,
            $this->config['from_email'] ?? 'stabilisatyourservice@gmail.com',
            $fromName
        );
    }

    private function saveInvoiceCopy($filename, $pdf) {
        $dir = __DIR__ . '/../storage/invoices';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $safeName = preg_replace('/[^A-Za-z0-9_.-]/', '_', $filename);
        file_put_contents($dir . '/' . $safeName, $pdf);
    }

    
    private function buildInvoiceEmail($orderData, $products) {
        $isPreOrder = !empty($orderData['is_pre_order']) || ($orderData['statut'] ?? '') === 'pre-order';
        $documentLabel = $isPreOrder ? 'Facture de pre-commande' : 'Facture de commande';
        $headerSubtitle = $isPreOrder
            ? 'Confirmation de pre-commande - Traitement prioritaire'
            : 'Facture de commande - Nutrition adaptative et durable';
        $greetingText = $isPreOrder
            ? 'Merci pour votre pre-commande. La facture PDF est jointe a cet email et votre demande sera traitee en priorite des que le stock sera disponible.'
            : 'Merci pour votre commande. La facture PDF est jointe a cet email et le resume est disponible ci-dessous.';
        $nextTitle = $isPreOrder ? 'Votre pre-commande est prioritaire' : 'Que se passe-t-il maintenant ?';
        $nextText = $isPreOrder
            ? "Votre pre-commande a ete enregistree avec priorite.<br>Des que le produit sera disponible, l'equipe Stabilis traitera votre commande avant les commandes classiques et vous recevrez un email de suivi."
            : "Votre commande a ete enregistree et est en cours de traitement.<br>\n                Vous recevrez un email de confirmation d'expedition prochainement.<br>\n                Livraison prevue dans 3 a 5 jours.";
        $totalAmount = 0;
        $totalQuantity = 0;
        $discountPercent = $orderData['discount_percent'] ?? 0;
        $discountAmount = $orderData['discount_amount'] ?? 0;
        $finalTotal = (float)($orderData['final_total'] ?? 0);

        if (!empty($products)) {
            foreach ($products as $product) {
                $quantity = $product['quantite'] ?? 0;
                $unitPrice = $product['prix'] ?? 0;
                $total = $quantity * $unitPrice;
                $totalQuantity += $quantity;
                $totalAmount += $total;
            }
        }

        if ($finalTotal <= 0) {
            $finalTotal = $totalAmount - (float)$discountAmount;
        }

        $email = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; background: #F8F9F6; color: #2B2D2A; }
        .email-container { max-width: 680px; margin: 20px auto; background: white; border: 1px solid #EDEDE9; border-radius: 16px; box-shadow: 0 8px 30px rgba(26,77,58,0.08); overflow: hidden; }
        .header { background: #1A4D3A; color: white; padding: 34px 30px; text-align: left; border-bottom: 5px solid #C6A15B; }
        .header h1 { font-size: 28px; margin-bottom: 8px; font-weight: 700; letter-spacing: 0.5px; }
        .header p { font-size: 13px; opacity: 0.95; }
        .content { padding: 34px 30px; }
        .greeting { font-size: 16px; margin-bottom: 24px; line-height: 1.6; }
        .section { margin-bottom: 32px; }
        .section-title { font-size: 13px; font-weight: 700; color: #6E6E68; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #EDEDE9; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px; }
        .info-item { font-size: 13px; }
        .info-label { color: #7A7A72; font-weight: 600; margin-bottom: 4px; }
        .info-value { color: #2B2D2A; font-weight: 500; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        thead { background: #FCFCFA; }
        th { padding: 12px; text-align: left; font-size: 11px; font-weight: 700; color: #6E6E68; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #EDEDE9; }
        td { padding: 12px; border-bottom: 1px solid #F0F0F0; font-size: 13px; }
        tr:last-child td { border-bottom: none; }
        .table-footer { background: #F9F9F7; font-weight: 600; }
        .totals { margin-top: 24px; padding-top: 20px; border-top: 2px solid #E8F0E9; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; }
        .total-row.final { font-size: 16px; font-weight: 700; color: #1A4D3A; margin-top: 12px; padding-top: 12px; border-top: 1px solid #E8F0E9; }
        .cta-section { background: #E8F0E9; padding: 22px; border-radius: 14px; text-align: left; margin: 32px 0; border-left: 4px solid #3A6B4B; }
        .cta-section h3 { color: #1A4D3A; font-size: 14px; margin-bottom: 8px; }
        .cta-section p { color: #666; font-size: 13px; line-height: 1.6; }
        .btn { display: inline-block; background: #3A6B4B; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; margin-top: 12px; }
        .btn:hover { background: #1A4D3A; }
        .footer { background: #FCFCFA; padding: 26px 30px; text-align: center; border-top: 1px solid #EDEDE9; color: #7A7A72; font-size: 12px; line-height: 1.6; }
        .footer p { margin-bottom: 8px; }
        .highlight { color: #3A6B4B; font-weight: 600; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Stabilis</h1>
            <p>' . $headerSubtitle . '</p>
        </div>

        <div class="content">
            <div class="greeting">
                Bonjour <strong>' . htmlspecialchars($orderData['prenom']) . ' ' . htmlspecialchars($orderData['nom']) . '</strong>,<br>
                ' . $greetingText . '
            </div>

            
            <div class="section">
                <div class="section-title">' . $documentLabel . '</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Numero de Commande</div>
                        <div class="info-value"><strong>' . ($orderData['id'] ?? 'N/A') . '</strong></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Date</div>
                        <div class="info-value">' . date('d/m/Y à H:i', strtotime($orderData['date_commande'] ?? 'now')) . '</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Statut</div>
                        <div class="info-value">' . htmlspecialchars($orderData['statut'] ?? 'En attente') . '</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Paiement</div>
                        <div class="info-value">' . htmlspecialchars($orderData['paiement'] ?? 'N/A') . '</div>
                    </div>
                </div>
            </div>

            
            <div class="section">
                <div class="section-title">Adresse de Livraison</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Nom</div>
                        <div class="info-value">' . htmlspecialchars($orderData['prenom'] . ' ' . $orderData['nom']) . '</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Telephone</div>
                        <div class="info-value">' . htmlspecialchars($orderData['telephone'] ?? 'N/A') . '</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Adresse</div>
                        <div class="info-value">' . htmlspecialchars($orderData['adresse']) . '</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Ville</div>
                        <div class="info-value">' . htmlspecialchars($orderData['ville'] . ' ' . $orderData['code_postal']) . '</div>
                    </div>
                </div>
            </div>

            
            <div class="section">
                <div class="section-title">Articles Commandes</div>
                <table>
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th style="text-align: center;">Quantite</th>
                            <th style="text-align: right;">Prix Unit.</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>';

        if (!empty($products)) {
            foreach ($products as $product) {
                $quantity = $product['quantite'] ?? 0;
                $unitPrice = $product['prix'] ?? 0;
                $originalPrice = $product['prix_original'] ?? $unitPrice;
                $total = $quantity * $unitPrice;
                $unitPriceDisplay = number_format($unitPrice, 2, ',', ' ') . ' EUR';
                if ((float)$originalPrice > (float)$unitPrice) {
                    $unitPriceDisplay = '<span style="text-decoration: line-through; color: #999;">' . number_format((float)$originalPrice, 2, ',', ' ') . ' EUR</span><br><strong style="color:#1A4D3A;">' . $unitPriceDisplay . '</strong>';
                }
                
                $email .= '<tr>
                            <td>' . htmlspecialchars($product['nom']) . '</td>
                            <td style="text-align: center;">' . $quantity . '</td>
                            <td style="text-align: right;">' . $unitPriceDisplay . '</td>
                            <td style="text-align: right;"><strong>' . number_format($total, 2, ',', ' ') . ' EUR</strong></td>
                        </tr>';
            }
        }

        $email .= '</tbody>
                </table>

                <div class="totals">
                    <div class="total-row">
                        <span>Sous-total:</span>
                        <span>' . number_format($totalAmount, 2, ',', ' ') . ' EUR</span>
                    </div>';

        if ($discountPercent > 0) {
            $email .= '<div class="total-row highlight">
                        <span>Reduction (-' . $discountPercent . '%):</span>
                        <span>-' . number_format($discountAmount, 2, ',', ' ') . ' EUR</span>
                    </div>';
        }

        $email .= '<div class="total-row final">
                        <span>Total Final:</span>
                        <span>' . number_format($finalTotal, 2, ',', ' ') . ' EUR</span>
                    </div>
                </div>
            </div>

            
            <div class="cta-section">
                <h3>' . $nextTitle . '</h3>
                <p>' . $nextText . '</p>
            </div>
        </div>

        <div class="footer">
            <p>Des questions sur votre commande ?</p>
            <p>Contactez-nous: <strong>stabilisatyourservice@gmail.com</strong></p>
            <p style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #EDEDE9;">Merci d\'avoir choisi Stabilis.</p>
        </div>
    </div>
</body>
</html>';

        return $email;
    }
}
