<?php require_once __DIR__ . '/../layout/header.php'; ?>

<div class="form-container">
    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php
        $isEdit = isset($action) && $action === 'edit';
        $statusLabels = [
            'in_progress' => 'En cours',
            'completed' => 'Terminée',
            'failed' => 'Échouée',
        ];
        $proofLabels = [
            'pending' => 'En attente',
            'approved' => 'Approuvée',
            'rejected' => 'Rejetée',
        ];
    ?>

    <h2><?php echo $isEdit ? 'Réviser Participation #' . $id : 'Créer une participation'; ?></h2>

    <form method="POST">
        <div class="row">
            <div class="col-md-6">
                <label for="id_utilisateur" class="form-label">ID Utilisateur *</label>
                <input type="number" class="form-control" id="id_utilisateur" name="id_utilisateur" min="1" step="1" inputmode="numeric" value="<?php echo htmlspecialchars($participation['id_utilisateur'] ?? ''); ?>" <?php echo $isEdit ? 'readonly' : ''; ?>>
                <small class="form-text text-muted d-block mt-1">
                    <i class="fas fa-lightbulb me-1"></i>Entrez un nombre entier positif (L'identifiant numérique de l'utilisateur)
                </small>
            </div>
            <div class="col-md-6">
                <label for="id_defi" class="form-label">ID Défi *</label>
                <input type="number" class="form-control" id="id_defi" name="id_defi" min="1" step="1" inputmode="numeric" value="<?php echo htmlspecialchars($participation['id_defi'] ?? ''); ?>" <?php echo $isEdit ? 'readonly' : ''; ?>>
                <small class="form-text text-muted d-block mt-1">
                    <i class="fas fa-lightbulb me-1"></i>Entrez un nombre entier positif (L'identifiant numérique du défi)
                </small>
            </div>
        </div>

        <?php if (!$isEdit): ?>
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-2"></i>À la création, la progression est automatiquement fixée à 0% et le statut à « En cours ».
            </div>
        <?php else: ?>
        <div class="row mt-3">
            <div class="col-md-6">
                <label for="progression" class="form-label">Progression (%) *</label>
                <input type="text" class="form-control" id="progression" name="progression" value="<?php echo htmlspecialchars($participation['progression'] ?? '0'); ?>">
                <small class="form-text text-muted d-block mt-1">
                    <i class="fas fa-lightbulb me-1"></i>Seul l'administrateur met à jour la progression après vérification des preuves.
                </small>
            </div>
            <div class="col-md-6">
                <label for="statut" class="form-label">Statut *</label>
                <select class="form-select" id="statut" name="statut">
                    <option value="">Choisir un statut</option>
                    <option value="in_progress" <?php echo ($participation['statut'] ?? '') === 'in_progress' ? 'selected' : ''; ?>>En cours</option>
                    <option value="completed" <?php echo ($participation['statut'] ?? '') === 'completed' ? 'selected' : ''; ?>>Terminée</option>
                    <option value="failed" <?php echo ($participation['statut'] ?? '') === 'failed' ? 'selected' : ''; ?>>Échouée</option>
                </select>
                <small class="form-text text-muted d-block mt-1">
                    <i class="fas fa-lightbulb me-1"></i>Le statut « Terminée » exige une progression à 100%.
                </small>
            </div>
        </div>
        <?php endif; ?>

        <div class="row mt-3">
            <div class="col-md-6">
                <label for="date_debut" class="form-label">Date début *</label>
                <input type="text" class="form-control" id="date_debut" name="date_debut" value="<?php
                    $dateDebut = $participation['date_debut'] ?? '';
                    if (!empty($dateDebut) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDebut)) {
                        $parts = explode('-', $dateDebut);
                        echo htmlspecialchars($parts[2] . '/' . $parts[1] . '/' . $parts[0]);
                    } else {
                        echo htmlspecialchars($dateDebut);
                    }
                ?>" placeholder="JJ/MM/AAAA" <?php echo $isEdit ? 'readonly' : ''; ?>>
                <small class="form-text text-muted d-block mt-1">
                    <i class="fas fa-lightbulb me-1"></i><?php echo $isEdit ? 'La date de départ reste liée au démarrage de la participation.' : 'Format: JJ/MM/AAAA. Les slashes s\'ajoutent automatiquement.'; ?>
                </small>
            </div>
            <?php if ($isEdit): ?>
            <div class="col-md-6">
                <label for="date_fin" class="form-label">Date fin (optionnel)</label>
                <input type="text" class="form-control" id="date_fin" name="date_fin" value="<?php
                    $dateFin = $participation['date_fin'] ?? '';
                    if ($dateFin === '0000-00-00') {
                        $dateFin = '';
                    }
                    if (!empty($dateFin) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFin)) {
                        $parts = explode('-', $dateFin);
                        echo htmlspecialchars($parts[2] . '/' . $parts[1] . '/' . $parts[0]);
                    } else {
                        echo htmlspecialchars($dateFin);
                    }
                ?>" placeholder="JJ/MM/AAAA">
                <small class="form-text text-muted d-block mt-1">
                    <i class="fas fa-lightbulb me-1"></i>Laissez vide si le défi est toujours en cours. La date est automatique si le statut devient terminé.
                </small>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($isEdit): ?>
            <div class="mt-4">
                <h3 class="h5">Preuves soumises</h3>
                <?php if (empty($proofs)): ?>
                    <div class="alert alert-secondary">Aucune preuve soumise pour cette participation.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Preuve</th>
                                    <th>Suggestion IA</th>
                                    <th>État</th>
                                    <th>Soumise le</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($proofs as $proof): ?>
                                    <tr>
                                        <td>#<?php echo (int)$proof['id']; ?></td>
                                        <td>
                                            <a href="../<?php echo htmlspecialchars($proof['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                Voir la preuve
                                            </a>
                                        </td>
                                        <td>
                                            <?php if (!empty($proof['ai_decision'])): ?>
                                                <?php
                                                    $aiDecision = $proof['ai_decision'];
                                                    $aiClasses = ['approved' => 'success', 'rejected' => 'danger', 'uncertain' => 'warning', 'error' => 'secondary'];
                                                    $aiLabels = ['approved' => 'Approbation suggeree', 'rejected' => 'Rejet suggere', 'uncertain' => 'Incertain', 'error' => 'Indisponible'];
                                                ?>
                                                <span class="badge bg-<?php echo htmlspecialchars($aiClasses[$aiDecision] ?? 'secondary'); ?>">
                                                    <?php echo htmlspecialchars($aiLabels[$aiDecision] ?? $aiDecision); ?>
                                                </span>
                                                <div class="small text-muted mt-1">
                                                    Confiance: <?php echo (int)($proof['ai_confidence'] ?? 0); ?>%
                                                    | Progression: +<?php echo (int)($proof['ai_progress_increment'] ?? 0); ?>%
                                                </div>
                                                <?php if (!empty($proof['ai_reason'])): ?>
                                                    <div class="small mt-1"><?php echo htmlspecialchars($proof['ai_reason']); ?></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Non analysee</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($proofLabels[$proof['review_state']] ?? $proof['review_state']); ?></td>
                                        <td><?php echo htmlspecialchars($proof['created_at']); ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="proof_id" value="<?php echo (int)$proof['id']; ?>">
                                                <?php if (in_array($proof['ai_decision'] ?? '', ['approved', 'rejected'], true)): ?>
                                                    <button type="submit" name="proof_action" value="apply_ai" class="btn btn-sm btn-outline-primary">Appliquer IA</button>
                                                <?php endif; ?>
                                                <button type="submit" name="proof_action" value="approve" class="btn btn-sm btn-success">Approuver</button>
                                                <button type="submit" name="proof_action" value="reject" class="btn btn-sm btn-danger">Rejeter</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i>
                <?php echo $isEdit ? 'Enregistrer la révision' : 'Créer'; ?>
            </button>
            <a href="index.php?entity=participations" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Annuler
            </a>
        </div>
    </form>

    <script>
        // Function to validate numeric input with 8 digit maximum
        function restrictToNumbers(element, maxDigits = 8) {
            element.addEventListener('keypress', function(e) {
                // Only allow digits
                if (!/[0-9]/.test(e.key)) {
                    e.preventDefault();
                    return false;
                }
                // Prevent exceeding max digits
                if (this.value.length >= maxDigits) {
                    e.preventDefault();
                    return false;
                }
            });

            element.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                // Only accept if all characters are digits
                if (/^[0-9]*$/.test(pastedText)) {
                    const newValue = (this.value + pastedText).slice(0, maxDigits);
                    this.value = newValue;
                }
            });

            // Restrict input on any change
            element.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, maxDigits);
            });
        }

        // Function to format date input as DD/MM/YYYY with auto-slashes
        function formatDateInput(element) {
            element.addEventListener('input', function(e) {
                let value = this.value.replace(/[^0-9]/g, ''); // Remove all non-digits
                
                if (value.length <= 2) {
                    this.value = value;
                } else if (value.length <= 4) {
                    this.value = value.slice(0, 2) + '/' + value.slice(2);
                } else if (value.length <= 8) {
                    this.value = value.slice(0, 2) + '/' + value.slice(2, 4) + '/' + value.slice(4, 8);
                } else {
                    this.value = value.slice(0, 2) + '/' + value.slice(2, 4) + '/' + value.slice(4, 8);
                }
            });

            // Prevent paste of non-numeric characters
            element.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                // Only accept if all characters are digits (and slashes for convenience)
                const cleanedText = pastedText.replace(/[^0-9]/g, '');
                
                if (cleanedText.length > 0) {
                    let formatted = cleanedText;
                    if (formatted.length <= 2) {
                        this.value = formatted;
                    } else if (formatted.length <= 4) {
                        this.value = formatted.slice(0, 2) + '/' + formatted.slice(2);
                    } else if (formatted.length <= 8) {
                        this.value = formatted.slice(0, 2) + '/' + formatted.slice(2, 4) + '/' + formatted.slice(4, 8);
                    } else {
                        this.value = formatted.slice(0, 2) + '/' + formatted.slice(2, 4) + '/' + formatted.slice(4, 8);
                    }
                }
            });
        }

        // Apply validation to numeric fields
        document.addEventListener('DOMContentLoaded', function() {
            const userId = document.getElementById('id_utilisateur');
            const defiId = document.getElementById('id_defi');
            const progression = document.getElementById('progression');
            if (userId && !userId.readOnly) restrictToNumbers(userId, 8);
            if (defiId && !defiId.readOnly) restrictToNumbers(defiId, 8);
            if (progression) restrictToNumbers(progression, 3);
            
            // Apply date formatting
            const dateDebut = document.getElementById('date_debut');
            const dateFin = document.getElementById('date_fin');
            if (dateDebut && !dateDebut.readOnly) formatDateInput(dateDebut);
            if (dateFin) formatDateInput(dateFin);
        });
    </script>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

