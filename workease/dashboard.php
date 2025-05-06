<?php
include_once './factory/conexao.php';
include('verificarLogin.php');

$query = "SELECT * FROM data_clients";
$result = $mysqli->query($query);
$mysqli->close();

// --- Simulação de Dados (Substitua pela sua lógica de banco de dados) ---
$nomeUsuario = "Admin";
$valorTotalEstoque = 150345.50;
$itensAbaixoMinimo = 15;
$produtosAtivos = 87;
$vendasHoje = 25;

$itensEstoqueBaixo = [
    ['sku' => 'SKU001', 'nome' => 'Produto Exemplo A', 'atual' => 8, 'minimo' => 10],
    ['sku' => 'SKU015', 'nome' => 'Componente XPTO B', 'atual' => 3, 'minimo' => 5],
    ['sku' => 'SKU042', 'nome' => 'Item Categoria X', 'atual' => 19, 'minimo' => 20],
    // ... mais itens
];

$atividadesRecentes = [
    ['tipo' => 'entrada', 'desc' => 'Recebimento de 50x SKU001', 'tempo' => '2 horas atrás'],
    ['tipo' => 'saida', 'desc' => 'Venda de 5x SKU015 (Pedido #123)', 'tempo' => '3 horas atrás'],
    ['tipo' => 'ajuste', 'desc' => 'Ajuste de estoque para SKU042 (+2)', 'tempo' => 'Ontem'],
    // ... mais atividades
];

// Dados simulados para gráfico (para demonstração - idealmente usar JS lib)
$distribuicaoEstoqueData = [
    'Eletrônicos' => 40,
    'Vestuário' => 25,
    'Alimentos' => 15,
    'Outros' => 20,
];

// --- Fim da Simulação de Dados ---
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gerenciamento de Estoque</title>
    <!-- Adicionar link para biblioteca de ícones (Ex: Font Awesome) -->
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> -->
    <!-- Adicionar link para biblioteca de gráficos (Ex: Chart.js) -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->

    <style>
        /* Importação de Fonte (Exemplo: Inter do Google Fonts) */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        /* ----- Variáveis de Cor ----- */
        :root {
            --navy-dark: #0A192F;      /* Azul Marinho Principal (Fundo Sidebar, Texto Escuro) */
            --navy-medium: #1E3A5F;   /* Azul Marinho Médio (Hover, Fundos secundários) */
            --cyan-highlight: #64FFDA;  /* Ciano Destaque (CTAs, Gráficos, Números) */
            --cyan-text-on-dark: #64FFDA; /* Ciano para texto sobre fundo escuro */
            --cyan-hover: #52d9bc;      /* Ciano para hover */
            --bg-main: #F0F4F8;         /* Fundo da Área de Conteúdo (Cinza muito claro) */
            --bg-card: #FFFFFF;         /* Fundo dos Cards */
            --text-primary: #0A192F;     /* Texto principal sobre fundo claro */
            --text-secondary: #8892B0;  /* Texto secundário, legendas */
            --text-on-dark: #CCD6F6;    /* Texto principal sobre fundo escuro (Navy) */
            --text-on-cyan: #0A192F;    /* Texto sobre o botão Ciano */
            --border-color: #E2E8F0;     /* Cor de bordas sutis */
            --shadow-color: rgba(10, 25, 47, 0.1); /* Sombra sutil */
            --alert-color: #ffcc00; /* Amarelo para alertas */ /* Mudança: Usando amarelo para destaque de alerta */
        }

        /* ----- Reset Básico e Padrões ----- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-primary);
            line-height: 1.6;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        ul {
            list-style: none;
        }

        /* ----- Layout Principal ----- */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: var(--navy-dark);
            color: var(--text-on-dark);
            padding: 20px 15px;
            display: flex;
            flex-direction: column;
            transition: width 0.3s ease; /* Para possível recolhimento */
        }

        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-x: hidden; /* Prevenir scroll horizontal */
        }

        .header {
            background-color: var(--bg-card);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 2px 4px var(--shadow-color);
            position: sticky; /* Mantém header fixo no topo */
            top: 0;
            z-index: 100;
        }

        .content {
            padding: 30px;
            flex: 1;
            overflow-y: auto; /* Para permitir rolagem se o conteúdo for grande */
        }

        /* ----- Sidebar ----- */
        .logo {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding: 10px 0;
        }

        .logo-icon {
            font-size: 2rem;
            margin-right: 10px;
            color: var(--cyan-highlight);
        }

        .logo-text {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--cyan-highlight);
        }

        .menu ul li {
            margin-bottom: 5px;
        }

        .menu ul li a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: 6px;
            color: var(--text-on-dark);
            font-weight: 500;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .menu ul li a .icon {
             margin-right: 15px;
             font-size: 1.2rem; /* Ajuste o tamanho se usar ícones de fonte */
             width: 20px; /* Garante alinhamento */
             text-align: center;
             color: var(--text-secondary); /* Cor padrão do ícone */
             transition: color 0.2s ease;
        }


        .menu ul li a:hover {
            background-color: var(--navy-medium);
            color: #FFF; /* Texto mais brilhante no hover */
        }
        .menu ul li a:hover .icon {
            color: var(--cyan-highlight); /* Cor do ícone no hover */
        }


        .menu ul li.active a {
            background-color: var(--navy-medium);
            color: var(--cyan-highlight);
            font-weight: 600;
            position: relative; /* Para a barra lateral */
        }
        /* Adiciona uma barra ciano à esquerda do item ativo */
        .menu ul li.active a::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background-color: var(--cyan-highlight);
            border-radius: 0 4px 4px 0;
        }

        .menu ul li.active a .icon {
            color: var(--cyan-highlight); /* Ícone ativo também em ciano */
        }


        /* ----- Header ----- */
        .search-bar {
            display: flex;
            align-items: center;
        }

        .search-bar input[type="text"] {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px 0 0 4px;
            min-width: 250px;
            font-size: 0.9rem;
        }
         .search-bar input[type="text"]:focus {
            outline: none;
            border-color: var(--cyan-highlight);
            box-shadow: 0 0 0 2px rgba(100, 255, 218, 0.3);
         }

        .search-bar button {
            padding: 8px 10px;
            border: 1px solid var(--border-color);
            border-left: none;
            background-color: var(--bg-main);
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            color: var(--text-secondary);
             transition: background-color 0.2s ease, color 0.2s ease;
        }
        .search-bar button:hover {
            background-color: var(--navy-medium);
            color: var(--text-on-dark);
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .notifications {
            position: relative;
            margin-right: 20px;
            cursor: pointer;
            font-size: 1.3rem;
            color: var(--text-secondary);
            transition: color 0.2s ease;
        }
         .notifications:hover {
             color: var(--navy-dark);
         }

        .notifications .badge {
            position: absolute;
            top: -5px;
            right: -8px;
            background-color: #E53E3E; /* Vermelho para notificações */
            color: white;
            border-radius: 50%;
            padding: 2px 5px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .username {
            margin-right: 10px;
            font-weight: 500;
        }

        .avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--cyan-highlight);
        }

        /* ----- Content Area ----- */
        .content h1 {
            font-size: 1.8rem;
            margin-bottom: 25px;
            color: var(--navy-dark);
            font-weight: 600;
        }

        /* ----- KPI Cards ----- */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .kpi-card {
            background-color: var(--bg-card);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px var(--shadow-color);
            display: flex;
            align-items: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-left: 4px solid transparent; /* Borda inicial transparente */
        }

        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(10, 25, 47, 0.15);
        }

        /* Borda lateral colorida para KPIs */
        .kpi-card.value { border-left-color: var(--cyan-highlight); }
        .kpi-card.alert { border-left-color: var(--alert-color); }
        .kpi-card:not(.value):not(.alert) { border-left-color: var(--navy-medium); }


        .kpi-card .card-icon {
            font-size: 1.5rem; /* Ícone um pouco menor */
            margin-right: 15px;
            padding: 10px;
            border-radius: 8px; /* Quadrado arredondado */
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 45px; /* Tamanho fixo */
            height: 45px;
        }

        .kpi-card.value .card-icon { background-color: rgba(100, 255, 218, 0.1); color: var(--cyan-highlight); }
        .kpi-card.alert .card-icon { background-color: rgba(255, 204, 0, 0.1); color: var(--alert-color); }
        .kpi-card:not(.value):not(.alert) .card-icon { background-color: rgba(30, 58, 95, 0.1); color: var(--navy-medium); }


        .card-content {
            display: flex;
            flex-direction: column;
        }

        .card-title {
            font-size: 0.85rem; /* Título um pouco menor */
            color: var(--text-secondary);
            margin-bottom: 5px;
            font-weight: 500;
        }

        .card-value {
            font-size: 1.5rem; /* Valor um pouco menor */
            font-weight: 700; /* Mais destaque */
            color: var(--navy-dark);
            line-height: 1.2;
        }
        .kpi-card.alert .card-value {
            color: var(--alert-color); /* Destaque para valor de alerta */
        }


        /* ----- Widgets Grid ----- */
        .widgets-grid {
            display: grid;
            /* grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); */
             grid-template-columns: repeat(3, 1fr); /* Layout fixo de 3 colunas */
            gap: 25px;
            margin-bottom: 30px;
        }
         /* Fazer alguns widgets ocuparem mais espaço */
        .widgets-grid > .widget-card:nth-child(1) { grid-column: span 2; } /* Gráfico */
        .widgets-grid > .widget-card:nth-child(2) { grid-column: span 1; } /* Atividades */
        .widgets-grid > .widget-card:nth-child(3) { grid-column: span 3; } /* Tabela */


        .widget-card {
            background-color: var(--bg-card);
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px var(--shadow-color);
            display: flex; /* Para controle de altura */
            flex-direction: column;
        }

        .widget-card h2 {
            font-size: 1.1rem; /* Título do widget um pouco menor */
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--navy-dark);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
            display: flex;
            align-items: center;
             justify-content: space-between; /* Para possíveis ações no título */
        }
         .widget-card h2 i { /* Ícone opcional no título */
             font-size: 1rem;
             color: var(--text-secondary);
         }

        /* Tabela */
        .table-responsive {
            overflow-x: auto; /* Para tabelas largas em telas menores */
            flex-grow: 1; /* Ocupa espaço disponível no card */
        }

        .widget-card table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .widget-card th,
        .widget-card td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap; /* Evita quebra de linha em células */
        }

        .widget-card thead th {
            background-color: var(--navy-dark); /* Fundo do cabeçalho da tabela */
            color: var(--text-on-dark);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem; /* Mais sutil */
            letter-spacing: 0.5px;
            position: sticky; /* Cabeçalho fixo dentro do scroll */
            top: 0;
            z-index: 1;
        }

        .widget-card tbody tr:hover {
            background-color: var(--bg-main);
        }
         .widget-card tbody tr:last-child td {
             border-bottom: none; /* Remove borda da última linha */
         }


        .widget-card tbody td.low-stock-value {
            color: var(--alert-color); /* Destaque para quantidade baixa */
            font-weight: 700;
        }

        .btn-action {
            background-color: var(--cyan-highlight);
            color: var(--text-on-cyan);
            border: none;
            padding: 5px 10px; /* Botão um pouco menor */
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600; /* Mais forte */
            font-size: 0.8rem;
            transition: background-color 0.2s ease, transform 0.1s ease;
            display: inline-flex;
            align-items: center;
        }
        .btn-action i { margin-right: 5px; } /* Ícone opcional no botão */

        .btn-action:hover {
            background-color: var(--cyan-hover);
            transform: scale(1.05);
        }
         .btn-action:active {
             transform: scale(0.98);
         }

        /* Lista de Atividades */
        .activity-card {
            max-height: 400px; /* Altura máxima para o card de atividades */
        }

        .activity-list {
             overflow-y: auto; /* Scroll se necessário */
             flex-grow: 1; /* Ocupa espaço vertical */
             margin-right: -15px; /* Compensar padding do scrollbar */
             padding-right: 15px;
        }

        .activity-list li {
            display: flex;
            align-items: flex-start; /* Alinha pelo topo */
            padding: 12px 0;
            border-bottom: 1px dashed var(--border-color);
        }
        .activity-list li:last-child {
            border-bottom: none;
        }

        .activity-icon {
            font-size: 1rem; /* Ícone um pouco menor */
            margin-right: 15px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px; /* Ajuste vertical fino */
        }
        .activity-icon.entrada { background-color: rgba(100, 255, 218, 0.2); color: var(--cyan-highlight); }
        .activity-icon.saida { background-color: rgba(255, 100, 100, 0.1); color: #ff6464; } /* Exemplo de cor para saída */
        .activity-icon.ajuste { background-color: rgba(30, 58, 95, 0.1); color: var(--navy-medium); }

        .activity-details {
            display: flex;
            flex-direction: column;
        }

        .activity-desc {
            font-size: 0.9rem;
            font-weight: 500;
            line-height: 1.4;
        }

        .activity-time {
            font-size: 0.75rem; /* Menor */
            color: var(--text-secondary);
            margin-top: 3px;
        }

        /* Placeholder do Gráfico */
        .chart-placeholder {
            min-height: 250px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-grow: 1;
            color: var(--text-secondary);
             border: 1px dashed var(--border-color);
             border-radius: 6px;
             background-color: var(--bg-main);
             padding: 20px;
        }
        .chart-placeholder ul {
            margin-top: 15px;
            font-size: 0.85rem;
        }
         .chart-placeholder li {
             margin-bottom: 5px;
         }

        /* ----- Responsividade ----- */
        @media (max-width: 1200px) {
            .widgets-grid {
                grid-template-columns: repeat(2, 1fr); /* 2 colunas */
             }
             .widgets-grid > .widget-card:nth-child(1) { grid-column: span 1; } /* Gráfico */
             .widgets-grid > .widget-card:nth-child(2) { grid-column: span 1; } /* Atividades */
             .widgets-grid > .widget-card:nth-child(3) { grid-column: span 2; } /* Tabela */
        }

        @media (max-width: 992px) {
             .widgets-grid {
                 grid-template-columns: 1fr; /* 1 coluna */
             }
              .widgets-grid > .widget-card:nth-child(n) { grid-column: span 1 !important; } /* Todos ocupam 1 coluna */
        }


        @media (max-width: 768px) {
            .sidebar {
                position: fixed; /* Ou absolute, dependendo do efeito desejado */
                left: -250px; /* Começa escondida */
                height: 100%;
                z-index: 200;
                transition: left 0.3s ease;
                /* Adicionar um botão para abrir/fechar */
            }
             .sidebar.open {
                 left: 0;
             }

            .main-wrapper {
                margin-left: 0; /* Ocupa toda a largura quando a sidebar está fechada */
            }
             /* Ajustar header e content quando sidebar abrir se necessário */

            .header {
                padding: 10px 15px;
            }
             .search-bar input[type="text"] { min-width: 120px; }
             .username { display: none; } /* Esconder nome em telas pequenas */
             .logo-text { display: none; } /* Opcional: Esconder texto do logo no header se ele estiver lá */

            .content {
                padding: 15px;
            }
            .kpi-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); /* Ajuste para KPIs menores */
                 gap: 15px;
            }
            .kpi-card { padding: 15px; }
            .card-value { font-size: 1.3rem; }
            .card-title { font-size: 0.8rem; }
        }

         /* Scrollbar customizado (Opcional, funciona em Webkit) */
         ::-webkit-scrollbar { width: 6px; height: 6px; }
         ::-webkit-scrollbar-track { background: var(--bg-main); }
         ::-webkit-scrollbar-thumb { background: var(--text-secondary); border-radius: 3px; }
         ::-webkit-scrollbar-thumb:hover { background: var(--navy-medium); }

    </style>
</head>
<body>
    <h2>Seja bem-vindo(a), <?php  echo $_SESSION ['user_id'] ?></h2>
    <a href="logout.php">Sair</a>
    <div class="dashboard-container">
        <!-- ===== Sidebar ===== -->
        <aside class="sidebar">
            <div class="logo">
                <span class="logo-icon">📦</span> <!-- Substituir por um ícone real -->
                <span class="logo-text">EstoquePrime</span>
            </div>
            <nav class="menu">
                <ul>
                    <li class="active"><a href="#"><span class="icon">📊</span> Dashboard</a></li>
                    <li><a href="#"><span class="icon">🏷️</span> Produtos</a></li>
                    <li><a href="#"><span class="icon">📦</span> Categorias</a></li>
                    <li><a href="#"><span class="icon">➕</span> Entradas</a></li>
                    <li><a href="#"><span class="icon">➖</span> Saídas</a></li>
                    <li><a href="#"><span class="icon">👥</span> Fornecedores</a></li>
                    <li><a href="#"><span class="icon">📈</span> Relatórios</a></li>
                    <li><a href="#"><span class="icon">⚙️</span> Configurações</a></li>
                </ul>
            </nav>
        </aside>

        <!-- ===== Main Content ===== -->
        <div class="main-wrapper">
            <!-- ===== Header ===== -->
            <header class="header">
                <!-- Adicionar botão Hambúrguer para mobile -->
                <!-- <button class="mobile-menu-toggle">☰</button> -->
                <div class="search-bar">
                    <input type="text" placeholder="Buscar produto, SKU...">
                    <button type="submit">🔍</button> <!-- Ícone de busca -->
                </div>
                <div class="user-info">
                    <span class="notifications" title="Notificações">🔔<span class="badge">3</span></span> <!-- Ícone de sino -->
                    <span class="username"><?php echo htmlspecialchars($nomeUsuario); ?></span>
                    <img src="placeholder-avatar.png" alt="Avatar" class="avatar" title="<?php echo htmlspecialchars($nomeUsuario); ?>"> <!-- Placeholder -->
                </div>
            </header>

            <!-- ===== Content Area ===== -->
            <main class="content">
                <h1>Visão Geral do Estoque</h1>

                <!-- KPIs -->
                <section class="kpi-grid">
                    <div class="kpi-card value"> <!-- Classe 'value' para borda ciano -->
                        <div class="card-icon">💰</div>
                        <div class="card-content">
                            <span class="card-title">Valor Total do Estoque</span>
                            <span class="card-value">R$ <?php echo number_format($valorTotalEstoque, 2, ',', '.'); ?></span>
                        </div>
                    </div>
                    <div class="kpi-card alert"> <!-- Classe 'alert' para borda amarela -->
                         <div class="card-icon">⚠️</div>
                         <div class="card-content">
                            <span class="card-title">Itens Abaixo do Mínimo</span>
                            <span class="card-value"><?php echo $itensAbaixoMinimo; ?></span>
                        </div>
                    </div>
                    <div class="kpi-card"> <!-- Sem classe específica, borda azul médio padrão -->
                         <div class="card-icon">📦</div>
                         <div class="card-content">
                            <span class="card-title">Produtos Ativos</span>
                            <span class="card-value"><?php echo $produtosAtivos; ?></span>
                        </div>
                    </div>
                     <div class="kpi-card">
                         <div class="card-icon">📈</div>
                        <div class="card-content">
                            <span class="card-title">Vendas (Hoje)</span>
                            <span class="card-value"><?php echo $vendasHoje; ?></span>
                        </div>
                    </div>
                </section>

                <!-- Widgets Grid (Gráficos, Tabelas, etc.) -->
                <section class="widgets-grid">
                    <!-- Gráfico de Distribuição (Placeholder - Ideal com JS) -->
                    <div class="widget-card chart-card">
                        <h2>Distribuição por Categoria</h2>
                        <div class="chart-placeholder">
                            <p>📊</p>
                            <p style="text-align: center; padding: 10px; color: var(--text-secondary); font-size:0.9rem;">
                                [Gráfico de Pizza/Donut aqui - Implementar com JavaScript]
                            </p>
                            <!-- Exemplo de legenda básica -->
                           <!-- <ul>
                                <?php //foreach ($distribuicaoEstoqueData as $categoria => $percent): ?>
                                <li><?php //echo htmlspecialchars($categoria); ?>: <?php //echo $percent; ?>%</li>
                                <?php //endforeach; ?>
                            </ul> -->
                        </div>
                    </div>

                     <!-- Atividades Recentes -->
                    <div class="widget-card activity-card">
                        <h2>Atividades Recentes</h2>
                        <ul class="activity-list">
                             <?php foreach ($atividadesRecentes as $atividade): ?>
                            <li>
                                <span class="activity-icon <?php echo $atividade['tipo']; ?>" title="<?php echo ucfirst($atividade['tipo']); ?>">
                                    <?php
                                        // Ícone baseado no tipo
                                        if ($atividade['tipo'] == 'entrada') echo '➕';
                                        else if ($atividade['tipo'] == 'saida') echo '➖';
                                        else echo '⚙️';
                                    ?>
                                </span>
                                <div class="activity-details">
                                    <span class="activity-desc"><?php echo htmlspecialchars($atividade['desc']); ?></span>
                                    <span class="activity-time"><?php echo htmlspecialchars($atividade['tempo']); ?></span>
                                </div>
                            </li>
                             <?php endforeach; ?>
                              <?php if (empty($atividadesRecentes)): ?>
                                <li><span class="activity-desc">Nenhuma atividade recente.</span></li>
                              <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Tabela de Estoque Baixo -->
                    <div class="widget-card table-card">
                        <h2>Itens com Estoque Baixo</h2>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Produto</th>
                                        <th>Qtd. Atual</th>
                                        <th>Qtd. Mínima</th>
                                        <th>Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($itensEstoqueBaixo as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                        <td><?php echo htmlspecialchars($item['nome']); ?></td>
                                        <td class="low-stock-value"><?php echo $item['atual']; ?></td>
                                        <td><?php echo $item['minimo']; ?></td>
                                        <td><button class="btn-action">Pedir</button></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($itensEstoqueBaixo)): ?>
                                        <tr><td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 20px;">Nenhum item com estoque baixo.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </section>

            </main>
        </div><!-- .main-wrapper -->
    </div><!-- .dashboard-container -->

    <!-- (Opcional) Seu script JS para gráficos ou interatividade -->
    <!-- <script src="js/script.js"></script> -->
    <script>
        // Script básico para toggle da sidebar em mobile (exemplo)
        // const menuToggle = document.querySelector('.mobile-menu-toggle'); // Precisa criar este botão no HTML
        // const sidebar = document.querySelector('.sidebar');
        // if(menuToggle && sidebar) {
        //     menuToggle.addEventListener('click', () => {
        //         sidebar.classList.toggle('open');
        //     });
        // }
    </script>
</body>
</html>