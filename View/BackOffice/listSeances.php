<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../Controller/SeanceC.php';

$sC = new SeanceC();

// All seances with join
$pdo = config::getConnexion();
$rows = $pdo->query(
    "SELECT s.*, e.nom AS entrainement_nom, e.type_sport, u.nom AS user_nom
     FROM seances_completees s
     INNER JOIN entrainements e ON e.id = s.entrainement_id
     INNER JOIN utilisateur u ON u.id = s.utilisateur_id
     ORDER BY s.completed_at DESC LIMIT 100"
)->fetchAll();

$totalCal = $sC->totalCaloriesAll();
$totalSeances = $sC->countAll();

$pageTitle = 'Séances'; $activePage = 'seances';
$breadcrumb = '<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Séances</li>';
require_once __DIR__ . '/partials/layout_top.php';
?>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-danger"><i class="fas fa-fire"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Calories totales brûlées</span>
                <span class="info-box-number"><?= number_format($totalCal, 0) ?> kcal</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total séances</span>
                <span class="info-box-number"><?= $totalSeances ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-calculator"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Moy. calories/séance</span>
                <span class="info-box-number"><?= $totalSeances > 0 ? number_format($totalCal/$totalSeances, 0) : 0 ?> kcal</span>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-fire mr-2"></i>Séances complétées (<?= count($rows) ?>)</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead class="thead-light">
                <tr><th>#</th><th>Utilisateur</th><th>Entraînement</th><th>Durée</th><th>Calories</th><th>Intensité</th><th>FC moy.</th><th>Date</th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $s): ?>
            <tr>
                <td><?= $s['id'] ?></td>
                <td><strong><?= htmlspecialchars($s['user_nom']) ?></strong></td>
                <td><?= htmlspecialchars($s['entrainement_nom']) ?><br><small class="text-muted"><?= htmlspecialchars($s['type_sport']) ?></small></td>
                <td><?= $s['duree_minutes'] ?> min</td>
                <td><strong style="color:#dc3545;"><?= number_format($s['calories'], 1) ?></strong> kcal</td>
                <td><?= ucfirst($s['intensite']) ?></td>
                <td><?= $s['fc_moyenne'] ? $s['fc_moyenne'].' bpm' : '—' ?></td>
                <td><small><?= date('d/m/Y H:i', strtotime($s['completed_at'])) ?></small></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
