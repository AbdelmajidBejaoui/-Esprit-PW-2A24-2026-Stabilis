<?php
session_start();
require_once __DIR__ . '/../../../Controllers/DefiController.php';
require_once __DIR__ . '/../../../Controllers/ParticipationController.php';

$defiController = new DefiController();
$participationController = new ParticipationController();
$id = (int)($_GET['id'] ?? 0);
$defi = $defiController->getById($id);
if (!$defi) {
    die('Defi introuvable.');
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['front_user_id'])) {
        header('Location: ../users/login.php');
        exit;
    }
    [$ok, $errors] = $participationController->start([
        'id_utilisateur' => (int)$_SESSION['front_user_id'],
        'id_defi' => $id,
        'date_debut' => date('Y-m-d'),
    ]);
    if ($ok) {
        header('Location: mes-defis.php?started=1');
        exit;
    }
}
$cartCount = array_sum($_SESSION['cart'] ?? []);
$icons = ['aliment' => 'fa-apple-alt', 'entrainement' => 'fa-dumbbell', 'compensation' => 'fa-leaf'];
$labels = ['aliment' => 'Nutrition', 'entrainement' => 'Entrainement', 'compensation' => 'Compensation'];
$heroImage = [
    'aliment' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=1400&h=900&fit=crop&crop=center',
    'entrainement' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=1400&h=900&fit=crop&crop=center',
    'compensation' => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=1400&h=900&fit=crop&crop=center',
][$defi['type']] ?? 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=1400&h=900&fit=crop&crop=center';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($defi['nom']); ?> - Stabilis</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/stabilis.css?v=1">
    <link rel="stylesheet" href="../../../assets/css/front-style.css?v=10">
    <link rel="stylesheet" href="../../../assets/css/front-pages.css?v=5">
    <style>
        .defi-detail {
            background: #fbfbf7;
            padding: 48px 0 72px;
        }

        .defi-detail-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.04fr) minmax(320px, 0.96fr);
            gap: 34px;
            align-items: stretch;
        }

        .defi-photo {
            min-height: 520px;
            border-radius: 22px;
            background:
                linear-gradient(180deg, rgba(20, 54, 38, 0.12), rgba(20, 54, 38, 0.42)),
                url('<?php echo $heroImage; ?>');
            background-size: cover;
            background-position: center;
            box-shadow: 0 24px 50px rgba(24, 55, 38, 0.15);
        }

        .defi-info-panel {
            background: #fff;
            border: 1px solid #edf1eb;
            border-radius: 22px;
            padding: 34px;
            box-shadow: 0 18px 36px rgba(35, 56, 41, 0.10);
        }

        .defi-detail-type {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: var(--accent-herb-light);
            color: var(--accent-herb-dark);
            font-size: 13px;
            font-weight: 900;
        }

        .defi-info-panel h1 {
            margin: 20px 0 14px;
            font-size: clamp(32px, 5vw, 54px);
            line-height: 1;
        }

        .defi-objective {
            color: #566257;
            font-size: 17px;
            line-height: 1.65;
            margin-bottom: 24px;
        }

        .defi-meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin: 24px 0;
        }

        .defi-meta {
            padding: 16px;
            border: 1px solid #edf1eb;
            border-radius: 14px;
            background: #fcfdfb;
        }

        .defi-meta i {
            color: var(--accent-earth);
            margin-right: 8px;
        }

        .defi-meta small {
            display: block;
            color: var(--text-muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 6px;
        }

        .defi-meta strong {
            color: var(--text-primary);
        }

        .defi-detail-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .defi-start-btn,
        .defi-back-btn {
            min-height: 46px;
            border: 0;
            border-radius: 999px;
            padding: 0 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            font-weight: 900;
            cursor: pointer;
        }

        .defi-start-btn {
            background: var(--accent-herb);
            color: #fff;
        }

        .defi-back-btn {
            background: #fff;
            color: var(--text-secondary);
            border: 1px solid #dfe7df;
        }

        @media (max-width: 860px) {
            .defi-detail-grid {
                grid-template-columns: 1fr;
            }
            .defi-photo {
                min-height: 320px;
            }
        }
    </style>
</head>
<body>
<?php $activeFrontPage = 'defis'; require __DIR__ . '/../partials/navigation.php'; ?>
<main class="defi-detail">
    <div class="container">
        <div class="defi-detail-grid">
            <div class="defi-photo" aria-label="<?php echo htmlspecialchars($defi['nom']); ?>"></div>
            <section class="defi-info-panel">
                <span class="defi-detail-type"><i class="fas <?php echo $icons[$defi['type']] ?? 'fa-star'; ?>"></i> <?php echo $labels[$defi['type']] ?? htmlspecialchars($defi['type']); ?></span>
                <h1><?php echo htmlspecialchars($defi['nom']); ?></h1>
                <p class="defi-objective"><?php echo htmlspecialchars($defi['objectif']); ?></p>

                <div class="defi-meta-grid">
                    <div class="defi-meta"><small>Recompense</small><strong><i class="fas fa-coins"></i><?php echo htmlspecialchars($defi['recompense']); ?></strong></div>
                    <div class="defi-meta"><small>Preuve</small><strong><i class="fas fa-camera"></i>Image ou video</strong></div>
                    <div class="defi-meta"><small>Progression</small><strong><i class="fas fa-chart-line"></i>0% au depart</strong></div>
                    <div class="defi-meta"><small>Revision</small><strong><i class="fas fa-user-shield"></i>Admin + IA</strong></div>
                </div>

                <?php if ($errors): ?><div class="alert"><ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div><?php endif; ?>

                <form method="POST" class="defi-detail-actions">
                    <button class="defi-start-btn" type="submit"><i class="fas fa-play"></i> Demarrer le defi</button>
                    <a class="defi-back-btn" href="index.php"><i class="fas fa-arrow-left"></i> Retour</a>
                    <?php if (isset($_SESSION['front_user_id'])): ?><a class="defi-back-btn" href="mes-defis.php"><i class="fas fa-list-check"></i> Mes defis</a><?php endif; ?>
                </form>
            </section>
        </div>
    </div>
</main>
<footer class="site-footer">
    <div class="container">
        <span>Stabilis<sup>&trade;</sup> &middot; defis durables</span>
    </div>
</footer>
<script src="../../../assets/js/front-animations.js"></script>
</body>
</html>
