<?php
require_once __DIR__ . '/../../../controllers/CommandeController.php';

$controller = new CommandeController();
$errors = [];
$success = false;
$info = '';

if (!isset($_GET['id'])) {
    header('Location: liste.php');
    exit();
}

$id = intval($_GET['id']);
$commande = $controller->getGroupedOrderByIdForBackoffice($id);
$commandeLignes = $controller->getOrderLinesForGroupById($id);

if (!$commande || empty($commandeLignes)) {
    header('Location: liste.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['statut'])) {
    $statut = trim($_POST['statut']);

    if ($statut === trim((string)$commande['statut'])) {
        $info = 'Le statut sélectionné est identique au statut actuel.';
    } elseif ($controller->updateStatusForGroupById($id, $statut)) {
        $success = true;
        $commande = $controller->getGroupedOrderByIdForBackoffice($id);
        $commandeLignes = $controller->getOrderLinesForGroupById($id);
    } else {
        $errors['statut'] = 'Impossible de mettre à jour le statut.';
    }
}

$title = 'Commande groupée #' . $commande['id'] . ' - Stabilis™';
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="form-card" style="max-width: 1100px; margin: 0 auto;">
    <div style="padding: 24px; border-bottom: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center; gap: 16px;">
        <div>
            <h3 style="margin: 0;">Commande groupée #<?php echo $commande['id']; ?></h3>
            <p class="text-muted" style="margin-top: 8px;">Détails du client et des produits commandés.</p>
        </div>
        <a href="liste.php" class="btn-secondary">Retour à la liste</a>
    </div>
    <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 24px; padding: 32px;">
        <div>
            <div class="form-group">
                <label>Client</label>
                <div><?php echo htmlspecialchars($commande['prenom'] . ' ' . $commande['nom']); ?></div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <div><?php echo htmlspecialchars($commande['email']); ?></div>
            </div>
            <div class="form-group">
                <label>Téléphone</label>
                <div><?php echo htmlspecialchars($commande['telephone']); ?></div>
            </div>
            <div class="form-group">
                <label>Adresse de livraison</label>
                <div><?php echo nl2br(htmlspecialchars($commande['adresse'] . '\n' . $commande['code_postal'] . ' ' . $commande['ville'] . ' - ' . $commande['pays'])); ?></div>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <div><?php echo nl2br(htmlspecialchars($commande['notes'] ?? 'Aucune note.')); ?></div>
            </div>

            <div style="margin-top: 24px;">
                <h4 style="margin-bottom: 16px;">Produits commandés</h4>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Prix unitaire</th>
                                <th>Quantité</th>
                                <th>Total ligne</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commandeLignes as $ligne): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ligne['produit_nom']); ?></td>
                                    <td><?php echo number_format((float)$ligne['produit_prix'], 2); ?> €</td>
                                    <td><?php echo (int)$ligne['quantite']; ?></td>
                                    <td><?php echo number_format((float)$ligne['produit_prix'] * (int)$ligne['quantite'], 2); ?> €</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div style="background: var(--bg-elevated); border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow-sm);">
            <?php if ($success): ?>
                <div class="alert" style="background: #e6f4ea; color: #2d6a3a;">Le statut a bien été mis à jour.</div>
            <?php endif; ?>
            <?php if ($info !== ''): ?>
                <div class="alert" style="background: #e9ecef; color: #495057;"><?php echo htmlspecialchars($info); ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label>Date de commande</label>
                <div><?php echo htmlspecialchars($commande['date_commande']); ?></div>
            </div>
            <div class="form-group">
                <label>Statut actuel</label>
                <div><?php echo htmlspecialchars($commande['statut']); ?></div>
            </div>
            <div class="form-group">
                <label>Total</label>
                <div><strong><?php echo number_format($commande['total'], 2); ?> €</strong></div>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Nouveau statut</label>
                    <select name="statut" class="form-control">
                        <option value="En attente"<?php echo $commande['statut'] === 'En attente' ? ' selected' : ''; ?>>En attente</option>
                        <option value="Validée"<?php echo $commande['statut'] === 'Validée' ? ' selected' : ''; ?>>Validée</option>
                        <option value="Expédiée"<?php echo $commande['statut'] === 'Expédiée' ? ' selected' : ''; ?>>Expédiée</option>
                        <option value="Annulée"<?php echo $commande['statut'] === 'Annulée' ? ' selected' : ''; ?>>Annulée</option>
                    </select>
                </div>
                <?php if (!empty($errors['statut'])): ?>
                    <div class="error-message"><?php echo $errors['statut']; ?></div>
                <?php endif; ?>
                <button type="submit" class="btn-primary" style="width:100%;">Mettre à jour le statut</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>

