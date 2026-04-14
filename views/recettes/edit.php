<div class="row">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Modifier Recette</h3>
            </div>
            <form method="post" action="router.php?controller=recette&action=edit&id=<?php echo $recette['id']; ?>">
                <div class="card-body">
                    <div class="form-group">
                        <label for="nom">Nom</label>
                        <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($recette['nom']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description"><?php echo htmlspecialchars($recette['description']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="instructions">Instructions</label>
                        <textarea class="form-control" id="instructions" name="instructions"><?php echo htmlspecialchars($recette['instructions']); ?></textarea>
                    </div>
                    <h4>Ingrédients</h4>
                    <div id="ingredients">
                        <?php foreach ($ingredients as $index => $ing): ?>
                        <div class="ingredient-row">
                            <div class="row">
                                <div class="col-md-4">
                                    <select class="form-control" name="ingredients[<?php echo $index; ?>][aliment_id]">
                                        <option value="">Sélectionner Aliment</option>
                                        <?php foreach ($aliments as $aliment): ?>
                                        <option value="<?php echo $aliment['id']; ?>" <?php if ($aliment['id'] == $ing['aliment_id']) echo 'selected'; ?>><?php echo htmlspecialchars($aliment['nom']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" class="form-control" name="ingredients[<?php echo $index; ?>][quantite]" value="<?php echo $ing['quantite']; ?>" step="0.01">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="ingredients[<?php echo $index; ?>][unite]" value="<?php echo htmlspecialchars($ing['unite']); ?>">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger btn-sm remove-ingredient">Supprimer</button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="add-ingredient" class="btn btn-secondary btn-sm">Ajouter Ingrédient</button>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Modifier</button>
                    <a href="router.php?controller=recette&action=index" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('add-ingredient').addEventListener('click', function() {
    var container = document.getElementById('ingredients');
    var rows = container.querySelectorAll('.ingredient-row');
    var index = rows.length;
    var row = document.createElement('div');
    row.className = 'ingredient-row';
    row.innerHTML = `
        <div class="row">
            <div class="col-md-4">
                <select class="form-control" name="ingredients[${index}][aliment_id]">
                    <option value="">Sélectionner Aliment</option>
                    <?php foreach ($aliments as $aliment): ?>
                    <option value="<?php echo $aliment['id']; ?>"><?php echo htmlspecialchars($aliment['nom']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" class="form-control" name="ingredients[${index}][quantite]" placeholder="Quantité" step="0.01">
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" name="ingredients[${index}][unite]" placeholder="Unité">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger btn-sm remove-ingredient">Supprimer</button>
            </div>
        </div>
    `;
    container.appendChild(row);
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-ingredient')) {
        e.target.closest('.ingredient-row').remove();
    }
});
</script>