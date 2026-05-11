<?php
require_once __DIR__ . '/../../Controller/EntrainementC.php';
require_once __DIR__ . '/../../Controller/UtilisateurC.php';
require_once __DIR__ . '/../../Controller/SeanceC.php';
require_once __DIR__ . '/../../Controller/AIGeneratorC.php';
require_once __DIR__ . '/../../Repository/GeneratedSessionRepository.php';

$eC = new EntrainementC();
$uC = new UtilisateurC();
$sC = new SeanceC();
$aiRepo = new GeneratedSessionRepository();

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
$breadcrumb = '<li class="breadcrumb-item active">Dashboard</li>';
require_once __DIR__ . '/partials/layout_top.php';
?>

<!-- ═══════════════════════════════════════════════════════════════
     ARCHITECTURE OVERVIEW BANNER
     "Gestion des entraînements intelligents"
     ═══════════════════════════════════════════════════════════════ -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-sitemap mr-2"></i>Architecture du Module — Gestion des entraînements intelligents</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- AI Generation -->
                    <div class="col-md-3">
                        <div class="info-box bg-gradient-success">
                            <span class="info-box-icon"><i class="fas fa-robot"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">IA Génération</span>
                                <span class="info-box-number">AI</span>
                                <div class="progress"><div class="progress-bar" style="width:100%"></div></div>
                                <span class="progress-description">Gemini · Prompts · Dynamic</span>
                            </div>
                        </div>
                    </div>
                    <!-- Services -->
                    <div class="col-md-3">
                        <div class="info-box bg-gradient-info">
                            <span class="info-box-icon"><i class="fas fa-cogs"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Services Métier</span>
                                <span class="info-box-number">3</span>
                                <div class="progress"><div class="progress-bar" style="width:100%"></div></div>
                                <span class="progress-description">Calorie · Generator · Tracker</span>
                            </div>
                        </div>
                    </div>
                    <!-- Architecture -->
                    <div class="col-md-3">
                        <div class="info-box bg-gradient-warning">
                            <span class="info-box-icon"><i class="fas fa-layer-group"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Architecture</span>
                                <span class="info-box-number">Clean</span>
                                <div class="progress"><div class="progress-bar" style="width:100%"></div></div>
                                <span class="progress-description">Repository · Service · Controller</span>
                            </div>
                        </div>
                    </div>
                    <!-- Database -->
                    <div class="col-md-3">
                        <div class="info-box bg-gradient-danger">
                            <span class="info-box-icon"><i class="fas fa-database"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Database</span>
                                <span class="info-box-number">6</span>
                                <div class="progress"><div class="progress-bar" style="width:100%"></div></div>
                                <span class="progress-description">Tables · Minimal · AI-First</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Services detail -->
                <div class="row mt-2">
                    <div class="col-12">
                        <small class="text-muted">
                            <strong>✨ AI-First Approach</strong> — Everything generated dynamically by Gemini AI &nbsp;|&nbsp;
                            <strong>CalorieService</strong> — MET formula (parDuree, parSetsReps, interpreterCalories) &nbsp;|&nbsp;
                            <strong>WorkoutGeneratorService</strong> — Rule-based generation (goal×level) &nbsp;|&nbsp;
                            <strong>PerformanceTracker</strong> — KPIs, charts, analytics &nbsp;|&nbsp;
                            <strong>Clean Architecture</strong> — Repository pattern, Service layer, Minimal database
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════ KPI BOXES -->
<div class="row">
    <div class="col-lg-4 col-6">
        <div class="small-box bg-success">
            <div class="inner"><h3><?= $eC->countAll() ?></h3><p>Entraînements créés (utilisateurs)</p></div>
            <div class="icon"><i class="fas fa-running"></i></div>
            <a href="listEntrainements.php" class="small-box-footer">Voir <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-info">
            <div class="inner"><h3><?= $uC->count() ?></h3><p>Utilisateurs</p></div>
            <div class="icon"><i class="fas fa-users"></i></div>
            <a href="listUsers.php" class="small-box-footer">Voir <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-danger">
            <div class="inner"><h3><?= $aiRepo->count() ?></h3><p>Séances IA Générées</p></div>
            <div class="icon"><i class="fas fa-robot"></i></div>
            <a href="ai_history.php" class="small-box-footer">Voir historique <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════ MÉTIERS AVANCÉS -->
<div class="row">
    <!-- Calcul Calorique -->
    <div class="col-md-6">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-fire mr-2"></i>Calcul Calorique MET</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-2">
                    <code>Calories = MET × poids(kg) × durée(h)</code>
                </p>
                <table class="table table-sm table-bordered">
                    <thead class="thead-light"><tr><th>Exercice (Exemple)</th><th>MET</th><th>Séries</th><th>Reps</th><th>≈ kcal (70kg)</th><th>Intensité</th></tr></thead>
                    <tbody>
                    <?php
                    // Sample AI-generated exercises for demonstration
                    require_once __DIR__ . '/../../Service/CalorieService.php';
                    $sampleExs = [
                        ['name' => 'Burpees', 'met_value' => 10.3, 'sets' => 4, 'reps' => 15, 'rest_sec' => 30],
                        ['name' => 'Squats', 'met_value' => 6.0, 'sets' => 4, 'reps' => 12, 'rest_sec' => 60],
                        ['name' => 'Pompes', 'met_value' => 4.0, 'sets' => 4, 'reps' => 15, 'rest_sec' => 45],
                        ['name' => 'Mountain Climbers', 'met_value' => 9.5, 'sets' => 4, 'reps' => 20, 'rest_sec' => 30],
                        ['name' => 'Planche', 'met_value' => 3.5, 'sets' => 4, 'reps' => 1, 'rest_sec' => 45],
                    ];
                    foreach ($sampleExs as $ex):
                        $cal = CalorieService::parSetsReps(
                            (float)$ex['met_value'], 70.0,
                            (int)$ex['sets'], (int)$ex['reps'], (int)$ex['rest_sec']
                        );
                        $interp = CalorieService::interpreterCalories($cal);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($ex['name']) ?> <small class="text-muted">(AI)</small></td>
                        <td><?= $ex['met_value'] ?></td>
                        <td><?= $ex['sets'] ?></td>
                        <td><?= $ex['reps'] ?></td>
                        <td><strong><?= $cal ?></strong></td>
                        <td><span style="color:<?= $interp['color'] ?>"><i class="fas <?= $interp['icon'] ?>"></i> <?= $interp['label'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <small class="text-muted"><i class="fas fa-robot mr-1"></i>Exemples d'exercices générés par IA</small>
            </div>
        </div>
    </div>

    <!-- Suivi Performance -->
    <div class="col-md-6">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Suivi de Performance</h3>
            </div>
            <div class="card-body">
                <?php
                $totalCal = $sC->totalCaloriesAll();
                $totalSean = $sC->countAll();
                $interp = CalorieService::interpreterCalories($totalCal / max(1, $totalSean));
                ?>
                <div class="row text-center">
                    <div class="col-4">
                        <div class="description-block border-right">
                            <h5 class="description-header"><?= $totalSean ?></h5>
                            <span class="description-text">Séances totales</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="description-block border-right">
                            <h5 class="description-header"><?= number_format($totalCal, 0) ?></h5>
                            <span class="description-text">kcal total brûlées</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="description-block">
                            <h5 class="description-header" style="color:<?= $interp['color'] ?>">
                                <i class="fas <?= $interp['icon'] ?>"></i> <?= $interp['label'] ?>
                            </h5>
                            <span class="description-text">Intensité moyenne</span>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row mt-2">
                    <div class="col-12">
                        <a href="listUsers.php" class="btn btn-info btn-sm"><i class="fas fa-users mr-1"></i>Gérer utilisateurs</a>
                        <a href="ai_history.php" class="btn btn-warning btn-sm ml-2"><i class="fas fa-robot mr-1"></i>Historique IA</a>
                        <a href="listEntrainements.php" class="btn btn-success btn-sm ml-2"><i class="fas fa-running mr-1"></i>Entraînements</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════ AI GENERATION STATS -->
<div class="row">
    <div class="col-md-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-robot mr-2"></i>Statistiques de Génération IA</h3>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="description-block">
                            <h5 class="description-header text-success"><?= $aiRepo->count() ?></h5>
                            <span class="description-text">Séances IA générées</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="description-block">
                            <h5 class="description-header text-info"><?= $eC->countAll() ?></h5>
                            <span class="description-text">Entraînements sauvegardés</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="description-block">
                            <h5 class="description-header text-warning"><?= $uC->count() ?></h5>
                            <span class="description-text">Utilisateurs actifs</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="description-block">
                            <h5 class="description-header text-danger"><?= number_format($sC->totalCaloriesAll(), 0) ?></h5>
                            <span class="description-text">Calories brûlées (total)</span>
                        </div>
                    </div>
                </div>
                <hr>
                <p class="text-center text-muted">
                    <i class="fas fa-magic mr-2"></i>Tout est généré dynamiquement par l'IA Gemini - Aucune donnée statique
                </p>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════ RECENT AI SESSIONS -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Séances IA Récentes</h3>
                <div class="card-tools">
                    <a href="ai_history.php" class="btn btn-success btn-sm">
                        <i class="fas fa-list"></i> Voir tout l'historique
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php 
                $recentSessions = $aiRepo->getRecent(10);
                if (empty($recentSessions)): 
                ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-robot fa-3x mb-3"></i>
                    <p>Aucune séance générée pour le moment</p>
                    <p><small>Les séances apparaîtront ici lorsque les utilisateurs utiliseront le générateur IA.</small></p>
                </div>
                <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Objectif</th>
                            <th>Niveau</th>
                            <th>Prompt</th>
                            <th>Exercices</th>
                            <th>Calories</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentSessions as $session): 
                        $goalLabels = ['perte_graisse' => '🔥 Perte', 'prise_muscle' => '💪 Muscle', 'endurance' => '🏃 Endurance'];
                        $niveauBadges = ['debutant' => 'success', 'intermediaire' => 'warning', 'avance' => 'danger'];
                    ?>
                    <tr>
                        <td><?= $goalLabels[$session['goal']] ?? $session['goal'] ?></td>
                        <td><span class="badge badge-<?= $niveauBadges[$session['niveau']] ?? 'secondary' ?>"><?= ucfirst($session['niveau']) ?></span></td>
                        <td>
                            <?php if (!empty($session['prompt'])): ?>
                            <small class="text-muted">
                                <i class="fas fa-comment-dots mr-1"></i><?= htmlspecialchars(substr($session['prompt'], 0, 40)) ?><?= strlen($session['prompt']) > 40 ? '...' : '' ?>
                            </small>
                            <?php else: ?>
                            <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-info">
                                <?= count(json_decode($session['exercises_json'] ?? '[]', true)) ?> exercices
                            </span>
                        </td>
                        <td>
                            <strong class="text-warning">
                                <?= number_format($session['total_calories'] ?? 0, 0) ?> kcal
                            </strong>
                        </td>
                        <td>
                            <small class="text-muted">
                                <?= date('d/m/Y H:i', strtotime($session['created_at'] ?? 'now')) ?>
                            </small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            <?php if (!empty($recentSessions)): ?>
            <div class="card-footer text-center">
                <a href="ai_history.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-list mr-1"></i>Voir tout l'historique (<?= $aiRepo->count() ?> générations)
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
