<?php
$title = "Commandes - Stabilis™";
require_once __DIR__ . '/../../partials/header.php';
require_once __DIR__ . '/../../../controllers/CommandeController.php';

$search = '';
$sort = $_GET['sort'] ?? 'recent';
$hasSearch = false;
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    if ($search !== '') {
        $hasSearch = true;
    }
}

$controller = new CommandeController();
$commandes = $controller->getAllGroupedForBackoffice($search, $sort);
$count = count($commandes);
$sortOptions = [
    'recent' => 'Plus recentes',
    'oldest' => 'Plus anciennes',
    'client_asc' => 'Client A-Z',
    'client_desc' => 'Client Z-A',
    'total_asc' => 'Total croissant',
    'total_desc' => 'Total decroissant',
    'status_asc' => 'Statut'
];
?>

<div class="table-card">
    <div class="table-header">
        <div>
            <h3>Liste des commandes</h3>
            <?php if ($hasSearch): ?>
                <div class="text-muted" style="margin-top: 8px;">Résultats pour "<?php echo htmlspecialchars($search); ?>"</div>
            <?php endif; ?>
        </div>
        <form method="GET" class="search-bar">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Rechercher par client, email ou statut" class="form-control">
            <select name="sort" class="form-control">
                <?php foreach ($sortOptions as $value => $label): ?>
                    <option value="<?php echo htmlspecialchars($value); ?>"<?php echo $sort === $value ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-secondary">Rechercher</button>
        </form>
        <div class="record-count">
            <?php echo $count . ' commande'; if ($count > 1) echo 's'; ?>
        </div>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Email</th>
                    <th>Produits</th>
                    <th>Total</th>
                    <th>Réduction</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($count === 0): ?>
                    <tr><td colspan="9" class="text-center">Aucune commande trouvée</td></tr>
                <?php else: ?>
                    <?php foreach ($commandes as $commande): ?>
                        <tr>
                            <td>#<?php echo $commande['id']; ?></td>
                            <td><?php echo htmlspecialchars($commande['prenom'] . ' ' . $commande['nom']); ?></td>
                            <td><?php echo htmlspecialchars($commande['email']); ?></td>
                            <td><?php echo htmlspecialchars($commande['produits_resume'] ?? '-'); ?></td>
                            <td><strong><?php echo number_format((float)$commande['total'], 2); ?> €</strong></td>
                            <td>
                                <?php if (!empty($commande['discount_percent'])): ?>
                                    <span style="color: #1b5e20; font-weight: 600;">-<?php echo $commande['discount_percent']; ?>%</span>
                                    <br/><small style="color: #666;">-<?php echo number_format((float)($commande['discount_amount'] ?? 0), 2); ?> €</small>
                                    <br/><strong style="color: #1b5e20;"><?php echo number_format((float)($commande['final_total'] ?? $commande['total']), 2); ?> €</strong>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $commande['statut'] === 'Validée' ? 'badge-aliment' : 'badge-compensation'; ?>">
                                    <?php echo htmlspecialchars($commande['statut']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($commande['date_commande']); ?></td>
                            <td>
                                <a href="voir.php?id=<?php echo $commande['id']; ?>" class="btn-icon"><i class="fas fa-eye"></i> <span>Voir</span></a>
                                <a href="javascript:void(0)" onclick="supprimer(<?php echo $commande['id']; ?>)" class="btn-icon btn-icon-danger"><i class="fas fa-trash"></i> <span>Supprimer</span></a>
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
        <h3>Supprimer la commande</h3>
        <p>Souhaitez-vous vraiment supprimer cette commande ?</p>
        <div class="delete-modal-actions">
            <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Annuler</button>
            <button type="button" class="btn-danger-custom" onclick="confirmDelete()">Supprimer</button>
        </div>
    </div>
</div>

<style>
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
let deleteCommandeId = null;

function supprimer(id) {
    deleteCommandeId = id;
    document.getElementById('delete-modal').style.display = 'flex';
}

function closeDeleteModal() {
    deleteCommandeId = null;
    document.getElementById('delete-modal').style.display = 'none';
}

function confirmDelete() {
    if (deleteCommandeId) {
        window.location.href = 'supprimer.php?id=' + deleteCommandeId;
    }
}
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
