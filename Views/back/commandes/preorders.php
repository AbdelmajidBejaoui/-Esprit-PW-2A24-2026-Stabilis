<?php
$title = "Pre-commandes - Stabilis";
require_once __DIR__ . '/../../partials/header.php';
require_once __DIR__ . '/../../../controllers/CommandeController.php';

$controller = new CommandeController();
$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($id > 0 && $action === 'notify_ready') {
        if ($controller->sendPreOrderReadyEmail($id)) {
            $controller->updateStatus($id, 'En attente');
            if ($controller->getLastMailTransport() === 'smtp') {
                $message = 'Email envoye au client. La pre-commande est maintenant en attente de traitement.';
                $messageType = 'success';
            } else {
                $message = 'SMTP indisponible: email sauvegarde dans storage/mail_logs. La pre-commande est maintenant en attente de traitement.';
                $messageType = 'info';
            }
        } else {
            $message = 'Impossible d envoyer l email. Verifiez que le stock est disponible et que le produit n est plus coming soon.';
            $messageType = 'error';
        }
    }

    if ($id > 0 && $action === 'cancel') {
        if ($controller->updateStatus($id, 'Annulee')) {
            $message = 'Pre-commande annulee.';
            $messageType = 'success';
        } else {
            $message = 'Impossible d annuler la pre-commande.';
            $messageType = 'error';
        }
    }
}

$preOrders = $controller->getPreOrders();
$readyCount = count(array_filter($preOrders, function ($order) {
    return (int)($order['is_ready'] ?? 0) === 1;
}));
?>

<div class="table-card">
    <div class="table-header">
        <div>
            <h3>Pre-commandes</h3>
            <div class="text-muted" style="margin-top: 8px;">Traitez les demandes les plus anciennes en premier quand le stock arrive.</div>
        </div>
        <div class="record-count"><?php echo count($preOrders); ?> pre-commande<?php echo count($preOrders) > 1 ? 's' : ''; ?></div>
    </div>

    <?php if ($message !== ''): ?>
        <div style="padding: 0 24px 18px;">
            <div class="preorder-alert preorder-alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        </div>
    <?php endif; ?>

    <div style="padding: 0 24px 18px; display:flex; gap:12px; flex-wrap:wrap;">
        <span class="preorder-pill">Pret a notifier: <?php echo $readyCount; ?></span>
        <span class="preorder-pill">Priorite: date la plus ancienne</span>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Priorite</th>
                    <th>Client</th>
                    <th>Email</th>
                    <th>Produit</th>
                    <th>Quantite</th>
                    <th>Stock</th>
                    <th>Statut produit</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($preOrders)): ?>
                    <tr><td colspan="9" class="text-center">Aucune pre-commande.</td></tr>
                <?php else: ?>
                    <?php foreach ($preOrders as $index => $order): ?>
                        <tr>
                            <td>#<?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($order['prenom'] . ' ' . $order['nom']); ?></td>
                            <td><?php echo htmlspecialchars($order['email']); ?></td>
                            <td><?php echo htmlspecialchars($order['produit_nom']); ?></td>
                            <td><?php echo (int)$order['quantite']; ?></td>
                            <td><?php echo (int)$order['stock']; ?></td>
                            <td>
                                <?php if ((int)$order['is_ready'] === 1): ?>
                                    <span class="badge badge-aliment">Disponible</span>
                                <?php elseif ((int)($order['coming_soon'] ?? 0) === 1): ?>
                                    <span class="badge badge-compensation">Coming soon</span>
                                <?php else: ?>
                                    <span class="badge badge-compensation">Stock insuffisant</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($order['date_commande']); ?></td>
                            <td style="display:flex; gap:8px; flex-wrap:wrap;">
                                <a href="voir.php?id=<?php echo (int)$order['id']; ?>" class="btn-icon"><i class="fas fa-eye"></i> Voir</a>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="id" value="<?php echo (int)$order['id']; ?>">
                                    <input type="hidden" name="action" value="notify_ready">
                                    <button type="submit" class="btn-icon" <?php echo (int)$order['is_ready'] === 1 ? '' : 'disabled'; ?>>
                                        <i class="fas fa-envelope"></i> Produit pret
                                    </button>
                                </form>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="id" value="<?php echo (int)$order['id']; ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="btn-icon btn-icon-danger" onclick="return confirm('Annuler cette pre-commande ?')">
                                        <i class="fas fa-times"></i> Annuler
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.preorder-pill {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    background: #F9F3E6;
    color: #8A6425;
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 700;
}
.preorder-alert {
    padding: 12px 14px;
    border-radius: 10px;
    background: #f0f7fb;
    color: #315266;
}
.preorder-alert-success { background: #e6f4ea; color: #2d6a3a; }
.preorder-alert-error { background: #fef2f0; color: #C55A4A; }
button.btn-icon:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}
</style>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
