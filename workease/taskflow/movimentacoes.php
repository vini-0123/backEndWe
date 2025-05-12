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
$filtro_produto_id = isset($_GET['produto_id']) ? filter_var($_GET['produto_id'], FILTER_SANITIZE_NUMBER_INT) : '';
$filtro_tipo = isset($_GET['tipo_movimentacao']) ? filter_var($_GET['tipo_movimentacao'], FILTER_SANITIZE_STRING) : '';
$filtro_data_inicio = isset($_GET['data_inicio']) ? filter_var($_GET['data_inicio'], FILTER_SANITIZE_STRING) : '';
$filtro_data_fim = isset($_GET['data_fim']) ? filter_var($_GET['data_fim'], FILTER_SANITIZE_STRING) : '';
$movimentacoes = []; $produtos_lista = [];
if ($mysqli && !$mysqli->connect_errno) {
    $stmt_prod_list = $mysqli->prepare("SELECT id, nome, sku FROM produtos WHERE ativo = 1 ORDER BY nome ASC");
    if ($stmt_prod_list) { $stmt_prod_list->execute(); $result_prod_list = $stmt_prod_list->get_result(); if ($result_prod_list) { $produtos_lista = $result_prod_list->fetch_all(MYSQLI_ASSOC); } $stmt_prod_list->close(); }
    $query = "SELECT m.id,m.tipo_movimentacao,m.quantidade,m.data_movimentacao,m.observacao,p.nome as produto_nome,p.sku as produto_sku FROM movimentacoes_estoque m JOIN produtos p ON m.produto_id = p.id WHERE 1=1";
    $params = []; $types = "";
    if (!empty($filtro_produto_id)) { $query .= " AND m.produto_id = ?"; $params[] = $filtro_produto_id; $types .= "i"; }
    if (!empty($filtro_tipo)) { $query .= " AND m.tipo_movimentacao = ?"; $params[] = $filtro_tipo; $types .= "s"; }
    if (!empty($filtro_data_inicio)) { $query .= " AND DATE(m.data_movimentacao) >= ?"; $params[] = $filtro_data_inicio; $types .= "s"; }
    if (!empty($filtro_data_fim)) { $query .= " AND DATE(m.data_movimentacao) <= ?"; $params[] = $filtro_data_fim; $types .= "s"; }
    $query .= " ORDER BY m.data_movimentacao DESC, m.id DESC LIMIT 50";
    $stmt_mov = $mysqli->prepare($query);
    if ($stmt_mov) { if (!empty($params)) { $stmt_mov->bind_param($types, ...$params); } $stmt_mov->execute(); $result_mov = $stmt_mov->get_result(); if ($result_mov) { $movimentacoes = $result_mov->fetch_all(MYSQLI_ASSOC); } $stmt_mov->close(); }
} else {
    $produtos_lista = [['id' => 1, 'nome' => 'Produto Exemplo A', 'sku' => 'SKU001'], ['id' => 2, 'nome' => 'Produto Exemplo B', 'sku' => 'SKU002']];
    $movimentacoes = [['id' => 1, 'produto_nome' => 'Produto Exemplo A', 'produto_sku' => 'SKU001', 'tipo_movimentacao' => 'entrada', 'quantidade' => 50, 'data_movimentacao' => '2023-10-20 10:00:00', 'observacao' => 'Recebimento do fornecedor X'], ['id' => 2, 'produto_nome' => 'Produto Exemplo B', 'produto_sku' => 'SKU002', 'tipo_movimentacao' => 'entrada', 'quantidade' => 100, 'data_movimentacao' => '2023-10-21 11:30:00', 'observacao' => 'Compra inicial'], ['id' => 3, 'produto_nome' => 'Produto Exemplo A', 'produto_sku' => 'SKU001', 'tipo_movimentacao' => 'saida', 'quantidade' => 10, 'data_movimentacao' => '2023-10-22 14:15:00', 'observacao' => 'Venda para cliente Y'], ['id' => 4, 'produto_nome' => 'Produto Exemplo C', 'produto_sku' => 'SKU003', 'tipo_movimentacao' => 'ajuste_positivo', 'quantidade' => 5, 'data_movimentacao' => '2023-10-23 09:00:00', 'observacao' => 'Contagem de inventário'], ['id' => 5, 'produto_nome' => 'Produto Exemplo B', 'produto_sku' => 'SKU002', 'tipo_movimentacao' => 'ajuste_negativo', 'quantidade' => 2, 'data_movimentacao' => '2023-10-24 16:45:00', 'observacao' => 'Produto avariado']];
}
$userName = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Usuário';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimentações de Estoque - TaskFlow</title>
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
            --taskflow-warning: #ffc107;
            --taskflow-info: #0dcaf0;
        }
        body { background-color: var(--taskflow-body-bg); font-family: 'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; color: var(--taskflow-text-primary); transition: margin-left .3s ease-in-out }
        .dashboard-container { display: flex; min-height: 100vh }
        .sidebar {
            width: 260px; background-color: var(--taskflow-deepest-purple); color: var(--taskflow-light-gray-beige);
            padding-top: 0; position: fixed; height: 100%; overflow-y: auto; 
            box-shadow: 3px 0 15px rgba(0,0,0,.15); z-index: 1030; 
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
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Menu Styles (Same as index.php) */
        .sidebar .menu ul { list-style: none; padding: 1.25rem 0; margin: 0 }
        .sidebar .menu li a { display: flex; align-items: center; padding: .9rem 1.75rem; color: var(--taskflow-light-lavender); text-decoration: none; transition: background-color .2s ease-in-out,color .2s ease-in-out,border-left-color .2s ease-in-out; font-size: .98rem; border-left: 4px solid transparent; font-weight:500; }
        .sidebar .menu li a i { margin-right: 1rem; width: 22px; text-align: center; font-size: 1.15em }
        .sidebar .menu li a:hover { background-color: rgba(76,1,130,.35); color: var(--taskflow-white); border-left-color: var(--taskflow-light-lavender) }
        .sidebar .menu li.active a { background-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white); font-weight: 600; border-left-color: var(--taskflow-light-gray-beige) }
        
        /* Main Wrapper, Header, Main content, Filters Card, Table Card, Table, Modal, FAB, Responsive (Same as previous movimentacoes.php) */
        .main-wrapper { flex-grow: 1; margin-left: 260px; display: flex; flex-direction: column; transition: margin-left .3s ease-in-out }
        .header { background-color: var(--taskflow-card-bg); padding: .9rem 1.75rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--taskflow-border-color); box-shadow: 0 2px 4px rgba(26,0,65,.05); position: sticky; top: 0; z-index: 1020 }
        .header .search-bar { visibility: hidden; }
        .header .user-info { margin-left: auto; }
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
        .table .badge-ajuste_negativo { background-color: rgba(255,193,7,.1); color: #b5830f; border: 1px solid rgba(255,193,7,.3) }
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
            <div class="logo-area">
                <i class="fas fa-cog logo-icon-gear"></i> 
                <span class="logo-text-brand">Taskflow</span>
            </div>
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
                    <a href="logout.php" class="btn-logout" title="Sair"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                </div>
            </header>

            <main>
                <div class="page-title-area">
                    <h1><i class="fas fa-truck-ramp-box me-2"></i>Movimentações de Estoque</h1>
                </div>

                <div class="filters-card">
                    <form method="GET" action="movimentacoes.php">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4"><label for="filtroProduto" class="form-label">Produto</label><select class="form-select form-select-sm" id="filtroProduto" name="produto_id"><option value="">Todos os Produtos</option><?php foreach ($produtos_lista as $prod): ?><option value="<?= $prod['id'] ?>" <?= ($filtro_produto_id == $prod['id'] ? 'selected' : '') ?>><?= htmlspecialchars($prod['nome']) ?> (<?= htmlspecialchars($prod['sku']) ?>)</option><?php endforeach; ?></select></div>
                            <div class="col-md-2"><label for="filtroTipoMov" class="form-label">Tipo</label><select class="form-select form-select-sm" id="filtroTipoMov" name="tipo_movimentacao"><option value="">Todos os Tipos</option><option value="entrada" <?= ($filtro_tipo == 'entrada' ? 'selected' : '') ?>>Entrada</option><option value="saida" <?= ($filtro_tipo == 'saida' ? 'selected' : '') ?>>Saída</option><option value="ajuste_positivo" <?= ($filtro_tipo == 'ajuste_positivo' ? 'selected' : '') ?>>Ajuste (+)</option><option value="ajuste_negativo" <?= ($filtro_tipo == 'ajuste_negativo' ? 'selected' : '') ?>>Ajuste (-)</option></select></div>
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
                                <thead><tr><th>Produto (SKU)</th><th class="text-center">Tipo</th><th class="text-center">Quantidade</th><th class="text-center">Data</th><th>Observação</th></tr></thead>
                                <tbody>
                                    <?php if (!empty($movimentacoes)): ?>
                                        <?php foreach ($movimentacoes as $mov): 
                                            $tipo_badge_class = ''; $tipo_texto = ucfirst(str_replace('_', ' ', $mov['tipo_movimentacao']));
                                            switch ($mov['tipo_movimentacao']) { case 'entrada': $tipo_badge_class = 'badge-entrada'; break; case 'saida': $tipo_badge_class = 'badge-saida'; break; case 'ajuste_positivo': $tipo_badge_class = 'badge-ajuste_positivo'; $tipo_texto = 'Ajuste (+)'; break; case 'ajuste_negativo': $tipo_badge_class = 'badge-ajuste_negativo'; $tipo_texto = 'Ajuste (-)'; break; }
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($mov['produto_nome']) ?><small class="d-block text-muted"><?= htmlspecialchars($mov['produto_sku']) ?></small></td>
                                            <td class="text-center"><span class="badge rounded-pill badge-movimentacao <?= $tipo_badge_class ?>"><?= $tipo_texto ?></span></td>
                                            <td class="text-center fw-bold"><?= number_format($mov['quantidade']) ?></td>
                                            <td class="text-center"><?= date('d/m/Y H:i', strtotime($mov['data_movimentacao'])) ?></td>
                                            <td class="observacao-column" title="<?= htmlspecialchars($mov['observacao'] ?? '') ?>"><?= htmlspecialchars($mov['observacao'] ?? 'N/A') ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="no-data-message">Nenhuma movimentação encontrada para os filtros aplicados.</td></tr>
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
                    <form id="movimentacaoForm">
                        <div class="row"><div class="col-md-8 mb-3"><label for="movProdutoId" class="form-label">Produto</label><select class="form-select" id="movProdutoId" name="produto_id" required><option value="" selected disabled>Selecione um produto...</option><?php foreach ($produtos_lista as $prod): ?><option value="<?= $prod['id'] ?>"><?= htmlspecialchars($prod['nome']) ?> (SKU: <?= htmlspecialchars($prod['sku']) ?>)</option><?php endforeach; ?></select></div><div class="col-md-4 mb-3"><label for="movTipo" class="form-label">Tipo de Movimentação</label><select class="form-select" id="movTipo" name="tipo_movimentacao" required><option value="" selected disabled>Selecione o tipo...</option><option value="entrada">Entrada</option><option value="saida">Saída</option><option value="ajuste_positivo">Ajuste Positivo (+)</option><option value="ajuste_negativo">Ajuste Negativo (-)</option></select></div></div>
                        <div class="row"><div class="col-md-4 mb-3"><label for="movQuantidade" class="form-label">Quantidade</label><input type="number" class="form-control" id="movQuantidade" name="quantidade" required min="1"></div><div class="col-md-8 mb-3"><label for="movObservacao" class="form-label">Observação (Opcional)</label><input type="text" class="form-control" id="movObservacao" name="observacao" maxlength="255"></div></div>
                    </form>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" onclick="saveMovimentacao()">Registrar Movimentação</button></div>
            </div>
        </div>
    </div>

    <a href="#" class="fab" title="Nova Movimentação" data-bs-toggle="modal" data-bs-target="#movimentacaoModal"><i class="fas fa-plus"></i></a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript (kept as is from previous movimentacoes.php)
        const movimentacaoModal = new bootstrap.Modal(document.getElementById('movimentacaoModal'));
        function saveMovimentacao() { const produtoId = document.getElementById('movProdutoId').value; const tipo = document.getElementById('movTipo').value; const quantidade = document.getElementById('movQuantidade').value; const observacao = document.getElementById('movObservacao').value; if (!produtoId || !tipo || !quantidade || parseFloat(quantidade) <= 0) { alert('Por favor, preencha todos os campos obrigatórios (Produto, Tipo, Quantidade > 0).'); return; } console.log('Salvando Movimentação:', { produtoId, tipo, quantidade, observacao }); alert(`Movimentação registrada! (Simulação)`); movimentacaoModal.hide(); document.getElementById('movimentacaoForm').reset(); }
    </script>
</body>
</html>