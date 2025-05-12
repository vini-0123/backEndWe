<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

include_once './factory/conexao.php';

if (isset($_GET['id']) && isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    $produto_id = (int)$_GET['id'];

    if ($produto_id > 0) {
        // Soft delete: marcar como inativo
        $stmt = $mysqli->prepare("UPDATE produtos SET ativo = 0 WHERE id = ?");
        // Para hard delete (remover permanentemente):
        // $stmt = $mysqli->prepare("DELETE FROM produtos WHERE id = ?");
        
        if ($stmt) {
            $stmt->bind_param("i", $produto_id);
            if ($stmt->execute()) {
                $_SESSION['form_success'] = "Produto excluído (marcado como inativo) com sucesso.";
            } else {
                $_SESSION['form_error'] = "Erro ao excluir produto: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['form_error'] = "Erro ao preparar a exclusão: " . $mysqli->error;
        }
    } else {
        $_SESSION['form_error'] = "ID de produto inválido.";
    }
} else {
     $_SESSION['form_error'] = "Ação de exclusão não confirmada ou ID inválido.";
}

$mysqli->close();
header('Location: produtos.php'); // Redirecionar de volta para a lista de produtos
exit;
?>