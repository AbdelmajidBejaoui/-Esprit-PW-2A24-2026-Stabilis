<?php
require_once 'models/RecetteModel.php';
require_once 'models/AlimentModel.php';

class RecetteController {
    private $model;
    private $alimentModel;

    public function __construct() {
        $this->model = new RecetteModel();
        $this->alimentModel = new AlimentModel();
    }

    public function index() {
        $recettes = $this->model->getAll();
        ob_start();
        include 'views/recettes/list.php';
        $content = ob_get_clean();
        $title = 'Recettes';
        include 'views/layout.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nom' => $_POST['nom'],
                'description' => $_POST['description'],
                'instructions' => $_POST['instructions']
            ];
            $recette_id = $this->model->create($data);
            // Handle ingredients if provided
            if (isset($_POST['ingredients'])) {
                $ingredients = [];
                foreach ($_POST['ingredients'] as $ing) {
                    if (!empty($ing['aliment_id']) && !empty($ing['quantite'])) {
                        $ingredients[] = $ing;
                    }
                }
                $this->model->updateIngredients($recette_id, $ingredients);
            }
            header('Location: router.php?controller=recette&action=index');
        } else {
            $aliments = $this->alimentModel->getAll();
            ob_start();
            include 'views/recettes/create.php';
            $content = ob_get_clean();
            $title = 'Créer Recette';
            include 'views/layout.php';
        }
    }

    public function edit($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nom' => $_POST['nom'],
                'description' => $_POST['description'],
                'instructions' => $_POST['instructions']
            ];
            $this->model->update($id, $data);
            // Handle ingredients
            if (isset($_POST['ingredients'])) {
                $ingredients = [];
                foreach ($_POST['ingredients'] as $ing) {
                    if (!empty($ing['aliment_id']) && !empty($ing['quantite'])) {
                        $ingredients[] = $ing;
                    }
                }
                $this->model->updateIngredients($id, $ingredients);
            }
            header('Location: router.php?controller=recette&action=index');
        } else {
            $recette = $this->model->getById($id);
            $ingredients = $this->model->getIngredients($id);
            $aliments = $this->alimentModel->getAll();
            ob_start();
            include 'views/recettes/edit.php';
            $content = ob_get_clean();
            $title = 'Modifier Recette';
            include 'views/layout.php';
        }
    }

    public function delete($id) {
        $this->model->delete($id);
        header('Location: router.php?controller=recette&action=index');
    }
}
?>