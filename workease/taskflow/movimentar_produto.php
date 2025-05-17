<?php
session_start();

// Simulate user session if not set for testing
if (!isset($_SESSION['user_name'])) {
    $_SESSION['user_name'] = 'Usuário Demonstração';
}
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['logged_in'] = true;
    // For a real app, you'd have $_SESSION['user_id'] set upon login
    // $_SESSION['user_id'] = 'demo-user-id'; // Uncomment and set if you use it
}

$softwareName = "Taskflow";
$userName = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Usuário';
// $userID = $_SESSION['user_id'] ?? null; // Uncomment if you use it in the INSERT query

$produto_id = isset($_GET['id']) ? trim($_GET['id']) : null;
$produto = null;
$form_error = null;
$form_success = null;

// --- Database Connection ---
$conexao_path = dirname(dirname(__FILE__)) . '/factory/conexao.php';
$mysqli = null;

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

    // Fetch product details if ID is provided
    if ($produto_id) {
        $stmt_prod = $mysqli->prepare("SELECT id, sku, nome, quantidade_estoque, preco_unitario FROM produtos WHERE id = ? AND ativo = 1");
        if ($stmt_prod) {
            $stmt_prod->bind_param("s", $produto_id);
            $stmt_prod->execute();
            $result_prod = $stmt_prod->get_result();
            if ($result_prod->num_rows === 1) {
                $produto = $result_prod->fetch_assoc();
            } else {
                $_SESSION['form_error'] = "Produto não encontrado ou inativo.";
                header("Location: produtos.php");
                exit;
            }
            $stmt_prod->close();
        } else {
            throw new Exception("Erro ao preparar consulta do produto: " . $mysqli->error);
        }
    } else {
        $_SESSION['form_error'] = "ID do produto não fornecido.";
        header("Location: produtos.php");
        exit;
    }

    // Handle Form Submission
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['movimentar_produto'])) {
        $posted_produto_id = $_POST['produto_id'] ?? null;
        $tipo_movimentacao = $_POST['tipo_movimentacao'] ?? null;
        $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);
        $observacao = trim($_POST['observacao'] ?? '');

        // Validation
        if (empty($posted_produto_id) || $posted_produto_id !== $produto_id) {
            $form_error = "ID do produto inválido na submissão.";
        } elseif (empty($tipo_movimentacao) || !in_array($tipo_movimentacao, ['entrada', 'saida'])) {
            $form_error = "Tipo de movimentação inválido.";
        } elseif ($quantidade === false || $quantidade <= 0) {
            $form_error = "Quantidade inválida. Deve ser um número inteiro positivo.";
        } elseif ($tipo_movimentacao === 'saida' && $quantidade > $produto['quantidade_estoque']) {
            $form_error = "Quantidade de saída excede o estoque atual ({$produto['quantidade_estoque']} unidades).";
        }

        if (empty($form_error)) {
            $mysqli->begin_transaction();
            try {
                // 1. Update product stock
                $nova_quantidade_estoque = $produto['quantidade_estoque'];
                if ($tipo_movimentacao === 'entrada') {
                    $nova_quantidade_estoque += $quantidade;
                } else { // saida
                    $nova_quantidade_estoque -= $quantidade;
                }

                $stmt_update_stock = $mysqli->prepare("UPDATE produtos SET quantidade_estoque = ? WHERE id = ?");
                if (!$stmt_update_stock) throw new Exception("Erro ao preparar atualização de estoque: " . $mysqli->error);
                $stmt_update_stock->bind_param("is", $nova_quantidade_estoque, $produto_id);
                if (!$stmt_update_stock->execute()) throw new Exception("Erro ao atualizar estoque: " . $stmt_update_stock->error);
                $stmt_update_stock->close();

                // 2. Insert movement record
                // If using user_id: $stmt_insert_mov = $mysqli->prepare("INSERT INTO movimentacoes (produto_id, tipo_movimentacao, quantidade, observacao, usuario_id, data_movimentacao) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt_insert_mov = $mysqli->prepare("INSERT INTO movimentacoes (produto_id, tipo_movimentacao, quantidade, observacao, data_movimentacao) VALUES (?, ?, ?, ?, NOW())");
                if (!$stmt_insert_mov) throw new Exception("Erro ao preparar inserção de movimentação: " . $mysqli->error);
                
                // If using user_id: $stmt_insert_mov->bind_param("ssiss", $produto_id, $tipo_movimentacao, $quantidade, $observacao, $userID);
                $stmt_insert_mov->bind_param("ssis", $produto_id, $tipo_movimentacao, $quantidade, $observacao);
                if (!$stmt_insert_mov->execute()) throw new Exception("Erro ao registrar movimentação: " . $stmt_insert_mov->error);
                $stmt_insert_mov->close();

                $mysqli->commit();
                $_SESSION['form_success'] = "Movimentação registrada com sucesso!";
                
                // Refresh product data after update
                $stmt_refresh = $mysqli->prepare("SELECT quantidade_estoque FROM produtos WHERE id = ?");
                $stmt_refresh->bind_param("s", $produto_id);
                $stmt_refresh->execute();
                $result_refresh = $stmt_refresh->get_result();
                $updated_product_data = $result_refresh->fetch_assoc();
                if ($updated_product_data) {
                    $produto['quantidade_estoque'] = $updated_product_data['quantidade_estoque'];
                }
                $stmt_refresh->close();
                // No redirect, stay on page to see updated stock and potentially do another movement
                // header("Location: produtos.php");
                // exit;

            } catch (Exception $e) {
                $mysqli->rollback();
                $form_error = "Erro ao processar movimentação: " . $e->getMessage();
                error_log("Erro na movimentação: " . $e->getMessage());
            }
        }
         // Store in session to persist after potential redirect (if we were redirecting)
        if ($form_error) $_SESSION['form_error'] = $form_error;
        if ($form_success) $_SESSION['form_success'] = $form_success;
    }


} catch (Exception $e) {
    error_log("Erro na página de movimentar produto: " . $e->getMessage());
    // Use $_SESSION for errors if redirecting, $form_error if staying on page
    $form_error = "Ocorreu um erro geral ao carregar a página. Tente novamente.";
    // $_SESSION['form_error'] = "Ocorreu um erro ao carregar a página. Tente novamente.";
    // header("Location: produtos.php");
    // exit;
}

// Retrieve messages from session if they were set (e.g., after a redirect or for general page load errors)
if (isset($_SESSION['form_error'])) {
    $form_error = $_SESSION['form_error'];
    unset($_SESSION['form_error']);
}
if (isset($_SESSION['form_success'])) {
    $form_success = $_SESSION['form_success'];
    unset($_SESSION['form_success']);
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimentar Produto - <?= htmlspecialchars($softwareName) ?></title>
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
            width: 260px; background-color: var(--taskflow-deepest-purple); color: var(--taskflow-light-gray-beige); 
            padding-top: 0; position: fixed; height:100%; overflow-y: auto; 
            box-shadow: 3px 0 15px rgba(0,0,0,0.15); z-index: 1030; transition: width .3s ease-in-out;
        }
        .sidebar .logo-area {
            padding: 0.8rem 1.2rem; text-align: left; border-bottom: 1px solid rgba(220, 215, 212, 0.1); 
            display: flex; align-items: center; justify-content: flex-start; gap: 0.6rem; height: 60px;
        }
        .sidebar .logo-image-wrapper { position: relative; width: 40px; height: 40px; }
        .sidebar .logo-image-wrapper img { width: 100%; height: 100%; object-fit: contain; border-radius: 50%; position: relative; z-index: 2; }
        .sidebar .logo-image-wrapper::before {
            content: ''; position: absolute; top: -4px; left: -4px; width: calc(100% + 8px); height: calc(100% + 8px);
            border-radius: 50%; box-shadow: 0 0 6px 1px var(--logo-aura-color), 0 0 9px 2px var(--logo-aura-highlight);
            animation: rotateAuraSidebar 10s linear infinite, pulseAuraSidebar 2.5s ease-in-out infinite alternate; z-index: 1;
        }
        @keyframes rotateAuraSidebar { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes pulseAuraSidebar {
            0% { box-shadow: 0 0 5px 1px var(--logo-aura-color), 0 0 8px 2px var(--logo-aura-highlight); opacity: 0.6; }
            100% { box-shadow: 0 0 8px 2px var(--logo-aura-highlight), 0 0 12px 3px var(--logo-aura-color); opacity: 1; }
        }
        .sidebar .logo-area .logo-text-brand { font-size: 1.5rem; font-weight: 700; color: var(--taskflow-white); letter-spacing: 0.5px; line-height: 1; }
        .sidebar .menu ul { list-style: none; padding: 1.25rem 0; margin:0; }
        .sidebar .menu li a {
            display: flex; align-items: center; padding: 0.9rem 1.75rem; color: var(--taskflow-light-lavender); text-decoration: none; 
            transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out, border-left-color 0.2s ease-in-out;
            font-size: 0.98rem; border-left: 4px solid transparent; font-weight: 500;
        }
        .sidebar .menu li a i { margin-right: 1rem; width: 22px; text-align: center; font-size: 1.15em; }
        .sidebar .menu li a:hover { background-color: rgba(76, 1, 130, 0.35); color: var(--taskflow-white); border-left-color: var(--taskflow-light-lavender); }
        .sidebar .menu li.active a { background-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white); font-weight: 600; border-left-color: var(--taskflow-light-gray-beige); }
        .main-wrapper { flex-grow: 1; margin-left: 260px; display: flex; flex-direction: column; transition: margin-left .3s ease-in-out; }
        .header { background-color: var(--taskflow-card-bg); padding: .9rem 1.75rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--taskflow-border-color); box-shadow: 0 2px 4px rgba(26,0,65,.05); position: sticky; top: 0; z-index: 1020; height: 60px; }
        .header .search-bar { max-width: 450px; flex-grow: 1; } /* Not used on this page, but kept for consistency */
        .header .search-bar .form-control { border-right: 0; border-top-right-radius: 0; border-bottom-right-radius: 0; border-color: var(--taskflow-border-color); font-size: .9rem; padding: .45rem .9rem }
        .header .search-bar .form-control:focus { border-color: var(--taskflow-vibrant-purple); box-shadow: 0 0 0 .2rem rgba(76,1,130,.2) }
        .header .search-bar button { border-top-left-radius: 0; border-bottom-left-radius: 0; background-color: var(--taskflow-vibrant-purple); color: white; border-color: var(--taskflow-vibrant-purple); padding: .45rem .9rem }
        .header .search-bar button:hover { background-color: var(--taskflow-deepest-purple); border-color: var(--taskflow-deepest-purple) }
        .header .user-info .username { margin-right: 1.25rem; font-weight: 500; color: var(--taskflow-text-primary) }
        .header .btn-logout { color: var(--taskflow-muted-purple); font-size: 1.2rem }
        .header .btn-logout:hover { color: var(--taskflow-deepest-purple) }
        main { padding: 1.75rem; background-color: var(--taskflow-body-bg); flex-grow: 1 }
        
        .page-title-area { margin-bottom: 1.75rem; display: flex; justify-content: space-between; align-items: center; }
        .page-title-area h1 { font-size: 1.8rem; font-weight: 600; color: var(--taskflow-text-primary); margin-bottom: 0; }
        
        .form-card {
            background-color: var(--taskflow-card-bg);
            border: 1px solid var(--taskflow-border-color);
            border-radius: .5rem;
            box-shadow: 0 .15rem .4rem rgba(26,0,65,.07);
            margin-bottom: 1.75rem;
        }
        .form-card .card-header {
            padding: 1rem 1.5rem;
            margin-bottom: 0;
            background-color: #fdfcff;
            border-bottom: 1px solid var(--taskflow-border-color);
            border-top-left-radius: .5rem;
            border-top-right-radius: .5rem;
        }
        .form-card .card-header h2 { font-size: 1.2rem; font-weight: 600; margin-bottom: 0; color: var(--taskflow-text-primary); }
        .form-card .card-body { padding: 1.5rem; }
        .form-card .card-footer { padding: 1rem 1.5rem; background-color: #f8f9fc; border-top: 1px solid var(--taskflow-border-color); border-bottom-left-radius: .5rem; border-bottom-right-radius: .5rem; }
        
        .product-summary-card {
            background-color: var(--taskflow-card-bg);
            border: 1px solid var(--taskflow-border-color);
            border-left: 5px solid var(--taskflow-muted-purple);
            border-radius: .5rem;
            padding: 1.25rem;
            margin-bottom: 1.75rem;
            font-size: 0.95rem;
        }
        .product-summary-card strong { color: var(--taskflow-text-secondary); }
        .product-summary-card .current-stock { font-size: 1.1rem; font-weight: bold; color: var(--taskflow-vibrant-purple); }

        .btn-primary { background-color: var(--taskflow-vibrant-purple); border-color: var(--taskflow-vibrant-purple); }
        .btn-primary:hover { background-color: var(--taskflow-deepest-purple); border-color: var(--taskflow-deepest-purple); }
        .btn-outline-secondary { border-color: var(--taskflow-muted-purple); color: var(--taskflow-muted-purple); }
        .btn-outline-secondary:hover { background-color: var(--taskflow-muted-purple); color: var(--taskflow-white); }
        .form-control:focus, .form-select:focus { border-color: var(--taskflow-vibrant-purple); box-shadow: 0 0 0 .2rem rgba(76,1,130,.2); }

        .fab { position: fixed; bottom: 30px; right: 30px; width: 56px; height: 56px; background-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.7rem; text-decoration: none; box-shadow: 0 5px 15px rgba(26,0,65,.3); z-index: 1050; transition: transform .25s ease,background-color .25s ease,box-shadow .25s ease }
        .fab:hover { transform: scale(1.08) translateY(-2px); background-color: var(--taskflow-deepest-purple); box-shadow: 0 8px 20px rgba(26,0,65,.4); color: var(--taskflow-white) }

        @media (max-width:768px) { 
            .sidebar { width: 0; padding-left: 0; padding-right: 0; overflow: hidden } 
            .main-wrapper { margin-left: 0 } 
            .header { padding: .75rem 1rem } 
            .header .user-info .username { display: none } 
            main { padding: 1rem } 
            .fab { bottom: 20px; right: 20px; width: 50px; height: 50px; font-size: 1.5rem } 
            .page-title-area h1 { font-size: 1.5rem; }
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
                <!-- Search bar can be empty or removed if not relevant here -->
                <div class="search-bar d-flex"></div> 
                <div class="user-info d-flex align-items-center">
                    <span class="username"><?= $userName ?></span>
                    <a href="../logout.php" class="btn-logout" title="Sair">
                        <i class="fas fa-sign-out-alt fa-lg"></i>
                    </a>
                </div>
            </header>

            <main>
                <div class="page-title-area">
                    <h1><i class="fas fa-exchange-alt me-2"></i>Movimentar Estoque</h1>
                     <a href="produtos.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Voltar para Produtos</a>
                </div>

                <?php if ($form_success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($form_success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if ($form_error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($form_error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($produto): ?>
                    <div class="product-summary-card">
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="mb-1"><strong>Produto:</strong> <?= htmlspecialchars($produto['nome']) ?></h5>
                                <p class="mb-1"><strong>SKU:</strong> <?= htmlspecialchars($produto['sku']) ?></p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <p class="mb-0"><strong>Estoque Atual:</strong> <span class="current-stock"><?= number_format($produto['quantidade_estoque'] ?? 0) ?></span> unidades</p>
                                <p class="mb-0"><small><strong>Preço Unit.:</strong> R$ <?= number_format($produto['preco_unitario'] ?? 0, 2, ',', '.') ?></small></p>
                            </div>
                        </div>
                    </div>

                    <div class="form-card">
                        <div class="card-header">
                            <h2>Registrar Nova Movimentação</h2>
                        </div>
                        <form method="POST" action="movimentar_produto.php?id=<?= htmlspecialchars($produto_id) ?>">
                            <input type="hidden" name="produto_id" value="<?= htmlspecialchars($produto_id) ?>">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="tipo_movimentacao" class="form-label">Tipo de Movimentação <span class="text-danger">*</span></label>
                                        <select class="form-select" id="tipo_movimentacao" name="tipo_movimentacao" required>
                                            <option value="entrada" <?= (isset($_POST['tipo_movimentacao']) && $_POST['tipo_movimentacao'] == 'entrada') ? 'selected' : '' ?>>Entrada de Estoque</option>
                                            <option value="saida" <?= (isset($_POST['tipo_movimentacao']) && $_POST['tipo_movimentacao'] == 'saida') ? 'selected' : '' ?>>Saída de Estoque</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="quantidade" class="form-label">Quantidade <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="quantidade" name="quantidade" 
                                               value="<?= htmlspecialchars($_POST['quantidade'] ?? '1') ?>" 
                                               min="1" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="observacao" class="form-label">Observação (Opcional)</label>
                                    <textarea class="form-control" id="observacao" name="observacao" rows="3"><?= htmlspecialchars($_POST['observacao'] ?? '') ?></textarea>
                                    <small class="form-text text-muted">Ex: Compra do fornecedor X, Venda para cliente Y, Ajuste de inventário.</small>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <a href="produtos.php" class="btn btn-outline-secondary me-2">Cancelar</a>
                                <button type="submit" name="movimentar_produto" class="btn btn-primary">
                                    <i class="fas fa-check me-1"></i> Registrar Movimentação
                                </button>
                            </div>
                        </form>
                    </div>
                <?php elseif (!$form_error) : // Only show this if $produto is null AND no specific error is already set by PHP logic ?>
                     <div class="alert alert-warning" role="alert">
                        Produto não encontrado ou ID inválido. <a href="produtos.php" class="alert-link">Voltar para a lista de produtos</a>.
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <a href="adicionar_produto.php" class="fab" title="Adicionar Novo Produto"><i class="fas fa-plus"></i></a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>