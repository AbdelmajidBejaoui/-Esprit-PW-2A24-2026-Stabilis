<?php error_reporting(E_ALL);
ini_set('display_errors', 1);

$title = "Dashboard - Stabilis™";

require_once __DIR__ . '/Views/partials/header.php';
require_once __DIR__ . '/controllers/ProduitController.php';
require_once __DIR__ . '/controllers/CommandeController.php';

$controller = new ProduitController();
$commandeController = new CommandeController();

$produits = $controller->getAll();
$totalProduits = count($produits);
$totalStock = array_sum(array_column($produits, 'stock'));

// Get recent commandes
$commandes = $commandeController->getAllGroupedForBackoffice();
$totalCommandes = count($commandes);
$totalRevenue = array_sum(array_column($commandes, 'total'));
?>

<!-- Stats Row -->
<div class="stats-row">
    <div class="stat-card hover-lift">
        <div class="stat-label">Produits</div>
        <div class="stat-value"><?php echo $totalProduits; ?></div>
        <div class="text-muted" style="font-size: 12px; margin-top: 8px;">en catalogue</div>
    </div>
    <div class="stat-card hover-lift">
        <div class="stat-label">Stock total</div>
        <div class="stat-value"><?php echo $totalStock; ?></div>
        <div class="text-muted" style="font-size: 12px; margin-top: 8px;">unités disponibles</div>
    </div>
    <div class="stat-card hover-lift">
        <div class="stat-label">Commandes</div>
        <div class="stat-value"><?php echo $totalCommandes; ?></div>
        <div class="text-muted" style="font-size: 12px; margin-top: 8px;">au total</div>
    </div>
    <div class="stat-card hover-lift">
        <div class="stat-label">Chiffre d'affaires</div>
        <div class="stat-value"><?php echo number_format($totalRevenue, 0, ',', ' '); ?> €</div>
        <div class="text-muted" style="font-size: 12px; margin-top: 8px;">revenue généré</div>
    </div>
</div>

<!-- Eco Widget -->
<div class="eco-widget">
    <div>
        <i class="fas fa-leaf" style="color: var(--accent-herb); font-size: 24px;"></i>
    </div>
    <div style="flex: 1;">
        <strong style="color: var(--accent-earth-dark);">Engagement durable</strong>
        <p style="margin: 0; font-size: 13px;">Chaque produit compense deux fois son empreinte carbone.</p>
    </div>
    <div>
        <span class="badge-aliment badge">+250kg CO₂ économisés</span>
    </div>
</div>

<!-- Tableau des produits -->
<div class="table-card">
    <div class="table-header">
        <h3><i class="fas fa-bolt"></i> Produits récents</h3>
        <div class="record-count">
            <i class="fas fa-box"></i> <?php echo $totalProduits; ?> références
        </div>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Catégorie</th>
                    <th>Stock</th>
                    <th>Prix</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($produits)): ?>
                <tr>
                    <td colspan="5" class="text-center">Aucun produit pour le moment</td>
                </tr>
                <?php else: ?>
                <?php foreach(array_slice($produits, 0, 5) as $p): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($p['nom']); ?></strong><br>
                        <small class="text-muted"><?php echo htmlspecialchars($p['description'] ?? 'Aucune description'); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($p['categorie']); ?></td>
                    <td><?php echo $p['stock']; ?> unités</td>
                    <td><strong><?php echo number_format($p['prix'], 2); ?> €</strong></td>
                    <td>
                        <a href="Views/back/produits/modifier.php?id=<?php echo $p['id']; ?>" class="btn-icon">
                            <i class="fas fa-edit"></i> <span>Modifier</span>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="table-header" style="border-top: 1px solid var(--border-light);">
        <a href="Views/back/produits/liste.php" class="btn-secondary">
            <i class="fas fa-arrow-right"></i> Voir tous les produits
        </a>
        <a href="Views/back/produits/ajout.php" class="btn-primary">
            <i class="fas fa-plus"></i> Ajouter un produit
        </a>
    </div>
</div>

<!-- Tableau des commandes -->
<div class="table-card">
    <div class="table-header">
        <h3><i class="fas fa-shopping-cart"></i> Commandes récentes</h3>
        <div class="record-count">
            <i class="fas fa-receipt"></i> <?php echo $totalCommandes; ?> commandes
        </div>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Produits</th>
                    <th>Total</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($commandes)): ?>
                <tr>
                    <td colspan="7" class="text-center">Aucune commande pour le moment</td>
                </tr>
                <?php else: ?>
                <?php foreach(array_slice($commandes, 0, 5) as $c): ?>
                <tr>
                    <td><strong>#<?php echo htmlspecialchars($c['id']); ?></strong></td>
                    <td><?php echo htmlspecialchars($c['prenom'] . ' ' . $c['nom']); ?></td>
                    <td><?php echo htmlspecialchars($c['produits_resume'] ?? '-'); ?></td>
                    <td><strong><?php echo number_format((float)$c['total'], 2); ?> €</strong></td>
                    <td>
                        <?php
                        $statusClass = 'badge-warning';
                        if ($c['statut'] === 'Validée') $statusClass = 'badge-success';
                        elseif ($c['statut'] === 'Expédiée') $statusClass = 'badge-info';
                        elseif ($c['statut'] === 'Annulée') $statusClass = 'badge-danger';
                        ?>
                        <span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($c['statut']); ?></span>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($c['date_commande'])); ?></td>
                    <td>
                        <a href="Views/back/commandes/voir.php?id=<?php echo $c['id']; ?>" class="btn-icon">
                            <i class="fas fa-eye"></i> <span>Voir</span>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="table-header" style="border-top: 1px solid var(--border-light);">
        <a href="Views/back/commandes/liste.php" class="btn-secondary">
            <i class="fas fa-arrow-right"></i> Voir toutes les commandes
        </a>
    </div>

<div class="inspo-widget">
    <i class="fas fa-quote-left" style="color: var(--accent-herb); font-size: 20px;"></i>
    <div>
        <em>"La performance durable commence par ce que vous mettez dans votre assiette — et dans votre code."</em>
        <div class="text-muted" style="font-size: 12px; margin-top: 5px;">— Stabilis Lab</div>
    </div>
</div>

<?php require_once __DIR__ . '/views/partials/footer.php'; ?>