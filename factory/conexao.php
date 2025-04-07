<?php
$host = "mysql.railway.internal"; // Substitua pelo host do seu banco de dados no Railway
$user = "root";             // Substitua pelo seu nome de usuário
$pass = "EdcnBqDAlSPVWcakrdObyimOnecOIrLI";         // Substitua pela sua senha
$bd = "railway";           // Substitua pelo nome do seu banco de dados

// Cria a conexão com o banco de dados
$mysqli = new mysqli($host, $user, $pass, $bd);

// Verifica se houve erro na conexão
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "Conectado com sucesso ao banco de dados!";
?>
