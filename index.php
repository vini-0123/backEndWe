<?php
    if(isset($_POST['email'])){

        include_once('conexao.php');

        $email = $_POST['email'];
        $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

        $mysqli->query("INSERT INTO clientes(email,senha) VALUES('$email','$senha') ");
    }
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css"> 
    <title>Controle acesso de usuarios</title>
</head>
<body>
    <main>
        <h1>
            Cadastro
        </h1>
    </main>
</body>
</html>
