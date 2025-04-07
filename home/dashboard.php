<?php
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

// Incluir partes (opcional, mas recomendado para organização)
// include 'includes/header.php'; // Incluiria o <head> e início do <body>
// include 'includes/sidebar.php'; // Incluiria a barra lateral <aside>
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gerenciamento de Estoque</title>
    <link rel="stylesheet" href="css/styles.css">
    <!-- Adicionar link para biblioteca de ícones (Ex: Font Awesome) -->
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> -->
    <!-- Adicionar link para biblioteca de gráficos (Ex: Chart.js) -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->
</head>
<body>
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
                    <li><a href="#"><span class="icon"> Vendas</span></a></li>
                    <li><a href="#"><span class="icon">🧾</span> Entradas</a></li>
                    <li><a href="#"><span class="icon">🛒</span> Saídas</a></li>
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
                <div class="search-bar">
                    <input type="text" placeholder="Buscar produto, SKU...">
                    <button type="submit">🔍</button> <!-- Ícone de busca -->
                </div>
                <div class="user-info">
                    <span class="notifications">🔔<span class="badge">3</span></span> <!-- Ícone de sino -->
                    <span class="username"><?php echo htmlspecialchars($nomeUsuario); ?></span>
                    <img src="placeholder-avatar.png" alt="Avatar" class="avatar"> <!-- Placeholder -->
                </div>
            </header>

            <!-- ===== Content Area ===== -->
            <main class="content">
                <h1>Visão Geral do Estoque</h1>

                <!-- KPIs -->
                <section class="kpi-grid">
                    <div class="kpi-card">
                        <div class="card-icon value-icon">💰</div>
                        <div class="card-content">
                            <span class="card-title">Valor Total do Estoque</span>
                            <span class="card-value">R$ <?php echo number_format($valorTotalEstoque, 2, ',', '.'); ?></span>
                        </div>
                    </div>
                    <div class="kpi-card alert">
                         <div class="card-icon alert-icon">⚠️</div>
                         <div class="card-content">
                            <span class="card-title">Itens Abaixo do Mínimo</span>
                            <span class="card-value"><?php echo $itensAbaixoMinimo; ?></span>
                        </div>
                    </div>
                    <div class="kpi-card">
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
                            <!-- Aqui entraria o <canvas> para Chart.js ou outra lib -->
                            <p style="text-align: center; padding: 20px; color: var(--text-secondary);">
                                [Gráfico de Pizza/Donut aqui - Implementar com JavaScript]
                            </p>
                            <ul>
                                <?php foreach ($distribuicaoEstoqueData as $categoria => $percent): ?>
                                <li><?php echo htmlspecialchars($categoria); ?>: <?php echo $percent; ?>%</li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
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
                                        <tr><td colspan="5">Nenhum item com estoque baixo.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Atividades Recentes -->
                    <div class="widget-card activity-card">
                        <h2>Atividades Recentes</h2>
                        <ul class="activity-list">
                             <?php foreach ($atividadesRecentes as $atividade): ?>
                            <li>
                                <span class="activity-icon <?php echo $atividade['tipo']; ?>">
                                    <?php
                                        // Simples ícone baseado no tipo
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
                        </ul>
                    </div>
                </section>

            </main>
        </div><!-- .main-wrapper -->
    </div><!-- .dashboard-container -->

    <!-- (Opcional) Seu script JS para gráficos ou interatividade -->
    <!-- <script src="js/script.js"></script> -->
</body>
</html>