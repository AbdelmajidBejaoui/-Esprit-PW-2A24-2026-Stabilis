<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Liste des Recettes</h3>
                <div class="card-tools">
                    <a href="router.php?controller=recette&action=create" class="btn btn-primary btn-sm">Ajouter Recette</a>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Instructions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recettes as $recette): ?>
                        <tr>
                            <td><?php echo $recette['id']; ?></td>
                            <td><?php echo htmlspecialchars($recette['nom']); ?></td>
                            <td><?php echo htmlspecialchars($recette['description']); ?></td>
                            <td><?php echo htmlspecialchars(substr($recette['instructions'], 0, 50)); ?>...</td>
                            <td>
                                <a href="router.php?controller=recette&action=edit&id=<?php echo $recette['id']; ?>" class="btn btn-warning btn-sm">Modifier</a>
                                <a href="router.php?controller=recette&action=delete&id=<?php echo $recette['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>