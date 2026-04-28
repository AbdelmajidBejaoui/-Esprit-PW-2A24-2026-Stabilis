<?php
require_once __DIR__ . '/../../Controller/UserC.php';

$userC = new UserC();
$search = trim($_GET['search'] ?? '');
$perPage = 5;
$requestedPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$requestedPage = max(1, $requestedPage);

$totalUsers = $userC->countUsers($search);
$totalPages = max(1, (int) ceil($totalUsers / $perPage));
$currentPage = min($requestedPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;

$users = $userC->listUsers($search, $perPage, $offset);

$pageTitle = 'Liste des utilisateurs';
$activePage = 'list';
require_once __DIR__ . '/partials/layout_top.php';
?>

<div class="card card-primary card-outline">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <h3 class="card-title mb-2 mb-sm-0">Gestion des comptes</h3>
            <div class="d-flex align-items-center">
                <form method="GET" action="" class="mr-2">
                    <div class="input-group input-group-sm" style="width: 260px;">
                        <input
                            type="text"
                            name="search"
                            class="form-control float-right"
                            placeholder="Rechercher nom/email/role"
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if ($search !== ''): ?>
                                <a href="listUsers.php" class="btn btn-outline-secondary">Reset</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <a href="addUser.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-user-plus mr-1"></i> Ajouter
                </a>

                <a href="exportUsersPDF.php" class="btn btn-danger btn-sm ml-2" title="Exporter en PDF">
                    <i class="fas fa-file-pdf mr-1"></i> Exporter PDF
                </a>
            </div>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <?php if ($search !== ''): ?>
            <div class="px-3 py-2 border-bottom bg-light">
                Resultat de recherche pour "<?php echo htmlspecialchars($search); ?>": <?php echo $totalUsers; ?> utilisateur(s)
            </div>
        <?php endif; ?>
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Preference</th>
                    <th>Date inscription</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u['id']); ?></td>
                            <td><?php echo htmlspecialchars($u['nom']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo htmlspecialchars($u['role']); ?></td>
                            <td><?php echo htmlspecialchars($u['preference_alimentaire']); ?></td>
                            <td><?php echo htmlspecialchars($u['date_inscription']); ?></td>
                            <td>
                                <?php if ((int) $u['statut_compte'] === 1): ?>
                                    <span class="badge badge-success">Actif</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="updateUser.php?id=<?php echo (int) $u['id']; ?>" class="btn btn-warning btn-xs">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="deleteUser.php?id=<?php echo (int) $u['id']; ?>" class="btn btn-danger btn-xs" onclick="return confirm('Supprimer cet utilisateur ?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">Aucun utilisateur trouve.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer clearfix">
        <?php
        $searchQuery = $search !== '' ? '&search=' . urlencode($search) : '';
        ?>
        <div class="float-left text-muted" style="padding-top: 6px;">
            Page <?php echo $currentPage; ?> / <?php echo $totalPages; ?>
            <?php if ($totalUsers > 0): ?>
                - <?php echo $totalUsers; ?> utilisateur(s)
            <?php endif; ?>
        </div>
        <ul class="pagination pagination-sm m-0 float-right">
            <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="listUsers.php?page=<?php echo max(1, $currentPage - 1) . $searchQuery; ?>">&laquo;</a>
            </li>

            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?php echo $p === $currentPage ? 'active' : ''; ?>">
                    <a class="page-link" href="listUsers.php?page=<?php echo $p . $searchQuery; ?>"><?php echo $p; ?></a>
                </li>
            <?php endfor; ?>

            <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                <a class="page-link" href="listUsers.php?page=<?php echo min($totalPages, $currentPage + 1) . $searchQuery; ?>">&raquo;</a>
            </li>
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
