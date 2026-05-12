<?php
require_once __DIR__ . '/../../../Controllers/ParticipationController.php';

$controller = new ParticipationController();
$id = (int)($_GET['id'] ?? 0);
$participation = $controller->getById($id);
if (!$participation) {
    die('Participation introuvable.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->delete($id);
    header('Location: liste.php?deleted=1');
    exit;
}
$title = 'Supprimer participation - Stabilis';
require_once __DIR__ . '/../../partials/header.php';
?>
<div class="form-card" style="padding:24px;">
    <h1>Supprimer participation</h1>
    <p>Confirmer la suppression de cette participation ?</p>
    <form method="POST">
        <button class="btn-primary" style="background:#B94A48;" type="submit"><i class="fas fa-trash"></i> Supprimer</button>
        <a class="btn-secondary" href="liste.php">Annuler</a>
    </form>
</div>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
