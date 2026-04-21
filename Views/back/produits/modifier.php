<?php
require_once __DIR__ . '/../../../controllers/ProduitController.php';

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
    'description' => $product['description'],
    'stock' => $product['stock'],
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
    if (isset($_POST['description'])) {
        $values['description'] = trim($_POST['description']);
    }
    if (isset($_POST['stock'])) {
        $values['stock'] = $_POST['stock'];
    }
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

    if(empty($errors)) {
        $produit = new Produit(
            $values['nom'],
            floatval($values['prix']),
            $values['description'],
            intval($values['stock']),
            $values['categorie'],
            $values['image_url']
        );

        if($controller->update($id, $produit)) {
            header('Location: liste.php?updated=1');
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
                <input type="number" step="0.01" name="prix" id="prix" class="form-control" placeholder="49.99" value="<?php echo htmlspecialchars($values['prix']); ?>">
                <div id="prixError" class="error-message"><?php if (isset($errors['prix'])) { echo $errors['prix']; } ?></div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3" placeholder="Description du produit..."><?php echo htmlspecialchars($values['description']); ?></textarea>
                <div class="hint">Décrivez les bénéfices clairs du produit.</div>
                <div class="error-message"><?php if (isset($errors['description'])) { echo $errors['description']; } ?></div>
            </div>

            <div class="form-group">
                <label for="stock">Stock</label>
                <input type="number" name="stock" id="stock" class="form-control" value="<?php echo htmlspecialchars($values['stock']); ?>">
                <div id="stockError" class="error-message"><?php if (isset($errors['stock'])) { echo $errors['stock']; } ?></div>
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

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
