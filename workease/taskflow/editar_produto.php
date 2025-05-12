<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

include_once './factory/conexao.php';
$userName = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Usuário';

$produto_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$produto = null;

if ($produto_id > 0) {
    $stmt = $mysqli->prepare("SELECT * FROM produtos WHERE id = ? AND ativo = 1"); // Garante que só edite ativos ou ajuste conforme regra
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $produto = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$produto) {
    $_SESSION['form_error'] = "Produto não encontrado ou inválido.";
    header('Location: produtos.php');
    exit;
}

// Buscar categorias para o select
$categorias = [];
$query_categorias = "SELECT id, nome FROM categorias WHERE ativo = 1 ORDER BY nome ASC";
if ($result_cat = $mysqli->query($query_categorias)) {
    while($cat = $result_cat->fetch_assoc()) {
        $categorias[] = $cat;
    }
}

$page_title = "Editar Produto: " . htmlspecialchars($produto['nome']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Taskflow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* (Copie os mesmos estilos do <style> de adicionar_produto.php aqui) */
        :root { /* ... seu root ... */ } body { /* ... */ } .dashboard-container { /* ... */ } /* etc. */
        :root {
            --taskflow-deepest-purple: #1A0041;
            --taskflow-vibrant-purple: #4C0182;
            --taskflow-muted-purple: #6c5f8d;
            --taskflow-light-lavender: #9C8CB9;
            --taskflow-light-gray-beige: #dcd7d4;
            --taskflow-white: #ffffff;
            --taskflow-body-bg: #f0eef3;
        }
        body { background-color: var(--taskflow-body-bg); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: var(--taskflow-deepest-purple); }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background-color: var(--taskflow-deepest-purple); color: var(--taskflow-light-gray-beige); padding-top: 0; position: fixed; height:100%; overflow-y: auto; box-shadow: 2px 0 10px rgba(0,0,0,0.1); z-index: 1030; }
        .sidebar .logo { padding: 1.35rem 1rem; text-align: center; font-size: 1.8rem; font-weight: 600; border-bottom: 1px solid var(--taskflow-vibrant-purple); color: var(--taskflow-light-lavender); }
        .sidebar .logo .logo-icon { margin-right: 10px; color: var(--taskflow-light-lavender); }
        .sidebar .logo .logo-text { color: var(--taskflow-light-gray-beige); }
        .sidebar .menu ul { list-style: none; padding: 1rem 0; margin:0; }
        .sidebar .menu li a { display: flex; align-items: center; padding: 0.85rem 1.5rem; color: var(--taskflow-light-lavender); text-decoration: none; transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out; font-size: 0.95rem; border-left: 3px solid transparent; }
        .sidebar .menu li a i { margin-right: 0.9rem; width: 20px; text-align: center; font-size: 1.1em; }
        .sidebar .menu li a:hover { background-color: rgba(76, 1, 130, 0.3); color: var(--taskflow-white); border-left-color: var(--taskflow-light-lavender); }
        .sidebar .menu li.active a { background-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white); font-weight: 500; border-left-color: var(--taskflow-light-gray-beige); }
        .main-wrapper { flex-grow: 1; margin-left: 250px; display: flex; flex-direction: column; }
        .header { background-color: var(--taskflow-white); padding: 0.85rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--taskflow-muted-purple); box-shadow: 0 0.125rem 0.35rem rgba(26,0,65,.075); position: sticky; top: 0; z-index: 1020; }
        .header .user-info .username { margin-right: 1rem; font-weight: 500; color: var(--taskflow-deepest-purple); }
        .header .btn-logout { color: var(--taskflow-muted-purple); }
        .header .btn-logout:hover { color: var(--taskflow-deepest-purple); }
        main { padding: 1.5rem; background-color: var(--taskflow-body-bg); flex-grow:1; }
        .form-control:focus, .form-select:focus { border-color: var(--taskflow-vibrant-purple); box-shadow: 0 0 0 0.2rem rgba(76, 1, 130, 0.25); }
        .btn-taskflow-primary { background-color: var(--taskflow-vibrant-purple); border-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white); }
        .btn-taskflow-primary:hover { background-color: var(--taskflow-deepest-purple); border-color: var(--taskflow-deepest-purple); }
        .btn-taskflow-secondary { background-color: var(--taskflow-muted-purple); border-color: var(--taskflow-muted-purple); color: var(--taskflow-white); }
        .btn-taskflow-secondary:hover { background-color: var(--taskflow-light-lavender); border-color: var(--taskflow-light-lavender); color: var(--taskflow-deepest-purple); }
        .card-form { background-color: var(--taskflow-white); border: 1px solid var(--taskflow-light-lavender); border-radius: 0.5rem; box-shadow: 0 0.25rem 0.75rem rgba(26,0,65,.05); }
        .card-form .card-header { background-color: rgba(26, 0, 65, 0.03); border-bottom: 1px solid var(--taskflow-light-lavender); color: var(--taskflow-vibrant-purple); }
        .current-image-preview { max-width: 150px; max-height: 150px; margin-top: 10px; border: 1px solid var(--taskflow-light-lavender); padding: 5px; border-radius: 0.25rem; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
             <div class="logo">
                <span class="logo-icon"><i class="fas fa-brain"></i></span>
                <span class="logo-text">Taskflow</span>
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
            <header class="header">
                <div class="ms-auto">
                    <div class="user-info d-flex align-items-center">
                        <span class="username"><?= $userName ?></span>
                        <a href="logout.php" class="btn-logout" title="Sair"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                    </div>
                </div>
            </header>
            <main>
                 <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="card card-form">
                                <div class="card-header">
                                    <h4 class="mb-0"><i class="fas fa-edit me-2"></i><?= $page_title ?></h4>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($_SESSION['form_error'])): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <?= htmlspecialchars($_SESSION['form_error']); ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                        <?php unset($_SESSION['form_error']); ?>
                                    <?php endif; ?>
                                     <?php if (isset($_SESSION['form_success'])): ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <?= htmlspecialchars($_SESSION['form_success']); ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                        <?php unset($_SESSION['form_success']); ?>
                                    <?php endif; ?>

                                    <form action="salvar_produto.php" method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="id" value="<?= $produto['id'] ?>">

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="nome" class="form-label">Nome do Produto <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="nome" name="nome" required value="<?= htmlspecialchars($produto['nome']) ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="sku" name="sku" required value="<?= htmlspecialchars($produto['sku']) ?>">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="descricao" class="form-label">Descrição</label>
                                            <textarea class="form-control" id="descricao" name="descricao" rows="3"><?= htmlspecialchars($produto['descricao'] ?? '') ?></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="preco_unitario" class="form-label">Preço Unitário (R$) <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" class="form-control" id="preco_unitario" name="preco_unitario" required value="<?= htmlspecialchars($produto['preco_unitario']) ?>">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="quantidade" class="form-label">Quantidade em Estoque</label>
                                                <input type="number" class="form-control" id="quantidade" name="quantidade" value="<?= htmlspecialchars($produto['quantidade']) ?>">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="quantidade_minima" class="form-label">Quantidade Mínima</label>
                                                <input type="number" class="form-control" id="quantidade_minima" name="quantidade_minima" value="<?= htmlspecialchars($produto['quantidade_minima']) ?>">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="categoria_id" class="form-label">Categoria</label>
                                                <select class="form-select" id="categoria_id" name="categoria_id">
                                                    <option value="">Selecione uma categoria</option>
                                                    <?php foreach ($categorias as $categoria): ?>
                                                        <option value="<?= $categoria['id'] ?>" <?= ($produto['categoria_id'] == $categoria['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($categoria['nome']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="imagem_destaque" class="form-label">Nova Imagem do Produto</label>
                                                <input class="form-control" type="file" id="imagem_destaque" name="imagem_destaque" accept="image/png, image/jpeg, image/gif">
                                                <small class="form-text text-muted">Deixe em branco para manter a imagem atual.</small>
                                                <?php if (!empty($produto['imagem_destaque']) && file_exists('uploads/produtos/' . $produto['imagem_destaque'])): ?>
                                                    <div class="mt-2">
                                                        <img src="uploads/produtos/<?= htmlspecialchars($produto['imagem_destaque']) ?>" alt="Imagem Atual" class="current-image-preview">
                                                        <br><small>Imagem Atual: <?= htmlspecialchars($produto['imagem_destaque']) ?></small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="ativo" name="ativo" value="1" <?= ($produto['ativo'] == 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="ativo">Produto Ativo</label>
                                        </div>

                                        <div class="mt-4">
                                            <button type="submit" class="btn btn-taskflow-primary"><i class="fas fa-save me-2"></i>Salvar Alterações</button>
                                            <a href="produtos.php" class="btn btn-taskflow-secondary"><i class="fas fa-times me-2"></i>Cancelar</a>
                                             <button type="button" class="btn btn-danger float-end" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">
                                                <i class="fas fa-trash-alt me-2"></i>Excluir Produto
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--taskflow-deepest-purple); color: var(--taskflow-light-gray-beige);">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Tem certeza de que deseja excluir o produto "<strong><?= htmlspecialchars($produto['nome']) ?></strong>"?
                    Esta ação marcará o produto como inativo e não poderá ser desfeita facilmente pela interface (mas os dados permanecerão no banco).
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-taskflow-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="excluir_produto.php?id=<?= $produto['id'] ?>&confirm=yes" class="btn btn-danger">Sim, Excluir</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>