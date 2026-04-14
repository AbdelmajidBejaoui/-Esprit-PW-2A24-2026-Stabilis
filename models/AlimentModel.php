<?php
require_once 'Database.php';

class AlimentModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM aliments ORDER BY nom");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM aliments WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO aliments (nom, description, calories, proteines, glucides, lipides) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$data['nom'], $data['description'], $data['calories'], $data['proteines'], $data['glucides'], $data['lipides']]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE aliments SET nom = ?, description = ?, calories = ?, proteines = ?, glucides = ?, lipides = ? WHERE id = ?");
        $stmt->execute([$data['nom'], $data['description'], $data['calories'], $data['proteines'], $data['glucides'], $data['lipides'], $id]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM aliments WHERE id = ?");
        $stmt->execute([$id]);
    }
}
?>