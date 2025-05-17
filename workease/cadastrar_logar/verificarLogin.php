<?php

session_start();

// Função para verificar se o usuário está logado
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Redireciona para o login se não estiver logado
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

?>
