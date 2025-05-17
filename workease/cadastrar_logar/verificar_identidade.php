<?php
session_start();
include_once '../factory/conexao.php';

$mensagem = '';
$tipo_mensagem = '';
$etapa = 1; // 1 para pedir email, 2 para pedir resposta da pergunta
$email_usuario_input = ''; // Para repopular o campo de email
$pergunta_usuario_display = ''; // Para exibir a pergunta
$id_usuario_para_redefinir = null;

// Se o usuário está na etapa 2, preencher os campos com os dados da sessão
if (isset($_SESSION['id_usuario_recuperacao']) && isset($_SESSION['pergunta_usuario_recuperacao']) && isset($_SESSION['email_usuario_recuperacao'])) {
    $etapa = 2;
    $email_usuario_input = htmlspecialchars($_SESSION['email_usuario_recuperacao']);
    $pergunta_usuario_display = htmlspecialchars($_SESSION['pergunta_usuario_recuperacao']);
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['email_submit'])) { // Etapa 1: Enviou o email
        $email_digitado = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $email_usuario_input = htmlspecialchars($email_digitado); // Para repopular

        if (empty($email_digitado) || !filter_var($email_digitado, FILTER_VALIDATE_EMAIL)) {
            $mensagem = "Por favor, insira um e-mail válido.";
            $tipo_mensagem = 'danger';
        } else {
            $query = "SELECT id, pergunta_seguranca FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1";
            if ($stmt = $mysqli->prepare($query)) {
                $stmt->bind_param("s", $email_digitado);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $usuario = $result->fetch_assoc();
                    if (!empty($usuario['pergunta_seguranca'])) {
                        $_SESSION['id_usuario_recuperacao'] = $usuario['id'];
                        $_SESSION['pergunta_usuario_recuperacao'] = $usuario['pergunta_seguranca'];
                        $_SESSION['email_usuario_recuperacao'] = $email_digitado;
                        // Atualizar variáveis para exibição imediata na etapa 2
                        $etapa = 2;
                        $pergunta_usuario_display = htmlspecialchars($usuario['pergunta_seguranca']);
                        // Limpar mensagem anterior se houver
                        $mensagem = '';
                        $tipo_mensagem = '';
                    } else {
                        $mensagem = "Nenhuma pergunta de segurança configurada para esta conta. Entre em contato com o suporte.";
                        $tipo_mensagem = 'warning';
                    }
                } else {
                    $mensagem = "Usuário não encontrado ou inativo.";
                    $tipo_mensagem = 'danger';
                }
                $stmt->close();
            } else {
                $mensagem = "Erro ao consultar o banco. Tente novamente.";
                $tipo_mensagem = 'danger';
                error_log("DB Prepare Error (Verificar Identidade - Etapa 1): " . $mysqli->error);
            }
        }
    } elseif (isset($_POST['resposta_submit']) && isset($_SESSION['id_usuario_recuperacao'])) { // Etapa 2: Enviou a resposta
        $resposta_fornecida = trim($_POST['resposta_seguranca']);
        $id_usuario = $_SESSION['id_usuario_recuperacao'];
        // $email_usuario_input e $pergunta_usuario_display já foram setados no início se a sessão existir

        if (empty($resposta_fornecida)) {
            $mensagem = "A resposta de segurança é obrigatória.";
            $tipo_mensagem = 'danger';
            $etapa = 2; // Manter na etapa 2
        } else {
            $query = "SELECT resposta_seguranca_hash FROM usuarios WHERE id = ? LIMIT 1";
            if ($stmt = $mysqli->prepare($query)) {
                $stmt->bind_param("s", $id_usuario);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $usuario = $result->fetch_assoc();
                    // Comparar com a resposta normalizada (minúsculas)
                    if (password_verify(strtolower($resposta_fornecida), $usuario['resposta_seguranca_hash'])) {
                        $_SESSION['token_redefinicao_senha_permitida'] = bin2hex(random_bytes(16));
                        $_SESSION['id_usuario_para_redefinir'] = $id_usuario;
                        
                        unset($_SESSION['id_usuario_recuperacao']);
                        unset($_SESSION['pergunta_usuario_recuperacao']);
                        unset($_SESSION['email_usuario_recuperacao']);
                        
                        header("Location: definir_nova_senha.php");
                        exit;
                    } else {
                        $mensagem = "Resposta de segurança incorreta.";
                        $tipo_mensagem = 'danger';
                        $etapa = 2; // Manter na etapa 2
                    }
                }
                $stmt->close();
            } else {
                $mensagem = "Erro ao verificar a resposta. Tente novamente.";
                $tipo_mensagem = 'danger';
                $etapa = 2; // Manter na etapa 2
                error_log("DB Prepare Error (Verificar Identidade - Etapa 2): " . $mysqli->error);
            }
        }
    }
} else {
    // Limpar sessões de recuperação se acessando a página via GET (exceto se já estiver na etapa 2 por uma submissão anterior)
    if ($etapa == 1) { // Só limpa se realmente está na etapa 1 inicial
        unset($_SESSION['id_usuario_recuperacao']);
        unset($_SESSION['pergunta_usuario_recuperacao']);
        unset($_SESSION['email_usuario_recuperacao']);
    }
    // Sempre limpar estes ao carregar a página via GET
    unset($_SESSION['id_usuario_para_redefinir']);
    unset($_SESSION['token_redefinicao_senha_permitida']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkEase - Recuperar Senha</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../cadastrar_logar/css/estilos_login.css">
    <style>
        .alert-success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .alert-warning { background-color: #fff3cd; color: #856404; border-color: #ffeeba; }
        .alert-info { background-color: #d1ecf1; color: #0c5460; border-color: #bee5eb; }
        .alert-message { padding: 10px 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: .25rem; text-align: left; }
        .question-display { margin-bottom: 15px; padding: 10px; background-color: var(--dark-blue); border: 1px solid var(--gray-text); border-left: 3px solid var(--accent-color); color: var(--light-text); border-radius: 5px;}
    </style>
</head>
<body>
    <div class="screen login-screen">
        <a href="login.php" class="back-link"><i class="fas fa-arrow-left"></i> Voltar para Login</a>
        <h1>WorkEase</h1>
        <div class="form-container">
            <h2 class="login-title">Verificar Identidade</h2>

            <div class="alert-message-container">
                <?php if (!empty($mensagem)) { echo '<div class="alert-message alert-' . $tipo_mensagem . '">' . $mensagem . '</div>'; } ?>
            </div>

            <?php if ($etapa == 1): ?>
                <p style="text-align: center; margin-bottom: 20px; color: var(--gray-text);">Insira seu e-mail para continuar.</p>
                <form action="verificar_identidade.php" method="POST" novalidate>
                    <div class="input-group">
                        <input type="email" id="email" name="email" placeholder="Seu email de cadastro" required value="<?= $email_usuario_input ?>">
                    </div>
                    <button type="submit" name="email_submit" class="btn btn-primary">Continuar</button>
                </form>
            <?php elseif ($etapa == 2): ?>
                <p style="text-align: center; margin-bottom: 10px; color: var(--gray-text);">Responda à sua pergunta de segurança:</p>
                <div class="question-display">
                    <strong>Pergunta:</strong> <?= $pergunta_usuario_display ?>
                </div>
                <form action="verificar_identidade.php" method="POST" novalidate>
                    <div class="input-group">
                        <input type="text" id="resposta_seguranca" name="resposta_seguranca" placeholder="Sua resposta secreta" required autofocus>
                        <small style="color: var(--gray-text); display: block; margin-top: 5px;">A resposta é sensível a maiúsculas/minúsculas.</small>
                    </div>
                    <button type="submit" name="resposta_submit" class="btn btn-primary">Verificar Resposta</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>