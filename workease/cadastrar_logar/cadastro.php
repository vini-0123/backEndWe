<?php
session_start();
include_once '../factory/conexao.php'; // Verifique o caminho

$erro = '';
$sucesso = '';

// Função para gerar UUID
function guidv4($data = null) {
    $data = $data ?? random_bytes(16);
    assert(strlen($data) == 16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Função para validar a complexidade da senha
function validarComplexidadeSenha($senha) {
    $erros_senha = [];
    if (strlen($senha) < 8) {
        $erros_senha[] = "Pelo menos 8 caracteres.";
    }
    if (!preg_match('/[A-Z]/', $senha)) {
        $erros_senha[] = "Pelo menos uma letra maiúscula.";
    }
    if (!preg_match('/[a-z]/', $senha)) {
        $erros_senha[] = "Pelo menos uma letra minúscula.";
    }
    if (!preg_match('/[0-9]/', $senha)) {
        $erros_senha[] = "Pelo menos um número.";
    }
    if (!preg_match('/[\W_]/', $senha)) { // \W corresponde a qualquer caractere não alfanumérico, _ é para underscore
        $erros_senha[] = "Pelo menos um caractere especial (ex: #, @, !).";
    }
    return $erros_senha;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cadastro_manual'])) {
    // Correção na sanitização de nome e email
    $nome = filter_input(INPUT_POST, 'nome');
    $email = filter_input(INPUT_POST, 'email');
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha)) {
        $erro = "Todos os campos são obrigatórios para cadastro manual.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Formato de email inválido.";
    } elseif ($senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem.";
    } else {
        $erros_complexidade = validarComplexidadeSenha($senha);
        if (!empty($erros_complexidade)) {
            $erro = "A senha não atende aos seguintes requisitos: <ul><li>" . implode("</li><li>", $erros_complexidade) . "</li></ul>";
        } else {
            $query_check_email = "SELECT id FROM usuarios WHERE email = ? LIMIT 1";
            if ($stmt_check = $mysqli->prepare($query_check_email)) {
                $stmt_check->bind_param("s", $email);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();

                if ($result_check->num_rows > 0) {
                    $erro = "Este email já está cadastrado. Tente fazer <a href='login.php'>login</a> ou use uma conta social.";
                } else {
                    $id_usuario = guidv4();
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                    $query_insert = "INSERT INTO usuarios (id, nome, email, senha, nivel_acesso, ativo, data_cadastro)
                                     VALUES (?, ?, ?, ?, 'user', 1, NOW())";

                    if ($stmt_insert = $mysqli->prepare($query_insert)) {
                        $stmt_insert->bind_param("ssss", $id_usuario, $nome, $email, $senha_hash);
                        if ($stmt_insert->execute()) {
                            $sucesso = "Cadastro realizado com sucesso! Você já pode fazer <a href='login.php'>login</a>.";
                            $_POST = array();
                        } else {
                            $erro = "Erro ao realizar o cadastro. Tente novamente.";
                            error_log("DB Execute Error (Cadastro): " . $mysqli->error);
                        }
                        $stmt_insert->close();
                    } else {
                        $erro = "Erro ao preparar o cadastro. Tente novamente.";
                        error_log("DB Prepare Error (Cadastro): " . $mysqli->error);
                    }
                }
                $stmt_check->close();
            } else {
                $erro = "Erro ao verificar o email. Tente novamente.";
                error_log("DB Prepare Error (Check Email Cadastro): " . $mysqli->error);
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
    <title>WorkEase - Cadastro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Assumindo que o CSS que você forneceu se chama estilos_cadastro.css e está no caminho correto -->
    <link rel="stylesheet" href="../cadastrar_logar/css/estilo_cadastro.css">
    <!-- Adicionando o Google Fonts aqui também, caso não esteja no estilos_cadastro.css -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- O CSS para password-policy e os alertas pode ser movido para estilos_cadastro.css se preferir -->
    <style>
        /* Estes estilos são para garantir que os alertas e a política de senha funcionem bem
           com as variáveis de cor do seu CSS principal.
           Considere movê-los para o seu arquivo estilos_cadastro.css principal. */

        .alert-message-container {
            margin-bottom: 20px;
            width: 100%;
            min-height: 1px; /* Para evitar colapso se vazio */
        }
        .alert-message {
            padding: 12px 15px;
            border-radius: var(--radius-sm, 6px); /* Usando variável do seu CSS se existir */
            font-size: 0.9em;
            text-align: center; /* Alterado para center para combinar com o design geral */
            width: 100%;
            box-sizing: border-box;
            margin: 0 auto 15px auto; /* Adicionado margin-bottom */
            line-height: 1.4;
            opacity: 0;
            animation: subtleFadeIn 0.5s ease-out forwards;
        }
        .alert-message.no-animate {
            opacity: 1;
            animation: none;
        }
        .alert-success {
            background-color: var(--accent-color);
            color: var(--dark-blue);
            border: 1px solid var(--accent-color-hover);
        }
        .alert-danger {
            background-color: var(--dark-blue); /* Tom mais escuro que o fundo do form */
            color: var(--light-text);
            border: 1px solid var(--gray-text); /* Usando var(--gray-text) para a borda */
        }
        .alert-danger a {
            color: var(--accent-color);
            font-weight: bold;
            text-decoration: underline;
        }
        .alert-danger a:hover {
            color: var(--accent-color-hover);
        }
        .alert-message ul {
            margin-top: 5px;
            margin-bottom: 0;
            padding-left: 20px;
            text-align: left; /* Alinhar itens da lista à esquerda dentro do alerta */
        }

        .password-policy {
            font-size: 0.85em;
            color: var(--gray-text);
            margin-top: -10px; /* Ajuste para ficar mais próximo do campo de senha */
            margin-bottom: 15px;
            padding-left: 5px; /* Pequeno recuo */
            text-align: left;
        }
        .password-policy ul {
            list-style: none;
            padding-left: 0;
            margin-top: 5px;
        }
        .password-policy li {
            margin-bottom: 3px;
            display: flex; /* Para alinhar ícone e texto */
            align-items: center; /* Alinhar verticalmente */
        }
        .password-policy li.valid { color: #B0E0FF; /* Ajustado para um azul claro que contrasta bem no tema escuro */ }
        .password-policy li.invalid { color: #FF7F7F; /* Um vermelho mais claro para o tema escuro */ }
        .password-policy li i {
            margin-right: 8px; /* Espaço entre ícone e texto */
            font-size: 0.9em; /* Tamanho do ícone */
        }
    </style>
</head>
<body>
    <!-- A tag <h1>WorkEase</h1> foi movida para dentro de .screen para consistência,
         ou você pode ajustar o CSS para o h1 global se preferir.
         No CSS fornecido, `h1` já tem um estilo global que pode ser o desejado.
         Se o `<h1>` do cadastro deve ser diferente, use uma classe específica.
    -->
    <div class="screen"> <!-- Removida a classe login-screen se estilos_cadastro.css já cuida do fundo -->
        <a href="../site/index.php" class="back-link"><i class="fas fa-arrow-left"></i> Voltar</a>
        <h1>WorkEase</h1> <!-- Se este h1 deve seguir o estilo global definido no seu CSS -->

        <div class="form-container">
            <h2 class="login-title">Criar Conta</h2> <!-- Reutilizando classe para título do formulário -->

            <div class="alert-message-container">
                <?php
                if (!empty($erro)) { echo '<div class="alert-message alert-danger' . (isset($_POST['cadastro_manual']) || isset($_SESSION['oauth_error_just_set']) ? ' no-animate' : '') . '">' . $erro . '</div>';
                    if (isset($_SESSION['oauth_error_just_set'])) unset($_SESSION['oauth_error_just_set']);
                }
                if (!empty($sucesso)) { echo '<div class="alert-message alert-success no-animate">' . $sucesso . '</div>'; }
                ?>
            </div>

            <?php if (empty($sucesso)): ?>
            <a href="social_auth.php?provider=Google&action=register" class="btn btn-social google" style="margin-bottom: 15px;">
                 <i class="fab fa-google" style="color: var(--color-google);"></i> Cadastrar com Google
            </a>
            <!-- Para o botão do LinkedIn, se for usar:
            <a href="social_auth.php?provider=LinkedIn&action=register" class="btn btn-social linkedin" style="margin-bottom: 15px;">
                 <i class="fab fa-linkedin" style="color: var(--color-linkedin);"></i> Cadastrar com LinkedIn
            </a>
             -->
            <p class="separator">ou cadastre-se manualmente</p>

            <form action="cadastro.php" method="POST" novalidate>
                <input type="hidden" name="cadastro_manual" value="1">
                <div class="input-group">
                    <input type="text" id="nome" name="nome" placeholder="Nome da empresa ou seu nome" class="company-name" required value="<?= isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : '' ?>">
                </div>
                <div class="input-group">
                    <input type="email" id="email" name="email" placeholder="Seu melhor email" class="company-email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
                <div class="input-group password-wrapper">
                    <input type="password" id="senha" name="senha" placeholder="Crie uma senha forte" class="company-password" required aria-describedby="passwordHelp">
                    <span class="toggle-password"><i class="fas fa-eye"></i></span>
                </div>
                <div id="passwordHelp" class="password-policy">
                    Sua senha deve ter:
                    <ul>
                        <li id="length" class="invalid"><i class="fas fa-times-circle"></i> Pelo menos 8 caracteres</li>
                        <li id="uppercase" class="invalid"><i class="fas fa-times-circle"></i> Pelo menos uma letra maiúscula (A-Z)</li>
                        <li id="lowercase" class="invalid"><i class="fas fa-times-circle"></i> Pelo menos uma letra minúscula (a-z)</li>
                        <li id="number" class="invalid"><i class="fas fa-times-circle"></i> Pelo menos um número (0-9)</li>
                        <li id="special" class="invalid"><i class="fas fa-times-circle"></i> Pelo menos um caractere especial (#, @, !, etc.)</li>
                    </ul>
                </div>
                <div class="input-group password-wrapper">
                    <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Confirme sua senha" class="company-password" required>
                    <span class="toggle-password"><i class="fas fa-eye"></i></span>
                </div>

                <button type="submit" class="btn btn-primary">Cadastrar Manualmente</button>
            </form>
            <?php endif; ?>

            <p class="extra-link">Já tem uma conta? <a href="login.php">Faça Login</a></p>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('senha');
        const lengthCheck = document.getElementById('length');
        const uppercaseCheck = document.getElementById('uppercase');
        const lowercaseCheck = document.getElementById('lowercase');
        const numberCheck = document.getElementById('number');
        const specialCheck = document.getElementById('special');

        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                validatePasswordPolicy(password);
            });
            // Validar ao carregar a página caso o campo já esteja preenchido (ex: erro de formulário)
            if(passwordInput.value){
                validatePasswordPolicy(passwordInput.value);
            }
        }

        function validatePasswordPolicy(password) {
            updatePolicyCheck(lengthCheck, password.length >= 8);
            updatePolicyCheck(uppercaseCheck, /[A-Z]/.test(password));
            updatePolicyCheck(lowercaseCheck, /[a-z]/.test(password));
            updatePolicyCheck(numberCheck, /[0-9]/.test(password));
            updatePolicyCheck(specialCheck, /[\W_]/.test(password));
        }

        function updatePolicyCheck(element, isValid) {
            if (element) {
                const icon = element.querySelector('i');
                if (isValid) {
                    element.classList.remove('invalid');
                    element.classList.add('valid');
                    icon.classList.remove('fa-times-circle');
                    icon.classList.add('fa-check-circle');
                } else {
                    element.classList.remove('valid');
                    element.classList.add('invalid');
                    icon.classList.remove('fa-check-circle');
                    icon.classList.add('fa-times-circle');
                }
            }
        }

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