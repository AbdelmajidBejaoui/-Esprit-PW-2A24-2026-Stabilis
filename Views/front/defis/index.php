<?php
session_start();
require_once __DIR__ . '/../../../Controllers/DefiController.php';

$controller = new DefiController();
$search = trim($_GET['search'] ?? '');
$type = trim($_GET['type'] ?? '');
$defis = $controller->getAll($search);
if ($type !== '') {
    $defis = array_values(array_filter($defis, fn($d) => $d['type'] === $type));
}
$totalDefis = $controller->count();
$co2Estimate = $controller->getEcoImpact();
$cartCount = array_sum($_SESSION['cart'] ?? []);

function defiIcon(string $type): string
{
    return ['aliment' => 'fa-apple-alt', 'entrainement' => 'fa-dumbbell', 'compensation' => 'fa-leaf'][$type] ?? 'fa-star';
}

function defiTypeLabel(string $type): string
{
    return ['aliment' => 'Nutrition', 'entrainement' => 'Entrainement', 'compensation' => 'Compensation'][$type] ?? ucfirst($type);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Defis - Stabilis</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/stabilis.css?v=8">
    <link rel="stylesheet" href="../../../assets/css/front-style.css?v=12">
    <link rel="stylesheet" href="../../../assets/css/front-pages.css?v=7">
    <style>
        body.defis-page {
            background: #fbfbf7;
        }

        .defis-hero {
            position: relative;
            min-height: 430px;
            display: flex;
            align-items: end;
            color: #fff;
            background:
                linear-gradient(90deg, rgba(18, 56, 38, 0.88), rgba(18, 56, 38, 0.45)),
                url('https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=1600&h=900&fit=crop&crop=center');
            background-size: cover;
            background-position: center;
            overflow: hidden;
        }

        .defis-hero .container {
            position: relative;
            z-index: 1;
            padding-top: 78px;
            padding-bottom: 64px;
        }

        .defis-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.22);
            font-weight: 700;
            font-size: 13px;
        }

        .defis-hero h1 {
            max-width: 760px;
            margin: 18px 0 12px;
            color: #fff;
            font-size: clamp(38px, 7vw, 72px);
            line-height: 0.98;
            font-weight: 800;
        }

        .defis-hero p {
            max-width: 650px;
            color: rgba(255, 255, 255, 0.86);
            font-size: 18px;
        }

        .defis-hero-stats {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 28px;
        }

        .defis-hero-stat {
            min-width: 150px;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 12px;
            backdrop-filter: blur(8px);
        }

        .defis-hero-stat strong {
            display: block;
            color: #fff;
            font-size: 24px;
            line-height: 1;
        }

        .defis-hero-stat span {
            display: block;
            margin-top: 6px;
            color: rgba(255, 255, 255, 0.74);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .defis-section {
            padding: 48px 0 72px;
        }

        .defis-panel {
            margin-top: -86px;
            position: relative;
            z-index: 3;
            background: #fff;
            border: 1px solid rgba(26, 77, 58, 0.08);
            border-radius: 18px;
            box-shadow: 0 20px 48px rgba(28, 54, 38, 0.12);
            padding: 18px;
            animation: defisRise .55s ease-out both;
        }

        .defis-filters {
            display: grid;
            grid-template-columns: 1fr 220px auto auto auto;
            gap: 10px;
            align-items: center;
        }

        .defis-filters .form-control {
            background: #fff;
            border-radius: 10px;
        }

        .defis-filter-btn,
        .defis-rank-btn,
        .defis-my-btn {
            min-height: 42px;
            border-radius: 10px;
            padding: 0 16px;
            border: 0;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 800;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .defis-filter-btn {
            background: var(--accent-herb);
            color: #fff;
        }

        .defis-filter-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .defis-reset-btn {
            background: #ef4444;
            color: #fff;
        }

        .defis-reset-btn:hover {
            background: #dc2626;
        }

        .defis-my-btn {
            background: var(--accent-earth-light);
            color: var(--accent-earth-dark);
            border: 1px solid #efdfbd;
        }

        .defis-rank-btn {
            background: #1f6f51;
            color: #fff;
            border: 1px solid rgba(255,255,255,.08);
            box-shadow: 0 12px 24px rgba(31, 111, 81, .18);
        }

        .defis-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
            margin-top: 30px;
        }

        .defi-card {
            position: relative;
            min-height: 340px;
            background: #fff;
            border: 1px solid #edf1eb;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 14px 28px rgba(35, 56, 41, 0.08);
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
            animation: defisCardIn .55s ease-out both;
        }

        .defi-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 22px 36px rgba(35, 56, 41, 0.14);
            border-color: rgba(58, 107, 75, 0.28);
        }

        .defi-card-media {
            height: 118px;
            background:
                linear-gradient(135deg, rgba(26, 77, 58, 0.86), rgba(198, 161, 91, 0.48)),
                url('https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=900&h=420&fit=crop&crop=center');
            background-size: cover;
            background-position: center;
        }

        .defi-card.entrainement .defi-card-media {
            background:
                linear-gradient(135deg, rgba(36, 71, 52, 0.78), rgba(198, 161, 91, 0.54)),
                url('https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=900&h=420&fit=crop&crop=center');
            background-size: cover;
            background-position: center;
        }

        .defi-card.compensation .defi-card-media {
            background:
                linear-gradient(135deg, rgba(24, 88, 61, 0.8), rgba(62, 126, 90, 0.45)),
                url('https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=900&h=420&fit=crop&crop=center');
            background-size: cover;
            background-position: center;
        }

        .defi-card-body {
            padding: 22px;
        }

        .defi-topline {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-top: -48px;
            margin-bottom: 18px;
        }

        .defi-icon {
            width: 58px;
            height: 58px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: #fff;
            color: var(--accent-herb);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.14);
            font-size: 24px;
        }

        .defi-number {
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.92);
            color: var(--accent-herb-dark);
            font-weight: 900;
            font-size: 12px;
            box-shadow: 0 8px 18px rgba(0,0,0,0.12);
        }

        .defi-type {
            display: inline-flex;
            margin-bottom: 14px;
            padding: 6px 10px;
            border-radius: 999px;
            background: var(--accent-herb-light);
            color: var(--accent-herb-dark);
            font-size: 12px;
            font-weight: 800;
        }

        .defi-card h3 {
            margin: 0 0 10px;
            font-size: 20px;
            line-height: 1.2;
        }

        .defi-card p {
            color: #5f675f;
            font-size: 14px;
            line-height: 1.55;
            min-height: 66px;
            margin: 0 0 18px;
        }

        .defi-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-top: 1px solid #edf1eb;
            padding-top: 16px;
        }

        .defi-reward {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--accent-earth-dark);
            font-weight: 900;
            font-size: 13px;
        }

        .defi-open {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 38px;
            padding: 0 14px;
            border-radius: 999px;
            background: var(--accent-herb);
            color: #fff;
            text-decoration: none;
            font-weight: 800;
            font-size: 13px;
        }

        .defis-empty {
            margin-top: 30px;
            padding: 54px 20px;
            text-align: center;
            background: #fff;
            border: 1px dashed #dfe7df;
            border-radius: 18px;
            color: var(--text-secondary);
        }

        .defis-empty i {
            color: var(--accent-herb);
            font-size: 42px;
            margin-bottom: 14px;
        }

        .defis-approach {
            margin-top: 34px;
            padding: 42px 28px;
            background: #fff;
            border: 1px solid #edf1eb;
            border-radius: 22px;
            box-shadow: 0 18px 38px rgba(35, 56, 41, 0.08);
            animation: defisRise .6s ease-out both;
        }

        .defis-approach-head {
            max-width: 820px;
            margin: 0 auto 30px;
            text-align: center;
        }

        .defis-approach-head h2 {
            margin: 0 0 12px;
            font-size: clamp(30px, 4vw, 48px);
            font-weight: 900;
            color: #111827;
        }

        .defis-approach-head em {
            color: #1769ff;
            font-style: normal;
        }

        .defis-approach-head p {
            margin: 0;
            color: #5f675f;
            font-size: 17px;
        }

        .defis-approach-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .defis-approach-card {
            min-height: 210px;
            padding: 24px 18px;
            border: 1px solid #edf1eb;
            border-radius: 18px;
            background: #fff;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 10px 22px rgba(35, 56, 41, 0.05);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .defis-approach-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 34px rgba(35, 56, 41, 0.12);
        }

        .defis-approach-card .approach-icon {
            width: 88px;
            height: 88px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 32px;
        }

        .approach-icon.green { background:#d9f9e5; color:#0b8f5f; }
        .approach-icon.yellow { background:#fff0bd; color:#ffae00; }
        .approach-icon.blue { background:#c9f5fb; color:#06b6d4; }

        .defis-approach-card h3 {
            margin: 8px 0 0;
            font-size: 20px;
            font-weight: 900;
        }

        .defis-approach-card p {
            margin: 0;
            color: #4f5b54;
            line-height: 1.5;
        }

        @keyframes defisRise {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes defisCardIn {
            from { opacity: 0; transform: translateY(28px) scale(.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }


        @media (max-width: 980px) {
            .defis-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .defis-filters {
                grid-template-columns: 1fr 1fr;
            }
            .defis-approach-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .defis-hero {
                min-height: 520px;
            }
            .defis-panel {
                margin-top: -58px;
            }
            .defis-grid,
            .defis-filters {
                grid-template-columns: 1fr;
            }
            .defis-approach-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="defis-page">
<?php $activeFrontPage = 'defis'; require __DIR__ . '/../partials/navigation.php'; ?>
<main>
    <section class="defis-hero">
        <div class="container">
            <span class="defis-kicker"><i class="fas fa-seedling"></i> Nutrition durable & performance</span>
            <h1>Des defis pour mieux manger, bouger et reduire l'impact.</h1>
            <p>Choisissez un objectif, demarrez votre participation, puis envoyez une preuve depuis votre espace personnel.</p>
            <div class="defis-hero-stats">
                <div class="defis-hero-stat"><strong><?php echo $totalDefis; ?></strong><span>Defis disponibles</span></div>
                <div class="defis-hero-stat"><strong><?php echo number_format($co2Estimate, 1, ',', ' '); ?> kg</strong><span>CO2 estime</span></div>
                <div class="defis-hero-stat"><strong><?php echo count($defis); ?></strong><span>Resultats affiches</span></div>
            </div>
        </div>
    </section>

    <section class="defis-section">
        <div class="container">
            <div class="defis-panel">
                <form method="GET" class="defis-filters" id="defisForm">
                    <input class="form-control" name="search" id="searchInput" value="<?php echo htmlspecialchars($search); ?>" placeholder="Rechercher par nom ou ID">
                    <select class="form-control" name="type" id="typeFilter">
                        <option value="">Tous les types</option>
                        <?php foreach (['aliment','entrainement','compensation'] as $option): ?>
                        <option value="<?php echo $option; ?>" <?php echo $type === $option ? 'selected' : ''; ?>><?php echo defiTypeLabel($option); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="defis-filter-btn defis-reset-btn" type="button" onclick="resetFilters()"><i class="fas fa-redo"></i> Réinitialiser</button>
                    <a class="defis-rank-btn" href="weekly-recap.php"><i class="fas fa-ranking-star"></i> Classement</a>
                    <?php if (isset($_SESSION['front_user_id'])): ?>
                        <a class="defis-my-btn" href="mes-defis.php"><i class="fas fa-chart-line"></i> Mes defis</a>
                    <?php else: ?>
                        <a class="defis-my-btn" href="../users/login.php"><i class="fas fa-user"></i> Connexion</a>
                    <?php endif; ?>
                </form>
            </div>

            <section class="defis-approach">
                <div class="defis-approach-head">
                    <h2>Une approche <em>simple et efficace</em></h2>
                    <p>Nutrition durable, performance mesurable, impact positif</p>
                </div>
                <div class="defis-approach-grid">
                    <article class="defis-approach-card">
                        <span class="approach-icon green"><i class="fas fa-leaf"></i></span>
                        <h3>Nutrition durable</h3>
                        <p>Des habitudes simples autour des produits locaux et de saison.</p>
                    </article>
                    <article class="defis-approach-card">
                        <span class="approach-icon yellow"><i class="fas fa-fire"></i></span>
                        <h3>Performance</h3>
                        <p>Des objectifs concrets pour progresser sans rendre le suivi lourd.</p>
                    </article>
                    <article class="defis-approach-card">
                        <span class="approach-icon blue"><i class="fas fa-globe"></i></span>
                        <h3>Engagement</h3>
                        <p>Chaque defi aide a reduire l'impact tout en gardant le plaisir.</p>
                    </article>
                    <article class="defis-approach-card">
                        <span class="approach-icon green"><i class="fas fa-trophy"></i></span>
                        <h3>Recompenses</h3>
                        <p>Gagnez des points, montez dans le classement et restez motive.</p>
                    </article>
                </div>
            </section>

            <?php if (!$defis): ?>
                <div class="defis-empty">
                    <i class="fas fa-search"></i>
                    <h3>Aucun defi trouve</h3>
                    <p>Essayez un autre mot-cle ou retirez le filtre de type.</p>
                </div>
            <?php else: ?>
                <div class="defis-grid">
                    <?php foreach ($defis as $index => $defi): ?>
                    <article class="defi-card <?php echo htmlspecialchars($defi['type']); ?>" data-reveal="up">
                        <div class="defi-card-media"></div>
                        <div class="defi-card-body">
                            <div class="defi-topline">
                                <span class="defi-icon"><i class="fas <?php echo defiIcon($defi['type']); ?>"></i></span>
                                <span class="defi-number">#<?php echo str_pad((string)$defi['id'], 2, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <span class="defi-type"><?php echo defiTypeLabel($defi['type']); ?></span>
                            <h3><?php echo htmlspecialchars($defi['nom']); ?></h3>
                            <p><?php echo htmlspecialchars($defi['objectif']); ?></p>
                            <div class="defi-card-footer">
                                <span class="defi-reward"><i class="fas fa-coins"></i> <?php echo htmlspecialchars($defi['recompense']); ?></span>
                                <a class="defi-open" href="detail.php?id=<?php echo (int)$defi['id']; ?>">Voir <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<footer class="site-footer">
    <div class="container">
        <span>Stabilis<sup>&trade;</sup> &middot; defis durables</span>
    </div>
</footer>
<script src="../../../assets/js/front-animations.js"></script>
<script>
    // Auto-submit form on type or search changes
    const typeFilter = document.getElementById('typeFilter');
    const searchInput = document.getElementById('searchInput');
    const defisForm = document.getElementById('defisForm');
    
    // Submit form when type changes
    typeFilter.addEventListener('change', function() {
        defisForm.submit();
    });
    
    // Submit form when user finishes typing (debounce)
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            defisForm.submit();
        }, 500); // Wait 500ms after user stops typing
    });
    
    // Reset filters function
    function resetFilters() {
        searchInput.value = '';
        typeFilter.value = '';
        defisForm.submit();
    }
</script>
</body>
</html>
