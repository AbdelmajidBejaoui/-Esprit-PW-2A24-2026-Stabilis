<?php
session_start();
require_once __DIR__ . '/../../../Controllers/NutritionController.php';

$controller = new NutritionController();
$model = $controller->model();
$cartCount = array_sum($_SESSION['cart'] ?? []);
$search = trim($_GET['search'] ?? '');
$alimentSort = $_GET['aliment_sort'] ?? 'nom_asc';
$recetteSort = $_GET['recette_sort'] ?? 'performance_desc';
$objective = $_POST['objective'] ?? $_GET['objective'] ?? 'equilibre';
$photoAnalysis = '';
$photoEstimate = [];
$generatedRecipe = '';
$fridgeSuggestion = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['analyze_photo'])) {
        $photoEstimate = $controller->estimatePhotoCalories($_FILES['food_photo'] ?? []);
        $photoAnalysis = $photoEstimate['summary'] ?? '';
    } elseif (isset($_POST['generate_recipe'])) {
        $generatedRecipe = $controller->generatedRecipe($objective);
    } elseif (isset($_POST['smart_fridge'])) {
        $fridgeSuggestion = $model->smartFridge(array_map('intval', $_POST['aliments'] ?? []), $objective);
    }
}

$allowedAlimentSorts = ['nom_asc', 'calories_desc', 'proteines_desc', 'eco_desc'];
$allowedRecetteSorts = ['performance_desc', 'nom_asc', 'calories_asc', 'proteines_desc'];
if (!in_array($alimentSort, $allowedAlimentSorts, true)) {
    $alimentSort = 'nom_asc';
}
if (!in_array($recetteSort, $allowedRecetteSorts, true)) {
    $recetteSort = 'performance_desc';
}

$aliments = $model->aliments($search, $alimentSort);
$recettes = $model->recettes($search, $recetteSort);
$dailyMenu = $model->recommendRecipes(null, 3);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nutrition - Stabilis</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/stabilis.css?v=1">
    <link rel="stylesheet" href="../../../assets/css/front-style.css?v=10">
    <link rel="stylesheet" href="../../../assets/css/front-pages.css?v=5">
    <style>
        body.nutrition-page { background:#fbfbf7; }
        .nutrition-page .navbar { animation: navDrop .55s ease both; }
        .nutrition-hero { min-height:calc(100vh - 96px); display:flex; align-items:center; color:#fff; background:linear-gradient(90deg,rgba(18,56,38,.93),rgba(18,56,38,.45)), url('https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=1800&h=1100&fit=crop&crop=center'); background-size:cover; background-position:center; }
        .nutrition-hero h1 { max-width:880px; color:#fff; font-size:clamp(44px,7vw,82px); line-height:.96; font-weight:850; margin:18px 0; }
        .nutrition-hero p { max-width:700px; color:rgba(255,255,255,.86); font-size:19px; line-height:1.55; }
        .nutrition-kicker { display:inline-flex; gap:9px; align-items:center; padding:9px 13px; border-radius:999px; background:rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.22); font-weight:800; }
        .nutrition-kicker,.nutrition-hero h1,.nutrition-hero p,.hero-search,.hero-links { animation: heroRise .75s cubic-bezier(.22,1,.36,1) both; }
        .nutrition-hero h1 { animation-delay:.08s; }
        .nutrition-hero p { animation-delay:.16s; }
        .hero-search { animation-delay:.24s; }
        .hero-links { animation-delay:.32s; }
        .hero-search { display:flex; gap:10px; flex-wrap:wrap; margin-top:30px; max-width:780px; }
        .hero-search input { flex:1 1 320px; border:0; border-radius:999px; padding:16px 18px; font:inherit; }
        .hero-search button,.nutrition-btn { border:0; border-radius:999px; padding:14px 20px; background:#C6A15B; color:#183d2f; font-weight:850; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:8px; transition:transform .18s ease, box-shadow .18s ease, background .18s ease; }
        .hero-search button:hover,.nutrition-btn:hover { transform:translateY(-2px); box-shadow:0 12px 24px rgba(0,0,0,.14); }
        .hero-links { display:flex; gap:10px; flex-wrap:wrap; margin-top:18px; }
        .hero-links a { display:inline-flex; align-items:center; gap:8px; border-radius:999px; padding:11px 14px; color:#fff; text-decoration:none; background:rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.22); font-weight:800; transition:transform .18s ease, background .18s ease; }
        .hero-links a:hover { transform:translateY(-2px); background:rgba(255,255,255,.24); }
        .nutrition-section { padding:70px 0; }
        .nutrition-section.alt { background:#f2f7f1; }
        .section-head { display:flex; justify-content:space-between; align-items:end; gap:20px; flex-wrap:wrap; margin-bottom:28px; }
        .section-head h2 { margin:0; color:#1A4D3A; font-size:38px; font-weight:850; }
        .section-head p { max-width:620px; margin:8px 0 0; color:#657266; line-height:1.55; }
        .front-sort-form { display:flex; gap:9px; flex-wrap:wrap; align-items:center; background:#fff; border:1px solid #e3ece5; border-radius:999px; padding:7px; box-shadow:0 10px 24px rgba(26,77,58,.06); }
        .front-sort-form label { padding-left:10px; color:#1A4D3A; font-size:12px; font-weight:900; text-transform:uppercase; letter-spacing:.35px; }
        .front-sort-form select { border:0; min-height:38px; color:#1A4D3A; font:inherit; font-weight:800; background:transparent; }
        .front-sort-form button { border:0; border-radius:999px; min-height:38px; padding:0 13px; background:#1A4D3A; color:#fff; font-weight:850; cursor:pointer; }
        .card-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:20px; }
        .wide-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:18px; }
        .nutrition-card { background:#fff; border:1px solid #e3ece5; border-radius:18px; padding:22px; min-height:190px; box-shadow:0 16px 34px rgba(26,77,58,.08); animation: cardIn .62s cubic-bezier(.22,1,.36,1) both; transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
        .nutrition-card:hover { transform:translateY(-6px); box-shadow:0 24px 44px rgba(26,77,58,.13); border-color:#cfe2d3; }
        .nutrition-card:nth-child(2){ animation-delay:.05s; }
        .nutrition-card:nth-child(3){ animation-delay:.1s; }
        .nutrition-card:nth-child(4){ animation-delay:.15s; }
        .nutrition-card:nth-child(5){ animation-delay:.2s; }
        .nutrition-card:nth-child(6){ animation-delay:.25s; }
        .nutrition-card:nth-child(7){ animation-delay:.3s; }
        .nutrition-card:nth-child(8){ animation-delay:.35s; }
        .nutrition-card h3 { margin:0 0 9px; color:#1A4D3A; font-size:21px; }
        .nutrition-card p { margin:0; color:#647166; line-height:1.55; }
        .macro-row { display:flex; flex-wrap:wrap; gap:8px; margin-top:16px; }
        .macro-row span { display:inline-flex; border-radius:999px; background:#edf6ef; color:#1A4D3A; padding:7px 10px; font-size:12px; font-weight:850; }
        .tools-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:20px; }
        .tool-card { background:#fff; border:1px solid #e3ece5; border-radius:18px; padding:22px; box-shadow:0 16px 34px rgba(26,77,58,.08); animation: toolFloatIn .7s cubic-bezier(.22,1,.36,1) both; transition:transform .2s ease, box-shadow .2s ease; }
        .tool-card:nth-child(2){ animation-delay:.08s; }
        .tool-card:nth-child(3){ animation-delay:.16s; }
        .tool-card:hover { transform:translateY(-5px); box-shadow:0 24px 44px rgba(26,77,58,.13); }
        .tool-card h3 { margin:0 0 14px; color:#1A4D3A; font-size:22px; }
        .tool-card select,.tool-card input[type=file] { width:100%; border:1px solid #dfe8df; border-radius:12px; padding:12px; margin-bottom:12px; font:inherit; }
        .check-list { display:grid; grid-template-columns:1fr 1fr; gap:8px; max-height:220px; overflow:auto; padding:12px; border:1px solid #edf1ed; border-radius:12px; margin-bottom:12px; background:#fbfdfb; }
        .result-box { margin-top:14px; padding:14px; border-radius:14px; background:#f3faf5; color:#24362b; line-height:1.6; }
        .result-box,.kcal-result { animation: resultPop .35s ease both; }
        .kcal-result { margin-top:14px; border:1px solid #dce9df; border-radius:16px; background:#f8fcf9; overflow:hidden; }
        .kcal-total { padding:16px; background:#1A4D3A; color:#fff; display:grid; grid-template-columns:1fr auto; gap:12px; align-items:center; }
        .kcal-total strong { display:block; color:#fff; font-size:34px; line-height:1; }
        .kcal-total span { color:rgba(255,255,255,.78); font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.4px; }
        .kcal-macros { display:grid; grid-template-columns:repeat(3,1fr); gap:1px; background:#dce9df; }
        .kcal-macros div { background:#fff; padding:12px; text-align:center; }
        .kcal-macros strong { display:block; color:#1A4D3A; }
        .kcal-items { padding:12px 14px; display:grid; gap:8px; }
        .kcal-item { display:flex; justify-content:space-between; gap:10px; padding:10px 0; border-bottom:1px solid #e6eee8; }
        .kcal-item:last-child { border-bottom:0; }
        .kcal-item small { display:block; color:#6f7b72; margin-top:3px; }
        .confidence-pill { display:inline-flex; border-radius:999px; background:rgba(255,255,255,.16); padding:7px 10px; font-size:12px; font-weight:850; }
        .nutrition-async-root { position:relative; }
        .nutrition-async-root.is-loading { cursor:progress; }
        .nutrition-async-root.is-loading::after { content:""; position:fixed; inset:0; z-index:40; pointer-events:none; background:rgba(251,251,247,.24); backdrop-filter:blur(1px); }
        .nutrition-async-root.is-loading .nutrition-section,
        .nutrition-async-root.is-loading .nutrition-hero { opacity:.72; transition:opacity .18s ease; }
        .nutrition-async-root button[disabled] { opacity:.68; cursor:progress; }
        @media (max-width:1050px){ .card-grid,.tools-grid{grid-template-columns:1fr;} .wide-grid{grid-template-columns:repeat(2,1fr);} }
        @media (max-width:640px){ .wide-grid{grid-template-columns:1fr;} .check-list{grid-template-columns:1fr;} .nutrition-hero{min-height:720px;} }
        @keyframes navDrop { from{opacity:0; transform:translateY(-10px);} to{opacity:1; transform:translateY(0);} }
        @keyframes heroRise { from{opacity:0; transform:translateY(26px);} to{opacity:1; transform:translateY(0);} }
        @keyframes cardIn { from{opacity:0; transform:translateY(22px) scale(.985);} to{opacity:1; transform:translateY(0) scale(1);} }
        @keyframes toolFloatIn { from{opacity:0; transform:translateY(28px);} to{opacity:1; transform:translateY(0);} }
        @keyframes resultPop { from{opacity:0; transform:scale(.98);} to{opacity:1; transform:scale(1);} }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration:.001ms !important; transition-duration:.001ms !important; scroll-behavior:auto !important; }
        }
    </style>
</head>
<body class="nutrition-page">
    <?php $activeFrontPage = 'nutrition'; require __DIR__ . '/../partials/navigation.php'; ?>

    <main class="nutrition-async-root" id="nutritionAsyncRoot">
    <section class="nutrition-hero">
        <div class="container">
            <span class="nutrition-kicker"><i class="fas fa-utensils"></i> Nutrition Stabilis</span>
            <h1>Aliments, recettes et IA dans un espace clair.</h1>
            <p>Explorez les aliments, consultez les recettes, composez un repas avec votre frigo et laissez l IA vous aider sans perdre le fil.</p>
            <form class="hero-search" method="GET">
                <input name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Rechercher un aliment ou une recette">
                <input type="hidden" name="aliment_sort" value="<?php echo htmlspecialchars($alimentSort); ?>">
                <input type="hidden" name="recette_sort" value="<?php echo htmlspecialchars($recetteSort); ?>">
                <button type="submit"><i class="fas fa-search"></i> Rechercher</button>
            </form>
            <div class="hero-links">
                <a href="#menu-jour"><i class="fas fa-calendar-day"></i> Menu de jour</a>
                <a href="#outils-ia"><i class="fas fa-robot"></i> Outils IA</a>
                <a href="#aliments"><i class="fas fa-apple-alt"></i> Aliments</a>
                <a href="#recettes"><i class="fas fa-bowl-food"></i> Recettes</a>
            </div>
        </div>
    </section>

    <section class="nutrition-section" id="menu-jour">
        <div class="container">
            <div class="section-head">
                <div><h2>Menu de jour recommande</h2><p>Trois recettes mises en avant selon leur performance nutritionnelle et leur equilibre.</p></div>
            </div>
            <div class="card-grid">
                <?php foreach ($dailyMenu as $recipe): ?>
                    <article class="nutrition-card">
                        <h3><?php echo htmlspecialchars($recipe['nom']); ?></h3>
                        <p><?php echo htmlspecialchars($recipe['description'] ?? 'Recette Stabilis.'); ?></p>
                        <div class="macro-row">
                            <span><?php echo (int)$recipe['totals']['calories']; ?> kcal</span>
                            <span><?php echo number_format((float)$recipe['totals']['proteines'], 1); ?>g proteines</span>
                            <span>Perf <?php echo number_format((float)$recipe['performance_score'], 1); ?>/10</span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="nutrition-section alt" id="outils-ia">
        <div class="container">
            <div class="section-head"><div><h2>Outils intelligents</h2><p>Chaque outil a son espace propre: frigo, analyse photo et generation de recette.</p></div></div>
            <div class="tools-grid">
                <form class="tool-card" method="POST">
                    <h3><i class="fas fa-snowflake"></i> Frigo intelligent</h3>
                    <select name="objective">
                        <option value="equilibre" <?php echo $objective === 'equilibre' ? 'selected' : ''; ?>>Equilibre</option>
                        <option value="musculation" <?php echo $objective === 'musculation' ? 'selected' : ''; ?>>Musculation</option>
                        <option value="regime" <?php echo $objective === 'regime' ? 'selected' : ''; ?>>Regime</option>
                    </select>
                    <div class="check-list">
                        <?php foreach ($model->aliments() as $a): ?><label><input type="checkbox" name="aliments[]" value="<?php echo (int)$a['id']; ?>"> <?php echo htmlspecialchars($a['nom']); ?></label><?php endforeach; ?>
                    </div>
                    <button class="nutrition-btn" name="smart_fridge" value="1" type="submit">Composer</button>
                    <?php if ($fridgeSuggestion): ?><div class="result-box"><strong>Suggestion:</strong> <?php echo htmlspecialchars(implode(', ', array_column($fridgeSuggestion['aliments'], 'nom'))); ?><br><?php echo (float)$fridgeSuggestion['totals']['calories']; ?> kcal - <?php echo (float)$fridgeSuggestion['totals']['proteines']; ?>g proteines</div><?php endif; ?>
                </form>

                <form class="tool-card" method="POST" enctype="multipart/form-data">
                    <h3><i class="fas fa-camera"></i> Calculateur kcal photo</h3>
                    <input type="file" name="food_photo" accept="image/jpeg,image/png,image/webp">
                    <button class="nutrition-btn" name="analyze_photo" value="1" type="submit">Calculer kcal</button>
                    <?php if ($photoEstimate): ?>
                        <div class="kcal-result">
                            <div class="kcal-total">
                                <div>
                                    <strong><?php echo (int)($photoEstimate['totals']['calories'] ?? 0); ?></strong>
                                    <span>kcal estimees</span>
                                </div>
                                <div class="confidence-pill"><?php echo (int)($photoEstimate['confidence'] ?? 0); ?>% confiance</div>
                            </div>
                            <div class="kcal-macros">
                                <div><strong><?php echo number_format((float)($photoEstimate['totals']['proteins'] ?? 0), 1); ?>g</strong><span>Proteines</span></div>
                                <div><strong><?php echo number_format((float)($photoEstimate['totals']['carbs'] ?? 0), 1); ?>g</strong><span>Glucides</span></div>
                                <div><strong><?php echo number_format((float)($photoEstimate['totals']['fats'] ?? 0), 1); ?>g</strong><span>Lipides</span></div>
                            </div>
                            <div class="kcal-items">
                                <p><?php echo htmlspecialchars($photoEstimate['summary'] ?? ''); ?></p>
                                <?php foreach (($photoEstimate['items'] ?? []) as $item): ?>
                                    <div class="kcal-item">
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['name'] ?? 'Aliment'); ?></strong>
                                            <small><?php echo htmlspecialchars($item['portion'] ?? 'portion estimee'); ?></small>
                                        </div>
                                        <strong><?php echo (int)($item['calories'] ?? 0); ?> kcal</strong>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (!empty($photoEstimate['advice'])): ?><small><?php echo htmlspecialchars($photoEstimate['advice']); ?></small><?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </form>

                <form class="tool-card" method="POST">
                    <h3><i class="fas fa-wand-magic-sparkles"></i> Generateur de recette</h3>
                    <select name="objective">
                        <option value="equilibre" <?php echo $objective === 'equilibre' ? 'selected' : ''; ?>>Equilibre</option>
                        <option value="musculation" <?php echo $objective === 'musculation' ? 'selected' : ''; ?>>Musculation</option>
                        <option value="regime" <?php echo $objective === 'regime' ? 'selected' : ''; ?>>Regime</option>
                    </select>
                    <button class="nutrition-btn" name="generate_recipe" value="1" type="submit">Generer</button>
                    <?php if ($generatedRecipe !== ''): ?><div class="result-box"><?php echo nl2br(htmlspecialchars($generatedRecipe)); ?></div><?php endif; ?>
                </form>
            </div>
        </div>
    </section>

    <section class="nutrition-section" id="aliments">
        <div class="container">
            <div class="section-head">
                <div><h2>Aliments</h2><p><?php echo count($aliments); ?> resultat(s). Macros simples et lisibles.</p></div>
                <form class="front-sort-form" method="GET">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="recette_sort" value="<?php echo htmlspecialchars($recetteSort); ?>">
                    <label for="frontAlimentSort">Trier</label>
                    <select id="frontAlimentSort" name="aliment_sort">
                        <option value="nom_asc" <?php echo $alimentSort === 'nom_asc' ? 'selected' : ''; ?>>Nom A-Z</option>
                        <option value="proteines_desc" <?php echo $alimentSort === 'proteines_desc' ? 'selected' : ''; ?>>Proteines hautes</option>
                        <option value="calories_desc" <?php echo $alimentSort === 'calories_desc' ? 'selected' : ''; ?>>Calories hautes</option>
                        <option value="eco_desc" <?php echo $alimentSort === 'eco_desc' ? 'selected' : ''; ?>>Eco score haut</option>
                    </select>
                    <button type="submit"><i class="fas fa-sort"></i></button>
                </form>
            </div>
            <div class="wide-grid">
                <?php foreach (array_slice($aliments, 0, 12) as $a): ?>
                    <article class="nutrition-card">
                        <h3><?php echo htmlspecialchars($a['nom']); ?></h3>
                        <p><?php echo htmlspecialchars($a['description'] ?? ''); ?></p>
                        <div class="macro-row"><span><?php echo (int)$a['calories']; ?> kcal</span><span><?php echo number_format((float)$a['proteines'], 1); ?>g prot.</span><span>Eco <?php echo $model->ecoScore($a); ?>/10</span></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="nutrition-section alt" id="recettes">
        <div class="container">
            <div class="section-head">
                <div><h2>Recettes</h2><p><?php echo count($recettes); ?> resultat(s). Performance et details nutritionnels.</p></div>
                <form class="front-sort-form" method="GET">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="aliment_sort" value="<?php echo htmlspecialchars($alimentSort); ?>">
                    <label for="frontRecetteSort">Trier</label>
                    <select id="frontRecetteSort" name="recette_sort">
                        <option value="performance_desc" <?php echo $recetteSort === 'performance_desc' ? 'selected' : ''; ?>>Performance haute</option>
                        <option value="nom_asc" <?php echo $recetteSort === 'nom_asc' ? 'selected' : ''; ?>>Nom A-Z</option>
                        <option value="calories_asc" <?php echo $recetteSort === 'calories_asc' ? 'selected' : ''; ?>>Calories basses</option>
                        <option value="proteines_desc" <?php echo $recetteSort === 'proteines_desc' ? 'selected' : ''; ?>>Proteines hautes</option>
                    </select>
                    <button type="submit"><i class="fas fa-sort"></i></button>
                </form>
            </div>
            <div class="wide-grid">
                <?php foreach (array_slice($recettes, 0, 12) as $r): ?>
                    <article class="nutrition-card">
                        <h3><?php echo htmlspecialchars($r['nom']); ?></h3>
                        <p><?php echo htmlspecialchars($r['description'] ?? ''); ?></p>
                        <div class="macro-row"><span><?php echo (int)$r['totals']['calories']; ?> kcal</span><span><?php echo number_format((float)$r['totals']['proteines'], 1); ?>g prot.</span><span>Perf <?php echo number_format((float)$r['performance_score'], 1); ?>/10</span></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    </main>
    <script>
    (() => {
        const rootId = 'nutritionAsyncRoot';

        const root = () => document.getElementById(rootId);
        const sectionTarget = (form) => {
            if (form.classList.contains('hero-search')) {
                return 'aliments';
            }
            return form.closest('section')?.id || 'outils-ia';
        };

        const setBusy = (form, busy) => {
            root()?.classList.toggle('is-loading', busy);
            form.querySelectorAll('button, input, select').forEach((control) => {
                if (control.type !== 'hidden') {
                    control.disabled = busy;
                }
            });
        };

        const replaceNutrition = async (responseText, targetId) => {
            const doc = new DOMParser().parseFromString(responseText, 'text/html');
            const nextRoot = doc.getElementById(rootId);
            if (!nextRoot || !root()) {
                throw new Error('Nutrition content missing');
            }
            root().innerHTML = nextRoot.innerHTML;
            const target = document.getElementById(targetId);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        };

        document.addEventListener('submit', async (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.closest('#' + rootId)) {
                return;
            }

            event.preventDefault();
            const method = (form.method || 'GET').toUpperCase();
            const submitter = event.submitter;
            const targetId = sectionTarget(form);
            const requestUrl = new URL(form.action || window.location.href, window.location.href);
            const options = { headers: { 'X-Requested-With': 'fetch' } };

            if (method === 'GET') {
                requestUrl.search = new URLSearchParams(new FormData(form)).toString();
                options.method = 'GET';
            } else {
                const data = new FormData(form);
                if (submitter?.name) {
                    data.append(submitter.name, submitter.value || '1');
                }
                options.method = 'POST';
                options.body = data;
            }

            try {
                setBusy(form, true);
                const response = await fetch(requestUrl.toString(), options);
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                await replaceNutrition(await response.text(), targetId);
                if (method === 'GET') {
                    history.pushState({}, '', requestUrl.toString());
                }
            } catch (error) {
                console.error(error);
                setBusy(form, false);
                form.submit();
            } finally {
                const currentForm = document.activeElement?.closest?.('form') || form;
                setBusy(currentForm, false);
            }
        });

        document.addEventListener('change', (event) => {
            const select = event.target;
            const form = select instanceof HTMLSelectElement ? select.closest('form.front-sort-form') : null;
            if (form?.closest('#' + rootId)) {
                form.requestSubmit();
            }
        });
    })();
    </script>
</body>
</html>
