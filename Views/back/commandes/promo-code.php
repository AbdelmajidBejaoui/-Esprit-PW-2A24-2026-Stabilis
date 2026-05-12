<?php
$title = "Codes promo - Stabilis";
require_once __DIR__ . '/../../partials/header.php';
require_once __DIR__ . '/../../../controllers/ProduitController.php';

$produitController = new ProduitController();
$produits = $produitController->getAll();
?>

<div class="form-card" style="max-width: 980px; margin: 0 auto;">
    <div style="padding: 24px; border-bottom: 1px solid var(--border-light); display: flex; justify-content: space-between; gap: 16px; align-items: center; flex-wrap: wrap;">
        <div>
            <h3 style="margin: 0;">Promo CODE</h3>
            <p class="text-muted" style="margin-top: 8px;">Creez un code manuel pour tous les produits ou un produit precis.</p>
        </div>
        <a href="liste.php" class="btn-secondary"><i class="fas fa-receipt"></i> Commandes</a>
    </div>

    <div style="padding: 32px;">
        <div id="promoAlert" class="alert" style="display:none; margin-bottom: 18px;"></div>

        <form id="manualPromoForm" novalidate>
            <div class="form-group">
                <label>Application du code</label>
                <div style="display:flex; gap: 12px; flex-wrap: wrap;">
                    <label class="btn-secondary" style="cursor:pointer;"><input type="radio" name="scope" value="all" checked style="margin-right:8px;"> Tous les produits</label>
                    <label class="btn-secondary" style="cursor:pointer;"><input type="radio" name="scope" value="product" style="margin-right:8px;"> Produit specifique</label>
                </div>
            </div>

            <div class="form-group" id="productSelectGroup" style="display:none;">
                <label for="product_id">Produit</label>
                <select name="product_id" id="product_id" class="form-control">
                    <option value="">Selectionner un produit</option>
                    <?php foreach ($produits as $produit): ?>
                        <option value="<?php echo (int)$produit['id']; ?>"><?php echo htmlspecialchars($produit['nom']); ?> - <?php echo number_format((float)$produit['prix'], 2); ?> EUR</option>
                    <?php endforeach; ?>
                </select>
                <div class="error-message" id="productError"></div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 150px 150px 150px; gap: 16px;">
                <div class="form-group">
                    <label for="code">Code promo</label>
                    <div style="display:flex; gap: 10px;">
                        <input type="text" name="code" id="code" class="form-control" placeholder="STABILIS-2026" maxlength="30" style="text-transform: uppercase;">
                        <button type="button" id="generateCodeBtn" class="btn-secondary" style="white-space: nowrap;"><i class="fas fa-wand-magic-sparkles"></i> Generer</button>
                    </div>
                    <div class="hint">Lettres, chiffres et tirets uniquement.</div>
                    <div class="error-message" id="codeError"></div>
                </div>

                <div class="form-group">
                    <label for="discount">Reduction (%)</label>
                    <input type="text" name="discount" id="discount" class="form-control" inputmode="numeric" value="15">
                    <div class="error-message" id="discountError"></div>
                </div>

                <div class="form-group">
                    <label for="days">Validite (jours)</label>
                    <input type="text" name="days" id="days" class="form-control" inputmode="numeric" value="7">
                    <div class="error-message" id="daysError"></div>
                </div>

                <div class="form-group">
                    <label for="usage_limit">Limite usage</label>
                    <input type="text" name="usage_limit" id="usage_limit" class="form-control" inputmode="numeric" value="1">
                    <div class="error-message" id="usageLimitError"></div>
                </div>
            </div>

            <div style="display:flex; gap: 12px; margin-top: 12px;">
                <button type="submit" class="btn-primary" id="savePromoBtn"><i class="fas fa-save"></i> Creer le code</button>
                <button type="button" class="btn-secondary" id="refreshCodesBtn"><i class="fas fa-rotate"></i> Actualiser</button>
            </div>
        </form>
    </div>
</div>

<div class="table-card" style="margin-top: 24px;">
    <div class="table-header">
        <div>
            <h3>Codes actifs</h3>
            <div class="text-muted" style="margin-top: 8px;">Chaque code respecte sa date d'expiration, sa limite d'utilisation et une seule utilisation par client.</div>
        </div>
        <div class="record-count" id="codesCount">0 code</div>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Portee</th>
                    <th>Reduction</th>
                    <th>Utilisation</th>
                    <th>Expiration</th>
                </tr>
            </thead>
            <tbody id="codesTableBody">
                <tr><td colspan="5" class="text-center">Chargement...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<style>
@media (max-width: 860px) {
    #manualPromoForm > div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('manualPromoForm');
    const scopeInputs = form.querySelectorAll('input[name="scope"]');
    const productGroup = document.getElementById('productSelectGroup');
    const productSelect = document.getElementById('product_id');
    const codeInput = document.getElementById('code');
    const discountInput = document.getElementById('discount');
    const daysInput = document.getElementById('days');
    const alertBox = document.getElementById('promoAlert');
    const saveBtn = document.getElementById('savePromoBtn');

    function selectedScope() {
        return form.querySelector('input[name="scope"]:checked').value;
    }

    function showAlert(message, type) {
        alertBox.textContent = message;
        alertBox.style.display = 'block';
        alertBox.style.borderLeft = type === 'success' ? '4px solid #1b5e20' : '4px solid #C55A4A';
    }

    function normalizeCode(value) {
        return value.toUpperCase().replace(/[^A-Z0-9-]/g, '').slice(0, 30);
    }

    scopeInputs.forEach(input => {
        input.addEventListener('change', function () {
            productGroup.style.display = selectedScope() === 'product' ? 'block' : 'none';
        });
    });

    codeInput.addEventListener('input', function () {
        codeInput.value = normalizeCode(codeInput.value);
    });

    document.getElementById('generateCodeBtn').addEventListener('click', function () {
        const scope = selectedScope();
        const discount = Math.max(1, Math.min(90, parseInt(discountInput.value, 10) || 15));
        const selectedProduct = productSelect.options[productSelect.selectedIndex]?.text || '';
        const source = scope === 'product' && selectedProduct ? selectedProduct : 'STABILIS';
        const prefix = normalizeCode(source.split(' ')[0] || 'STABILIS').slice(0, 8) || 'STABILIS';
        const random = Math.random().toString(36).toUpperCase().replace(/[^A-Z0-9]/g, '').slice(2, 7);
        codeInput.value = `${prefix}-${discount}-${random}`;
    });

    function validateForm() {
        let valid = true;
        document.querySelectorAll('.error-message').forEach(el => el.textContent = '');

        if (codeInput.value.length < 4) {
            document.getElementById('codeError').textContent = 'Le code doit contenir au moins 4 caracteres.';
            valid = false;
        }

        const discount = parseInt(discountInput.value, 10);
        if (Number.isNaN(discount) || discount < 1 || discount > 90) {
            document.getElementById('discountError').textContent = 'Reduction entre 1 et 90%.';
            valid = false;
        }

        const days = parseInt(daysInput.value, 10);
        if (Number.isNaN(days) || days < 1 || days > 365) {
            document.getElementById('daysError').textContent = 'Validite entre 1 et 365 jours.';
            valid = false;
        }

        const usageLimit = parseInt(document.getElementById('usage_limit').value, 10);
        if (Number.isNaN(usageLimit) || usageLimit < 1 || usageLimit > 1000) {
            document.getElementById('usageLimitError').textContent = 'Limite entre 1 et 1000.';
            valid = false;
        }

        if (selectedScope() === 'product' && !productSelect.value) {
            document.getElementById('productError').textContent = 'Selectionnez un produit.';
            valid = false;
        }

        return valid;
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!validateForm()) {
            return;
        }

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="loading-spinner-custom"></span> Creation...';

        fetch('../../../Controllers/PromoCodeHandler.php?action=create_manual_code', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(new FormData(form))
        })
            .then(response => response.json())
            .then(data => {
                showAlert(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    form.reset();
                    productGroup.style.display = 'none';
                    loadCodes();
                }
            })
            .catch(error => showAlert('Erreur reseau: ' + error.message, 'error'))
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save"></i> Creer le code';
            });
    });

    function loadCodes() {
        fetch('../../../Controllers/PromoCodeHandler.php?action=get_active_codes')
            .then(response => response.json())
            .then(data => {
                const body = document.getElementById('codesTableBody');
                const count = data.count || 0;
                document.getElementById('codesCount').textContent = count + ' code' + (count > 1 ? 's' : '');

                if (!data.success || count === 0) {
                    body.innerHTML = '<tr><td colspan="5" class="text-center">Aucun code actif</td></tr>';
                    return;
                }

                body.innerHTML = data.codes.map(code => {
                    const scope = code.product_id ? (code.product_name || 'Produit #' + code.product_id) : 'Tous les produits';
                    const usage = parseInt(code.usage_limit, 10) === 0 ? `${code.times_used || 0} utilisation(s)` : `${code.times_used || 0}/${code.usage_limit || 1}`;
                    return `<tr>
                        <td><strong>${code.code}</strong></td>
                        <td>${scope}</td>
                        <td><span class="badge badge-aliment">-${code.discount}%</span></td>
                        <td>${usage}</td>
                        <td>${new Date(code.expires_at.replace(' ', 'T')).toLocaleDateString('fr-FR')}</td>
                    </tr>`;
                }).join('');
            })
            .catch(() => {
                document.getElementById('codesTableBody').innerHTML = '<tr><td colspan="5" class="text-center">Impossible de charger les codes</td></tr>';
            });
    }

    document.getElementById('refreshCodesBtn').addEventListener('click', loadCodes);
    loadCodes();
});
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
