<?php
class Database
{
    public static function connect(): mysqli
    {
        $config = require __DIR__ . '/../config.php';

        $conn = new mysqli(
            $config['host'],
            $config['user'],
            $config['password'],
            $config['database']
        );

        if ($conn->connect_errno) {
            die('Connection failed: ' . $conn->connect_error);
        }

        $conn->set_charset('utf8mb4');

        return $conn;
    }
}
