<?php
require_once __DIR__ . '/../../../controllers/PackController.php';
require_once __DIR__ . '/../../../controllers/ProduitController.php';

$packController = new PackController();
$produitController = new ProduitController();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$pack = $packController->getById($id);
if (!$pack) {
    header('Location: ../produits/liste.php');
    exit();
}

$produits = $produitController->getAll();
$errors = [];
$values = [
    'nom' => $pack['nom'] ?? '',
    'description' => $pack['description'] ?? '',
    'prix' => $pack['prix'] ?? '',
    'pack_stock' => !empty($pack['items']) ? (string)(int)$pack['items'][0]['quantite'] : '1',
    'active' => (int)($pack['active'] ?? 1) === 1 ? '1' : '0'
];
$selectedItems = [];
foreach ($pack['items'] as $item) {
    $selectedItems[(int)$item['produit_id']] = (int)$item['quantite'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['nom'] = trim($_POST['nom'] ?? '');
    $values['description'] = trim($_POST['description'] ?? '');
    $values['prix'] = str_replace(',', '.', trim($_POST['prix'] ?? ''));
    $values['pack_stock'] = trim($_POST['pack_stock'] ?? '1');
    $values['active'] = isset($_POST['active']) ? '1' : '0';
    $packStock = max(1, (int)$values['pack_stock']);
    $selectedItems = [];

    foreach ($_POST['products'] ?? [] as $productId => $enabled) {
        if ($enabled === '1') {
            $selectedItems[(int)$productId] = $packStock;
        }
    }

    if ($packController->validateData($values, $selectedItems, $errors)) {
        $imageName = $packController->saveImage($_FILES['image'] ?? []);
        if ($imageName === false) {
            $errors['image'] = 'Image invalide. Utilisez JPG, PNG ou WEBP.';
        } else {
            $updatedPack = new Pack($values['nom'], $values['description'], (float)$values['prix'], $imageName, (int)$values['active']);
            if ($packController->update($id, $updatedPack, $selectedItems, true)) {
                header('Location: ../produits/liste.php?pack_updated=1');
                exit();
            }
            $errors['general'] = 'Impossible de modifier le pack.';
        }
    }
}

$title = 'Modifier pack - Stabilis';
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="form-card" style="max-width: 980px; margin:0 auto;">
    <div style="padding:24px; border-bottom:1px solid var(--border-light); display:flex; justify-content:space-between; gap:12px; align-items:center;">
        <div>
            <h3 style="margin:0;">Modifier le pack</h3>
            <p class="text-muted" style="margin-top:8px;">Mettez a jour les infos, la photo et les produits inclus.</p>
        </div>
        <a href="../../front/pack.php?id=<?php echo (int)$id; ?>" class="btn-secondary"><i class="fas fa-eye"></i> Voir</a>
    </div>
    <div style="padding:28px;">
        <?php if (!empty($errors['general'])): ?><div class="alert"><?php echo htmlspecialchars($errors['general']); ?></div><?php endif; ?>
        <?php if (!empty($errors['items'])): ?><div class="alert"><?php echo htmlspecialchars($errors['items']); ?></div><?php endif; ?>
        <?php if (!empty($errors['image'])): ?><div class="alert"><?php echo htmlspecialchars($errors['image']); ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
            <div class="pack-edit-grid">
                <div class="form-group"><label>Nom</label><input class="form-control" name="nom" value="<?php echo htmlspecialchars($values['nom']); ?>"><div class="error-message"><?php echo htmlspecialchars($errors['nom'] ?? ''); ?></div></div>
                <div class="form-group"><label>Prix total (EUR)</label><input class="form-control" name="prix" inputmode="decimal" value="<?php echo htmlspecialchars($values['prix']); ?>"><div class="error-message"><?php echo htmlspecialchars($errors['prix'] ?? ''); ?></div></div>
                <div class="form-group"><label>Stock du pack</label><input class="form-control" name="pack_stock" id="pack_stock" inputmode="numeric" value="<?php echo htmlspecialchars($values['pack_stock']); ?>"><div class="error-message"><?php echo htmlspecialchars($errors['pack_stock'] ?? ''); ?></div></div>
            </div>
            <div class="form-group"><label>Description</label><textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($values['description']); ?></textarea></div>
            <div class="form-group"><label>Nouvelle photo</label><input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp"><div class="hint">Laissez vide pour garder la photo actuelle.</div></div>
            <div class="form-group"><label style="display:flex; gap:10px; align-items:center; text-transform:none; letter-spacing:0; color:var(--text-secondary);"><input type="checkbox" name="active" value="1" <?php echo $values['active'] === '1' ? 'checked' : ''; ?>> Pack visible dans la boutique</label></div>

            <div class="form-group">
                <label>Produits du pack</label>
                <div class="pack-edit-products">
                    <?php foreach ($produits as $produit): $productId = (int)$produit['id']; ?>
                    <label class="pack-edit-product" data-stock="<?php echo (int)$produit['stock']; ?>">
                        <input type="checkbox" name="products[<?php echo $productId; ?>]" value="1" <?php echo isset($selectedItems[$productId]) ? 'checked' : ''; ?>>
                        <img src="/AdminLTE3/dist/img/<?php echo htmlspecialchars($produit['image_url'] ?? 'default-product.png'); ?>" onerror="this.src='/AdminLTE3/dist/img/default-product.png'" alt="">
                        <span><strong><?php echo htmlspecialchars($produit['nom']); ?></strong><small>Stock <?php echo (int)$produit['stock']; ?></small></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="display:flex; gap:12px; margin-top:24px;">
                <button class="btn-primary" type="submit"><i class="fas fa-save"></i> Enregistrer</button>
                <a href="../produits/liste.php" class="btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>

<style>
.pack-edit-grid { display:grid; grid-template-columns: 1.4fr .8fr .8fr; gap:16px; }
.pack-edit-products { display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:12px; }
.pack-edit-product { display:flex; align-items:center; gap:12px; padding:12px; border:1px solid var(--border-light); border-radius:12px; cursor:pointer; background:#fff; }
.pack-edit-product img { width:44px; height:44px; object-fit:cover; border-radius:8px; }
.pack-edit-product span { min-width:0; }
.pack-edit-product strong { display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pack-edit-product small { color:var(--text-muted); }
@media (max-width: 850px) { .pack-edit-grid { grid-template-columns:1fr; } }
</style>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
