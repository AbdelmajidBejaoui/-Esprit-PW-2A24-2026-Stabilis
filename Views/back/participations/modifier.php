<?php
require_once __DIR__ . '/../../../Controllers/ParticipationController.php';

$controller = new ParticipationController();
$id = (int)($_GET['id'] ?? 0);
$participation = $controller->getById($id);
if (!$participation) {
    die('Participation introuvable.');
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['proof_action'], $_POST['proof_id'])) {
        [$ok, $message] = $controller->applyProofAction($id, (int)$_POST['proof_id'], $_POST['proof_action']);
        if ($ok) {
            header('Location: modifier.php?id=' . $id);
            exit;
        }
        $errors[] = $message ?: 'Erreur lors de la revision.';
    } else {
        $input = [
            'progression' => trim((string)($_POST['progression'] ?? '')),
            'statut' => $_POST['statut'] ?? 'in_progress',
            'date_fin' => $_POST['date_fin'] ?? null,
        ];
        $errors = $controller->validateAdminUpdate($input);
        if (!$errors && $controller->updateAdminProgress($id, $input)) {
            header('Location: liste.php');
            exit;
        }
    }
    $participation = $controller->getById($id);
}

$proofs = $controller->getProofs($id);
$title = 'Modifier participation - Stabilis';
require_once __DIR__ . '/../../partials/header.php';
?>
<div class="form-card" style="padding:24px; max-width:1000px;">
    <h1>Modifier participation</h1>
    <p class="text-muted"><?php echo htmlspecialchars(($participation['utilisateur_nom'] ?? '') . ' - ' . ($participation['defi_nom'] ?? '')); ?></p>
    <?php if ($errors): ?><div class="alert"><ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="POST">
        <div class="form-group"><label>Progression (%)</label><input class="form-control" name="progression" value="<?php echo (int)$participation['progression']; ?>"></div>
        <div class="form-group"><label>Statut</label><select class="form-control" name="statut">
            <?php foreach (['in_progress','completed','failed'] as $s): ?><option value="<?php echo $s; ?>" <?php echo $participation['statut'] === $s ? 'selected' : ''; ?>><?php echo $s; ?></option><?php endforeach; ?>
        </select></div>
        <div class="form-group"><label>Date fin</label><input class="form-control" type="date" name="date_fin" value="<?php echo htmlspecialchars($participation['date_fin'] ?? ''); ?>"></div>
        <button class="btn-primary" type="submit"><i class="fas fa-save"></i> Enregistrer progression</button>
        <a class="btn-secondary" href="liste.php">Retour</a>
    </form>
</div>

<div class="table-card" style="margin-top:24px;">
    <div class="table-header"><h3>Preuves envoyees</h3><span class="record-count"><?php echo count($proofs); ?> preuve(s)</span></div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Fichier</th><th>Etat</th><th>Suggestion IA</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (!$proofs): ?><tr><td colspan="5" class="text-center">Aucune preuve.</td></tr><?php endif; ?>
            <?php foreach ($proofs as $proof): ?>
                <tr>
                    <td><a href="/AdminLTE3/<?php echo htmlspecialchars($proof['file_path']); ?>" target="_blank"><?php echo htmlspecialchars(basename($proof['file_path'])); ?></a></td>
                    <td><?php echo htmlspecialchars($proof['review_state']); ?></td>
                    <td><?php echo htmlspecialchars($proof['ai_decision'] ?? 'non analysee'); ?><?php if (isset($proof['ai_confidence'])): ?> - <?php echo (int)$proof['ai_confidence']; ?>%<br><span class="text-muted"><?php echo htmlspecialchars($proof['ai_reason'] ?? ''); ?></span><?php endif; ?></td>
                    <td><?php echo htmlspecialchars($proof['created_at']); ?></td>
                    <td>
                        <form method="POST" style="display:inline-flex; gap:4px;">
                            <input type="hidden" name="proof_id" value="<?php echo (int)$proof['id']; ?>">
                            <button class="btn-icon" name="proof_action" value="approve" title="Approuver"><i class="fas fa-check"></i></button>
                            <button class="btn-icon btn-icon-danger" name="proof_action" value="reject" title="Rejeter"><i class="fas fa-times"></i></button>
                            <?php if (in_array($proof['ai_decision'] ?? '', ['approved', 'rejected'], true)): ?>
                                <button class="btn-icon" name="proof_action" value="apply_ai" title="Appliquer IA"><i class="fas fa-robot"></i></button>
                            <?php endif; ?>
                            <?php if (!in_array($proof['ai_decision'] ?? '', ['approved', 'rejected'], true)): ?>
                                <button class="btn-icon" name="proof_action" value="analyze_ai" title="Analyser IA"><i class="fas fa-sync-alt"></i></button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
