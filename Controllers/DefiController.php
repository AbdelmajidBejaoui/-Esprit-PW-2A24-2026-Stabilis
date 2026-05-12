<?php
require_once __DIR__ . '/../Models/Defi.php';

class DefiController
{
    private Defi $defi;

    public function __construct()
    {
        $this->defi = new Defi();
    }

    public function getAll(string $search = '', string $sort = 'recent'): array
    {
        return $this->defi->getAll($search, $sort);
    }

    public function getById(int $id): ?array
    {
        return $this->defi->getById($id);
    }

    public function count(): int
    {
        return $this->defi->count();
    }

    public function getEcoImpact(): float
    {
        return $this->defi->getEcoImpact();
    }

    public function sanitize(array $input): array
    {
        return [
            'id' => trim($input['id'] ?? ''),
            'nom' => trim($input['nom'] ?? ''),
            'type' => trim($input['type'] ?? ''),
            'objectif' => trim($input['objectif'] ?? ''),
            'recompense' => trim($input['recompense'] ?? ''),
        ];
    }

    public function validate(array $input, bool $allowId = true): array
    {
        $errors = [];

        if ($allowId && $input['id'] !== '') {
            if (!ctype_digit($input['id']) || (int)$input['id'] <= 0) {
                $errors[] = 'ID invalide.';
            } elseif ($this->defi->idExists((int)$input['id'])) {
                $errors[] = 'Un defi avec cet ID existe deja.';
            }
        }

        if ($input['nom'] === '' || mb_strlen($input['nom']) > 100) {
            $errors[] = 'Le nom est obligatoire et limite a 100 caracteres.';
        }

        if (!in_array($input['type'], ['aliment', 'entrainement', 'compensation'], true)) {
            $errors[] = 'Type invalide.';
        }

        if ($input['objectif'] === '') {
            $errors[] = 'L objectif est obligatoire.';
        }

        if ($input['recompense'] === '' || mb_strlen($input['recompense']) > 50) {
            $errors[] = 'La recompense est obligatoire et limitee a 50 caracteres.';
        }

        return $errors;
    }

    public function add(array $data): bool
    {
        return $this->defi->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->defi->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->defi->delete($id);
    }
}
