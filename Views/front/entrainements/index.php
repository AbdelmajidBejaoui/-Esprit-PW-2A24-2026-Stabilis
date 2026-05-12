<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../Views/front/users/partials/auth.php';
require_once __DIR__ . '/../../../Controllers/AIGeneratorC.php';
require_once __DIR__ . '/../../../Controllers/EntrainementC.php';
require_once __DIR__ . '/../../../Controllers/ProgrammeC.php';
require_once __DIR__ . '/../../../Controllers/SeanceC.php';
require_once __DIR__ . '/../../../Controllers/UtilisateurC.php';
require_once __DIR__ . '/../../../Models/Entrainement.php';
require_once __DIR__ . '/../../../Models/Seance.php';

$cartCount = array_sum($_SESSION['cart'] ?? []);
$isLoggedIn = frontofficeIsLoggedIn();
$uid = $isLoggedIn ? (int)$_SESSION['front_user_id'] : 0;

$entrainementC = new EntrainementC();
$programmeC = new ProgrammeC();
$seanceC = new SeanceC();
$utilisateurC = new UtilisateurC();
$user = $uid > 0 ? $utilisateurC->getById($uid) : null;
$weight = $user && $user->getPoids() ? (float)$user->getPoids() : 70.0;

$flash = $_SESSION['entrainement_flash'] ?? null;
unset($_SESSION['entrainement_flash']);
$generatedWorkout = $_SESSION['generated_workout'] ?? null;

function entrainement_flash(string $type, string $message): void
{
    $_SESSION['entrainement_flash'] = ['type' => $type, 'message' => $message];
}

function trainingGoalLabel(string $goal): string
{
    return [
        'perte_graisse' => 'Perte de graisse',
        'prise_muscle' => 'Prise de muscle',
        'endurance' => 'Endurance',
    ][$goal] ?? ucfirst(str_replace('_', ' ', $goal));
}

function trainingLevelLabel(string $level): string
{
    return [
        'debutant' => 'Debutant',
        'intermediaire' => 'Intermediaire',
        'avance' => 'Avance',
    ][$level] ?? ucfirst($level);
}

function trainingWorkoutName(array $workout): string
{
    $goal = trainingGoalLabel((string)($workout['goal'] ?? 'entrainement'));
    $level = trainingLevelLabel((string)($workout['niveau'] ?? 'intermediaire'));
    return $goal . ' ' . $level;
}

function trainingBmiLabel(float $bmi): string
{
    if ($bmi < 18.5) return 'Maigreur';
    if ($bmi < 25) return 'Normal';
    if ($bmi < 30) return 'Surpoids';
    return 'Obesite';
}

function trainingSaveGeneratedWorkout(array $workout, int $uid, string $prompt): int
{
    $entrainementC = new EntrainementC();
    $programmeC = new ProgrammeC();
    $exercises = $workout['exercises'] ?? [];
    $avgMet = 5.0;
    if ($exercises) {
        $avgMet = array_sum(array_map(fn($ex) => (float)($ex['met_value'] ?? 5.0), $exercises)) / count($exercises);
    }

    $entrainement = new Entrainement(
        null,
        trainingWorkoutName($workout),
        trim("Seance generee par IA Stabilis.\n" . $prompt),
        trainingGoalLabel((string)($workout['goal'] ?? 'entrainement')),
        (string)($workout['niveau'] ?? 'intermediaire'),
        round($avgMet, 1),
        1,
        $uid
    );

    $newId = $entrainementC->insert($entrainement);
    $steps = [];
    foreach ($exercises as $index => $exercise) {
        $sets = $exercise['sets'] ?? '-';
        $reps = $exercise['reps'] ?? '-';
        $rest = $exercise['rest_sec'] ?? '-';
        $calories = round((float)($exercise['calories'] ?? 0), 1);
        $steps[] = [
            'titre' => $exercise['name'] ?? ('Exercice ' . ($index + 1)),
            'description' => trim(($exercise['description'] ?? '') . "\nSeries: {$sets} x {$reps}. Repos: {$rest}s. Calories: {$calories} kcal."),
        ];
    }
    $entrainementC->insertEtapes($newId, $steps);
    $programmeC->add($uid, $newId);

    return $newId;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        if (!$isLoggedIn || !$user) {
            entrainement_flash('error', 'Connectez-vous pour mettre a jour votre profil sportif.');
            header('Location: /AdminLTE3/Views/front/users/login.php');
            exit;
        }

        $errors = $utilisateurC->validateProfile($_POST, $uid);
        if ($errors) {
            entrainement_flash('error', implode(' ', $errors));
        } else {
            $user->setNom(trim($_POST['nom']));
            $user->setEmail(trim($_POST['email']));
            $user->setPoids($_POST['poids'] !== '' ? (float)$_POST['poids'] : null);
            $user->setTaille($_POST['taille'] !== '' ? (int)$_POST['taille'] : null);
            $user->setAge($_POST['age'] !== '' ? (int)$_POST['age'] : null);
            $user->setSexe(in_array($_POST['sexe'] ?? '', ['H', 'F'], true) ? $_POST['sexe'] : 'H');
            $utilisateurC->update($user);
            $_SESSION['front_user_nom'] = $user->getNom();
            $_SESSION['front_user_email'] = $user->getEmail();
            entrainement_flash('success', 'Profil sportif mis a jour.');
        }
        header('Location: index.php#profil');
        exit;
    }

    if ($action === 'generate') {
        $goal = $_POST['goal'] ?? 'prise_muscle';
        $niveau = $_POST['niveau'] ?? 'intermediaire';
        $prompt = trim($_POST['prompt'] ?? '');
        $ai = new AIGeneratorC($weight);
        $result = $ai->genererAvecPrompt($goal, $niveau, $prompt);
        if (isset($result['error'])) {
            entrainement_flash('error', $result['error']);
        } else {
            $_SESSION['generated_workout'] = $result;
            $_SESSION['generated_prompt'] = $prompt;
            entrainement_flash('success', $result['warning'] ?? 'Seance IA generee. Vous pouvez la sauvegarder dans votre programme.');
        }
        header('Location: index.php#generateur');
        exit;
    }

    if (!$isLoggedIn) {
        entrainement_flash('error', 'Connectez-vous pour modifier votre programme.');
        header('Location: /AdminLTE3/Views/front/users/login.php');
        exit;
    }

    if ($action === 'save_generated' && is_array($generatedWorkout)) {
        trainingSaveGeneratedWorkout($generatedWorkout, $uid, (string)($_SESSION['generated_prompt'] ?? ''));
        unset($_SESSION['generated_workout'], $_SESSION['generated_prompt']);
        entrainement_flash('success', 'Seance ajoutee a votre programme.');
        header('Location: index.php#programme');
        exit;
    }

    if ($action === 'remove' && isset($_POST['entrainement_id'])) {
        $programmeC->remove($uid, (int)$_POST['entrainement_id']);
        entrainement_flash('success', 'Entrainement retire du programme.');
        header('Location: index.php#programme');
        exit;
    }

    if ($action === 'log' && isset($_POST['entrainement_id'])) {
        $entrainement = $entrainementC->getById((int)$_POST['entrainement_id']);
        $errors = SeanceC::validate($_POST);
        if ($entrainement && !$errors) {
            $seance = new Seance(
                null,
                $uid,
                $entrainement->getId(),
                (int)$_POST['duree_minutes'],
                0,
                $_POST['intensite'] ?? 'moderee',
                $_POST['fc_moyenne'] !== '' ? (int)$_POST['fc_moyenne'] : null,
                trim($_POST['notes'] ?? '')
            );
            $seanceC->enregistrer($seance, (float)$entrainement->getMetValue(), $weight);
            entrainement_flash('success', 'Seance terminee enregistree.');
        } else {
            entrainement_flash('error', implode(' ', $errors ?: ['Entrainement introuvable.']));
        }
        header('Location: index.php#programme');
        exit;
    }
}

$programme = $isLoggedIn ? $programmeC->listByUser($uid) : [];
$stats = $isLoggedIn ? $seanceC->statsUser($uid) : ['nb_seances' => 0, 'total_calories' => 0, 'total_minutes' => 0, 'avg_calories' => 0];
$kpis = $isLoggedIn ? $seanceC->kpisUser($uid) : [];
$history = $isLoggedIn ? $seanceC->listByUser($uid, 6) : [];
$recentGenerations = (new AIGeneratorC($weight))->getHistory(4);
$bmi = ($user && $user->getPoids() && $user->getTaille()) ? round($user->getPoids() / pow($user->getTaille() / 100, 2), 1) : null;
$baseMetabolism = null;
if ($user && $user->getPoids() && $user->getTaille() && $user->getAge()) {
    $baseMetabolism = $user->getSexe() === 'F'
        ? round(447.593 + 9.247 * (float)$user->getPoids() + 3.098 * (int)$user->getTaille() - 4.330 * (int)$user->getAge())
        : round(88.362 + 13.397 * (float)$user->getPoids() + 4.799 * (int)$user->getTaille() - 5.677 * (int)$user->getAge());
}

$recommendations = [
    ['goal' => 'perte_graisse', 'niveau' => 'debutant', 'title' => 'Cardio progressif', 'text' => 'Un demarrage clair pour bouger plus sans surcharge.', 'icon' => 'fa-heart-pulse', 'image' => 'https://images.unsplash.com/photo-1648995361141-30676a75fd27?auto=format&fit=crop&q=80&w=1200', 'image_pos' => 'center 44%'],
    ['goal' => 'prise_muscle', 'niveau' => 'intermediaire', 'title' => 'Force fonctionnelle', 'text' => 'Une seance equilibree pour construire de la puissance.', 'icon' => 'fa-dumbbell', 'image' => 'https://images.unsplash.com/photo-1704223524532-c5b4e8490297?auto=format&fit=crop&q=80&w=1200', 'image_pos' => 'center 40%'],
    ['goal' => 'endurance', 'niveau' => 'avance', 'title' => 'Endurance soutenue', 'text' => 'Une structure plus longue pour travailler le souffle.', 'icon' => 'fa-person-running', 'image' => 'https://images.unsplash.com/photo-1758512867312-70bd41431cd6?auto=format&fit=crop&q=80&w=1200', 'image_pos' => 'center 48%'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrainements - Stabilis</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/stabilis.css?v=8">
    <link rel="stylesheet" href="../../../assets/css/front-style.css?v=12">
    <link rel="stylesheet" href="../../../assets/css/front-pages.css?v=7">
    <style>
        body.training-page { background:#f7f8f4; color:#1e2e25; }
        .training-page .container { max-width:1180px; }
        .training-page .navbar { animation: trainingNavIn .5s ease both; }
        .training-hero { min-height:520px; display:flex; align-items:center; color:#fff; background:linear-gradient(90deg,rgba(16,45,34,.94),rgba(16,45,34,.62)), url('https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=1800&h=1100&fit=crop&crop=center'); background-size:cover; background-position:center 42%; border-bottom:1px solid rgba(255,255,255,.12); padding:72px 0 92px; }
        .training-hero h1 { max-width:760px; margin:14px 0; color:#fff; font-size:clamp(36px,5vw,64px); line-height:1.02; font-weight:850; }
        .training-hero p { max-width:650px; color:rgba(255,255,255,.84); font-size:17px; line-height:1.6; }
        .training-kicker,.training-hero h1,.training-hero p,.training-actions { animation: trainingHeroRise .72s cubic-bezier(.22,1,.36,1) both; }
        .training-hero h1 { animation-delay:.07s; }
        .training-hero p { animation-delay:.14s; }
        .training-actions { animation-delay:.21s; }
        .training-kicker { display:inline-flex; align-items:center; gap:9px; padding:8px 12px; border-radius:999px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.2); font-size:13px; font-weight:850; text-transform:uppercase; letter-spacing:.35px; }
        .training-actions { display:flex; flex-wrap:wrap; gap:12px; margin-top:30px; }
        .training-btn { border:0; border-radius:8px; min-height:44px; padding:0 16px; background:#C6A15B; color:#17382b; font-weight:850; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:8px; cursor:pointer; transition:transform .18s ease, box-shadow .18s ease, background .18s ease; }
        .training-btn.dark { background:#1A4D3A; color:#fff; }
        .training-btn.light { background:#fff; color:#1A4D3A; border:1px solid #dfe8df; }
        .training-btn:hover { transform:translateY(-1px); box-shadow:0 10px 20px rgba(26,77,58,.14); }
        .training-section { padding:58px 0; }
        .training-section.alt { background:#eef4ee; border-top:1px solid #dfe8df; border-bottom:1px solid #dfe8df; }
        .training-head { display:flex; justify-content:space-between; gap:18px; align-items:end; flex-wrap:wrap; margin-bottom:22px; }
        .training-head h2 { margin:0; color:#163f30; font-size:clamp(28px,3vw,38px); font-weight:850; }
        .training-head p { max-width:650px; color:#657166; line-height:1.55; margin:8px 0 0; }
        .training-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:18px; margin-top:28px; margin-bottom:16px; position:relative; z-index:2; }
        .training-stat { background:#fff; border:1px solid #dfe8df; border-left:4px solid #1A4D3A; border-radius:8px; padding:18px; box-shadow:0 12px 28px rgba(26,77,58,.08); animation: trainingCardIn .58s cubic-bezier(.22,1,.36,1) both; transition:transform .18s ease, box-shadow .18s ease; }
        .training-stat:hover { transform:translateY(-3px); box-shadow:0 18px 34px rgba(26,77,58,.12); }
        .training-stat:nth-child(2) { border-left-color:#C6A15B; }
        .training-stat:nth-child(3) { border-left-color:#3A6B8F; }
        .training-stat:nth-child(4) { border-left-color:#5a8f68; }
        .training-stat:nth-child(2) { animation-delay:.05s; }
        .training-stat:nth-child(3) { animation-delay:.1s; }
        .training-stat:nth-child(4) { animation-delay:.15s; }
        .training-stat strong { display:block; color:#163f30; font-size:28px; line-height:1; }
        .training-stat span { display:block; margin-top:8px; color:#6d796f; font-size:12px; font-weight:850; text-transform:uppercase; letter-spacing:.35px; }
        .training-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; }
        .training-card, .training-panel { background:#fff; border:1px solid #dfe8df; border-radius:8px; padding:20px; box-shadow:0 12px 28px rgba(26,77,58,.07); animation: trainingCardIn .62s cubic-bezier(.22,1,.36,1) both; transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
        .training-card:hover, .training-panel:hover { transform:translateY(-3px); box-shadow:0 18px 34px rgba(26,77,58,.11); border-color:#cfe0d2; }
        .training-card { position:relative; overflow:hidden; min-height:260px; padding-top:126px; }
        .training-card::before { content:""; position:absolute; inset:0 auto 0 0; width:4px; background:#1A4D3A; }
        .training-card-media { position:absolute; inset:0 0 auto; height:112px; overflow:hidden; }
        .training-card-media img { width:100%; height:100%; object-fit:cover; display:block; filter:saturate(1.05); }
        .training-card-media::after { content:""; position:absolute; inset:0; background:linear-gradient(90deg, rgba(26,77,58,.76), rgba(26,77,58,.08)); }
        .training-card-icon { position:absolute; left:18px; top:78px; z-index:2; width:58px; height:58px; display:inline-flex; align-items:center; justify-content:center; border-radius:18px; background:#fff; color:#1A4D3A; border:1px solid #dfe8df; box-shadow:0 14px 28px rgba(26,77,58,.18); font-size:23px; }
        .training-card:nth-child(2)::before { background:#C6A15B; }
        .training-card:nth-child(3)::before { background:#3A6B8F; }
        .training-card:nth-child(2) .training-card-media::after { background:linear-gradient(90deg, rgba(198,161,91,.82), rgba(198,161,91,.08)); }
        .training-card:nth-child(2) .training-card-icon { color:#946200; }
        .training-card:nth-child(3) .training-card-media::after { background:linear-gradient(90deg, rgba(58,107,143,.82), rgba(58,107,143,.08)); }
        .training-card:nth-child(3) .training-card-icon { color:#24506b; }
        .training-card:nth-child(2), .training-panel:nth-child(2) { animation-delay:.06s; }
        .training-card:nth-child(3), .training-panel:nth-child(3) { animation-delay:.12s; }
        .training-card h3, .training-panel h3 { color:#163f30; margin:0 0 8px; font-size:20px; }
        .training-card p, .training-panel p { color:#657166; line-height:1.55; margin:0 0 16px; }
        .training-form { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .training-form .full { grid-column:1 / -1; }
        .training-form input, .training-form select, .training-form textarea { width:100%; border:1px solid #dfe8df; border-radius:8px; padding:12px; font:inherit; background:#fff; color:#1e2e25; transition:border-color .18s ease, box-shadow .18s ease; }
        .training-form input:focus, .training-form select:focus, .training-form textarea:focus, .mini-log input:focus, .mini-log select:focus, .mini-log textarea:focus { outline:0; border-color:#1A4D3A; box-shadow:0 0 0 3px rgba(26,77,58,.12); }
        .training-result { border:1px solid #d9e8dc; background:#f8fcf9; border-radius:8px; padding:18px; margin-top:18px; animation: trainingResultIn .34s ease both; }
        .exercise-list { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; margin-top:14px; }
        .exercise-item { padding:13px; border-radius:8px; background:#fff; border:1px solid #e5eee6; animation: trainingCardIn .45s ease both; }
        .exercise-item strong { color:#163f30; }
        .exercise-item small { display:block; color:#6b756d; margin-top:5px; }
        .programme-list { display:grid; gap:14px; }
        .programme-item { display:grid; grid-template-columns:1fr minmax(280px,340px); gap:18px; align-items:start; background:#fff; border:1px solid #dfe8df; border-radius:8px; padding:18px; box-shadow:0 12px 28px rgba(26,77,58,.07); animation: trainingCardIn .58s cubic-bezier(.22,1,.36,1) both; transition:transform .18s ease, box-shadow .18s ease; }
        .programme-item:hover { transform:translateY(-3px); box-shadow:0 18px 34px rgba(26,77,58,.11); }
        .programme-item h3 { margin:0 0 8px; color:#163f30; font-size:21px; }
        .programme-meta { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
        .programme-meta span { border-radius:999px; background:#edf6ef; color:#1A4D3A; border:1px solid #d6e8db; padding:7px 10px; font-size:12px; font-weight:850; }
        .programme-meta span:nth-child(2) { background:#fff4df; color:#7b5518; border-color:#efd7a7; }
        .programme-meta span:nth-child(3) { background:#eaf3fb; color:#24506b; border-color:#cfe2ee; }
        .programme-actions { display:grid; gap:12px; min-width:0; padding:16px; border:1px solid #edf1ed; border-radius:8px; background:#fbfdfb; }
        .programme-actions-title { display:flex; align-items:center; gap:8px; margin:0; color:#163f30; font-size:15px; font-weight:900; }
        .programme-actions-help { margin:-5px 0 2px; color:#6b756d; font-size:12px; line-height:1.35; }
        .steps-list { margin-top:14px; display:grid; gap:10px; }
        .steps-list summary { list-style:none; }
        .steps-list summary::-webkit-details-marker { display:none; }
        .step-item { display:grid; grid-template-columns:32px 1fr; gap:10px; padding:12px; border-radius:8px; background:#f8fcf9; border:1px solid #e2ece4; animation: trainingResultIn .28s ease both; }
        .step-item b { width:32px; height:32px; border-radius:8px; background:#1A4D3A; color:#fff; display:flex; align-items:center; justify-content:center; }
        .step-item h4 { margin:0 0 4px; color:#163f30; font-size:15px; }
        .step-item p { margin:0; color:#657166; font-size:14px; line-height:1.45; }
        .profile-grid { display:grid; grid-template-columns:.85fr 1.15fr; gap:16px; }
        .profile-metrics { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
        .profile-metric { background:#f8fcf9; border:1px solid #e2ece4; border-left:4px solid #1A4D3A; border-radius:8px; padding:14px; }
        .profile-metric:nth-child(2) { border-left-color:#C6A15B; }
        .profile-metric:nth-child(3) { border-left-color:#3A6B8F; }
        .profile-metric strong { display:block; color:#163f30; font-size:24px; }
        .profile-metric span { color:#6b756d; font-size:12px; font-weight:850; text-transform:uppercase; }
        .mini-log { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .mini-log .full { grid-column:1 / -1; }
        .mini-field { display:grid; gap:6px; min-width:0; }
        .mini-field label { color:#435247; font-size:12px; line-height:1.25; font-weight:900; }
        .mini-field span { color:#7a857c; font-size:11px; line-height:1.25; }
        .mini-log input, .mini-log select, .mini-log textarea { width:100%; border:1px solid #dfe8df; border-radius:8px; padding:11px 12px; font:inherit; background:#fff; color:#1e2e25; }
        .mini-log textarea { min-height:72px; resize:vertical; }
        .remove-programme-form { margin-top:2px; }
        .remove-programme-form .training-btn { width:100%; }
        .training-alert { margin:18px auto 0; max-width:1180px; border-radius:8px; padding:14px 16px; font-weight:750; }
        .training-alert.success { background:#eaf7ed; color:#1A4D3A; border:1px solid #cae7d0; animation: trainingResultIn .3s ease both; }
        .training-alert.error { background:#fff1f1; color:#9f1d1d; border:1px solid #ffd1d1; animation: trainingResultIn .3s ease both; }
        .history-table { width:100%; border-collapse:collapse; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 12px 28px rgba(26,77,58,.07); }
        .history-table th, .history-table td { padding:14px; border-bottom:1px solid #edf1ed; text-align:left; }
        .history-table th { color:#1A4D3A; background:#f5faf6; font-size:12px; text-transform:uppercase; letter-spacing:.35px; }
        @media (max-width:1050px){ .training-stats,.training-grid,.exercise-list{grid-template-columns:1fr 1fr;} .programme-item,.profile-grid{grid-template-columns:1fr;} }
        @media (max-width:680px){ .training-stats,.training-grid,.training-form,.profile-metrics,.exercise-list,.mini-log{grid-template-columns:1fr;} .training-hero{min-height:600px; padding:64px 0 72px;} }
        @keyframes trainingNavIn { from{opacity:0; transform:translateY(-10px);} to{opacity:1; transform:translateY(0);} }
        @keyframes trainingHeroRise { from{opacity:0; transform:translateY(24px);} to{opacity:1; transform:translateY(0);} }
        @keyframes trainingCardIn { from{opacity:0; transform:translateY(18px) scale(.99);} to{opacity:1; transform:translateY(0) scale(1);} }
        @keyframes trainingResultIn { from{opacity:0; transform:translateY(8px) scale(.99);} to{opacity:1; transform:translateY(0) scale(1);} }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration:.001ms !important; transition-duration:.001ms !important; scroll-behavior:auto !important; }
        }
    </style>
</head>
<body class="training-page">
    <?php $activeFrontPage = 'entrainements'; require __DIR__ . '/../partials/navigation.php'; ?>

    <section class="training-hero">
        <div class="container">
            <span class="training-kicker"><i class="fas fa-dumbbell"></i> Entrainements Stabilis</span>
            <h1>Planifiez, adaptez et suivez vos seances sportives.</h1>
            <p>Generez une seance selon votre objectif, sauvegardez-la dans votre programme, puis suivez votre effort, vos calories et votre progression depuis le meme espace.</p>
            <div class="training-actions">
                <a href="#programme" class="training-btn"><i class="fas fa-list-check"></i> Mon programme</a>
                <a href="#generateur" class="training-btn light"><i class="fas fa-robot"></i> Generer une seance</a>
            </div>
        </div>
    </section>

    <?php if ($flash): ?>
        <div class="training-alert <?php echo htmlspecialchars($flash['type']); ?>"><?php echo htmlspecialchars($flash['message']); ?></div>
    <?php endif; ?>

    <div class="container">
        <div class="training-stats">
            <div class="training-stat"><strong><?php echo (int)$stats['nb_seances']; ?></strong><span>Seances terminees</span></div>
            <div class="training-stat"><strong><?php echo number_format((float)$stats['total_calories'], 0); ?></strong><span>Kcal brulees</span></div>
            <div class="training-stat"><strong><?php echo round(((float)$stats['total_minutes']) / 60, 1); ?>h</strong><span>Temps total</span></div>
            <div class="training-stat"><strong><?php echo count($programme); ?></strong><span>Au programme</span></div>
        </div>
    </div>

    <section class="training-section alt" id="programme">
        <div class="container">
            <div class="training-head"><div><h2>Mon programme</h2><p>Vos seances sauvegardees, avec enregistrement rapide de la duree, intensite et ressenti.</p></div></div>
            <?php if (!$isLoggedIn): ?>
                <div class="training-panel"><h3>Connexion requise</h3><p>Connectez-vous pour sauvegarder et suivre vos entrainements.</p><a class="training-btn" href="/AdminLTE3/Views/front/users/login.php">Connexion</a></div>
            <?php elseif (!$programme): ?>
                <div class="training-panel"><h3>Aucune seance sauvegardee</h3><p>Generez une seance IA puis sauvegardez-la pour construire votre programme.</p></div>
            <?php else: ?>
                <div class="programme-list">
                    <?php foreach ($programme as $item): ?>
                        <article class="programme-item">
                            <div>
                                <h3><?php echo htmlspecialchars($item['nom']); ?></h3>
                                <p><?php echo nl2br(htmlspecialchars($item['description'] ?? '')); ?></p>
                                <div class="programme-meta">
                                    <span><?php echo htmlspecialchars($item['type_sport']); ?></span>
                                    <span><?php echo trainingLevelLabel($item['niveau']); ?></span>
                                    <span>Effort MET <?php echo number_format((float)$item['met_value'], 1); ?></span>
                                    <span><?php echo (int)$item['nb_seances']; ?> terminee(s)</span>
                                </div>
                                <?php $steps = $entrainementC->getEtapes((int)$item['id']); ?>
                                <?php if ($steps): ?>
                                    <details class="steps-list">
                                        <summary class="training-btn light" style="width:max-content;">Voir le tutoriel</summary>
                                        <?php foreach ($steps as $index => $step): ?>
                                            <div class="step-item">
                                                <b><?php echo $index + 1; ?></b>
                                                <div>
                                                    <h4><?php echo htmlspecialchars($step['titre']); ?></h4>
                                                    <p><?php echo nl2br(htmlspecialchars($step['description'])); ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </details>
                                <?php endif; ?>
                            </div>
                            <div class="programme-actions">
                                <h4 class="programme-actions-title"><i class="fas fa-clipboard-check"></i> Enregistrer une seance</h4>
                                <p class="programme-actions-help">Remplissez ce que vous venez de faire. Les calories seront estimees automatiquement.</p>
                                <form class="mini-log" method="POST">
                                    <input type="hidden" name="action" value="log">
                                    <input type="hidden" name="entrainement_id" value="<?php echo (int)$item['id']; ?>">
                                    <div class="mini-field">
                                        <label for="duree_<?php echo (int)$item['id']; ?>">Duree</label>
                                        <input id="duree_<?php echo (int)$item['id']; ?>" type="number" name="duree_minutes" min="1" max="600" value="30" placeholder="Ex: 30">
                                        <span>En minutes</span>
                                    </div>
                                    <div class="mini-field">
                                        <label for="intensite_<?php echo (int)$item['id']; ?>">Intensite</label>
                                        <select id="intensite_<?php echo (int)$item['id']; ?>" name="intensite">
                                            <option value="faible">Faible</option>
                                            <option value="moderee" selected>Moderee</option>
                                            <option value="elevee">Elevee</option>
                                            <option value="maximale">Maximale</option>
                                        </select>
                                        <span>Votre ressenti</span>
                                    </div>
                                    <div class="mini-field full">
                                        <label for="fc_<?php echo (int)$item['id']; ?>">Frequence cardiaque</label>
                                        <input id="fc_<?php echo (int)$item['id']; ?>" type="number" name="fc_moyenne" min="40" max="250" placeholder="Optionnel, ex: 135">
                                        <span>Battements par minute si vous l'avez mesuree</span>
                                    </div>
                                    <div class="mini-field full">
                                        <label for="notes_<?php echo (int)$item['id']; ?>">Notes</label>
                                        <textarea id="notes_<?php echo (int)$item['id']; ?>" name="notes" rows="2" placeholder="Ex: bonne energie, douleur au genou, trop facile..."></textarea>
                                    </div>
                                    <button class="training-btn dark full" type="submit"><i class="fas fa-check"></i> Marquer comme terminee</button>
                                </form>
                                <form class="remove-programme-form" method="POST">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="entrainement_id" value="<?php echo (int)$item['id']; ?>">
                                    <button class="training-btn light" type="submit"><i class="fas fa-minus-circle"></i> Retirer du programme</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="training-section" id="generateur">
        <div class="container">
            <div class="training-head">
                <div><h2>Generateur IA</h2><p>Choisissez un objectif, un niveau et donnez une intention courte. La seance reste sauvegardable dans votre programme Stabilis.</p></div>
            </div>
            <div class="training-grid">
                <?php foreach ($recommendations as $rec): ?>
                    <form class="training-card" method="POST">
                        <div class="training-card-media">
                            <img src="<?php echo htmlspecialchars($rec['image']); ?>" alt="" style="object-position: <?php echo htmlspecialchars($rec['image_pos']); ?>;">
                        </div>
                        <span class="training-card-icon"><i class="fas <?php echo htmlspecialchars($rec['icon']); ?>"></i></span>
                        <h3><?php echo htmlspecialchars($rec['title']); ?></h3>
                        <p><?php echo htmlspecialchars($rec['text']); ?></p>
                        <input type="hidden" name="action" value="generate">
                        <input type="hidden" name="goal" value="<?php echo htmlspecialchars($rec['goal']); ?>">
                        <input type="hidden" name="niveau" value="<?php echo htmlspecialchars($rec['niveau']); ?>">
                        <input type="hidden" name="prompt" value="<?php echo htmlspecialchars($rec['text']); ?>">
                        <button class="training-btn dark" type="submit"><i class="fas fa-wand-magic-sparkles"></i> Generer</button>
                    </form>
                <?php endforeach; ?>
            </div>

            <div class="training-panel" style="margin-top:20px;">
                <h3>Seance personnalisee</h3>
                <form class="training-form" method="POST">
                    <input type="hidden" name="action" value="generate">
                    <select name="goal">
                        <option value="perte_graisse">Perte de graisse</option>
                        <option value="prise_muscle" selected>Prise de muscle</option>
                        <option value="endurance">Endurance</option>
                    </select>
                    <select name="niveau">
                        <option value="debutant">Debutant</option>
                        <option value="intermediaire" selected>Intermediaire</option>
                        <option value="avance">Avance</option>
                    </select>
                    <textarea class="full" name="prompt" rows="3" maxlength="500" placeholder="Ex: seance courte sans materiel, focus abdos et cardio"></textarea>
                    <button class="training-btn full" type="submit"><i class="fas fa-robot"></i> Generer avec Stabilis IA</button>
                </form>

                <?php if (is_array($generatedWorkout)): ?>
                    <div class="training-result">
                        <h3><?php echo htmlspecialchars(trainingWorkoutName($generatedWorkout)); ?></h3>
                        <p><?php echo (int)($generatedWorkout['duree_estimee'] ?? 0); ?> min estimees - <?php echo number_format((float)($generatedWorkout['total_calories'] ?? 0), 0); ?> kcal</p>
                        <div class="exercise-list">
                            <?php foreach (($generatedWorkout['exercises'] ?? []) as $exercise): ?>
                                <div class="exercise-item">
                                    <strong><?php echo htmlspecialchars($exercise['name'] ?? 'Exercice'); ?></strong>
                                    <small><?php echo htmlspecialchars($exercise['description'] ?? ''); ?></small>
                                    <small><?php echo htmlspecialchars(($exercise['sets'] ?? '-') . ' x ' . ($exercise['reps'] ?? '-') . ' - repos ' . ($exercise['rest_sec'] ?? '-') . 's'); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <form method="POST" style="margin-top:14px;">
                            <input type="hidden" name="action" value="save_generated">
                            <button class="training-btn dark" type="submit"><i class="fas fa-save"></i> Sauvegarder dans mon programme</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="training-section" id="profil">
        <div class="container">
            <div class="training-head"><div><h2>Profil sportif</h2><p>Ces donnees alimentent les estimations de calories et rendent les seances plus justes pour votre corps.</p></div></div>
            <?php if (!$isLoggedIn || !$user): ?>
                <div class="training-panel"><h3>Connexion requise</h3><p>Connectez-vous pour completer votre profil sportif.</p><a class="training-btn" href="/AdminLTE3/Views/front/users/login.php">Connexion</a></div>
            <?php else: ?>
                <div class="profile-grid">
                    <div class="training-panel">
                        <h3><?php echo htmlspecialchars($user->getNom()); ?></h3>
                        <div class="profile-metrics">
                            <div class="profile-metric"><strong><?php echo $bmi ? htmlspecialchars((string)$bmi) : '-'; ?></strong><span>IMC <?php echo $bmi ? trainingBmiLabel($bmi) : ''; ?></span></div>
                            <div class="profile-metric"><strong><?php echo $baseMetabolism ? number_format((float)$baseMetabolism, 0) : '-'; ?></strong><span>Kcal / jour</span></div>
                            <div class="profile-metric"><strong><?php echo number_format((float)($stats['avg_calories'] ?? 0), 0); ?></strong><span>Kcal / seance</span></div>
                            <div class="profile-metric"><strong><?php echo htmlspecialchars($kpis['progression'] ?? '-'); ?></strong><span>Progression</span></div>
                        </div>
                    </div>
                    <div class="training-panel">
                        <h3>Mesure et objectif</h3>
                        <form class="training-form" method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            <input name="nom" value="<?php echo htmlspecialchars($user->getNom()); ?>" placeholder="Nom">
                            <input name="email" value="<?php echo htmlspecialchars($user->getEmail()); ?>" placeholder="Email">
                            <input type="number" step="0.1" min="30" max="300" name="poids" value="<?php echo htmlspecialchars((string)$user->getPoids()); ?>" placeholder="Poids kg">
                            <input type="number" min="100" max="250" name="taille" value="<?php echo htmlspecialchars((string)$user->getTaille()); ?>" placeholder="Taille cm">
                            <input type="number" min="10" max="100" name="age" value="<?php echo htmlspecialchars((string)$user->getAge()); ?>" placeholder="Age">
                            <select name="sexe">
                                <option value="H" <?php echo $user->getSexe() === 'H' ? 'selected' : ''; ?>>Homme</option>
                                <option value="F" <?php echo $user->getSexe() === 'F' ? 'selected' : ''; ?>>Femme</option>
                            </select>
                            <button class="training-btn full" type="submit"><i class="fas fa-save"></i> Mettre a jour</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="training-section" id="historique">
        <div class="container">
            <div class="training-head"><div><h2>Historique</h2><p>Les dernieres seances completees et les generations IA recentes dans Stabilis.</p></div></div>
            <div class="training-grid">
                <div class="training-panel" style="grid-column:span 2;">
                    <h3>Seances terminees</h3>
                    <table class="history-table">
                        <thead><tr><th>Seance</th><th>Duree</th><th>Kcal</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($history as $row): ?>
                                <tr><td><?php echo htmlspecialchars($row['entrainement_nom']); ?></td><td><?php echo (int)$row['duree_minutes']; ?> min</td><td><?php echo number_format((float)$row['calories'], 0); ?></td><td><?php echo date('d/m/Y H:i', strtotime($row['completed_at'])); ?></td></tr>
                            <?php endforeach; ?>
                            <?php if (!$history): ?><tr><td colspan="4">Aucune seance terminee pour le moment.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="training-panel">
                    <h3>IA recente</h3>
                    <?php foreach ($recentGenerations as $gen): ?>
                        <p><strong><?php echo trainingGoalLabel($gen['goal']); ?></strong><br><?php echo trainingLevelLabel($gen['niveau']); ?> - <?php echo number_format((float)$gen['total_calories'], 0); ?> kcal</p>
                    <?php endforeach; ?>
                    <?php if (!$recentGenerations): ?><p>Aucune generation IA pour le moment.</p><?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</body>
</html>


