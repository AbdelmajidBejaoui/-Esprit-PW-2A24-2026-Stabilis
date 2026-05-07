<?php


require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/SimpleSmtpMailer.php';
require_once __DIR__ . '/MailService.php';

class PromoEmailService {
    private $simpleSmtp;
    private $mailService;
    private $config;
    private $pdo;
    private $geminiApiKey;
    private $textsDir;

    public function __construct($pdo, $geminiApiKey, $mailConfig = null) {
        $this->pdo = $pdo;
        $this->geminiApiKey = $geminiApiKey;
        $this->config = $mailConfig ?? require __DIR__ . '/../config/mail.php';
        $this->mailService = new MailService($this->config);
        $this->textsDir = __DIR__ . '/../storage/promo_texts';
        
        if (!is_dir($this->textsDir)) {
            mkdir($this->textsDir, 0755, true);
        }
        
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

    public function generateMarketingTextWithAI($productName, $category, $price, $customerName, $customerType, $discount, $description = '', $profile = []) {
        $priceDisplay = number_format($price, 2, '.', '');
        $purchaseCount = (int)($profile['total_purchases'] ?? 0);
        $totalSpent = number_format((float)($profile['total_spent'] ?? 0), 2, '.', '');
        $favoriteCategories = $profile['categories'] ?? $category;
        
        $customerContext = match($customerType) {
            'new' => "This is a NEW customer - Create an exciting, welcoming message. They're about to discover your brand. Make them feel special and appreciated. Emphasize the $discount% welcome discount as a gesture of appreciation.",
            'regular' => "This is a REGULAR customer - They know and like your brand. Create a warm, friendly message. Thank them for coming back and show you value their continued business. Highlight the $discount% loyalty discount.",
            'loyal' => "This is a LOYAL customer - They're your best customer, 5+ purchases. Create an exclusive, premium tone. Make them feel like VIP. Offer them the $discount% exclusive discount as recognition of their loyalty.",
            default => "Create a professional, friendly marketing message."
        };
        
        $prompt = "Tu es un redacteur CRM expert pour Stabilis, une boutique e-commerce de nutrition sportive.

Objectif: ecrire un email promotionnel personnalise, utile et credible, qui donne envie d'acheter sans sonner comme un spam.

Client:
- Prenom: $customerName
- Type: $customerType
- Contexte: $customerContext
- Nombre d'achats: $purchaseCount
- Total depense: $totalSpent euros
- Categories deja achetees/interessees: $favoriteCategories

Produit:
- Nom: $productName
- Categorie: $category
- Prix: $priceDisplay euros
- Remise: $discount%
- Description produit: $description

Contraintes:
- Ecris en francais naturel
- Commence par Bonjour $customerName,
- 2 a 3 paragraphes courts, 110 a 160 mots
- Ton professionnel, chaleureux et personnalise selon le type client
- Explique concretement l'utilite du produit dans une routine sportive ou nutritionnelle
- Relie le produit aux categories ou habitudes du client quand c'est pertinent
- Mentionne la remise de $discount% naturellement, sans agressivite
- Cree une urgence douce et credible, sans pression excessive
- Termine par un appel a l'action clair
- Pas d emojis, pas de markdown, pas de liste
- Pas de promesses medicales ni garanties de resultats
- Ne donne pas de dosage, frequence ou conseil de consommation precis sauf si fourni dans la description
- Retourne uniquement le corps de l'email.";

        return $this->callGeminiAPI($prompt, [
            'productName' => $productName,
            'category' => $category,
            'customerName' => $customerName,
            'customerType' => $customerType,
            'discount' => $discount,
            'description' => $description
        ]);
    }

    public function getCustomerProfile($customerEmail) {
        $sql = "
            SELECT 
                c.email,
                c.prenom,
                COUNT(c.id) as total_purchases,
                SUM(COALESCE(NULLIF(c.final_total, 0), c.total, 0)) as total_spent,
                GROUP_CONCAT(DISTINCT p.categorie ORDER BY p.categorie SEPARATOR ', ') as categories
            FROM commandes c
            JOIN produits p ON p.id = c.produit_id
            WHERE c.email = ?
            GROUP BY c.email
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$customerEmail]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getCustomerTypeAndDiscount($customerEmail) {
        $profile = $this->getCustomerProfile($customerEmail);
        
        if (!$profile) {
            return ['type' => 'new', 'discount' => 25];
        }
        
        $purchaseCount = (int)$profile['total_purchases'];
        
        if ($purchaseCount >= 5) {
            return ['type' => 'loyal', 'discount' => 15];
        } elseif ($purchaseCount >= 2) {
            return ['type' => 'regular', 'discount' => 20];
        } else {
            return ['type' => 'new', 'discount' => 25];
        }
    }

    private function callGeminiAPI($prompt, $fallbackData = []) {
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.55,
                'topP' => 0.9,
                'maxOutputTokens' => 1024,
                'thinkingConfig' => [
                    'thinkingBudget' => 0
                ]
            ]
        ];

        $models = [
            'gemini-3-flash-preview',
            'gemini-2.5-flash',
            'gemini-flash-latest',
            'gemini-2.0-flash',
            'gemini-1.5-flash-latest'
        ];
        $endpoints = [
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=' . urlencode($this->geminiApiKey),
            'https://generativelanguage.googleapis.com/v1/models/%s:generateContent?key=' . urlencode($this->geminiApiKey)
        ];

        if (!$this->geminiApiKey || !function_exists('curl_init')) {
            return $this->generateFallbackText($fallbackData);
        }

        foreach ($models as $model) {
            foreach ($endpoints as $endpoint) {
                $url = sprintf($endpoint, rawurlencode($model));
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($curlError || $httpCode !== 200) {
                    error_log("Gemini email generation failed. Model: $model HTTP: $httpCode Error: $curlError Response: " . substr((string)$response, 0, 500));
                    continue;
                }

                $data = json_decode($response, true);
                $parts = $data['candidates'][0]['content']['parts'] ?? [];
                $text = '';
                foreach ($parts as $part) {
                    $text .= ' ' . ($part['text'] ?? '');
                }
                $text = $this->cleanGeneratedEmailText($text);

                if (strlen($text) >= 180) {
                    return $text;
                }
            }
        }

        return $this->generateFallbackText($fallbackData);
    }

    private function cleanGeneratedEmailText($text) {
        $text = trim((string)$text);
        $text = preg_replace('/^["\'`]+|["\'`]+$/', '', $text);
        $text = preg_replace('/\*\*?|#{1,6}|^-+\s*/m', '', $text);
        $text = preg_replace("/[ \t]+\n/", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }

    private function generateFallbackText($data = []) {
        error_log("Using professional fallback text generation");
        $productName = $data['productName'] ?? 'ce produit';
        $category = $data['category'] ?? 'nutrition sportive';
        $customerName = $data['customerName'] ?? 'cher client';
        $discount = (int)($data['discount'] ?? 15);
        $description = trim($data['description'] ?? '');
        $isCreatine = stripos($productName . ' ' . $description, 'creatine') !== false || stripos($productName . ' ' . $description, 'créatine') !== false;

        $customerType = $data['customerType'] ?? '';
        $relationship = match($customerType) {
            'loyal' => "Votre fidelite merite une attention particuliere, alors nous voulions vous presenter cette selection en priorite.",
            'regular' => "Comme vous connaissez deja l'univers Stabilis, cette offre peut completer naturellement votre routine actuelle.",
            'new' => "C'est une belle occasion de decouvrir Stabilis avec un produit clair, pratique et facile a integrer dans une routine active.",
            default => "Cette selection peut s'integrer facilement dans une routine sportive plus structuree."
        };
        $productContext = $description !== ''
            ? "D'apres sa fiche produit, $description"
            : "Il a ete choisi pour son usage pratique, sa place dans une routine sportive serieuse et sa simplicite au quotidien.";

        if ($isCreatine && (int)$discount > 0) {
            return "Bonjour $customerName,\n\n"
                . "$productName rejoint notre selection $category. $relationship Ce produit s'adresse surtout aux sportifs qui travaillent la force, les efforts courts et repetes, ou une progression plus reguliere dans leurs seances.\n\n"
                . "Avec votre remise de $discount%, c'est le bon moment pour ajouter un basique solide a votre programme, sans promesse excessive et avec une vraie logique de constance. Utilisez votre code promo pour profiter de l'offre pendant sa disponibilite.";
        }

        if ((int)$discount <= 0) {
            return "$productName rejoint notre selection $category. $relationship $productContext C'est une nouveaute interessante a decouvrir si vous voulez avancer avec plus de regularite, sans compliquer votre organisation nutritionnelle.";
        }

        return "Bonjour $customerName,\n\n"
            . "$productName est disponible dans notre categorie $category. $relationship $productContext\n\n"
            . "Votre remise de $discount% rend le moment interessant pour l'essayer maintenant, surtout si vous cherchez a completer votre parcours nutrition et performance avec plus de coherence. Utilisez votre code promo avant expiration pour profiter de l'offre.";
    }

    public function savePromoText($productId, $productName, $text) {
        $filename = $this->textsDir . '/promo_' . $productId . '_' . date('Y-m-d_H-i-s') . '.txt';
        
        $content = "================================================================================\n";
        $content .= "PROMO TEXT - " . date('Y-m-d H:i:s') . "\n";
        $content .= "================================================================================\n";
        $content .= "Product ID: $productId\n";
        $content .= "Product Name: $productName\n";
        $content .= "Date Created: " . date('d/m/Y H:i:s') . "\n";
        $content .= "================================================================================\n\n";
        $content .= $text;
        $content .= "\n\n================================================================================\n";
        
        $saved = file_put_contents($filename, $content);
        
        if ($saved) {
            error_log("Promo text saved: $filename");
            return $filename;
        }
        
        error_log("Failed to save promo text: $filename");
        return false;
    }

    public function getCustomersByCategory($category) {
        $sql = "
            SELECT DISTINCT c.email, c.prenom
            FROM commandes c
            JOIN produits p ON p.id = c.produit_id
            WHERE p.categorie = ? 
            AND c.email IS NOT NULL 
            AND c.email != ''
            ORDER BY c.email
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$category]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sendPromoToCustomers($productId, $productName, $category, $price, $description = '', $audience = 'all') {
        require_once __DIR__ . '/PromoCodeValidator.php';
        
        $customers = $this->getCustomersByCategory($category);
        
        if (empty($customers)) {
            error_log("No customers found for category: $category");
            return ['success' => false, 'message' => 'No customers found for this category', 'sent' => 0];
        }

        $managerEmail = $this->config['smtp']['username'] ?? 'stabilisatyourservice@gmail.com';
        $successCount = 0;
        $failureCount = 0;
        $customerDetails = [];
        $validator = new PromoCodeValidator($this->pdo);

        foreach ($customers as $customer) {
            $profile = $this->getCustomerProfile($customer['email']);
            $typeAndDiscount = $this->getCustomerTypeAndDiscount($customer['email']);
            
            $customerType = $typeAndDiscount['type'];
            $discount = $typeAndDiscount['discount'];

            if (!$this->matchesAudience($customerType, $audience)) {
                continue;
            }
            
            $marketingText = $this->generateMarketingTextWithAI(
                $productName,
                $category,
                $price,
                $customer['prenom'],
                $customerType,
                $discount,
                $description,
                $profile ?: []
            );

            if (!$marketingText) {
                error_log("Failed to generate email for: " . $customer['email']);
                $failureCount++;
                continue;
            }

            $promoCode = $this->generatePromoCode($productName, $customer['email']);
            
            $expiresAt = date('Y-m-d H:i:s', strtotime('+3 days'));
            $codeSaved = $validator->savePromoCode($promoCode, $productId, $customer['email'], $discount, $expiresAt);
            
            if (!$codeSaved) {
                error_log("Failed to save promo code: $promoCode");
                $failureCount++;
                continue;
            }
            
            $emailHtml = $this->buildHTMLEmail(
                $customer['prenom'],
                $productName,
                $category,
                $price,
                $discount,
                $promoCode,
                $marketingText,
                $customerType
            );

            $subject = match($customerType) {
                'new' => "Votre avantage Stabilis: -$discount% sur " . substr($productName, 0, 20),
                'regular' => "Nouvelle selection Stabilis: -$discount%",
                'loyal' => "Votre offre reservee Stabilis: -$discount%",
                default => "Decouvrez " . substr($productName, 0, 30)
            };
            
            $sent = $this->sendHTMLEmail(
                $customer['email'],
                $subject,
                $emailHtml,
                $managerEmail,
                'Stabilis - Promotions'
            );

            if ($sent) {
                error_log("Personalized promo email sent to: " . $customer['email'] . " (Type: $customerType, Discount: $discount%, Code: $promoCode for Product: $productId)");
                $successCount++;
                $customerDetails[] = [
                    'email' => $customer['email'],
                    'type' => $customerType,
                    'discount' => $discount,
                    'code' => $promoCode
                ];
            } else {
                error_log("Failed to send promo email to: " . $customer['email']);
                $failureCount++;
            }
        }

        return [
            'success' => $successCount > 0,
            'message' => "Sent to $successCount customer(s) | Failed: $failureCount",
            'sent' => $successCount,
            'failed' => $failureCount,
            'customers' => $customerDetails
        ];
    }

    private function generatePromoCode($productName, $email) {
        $productPrefix = strtoupper(substr($productName, 0, 6));
        $code = substr(hash('md5', $email . $productName . date('Y-m-d')), 0, 5);
        return $productPrefix . '-' . strtoupper($code);
    }

    private function buildHTMLEmail($customerName, $productName, $category, $price, $discount, $promoCode, $marketingText, $customerType) {
        $discountedPrice = number_format($price * (1 - $discount/100), 2, ',', ' ');
        $originalPrice = number_format($price, 2, ',', ' ');
        $marketingText = $this->removeEmojiCharacters($marketingText);
        
        $darkGreen = '#1A4D3A';
        $mediumGreen = '#3A6B4B';
        $lightGreen = '#E8F0E9';
        $textGray = '#2B2D2A';

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Inter, 'Segoe UI', Arial, sans-serif; margin: 0; padding: 0; background: #F8F9F6; color: $textGray; }
        .container { max-width: 680px; margin: 20px auto; background: white; border: 1px solid #EDEDE9; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 30px rgba(26,77,58,0.08); }
        .header { background: $darkGreen; color: white; padding: 34px 30px; text-align: left; border-bottom: 5px solid #C6A15B; }
        .header h1 { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: 0.5px; }
        .header p { margin: 6px 0 0; color: #D4E6DE; font-size: 13px; }
        
        .greeting { padding: 24px 30px; border-bottom: 1px solid #EDEDE9; }
        .greeting h2 { color: $darkGreen; margin: 0; font-size: 20px; font-weight: 700; }
        
        .content { padding: 30px; }
        .product-box { background: $lightGreen; padding: 22px; border-left: 4px solid $mediumGreen; margin: 20px 0; border-radius: 14px; }
        .product-name { font-size: 20px; color: $darkGreen; font-weight: 600; margin: 0; }
        .product-category { font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 1px; margin: 5px 0 10px 0; }
        
        .pricing { display: flex; gap: 20px; align-items: center; margin: 15px 0; }
        .price-old { font-size: 16px; color: #999; text-decoration: line-through; }
        .price-new { font-size: 28px; color: $darkGreen; font-weight: bold; }
        .discount { background: $darkGreen; color: white; padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        
        .description { font-size: 14px; color: $textGray; line-height: 1.5; margin: 15px 0; }
        
        .code-section { background: $darkGreen; color: white; padding: 22px; text-align: center; margin: 22px 0; border-radius: 14px; }
        .code-label { font-size: 11px; text-transform: uppercase; letter-spacing: 2px; opacity: 0.9; margin-bottom: 8px; }
        .code { font-size: 24px; font-weight: bold; font-family: 'Courier New', monospace; letter-spacing: 2px; background: rgba(255,255,255,0.1); padding: 10px; border-radius: 3px; }
        
        .cta { text-align: center; margin: 20px 0; }
        .cta-button { background: $mediumGreen; color: white; padding: 12px 35px; text-decoration: none; display: inline-block; font-size: 15px; font-weight: 600; border-radius: 999px; }
        
        .footer { padding: 22px; text-align: center; font-size: 11px; color: #7A7A72; border-top: 1px solid #EDEDE9; background: #FCFCFA; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>STABILIS</h1>
            <p>Offre personnalisee - Nutrition adaptative et durable</p>
        </div>

        <div class="greeting">
            <h2>Bienvenue, $customerName</h2>
        </div>

        <div class="content">
            <div class="product-box">
                <h3 class="product-name">$productName</h3>
                <p class="product-category">$category</p>
                
                <div class="pricing">
                    <span class="price-old">$originalPrice EUR</span>
                    <span class="price-new">$discountedPrice EUR</span>
                    <span class="discount">-$discount%</span>
                </div>
            </div>

            <p class="description">$marketingText</p>

            <div class="code-section">
                <div class="code-label">Code promo exclusif</div>
                <div class="code">$promoCode</div>
            </div>

            <div class="cta">
                <a href="http://localhost/AdminLTE3/index.php" class="cta-button">Voir le produit</a>
            </div>
        </div>

        <div class="footer">
            <p>Stabilis - Nutrition adaptative et durable</p>
        </div>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    private function sendHTMLEmail($to, $subject, $htmlBody, $fromEmail, $fromName) {
        if ($this->simpleSmtp && ($this->config['method'] ?? '') === 'smtp') {
            $sent = $this->simpleSmtp->send($to, $subject, $htmlBody, $fromEmail, $fromName, true);
            if ($sent) {
                return true;
            }
        }

        return $this->mailService->send($to, $subject, $htmlBody, $fromEmail, $fromName);
    }

    private function matchesAudience($customerType, $audience) {
        if ($audience === 'all') {
            return true;
        }
        if ($audience === 'normal') {
            return $customerType === 'regular';
        }
        return $customerType === $audience;
    }

    public function sendAnnouncementToCustomers($productName, $category, $description = '', $audience = 'all') {
        $customers = $this->getCustomersByCategory($category);
        $managerEmail = $this->config['smtp']['username'] ?? 'stabilisatyourservice@gmail.com';
        $sent = 0;

        foreach ($customers as $customer) {
            $profile = $this->getCustomerProfile($customer['email']);
            $typeAndDiscount = $this->getCustomerTypeAndDiscount($customer['email']);
            if (!$this->matchesAudience($typeAndDiscount['type'], $audience)) {
                continue;
            }

            $personalText = $this->generateAnnouncementTextWithAI(
                $productName,
                $category,
                $customer['prenom'],
                $typeAndDiscount['type'],
                $description,
                $profile ?: []
            );

            $body = $this->buildAnnouncementEmail($customer['prenom'], $productName, $category, $personalText);

            if ($this->sendHTMLEmail($customer['email'], 'Nouveau produit Stabilis: ' . $productName, $body, $managerEmail, 'Stabilis')) {
                $sent++;
            }
        }

        return ['success' => $sent > 0, 'message' => "Annonce envoyee a $sent client(s)", 'sent' => $sent, 'failed' => 0, 'customers' => []];
    }

    public function generateAnnouncementTextWithAI($productName, $category, $customerName, $customerType, $description = '', $profile = []) {
        $purchaseCount = (int)($profile['total_purchases'] ?? 0);
        $totalSpent = number_format((float)($profile['total_spent'] ?? 0), 2, '.', '');
        $favoriteCategories = $profile['categories'] ?? $category;

        $customerContext = match($customerType) {
            'new' => "This customer is new or almost new. Make the message welcoming, clear, and trust-building.",
            'regular' => "This customer has bought before. Make the message warm and relevant to their previous interest.",
            'loyal' => "This is a loyal customer. Make the message feel exclusive and attentive without offering a discount.",
            default => "Create a professional ecommerce announcement."
        };

        $prompt = "Tu es un redacteur CRM expert pour Stabilis, une boutique e-commerce de nutrition sportive.

Objectif: ecrire un paragraphe d'annonce produit personnalise, clair et utile, sans code promo.

Client:
- Prenom: $customerName
- Type: $customerType
- Contexte: $customerContext
- Nombre d'achats: $purchaseCount
- Total depense: $totalSpent euros
- Categories deja achetees/interessees: $favoriteCategories

Produit:
- Nom: $productName
- Categorie: $category
- Description produit: $description

Contraintes:
- Ne mentionne aucun code promo ni aucune remise
- Ne commence pas par Bonjour, le template l'ajoute deja
- 80 a 120 mots
- Ecris en francais naturel, simple et commercial
- Decris concretement le produit, son usage et le profil de client concerne
- Relie le produit aux interets ou achats precedents du client quand c'est pertinent
- Ton plus exclusif pour client loyal, plus accueillant pour nouveau client
- Pas d emojis, pas de markdown, pas de liste
- Pas de promesses medicales ni garanties de resultats
- Ne donne pas de dosage, frequence ou conseil de consommation precis sauf si fourni dans la description
- Retourne uniquement le paragraphe.";

        return $this->callGeminiAPI($prompt, [
            'productName' => $productName,
            'category' => $category,
            'customerName' => $customerName,
            'customerType' => $customerType,
            'discount' => 0,
            'description' => $description
        ]);
    }

    private function buildAnnouncementEmail($customerName, $productName, $category, $description = '') {
        $customerName = htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8');
        $productName = htmlspecialchars($productName, ENT_QUOTES, 'UTF-8');
        $category = htmlspecialchars($category, ENT_QUOTES, 'UTF-8');
        $description = trim($description) !== '' ? nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8')) : 'Un nouveau produit vient d arriver dans notre boutique Stabilis.';

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; background:#f3faf5; font-family:Arial, sans-serif; color:#24362b;">
    <div style="max-width:640px; margin:24px auto; background:#ffffff; border:1px solid #d9eadf; border-radius:14px; overflow:hidden;">
        <div style="background:#1A4D3A; color:#ffffff; padding:26px 24px;">
            <h1 style="margin:0; font-size:26px;">STABILIS</h1>
            <p style="margin:6px 0 0; color:#d9eadf;">Nouveau produit disponible</p>
        </div>
        <div style="padding:24px;">
            <p style="font-size:16px; margin:0 0 18px;">Bonjour <strong>$customerName</strong>,</p>
            <div style="background:#f3faf5; border-left:4px solid #3A6B4B; padding:18px; border-radius:10px; margin-bottom:20px;">
                <div style="font-size:12px; color:#587260; text-transform:uppercase; letter-spacing:.5px;">$category</div>
                <div style="font-size:22px; font-weight:700; color:#1A4D3A; margin-top:5px;">$productName</div>
            </div>
            <div style="font-size:15px; line-height:1.7; margin-bottom:22px;">$description</div>
            <div style="text-align:center; margin:26px 0 8px;">
                <a href="http://localhost/AdminLTE3/Views/front/shop.php" style="background:#1A4D3A; color:#ffffff; text-decoration:none; padding:13px 24px; border-radius:999px; font-weight:700; display:inline-block;">Voir la boutique</a>
            </div>
        </div>
        <div style="background:#f3faf5; color:#587260; padding:14px 24px; font-size:12px; border-top:1px solid #d9eadf;">
            Stabilis - Nutrition adaptative et durable
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function removeEmojiCharacters($text) {
        $text = preg_replace('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $text);
        return trim($text);
    }

    public function generateAndSendPromo($productId, $productName, $category, $price, $description = '', $audience = 'all') {
        error_log("=== STARTING PERSONALIZED PROMO CAMPAIGN ===");
        error_log("Product: $productName | Category: $category | Price: $price EUR");
        
        $genericText = $this->generateFallbackText();
        $savedFile = $this->savePromoText($productId, $productName, $genericText);

        $sendResult = $this->sendPromoToCustomers($productId, $productName, $category, $price, $description, $audience);

        error_log("=== PROMO CAMPAIGN COMPLETE ===");
        error_log("Results: " . json_encode($sendResult));

        return [
            'success' => $sendResult['success'],
            'message' => $sendResult['message'],
            'sent' => $sendResult['sent'],
            'failed' => $sendResult['failed'],
            'saved_file' => $savedFile,
            'customers' => $sendResult['customers'] ?? []
        ];
    }
}
