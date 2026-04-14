<div class="row">
    <div class="col-md-6">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Modifier Aliment</h3>
            </div>
            <form method="post" action="router.php?controller=aliment&action=edit&id=<?php echo $aliment['id']; ?>">
                <div class="card-body">
                    <div class="form-group">
                        <label for="nom">Nom</label>
                        <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($aliment['nom']); ?>" pattern="[A-Za-zÀ-ÿ\s'-]+" title="Le nom ne doit contenir que des lettres, espaces, apostrophes ou tirets." required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description"><?php echo htmlspecialchars($aliment['description']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="calories">Calories</label>
                        <input type="number" class="form-control" id="calories" name="calories" value="<?php echo $aliment['calories']; ?>" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="proteines">Protéines (g)</label>
                        <input type="number" class="form-control" id="proteines" name="proteines" value="<?php echo $aliment['proteines']; ?>" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="glucides">Glucides (g)</label>
                        <input type="number" class="form-control" id="glucides" name="glucides" value="<?php echo $aliment['glucides']; ?>" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="lipides">Lipides (g)</label>
                        <input type="number" class="form-control" id="lipides" name="lipides" value="<?php echo $aliment['lipides']; ?>" step="0.01">
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Modifier</button>
                    <a href="router.php?controller=aliment&action=index" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>