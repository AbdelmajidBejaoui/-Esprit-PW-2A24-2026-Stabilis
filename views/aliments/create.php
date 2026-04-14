<div class="row">
    <div class="col-md-6">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Créer Aliment</h3>
            </div>
            <form method="post" action="router.php?controller=aliment&action=create">
                <div class="card-body">
                    <div class="form-group">
                        <label for="nom">Nom</label>
                        <input type="text" class="form-control" id="nom" name="nom" pattern="[A-Za-zÀ-ÿ\s'-]+" title="Le nom ne doit contenir que des lettres, espaces, apostrophes ou tirets." required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="calories">Calories</label>
                        <input type="number" class="form-control" id="calories" name="calories" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="proteines">Protéines (g)</label>
                        <input type="number" class="form-control" id="proteines" name="proteines" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="glucides">Glucides (g)</label>
                        <input type="number" class="form-control" id="glucides" name="glucides" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="lipides">Lipides (g)</label>
                        <input type="number" class="form-control" id="lipides" name="lipides" step="0.01">
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Créer</button>
                    <a href="router.php?controller=aliment&action=index" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>