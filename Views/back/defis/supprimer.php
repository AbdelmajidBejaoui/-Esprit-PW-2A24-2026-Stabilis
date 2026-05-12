<?php
require_once __DIR__ . '/../../../Controllers/DefiController.php';

$controller = new DefiController();
$id = (int)($_GET['id'] ?? 0);
$defi = $controller->getById($id);
if (!$defi) {
    die('Defi introuvable.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->delete($id);
    header('Location: liste.php?deleted=1');
    exit;
}

$title = 'Supprimer un defi - Stabilis';
require_once __DIR__ . '/../../partials/header.php';
?>
<div class="form-card" style="padding:24px;">
    <h1>Supprimer le defi</h1>
    <p>Confirmer la suppression de <strong><?php echo htmlspecialchars($defi['nom']); ?></strong> ? Les participations liees seront aussi supprimees.</p>
    <form method="POST">
        <button class="btn-primary" type="submit" style="background:#B94A48;"><i class="fas fa-trash"></i> Supprimer</button>
        <a class="btn-secondary" href="liste.php">Annuler</a>
    </form>
</div>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
