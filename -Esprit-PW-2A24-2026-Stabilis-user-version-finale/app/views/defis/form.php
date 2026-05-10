<?php require_once __DIR__ . '/../layout/header.php'; ?>

<div class="form-container">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <h2><?php echo isset($action) && $action === 'create' ? 'Créer un nouveau Défi' : 'Modifier le Défi #' . htmlspecialchars($defi['id'] ?? ''); ?></h2>

    <form method="POST" action="">
        <?php if (isset($action) && $action === 'create'): ?>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="id" class="form-label">ID du Defi (optionnel)</label>
                    <input type="number" class="form-control" id="id" name="id" value="<?php echo htmlspecialchars($defi['id'] ?? ''); ?>" placeholder="Laisser vide pour auto-increment">
                    <small class="form-text text-muted d-block mt-1">
                        <i class="fas fa-info-circle me-1"></i>Laissez vide pour utiliser l auto-increment, comme les defis generes par IA.
                    </small>
                </div>
            </div>
        <?php else: ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($defi['id'] ?? ''); ?>">
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <label for="nom" class="form-label">Nom du Défi *</label>
                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($defi['nom'] ?? ''); ?>">
                <small class="form-text text-muted d-block mt-1">
                    <i class="fas fa-lightbulb me-1"></i>Saisissez un titre court et descriptif du défi (max 255 caractères). La validation sera effectuée en PHP.
                </small>
            </div>
            <div class="col-md-6">
                <label for="type" class="form-label">Type de Défi *</label>
                <select class="form-select" id="type" name="type">
                    <option value="">Choisir un type</option>
                    <option value="aliment" <?php echo ($defi['type'] ?? '') === 'aliment' ? 'selected' : ''; ?>>Alimentaire</option>
                    <option value="entrainement" <?php echo ($defi['type'] ?? '') === 'entrainement' ? 'selected' : ''; ?>>Entraînement</option>
                    <option value="compensation" <?php echo ($defi['type'] ?? '') === 'compensation' ? 'selected' : ''; ?>>Compensation</option>
                </select>
                <small class="form-text text-muted d-block mt-1">
                    <i class="fas fa-lightbulb me-1"></i><strong>Alimentaire:</strong> Défis liés à la nutrition | <strong>Entraînement:</strong> Défis sportifs | <strong>Compensation:</strong> Récompenses ou activités
                </small>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-12">
                <label for="objectif" class="form-label">Objectif *</label>
                <textarea class="form-control" id="objectif" name="objectif" rows="4"><?php echo htmlspecialchars($defi['objectif'] ?? ''); ?></textarea>
                <small class="form-text text-muted d-block mt-1">
                    <i class="fas fa-lightbulb me-1"></i>Décrivez précisément le défi et ses conditions de réussite. La validation sera effectuée en PHP.
                </small>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-12">
                <label for="recompense" class="form-label">Récompense *</label>
                <input type="text" class="form-control" id="recompense" name="recompense" value="<?php echo htmlspecialchars($defi['recompense'] ?? ''); ?>">
                <small class="form-text text-muted d-block mt-1">
                    <i class="fas fa-lightbulb me-1"></i>Spécifiez la récompense pour ceux qui réussissent le défi (Ex: 50 points, 1 jour libre). La validation sera effectuée en PHP.
                </small>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i>
                <?php echo isset($action) && $action === 'create' ? 'Créer' : 'Modifier'; ?>
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Annuler
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
