<?php
// define('HOST', 'localhost');
// define('USER', 'root');
// define('PASS', '');
// define('DB', 'kosan');
// $con = mysqli_connect(HOST,USER,PASS,DB) or die('unable to connect');

$host = "localhost";
$user = "root";
$password = "";
$dbname = "kosan";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>