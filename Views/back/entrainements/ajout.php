<?php
$title = "Ajouter entrainement - Stabilis";
require_once __DIR__ . '/../../../Controllers/EntrainementC.php';
require_once __DIR__ . '/../../../Models/Entrainement.php';

$controller = new EntrainementC();
$errors = [];
$sports = ['Course a pied','Musculation','Yoga','HIIT','Cyclisme','Natation','Football','Basketball','Tennis','Boxe','Pilates','CrossFit','Cardio','Marche','Mixte','Autre'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $errors = EntrainementC::validate($_POST);
    if (!$errors) {
        $entrainement = new Entrainement(
            null,
            trim($_POST['nom']),
            trim($_POST['description'] ?? ''),
            $_POST['type_sport'],
            $_POST['niveau'],
            (float)($_POST['met_value'] ?? 5),
            (int)($_POST['is_custom'] ?? 0),
            null
        );
        $id = $controller->insert($entrainement);
        $steps = [];
        foreach (($_POST['etapes'] ?? []) as $step) {
            if (trim($step['titre'] ?? '') !== '') {
                $steps[] = ['titre' => trim($step['titre']), 'description' => trim($step['description'] ?? '')];
            }
        }
        if ($steps) {
            $controller->insertEtapes($id, $steps);
        }
        header('Location: liste.php?created=1');
        exit;
    }
}
require_once __DIR__ . '/../../../Views/partials/header.php';
?>
<style>
    .training-form-card { background:var(--bg-elevated); border:1px solid var(--border-light); border-radius:var(--radius-lg); padding:26px 30px 30px; box-shadow:var(--shadow-sm); }
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
    .steps-box { border:1px solid var(--border-light); border-radius:14px; padding:16px; background:#fbfdfb; }
    .steps-head { margin-bottom:12px; }
    .steps-head h3 { margin:0; color:var(--accent-herb-dark); }
    .steps-head p { margin:5px 0 0; color:var(--text-muted); font-size:13px; }
    .step-row { display:grid; grid-template-columns:1fr 1.5fr 48px; gap:10px; margin-bottom:10px; align-items:start; }
    .step-row .training-btn { min-height:48px; justify-content:center; padding:0; }
    .form-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:8px; }
    .training-alert { border-radius:12px; padding:12px; margin-bottom:16px; background:#fff1f1; color:#9f1d1d; border:1px solid #ffd1d1; }
    @media (max-width:760px){ .training-form-grid,.step-row{grid-template-columns:1fr;} .training-form-grid .full{grid-column:auto;} }
</style>

<div class="training-form-card">
    <h1>Ajouter un entrainement</h1>
    <p class="training-form-intro">Completez les informations qui seront affichees aux utilisateurs. La valeur MET sert au calcul automatique des calories.</p>
    <?php if ($errors): ?><div class="training-alert"><?php echo htmlspecialchars(implode(' ', $errors)); ?></div><?php endif; ?>
    <form method="POST">
        <div class="training-form-grid">
            <div class="training-field full"><label>Nom de l'entrainement</label><input name="nom" required maxlength="100" placeholder="Ex: Full body debutant" value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>"><small>Nom court et clair visible dans le programme.</small></div>
            <div class="training-field full"><label>Description</label><textarea name="description" rows="4" placeholder="Objectif, materiel necessaire, public cible..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea></div>
            <div class="training-field"><label>Type de sport</label><select name="type_sport" required>
                    <option value="">Choisir un type</option>
                    <?php foreach ($sports as $sport): ?><option value="<?php echo htmlspecialchars($sport); ?>" <?php echo ($_POST['type_sport'] ?? '') === $sport ? 'selected' : ''; ?>><?php echo htmlspecialchars($sport); ?></option><?php endforeach; ?>
                </select></div>
            <div class="training-field"><label>Niveau</label><select name="niveau" required>
                    <?php foreach (['debutant'=>'Debutant','intermediaire'=>'Intermediaire','avance'=>'Avance'] as $value => $label): ?><option value="<?php echo $value; ?>" <?php echo ($_POST['niveau'] ?? 'intermediaire') === $value ? 'selected' : ''; ?>><?php echo $label; ?></option><?php endforeach; ?>
                </select></div>
            <div class="training-field"><label>Valeur MET</label><input type="number" step="0.1" min="1" max="20" name="met_value" value="<?php echo htmlspecialchars($_POST['met_value'] ?? '5.0'); ?>" placeholder="Ex: 5.0"><small>Intensite energetique: marche 3, musculation 5-7, HIIT 10+.</small></div>
            <div class="training-field"><label>Source</label><select name="is_custom">
                    <option value="0">Catalogue admin</option>
                    <option value="1">Personnalise</option>
                </select><small>Catalogue = visible comme modele admin.</small></div>
            <div class="full">
                <div class="steps-box">
                    <div class="steps-head"><h3>Etapes du tutoriel</h3><p>Ajoutez les consignes dans l'ordre. Exemple: echauffement, exercice principal, retour au calme.</p></div>
                    <div id="steps">
                        <div class="step-row">
                            <div class="training-field"><label>Titre</label><input name="etapes[0][titre]" placeholder="Ex: Echauffement"></div>
                            <div class="training-field"><label>Consignes</label><textarea name="etapes[0][description]" rows="2" placeholder="Ex: 5 minutes de mobilisation articulaire"></textarea></div>
                            <button class="training-btn light" type="button" title="Supprimer l'etape" onclick="this.closest('.step-row').remove()"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <button class="training-btn light" type="button" onclick="addStep()"><i class="fas fa-plus"></i> Ajouter une etape</button>
                </div>
            </div>
            <div class="full form-actions">
                <button class="training-btn" type="submit"><i class="fas fa-save"></i> Enregistrer</button>
                <a class="training-btn light" href="liste.php">Retour</a>
            </div>
        </div>
    </form>
</div>
<script>
let stepIndex = 1;
function addStep() {
    document.getElementById('steps').insertAdjacentHTML('beforeend', `
        <div class="step-row">
            <div class="training-field"><label>Titre</label><input name="etapes[${stepIndex}][titre]" placeholder="Ex: Exercice principal"></div>
            <div class="training-field"><label>Consignes</label><textarea name="etapes[${stepIndex}][description]" rows="2" placeholder="Series, repetitions, posture, respiration..."></textarea></div>
            <button class="training-btn light" type="button" title="Supprimer l'etape" onclick="this.closest('.step-row').remove()"><i class="fas fa-times"></i></button>
        </div>
    `);
    stepIndex++;
}
</script>
<?php require_once __DIR__ . '/../../../Views/partials/footer.php'; ?>


