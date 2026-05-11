<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../Controller/EntrainementC.php';
require_once __DIR__ . '/../../Controller/ProgrammeC.php';
require_once __DIR__ . '/../../Controller/SeanceC.php';

$eC = new EntrainementC();
$pC = new ProgrammeC();
$sC = new SeanceC();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$entrainement = $eC->getById($id);
if (!$entrainement) { header('Location: catalogue.php'); exit; }

$etapes  = $eC->getEtapes($id);
$inProg  = frontIsLoggedIn() && $pC->isInProgramme(frontUserId(), $id);

// Add to programme
if (frontIsLoggedIn() && isset($_GET['add_prog'])) {
    $pC->add(frontUserId(), $id);
    flash('success', 'Entraînement ajouté à votre programme !');
    header("Location: detail.php?id=$id"); exit;
}

$sportImages = ['Course à pied'=>'image_1.jpg','Musculation'=>'bg_3.jpg','HIIT'=>'bg_1.jpg','Yoga'=>'about.jpg',
                'Cyclisme'=>'image_2.jpg','Natation'=>'image_3.jpg','Football'=>'image_4.jpg','Autre'=>'category-2.jpg'];
$heroBg = $sportImages[$entrainement->getTypeSport()] ?? 'bg_1.jpg';

$pageTitle = $entrainement->getNom();
$heroTitle = $entrainement->getNom();
$activePage = 'catalogue';
$breadcrumb = '<span class="mr-2"><a href="catalogue.php">Catalogue</a></span><span>'.$entrainement->getNom().'</span>';
require_once __DIR__ . '/partials/layout_top.php';
?>

<div class="row">
    <!-- INFO CARD -->
    <div class="col-lg-4 mb-5">
        <div style="border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,.1);">
            <div style="background:linear-gradient(135deg,#82ae46,#4a7c20);color:white;padding:24px;">
                <span class="badge-<?= $entrainement->getNiveau() ?>"><?= ucfirst($entrainement->getNiveau()) ?></span>
                <h4 class="mt-2 mb-0"><?= htmlspecialchars($entrainement->getNom()) ?></h4>
            </div>
            <div style="background:#fff;padding:24px;">
                <div class="d-flex align-items-center mb-3">
                    <div style="width:38px;height:38px;background:#f0f8e8;border-radius:50%;display:flex;align-items:center;justify-content:center;margin-right:12px;"><i class="fas fa-dumbbell" style="color:#82ae46;"></i></div>
                    <div><small class="text-muted d-block">Type</small><strong><?= htmlspecialchars($entrainement->getTypeSport()) ?></strong></div>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <div style="width:38px;height:38px;background:#f0f8e8;border-radius:50%;display:flex;align-items:center;justify-content:center;margin-right:12px;"><i class="fas fa-bolt" style="color:#82ae46;"></i></div>
                    <div><small class="text-muted d-block">Valeur MET</small><strong><?= $entrainement->getMetValue() ?></strong></div>
                </div>
                <?php if(frontIsLoggedIn()): ?>
                <?php $ud = frontUser(); if(!empty($ud['poids'])): ?>
                <div style="background:#f0f8e8;border-radius:10px;padding:12px;margin-top:8px;">
                    <small class="text-muted d-block mb-1">Calories estimées pour vous</small>
                    <?php foreach([30,45,60] as $dur): ?>
                    <div class="d-flex justify-content-between">
                        <span style="font-size:.82rem;"><?= $dur ?> min</span>
                        <strong style="color:#82ae46;"><?= round($entrainement->getMetValue() * (float)$ud['poids'] * ($dur/60), 0) ?> kcal</strong>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?><p class="text-muted small mt-2">Complétez votre <a href="profil.php">profil</a> pour voir vos calories estimées.</p><?php endif; ?>
                <?php endif; ?>
                <?php if($entrainement->getDescription()): ?><hr><p class="text-muted" style="font-size:.88rem;"><?= nl2br(htmlspecialchars($entrainement->getDescription())) ?></p><?php endif; ?>
            </div>
            <div style="padding:16px 24px;background:#f9f9f9;border-top:1px solid #eee;">
                <?php if(frontIsLoggedIn()): ?>
                <?php if($inProg): ?>
                <a href="programme.php" class="btn btn-warning btn-block" style="border-radius:20px;font-size:.85rem;">
                    <i class="fas fa-check mr-1"></i>Dans votre programme → Voir
                </a>
                <a href="workout.php?id=<?= $id ?>" class="btn btn-block mt-2" style="background:linear-gradient(135deg,#f093fb,#f5576c);color:#fff;border-radius:20px;border:none;font-size:.85rem;">
                    <i class="fas fa-play mr-1"></i>🔥 Démarrer le Workout
                </a>
                <?php else: ?>
                <a href="detail.php?id=<?= $id ?>&add_prog=1" class="btn btn-vege btn-block" style="font-size:.85rem;border-radius:20px;">
                    <i class="fas fa-plus mr-1"></i>Ajouter à mon programme
                </a>
                <?php endif; ?>
                <?php else: ?>
                <a href="login.php" class="btn btn-vege btn-block" style="border-radius:20px;">Connectez-vous pour continuer</a>
                <?php endif; ?>
                <a href="catalogue.php" class="btn btn-block mt-2" style="border-radius:20px;border:1px solid #ccc;color:#666;font-size:.85rem;">← Retour</a>
            </div>
        </div>
    </div>

    <!-- TUTORIEL -->
    <div class="col-lg-8">
        <div class="heading-section mb-4">
            <span class="subheading">Guide pratique</span>
            <h3>📋 Tutoriel étape par étape</h3>
        </div>
        <?php if(empty($etapes)): ?>
        <div style="background:#f9f9f9;border-radius:12px;padding:30px;text-align:center;color:#aaa;">
            <i class="fas fa-list-ol fa-3x mb-3"></i><p>Aucune étape définie pour cet entraînement.</p>
        </div>
        <?php else: ?>
        <?php $totalDuree=array_sum(array_column($etapes,'duree_secondes')); ?>
        <div class="mb-3 d-flex align-items-center flex-wrap" style="gap:10px;">
            <span style="background:#f0f8e8;border-radius:20px;padding:4px 14px;font-size:.85rem;color:#82ae46;font-weight:600;"><i class="fas fa-list-ol mr-1"></i><?= count($etapes) ?> étapes</span>
            <span style="background:#fff3cd;border-radius:20px;padding:4px 14px;font-size:.85rem;color:#856404;font-weight:600;"><i class="fas fa-clock mr-1"></i><?= floor($totalDuree/60) ?>m <?= $totalDuree%60 ?>s</span>
            <?php if($inProg): ?>
            <a href="workout.php?id=<?= $id ?>" class="btn btn-sm ml-auto" style="background:linear-gradient(135deg,#f093fb,#f5576c);color:#fff;border-radius:20px;border:none;"><i class="fas fa-play mr-1"></i>Mode Workout</a>
            <?php endif; ?>
        </div>
        <?php foreach($etapes as $i=>$s): ?>
        <div style="background:#fff;border-radius:12px;box-shadow:0 2px 14px rgba(0,0,0,.07);padding:20px 22px;margin-bottom:12px;border-left:4px solid #82ae46;">
            <div class="d-flex align-items-start">
                <div style="min-width:40px;height:40px;background:#82ae46;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;margin-right:14px;"><?= $i+1 ?></div>
                <div style="flex:1;">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 style="margin:0 0 5px;color:#333;font-size:.95rem;"><?= htmlspecialchars($s['titre']) ?></h5>
                        <span style="background:#f0f8e8;color:#82ae46;padding:2px 10px;border-radius:20px;font-size:.75rem;font-weight:600;margin-left:8px;white-space:nowrap;"><i class="fas fa-stopwatch mr-1"></i><?= $s['duree_secondes'] ?>s</span>
                    </div>
                    <p style="color:#555;margin-bottom:5px;font-size:.88rem;"><?= htmlspecialchars($s['description']) ?></p>
                    <?php if($s['conseil']): ?>
                    <div style="background:#fffde7;border-left:3px solid #ffc107;padding:5px 10px;border-radius:0 8px 8px 0;font-size:.8rem;color:#7a6000;">
                        <i class="fas fa-lightbulb mr-1" style="color:#ffc107;"></i><?= htmlspecialchars($s['conseil']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
