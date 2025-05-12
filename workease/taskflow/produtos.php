<?php
session_start();
// Simulate user session if not set for testing
if (!isset($_SESSION['user_name'])) {
    $_SESSION['user_name'] = 'Usuário Teste';
}
// Simulate login status for sidebar consistency if not fully set up
if (!isset($_SESSION['logged_in'])) {
    $_SESSION['logged_in'] = true;
}

$conexao_path = dirname(dirname(__FILE__)) . '/factory/conexao.php';
if (file_exists($conexao_path)) {
    require_once $conexao_path;
} else {
    $mysqli = null; 
}

// Get search parameters
$search = isset($_GET['busca']) ? filter_var($_GET['busca'], FILTER_SANITIZE_STRING) : '';
$categoria_id = isset($_GET['categoria']) ? filter_var($_GET['categoria'], FILTER_SANITIZE_NUMBER_INT) : '';

// --- Data Fetching Logic (Kept as is from your provided code) ---
$query = "SELECT p.*, c.nome as categoria_nome FROM produtos p LEFT JOIN categorias c ON p.categoria_id = c.id WHERE p.ativo = 1";
$params = []; $types = "";
if (!empty($search)) { $query .= " AND (p.nome LIKE ? OR p.sku LIKE ?)"; $search_param = "%$search%"; $params[] = $search_param; $params[] = $search_param; $types .= "ss"; }
if (!empty($categoria_id)) { $query .= " AND p.categoria_id = ?"; $params[] = $categoria_id; $types .= "i"; }
$query .= " ORDER BY p.nome ASC";
$produtos = []; $categorias = [];
if ($mysqli && !$mysqli->connect_errno) {
    $stmt = $mysqli->prepare($query); if ($stmt) { if (!empty($params)) { $stmt->bind_param($types, ...$params); } $stmt->execute(); $result = $stmt->get_result(); if ($result) { $produtos = $result->fetch_all(MYSQLI_ASSOC); } $stmt->close(); }
    $stmt_cat = $mysqli->prepare("SELECT id, nome FROM categorias WHERE ativo = 1 ORDER BY nome"); if ($stmt_cat) { $stmt_cat->execute(); $result_cat = $stmt_cat->get_result(); if ($result_cat) { while ($row = $result_cat->fetch_assoc()) { $categorias[] = $row; } } $stmt_cat->close(); }
} else {
    $produtos = [['id' => 1, 'nome' => 'Produto Exemplo Moderno 1', 'sku' => 'SKU001', 'categoria_nome' => 'Eletrônicos', 'preco_unitario' => 199.99, 'quantidade' => 10, 'quantidade_minima' => 5], ['id' => 2, 'nome' => 'Produto Exemplo Moderno 2', 'sku' => 'SKU002', 'categoria_nome' => 'Livros', 'preco_unitario' => 29.90, 'quantidade' => 2, 'quantidade_minima' => 3], ['id' => 3, 'nome' => 'Produto Exemplo Moderno 3', 'sku' => 'SKU003', 'categoria_nome' => 'Roupas', 'preco_unitario' => 79.50, 'quantidade' => 0, 'quantidade_minima' => 2], ['id' => 4, 'nome' => 'Produto Longo Nome Teste', 'sku' => 'SKULNG004', 'categoria_nome' => 'Decoração', 'preco_unitario' => 129.00, 'quantidade' => 15, 'quantidade_minima' => 4]];
    $categorias = [['id' => 1, 'nome' => 'Eletrônicos'], ['id' => 2, 'nome' => 'Livros'], ['id' => 3, 'nome' => 'Roupas'], ['id' => 4, 'nome' => 'Decoração']];
}
$userName = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Usuário';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - TaskFlow</title>
    
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
            --taskflow-body-bg: #f4f6fc;
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
        
        /* LOGO AREA STYLING - SPINNING GEAR */
        .sidebar .logo-area {
            padding: 1rem 1.5rem; /* Added more horizontal padding */
            text-align: left; /* Changed from center to left */
            border-bottom: 1px solid rgba(220, 215, 212, 0.1); 
            display: flex;
            align-items: center;
            justify-content: flex-start; /* Changed from center to flex-start */
            gap: 0.75rem; /* Added gap between icon and text */
        }

        .sidebar .logo-area .logo-icon-gear {
            font-size: 2rem; /* Slightly reduced size */
            color: var(--taskflow-light-lavender);
            animation: spin 4s linear infinite;
            display: inline-block;
        }

        .sidebar .logo-area .logo-text-brand { 
            font-size: 1.6rem; /* Slightly reduced size */
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

        /* Main Wrapper, Header, Main content, Filters, Product Grid, Cards, Badges, FAB, Responsive (Same as previous produtos.php) */
        .main-wrapper { flex-grow: 1; margin-left: 260px; display: flex; flex-direction: column; transition: margin-left .3s ease-in-out }
        .header { background-color: var(--taskflow-card-bg); padding: .9rem 1.75rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--taskflow-border-color); box-shadow: 0 2px 4px rgba(26,0,65,.05); position: sticky; top: 0; z-index: 1020 }
        .header .search-bar { max-width: 450px; flex-grow: 1 }
        .header .search-bar .form-control { border-right: 0; border-top-right-radius: 0; border-bottom-right-radius: 0; border-color: var(--taskflow-border-color); font-size: .9rem; padding: .45rem .9rem }
        .header .search-bar .form-control:focus { border-color: var(--taskflow-vibrant-purple); box-shadow: 0 0 0 .2rem rgba(76,1,130,.2) }
        .header .search-bar button { border-top-left-radius: 0; border-bottom-left-radius: 0; background-color: var(--taskflow-vibrant-purple); color: white; border-color: var(--taskflow-vibrant-purple); padding: .45rem .9rem }
        .header .search-bar button:hover { background-color: var(--taskflow-deepest-purple); border-color: var(--taskflow-deepest-purple) }
        .header .user-info .username { margin-right: 1.25rem; font-weight: 500; color: var(--taskflow-text-primary) }
        .header .btn-logout { color: var(--taskflow-muted-purple); font-size: 1.2rem }
        .header .btn-logout:hover { color: var(--taskflow-deepest-purple) }
        main { padding: 1.75rem; background-color: var(--taskflow-body-bg); flex-grow: 1 }
        .page-title-area { margin-bottom: 1.75rem; display: flex; justify-content: space-between; align-items: center }
        .page-title-area h1 { font-size: 1.75rem; font-weight: 600; color: var(--taskflow-text-primary); margin-bottom: 0 }
        .filters-bar { background-color: var(--taskflow-card-bg); border: 1px solid var(--taskflow-border-color); border-radius: .5rem; box-shadow: 0 .1rem .3rem rgba(26,0,65,.06); padding: 1rem 1.25rem; margin-bottom: 1.75rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap }
        .filters-bar .form-select { background-color: var(--taskflow-white); border-color: var(--taskflow-border-color); color: var(--taskflow-text-primary); font-size: .9rem; padding: .45rem 2.25rem .45rem .9rem }
        .filters-bar .form-select:focus { border-color: var(--taskflow-vibrant-purple); box-shadow: 0 0 0 .2rem rgba(76,1,130,.2) }
        .filters-bar .btn-outline-secondary { border-color: var(--taskflow-muted-purple); color: var(--taskflow-muted-purple); font-size: .9rem; padding: .45rem .9rem }
        .filters-bar .btn-outline-secondary:hover { background-color: var(--taskflow-muted-purple); color: var(--taskflow-white) }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(280px,1fr)); gap: 1.75rem }
        .product-card { background-color: var(--taskflow-card-bg); border: 1px solid var(--taskflow-border-color); border-radius: .5rem; box-shadow: 0 .1rem .3rem rgba(26,0,65,.06); transition: transform .2s ease-out,box-shadow .2s ease-out; display: flex; flex-direction: column; color: var(--taskflow-text-primary); overflow: hidden }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1.25rem rgba(26,0,65,.1) }
        .product-header { padding: 1rem 1.25rem; border-bottom: 1px solid var(--taskflow-border-color); background-color: #fdfcff }
        .product-header h5 { font-size: 1.1rem; font-weight: 600; color: var(--taskflow-text-primary); margin-bottom: .15rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis }
        .product-header small { font-size: .8rem; color: var(--taskflow-text-secondary) }
        .product-body { padding: 1.25rem; flex-grow: 1; font-size: .9rem }
        .product-body .mb-2 { margin-bottom: .75rem!important }
        .product-body strong { color: var(--taskflow-vibrant-purple); font-weight: 500 }
        .product-body span.value-text { color: var(--taskflow-text-primary); font-weight: 500 }
        .stock-badge { padding: .25rem .65rem; border-radius: 1rem; font-size: .8rem; font-weight: 500; border: 1px solid transparent }
        .stock-normal { background-color: rgba(34,197,94,.1); color: #198754; border-color: rgba(34,197,94,.3) }
        .stock-low { background-color: rgba(255,193,7,.1); color: #b5830f; border-color: rgba(255,193,7,.4) }
        .stock-zero { background-color: rgba(220,53,69,.1); color: #dc3545; border-color: rgba(220,53,69,.3) }
        .product-footer { padding: .9rem 1.25rem; background-color: #f8f9fc; border-top: 1px solid var(--taskflow-border-color) }
        .product-footer .btn { font-size: .85rem; padding: .35rem .75rem }
        .product-footer .btn-outline-primary { border-color: var(--taskflow-vibrant-purple); color: var(--taskflow-vibrant-purple) }
        .product-footer .btn-outline-primary:hover { background-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white) }
        .product-footer .btn-outline-secondary { border-color: var(--taskflow-muted-purple); color: var(--taskflow-muted-purple) }
        .product-footer .btn-outline-secondary:hover { background-color: var(--taskflow-muted-purple); color: var(--taskflow-white) }
        .fab { position: fixed; bottom: 30px; right: 30px; width: 56px; height: 56px; background-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.7rem; text-decoration: none; box-shadow: 0 5px 15px rgba(26,0,65,.3); z-index: 1050; transition: transform .25s ease,background-color .25s ease,box-shadow .25s ease }
        .fab:hover { transform: scale(1.08) translateY(-2px); background-color: var(--taskflow-deepest-purple); box-shadow: 0 8px 20px rgba(26,0,65,.4); color: var(--taskflow-white) }
        .no-products-message { background-color: var(--taskflow-card-bg); border: 1px solid var(--taskflow-border-color); border-radius: .5rem; padding: 3rem; text-align: center; color: var(--taskflow-text-secondary) }
        .no-products-message i { font-size: 3rem; color: var(--taskflow-light-lavender); margin-bottom: 1rem }
        .no-products-message p { font-size: 1.1rem; margin-bottom: .5rem }
        .no-products-message a { color: var(--taskflow-vibrant-purple); font-weight: 500 }
        .no-products-message a:hover { color: var(--taskflow-deepest-purple) }
        @media (max-width:992px) { .product-grid { grid-template-columns:repeat(auto-fill,minmax(260px,1fr)) } }
        @media (max-width:768px) { .sidebar { width: 0; padding-left: 0; padding-right: 0; overflow: hidden } .main-wrapper { margin-left: 0 } .header { padding: .75rem 1rem } .header .search-bar { max-width: none; margin-right: .5rem } .header .search-bar input { flex-grow: 1 } .header .user-info .username { display: none } main { padding: 1rem } .page-title-area h1 { font-size: 1.5rem } .filters-bar { flex-direction: column; align-items: stretch } .filters-bar>div { width: 100% } .filters-bar .btn { width: 100%; margin-top: .5rem } .fab { bottom: 20px; right: 20px; width: 50px; height: 50px; font-size: 1.5rem } .product-grid { grid-template-columns:1fr; gap: 1rem } }

    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
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
            <!-- Header -->
            <header class="header">
                <form class="search-bar d-flex" method="GET" action="produtos.php">
                    <input class="form-control form-control-sm" type="search" name="busca" 
                           value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Buscar por nome ou SKU...">
                    <?php if (!empty($categoria_id)): ?>
                        <input type="hidden" name="categoria" value="<?= htmlspecialchars($categoria_id) ?>">
                    <?php endif; ?>
                    <button class="btn btn-sm" type="submit"><i class="fas fa-search"></i></button>
                </form>
                <div class="user-info d-flex align-items-center">
                    <span class="username"><?= $userName ?></span>
                    <a href="logout.php" class="btn-logout" title="Sair">
                        <i class="fas fa-sign-out-alt fa-lg"></i>
                    </a>
                </div>
            </header>

            <main>
                <div class="page-title-area">
                    <h1><i class="fas fa-boxes-stacked me-2"></i>Produtos</h1>
                </div>

                <form method="GET" action="produtos.php">
                    <div class="filters-bar">
                        <?php if (!empty($search)): ?>
                            <input type="hidden" name="busca" value="<?= htmlspecialchars($search) ?>">
                        <?php endif; ?>
                        <div class="flex-grow-1">
                            <select class="form-select form-select-sm" name="categoria" onchange="this.form.submit()">
                                <option value="">Todas as Categorias</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" 
                                        <?= ($categoria_id == $cat['id'] ? 'selected' : '') ?>>
                                        <?= htmlspecialchars($cat['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (!empty($search) || !empty($categoria_id)): ?>
                            <a href="produtos.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Limpar Filtros
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if (!empty($produtos)): ?>
                    <div class="product-grid">
                        <?php foreach ($produtos as $produto): ?>
                            <div class="product-card">
                                <div class="product-header">
                                    <h5 title="<?= htmlspecialchars($produto['nome']) ?>"><?= htmlspecialchars($produto['nome']) ?></h5>
                                    <small>SKU: <?= htmlspecialchars($produto['sku']) ?></small>
                                </div>
                                <div class="product-body">
                                    <div class="mb-2">
                                        <strong>Categoria:</strong> 
                                        <span class="value-text"><?= htmlspecialchars($produto['categoria_nome'] ?? 'N/A') ?></span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Preço:</strong> 
                                        <span class="value-text">R$ <?= number_format($produto['preco_unitario'] ?? 0, 2, ',', '.') ?></span>
                                    </div>
                                    <div>
                                        <strong>Estoque:</strong>
                                        <?php
                                        $quantidade = $produto['quantidade'] ?? 0;
                                        $quantidade_minima = $produto['quantidade_minima'] ?? 0;
                                        $stock_class = 'stock-normal'; $stock_text = 'Normal';
                                        if ($quantidade <= 0) { $stock_class = 'stock-zero'; $stock_text = 'Sem Estoque'; } 
                                        elseif ($quantidade_minima > 0 && $quantidade <= $quantidade_minima) { $stock_class = 'stock-low'; $stock_text = 'Baixo'; }
                                        ?>
                                        <span class="stock-badge <?= $stock_class ?>">
                                            <?= number_format($quantidade) ?> un. <span class="d-none d-sm-inline">- <?= $stock_text ?></span>
                                        </span>
                                    </div>
                                </div>
                                <div class="product-footer">
                                    <div class="btn-group w-100" role="group">
                                        <a href="editar_produto.php?id=<?= $produto['id'] ?>" class="btn btn-outline-primary"><i class="fas fa-edit me-1"></i> Editar</a>
                                        <a href="movimentar_produto.php?id=<?= $produto['id'] ?>" class="btn btn-outline-secondary"><i class="fas fa-exchange-alt me-1"></i> Movimentar</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-products-message">
                        <i class="fas fa-box-open"></i>
                        <p>Nenhum produto encontrado.</p>
                        <?php if (!empty($search) || !empty($categoria_id)): ?>
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