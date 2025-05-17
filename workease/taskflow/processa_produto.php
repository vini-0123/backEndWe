<?php
session_start();

// Verificar autenticação
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Conexão com o banco
require_once dirname(dirname(__FILE__)) . '/factory/conexao.php';

// Verificar se recebeu POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['form_error'] = "Método inválido";
    header('Location: produtos.php');
    exit;
}

// Funções auxiliares (mantidas como antes)
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(trim($data ?? ''));
}

function process_upload($file, $existing_filename = null) {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $existing_filename;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE   => "O arquivo excede a diretiva upload_max_filesize no php.ini.",
            UPLOAD_ERR_FORM_SIZE  => "O arquivo excede a diretiva MAX_FILE_SIZE especificada no formulário HTML.",
            UPLOAD_ERR_PARTIAL    => "O upload do arquivo foi feito parcialmente.",
            UPLOAD_ERR_NO_TMP_DIR => "Faltando uma pasta temporária.",
            UPLOAD_ERR_CANT_WRITE => "Falha ao escrever o arquivo em disco.",
            UPLOAD_ERR_EXTENSION  => "Uma extensão do PHP interrompeu o upload do arquivo.",
        ];
        $error_message = $upload_errors[$file['error']] ?? 'Erro desconhecido no upload.';
        throw new Exception('Erro no upload: ' . $error_message);
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        throw new Exception('Tipo de arquivo não permitido (' . htmlspecialchars($file_type) . '). Permitidos: JPEG, PNG, GIF.');
    }

    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        throw new Exception('Arquivo muito grande (Máx: 5MB)');
    }

    $upload_dir_relative = 'uploads/produtos/';
    $upload_dir_absolute = dirname(dirname(__FILE__)) . '/' . $upload_dir_relative;


    if (!file_exists($upload_dir_absolute)) {
        if (!mkdir($upload_dir_absolute, 0775, true)) {
            throw new Exception('Erro ao criar diretório de uploads.');
        }
    }

    if ($existing_filename && file_exists($upload_dir_absolute . $existing_filename)) {
        unlink($upload_dir_absolute . $existing_filename);
    }

    $original_filename_sanitized = preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($file['name']));
    if (empty($original_filename_sanitized)) $original_filename_sanitized = "uploaded_image";
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)); // Pega extensão em minúsculas
    $filename_base = uniqid('prod_') . '_' . pathinfo($original_filename_sanitized, PATHINFO_FILENAME);
    $filename = $filename_base . '.' . $extension; // Reconstroi com extensão original

    $filepath = $upload_dir_absolute . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Erro ao salvar arquivo de upload.');
    }

    return $filename;
}


$action = $_POST['action'] ?? '';
$produto_id_str_post = $_POST['id'] ?? '';

$_SESSION['old_input'] = $_POST;


try {
    // Não precisamos mais buscar a categoria "Eletrônicos" fixamente aqui.
    // A categoria virá do POST.

    switch ($action) {
        case 'add':
        case 'edit':
            $nome = sanitize_input($_POST['nome']);
            $sku = sanitize_input($_POST['sku']);
            $descricao = trim($_POST['descricao'] ?? '');
            $preco_unitario_str = str_replace(',', '.', $_POST['preco_unitario'] ?? '0');
            $preco_unitario = (float)$preco_unitario_str;
            $quantidade_estoque = (int)($_POST['quantidade_estoque'] ?? 0);
            $quantidade_minima_str = $_POST['quantidade_minima'] ?? '';
            $quantidade_minima = $quantidade_minima_str === '' ? null : (int)$quantidade_minima_str;
            $ativo = (isset($_POST['ativo']) && $_POST['ativo'] == '1') ? 1 : 0;

            // --- VALIDAÇÃO DA CATEGORIA ---
            $categoria_id_post = trim($_POST['categoria_id'] ?? '');
            $categoria_id_para_salvar = null;

            if (empty($categoria_id_post)) {
                // DECISÃO: categoria_id é NOT NULL na tabela produtos?
                // Se SIM (obrigatória):
                throw new Exception("Categoria é obrigatória.");
                // Se NÃO (pode ser NULL):
                // $categoria_id_para_salvar = null; // Já é o default
            } elseif (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $categoria_id_post)) {
                throw new Exception("Formato do ID da categoria inválido.");
            } else {
                // Opcional: Verificar se a categoria_id enviada existe no banco e está ativa
                $stmt_check_cat = $mysqli->prepare("SELECT id FROM categorias WHERE id = ? AND ativo = 1 LIMIT 1");
                if (!$stmt_check_cat) throw new Exception("Erro ao preparar verificação da categoria: " . $mysqli->error);
                $stmt_check_cat->bind_param("s", $categoria_id_post);
                $stmt_check_cat->execute();
                $result_check_cat = $stmt_check_cat->get_result();
                if ($result_check_cat->num_rows === 0) {
                    throw new Exception("Categoria selecionada não encontrada ou está inativa.");
                }
                $categoria_id_para_salvar = $categoria_id_post; // Categoria é válida
                $stmt_check_cat->close();
            }
            // Se produtos.categoria_id PODE SER NULL e $categoria_id_post estava vazio, $categoria_id_para_salvar continuará null.


            $produto_id_to_use = null;
            if ($action === 'edit') {
                $produto_id_to_use = sanitize_input($produto_id_str_post);
                if (empty($produto_id_to_use) || !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $produto_id_to_use)) {
                    throw new Exception("ID do produto para edição é inválido.");
                }
            }

            $errors = [];
            if (empty($nome)) $errors[] = "Nome do produto é obrigatório.";
            if (empty($sku)) $errors[] = "SKU é obrigatório.";
            // A validação de categoria já foi feita acima se ela for NOT NULL
            if ($preco_unitario <= 0) $errors[] = "Preço unitário deve ser maior que zero.";
            if ($quantidade_estoque < 0) $errors[] = "Quantidade em estoque não pode ser negativa.";
            if ($quantidade_minima !== null && $quantidade_minima < 0) $errors[] = "Quantidade mínima não pode ser negativa.";


            if (!empty($errors)) {
                throw new Exception(implode("<br>", $errors));
            }

            $nome_arquivo_imagem = null;
            $imagem_atual_produto = null;

            if ($action === 'edit' && $produto_id_to_use) {
                $stmt_img = $mysqli->prepare("SELECT imagem_destaque FROM produtos WHERE id = ?");
                if (!$stmt_img) throw new Exception("Erro ao preparar busca da imagem: " . $mysqli->error);
                $stmt_img->bind_param("s", $produto_id_to_use);
                $stmt_img->execute();
                $res_img = $stmt_img->get_result();
                if ($row_img = $res_img->fetch_assoc()) {
                    $imagem_atual_produto = $row_img['imagem_destaque'];
                }
                $stmt_img->close();
            }

            if (isset($_FILES['imagem_destaque']) && $_FILES['imagem_destaque']['error'] !== UPLOAD_ERR_NO_FILE) {
                $nome_arquivo_imagem = process_upload($_FILES['imagem_destaque'], ($action === 'edit' ? $imagem_atual_produto : null));
            } elseif ($action === 'edit') {
                $nome_arquivo_imagem = $imagem_atual_produto;
            }

            if ($action === 'add') {
                $sql = "INSERT INTO produtos (id, nome, sku, descricao, categoria_id, preco_unitario, quantidade_estoque, 
                        quantidade_minima, imagem_destaque, ativo, data_cadastro) 
                        VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $mysqli->prepare($sql);
                if (!$stmt) throw new Exception("Erro ao preparar INSERT: " . $mysqli->error);
                // Tipos: s, s, s, s(cat_id), d(preco), i(qtd_e), i(qtd_m), s(img), i(ativo)
                // Se categoria_id_para_salvar for null, o bind_param para string (s) lidará com isso.
                $stmt->bind_param("ssssdiisi",
                    $nome, $sku, $descricao, $categoria_id_para_salvar, // Agora usa a categoria do POST (validada)
                    $preco_unitario, $quantidade_estoque, $quantidade_minima,
                    $nome_arquivo_imagem, $ativo
                );
            } else { // edit
                $sql_update_parts = [
                    "nome = ?", "sku = ?", "descricao = ?", "categoria_id = ?",
                    "preco_unitario = ?", "quantidade_estoque = ?", "quantidade_minima = ?", "ativo = ?"
                ];
                // Tipos: s, s, s, s(cat_id), d(preco), i(qtd_e), i(qtd_m), i(ativo)
                $bind_types = "ssssdiis";
                $bind_params_array = [
                    $nome, $sku, $descricao, $categoria_id_para_salvar, // Agora usa a categoria do POST (validada)
                    $preco_unitario, $quantidade_estoque, $quantidade_minima, $ativo
                ];

                if ($nome_arquivo_imagem !== null || (isset($_FILES['imagem_destaque']) && $_FILES['imagem_destaque']['error'] === UPLOAD_ERR_OK)) {
                    $sql_update_parts[] = "imagem_destaque = ?";
                    $bind_types .= "s";
                    $bind_params_array[] = $nome_arquivo_imagem;
                }
                
                $bind_types .= "s";
                $bind_params_array[] = $produto_id_to_use;

                $sql = "UPDATE produtos SET " . implode(", ", $sql_update_parts) . " WHERE id = ?";

                $stmt = $mysqli->prepare($sql);
                if (!$stmt) throw new Exception("Erro ao preparar UPDATE: " . $mysqli->error . " SQL: " . $sql);
                
                array_unshift($bind_params_array, $bind_types);
                if (!call_user_func_array([$stmt, 'bind_param'], $bind_params_array)) {
                     throw new Exception("Erro no bind_param do UPDATE: " . $stmt->error);
                }
            }

            if (!$stmt->execute()) {
                 $db_error = $mysqli->error;
                 if ($mysqli->errno == 1062) { // Erro de entrada duplicada (ex: SKU)
                     // Extrair qual chave causou o erro de duplicidade
                     if (strpos($db_error, 'sku_unico_produto') !== false) {
                         throw new Exception("Erro ao salvar produto: O SKU ('" . htmlspecialchars($sku) . "') já existe.");
                     } else {
                         throw new Exception("Erro ao salvar produto: Violação de chave única. Verifique os dados.");
                     }
                 } elseif ($mysqli->errno == 1452) { // Erro de chave estrangeira
                     if (strpos($db_error, 'fk_produtos_categoria') !== false) {
                         throw new Exception("Erro ao salvar produto: A categoria selecionada é inválida ou não existe.");
                     } else if (strpos($db_error, 'fk_produtos_fornecedor') !== false) {
                        // Se você adicionar fornecedor no futuro
                         throw new Exception("Erro ao salvar produto: O fornecedor selecionado é inválido ou não existe.");
                     } else {
                        throw new Exception("Erro ao salvar produto: Violação de chave estrangeira. Verifique os dados relacionados.");
                     }
                 }
                throw new Exception("Erro ao salvar produto no banco de dados: " . $db_error);
            }
            $stmt->close();

            unset($_SESSION['old_input']);
            $_SESSION['form_success'] = ($action === 'add') ?
                "Produto cadastrado com sucesso!" :
                "Produto atualizado com sucesso!";

            header('Location: produtos.php');
            break;

        case 'delete':
            $produto_id_to_delete = sanitize_input($produto_id_str_post);
            if (empty($produto_id_to_delete) || !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $produto_id_to_delete)) {
                throw new Exception("ID do produto para marcar como inativo é inválido.");
            }

            $stmt = $mysqli->prepare("UPDATE produtos SET ativo = 0 WHERE id = ?");
            if (!$stmt) throw new Exception("Erro ao preparar para marcar como inativo: " . $mysqli->error);
            $stmt->bind_param("s", $produto_id_to_delete);

            if (!$stmt->execute()) {
                throw new Exception("Erro ao marcar produto como inativo: " . $mysqli->error);
            }
            $stmt->close();

            $_SESSION['form_success'] = "Produto marcado como inativo com sucesso!";
            header('Location: produtos.php');
            break;

        default:
            throw new Exception("Ação inválida: " . htmlspecialchars($action));
    }

} catch (Exception $e) {
    $_SESSION['form_error'] = $e->getMessage();
    $redirect_id = ($action === 'edit' && !empty($produto_id_str_post)) ? sanitize_input($produto_id_str_post) : null;

    if ($action === 'edit' && $redirect_id) {
        header("Location: editar_produto.php?id=" . urlencode($redirect_id));
    } elseif ($action === 'add') {
        header("Location: adicionar_produto.php");
    }
    else {
        header('Location: produtos.php');
    }
} finally {
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        $mysqli->close();
    }
}
exit;