<?php require_once __DIR__ . '/../layout/header.php'; ?>

<div class="form-container">
    <h2>Confirmer suppression Participation #<?php echo $id; ?></h2>
    <p>Supprimer définitivement ?</p>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="confirm" value="oui">
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash me-1"></i>Supprimer
            </button>
            <a href="index.php?entity=participations" class="btn btn-secondary">
                <i class="fas fa-times me-1"></i>Annuler
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

