<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['logged_in'] = true; 
    $_SESSION['user_name'] = 'Usuário Demonstração'; 
}

$conexao_path = dirname(dirname(__FILE__)) . '/factory/conexao.php';
if (file_exists($conexao_path)) {
    require_once $conexao_path;
} else {
    $mysqli = null; 
}

// --- LÓGICA DE DADOS DO DASHBOARD --- (Kept as is)
$summary = ['total_produtos' => 0, 'total_itens' => 0, 'valor_total' => 0];
$produtos_recentes = [];
$low_stock_items = [];
$category_stock_data = ["labels" => [], "data" => [], "colors" => []];

if ($mysqli && !$mysqli->connect_errno) {
    // ... (PHP data fetching logic - kept identical to your previous version) ...
    // 1. Sumário do Estoque (KPIs)
    $query_total = "SELECT COUNT(*) as total_produtos, SUM(quantidade) as total_itens, SUM(quantidade * preco_unitario) as valor_total FROM produtos WHERE ativo = 1";
    if ($stmt_total = $mysqli->prepare($query_total)) { $stmt_total->execute(); $result_total = $stmt_total->get_result(); if ($result_total && $result_total->num_rows > 0) { $summary_data = $result_total->fetch_assoc(); $summary['total_produtos'] = $summary_data['total_produtos']??0; $summary['total_itens'] = $summary_data['total_itens']??0; $summary['valor_total'] = $summary_data['valor_total']??0; } $stmt_total->close(); }
    // 2. Produtos Recentes
    $query_products = "SELECT p.id, p.sku, p.nome, p.quantidade, p.preco_unitario, p.quantidade_minima, p.data_cadastro, c.nome as categoria_nome FROM produtos p LEFT JOIN categorias c ON p.categoria_id = c.id AND c.ativo = 1 WHERE p.ativo = 1 ORDER BY p.data_cadastro DESC LIMIT 7";
    if ($stmt_products = $mysqli->prepare($query_products)) { $stmt_products->execute(); $result_products = $stmt_products->get_result(); if ($result_products) { while($produto = $result_products->fetch_assoc()) { $produtos_recentes[] = $produto; } } $stmt_products->close(); }
    // 3. Produtos com Estoque Baixo
    $query_low_stock = "SELECT p.id, p.sku, p.nome, p.quantidade, p.quantidade_minima, c.nome as categoria_nome FROM produtos p LEFT JOIN categorias c ON p.categoria_id = c.id AND c.ativo = 1 WHERE p.quantidade <= p.quantidade_minima AND p.quantidade_minima > 0 AND p.ativo = 1 ORDER BY (p.quantidade / NULLIF(p.quantidade_minima, 0)) ASC, p.quantidade ASC";
    if ($stmt_low_stock = $mysqli->prepare($query_low_stock)) { $stmt_low_stock->execute(); $result_low_stock = $stmt_low_stock->get_result(); if ($result_low_stock) { while($item = $result_low_stock->fetch_assoc()) { $low_stock_items[] = $item; } } $stmt_low_stock->close(); }
    // 4. Dados para Gráfico
    $query_category_stock = "SELECT c.nome as categoria_nome, SUM(p.quantidade) as total_quantidade FROM produtos p JOIN categorias c ON p.categoria_id = c.id WHERE p.ativo = 1 AND c.ativo = 1 GROUP BY c.id, c.nome HAVING SUM(p.quantidade) > 0 ORDER BY total_quantidade DESC LIMIT 5";
    if ($stmt_cat_stock = $mysqli->prepare($query_category_stock)) { $stmt_cat_stock->execute(); $result_cat_stock = $stmt_cat_stock->get_result(); $bgColors = ['rgba(76,1,130,.8)','rgba(108,95,141,.8)','rgba(156,140,185,.8)','rgba(125,60,180,.8)','rgba(26,0,65,.8)']; $i=0; if ($result_cat_stock) { while($cat_data = $result_cat_stock->fetch_assoc()) { $category_stock_data['labels'][] = $cat_data['categoria_nome']; $category_stock_data['data'][] = (int)$cat_data['total_quantidade']; $category_stock_data['colors'][] = $bgColors[$i % count($bgColors)]; $i++; } } $stmt_cat_stock->close(); }
} else {
    // Dummy data
    $summary = ['total_produtos' => 125, 'total_itens' => 2340, 'valor_total' => 150200.75];
    $produtos_recentes = [['id'=>1, 'sku'=>'SKU001', 'nome'=>'Produto Exemplo A', 'categoria_nome'=>'Eletrônicos', 'quantidade'=>10, 'preco_unitario'=>199.90, 'quantidade_minima'=>5, 'data_cadastro' => '2023-10-26'], ['id'=>2, 'sku'=>'SKU002', 'nome'=>'Produto Exemplo B', 'categoria_nome'=>'Livros', 'quantidade'=>5, 'preco_unitario'=>29.50, 'quantidade_minima'=>3, 'data_cadastro' => '2023-10-25']];
    $low_stock_items = [['id'=>3, 'sku'=>'SKU003', 'nome'=>'Item Crítico X', 'quantidade'=>2, 'quantidade_minima'=>5, 'categoria_nome'=>'Material Escritório'], ['id'=>4, 'sku'=>'SKU004', 'nome'=>'Item Crítico Y', 'quantidade'=>8, 'quantidade_minima'=>10, 'categoria_nome'=>'Acessórios']];
    $category_stock_data = ["labels" => ["Eletrônicos","Livros","Roupas","Casa","Escritório"], "data" => [350,120,280,150,90], "colors" => ['rgba(76,1,130,.8)','rgba(108,95,141,.8)','rgba(156,140,185,.8)','rgba(125,60,180,.8)','rgba(26,0,65,.8)']];
}

$userName = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Taskflow</title>
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

        /* Spinning Animation for Gear */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Menu item styling - kept as original dark theme */
        .sidebar .menu ul { list-style: none; padding: 1.25rem 0; margin:0; }
        .sidebar .menu li a {
            display: flex; align-items: center; padding: 0.9rem 1.75rem; 
            color: var(--taskflow-light-lavender); 
            text-decoration: none; 
            transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out, border-left-color 0.2s ease-in-out;
            font-size: 0.98rem; border-left: 4px solid transparent; font-weight: 500;
        }
        .sidebar .menu li a i { margin-right: 1rem; width: 22px; text-align: center; font-size: 1.15em; }
        .sidebar .menu li a:hover {
            background-color: rgba(76, 1, 130, 0.35); 
            color: var(--taskflow-white); 
            border-left-color: var(--taskflow-light-lavender);
        }
        .sidebar .menu li.active a {
            background-color: var(--taskflow-vibrant-purple); 
            color: var(--taskflow-white); 
            font-weight: 600;
            border-left-color: var(--taskflow-light-gray-beige); 
        }

        /* ... (Rest of the CSS remains IDENTICAL to your previous dashboard.php) ... */
        .main-wrapper { flex-grow: 1; margin-left: 260px; display: flex; flex-direction: column; transition: margin-left .3s ease-in-out; }
        .header { background-color: var(--taskflow-card-bg); padding: .9rem 1.75rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--taskflow-border-color); box-shadow: 0 2px 4px rgba(26,0,65,.05); position: sticky; top: 0; z-index: 1020 }
        .header .search-bar { max-width: 450px; flex-grow: 1; }
        .header .search-bar .form-control { border-right: 0; border-top-right-radius: 0; border-bottom-right-radius: 0; border-color: var(--taskflow-border-color); font-size: .9rem; padding: .45rem .9rem }
        .header .search-bar .form-control:focus { border-color: var(--taskflow-vibrant-purple); box-shadow: 0 0 0 .2rem rgba(76,1,130,.2) }
        .header .search-bar button { border-top-left-radius: 0; border-bottom-left-radius: 0; background-color: var(--taskflow-vibrant-purple); color: white; border-color: var(--taskflow-vibrant-purple); padding: .45rem .9rem }
        .header .search-bar button:hover { background-color: var(--taskflow-deepest-purple); border-color: var(--taskflow-deepest-purple) }
        .header .user-info .username { margin-right: 1.25rem; font-weight: 500; color: var(--taskflow-text-primary) }
        .header .btn-logout { color: var(--taskflow-muted-purple); font-size: 1.2rem }
        .header .btn-logout:hover { color: var(--taskflow-deepest-purple) }
        main { padding: 1.75rem; background-color: var(--taskflow-body-bg); flex-grow: 1 }
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(250px,1fr)); gap: 1.75rem; margin-bottom: 1.75rem }
        .kpi-card { background-color: var(--taskflow-card-bg); border: 1px solid var(--taskflow-border-color); border-left-width: 5px; border-radius: .5rem; box-shadow: 0 .15rem .4rem rgba(26,0,65,.07); padding: 1.5rem; display: flex; align-items: center; transition: transform .2s ease-out,box-shadow .2s ease-out }
        .kpi-card:hover { transform: translateY(-4px); box-shadow: 0 .5rem 1rem rgba(26,0,65,.1) }
        .kpi-card .card-icon { font-size: 1.8rem; min-width: 52px; height: 52px; margin-right: 1.25rem; border-radius: 50%; color: var(--taskflow-white); display: flex; align-items: center; justify-content: center }
        .kpi-card.icon-produtos { border-left-color: var(--taskflow-vibrant-purple) }
        .kpi-card.icon-produtos .card-icon { background-color: var(--taskflow-vibrant-purple) }
        .kpi-card.icon-itens { border-left-color: var(--taskflow-muted-purple) }
        .kpi-card.icon-itens .card-icon { background-color: var(--taskflow-muted-purple) }
        .kpi-card.icon-valor { border-left-color: var(--taskflow-light-lavender) }
        .kpi-card.icon-valor .card-icon { background-color: var(--taskflow-light-lavender); color: var(--taskflow-deepest-purple) }
        .kpi-card .card-content .card-title { font-size: .9rem; color: var(--taskflow-text-secondary); margin-bottom: .3rem; text-transform: uppercase; letter-spacing: .6px; font-weight: 500 }
        .kpi-card .card-content .card-value { font-size: 1.9rem; font-weight: 700; color: var(--taskflow-text-primary) }
        .widget-card { background-color: var(--taskflow-card-bg); border: 1px solid var(--taskflow-border-color); border-radius: .5rem; box-shadow: 0 .15rem .4rem rgba(26,0,65,.07); margin-bottom: 1.75rem; display: flex; flex-direction: column; height: 100% }
        .widget-card .card-header { padding: 1rem 1.5rem; margin-bottom: 0; background-color: #fdfcff; border-bottom: 1px solid var(--taskflow-border-color); display: flex; justify-content: space-between; align-items: center; border-top-left-radius: .5rem; border-top-right-radius: .5rem }
        .widget-card .card-header h2 { font-size: 1.1rem; font-weight: 600; margin-bottom: 0; color: var(--taskflow-text-primary) }
        .widget-card .card-body { padding: 1.5rem; flex-grow: 1 }
        .widget-card .card-body.p-0 { padding: 0 }
        .widget-card .table { margin-bottom: 0 }
        .widget-card .table th,.widget-card .table td { vertical-align: middle; font-size: .92rem; padding: .85rem 1.25rem }
        .widget-card .table thead th { background-color: #f8f9fc; color: var(--taskflow-text-secondary); font-weight: 600; border-bottom-width: 1px }
        .widget-card .table tbody tr:hover { background-color: rgba(156,140,185,.07) }
        .widget-card .table td a { color: var(--taskflow-vibrant-purple); text-decoration: none; font-weight: 500 }
        .widget-card .table td a:hover { color: var(--taskflow-deepest-purple); text-decoration: underline }
        .low-stock-value { color: #e74c3c; font-weight: 700 } 
        .actions .btn-action { margin: 0 .25rem; padding: .3rem .6rem; font-size: .88rem }
        .btn-outline-primary { border-color: var(--taskflow-vibrant-purple); color: var(--taskflow-vibrant-purple) }
        .btn-outline-primary:hover { background-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white) }
        .btn-outline-success { border-color: var(--taskflow-muted-purple); color: var(--taskflow-muted-purple) }
        .btn-outline-success:hover { background-color: var(--taskflow-muted-purple); color: var(--taskflow-white) }
        .alert-estoque-baixo { background-color: rgba(231,76,60,.1); border: 1px solid rgba(231,76,60,.3); color: #c0392b; border-left: 4px solid #e74c3c; border-radius: .375rem }
        .alert-estoque-baixo .alert-heading { color: #e74c3c }
        .alert-estoque-baixo ul { margin-bottom: 0; padding-left: 1.2rem }
        .alert-estoque-baixo li { margin-bottom: .3rem; font-size: .9rem }
        .alert-estoque-baixo .btn-outline-secondary { border-color: var(--taskflow-muted-purple); color: var(--taskflow-muted-purple); background-color: transparent; font-size: .8rem; padding: .1rem .4rem }
        .alert-estoque-baixo .btn-outline-secondary:hover { background-color: var(--taskflow-muted-purple); color: var(--taskflow-white) }
        .alert-estoque-baixo .btn-close { filter: invert(30%) sepia(40%) saturate(2000%) hue-rotate(330deg) brightness(90%) contrast(100%) }
        .card-footer.bg-light { background-color: #fdfcff!important; border-top: 1px solid var(--taskflow-border-color) }
        .btn-outline-secondary { border-color: var(--taskflow-muted-purple); color: var(--taskflow-muted-purple) }
        .btn-outline-secondary:hover { background-color: var(--taskflow-muted-purple); color: var(--taskflow-white) }
        .bg-critical-header { background-color: rgba(231,76,60,.08)!important }
        .text-critical-header { color: #c0392b!important }
        .fab { position: fixed; bottom: 30px; right: 30px; width: 56px; height: 56px; background-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.7rem; text-decoration: none; box-shadow: 0 5px 15px rgba(26,0,65,.3); z-index: 1050; transition: transform .25s ease,background-color .25s ease,box-shadow .25s ease }
        .fab:hover { transform: scale(1.08) translateY(-2px); background-color: var(--taskflow-deepest-purple); box-shadow: 0 8px 20px rgba(26,0,65,.4); color: var(--taskflow-white) }
        @media (max-width:992px) { .kpi-grid { grid-template-columns:repeat(auto-fit,minmax(220px,1fr)) } }
        @media (max-width:768px) { .sidebar { width: 0; padding-left: 0; padding-right: 0; overflow: hidden } .main-wrapper { margin-left: 0 } .header { padding: .75rem 1rem } .header .search-bar { max-width: none } .header .user-info .username { display: none } main { padding: 1rem } .kpi-grid { gap: 1rem } .kpi-card { padding: 1rem } .kpi-card .card-icon { font-size: 1.5rem; min-width: 40px; height: 40px; margin-right: .8rem } .kpi-card .card-content .card-value { font-size: 1.5rem } .fab { bottom: 20px; right: 20px; width: 50px; height: 50px; font-size: 1.5rem } .row>[class*=col-] { margin-bottom: 1rem } .widget-card .card-header h2 { font-size: 1rem } }
        #categoryStockChartContainer { position: relative; height: 300px; width: 100% }
        @media (min-width:768px) { #categoryStockChartContainer { height: 350px } }

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
                    <li class="active"><a href="index.php"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
                    <li><a href="produtos.php"><i class="fas fa-boxes-stacked"></i> Produtos</a></li>
                    <li><a href="categorias.php"><i class="fas fa-tags"></i> Categorias</a></li>
                    <li><a href="movimentacoes.php"><i class="fas fa-truck-ramp-box"></i> Movimentações</a></li>
                    <li><a href="relatorios.php"><i class="fas fa-file-invoice"></i> Relatórios</a></li>
                </ul>
            </nav>
        </aside>
        <div class="main-wrapper">
            <header class="header">
                <form class="search-bar d-flex" action="produtos.php" method="GET">
                    <input class="form-control form-control-sm" type="search" name="busca" placeholder="Buscar produtos por nome ou SKU..." aria-label="Search">
                    <button class="btn btn-sm" type="submit"><i class="fas fa-search"></i></button>
                </form>
                <div class="user-info d-flex align-items-center">
                    <span class="username"><?= $userName ?></span>
                    <a href="logout.php" class="btn-logout" title="Sair"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                </div>
            </header>
            <main>
                <?php if (!empty($low_stock_items)): ?>
                <div class="alert alert-dismissible fade show alert-estoque-baixo" role="alert">
                    <h5 class="alert-heading mb-2"><i class="fas fa-exclamation-triangle me-2"></i>Atenção! Produtos com Estoque Baixo:</h5>
                    <ul class="list-unstyled">
                        <?php foreach (array_slice($low_stock_items, 0, 3) as $item): ?>
                            <li class="d-flex justify-content-between align-items-center mb-1">
                                <span>
                                    <strong><?= htmlspecialchars($item['nome']) ?></strong> (SKU: <?= htmlspecialchars($item['sku']) ?>) - 
                                    Estoque: <span class="fw-bold"><?= number_format($item['quantidade']) ?></span> (Mín: <?= number_format($item['quantidade_minima']) ?>)
                                </span>
                                <a href="editar_produto.php?id=<?= $item['id'] ?>" class="btn btn-xs btn-outline-secondary ms-2" title="Ver Produto">Ver</a>
                            </li>
                        <?php endforeach; ?>
                        <?php if (count($low_stock_items) > 3): ?>
                            <li><small class="text-muted">E mais <?= count($low_stock_items) - 3 ?> outro(s) item(ns) em estoque baixo.</small></li>
                        <?php endif; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="kpi-grid">
                    <div class="kpi-card icon-produtos">
                        <div class="card-icon"><i class="fas fa-archive"></i></div>
                        <div class="card-content">
                            <span class="card-title">Produtos Ativos</span>
                            <span class="card-value"><?= number_format($summary['total_produtos'] ?? 0) ?></span>
                        </div>
                    </div>
                    <div class="kpi-card icon-itens">
                        <div class="card-icon"><i class="fas fa-cubes"></i></div>
                        <div class="card-content">
                            <span class="card-title">Itens em Estoque (Ativos)</span>
                            <span class="card-value"><?= number_format($summary['total_itens'] ?? 0) ?></span>
                        </div>
                    </div>
                    <div class="kpi-card icon-valor">
                        <div class="card-icon"><i class="fas fa-dollar-sign"></i></div>
                        <div class="card-content">
                            <span class="card-title">Valor em Estoque (Ativos)</span>
                            <span class="card-value">R$ <?= number_format($summary['valor_total'] ?? 0, 2, ',', '.') ?></span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-7 mb-4 d-flex flex-column">
                        <div class="widget-card flex-grow-1">
                            <div class="card-header">
                                <h2><i class="fas fa-history me-2"></i>Produtos Cadastrados Recentemente</h2>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped mb-0">
                                        <thead>
                                            <tr><th>SKU</th><th>Nome</th><th>Categoria</th><th class="text-center">Qtd.</th><th>Preço Unit.</th><th class="text-center">Ações</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($produtos_recentes)): ?>
                                                <?php foreach($produtos_recentes as $produto): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($produto['sku']) ?></td>
                                                        <td><a href="editar_produto.php?id=<?= $produto['id'] ?>"><?= htmlspecialchars($produto['nome']) ?></a></td>
                                                        <td><?= htmlspecialchars($produto['categoria_nome'] ?? 'N/A') ?></td>
                                                        <td class="text-center <?= ($produto['quantidade'] !== null && $produto['quantidade_minima'] !== null && $produto['quantidade'] <= $produto['quantidade_minima']) ? 'low-stock-value' : '' ?>"><?= number_format($produto['quantidade'] ?? 0) ?></td>
                                                        <td>R$ <?= number_format($produto['preco_unitario'] ?? 0, 2, ',', '.') ?></td>
                                                        <td class="actions text-center">
                                                            <a href="editar_produto.php?id=<?= $produto['id'] ?>" class="btn btn-sm btn-outline-primary btn-action" title="Editar"><i class="fas fa-edit"></i></a>
                                                            <a href="movimentar_produto.php?id=<?= $produto['id'] ?>" class="btn btn-sm btn-outline-success btn-action" title="Movimentar"><i class="fas fa-exchange-alt"></i></a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="6" class="text-center py-5 text-muted fst-italic">Nenhum produto recente para exibir.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                             <?php if (count($produtos_recentes) >= 7): ?>
                            <div class="card-footer text-center"><a href="produtos.php" class="btn btn-outline-secondary btn-sm">Ver Todos os Produtos</a></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-lg-5 mb-4 d-flex flex-column">
                        <div class="widget-card mb-4 flex-grow-1">
                            <div class="card-header">
                                <h2><i class="fas fa-chart-pie me-2"></i>Estoque por Categoria (Top 5)</h2>
                            </div>
                            <div class="card-body d-flex align-items-center justify-content-center" id="categoryStockChartContainer">
                                <?php if (!empty($category_stock_data['labels'])): ?>
                                    <canvas id="categoryStockChart"></canvas>
                                <?php else: ?>
                                    <p class="text-center text-muted m-0 fst-italic">Não há dados de estoque por categoria para exibir o gráfico.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($low_stock_items)): ?>
                        <div class="widget-card flex-grow-0">
                            <div class="card-header bg-critical-header">
                                <h2 class="text-critical-header"><i class="fas fa-exclamation-circle me-2"></i>Itens Críticos em Estoque</h2>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    <?php foreach (array_slice($low_stock_items, 0, 5) as $item): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                            <div>
                                                <a href="editar_produto.php?id=<?= $item['id'] ?>" class="text-decoration-none fw-medium"><?= htmlspecialchars($item['nome']) ?></a>
                                                <small class="d-block text-muted">SKU: <?= htmlspecialchars($item['sku']) ?></small>
                                            </div>
                                            <span class="badge bg-danger rounded-pill fs-08rem px-2 py-1"><?= number_format($item['quantidade']) ?> / <?= number_format($item['quantidade_minima']) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                             <?php if (count($low_stock_items) > 5) : ?>
                                <div class="card-footer text-center py-2"><small class="text-muted">E mais <?= count($low_stock_items) - 5 ?> item(ns) em estado crítico...</small></div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <a href="adicionar_produto.php" class="fab" title="Adicionar Novo Produto"><i class="fas fa-plus"></i></a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // JavaScript remains the same
    document.addEventListener('DOMContentLoaded', function() {
        const categoryLabels = <?= json_encode($category_stock_data['labels'] ?? []); ?>;
        const categoryData = <?= json_encode($category_stock_data['data'] ?? []); ?>;
        const categoryColors = <?= json_encode($category_stock_data['colors'] ?? []); ?>;
        const ctxCategory = document.getElementById('categoryStockChart');
        if (ctxCategory && categoryLabels.length > 0 && categoryData.length > 0) {
            Chart.defaults.font.family = 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif';
            new Chart(ctxCategory, {
                type: 'doughnut',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        label: 'Quantidade em Estoque', data: categoryData, backgroundColor: categoryColors,
                        borderColor: categoryColors.map(color => color.replace(/0\.[0-9]+\)/, '1)')),
                        borderWidth: 1.5, hoverOffset: 10, hoverBorderColor: 'var(--taskflow-white)', hoverBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    animation: { animateScale: true, animateRotate: true, duration: 1200 },
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 25, font: { size: 13, weight: '500' }, color: 'var(--taskflow-text-secondary)' } },
                        tooltip: {
                            enabled: true, backgroundColor: 'rgba(26, 0, 65, 0.95)', titleColor: 'var(--taskflow-light-lavender)',
                            bodyColor: 'var(--taskflow-white)', titleFont: { size: 14, weight: 'bold'}, bodyFont: { size: 13 },
                            padding: 12, cornerRadius: 6, displayColors: true, boxPadding: 3,
                            callbacks: { label: function(context) { let label = context.label||''; if(label){label+=': '} if(context.parsed!==null){label+=context.parsed.toLocaleString('pt-BR')} return label+' itens' } }
                        }
                    },
                    cutout: '65%'
                }
            });
        }
    });
    </script>
</body>
</html>