<?php
include_once './factory/conexao.php';

if (!isset($_GET['token'])) {
    die("Token inválido.");
}

$token = $_GET['token'];

$stmt = $mysqli->prepare("
    SELECT rs.client_id, dc.email 
    FROM recuperacao_senhas rs
    JOIN data_clients dc ON rs.client_id = dc.id
    WHERE rs.token = ? AND rs.usado = 0 AND rs.expira_em > NOW()
");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$info = $result->fetch_assoc();

if (!$info) {
    die("Token inválido ou expirado.");
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>WorkEase – Redefinir Senha</title>
  <link rel="stylesheet" href="css/style.css">
  <!-- outros head… -->
</head>
<body>
  <div class="screen login-screen">
    <a href="index.php" class="back-link">
      <i class="fas fa-arrow-left"></i> Voltar
    </a>
    <h1 class="logo">WorkEase</h1>
    <div class="form-container">
      <section class="alert">
        <?= $erro ?? '' ?>
      </section>
      <form action="processar_redefinicao.php" method="post">
        <!-- 1) Campo oculto para passar o token -->
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <!-- 2) Campo de nova senha com o name que o PHP espera -->
        <label for="nova_senha">Nova senha:</label>
        <div class="input-group password-wrapper">
          <input
            type="password"
            id="nova_senha"
            name="nova_senha"
            placeholder="Digite a nova senha"
            required
          >
          <span class="toggle-password"><i class="fas fa-eye"></i></span>
        </div>

        <button type="submit" class="btn btn-primary">Redefinir Senha</button>
      </form>
    </div>
  </div>

  <script>
    document.querySelectorAll('.toggle-password').forEach(item => {
      item.addEventListener('click', () => {
        const input = item.previousElementSibling;
        const icon = item.querySelector('i');
        if (input.type === "password") {
          input.type = "text";
          icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
          input.type = "password";
          icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
      });
    });
  </script>
</body>
</html>
