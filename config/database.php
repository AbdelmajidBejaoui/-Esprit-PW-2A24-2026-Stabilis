<?php
class Database {
    private static $pdo = null;
    
    public static function getConnection() {
        if (self::$pdo === null) {
            $host = 'localhost';
            $dbname = 'stabilis';
            $username = 'root';
            $password = '';
            
            try {
                self::$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", 
                                    $username, $password);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                die("Erreur de connexion : " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}
?>
