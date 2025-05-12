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

// --- DATA LOGIC FOR REPORTS (Examples - Kept as is) ---
$stock_summary = ['total_produtos' => 0, 'total_itens' => 0, 'valor_total_custo' => 0, 'valor_total_venda' => 0, 'categorias_ativas' => 0];
if ($mysqli && !$mysqli->connect_errno) {
    $query_summary = "SELECT COUNT(DISTINCT p.id) as total_produtos, SUM(p.quantidade) as total_itens, SUM(p.quantidade * p.preco_custo) as valor_total_custo, SUM(p.quantidade * p.preco_unitario) as valor_total_venda, (SELECT COUNT(*) FROM categorias WHERE ativo = 1) as categorias_ativas FROM produtos p WHERE p.ativo = 1";
    if ($stmt_summary = $mysqli->prepare($query_summary)) { $stmt_summary->execute(); $result_summary = $stmt_summary->get_result(); if ($result_summary && $result_summary->num_rows > 0) { $data = $result_summary->fetch_assoc(); $stock_summary['total_produtos'] = $data['total_produtos']??0; $stock_summary['total_itens'] = $data['total_itens']??0; $stock_summary['valor_total_custo'] = $data['valor_total_custo']??0; $stock_summary['valor_total_venda'] = $data['valor_total_venda']??0; $stock_summary['categorias_ativas'] = $data['categorias_ativas']??0; } $stmt_summary->close(); }
} else {
    $stock_summary = ['total_produtos' => 150, 'total_itens' => 3500, 'valor_total_custo' => 75000.50, 'valor_total_venda' => 120000.75, 'categorias_ativas' => 12];
}
$userName = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Usuário';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - TaskFlow</title>
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
        .sidebar .menu li a { display: flex; align-items: center; padding: .9rem 1.75rem; color: var(--taskflow-light-lavender); text-decoration: none; transition: background-color .2s ease-in-out,color .2s ease-in-out,border-left-color .2s ease-in-out; font-size: .98rem; border-left: 4px solid transparent; font-weight: 500; }
        .sidebar .menu li a i { margin-right: 1rem; width: 22px; text-align: center; font-size: 1.15em }
        .sidebar .menu li a:hover { background-color: rgba(76,1,130,.35); color: var(--taskflow-white); border-left-color: var(--taskflow-light-lavender) }
        .sidebar .menu li.active a { background-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white); font-weight: 600; border-left-color: var(--taskflow-light-gray-beige) }
        
        /* Main Wrapper, Header, Main content, Report Card, Responsive (Same as previous relatorios.php) */
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
        .report-card { background-color: var(--taskflow-card-bg); border: 1px solid var(--taskflow-border-color); border-radius: .5rem; box-shadow: 0 .15rem .4rem rgba(26,0,65,.07); margin-bottom: 1.75rem }
        .report-card .card-header { padding: 1rem 1.5rem; margin-bottom: 0; background-color: #fdfcff; border-bottom: 1px solid var(--taskflow-border-color); border-top-left-radius: .5rem; border-top-right-radius: .5rem }
        .report-card .card-header h2 { font-size: 1.1rem; font-weight: 600; margin-bottom: 0; color: var(--taskflow-text-primary) }
        .report-card .card-header h2 i { color: var(--taskflow-vibrant-purple); margin-right: .5rem }
        .report-card .card-body { padding: 1.5rem }
        .report-card .card-body .form-label { font-weight: 500; color: var(--taskflow-text-secondary); margin-bottom: .3rem; font-size: .85rem }
        .report-card .card-body .form-control,.report-card .card-body .form-select { font-size: .9rem; padding: .45rem .9rem; border-color: var(--taskflow-border-color) }
        .report-card .card-body .form-control:focus,.report-card .card-body .form-select:focus { border-color: var(--taskflow-vibrant-purple); box-shadow: 0 0 0 .2rem rgba(76,1,130,.2) }
        .report-card .btn-primary { background-color: var(--taskflow-vibrant-purple); border-color: var(--taskflow-vibrant-purple) }
        .report-card .btn-primary:hover { background-color: var(--taskflow-deepest-purple); border-color: var(--taskflow-deepest-purple) }
        .report-card .btn-outline-secondary { border-color: var(--taskflow-muted-purple); color: var(--taskflow-muted-purple) }
        .report-card .btn-outline-secondary:hover { background-color: var(--taskflow-muted-purple); color: var(--taskflow-white) }
        .report-card .report-data-placeholder { text-align: center; padding: 2rem; border: 2px dashed var(--taskflow-border-color); border-radius: .375rem; color: var(--taskflow-text-secondary); font-style: italic }
        .report-summary-item { display: flex; justify-content: space-between; padding: .6rem 0; border-bottom: 1px solid var(--taskflow-border-color); font-size: .95rem }
        .report-summary-item:last-child { border-bottom: none }
        .report-summary-item .item-label { color: var(--taskflow-text-secondary) }
        .report-summary-item .item-value { font-weight: 600; color: var(--taskflow-text-primary) }
        @media (max-width:768px) { .sidebar { width: 0; padding-left: 0; padding-right: 0; overflow: hidden } .main-wrapper { margin-left: 0 } .header { padding: .75rem 1rem } .header .search-bar { display: none } .header .user-info .username { display: none } main { padding: 1rem } .page-title-area h1 { font-size: 1.5rem } .report-card .card-body .row>[class*=col-] { margin-bottom: .75rem } }

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
                    <li><a href="movimentacoes.php"><i class="fas fa-truck-ramp-box"></i> Movimentações</a></li>
                    <li class="active"><a href="relatorios.php"><i class="fas fa-file-invoice"></i> Relatórios</a></li>
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
                    <h1><i class="fas fa-file-invoice me-2"></i>Central de Relatórios</h1>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="report-card">
                            <div class="card-header"><h2><i class="fas fa-boxes"></i>Sumário Geral do Estoque</h2></div>
                            <div class="card-body">
                                <div class="report-summary-item"><span class="item-label">Total de Produtos Ativos:</span><span class="item-value"><?= number_format($stock_summary['total_produtos']) ?></span></div>
                                <div class="report-summary-item"><span class="item-label">Total de Itens em Estoque:</span><span class="item-value"><?= number_format($stock_summary['total_itens']) ?></span></div>
                                <div class="report-summary-item"><span class="item-label">Valor Total (Custo):</span><span class="item-value">R$ <?= number_format($stock_summary['valor_total_custo'], 2, ',', '.') ?></span></div>
                                <div class="report-summary-item"><span class="item-label">Valor Total (Venda):</span><span class="item-value">R$ <?= number_format($stock_summary['valor_total_venda'], 2, ',', '.') ?></span></div>
                                <div class="report-summary-item"><span class="item-label">Total de Categorias Ativas:</span><span class="item-value"><?= number_format($stock_summary['categorias_ativas']) ?></span></div>
                                <div class="mt-3 text-end"><a href="#" class="btn btn-sm btn-outline-primary disabled" aria-disabled="true" title="Função Detalhada em Desenvolvimento"><i class="fas fa-search-plus me-1"></i> Ver Detalhes</a></div>
                            </div>
                        </div>
                        <div class="report-card">
                            <div class="card-header"><h2><i class="fas fa-chart-line"></i>Relatório de Vendas por Período</h2></div>
                            <div class="card-body">
                                <form id="formVendasPeriodo" action="relatorios.php" method="POST">
                                    <div class="row g-3 align-items-end"><div class="col-md-5"><label for="vendasDataInicio" class="form-label">Data Início</label><input type="date" class="form-control form-control-sm" id="vendasDataInicio" name="vendas_data_inicio"></div><div class="col-md-5"><label for="vendasDataFim" class="form-label">Data Fim</label><input type="date" class="form-control form-control-sm" id="vendasDataFim" name="vendas_data_fim"></div><div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm w-100" name="gerar_relatorio_vendas">Gerar</button></div></div>
                                </form>
                                <div class="report-data-placeholder mt-3"><p><i class="fas fa-info-circle me-1"></i>Selecione o período e clique em "Gerar" para visualizar o relatório de vendas.</p></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="report-card">
                            <div class="card-header"><h2><i class="fas fa-sync-alt"></i>Giro de Estoque</h2></div>
                            <div class="card-body">
                                <form id="formGiroEstoque" action="relatorios.php" method="POST">
                                    <div class="row g-3 align-items-end"><div class="col-md-5"><label for="giroCategoria" class="form-label">Categoria (Opcional)</label><select class="form-select form-select-sm" id="giroCategoria" name="giro_categoria_id"><option value="">Todas</option></select></div><div class="col-md-5"><label for="giroPeriodo" class="form-label">Período (Ex: Últimos 30 dias)</label><select class="form-select form-select-sm" id="giroPeriodo" name="giro_periodo"><option value="30">Últimos 30 dias</option><option value="90">Últimos 90 dias</option><option value="180">Últimos 180 dias</option><option value="365">Último Ano</option></select></div><div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm w-100" name="gerar_relatorio_giro">Gerar</button></div></div>
                                </form>
                                <div class="report-data-placeholder mt-3"><p><i class="fas fa-info-circle me-1"></i>Selecione os filtros para calcular o giro de estoque.</p></div>
                            </div>
                        </div>
                        <div class="report-card">
                            <div class="card-header"><h2><i class="fas fa-calendar-times"></i>Produtos Próximos ao Vencimento</h2></div>
                            <div class="card-body">
                                <form id="formVencimento" action="relatorios.php" method="POST">
                                    <div class="row g-3 align-items-end"><div class="col-md-10"><label for="vencimentoDias" class="form-label">Vencendo nos próximos (dias)</label><input type="number" class="form-control form-control-sm" id="vencimentoDias" name="vencimento_dias" value="30" min="1"></div><div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm w-100" name="gerar_relatorio_vencimento">Gerar</button></div></div>
                                </form>
                                <div class="report-data-placeholder mt-3"><p><i class="fas fa-info-circle me-1"></i>Defina o período para listar produtos próximos ao vencimento (requer campo 'data_validade' nos produtos).</p></div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript (kept as is from previous relatorios.php)
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('main form');
            forms.forEach(form => {
                form.addEventListener('submit', function(event) { /* AJAX placeholder */ });
            });
        });
    </script>
</body>
</html>