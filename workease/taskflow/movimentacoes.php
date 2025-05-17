<?php
session_start();
// Simulate user session if not set for testing
if (!isset($_SESSION['user_name'])) {
    $_SESSION['user_name'] = 'Usuário Teste';
}
if (!isset($_SESSION['logged_in'])) {
    $_SESSION['logged_in'] = true;
}

$conexao_path = dirname(dirname(__FILE__)) . '/factory/conexao.php';
if (file_exists($conexao_path)) {
    require_once $conexao_path;
} else {
    $mysqli = null;
}

// --- DATA LOGIC FOR MOVIMENTACOES --- (Kept as is from your provided code) ---
$filtro_produto_id = isset($_GET['produto_id']) ? filter_var($_GET['produto_id'], FILTER_VALIDATE_INT) : ''; // Usar FILTER_VALIDATE_INT
$filtro_tipo = isset($_GET['tipo_movimento']) ? filter_var($_GET['tipo_movimento']) : '';
$filtro_data_inicio = isset($_GET['data_inicio']) ? filter_var($_GET['data_inicio']) : '';
$filtro_data_fim = isset($_GET['data_fim']) ? filter_var($_GET['data_fim']) : '';

$movimentacoes = []; $produtos_lista = [];
if ($mysqli && !$mysqli->connect_errno) {
    // Adicionado WHERE ativo = 1 para produtos na lista do modal também
    $stmt_prod_list = $mysqli->prepare("SELECT id, nome, sku FROM produtos WHERE ativo = 1 ORDER BY nome ASC");
    if ($stmt_prod_list) { $stmt_prod_list->execute(); $result_prod_list = $stmt_prod_list->get_result(); if ($result_prod_list) { $produtos_lista = $result_prod_list->fetch_all(MYSQLI_ASSOC); } $stmt_prod_list->close(); }
    
    $query = "SELECT m.id, 
              m.tipo_movimento,
              m.quantidade,
              m.data_movimento,
              m.motivo,
              m.observacoes,
              m.documento_referencia,
              p.nome as produto_nome,
              p.sku as produto_sku,
              f.nome as responsavel_nome
              FROM movimentacoes_estoque m
              LEFT JOIN produtos p ON m.produto_id = p.id
              LEFT JOIN funcionarios f ON m.responsavel_id = f.id"; // Assumindo que funcionarios.nome existe

    $where_conditions = [];
    $params = [];
    $types = "";

    // Condição para apenas produtos ativos, se aplicável às movimentações
    // $where_conditions[] = "p.ativo = 1"; // Descomente se movimentações devem ser apenas de produtos ativos

    if (!empty($filtro_produto_id) && $filtro_produto_id !== false) { // Verificar se a validação foi ok
        $where_conditions[] = "m.produto_id = ?";
        $params[] = $filtro_produto_id;
        $types .= "i";
    }

    if (!empty($filtro_tipo)) {
        $where_conditions[] = "m.tipo_movimento = ?";
        $params[] = $filtro_tipo;
        $types .= "s";
    }

    if (!empty($filtro_data_inicio)) {
        $where_conditions[] = "DATE(m.data_movimento) >= ?";
        $params[] = $filtro_data_inicio;
        $types .= "s";
    }

    if (!empty($filtro_data_fim)) {
        $where_conditions[] = "DATE(m.data_movimento) <= ?";
        $params[] = $filtro_data_fim;
        $types .= "s";
    }

    if (!empty($where_conditions)) {
        $query .= " WHERE " . implode(" AND ", $where_conditions);
    }

    $query .= " ORDER BY m.data_movimento DESC, m.id DESC LIMIT 50";

    $stmt_mov = $mysqli->prepare($query);
    if ($stmt_mov) { if (!empty($params)) { $stmt_mov->bind_param($types, ...$params); } $stmt_mov->execute(); $result_mov = $stmt_mov->get_result(); if ($result_mov) { $movimentacoes = $result_mov->fetch_all(MYSQLI_ASSOC); } $stmt_mov->close(); }
} else {
    $produtos_lista = [['id' => 1, 'nome' => 'Produto Exemplo A', 'sku' => 'SKU001'], ['id' => 2, 'nome' => 'Produto Exemplo B', 'sku' => 'SKU002']];
    // Corrigindo nomes das chaves nos dados simulados para consistência
    $movimentacoes = [
        ['id' => 1, 'produto_nome' => 'Produto Exemplo A', 'produto_sku' => 'SKU001', 'tipo_movimento' => 'entrada', 'quantidade' => 50, 'data_movimento' => '2023-10-20 10:00:00', 'observacoes' => 'Recebimento do fornecedor X', 'responsavel_nome' => 'Admin'], 
        ['id' => 2, 'produto_nome' => 'Produto Exemplo B', 'produto_sku' => 'SKU002', 'tipo_movimento' => 'entrada', 'quantidade' => 100, 'data_movimento' => '2023-10-21 11:30:00', 'observacoes' => 'Compra inicial', 'responsavel_nome' => 'Admin'], 
        ['id' => 3, 'produto_nome' => 'Produto Exemplo A', 'produto_sku' => 'SKU001', 'tipo_movimento' => 'saida', 'quantidade' => 10, 'data_movimento' => '2023-10-22 14:15:00', 'observacoes' => 'Venda para cliente Y', 'responsavel_nome' => 'Vendedor 1'], 
        ['id' => 4, 'produto_nome' => 'Produto Exemplo C', 'produto_sku' => 'SKU003', 'tipo_movimento' => 'ajuste_positivo', 'quantidade' => 5, 'data_movimento' => '2023-10-23 09:00:00', 'observacoes' => 'Contagem de inventário', 'responsavel_nome' => 'Estoquista'], 
        ['id' => 5, 'produto_nome' => 'Produto Exemplo B', 'produto_sku' => 'SKU002', 'tipo_movimento' => 'ajuste_negativo', 'quantidade' => 2, 'data_movimento' => '2023-10-24 16:45:00', 'observacoes' => 'Produto avariado', 'responsavel_nome' => 'Estoquista']
    ];
}
$userName = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Usuário';
$softwareName = "Taskflow"; // ** ADICIONADO PARA CONSISTÊNCIA **
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimentações de Estoque - <?= htmlspecialchars($softwareName) // ** USANDO $softwareName ** ?></title>
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
            --taskflow-text-primary: #1f2937;
            --taskflow-text-secondary: #6b7280;
            --taskflow-border-color: #e3e6f0;
            --taskflow-success: #198754; 
            --taskflow-danger: #dc3545;
            --taskflow-warning: #ffc107; /* Usada para ajuste_negativo */
            --taskflow-info: #0dcaf0;    /* Usada para ajuste_positivo */

            /* ** CORES PARA A ÁUREA DO LOGO (COPIADO DO INDEX.PHP DO DASHBOARD) ** */
            --logo-aura-color: #6A0DAD; 
            --logo-aura-highlight: #9370DB;
        }
        body { background-color: var(--taskflow-body-bg); font-family: 'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; color: var(--taskflow-text-primary); transition: margin-left .3s ease-in-out }
        .dashboard-container { display: flex; min-height: 100vh }
        .sidebar {
            width: 260px; background-color: var(--taskflow-deepest-purple); color: var(--taskflow-light-gray-beige);
            padding-top: 0; position: fixed; height: 100%; overflow-y: auto; 
            box-shadow: 3px 0 15px rgba(0,0,0,.15); z-index: 1030; 
            transition: width .3s ease-in-out;
        }
        
        /* ** ESTILOS DO LOGO DA SIDEBAR (COPIADO DO INDEX.PHP DO DASHBOARD) ** */
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
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 50%; 
            position: relative;
            z-index: 2;
        }

        .sidebar .logo-image-wrapper::before { 
            content: '';
            position: absolute;
            top: -4px; 
            left: -4px;
            width: calc(100% + 8px); 
            height: calc(100% + 8px);
            border-radius: 50%;
            box-shadow: 0 0 6px 1px var(--logo-aura-color), 0 0 9px 2px var(--logo-aura-highlight);
            animation: rotateAuraSidebar 10s linear infinite, pulseAuraSidebar 2.5s ease-in-out infinite alternate;
            z-index: 1;
        }

        @keyframes rotateAuraSidebar {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes pulseAuraSidebar {
            0% {
                box-shadow: 0 0 5px 1px var(--logo-aura-color), 0 0 8px 2px var(--logo-aura-highlight);
                opacity: 0.6;
            }
            100% {
                box-shadow: 0 0 8px 2px var(--logo-aura-highlight), 0 0 12px 3px var(--logo-aura-color);
                opacity: 1;
            }
        }
        
        .sidebar .logo-area .logo-text-brand { 
            font-size: 1.5rem; 
            font-weight: 700;
            color: var(--taskflow-white);
            letter-spacing: 0.5px;
            line-height: 1; 
        }
        /* ** FIM DOS ESTILOS DO LOGO DA SIDEBAR ** */


        /* Menu Styles (Same as index.php) */
        .sidebar .menu ul { list-style: none; padding: 1.25rem 0; margin: 0 }
        .sidebar .menu li a { display: flex; align-items: center; padding: .9rem 1.75rem; color: var(--taskflow-light-lavender); text-decoration: none; transition: background-color .2s ease-in-out,color .2s ease-in-out,border-left-color .2s ease-in-out; font-size: .98rem; border-left: 4px solid transparent; font-weight:500; }
        .sidebar .menu li a i { margin-right: 1rem; width: 22px; text-align: center; font-size: 1.15em }
        .sidebar .menu li a:hover { background-color: rgba(76,1,130,.35); color: var(--taskflow-white); border-left-color: var(--taskflow-light-lavender) }
        .sidebar .menu li.active a { background-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white); font-weight: 600; border-left-color: var(--taskflow-light-gray-beige) }
        
        /* Main Wrapper, Header, Main content, Filters Card, Table Card, Table, Modal, FAB, Responsive (Same as previous movimentacoes.php) */
        .main-wrapper { flex-grow: 1; margin-left: 260px; display: flex; flex-direction: column; transition: margin-left .3s ease-in-out }
        .header { background-color: var(--taskflow-card-bg); padding: .9rem 1.75rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--taskflow-border-color); box-shadow: 0 2px 4px rgba(26,0,65,.05); position: sticky; top: 0; z-index: 1020; height: 60px; /* ** ALTURA DO HEADER IGUAL À LOGO-AREA ** */ }
        .header .search-bar { visibility: hidden; } /* Search bar escondida nesta página */
        .header .user-info { margin-left: auto; } /* Alinha user-info à direita quando search-bar está hidden */
        .header .user-info .username { margin-right: 1.25rem; font-weight: 500; color: var(--taskflow-text-primary) }
        .header .btn-logout { color: var(--taskflow-muted-purple); font-size: 1.2rem }
        .header .btn-logout:hover { color: var(--taskflow-deepest-purple) }
        main { padding: 1.75rem; background-color: var(--taskflow-body-bg); flex-grow: 1 }
        .page-title-area { margin-bottom: 1.75rem; display: flex; justify-content: space-between; align-items: center }
        .page-title-area h1 { font-size: 1.75rem; font-weight: 600; color: var(--taskflow-text-primary); margin-bottom: 0 }
        .filters-card { background-color: var(--taskflow-card-bg); border: 1px solid var(--taskflow-border-color); border-radius: .5rem; box-shadow: 0 .1rem .3rem rgba(26,0,65,.06); padding: 1.25rem; margin-bottom: 1.75rem }
        .filters-card .form-label { font-weight: 500; color: var(--taskflow-text-secondary); margin-bottom: .3rem; font-size: .85rem }
        .filters-card .form-control,.filters-card .form-select { font-size: .9rem; padding: .45rem .9rem; border-color: var(--taskflow-border-color) }
        .filters-card .form-control:focus,.filters-card .form-select:focus { border-color: var(--taskflow-vibrant-purple); box-shadow: 0 0 0 .2rem rgba(76,1,130,.2) }
        .filters-card .btn-primary { background-color: var(--taskflow-vibrant-purple); border-color: var(--taskflow-vibrant-purple) }
        .filters-card .btn-primary:hover { background-color: var(--taskflow-deepest-purple); border-color: var(--taskflow-deepest-purple) }
        .filters-card .btn-outline-secondary { border-color: var(--taskflow-muted-purple); color: var(--taskflow-muted-purple) }
        .filters-card .btn-outline-secondary:hover { background-color: var(--taskflow-muted-purple); color: var(--taskflow-white) }
        .content-table-card { background-color: var(--taskflow-card-bg); border: 1px solid var(--taskflow-border-color); border-radius: .5rem; box-shadow: 0 .15rem .4rem rgba(26,0,65,.07); margin-bottom: 1.75rem }
        .content-table-card .card-header { padding: 1rem 1.5rem; margin-bottom: 0; background-color: #fdfcff; border-bottom: 1px solid var(--taskflow-border-color); display: flex; justify-content: space-between; align-items: center; border-top-left-radius: .5rem; border-top-right-radius: .5rem }
        .content-table-card .card-header h2 { font-size: 1.1rem; font-weight: 600; margin-bottom: 0; color: var(--taskflow-text-primary) }
        .content-table-card .card-body.p-0 { padding: 0 }
        .table { margin-bottom: 0 }
        .table th,.table td { vertical-align: middle; font-size: .92rem; padding: .85rem 1.25rem }
        .table thead th { background-color: #f8f9fc; color: var(--taskflow-text-secondary); font-weight: 600; border-bottom-width: 1px }
        .table tbody tr:hover { background-color: rgba(156,140,185,.07) }
        .table .badge-movimentacao { font-size: .8rem; padding: .35em .65em; font-weight: 500 }
        .table .badge-entrada { background-color: rgba(25,135,84,.1); color: var(--taskflow-success); border: 1px solid rgba(25,135,84,.3) }
        .table .badge-saida { background-color: rgba(220,53,69,.1); color: var(--taskflow-danger); border: 1px solid rgba(220,53,69,.3) }
        .table .badge-ajuste_positivo { background-color: rgba(13,202,240,.1); color: var(--taskflow-info); border: 1px solid rgba(13,202,240,.3) }
        .table .badge-ajuste_negativo { background-color: rgba(255,193,7,.1); color: var(--taskflow-warning); border: 1px solid rgba(255,193,7,.3) } /* Usando --taskflow-warning */
        .table .observacao-column { max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis }
        .modal-content { background-color: var(--taskflow-card-bg); border: 1px solid var(--taskflow-border-color); border-radius: .5rem }
        .modal-header { background-color: #fdfcff; border-bottom: 1px solid var(--taskflow-border-color); color: var(--taskflow-text-primary); padding: 1rem 1.5rem }
        .modal-header .modal-title { font-weight: 600 }
        .modal-header .btn-close { filter: invert(20%) sepia(10%) saturate(500%) hue-rotate(220deg) brightness(90%) contrast(90%) }
        .modal-body { padding: 1.5rem; color: var(--taskflow-text-primary) }
        .modal-body .form-label { font-weight: 500; color: var(--taskflow-text-secondary); margin-bottom: .3rem; font-size: .9rem }
        .modal-body .form-control,.modal-body .form-select { font-size: .9rem; padding: .45rem .9rem; border-color: var(--taskflow-border-color) }
        .modal-body .form-control:focus,.modal-body .form-select:focus { border-color: var(--taskflow-vibrant-purple); box-shadow: 0 0 0 .2rem rgba(76,1,130,.2) }
        .modal-footer { background-color: #f8f9fc; border-top: 1px solid var(--taskflow-border-color); padding: .9rem 1.5rem }
        .btn-secondary { background-color: var(--taskflow-light-lavender); border-color: var(--taskflow-light-lavender); color: var(--taskflow-deepest-purple) }
        .btn-secondary:hover { background-color: var(--taskflow-muted-purple); border-color: var(--taskflow-muted-purple); color: var(--taskflow-white) }
        .btn-primary { background-color: var(--taskflow-vibrant-purple); border-color: var(--taskflow-vibrant-purple) }
        .btn-primary:hover { background-color: var(--taskflow-deepest-purple); border-color: var(--taskflow-deepest-purple) }
        .fab { position: fixed; bottom: 30px; right: 30px; width: 56px; height: 56px; background-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.7rem; text-decoration: none; box-shadow: 0 5px 15px rgba(26,0,65,.3); z-index: 1050; transition: transform .25s ease,background-color .25s ease,box-shadow .25s ease }
        .fab:hover { transform: scale(1.08) translateY(-2px); background-color: var(--taskflow-deepest-purple); box-shadow: 0 8px 20px rgba(26,0,65,.4); color: var(--taskflow-white) }
        .no-data-message { text-align: center; padding: 2rem; color: var(--taskflow-text-secondary); font-style: italic }
        @media (max-width:768px) { .sidebar { width: 0; padding-left: 0; padding-right: 0; overflow: hidden } .main-wrapper { margin-left: 0 } .header { padding: .75rem 1rem } .header .search-bar { display: none } .header .user-info .username { display: none } main { padding: 1rem } .page-title-area h1 { font-size: 1.5rem } .fab { bottom: 20px; right: 20px; width: 50px; height: 50px; font-size: 1.5rem } .filters-card .row>.col-md-3,.filters-card .row>.col-md-2 { margin-bottom: .75rem } }

    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <!-- ** LOGO AREA MODIFICADA PARA SER IGUAL AO DASHBOARD INDEX.PHP ** -->
            <a href="index.php" class="logo-area" style="text-decoration: none;"> 
                <div class="logo-image-wrapper">
                    <img src="img/taskflow_logo.png" alt="<?= htmlspecialchars($softwareName) ?> Logo">
                </div>
                <span class="logo-text-brand"><?= htmlspecialchars($softwareName) ?></span>
            </a>
            <!-- ** FIM LOGO AREA MODIFICADA ** -->
            <nav class="menu">
                <ul>
                    <li><a href="index.php"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
                    <li><a href="produtos.php"><i class="fas fa-boxes-stacked"></i> Produtos</a></li>
                    <li><a href="categorias.php"><i class="fas fa-tags"></i> Categorias</a></li>
                    <li class="active"><a href="movimentacoes.php"><i class="fas fa-truck-ramp-box"></i> Movimentações</a></li>
                    <li><a href="relatorios.php"><i class="fas fa-file-invoice"></i> Relatórios</a></li>
                </ul>
            </nav>
        </aside>

        <div class="main-wrapper">
            <header class="header">
                <form class="search-bar d-none d-md-flex"></form> 
                <div class="user-info d-flex align-items-center">
                    <span class="username"><?= $userName ?></span>
                    <a href="../logout.php" class="btn-logout" title="Sair"><i class="fas fa-sign-out-alt fa-lg"></i></a> <!-- ** AJUSTADO CAMINHO DO LOGOUT ** -->
                </div>
            </header>

            <main>
                <div class="page-title-area">
                    <h1><i class="fas fa-truck-ramp-box me-2"></i>Movimentações de Estoque</h1>
                </div>
                 <!-- Mensagens de Sucesso/Erro (se aplicável para esta página) -->
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

                <div class="filters-card">
                    <form method="GET" action="movimentacoes.php">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4"><label for="filtroProduto" class="form-label">Produto</label><select class="form-select form-select-sm" id="filtroProduto" name="produto_id"><option value="">Todos os Produtos</option><?php foreach ($produtos_lista as $prod): ?><option value="<?= $prod['id'] ?>" <?= ($filtro_produto_id == $prod['id'] ? 'selected' : '') ?>><?= htmlspecialchars($prod['nome']) ?> (SKU: <?= htmlspecialchars($prod['sku']) ?>)</option><?php endforeach; ?></select></div>
                            <div class="col-md-2"><label for="filtroTipoMov" class="form-label">Tipo</label><select class="form-select form-select-sm" id="filtroTipoMov" name="tipo_movimento"><option value="">Todos os Tipos</option><option value="entrada" <?= ($filtro_tipo == 'entrada' ? 'selected' : '') ?>>Entrada</option><option value="saida" <?= ($filtro_tipo == 'saida' ? 'selected' : '') ?>>Saída</option><option value="ajuste_positivo" <?= ($filtro_tipo == 'ajuste_positivo' ? 'selected' : '') ?>>Ajuste (+)</option><option value="ajuste_negativo" <?= ($filtro_tipo == 'ajuste_negativo' ? 'selected' : '') ?>>Ajuste (-)</option></select></div>
                            <div class="col-md-2"><label for="filtroDataInicio" class="form-label">Data Início</label><input type="date" class="form-control form-control-sm" id="filtroDataInicio" name="data_inicio" value="<?= htmlspecialchars($filtro_data_inicio) ?>"></div>
                            <div class="col-md-2"><label for="filtroDataFim" class="form-label">Data Fim</label><input type="date" class="form-control form-control-sm" id="filtroDataFim" name="data_fim" value="<?= htmlspecialchars($filtro_data_fim) ?>"></div>
                            <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter me-1"></i> Filtrar</button><?php if (!empty($filtro_produto_id) || !empty($filtro_tipo) || !empty($filtro_data_inicio) || !empty($filtro_data_fim)): ?><a href="movimentacoes.php" class="btn btn-outline-secondary btn-sm w-100 mt-2"><i class="fas fa-times me-1"></i> Limpar</a><?php endif; ?></div>
                        </div>
                    </form>
                </div>

                <div class="content-table-card">
                    <div class="card-header"><h2>Histórico de Movimentações</h2></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead><tr><th>Produto (SKU)</th><th>Responsável</th><th class="text-center">Tipo</th><th class="text-center">Quantidade</th><th class="text-center">Data</th><th>Observação/Motivo</th><th>Ref. Doc.</th></tr></thead>
                                <tbody>
                                    <?php if (!empty($movimentacoes)): ?>
                                        <?php foreach ($movimentacoes as $mov): 
                                            $tipo_badge_class = ''; 
                                            $tipo_texto = ucfirst(str_replace('_', ' ', $mov['tipo_movimento']));
                                            switch ($mov['tipo_movimento']) {
                                                case 'entrada': $tipo_badge_class = 'badge-entrada'; break; 
                                                case 'saida': $tipo_badge_class = 'badge-saida'; break; 
                                                case 'ajuste_positivo': $tipo_badge_class = 'badge-ajuste_positivo'; $tipo_texto = 'Ajuste (+)'; break; 
                                                case 'ajuste_negativo': $tipo_badge_class = 'badge-ajuste_negativo'; $tipo_texto = 'Ajuste (-)'; break; 
                                            }
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($mov['produto_nome'] ?? 'N/A') ?>
                                                <small class="d-block text-muted"><?= htmlspecialchars($mov['produto_sku'] ?? 'SKU N/A') ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($mov['responsavel_nome'] ?? 'N/A') ?></td>
                                            <td class="text-center"><span class="badge rounded-pill badge-movimentacao <?= $tipo_badge_class ?>"><?= $tipo_texto ?></span></td>
                                            <td class="text-center fw-bold"><?= number_format($mov['quantidade']) ?></td>
                                            <td class="text-center"><?= date('d/m/Y H:i', strtotime($mov['data_movimento'])) ?></td>
                                            <td class="observacao-column" title="<?= htmlspecialchars($mov['motivo'] ? ($mov['motivo'] . ($mov['observacoes'] ? ' - ' . $mov['observacoes'] : '')) : ($mov['observacoes'] ?? '')) ?>">
                                                <?= htmlspecialchars($mov['motivo'] ? $mov['motivo'] : ($mov['observacoes'] ?? 'N/A')) ?>
                                            </td>
                                            <td><?= htmlspecialchars($mov['documento_referencia'] ?? 'N/A') ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="no-data-message">Nenhuma movimentação encontrada para os filtros aplicados.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="movimentacaoModal" tabindex="-1" aria-labelledby="movimentacaoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="movimentacaoModalLabel">Nova Movimentação de Estoque</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <form id="movimentacaoForm" action="salvar_movimentacao.php" method="POST">
                        <input type="hidden" name="responsavel_id" value="<?= htmlspecialchars($_SESSION['user_id'] ?? '') ?>"> <!-- Assumindo que user_id é o responsável -->
                        <div class="row">
                            <div class="col-md-7 mb-3">
                                <label for="movProdutoId" class="form-label">Produto <span class="text-danger">*</span></label>
                                <select class="form-select" id="movProdutoId" name="produto_id" required>
                                    <option value="" selected disabled>Selecione um produto...</option>
                                    <?php foreach ($produtos_lista as $prod): ?>
                                        <option value="<?= $prod['id'] ?>"><?= htmlspecialchars($prod['nome']) ?> (SKU: <?= htmlspecialchars($prod['sku']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5 mb-3">
                                <label for="movTipo" class="form-label">Tipo de Movimentação <span class="text-danger">*</span></label>
                                <select class="form-select" id="movTipo" name="tipo_movimento" required>
                                    <option value="" selected disabled>Selecione o tipo...</option>
                                    <option value="entrada">Entrada</option>
                                    <option value="saida">Saída</option>
                                    <option value="ajuste_positivo">Ajuste Positivo (+)</option>
                                    <option value="ajuste_negativo">Ajuste Negativo (-)</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="movQuantidade" class="form-label">Quantidade <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="movQuantidade" name="quantidade" required min="1" step="any">
                            </div>
                            <div class="col-md-8 mb-3">
                                <label for="movMotivo" class="form-label">Motivo / Justificativa</label>
                                <input type="text" class="form-control" id="movMotivo" name="motivo" maxlength="100">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="movObservacao" class="form-label">Observações Adicionais</label>
                            <textarea class="form-control" id="movObservacao" name="observacoes" rows="2" maxlength="255"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="movDocumento" class="form-label">Documento de Referência (Ex: NF, Pedido)</label>
                            <input type="text" class="form-control" id="movDocumento" name="documento_referencia" maxlength="50">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('movimentacaoForm').submit();">Registrar Movimentação</button>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="fab" title="Nova Movimentação" data-bs-toggle="modal" data-bs-target="#movimentacaoModal"><i class="fas fa-plus"></i></a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para exibir mensagens de sucesso/erro da sessão após o POST
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const successMessage = urlParams.get('success_message');
            const errorMessage = urlParams.get('error_message');

            const mainElement = document.querySelector('main');

            if (successMessage && mainElement) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show';
                alertDiv.setAttribute('role', 'alert');
                alertDiv.innerHTML = `
                    ${decodeURIComponent(successMessage)}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                mainElement.insertBefore(alertDiv, mainElement.firstChild);
                // Limpa o parâmetro da URL para não mostrar de novo no refresh
                window.history.replaceState({}, document.title, window.location.pathname + window.location.hash);
            }

            if (errorMessage && mainElement) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertDiv.setAttribute('role', 'alert');
                alertDiv.innerHTML = `
                    ${decodeURIComponent(errorMessage)}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                mainElement.insertBefore(alertDiv, mainElement.firstChild);
                window.history.replaceState({}, document.title, window.location.pathname + window.location.hash);
            }
        });
    </script>
</body>
</html>