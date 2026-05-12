<?php
$title = "Ajouter seance - Stabilis";
require_once __DIR__ . '/../../../config/entrainements.php';
require_once __DIR__ . '/../../../Controllers/SeanceC.php';
require_once __DIR__ . '/../../../Models/Seance.php';

$db = config::getConnexion();
$seanceC = new SeanceC();
$users = $db->query("SELECT id, nom, email, COALESCE(poids, 70) AS poids FROM `user` ORDER BY nom")->fetchAll();
$entrainements = $db->query("SELECT id, nom, met_value FROM entrainements ORDER BY nom")->fetchAll();
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $errors = SeanceC::validate($_POST);
    $userId = (int)($_POST['utilisateur_id'] ?? 0);
    $entrainementId = (int)($_POST['entrainement_id'] ?? 0);

    $userStmt = $db->prepare("SELECT COALESCE(poids, 70) AS poids FROM `user` WHERE id=:id");
    $userStmt->execute([':id' => $userId]);
    $user = $userStmt->fetch();

    $trainingStmt = $db->prepare("SELECT met_value FROM entrainements WHERE id=:id");
    $trainingStmt->execute([':id' => $entrainementId]);
    $training = $trainingStmt->fetch();

    if (!$user) $errors[] = 'Utilisateur invalide.';
    if (!$training) $errors[] = 'Entrainement invalide.';

    if (!$errors) {
        $seance = new Seance(
            null,
            $userId,
            $entrainementId,
            (int)$_POST['duree_minutes'],
            0,
            $_POST['intensite'],
            ($_POST['fc_moyenne'] ?? '') !== '' ? (int)$_POST['fc_moyenne'] : null,
            trim($_POST['notes'] ?? '')
        );
        $seanceC->enregistrer($seance, (float)$training['met_value'], (float)$user['poids']);
        header('Location: seances.php?created=1');
        exit;
    }
}

require_once __DIR__ . '/../../../Views/partials/header.php';
?>
<style>
    .training-form-card { background:var(--bg-elevated); border:1px solid var(--border-light); border-radius:var(--radius-lg); padding:26px 30px 30px; box-shadow:var(--shadow-sm); max-width:920px; }
    .training-form-card h1 { margin:0; color:var(--accent-herb-dark); font-size:34px; }
    .training-form-intro { margin:8px 0 24px; color:var(--text-muted); line-height:1.5; }
    .training-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .training-form-grid .full { grid-column:1 / -1; }
    .training-field { display:grid; gap:7px; }
    .training-field label { color:var(--accent-herb-dark); font-size:13px; font-weight:900; }
    .training-field small { color:var(--text-muted); font-size:12px; line-height:1.35; }
    .training-form-grid input,.training-form-grid select,.training-form-grid textarea { width:100%; border:1px solid var(--border-light); border-radius:12px; padding:12px; font:inherit; background:#fff; }
    .training-form-grid input:focus,.training-form-grid select:focus,.training-form-grid textarea:focus { outline:0; border-color:var(--accent-herb); box-shadow:0 0 0 3px rgba(26,77,58,.12); }
    .training-btn { border-radius:999px; padding:11px 15px; border:0; background:var(--accent-herb); color:#fff; font-weight:800; text-decoration:none; cursor:pointer; display:inline-flex; gap:8px; align-items:center; }
    .training-btn.light { background:#edf6ef; color:var(--accent-herb-dark); }
    .form-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:8px; }
    .training-alert { border-radius:12px; padding:12px; margin-bottom:16px; background:#fff1f1; color:#9f1d1d; border:1px solid #ffd1d1; }
    @media (max-width:760px){ .training-form-grid{grid-template-columns:1fr;} .training-form-grid .full{grid-column:auto;} }
</style>

<div class="training-form-card">
    <h1>Ajouter une seance</h1>
    <p class="training-form-intro">Associez un utilisateur a un entrainement termine. Les calories seront calculees automatiquement avec MET x poids x duree.</p>
    <?php if ($errors): ?><div class="training-alert"><?php echo htmlspecialchars(implode(' ', $errors)); ?></div><?php endif; ?>
    <form method="POST">
        <div class="training-form-grid">
            <div class="training-field"><label>Utilisateur</label><select name="utilisateur_id" required>
                    <option value="">Choisir un utilisateur</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo (int)$user['id']; ?>" <?php echo (int)($_POST['utilisateur_id'] ?? 0) === (int)$user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['nom'] . ' - ' . $user['email']); ?>
                        </option>
                    <?php endforeach; ?>
                </select><small>Le poids de cet utilisateur sert au calcul des calories.</small></div>
            <div class="training-field"><label>Entrainement</label><select name="entrainement_id" required>
                    <option value="">Choisir un entrainement</option>
                    <?php foreach ($entrainements as $entrainement): ?>
                        <option value="<?php echo (int)$entrainement['id']; ?>" <?php echo (int)($_POST['entrainement_id'] ?? 0) === (int)$entrainement['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($entrainement['nom'] . ' - MET ' . $entrainement['met_value']); ?>
                        </option>
                    <?php endforeach; ?>
                </select><small>Le MET de l'entrainement indique son intensite energetique.</small></div>
            <div class="training-field"><label>Duree</label><input type="number" min="1" max="600" name="duree_minutes" value="<?php echo htmlspecialchars($_POST['duree_minutes'] ?? '30'); ?>" placeholder="Ex: 30" required><small>Temps reel de la seance, en minutes.</small></div>
            <div class="training-field"><label>Intensite ressentie</label><select name="intensite" required>
                    <?php foreach (['faible'=>'Faible','moderee'=>'Moderee','elevee'=>'Elevee','maximale'=>'Maximale'] as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo ($_POST['intensite'] ?? 'moderee') === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select><small>Ressenti utilisateur pendant la seance.</small></div>
            <div class="training-field full"><label>Frequence cardiaque moyenne</label><input type="number" min="40" max="250" name="fc_moyenne" value="<?php echo htmlspecialchars($_POST['fc_moyenne'] ?? ''); ?>" placeholder="Optionnel, ex: 135"><small>Battements par minute si la mesure existe.</small></div>
            <div class="training-field full"><label>Notes</label><textarea name="notes" rows="4" placeholder="Ex: bonne energie, fatigue en fin de seance, douleur au genou..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea></div>
            <div class="full form-actions">
                <button class="training-btn" type="submit"><i class="fas fa-save"></i> Enregistrer</button>
                <a class="training-btn light" href="seances.php">Retour</a>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../../Views/partials/footer.php'; ?>


