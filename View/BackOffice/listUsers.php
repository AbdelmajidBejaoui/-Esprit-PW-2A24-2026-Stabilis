<?php
require_once __DIR__ . '/../../Controller/UtilisateurC.php';
$uC = new UtilisateurC();

if (isset($_GET['delete'])) {
    $uC->delete((int)$_GET['delete']);
    $_SESSION['flash'] = ['type'=>'warning','msg'=>'Utilisateur supprimé.'];
    header('Location: listUsers.php'); exit;
}

$users = $uC->listAll();
$pageTitle = 'Utilisateurs'; $activePage = 'users';
$breadcrumb = '<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Utilisateurs</li>';
require_once __DIR__ . '/partials/layout_top.php';
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users mr-2"></i>Utilisateurs (<?= count($users) ?>)</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead class="thead-light">
                <tr><th>#</th><th>Nom</th><th>Email</th><th>Poids</th><th>Taille</th><th>Âge</th><th>Sexe</th><th>Inscrit le</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><strong><?= htmlspecialchars($u['nom']) ?></strong></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= $u['poids'] ? $u['poids'].' kg' : '<span class="text-muted">—</span>' ?></td>
                <td><?= $u['taille'] ? $u['taille'].' cm' : '<span class="text-muted">—</span>' ?></td>
                <td><?= $u['age'] ?? '<span class="text-muted">—</span>' ?></td>
                <td><?= $u['sexe'] === 'H' ? 'Homme' : 'Femme' ?></td>
                <td><small><?= date('d/m/Y', strtotime($u['created_at'])) ?></small></td>
                <td>
                    <a href="listUsers.php?delete=<?= $u['id'] ?>" class="btn btn-danger btn-sm"
                       onclick="return confirm('Supprimer cet utilisateur et toutes ses données ?')">
                        <i class="fas fa-trash"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
