<?php
session_start();
require_once __DIR__ . '/../users/partials/auth.php';
require_once __DIR__ . '/../../../Controllers/ParticipationController.php';

frontofficeRequireLogin();
$controller = new ParticipationController();
$errors = [];
$success = '';
if (isset($_GET['started'])) {
    $success = 'Defi demarre. Vous pouvez envoyer une preuve depuis cette page.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$ok, $message] = $controller->saveProof((int)($_POST['participation_id'] ?? 0), (int)$_SESSION['front_user_id'], $_FILES['proof_file'] ?? []);
    if ($ok) {
        $success = $message;
    } else {
        $errors[] = $message;
    }
}
$participations = $controller->getByUserId((int)$_SESSION['front_user_id']);
$proofsByParticipation = [];
foreach ($participations as $participation) {
    $proofsByParticipation[(int)$participation['id']] = $controller->getProofs((int)$participation['id']);
}

$totalParticipations = count($participations);
$completedCount = count(array_filter($participations, fn($p) => $p['statut'] === 'completed'));
$pendingProofCount = array_sum(array_map(fn($p) => (int)$p['pending_proof_count'], $participations));
$averageProgress = $totalParticipations > 0 ? (int)round(array_sum(array_map(fn($p) => (int)$p['progression'], $participations)) / $totalParticipations) : 0;
$cartCount = array_sum($_SESSION['cart'] ?? []);

$statusLabels = [
    'in_progress' => 'En cours',
    'completed' => 'Termine',
    'failed' => 'Echoue',
];
$proofLabels = [
    'pending' => 'En attente',
    'approved' => 'Approuvee',
    'rejected' => 'Rejetee',
];
$icons = ['aliment' => 'fa-apple-alt', 'entrainement' => 'fa-dumbbell', 'compensation' => 'fa-leaf'];
$typeLabels = ['aliment' => 'Nutrition', 'entrainement' => 'Entrainement', 'compensation' => 'Compensation'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mes defis - Stabilis</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/stabilis.css?v=8">
    <link rel="stylesheet" href="../../../assets/css/front-style.css?v=12">
    <link rel="stylesheet" href="../../../assets/css/front-pages.css?v=7">
    <style>
        body.my-defis-page {
            background: #fbfbf7;
        }

        .my-defis-hero {
            position: relative;
            min-height: 360px;
            display: flex;
            align-items: end;
            color: #fff;
            background:
                linear-gradient(90deg, rgba(18, 56, 38, 0.9), rgba(18, 56, 38, 0.52)),
                url('https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=1600&h=900&fit=crop&crop=center');
            background-size: cover;
            background-position: center;
        }

        .my-defis-hero .container {
            padding-top: 72px;
            padding-bottom: 62px;
        }

        .my-defis-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-weight: 800;
            font-size: 13px;
        }

        .my-defis-hero h1 {
            max-width: 760px;
            margin: 18px 0 12px;
            color: #fff;
            font-size: clamp(38px, 6vw, 66px);
            line-height: 1;
            font-weight: 850;
        }

        .my-defis-hero p {
            max-width: 680px;
            margin: 0;
            color: rgba(255,255,255,0.82);
            font-size: 18px;
        }

        .my-defis-section {
            padding: 0 0 76px;
        }

        .my-defis-summary {
            position: relative;
            z-index: 2;
            margin-top: -48px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 26px;
        }

        .summary-tile {
            background: #fff;
            border: 1px solid rgba(26, 77, 58, 0.08);
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 16px 34px rgba(28, 54, 38, 0.11);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .summary-tile:hover {
            transform: translateY(-3px);
            box-shadow: 0 22px 40px rgba(28, 54, 38, 0.15);
        }

        .summary-tile i {
            color: var(--accent-herb);
            margin-bottom: 10px;
            font-size: 20px;
        }

        .summary-tile strong {
            display: block;
            color: var(--accent-herb-dark);
            font-size: 30px;
            line-height: 1;
        }

        .summary-tile span {
            display: block;
            margin-top: 7px;
            color: var(--text-muted);
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 850;
            letter-spacing: .35px;
        }

        .my-defis-alert {
            background: #fff;
            border: 1px solid #edf1eb;
            border-left: 4px solid var(--accent-herb);
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 18px;
            box-shadow: 0 10px 24px rgba(28, 54, 38, 0.07);
        }

        .my-defis-alert.is-error {
            border-left-color: #B94A48;
        }

        .my-defis-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
        }

        .my-defi-card {
            background: #fff;
            border: 1px solid #edf1eb;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 16px 34px rgba(35, 56, 41, 0.10);
        }

        .my-defi-card-top {
            min-height: 112px;
            padding: 20px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            color: #fff;
            background:
                linear-gradient(135deg, rgba(26, 77, 58, .92), rgba(58, 107, 75, .72)),
                url('https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=900&h=420&fit=crop&crop=center');
            background-size: cover;
            background-position: center;
        }

        .my-defi-card.entrainement .my-defi-card-top {
            background:
                linear-gradient(135deg, rgba(36, 71, 52, .88), rgba(198, 161, 91, .58)),
                url('https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=900&h=420&fit=crop&crop=center');
            background-size: cover;
            background-position: center;
        }

        .my-defi-card.compensation .my-defi-card-top {
            background:
                linear-gradient(135deg, rgba(24, 88, 61, .88), rgba(62, 126, 90, .55)),
                url('https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=900&h=420&fit=crop&crop=center');
            background-size: cover;
            background-position: center;
        }

        .my-defi-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: rgba(255,255,255,.92);
            color: var(--accent-herb);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 11px;
            border-radius: 999px;
            background: rgba(255,255,255,.92);
            color: var(--accent-herb-dark);
            font-size: 12px;
            font-weight: 900;
        }

        .status-badge.completed {
            color: #27613c;
        }

        .status-badge.failed {
            color: #a83f3c;
        }

        .my-defi-body {
            padding: 22px;
        }

        .my-defi-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .type-chip {
            display: inline-flex;
            padding: 6px 10px;
            border-radius: 999px;
            background: var(--accent-herb-light);
            color: var(--accent-herb-dark);
            font-weight: 850;
            font-size: 12px;
        }

        .participation-id {
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 800;
        }

        .my-defi-body h2 {
            font-size: 22px;
            line-height: 1.2;
            margin: 0 0 10px;
        }

        .my-defi-objective {
            color: #5f675f;
            font-size: 14px;
            line-height: 1.55;
            min-height: 62px;
            margin-bottom: 20px;
        }

        .progress-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 850;
        }

        .progress-track {
            height: 11px;
            border-radius: 999px;
            background: #edf1ed;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--accent-herb), #67a875);
        }

        .proof-panel {
            margin-top: 22px;
            padding: 16px;
            border: 1px solid #edf1eb;
            border-radius: 16px;
            background: #fcfdfb;
        }

        .proof-panel h3,
        .proof-history h3 {
            margin: 0 0 12px;
            font-size: 14px;
            color: var(--text-primary);
        }

        .proof-upload-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
            align-items: center;
        }

        .proof-file-input {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }

        .proof-file-label {
            min-width: 0;
            width: 100%;
            min-height: 42px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 12px;
            border: 1px solid #dfe7df;
            border-radius: 10px;
            background: #fff;
            cursor: pointer;
            color: var(--text-primary);
            font-weight: 800;
        }

        .proof-file-label i {
            color: var(--accent-herb);
        }

        .proof-file-label span {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: var(--text-muted);
            font-weight: 700;
        }

        .proof-upload-row button {
            min-height: 42px;
            border: 0;
            border-radius: 10px;
            padding: 0 14px;
            background: var(--accent-herb);
            color: #fff;
            font-weight: 900;
            cursor: pointer;
        }

        .proof-hint {
            display: block;
            color: var(--text-muted);
            font-size: 12px;
            margin-top: 9px;
        }

        .proof-history {
            margin-top: 18px;
            border-top: 1px solid #edf1eb;
            padding-top: 16px;
        }

        .proof-list {
            display: grid;
            gap: 8px;
        }

        .proof-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border: 1px solid #edf1eb;
            border-radius: 12px;
            background: #fff;
        }

        .proof-item a {
            color: var(--accent-herb);
            text-decoration: none;
            font-weight: 800;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .proof-state {
            flex: 0 0 auto;
            padding: 5px 9px;
            border-radius: 999px;
            background: #fff3cd;
            color: #8a6d1f;
            font-size: 11px;
            font-weight: 900;
        }

        .proof-state.approved {
            background: var(--accent-herb-light);
            color: var(--accent-herb-dark);
        }

        .proof-state.rejected {
            background: #fdecea;
            color: #a83f3c;
        }

        .empty-my-defis {
            padding: 56px 24px;
            text-align: center;
            background: #fff;
            border: 1px dashed #dfe7df;
            border-radius: 20px;
            box-shadow: 0 14px 28px rgba(35, 56, 41, 0.06);
        }

        .empty-my-defis i {
            color: var(--accent-herb);
            font-size: 42px;
            margin-bottom: 16px;
        }

        .empty-my-defis h2 {
            margin-bottom: 10px;
        }

        .empty-my-defis a {
            display: inline-flex;
            margin-top: 16px;
            min-height: 42px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 16px;
            border-radius: 999px;
            background: var(--accent-herb);
            color: #fff;
            text-decoration: none;
            font-weight: 900;
        }

        @media (max-width: 980px) {
            .my-defis-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .my-defis-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 620px) {
            .my-defis-summary {
                grid-template-columns: 1fr;
            }
            .proof-upload-row {
                grid-template-columns: 1fr;
            }
            .my-defis-hero {
                min-height: 430px;
            }
        }
    </style>
</head>
<body class="my-defis-page">
<?php $activeFrontPage = 'defis'; require __DIR__ . '/../partials/navigation.php'; ?>
<main>
    <section class="my-defis-hero">
        <div class="container">
            <span class="my-defis-kicker"><i class="fas fa-chart-line"></i> Espace progression</span>
            <h1>Mes defis demarres</h1>
            <p>Suivez vos objectifs, envoyez vos preuves et laissez l'administration valider votre progression.</p>
        </div>
    </section>

    <section class="my-defis-section">
        <div class="container">
            <div class="my-defis-summary">
                <div class="summary-tile"><i class="fas fa-trophy"></i><strong><?php echo $totalParticipations; ?></strong><span>Defis demarres</span></div>
                <div class="summary-tile"><i class="fas fa-check-circle"></i><strong><?php echo $completedCount; ?></strong><span>Termines</span></div>
                <div class="summary-tile"><i class="fas fa-hourglass-half"></i><strong><?php echo $pendingProofCount; ?></strong><span>Preuves en attente</span></div>
                <div class="summary-tile"><i class="fas fa-chart-simple"></i><strong><?php echo $averageProgress; ?>%</strong><span>Progression moyenne</span></div>
            </div>

            <?php if ($success): ?><div class="my-defis-alert"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            <?php if ($errors): ?><div class="my-defis-alert is-error"><ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div><?php endif; ?>

            <?php if (!$participations): ?>
                <div class="empty-my-defis">
                    <i class="fas fa-seedling"></i>
                    <h2>Aucun defi demarre</h2>
                    <p class="text-muted">Choisissez un defi, demarrez-le, puis revenez ici pour envoyer vos preuves.</p>
                    <a href="index.php"><i class="fas fa-arrow-right"></i> Decouvrir les defis</a>
                </div>
            <?php else: ?>
                <div class="my-defis-grid">
                    <?php foreach ($participations as $p): ?>
                    <?php
                        $proofs = $proofsByParticipation[(int)$p['id']] ?? [];
                        $status = $p['statut'] ?? 'in_progress';
                        $type = $p['defi_type'] ?? 'aliment';
                    ?>
                    <article class="my-defi-card <?php echo htmlspecialchars($type); ?>">
                        <div class="my-defi-card-top">
                            <span class="my-defi-icon"><i class="fas <?php echo $icons[$type] ?? 'fa-star'; ?>"></i></span>
                            <span class="status-badge <?php echo htmlspecialchars($status); ?>">
                                <i class="fas <?php echo $status === 'completed' ? 'fa-check' : ($status === 'failed' ? 'fa-times' : 'fa-spinner'); ?>"></i>
                                <?php echo htmlspecialchars($statusLabels[$status] ?? $status); ?>
                            </span>
                        </div>
                        <div class="my-defi-body">
                            <div class="my-defi-meta">
                                <span class="type-chip"><?php echo htmlspecialchars($typeLabels[$type] ?? ucfirst($type)); ?></span>
                                <span class="participation-id">Participation #<?php echo (int)$p['id']; ?></span>
                            </div>
                            <h2><?php echo htmlspecialchars($p['defi_nom'] ?? ('Defi #' . $p['id_defi'])); ?></h2>
                            <p class="my-defi-objective"><?php echo htmlspecialchars($p['defi_objectif'] ?? ''); ?></p>

                            <div class="progress-head">
                                <span>Progression</span>
                                <span><?php echo (int)$p['progression']; ?>%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width: <?php echo max(0, min(100, (int)$p['progression'])); ?>%;"></div>
                            </div>

                            <div class="proof-panel">
                                <h3><i class="fas fa-upload"></i> Ajouter une preuve</h3>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="participation_id" value="<?php echo (int)$p['id']; ?>">
                                    <div class="proof-upload-row">
                                        <label class="proof-file-label">
                                            <input class="proof-file-input" type="file" name="proof_file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime">
                                            <i class="fas fa-paperclip"></i>
                                            <span data-proof-file-label>Ajouter un fichier</span>
                                        </label>
                                        <button type="submit"><i class="fas fa-paper-plane"></i> Envoyer</button>
                                    </div>
                                    <span class="proof-hint">JPG, PNG, WEBP jusqu'a 3 Mo. MP4, WEBM, MOV jusqu'a 25 Mo.</span>
                                </form>

                                <div class="proof-history">
                                    <h3><i class="fas fa-folder-open"></i> Preuves envoyees</h3>
                                    <?php if (!$proofs): ?>
                                        <p class="text-muted" style="margin:0;">Aucune preuve envoyee pour le moment.</p>
                                    <?php else: ?>
                                        <div class="proof-list">
                                        <?php foreach ($proofs as $proof): ?>
                                            <div class="proof-item">
                                                <a href="/AdminLTE3/<?php echo htmlspecialchars($proof['file_path']); ?>" target="_blank"><i class="fas fa-file"></i> <?php echo htmlspecialchars(basename($proof['file_path'])); ?></a>
                                                <span class="proof-state <?php echo htmlspecialchars($proof['review_state']); ?>"><?php echo htmlspecialchars($proofLabels[$proof['review_state']] ?? $proof['review_state']); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
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
<script>
document.querySelectorAll('.proof-file-input').forEach(input => {
    input.addEventListener('change', () => {
        const label = input.closest('.proof-file-label')?.querySelector('[data-proof-file-label]');
        if (label) {
            label.textContent = input.files && input.files.length ? input.files[0].name : 'Ajouter un fichier';
        }
    });
});
</script>
<script src="../../../assets/js/front-animations.js"></script>
</body>
</html>
