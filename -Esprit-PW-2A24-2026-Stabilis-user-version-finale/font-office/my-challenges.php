<?php
$page_title = "Mes défis";
include 'config.php';
include 'header.php';

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$participations = [];
$proofsByParticipation = [];

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

if ($userId > 0) {
    $stmt = mysqli_prepare($conn, "
        SELECT p.*, d.nom AS defi_nom, d.objectif AS defi_objectif, d.recompense AS defi_recompense
        FROM participations p
        LEFT JOIN defis d ON d.id = p.id_defi
        WHERE p.id_utilisateur = ?
        ORDER BY p.id DESC
    ");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $participations = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];

    foreach ($participations as $participation) {
        $proofStmt = mysqli_prepare($conn, "
            SELECT *
            FROM participation_proofs
            WHERE participation_id = ?
            ORDER BY created_at DESC, id DESC
        ");
        mysqli_stmt_bind_param($proofStmt, 'i', $participation['id']);
        mysqli_stmt_execute($proofStmt);
        $proofResult = mysqli_stmt_get_result($proofStmt);
        $proofsByParticipation[$participation['id']] = $proofResult ? mysqli_fetch_all($proofResult, MYSQLI_ASSOC) : [];
    }
}
?>

<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge badge-success mb-3">Mes participations</span>
            <h1 class="display-5 fw-bold">Mes défis démarrés</h1>
            <p class="lead text-muted">Suivez votre progression et envoyez vos preuves pour révision.</p>
        </div>

        <form method="GET" class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <label for="user_id" class="form-label fw-semibold">Votre ID utilisateur</label>
                <div class="input-group">
                    <input type="number" class="form-control" id="user_id" name="user_id" min="1" value="<?php echo $userId > 0 ? htmlspecialchars((string)$userId) : ''; ?>" placeholder="Ex: 2">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search me-2"></i>Afficher mes défis
                    </button>
                </div>
            </div>
        </form>

        <?php if ($userId <= 0): ?>
            <div class="alert alert-info">Entrez votre ID utilisateur pour afficher vos défis commencés.</div>
        <?php elseif (empty($participations)): ?>
            <div class="alert alert-warning">Aucun défi démarré pour cet utilisateur.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($participations as $participation): ?>
                    <?php $proofs = $proofsByParticipation[$participation['id']] ?? []; ?>
                    <div class="col-lg-6">
                        <article class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                    <div>
                                        <h2 class="h5 mb-1"><?php echo htmlspecialchars($participation['defi_nom'] ?? 'Défi #' . $participation['id_defi']); ?></h2>
                                        <small class="text-muted">Participation #<?php echo (int)$participation['id']; ?></small>
                                    </div>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($statusLabels[$participation['statut']] ?? $participation['statut']); ?></span>
                                </div>

                                <p class="text-muted"><?php echo htmlspecialchars($participation['defi_objectif'] ?? ''); ?></p>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <strong>Progression</strong>
                                        <span><?php echo (int)$participation['progression']; ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo (int)$participation['progression']; ?>%;"></div>
                                    </div>
                                </div>

                                <form class="proof-upload-form" enctype="multipart/form-data">
                                    <input type="hidden" name="participation_id" value="<?php echo (int)$participation['id']; ?>">
                                    <input type="hidden" name="id_utilisateur" value="<?php echo (int)$userId; ?>">
                                    <label class="form-label fw-semibold">Ajouter une preuve</label>
                                    <div class="input-group mb-2">
                                        <input type="file" class="form-control" name="proof_file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime" required>
                                        <button type="submit" class="btn btn-outline-success">
                                            <i class="fas fa-upload me-1"></i>Envoyer
                                        </button>
                                    </div>
                                    <small class="text-muted">Image JPG, PNG, WEBP ou video MP4, WEBM, MOV. La preuve sera en attente de revision.</small>
                                    <div class="proof-upload-message mt-2"></div>
                                </form>

                                <hr>

                                <h3 class="h6">Preuves envoyées</h3>
                                <?php if (empty($proofs)): ?>
                                    <p class="text-muted mb-0">Aucune preuve envoyée pour le moment.</p>
                                <?php else: ?>
                                    <ul class="list-unstyled mb-0">
                                        <?php foreach ($proofs as $proof): ?>
                                            <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                                <a href="../<?php echo htmlspecialchars($proof['file_path']); ?>" target="_blank">Voir la preuve</a>
                                                <span class="badge bg-<?php echo $proof['review_state'] === 'approved' ? 'success' : ($proof['review_state'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                                    <?php echo htmlspecialchars($proofLabels[$proof['review_state']] ?? $proof['review_state']); ?>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.querySelectorAll('.proof-upload-form').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const message = form.querySelector('.proof-upload-message');
        const button = form.querySelector('button[type="submit"]');
        const original = button.innerHTML;

        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Envoi...';
        message.className = 'proof-upload-message mt-2 text-muted';
        message.textContent = 'Envoi de la preuve...';

        try {
            const response = await fetch('../app/api/upload-proof.php', {
                method: 'POST',
                body: new FormData(form)
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Erreur lors de l envoi.');
            }

            message.className = 'proof-upload-message mt-2 text-success';
            message.textContent = data.message;
            setTimeout(() => window.location.reload(), 900);
        } catch (error) {
            message.className = 'proof-upload-message mt-2 text-danger';
            message.textContent = error.message;
        } finally {
            button.disabled = false;
            button.innerHTML = original;
        }
    });
});
</script>

<?php include 'footer.php'; ?>
