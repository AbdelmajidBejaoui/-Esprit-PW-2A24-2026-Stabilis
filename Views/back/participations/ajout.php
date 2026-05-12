<?php
require_once __DIR__ . '/../../../Controllers/ParticipationController.php';
require_once __DIR__ . '/../../../Controllers/DefiController.php';
require_once __DIR__ . '/../../../Controllers/UserC.php';

$controller = new ParticipationController();
$defiController = new DefiController();
$userController = new UserC();
$errors = [];
$values = ['id_utilisateur' => '', 'id_defi' => '', 'date_debut' => date('Y-m-d')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = [
        'id_utilisateur' => (int)($_POST['id_utilisateur'] ?? 0),
        'id_defi' => (int)($_POST['id_defi'] ?? 0),
        'date_debut' => $_POST['date_debut'] ?? date('Y-m-d'),
    ];
    [$ok, $errors] = $controller->start($values);
    if ($ok) {
        header('Location: liste.php');
        exit;
    }
    if (!$errors) {
        $errors[] = 'Erreur lors de la creation.';
    }
}

$users = $userController->listUsers('', null, 0, 'recent');
$defis = $defiController->getAll();
$title = 'Ajouter participation - Stabilis';
require_once __DIR__ . '/../../partials/header.php';
?>
<div class="form-card" style="padding:24px;">
    <h1>Ajouter une participation</h1>
    <?php if ($errors): ?><div class="alert"><ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="POST">
        <div class="form-group"><label>Utilisateur actif</label><select class="form-control" name="id_utilisateur">
            <option value="">Choisir</option>
            <?php foreach ($users as $u): if ((int)$u['statut_compte'] !== 1) continue; ?><option value="<?php echo (int)$u['id']; ?>" <?php echo (int)$values['id_utilisateur'] === (int)$u['id'] ? 'selected' : ''; ?>>#<?php echo (int)$u['id']; ?> - <?php echo htmlspecialchars($u['nom'] . ' (' . $u['email'] . ')'); ?></option><?php endforeach; ?>
        </select></div>
        <div class="form-group"><label>Defi</label><select class="form-control" name="id_defi">
            <option value="">Choisir</option>
            <?php foreach ($defis as $d): ?><option value="<?php echo (int)$d['id']; ?>" <?php echo (int)$values['id_defi'] === (int)$d['id'] ? 'selected' : ''; ?>>#<?php echo (int)$d['id']; ?> - <?php echo htmlspecialchars($d['nom']); ?></option><?php endforeach; ?>
        </select></div>
        <div class="form-group"><label>Date debut</label><input class="form-control" type="date" name="date_debut" value="<?php echo htmlspecialchars($values['date_debut']); ?>"></div>
        <button class="btn-primary" type="submit"><i class="fas fa-save"></i> Enregistrer</button>
        <a class="btn-secondary" href="liste.php">Annuler</a>
    </form>
</div>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
