<?php
$host = "mysql.railway.internal";   // Host do banco de dados
$user = "root";                     // Usuário
$pass = "EdcnBqDA1SPVWcakrdObyimOnecOIrLI";  // Senha
$bd   = "railway";                  // Nome do banco de dados

$mysqli = new mysqli($host, $user, $pass, $bd);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "Conectado com sucesso ao banco de dados!";
?>
