<?php
session_start();
include_once '../factory/conexao.php';

$erro = ''; // Initialize error variable

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];

    if (empty($email) || empty($senha)) {
        $erro = "Email e senha são obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Formato de email inválido.";
    } else {
        if ($mysqli->connect_errno) {
            $erro = "Falha na conexão com o banco de dados. Por favor, tente mais tarde.";
            error_log("MySQL Connect Error (login.php): " . $mysqli->connect_error);
        } else {
            $query = "SELECT id, senha, nome, nivel_acesso, ativo, email FROM usuarios WHERE email = ? LIMIT 1";
            if ($stmt = $mysqli->prepare($query)) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $usuario = $result->fetch_assoc();
                    if ($usuario['ativo'] == 1 && password_verify($senha, $usuario['senha'])) {
                        $_SESSION['logged_in'] = true;
                        $_SESSION['user_id'] = $usuario['id'];
                        $_SESSION['user_nome'] = $usuario['nome'];
                        $_SESSION['email'] = $usuario['email'];
                        $_SESSION['nivel_acesso'] = $usuario['nivel_acesso'];
                        $_SESSION['login_provider'] = 'Email';
                        $_SESSION['login_time'] = time();

                        $update_query = "UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?";
                        if ($stmt_update = $mysqli->prepare($update_query)) {
                            $stmt_update->bind_param("s", $usuario['id']); // Corrigido para "s"
                            $stmt_update->execute();
                            $stmt_update->close();
                        } else {
                            error_log("Error updating last access (login.php): " . $mysqli->error);
                        }
                        header('Location: ../site/index.php');
                        exit;
                    } else {
                        $erro = "Email ou senha inválidos, ou conta inativa.";
                    }
                } else {
                    $erro = "Email ou senha inválidos.";
                }
                $stmt->close();
            } else {
                $erro = "Erro ao consultar o banco de dados. Por favor, tente mais tarde.";
                error_log("DB Prepare Error (Select login.php): " . $mysqli->error);
            }
        }
    }
}

if (isset($_SESSION['oauth_error'])) {
    $erro = (!empty($erro) ? $erro . "<br>" : "") . $_SESSION['oauth_error'];
    unset($_SESSION['oauth_error']);
    $_SESSION['oauth_error_just_set'] = true;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkEase - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../cadastrar_logar/css/estilos_login.css">
</head>
<body>
    <div class="screen login-screen">
        <a href="../site/index.php" class="back-link"><i class="fas fa-arrow-left"></i> Voltar</a>
        <h1>WorkEase</h1>
        <div class="form-container">
            <h2 class="login-title">Login</h2>
            <div class="alert-message-container">
                <?php
                if (!empty($erro)) {
                    echo '<div class="alert-message alert-danger' . (isset($_POST['email']) || isset($_SESSION['oauth_error_just_set']) ? ' no-animate' : '') . '">' . $erro . '</div>';
                    if (isset($_SESSION['oauth_error_just_set'])) unset($_SESSION['oauth_error_just_set']);
                }
                ?>
            </div>
            <form action="login.php" method="POST" novalidate>
                <div class="input-group">
                     <input type="email" id="email" name="email" placeholder="Seu email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                 </div>
                <div class="input-group password-wrapper">
                    <input type="password" id="senha" name="senha" placeholder="Sua senha" required>
                    <span class="toggle-password"><i class="fas fa-eye"></i></span>
                </div>
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember"> Lembrar-me
                    </label>
                    <a href="verificar_identidade.php" class="forgot-password">Esqueceu a senha?</a>
                </div>
                <button type="submit" class="btn btn-primary">Entrar</button>
            </form>
            <p class="extra-link">Não tem uma conta? <a href="cadastro.php">Cadastre-se</a></p>
            <p class="separator">ou entre com</p>
            <a href="social_auth.php?provider=Google" class="btn btn-social google">
                 <i class="fab fa-google"></i> Google
            </a>
            <a href="social_auth.php?provider=LinkedIn" class="btn btn-social linkedin">
                <i class="fab fa-linkedin"></i> LinkedIn
            </a>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.toggle-password').forEach(item => {
                item.addEventListener('click', function() {
                    const icon = this.querySelector('i');
                    const input = this.closest('.password-wrapper').querySelector('input');
                    if (input) {
                        if (input.type === "password") { input.type = "text"; icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash');
                        } else { input.type = "password"; icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
                    }
                });
            });
        });
    </script>
</body>
</html>