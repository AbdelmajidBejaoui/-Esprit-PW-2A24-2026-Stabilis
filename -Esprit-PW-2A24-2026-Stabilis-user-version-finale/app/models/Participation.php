<?php
class Participation
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $sql = "SELECT p.*, d.nom AS defi_nom,
                       COUNT(pr.id) AS proof_count,
                       SUM(CASE WHEN pr.review_state = 'pending' THEN 1 ELSE 0 END) AS pending_proof_count,
                       MAX(pr.created_at) AS latest_proof_at
                FROM participations p
                LEFT JOIN defis d ON d.id = p.id_defi
                LEFT JOIN participation_proofs pr ON pr.participation_id = p.id
                GROUP BY p.id
                ORDER BY p.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM participations";
        $result = $this->db->query($sql);
        $row = $result ? $result->fetch_assoc() : null;
        return $row ? (int) $row['total'] : 0;
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT p.*, d.nom AS defi_nom, d.type AS defi_type, d.objectif AS defi_objectif, d.recompense AS defi_recompense
                FROM participations p
                LEFT JOIN defis d ON d.id = p.id_defi
                WHERE p.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }

    public function searchByIdOrUser(string $term): array
    {
        $term = trim($term);
        if (empty($term)) {
            return [];
        }

        // Check if term is numeric (search by ID or user ID)
        if (is_numeric($term)) {
            $id = intval($term);
            $sql = "SELECT p.*, d.nom AS defi_nom
                    FROM participations p
                    LEFT JOIN defis d ON d.id = p.id_defi
                    LEFT JOIN participation_proofs pr ON pr.participation_id = p.id
                    WHERE p.id = ? OR p.id_utilisateur = ?
                    GROUP BY p.id
                    ORDER BY p.id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ii', $id, $id);
        } else {
            // Search by challenge name
            $sql = "SELECT p.*, d.nom AS defi_nom
                    FROM participations p
                    LEFT JOIN defis d ON d.id = p.id_defi
                    LEFT JOIN participation_proofs pr ON pr.participation_id = p.id
                    WHERE d.nom LIKE ?
                    GROUP BY p.id
                    ORDER BY p.id DESC";
            $stmt = $this->db->prepare($sql);
            $searchTerm = '%' . $term . '%';
            $stmt->bind_param('s', $searchTerm);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO participations (id_utilisateur, id_defi, progression, statut, date_debut, date_fin)
                VALUES (?, ?, 0, 'in_progress', ?, NULL)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'iis',
            $data['id_utilisateur'],
            $data['id_defi'],
            $data['date_debut']
        );
        return $stmt->execute();
    }

    public function userExists(int $userId): bool
    {
        $sql = "SELECT id FROM user WHERE id = ? AND statut_compte = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result && $result->fetch_assoc() !== null;
    }

    public function existsForUserAndDefi(int $userId, int $defiId, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $sql = "SELECT id FROM participations WHERE id_utilisateur = ? AND id_defi = ? AND id <> ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iii', $userId, $defiId, $excludeId);
        } else {
            $sql = "SELECT id FROM participations WHERE id_utilisateur = ? AND id_defi = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ii', $userId, $defiId);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        return $result && $result->fetch_assoc() !== null;
    }

    public function update(int $id, array $data): bool
    {
        return $this->updateAdminProgress($id, $data);
    }

    public function updateAdminProgress(int $id, array $data): bool
    {
        $dateFin = $data['statut'] === 'completed' ? date('Y-m-d') : ($data['date_fin'] ?? null);
        if ($data['statut'] === 'in_progress') {
            $dateFin = null;
        }

        $sql = "UPDATE participations
                SET progression = ?, statut = ?, date_fin = ?
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'issi',
            $data['progression'],
            $data['statut'],
            $dateFin,
            $id
        );
        return $stmt->execute();
    }

    public function getByUserId(int $userId): array
    {
        $sql = "SELECT p.*, d.nom AS defi_nom, d.type AS defi_type, d.objectif AS defi_objectif, d.recompense AS defi_recompense,
                       COUNT(pr.id) AS proof_count,
                       SUM(CASE WHEN pr.review_state = 'pending' THEN 1 ELSE 0 END) AS pending_proof_count,
                       MAX(pr.created_at) AS latest_proof_at
                FROM participations p
                LEFT JOIN defis d ON d.id = p.id_defi
                LEFT JOIN participation_proofs pr ON pr.participation_id = p.id
                WHERE p.id_utilisateur = ?
                GROUP BY p.id
                ORDER BY p.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function userOwnsParticipation(int $participationId, int $userId): bool
    {
        $sql = "SELECT id FROM participations WHERE id = ? AND id_utilisateur = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $participationId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result && $result->fetch_assoc() !== null;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM participations WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}
