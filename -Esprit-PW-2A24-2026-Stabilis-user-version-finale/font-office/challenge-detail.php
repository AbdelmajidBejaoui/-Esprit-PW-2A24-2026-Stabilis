<?php
$page_title = "Detail du defi";
include 'config.php';
include 'header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$defi = null;

if ($id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT id, nom, type, objectif, recompense FROM defis WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $defi = $result ? mysqli_fetch_assoc($result) : null;
}
?>

<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <?php if (!$defi): ?>
                    <div class="alert alert-warning">
                        Defi introuvable. <a href="challenges.php">Retour aux defis</a>
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                                <div>
                                    <h1 class="card-title mb-2"><?php echo htmlspecialchars($defi['nom']); ?></h1>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars(ucfirst($defi['type'])); ?></span>
                                </div>
                                <a href="challenges.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-arrow-left me-1"></i>Retour
                                </a>
                            </div>
                            <h2 class="h5 mt-4">Objectif</h2>
                            <p class="card-text"><?php echo htmlspecialchars($defi['objectif']); ?></p>
                            <div class="alert alert-success mb-0">
                                <i class="fas fa-coins me-2"></i>
                                Recompense: <?php echo htmlspecialchars($defi['recompense']); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
