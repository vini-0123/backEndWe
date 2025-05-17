<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../factory/conexao.php';

// Initialize $userData with default values
$userData = [
    'nome' => '',
    'email' => '',
    'telefone' => '',
    'foto_perfil' => 'https://via.placeholder.com/150/B0E0FF/01122E?text=?',
    'cargo' => '',
    'departamento' => ''
];

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$companyName = "WorkEase";
$success = '';
$error = '';

// Buscar dados do usuário do banco de dados
try {
    $stmt = $mysqli->prepare("
        SELECT 
            u.id,
            u.nome,
            u.email,
            u.telefone,
            IFNULL(u.foto_perfil, '') as foto_perfil,
            IFNULL(u.cargo, '') as cargo,
            IFNULL(u.departamento, '') as departamento
        FROM usuarios u
        WHERE u.id = ?
    ");
    
    if (!$stmt) {
        throw new Exception("Erro ao preparar consulta: " . $mysqli->error);
    }
    
    $stmt->bind_param("s", $_SESSION['user_id']);
    if (!$stmt->execute()) {
        throw new Exception("Erro ao executar consulta: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Usuário não encontrado");
    }
    
    $userData = $result->fetch_assoc();
    
    // Se não existir foto de perfil, usar um placeholder
    if (empty($userData['foto_perfil'])) {
        $userData['foto_perfil'] = 'https://via.placeholder.com/150/B0E0FF/01122E?text=' . substr($userData['nome'], 0, 1);
    }

} catch (Exception $e) {
    $error = "Erro ao carregar dados do usuário: " . $e->getMessage();
    error_log("Erro em perfil.php: " . $e->getMessage());
}

// Processar atualização do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_perfil'])) {
    try {
        $stmt = $mysqli->prepare("
            UPDATE usuarios 
            SET 
                telefone = ?,
                cargo = ?,
                departamento = ?
            WHERE id = ?
        ");
        
        $telefone = filter_var($_POST['telefone']);
        $cargo = filter_var($_POST['cargo']);
        $departamento = filter_var($_POST['departamento']);
        
        $stmt->bind_param("ssss", $telefone, $cargo, $departamento, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = 'Dados atualizados com sucesso!';
            // Atualizar dados em memória
            $userData['telefone'] = $telefone;
            $userData['cargo'] = $cargo;
            $userData['departamento'] = $departamento;
        } else {
            throw new Exception("Erro ao atualizar dados");
        }
    } catch (Exception $e) {
        $error = 'Erro ao atualizar perfil: ' . $e->getMessage();
    }
}

// Processar upload de foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_foto'])) {
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        try {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['foto']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed)) {
                throw new Exception('Formato de arquivo não permitido');
            }
            
            $upload_dir = 'uploads/profile_pics/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = uniqid() . '.' . $ext;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $destination)) {
                $stmt = $mysqli->prepare("
                    UPDATE usuarios 
                    SET foto_perfil = ? 
                    WHERE id = ?
                ");
                $stmt->bind_param("ss", $destination, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $userData['foto_perfil'] = $destination;
                    $success = 'Foto de perfil atualizada com sucesso!';
                } else {
                    throw new Exception("Erro ao salvar caminho da foto no banco de dados");
                }
            } else {
                throw new Exception("Erro ao fazer upload da foto");
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - <?= htmlspecialchars($companyName) ?></title>

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
            --input-disabled-bg: #01122e; /* Um pouco mais escuro para desabilitado */
            --input-disabled-border: #08306b;
            --danger-bg: #4d1212;
            --danger-text: #ffdddd;
            --danger-border: #8c1c1c;
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
        h5 { font-size: 1.15rem; font-weight: 500; }
        p { color: var(--gray-text); margin-bottom: 1em; }
        a { color: var(--accent-color); text-decoration: none; transition: color 0.3s ease; }
        a:hover { color: var(--accent-color-hover); }

        /* Navbar Customizada (igual ao controle_acesso.php) */
        .navbar-custom {
            background-color: rgba(1, 18, 46, 0.9);
            backdrop-filter: blur(8px);
            box-shadow: 0 2px 10px var(--shadow-color);
            padding-top: 0.8rem;
            padding-bottom: 0.8rem;
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
        .profile-pic-card .card-body { display: flex; flex-direction: column; align-items: center;}

        /* Formulários */
        .form-label { color: var(--light-text); font-weight: 500; margin-bottom: 0.5rem; }
        .form-control, .form-select {
            background-color: var(--input-bg); color: var(--light-text);
            border: 1px solid var(--input-border); border-radius: 0.375rem;
            padding: 0.6rem 1rem; transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .form-control::placeholder { color: var(--gray-text); opacity: 0.7; }
        .form-control:focus, .form-select:focus {
            background-color: var(--input-bg); color: var(--white);
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.3); outline: none;
        }
        .form-control:disabled, .form-control[readonly] {
            background-color: var(--input-disabled-bg);
            border-color: var(--input-disabled-border);
            color: var(--gray-text);
            opacity: 0.7;
        }
        .form-text { color: var(--gray-text); font-size: 0.875em; }

        /* Botões */
        .btn { padding: 0.6rem 1.5rem; font-weight: 500; border-radius: 0.375rem; transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.1s ease; }
        .btn-primary-custom { background-color: var(--accent-color); color: var(--dark-blue); border: 1px solid var(--accent-color); }
        .btn-primary-custom:hover { background-color: var(--accent-color-hover); border-color: var(--accent-color-hover); color: var(--dark-blue); transform: translateY(-1px); }
        
        .btn-outline-accent-custom {
            color: var(--accent-color);
            border-color: var(--accent-color);
        }
        .btn-outline-accent-custom:hover {
            color: var(--dark-blue);
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            transform: translateY(-1px);
        }


        /* Alertas */
        .alert { border-radius: 0.375rem; padding: 1rem 1.25rem; border-left-width: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
        .alert-success { background-color: var(--success-bg); color: var(--success-text); border-color: var(--success-border); }
        .alert-danger { background-color: var(--danger-bg); color: var(--danger-text); border-color: var(--danger-border); }

        /* Imagem de Perfil */
        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 3px solid var(--accent-color);
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

        /* Media Queries para Responsividade */
        @media (max-width: 991.98px) { 
            .navbar-custom .navbar-nav { margin-top: 0.5rem; }
             .navbar-custom .nav-link { padding-left: 0; }
        }
        @media (max-width: 768px) {
            h1 { font-size: 1.6rem; margin-top: 1.5rem; margin-bottom: 1rem;}
            .container-main { padding: 0 15px; }
            .card-body { padding: 1rem; }
            .btn { font-size: 0.9rem; padding: 0.5rem 1.2rem; }
            .profile-pic-card { margin-bottom: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
            <div class="container-fluid container-main"> <!-- Usar container-main para consistência de largura -->
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
                                    <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($userData['nome']) ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                                    <li><a class="dropdown-item active" href="perfil.php"><i class="fas fa-user-edit me-2"></i>Meu Perfil</a></li>
                                    <li><a class="dropdown-item" href="controle_acesso.php"><i class="fas fa-shield-alt me-2"></i>Segurança</a></li>
                                    <li><a class="dropdown-item" href="notificacoes.php"><i class="fas fa-bell me-2"></i>Notificações</a></li>
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
            <h1><i class="fas fa-id-card me-2"></i>Meu Perfil</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Coluna da foto -->
                <div class="col-lg-4 col-md-5 mb-4">
                    <div class="card profile-pic-card">
                        <div class="card-body text-center">
                            <img src="<?php echo htmlspecialchars($userData['foto_perfil']); ?>"class="img-fluid rounded-circle mb-3 profile-img">
                            <h5 class="mb-3"><?php echo htmlspecialchars($userData['nome']); ?></h5>
                            <form method="post" enctype="multipart/form-data" class="mt-2">
                                <div class="mb-3">
                                    <label for="foto" class="form-label visually-hidden">Atualizar foto</label>
                                    <input type="file" class="form-control form-control-sm" id="foto" name="foto" accept="image/*">
                                </div>
                                <button type="submit" name="upload_foto" class="btn btn-primary-custom btn-sm w-100"><i class="fas fa-camera me-2"></i>Atualizar Foto</button>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- Coluna dos dados pessoais -->
                <div class="col-lg-8 col-md-7">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-user-edit me-2"></i>Dados Pessoais</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="nome" class="form-label">Nome completo</label>
                                        <input type="text" class="form-control" id="nome" 
                                            value="<?php echo htmlspecialchars($userData['nome'] ?? ''); ?>" disabled>
                                        <div class="form-text">O nome não pode ser alterado.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">E-mail</label>
                                        <input type="email" class="form-control" id="email" 
                                            value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" disabled>
                                        <div class="form-text">O e-mail não pode ser alterado.</div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="telefone" class="form-label">Telefone</label>
                                        <input type="text" class="form-control" id="telefone" name="telefone" 
                                            value="<?php echo htmlspecialchars($userData['telefone'] ?? ''); ?>" 
                                            placeholder="(00) 00000-0000">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cargo" class="form-label">Cargo</label>
                                        <input type="text" class="form-control" id="cargo" name="cargo" 
                                            value="<?php echo htmlspecialchars($userData['cargo'] ?? ''); ?>" 
                                            placeholder="Seu cargo">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="departamento" class="form-label">Departamento</label>
                                    <input type="text" class="form-control" id="departamento" name="departamento" 
                                        value="<?php echo htmlspecialchars($userData['departamento'] ?? ''); ?>" 
                                        placeholder="Seu departamento">
                                </div>
                                <button type="submit" name="atualizar_perfil" class="btn btn-primary-custom"><i class="fas fa-save me-2"></i>Salvar Alterações</button>
                            </form>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="card h-100"> <!-- h-100 para igualar altura se necessário -->
                                <div class="card-header">
                                    <h5><i class="fas fa-shield-alt me-2"></i>Segurança</h5>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <p>Gerencie sua senha e configurações de segurança da conta.</p>
                                    <a href="controle_acesso.php?tab=senha" class="btn btn-outline-accent-custom mt-auto"><i class="fas fa-key me-2"></i>Alterar Senha</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5><i class="fas fa-bell-slash me-2"></i>Notificações</h5> <!-- Ícone alterado para bell-slash como exemplo -->
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <p>Configure suas preferências de notificação do sistema.</p>
                                    <a href="notificacoes.php" class="btn btn-outline-accent-custom mt-auto"><i class="fas fa-cog me-2"></i>Configurar</a>
                                </div>
                            </div>
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