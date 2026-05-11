<?php
require_once __DIR__ . '/partials/auth.php';
requireLogin();
require_once __DIR__ . '/../../Controller/ProgrammeC.php';
require_once __DIR__ . '/../../Controller/SeanceC.php';

$pC = new ProgrammeC();
$sC = new SeanceC();
$uid = frontUserId();

// Remove from programme
if (isset($_GET['remove'])) {
    $pC->remove($uid, (int)$_GET['remove']);
    flash('warning', 'Entraînement retiré de votre programme.');
    header('Location: programme.php'); exit;
}

$items = $pC->listByUser($uid);
$stats = $sC->statsUser($uid);

$pageTitle='Mon Programme'; $heroTitle='Mon Programme'; $heroBg='bg_2.jpg';
$activePage='programme';
$breadcrumb='<span>Mon Programme</span>';
require_once __DIR__ . '/partials/layout_top.php';
?>

<!-- Stats -->
<div class="row mb-5">
    <div class="col-md-3 col-6 mb-3">
        <div style="background:linear-gradient(135deg,#82ae46,#4a7c20);color:#fff;border-radius:14px;padding:22px;text-align:center;">
            <h3 class="font-weight-bold mb-0"><?= count($items) ?></h3><small>Exercices</small>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div style="background:linear-gradient(135deg,#f093fb,#f5576c);color:#fff;border-radius:14px;padding:22px;text-align:center;">
            <h3 class="font-weight-bold mb-0"><?= number_format($stats['total_calories'],0) ?></h3><small>kcal brûlées</small>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div style="background:linear-gradient(135deg,#4facfe,#00f2fe);color:#fff;border-radius:14px;padding:22px;text-align:center;">
            <h3 class="font-weight-bold mb-0"><?= $stats['nb_seances'] ?></h3><small>Séances faites</small>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div style="background:linear-gradient(135deg,#43e97b,#38f9d7);color:#fff;border-radius:14px;padding:22px;text-align:center;">
            <h3 class="font-weight-bold mb-0"><?= round($stats['total_minutes']/60,1) ?>h</h3><small>Temps total</small>
        </div>
    </div>
</div>

<?php if (empty($items)): ?>
<div class="row justify-content-center">
    <div class="col-md-6 text-center py-5">
        <i class="fas fa-dumbbell fa-4x mb-4" style="color:#ddd;"></i>
        <h4 class="text-muted">Programme vide</h4>
        <p class="text-muted">Parcourez le catalogue et ajoutez vos entraînements.</p>
        <a href="catalogue.php" class="btn btn-vege px-4 mt-2" style="border-radius:30px;"><i class="fas fa-search mr-2"></i>Voir le catalogue</a>
    </div>
</div>
<?php else: ?>

<div class="row justify-content-center mb-4">
    <div class="col-md-7 text-center heading-section">
        <span class="subheading">Vos exercices</span>
        <h2 class="mb-2">Programme personnalisé</h2>
    </div>
</div>

<div class="row">
<?php
$sportImages=['Course à pied'=>'image_1.jpg','Musculation'=>'bg_3.jpg','HIIT'=>'bg_1.jpg','Yoga'=>'about.jpg',
              'Cyclisme'=>'image_2.jpg','Natation'=>'image_3.jpg','Football'=>'image_4.jpg','Autre'=>'category-2.jpg'];
foreach ($items as $e):
    $img = $sportImages[$e['type_sport']] ?? 'bg_1.jpg';
?>
<div class="col-md-6 col-lg-4">
    <div class="product">
        <a href="detail.php?id=<?= $e['id'] ?>" class="img-prod">
            <img src="../../public/vegefoods/images/<?= $img ?>" alt="">
            <span class="status" style="background:#82ae46;color:#fff;">Dans mon programme</span>
            <div class="overlay"></div>
        </a>
        <div class="text">
            <h3><a href="detail.php?id=<?= $e['id'] ?>"><?= htmlspecialchars($e['nom']) ?></a></h3>
            <p class="text-muted" style="font-size:.8rem;margin-bottom:6px;">
                <i class="fas fa-dumbbell mr-1" style="color:#82ae46;"></i><?= htmlspecialchars($e['type_sport']) ?>
                &nbsp;•&nbsp;<i class="fas fa-fire mr-1" style="color:#f5576c;"></i><?= number_format($e['total_calories'],0) ?> kcal
                &nbsp;•&nbsp;<?= $e['nb_seances'] ?> séance(s)
            </p>
            <div class="d-flex" style="gap:6px;">
                <a href="workout.php?id=<?= $e['id'] ?>" class="btn btn-sm flex-fill"
                   style="background:linear-gradient(135deg,#f093fb,#f5576c);color:#fff;border-radius:20px;border:none;font-size:.78rem;">
                    <i class="fas fa-play mr-1"></i>Workout
                </a>
                <a href="log_seance.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-vege flex-fill" style="border-radius:20px;font-size:.78rem;">
                    <i class="fas fa-check mr-1"></i>Compléter
                </a>
                <a href="programme.php?remove=<?= $e['id'] ?>" class="btn btn-sm btn-outline-danger" style="border-radius:20px;font-size:.78rem;"
                   onclick="return confirm('Retirer du programme ?')"><i class="fas fa-times"></i></a>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
