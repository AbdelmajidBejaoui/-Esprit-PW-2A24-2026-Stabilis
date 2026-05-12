<?php
require_once __DIR__ . '/Repository.php';

/**
 * EntrainementRepository - Data access for entrainements
 */
class EntrainementRepository extends Repository
{
    protected string $table = 'entrainements';

    /**
     * Get catalogue workouts (non-custom)
     */
    public function getCatalogue(): array
    {
        return $this->query(
            "SELECT * FROM {$this->table} 
             WHERE is_custom = 0 
             ORDER BY type_sport, niveau"
        );
    }

    /**
     * Get custom workouts by user
     */
    public function getCustomByUser(int $userId): array
    {
        return $this->findBy(
            ['is_custom' => 1, 'user_id' => $userId],
            'id DESC'
        );
    }

    /**
     * Get workout steps/etapes
     */
    public function getSteps(int $entrainementId): array
    {
        return $this->query(
            "SELECT * FROM etapes_exercice 
             WHERE entrainement_id = :id 
             ORDER BY ordre",
            [':id' => $entrainementId]
        );
    }

    /**
     * Insert workout steps
     */
    public function insertSteps(int $entrainementId, array $steps): void
    {
        // Delete existing steps
        $this->db->prepare(
            "DELETE FROM etapes_exercice WHERE entrainement_id = :id"
        )->execute([':id' => $entrainementId]);

        // Insert new steps
        $stmt = $this->db->prepare(
            "INSERT INTO etapes_exercice 
             (entrainement_id, ordre, titre, description, duree_secondes, conseil) 
             VALUES (:eid, :ordre, :titre, :desc, :duree, :conseil)"
        );

        foreach ($steps as $index => $step) {
            $stmt->execute([
                ':eid'    => $entrainementId,
                ':ordre'  => $index + 1,
                ':titre'  => $step['titre'] ?? "Étape " . ($index + 1),
                ':desc'   => $step['description'] ?? '',
                ':duree'  => $step['duree_secondes'] ?? 60,
                ':conseil' => $step['conseil'] ?? null,
            ]);
        }
    }
}

