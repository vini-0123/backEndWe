<?php
session_start();
// Simulate user session if not set for testing
if (!isset($_SESSION['user_name'])) {
    $_SESSION['user_name'] = 'Usuário Teste';
}
// Simulate login status for sidebar consistency if not fully set up
if (!isset($_SESSION['logged_in'])) {
    $_SESSION['logged_in'] = true;
}

$conexao_path = dirname(dirname(__FILE__)) . '/factory/conexao.php';
if (file_exists($conexao_path)) {
    require_once $conexao_path;
} else {
    $mysqli = null; 
}

// --- Data Fetching Logic (Kept as is from your provided code) ---
$categorias = [];
if ($mysqli && !$mysqli->connect_errno) {
    $stmt = $mysqli->prepare("SELECT id, nome, descricao, data_cadastro FROM categorias WHERE ativo = 1 ORDER BY nome");
    if ($stmt) { $stmt->execute(); $result = $stmt->get_result(); if ($result) { $categorias = $result->fetch_all(MYSQLI_ASSOC); } $stmt->close(); }
} else {
    $categorias = [['id' => 1, 'nome' => 'Eletrônicos', 'descricao' => 'Dispositivos e gadgets eletrônicos.', 'data_cadastro' => '2023-01-15 10:00:00'], ['id' => 2, 'nome' => 'Livros', 'descricao' => 'Livros de diversos gêneros.', 'data_cadastro' => '2023-02-20 14:30:00'], ['id' => 3, 'nome' => 'Roupas', 'descricao' => 'Vestuário masculino e feminino.', 'data_cadastro' => '2023-03-10 09:15:00']];
}
$userName = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Usuário';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias - TaskFlow</title>
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
            --taskflow-danger: #dc3545;
            --taskflow-danger-hover: #bb2d3b;
            --taskflow-success: #198754;
        }
        body { background-color: var(--taskflow-body-bg); font-family: 'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; color: var(--taskflow-text-primary); transition: margin-left .3s ease-in-out }
        .dashboard-container { display: flex; min-height: 100vh }
        .sidebar {
            width: 260px; background-color: var(--taskflow-deepest-purple); color: var(--taskflow-light-gray-beige);
            padding-top: 0; position: fixed; height:100%; overflow-y: auto; 
            box-shadow: 3px 0 15px rgba(0,0,0,0.15); z-index: 1030;
            transition: width 0.3s ease-in-out;
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
        .sidebar .menu ul { list-style: none; padding: 1.25rem 0; margin:0; }
        .sidebar .menu li a { display: flex; align-items: center; padding: 0.9rem 1.75rem; color: var(--taskflow-light-lavender); text-decoration: none; transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out, border-left-color 0.2s ease-in-out; font-size: 0.98rem; border-left: 4px solid transparent; font-weight:500; }
        .sidebar .menu li a i { margin-right: 1rem; width: 22px; text-align: center; font-size: 1.15em; }
        .sidebar .menu li a:hover { background-color: rgba(76, 1, 130, 0.35); color: var(--taskflow-white); border-left-color: var(--taskflow-light-lavender); }
        .sidebar .menu li.active a { background-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white); font-weight: 600; border-left-color: var(--taskflow-light-gray-beige); }

        /* Main Wrapper, Header, Main content, Stats Card, Table Card, Table, Modal, FAB, Responsive (Same as previous categorias.php) */
        .main-wrapper { flex-grow: 1; margin-left: 260px; display: flex; flex-direction: column; transition: margin-left .3s ease-in-out }
        .header { background-color: var(--taskflow-card-bg); padding: .9rem 1.75rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--taskflow-border-color); box-shadow: 0 2px 4px rgba(26,0,65,.05); position: sticky; top: 0; z-index: 1020 }
        .header .search-bar { max-width: 450px; flex-grow: 1; visibility: hidden }
        .header .user-info { margin-left: auto }
        .header .user-info .username { margin-right: 1.25rem; font-weight: 500; color: var(--taskflow-text-primary) }
        .header .btn-logout { color: var(--taskflow-muted-purple); font-size: 1.2rem }
        .header .btn-logout:hover { color: var(--taskflow-deepest-purple) }
        main { padding: 1.75rem; background-color: var(--taskflow-body-bg); flex-grow: 1 }
        .page-title-area { margin-bottom: 1.75rem; display: flex; justify-content: space-between; align-items: center }
        .page-title-area h1 { font-size: 1.75rem; font-weight: 600; color: var(--taskflow-text-primary); margin-bottom: 0 }
        .stats-card-wrapper { margin-bottom: 1.75rem }
        .stats-card { background-color: var(--taskflow-card-bg); border: 1px solid var(--taskflow-border-color); border-left: 5px solid var(--taskflow-vibrant-purple); border-radius: .5rem; box-shadow: 0 .15rem .4rem rgba(26,0,65,.07); padding: 1.5rem; display: flex; align-items: center; transition: transform .2s ease-out,box-shadow .2s ease-out }
        .stats-card:hover { transform: translateY(-4px); box-shadow: 0 .5rem 1rem rgba(26,0,65,.1) }
        .stats-card .card-icon { font-size: 1.8rem; min-width: 52px; height: 52px; margin-right: 1.25rem; border-radius: 50%; color: var(--taskflow-white); display: flex; align-items: center; justify-content: center; background-color: var(--taskflow-vibrant-purple) }
        .stats-card .card-content .card-title { font-size: .9rem; color: var(--taskflow-text-secondary); margin-bottom: .3rem; text-transform: uppercase; letter-spacing: .6px; font-weight: 500 }
        .stats-card .card-content .card-value { font-size: 1.9rem; font-weight: 700; color: var(--taskflow-text-primary) }
        .content-table-card { background-color: var(--taskflow-card-bg); border: 1px solid var(--taskflow-border-color); border-radius: .5rem; box-shadow: 0 .15rem .4rem rgba(26,0,65,.07); margin-bottom: 1.75rem }
        .content-table-card .card-header { padding: 1rem 1.5rem; margin-bottom: 0; background-color: #fdfcff; border-bottom: 1px solid var(--taskflow-border-color); display: flex; justify-content: space-between; align-items: center; border-top-left-radius: .5rem; border-top-right-radius: .5rem }
        .content-table-card .card-header h2 { font-size: 1.1rem; font-weight: 600; margin-bottom: 0; color: var(--taskflow-text-primary) }
        .content-table-card .card-body.p-0 { padding: 0 }
        .table { margin-bottom: 0 }
        .table th,.table td { vertical-align: middle; font-size: .92rem; padding: .85rem 1.25rem }
        .table thead th { background-color: #f8f9fc; color: var(--taskflow-text-secondary); font-weight: 600; border-bottom-width: 1px }
        .table tbody tr:hover { background-color: rgba(156,140,185,.07) }
        .table .actions .btn { margin: 0 .2rem; padding: .3rem .6rem; font-size: .85rem }
        .btn-primary { background-color: var(--taskflow-vibrant-purple); border-color: var(--taskflow-vibrant-purple) }
        .btn-primary:hover { background-color: var(--taskflow-deepest-purple); border-color: var(--taskflow-deepest-purple) }
        .btn-outline-primary { border-color: var(--taskflow-vibrant-purple); color: var(--taskflow-vibrant-purple) }
        .btn-outline-primary:hover { background-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white) }
        .btn-outline-danger { border-color: var(--taskflow-danger); color: var(--taskflow-danger) }
        .btn-outline-danger:hover { background-color: var(--taskflow-danger); color: var(--taskflow-white) }
        .modal-content { background-color: var(--taskflow-card-bg); border: 1px solid var(--taskflow-border-color); border-radius: .5rem }
        .modal-header { background-color: #fdfcff; border-bottom: 1px solid var(--taskflow-border-color); color: var(--taskflow-text-primary); padding: 1rem 1.5rem }
        .modal-header .modal-title { font-weight: 600 }
        .modal-header .btn-close { filter: invert(20%) sepia(10%) saturate(500%) hue-rotate(220deg) brightness(90%) contrast(90%) }
        .modal-body { padding: 1.5rem; color: var(--taskflow-text-primary) }
        .modal-body .form-label { font-weight: 500; color: var(--taskflow-text-secondary); margin-bottom: .3rem; font-size: .9rem }
        .modal-body .form-control { font-size: .9rem; padding: .45rem .9rem; border-color: var(--taskflow-border-color) }
        .modal-body .form-control:focus { border-color: var(--taskflow-vibrant-purple); box-shadow: 0 0 0 .2rem rgba(76,1,130,.2) }
        .modal-footer { background-color: #f8f9fc; border-top: 1px solid var(--taskflow-border-color); padding: .9rem 1.5rem }
        .btn-secondary { background-color: var(--taskflow-light-lavender); border-color: var(--taskflow-light-lavender); color: var(--taskflow-deepest-purple) }
        .btn-secondary:hover { background-color: var(--taskflow-muted-purple); border-color: var(--taskflow-muted-purple); color: var(--taskflow-white) }
        .fab { position: fixed; bottom: 30px; right: 30px; width: 56px; height: 56px; background-color: var(--taskflow-vibrant-purple); color: var(--taskflow-white); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.7rem; text-decoration: none; box-shadow: 0 5px 15px rgba(26,0,65,.3); z-index: 1050; transition: transform .25s ease,background-color .25s ease,box-shadow .25s ease }
        .fab:hover { transform: scale(1.08) translateY(-2px); background-color: var(--taskflow-deepest-purple); box-shadow: 0 8px 20px rgba(26,0,65,.4); color: var(--taskflow-white) }
        .no-data-message { text-align: center; padding: 2rem; color: var(--taskflow-text-secondary); font-style: italic }
        @media (max-width:768px) { .sidebar { width: 0; padding-left: 0; padding-right: 0; overflow: hidden } .main-wrapper { margin-left: 0 } .header { padding: .75rem 1rem } .header .search-bar { display: none } .header .user-info .username { display: none } main { padding: 1rem } .page-title-area h1 { font-size: 1.5rem } .fab { bottom: 20px; right: 20px; width: 50px; height: 50px; font-size: 1.5rem } .stats-card-wrapper .col-md-4 { width: 100% } }

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
                    <li class="active"><a href="categorias.php"><i class="fas fa-tags"></i> Categorias</a></li>
                    <li><a href="movimentacoes.php"><i class="fas fa-truck-ramp-box"></i> Movimentações</a></li>
                    <li><a href="relatorios.php"><i class="fas fa-file-invoice"></i> Relatórios</a></li>
                </ul>
            </nav>
        </aside>

        <div class="main-wrapper">
            <header class="header">
                <form class="search-bar d-none d-md-flex" action="#" method="GET"></form>
                <div class="user-info d-flex align-items-center">
                    <span class="username"><?= $userName ?></span>
                    <a href="logout.php" class="btn-logout" title="Sair"><i class="fas fa-sign-out-alt fa-lg"></i></a>
                </div>
            </header>

            <main>
                <div class="page-title-area">
                    <h1><i class="fas fa-tags me-2"></i>Categorias</h1>
                </div>

                <div class="row stats-card-wrapper">
                    <div class="col-md-4"> 
                        <div class="stats-card">
                            <div class="card-icon"><i class="fas fa-folder-open"></i></div>
                            <div class="card-content">
                                <span class="card-title">Categorias Ativas</span>
                                <span class="card-value"><?= count($categorias) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-table-card">
                    <div class="card-header">
                        <h2>Lista de Categorias</h2>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Descrição</th>
                                        <th class="text-center">Data Cadastro</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($categorias)): ?>
                                        <?php foreach ($categorias as $categoria): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($categoria['nome']) ?></td>
                                            <td><?= htmlspecialchars($categoria['descricao'] ?? 'N/A') ?></td>
                                            <td class="text-center"><?= isset($categoria['data_cadastro']) ? date('d/m/Y', strtotime($categoria['data_cadastro'])) : 'N/A' ?></td>
                                            <td class="actions text-center">
                                                <button class="btn btn-sm btn-outline-primary" title="Editar Categoria" onclick="openEditModal(<?= $categoria['id'] ?>, '<?= htmlspecialchars(addslashes($categoria['nome']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($categoria['descricao'] ?? ''), ENT_QUOTES) ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" title="Excluir Categoria" onclick="confirmDeleteCategoria(<?= $categoria['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="no-data-message">Nenhuma categoria encontrada.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="categoryModalLabel">Nova Categoria</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <form id="categoryForm">
                        <input type="hidden" id="categoriaId" name="categoriaId">
                        <div class="mb-3"><label for="categoriaNome" class="form-label">Nome da Categoria</label><input type="text" class="form-control" id="categoriaNome" name="nome" required></div>
                        <div class="mb-3"><label for="categoriaDescricao" class="form-label">Descrição (Opcional)</label><textarea class="form-control" id="categoriaDescricao" name="descricao" rows="3"></textarea></div>
                    </form>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" onclick="saveCategoria()">Salvar Categoria</button></div>
            </div>
        </div>
    </div>

    <a href="#" class="fab" title="Nova Categoria" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="prepareNewModal()"><i class="fas fa-plus"></i></a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript (kept as is from previous categorias.php)
        const categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
        const modalLabel = document.getElementById('categoryModalLabel');
        const categoriaIdInput = document.getElementById('categoriaId');
        const categoriaNomeInput = document.getElementById('categoriaNome');
        const categoriaDescricaoInput = document.getElementById('categoriaDescricao');
        function prepareNewModal() { modalLabel.textContent = 'Nova Categoria'; categoriaIdInput.value = ''; document.getElementById('categoryForm').reset(); }
        function openEditModal(id, nome, descricao) { modalLabel.textContent = 'Editar Categoria'; categoriaIdInput.value = id; categoriaNomeInput.value = nome; categoriaDescricaoInput.value = descricao; categoryModal.show(); }
        function saveCategoria() { const id = categoriaIdInput.value; const nome = categoriaNomeInput.value; const descricao = categoriaDescricaoInput.value; if (!nome.trim()) { alert('O nome da categoria é obrigatório.'); return; } console.log('Salvando Categoria:', { id, nome, descricao }); alert(`Categoria "${nome}" ${id ? 'atualizada' : 'salva'}! (Simulação)`); categoryModal.hide(); }
        function confirmDeleteCategoria(id) { if (confirm('Deseja realmente excluir esta categoria? Esta ação não pode ser desfeita.')) { console.log('Excluir categoria ID:', id); alert('Categoria ID ' + id + ' excluída! (Simulação)'); } }
    </script>
</body>
</html>