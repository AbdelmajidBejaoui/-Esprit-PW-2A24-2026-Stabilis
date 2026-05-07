<?php
require_once __DIR__ . '/../../../controllers/PackController.php';
require_once __DIR__ . '/../../../controllers/ProduitController.php';

$packController = new PackController();
$produitController = new ProduitController();
$produits = $produitController->getAll();
$errors = [];
$values = [
    'nom' => '',
    'description' => '',
    'prix' => '',
    'pack_stock' => '1',
    'active' => '1'
];
$selectedItems = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['nom'] = trim($_POST['nom'] ?? '');
    $values['description'] = trim($_POST['description'] ?? '');
    $values['prix'] = str_replace(',', '.', trim($_POST['prix'] ?? ''));
    $values['pack_stock'] = trim($_POST['pack_stock'] ?? '1');
    $values['active'] = isset($_POST['active']) ? '1' : '0';
    $packStock = max(1, (int)$values['pack_stock']);

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
            $pack = new Pack($values['nom'], $values['description'], (float)$values['prix'], $imageName, (int)$values['active']);
            if ($packController->add($pack, $selectedItems)) {
                header('Location: ../produits/liste.php?pack_created=1');
                exit();
            }
            $errors['general'] = 'Impossible de creer le pack.';
        }
    }
}

$title = 'Ajouter un pack - Stabilis';
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="form-card" style="max-width: 980px; margin:0 auto;">
    <div class="pack-form-header">
        <div>
            <h3 style="margin:0;">Nouveau pack</h3>
            <p class="text-muted" style="margin-top:8px;">Composez le pack et selectionnez ses produits.</p>
        </div>
        <span class="record-count"><span id="selectedCount">0</span> produits</span>
    </div>

    <div style="padding:28px;">
        <?php if (!empty($errors['general'])): ?><div class="alert"><?php echo htmlspecialchars($errors['general']); ?></div><?php endif; ?>
        <?php if (!empty($errors['items'])): ?><div class="alert"><?php echo htmlspecialchars($errors['items']); ?></div><?php endif; ?>
        <?php if (!empty($errors['image'])): ?><div class="alert"><?php echo htmlspecialchars($errors['image']); ?></div><?php endif; ?>

        <form method="POST" id="packForm" enctype="multipart/form-data" novalidate>
            <div class="pack-edit-grid">
                <div class="form-group">
                    <label>Nom</label>
                    <input class="form-control" name="nom" id="nom" value="<?php echo htmlspecialchars($values['nom']); ?>" placeholder="Pack prise de masse">
                    <div class="error-message"><?php echo htmlspecialchars($errors['nom'] ?? ''); ?></div>
                </div>
                <div class="form-group">
                    <label>Prix total (EUR)</label>
                    <input class="form-control" name="prix" id="prix" inputmode="decimal" value="<?php echo htmlspecialchars($values['prix']); ?>" placeholder="79.99">
                    <div class="error-message"><?php echo htmlspecialchars($errors['prix'] ?? ''); ?></div>
                </div>
                <div class="form-group">
                    <label>Stock du pack</label>
                    <input class="form-control" name="pack_stock" id="pack_stock" inputmode="numeric" value="<?php echo htmlspecialchars($values['pack_stock']); ?>" placeholder="6">
                    <div class="error-message"><?php echo htmlspecialchars($errors['pack_stock'] ?? ''); ?></div>
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea class="form-control" name="description" id="description" rows="4" placeholder="Selectionnez les produits puis laissez l'AI proposer une description."><?php echo htmlspecialchars($values['description']); ?></textarea>
                <button type="button" class="btn-secondary" id="generatePackDescription" style="margin-top:10px;">
                    <i class="fas fa-wand-magic-sparkles"></i> Generer avec AI
                </button>
                <div class="hint" id="aiDescriptionStatus">La generation utilise les produits choisis.</div>
            </div>

            <div class="form-group">
                <label>Photo du pack</label>
                <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                <div class="hint">Sans photo, la boutique affichera un diaporama des produits du pack.</div>
            </div>

            <div class="form-group">
                <label style="display:flex; gap:10px; align-items:center; text-transform:none; letter-spacing:0; color:var(--text-secondary);">
                    <input type="checkbox" name="active" value="1" <?php echo $values['active'] === '1' ? 'checked' : ''; ?>>
                    Pack visible dans la boutique
                </label>
            </div>

            <div class="form-group">
                <label>Produits du pack</label>
                <div class="pack-edit-products">
                    <?php foreach ($produits as $produit): $productId = (int)$produit['id']; ?>
                    <label class="pack-edit-product" data-stock="<?php echo (int)$produit['stock']; ?>" data-name="<?php echo htmlspecialchars($produit['nom']); ?>" data-category="<?php echo htmlspecialchars($produit['categorie'] ?? ''); ?>">
                        <input type="checkbox" name="products[<?php echo $productId; ?>]" value="1" <?php echo isset($selectedItems[$productId]) ? 'checked' : ''; ?>>
                        <img src="/AdminLTE3/dist/img/<?php echo htmlspecialchars($produit['image_url'] ?? 'default-product.png'); ?>" onerror="this.src='/AdminLTE3/dist/img/default-product.png'" alt="">
                        <span>
                            <strong><?php echo htmlspecialchars($produit['nom']); ?></strong>
                            <small>Stock <?php echo (int)$produit['stock']; ?> | x<span class="stockMirror"><?php echo htmlspecialchars($values['pack_stock']); ?></span></small>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display:flex; gap:12px; margin-top:24px;">
                <button class="btn-primary" type="submit"><i class="fas fa-boxes-stacked"></i> Creer le pack</button>
                <a href="../produits/liste.php" class="btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>

<style>
.pack-form-header { padding:24px; border-bottom:1px solid var(--border-light); display:flex; justify-content:space-between; gap:12px; align-items:center; }
.pack-edit-grid { display:grid; grid-template-columns: 1.4fr .8fr .8fr; gap:16px; }
.pack-edit-products { display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:12px; }
.pack-edit-product { display:flex; align-items:center; gap:12px; padding:12px; border:1px solid var(--border-light); border-radius:12px; cursor:pointer; background:#fff; transition:all .16s ease; }
.pack-edit-product:hover { border-color:var(--accent-herb-soft); box-shadow:var(--shadow-sm); transform:translateY(-1px); }
.pack-edit-product.is-selected { border-color:var(--accent-herb); background:var(--accent-herb-light); }
.pack-edit-product.is-short { opacity:.5; border-color:#f2c6bd; }
.pack-edit-product img { width:44px; height:44px; object-fit:cover; border-radius:8px; }
.pack-edit-product span { min-width:0; }
.pack-edit-product strong { display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pack-edit-product small { color:var(--text-muted); }
@media (max-width: 850px) { .pack-edit-grid { grid-template-columns:1fr; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const stockInput = document.getElementById('pack_stock');
    const selectedCount = document.getElementById('selectedCount');
    const options = Array.from(document.querySelectorAll('.pack-edit-product'));
    const generateBtn = document.getElementById('generatePackDescription');
    const status = document.getElementById('aiDescriptionStatus');
    const description = document.getElementById('description');

    function currentPackStock() {
        return Math.max(1, parseInt(stockInput.value.replace(/\D/g, ''), 10) || 1);
    }

    function syncPackUi() {
        const packStock = currentPackStock();
        stockInput.value = packStock;
        let checked = 0;

        options.forEach(option => {
            const checkbox = option.querySelector('input[type="checkbox"]');
            const stock = parseInt(option.dataset.stock, 10) || 0;
            option.querySelector('.stockMirror').textContent = packStock;
            option.classList.toggle('is-selected', checkbox.checked);
            option.classList.toggle('is-short', stock < packStock);
            checkbox.disabled = stock < packStock;
            if (stock < packStock) {
                checkbox.checked = false;
                option.classList.remove('is-selected');
            }
            if (checkbox.checked) checked++;
        });

        selectedCount.textContent = checked;
    }

    stockInput.addEventListener('input', syncPackUi);
    options.forEach(option => option.querySelector('input[type="checkbox"]').addEventListener('change', syncPackUi));
    syncPackUi();

    generateBtn.addEventListener('click', function () {
        const selected = options
            .filter(option => option.querySelector('input[type="checkbox"]').checked)
            .map(option => ({
                name: option.dataset.name,
                category: option.dataset.category,
                stock: option.dataset.stock
            }));

        if (selected.length < 2) {
            status.textContent = 'Selectionnez au moins deux produits avant de generer une description.';
            status.style.color = '#C55A4A';
            return;
        }

        generateBtn.disabled = true;
        generateBtn.innerHTML = '<span class="loading-spinner-custom"></span>Generation...';
        status.textContent = 'Generation de la description en cours...';
        status.style.color = '';

        fetch('../../../Controllers/PackDescriptionAI.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                name: document.getElementById('nom').value.trim(),
                price: document.getElementById('prix').value.trim(),
                pack_stock: currentPackStock(),
                current_description: description.value.trim(),
                products: JSON.stringify(selected)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Generation impossible.');
            description.value = data.description;
            status.textContent = data.source === 'gemini' ? 'Description generee avec AI.' : 'Description generee localement.';
            status.style.color = '#3A6B4B';
        })
        .catch(error => {
            status.textContent = error.message || 'Erreur pendant la generation.';
            status.style.color = '#C55A4A';
        })
        .finally(() => {
            generateBtn.disabled = false;
            generateBtn.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i> Generer avec AI';
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
