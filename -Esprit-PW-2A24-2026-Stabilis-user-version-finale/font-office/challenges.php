<?php
$page_title = "Défis";
include 'config.php';
include 'header.php';

// Récupérer tous les défis
$sql = "SELECT * FROM defis ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
$defis = mysqli_fetch_all($result, MYSQLI_ASSOC);

function formatRewardLabel(string $reward): string
{
    $reward = trim($reward);
    if ($reward === '') {
        return '0 points';
    }

    return preg_match('/\bpoints?\b/i', $reward) ? $reward : $reward . ' points';
}
?>

<section class="challenges-page py-5">
    <div class="container">
        <!-- Page Header -->
        <div class="challenges-header text-center mb-5" data-aos="fade-up">
            <span class="badge badge-success mb-3">Defis disponibles</span>
            <h1 class="display-4 fw-bold mb-3">Relevez nos défis</h1>
            <p class="lead text-muted">Transformez votre alimentation en aventure. Complétez des défis, gagnez des points et changez le monde, un repas à la fois.</p>
        </div>

        <?php if (!empty($defis)): ?>
            <!-- Challenges Grid -->
            <div class="challenges-grid">
                <?php foreach ($defis as $index => $defi): ?>
                    <div class="challenge-card-wrapper" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                        <div class="challenge-card">
                            <!-- Challenge Card Front -->
                            <div class="challenge-card-front">
                                <div class="challenge-badge">
                                    <span class="challenge-number"><?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?></span>
                                </div>

                                <div class="challenge-icon-wrapper">
                                    <?php
                                    $icons = ['aliment' => 'fa-apple-alt', 'entrainement' => 'fa-dumbbell', 'compensation' => 'fa-leaf'];
                                    $icon = isset($icons[$defi['type']]) ? $icons[$defi['type']] : 'fa-star';
                                    ?>
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>

                                <h3 class="challenge-title"><?php echo htmlspecialchars($defi['nom']); ?></h3>
                                
                                <p class="challenge-desc"><?php echo htmlspecialchars($defi['objectif']); ?></p>

                                <div class="challenge-type">
                                    <?php
                                    $typeColors = ['aliment' => 'success', 'entrainement' => 'warning', 'compensation' => 'info'];
                                    $typeIcon = isset($typeColors[$defi['type']]) ? $typeColors[$defi['type']] : 'secondary';
                                    ?>
                                    <span class="badge-type badge-<?php echo $typeIcon; ?>">
                                        <?php echo htmlspecialchars(ucfirst($defi['type'])); ?>
                                    </span>
                                </div>

                                <div class="challenge-stats mt-4">
                                    <div class="stat">
                                        <i class="fas fa-coins icon-gold"></i>
                                        <span class="reward-value"><?php echo htmlspecialchars(formatRewardLabel($defi['recompense'])); ?></span>
                                    </div>
                                </div>

                                <button class="btn-challenge-action btn btn-primary mt-4 w-100 challenge-trigger" data-challenge-id="<?php echo $defi['id']; ?>" data-challenge-title="<?php echo htmlspecialchars($defi['nom']); ?>" data-challenge-objective="<?php echo htmlspecialchars($defi['objectif']); ?>" data-challenge-type="<?php echo htmlspecialchars($defi['type']); ?>" data-challenge-reward="<?php echo htmlspecialchars($defi['recompense']); ?>">
                                    <span>Relever le défi</span>
                                    <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>

                            <!-- Animated Background -->
                            <div class="challenge-bg-gradient"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state text-center py-5" data-aos="fade-up">
                <div class="empty-icon mb-4">
                    <i class="fas fa-inbox fa-4x text-muted"></i>
                </div>
                <h4 class="text-muted mb-2">Aucun défi disponible</h4>
                <p class="text-muted">Les défis seront bientôt ajoutés. Revenez nous voir !</p>
                <a href="index.php" class="btn btn-outline-primary mt-3">Retourner à l'accueil</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Challenge Modal -->
<div id="challengeModal" class="challenge-modal-overlay">
    <div class="challenge-modal">
        <!-- Header -->
        <div class="modal-header">
            <div class="header-content">
                <h2 id="modalTitle" class="modal-title">Titre du défi</h2>
                <div class="header-meta">
                    <span id="modalDifficulty" class="difficulty-badge difficulty-easy">Facile</span>
                    <span id="modalType" class="challenge-type-badge type-aliment">Type</span>
                </div>
            </div>
            <button class="modal-close" aria-label="Close modal">&times;</button>
        </div>

        <!-- Body -->
        <div class="modal-body">
            <!-- Description Section -->
            <div class="modal-section">
                <h3>Objectif</h3>
                <p id="modalDescription" class="section-text">Selectionnez un defi pour afficher son objectif.</p>
            </div>

            <!-- Metadata Section -->
            <div class="modal-section metadata-section">
                <div class="metadata-item">
                    <div class="metadata-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div>
                        <small class="text-muted">Récompense</small>
                        <p id="modalReward">100 pts</p>
                    </div>
                </div>

                <div class="metadata-item">
                    <div class="metadata-icon">
                        <i class="fas fa-camera-retro"></i>
                    </div>
                    <div>
                        <small class="text-muted">Preuve</small>
                        <p>Image ou video depuis Mes defis</p>
                    </div>
                </div>
            </div>

            <!-- Participation Form Section -->
            <div class="modal-section participation-form-section">
                <h3>Démarrer le défi</h3>
                <p class="text-muted mb-3">La progression commence à 0%. Le statut et les points seront mis à jour par l'administration après révision des preuves.</p>
                <form id="participationForm" class="participation-form">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group mb-3">
                                <label for="userId" class="form-label">Votre ID</label>
                                <input type="number" class="form-control" id="userId" name="id_utilisateur" placeholder="Votre ID utilisateur" required min="1">
                                <small class="form-text text-muted">Saisissez votre ID utilisateur</small>
                            </div>
                        </div>
                    </div>

                    <div id="participationFormError" class="alert alert-danger" style="display: none; margin-top: 1rem;"></div>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
            <button class="btn btn-outline-secondary modal-cancel">
                <i class="fas fa-times me-2"></i>Fermer
            </button>
            <button class="btn btn-primary modal-start" id="submitParticipation">
                <i class="fas fa-play me-2"></i>Démarrer le défi
            </button>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
