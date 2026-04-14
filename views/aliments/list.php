<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Liste des Aliments</h3>
                <div class="card-tools">
                    <a href="router.php?controller=aliment&action=create" class="btn btn-primary btn-sm">Ajouter Aliment</a>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Calories</th>
                            <th>Protéines</th>
                            <th>Glucides</th>
                            <th>Lipides</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aliments as $aliment): ?>
                        <tr>
                            <td><?php echo $aliment['id']; ?></td>
                            <td><?php echo htmlspecialchars($aliment['nom']); ?></td>
                            <td><?php echo htmlspecialchars($aliment['description']); ?></td>
                            <td><?php echo $aliment['calories']; ?></td>
                            <td><?php echo $aliment['proteines']; ?>g</td>
                            <td><?php echo $aliment['glucides']; ?>g</td>
                            <td><?php echo $aliment['lipides']; ?>g</td>
                            <td>
                                <a href="router.php?controller=aliment&action=edit&id=<?php echo $aliment['id']; ?>" class="btn btn-warning btn-sm">Modifier</a>
                                <a href="router.php?controller=aliment&action=delete&id=<?php echo $aliment['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>