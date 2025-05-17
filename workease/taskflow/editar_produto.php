<?php
session_start();

// Verificar autenticação e definir variáveis de usuário
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$userName = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Usuário';
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

require_once dirname(dirname(__FILE__)) . '/factory/conexao.php';

if ($mysqli->connect_error) {
    error_log("Erro na conexão MySQL: " . $mysqli->connect_error);
    $_SESSION['form_error'] = "Erro interno ao conectar com o banco de dados.";
    header('Location: produtos.php');
    exit;
}

$produto_id_str = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($produto_id_str) || !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $produto_id_str)) {
    $_SESSION['form_error'] = "ID do produto inválido ou não fornecido.";
    header('Location: produtos.php');
    exit;
}

$stmt = $mysqli->prepare("SELECT * FROM produtos WHERE id = ?");
if (!$stmt) {
    error_log("Erro ao preparar statement para buscar produto: " . $mysqli->error);
    $_SESSION['form_error'] = "Erro ao buscar dados do produto.";
    header('Location: produtos.php');
    exit;
}
$stmt->bind_param("s", $produto_id_str);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $_SESSION['form_error'] = "Produto com ID " . htmlspecialchars($produto_id_str) . " não encontrado.";
    header('Location: produtos.php');
    exit;
}

$produto = $result->fetch_assoc();
$stmt->close();

// --- BUSCAR TODAS AS CATEGORIAS ATIVAS ---
$categorias_disponiveis = [];
$query_categorias = "SELECT id, nome FROM categorias WHERE ativo = 1 ORDER BY nome ASC";
if ($result_cat_all = $mysqli->query($query_categorias)) {
    while($cat = $result_cat_all->fetch_assoc()) {
        $categorias_disponiveis[] = $cat;
    }
    $result_cat_all->free();
} else {
     error_log("Erro ao buscar todas as categorias: " . $mysqli->error);
     // Pode definir uma mensagem de aviso se categorias não puderem ser carregadas
     $_SESSION['form_warning'] = "Atenção: Não foi possível carregar a lista de categorias.";
}


$page_title = "Editar Produto: " . htmlspecialchars($produto['nome']);

$form_error = isset($_SESSION['form_error']) ? $_SESSION['form_error'] : null;
unset($_SESSION['form_error']);
$form_success = isset($_SESSION['form_success']) ? $_SESSION['form_success'] : null;
unset($_SESSION['form_success']);
$form_warning = isset($_SESSION['form_warning']) ? $_SESSION['form_warning'] : null;
unset($_SESSION['form_warning']);


// --- PREPARAÇÃO DE $old_input ---
$old_input_session = isset($_SESSION['old_input']) ? $_SESSION['old_input'] : null;
unset($_SESSION['old_input']);

$old_input = [
    'nome' => htmlspecialchars($old_input_session['nome'] ?? $produto['nome'] ?? ''),
    'sku' => htmlspecialchars($old_input_session['sku'] ?? $produto['sku'] ?? ''),
    'descricao' => htmlspecialchars($old_input_session['descricao'] ?? $produto['descricao'] ?? ''),
    'preco_unitario' => htmlspecialchars($old_input_session['preco_unitario'] ?? $produto['preco_unitario'] ?? '0.00'),
    'quantidade_estoque' => htmlspecialchars($old_input_session['quantidade_estoque'] ?? $produto['quantidade_estoque'] ?? '0'),
    'quantidade_minima' => htmlspecialchars($old_input_session['quantidade_minima'] ?? $produto['quantidade_minima'] ?? ''),
    // 'categoria_id' agora pega o valor do produto ou do old_input da sessão.
    // Se $produto['categoria_id'] for NULL e não houver $old_input_session['categoria_id'], será string vazia.
    'categoria_id' => htmlspecialchars($old_input_session['categoria_id'] ?? $produto['categoria_id'] ?? ''),
    'ativo' => isset($old_input_session['ativo']) ? (int)$old_input_session['ativo'] : (isset($produto['ativo']) ? (int)$produto['ativo'] : 0),
];

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
        :root {
            --taskflow-deepest-purple: #1A0041;
            --taskflow-vibrant-purple: #4C0182;
            --taskflow-muted-purple: #6c5f8d;
            --taskflow-light-lavender: #9C8CB9;
            --taskflow-light-gray-beige: #dcd7d4;
            --taskflow-white: #ffffff;
            --taskflow-body-bg: #f0eef3; 
            --taskflow-card-bg: var(--taskflow-white);
            --taskflow-text-primary: #1f2937; 
            --taskflow-text-secondary: #6b7280; 
            --taskflow-border-color: #e3e6f0; 
        }
        body { background-color: var(--taskflow-body-bg); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: var(--taskflow-text-primary); transition: margin-left .3s ease-in-out; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background-color: var(--taskflow-deepest-purple); color: var(--taskflow-light-gray-beige); padding-top: 0; position: fixed; height:100%; overflow-y: auto; box-shadow: 3px 0 15px rgba(0,0,0,0.15); z-index: 1030; transition: width 0.3s ease-in-out; }
        .sidebar .logo-area { padding: 1rem 1.5rem; text-align: left; border-bottom: 1px solid rgba(220, 215, 212, 0.1); display: flex; align-items: center; justify-content: flex-start; gap: 0.75rem; }
        .sidebar .logo-area .logo-icon { font-size: 2rem; color: var(--taskflow-light-lavender); display: inline-block; }
        .sidebar .logo-area .logo-text-brand { font-size: 1.6rem; font-weight: 700; color: var(--taskflow-white); letter-spacing: 0.5px; }
        .sidebar .menu ul { list-style: none; padding: 1.25rem 0; margin:0; }
        .sidebar .menu li a { display: flex; align-items: center; padding: 0.9rem 1.75rem; color: var(--taskflow-light-lavender); text-decoration: none; transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out, border-left-color 0.2s ease-in-out; font-size: 0.98rem; border-left: 4px solid transparent; font-weight: 500; }
        .sidebar .menu li a i { margin-right: 1rem; width: 22px; text-align: center; font-size: 1.15em; }
        .sidebar .menu li a:hover { background-color: rgba(76, 1, 130, 0.35); color: var(--taskflow-white); border-left-color: var(--taskflow-light-lavender); }
        .sidebar .menu li.active a { background-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white); font-weight: 600; border-left-color: var(--taskflow-light-gray-beige); }
        .main-wrapper { flex-grow: 1; margin-left: 260px; display: flex; flex-direction: column; transition: margin-left .3s ease-in-out; }
        .header { background-color: var(--taskflow-card-bg); padding: .9rem 1.75rem; display: flex; justify-content: flex-end; align-items: center; border-bottom: 1px solid var(--taskflow-border-color); box-shadow: 0 2px 4px rgba(26,0,65,.05); position: sticky; top: 0; z-index: 1020; }
        .header .user-info { display: flex; align-items: center; }
        .header .user-info .username { margin-right: 1.25rem; font-weight: 500; color: var(--taskflow-text-primary); }
        .header .btn-logout { color: var(--taskflow-muted-purple); font-size: 1.2rem; text-decoration: none; }
        .header .btn-logout:hover { color: var(--taskflow-deepest-purple); }
        main { padding: 1.75rem; background-color: var(--taskflow-body-bg); flex-grow:1; }
        .card-form { background-color: var(--taskflow-card-bg); border: 1px solid var(--taskflow-border-color); border-radius: .5rem; box-shadow: 0 .1rem .3rem rgba(26,0,65,.06); color: var(--taskflow-text-primary); }
        .card-form .card-header { background-color: #f8f9fc; border-bottom: 1px solid var(--taskflow-border-color); color: var(--taskflow-vibrant-purple); padding: 1rem 1.25rem; }
        .card-form .card-header h4 { font-size: 1.25rem; font-weight: 600; margin-bottom: 0; }
        .card-form .card-body { padding: 1.5rem; font-size: .95rem; }
        .form-label { font-weight: 500; margin-bottom: .5rem; color: var(--taskflow-text-primary); }
        .form-control, .form-select { font-size: .95rem; border-color: var(--taskflow-border-color); border-radius: .375rem; }
        .form-control:focus, .form-select:focus { border-color: var(--taskflow-vibrant-purple); box-shadow: 0 0 0 .2rem rgba(76, 1, 130, .2); }
        .form-check-input:checked { background-color: var(--taskflow-vibrant-purple); border-color: var(--taskflow-vibrant-purple); }
        .form-check-input:focus { box-shadow: 0 0 0 .2rem rgba(76, 1, 130, .2); }
        .is-invalid { border-color: #dc3545; }
        .invalid-feedback { display: block; }
        .btn-taskflow-primary { background-color: var(--taskflow-vibrant-purple); border-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white); }
        .btn-taskflow-primary:hover { background-color: var(--taskflow-deepest-purple); border-color: var(--taskflow-deepest-purple); }
        .btn-taskflow-secondary { background-color: var(--taskflow-muted-purple); border-color: var(--taskflow-muted-purple); color: var(--taskflow-white); }
        .btn-taskflow-secondary:hover { background-color: #5a4d75; border-color: #5a4d75; }
        .btn-danger { background-color: #dc3545; border-color: #dc3545; }
        .btn-danger:hover { background-color: #bb2d3b; border-color: #b02a37; }
        .current-image-preview { max-width: 120px; max-height: 120px; margin-top: 10px; border: 1px solid var(--taskflow-border-color); padding: 4px; border-radius: .375rem; object-fit: cover; }
        .modal-header { background-color: var(--taskflow-deepest-purple); color: var(--taskflow-light-gray-beige); border-bottom: 1px solid var(--taskflow-vibrant-purple); }
        .modal-header .btn-close-white { filter: invert(1) grayscale(100%) brightness(200%); }
        .modal-body strong { color: var(--taskflow-vibrant-purple); }
        @media (max-width: 768px) {
            .sidebar { width: 0; padding-left: 0; padding-right: 0; overflow: hidden; }
            .main-wrapper { margin-left: 0; }
            .header { padding: .75rem 1rem; }
            .header .user-info .username { display: none; }
            main { padding: 1rem; }
            .card-form .card-header h4 { font-size: 1.1rem; }
            .card-form .card-body { padding: 1rem; }
            .form-buttons .btn { margin-bottom: 0.5rem; }
            .form-buttons .float-end { float: none !important; display: block; width: 100%; }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
             <div class="logo-area">
                <i class="fas fa-boxes-stacked logo-icon"></i>
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
            <header class="header">
                <div class="user-info">
                    <span class="username"><?= $userName ?></span>
                    <a href="logout.php" class="btn-logout" title="Sair">
                         <i class="fas fa-sign-out-alt fa-lg"></i>
                    </a>
                </div>
            </header>

            <main>
                <div class="container-fluid">
                     <?php if ($form_success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($form_success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                     <?php endif; ?>
                     <?php if ($form_warning): ?>
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($form_warning) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                     <?php endif; ?>
                     <?php if ($form_error): ?>
                         <div class="alert alert-danger alert-dismissible fade show" role="alert">
                             <?= is_array($form_error) ? implode('<br>', array_map('htmlspecialchars', $form_error)) : htmlspecialchars($form_error) ?>
                             <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                         </div>
                     <?php endif; ?>

                    <div class="card card-form shadow-sm">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="fas fa-edit me-2"></i><?= $page_title ?></h4>
                        </div>
                        <div class="card-body">
                            <form action="processa_produto.php" method="POST" enctype="multipart/form-data" novalidate>
                                <input type="hidden" name="id" value="<?= htmlspecialchars($produto['id']) ?>">
                                <input type="hidden" name="action" value="edit">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nome" class="form-label">Nome do Produto <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nome" name="nome"
                                               value="<?= $old_input['nome'] ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="sku" name="sku"
                                               value="<?= $old_input['sku'] ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="descricao" class="form-label">Descrição</label>
                                    <textarea class="form-control" id="descricao" name="descricao" rows="3"><?= $old_input['descricao'] ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="preco_unitario" class="form-label">Preço Unitário (R$) <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="preco_unitario" name="preco_unitario"
                                               value="<?= str_replace('.', ',', $old_input['preco_unitario']) ?>" required placeholder="Ex: 19,90">
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="quantidade_estoque" class="form-label">Quantidade em Estoque <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="quantidade_estoque" name="quantidade_estoque"
                                               min="0" value="<?= $old_input['quantidade_estoque'] ?>" required>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="quantidade_minima" class="form-label">Quantidade Mínima</label>
                                        <input type="number" class="form-control" id="quantidade_minima" name="quantidade_minima"
                                               min="0" value="<?= $old_input['quantidade_minima'] ?>">
                                         <small class="form-text text-muted">Deixe em branco ou 0 se não houver mínimo.</small>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="categoria_id" class="form-label">Categoria <span class="text-danger">*</span></label>
                                        <select class="form-select" id="categoria_id" name="categoria_id" required>
                                            <option value="">Selecione uma Categoria</option>
                                            <?php foreach ($categorias_disponiveis as $categoria_opt): ?>
                                                <option value="<?= htmlspecialchars($categoria_opt['id']) ?>"
                                                    <?= ($old_input['categoria_id'] == $categoria_opt['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($categoria_opt['nome']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <?php if (empty($categorias_disponiveis)): ?>
                                                <option value="" disabled>Nenhuma categoria ativa encontrada.</option>
                                            <?php endif; ?>
                                        </select>
                                        <?php if (empty($categorias_disponiveis) && !isset($_SESSION['form_warning'])): ?>
                                            <div class="text-danger small mt-1">
                                                Nenhuma categoria ativa disponível. Cadastre ou ative categorias para associar ao produto.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="imagem_destaque" class="form-label">Nova Imagem do Produto</label>
                                        <input class="form-control" type="file" id="imagem_destaque" name="imagem_destaque" accept="image/png, image/jpeg, image/gif">
                                        <small class="form-text text-muted">Envie uma nova imagem para substituí-la.</small>
                                        <?php
                                            $current_image_filename = $produto['imagem_destaque'] ?? '';
                                            if (!empty($current_image_filename)) {
                                                $image_path_relative = 'uploads/produtos/' . basename($current_image_filename);
                                                $image_path_for_file_exists = dirname(dirname(__FILE__)) . '/' . $image_path_relative;

                                                if (file_exists($image_path_for_file_exists)) {
                                        ?>
                                            <div class="mt-2">
                                                <p class="mb-1"><small>Imagem Atual:</small></p>
                                                <img src="../<?= htmlspecialchars($image_path_relative) ?>?t=<?= time() ?>" alt="Imagem Atual" class="current-image-preview">
                                            </div>
                                        <?php
                                                } else {
                                                     echo '<small class="text-muted d-block mt-2">Arquivo da imagem atual não encontrado (' . htmlspecialchars($current_image_filename) . ').</small>';
                                                }
                                            } else {
                                                echo '<small class="text-muted d-block mt-2">Nenhuma imagem cadastrada.</small>';
                                            }
                                        ?>
                                    </div>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="hidden" name="ativo" value="0">
                                    <input type="checkbox" class="form-check-input" id="ativo" name="ativo" value="1"
                                           <?= ($old_input['ativo'] == 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ativo">Produto Ativo</label>
                                </div>

                                <hr>

                                <div class="mt-4 d-flex justify-content-between flex-wrap form-buttons">
                                    <div>
                                        <button type="submit" class="btn btn-taskflow-primary"><i class="fas fa-save me-1"></i> Salvar Alterações</button>
                                        <a href="produtos.php" class="btn btn-taskflow-secondary ms-2"><i class="fas fa-times me-1"></i> Cancelar</a>
                                    </div>
                                     <button type="button" class="btn btn-danger float-end" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">
                                        <i class="fas fa-trash-alt me-1"></i> Marcar como Inativo
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Ação</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Tem certeza de que deseja marcar o produto "<strong><?= htmlspecialchars($produto['nome']) ?></strong>" como inativo?
                    <br><br>
                    <span class="text-danger fw-bold">Atenção:</span> O produto não será exibido nas listagens padrão, mas seus dados e histórico de movimentações serão mantidos.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-taskflow-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="processa_produto.php" method="POST" style="display: inline;">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($produto['id']) ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn btn-danger">Sim, Marcar como Inativo</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>