<?php require_once __DIR__ . '/../layout/header.php'; ?>

<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-success">Défi supprimé avec succès!</div>
<?php endif; ?>

<!-- Entity Tabs -->
<div class="d-flex justify-content-center mb-4">
    <ul class="nav nav-pills nav-fill w-50">
        <li class="nav-item">
            <a class="nav-link active" href="?entity=defis">
                <i class="fas fa-list me-1"></i>Défis
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="?entity=participations">
                <i class="fas fa-users me-1"></i>Participations
            </a>
        </li>
    </ul>
</div>

<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-number"><?php echo $count; ?></div>
        <div class="stat-label">Défis totaux</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo number_format($co2_evite, 1); ?>kg</div>
        <div class="stat-label">CO2 évité (estimé)</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo count($defis); ?></div>
        <div class="stat-label">Défis actifs</div>
    </div>
</div>

<!-- Search, filter, and action toolbar -->
<div class="search-toolbar admin-toolbar mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-lg-5">
            <label class="toolbar-label">Recherche</label>
            <form method="GET" action="index.php" style="display: contents;">
                <input type="hidden" name="entity" value="defis">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" id="searchInput" class="form-control" placeholder="Rechercher par ID ou nom..." value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-arrow-right"></i></button>
                </div>
            </form>
        </div>
        <div class="col-lg-3">
            <label class="toolbar-label">Filtre</label>
            <select id="typeFilter" class="form-select">
                <option value="">Tous les types</option>
                <option value="aliment">Alimentaire</option>
                <option value="entrainement">Entraînement</option>
                <option value="compensation">Compensation</option>
            </select>
        </div>
        <div class="col-lg-4">
            <label class="toolbar-label">Actions</label>
            <div class="toolbar-actions justify-content-lg-end">
                <button type="button" id="sortBtn" class="btn btn-outline-primary">
                    <i class="fas fa-sort me-2"></i>Trier
                </button>
                <button type="button" id="exportPdfBtn" class="btn btn-outline-danger" data-export-title="Liste des défis">
                    <i class="fas fa-file-pdf me-2"></i>PDF
                </button>
                <a href="index.php?entity=defis" class="btn btn-outline-warning">
                    <i class="fas fa-times me-1"></i>Réinitialiser
                </a>
                <a href="index.php?action=create" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Nouveau
                </a>
            </div>
        </div>
    </div>
</div>

<div class="section-heading">
    <h2><i class="fas fa-list text-primary me-2"></i>Liste des Défis</h2>
    <span class="table-hint">Le PDF exporte les lignes actuellement visibles.</span>
</div>

<?php if (empty($defis)): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>Aucun défi trouvé. <a href="index.php?action=create">Créer le premier défi</a> !
</div>
<?php else: ?>

<table class="table table-striped table-hover defis-table">
    <thead>
        <tr>
            <th><input type="checkbox" id="selectAll"></th>
            <th>ID</th>
            <th>Nom</th>
            <th>Type</th>
            <th>Objectif</th>
            <th>Récompense</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($defis as $defi): 
            $typeClass = '';
            if ($defi['type'] === 'aliment') {
                $typeClass = 'success';
            } elseif ($defi['type'] === 'entrainement') {
                $typeClass = 'warning';
            } elseif ($defi['type'] === 'compensation') {
                $typeClass = 'info';
            } else {
                $typeClass = 'secondary';
            }
        ?>
        <tr data-id="<?php echo htmlspecialchars($defi['id']); ?>" data-type="<?php echo htmlspecialchars($defi['type']); ?>" data-nom="<?php echo htmlspecialchars(strtolower($defi['nom'])); ?>" data-recompense="<?php echo htmlspecialchars($defi['recompense']); ?>">
            <td><input type="checkbox" class="row-checkbox" value="<?php echo $defi['id']; ?>"></td>
            <td><strong>#<?php echo $defi['id']; ?></strong></td>
            <td><?php echo htmlspecialchars($defi['nom']); ?></td>
            <td>
                <span class="badge bg-<?php echo $typeClass; ?>">
                    <?php echo ucfirst($defi['type']); ?>
                </span>
            </td>
            <td><?php echo htmlspecialchars(substr($defi['objectif'], 0, 50)); ?>...</td>
            <td><?php echo htmlspecialchars($defi['recompense']); ?></td>
            <td>
                <a href="index.php?action=edit&id=<?php echo $defi['id']; ?>" class="btn btn-sm btn-warning me-1 table-action-btn" title="Modifier">
                    <i class="fas fa-edit"></i>
                </a>
                <a href="index.php?action=delete&id=<?php echo $defi['id']; ?>" class="btn btn-sm btn-danger delete-btn table-action-btn" data-id="<?php echo $defi['id']; ?>" title="Supprimer">
                    <i class="fas fa-trash"></i>
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<nav class="d-flex justify-content-end mt-3" aria-label="Pagination des défis">
    <ul class="pagination mb-0">
        <li class="page-item">
            <button type="button" class="page-link" id="prevPage">Précédent</button>
        </li>
        <li class="page-item">
            <button type="button" class="page-link" id="nextPage">Suivant</button>
        </li>
    </ul>
</nav>

<?php endif; ?>

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
                <button class="sort-option-btn" data-sort="points-desc">
                    <i class="fas fa-coins me-2"></i>
                    <span>Points (Plus élevé)</span>
                </button>
                <button class="sort-option-btn" data-sort="points-asc">
                    <i class="fas fa-coins me-2"></i>
                    <span>Points (Plus bas)</span>
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
