<?php
session_start();
header('Content-Type: application/json');

require_once dirname(dirname(__FILE__)) . '/factory/conexao.php';

function generate_uuid() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['nome']) || empty(trim($data['nome']))) {
    echo json_encode(['success' => false, 'message' => 'Nome da categoria é obrigatório']);
    exit;
}

$id = isset($data['id']) ? filter_var($data['id']) : null;
$nome = filter_var($data['nome']);
$descricao = isset($data['descricao']) ? filter_var($data['descricao']) : '';

try {
    if ($id) {
        // Atualização
        $stmt = $mysqli->prepare("UPDATE categorias SET nome = ?, descricao = ? WHERE id = ?");
        $stmt->bind_param("sss", $nome, $descricao, $id);
    } else {
        // Inserção
        $id = generate_uuid();
        $stmt = $mysqli->prepare("INSERT INTO categorias (id, nome, descricao) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $id, $nome, $descricao);
    }

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => $id ? 'Categoria atualizada com sucesso!' : 'Categoria criada com sucesso!',
            'id' => $id ?? $mysqli->insert_id
        ]);
    } else {
        throw new Exception("Erro ao salvar: " . $stmt->error);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}