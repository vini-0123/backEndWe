<?php
session_start();

// Simulação de login se não estiver logado (para fins de teste desta página isoladamente)
if (!isset($_SESSION['user_id'])) {
    // Em um ambiente real, você redirecionaria para login.php
    header('Location: ../cadastrar_logar/login.php');
    exit;
    
    // Para teste, vamos simular um usuário logado:
    /*
    $_SESSION['user_id'] = 'test-user-id-notif';
    $_SESSION['user_nome'] = 'Usuário de Teste Notif'; // Changed from user_name to user_nome
    $_SESSION['email'] = 'notif.teste@example.com';
    */
}

$companyName = "WorkEase";
$success = '';
$error = '';

// Configurações de notificação simuladas (em um app real, viriam do banco de dados)
$userNotificationSettings = isset($_SESSION['notification_settings']) ? $_SESSION['notification_settings'] : [
    'email_novas_tarefas' => true,
    'email_lembretes_prazo' => true,
    'email_atualizacoes_projeto' => false,
    'email_newsletter' => true,
    'inapp_mencoes' => true,
    'inapp_comentarios' => true,
    'frequencia_resumo_semanal' => 'semanal', // 'nunca', 'diario', 'semanal'
    'notificacoes_push_mobile' => false,
];

// Simulação de atualização das configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_config_notificacoes'])) {
    $userNotificationSettings['email_novas_tarefas'] = isset($_POST['email_novas_tarefas']);
    $userNotificationSettings['email_lembretes_prazo'] = isset($_POST['email_lembretes_prazo']);
    $userNotificationSettings['email_atualizacoes_projeto'] = isset($_POST['email_atualizacoes_projeto']);
    $userNotificationSettings['email_newsletter'] = isset($_POST['email_newsletter']);
    $userNotificationSettings['inapp_mencoes'] = isset($_POST['inapp_mencoes']);
    $userNotificationSettings['inapp_comentarios'] = isset($_POST['inapp_comentarios']);
    $userNotificationSettings['frequencia_resumo_semanal'] = htmlspecialchars($_POST['frequencia_resumo_semanal'] ?? 'semanal');
    $userNotificationSettings['notificacoes_push_mobile'] = isset($_POST['notificacoes_push_mobile']);

    $_SESSION['notification_settings'] = $userNotificationSettings; // Salva na sessão para simulação
    // Em um app real, salvaria no banco de dados
    $success = 'Configurações de notificação atualizadas com sucesso!';
}

// Notificações simuladas (em um app real, viriam do banco de dados)
$notifications = [
    ['id' => 1, 'tipo' => 'tarefa', 'titulo' => 'Nova tarefa atribuída', 'mensagem' => 'Você foi atribuído à tarefa "Revisar design do dashboard".', 'data' => '2023-10-27 10:00', 'lida' => false, 'link' => 'taskflow/tarefa.php?id=123'],
    ['id' => 2, 'tipo' => 'lembrete', 'titulo' => 'Lembrete de Prazo', 'mensagem' => 'A tarefa "Finalizar relatório trimestral" vence amanhã.', 'data' => '2023-10-26 15:30', 'lida' => false, 'link' => 'taskflow/tarefa.php?id=120'],
    ['id' => 3, 'tipo' => 'sistema', 'titulo' => 'Atualização do Sistema', 'mensagem' => 'Manutenção programada para domingo às 02:00 AM.', 'data' => '2023-10-26 09:00', 'lida' => true, 'link' => '#'],
    ['id' => 4, 'tipo' => 'mencao', 'titulo' => 'Você foi mencionado', 'mensagem' => '@Maria te mencionou no projeto "Lançamento Alpha".', 'data' => '2023-10-25 17:45', 'lida' => false, 'link' => 'taskflow/projeto.php?id=10&comment=55'],
    ['id' => 5, 'tipo' => 'comentario', 'titulo' => 'Novo comentário', 'mensagem' => 'João comentou na sua tarefa "Planejamento Sprint Q4".', 'data' => '2023-10-24 11:20', 'lida' => true, 'link' => 'taskflow/tarefa.php?id=115#comentarios'],
];

// Simulação de ações nas notificações
if (isset($_GET['action']) && isset($_GET['notif_id'])) {
    $notifId = (int)$_GET['notif_id'];
    if ($_GET['action'] === 'mark_read') {
        // Lógica para marcar como lida no DB
        $success = "Notificação #{$notifId} marcada como lida (simulação).";
        // Poderia recarregar a página ou atualizar via JS
    } elseif ($_GET['action'] === 'delete') {
        // Lógica para excluir no DB
        $success = "Notificação #{$notifId} excluída (simulação).";
    }
    // Para evitar que a ação se repita no refresh, redirecionar ou limpar GET params
    // header('Location: notificacoes.php'); exit; (melhor abordagem)
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações - <?= htmlspecialchars($companyName) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
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
            --input-disabled-bg: #01122e;
            --input-disabled-border: #08306b;
            --danger-bg: #4d1212;
            --danger-text: #ffdddd;
            --danger-border: #8c1c1c;
            --success-bg: #113e11;
            --success-text: #ccffcc;
            --success-border: #1a631a;
            --notification-unread-bg: #032250; /* Fundo para notificação não lida */
            --notification-border: #052a66;
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
        h5 { font-size: 1.15rem; font-weight: 500; }
        p { color: var(--gray-text); margin-bottom: 1em; }
        a { color: var(--accent-color); text-decoration: none; transition: color 0.3s ease; }
        a:hover { color: var(--accent-color-hover); }

        /* Navbar Customizada */
        .navbar-custom {
            background-color: rgba(1, 18, 46, 0.9); backdrop-filter: blur(8px);
            box-shadow: 0 2px 10px var(--shadow-color); padding-top: 0.8rem; padding-bottom: 0.8rem;
        }
        .navbar-custom .navbar-brand { font-size: 1.6em; font-weight: bold; color: var(--white); }
        .navbar-custom .nav-link { color: var(--light-text); font-size: 0.95em; padding-left: 1rem; padding-right: 1rem; transition: color 0.3s ease; }
        .navbar-custom .nav-link:hover,
        .navbar-custom .nav-link.active { color: var(--white); }
        .navbar-custom .dropdown-menu { background-color: var(--card-bg); border: 1px solid var(--medium-blue); box-shadow: 0 4px 15px var(--shadow-color); }
        .navbar-custom .dropdown-item { color: var(--light-text); transition: background-color 0.2s ease, color 0.2s ease; }
        .navbar-custom .dropdown-item:hover,
        .navbar-custom .dropdown-item:focus { background-color: var(--medium-blue); color: var(--white); }
        .navbar-custom .dropdown-item.active, .navbar-custom .dropdown-item:active { background-color: var(--accent-color); color: var(--dark-blue); }
        .navbar-custom .navbar-toggler { border-color: var(--gray-text); }
        .navbar-custom .navbar-toggler-icon { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28176, 224, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e"); }
        .navbar-custom .dropdown-divider { border-top-color: var(--input-border); }

        /* Cards */
        .card {
            background-color: var(--card-bg); border: 1px solid var(--input-border);
            border-radius: 0.5rem; box-shadow: 0 3px 15px var(--shadow-color);
            color: var(--light-text); margin-bottom: 1.5rem;
        }
        .card-header {
            background-color: var(--medium-blue); color: var(--white);
            padding: 1rem 1.25rem; border-bottom: 1px solid var(--input-border);
            border-top-left-radius: calc(0.5rem - 1px); border-top-right-radius: calc(0.5rem - 1px);
        }
        .card-header h5 { margin-bottom: 0; font-size: 1.1rem; }
        .card-body { padding: 1.5rem; }

        /* Formulários (para configurações) */
        .form-check { padding-left: 2.5em; margin-bottom: 0.75rem; } /* Aumenta espaço para switch/checkbox */
        .form-check-input {
            width: 2em; height: 1em; margin-left: -2.5em; /* Ajusta posição do switch */
            background-color: var(--input-bg); border: 1px solid var(--input-border);
        }
        .form-check-input:checked { background-color: var(--accent-color); border-color: var(--accent-color); }
        .form-check-input:focus { box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.3); }
        .form-check-label { color: var(--light-text); }
        .form-select {
            background-color: var(--input-bg); color: var(--light-text);
            border: 1px solid var(--input-border);
        }
        .form-select:focus {
             background-color: var(--input-bg); color: var(--white);
             border-color: var(--accent-color); box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.3);
        }
        .form-label { color: var(--light-text); font-weight: 500; margin-bottom: 0.5rem; }

        /* Botões */
        .btn { padding: 0.6rem 1.5rem; font-weight: 500; border-radius: 0.375rem; transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.1s ease; }
        .btn-primary-custom { background-color: var(--accent-color); color: var(--dark-blue); border: 1px solid var(--accent-color); }
        .btn-primary-custom:hover { background-color: var(--accent-color-hover); border-color: var(--accent-color-hover); color: var(--dark-blue); transform: translateY(-1px); }
        .btn-sm-custom { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
        .btn-outline-light-custom { color: var(--light-text); border-color: var(--light-text); }
        .btn-outline-light-custom:hover { color: var(--dark-blue); background-color: var(--light-text); border-color: var(--light-text); }
        .btn-outline-danger-custom { color: var(--danger-text); border-color: var(--danger-border); }
        .btn-outline-danger-custom:hover { color: var(--white); background-color: var(--danger-bg); border-color: var(--danger-border); }


        /* Alertas */
        .alert { border-radius: 0.375rem; padding: 1rem 1.25rem; border-left-width: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
        .alert-success { background-color: var(--success-bg); color: var(--success-text); border-color: var(--success-border); }
        .alert-danger { background-color: var(--danger-bg); color: var(--danger-text); border-color: var(--danger-border); }

        /* Lista de Notificações */
        .notification-list .list-group-item {
            background-color: var(--card-bg);
            border: 1px solid var(--notification-border);
            color: var(--light-text);
            margin-bottom: 0.5rem;
            border-radius: 0.375rem;
            padding: 1rem 1.25rem;
            transition: background-color 0.3s ease;
        }
        .notification-list .list-group-item.unread {
            background-color: var(--notification-unread-bg);
            border-left: 4px solid var(--accent-color);
            font-weight: 500; /* Destaque para não lidas */
        }
        .notification-list .list-group-item:hover {
            background-color: var(--medium-blue);
        }
        .notification-icon {
            font-size: 1.5rem;
            margin-right: 1rem;
            color: var(--accent-color);
            width: 30px; /* Largura fixa para alinhar textos */
            text-align: center;
        }
        .notification-list .list-group-item.unread .notification-icon {
            color: var(--accent-color-hover);
        }
        .notification-content { flex-grow: 1; }
        .notification-title { font-weight: 500; color: var(--white); margin-bottom: 0.25rem; display: block;}
        .notification-message { font-size: 0.9em; color: var(--gray-text); margin-bottom: 0.5rem; }
        .notification-date { font-size: 0.8em; color: var(--gray-text); }
        .notification-actions { margin-left: auto; white-space: nowrap; } /* Impede quebra de linha dos botões */
        .notification-actions .btn { margin-left: 0.5rem; }

        .no-notifications {
            text-align: center;
            padding: 2rem;
            color: var(--gray-text);
        }
        .no-notifications i {
            font-size: 3rem;
            display: block;
            margin-bottom: 1rem;
            color: var(--medium-blue);
        }


        /* Footer */
        footer.footer-custom {
            background-color: var(--medium-blue); padding: 30px 0; margin-top: auto;
            border-top: 2px solid var(--accent-color); text-align: center;
            color: var(--gray-text); font-size: 0.9em;
        }
        footer.footer-custom p { margin-bottom: 0.3rem; }
        footer.footer-custom a { color: var(--light-text); }
        footer.footer-custom a:hover { color: var(--accent-color-hover); }

        /* Media Queries */
        @media (max-width: 991.98px) { 
            .navbar-custom .navbar-nav { margin-top: 0.5rem; }
            .navbar-custom .nav-link { padding-left: 0; }
        }
        @media (max-width: 768px) {
            h1 { font-size: 1.6rem; margin-top: 1.5rem; margin-bottom: 1rem;}
            .container-main { padding: 0 15px; }
            .card-body { padding: 1rem; }
            .btn { font-size: 0.9rem; padding: 0.5rem 1.2rem; }
            .notification-list .list-group-item { flex-direction: column; align-items: flex-start !important; }
            .notification-actions { margin-left: 0; margin-top: 0.5rem; width: 100%; }
            .notification-actions .btn { margin-left: 0; margin-right: 0.5rem; margin-bottom: 0.5rem; }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
            <div class="container-fluid container-main">
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
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle active" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($_SESSION['user_nome'] ?? 'Usuário') ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                                    <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user-edit me-2"></i>Meu Perfil</a></li>
                                    <li><a class="dropdown-item" href="controle_acesso.php"><i class="fas fa-shield-alt me-2"></i>Segurança</a></li>
                                    <li><a class="dropdown-item active" href="notificacoes.php"><i class="fas fa-bell me-2"></i>Notificações</a></li>
                                    <li><hr class="dropdown-divider"></li>
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

        <!-- Main Content -->
        <main class="container-main my-4 my-lg-5">
            <h1><i class="fas fa-bell me-2"></i>Gerenciar Notificações</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-cogs me-2"></i>Configurações de Notificação</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <h6 class="mb-3" style="color: var(--white);">Notificações por E-mail:</h6>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="email_novas_tarefas" name="email_novas_tarefas" <?php if ($userNotificationSettings['email_novas_tarefas']) echo 'checked'; ?>>
                                    <label class="form-check-label" for="email_novas_tarefas">Novas tarefas atribuídas</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="email_lembretes_prazo" name="email_lembretes_prazo" <?php if ($userNotificationSettings['email_lembretes_prazo']) echo 'checked'; ?>>
                                    <label class="form-check-label" for="email_lembretes_prazo">Lembretes de prazo</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="email_atualizacoes_projeto" name="email_atualizacoes_projeto" <?php if ($userNotificationSettings['email_atualizacoes_projeto']) echo 'checked'; ?>>
                                    <label class="form-check-label" for="email_atualizacoes_projeto">Atualizações importantes de projetos</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="email_newsletter" name="email_newsletter" <?php if ($userNotificationSettings['email_newsletter']) echo 'checked'; ?>>
                                    <label class="form-check-label" for="email_newsletter">Newsletter e novidades</label>
                                </div>

                                <hr style="border-color: var(--input-border); margin: 1.5rem 0;">

                                <h6 class="mb-3" style="color: var(--white);">Notificações no Aplicativo:</h6>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="inapp_mencoes" name="inapp_mencoes" <?php if ($userNotificationSettings['inapp_mencoes']) echo 'checked'; ?>>
                                    <label class="form-check-label" for="inapp_mencoes">Menções diretas (@você)</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="inapp_comentarios" name="inapp_comentarios" <?php if ($userNotificationSettings['inapp_comentarios']) echo 'checked'; ?>>
                                    <label class="form-check-label" for="inapp_comentarios">Novos comentários em suas tarefas/posts</label>
                                </div>

                                <hr style="border-color: var(--input-border); margin: 1.5rem 0;">
                                
                                <div class="mb-3">
                                    <label for="frequencia_resumo_semanal" class="form-label">Resumo de atividades por e-mail:</label>
                                    <select class="form-select" id="frequencia_resumo_semanal" name="frequencia_resumo_semanal">
                                        <option value="nunca" <?php if ($userNotificationSettings['frequencia_resumo_semanal'] === 'nunca') echo 'selected'; ?>>Nunca</option>
                                        <option value="diario" <?php if ($userNotificationSettings['frequencia_resumo_semanal'] === 'diario') echo 'selected'; ?>>Resumo Diário</option>
                                        <option value="semanal" <?php if ($userNotificationSettings['frequencia_resumo_semanal'] === 'semanal') echo 'selected'; ?>>Resumo Semanal</option>
                                    </select>
                                </div>

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="notificacoes_push_mobile" name="notificacoes_push_mobile" <?php if ($userNotificationSettings['notificacoes_push_mobile']) echo 'checked'; ?>>
                                    <label class="form-check-label" for="notificacoes_push_mobile">Notificações Push (Mobile App)</label>
                                </div>
                                
                                <button type="submit" name="salvar_config_notificacoes" class="btn btn-primary-custom w-100 mt-3"><i class="fas fa-save me-2"></i>Salvar Configurações</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-history me-2"></i>Histórico de Notificações</h5>
                            <a href="?action=mark_all_read" class="btn btn-outline-light-custom btn-sm-custom"><i class="fas fa-check-double me-1"></i>Marcar todas como lidas</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($notifications)): ?>
                                <div class="no-notifications">
                                    <i class="fas fa-bell-slash"></i>
                                    <p>Você não tem nenhuma notificação no momento.</p>
                                </div>
                            <?php else: ?>
                                <ul class="list-group list-group-flush notification-list">
                                    <?php foreach ($notifications as $notif): 
                                        $iconClass = 'fa-info-circle'; // Default
                                        if ($notif['tipo'] === 'tarefa') $iconClass = 'fa-tasks';
                                        elseif ($notif['tipo'] === 'lembrete') $iconClass = 'fa-clock';
                                        elseif ($notif['tipo'] === 'sistema') $iconClass = 'fa-cog';
                                        elseif ($notif['tipo'] === 'mencao') $iconClass = 'fa-at';
                                        elseif ($notif['tipo'] === 'comentario') $iconClass = 'fa-comments';
                                    ?>
                                    <li class="list-group-item d-flex align-items-center <?php if (!$notif['lida']) echo 'unread'; ?>">
                                        <span class="notification-icon"><i class="fas <?php echo $iconClass; ?>"></i></span>
                                        <div class="notification-content">
                                            <a href="<?php echo htmlspecialchars($notif['link']); ?>" class="notification-title"><?php echo htmlspecialchars($notif['titulo']); ?></a>
                                            <p class="notification-message mb-1"><?php echo htmlspecialchars($notif['mensagem']); ?></p>
                                            <small class="notification-date"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($notif['data']))); ?></small>
                                        </div>
                                        <div class="notification-actions">
                                            <?php if (!$notif['lida']): ?>
                                            <a href="?action=mark_read¬if_id=<?php echo $notif['id']; ?>" class="btn btn-outline-light-custom btn-sm-custom" title="Marcar como lida"><i class="fas fa-check"></i></a>
                                            <?php endif; ?>
                                            <a href="?action=delete¬if_id=<?php echo $notif['id']; ?>" class="btn btn-outline-danger-custom btn-sm-custom" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta notificação?');"><i class="fas fa-trash-alt"></i></a>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer class="footer-custom">
        <div class="container-main">
            <p>© <?= date("Y") ?> <?= htmlspecialchars($companyName) ?>. Todos os direitos reservados.</p>
            <p>
                <a href="politica_privacidade.php" class="me-3">Política de Privacidade</a>
                <a href="termos_uso.php">Termos de Uso</a>
            </p>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>