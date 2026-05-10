<?php
require_once __DIR__ . '/../Models/Defi.php';
require_once __DIR__ . '/../Models/Participation.php';
require_once __DIR__ . '/../Models/ParticipationProof.php';
require_once __DIR__ . '/../Services/DefiGeminiService.php';

class ParticipationController
{
    private Defi $defi;
    private Participation $participation;
    private ParticipationProof $proof;

    public function __construct()
    {
        $this->defi = new Defi();
        $this->participation = new Participation();
        $this->proof = new ParticipationProof();
    }

    public function getAll(string $search = '', string $sort = 'recent'): array
    {
        return $this->participation->getAll($search, $sort);
    }

    public function getById(int $id): ?array
    {
        return $this->participation->getById($id);
    }

    public function getByUserId(int $userId): array
    {
        return $this->participation->getByUserId($userId);
    }

    public function getLeaderboard(int $limit = 8): array
    {
        return $this->participation->getLeaderboard($limit);
    }

    public function getProofs(int $participationId): array
    {
        return $this->proof->getByParticipationId($participationId);
    }

    public function start(array $data): array
    {
        $payload = [
            'id_utilisateur' => (int)($data['id_utilisateur'] ?? 0),
            'id_defi' => (int)($data['id_defi'] ?? 0),
            'date_debut' => $data['date_debut'] ?? date('Y-m-d'),
        ];

        $errors = $this->validateStart($payload);
        if ($errors) {
            return [false, $errors];
        }

        return [$this->participation->create($payload), []];
    }

    public function validateStart(array $input): array
    {
        $errors = [];
        $today = date('Y-m-d');

        if ((int)$input['id_utilisateur'] <= 0 || !$this->participation->userExists((int)$input['id_utilisateur'])) {
            $errors[] = 'Utilisateur introuvable ou inactif.';
        }

        if ((int)$input['id_defi'] <= 0 || !$this->defi->getById((int)$input['id_defi'])) {
            $errors[] = 'Defi introuvable.';
        }

        if ((int)$input['id_utilisateur'] > 0 && (int)$input['id_defi'] > 0 && $this->participation->existsForUserAndDefi((int)$input['id_utilisateur'], (int)$input['id_defi'])) {
            $errors[] = 'Cet utilisateur participe deja a ce defi.';
        }

        if (!$this->isValidSqlDate((string)$input['date_debut'])) {
            $errors[] = 'Date debut invalide.';
        } elseif ($input['date_debut'] < $today) {
            $errors[] = 'Date debut ne peut pas etre ancienne.';
        }

        return $errors;
    }

    public function validateAdminUpdate(array $input): array
    {
        $errors = [];
        $progressionRaw = trim((string)($input['progression'] ?? ''));
        $statut = $input['statut'] ?? 'in_progress';

        if ($progressionRaw === '' || !ctype_digit($progressionRaw)) {
            $errors[] = 'La progression doit etre un nombre entier.';
            $progression = 0;
        } else {
            $progression = (int)$progressionRaw;
        }

        if ($progression < 0 || $progression > 100) {
            $errors[] = 'La progression doit etre entre 0 et 100%.';
        }

        if (!in_array($statut, ['in_progress', 'completed', 'failed'], true)) {
            $errors[] = 'Statut invalide.';
        }

        if ($statut === 'completed' && $progression !== 100) {
            $errors[] = 'Une participation terminee doit etre a 100%.';
        }

        if ($statut === 'in_progress' && $progression === 100) {
            $errors[] = 'Une participation a 100% doit etre terminee ou ajustee.';
        }

        if (!empty($input['date_fin']) && !$this->isValidSqlDate($input['date_fin'])) {
            $errors[] = 'Date fin invalide.';
        }

        return $errors;
    }

    public function updateAdminProgress(int $id, array $input): bool
    {
        return $this->participation->updateAdminProgress($id, [
            'progression' => (int)($input['progression'] ?? 0),
            'statut' => $input['statut'] ?? 'in_progress',
            'date_fin' => $input['date_fin'] ?? null,
        ]);
    }

    public function applyProofAction(int $participationId, int $proofId, string $action): array
    {
        $participation = $this->participation->getById($participationId);
        $proof = $this->proof->getByIdForParticipation($proofId, $participationId);
        if (!$participation || !$proof) {
            return [false, 'Preuve introuvable.'];
        }

        if ($action === 'approve') {
            return [$this->proof->updateReviewStateForParticipation($proofId, $participationId, 'approved'), ''];
        }

        if ($action === 'reject') {
            return [$this->proof->updateReviewStateForParticipation($proofId, $participationId, 'rejected'), ''];
        }

        if ($action === 'apply_ai') {
            if (empty($proof['ai_decision'])) {
                return [false, 'Aucune suggestion IA disponible.'];
            }
            if ($proof['ai_decision'] === 'approved') {
                $newProgress = min(100, (int)$participation['progression'] + (int)$proof['ai_progress_increment']);
                $newStatus = $newProgress >= 100 ? 'completed' : 'in_progress';
                $ok = $this->proof->updateReviewStateForParticipation($proofId, $participationId, 'approved')
                    && $this->participation->updateAdminProgress($participationId, [
                        'progression' => $newProgress,
                        'statut' => $newStatus,
                        'date_fin' => null,
                    ]);
                return [$ok, ''];
            }
            if ($proof['ai_decision'] === 'rejected') {
                return [$this->proof->updateReviewStateForParticipation($proofId, $participationId, 'rejected'), ''];
            }
            return [false, 'Suggestion IA incertaine. Verification manuelle requise.'];
        }

        return [false, 'Action invalide.'];
    }

    public function saveProof(int $participationId, int $userId, array $file): array
    {
        if (!$this->participation->userOwnsParticipation($participationId, $userId)) {
            return [false, 'Cette participation ne correspond pas a cet utilisateur.', null];
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return [false, 'Fichier de preuve obligatoire.', null];
        }

        $allowed = [
            'image/jpeg' => ['extension' => 'jpg', 'max_size' => 3 * 1024 * 1024],
            'image/png' => ['extension' => 'png', 'max_size' => 3 * 1024 * 1024],
            'image/webp' => ['extension' => 'webp', 'max_size' => 3 * 1024 * 1024],
            'video/mp4' => ['extension' => 'mp4', 'max_size' => 25 * 1024 * 1024],
            'video/webm' => ['extension' => 'webm', 'max_size' => 25 * 1024 * 1024],
            'video/quicktime' => ['extension' => 'mov', 'max_size' => 25 * 1024 * 1024],
        ];
        $mime = mime_content_type($file['tmp_name']);

        if (!isset($allowed[$mime])) {
            return [false, 'Format de preuve non autorise. Utilisez JPG, PNG, WEBP, MP4, WEBM ou MOV.', null];
        }

        if ((int)$file['size'] > $allowed[$mime]['max_size']) {
            return [false, str_starts_with($mime, 'video/') ? 'La video ne doit pas depasser 25 Mo.' : 'L image ne doit pas depasser 3 Mo.', null];
        }

        $uploadDir = __DIR__ . '/../storage/uploads/proofs';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $fileName = 'proof_' . $participationId . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime]['extension'];
        $target = $uploadDir . '/' . $fileName;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return [false, 'Impossible d enregistrer la preuve.', null];
        }

        $relativePath = 'storage/uploads/proofs/' . $fileName;
        if (!$this->proof->create($participationId, $relativePath)) {
            return [false, 'Impossible de creer la preuve.', null];
        }

        $proofId = (int)Database::getConnection()->lastInsertId();
        try {
            $gemini = new DefiGeminiService();
            if ($gemini->isConfigured()) {
                $participation = $this->participation->getById($participationId) ?? [];
                $review = $gemini->reviewProof($participation, $target, $mime);
                $this->proof->saveAiReview($proofId, $review);
            }
        } catch (Throwable $ignored) {
            try {
                $this->proof->saveAiReview($proofId, [
                    'decision' => 'error',
                    'confidence' => 0,
                    'progress_increment' => 0,
                    'reason' => 'Analyse IA indisponible.',
                ]);
            } catch (Throwable $alsoIgnored) {
            }
        }

        return [true, 'Preuve envoyee. Elle est en attente de revision.', $relativePath];
    }

    public function delete(int $id): bool
    {
        return $this->participation->delete($id);
    }

    private function isValidSqlDate(string $date): bool
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)) {
            return false;
        }
        return checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
    }
}
