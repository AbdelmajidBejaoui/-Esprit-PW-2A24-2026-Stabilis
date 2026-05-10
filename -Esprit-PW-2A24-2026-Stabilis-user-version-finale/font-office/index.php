<?php
$page_title = "Accueil";
include 'config.php';
include 'header.php';

// Récupérer les 6 derniers défis
$sql = "SELECT * FROM defis ORDER BY id DESC LIMIT 6";
$result = mysqli_query($conn, $sql);
$defis = mysqli_fetch_all($result, MYSQLI_ASSOC);
$totalDefis = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM defis"))['total'] ?? 0);
$activeUsers = 0;
$userTableExists = mysqli_query($conn, "SHOW TABLES LIKE 'user'");
if ($userTableExists && mysqli_num_rows($userTableExists) > 0) {
    $activeUsers = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM user WHERE statut_compte = 1"))['total'] ?? 0);
}
$co2EstimateKg = $totalDefis * 2.3;

function formatRewardLabel(string $reward): string
{
    $reward = trim($reward);
    if ($reward === '') {
        return '0 points';
    }

    return preg_match('/\bpoints?\b/i', $reward) ? $reward : $reward . ' points';
}
?>

<!-- Hero Section -->
<section class="hero-section position-relative overflow-hidden">
    <div class="hero-bg"></div>
    <div class="container position-relative">
        <div class="row align-items-center min-vh-100">
            <div class="col-lg-6 text-white">
                <div class="hero-content">
                    <span class="badge badge-pill badge-light mb-3 px-3 py-2">Nutrition durable</span>
                    <h1 class="display-1 font-weight-bold mb-4">
                        Des défis pour<br>
                        <span class="text-accent">mieux manger</span>
                    </h1>
                    <p class="lead mb-4 fs-5">
                        Transformez votre alimentation en aventure durable.
                        Atteignez vos objectifs tout en préservant la planète.
                    </p>
                    <div class="hero-buttons d-flex flex-wrap gap-3">
                        <a href="challenges.php" class="btn btn-primary btn-lg px-5 py-3">
                            <i class="fas fa-play me-2"></i>Découvrir les défis
                        </a>
                        <a href="../View/FrontOffice/listUsers.php" class="btn btn-light btn-lg px-5 py-3">
                            <i class="fas fa-user me-2"></i>Espace utilisateur
                        </a>
                        <a href="weekly-recap.html" class="btn btn-light btn-lg px-5 py-3">
                            <i class="fas fa-trophy me-2"></i>Voir le classement
                        </a>
                        <a href="about.php" class="btn btn-outline-light btn-lg px-5 py-3">
                            En savoir plus
                        </a>
                    </div>
                    <div class="hero-stats mt-5">
                        <div class="row g-4">
                            <div class="col-4">
                                <div class="stat-item">
                                    <h3 class="text-white mb-1"><?php echo $activeUsers; ?></h3>
                                    <small class="text-white-50">Utilisateurs actifs</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <h3 class="text-white mb-1"><?php echo number_format($co2EstimateKg, 1); ?>kg</h3>
                                    <small class="text-white-50">CO2 estimé</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <h3 class="text-white mb-1"><?php echo $totalDefis; ?></h3>
                                    <small class="text-white-50">Défis disponibles</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image">
                    <div class="floating-card card-1">
                        <div class="card bg-white shadow-lg border-0 p-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar bg-success rounded-circle me-3">
                                    <i class="fas fa-leaf text-white"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Protéines végétales</h6>
                                    <small class="text-muted">Défi complété !</small>
                                </div>
                                <div class="ms-auto">
                                    <span class="badge bg-success">+100 pts</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="floating-card card-2">
                        <div class="card bg-white shadow-lg border-0 p-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar bg-primary rounded-circle me-3">
                                    <i class="fas fa-trophy text-white"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Sport durable</h6>
                                    <small class="text-muted">En cours</small>
                                </div>
                                <div class="ms-auto">
                                    <span class="badge bg-warning">75%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hero-main-image">
                        <img src="https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=800&fit=crop&crop=center" alt="Nutrition saine" class="img-fluid rounded-3 shadow-lg">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="hero-shape">
        <svg viewBox="0 0 100 100" preserveAspectRatio="none">
            <polygon points="0,0 100,0 100,100" fill="rgba(255,255,255,0.1)"/>
        </svg>
    </div>
</section>

<!-- Services Section -->
<section class="services-section py-6">
    <div class="container">
        <div class="row justify-content-center text-center mb-5">
            <div class="col-lg-8">
                <h2 class="display-4 fw-bold mb-4">
                    Une approche <span class="text-primary">simple et efficace</span>
                </h2>
                <p class="lead text-muted">
                    Nutrition durable, performance mesurable, impact positif
                </p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="service-card card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="service-icon mb-4">
                            <div class="icon-circle bg-success-soft">
                                <i class="fas fa-leaf fa-2x text-success"></i>
                            </div>
                        </div>
                        <h5 class="card-title fw-bold mb-3">Nutrition durable</h5>
                        <p class="card-text text-muted small">
                            Produits locaux et de saison
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="service-card card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="service-icon mb-4">
                            <div class="icon-circle bg-warning-soft">
                                <i class="fas fa-fire fa-2x text-warning"></i>
                            </div>
                        </div>
                        <h5 class="card-title fw-bold mb-3">Performance</h5>
                        <p class="card-text text-muted small">
                            Objectifs personnalisés et suivi
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="service-card card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="service-icon mb-4">
                            <div class="icon-circle bg-info-soft">
                                <i class="fas fa-globe fa-2x text-info"></i>
                            </div>
                        </div>
                        <h5 class="card-title fw-bold mb-3">Engagement</h5>
                        <p class="card-text text-muted small">
                            Réduisez votre empreinte carbone
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="service-card card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="service-icon mb-4">
                            <div class="icon-circle bg-primary-soft">
                                <i class="fas fa-trophy fa-2x text-primary"></i>
                            </div>
                        </div>
                        <h5 class="card-title fw-bold mb-3">Récompenses</h5>
                        <p class="card-text text-muted small">
                            Gagnez des points et débloquez des défis
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-6 bg-white">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <span class="badge badge-success mb-3">Espace utilisateur</span>
                <h2 class="display-4 fw-bold mb-4">Votre compte et vos defis dans le meme front office.</h2>
                <p class="lead text-muted">
                    Les fonctionnalites utilisateur restent intactes: inscription, connexion, profil, Face ID,
                    verification email, 2FA et recuperation de mot de passe.
                </p>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap gap-3">
                            <a href="../View/FrontOffice/login.php" class="btn btn-primary">
                                <i class="fas fa-right-to-bracket me-2"></i>Connexion
                            </a>
                            <a href="../View/FrontOffice/addUser.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus me-2"></i>Inscription
                            </a>
                            <a href="../View/FrontOffice/listUsers.php" class="btn btn-outline-secondary">
                                <i class="fas fa-id-card me-2"></i>Profil
                            </a>
                            <a href="my-challenges.php" class="btn btn-outline-secondary">
                                <i class="fas fa-list-check me-2"></i>Mes defis
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Challenges Section -->
<section class="challenges-section py-6 bg-light">
    <div class="container">
        <div class="row justify-content-center text-center mb-5">
            <div class="col-lg-8">
                <h2 class="display-4 fw-bold mb-4">
                    Relevez le <span class="text-success">défi</span>
                </h2>
                <p class="lead text-muted">
                    <?php echo count($defis); ?> défis sélectionnés pour débuter votre transformation
                </p>
            </div>
        </div>

        <div class="row g-4">
            <?php if (!empty($defis)): ?>
                <?php foreach ($defis as $index => $defi): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="challenge-card card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="badge badge-<?php echo $defi['type'] == 'aliment' ? 'success' : ($defi['type'] == 'entrainement' ? 'warning' : 'info'); ?> badge-pill px-3 py-2">
                                        <?php echo htmlspecialchars(ucfirst($defi['type'])); ?>
                                    </span>
                                    <div class="card-number">#<?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?></div>
                                </div>

                                <h5 class="card-title fw-bold mb-2">
                                    <?php echo htmlspecialchars($defi['nom']); ?>
                                </h5>

                                <p class="card-text text-muted small mb-3">
                                    <?php echo htmlspecialchars(substr($defi['objectif'], 0, 80)) . '...'; ?>
                                </p>

                                <div class="challenge-reward mb-4 mt-auto">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-coins text-warning small"></i>
                                        <span class="text-muted small fw-bold"><?php echo htmlspecialchars(formatRewardLabel($defi['recompense'])); ?></span>
                                    </div>
                                </div>

                                <a href="challenge-detail.php?id=<?php echo $defi['id']; ?>" class="btn btn-sm btn-outline-primary w-100">
                                    Découvrir
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <div class="empty-state">
                        <i class="fas fa-inbox fa-4x text-muted mb-4"></i>
                        <h4 class="text-muted">Aucun défi disponible</h4>
                        <p class="text-muted">Revenez bientôt pour découvrir de nouveaux défis !</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-5">
            <a href="challenges.php" class="btn btn-primary btn-lg px-5 py-3">
                Voir tous les défis
                <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section py-6">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h2 class="display-4 fw-bold text-white mb-4">
                    Prêt à commencer ?
                </h2>
                <p class="lead text-white-50 mb-5">
                    Rejoignez notre communauté et transformez votre nutrition en 30 jours
                </p>
                <div class="cta-buttons">
                    <a href="challenges.php" class="btn btn-light btn-lg px-5 py-3 me-3">
                        <i class="fas fa-play me-2"></i>Commencer
                    </a>
                    <a href="weekly-recap.html" class="btn btn-outline-light btn-lg px-5 py-3 me-3">
                        <i class="fas fa-ranking-star me-2"></i>Classement
                    </a>
                    <a href="contact.php" class="btn btn-outline-light btn-lg px-5 py-3">
                        <i class="fas fa-question-circle me-2"></i>Infos
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
