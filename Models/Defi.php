<?php
require_once __DIR__ . '/../config/database.php';

class Defi
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function getAll(string $search = '', string $sort = 'recent'): array
    {
        $search = trim($search);
        $sortOptions = [
            'recent' => 'id DESC',
            'oldest' => 'id ASC',
            'name_asc' => 'nom ASC',
            'name_desc' => 'nom DESC',
            'type_asc' => 'type ASC, nom ASC',
            'reward_desc' => 'CAST(recompense AS UNSIGNED) DESC',
            'reward_asc' => 'CAST(recompense AS UNSIGNED) ASC',
        ];
        $orderBy = $sortOptions[$sort] ?? $sortOptions['recent'];

        if ($search !== '') {
            if (ctype_digit($search)) {
                $query = $this->db->prepare("SELECT * FROM defis WHERE id = :id ORDER BY $orderBy");
                $query->execute(['id' => (int)$search]);
                return $query->fetchAll();
            }

            $query = $this->db->prepare("SELECT * FROM defis WHERE nom LIKE :search OR type LIKE :search ORDER BY $orderBy");
            $query->execute(['search' => '%' . $search . '%']);
            return $query->fetchAll();
        }

        return $this->db->query("SELECT * FROM defis ORDER BY $orderBy")->fetchAll();
    }

    public function count(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM defis')->fetchColumn();
    }

    public function getById(int $id): ?array
    {
        $query = $this->db->prepare('SELECT * FROM defis WHERE id = :id');
        $query->execute(['id' => $id]);
        $row = $query->fetch();
        return $row ?: null;
    }

    public function idExists(int $id): bool
    {
        $query = $this->db->prepare('SELECT COUNT(*) FROM defis WHERE id = :id');
        $query->execute(['id' => $id]);
        return (int)$query->fetchColumn() > 0;
    }

    public function create(array $data): bool
    {
        if (!empty($data['id'])) {
            $query = $this->db->prepare('INSERT INTO defis (id, nom, type, objectif, recompense) VALUES (:id, :nom, :type, :objectif, :recompense)');
            return $query->execute([
                'id' => (int)$data['id'],
                'nom' => $data['nom'],
                'type' => $data['type'],
                'objectif' => $data['objectif'],
                'recompense' => $data['recompense'],
            ]);
        }

        $query = $this->db->prepare('INSERT INTO defis (nom, type, objectif, recompense) VALUES (:nom, :type, :objectif, :recompense)');
        return $query->execute([
            'nom' => $data['nom'],
            'type' => $data['type'],
            'objectif' => $data['objectif'],
            'recompense' => $data['recompense'],
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $query = $this->db->prepare('UPDATE defis SET nom = :nom, type = :type, objectif = :objectif, recompense = :recompense WHERE id = :id');
        return $query->execute([
            'nom' => $data['nom'],
            'type' => $data['type'],
            'objectif' => $data['objectif'],
            'recompense' => $data['recompense'],
            'id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $query = $this->db->prepare('DELETE FROM defis WHERE id = :id');
        return $query->execute(['id' => $id]);
    }

    public function getEcoImpact(): float
    {
        return $this->count() * 2.3;
    }
}
