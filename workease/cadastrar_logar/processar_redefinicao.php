<?php
require_once __DIR__ . '/factory/conexao.php';
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Redefinir senha</title>
</head>
<body>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'], $_POST['nova_senha'])) {
    $token = $_POST['token'];
    $nova_senha = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT);

    $stmt = $mysqli->prepare("
        SELECT client_id FROM recuperacao_senhas
        WHERE token = ? AND usado = 0 AND expira_em > NOW()
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        $client_id = $row['client_id'];

        $stmt2 = $mysqli->prepare("UPDATE data_clients SET senha = ? WHERE id = ?");
        $stmt2->bind_param("ss", $nova_senha, $client_id);
        $stmt2->execute();

        $stmt3 = $mysqli->prepare("UPDATE recuperacao_senhas SET usado = 1 WHERE token = ?");
        $stmt3->bind_param("s", $token);
        $stmt3->execute();

        echo "<h2>Senha redefinida com sucesso!</h2>";
        header("Location: login.php");
        exit;
    } else {
        echo "<p style='color:red;'>Token inválido ou expirado.</p>";
    }
} else {
    echo "<p style='color:red;'>Requisição inválida.</p>";
}
?>
</body>
</html>
