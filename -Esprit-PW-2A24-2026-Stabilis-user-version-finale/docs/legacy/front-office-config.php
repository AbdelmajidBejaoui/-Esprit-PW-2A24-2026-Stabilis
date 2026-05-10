<?php
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'stabilis';

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Erreur de connexion : " . mysqli_connect_error());
}

$site_name = "Stabilis";
$site_tagline = "Nutrition adaptative · Performance durable";
?>  