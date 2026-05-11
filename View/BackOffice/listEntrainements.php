<?php
require_once __DIR__ . '/../../Controller/EntrainementC.php';
require_once __DIR__ . '/../../Controller/UtilisateurC.php';
$eC = new EntrainementC();
$uC = new UtilisateurC();

if (isset($_GET['delete'])) {
    $eC->delete((int)$_GET['delete']);
    $_SESSION['flash'] = ['type'=>'warning','msg'=>'Entraînement supprimé.'];
    header('Location: listEntrainements.php'); exit;
}

// Get all user-created workouts (is_custom = 1)
$pdo = config::getConnexion();
$stmt = $pdo->query(
    "SELECT e.*, u.nom as user_nom, u.email as user_email
     FROM entrainements e
     INNER JOIN utilisateur u ON u.id = e.user_id
     WHERE e.is_custom = 1
     ORDER BY e.created_at DESC"
);
$entrainements = $stmt->fetchAll();

$pageTitle = 'Entraînements'; $activePage = 'entrainements';
$breadcrumb = '<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Entraînements</li>';
require_once __DIR__ . '/partials/layout_top.php';

$niveauBadges = [
    'debutant' => 'success',
    'intermediaire' => 'warning',
    'avance' => 'danger'
];
?>

<div class="row mb-3">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-body">
                <h5><i class="fas fa-info-circle mr-2 text-primary"></i>Entraînements créés par les utilisateurs</h5>
                <p class="text-muted mb-0">
                    Cette liste affiche tous les entraînements générés par l'IA et sauvegardés par les utilisateurs dans leur programme.
                    Ces entraînements sont dynamiques et personnalisés selon les besoins de chaque utilisateur.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-running mr-2"></i>Entraînements utilisateurs (<?= count($entrainements) ?>)</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <?php if (empty($entrainements)): ?>
        <div class="p-5 text-center text-muted">
            <i class="fas fa-robot fa-3x mb-3"></i>
            <p>Aucun entraînement créé pour le moment.</p>
            <p><small>Les entraînements apparaîtront ici lorsque les utilisateurs sauvegarderont des séances IA.</small></p>
        </div>
        <?php else: ?>
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Nom</th>
                    <th>Utilisateur</th>
                    <th>Type</th>
                    <th>Niveau</th>
                    <th>MET</th>
                    <th>Date création</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($entrainements as $e): ?>
            <tr>
                <td><strong><?= $e['id'] ?></strong></td>
                <td>
                    <strong><?= htmlspecialchars($e['nom']) ?></strong>
                    <?php if (strpos($e['nom'], 'IA') !== false): ?>
                    <span class="badge badge-primary badge-sm ml-1"><i class="fas fa-robot"></i> IA</span>
                    <?php endif; ?>
                </td>
                <td>
                    <i class="fas fa-user mr-1 text-muted"></i><?= htmlspecialchars($e['user_nom']) ?>
                    <br><small class="text-muted"><?= htmlspecialchars($e['user_email']) ?></small>
                </td>
                <td><?= htmlspecialchars($e['type_sport']) ?></td>
                <td>
                    <span class="badge badge-<?= $niveauBadges[$e['niveau']] ?? 'secondary' ?>">
                        <?= ucfirst($e['niveau']) ?>
                    </span>
                </td>
                <td><strong style="color:#82ae46;"><?= $e['met_value'] ?></strong></td>
                <td><small><?= date('d/m/Y H:i', strtotime($e['created_at'])) ?></small></td>
                <td>
                    <button class="btn btn-info btn-sm" onclick="viewDetails(<?= $e['id'] ?>)">
                        <i class="fas fa-eye"></i>
                    </button>
                    <a href="listEntrainements.php?delete=<?= $e['id'] ?>" class="btn btn-danger btn-sm"
                       onclick="return confirm('Supprimer cet entraînement ?')">
                        <i class="fas fa-trash"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal for viewing details -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-running mr-2"></i>Détails de l'entraînement</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="detailsContent">
                <div class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewDetails(id) {
    document.getElementById('detailsContent').innerHTML = '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    $('#detailsModal').modal('show');
    
    fetch('get_entrainement_details.php?id=' + id)
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                document.getElementById('detailsContent').innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
                return;
            }
            
            let html = `
                <div class="mb-3">
                    <h5>${data.nom}</h5>
                    <p class="text-muted">${data.description || 'Aucune description'}</p>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3"><strong>Type:</strong> ${data.type_sport}</div>
                    <div class="col-md-3"><strong>Niveau:</strong> ${data.niveau}</div>
                    <div class="col-md-3"><strong>MET:</strong> ${data.met_value}</div>
                    <div class="col-md-3"><strong>Utilisateur:</strong> ${data.user_nom}</div>
                </div>
            `;
            
            if (data.etapes && data.etapes.length > 0) {
                html += '<h6><i class="fas fa-list-ol mr-2"></i>Étapes (' + data.etapes.length + ')</h6>';
                html += '<div class="list-group">';
                data.etapes.forEach((etape, i) => {
                    html += `
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${i + 1}. ${etape.titre}</h6>
                            </div>
                            <p class="mb-0 text-muted">${etape.description}</p>
                        </div>
                    `;
                });
                html += '</div>';
            }
            
            document.getElementById('detailsContent').innerHTML = html;
        })
        .catch(err => {
            document.getElementById('detailsContent').innerHTML = '<div class="alert alert-danger">Erreur de chargement</div>';
        });
}
</script>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>

