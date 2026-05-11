<?php
require_once __DIR__ . '/../../Repository/GeneratedSessionRepository.php';
require_once __DIR__ . '/../../Service/CalorieService.php';

$aiRepo = new GeneratedSessionRepository();
$sessions = $aiRepo->getRecent(50);

$pageTitle = 'Historique IA';
$activePage = 'ai_history';
$breadcrumb = '<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Historique IA</li>';
require_once __DIR__ . '/partials/layout_top.php';

$goalLabels = [
    'perte_graisse' => ['label' => 'Perte de graisse', 'color' => '#f5576c', 'icon' => 'fa-fire'],
    'prise_muscle' => ['label' => 'Prise de muscle', 'color' => '#82ae46', 'icon' => 'fa-dumbbell'],
    'endurance' => ['label' => 'Endurance', 'color' => '#4facfe', 'icon' => 'fa-running']
];

$niveauLabels = [
    'debutant' => ['label' => 'Débutant', 'badge' => 'success'],
    'intermediaire' => ['label' => 'Intermédiaire', 'badge' => 'warning'],
    'avance' => ['label' => 'Avancé', 'badge' => 'danger']
];
?>

<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header" style="background:#667eea;color:#fff;">
                <h3 class="card-title"><i class="fas fa-robot mr-2"></i>Historique des Générations IA</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    <i class="fas fa-info-circle mr-1"></i>
                    Toutes les séances générées par l'IA Gemini. Les utilisateurs peuvent sauvegarder ces séances dans leur programme.
                </p>
                <div class="row text-center mb-3">
                    <div class="col-md-3">
                        <div class="info-box bg-gradient-success">
                            <span class="info-box-icon"><i class="fas fa-fire"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Perte de graisse</span>
                                <span class="info-box-number"><?= count(array_filter($sessions, fn($s) => $s['goal'] === 'perte_graisse')) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-gradient-info">
                            <span class="info-box-icon"><i class="fas fa-dumbbell"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Prise de muscle</span>
                                <span class="info-box-number"><?= count(array_filter($sessions, fn($s) => $s['goal'] === 'prise_muscle')) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-gradient-primary">
                            <span class="info-box-icon"><i class="fas fa-running"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Endurance</span>
                                <span class="info-box-number"><?= count(array_filter($sessions, fn($s) => $s['goal'] === 'endurance')) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-gradient-warning">
                            <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total généré</span>
                                <span class="info-box-number"><?= count($sessions) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (empty($sessions)): ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-robot fa-4x mb-3" style="color:#ddd;"></i>
                <h4 class="text-muted">Aucune génération IA pour le moment</h4>
                <p class="text-muted">Les générations apparaîtront ici lorsque les utilisateurs utiliseront le générateur IA.</p>
            </div>
        </div>
    </div>
</div>
<?php else: ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Dernières générations (<?= count($sessions) ?>)</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>ID</th>
                                <th>Objectif</th>
                                <th>Niveau</th>
                                <th>Prompt</th>
                                <th>Exercices</th>
                                <th>Calories</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($sessions as $session): 
                            $exercises = json_decode($session['exercises_json'], true);
                            $goal = $goalLabels[$session['goal']] ?? ['label' => $session['goal'], 'color' => '#999', 'icon' => 'fa-question'];
                            $niveau = $niveauLabels[$session['niveau']] ?? ['label' => $session['niveau'], 'badge' => 'secondary'];
                            $calInterp = CalorieService::interpreterCalories($session['total_calories']);
                        ?>
                        <tr>
                            <td><strong>#<?= $session['id'] ?></strong></td>
                            <td>
                                <i class="fas <?= $goal['icon'] ?> mr-1" style="color:<?= $goal['color'] ?>;"></i>
                                <?= $goal['label'] ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= $niveau['badge'] ?>"><?= $niveau['label'] ?></span>
                            </td>
                            <td>
                                <?php if (!empty($session['prompt'])): ?>
                                <small class="text-muted" style="max-width:200px;display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    <i class="fas fa-comment-dots mr-1"></i><?= htmlspecialchars($session['prompt']) ?>
                                </small>
                                <?php else: ?>
                                <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-info"><?= count($exercises) ?> exercices</span>
                            </td>
                            <td>
                                <strong style="color:<?= $calInterp['color'] ?>;">
                                    <?= number_format($session['total_calories'], 0) ?> kcal
                                </strong>
                                <i class="fas <?= $calInterp['icon'] ?> ml-1" style="color:<?= $calInterp['color'] ?>;"></i>
                            </td>
                            <td>
                                <small><?= date('d/m/Y H:i', strtotime($session['created_at'])) ?></small>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="viewSession(<?= $session['id'] ?>)">
                                    <i class="fas fa-eye"></i> Voir
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for viewing session details -->
<div class="modal fade" id="sessionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-robot mr-2"></i>Détails de la séance IA</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="sessionDetails">
                <div class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const sessions = <?= json_encode($sessions) ?>;

function viewSession(id) {
    const session = sessions.find(s => s.id == id);
    if (!session) return;
    
    const exercises = JSON.parse(session.exercises_json);
    const goalLabels = {
        'perte_graisse': 'Perte de graisse',
        'prise_muscle': 'Prise de muscle',
        'endurance': 'Endurance'
    };
    const niveauLabels = {
        'debutant': 'Débutant',
        'intermediaire': 'Intermédiaire',
        'avance': 'Avancé'
    };
    
    let html = `
        <div class="mb-3">
            <strong>Objectif:</strong> ${goalLabels[session.goal] || session.goal}<br>
            <strong>Niveau:</strong> ${niveauLabels[session.niveau] || session.niveau}<br>
            <strong>Calories totales:</strong> ${Math.round(session.total_calories)} kcal<br>
            <strong>Date:</strong> ${new Date(session.created_at).toLocaleString('fr-FR')}<br>
            ${session.prompt ? `<strong>Prompt:</strong> <em>"${session.prompt}"</em><br>` : ''}
        </div>
        <h6><i class="fas fa-dumbbell mr-2"></i>Exercices (${exercises.length})</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Exercice</th>
                        <th>Catégorie</th>
                        <th>Séries</th>
                        <th>Reps</th>
                        <th>Repos</th>
                        <th>Calories</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    exercises.forEach((ex, i) => {
        html += `
            <tr>
                <td>${i + 1}</td>
                <td><strong>${ex.name}</strong>${ex.description ? '<br><small class="text-muted">' + ex.description + '</small>' : ''}</td>
                <td><span class="badge badge-secondary">${ex.category}</span></td>
                <td>${ex.sets}</td>
                <td>${ex.reps}</td>
                <td>${ex.rest_sec}s</td>
                <td><strong>${Math.round(ex.calories)}</strong> kcal</td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    document.getElementById('sessionDetails').innerHTML = html;
    $('#sessionModal').modal('show');
}
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
