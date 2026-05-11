<?php
$title = "Modifier seance - Stabilis";
require_once __DIR__ . '/../../../Controllers/SeanceC.php';

$seanceC = new SeanceC();
$id = (int)($_GET['id'] ?? 0);
$seance = $seanceC->getById($id);
if (!$seance) {
    header('Location: seances.php?missing=1');
    exit;
}

$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        $seanceC->update($id, $_POST);
        header('Location: seances.php?updated=1');
        exit;
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
        $seance = array_merge($seance, $_POST);
    }
}

require_once __DIR__ . '/../../../Views/partials/header.php';
?>
<style>
    .training-form-card { background:var(--bg-elevated); border:1px solid var(--border-light); border-radius:var(--radius-lg); padding:26px 30px 30px; box-shadow:var(--shadow-sm); max-width:860px; }
    .training-form-card h1 { margin:0; color:var(--accent-herb-dark); font-size:34px; }
    .training-form-card p { color:var(--text-muted); margin:8px 0 20px; line-height:1.5; }
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
    .training-meta { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:18px; }
    .training-meta span { border-radius:999px; padding:7px 10px; background:#edf6ef; color:var(--accent-herb-dark); font-size:12px; font-weight:800; }
    @media (max-width:760px){ .training-form-grid{grid-template-columns:1fr;} .training-form-grid .full{grid-column:auto;} }
</style>

<div class="training-form-card">
    <h1>Modifier la seance</h1>
    <p>Les calories seront recalculees avec le MET de l'entrainement et le poids de l'utilisateur.</p>
    <div class="training-meta">
        <span><?php echo htmlspecialchars($seance['user_nom']); ?></span>
        <span><?php echo htmlspecialchars($seance['entrainement_nom']); ?></span>
        <span>MET <?php echo number_format((float)$seance['met_value'], 1); ?></span>
        <span><?php echo number_format((float)$seance['user_poids'], 1); ?> kg</span>
    </div>
    <?php if ($errors): ?><div class="training-alert"><?php echo htmlspecialchars(implode(' ', $errors)); ?></div><?php endif; ?>
    <form method="POST">
        <div class="training-form-grid">
            <div class="training-field"><label>Duree</label><input type="number" min="1" max="600" name="duree_minutes" value="<?php echo htmlspecialchars((string)$seance['duree_minutes']); ?>" placeholder="Ex: 30" required><small>Temps reel de la seance, en minutes.</small></div>
            <div class="training-field"><label>Intensite ressentie</label><select name="intensite" required>
                    <?php foreach (['faible'=>'Faible','moderee'=>'Moderee','elevee'=>'Elevee','maximale'=>'Maximale'] as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo ($seance['intensite'] ?? '') === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select><small>Ressenti utilisateur pendant la seance.</small></div>
            <div class="training-field full"><label>Frequence cardiaque moyenne</label><input type="number" min="40" max="250" name="fc_moyenne" value="<?php echo htmlspecialchars((string)($seance['fc_moyenne'] ?? '')); ?>" placeholder="Optionnel, ex: 135"><small>Battements par minute si la mesure existe.</small></div>
            <div class="training-field full"><label>Notes</label><textarea name="notes" rows="4" placeholder="Ex: bonne energie, fatigue en fin de seance, douleur au genou..."><?php echo htmlspecialchars($seance['notes'] ?? ''); ?></textarea></div>
            <div class="full form-actions">
                <button class="training-btn" type="submit"><i class="fas fa-save"></i> Mettre a jour</button>
                <a class="training-btn light" href="seances.php">Retour</a>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../../Views/partials/footer.php'; ?>


