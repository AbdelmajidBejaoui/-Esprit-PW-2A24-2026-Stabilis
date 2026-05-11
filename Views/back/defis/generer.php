<?php
require_once __DIR__ . '/../../../Controllers/DefiController.php';
require_once __DIR__ . '/../../../Services/DefiGeminiService.php';

$controller = new DefiController();
$service = new DefiGeminiService();
$errors = [];
$generated = [];
$values = ['topic' => 'nutrition durable', 'difficulty' => 'moyen', 'count' => 3];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = [
        'topic' => trim($_POST['topic'] ?? 'nutrition durable'),
        'difficulty' => trim($_POST['difficulty'] ?? 'moyen'),
        'count' => trim((string)($_POST['count'] ?? '3')),
    ];

    if (isset($_POST['save'], $_POST['defis']) && is_array($_POST['defis'])) {
        foreach ($_POST['defis'] as $defi) {
            $data = $controller->sanitize($defi);
            $itemErrors = $controller->validate($data, false);
            if ($itemErrors) {
                $errors = array_merge($errors, $itemErrors);
                continue;
            }
            $controller->add($data);
        }
        if (!$errors) {
            header('Location: liste.php?generated=1');
            exit;
        }
    } else {
        try {
            $generated = $service->generateChallenges($values);
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$title = 'Generer des defis - Stabilis';
require_once __DIR__ . '/../../partials/header.php';
?>
<div class="form-card" style="padding:24px; max-width:1000px;">
    <h1>Generer des defis IA</h1>
    <?php if ($errors): ?><div class="alert"><ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="POST">
        <div class="form-group"><label>Sujet</label><input class="form-control" name="topic" value="<?php echo htmlspecialchars($values['topic']); ?>"></div>
        <div class="form-group"><label>Difficulte</label><input class="form-control" name="difficulty" value="<?php echo htmlspecialchars($values['difficulty']); ?>"></div>
        <div class="form-group"><label>Nombre</label><input class="form-control" name="count" value="<?php echo htmlspecialchars((string)$values['count']); ?>"></div>
        <button class="btn-primary" type="submit"><i class="fas fa-robot"></i> Generer</button>
        <a class="btn-secondary" href="liste.php">Retour</a>
    </form>

    <?php if ($generated): ?>
    <form method="POST" style="margin-top:24px;">
        <input type="hidden" name="topic" value="<?php echo htmlspecialchars($values['topic']); ?>">
        <input type="hidden" name="difficulty" value="<?php echo htmlspecialchars($values['difficulty']); ?>">
        <input type="hidden" name="count" value="<?php echo htmlspecialchars((string)$values['count']); ?>">
        <?php foreach ($generated as $index => $defi): ?>
            <div class="table-card" style="padding:18px; margin-bottom:14px;">
                <input type="hidden" name="defis[<?php echo $index; ?>][id]" value="">
                <div class="form-group"><label>Nom</label><input class="form-control" name="defis[<?php echo $index; ?>][nom]" value="<?php echo htmlspecialchars($defi['nom']); ?>"></div>
                <div class="form-group"><label>Type</label><select class="form-control" name="defis[<?php echo $index; ?>][type]"><?php foreach (['aliment','entrainement','compensation'] as $type): ?><option value="<?php echo $type; ?>" <?php echo $defi['type'] === $type ? 'selected' : ''; ?>><?php echo $type; ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Objectif</label><textarea class="form-control" name="defis[<?php echo $index; ?>][objectif]"><?php echo htmlspecialchars($defi['objectif']); ?></textarea></div>
                <div class="form-group"><label>Recompense</label><input class="form-control" name="defis[<?php echo $index; ?>][recompense]" value="<?php echo htmlspecialchars($defi['recompense']); ?>"></div>
            </div>
        <?php endforeach; ?>
        <button class="btn-primary" type="submit" name="save" value="1"><i class="fas fa-save"></i> Enregistrer dans defis</button>
    </form>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
