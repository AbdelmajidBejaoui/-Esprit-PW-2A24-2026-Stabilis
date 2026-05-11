<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../Controller/AIGeneratorC.php';
require_once __DIR__ . '/../../Controller/ProgrammeC.php';

$aiGen = new AIGeneratorC();
$pC = new ProgrammeC();

// Define AI-generated workout recommendations
$recommendations = [
    // Perte de graisse
    ['goal' => 'perte_graisse', 'niveau' => 'debutant', 'prompt' => 'Programme cardio pour débutant, 6 exercices accessibles sans équipement', 'icon' => '🔥', 'color' => '#f5576c'],
    ['goal' => 'perte_graisse', 'niveau' => 'intermediaire', 'prompt' => 'HIIT intense pour brûler les graisses, 8 exercices variés', 'icon' => '🔥', 'color' => '#f5576c'],
    ['goal' => 'perte_graisse', 'niveau' => 'avance', 'prompt' => 'Circuit métabolique explosif, 10 exercices haute intensité', 'icon' => '🔥', 'color' => '#f5576c'],
    
    // Prise de muscle
    ['goal' => 'prise_muscle', 'niveau' => 'debutant', 'prompt' => 'Musculation corps entier pour débutant, 6 exercices de base', 'icon' => '💪', 'color' => '#82ae46'],
    ['goal' => 'prise_muscle', 'niveau' => 'intermediaire', 'prompt' => 'Programme hypertrophie, 8 exercices pour développer la masse musculaire', 'icon' => '💪', 'color' => '#82ae46'],
    ['goal' => 'prise_muscle', 'niveau' => 'avance', 'prompt' => 'Force maximale, 10 exercices avec charges lourdes', 'icon' => '💪', 'color' => '#82ae46'],
    
    // Endurance
    ['goal' => 'endurance', 'niveau' => 'debutant', 'prompt' => 'Améliorer le souffle et la résistance, 6 exercices cardio progressifs', 'icon' => '🏃', 'color' => '#4facfe'],
    ['goal' => 'endurance', 'niveau' => 'intermediaire', 'prompt' => 'Endurance cardiovasculaire, 8 exercices pour améliorer la VMA', 'icon' => '🏃', 'color' => '#4facfe'],
    ['goal' => 'endurance', 'niveau' => 'avance', 'prompt' => 'Endurance extrême, 10 exercices longue durée haute intensité', 'icon' => '🏃', 'color' => '#4facfe'],
    
    // Spécialisés
    ['goal' => 'prise_muscle', 'niveau' => 'intermediaire', 'prompt' => 'Focus haut du corps, 8 exercices pour bras, épaules et dos', 'icon' => '💪', 'color' => '#667eea'],
    ['goal' => 'prise_muscle', 'niveau' => 'intermediaire', 'prompt' => 'Focus bas du corps, 8 exercices pour jambes et fessiers', 'icon' => '🦵', 'color' => '#764ba2'],
    ['goal' => 'prise_muscle', 'niveau' => 'intermediaire', 'prompt' => 'Abdos et gainage, 8 exercices pour renforcer la sangle abdominale', 'icon' => '🎯', 'color' => '#f093fb'],
];

// Filter by goal if specified
$filterGoal = trim($_GET['goal'] ?? '');
if ($filterGoal && in_array($filterGoal, ['perte_graisse', 'prise_muscle', 'endurance'])) {
    $recommendations = array_filter($recommendations, fn($r) => $r['goal'] === $filterGoal);
}

// Filter by level if specified
$filterNiveau = trim($_GET['niveau'] ?? '');
if ($filterNiveau && in_array($filterNiveau, ['debutant', 'intermediaire', 'avance'])) {
    $recommendations = array_filter($recommendations, fn($r) => $r['niveau'] === $filterNiveau);
}

$goalLabels = ['perte_graisse' => 'Perte de graisse', 'prise_muscle' => 'Prise de muscle', 'endurance' => 'Endurance'];
$niveauLabels = ['debutant' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'avance' => 'Avancé'];

$pageTitle='Catalogue IA'; $heroTitle='Séances Générées par IA'; $heroBg='bg_1.jpg'; $activePage='catalogue';
$breadcrumb='<span>Catalogue IA</span>';
require_once __DIR__ . '/partials/layout_top.php';
?>

<!-- Hero Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;border-radius:16px;padding:24px;">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3 class="mb-2" style="font-weight:700;"><i class="fas fa-robot mr-2"></i>Catalogue Intelligent</h3>
                    <p class="mb-0" style="opacity:.9;">Toutes nos séances sont générées par IA selon votre objectif et votre niveau. Chaque séance est unique et optimisée pour vos besoins!</p>
                </div>
                <div class="col-md-4 text-right">
                    <a href="custom_workout.php" class="btn btn-lg" style="background:#fff;color:#764ba2;border-radius:30px;font-weight:700;padding:12px 24px;">
                        <i class="fas fa-magic mr-2"></i>Créer Ma Séance
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="row mb-4">
    <div class="col-md-6">
        <label style="font-weight:600;margin-bottom:8px;"><i class="fas fa-bullseye mr-2"></i>Objectif</label>
        <div class="d-flex flex-wrap" style="gap:8px;">
            <a href="catalogue.php" class="btn btn-sm <?= !$filterGoal?'btn-vege':'btn-outline-secondary' ?>" style="border-radius:20px;">Tous</a>
            <a href="catalogue.php?goal=perte_graisse<?= $filterNiveau?"&niveau=$filterNiveau":'' ?>" class="btn btn-sm <?= $filterGoal==='perte_graisse'?'btn-vege':'btn-outline-secondary' ?>" style="border-radius:20px;">🔥 Perte de graisse</a>
            <a href="catalogue.php?goal=prise_muscle<?= $filterNiveau?"&niveau=$filterNiveau":'' ?>" class="btn btn-sm <?= $filterGoal==='prise_muscle'?'btn-vege':'btn-outline-secondary' ?>" style="border-radius:20px;">💪 Prise de muscle</a>
            <a href="catalogue.php?goal=endurance<?= $filterNiveau?"&niveau=$filterNiveau":'' ?>" class="btn btn-sm <?= $filterGoal==='endurance'?'btn-vege':'btn-outline-secondary' ?>" style="border-radius:20px;">🏃 Endurance</a>
        </div>
    </div>
    <div class="col-md-6">
        <label style="font-weight:600;margin-bottom:8px;"><i class="fas fa-signal mr-2"></i>Niveau</label>
        <div class="d-flex flex-wrap" style="gap:8px;">
            <a href="catalogue.php<?= $filterGoal?"?goal=$filterGoal":'' ?>" class="btn btn-sm <?= !$filterNiveau?'btn-vege':'btn-outline-secondary' ?>" style="border-radius:20px;">Tous</a>
            <a href="catalogue.php?niveau=debutant<?= $filterGoal?"&goal=$filterGoal":'' ?>" class="btn btn-sm <?= $filterNiveau==='debutant'?'btn-vege':'btn-outline-secondary' ?>" style="border-radius:20px;">🌱 Débutant</a>
            <a href="catalogue.php?niveau=intermediaire<?= $filterGoal?"&goal=$filterGoal":'' ?>" class="btn btn-sm <?= $filterNiveau==='intermediaire'?'btn-vege':'btn-outline-secondary' ?>" style="border-radius:20px;">⚡ Intermédiaire</a>
            <a href="catalogue.php?niveau=avance<?= $filterGoal?"&goal=$filterGoal":'' ?>" class="btn btn-sm <?= $filterNiveau==='avance'?'btn-vege':'btn-outline-secondary' ?>" style="border-radius:20px;">🔥 Avancé</a>
        </div>
    </div>
</div>

<div class="row justify-content-center mb-4">
    <div class="col-md-8 text-center">
        <h2 class="mb-2">Séances Recommandées</h2>
        <p class="text-muted">Cliquez sur "Générer" pour créer une séance personnalisée avec l'IA</p>
    </div>
</div>

<!-- Recommendations Grid -->
<div class="row">
<?php foreach($recommendations as $i => $rec): ?>
<div class="col-md-6 col-lg-4 mb-4">
    <div class="card" style="border:none;border-radius:16px;box-shadow:0 4px 15px rgba(0,0,0,.1);overflow:hidden;transition:.3s;height:100%;" onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 8px 25px rgba(0,0,0,.15)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 15px rgba(0,0,0,.1)'">
        <!-- Header with gradient -->
        <div style="background:linear-gradient(135deg,<?= $rec['color'] ?>,#333);padding:24px;color:#fff;">
            <div style="font-size:2.5rem;margin-bottom:8px;"><?= $rec['icon'] ?></div>
            <h5 style="font-weight:700;margin-bottom:4px;"><?= $goalLabels[$rec['goal']] ?></h5>
            <span class="badge" style="background:rgba(255,255,255,.2);color:#fff;font-size:.75rem;padding:4px 12px;border-radius:12px;">
                <?= $niveauLabels[$rec['niveau']] ?>
            </span>
        </div>
        
        <!-- Body -->
        <div class="card-body" style="padding:20px;">
            <p style="color:#666;font-size:.9rem;margin-bottom:16px;min-height:60px;">
                <?= htmlspecialchars($rec['prompt']) ?>
            </p>
            
            <div style="background:#f8f9fa;border-radius:10px;padding:12px;margin-bottom:16px;">
                <div class="d-flex justify-content-between" style="font-size:.8rem;">
                    <span><i class="fas fa-dumbbell mr-1" style="color:<?= $rec['color'] ?>;"></i>Personnalisé</span>
                    <span><i class="fas fa-robot mr-1" style="color:<?= $rec['color'] ?>;"></i>IA</span>
                    <span><i class="fas fa-bolt mr-1" style="color:<?= $rec['color'] ?>;"></i>Optimisé</span>
                </div>
            </div>
            
            <form method="POST" action="generate_recommended.php">
                <input type="hidden" name="goal" value="<?= $rec['goal'] ?>">
                <input type="hidden" name="niveau" value="<?= $rec['niveau'] ?>">
                <input type="hidden" name="prompt" value="<?= htmlspecialchars($rec['prompt']) ?>">
                <button type="submit" class="btn btn-block" style="background:<?= $rec['color'] ?>;color:#fff;border-radius:30px;font-weight:600;padding:12px;">
                    <i class="fas fa-magic mr-2"></i>Générer Cette Séance
                </button>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php if(empty($recommendations)): ?>
<div class="row">
    <div class="col-12 text-center py-5">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <h4 class="text-muted">Aucune séance trouvée</h4>
        <p class="text-muted">Essayez de modifier vos filtres</p>
        <a href="catalogue.php" class="btn btn-vege">Voir toutes les séances</a>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
