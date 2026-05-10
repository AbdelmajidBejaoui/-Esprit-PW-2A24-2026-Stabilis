<?php require_once __DIR__ . '/../layout/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow">
            <div class="card-body text-center p-5">
                <div class="mb-4">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                </div>
                
                <h3 class="mb-4">Confirmer la suppression</h3>
                
                <div class="alert alert-warning">
                    <h6>Attention !</h6>
                    <p>Vous êtes sur le point de supprimer définitivement le défi :</p>
                    <div class="bg-light p-3 rounded">
                        <strong>"<?php echo htmlspecialchars($defi['nom']); ?>"</strong>
                        <br><small class="text-muted">Type: <?php echo ucfirst($defi['type']); ?></small>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                    <p><i class="fas fa-times-circle me-2"></i><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="confirm" value="oui">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="index.php" class="btn btn-secondary btn-lg px-4 me-md-2">
                            <i class="fas fa-times me-2"></i>Annuler
                        </a>
                        <button type="submit" class="btn btn-danger btn-lg px-4">
                            <i class="fas fa-trash me-2"></i>Oui, Supprimer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
