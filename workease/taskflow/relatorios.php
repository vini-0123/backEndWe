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
    // Query para sumário do estoque
    $query_summary = "SELECT 
        COUNT(DISTINCT p.id) as total_produtos,
        COALESCE(SUM(p.quantidade_estoque), 0) as total_itens,  -- Corrigido para usar quantidade_estoque
        COALESCE(SUM(p.quantidade_estoque * COALESCE(p.preco_custo, 0)), 0) as valor_custo_total, -- Corrigido
        COALESCE(SUM(p.quantidade_estoque * COALESCE(p.preco_unitario, 0)), 0) as valor_venda_total, -- Corrigido
        (SELECT COUNT(DISTINCT c.id) FROM categorias c WHERE c.ativo = 1) as categorias_ativas -- Subquery para categorias ativas
        FROM produtos p 
        WHERE p.ativo = 1";
    if ($stmt_summary = $mysqli->prepare($query_summary)) { 
        $stmt_summary->execute(); 
        $result_summary = $stmt_summary->get_result(); 
        if ($result_summary && $result_summary->num_rows > 0) { 
            $data = $result_summary->fetch_assoc(); 
            $stock_summary['total_produtos'] = $data['total_produtos'] ?? 0; 
            $stock_summary['total_itens'] = $data['total_itens'] ?? 0; 
            $stock_summary['valor_total_custo'] = $data['valor_custo_total'] ?? 0; 
            $stock_summary['valor_total_venda'] = $data['valor_venda_total'] ?? 0; 
            $stock_summary['categorias_ativas'] = $data['categorias_ativas'] ?? 0; 
        } 
        $stmt_summary->close(); 
    } else {
        error_log("Erro ao preparar query de sumário de estoque: " . $mysqli->error);
    }
} else {
    $stock_summary = ['total_produtos' => 150, 'total_itens' => 3500, 'valor_total_custo' => 75000.50, 'valor_total_venda' => 120000.75, 'categorias_ativas' => 12];
    if ($mysqli && $mysqli->connect_errno) {
        error_log("Falha na conexão com o banco de dados (Relatórios). Usando dados simulados. Erro: " . $mysqli->connect_error);
    } elseif (!$mysqli) {
        error_log("Variável \$mysqli não definida (Relatórios). Usando dados simulados.");
    }
}
$userName = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Usuário';
$softwareName = "Taskflow"; // ** ADICIONADO PARA CONSISTÊNCIA **
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - <?= htmlspecialchars($softwareName) // ** USANDO $softwareName ** ?></title>
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
        .sidebar .menu li a { display: flex; align-items: center; padding: .9rem 1.75rem; color: var(--taskflow-light-lavender); text-decoration: none; transition: background-color .2s ease-in-out,color .2s ease-in-out,border-left-color .2s ease-in-out; font-size: .98rem; border-left: 4px solid transparent; font-weight: 500; }
        .sidebar .menu li a i { margin-right: 1rem; width: 22px; text-align: center; font-size: 1.15em }
        .sidebar .menu li a:hover { background-color: rgba(76,1,130,.35); color: var(--taskflow-white); border-left-color: var(--taskflow-light-lavender) }
        .sidebar .menu li.active a { background-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white); font-weight: 600; border-left-color: var(--taskflow-light-gray-beige) }
        
        /* Main Wrapper, Header, Main content, Report Card, Responsive (Same as previous relatorios.php) */
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
                    <a href="../logout.php" class="btn-logout" title="Sair"><i class="fas fa-sign-out-alt fa-lg"></i></a> <!-- ** AJUSTADO CAMINHO DO LOGOUT ** -->
                </div>
            </header>

            <main>
                <div class="page-title-area">
                    <h1><i class="fas fa-file-invoice me-2"></i>Central de Relatórios</h1>
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
                                <form id="formVendasPeriodo" action="gerar_relatorio_vendas.php" method="POST" target="_blank"> <!-- Ajustado action e target -->
                                    <div class="row g-3 align-items-end"><div class="col-md-5"><label for="vendasDataInicio" class="form-label">Data Início</label><input type="date" class="form-control form-control-sm" id="vendasDataInicio" name="vendas_data_inicio" required></div><div class="col-md-5"><label for="vendasDataFim" class="form-label">Data Fim</label><input type="date" class="form-control form-control-sm" id="vendasDataFim" name="vendas_data_fim" required></div><div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm w-100" name="gerar_relatorio_vendas">Gerar</button></div></div>
                                </form>
                                <div class="report-data-placeholder mt-3"><p><i class="fas fa-info-circle me-1"></i>Selecione o período e clique em "Gerar" para visualizar o relatório de vendas.</p></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="report-card">
                            <div class="card-header"><h2><i class="fas fa-sync-alt"></i>Giro de Estoque</h2></div>
                            <div class="card-body">
                                <form id="formGiroEstoque" action="gerar_relatorio_giro.php" method="POST" target="_blank"> <!-- Ajustado action e target -->
                                    <div class="row g-3 align-items-end"><div class="col-md-5"><label for="giroCategoria" class="form-label">Categoria (Opcional)</label><select class="form-select form-select-sm" id="giroCategoria" name="giro_categoria_id"><option value="">Todas</option><!-- Popular com categorias do BD --></select></div><div class="col-md-5"><label for="giroPeriodo" class="form-label">Período</label><select class="form-select form-select-sm" id="giroPeriodo" name="giro_periodo" required><option value="30">Últimos 30 dias</option><option value="90">Últimos 90 dias</option><option value="180">Últimos 180 dias</option><option value="365">Último Ano</option></select></div><div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm w-100" name="gerar_relatorio_giro">Gerar</button></div></div>
                                </form>
                                <div class="report-data-placeholder mt-3"><p><i class="fas fa-info-circle me-1"></i>Selecione os filtros para calcular o giro de estoque.</p></div>
                            </div>
                        </div>
                        <div class="report-card">
                            <div class="card-header"><h2><i class="fas fa-calendar-times"></i>Produtos Próximos ao Vencimento</h2></div>
                            <div class="card-body">
                                <form id="formVencimento" action="gerar_relatorio_vencimento.php" method="POST" target="_blank"> <!-- Ajustado action e target -->
                                    <div class="row g-3 align-items-end"><div class="col-md-10"><label for="vencimentoDias" class="form-label">Vencendo nos próximos (dias)</label><input type="number" class="form-control form-control-sm" id="vencimentoDias" name="vencimento_dias" value="30" min="1" required></div><div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm w-100" name="gerar_relatorio_vencimento">Gerar</button></div></div>
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
            // Exemplo: Popular select de categorias para o relatório de Giro de Estoque
            // Você precisaria buscar as categorias via PHP e passar para o JS, ou fazer um fetch aqui.
            const selectGiroCategoria = document.getElementById('giroCategoria');
            if (selectGiroCategoria) {
                // Exemplo de como adicionar opções dinamicamente (substituir com dados reais)
                // const categorias = [{id:1, nome:'Eletrônicos'}, {id:2, nome:'Livros'}];
                // categorias.forEach(cat => {
                //     const option = document.createElement('option');
                //     option.value = cat.id;
                //     option.textContent = cat.nome;
                //     selectGiroCategoria.appendChild(option);
                // });
            }

            const forms = document.querySelectorAll('main form');
            forms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    // Poderia adicionar validações JS aqui antes de submeter
                    // Se o form tem target="_blank", a submissão abrirá em nova aba.
                    // Não precisa de preventDefault() a menos que queira tratar via AJAX.
                });
            });
        });
    </script>
</body>
</html>