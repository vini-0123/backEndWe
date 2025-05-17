<?php
session_start();

// Simulação de login se não estiver logado (para fins de teste)
if (!isset($_SESSION['user_id'])) {
    // Esta parte é apenas para simulação. Em um ambiente real, você teria um sistema de login.
    if (!isset($_SESSION['logged_in_simulated'])) { // Usar uma flag diferente para simulação
        $_SESSION['logged_in_simulated'] = true; // Marcar que a simulação ocorreu
        $_SESSION['user_name'] = 'Usuário Teste';
        $_SESSION['user_id'] = 'test-user-id'; // Um ID de usuário real viria do banco
        $_SESSION['user_email'] = 'teste@example.com';
    }
}

$isUserLoggedIn = isset($_SESSION['user_id']); // Verificar pelo ID de usuário real
$userName = $isUserLoggedIn && isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Visitante';
$userEmail = $isUserLoggedIn && isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : '';

$success = '';
$error = '';
$active_tab = 'senha'; // Aba padrão

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'alterar_senha') {
        $active_tab = 'senha';
        $senha_atual = $_POST['senha_atual'] ?? '';
        $nova_senha = $_POST['nova_senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';
        
        if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
            $error = 'Todos os campos de senha são obrigatórios.';
        } elseif ($senha_atual !== 'Senha123') { // Em um app real, verificaria hash da senha no DB
            $error = 'Senha atual incorreta.';
        } elseif (strlen($nova_senha) < 8) {
            $error = 'A nova senha deve ter pelo menos 8 caracteres.';
        } elseif ($nova_senha !== $confirmar_senha) {
            $error = 'As novas senhas não conferem.';
        } else {
            // Lógica para alterar a senha no banco de dados aqui
            $success = 'Senha alterada com sucesso!';
        }
    } elseif (isset($_POST['enviar_mensagem'])) {
        $active_tab = 'ajuda';
        // Lógica para processar a mensagem de contato aqui
        $success = "Mensagem enviada com sucesso (simulação)! Entraremos em contato em breve.";
        // Limpar POST para evitar reenvio F5 (ou redirecionar)
        $_POST = array(); 
        // header("Location: " . $_SERVER['PHP_SELF'] . "?tab=ajuda&success_contact=1"); exit; // Melhor abordagem
    }
}

// Lógica para mensagens de sucesso/erro via GET (ex: após redirecionamento)
if (isset($_GET['delete_success'])) {
    $active_tab = 'excluir';
    $success = "Solicitação de exclusão de conta processada (simulação).";
}
if (isset($_GET['delete_error'])) {
    $active_tab = 'excluir';
    $error = htmlspecialchars($_GET['delete_error']);
}
// if (isset($_GET['success_contact'])) {
//     $active_tab = 'ajuda';
//     $success = "Mensagem enviada com sucesso! Entraremos em contato em breve.";
// }

// Se uma aba específica foi solicitada via GET (ex: link de outro lugar)
if (isset($_GET['tab'])) {
    $allowed_tabs = ['senha', 'excluir', 'ajuda'];
    if (in_array($_GET['tab'], $allowed_tabs)) {
        $active_tab = $_GET['tab'];
    }
}


$companyName = "WorkEase";

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Acesso - <?= htmlspecialchars($companyName) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <!-- Bootstrap CSS (opcional, se você for usar mais componentes Bootstrap) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">


    <style>
        :root {
            --dark-blue: #01122E;
            --medium-blue: #0B4090;
            --accent-color: #4A90E2;
            --accent-color-hover: #63A5F2;
            --light-text: #B0E0FF;
            --gray-text: #88AACC;
            --white: #FFFFFF;
            --shadow-color: rgba(1, 18, 46, 0.5);
            --card-bg: #021A40;
            --input-bg: #011838;
            --input-border: #0B4090;
            --danger-bg: #4d1212;
            --danger-text: #ffdddd;
            --danger-border: #8c1c1c;
            --warning-bg: #504214;
            --warning-text: #fffacd;
            --warning-border: #876d17;
            --success-bg: #113e11;
            --success-text: #ccffcc;
            --success-border: #1a631a;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--dark-blue);
            color: var(--light-text);
            line-height: 1.7;
            font-size: 16px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .page-wrapper { flex-grow: 1; }
        .container-main { max-width: 960px; margin: 0 auto; padding: 0 20px; }
        h1, h2, h3, h4, h5 { color: var(--white); font-weight: 700; line-height: 1.3; margin-bottom: 0.75em; }
        h1 { font-size: clamp(1.8rem, 4vw, 2.5rem); margin-top: 2rem; margin-bottom: 1.5rem; text-align: center;}
        h2 { font-size: clamp(1.5rem, 3vw, 2rem); }
        h5 { font-size: 1.15rem; font-weight: 500; }
        p { color: var(--gray-text); margin-bottom: 1em; }
        a { color: var(--accent-color); text-decoration: none; transition: color 0.3s ease; }
        a:hover { color: var(--accent-color-hover); }

        /* Navbar Customizada */
        .navbar-custom {
            background-color: rgba(1, 18, 46, 0.9); /* Fundo com transparência */
            backdrop-filter: blur(8px); /* Efeito de desfoque no fundo */
            box-shadow: 0 2px 10px var(--shadow-color);
            padding-top: 0.8rem;
            padding-bottom: 0.8rem;
        }
        .navbar-custom .navbar-brand {
            font-size: 1.6em;
            font-weight: bold;
            color: var(--white);
        }
        .navbar-custom .nav-link {
            color: var(--light-text);
            font-size: 0.95em;
            padding-left: 1rem;
            padding-right: 1rem;
            transition: color 0.3s ease;
        }
        .navbar-custom .nav-link:hover,
        .navbar-custom .nav-link.active { /* Estilo para link ativo (se necessário) */
            color: var(--white);
        }
        .navbar-custom .dropdown-menu {
            background-color: var(--card-bg);
            border: 1px solid var(--medium-blue);
            box-shadow: 0 4px 15px var(--shadow-color);
        }
        .navbar-custom .dropdown-item {
            color: var(--light-text);
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .navbar-custom .dropdown-item:hover,
        .navbar-custom .dropdown-item:focus {
            background-color: var(--medium-blue);
            color: var(--white);
        }
        /* Estilo do ícone do toggler para tema escuro */
        .navbar-custom .navbar-toggler {
            border-color: var(--gray-text);
        }
        .navbar-custom .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28176, 224, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        /* Abas (Tabs) */
        .nav-tabs {
            border-bottom: 2px solid var(--medium-blue);
            margin-bottom: 2rem;
        }
        .nav-tabs .nav-link {
            background-color: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            color: var(--gray-text);
            padding: 0.75rem 1.25rem;
            font-weight: 500;
            transition: color 0.3s ease, border-color 0.3s ease;
            border-top-left-radius: 0.375rem;
            border-top-right-radius: 0.375rem;
        }
        .nav-tabs .nav-link:hover {
            color: var(--light-text);
            border-bottom-color: var(--accent-color-hover);
        }
        .nav-tabs .nav-link.active {
            color: var(--white);
            background-color: var(--medium-blue);
            border-color: var(--medium-blue) var(--medium-blue) var(--accent-color);
        }

        /* Cards */
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--input-border);
            border-radius: 0.5rem;
            box-shadow: 0 3px 15px var(--shadow-color);
            color: var(--light-text);
            margin-bottom: 1.5rem; /* Adicionado para espaçamento entre cards */
        }
        .card-header {
            background-color: var(--medium-blue);
            color: var(--white);
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--input-border);
            border-top-left-radius: calc(0.5rem - 1px);
            border-top-right-radius: calc(0.5rem - 1px);
        }
        .card-header h5 { margin-bottom: 0; font-size: 1.1rem; }
        .card-body { padding: 1.5rem; }
        .card ul { padding-left: 1.5rem; margin-bottom: 1rem; }
        .card ul li { margin-bottom: 0.3rem; color: var(--gray-text); }
        .card ul li strong { color: var(--light-text); }

        /* Formulários */
        .form-label { color: var(--light-text); font-weight: 500; margin-bottom: 0.5rem; }
        .form-control, .form-select {
            background-color: var(--input-bg);
            color: var(--light-text);
            border: 1px solid var(--input-border);
            border-radius: 0.375rem;
            padding: 0.6rem 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .form-control::placeholder { color: var(--gray-text); opacity: 0.7; }
        .form-control:focus, .form-select:focus {
            background-color: var(--input-bg);
            color: var(--white);
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.3); /* Bootstrap-like focus */
            outline: none;
        }
        .form-check-input { background-color: var(--input-bg); border: 1px solid var(--input-border); }
        .form-check-input:checked { background-color: var(--accent-color); border-color: var(--accent-color); }
        .form-check-input:focus { box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.3); }
        .form-check-label { color: var(--light-text); }
        .input-group .form-control { padding-right: 3rem; } /* Espaço para o botão de mostrar senha */
        .password-toggle-btn {
            position: absolute;
            top: 50%;
            right: 0.75rem;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: var(--gray-text);
            cursor: pointer;
            padding: 0.375rem 0.5rem;
            z-index: 100; /* Para ficar sobre o input */
        }
        .password-toggle-btn:hover { color: var(--light-text); }
        .password-toggle-btn i { font-size: 0.9em; }


        /* Botões */
        .btn { /* Estilos base para todos os botões, se necessário */
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            border-radius: 0.375rem;
            transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.1s ease;
            border: none; /* Removido para usar os do Bootstrap ou customizados */
        }
        .btn-primary-custom {
            background-color: var(--accent-color);
            color: var(--dark-blue); /* Ou var(--white) se preferir contraste */
        }
        .btn-primary-custom:hover {
            background-color: var(--accent-color-hover);
            color: var(--dark-blue);
            transform: translateY(-1px);
        }
        .btn-danger-custom {
            background-color: var(--danger-bg);
            color: var(--danger-text);
            border: 1px solid var(--danger-border);
        }
        .btn-danger-custom:hover {
            background-color: #6b1b1b; /* Um pouco mais escuro no hover */
            border-color: #a52a2a;
            color: var(--white);
            transform: translateY(-1px);
        }

        /* Alertas */
        .alert {
            border-radius: 0.375rem;
            padding: 1rem 1.25rem;
            border-left-width: 4px; /* Estilo de borda à esquerda */
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            /* Cores já definidas pelas classes Bootstrap, mas podemos sobrescrever se necessário */
        }
        .alert-success { background-color: var(--success-bg); color: var(--success-text); border-color: var(--success-border); }
        .alert-danger { background-color: var(--danger-bg); color: var(--danger-text); border-color: var(--danger-border); }
        .alert-warning { background-color: var(--warning-bg); color: var(--warning-text); border-color: var(--warning-border); }
        .alert-warning h6 strong { color: var(--white); }

        /* Accordion (para FAQ) */
        .accordion-item {
             background-color: var(--input-bg) !important; /* Forçar sobre o Bootstrap */
             border: 1px solid var(--input-border) !important;
             margin-bottom: 10px; /* Espaçamento entre itens do accordion */
        }
        .accordion-button {
            background-color: var(--medium-blue) !important;
            color: var(--white) !important;
            font-weight: 500;
        }
        .accordion-button:not(.collapsed) { /* Estilo quando aberto */
            background-color: var(--accent-color) !important;
            color: var(--dark-blue) !important;
        }
        .accordion-button:focus {
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.3) !important;
        }
        .accordion-button::after { /* Seta do accordion */
            filter: brightness(0) invert(1); /* Para a seta ficar branca */
        }
        .accordion-button:not(.collapsed)::after {
             filter: none; /* Seta padrão quando aberto e cor de fundo escura */
        }
        .accordion-body { color: var(--light-text); }


        /* Footer */
        footer.footer-custom {
            background-color: var(--medium-blue);
            padding: 30px 0;
            margin-top: auto; /* Empurra o footer para baixo */
            border-top: 2px solid var(--accent-color);
            text-align: center;
            color: var(--gray-text);
            font-size: 0.9em;
        }
        footer.footer-custom p { margin-bottom: 0.3rem; }
        footer.footer-custom a { color: var(--light-text); }
        footer.footer-custom a:hover { color: var(--accent-color-hover); }

        /* Botão Voltar ao Topo */
        #backToTopBtn {
            display: none; position: fixed; bottom: 25px; right: 25px; z-index: 999;
            border: none; outline: none; background-color: var(--accent-color);
            color: var(--dark-blue); cursor: pointer; padding: 0;
            border-radius: 50%; font-size: 1.5rem; width: 50px; height: 50px;
            box-shadow: 0 4px 10px var(--shadow-color);
            transition: background-color 0.3s ease, opacity 0.3s ease, transform 0.2s ease;
            opacity: 0.8; line-height: 50px; text-align: center; /* Para centralizar ícone */
        }
        #backToTopBtn:hover { background-color: var(--accent-color-hover); opacity: 1; transform: translateY(-2px); }

        /* Media Queries para Responsividade */
        @media (max-width: 991.98px) { /* Abaixo do lg, onde a navbar colapsa */
            .navbar-custom .navbar-nav {
                margin-top: 0.5rem; /* Espaço acima dos links no menu colapsado */
            }
             .navbar-custom .nav-link {
                padding-left: 0; /* Alinhar com toggler */
             }
        }

        @media (max-width: 768px) {
            h1 { font-size: 1.6rem; margin-top: 1.5rem; margin-bottom: 1rem;}
            .container-main { padding: 0 15px; }
            .nav-tabs .nav-link { padding: 0.6rem 0.8rem; font-size: 0.9rem; }
            .card-body { padding: 1rem; }
            .btn { font-size: 0.9rem; padding: 0.5rem 1.2rem; }
        }
         @media (max-width: 576px) {
            .nav-tabs { flex-direction: column; } /* Abas empilhadas */
            .nav-tabs .nav-item { width: 100%; }
            .nav-tabs .nav-link {
                text-align: center;
                margin-bottom: 2px; /* Pequeno espaço entre abas empilhadas */
                border-radius: 0.375rem; /* Bordas arredondadas em todas as abas */
            }
            .nav-tabs .nav-link.active {
                border-bottom-color: var(--accent-color); /* Mantém a indicação ativa */
            }
         }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <!-- Navbar Melhorada com Bootstrap -->
        <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
            <div class="container-fluid">
                <a class="navbar-brand" href="../site/index.php"><?= htmlspecialchars($companyName) ?></a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavMain" aria-controls="navbarNavMain" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNavMain">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="../site/index.php">Página Inicial</a>
                        </li>
                    </ul>
                    <ul class="navbar-nav">
                        <?php if ($isUserLoggedIn): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i> <?= $userName ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                                    <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user-edit me-2"></i>Meu Perfil</a></li>
                                    <li><a class="dropdown-item <?= ($active_tab === 'seguranca_geral' ? 'active' : '') // Exemplo se houvesse uma página de segurança geral ?>" href="controle_acesso.php"><i class="fas fa-shield-alt me-2"></i>Segurança</a></li>
                                    <li><a class="dropdown-item" href="notificacoes.php"><i class="fas fa-bell me-2"></i>Notificações</a></li>
                                    <li><hr class="dropdown-divider" style="border-color: var(--input-border);"></li>
                                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="login.php">Entrar</a></li>
                            <li class="nav-item"><a class="nav-link" href="cadastro.php">Cadastrar</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>

        <main class="container-main my-4 my-lg-5">
            <h1><i class="fas fa-user-shield me-2"></i>Controle de Acesso e Suporte</h1>
            
            <!-- Abas Bootstrap -->
            <ul class="nav nav-tabs mb-4" id="controleAcessoTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= ($active_tab === 'senha' ? 'active' : '') ?>" id="senha-tab" data-bs-toggle="tab" data-bs-target="#senha-tab-pane" type="button" role="tab" aria-controls="senha-tab-pane" aria-selected="<?= ($active_tab === 'senha' ? 'true' : 'false') ?>">
                        <i class="fas fa-key me-1" aria-hidden="true"></i>Alterar Senha
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= ($active_tab === 'excluir' ? 'active' : '') ?>" id="excluir-tab" data-bs-toggle="tab" data-bs-target="#excluir-tab-pane" type="button" role="tab" aria-controls="excluir-tab-pane" aria-selected="<?= ($active_tab === 'excluir' ? 'true' : 'false') ?>">
                        <i class="fas fa-user-times me-1" aria-hidden="true"></i>Excluir Conta
                    </button>
                </li>
                 <li class="nav-item" role="presentation">
                    <button class="nav-link <?= ($active_tab === 'ajuda' ? 'active' : '') ?>" id="ajuda-tab" data-bs-toggle="tab" data-bs-target="#ajuda-tab-pane" type="button" role="tab" aria-controls="ajuda-tab-pane" aria-selected="<?= ($active_tab === 'ajuda' ? 'true' : 'false') ?>">
                        <i class="fas fa-life-ring me-1" aria-hidden="true"></i>Ajuda e Suporte
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="controleAcessoTabContent">
                <!-- Aba Alterar Senha -->
                <div class="tab-pane fade <?= ($active_tab === 'senha' ? 'show active' : '') ?>" id="senha-tab-pane" role="tabpanel" aria-labelledby="senha-tab" tabindex="0">
                    <?php if ($active_tab === 'senha' && $success): ?><div class="alert alert-success" role="alert"><?= $success; ?></div><?php endif; ?>
                    <?php if ($active_tab === 'senha' && $error): ?><div class="alert alert-danger" role="alert"><?= $error; ?></div><?php endif; ?>
                    
                    <div class="card mb-4">
                        <div class="card-header"><h5><i class="fas fa-lock me-2" aria-hidden="true"></i>Política de Senha</h5></div>
                        <div class="card-body">
                            <p>Para garantir a segurança da sua conta, sua senha deve:</p>
                            <ul>
                                <li>Ter pelo menos 8 caracteres</li>
                                <li>Incluir pelo menos uma letra maiúscula (A-Z)</li>
                                <li>Incluir pelo menos uma letra minúscula (a-z)</li>
                                <li>Incluir pelo menos um número (0-9)</li>
                                <li>Opcional, mas recomendado: um caractere especial (ex: !@#$%)</li>
                                <li>Não conter informações pessoais facilmente identificáveis (ex: nome, data de nascimento).</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header"><h5>Alterar sua Senha</h5></div>
                        <div class="card-body">
                            <form method="post" action="controle_acesso.php#senha" aria-labelledby="alterar-senha-heading">
                                <input type="hidden" name="action" value="alterar_senha">
                                <div class="mb-3">
                                    <label for="senha_atual" class="form-label">Senha atual</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="senha_atual" name="senha_atual" required aria-describedby="senha_atual_feedback">
                                        <button class="btn password-toggle-btn" type="button" aria-label="Mostrar senha atual" data-target-input="senha_atual"><i class="fas fa-eye"></i></button>
                                    </div>
                                    <div id="senha_atual_feedback" class="invalid-feedback"></div> <!-- Para JS validation -->
                                </div>
                                <div class="mb-3">
                                    <label for="nova_senha" class="form-label">Nova senha</label>
                                     <div class="input-group">
                                        <input type="password" class="form-control" id="nova_senha" name="nova_senha" required aria-describedby="nova_senha_feedback nova_senha_politica">
                                        <button class="btn password-toggle-btn" type="button" aria-label="Mostrar nova senha" data-target-input="nova_senha"><i class="fas fa-eye"></i></button>
                                    </div>
                                    <small id="nova_senha_politica" class="form-text" style="color: var(--gray-text);">Mínimo 8 caracteres, com maiúsculas, minúsculas e números.</small>
                                    <div id="nova_senha_feedback" class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirmar_senha" class="form-label">Confirmar nova senha</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required aria-describedby="confirmar_senha_feedback">
                                        <button class="btn password-toggle-btn" type="button" aria-label="Mostrar confirmação de senha" data-target-input="confirmar_senha"><i class="fas fa-eye"></i></button>
                                    </div>
                                    <div id="confirmar_senha_feedback" class="invalid-feedback"></div>
                                </div>
                                <button type="submit" class="btn btn-primary-custom"><i class="fas fa-save me-2" aria-hidden="true"></i>Alterar senha</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Aba Excluir Conta -->
                <div class="tab-pane fade <?= ($active_tab === 'excluir' ? 'show active' : '') ?>" id="excluir-tab-pane" role="tabpanel" aria-labelledby="excluir-tab" tabindex="0">
                     <?php if ($active_tab === 'excluir' && $success): ?><div class="alert alert-success" role="alert"><?= $success; ?></div><?php endif; ?>
                     <?php if ($active_tab === 'excluir' && $error): ?><div class="alert alert-danger" role="alert"><?= $error; ?></div><?php endif; ?>
                    <div class="card">
                        <div class="card-header" style="background-color: var(--danger-bg); border-bottom-color: var(--danger-border);">
                            <h5 style="color: var(--danger-text);"><i class="fas fa-exclamation-triangle me-2" aria-hidden="true"></i>Excluir Conta Permanentemente</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning" role="alert">
                                <h6 class="alert-heading"><strong>Atenção!</strong> Esta ação não pode ser desfeita.</h6>
                                <p class="mb-0">Ao excluir sua conta, você perderá permanentemente todos os seus dados, histórico de atividades e configurações personalizadas.</p>
                            </div>
                            <p>Se você tem certeza, por favor, confirme abaixo:</p>
                            <form method="post" action="excluir_conta.php" onsubmit="return confirm('Tem certeza ABSOLUTA que deseja excluir sua conta permanentemente? Esta ação é IRREVERSÍVEL.');" aria-labelledby="excluir-conta-heading">
                                <div class="mb-3">
                                    <label for="confirmar_email_excluir" class="form-label">Para confirmar, digite seu e-mail cadastrado</label>
                                    <input type="email" class="form-control" id="confirmar_email_excluir" name="confirmar_email" required placeholder="<?= htmlspecialchars($userEmail) ?>" aria-describedby="confirmar_email_excluir_feedback" autocomplete="email">
                                     <div id="confirmar_email_excluir_feedback" class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="senha_confirmar_excluir" class="form-label">Digite sua senha atual</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="senha_confirmar_excluir" name="senha_confirmar" required aria-describedby="senha_confirmar_excluir_feedback" autocomplete="current-password">
                                        <button class="btn password-toggle-btn" type="button" aria-label="Mostrar senha para exclusão" data-target-input="senha_confirmar_excluir"><i class="fas fa-eye"></i></button>
                                    </div>
                                     <div id="senha_confirmar_excluir_feedback" class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="confirmar_exclusao_check" name="confirmar_exclusao_check" required aria-describedby="confirmar_exclusao_check_feedback">
                                    <label class="form-check-label" for="confirmar_exclusao_check">
                                        Compreendo que esta ação é irreversível e concordo em excluir minha conta e todos os meus dados permanentemente.
                                    </label>
                                    <div id="confirmar_exclusao_check_feedback" class="invalid-feedback">Você deve confirmar para prosseguir.</div>
                                </div>
                                <button type="submit" class="btn btn-danger-custom w-100"><i class="fas fa-trash-alt me-2" aria-hidden="true"></i>Excluir Minha Conta Permanentemente</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Aba Ajuda e Suporte -->
                <div class="tab-pane fade <?= ($active_tab === 'ajuda' ? 'show active' : '') ?>" id="ajuda-tab-pane" role="tabpanel" aria-labelledby="ajuda-tab" tabindex="0">
                    <?php if ($active_tab === 'ajuda' && $success): ?><div class="alert alert-success" role="alert"><?= $success; ?></div><?php endif; ?>
                    <?php if ($active_tab === 'ajuda' && $error): ?><div class="alert alert-danger" role="alert"><?= $error; ?></div><?php endif; ?>

                    <div class="row">
                        <div class="col-lg-7 mb-4 mb-lg-0">
                            <div class="card">
                                <div class="card-header"><h5><i class="fas fa-question-circle me-2" aria-hidden="true"></i>Perguntas Frequentes (FAQ)</h5></div>
                                <div class="card-body">
                                    <div class="accordion" id="accordionFAQ">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="faqHeadingOne">
                                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseOne" aria-expanded="true" aria-controls="faqCollapseOne">
                                                    Como atualizar meus dados pessoais?
                                                </button>
                                            </h2>
                                            <div id="faqCollapseOne" class="accordion-collapse collapse show" aria-labelledby="faqHeadingOne" data-bs-parent="#accordionFAQ">
                                                <div class="accordion-body">
                                                    Para atualizar seus dados, acesse a seção "Meu Perfil" no menu do usuário. Lá você poderá editar informações como telefone e endereço. Seu nome e e-mail não podem ser alterados diretamente por questões de segurança; contate o suporte se necessário.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="faqHeadingTwo">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseTwo" aria-expanded="false" aria-controls="faqCollapseTwo">
                                                    Esqueci minha senha, e agora?
                                                </button>
                                            </h2>
                                            <div id="faqCollapseTwo" class="accordion-collapse collapse" aria-labelledby="faqHeadingTwo" data-bs-parent="#accordionFAQ">
                                                <div class="accordion-body">
                                                    Se você esqueceu sua senha, utilize o link "Esqueci minha senha" na <a href="login.php">página de login</a>. Você receberá instruções por e-mail para redefinir sua senha de forma segura.
                                                </div>
                                            </div>
                                        </div>
                                         <div class="accordion-item">
                                            <h2 class="accordion-header" id="faqHeadingThree">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseThree" aria-expanded="false" aria-controls="faqCollapseThree">
                                                    Onde configuro notificações?
                                                </button>
                                            </h2>
                                            <div id="faqCollapseThree" class="accordion-collapse collapse" aria-labelledby="faqHeadingThree" data-bs-parent="#accordionFAQ">
                                                <div class="accordion-body">
                                                    As configurações de notificação podem ser acessadas em "Notificações" no menu do usuário. Você pode personalizar quais alertas deseja receber e por quais canais (e-mail, app, etc.).
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                             <div class="card">
                                <div class="card-header"><h5><i class="fas fa-envelope me-2" aria-hidden="true"></i>Entre em Contato</h5></div>
                                <div class="card-body">
                                    <p>Se precisar de ajuda adicional ou tiver alguma dúvida não respondida, preencha o formulário abaixo:</p>
                                    <form method="post" action="controle_acesso.php#ajuda" aria-labelledby="contato-heading">
                                        <input type="hidden" name="enviar_mensagem" value="1">
                                        <div class="mb-3">
                                            <label for="contato_nome" class="form-label">Seu Nome</label>
                                            <input type="text" class="form-control" id="contato_nome" name="contato_nome" value="<?= $userName ?>" required autocomplete="name">
                                        </div>
                                        <div class="mb-3">
                                            <label for="contato_email" class="form-label">Seu E-mail</label>
                                            <input type="email" class="form-control" id="contato_email" name="contato_email" value="<?= $userEmail ?>" required autocomplete="email">
                                        </div>
                                        <div class="mb-3">
                                            <label for="contato_assunto" class="form-label">Assunto</label>
                                            <select class="form-select" id="contato_assunto" name="contato_assunto" required>
                                                <option value="">Selecione...</option>
                                                <option value="duvida_geral">Dúvida Geral</option>
                                                <option value="problema_tecnico">Problema Técnico</option>
                                                <option value="sugestao_melhoria">Sugestão de Melhoria</option>
                                                <option value="parcerias">Parcerias</option>
                                                <option value="outro_contato">Outro</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="contato_mensagem" class="form-label">Sua Mensagem</label>
                                            <textarea class="form-control" id="contato_mensagem" name="contato_mensagem" rows="4" required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary-custom w-100"><i class="fas fa-paper-plane me-2" aria-hidden="true"></i>Enviar Mensagem</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <footer class="footer-custom">
        <div class="container-main">
            <p>© <?= date("Y") ?> <?= htmlspecialchars($companyName) ?>. Todos os direitos reservados.</p>
            <p>
                <a href="politica_privacidade.php" class="me-3">Política de Privacidade</a>
                <a href="termos_uso.php">Termos de Uso</a>
            </p>
        </div>
    </footer>

    <button id="backToTopBtn" title="Voltar ao topo" aria-label="Voltar ao topo"><i class="fas fa-arrow-up" aria-hidden="true"></i></button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            'use strict';

            // Botão Voltar ao Topo
            let mybutton = document.getElementById("backToTopBtn");
            if (mybutton) {
                function scrollFunction() {
                    const scrollTop = (document.documentElement && document.documentElement.scrollTop) || document.body.scrollTop;
                    if (scrollTop > 100) { mybutton.style.display = "block"; } else { mybutton.style.display = "none"; }
                }
                window.onscroll = scrollFunction;
                mybutton.addEventListener('click', function(){ window.scrollTo({top: 0, behavior: 'smooth'}); });
            }

            // Lógica para manter a aba ativa com base no PHP ou Hash da URL
            // (O próprio Bootstrap 5 lida com a ativação inicial se as classes 'active' e 'show' estiverem corretas no HTML)
            // Este script pode ajudar a garantir que, se o hash mudar dinamicamente, a aba correspondente seja mostrada.
            function activateTabFromHash() {
                if (window.location.hash) {
                    // O hash na URL pode ser o ID do painel da aba (ex: #senha-tab-pane)
                    // ou um identificador mais curto (ex: #senha)
                    let hash = window.location.hash;
                    let tabTriggerEl = document.querySelector(`button[data-bs-target="${hash}"], button[aria-controls="${hash.substring(1)}"]`);
                    
                    // Tenta encontrar pelo ID do botão da aba (ex: #senha-tab)
                    if (!tabTriggerEl) {
                        tabTriggerEl = document.querySelector(hash + '-tab');
                    }
                    // Tenta encontrar pela parte principal do ID do painel (ex: #senha-tab-pane -> #senha-tab)
                    if (!tabTriggerEl && hash.endsWith('-tab-pane')) {
                         tabTriggerEl = document.getElementById(hash.substring(1, hash.length - 9) + 'tab'); // remove # e -tab-pane, adiciona -tab
                    }

                    if (tabTriggerEl) {
                        var tab = new bootstrap.Tab(tabTriggerEl);
                        tab.show();
                    }
                }
            }

            // Ativar aba ao carregar a página se houver hash
            // A lógica PHP já define a classe 'active', mas o hash pode ter prioridade
            // ou ser usado para navegação interna.
            window.addEventListener('DOMContentLoaded', activateTabFromHash);

            // Se os links de abas mudarem o hash (o que não é o caso com os botões)
            // window.addEventListener('hashchange', activateTabFromHash, false);

            // Botão de mostrar/ocultar senha
            const passwordToggleButtons = document.querySelectorAll('.password-toggle-btn');
            passwordToggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetInputId = this.getAttribute('data-target-input');
                    const targetInput = document.getElementById(targetInputId);
                    if (targetInput) {
                        if (targetInput.type === 'password') {
                            targetInput.type = 'text';
                            this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                            this.setAttribute('aria-label', this.getAttribute('aria-label').replace('Mostrar', 'Ocultar'));
                        } else {
                            targetInput.type = 'password';
                            this.innerHTML = '<i class="fas fa-eye"></i>';
                            this.setAttribute('aria-label', this.getAttribute('aria-label').replace('Ocultar', 'Mostrar'));
                        }
                    }
                });
            });

            // Para manter a aba ativa correta se o PHP a definir e não houver hash,
            // a lógica no PHP que adiciona 'active' e 'show' já deve ser suficiente.
            // O script abaixo é mais para o caso de mudar o hash via JS e querer que a aba reflita isso
            // ou se os links das abas usassem href="#aba-id".
            // Para os botões, o Bootstrap faz a mágica ao clicar.
            // A variável $active_tab no PHP já cuida da aba inicial.
            // Se você quiser que a URL reflita a aba ativa ao clicar:
            var tabElList = [].slice.call(document.querySelectorAll('#controleAcessoTab button[data-bs-toggle="tab"]'));
            tabElList.forEach(function (tabEl) {
              tabEl.addEventListener('shown.bs.tab', function (event) {
                // event.target // newly activated tab
                // event.relatedTarget // previous active tab
                // Construir um hash limpo, por exemplo, 'senha' a partir de 'senha-tab-pane'
                let newHash = event.target.getAttribute('aria-controls');
                if (newHash && newHash.endsWith('-tab-pane')) {
                    newHash = newHash.slice(0, -9); // remove '-tab-pane'
                }
                // Atualizar o hash da URL sem recarregar a página
                // Cuidado: isso pode interferir se você já tem lógica de hash.
                // history.replaceState(null, null, '#' + newHash);
              });
            });

        })();
    </script>
</body>
</html>