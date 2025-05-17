<?php
session_start();
include_once '../factory/conexao.php';

$mensagem = '';
$tipo_mensagem = '';
$pode_redefinir = false;
$id_usuario = null; // Inicializar

// Verificar se o usuário passou pela verificação de identidade
if (isset($_SESSION['token_redefinicao_senha_permitida']) && isset($_SESSION['id_usuario_para_redefinir'])) {
    $pode_redefinir = true;
    $id_usuario = $_SESSION['id_usuario_para_redefinir'];
} else {
    $mensagem = "Acesso inválido. Por favor, verifique sua identidade primeiro.";
    $tipo_mensagem = 'danger';
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && $pode_redefinir) {
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    if (empty($nova_senha) || empty($confirmar_senha)) {
        $mensagem = "Todos os campos de senha são obrigatórios.";
        $tipo_mensagem = 'danger';
    } elseif (strlen($nova_senha) < 6) {
        $mensagem = "A nova senha deve ter pelo menos 6 caracteres.";
        $tipo_mensagem = 'danger';
    } elseif ($nova_senha !== $confirmar_senha) {
        $mensagem = "As senhas não coincidem.";
        $tipo_mensagem = 'danger';
    } else {
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $query_update_senha = "UPDATE usuarios SET senha = ? WHERE id = ?";

        if ($stmt_update = $mysqli->prepare($query_update_senha)) {
            $stmt_update->bind_param("ss", $senha_hash, $id_usuario);
            if ($stmt_update->execute()) {
                $mensagem = "Sua senha foi redefinida com sucesso! Você já pode fazer login com a nova senha.";
                $tipo_mensagem = 'success';
                $pode_redefinir = false; // Impedir reenvio

                // Limpar tokens de sessão
                unset($_SESSION['token_redefinicao_senha_permitida']);
                unset($_SESSION['id_usuario_para_redefinir']);

            } else {
                $mensagem = "Erro ao atualizar sua senha. Por favor, tente novamente.";
                $tipo_mensagem = 'danger';
                error_log("DB Execute Error (Update Password definir_nova_senha.php): " . $mysqli->error);
            }
            $stmt_update->close();
        } else {
            $mensagem = "Erro ao preparar a atualização da senha. Por favor, tente novamente.";
            $tipo_mensagem = 'danger';
            error_log("DB Prepare Error (Update Password definir_nova_senha.php): " . $mysqli->error);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkEase - Definir Nova Senha</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../cadastrar_logar/css/estilos_login.css">
     <style>
        .alert-success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .alert-message { padding: 10px 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: .25rem; text-align: left; }
    </style>
</head>
<body>
    <div class="screen login-screen">
        <h1>WorkEase</h1>
        <div class="form-container">
            <h2 class="login-title">Definir Nova Senha</h2>

            <div class="alert-message-container">
                <?php if (!empty($mensagem)) { echo '<div class="alert-message alert-' . $tipo_mensagem . '">' . $mensagem . '</div>'; } ?>
            </div>

            <?php if ($pode_redefinir): ?>
            <form action="definir_nova_senha.php" method="POST" novalidate>
                <div class="input-group password-wrapper">
                    <input type="password" id="nova_senha" name="nova_senha" placeholder="Digite sua nova senha" required>
                    <span class="toggle-password"><i class="fas fa-eye"></i></span>
                </div>
                <div class="input-group password-wrapper">
                    <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Confirme sua nova senha" required>
                    <span class="toggle-password"><i class="fas fa-eye"></i></span>
                </div>
                <button type="submit" class="btn btn-primary">Salvar Nova Senha</button>
            </form>
            <?php elseif ($tipo_mensagem === 'success'): ?>
                 <p style="text-align: center; margin-top: 20px;"><a href="login.php" class="btn btn-primary" style="text-decoration: none; display: inline-block; width: auto;">Ir para Login</a></p>
            <?php else: // Acesso inválido ou outro erro que impede redefinição ?>
                <p style="text-align: center; margin-top: 20px;"><a href="verificar_identidade.php" class="btn btn-primary" style="text-decoration: none; display: inline-block; width: auto;">Tentar Verificar Identidade Novamente</a></p>
            <?php endif; ?>
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