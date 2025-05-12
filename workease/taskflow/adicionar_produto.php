<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // For demonstration, simulating login. In production, redirect.
    // header('Location: login.php');
    // exit;
    $_SESSION['logged_in'] = true;
    $_SESSION['user_name'] = 'Usuário Demonstração';
}

// Corrected path for conexao.php - assuming 'factory' is two levels up
$conexao_path = dirname(dirname(__FILE__)) . '/factory/conexao.php';
if (file_exists($conexao_path)) {
    require_once $conexao_path; // Changed from include_once to require_once for critical files
} else {
    // Fallback if conexao.php is missing
    die("Erro crítico: Arquivo de conexão não encontrado em '$conexao_path'. Verifique o caminho.");
    // Or, for development with dummy data:
    // $mysqli = null;
    // echo "<p style='color:red; text-align:center;'>Arquivo de conexão não encontrado. Usando dados de demonstração.</p>";
}

$userName = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Usuário';

// Buscar categorias para o select
$categorias = [];
if ($mysqli && !$mysqli->connect_errno) { // Check if $mysqli is valid
    $query_categorias = "SELECT id, nome FROM categorias WHERE ativo = 1 ORDER BY nome ASC";
    if ($result_cat = $mysqli->query($query_categorias)) {
        while($cat = $result_cat->fetch_assoc()) {
            $categorias[] = $cat;
        }
        $result_cat->free(); // Free result set
    } else {
        // Log error or display a user-friendly message if query fails
        error_log("Erro ao buscar categorias: " . $mysqli->error);
        // $_SESSION['form_error'] = "Não foi possível carregar as categorias."; // Example
    }
} else {
    // Dummy data if DB connection failed earlier or $mysqli is null
    $categorias = [
        ['id' => 1, 'nome' => 'Eletrônicos (Dummy)'],
        ['id' => 2, 'nome' => 'Livros (Dummy)'],
    ];
    if (!$mysqli) {
         // $_SESSION['form_error'] = "Erro de conexão com o banco de dados. Categorias não puderam ser carregadas.";
    } else if ($mysqli->connect_errno) {
        // $_SESSION['form_error'] = "Falha na conexão: " . $mysqli->connect_error . ". Categorias não puderam ser carregadas.";
    }
}

$page_title = "Adicionar Novo Produto";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Taskflow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS Variables and Base Styles (same as index.php/dashboard) */
        :root {
            --taskflow-deepest-purple: #1A0041;
            --taskflow-vibrant-purple: #4C0182;
            --taskflow-muted-purple: #6c5f8d;
            --taskflow-light-lavender: #9C8CB9;
            --taskflow-light-gray-beige: #dcd7d4;
            --taskflow-white: #ffffff;
            --taskflow-body-bg: #f4f6fc; /* Consistent background */
            --taskflow-card-bg: var(--taskflow-white);
            --taskflow-text-primary: #1f2937; /* Darker text for body */
            --taskflow-text-secondary: #6b7280; /* Muted text for body */
            --taskflow-border-color: #e3e6f0;
        }
        body { background-color: var(--taskflow-body-bg); font-family: 'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; color: var(--taskflow-text-primary); transition: margin-left .3s ease-in-out }
        .dashboard-container { display: flex; min-height: 100vh }
        .sidebar {
            width: 260px; background-color: var(--taskflow-deepest-purple); color: var(--taskflow-light-gray-beige);
            padding-top: 0; position: fixed; height:100%; overflow-y: auto; 
            box-shadow: 3px 0 15px rgba(0,0,0,0.15); z-index: 1030;
            transition: width 0.3s ease-in-out;
        }
        
        /* LOGO AREA STYLING - SPINNING GEAR (Same as index.php) */
        .sidebar .logo-area {
            padding: 1.25rem 1rem; 
            text-align: center;
            border-bottom: 1px solid rgba(220, 215, 212, 0.1); 
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .sidebar .logo-area .logo-icon-gear {
            font-size: 2.2rem; 
            color: var(--taskflow-light-lavender); 
            margin-right: 0.75rem; 
            animation: spin 4s linear infinite; 
            display: inline-block; 
        }
        .sidebar .logo-area .logo-text-brand { 
            font-size: 1.8rem; 
            font-weight: 700;
            color: var(--taskflow-white); 
            letter-spacing: 0.5px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Menu Styles (Same as index.php) */
        .sidebar .menu ul { list-style: none; padding: 1.25rem 0; margin:0; }
        .sidebar .menu li a { display: flex; align-items: center; padding: 0.9rem 1.75rem; color: var(--taskflow-light-lavender); text-decoration: none; transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out, border-left-color 0.2s ease-in-out; font-size: 0.98rem; border-left: 4px solid transparent; font-weight: 500; }
        .sidebar .menu li a i { margin-right: 1rem; width: 22px; text-align: center; font-size: 1.15em; }
        .sidebar .menu li a:hover { background-color: rgba(76, 1, 130, 0.35); color: var(--taskflow-white); border-left-color: var(--taskflow-light-lavender); }
        .sidebar .menu li.active a { background-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white); font-weight: 600; border-left-color: var(--taskflow-light-gray-beige); }
        
        .main-wrapper { flex-grow: 1; margin-left: 260px; display: flex; flex-direction: column; }
        .header { background-color: var(--taskflow-card-bg); padding: 0.9rem 1.75rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--taskflow-border-color); box-shadow: 0 2px 4px rgba(26,0,65,.05); position: sticky; top: 0; z-index: 1020; }
        .header .user-info { margin-left: auto; } /* Push to right as search bar is absent */
        .header .user-info .username { margin-right: 1.25rem; font-weight: 500; color: var(--taskflow-text-primary); }
        .header .btn-logout { color: var(--taskflow-muted-purple); font-size: 1.2rem; }
        .header .btn-logout:hover { color: var(--taskflow-deepest-purple); }
        main { padding: 1.75rem; background-color: var(--taskflow-body-bg); flex-grow:1; }
        
        .form-control:focus, .form-select:focus { border-color: var(--taskflow-vibrant-purple); box-shadow: 0 0 0 0.2rem rgba(76, 1, 130, 0.25); }
        .btn-taskflow-primary { background-color: var(--taskflow-vibrant-purple); border-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white); }
        .btn-taskflow-primary:hover { background-color: var(--taskflow-deepest-purple); border-color: var(--taskflow-deepest-purple); }
        .btn-taskflow-secondary { 
            background-color: transparent; 
            border-color: var(--taskflow-muted-purple); 
            color: var(--taskflow-muted-purple); 
        }
        .btn-taskflow-secondary:hover { 
            background-color: var(--taskflow-muted-purple); 
            border-color: var(--taskflow-muted-purple); 
            color: var(--taskflow-white); 
        }
        .card-form { 
            background-color: var(--taskflow-card-bg); 
            border: 1px solid var(--taskflow-border-color); 
            border-radius: 0.5rem; 
            box-shadow: 0 0.15rem 0.4rem rgba(26,0,65,.07); 
        }
        .card-form .card-header { 
            background-color: #fdfcff; /* Lighter than body */
            border-bottom: 1px solid var(--taskflow-border-color); 
            color: var(--taskflow-text-primary); 
            font-weight: 600;
        }
        .card-form .card-header i { color: var(--taskflow-vibrant-purple); }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .sidebar { width: 0; padding-left: 0; padding-right: 0; overflow: hidden; }
            .main-wrapper { margin-left: 0; }
            .header { padding: 0.75rem 1rem; }
            .header .user-info .username { display: none; }
            main { padding: 1rem; }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-area"> 
                <i class="fas fa-cog logo-icon-gear"></i> 
                <span class="logo-text-brand">Taskflow</span>
            </div>
            <nav class="menu">
                 <ul>
                    <li><a href="index.php"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
                    <li class="active"><a href="produtos.php"><i class="fas fa-boxes-stacked"></i> Produtos</a></li> 
                    <li><a href="categorias.php"><i class="fas fa-tags"></i> Categorias</a></li>
                    <li><a href="movimentacoes.php"><i class="fas fa-truck-ramp-box"></i> Movimentações</a></li>
                    <li><a href="relatorios.php"><i class="fas fa-file-invoice"></i> Relatórios</a></li>
                </ul>
            </nav>
        </aside>
        <div class="main-wrapper">
            <header class="header">
                <div class="user-info d-flex align-items-center"> 
                    <span class="username"><?= $userName ?></span>
                    <a href="logout.php" class="btn-logout" title="Sair"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                </div>
            </header>
            <main>
                <div class="container-fluid"> 
                    <div class="row justify-content-center"> 
                        <div class="col-lg-10 col-xl-8"> 
                            <div class="card card-form">
                                <div class="card-header">
                                    <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i><?= $page_title ?></h4>
                                </div>
                                <div class="card-body p-4"> 
                                    <?php if (isset($_SESSION['form_error'])): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <?= htmlspecialchars($_SESSION['form_error']); ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                        <?php unset($_SESSION['form_error']); ?>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['form_success'])): ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <?= htmlspecialchars($_SESSION['form_success']); ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                        <?php unset($_SESSION['form_success']); ?>
                                    <?php endif; ?>

                                    <form action="salvar_produto.php" method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="nome" class="form-label">Nome do Produto <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm" id="nome" name="nome" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm" id="sku" name="sku" required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="descricao" class="form-label">Descrição</label>
                                            <textarea class="form-control form-control-sm" id="descricao" name="descricao" rows="3"></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="preco_unitario" class="form-label">Preço Unitário (R$) <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" class="form-control form-control-sm" id="preco_unitario" name="preco_unitario" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="quantidade" class="form-label">Quantidade em Estoque</label>
                                                <input type="number" class="form-control form-control-sm" id="quantidade" name="quantidade" value="0" min="0">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="quantidade_minima" class="form-label">Quantidade Mínima</label>
                                                <input type="number" class="form-control form-control-sm" id="quantidade_minima" name="quantidade_minima" value="0" min="0">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="categoria_id" class="form-label">Categoria</label>
                                                <select class="form-select form-select-sm" id="categoria_id" name="categoria_id">
                                                    <option value="">Selecione uma categoria</option>
                                                    <?php foreach ($categorias as $categoria): ?>
                                                        <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nome']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="imagem_destaque" class="form-label">Imagem do Produto</label>
                                                <input class="form-control form-control-sm" type="file" id="imagem_destaque" name="imagem_destaque" accept="image/png, image/jpeg, image/gif">
                                                 <small class="form-text text-muted">Formatos: PNG, JPG, GIF. Max: 2MB.</small>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="ativo" name="ativo" value="1" checked>
                                            <label class="form-check-label" for="ativo">Produto Ativo</label>
                                        </div>

                                        <hr class="my-4"> 

                                        <div class="d-flex justify-content-end"> 
                                            <a href="produtos.php" class="btn btn-taskflow-secondary me-2"><i class="fas fa-times me-1"></i>Cancelar</a>
                                            <button type="submit" class="btn btn-taskflow-primary"><i class="fas fa-save me-1"></i>Salvar Produto</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>