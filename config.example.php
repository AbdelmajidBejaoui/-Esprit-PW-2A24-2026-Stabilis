<?php
// ── Configuration API Gemini (GRATUIT) ────────────────────────────────────
// Obtenez votre clé API GRATUITE sur: https://aistudio.google.com/app/apikey
// Pas de carte bancaire requise!
define('GEMINI_API_KEY', 'votre_cle_api_ici');

// ── Configuration Base de Données ─────────────────────────────────────────
class config {
    private static $pdo = null;

    public static function getConnexion() {
        if (self::$pdo === null) {
            // Modifiez ces valeurs selon votre configuration
            $host    = 'localhost';
            $dbname  = 'gestion_fitness';
            $user    = 'root';
            $pass    = '';  // Votre mot de passe MySQL (vide par défaut sur XAMPP)
            
            try {
                self::$pdo = new PDO(
                    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                    $user, $pass
                );
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                die('Erreur connexion BDD : ' . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}
