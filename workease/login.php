<?php

session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

include_once'./factory/conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = $_POST['login-email'];
    $password = $_POST['login-password'];


    $stmt = $mysqli->prepare("SELECT * FROM data_clients WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['senha'])) {
   
            $_SESSION['user_id'] = $user['nome_empresa'];
            header("Location: dashboard.php");
            exit;
        } else {
            $erro = "Senha incorreta.";
        }
    } else {
        $erro = "Usuário não encontrado.";
    }
}
?>
<section style="background-color: red; margin: 10px;">
    <?php echo $erro ?? '' ?>
</section>

<!DOCTYPE html>
<html lang="pt-BR"> <!-- Changed lang to pt-BR -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkEase - Login</title> <!-- Improved Title -->
    <!-- === IMPORTANT: Add Font Awesome === -->
    <link rel="stylesheet" href= "css/style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- === IMPORTANT: Add Google Font (Poppins) === -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="screen login-screen">
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Voltar</a>
        <h1 class="logo">WorkEase</h1>

        <div class="form-container">
            <form action="#" method="POST">
                <h2>Login</h2>

                <div class="input-group">
                     <input type="email" id="login-email" name="login-email" placeholder="E-mail" required>
                 </div>

                <div class="input-group password-wrapper">
                    <input type="password" id="login-password" name="login-password" placeholder="Senha" required>
                    <span class="toggle-password"><i class="fas fa-eye"></i></span>
                </div>

                <div class="form-options">
                    <a href="#forgot-password" class="forgot-password">Esqueceu a senha?</a>
                    <label class="remember-me">
                        <input type="checkbox" name="remember"> Lembrar-me
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">Login</button>

                <p class="extra-link">Não tem uma conta? <a href="cadastro.php">Cadastrar</a></p>

                <p class="separator">ou</p>

                <a href="#linkedin-login" class="btn-social">
                    Entrar com <i class="fab fa-linkedin"></i>
                </a>
            </form>
        </div>
    </div>

    <!-- === IMPORTANT: Add JS for Password Toggle (if needed) === -->
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