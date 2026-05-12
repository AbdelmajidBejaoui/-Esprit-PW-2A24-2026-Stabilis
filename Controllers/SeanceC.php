<?php
require_once __DIR__ . '/../config/entrainements.php';
require_once __DIR__ . '/../Models/Seance.php';
require_once __DIR__ . '/../Models/Entrainement.php';
require_once __DIR__ . '/../Services/CalorieService.php';
require_once __DIR__ . '/../Services/PerformanceTracker.php';
require_once __DIR__ . '/../Services/EntityHelper.php';

/**
 * SeanceC — Contrôleur des séances complétées
 *
 * Responsabilité : persistance des séances et accès aux données.
 * Délègue les calculs caloriques à CalorieService
 * et l'analyse de performance à PerformanceTracker.
 */
class SeanceC
{
    private function db() { return config::getConnexion(); }

    /**
     * Enregistre une séance avec calcul automatique des calories.
     * Calories = MET × poids_kg × durée(h) × coeff_intensité (via CalorieService).
     */
    public function enregistrer(Seance $s, float $met, float $poids_kg): int
    {
        // Déléguer le calcul calorique (avec coefficient d'intensité) au service
        $calories = CalorieService::parDuree($met, $poids_kg, $s->getDureeMinutes(), $s->getIntensite());
        $s->setCalories($calories);

        $q = $this->db()->prepare(
            "INSERT INTO seances_completees (utilisateur_id,entrainement_id,duree_minutes,calories,intensite,fc_moyenne,notes)
             VALUES(:uid,:eid,:dur,:cal,:int,:fc,:notes)"
        );
        $q->execute([
            ':uid'   => $s->getUtilisateurId(),
            ':eid'   => $s->getEntrainementId(),
            ':dur'   => $s->getDureeMinutes(),
            ':cal'   => $calories,
            ':int'   => $s->getIntensite(),
            ':fc'    => $s->getFcMoyenne(),
            ':notes' => $s->getNotes(),
        ]);
        return (int)$this->db()->lastInsertId();
    }

    public function listByUser(int $uid, int $limit = 20): array
    {
        $q = $this->db()->prepare(
            "SELECT s.*, e.nom AS entrainement_nom, e.type_sport, e.met_value
             FROM seances_completees s
             INNER JOIN entrainements e ON e.id = s.entrainement_id
             WHERE s.utilisateur_id=:uid
             ORDER BY s.completed_at DESC LIMIT $limit"
        );
        $q->execute([':uid'=>$uid]);
        return $q->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $q = $this->db()->prepare(
            "SELECT s.*, e.nom AS entrainement_nom, e.type_sport, e.met_value, u.nom AS user_nom, u.email AS user_email,
                    COALESCE(u.poids, 70) AS user_poids
             FROM seances_completees s
             INNER JOIN entrainements e ON e.id = s.entrainement_id
             INNER JOIN `user` u ON u.id = s.utilisateur_id
             WHERE s.id=:id"
        );
        $q->execute([':id' => $id]);
        $row = $q->fetch();
        return $row ?: null;
    }

    public function update(int $id, array $data): void
    {
        $errors = self::validate($data);
        if ($errors) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }

        $current = $this->getById($id);
        if (!$current) {
            throw new RuntimeException('Seance introuvable.');
        }

        $calories = CalorieService::parDuree(
            (float)$current['met_value'],
            (float)($current['user_poids'] ?: 70),
            (int)$data['duree_minutes'],
            $data['intensite'] ?? 'moderee'
        );

        $q = $this->db()->prepare(
            "UPDATE seances_completees
             SET duree_minutes=:dur, calories=:cal, intensite=:int, fc_moyenne=:fc, notes=:notes
             WHERE id=:id"
        );
        $q->execute([
            ':dur' => (int)$data['duree_minutes'],
            ':cal' => $calories,
            ':int' => $data['intensite'] ?? 'moderee',
            ':fc' => ($data['fc_moyenne'] ?? '') !== '' ? (int)$data['fc_moyenne'] : null,
            ':notes' => trim($data['notes'] ?? ''),
            ':id' => $id,
        ]);
    }

    /**
     * Statistiques simples (agrégats SQL rapides).
     */
    public function statsUser(int $uid): array
    {
        $q = $this->db()->prepare(
            "SELECT COUNT(*) AS nb_seances,
                    COALESCE(SUM(calories),0)       AS total_calories,
                    COALESCE(SUM(duree_minutes),0)   AS total_minutes,
                    COALESCE(AVG(calories),0)        AS avg_calories
             FROM seances_completees WHERE utilisateur_id=:uid"
        );
        $q->execute([':uid'=>$uid]);
        return $q->fetch();
    }

    /**
     * KPIs avancés via PerformanceTracker.
     */
    public function kpisUser(int $uid): array
    {
        $seances = $this->listByUser($uid, 100);
        return PerformanceTracker::calculerKPIs($seances);
    }

    /**
     * Données graphique via PerformanceTracker.
     */
    public function donneesGraphique(int $uid, int $limit = 20): array
    {
        $seances = $this->listByUser($uid, $limit);
        return PerformanceTracker::donneesGraphique($seances);
    }

    /**
     * Analyse d'assiduité via PerformanceTracker.
     */
    public function analyserAssiduité(int $uid): array
    {
        $seances = $this->listByUser($uid, 100);
        return PerformanceTracker::analyserAssiduité($seances);
    }

    public function delete(int $id, int $uid): void
    {
        $q = $this->db()->prepare("DELETE FROM seances_completees WHERE id=:id AND utilisateur_id=:uid");
        $q->execute([':id'=>$id,':uid'=>$uid]);
    }

    public function deleteAdmin(int $id): void
    {
        $q = $this->db()->prepare("DELETE FROM seances_completees WHERE id=:id");
        $q->execute([':id' => $id]);
    }

    public function totalCaloriesAll(): float
    {
        return (float)$this->db()->query("SELECT COALESCE(SUM(calories),0) FROM seances_completees")->fetchColumn();
    }

    public function countAll(): int
    {
        return (int)$this->db()->query("SELECT COUNT(*) FROM seances_completees")->fetchColumn();
    }

    public static function validate(array $p): array
    {
        $errors = [];
        if (empty($p['duree_minutes']) || !is_numeric($p['duree_minutes']) || (int)$p['duree_minutes'] < 1 || (int)$p['duree_minutes'] > 600)
            $errors[] = 'Durée invalide (1–600 minutes).';
        if (!in_array($p['intensite']??'', EntityHelper::validIntensites()))
            $errors[] = 'Intensité invalide.';
        if (!empty($p['fc_moyenne']) && (!is_numeric($p['fc_moyenne']) || (int)$p['fc_moyenne'] < 40 || (int)$p['fc_moyenne'] > 250))
            $errors[] = 'FC moyenne doit être entre 40 et 250 bpm.';
        return $errors;
    }
}

