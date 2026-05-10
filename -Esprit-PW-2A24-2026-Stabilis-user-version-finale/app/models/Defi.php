<?php
class Defi
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $sql = "SELECT * FROM defis ORDER BY id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM defis";
        $result = $this->db->query($sql);
        $row = $result ? $result->fetch_assoc() : null;
        return $row ? (int) $row['total'] : 0;
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM defis WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }

    public function searchByIdOrName(string $term): array
    {
        $term = trim($term);
        if (empty($term)) {
            return [];
        }

        // Check if term is numeric (search by ID)
        if (is_numeric($term)) {
            $sql = "SELECT * FROM defis WHERE id = ? ORDER BY id DESC";
            $stmt = $this->db->prepare($sql);
            $id = intval($term);
            $stmt->bind_param('i', $id);
        } else {
            // Search by name
            $sql = "SELECT * FROM defis WHERE nom LIKE ? ORDER BY id DESC";
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
        // If ID is provided, use it; otherwise let auto-increment
        if (!empty($data['id']) && is_numeric($data['id'])) {
            $sql = "INSERT INTO defis (id, nom, type, objectif, recompense) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $id = (int)$data['id'];
            $stmt->bind_param('issss', $id, $data['nom'], $data['type'], $data['objectif'], $data['recompense']);
            return $stmt->execute();
        } else {
            $sql = "INSERT INTO defis (nom, type, objectif, recompense) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ssss', $data['nom'], $data['type'], $data['objectif'], $data['recompense']);
            return $stmt->execute();
        }
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE defis SET nom = ?, type = ?, objectif = ?, recompense = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssssi', $data['nom'], $data['type'], $data['objectif'], $data['recompense'], $id);
        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM defis WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    public function idExists(int $id): bool
    {
        $sql = "SELECT id FROM defis WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result && $result->fetch_assoc() !== null;
    }

    public function getEcoImpact(): float
    {
        return $this->count() * 2.3;
    }
}
