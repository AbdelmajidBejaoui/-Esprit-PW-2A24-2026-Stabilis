<?php
$title = "Produits - Stabilis™";
require_once __DIR__ . '/../../partials/header.php';
require_once __DIR__ . '/../../../controllers/ProduitController.php';

$search = '';
$hasSearch = false;
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    if ($search !== '') {
        $hasSearch = true;
    }
}
$controller = new ProduitController();
$produits = $controller->getAll($search);
$count = count($produits);
?>

<div class="table-card">
    <div class="table-header">
        <div>
            <h3>Catalogue complet</h3>
            <?php
            if ($hasSearch) {
                echo '<div class="text-muted" style="margin-top: 8px;">Résultats pour "' . htmlspecialchars($search) . '"</div>';
            }
            ?>
        </div>
        <form method="GET" class="search-bar">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Rechercher par nom ou catégorie" class="form-control">
            <button type="submit" class="btn-secondary">Rechercher</button>
        </form>
        <div class="record-count">
            <?php
            echo $count;
            echo ' produit';
            if ($count > 1) {
                echo 's';
            }
            ?>
        </div>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Nom</th>
                    <th>Catégorie</th>
                    <th>Stock</th>
                    <th>Prix</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($count == 0) {
                    echo '<tr><td colspan="7" class="text-center">Aucun produit trouvé</td></tr>';
                } else {
                    foreach ($produits as $p) {
                        $image = 'default-product.png';
                        if (!empty($p['image_url'])) {
                            $image = $p['image_url'];
                        }
                        $badgeClass = 'badge-compensation';
                        if ($p['stock'] > 0) {
                            $badgeClass = 'badge-aliment';
                        }
                        echo '<tr>';
                        echo '<td>#' . $p['id'] . '</td>';
                        echo '<td><img src="/AdminLTE3/dist/img/' . $image . '" class="product-img" onerror="this.src=\'/AdminLTE3/dist/img/default-product.png\'"></td>';
                        echo '<td><strong>' . htmlspecialchars($p['nom']) . '</strong></td>';
                        echo '<td>' . htmlspecialchars($p['categorie']) . '</td>';
                        echo '<td><span class="badge ' . $badgeClass . '">' . $p['stock'] . ' unités</span></td>';
                        echo '<td><strong>' . number_format($p['prix'], 2) . ' €</strong></td>';
                        echo '<td>';
                        echo '<a href="modifier.php?id=' . $p['id'] . '" class="btn-icon"><i class="fas fa-edit"></i> <span>Modifier</span></a>';
                        echo '<a href="javascript:void(0)" onclick="supprimer(' . $p['id'] . ')" class="btn-icon btn-icon-danger"><i class="fas fa-trash"></i> <span>Supprimer</span></a>';
                        echo '</td>';
                        echo '</tr>';
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
    <div class="table-header" style="border-top: 1px solid var(--border-light);">
        <a href="ajout.php" class="btn-primary">
            <i class="fas fa-plus"></i> Ajouter un produit
        </a>
    </div>
</div>

<script>
function supprimer(id) {
    if (confirm('Souhaitez-vous vraiment supprimer ce produit ?')) {
        window.location.href = 'supprimer.php?id=' + id;
    }
}
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>