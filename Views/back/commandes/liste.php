<?php
$title = "Commandes - Stabilis™";
require_once __DIR__ . '/../../partials/header.php';
require_once __DIR__ . '/../../../controllers/CommandeController.php';

$search = '';
$hasSearch = false;
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    if ($search !== '') {
        $hasSearch = true;
    }
}

$controller = new CommandeController();
$commandes = $controller->getAllGroupedForBackoffice($search);
$count = count($commandes);
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
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($count === 0): ?>
                    <tr><td colspan="8" class="text-center">Aucune commande trouvée</td></tr>
                <?php else: ?>
                    <?php foreach ($commandes as $commande): ?>
                        <tr>
                            <td>#<?php echo $commande['id']; ?></td>
                            <td><?php echo htmlspecialchars($commande['prenom'] . ' ' . $commande['nom']); ?></td>
                            <td><?php echo htmlspecialchars($commande['email']); ?></td>
                            <td><?php echo htmlspecialchars($commande['produits_resume'] ?? '-'); ?></td>
                            <td><strong><?php echo number_format((float)$commande['total'], 2); ?> €</strong></td>
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

<script>
function supprimer(id) {
    if (confirm('Souhaitez-vous vraiment supprimer cette commande ?')) {
        window.location.href = 'supprimer.php?id=' + id;
    }
}
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
