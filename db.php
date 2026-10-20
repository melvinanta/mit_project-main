<?php

$host = "localhost";
$db   = "project";
$user = "root";
$pass = "";


try {

    $conn = new PDO( "mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    die("System Error");
    }


?>