<?php
require_once __DIR__ . '/partials/auth.php';
requireLogin();
require_once __DIR__ . '/../../Controller/AIGeneratorC.php';
require_once __DIR__ . '/../../Service/EntityHelper.php';

$result  = null;
$errors  = [];
$uid     = frontUserId();
$aiGen   = new AIGeneratorC(70.0);
$history = $aiGen->getHistory(5);

// Check if coming from catalogue with auto-generate
$autoGenerate = false;
if (isset($_SESSION['ai_generate'])) {
    $autoGenerate = true;
    $_POST['goal'] = $_SESSION['ai_generate']['goal'];
    $_POST['niveau'] = $_SESSION['ai_generate']['niveau'];
    $_POST['custom_prompt'] = $_SESSION['ai_generate']['prompt'];
    unset($_SESSION['ai_generate']); // Clear after use
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $autoGenerate) {
    $goal   = trim($_POST['goal']   ?? '');
    $niveau = trim($_POST['niveau'] ?? '');
    $customPrompt = trim($_POST['custom_prompt'] ?? '');
    
    if (!in_array($goal,   ['perte_graisse','prise_muscle','endurance']))     $errors[] = 'Objectif invalide.';
    if (!in_array($niveau, ['debutant','intermediaire','avance']))            $errors[] = 'Niveau invalide.';
    
    if (empty($errors)) {
        $result = $aiGen->genererAvecPrompt($goal, $niveau, $customPrompt);
        
        if (isset($result['error'])) {
            $errors[] = $result['error'];
            $result = null;
        }
    }
}

$pageTitle  = 'Générateur IA'; $heroTitle = '🤖 Générateur IA de Séances';
$heroBg     = 'bg_2.jpg';       $activePage = 'catalogue';
$breadcrumb = '<span class="mr-2"><a href="catalogue.php">Catalogue</a></span><span>Générateur IA</span>';
require_once __DIR__ . '/partials/layout_top.php';

$goalColors = ['perte_graisse'=>'#f5576c','prise_muscle'=>'#82ae46','endurance'=>'#4facfe'];
$catColors  = ['cardio'=>'info','force'=>'success','endurance'=>'primary','hiit'=>'danger','flexibilite'=>'warning'];
?>

<div class="row">
    <!-- Form -->
    <div class="col-lg-4 mb-4">
        <div class="card card-vege" style="position:sticky;top:20px;">
            <div class="card-header" style="background:linear-gradient(135deg,#667eea,#764ba2);">
                <i class="fas fa-magic mr-2"></i>Paramètres de génération
            </div>
            <div class="card-body p-4">
                <?php if(!empty($errors)): ?>
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 error-list"><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
                    <?php if(strpos(implode(' ', $errors), 'Configuration API') !== false): ?>
                    <hr style="margin:10px 0;">
                    <small>
                        <strong><i class="fas fa-info-circle mr-1"></i>Comment configurer l'API (GRATUIT) :</strong><br>
                        1. Obtenez une clé API GRATUITE sur <a href="https://aistudio.google.com/app/apikey" target="_blank" style="color:#fff;text-decoration:underline;font-weight:bold;">Google AI Studio</a><br>
                        2. Ajoutez-la dans le fichier <code>config.php</code><br>
                        3. Consultez <code>API_SETUP.md</code> pour plus de détails<br>
                        <br>
                        ✅ <strong>Gemini est GRATUIT</strong> avec un quota généreux!
                    </small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label style="font-weight:700;">Mon objectif <span class="text-danger">*</span></label>
                        <?php foreach(['perte_graisse'=>['🔥','Perte de graisse','Brûler des calories, perdre du poids'],
                                       'prise_muscle' =>['💪','Prise de muscle', 'Développer la force et la masse'],
                                       'endurance'    =>['🏃','Endurance',        'Améliorer le cardio et la résistance']] as $v=>[$ico,$lbl,$desc]): ?>
                        <div class="custom-control custom-radio mb-2 p-3 <?= ($_POST['goal']??'')===$v?'selected-goal':'' ?>"
                             style="border:2px solid <?= ($_POST['goal']??'')===$v?$goalColors[$v]:'#dee2e6' ?>;border-radius:10px;transition:.2s;">
                            <input class="custom-control-input" type="radio" name="goal" value="<?= $v ?>" id="goal_<?= $v ?>"
                                <?= ($_POST['goal']??'')===$v?'checked':'' ?> required>
                            <label class="custom-control-label w-100" for="goal_<?= $v ?>" style="cursor:pointer;">
                                <strong><?= $ico ?> <?= $lbl ?></strong><br>
                                <small class="text-muted"><?= $desc ?></small>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-group">
                        <label style="font-weight:700;">Mon niveau <span class="text-danger">*</span></label>
                        <?php foreach(['debutant'=>['🌱','Débutant','Moins de 3 mois de pratique'],
                                       'intermediaire'=>['⚡','Intermédiaire','3 mois à 2 ans de pratique'],
                                       'avance'=>['🔥','Avancé','Plus de 2 ans de pratique']] as $v=>[$ico,$lbl,$desc]): ?>
                        <div class="custom-control custom-radio mb-2 p-2"
                             style="border:2px solid <?= ($_POST['niveau']??'')===$v?'#82ae46':'#dee2e6' ?>;border-radius:10px;">
                            <input class="custom-control-input" type="radio" name="niveau" value="<?= $v ?>" id="niv_<?= $v ?>"
                                <?= ($_POST['niveau']??'')===$v?'checked':'' ?> required>
                            <label class="custom-control-label w-100" for="niv_<?= $v ?>" style="cursor:pointer;">
                                <strong><?= $ico ?> <?= $lbl ?></strong><br>
                                <small class="text-muted"><?= $desc ?></small>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Custom Prompt -->
                    <div class="form-group">
                        <label style="font-weight:700;"><i class="fas fa-comment-dots mr-2" style="color:#667eea;"></i>Personnalisez votre séance</label>
                        <textarea name="custom_prompt" class="form-control" rows="4" 
                                  placeholder="Décrivez ce que vous voulez... Ex: Je veux travailler les jambes et les abdos, 8 exercices, sans burpees" 
                                  style="border-radius:10px;border:2px solid #e0e0e0;font-size:.9rem;"><?= htmlspecialchars($_POST['custom_prompt']??'') ?></textarea>
                        <small class="text-muted">
                            <i class="fas fa-lightbulb mr-1"></i>
                            Exemples : "Focus sur le haut du corps", "Seulement du cardio", "10 exercices sans sauts", "Travailler les bras et le dos"
                        </small>
                    </div>

                    <button type="submit" class="btn btn-block py-3" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border-radius:30px;font-weight:700;border:none;font-size:1rem;">
                        <i class="fas fa-magic mr-2"></i>Générer ma séance
                    </button>
                </form>

                <div class="mt-3 p-3" style="background:#f9f9f9;border-radius:10px;">
                    <small class="text-muted" style="font-size:.78rem;">
                        <i class="fas fa-info-circle mr-1 text-info"></i>
                        <strong>Comment ça marche ?</strong> Décrivez simplement ce que vous voulez et notre IA adaptera la séance : muscles ciblés, nombre d'exercices, types d'exercices à inclure ou exclure.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Result -->
    <div class="col-lg-8">
        <?php if ($result): ?>
        <div style="animation:fadeIn .5s ease;">
            <!-- Summary -->
            <div style="background:linear-gradient(135deg,<?= $goalColors[$result['goal']] ?>,#333);color:#fff;border-radius:16px;padding:24px 28px;margin-bottom:24px;">
                <div class="row align-items-center">
                    <div class="col-8">
                        <h4 class="mb-1">
                            ✅ Séance générée !
                            <?php if (!empty($result['ai_generated'])): ?>
                            <span style="font-size:.7rem;opacity:.9;background:rgba(255,255,255,.2);padding:4px 10px;border-radius:12px;margin-left:8px;">
                                <i class="fas fa-robot mr-1"></i>IA Personnalisée
                            </span>
                            <?php endif; ?>
                        </h4>
                        <p class="mb-0" style="opacity:.85;">
                            <?= AIGeneratorC::goalLabel($result['goal']) ?> •
                            <?= AIGeneratorC::niveauLabel($result['niveau']) ?>
                        </p>
                        <?php if (!empty($_POST['custom_prompt'])): ?>
                        <p class="mb-0 mt-2" style="opacity:.75;font-size:.85rem;font-style:italic;">
                            <i class="fas fa-comment-dots mr-1"></i>"<?= htmlspecialchars(mb_substr($_POST['custom_prompt'], 0, 100)) ?><?= mb_strlen($_POST['custom_prompt']) > 100 ? '...' : '' ?>"
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="col-4 text-right">
                        <div class="row text-center">
                            <div class="col-4">
                                <div style="font-size:1.6rem;font-weight:800;"><?= $result['nb_exercises'] ?></div>
                                <small style="opacity:.8;">exercices</small>
                            </div>
                            <div class="col-4">
                                <div style="font-size:1.6rem;font-weight:800;"><?= $result['duree_estimee'] ?></div>
                                <small style="opacity:.8;">minutes</small>
                            </div>
                            <div class="col-4">
                                <div style="font-size:1.6rem;font-weight:800;"><?= number_format($result['total_calories'],0) ?></div>
                                <small style="opacity:.8;">kcal</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exercise list with mark-as-done -->
            <div id="ai-session">
            <?php foreach ($result['exercises'] as $i => $e): ?>
            <div class="ai-exo-row" id="ai-exo-<?= $i ?>"
                 style="background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.07);padding:18px 20px;margin-bottom:14px;border-left:4px solid <?= $goalColors[$result['goal']] ?>;transition:.3s;">
                <div class="d-flex align-items-center">
                    <div style="width:36px;height:36px;background:<?= $goalColors[$result['goal']] ?>;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;margin-right:14px;flex-shrink:0;">
                        <?= $e['ordre'] ?>
                    </div>
                    <div style="flex:1;">
                        <strong><?= htmlspecialchars($e['name']) ?></strong>
                        <span class="ml-2 badge badge-<?= $catColors[$e['category']] ?? 'secondary' ?>" style="font-size:.7rem;"><?= $e['category'] ?></span>
                        <?php if($e['description']): ?><br><small class="text-muted"><?= htmlspecialchars($e['description']) ?></small><?php endif; ?>
                    </div>
                    <div class="d-flex text-center ml-3" style="gap:14px;flex-shrink:0;">
                        <div><div style="font-weight:800;color:#82ae46;font-size:1.1rem;"><?= $e['sets'] ?></div><small class="text-muted" style="font-size:.7rem;">séries</small></div>
                        <div><div style="font-weight:800;color:#82ae46;font-size:1.1rem;"><?= $e['reps'] ?></div><small class="text-muted" style="font-size:.7rem;">reps</small></div>
                        <div><div style="font-weight:800;color:#f5576c;font-size:1.1rem;"><?= $e['rest_sec'] ?>s</div><small class="text-muted" style="font-size:.7rem;">repos</small></div>
                        <div><div style="font-weight:800;color:#4facfe;font-size:1.1rem;"><?= $e['calories'] ?></div><small class="text-muted" style="font-size:.7rem;">kcal</small></div>
                    </div>
                    <button onclick="markAIDone(this, <?= $i ?>)" class="btn btn-sm ml-3"
                            style="border-radius:20px;border:2px solid <?= $goalColors[$result['goal']] ?>;color:<?= $goalColors[$result['goal']] ?>;background:#fff;font-size:.75rem;flex-shrink:0;min-width:75px;">
                        <i class="fas fa-check mr-1"></i>Fait
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            </div>

            <!-- Progress bar for AI session -->
            <div class="p-4" style="background:#f9fdf3;border-radius:14px;border:2px solid #82ae46;margin-top:8px;">
                <div class="d-flex justify-content-between mb-1">
                    <span style="font-weight:700;">Progression totale</span>
                    <span id="ai-cal-display" style="color:#f5576c;font-weight:700;">0 / <?= number_format($result['total_calories'],1) ?> kcal</span>
                </div>
                <div style="background:#ddd;border-radius:10px;height:10px;overflow:hidden;">
                    <div id="ai-cal-bar" style="height:100%;background:linear-gradient(90deg,<?= $goalColors[$result['goal']] ?>,#43e97b);width:0%;transition:.5s;border-radius:10px;"></div>
                </div>
                <p class="text-muted text-center mt-3 mb-0" style="font-size:.82rem;">Complétez chaque exercice en cliquant sur "Fait"</p>
            </div>

            <!-- Save to Program Button -->
            <div class="text-center mt-4">
                <form method="POST" action="save_ai_workout.php" style="display:inline-block;">
                    <input type="hidden" name="workout_data" value='<?= htmlspecialchars(json_encode($result)) ?>'>
                    <input type="hidden" name="custom_prompt" value="<?= htmlspecialchars($_POST['custom_prompt']??'') ?>">
                    <button type="submit" class="btn btn-success btn-lg px-5" style="border-radius:30px;box-shadow:0 4px 15px rgba(130,174,70,.3);">
                        <i class="fas fa-save mr-2"></i>Sauvegarder dans Mon Programme
                    </button>
                </form>
                <a href="custom_workout.php" class="btn btn-outline-secondary btn-lg px-5 ml-3" style="border-radius:30px;">
                    <i class="fas fa-redo mr-2"></i>Générer une Nouvelle Séance
                </a>
            </div>
        </div>

        <script>
        const AI_TOTAL_CAL = <?= $result['total_calories'] ?>;
        const AI_NB = <?= $result['nb_exercises'] ?>;
        let aiDone = {};
        function markAIDone(btn, idx) {
            if (aiDone[idx]) return;
            aiDone[idx] = true;
            btn.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Fait !';
            btn.style.background = '#82ae46'; btn.style.color = '#fff'; btn.style.borderColor = '#82ae46';
            const row = document.getElementById('ai-exo-' + idx);
            if (row) { row.style.background = '#e8f5e9'; row.style.borderLeftColor = '#28a745'; }
            const done = Object.keys(aiDone).length;
            const pct  = Math.round(done / AI_NB * 100);
            document.getElementById('ai-cal-bar').style.width = pct + '%';
            const earned = Math.round(AI_TOTAL_CAL * done / AI_NB * 10) / 10;
            document.getElementById('ai-cal-display').textContent = earned.toFixed(1) + ' / ' + AI_TOTAL_CAL.toFixed(1) + ' kcal';
        }
        </script>
        <style>@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}</style>

        <?php else: ?>
        <!-- Empty state -->
        <div style="background:#f9f9f9;border-radius:16px;padding:48px;text-align:center;border:2px dashed #dee2e6;">
            <div style="font-size:4rem;margin-bottom:16px;">🤖</div>
            <h4 class="text-muted mb-2">Configurez votre séance</h4>
            <p class="text-muted">Sélectionnez votre objectif et votre niveau dans le formulaire, puis cliquez sur Générer.</p>
        </div>

        <!-- History -->
        <?php if (!empty($history)): ?>
        <div class="mt-4">
            <h5 class="mb-3"><i class="fas fa-history mr-2 text-muted"></i>Dernières générations</h5>
            <?php foreach ($history as $h): ?>
            <?php $exos = json_decode($h['exercises_json'], true); ?>
            <div style="background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:14px 18px;margin-bottom:10px;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?= AIGeneratorC::goalLabel($h['goal']) ?></strong>
                        <span class="text-muted mx-2">•</span>
                        <span><?= AIGeneratorC::niveauLabel($h['niveau']) ?></span>
                        <span class="text-muted mx-2">•</span>
                        <span class="text-danger font-weight-bold"><?= number_format($h['total_calories'],1) ?> kcal</span>
                    </div>
                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></small>
                </div>
                <div class="mt-1" style="font-size:.78rem;color:#888;">
                    <?= count($exos) ?> exercices : <?= implode(', ', array_map(fn($e) => htmlspecialchars($e['name']), array_slice($exos,0,3))) ?>…
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
