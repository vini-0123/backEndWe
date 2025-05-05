<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (isset($_POST['email'])) {
    include_once('factory/conexao.php');

    function generateUUIDv4() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    $uuid = generateUUIDv4();
    $empresa = $_POST['nome_empresa'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    $stmt = $mysqli->prepare("INSERT INTO data_clients (id, nome_empresa, email, senha) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $uuid, $empresa, $email, $senha);

    if ($stmt->execute()) {
        echo "<script>alert('Usuário cadastrado com sucesso!');</script>";
    } else {
        echo "Erro: " . $stmt->error;
    }

    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href= "css/style.css"> 
        <!-- === IMPORTANT: Add Font Awesome === -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <!-- === IMPORTANT: Add Google Font (Poppins) === -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Tela de Cadastro (Sign Up) -->
    <div class="screen signup-screen">
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Voltar</a>
        <h1 class="logo">WorkEase</h1>

        <div class="form-container">
            <form action="" method="POST">
                <h2>Cadastre-se</h2>

                <div class="input-group">
                    <input type="text" id="company-name" name="nome_empresa" placeholder="Nome da empresa" required>
                </div>

                <div class="input-group">
                    <input type="email" id="signup-email" name="email" placeholder="Email" required>
                </div>

                <div class="input-group password-wrapper">
                    <input type="password" id="signup-password" name="senha" placeholder="Senha" required>
                    <span class="toggle-password"><i class="fas fa-eye"></i></span>
                </div>

                <button type="submit" class="btn btn-primary">Cadastrar</button>

                <p class="separator">ou</p>

                <a href="#linkedin-login" class="btn btn-social">
                    Cadastrar com <i class="fab fa-linkedin"></i>
                </a>
            </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(item => {
            item.addEventListener('click', event => {
                const icon = item.querySelector('i');
                const input = item.previousElementSibling;
                if (input.type === "password") {
                    input.type = "text";
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = "password";
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    </script>

</body>
</html>