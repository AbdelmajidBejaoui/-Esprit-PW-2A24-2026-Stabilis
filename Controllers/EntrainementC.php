<?php
require_once __DIR__ . '/../config/entrainements.php';
require_once __DIR__ . '/../Models/Entrainement.php';
require_once __DIR__ . '/../Services/CalorieService.php';

/**
 * EntrainementC — Contrôleur des entraînements (catalogue + custom)
 *
 * Responsabilité : gestion des entraînements du catalogue et personnalisés.
 * Les calculs caloriques sont délégués à CalorieService.
 */
class EntrainementC
{
    private function db() { return config::getConnexion(); }

    public function listCatalogue(): array
    {
        return $this->db()->query(
            "SELECT * FROM entrainements WHERE is_custom=0 ORDER BY type_sport, niveau"
        )->fetchAll();
    }

    public function listCustomByUser(int $userId): array
    {
        $q = $this->db()->prepare("SELECT * FROM entrainements WHERE is_custom=1 AND user_id=:uid ORDER BY id DESC");
        $q->execute([':uid'=>$userId]);
        return $q->fetchAll();
    }

    public function getById(int $id): ?Entrainement
    {
        $q = $this->db()->prepare("SELECT * FROM entrainements WHERE id=:id");
        $q->execute([':id'=>$id]);
        $r = $q->fetch();
        if (!$r) return null;
        return new Entrainement($r['id'],$r['nom'],$r['description'],$r['type_sport'],$r['niveau'],(float)$r['met_value'],(int)$r['is_custom'],$r['user_id'],$r['created_at']);
    }

    public function getEtapes(int $id): array
    {
        $q = $this->db()->prepare("SELECT * FROM etapes_exercice WHERE entrainement_id=:id ORDER BY ordre");
        $q->execute([':id'=>$id]);
        return $q->fetchAll();
    }

    /**
     * Calcule les calories pour un entraînement via CalorieService.
     * Remplace l'ancienne méthode statique Entrainement::calculerCalories.
     */
    public function calculerCalories(float $met, float $poids_kg, int $duree_min, string $intensite = 'moderee'): float
    {
        return CalorieService::parDuree($met, $poids_kg, $duree_min, $intensite);
    }

    public function insert(Entrainement $e): int
    {
        $q = $this->db()->prepare(
            "INSERT INTO entrainements (nom,description,type_sport,niveau,met_value,is_custom,user_id)
             VALUES(:n,:d,:t,:nv,:m,:ic,:uid)"
        );
        $q->execute([':n'=>$e->getNom(),':d'=>$e->getDescription(),':t'=>$e->getTypeSport(),
                     ':nv'=>$e->getNiveau(),':m'=>$e->getMetValue(),':ic'=>$e->getIsCustom(),':uid'=>$e->getUserId()]);
        return (int)$this->db()->lastInsertId();
    }

    public function update(Entrainement $e): void
    {
        $q = $this->db()->prepare(
            "UPDATE entrainements SET nom=:n,description=:d,type_sport=:t,niveau=:nv,met_value=:m,is_custom=:ic
             WHERE id=:id AND (is_custom=0 OR user_id=:uid)"
        );
        $q->execute([':n'=>$e->getNom(),':d'=>$e->getDescription(),':t'=>$e->getTypeSport(),
                     ':nv'=>$e->getNiveau(),':m'=>$e->getMetValue(),':ic'=>$e->getIsCustom(),
                     ':id'=>$e->getId(),':uid'=>$e->getUserId()]);
    }

    public function delete(int $id): void
    {
        $this->db()->prepare("DELETE FROM entrainements WHERE id=:id")->execute([':id'=>$id]);
    }

    public function insertEtapes(int $entrainementId, array $etapes): void
    {
        $this->db()->prepare("DELETE FROM etapes_exercice WHERE entrainement_id=:id")->execute([':id'=>$entrainementId]);
        $q = $this->db()->prepare(
            "INSERT INTO etapes_exercice (entrainement_id, ordre, titre, description) VALUES(:eid,:o,:t,:d)"
        );
        foreach ($etapes as $i => $etape) {
            $q->execute([
                ':eid' => $entrainementId,
                ':o'   => $i + 1,
                ':t'   => $etape['titre'] ?? ('Étape ' . ($i+1)),
                ':d'   => $etape['description'] ?? '',
            ]);
        }
    }

    public static function generateTutorialViaAPI(string $nom, string $typeSport, string $niveau, string $description = ''): array
    {
        $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
        if (empty($apiKey)) {
            return [
                ['titre'=>'Échauffement',       'description'=>"Faites 5 minutes d'échauffement général pour préparer vos muscles."],
                ['titre'=>'Position de départ',  'description'=>"Adoptez la bonne posture pour $nom. Gardez le dos droit et les épaules relâchées."],
                ['titre'=>'Exécution',           'description'=>"Effectuez le mouvement principal de $nom avec contrôle et régularité."],
                ['titre'=>'Respiration',         'description'=>"Synchronisez votre respiration avec le mouvement : expirez à l'effort."],
                ['titre'=>'Récupération',        'description'=>"Terminez par 5 minutes d'étirements ciblant les groupes musculaires sollicités."],
            ];
        }

        $prompt = "Tu es un coach sportif expert. Génère un tutoriel pratique étape par étape pour l'entraînement suivant.\n"
            . "Nom: $nom\nSport: $typeSport\nNiveau: $niveau\n"
            . ($description ? "Description: $description\n" : "")
            . "Réponds UNIQUEMENT avec un JSON valide (sans markdown), tableau d'objets: [{\"titre\":\"...\",\"description\":\"...\"}]\n"
            . "5 à 7 étapes concrètes. Titres courts. Descriptions claires de 1-2 phrases.";

        $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent?key=' . $apiKey);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 800]
            ]),
        ]);
        $raw  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Gemini API Error (HTTP $httpCode): $raw");
            return [
                ['titre'=>'Échauffement',       'description'=>"Faites 5 minutes d'échauffement général pour préparer vos muscles."],
                ['titre'=>'Position de départ',  'description'=>"Adoptez la bonne posture pour $nom. Gardez le dos droit et les épaules relâchées."],
                ['titre'=>'Exécution',           'description'=>"Effectuez le mouvement principal de $nom avec contrôle et régularité."],
                ['titre'=>'Respiration',         'description'=>"Synchronisez votre respiration avec le mouvement : expirez à l'effort."],
                ['titre'=>'Récupération',        'description'=>"Terminez par 5 minutes d'étirements ciblant les groupes musculaires sollicités."],
            ];
        }

        $data = json_decode($raw, true);
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $text = preg_replace('/```json|```/', '', $text);
        $etapes = json_decode(trim($text), true);
        
        if (!is_array($etapes) || empty($etapes)) {
            return [
                ['titre'=>'Échauffement',       'description'=>"Faites 5 minutes d'échauffement général pour préparer vos muscles."],
                ['titre'=>'Position de départ',  'description'=>"Adoptez la bonne posture pour $nom. Gardez le dos droit et les épaules relâchées."],
                ['titre'=>'Exécution',           'description'=>"Effectuez le mouvement principal de $nom avec contrôle et régularité."],
                ['titre'=>'Respiration',         'description'=>"Synchronisez votre respiration avec le mouvement : expirez à l'effort."],
                ['titre'=>'Récupération',        'description'=>"Terminez par 5 minutes d'étirements ciblant les groupes musculaires sollicités."],
            ];
        }
        
        return $etapes;
    }

    public function count(): int
    {
        return (int)$this->db()->query("SELECT COUNT(*) FROM entrainements WHERE is_custom=0")->fetchColumn();
    }

    public function countAll(): int
    {
        return (int)$this->db()->query("SELECT COUNT(*) FROM entrainements")->fetchColumn();
    }

    public static function validate(array $p): array
    {
        $errors = [];
        if (empty(trim($p['nom'] ?? '')))           $errors[] = 'Le nom est obligatoire.';
        if (strlen($p['nom']??'') > 100)             $errors[] = 'Nom max 100 caractères.';
        if (empty($p['type_sport']))                 $errors[] = 'Le type de sport est obligatoire.';
        if (!in_array($p['niveau']??'', ['debutant','intermediaire','avance']))
                                                     $errors[] = 'Niveau invalide.';
        if (!empty($p['met_value']) && (!is_numeric($p['met_value']) || (float)$p['met_value'] < 1 || (float)$p['met_value'] > 20))
                                                     $errors[] = 'Valeur MET invalide (1–20).';
        return $errors;
    }
}

