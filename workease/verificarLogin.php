<?php

session_start();

if(!isset($_SESSION['user_id'])) {
    // Se o usuário não estiver logado, redireciona para a página de login
    header('Location: login.php ?erro=true');
    exit;
}

?>
