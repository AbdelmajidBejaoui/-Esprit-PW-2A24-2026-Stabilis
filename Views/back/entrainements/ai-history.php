<?php
$title = "Historique IA - Stabilis";
require_once __DIR__ . '/../../../Views/partials/header.php';
require_once __DIR__ . '/../../../Models/GeneratedSessionRepository.php';
require_once __DIR__ . '/../../../Services/CalorieService.php';

$repo = new GeneratedSessionRepository();
$sessions = $repo->getRecent(50);
$stats = $repo->getStats();

$goalLabels = [
    'perte_graisse' => 'Perte de graisse',
    'prise_muscle' => 'Prise de muscle',
    'endurance' => 'Endurance',
];
$levelLabels = [
    'debutant' => 'Debutant',
    'intermediaire' => 'Intermediaire',
    'avance' => 'Avance',
];
?>
<style>
    .training-admin-head { display:flex; justify-content:space-between; gap:18px; align-items:end; margin-bottom:24px; }
    .training-admin-head h1 { margin:0; color:var(--accent-herb-dark); font-size:30px; }
    .training-admin-head p { color:var(--text-muted); margin:6px 0 0; }
    .training-kpis { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; margin-bottom:22px; }
    .training-kpi { background:var(--bg-elevated); border:1px solid var(--border-light); border-radius:var(--radius-lg); padding:20px; box-shadow:var(--shadow-sm); }
    .training-kpi strong { display:block; color:var(--accent-herb-dark); font-size:30px; line-height:1; }
    .training-kpi span { display:block; color:var(--text-muted); margin-top:8px; font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.35px; }
    .training-admin-card { background:var(--bg-elevated); border:1px solid var(--border-light); border-radius:var(--radius-lg); padding:22px; box-shadow:var(--shadow-sm); }
    .training-admin-table { width:100%; border-collapse:collapse; }
    .training-admin-table th,.training-admin-table td { padding:12px; border-bottom:1px solid var(--border-light); text-align:left; vertical-align:top; }
    .training-admin-table th { color:var(--text-muted); font-size:12px; text-transform:uppercase; letter-spacing:.35px; }
    .training-badge { display:inline-flex; border-radius:999px; padding:6px 9px; background:#edf6ef; color:var(--accent-herb-dark); font-size:12px; font-weight:800; }
    .exercise-details { margin-top:10px; }
    .exercise-details summary { cursor:pointer; color:var(--accent-herb-dark); font-weight:800; }
    .exercise-list { margin:10px 0 0; padding-left:18px; color:var(--text-muted); }
    @media (max-width:900px){ .training-kpis{grid-template-columns:1fr;} }
</style>

<div class="training-admin-head">
    <div>
        <h1>Historique IA</h1>
        <p>Suivi des seances generees par Gemini, prompts inclus.</p>
    </div>
</div>

<div class="training-kpis">
    <div class="training-kpi"><strong><?php echo (int)($stats['total_generated'] ?? 0); ?></strong><span>Generations</span></div>
    <div class="training-kpi"><strong><?php echo number_format((float)($stats['avg_calories'] ?? 0), 0); ?></strong><span>Kcal moyenne</span></div>
    <div class="training-kpi"><strong><?php echo number_format((float)($stats['total_calories'] ?? 0), 0); ?></strong><span>Kcal generees</span></div>
</div>

<div class="training-admin-card">
    <table class="training-admin-table">
        <thead>
            <tr><th>ID</th><th>Objectif</th><th>Niveau</th><th>Prompt</th><th>Exercices</th><th>Kcal</th><th>Date</th></tr>
        </thead>
        <tbody>
        <?php foreach ($sessions as $session): ?>
            <?php $exercises = json_decode($session['exercises_json'] ?? '[]', true) ?: []; ?>
            <?php $calorieState = CalorieService::interpreterCalories((float)$session['total_calories']); ?>
            <tr>
                <td>#<?php echo (int)$session['id']; ?></td>
                <td><?php echo htmlspecialchars($goalLabels[$session['goal']] ?? $session['goal']); ?></td>
                <td><span class="training-badge"><?php echo htmlspecialchars($levelLabels[$session['niveau']] ?? $session['niveau']); ?></span></td>
                <td><?php echo htmlspecialchars($session['prompt'] ?: '-'); ?></td>
                <td>
                    <?php echo count($exercises); ?> exercice(s)
                    <?php if ($exercises): ?>
                        <details class="exercise-details">
                            <summary>Voir</summary>
                            <ol class="exercise-list">
                                <?php foreach ($exercises as $exercise): ?>
                                    <li><strong><?php echo htmlspecialchars($exercise['name'] ?? 'Exercice'); ?></strong> - <?php echo htmlspecialchars(($exercise['sets'] ?? '-') . ' x ' . ($exercise['reps'] ?? '-') . ', repos ' . ($exercise['rest_sec'] ?? '-') . 's'); ?></li>
                                <?php endforeach; ?>
                            </ol>
                        </details>
                    <?php endif; ?>
                </td>
                <td><strong style="color:<?php echo htmlspecialchars($calorieState['color']); ?>"><?php echo number_format((float)$session['total_calories'], 0); ?></strong></td>
                <td><?php echo date('d/m/Y H:i', strtotime($session['created_at'])); ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$sessions): ?><tr><td colspan="7">Aucune generation IA pour le moment.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../../Views/partials/footer.php'; ?>


