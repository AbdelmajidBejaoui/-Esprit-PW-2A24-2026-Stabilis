<?php
require_once __DIR__ . '/../../../controllers/ProduitController.php';

$controller = new ProduitController();
$errors = [];
$values = [
    'nom' => '',
    'prix' => '',
    'description' => '',
    'stock' => '0',
    'coming_soon' => '0',
    'categorie' => '',
    'image_url' => null,
    'mail_mode' => 'promo',
    'promo_audience' => 'all',
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
    $values['coming_soon'] = isset($_POST['coming_soon']) ? '1' : '0';
    if (isset($_POST['categorie'])) {
        $values['categorie'] = $_POST['categorie'];
    }
    $values['mail_mode'] = $_POST['mail_mode'] ?? 'promo';
    $values['promo_audience'] = $_POST['promo_audience'] ?? 'all';

    if ($controller->validateData($values, $errors)) {
        $upload = $controller->saveImage(isset($_FILES['image']) ? $_FILES['image'] : []);
        if ($upload === false) {
            $errors['image'] = 'Format de fichier non pris en charge. Utilisez JPG, PNG ou WEBP.';
        } else {
            $values['image_url'] = $upload;
        }
    }

    if (count($errors) == 0) {
        $produit = new Produit(
            $values['nom'],
            floatval($values['prix']),
            $values['description'],
            intval($values['stock']),
            $values['categorie'],
            $values['image_url'],
            null,
            intval($values['coming_soon'])
        );

        if ($controller->add($produit, ['mode' => $values['mail_mode'], 'audience' => $values['promo_audience']])) {
            header('Location: liste.php?success=1');
            exit();
        }

        $errors['general'] = 'Une erreur est survenue lors de l’ajout du produit.';
    }
}

$title = "Ajouter un produit - Stabilis";
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="form-card" style="max-width: 700px; margin: 0 auto;">
    <div style="padding: 24px; border-bottom: 1px solid var(--border-light);">
        <h3 style="margin: 0;">Nouveau produit</h3>
        <p class="text-muted" style="margin-top: 8px;">Ajoutez un produit à votre catalogue.</p>
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
                <label for="description">Description</label>
                <div style="display: flex; gap: 10px; align-items: flex-start;">
                    <textarea name="description" id="description" class="form-control" rows="3" placeholder="Description du produit..."><?php echo htmlspecialchars($values['description']); ?></textarea>
                    <button type="button" id="generateDescriptionBtn" class="btn-secondary" style="white-space: nowrap;">
                        <i class="fas fa-wand-magic-sparkles"></i> Generer
                    </button>
                </div>
                <div class="hint">Décrivez les bénéfices du produit.</div>
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
                <div class="hint">Activez cette option si le produit n'est pas encore lance mais peut etre pre-commande.</div>
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
                <label for="image">Image du produit</label>
                <input type="file" accept="image/png,image/jpeg,image/webp" name="image" id="image" class="form-control">
                <div class="hint">Choisissez une image au format JPG, PNG ou WEBP. Le système se charge du stockage.</div>
                <div class="error-message"><?php if (isset($errors['image'])) { echo $errors['image']; } ?></div>
            </div>
            
            <div class="form-group">
                <label for="mail_mode">Email apres ajout</label>
                <select name="mail_mode" id="mail_mode" class="form-control">
                    <option value="promo"<?php echo $values['mail_mode'] === 'promo' ? ' selected' : ''; ?>>Annonce avec code promo</option>
                    <option value="announcement"<?php echo $values['mail_mode'] === 'announcement' ? ' selected' : ''; ?>>Annonce simple sans code promo</option>
                    <option value="none"<?php echo $values['mail_mode'] === 'none' ? ' selected' : ''; ?>>Ne pas envoyer d'email</option>
                </select>
            </div>

            <div class="form-group" id="audienceGroup">
                <label for="promo_audience">Destinataires</label>
                <select name="promo_audience" id="promo_audience" class="form-control">
                    <option value="all"<?php echo $values['promo_audience'] === 'all' ? ' selected' : ''; ?>>Tous les clients</option>
                    <option value="new"<?php echo $values['promo_audience'] === 'new' ? ' selected' : ''; ?>>Nouveaux clients</option>
                    <option value="normal"<?php echo $values['promo_audience'] === 'normal' ? ' selected' : ''; ?>>Clients normaux</option>
                    <option value="loyal"<?php echo $values['promo_audience'] === 'loyal' ? ' selected' : ''; ?>>Clients fideles</option>
                </select>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 32px;">
                <button type="submit" id="submitBtn" class="btn-primary">
                    <span id="btnText">Ajouter le produit</span>
                </button>
                <a href="liste.php" class="btn-secondary">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('productForm');
    const button = document.getElementById('submitBtn');
    const text = document.getElementById('btnText');
    const generateBtn = document.getElementById('generateDescriptionBtn');
    const nameInput = document.getElementById('nom');
    const categoryInput = document.getElementById('categorie');
    const priceInput = document.getElementById('prix');
    const stockInput = document.getElementById('stock');
    const descriptionInput = document.getElementById('description');
    const aiStatus = document.getElementById('descriptionAiStatus');
    const mailMode = document.getElementById('mail_mode');
    const audienceGroup = document.getElementById('audienceGroup');

    function toggleAudience() {
        if (mailMode && audienceGroup) {
            audienceGroup.style.display = mailMode.value === 'none' ? 'none' : 'block';
        }
    }

    if (mailMode) {
        mailMode.addEventListener('change', toggleAudience);
        toggleAudience();
    }

    if (form && button) {
        form.addEventListener('submit', function () {
            button.disabled = true;
            if (text) {
                text.textContent = 'Ajout en cours...';
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
