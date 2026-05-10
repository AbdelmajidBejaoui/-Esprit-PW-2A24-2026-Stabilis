<?php require_once __DIR__ . '/../layout/header.php'; ?>

<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-success">Participation supprimée !</div>
<?php endif; ?>

<?php
    $statusLabels = [
        'in_progress' => 'En cours',
        'completed' => 'Terminée',
        'failed' => 'Échouée',
    ];
    $statusClasses = [
        'in_progress' => 'warning',
        'completed' => 'success',
        'failed' => 'danger',
    ];
?>

<!-- Entity Tabs -->
<div class="d-flex justify-content-center mb-4">
    <ul class="nav nav-pills nav-fill w-50">
        <li class="nav-item">
            <a class="nav-link" href="?entity=defis">
                <i class="fas fa-list me-1"></i>Défis
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="?entity=participations">
                <i class="fas fa-users me-1"></i>Participations
            </a>
        </li>
    </ul>
</div>

<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-number"><?php echo $count; ?></div>
        <div class="stat-label">Participations totales</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo count(array_filter($participations, fn($p) => $p['statut'] === 'in_progress')); ?></div>
        <div class="stat-label">En cours</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo count(array_filter($participations, fn($p) => $p['statut'] === 'completed')); ?></div>
        <div class="stat-label">Terminées</div>
    </div>
</div>

<div class="search-toolbar admin-toolbar mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-lg-5">
            <label class="toolbar-label">Recherche</label>
            <form method="GET" action="index.php" style="display: contents;">
                <input type="hidden" name="entity" value="participations">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" id="searchInput" class="form-control" placeholder="Rechercher par ID ou utilisateur..." value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-arrow-right"></i></button>
                </div>
            </form>
        </div>
        <div class="col-lg-3">
            <label class="toolbar-label">Filtre</label>
            <select id="statutFilter" class="form-select">
                <option value="">Tous statuts</option>
                <option value="in_progress">En cours</option>
                <option value="completed">Terminée</option>
                <option value="failed">Échouée</option>
            </select>
        </div>
        <div class="col-lg-4">
            <label class="toolbar-label">Actions</label>
            <div class="toolbar-actions justify-content-lg-end">
                <button type="button" id="sortBtn" class="btn btn-outline-primary">
                    <i class="fas fa-sort me-2"></i>Trier
                </button>
                <button type="button" id="exportPdfBtn" class="btn btn-outline-danger" data-export-title="Liste des participations">
                    <i class="fas fa-file-pdf me-2"></i>PDF
                </button>
                <a href="index.php?entity=participations" class="btn btn-outline-warning">
                    <i class="fas fa-times me-1"></i>Réinitialiser
                </a>
                <a href="index.php?entity=participations&action=create" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Nouveau
                </a>
            </div>
        </div>
    </div>
</div>

<div class="section-heading">
    <h2><i class="fas fa-users text-primary me-2"></i>Liste des Participations</h2>
    <span class="table-hint">Le PDF exporte les lignes actuellement visibles.</span>
</div>

<div class="table-responsive">
    <table class="table table-hover defis-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Utilisateur</th>
                <th>Défi</th>
                <th>Progression</th>
                <th>Statut</th>
                <th>Preuves</th>
                <th>Début</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($participations as $p): ?>
            <tr data-id="<?php echo htmlspecialchars($p['id']); ?>" data-userid="<?php echo htmlspecialchars($p['id_utilisateur']); ?>" data-nom="<?php echo htmlspecialchars($p['defi_nom'] ?? 'Défi #' . $p['id_defi']); ?>" data-type="" data-statut="<?php echo strtolower($p['statut']); ?>" data-date="<?php echo htmlspecialchars($p['date_debut']); ?>" data-progression="<?php echo htmlspecialchars($p['progression']); ?>">
                <td><strong>#<?php echo $p['id']; ?></strong></td>
                <td>#<?php echo $p['id_utilisateur']; ?></td>
                <td><?php echo htmlspecialchars($p['defi_nom'] ?? 'Défi #' . $p['id_defi']); ?></td>
                <td>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar" style="width: <?php echo $p['progression']; ?>%;">
                            <?php echo $p['progression']; ?>%
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-<?php echo $statusClasses[$p['statut']] ?? 'secondary'; ?>">
                        <?php echo htmlspecialchars($statusLabels[$p['statut']] ?? $p['statut']); ?>
                    </span>
                </td>
                <td>
                    <span class="badge bg-<?php echo ((int)($p['pending_proof_count'] ?? 0) > 0) ? 'warning' : 'secondary'; ?>">
                        <?php echo (int)($p['proof_count'] ?? 0); ?> preuve(s)
                    </span>
                </td>
                <td><?php echo htmlspecialchars($p['date_debut']); ?></td>
                <td>
                    <a href="index.php?entity=participations&action=edit&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-warning me-1 table-action-btn" title="Modifier">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="index.php?entity=participations&action=delete&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger delete-btn table-action-btn" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<nav class="d-flex justify-content-end mt-3" aria-label="Pagination des participations">
    <ul class="pagination mb-0">
        <li class="page-item">
            <button type="button" class="page-link" id="prevPage">Précédent</button>
        </li>
        <li class="page-item">
            <button type="button" class="page-link" id="nextPage">Suivant</button>
        </li>
    </ul>
</nav>

<!-- Sort Modal -->
<div id="sortModal" class="sort-modal-overlay">
    <div class="sort-modal">
        <div class="sort-modal-header">
            <h3>Trier par</h3>
            <button type="button" class="btn-close" aria-label="Close"></button>
        </div>
        <div class="sort-modal-body">
            <div class="sort-options">
                <button class="sort-option-btn" data-sort="id-desc">
                    <i class="fas fa-arrow-down me-2"></i>
                    <span>ID (Récent d'abord)</span>
                </button>
                <button class="sort-option-btn" data-sort="id-asc">
                    <i class="fas fa-arrow-up me-2"></i>
                    <span>ID (Ancien d'abord)</span>
                </button>
                <button class="sort-option-btn" data-sort="date-desc">
                    <i class="fas fa-calendar me-2"></i>
                    <span>Date (Récent d'abord)</span>
                </button>
                <button class="sort-option-btn" data-sort="date-asc">
                    <i class="fas fa-calendar me-2"></i>
                    <span>Date (Ancien d'abord)</span>
                </button>
                <button class="sort-option-btn" data-sort="progression-desc">
                    <i class="fas fa-chart-line me-2"></i>
                    <span>Progression (Plus élevée)</span>
                </button>
                <button class="sort-option-btn" data-sort="progression-asc">
                    <i class="fas fa-chart-line me-2"></i>
                    <span>Progression (Plus basse)</span>
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

