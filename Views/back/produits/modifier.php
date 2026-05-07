<?php
require_once __DIR__ . '/../../../controllers/ProduitController.php';
require_once __DIR__ . '/../../../controllers/WishlistController.php';

$controller = new ProduitController();
$errors = [];

$id = null;
if (isset($_GET['id'])) {
    $id = $_GET['id'];
}

if (!$id) {
    header('Location: liste.php');
    exit();
}
$product = $controller->getById($id);
if (!$product) {
    header('Location: liste.php');
    exit();
}

$values = [
    'nom' => $product['nom'],
    'prix' => $product['prix'],
    'promo_prix' => $product['promo_prix'] ?? '',
    'description' => $product['description'],
    'stock' => $product['stock'],
    'coming_soon' => (string)($product['coming_soon'] ?? 0),
    'categorie' => $product['categorie'],
    'image_url' => $product['image_url'],
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['nom'])) {
        $values['nom'] = trim($_POST['nom']);
    }
    if (isset($_POST['prix'])) {
        $values['prix'] = $_POST['prix'];
    }
    if (isset($_POST['promo_prix'])) {
        $values['promo_prix'] = $_POST['promo_prix'];
    }
    if (isset($_POST['description'])) {
        $values['description'] = trim($_POST['description']);
    }
    if (isset($_POST['stock'])) {
        $values['stock'] = $_POST['stock'];
    }
    $values['coming_soon'] = isset($_POST['coming_soon']) ? '1' : '0';
    if (isset($_POST['categorie'])) {
        $values['categorie'] = $_POST['categorie'];
    }
    $values['image_url'] = $product['image_url'];

    if ($controller->validateData($values, $errors)) {
        $upload = $controller->saveImage(isset($_FILES['image']) ? $_FILES['image'] : []);
        if ($upload === false) {
            $errors['image'] = 'Format de fichier non pris en charge. Utilisez JPG, PNG ou WEBP.';
        } elseif ($upload !== null) {
            $values['image_url'] = $upload;
        }
    }

    if (empty($errors)) {
        $produit = new Produit(
            $values['nom'],
            floatval($values['prix']),
            $values['description'],
            intval($values['stock']),
            $values['categorie'],
            $values['image_url'],
            $values['promo_prix'] !== '' ? floatval($values['promo_prix']) : null,
            intval($values['coming_soon'])
        );

        if($controller->update($id, $produit)) {
            $newStock = (int)$values['stock'];
            $notifyResult = ['sent' => 0, 'failed' => 0, 'total' => 0];

            if ($newStock > 0) {
                $wishlistController = new WishlistController();
                $updatedProduct = $controller->getById($id);
                if ($updatedProduct) {
                    $notifyResult = $wishlistController->notifyProductAvailable($updatedProduct);
                }
            }

            $notificationQuery = '';
            if ($notifyResult['total'] > 0) {
                $notificationQuery = '&wishlist_total=' . (int)$notifyResult['total'] . '&wishlist_sent=' . (int)$notifyResult['sent'] . '&wishlist_failed=' . (int)$notifyResult['failed'];
            }

            header('Location: liste.php?updated=1' . $notificationQuery);
            exit();
        }

        $errors['general'] = 'Impossible de mettre à jour le produit.';
    }
}

$title = "Modifier un produit - Stabilis™";
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="form-card" style="max-width: 700px; margin: 0 auto;">
    <div style="padding: 24px; border-bottom: 1px solid var(--border-light);">
        <h3 style="margin: 0;">Modifier le produit</h3>
        <p class="text-muted" style="margin-top: 8px;">Mettez à jour les informations du produit.</p>
    </div>

    <div style="padding: 32px;">
        <?php if(!empty($errors['general'])): ?>
            <div class="alert"><?php echo $errors['general']; ?></div>
        <?php endif; ?>

        <form method="POST" id="productForm" enctype="multipart/form-data" novalidate>
            <div class="form-group">
                <label for="nom">Nom du produit</label>
                <input type="text" name="nom" id="nom" class="form-control" placeholder="Whey Protein Isolat" value="<?php echo htmlspecialchars($values['nom']); ?>">
                <div id="nomError" class="error-message"><?php if (isset($errors['nom'])) { echo $errors['nom']; } ?></div>
            </div>

            <div class="form-group">
                <label for="prix">Prix (€)</label>
                <input type="text" name="prix" id="prix" class="form-control" placeholder="49.99" inputmode="decimal" value="<?php echo htmlspecialchars($values['prix']); ?>">
                <div id="prixError" class="error-message"><?php if (isset($errors['prix'])) { echo $errors['prix']; } ?></div>
            </div>

            <div class="form-group">
                <label for="promo_prix">Prix promo (€)</label>
                <input type="text" name="promo_prix" id="promo_prix" class="form-control" placeholder="Ex: 7.99" inputmode="decimal" value="<?php echo htmlspecialchars($values['promo_prix']); ?>">
                <div class="hint">Laissez vide pour retirer la promotion. Le prix promo doit etre inferieur au prix actuel.</div>
                <div id="promoPrixError" class="error-message"><?php if (isset($errors['promo_prix'])) { echo $errors['promo_prix']; } ?></div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <div style="display: flex; gap: 10px; align-items: flex-start;">
                    <textarea name="description" id="description" class="form-control" rows="3" placeholder="Description du produit..."><?php echo htmlspecialchars($values['description']); ?></textarea>
                    <button type="button" id="generateDescriptionBtn" class="btn-secondary" style="white-space: nowrap;">
                        <i class="fas fa-wand-magic-sparkles"></i> Generer
                    </button>
                </div>
                <div class="hint">Décrivez les bénéfices clairs du produit.</div>
                <div id="descriptionAiStatus" class="hint" style="display: none;"></div>
                <div class="error-message"><?php if (isset($errors['description'])) { echo $errors['description']; } ?></div>
            </div>

            <div class="form-group">
                <label for="stock">Stock</label>
                <input type="text" name="stock" id="stock" class="form-control" inputmode="numeric" value="<?php echo htmlspecialchars($values['stock']); ?>">
                <div id="stockError" class="error-message"><?php if (isset($errors['stock'])) { echo $errors['stock']; } ?></div>
            </div>

            <div class="form-group">
                <label style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="coming_soon" value="1"<?php echo $values['coming_soon'] === '1' ? ' checked' : ''; ?>>
                    Coming soon / disponible en pre-commande
                </label>
                <div class="hint">Decochez quand le produit est officiellement disponible.</div>
                <div class="error-message"><?php if (isset($errors['coming_soon'])) { echo $errors['coming_soon']; } ?></div>
            </div>

            <div class="form-group">
                <label for="categorie">Catégorie</label>
                <select name="categorie" id="categorie" class="form-control">
                    <option value="">Sélectionner</option>
                    <option value="Protéines"<?php if ($values['categorie'] === 'Protéines') { echo ' selected'; } ?>>Protéines</option>
                    <option value="Acides Aminés"<?php if ($values['categorie'] === 'Acides Aminés') { echo ' selected'; } ?>>Acides Aminés</option>
                    <option value="Pré-workout"<?php if ($values['categorie'] === 'Pré-workout') { echo ' selected'; } ?>>Pré-workout</option>
                    <option value="Vitamines"<?php if ($values['categorie'] === 'Vitamines') { echo ' selected'; } ?>>Vitamines</option>
                    <option value="Snacks"<?php if ($values['categorie'] === 'Snacks') { echo ' selected'; } ?>>Snacks</option>
                    <option value="Accessoires"<?php if ($values['categorie'] === 'Accessoires') { echo ' selected'; } ?>>Accessoires</option>
                </select>
                <div id="categorieError" class="error-message"><?php if (isset($errors['categorie'])) { echo $errors['categorie']; } ?></div>
            </div>

            <div class="form-group">
                <label>Image actuelle</label>
                <?php
                $previewImage = 'default-product.png';
                if (!empty($values['image_url'])) {
                    $previewImage = $values['image_url'];
                }
                ?>
                <div style="display:flex; align-items:center; gap:12px;">
                    <img src="/AdminLTE3/dist/img/<?php echo htmlspecialchars($previewImage); ?>" class="product-img" onerror="this.src='/AdminLTE3/dist/img/default-product.png'">
                    <span class="text-muted">Conservez cette image ou choisissez-en une nouvelle.</span>
                </div>
            </div>

            <div class="form-group">
                <label for="image">Modifier l'image du produit</label>
                <input type="file" accept="image/png,image/jpeg,image/webp" name="image" id="image" class="form-control">
                <div class="hint">Sélectionnez une nouvelle image JPG, PNG ou WEBP.</div>
                <div class="error-message"><?php if (isset($errors['image'])) { echo $errors['image']; } ?></div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 32px;">
                <button type="submit" id="submitBtn" class="btn-primary">
                    <span id="btnText">Enregistrer</span>
                </button>
                <a href="liste.php" class="btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const generateBtn = document.getElementById('generateDescriptionBtn');
    const nameInput = document.getElementById('nom');
    const categoryInput = document.getElementById('categorie');
    const descriptionInput = document.getElementById('description');
    const aiStatus = document.getElementById('descriptionAiStatus');
    const priceInput = document.getElementById('prix');
    const promoPriceInput = document.getElementById('promo_prix');
    const stockInput = document.getElementById('stock');
    const promoPriceError = document.getElementById('promoPrixError');

    function validatePromoPrice() {
        if (!priceInput || !promoPriceInput || !promoPriceError) {
            return true;
        }

        const price = parseFloat(String(priceInput.value).replace(',', '.'));
        const promoPrice = parseFloat(String(promoPriceInput.value).replace(',', '.'));

        if (!promoPriceInput.value.trim()) {
            promoPriceError.textContent = '';
            return true;
        }

        if (Number.isNaN(promoPrice) || promoPrice <= 0) {
            promoPriceError.textContent = 'Le prix promo doit etre un nombre positif.';
            return false;
        }

        if (!Number.isNaN(price) && promoPrice >= price) {
            promoPriceError.textContent = 'Le prix promo doit etre inferieur au prix actuel.';
            return false;
        }

        promoPriceError.textContent = '';
        return true;
    }

    if (priceInput && promoPriceInput) {
        priceInput.addEventListener('input', validatePromoPrice);
        promoPriceInput.addEventListener('input', validatePromoPrice);
        document.getElementById('productForm').addEventListener('submit', function (event) {
            if (!validatePromoPrice()) {
                event.preventDefault();
                promoPriceInput.focus();
            }
        });
    }

    if (generateBtn && nameInput && descriptionInput && aiStatus) {
        generateBtn.addEventListener('click', async function () {
            const productName = nameInput.value.trim();

            if (!productName) {
                aiStatus.style.display = 'flex';
                aiStatus.style.color = '#C55A4A';
                aiStatus.textContent = 'Veuillez entrer le nom du produit avant de generer une description.';
                nameInput.focus();
                return;
            }

            const originalText = generateBtn.innerHTML;
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<span class="loading-spinner-custom"></span> Generation...';
            aiStatus.style.display = 'flex';
            aiStatus.style.color = 'var(--text-muted)';
            aiStatus.textContent = 'Generation de la description avec IA...';

            try {
                const formData = new FormData();
                formData.append('name', productName);
                formData.append('category', categoryInput ? categoryInput.value : '');
                formData.append('price', priceInput ? priceInput.value : '');
                formData.append('promo_price', promoPriceInput ? promoPriceInput.value : '');
                formData.append('stock', stockInput ? stockInput.value : '');
                formData.append('current_description', descriptionInput ? descriptionInput.value : '');

                const response = await fetch('../../../Controllers/ProductDescriptionAI.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Impossible de generer la description.');
                }

                descriptionInput.value = data.description;
                if (data.source === 'gemini') {
                    aiStatus.style.color = 'var(--accent-herb)';
                    aiStatus.textContent = 'Description generee avec Gemini.';
                } else {
                    aiStatus.style.color = '#B0873A';
                    aiStatus.textContent = 'Description generee en fallback local' + (data.ai_error ? ' (' + data.ai_error + ')' : '') + '.';
                }
            } catch (error) {
                aiStatus.style.color = '#C55A4A';
                aiStatus.textContent = error.message;
            } finally {
                generateBtn.disabled = false;
                generateBtn.innerHTML = originalText;
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
