<?php
require_once 'Database.php';

class RecetteModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM recettes ORDER BY nom");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM recettes WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO recettes (nom, description, instructions) VALUES (?, ?, ?)");
        $stmt->execute([$data['nom'], $data['description'], $data['instructions']]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE recettes SET nom = ?, description = ?, instructions = ? WHERE id = ?");
        $stmt->execute([$data['nom'], $data['description'], $data['instructions'], $id]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM recettes WHERE id = ?");
        $stmt->execute([$id]);
    }

    // For ingredients, additional methods
    public function getIngredients($recette_id) {
        $stmt = $this->db->prepare("
            SELECT i.*, a.nom as aliment_nom
            FROM ingredients i
            JOIN aliments a ON i.aliment_id = a.id
            WHERE i.recette_id = ?
        ");
        $stmt->execute([$recette_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addIngredient($recette_id, $aliment_id, $quantite, $unite) {
        $stmt = $this->db->prepare("INSERT INTO ingredients (recette_id, aliment_id, quantite, unite) VALUES (?, ?, ?, ?)");
        $stmt->execute([$recette_id, $aliment_id, $quantite, $unite]);
    }

    public function updateIngredients($recette_id, $ingredients) {
        // First, delete existing
        $stmt = $this->db->prepare("DELETE FROM ingredients WHERE recette_id = ?");
        $stmt->execute([$recette_id]);
        // Then add new
        foreach ($ingredients as $ing) {
            $this->addIngredient($recette_id, $ing['aliment_id'], $ing['quantite'], $ing['unite']);
        }
    }
}
?>