<?php
require_once __DIR__ . '/../models/Participation.php';
require_once __DIR__ . '/../models/Defi.php';
require_once __DIR__ . '/../models/ParticipationProof.php';

class ParticipationsController
{
    private Participation $participation;
    private Defi $defi;
    private ParticipationProof $proof;
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->participation = new Participation($db);
        $this->defi = new Defi($db);
        $this->proof = new ParticipationProof($db);
        $this->db = $db;
    }

    public function index(): void
    {
        // Check if search is requested
        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

        if ($searchTerm) {
            $participations = $this->participation->searchByIdOrUser($searchTerm);
        } else {
            $participations = $this->participation->getAll();
        }

        $count = $this->participation->count();
        $search_term = $searchTerm;

        require __DIR__ . '/../views/participations/index.php';
    }

    public function create(): void
    {
        $errors = [];
        $participation = [
            'id_utilisateur' => '',
            'id_defi' => '',
            'progression' => '0',
            'statut' => 'in_progress',
            'date_debut' => '',
            'date_fin' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $participation = $this->sanitizeStartInput($_POST);
            $errors = $this->validateStart($participation);

            if (empty($errors)) {
                if ($this->participation->create($participation)) {
                    header('Location: index.php?entity=participations');
                    exit();
                }
                $errors[] = 'Erreur lors de l\'ajout.';
            }
        }

        require __DIR__ . '/../views/participations/form.php';
    }

    public function edit(): void
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            header('Location: index.php?entity=participations');
            exit();
        }

        $participation = $this->participation->getById($id);
        if (!$participation) {
            header('Location: index.php?entity=participations');
            exit();
        }

        $errors = [];
        $proofs = $this->proof->getByParticipationId($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['proof_action'], $_POST['proof_id'])) {
                $proofId = (int)$_POST['proof_id'];
                $proofAction = $_POST['proof_action'];
                $reviewState = $proofAction === 'approve' ? 'approved' : 'rejected';

                if ($proofAction === 'apply_ai') {
                    $proof = $this->proof->getByIdForParticipation($proofId, $id);

                    if (!$proof || empty($proof['ai_decision'])) {
                        $errors[] = 'Aucune suggestion IA disponible pour cette preuve.';
                    } elseif ($proof['ai_decision'] === 'approved') {
                        $newProgression = min(100, (int)$participation['progression'] + (int)$proof['ai_progress_increment']);
                        $newStatus = $newProgression >= 100 ? 'completed' : 'in_progress';

                        if (
                            $this->proof->updateReviewStateForParticipation($proofId, $id, 'approved')
                            && $this->participation->updateAdminProgress($id, [
                                'progression' => $newProgression,
                                'statut' => $newStatus,
                                'date_fin' => null,
                            ])
                        ) {
                            header('Location: index.php?entity=participations&action=edit&id=' . $id);
                            exit();
                        }
                        $errors[] = 'Erreur lors de l application de la suggestion IA.';
                    } elseif ($proof['ai_decision'] === 'rejected') {
                        if ($this->proof->updateReviewStateForParticipation($proofId, $id, 'rejected')) {
                            header('Location: index.php?entity=participations&action=edit&id=' . $id);
                            exit();
                        }
                        $errors[] = 'Erreur lors de l application de la suggestion IA.';
                    } else {
                        $errors[] = 'La suggestion IA est incertaine. Verification manuelle requise.';
                    }
                }

                if ($proofAction !== 'apply_ai' && $this->proof->updateReviewStateForParticipation($proofId, $id, $reviewState)) {
                    header('Location: index.php?entity=participations');
                    exit();
                }
                $errors[] = 'Erreur lors de la révision de la preuve.';
            } else {
                $input = $this->sanitizeAdminInput($_POST);
                $errors = $this->validateAdminUpdate($input);

                if (empty($errors)) {
                    if ($this->participation->updateAdminProgress($id, $input)) {
                        header('Location: index.php?entity=participations');
                        exit();
                    }
                    $errors[] = 'Erreur lors de la modification.';
                }

                $participation = array_merge($participation, $input);
            }

            $proofs = $this->proof->getByParticipationId($id);
        }

        $action = 'edit';
        require __DIR__ . '/../views/participations/form.php';
    }

    public function delete(): void
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            header('Location: index.php?entity=participations');
            exit();
        }

        $participation = $this->participation->getById($id);
        if (!$participation) {
            header('Location: index.php?entity=participations');
            exit();
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'oui') {
            if ($this->participation->delete($id)) {
                header('Location: index.php?entity=participations&deleted=1');
                exit();
            }
            $errors[] = 'Erreur lors de la suppression.';
        }

        require __DIR__ . '/../views/participations/delete.php';
    }

    private function sanitizeStartInput(array $input): array
    {
        // Convert DD/MM/YYYY to YYYY-MM-DD for dates
        $dateDebut = $this->convertDateFormat($input['date_debut'] ?? '');

        return [
            'id_utilisateur' => intval($input['id_utilisateur'] ?? 0),
            'id_defi' => intval($input['id_defi'] ?? 0),
            'progression' => 0,
            'statut' => 'in_progress',
            'date_debut' => $dateDebut,
            'date_fin' => null
        ];
    }

    private function sanitizeAdminInput(array $input): array
    {
        return [
            'progression' => intval($input['progression'] ?? 0),
            'statut' => $input['statut'] ?? 'in_progress',
            'date_fin' => !empty($input['date_fin']) ? $this->convertDateFormat($input['date_fin']) : null,
        ];
    }

    private function convertDateFormat(string $date): string
    {
        // Convert DD/MM/YYYY to YYYY-MM-DD
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $matches)) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }
        return '';
    }

    private function validateStart(array $input): array
    {
        $errors = [];
        $today = date('Y-m-d');

        if ($input['id_utilisateur'] <= 0) {
            $errors[] = 'ID Utilisateur invalide';
        } elseif (!$this->participation->userExists($input['id_utilisateur'])) {
            $errors[] = 'Utilisateur introuvable ou inactif';
        }

        if ($input['id_defi'] <= 0) {
            $errors[] = 'ID Défi invalide';
        } else {
            // Check if the defi exists
            if (!$this->defi->getById($input['id_defi'])) {
                $errors[] = 'Le défi sélectionné n\'existe pas';
            }
        }

        if (
            $input['id_utilisateur'] > 0
            && $input['id_defi'] > 0
            && $this->participation->existsForUserAndDefi($input['id_utilisateur'], $input['id_defi'])
        ) {
            $errors[] = 'Cet utilisateur participe dÃ©jÃ  Ã  ce dÃ©fi.';
        }

        // Validate date_debut
        if (empty($input['date_debut'])) {
            $errors[] = 'Date début est requise';
        } else {
            // The input contains YYYY-MM-DD (already converted), but we validate the original format
            // Reconstruct the original DD/MM/YYYY for validation
            if (!$this->validateDateFromPost($_POST['date_debut'] ?? '')) {
                $errors[] = 'Format de date début invalide (JJ/MM/AAAA)';
            } elseif ($input['date_debut'] < $today) {
                $errors[] = 'Date début ne peut pas être une date ancienne';
            }
        }

        // Validate date_fin (optional but if provided must be after date_debut)
        if (!empty($input['date_fin'])) {
            if (!$this->validateDateFromPost($_POST['date_fin'] ?? '')) {
                $errors[] = 'Format de date fin invalide (JJ/MM/AAAA)';
            } elseif ($input['date_fin'] < $today) {
                $errors[] = 'Date fin ne peut pas être une date ancienne';
            } elseif (!empty($input['date_debut']) && $input['date_fin'] <= $input['date_debut']) {
                $errors[] = 'Date fin doit être après la date début';
            }
        }

        return $errors;
    }

    private function validateAdminUpdate(array $input): array
    {
        $errors = [];

        if ($input['progression'] < 0 || $input['progression'] > 100) {
            $errors[] = 'La progression doit être entre 0 et 100%.';
        }

        if (!in_array($input['statut'], ['in_progress', 'completed', 'failed'], true)) {
            $errors[] = 'Statut invalide.';
        }

        if ($input['statut'] === 'completed' && $input['progression'] !== 100) {
            $errors[] = 'Une participation ne peut être terminée que si la progression est à 100%.';
        }

        if ($input['statut'] === 'in_progress' && $input['progression'] === 100) {
            $errors[] = 'Une participation à 100% doit être marquée comme terminée ou ajustée.';
        }

        if (!empty($input['date_fin']) && !$this->isValidSqlDate($input['date_fin'])) {
            $errors[] = 'Format de date fin invalide.';
        }

        return $errors;
    }

    private function isValidSqlDate(string $date): bool
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
            return false;
        }

        return checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1]);
    }

    private function isValidDate(string $date): bool
    {
        // Check if date follows DD/MM/YYYY format
        if (!preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $matches)) {
            return false;
        }

        // Verify it's a valid date
        $day = intval($matches[1]);
        $month = intval($matches[2]);
        $year = intval($matches[3]);

        return checkdate($month, $day, $year);
    }

    private function validateDateFromPost(string $date): bool
    {
        // Validate date in DD/MM/YYYY format from POST
        return $this->isValidDate($date);
    }
}
?>

