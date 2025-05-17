<?php
session_start();

// Verificar autenticação
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Corrigir o caminho do arquivo de conexão
require_once dirname(dirname(__FILE__)) . '/factory/conexao.php';

// Verificar conexão
if ($mysqli->connect_error) {
    die("Erro na conexão: " . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitização e coleta de dados
    $produto_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nome = trim($_POST['nome'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $descricao = trim($_POST['descricao'] ?? null);
    $preco_unitario = isset($_POST['preco_unitario']) ? (float)$_POST['preco_unitario'] : 0.0;
    $quantidade_estoque = isset($_POST['quantidade_estoque']) ? (int)$_POST['quantidade_estoque'] : 0;
    $quantidade_minima = isset($_POST['quantidade_minima']) ? (int)$_POST['quantidade_minima'] : 5;
    $categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
    $fornecedor_id = !empty($_POST['fornecedor_id']) ? (int)$_POST['fornecedor_id'] : null;
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    // Validações básicas
    if (empty($nome) || empty($sku) || $preco_unitario <= 0) {
        $_SESSION['form_error'] = "Nome, SKU e Preço Unitário são obrigatórios e o preço deve ser maior que zero.";
        if ($produto_id) {
            header('Location: editar_produto.php?id=' . $produto_id);
        } else {
            header('Location: adicionar_produto.php');
        }
        exit;
    }

    // Lógica de Upload de Imagem
    $imagem_destaque_nome = null;
    if (isset($_FILES['imagem_destaque']) && $_FILES['imagem_destaque']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/produtos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true); // Tenta criar o diretório se não existir
        }

        $tmp_name = $_FILES['imagem_destaque']['tmp_name'];
        $file_name = basename($_FILES['imagem_destaque']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_exts)) {
            if ($_FILES['imagem_destaque']['size'] <= 2097152) { // 2MB
                $imagem_destaque_nome = uniqid('produto_', true) . '.' . $file_ext;
                $destination = $upload_dir . $imagem_destaque_nome;

                if (!move_uploaded_file($tmp_name, $destination)) {
                    $_SESSION['form_error'] = "Erro ao mover o arquivo de imagem.";
                    $imagem_destaque_nome = null; // Reseta se falhar
                } else {
                    // Se for edição e uma imagem antiga existir, pode ser interessante removê-la aqui
                    if ($produto_id) {
                        $stmt_old_img = $mysqli->prepare("SELECT imagem_destaque FROM produtos WHERE id = ?");
                        $stmt_old_img->bind_param("i", $produto_id);
                        $stmt_old_img->execute();
                        $result_old_img = $stmt_old_img->get_result();
                        if ($old_img_data = $result_old_img->fetch_assoc()) {
                            if (!empty($old_img_data['imagem_destaque']) && file_exists($upload_dir . $old_img_data['imagem_destaque'])) {
                                unlink($upload_dir . $old_img_data['imagem_destaque']);
                            }
                        }
                        $stmt_old_img->close();
                    }
                }
            } else {
                $_SESSION['form_error'] = "O arquivo de imagem excede o tamanho máximo de 2MB.";
                $imagem_destaque_nome = null;
            }
        } else {
            $_SESSION['form_error'] = "Formato de imagem inválido. Use JPG, JPEG, PNG ou GIF.";
            $imagem_destaque_nome = null;
        }
        if (isset($_SESSION['form_error'])) { // Redireciona se houve erro no upload
            if ($produto_id) { header('Location: editar_produto.php?id=' . $produto_id); } else { header('Location: adicionar_produto.php'); }
            exit;
        }
    }

    if ($produto_id > 0) { // Atualizar produto existente
        $sql = "UPDATE produtos SET 
            nome = ?, 
            sku = ?, 
            descricao = ?, 
            categoria_id = ?, 
            preco_unitario = ?, 
            quantidade_estoque = ?, 
            quantidade_minima = ?, 
            fornecedor_id = ?, 
            ativo = ?
            WHERE id = ?";
        $types = "sssidiiiis";
        $params = [
            $nome, 
            $sku, 
            $descricao, 
            $categoria_id, 
            $preco_unitario, 
            $quantidade_estoque, 
            $quantidade_minima,
            $fornecedor_id,
            $ativo,
            $produto_id
        ];
        $acao = "atualizado";
        $redirect_url = 'editar_produto.php?id=' . $produto_id;

    } else { // Inserir novo produto
        $sql = "INSERT INTO produtos (
            nome, 
            sku, 
            descricao, 
            categoria_id, 
            preco_unitario, 
            quantidade_estoque, 
            quantidade_minima,
            fornecedor_id,
            ativo,
            data_cadastro
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
        $types = "sssidiiii";
        $params = [
            $nome, 
            $sku, 
            $descricao, 
            $categoria_id, 
            $preco_unitario, 
            $quantidade_estoque, 
            $quantidade_minima,
            $fornecedor_id,
            $ativo
        ];
        $acao = "cadastrado";
        $redirect_url = 'adicionar_produto.php'; // Ou produtos.php
    }

    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $_SESSION['form_success'] = "Produto $acao com sucesso!";
            if ($acao == "cadastrado") {
                $novo_id = $mysqli->insert_id;
                header('Location: editar_produto.php?id=' . $novo_id); // Redireciona para editar o recém-criado
            } else {
                 header('Location: ' . $redirect_url);
            }
        } else {
            $_SESSION['form_error'] = "Erro ao salvar produto: " . $stmt->error;
            header('Location: ' . $redirect_url);
        }
        $stmt->close();
    } else {
        $_SESSION['form_error'] = "Erro ao preparar a query: " . $mysqli->error;
        header('Location: ' . $redirect_url);
    }
    $mysqli->close();
    exit;

} else {
    // Método não permitido
    $_SESSION['form_error'] = "Acesso inválido.";
    header('Location: produtos.php');
    exit;
}
?>