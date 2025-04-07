<?php
    if(isset($_POST['email'])){

        include_once('conexao.php');

        $email = $_POST['email'];
        $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

        $mysqli->query("INSERT INTO clientes(email,senha) VALUES('$email','$senha') ");
    }
?>