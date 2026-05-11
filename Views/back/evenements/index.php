<?php
$title = 'Evenements - Stabilis';
require_once __DIR__ . '/../../partials/header.php';
require_once __DIR__ . '/../../../Controllers/EventController.php';

$controller = new EventController();
$errors = [];
$values = ['titre' => '', 'message' => '', 'code_promo' => '', 'bg_color' => '#F9F3E6', 'active' => '1'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'create') {
        foreach ($values as $field => $default) {
            $values[$field] = trim($_POST[$field] ?? '');
        }
        $customColor = trim($_POST['bg_color_custom'] ?? '');
        if ($customColor !== '') {
            $values['bg_color'] = $customColor;
        }
        $values['active'] = isset($_POST['active']) ? '1' : '0';
        if ($controller->add($values, $errors)) {
            header('Location: index.php?created=1');
            exit();
        }
    } elseif ($action === 'activate' && $id > 0) {
        $controller->setActive($id, 1);
        header('Location: index.php?updated=1');
        exit();
    } elseif ($action === 'disable' && $id > 0) {
        $controller->setActive($id, 0);
        header('Location: index.php?updated=1');
        exit();
    } elseif ($action === 'delete' && $id > 0) {
        $controller->delete($id);
        header('Location: index.php?deleted=1');
        exit();
    }
}

$events = $controller->getAll();
?>

<div class="event-layout">
    <div class="form-card event-form">
        <div class="event-card-header">
            <h3>Nouvel evenement</h3>
        </div>
        <div class="event-card-body">
            <form method="POST" novalidate>
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label>Titre</label>
                    <input class="form-control" name="titre" value="<?php echo htmlspecialchars($values['titre']); ?>" placeholder="CODE PROMO -20%">
                    <div class="error-message"><?php echo htmlspecialchars($errors['titre'] ?? ''); ?></div>
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea class="form-control" name="message" rows="4" placeholder="20% sur tous les produits avec le code STABILIS20"><?php echo htmlspecialchars($values['message']); ?></textarea>
                    <div class="error-message"><?php echo htmlspecialchars($errors['message'] ?? ''); ?></div>
                </div>
                <div class="event-grid">
                    <div class="form-group">
                        <label>Code promo</label>
                        <input class="form-control" name="code_promo" value="<?php echo htmlspecialchars($values['code_promo']); ?>" placeholder="STABILIS20">
                    </div>
                    <div class="form-group">
                        <label>Couleur</label>
                        <div class="event-color-row">
                            <label class="event-swatch" style="--swatch:#F9F3E6;"><input type="radio" name="bg_color" value="#F9F3E6" <?php echo $values['bg_color'] === '#F9F3E6' ? 'checked' : ''; ?>><span></span></label>
                            <label class="event-swatch" style="--swatch:#E8F0E9;"><input type="radio" name="bg_color" value="#E8F0E9" <?php echo $values['bg_color'] === '#E8F0E9' ? 'checked' : ''; ?>><span></span></label>
                            <label class="event-swatch" style="--swatch:#FEEAE6;"><input type="radio" name="bg_color" value="#FEEAE6" <?php echo $values['bg_color'] === '#FEEAE6' ? 'checked' : ''; ?>><span></span></label>
                            <input type="text" name="bg_color_custom" id="customEventColor" class="event-custom-color" value="" placeholder="#1A4D3A">
                        </div>
                        <div class="error-message"><?php echo htmlspecialchars($errors['bg_color'] ?? ''); ?></div>
                    </div>
                </div>
                <label class="event-toggle">
                    <input type="checkbox" name="active" value="1" checked>
                    <span>Publier maintenant</span>
                </label>
                <button class="btn-primary" type="submit" style="margin-top:18px;"><i class="fas fa-bullhorn"></i> Creer l'evenement</button>
            </form>
        </div>
    </div>

    <div class="table-card">
        <div class="table-header">
            <div>
                <h3>Evenements</h3>
            </div>
            <div class="record-count"><?php echo count($events); ?> annonce<?php echo count($events) > 1 ? 's' : ''; ?></div>
        </div>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Titre</th><th>Message</th><th>Code</th><th>Couleur</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($events)): ?>
                    <tr><td colspan="6" class="text-center">Aucun evenement.</td></tr>
                    <?php else: foreach ($events as $event): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($event['titre']); ?></strong></td>
                        <td><?php echo htmlspecialchars($event['message']); ?></td>
                        <td><?php echo htmlspecialchars($event['code_promo'] ?: '-'); ?></td>
                        <td><span class="event-color-chip" style="background:<?php echo htmlspecialchars($event['bg_color'] ?? '#F9F3E6'); ?>"></span></td>
                        <td><span class="badge <?php echo (int)$event['active'] === 1 ? 'badge-aliment' : 'badge-compensation'; ?>"><?php echo (int)$event['active'] === 1 ? 'Actif' : 'Inactif'; ?></span></td>
                        <td style="display:flex; gap:8px; flex-wrap:wrap;">
                            <?php if ((int)$event['active'] === 1): ?>
                            <form method="POST"><input type="hidden" name="id" value="<?php echo (int)$event['id']; ?>"><input type="hidden" name="action" value="disable"><button class="btn-icon" type="submit"><i class="fas fa-pause"></i> Desactiver</button></form>
                            <?php else: ?>
                            <form method="POST"><input type="hidden" name="id" value="<?php echo (int)$event['id']; ?>"><input type="hidden" name="action" value="activate"><button class="btn-icon" type="submit"><i class="fas fa-play"></i> Activer</button></form>
                            <?php endif; ?>
                            <form method="POST" onsubmit="return confirm('Supprimer cet evenement ?')"><input type="hidden" name="id" value="<?php echo (int)$event['id']; ?>"><input type="hidden" name="action" value="delete"><button class="btn-icon btn-icon-danger" type="submit"><i class="fas fa-trash"></i> Supprimer</button></form>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.event-layout { display:grid; grid-template-columns: 420px minmax(0, 1fr); gap:24px; align-items:start; }
.event-card-header { padding:24px; border-bottom:1px solid var(--border-light); }
.event-card-header h3 { margin:0; }
.event-card-body { padding:24px; }
.event-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.event-toggle { display:flex; align-items:center; gap:10px; color:var(--text-secondary); font-weight:700; }
.event-toggle input { accent-color:var(--accent-herb); }
.event-color-row { min-height:55px; display:flex; align-items:center; gap:10px; }
.event-swatch input { position:absolute; opacity:0; }
.event-swatch span { display:block; width:34px; height:34px; border-radius:999px; background:var(--swatch); border:2px solid #fff; box-shadow:0 0 0 1px var(--border-light); cursor:pointer; }
.event-swatch input:checked + span { box-shadow:0 0 0 3px var(--accent-herb); }
.event-custom-color { width:96px; height:38px; border:1px solid var(--border-light); border-radius:999px; padding:0 12px; background:#fff; font-weight:700; color:var(--text-secondary); }
.event-color-chip { display:inline-flex; width:30px; height:20px; border-radius:999px; border:1px solid var(--border-light); }
@media (max-width: 1100px) { .event-layout { grid-template-columns:1fr; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const customColor = document.getElementById('customEventColor');
    customColor?.addEventListener('input', function () {
        this.value = this.value.replace(/[^#0-9A-Fa-f]/g, '').slice(0, 7);
        document.querySelectorAll('input[name="bg_color"]').forEach(input => input.checked = false);
    });
});
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
