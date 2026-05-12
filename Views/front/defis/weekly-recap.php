<?php
session_start();
require_once __DIR__ . '/../../../Controllers/ParticipationController.php';

$controller = new ParticipationController();
$leaderboard = $controller->getLeaderboard(12);

function recapAvatar(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return '--';
    }
    $parts = preg_split('/\s+/', $name);
    $first = strtoupper(substr($parts[0] ?? '', 0, 1));
    $second = strtoupper(substr($parts[1] ?? '', 0, 1));
    return $first . ($second !== '' ? $second : '');
}

$ranked = [];
foreach ($leaderboard as $index => $row) {
    $ranked[] = [
        'rank' => $index + 1,
        'name' => (string)$row['nom'],
        'points' => (int)$row['points'],
        'challenges' => (int)$row['participations'],
        'completed' => (int)$row['completed_count'],
        'progress' => (int)round((float)$row['average_progress']),
        'avatar' => recapAvatar((string)$row['nom']),
    ];
}

$topGainer = $ranked[0] ?? ['name' => 'Aucune donnee', 'points' => 0, 'avatar' => '--'];
$mostActive = $ranked;
usort($mostActive, fn($a, $b) => [$b['challenges'], $b['completed'], $b['points']] <=> [$a['challenges'], $a['completed'], $a['points']]);
$mostActive = $mostActive[0] ?? ['name' => 'Aucune donnee', 'challenges' => 0, 'avatar' => '--'];
$top3 = array_slice($ranked, 0, 3);
while (count($top3) < 3) {
    $top3[] = ['rank' => count($top3) + 1, 'name' => 'En attente', 'points' => 0, 'challenges' => 0, 'completed' => 0, 'progress' => 0, 'avatar' => '--'];
}

$weeklyData = [
    'weekNumber' => date('W'),
    'topGainer' => $topGainer,
    'mostActive' => $mostActive,
    'top3' => $top3,
    'fullLeaderboard' => $ranked,
];
$jsonData = json_encode($weeklyData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Classement Defis - Stabilis</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700;14..32,800;14..32,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --green: #129f72;
            --green-dark: #1a4d3a;
            --gold: #ffd166;
            --silver: #c9d3d0;
            --bronze: #c77945;
            --ink: #102018;
        }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            overflow: hidden;
            font-family: Inter, system-ui, sans-serif;
            color: #fff;
            background:
                radial-gradient(circle at 18% 18%, rgba(18, 159, 114, .42), transparent 28%),
                radial-gradient(circle at 82% 76%, rgba(255, 209, 102, .18), transparent 30%),
                linear-gradient(135deg, #102018 0%, #143e31 48%, #07130f 100%);
        }
        .recap-back {
            position: fixed;
            top: 22px;
            left: 22px;
            z-index: 20;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: #fff;
            text-decoration: none;
            padding: 11px 16px;
            border-radius: 999px;
            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.24);
            backdrop-filter: blur(10px);
            font-weight: 800;
        }
        .slideshow-container {
            position: relative;
            width: min(1180px, calc(100vw - 32px));
            height: min(700px, calc(100vh - 56px));
            min-height: 560px;
            border-radius: 24px;
            overflow: hidden;
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.16);
            box-shadow: 0 26px 70px rgba(0,0,0,.42);
            backdrop-filter: blur(14px);
        }
        .particle {
            position: absolute;
            bottom: -10px;
            border-radius: 50%;
            background: rgba(255,255,255,.36);
            animation: floatUp linear infinite;
        }
        @keyframes floatUp {
            to { transform: translateY(-760px) translateX(70px); opacity: 0; }
        }
        .slide {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 20px;
            padding: 48px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(24px) scale(.96);
            transition: opacity .72s ease, transform .72s ease, visibility .72s ease;
        }
        .slide.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }
        .week-badge {
            display: inline-flex;
            padding: 10px 22px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--gold), #f59e0b);
            color: #2a1a05;
            font-weight: 900;
            animation: badgeFloat 2.6s ease-in-out infinite;
        }
        @keyframes badgeFloat {
            50% { transform: translateY(-8px); }
        }
        h1 {
            margin: 0;
            font-size: clamp(44px, 8vw, 86px);
            line-height: .94;
            text-align: center;
            letter-spacing: 0;
            font-weight: 900;
        }
        h2 {
            margin: 0;
            font-size: clamp(30px, 5vw, 54px);
            text-align: center;
            font-weight: 900;
        }
        .subtitle {
            max-width: 660px;
            text-align: center;
            color: rgba(255,255,255,.72);
            font-size: 18px;
            line-height: 1.55;
        }
        .spotlight-card {
            width: min(620px, 100%);
            padding: 42px;
            text-align: center;
            border-radius: 24px;
            background: linear-gradient(135deg, rgba(18,159,114,.24), rgba(255,255,255,.08));
            border: 1px solid rgba(255,255,255,.18);
            box-shadow: 0 0 42px rgba(18,159,114,.28);
        }
        .spotlight-card.gold {
            background: linear-gradient(135deg, rgba(255,209,102,.24), rgba(255,255,255,.08));
            box-shadow: 0 0 42px rgba(255,209,102,.20);
        }
        .big-icon {
            font-size: 74px;
            color: var(--gold);
            filter: drop-shadow(0 0 20px rgba(255,209,102,.5));
            animation: pulseTilt 1.8s ease-in-out infinite;
        }
        @keyframes pulseTilt {
            50% { transform: scale(1.14) rotate(8deg); }
        }
        .player-name {
            margin-top: 20px;
            font-size: clamp(34px, 6vw, 58px);
            font-weight: 900;
        }
        .metric {
            color: var(--gold);
            font-size: clamp(24px, 4vw, 36px);
            font-weight: 900;
        }
        .podium {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 32px;
            width: 100%;
            margin-top: 12px;
        }
        .podium-item {
            width: 170px;
            text-align: center;
            animation: podiumRise .9s ease-out both;
        }
        .podium-item:nth-child(1) { animation-delay: .12s; }
        .podium-item:nth-child(2) { animation-delay: .24s; }
        .podium-item:nth-child(3) { animation-delay: .36s; }
        @keyframes podiumRise {
            from { opacity: 0; transform: translateY(80px); }
        }
        .medal {
            font-size: 40px;
            font-weight: 900;
            margin-bottom: 10px;
        }
        .bar {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            margin: 10px auto 0;
            border-radius: 14px 14px 0 0;
            padding-bottom: 12px;
            font-size: 28px;
            font-weight: 900;
        }
        .first { height: 190px; width: 126px; background: linear-gradient(to top, var(--gold), #f59e0b); color: #2a1a05; }
        .second { height: 138px; width: 112px; background: linear-gradient(to top, var(--silver), #87928e); color: #102018; }
        .third { height: 96px; width: 100px; background: linear-gradient(to top, var(--bronze), #8b4b2a); }
        .podium-name { font-weight: 900; line-height: 1.2; min-height: 38px; }
        .podium-points { color: rgba(255,255,255,.72); font-weight: 800; margin-top: 5px; }
        .leaderboard-wrap {
            width: min(920px, 100%);
            max-height: 420px;
            overflow: auto;
            border-radius: 18px;
            border: 1px solid rgba(255,255,255,.14);
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 18px; text-align: left; }
        th {
            background: rgba(255,255,255,.12);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        td { border-top: 1px solid rgba(255,255,255,.10); }
        tbody tr {
            background: rgba(255,255,255,.04);
            opacity: 0;
            transform: translateX(46px);
            animation: rowIn .55s ease-out forwards;
        }
        @keyframes rowIn { to { opacity: 1; transform: translateX(0); } }
        .rank-badge {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,.16);
            font-weight: 900;
        }
        .points-cell { color: var(--gold); font-weight: 900; }
        .controls {
            position: absolute;
            left: 50%;
            bottom: 24px;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
            z-index: 10;
        }
        .control-btn {
            border: 1px solid rgba(255,255,255,.28);
            background: rgba(255,255,255,.14);
            color: #fff;
            border-radius: 999px;
            padding: 11px 18px;
            font-weight: 900;
            cursor: pointer;
        }
        .indicators {
            position: absolute;
            right: 28px;
            top: 28px;
            display: flex;
            gap: 9px;
            z-index: 10;
        }
        .indicator {
            width: 11px;
            height: 11px;
            border-radius: 50%;
            background: rgba(255,255,255,.28);
        }
        .indicator.active { background: var(--gold); }
        .progress-container {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 4px;
            background: rgba(255,255,255,.12);
        }
        .progress-bar {
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, var(--green), var(--gold));
        }
        @media (max-width: 760px) {
            body { overflow: auto; padding: 74px 0 20px; }
            .slideshow-container { min-height: 680px; height: auto; }
            .slide { padding: 74px 20px 96px; }
            .podium { flex-direction: column; align-items: center; }
            .podium-item { width: 100%; }
            .bar { height: 72px !important; width: min(260px, 80%) !important; border-radius: 14px; align-items: center; padding: 0; }
        }
    </style>
</head>
<body>
<a class="recap-back" href="index.php"><i class="fas fa-arrow-left"></i> Retour defis</a>
<div class="slideshow-container">
    <div id="particles"></div>
    <div class="indicators">
        <span class="indicator active"></span>
        <span class="indicator"></span>
        <span class="indicator"></span>
        <span class="indicator"></span>
        <span class="indicator"></span>
    </div>

    <section class="slide active">
        <span class="week-badge">Semaine <span id="week-number"></span></span>
        <h1>Classement Defis</h1>
        <p class="subtitle">Un recap anime des progressions, points et participations de la communaute Stabilis.</p>
    </section>

    <section class="slide">
        <h2>Meilleur score</h2>
        <div class="spotlight-card gold">
            <div class="big-icon"><i class="fas fa-trophy"></i></div>
            <div class="player-name" id="top-gainer-name"></div>
            <div class="metric">+<span id="top-gainer-points"></span> points</div>
        </div>
    </section>

    <section class="slide">
        <h2>Plus actif</h2>
        <div class="spotlight-card">
            <div class="big-icon"><i class="fas fa-bolt"></i></div>
            <div class="player-name" id="most-active-name"></div>
            <div class="metric"><span id="most-active-count"></span> participation(s)</div>
        </div>
    </section>

    <section class="slide">
        <h2>Top 3</h2>
        <div class="podium" id="podium-container"></div>
    </section>

    <section class="slide">
        <h2>Classement complet</h2>
        <div class="leaderboard-wrap">
            <table>
                <thead>
                    <tr><th>Rang</th><th>Utilisateur</th><th>Participations</th><th>Termines</th><th>Progression</th><th>Points</th></tr>
                </thead>
                <tbody id="leaderboard-body"></tbody>
            </table>
        </div>
    </section>

    <div class="controls">
        <button class="control-btn" type="button" id="pause-btn"><i class="fas fa-pause"></i> Pause</button>
        <button class="control-btn" type="button" id="next-btn"><i class="fas fa-forward"></i> Next</button>
    </div>
    <div class="progress-container"><div class="progress-bar" id="progress-bar"></div></div>
</div>

<script>
const weeklyData = <?php echo $jsonData ?: '{}'; ?>;
const slides = Array.from(document.querySelectorAll('.slide'));
const indicators = Array.from(document.querySelectorAll('.indicator'));
let currentSlide = 0;
let paused = false;
let progress = 0;
const slideDuration = 6000;
const tick = 50;

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[char]));
}

function fillData() {
    document.getElementById('week-number').textContent = weeklyData.weekNumber || '';
    document.getElementById('top-gainer-name').textContent = weeklyData.topGainer?.name || 'Aucune donnee';
    document.getElementById('top-gainer-points').textContent = weeklyData.topGainer?.points || 0;
    document.getElementById('most-active-name').textContent = weeklyData.mostActive?.name || 'Aucune donnee';
    document.getElementById('most-active-count').textContent = weeklyData.mostActive?.challenges || 0;
    renderPodium();
    renderLeaderboard();
}

function renderPodium() {
    const top3 = weeklyData.top3 || [];
    const order = [1, 0, 2];
    const medalClasses = ['second', 'first', 'third'];
    const medalLabels = ['2', '1', '3'];
    document.getElementById('podium-container').innerHTML = order.map((sourceIndex, visualIndex) => {
        const player = top3[sourceIndex] || { name: 'En attente', points: 0 };
        return `
            <div class="podium-item">
                <div class="medal">${medalLabels[visualIndex]}</div>
                <div class="podium-name">${escapeHtml(player.name)}</div>
                <div class="podium-points">${Number(player.points || 0)} pts</div>
                <div class="bar ${medalClasses[visualIndex]}">${medalLabels[visualIndex]}</div>
            </div>
        `;
    }).join('');
}

function renderLeaderboard() {
    const rows = weeklyData.fullLeaderboard || [];
    const tbody = document.getElementById('leaderboard-body');
    if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:rgba(255,255,255,.68);">Aucun classement disponible pour le moment</td></tr>';
        return;
    }
    tbody.innerHTML = rows.map((player, index) => `
        <tr style="animation-delay:${index * 0.08}s">
            <td><span class="rank-badge">${Number(player.rank || index + 1)}</span></td>
            <td><strong>${escapeHtml(player.name)}</strong></td>
            <td>${Number(player.challenges || 0)}</td>
            <td>${Number(player.completed || 0)}</td>
            <td>${Number(player.progress || 0)}%</td>
            <td class="points-cell">${Number(player.points || 0)}</td>
        </tr>
    `).join('');
}

function goToSlide(index) {
    slides[currentSlide].classList.remove('active');
    indicators[currentSlide].classList.remove('active');
    currentSlide = (index + slides.length) % slides.length;
    slides[currentSlide].classList.add('active');
    indicators[currentSlide].classList.add('active');
    progress = 0;
    document.getElementById('progress-bar').style.width = '0%';
}

function nextSlide() {
    goToSlide(currentSlide + 1);
}

document.getElementById('next-btn').addEventListener('click', nextSlide);
document.getElementById('pause-btn').addEventListener('click', () => {
    paused = !paused;
    document.getElementById('pause-btn').innerHTML = paused ? '<i class="fas fa-play"></i> Reprendre' : '<i class="fas fa-pause"></i> Pause';
});
indicators.forEach((indicator, index) => indicator.addEventListener('click', () => goToSlide(index)));

function createParticles() {
    const container = document.getElementById('particles');
    for (let i = 0; i < 30; i++) {
        const particle = document.createElement('span');
        particle.className = 'particle';
        const size = 2 + Math.random() * 5;
        particle.style.width = size + 'px';
        particle.style.height = size + 'px';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDuration = (8 + Math.random() * 12) + 's';
        particle.style.animationDelay = Math.random() * 8 + 's';
        container.appendChild(particle);
    }
}

fillData();
createParticles();
setInterval(() => {
    if (paused) return;
    progress += (tick / slideDuration) * 100;
    if (progress >= 100) {
        nextSlide();
        progress = 0;
    }
    document.getElementById('progress-bar').style.width = progress + '%';
}, tick);
</script>
</body>
</html>
