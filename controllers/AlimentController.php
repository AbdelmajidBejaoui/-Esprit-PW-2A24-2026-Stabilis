<?php
require_once 'models/AlimentModel.php';

class AlimentController {
    private $model;

    public function __construct() {
        $this->model = new AlimentModel();
    }

    public function index() {
        $aliments = $this->model->getAll();
        ob_start();
        include 'views/aliments/list.php';
        $content = ob_get_clean();
        $title = 'Aliments';
        include 'views/layout.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = trim($_POST['nom']);
            if (!preg_match("/^[A-Za-zÀ-ÿ\s'-]+$/", $nom)) {
                die("Erreur: Le nom ne doit contenir que des lettres, espaces, apostrophes ou tirets.");
            }
            $data = [
                'nom' => $nom,
                'description' => trim($_POST['description']),
                'calories' => $_POST['calories'],
                'proteines' => $_POST['proteines'],
                'glucides' => $_POST['glucides'],
                'lipides' => $_POST['lipides']
            ];
            $this->model->create($data);
            header('Location: router.php?controller=aliment&action=index');
        } else {
            ob_start();
            include 'views/aliments/create.php';
            $content = ob_get_clean();
            $title = 'Créer Aliment';
            include 'views/layout.php';
        }
    }

    public function edit($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = trim($_POST['nom']);
            if (!preg_match("/^[A-Za-zÀ-ÿ\s'-]+$/", $nom)) {
                die("Erreur: Le nom ne doit contenir que des lettres, espaces, apostrophes ou tirets.");
            }
            $data = [
                'nom' => $nom,
                'description' => $_POST['description'],
                'calories' => $_POST['calories'],
                'proteines' => $_POST['proteines'],
                'glucides' => $_POST['glucides'],
                'lipides' => $_POST['lipides']
            ];
            $this->model->update($id, $data);
            header('Location: router.php?controller=aliment&action=index');
        } else {
            $aliment = $this->model->getById($id);
            ob_start();
            include 'views/aliments/edit.php';
            $content = ob_get_clean();
            $title = 'Modifier Aliment';
            include 'views/layout.php';
        }
    }

    public function delete($id) {
        $this->model->delete($id);
        header('Location: index.php?controller=aliment&action=index');
    }
}
?>