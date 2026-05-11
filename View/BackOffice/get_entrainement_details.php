<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['error' => 'ID invalide']);
    exit;
}

try {
    $db = config::getConnexion();
    
    // Get entrainement
    $stmt = $db->prepare(
        "SELECT e.*, u.nom as user_nom, u.email as user_email
         FROM entrainements e
         INNER JOIN utilisateur u ON u.id = e.user_id
         WHERE e.id = :id"
    );
    $stmt->execute([':id' => $id]);
    $entrainement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$entrainement) {
        echo json_encode(['error' => 'Entraînement non trouvé']);
        exit;
    }
    
    // Get etapes
    $stmt = $db->prepare(
        "SELECT * FROM etapes_exercice 
         WHERE entrainement_id = :id 
         ORDER BY ordre"
    );
    $stmt->execute([':id' => $id]);
    $entrainement['etapes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($entrainement);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur: ' . $e->getMessage()]);
}
?>
