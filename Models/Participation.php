<?php
require_once __DIR__ . '/../config/database.php';

class Participation
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function getAll(string $search = '', string $sort = 'recent'): array
    {
        $params = [];
        $where = '';
        $search = trim($search);
        $sortOptions = [
            'recent' => 'p.id DESC',
            'oldest' => 'p.id ASC',
            'user_asc' => 'u.nom ASC, p.id DESC',
            'defi_asc' => 'd.nom ASC, p.id DESC',
            'progress_desc' => 'p.progression DESC, p.id DESC',
            'progress_asc' => 'p.progression ASC, p.id DESC',
            'status_asc' => 'p.statut ASC, p.id DESC',
            'date_desc' => 'p.date_debut DESC, p.id DESC',
            'date_asc' => 'p.date_debut ASC, p.id DESC',
        ];
        $orderBy = $sortOptions[$sort] ?? $sortOptions['recent'];

        if ($search !== '') {
            if (ctype_digit($search)) {
                $where = 'WHERE p.id = :id OR p.id_utilisateur = :id';
                $params['id'] = (int)$search;
            } else {
                $where = 'WHERE d.nom LIKE :search OR u.email LIKE :search OR u.nom LIKE :search';
                $params['search'] = '%' . $search . '%';
            }
        }

        $sql = "SELECT p.*, d.nom AS defi_nom, d.type AS defi_type, u.nom AS utilisateur_nom, u.email AS utilisateur_email,
                       COUNT(pr.id) AS proof_count,
                       SUM(CASE WHEN pr.review_state = 'pending' THEN 1 ELSE 0 END) AS pending_proof_count,
                       MAX(pr.created_at) AS latest_proof_at
                FROM participations p
                LEFT JOIN defis d ON d.id = p.id_defi
                LEFT JOIN `user` u ON u.id = p.id_utilisateur
                LEFT JOIN participation_proofs pr ON pr.participation_id = p.id
                $where
                GROUP BY p.id
                ORDER BY $orderBy";
        $query = $this->db->prepare($sql);
        $query->execute($params);
        return $query->fetchAll();
    }

    public function count(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM participations')->fetchColumn();
    }

    public function getById(int $id): ?array
    {
        $query = $this->db->prepare("SELECT p.*, d.nom AS defi_nom, d.type AS defi_type, d.objectif AS defi_objectif, d.recompense AS defi_recompense,
                                            u.nom AS utilisateur_nom, u.email AS utilisateur_email
                                     FROM participations p
                                     LEFT JOIN defis d ON d.id = p.id_defi
                                     LEFT JOIN `user` u ON u.id = p.id_utilisateur
                                     WHERE p.id = :id");
        $query->execute(['id' => $id]);
        $row = $query->fetch();
        return $row ?: null;
    }

    public function getByUserId(int $userId): array
    {
        $query = $this->db->prepare("SELECT p.*, d.nom AS defi_nom, d.type AS defi_type, d.objectif AS defi_objectif, d.recompense AS defi_recompense,
                                            COUNT(pr.id) AS proof_count,
                                            SUM(CASE WHEN pr.review_state = 'pending' THEN 1 ELSE 0 END) AS pending_proof_count,
                                            MAX(pr.created_at) AS latest_proof_at
                                     FROM participations p
                                     LEFT JOIN defis d ON d.id = p.id_defi
                                     LEFT JOIN participation_proofs pr ON pr.participation_id = p.id
                                     WHERE p.id_utilisateur = :user_id
                                     GROUP BY p.id
                                     ORDER BY p.id DESC");
        $query->execute(['user_id' => $userId]);
        return $query->fetchAll();
    }

    public function getLeaderboard(int $limit = 8): array
    {
        $limit = max(1, min($limit, 20));
        $rewardSql = "CAST(COALESCE(NULLIF(d.recompense, ''), '0') AS UNSIGNED)";
        $pointsSql = "
            CASE
                WHEN p.statut = 'completed' THEN $rewardSql
                WHEN p.statut = 'in_progress' THEN ROUND($rewardSql * GREATEST(0, LEAST(COALESCE(p.progression, 0), 100)) / 100)
                ELSE 0
            END
        ";

        $sql = "
            SELECT
                p.id_utilisateur AS user_id,
                COALESCE(NULLIF(u.nom, ''), CONCAT('Utilisateur #', p.id_utilisateur)) AS nom,
                COALESCE(NULLIF(u.email, ''), '') AS email,
                SUM($pointsSql) AS points,
                COUNT(p.id) AS participations,
                SUM(CASE WHEN p.statut = 'completed' THEN 1 ELSE 0 END) AS completed_count,
                AVG(COALESCE(p.progression, 0)) AS average_progress
            FROM participations p
            LEFT JOIN defis d ON d.id = p.id_defi
            LEFT JOIN `user` u ON u.id = p.id_utilisateur
            GROUP BY p.id_utilisateur, u.nom, u.email
            ORDER BY points DESC, completed_count DESC, participations DESC, nom ASC
            LIMIT $limit
        ";

        return $this->db->query($sql)->fetchAll();
    }

    public function userExists(int $userId): bool
    {
        $query = $this->db->prepare('SELECT COUNT(*) FROM `user` WHERE id = :id AND statut_compte = 1');
        $query->execute(['id' => $userId]);
        return (int)$query->fetchColumn() > 0;
    }

    public function existsForUserAndDefi(int $userId, int $defiId, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM participations WHERE id_utilisateur = :user_id AND id_defi = :defi_id';
        $params = ['user_id' => $userId, 'defi_id' => $defiId];
        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $query = $this->db->prepare($sql);
        $query->execute($params);
        return (int)$query->fetchColumn() > 0;
    }

    public function create(array $data): bool
    {
        $query = $this->db->prepare("INSERT INTO participations (id_utilisateur, id_defi, progression, statut, date_debut, date_fin)
                                     VALUES (:user_id, :defi_id, 0, 'in_progress', :date_debut, NULL)");
        return $query->execute([
            'user_id' => (int)$data['id_utilisateur'],
            'defi_id' => (int)$data['id_defi'],
            'date_debut' => $data['date_debut'],
        ]);
    }

    public function updateAdminProgress(int $id, array $data): bool
    {
        $dateFin = $data['statut'] === 'completed' ? date('Y-m-d') : ($data['date_fin'] ?? null);
        if ($data['statut'] === 'in_progress') {
            $dateFin = null;
        }

        $query = $this->db->prepare('UPDATE participations SET progression = :progression, statut = :statut, date_fin = :date_fin WHERE id = :id');
        return $query->execute([
            'progression' => (int)$data['progression'],
            'statut' => $data['statut'],
            'date_fin' => $dateFin,
            'id' => $id,
        ]);
    }

    public function userOwnsParticipation(int $participationId, int $userId): bool
    {
        $query = $this->db->prepare('SELECT COUNT(*) FROM participations WHERE id = :id AND id_utilisateur = :user_id');
        $query->execute(['id' => $participationId, 'user_id' => $userId]);
        return (int)$query->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $query = $this->db->prepare('DELETE FROM participations WHERE id = :id');
        return $query->execute(['id' => $id]);
    }
}
