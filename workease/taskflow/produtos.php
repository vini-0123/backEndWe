<?php
session_start();

// Simulate user session if not set for testing
if (!isset($_SESSION['user_name'])) {
    $_SESSION['user_name'] = 'Usuário Teste'; // Default for demo
}
// Simulate login status for sidebar consistency
if (!isset($_SESSION['logged_in'])) {
    $_SESSION['logged_in'] = true; // Default for demo
}

$softwareName = "Taskflow";
$userName = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Usuário';

// --- Initialize variables for filters and search ---
$search = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$categoria_id_filter = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';

// --- Database Connection and Data Fetching ---
$conexao_path = dirname(dirname(__FILE__)) . '/factory/conexao.php';
$mysqli = null;
$produtos = [];
$categorias = [];

try {
    if (file_exists($conexao_path)) {
        require_once $conexao_path;
        if (!$mysqli || !($mysqli instanceof mysqli)) {
            throw new Exception("Conexão com o banco de dados não estabelecida corretamente.");
        }
        if ($mysqli->connect_error) {
            throw new Exception("Erro de conexão: " . $mysqli->connect_error);
        }
    } else {
        throw new Exception("Arquivo de conexão não encontrado: " . $conexao_path);
    }

    // Fetch Categories for filter
    $query_cat = "SELECT id, nome FROM categorias WHERE ativo = 1 ORDER BY nome ASC";
    if ($stmt_cat = $mysqli->prepare($query_cat)) {
        $stmt_cat->execute();
        $result_cat = $stmt_cat->get_result();
        while ($cat_row = $result_cat->fetch_assoc()) {
            $categorias[] = $cat_row;
        }
        $stmt_cat->close();
    } else {
        error_log("Erro ao preparar query de categorias: " . $mysqli->error);
        // Provide dummy categories if query fails, to allow page rendering
        $categorias = [
            ['id' => 'cat-dummy-1', 'nome' => 'Eletrônicos (D)' ],
            ['id' => 'cat-dummy-2', 'nome' => 'Livros (D)' ]
        ];
    }

    // Fetch Products
    $sql = "SELECT 
                p.id,
                p.sku,
                p.nome,
                p.quantidade_estoque,
                p.preco_unitario,
                p.quantidade_minima,
                c.nome as categoria_nome 
            FROM produtos p 
            LEFT JOIN categorias c ON p.categoria_id = c.id AND c.ativo = 1 
            WHERE p.ativo = 1";

    $params = [];
    $types = "";

    if (!empty($search)) {
        $sql .= " AND (p.nome LIKE ? OR p.sku LIKE ?)";
        $searchTerm = "%" . $search . "%";
        array_push($params, $searchTerm, $searchTerm);
        $types .= "ss";
    }

    if (!empty($categoria_id_filter)) {
        $sql .= " AND p.categoria_id = ?";
        array_push($params, $categoria_id_filter);
        $types .= "s"; // Assuming category_id is string (like UUID). Adjust to "i" if integer.
    }

    $sql .= " ORDER BY p.nome ASC";

    $stmt_prod = $mysqli->prepare($sql);
    if ($stmt_prod) {
        if (!empty($types) && !empty($params)) {
            $stmt_prod->bind_param($types, ...$params);
        }
        if (!$stmt_prod->execute()) {
            throw new Exception("Erro na execução da consulta de produtos: " . $stmt_prod->error);
        }
        $result_prod = $stmt_prod->get_result();
        while ($row = $result_prod->fetch_assoc()) {
            $produtos[] = $row;
        }
        $stmt_prod->close();
    } else {
        throw new Exception("Erro na preparação da consulta de produtos: " . $mysqli->error);
    }

} catch (Exception $e) {
    error_log("Erro na página de produtos: " . $e->getMessage());
    $_SESSION['form_error'] = "Ocorreu um erro ao carregar os dados dos produtos. Usando dados simulados.";
    // Use dados simulados como fallback
    $produtos = [
        [
            'id' => 'uuid-sim-1', 'nome' => 'Produto Exemplo Alpha (Simulado)', 'sku' => 'SKU-SIM-001',
            'quantidade_estoque' => 10, 'preco_unitario' => 199.90, 'quantidade_minima' => 5,
            'categoria_nome' => 'Eletrônicos (D)'
        ],
        [
            'id' => 'uuid-sim-2', 'nome' => 'Produto Exemplo Beta (Simulado)', 'sku' => 'SKU-SIM-002',
            'quantidade_estoque' => 2, 'preco_unitario' => 29.50, 'quantidade_minima' => 3,
            'categoria_nome' => 'Livros (D)'
        ],
        [
            'id' => 'uuid-sim-3', 'nome' => 'Produto Exemplo Gamma (Sem Estoque)', 'sku' => 'SKU-SIM-003',
            'quantidade_estoque' => 0, 'preco_unitario' => 75.00, 'quantidade_minima' => 2,
            'categoria_nome' => 'Eletrônicos (D)'
        ]
    ];
    if(empty($categorias)) { // Populate dummy categories if not already populated
        $categorias = [
            ['id' => 'cat-dummy-1', 'nome' => 'Eletrônicos (D)' ],
            ['id' => 'cat-dummy-2', 'nome' => 'Livros (D)' ]
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - <?= htmlspecialchars($softwareName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --taskflow-deepest-purple: #1A0041; 
            --taskflow-vibrant-purple: #4C0182; 
            --taskflow-muted-purple: #6c5f8d;
            --taskflow-light-lavender: #9C8CB9;
            --taskflow-light-gray-beige: #dcd7d4; 
            --taskflow-white: #ffffff;
            --taskflow-body-bg: #f4f6fc;
            --taskflow-card-bg: var(--taskflow-white);
            --taskflow-text-primary: #1f2937; 
            --taskflow-text-secondary: #6b7280; 
            --taskflow-border-color: #e3e6f0; 
            --logo-aura-color: #6A0DAD; 
            --logo-aura-highlight: #9370DB;
        }
        body { background-color: var(--taskflow-body-bg); font-family: 'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; color: var(--taskflow-text-primary); transition: margin-left .3s ease-in-out }
        .dashboard-container { display: flex; min-height: 100vh }
        .sidebar {
            width: 260px; 
            background-color: var(--taskflow-deepest-purple);
            color: var(--taskflow-light-gray-beige); 
            padding-top: 0; position: fixed; height:100%; overflow-y: auto; 
            box-shadow: 3px 0 15px rgba(0,0,0,0.15); 
            z-index: 1030;
            transition: width .3s ease-in-out;
        }
        .sidebar .logo-area {
            padding: 0.8rem 1.2rem;
            text-align: left; 
            border-bottom: 1px solid rgba(220, 215, 212, 0.1); 
            display: flex;
            align-items: center;
            justify-content: flex-start; 
            gap: 0.6rem;
            height: 60px;
        }
        .sidebar .logo-image-wrapper {
            position: relative;
            width: 40px;
            height: 40px;
        }
        .sidebar .logo-image-wrapper img {
            width: 100%; height: 100%; object-fit: contain;
            border-radius: 50%; position: relative; z-index: 2;
        }
        .sidebar .logo-image-wrapper::before {
            content: ''; position: absolute; top: -4px; left: -4px;
            width: calc(100% + 8px); height: calc(100% + 8px);
            border-radius: 50%;
            box-shadow: 0 0 6px 1px var(--logo-aura-color), 0 0 9px 2px var(--logo-aura-highlight);
            animation: rotateAuraSidebar 10s linear infinite, pulseAuraSidebar 2.5s ease-in-out infinite alternate;
            z-index: 1;
        }
        @keyframes rotateAuraSidebar { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes pulseAuraSidebar {
            0% { box-shadow: 0 0 5px 1px var(--logo-aura-color), 0 0 8px 2px var(--logo-aura-highlight); opacity: 0.6; }
            100% { box-shadow: 0 0 8px 2px var(--logo-aura-highlight), 0 0 12px 3px var(--logo-aura-color); opacity: 1; }
        }
        .sidebar .logo-area .logo-text-brand { 
            font-size: 1.5rem; font-weight: 700; color: var(--taskflow-white);
            letter-spacing: 0.5px; line-height: 1;
        }
        .sidebar .menu ul { list-style: none; padding: 1.25rem 0; margin:0; }
        .sidebar .menu li a {
            display: flex; align-items: center; padding: 0.9rem 1.75rem; 
            color: var(--taskflow-light-lavender); text-decoration: none; 
            transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out, border-left-color 0.2s ease-in-out;
            font-size: 0.98rem; border-left: 4px solid transparent; font-weight: 500;
        }
        .sidebar .menu li a i { margin-right: 1rem; width: 22px; text-align: center; font-size: 1.15em; }
        .sidebar .menu li a:hover {
            background-color: rgba(76, 1, 130, 0.35); color: var(--taskflow-white); 
            border-left-color: var(--taskflow-light-lavender);
        }
        .sidebar .menu li.active a {
            background-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white); 
            font-weight: 600; border-left-color: var(--taskflow-light-gray-beige); 
        }
        .main-wrapper { flex-grow: 1; margin-left: 260px; display: flex; flex-direction: column; transition: margin-left .3s ease-in-out; }
        .header { background-color: var(--taskflow-card-bg); padding: .9rem 1.75rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--taskflow-border-color); box-shadow: 0 2px 4px rgba(26,0,65,.05); position: sticky; top: 0; z-index: 1020; height: 60px; }
        .header .search-bar { max-width: 450px; flex-grow: 1; }
        .header .search-bar .form-control { border-right: 0; border-top-right-radius: 0; border-bottom-right-radius: 0; border-color: var(--taskflow-border-color); font-size: .9rem; padding: .45rem .9rem }
        .header .search-bar .form-control:focus { border-color: var(--taskflow-vibrant-purple); box-shadow: 0 0 0 .2rem rgba(76,1,130,.2) }
        .header .search-bar button { border-top-left-radius: 0; border-bottom-left-radius: 0; background-color: var(--taskflow-vibrant-purple); color: white; border-color: var(--taskflow-vibrant-purple); padding: .45rem .9rem }
        .header .search-bar button:hover { background-color: var(--taskflow-deepest-purple); border-color: var(--taskflow-deepest-purple) }
        .header .user-info .username { margin-right: 1.25rem; font-weight: 500; color: var(--taskflow-text-primary) }
        .header .btn-logout { color: var(--taskflow-muted-purple); font-size: 1.2rem }
        .header .btn-logout:hover { color: var(--taskflow-deepest-purple) }
        main { padding: 1.75rem; background-color: var(--taskflow-body-bg); flex-grow: 1 }
        
        /* Styles specific to produtos.php */
        .page-title-area {
            margin-bottom: 1.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .page-title-area h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--taskflow-text-primary);
            margin-bottom: 0;
        }
        .filters-bar {
            display: flex;
            gap: 1rem;
            align-items: center;
            padding: 0.75rem 1.25rem; /* Increased padding slightly */
            background-color: var(--taskflow-card-bg);
            border: 1px solid var(--taskflow-border-color);
            border-radius: .5rem;
            margin-bottom: 1.75rem;
            box-shadow: 0 .1rem .3rem rgba(26,0,65,.06); /* Softer shadow */
        }
        .filters-bar .form-select-sm {
            font-size: .9rem;
            padding: .45rem .9rem;
            border-color: var(--taskflow-border-color); /* Ensure consistent border */
        }
        .filters-bar .form-select-sm:focus {
            border-color: var(--taskflow-vibrant-purple);
            box-shadow: 0 0 0 .2rem rgba(76,1,130,.2);
        }
        .filters-bar .btn-outline-secondary { /* Style for "Limpar Filtros" */
             border-color: var(--taskflow-muted-purple); color: var(--taskflow-muted-purple);
        }
        .filters-bar .btn-outline-secondary:hover {
             background-color: var(--taskflow-muted-purple); color: var(--taskflow-white);
        }


        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.75rem;
            margin-bottom: 1.75rem;
        }
        .product-card {
            background-color: var(--taskflow-card-bg);
            border: 1px solid var(--taskflow-border-color);
            border-radius: .5rem; /* Consistent with widget-card */
            box-shadow: 0 .15rem .4rem rgba(26,0,65,.07); /* Consistent with widget-card */
            display: flex;
            flex-direction: column;
            transition: transform .25s ease-out, box-shadow .25s ease-out; /* Smoother transition */
        }
        .product-card:hover {
            transform: translateY(-5px) scale(1.015); /* Slightly more pronounced hover */
            box-shadow: 0 .7rem 1.5rem rgba(26,0,65,.13); /* Enhanced shadow on hover */
        }
        .product-card .product-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--taskflow-border-color);
            /* background-color: #fdfcff; /* Light bg for header */
        }
        .product-card .product-header h5 {
            font-size: 1.15rem; /* Slightly larger product name */
            font-weight: 600;
            margin-bottom: .2rem;
            color: var(--taskflow-deepest-purple); /* Darker title for emphasis */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.3;
        }
        .product-card .product-header small {
            font-size: 0.8rem; /* Smaller SKU text */
            color: var(--taskflow-text-secondary);
            display: block; /* Ensure it takes its own line if needed */
        }
        .product-card .product-body {
            padding: 1.25rem;
            flex-grow: 1;
            font-size: 0.9rem; /* Base font size for body content */
        }
        .product-card .product-body > div {
            margin-bottom: 0.7rem; /* Spacing between items */
            display: flex; /* For aligning strong and span */
            align-items: flex-start;
        }
        .product-card .product-body > div:last-child {
            margin-bottom: 0;
        }
        .product-card .product-body strong {
            color: var(--taskflow-text-secondary);
            margin-right: 0.5rem; /* Space after label */
            font-weight: 500; /* Slightly less bold */
            min-width: 80px; /* Align labels */
            flex-shrink: 0; /* Prevent label from shrinking */
        }
        .product-card .product-body .value-text {
            color: var(--taskflow-text-primary);
            font-weight: 500;
        }
        .stock-badge {
            padding: .3em .7em; /* Adjusted padding */
            font-size: .8em; /* Smaller badge text */
            font-weight: 600;
            line-height: 1;
            color: var(--taskflow-white);
            text-align: center;
            white-space: nowrap;
            vertical-align: middle; /* Better alignment with text */
            border-radius: .375rem;
            letter-spacing: 0.3px;
        }
        .stock-badge.stock-normal { background-color: #198754; } /* BS5 Success Green */
        .stock-badge.stock-low { background-color: #ffc107; color: var(--taskflow-deepest-purple); } /* BS5 Warning Yellow */
        .stock-badge.stock-zero { background-color: #dc3545; } /* BS5 Danger Red */

        .product-card .product-footer {
            padding: .75rem 1.25rem;
            background-color: #f8f9fc;
            border-top: 1px solid var(--taskflow-border-color);
            border-bottom-left-radius: .5rem;
            border-bottom-right-radius: .5rem;
        }
        .product-card .product-footer .btn-group .btn {
            font-size: 0.85rem; /* Smaller buttons */
            padding: .4rem .8rem; /* Adjust padding */
        }
        .product-card .product-footer .btn i {
            font-size: 0.95em; /* Icon size */
        }
        .btn-outline-primary { border-color: var(--taskflow-vibrant-purple); color: var(--taskflow-vibrant-purple) }
        .btn-outline-primary:hover { background-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white) }
        .btn-outline-secondary { border-color: var(--taskflow-muted-purple); color: var(--taskflow-muted-purple) }
        .btn-outline-secondary:hover { background-color: var(--taskflow-muted-purple); color: var(--taskflow-white) }

        .no-products-message {
            text-align: center;
            padding: 3rem 1.5rem; /* More padding */
            background-color: var(--taskflow-card-bg);
            border: 1px solid var(--taskflow-border-color);
            border-radius: .5rem;
            color: var(--taskflow-text-secondary);
            margin-top: 1rem; /* Space from filters if no products */
        }
        .no-products-message i {
            font-size: 3.5rem; /* Larger icon */
            margin-bottom: 1.25rem;
            color: var(--taskflow-light-lavender);
        }
        .no-products-message p {
            font-size: 1.15rem; /* Slightly larger text */
            margin-bottom: 0.5rem;
        }
        .no-products-message a {
            color: var(--taskflow-vibrant-purple);
            font-weight: 500;
            text-decoration: none;
        }
        .no-products-message a:hover {
            text-decoration: underline;
        }
        .fab { position: fixed; bottom: 30px; right: 30px; width: 56px; height: 56px; background-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.7rem; text-decoration: none; box-shadow: 0 5px 15px rgba(26,0,65,.3); z-index: 1050; transition: transform .25s ease,background-color .25s ease,box-shadow .25s ease }
        .fab:hover { transform: scale(1.08) translateY(-2px); background-color: var(--taskflow-deepest-purple); box-shadow: 0 8px 20px rgba(26,0,65,.4); color: var(--taskflow-white) }
        
        /* Responsive adjustments from index.php (some might be overridden or extended) */
        @media (max-width:992px) { /* No changes here unless kpi-grid styles are needed */ }
        @media (max-width:768px) { 
            .sidebar { width: 0; padding-left: 0; padding-right: 0; overflow: hidden } 
            .main-wrapper { margin-left: 0 } 
            .header { padding: .75rem 1rem } .header .search-bar { max-width: none } 
            .header .user-info .username { display: none } 
            main { padding: 1rem } 
            .fab { bottom: 20px; right: 20px; width: 50px; height: 50px; font-size: 1.5rem } 
            /* Product specific responsive */
            .product-grid {
                grid-template-columns: 1fr; /* Single column on small screens */
                gap: 1rem;
            }
            .filters-bar {
                flex-direction: column;
                align-items: stretch;
                padding: 0.75rem;
            }
            .filters-bar > * { width: 100%; }
            .filters-bar .btn { margin-top: 0.5rem; }

            .page-title-area {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
                margin-bottom: 1rem;
            }
            .page-title-area h1 {
                font-size: 1.5rem;
            }
            .product-card .product-header h5 {
                font-size: 1.05rem;
            }
            .product-card .product-body strong {
                min-width: 70px; /* Adjust for smaller screens if needed */
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <a href="index.php" class="logo-area" style="text-decoration: none;"> 
                <div class="logo-image-wrapper">
                    <img src="img/taskflow_logo.png" alt="<?= htmlspecialchars($softwareName) ?> Logo">
                </div>
                <span class="logo-text-brand"><?= htmlspecialchars($softwareName) ?></span>
            </a>
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
                <form class="search-bar d-flex" method="GET" action="produtos.php">
                    <input class="form-control form-control-sm" type="search" name="busca" 
                           value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Buscar por nome ou SKU...">
                    <?php if (!empty($categoria_id_filter)): ?>
                        <input type="hidden" name="categoria" value="<?= htmlspecialchars($categoria_id_filter) ?>">
                    <?php endif; ?>
                    <button class="btn btn-sm" type="submit"><i class="fas fa-search"></i></button>
                </form>
                <div class="user-info d-flex align-items-center">
                    <span class="username"><?= $userName ?></span>
                    <a href="../logout.php" class="btn-logout" title="Sair">
                        <i class="fas fa-sign-out-alt fa-lg"></i>
                    </a>
                </div>
            </header>

            <main>
                <div class="page-title-area">
                    <h1><i class="fas fa-boxes-stacked me-2"></i>Lista de Produtos</h1>
                    <!-- Optional: Add breadcrumbs or additional info here -->
                </div>

                <form method="GET" action="produtos.php" id="filterForm">
                    <div class="filters-bar">
                        <?php if (!empty($search)): ?>
                            <input type="hidden" name="busca" value="<?= htmlspecialchars($search) ?>">
                        <?php endif; ?>
                        <div class="flex-grow-1">
                            <label for="categoria_filtro" class="visually-hidden">Filtrar por Categoria</label>
                            <select class="form-select form-select-sm" id="categoria_filtro" name="categoria" onchange="document.getElementById('filterForm').submit()">
                                <option value="">Todas as Categorias</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['id']) ?>" 
                                        <?= ($categoria_id_filter == $cat['id'] ? 'selected' : '') ?>>
                                        <?= htmlspecialchars($cat['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if(empty($categorias) && $mysqli && !$mysqli->connect_error): // Show if connected but no categories found ?>
                                    <option value="" disabled>Nenhuma categoria cadastrada</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <?php if (!empty($search) || !empty($categoria_id_filter)): ?>
                            <a href="produtos.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Limpar Filtros
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if (isset($_SESSION['form_success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['form_success']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['form_success']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['form_error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['form_error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['form_error']); ?>
                <?php endif; ?>

                <?php if (!empty($produtos)): ?>
                    <div class="product-grid">
                        <?php foreach ($produtos as $produto): ?>
                            <div class="product-card">
                                <div class="product-header">
                                    <h5 title="<?= htmlspecialchars($produto['nome']) ?>"><?= htmlspecialchars($produto['nome']) ?></h5>
                                    <small>SKU: <?= htmlspecialchars($produto['sku'] ?? 'N/A') ?></small>
                                </div>
                                <div class="product-body">
                                    <div>
                                        <strong>Categoria:</strong> 
                                        <span class="value-text"><?= htmlspecialchars($produto['categoria_nome'] ?? 'N/A') ?></span>
                                    </div>
                                    <div>
                                        <strong>Preço:</strong> 
                                        <span class="value-text">R$ <?= number_format($produto['preco_unitario'] ?? 0, 2, ',', '.') ?></span>
                                    </div>
                                    <div>
                                        <strong>Estoque:</strong>
                                        <?php
                                        $quantidade_em_estoque = $produto['quantidade_estoque'] ?? 0; 
                                        $quantidade_minima = $produto['quantidade_minima'] ?? 0;
                                        
                                        $stock_class = 'stock-normal'; 
                                        $stock_text = 'Normal';
                                        
                                        if ($quantidade_em_estoque <= 0) { 
                                            $stock_class = 'stock-zero'; 
                                            $stock_text = 'Zerado'; 
                                        } elseif ($quantidade_minima > 0 && $quantidade_em_estoque <= $quantidade_minima) { 
                                            $stock_class = 'stock-low'; 
                                            $stock_text = 'Baixo'; 
                                        }
                                        ?>
                                        <span class="value-text"><?= number_format($quantidade_em_estoque) ?> un.</span>
                                        <span class="stock-badge <?= $stock_class ?> ms-2"><?= $stock_text ?></span>
                                    </div>
                                </div>
                                <div class="product-footer">
                                    <div class="btn-group w-100" role="group">
                                        <a href="editar_produto.php?id=<?= htmlspecialchars($produto['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit me-1"></i> Editar</a>
                                        <a href="movimentar_produto.php?id=<?= htmlspecialchars($produto['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-exchange-alt me-1"></i> Movimentar</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-products-message">
                        <i class="fas fa-box-open fa-3x"></i>
                        <p class="mt-3">Nenhum produto encontrado.</p>
                        <?php if (!empty($search) || !empty($categoria_id_filter)): ?>
                            <p><small>Tente ajustar seus filtros ou <a href="produtos.php">limpar a busca</a>.</small></p>
                        <?php else: ?>
                            <p><small>Que tal <a href="adicionar_produto.php">adicionar um novo produto</a>?</small></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <a href="adicionar_produto.php" class="fab" title="Adicionar Novo Produto"><i class="fas fa-plus"></i></a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>