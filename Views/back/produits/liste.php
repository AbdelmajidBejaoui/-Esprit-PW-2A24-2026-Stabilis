<?php
$title = "Produits - Stabilis™";
require_once __DIR__ . '/../../partials/header.php';
require_once __DIR__ . '/../../../controllers/ProduitController.php';
require_once __DIR__ . '/../../../controllers/PackController.php';

$search = '';
$sort = $_GET['sort'] ?? 'recent';
$hasSearch = false;
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    if ($search !== '') {
        $hasSearch = true;
    }
}
$controller = new ProduitController();
$packController = new PackController();
$produits = $controller->getAll($search, '', $sort);
$packs = $packController->hydrateItems($packController->getAll(false, $search, $sort));
$count = count($produits);
$packCount = count($packs);
$sortOptions = [
    'recent' => 'Plus recents',
    'name_asc' => 'Nom A-Z',
    'name_desc' => 'Nom Z-A',
    'price_asc' => 'Prix croissant',
    'price_desc' => 'Prix decroissant',
    'stock_asc' => 'Stock bas',
    'stock_desc' => 'Stock eleve',
    'category_asc' => 'Categorie'
];
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
            <select name="sort" class="form-control">
                <?php foreach ($sortOptions as $value => $label): ?>
                    <option value="<?php echo htmlspecialchars($value); ?>"<?php echo $sort === $value ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                <?php endforeach; ?>
            </select>
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
                        if ((int)($p['coming_soon'] ?? 0) === 1) {
                            $badgeClass = 'badge-compensation';
                        } elseif ($p['stock'] > 0) {
                            $badgeClass = 'badge-aliment';
                        }
                        echo '<tr>';
                        echo '<td>#' . $p['id'] . '</td>';
                        echo '<td><img src="/AdminLTE3/dist/img/' . $image . '" class="product-img" onerror="this.src=\'/AdminLTE3/dist/img/default-product.png\'"></td>';
                        echo '<td><strong>' . htmlspecialchars($p['nom']) . '</strong>';
                        if ((int)($p['coming_soon'] ?? 0) === 1) {
                            echo '<br><small style="color:#B0873A; font-weight:700;">Coming soon / pre-order</small>';
                        }
                        echo '</td>';
                        echo '<td>' . htmlspecialchars($p['categorie']) . '</td>';
                        echo '<td><span class="badge ' . $badgeClass . '">' . $p['stock'] . ' unités</span></td>';
                        $price = (float)$p['prix'];
                        $promoPrice = $p['promo_prix'] ?? null;
                        echo '<td>';
                        if ($promoPrice !== null && $promoPrice !== '' && (float)$promoPrice > 0 && (float)$promoPrice < $price) {
                            echo '<span style="text-decoration: line-through; color: #888;">' . number_format($price, 2) . ' €</span><br>';
                            echo '<strong style="color: #1b5e20;">' . number_format((float)$promoPrice, 2) . ' €</strong>';
                        } else {
                            echo '<strong>' . number_format($price, 2) . ' €</strong>';
                        }
                        echo '</td>';
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
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="ajout.php" class="btn-primary">
                <i class="fas fa-plus"></i> Ajouter un produit
            </a>
            <button type="button" class="btn-secondary" onclick="testEmail()" id="btn-test-email">
                <i class="fas fa-envelope"></i> Tester l'alerte mail
            </button>
            <button type="button" class="btn-secondary" onclick="checkLowStock()" id="btn-check-stock">
                <i class="fas fa-exclamation-triangle"></i> Vérifier stock faible
            </button>
            <button type="button" class="btn-secondary" onclick="sendAlert()" id="btn-send-alert">
                <i class="fas fa-bell"></i> Envoyer alerte
            </button>
        </div>
    </div>
</div>

<div class="table-card" style="margin-top:28px;">
    <div class="table-header">
        <div>
            <h3>Packs</h3>
            <div class="text-muted" style="margin-top: 8px;">Voir, modifier ou supprimer les packs affiches dans la boutique.</div>
        </div>
        <a href="../packs/ajout.php" class="btn-primary"><i class="fas fa-boxes-stacked"></i> Nouveau pack</a>
        <div class="record-count"><?php echo $packCount; ?> pack<?php echo $packCount > 1 ? 's' : ''; ?></div>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Nom</th>
                    <th>Produits</th>
                    <th>Prix</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($packCount === 0): ?>
                    <tr><td colspan="7" class="text-center">Aucun pack trouve</td></tr>
                <?php else: ?>
                    <?php foreach ($packs as $pack): ?>
                    <?php
                    $image = $pack['image_url'] ?: (($pack['items'][0]['image_url'] ?? '') ?: 'default-product.png');
                    $items = array_map(function ($item) {
                        return $item['nom'] . ' x' . (int)$item['quantite'];
                    }, $pack['items'] ?? []);
                    ?>
                    <tr>
                        <td>#<?php echo (int)$pack['id']; ?></td>
                        <td><img src="/AdminLTE3/dist/img/<?php echo htmlspecialchars($image); ?>" class="product-img" onerror="this.src='/AdminLTE3/dist/img/default-product.png'"></td>
                        <td><strong><?php echo htmlspecialchars($pack['nom']); ?></strong></td>
                        <td><?php echo htmlspecialchars(implode(', ', $items)); ?></td>
                        <td><strong><?php echo number_format((float)$pack['prix'], 2); ?> EUR</strong></td>
                        <td><span class="badge <?php echo (int)$pack['active'] === 1 ? 'badge-aliment' : 'badge-compensation'; ?>"><?php echo (int)$pack['active'] === 1 ? 'Visible' : 'Masque'; ?></span></td>
                        <td>
                            <a href="../../front/pack.php?id=<?php echo (int)$pack['id']; ?>" class="btn-icon"><i class="fas fa-eye"></i> <span>Voir</span></a>
                            <a href="../packs/modifier.php?id=<?php echo (int)$pack['id']; ?>" class="btn-icon"><i class="fas fa-edit"></i> <span>Modifier</span></a>
                            <a href="javascript:void(0)" onclick="supprimerPack(<?php echo (int)$pack['id']; ?>)" class="btn-icon btn-icon-danger"><i class="fas fa-trash"></i> <span>Supprimer</span></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="delete-modal" class="delete-modal" style="display:none;">
    <div class="delete-modal-card">
        <h3>Supprimer le produit</h3>
        <p>Souhaitez-vous vraiment supprimer ce produit ?</p>
        <div class="delete-modal-actions">
            <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Annuler</button>
            <button type="button" class="btn-danger-custom" onclick="confirmDelete()">Supprimer</button>
        </div>
    </div>
</div>

<div id="delete-pack-modal" class="delete-modal" style="display:none;">
    <div class="delete-modal-card">
        <h3>Supprimer le pack</h3>
        <p>Souhaitez-vous vraiment supprimer ce pack ?</p>
        <div class="delete-modal-actions">
            <button type="button" class="btn-secondary" onclick="closePackDeleteModal()">Annuler</button>
            <button type="button" class="btn-danger-custom" onclick="confirmPackDelete()">Supprimer</button>
        </div>
    </div>
</div>

<div id="alert-notification" class="alert-notification" style="display: none;">
    <div class="alert-content">
        <span id="alert-message"></span>
        <button onclick="closeAlert()" class="alert-close">&times;</button>
    </div>
</div>

<style>
.alert-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    animation: slideIn 0.3s ease-out;
}

.alert-content {
    background: white;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    min-width: 300px;
}

.alert-notification.success .alert-content {
    border-left: 4px solid #28a745;
    background: #f1f9f5;
}

.alert-notification.error .alert-content {
    border-left: 4px solid #dc3545;
    background: #fef5f5;
}

.alert-notification.info .alert-content {
    border-left: 4px solid #17a2b8;
    background: #f0f7fb;
}

.alert-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #999;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
}

@keyframes slideIn {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.delete-modal {
    position: fixed;
    inset: 0;
    z-index: 10000;
    background: rgba(18, 28, 23, 0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 18px;
}

.delete-modal-card {
    width: min(420px, 100%);
    background: #fff;
    border-radius: 10px;
    border: 1px solid var(--border-light);
    box-shadow: 0 20px 45px rgba(0, 0, 0, 0.18);
    padding: 24px;
}

.delete-modal-card h3 {
    margin: 0 0 8px;
    color: #1A4D3A;
}

.delete-modal-card p {
    margin: 0;
    color: #465348;
}

.delete-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 22px;
}

.btn-danger-custom {
    border: none;
    border-radius: 999px;
    background: #C55A4A;
    color: #fff;
    padding: 10px 18px;
    font-weight: 700;
    cursor: pointer;
}
</style>

<script>
let deleteProductId = null;
let deletePackId = null;

function showAlert(message, type = 'info') {
    const notification = document.getElementById('alert-notification');
    const messageEl = document.getElementById('alert-message');
    
    notification.className = 'alert-notification ' + type;
    messageEl.textContent = message;
    notification.style.display = 'block';
    
    setTimeout(closeAlert, 5000);
}

function closeAlert() {
    document.getElementById('alert-notification').style.display = 'none';
}

function disableAlertButtons(disabled = true) {
    document.getElementById('btn-test-email').disabled = disabled;
    document.getElementById('btn-check-stock').disabled = disabled;
    document.getElementById('btn-send-alert').disabled = disabled;
}

function testEmail() {
    disableAlertButtons(true);
    showAlert('Envoi du mail de test en cours...', 'info');
    
    fetch('../../controllers/StockAlertHandler.php?action=test_email')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let msg = '✓ Email de test envoyé (' + data.recipient + ')';
                if (data.log_file) {
                    msg += ' - <a href="' + data.log_file + '" target="_blank" style="color: #0066cc; text-decoration: underline;">Voir le log</a>';
                }
                showAlert(msg, 'success');
            } else {
                showAlert('✗ Erreur : ' + data.message, 'error');
            }
        })
        .catch(error => {
            showAlert('✗ Erreur réseau : ' + error.message, 'error');
            console.error('Error:', error);
        })
        .finally(() => disableAlertButtons(false));
}

function checkLowStock() {
    disableAlertButtons(true);
    showAlert('Vérification des produits en stock faible...', 'info');
    
    fetch('../../controllers/StockAlertHandler.php?action=get_low_stock')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.count === 0) {
                    showAlert('✓ Aucun produit en stock faible actuellement', 'success');
                } else {
                    let productList = data.products.map(p => p.nom + ' (' + p.stock + ' unités)').join(', ');
                    showAlert('ℹ️ ' + data.count + ' produit(s) en stock faible : ' + productList, 'info');
                }
            } else {
                showAlert('✗ Erreur : ' + data.message, 'error');
            }
        })
        .catch(error => {
            showAlert('✗ Erreur réseau : ' + error.message, 'error');
            console.error('Error:', error);
        })
        .finally(() => disableAlertButtons(false));
}

function sendAlert() {
    disableAlertButtons(true);
    showAlert('Envoi de l\'alerte stock en cours...', 'info');
    
    fetch('../../controllers/StockAlertHandler.php?action=check_low_stock')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let msg = '✓ Alerte envoyée avec succès (' + data.products_count + ' produit(s))';
                if (data.log_file) {
                    msg += ' - <a href="' + data.log_file + '" target="_blank" style="color: #0066cc; text-decoration: underline;">Voir le log</a>';
                }
                showAlert(msg, 'success');
            } else {
                showAlert('ℹ️ ' + data.message, 'info');
            }
        })
        .catch(error => {
            showAlert('✗ Erreur réseau : ' + error.message, 'error');
            console.error('Error:', error);
        })
        .finally(() => disableAlertButtons(false));
}

function supprimer(id) {
    deleteProductId = id;
    document.getElementById('delete-modal').style.display = 'flex';
}

function closeDeleteModal() {
    deleteProductId = null;
    document.getElementById('delete-modal').style.display = 'none';
}

function confirmDelete() {
    if (deleteProductId) {
        window.location.href = 'supprimer.php?id=' + deleteProductId;
    }
}

function supprimerPack(id) {
    deletePackId = id;
    document.getElementById('delete-pack-modal').style.display = 'flex';
}

function closePackDeleteModal() {
    deletePackId = null;
    document.getElementById('delete-pack-modal').style.display = 'none';
}

function confirmPackDelete() {
    if (deletePackId) {
        window.location.href = '../packs/supprimer.php?id=' + deletePackId;
    }
}

const params = new URLSearchParams(window.location.search);
const wishlistTotal = parseInt(params.get('wishlist_total') || '0', 10);
const wishlistSent = parseInt(params.get('wishlist_sent') || '0', 10);
const wishlistFailed = parseInt(params.get('wishlist_failed') || '0', 10);
if (wishlistTotal > 0) {
    if (wishlistSent > 0 && wishlistFailed === 0) {
        showAlert(wishlistSent + ' client(s) prevenu(s) du retour en stock.', 'success');
    } else if (wishlistSent > 0 && wishlistFailed > 0) {
        showAlert(wishlistSent + ' client(s) prevenu(s). ' + wishlistFailed + ' email(s) restent en attente.', 'error');
    } else {
        showAlert('Aucun email envoye. ' + wishlistFailed + ' notification(s) restent en attente: verifiez SMTP/Gmail puis re-enregistrez le produit.', 'error');
    }
}
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
