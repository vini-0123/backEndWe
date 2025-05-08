<?php
include_once './factory/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login-email'])) {
    $email = $_POST['login-email'];

    // 1) Procura o usuário
    $stmt = $mysqli->prepare("SELECT id FROM data_clients WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // 2) Gera token e salva
        $token = bin2hex(random_bytes(50));
        $expira_em = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt2 = $mysqli->prepare("
            INSERT INTO recuperacao_senhas (client_id, token, expira_em)
            VALUES (?, ?, ?)
        ");
        $stmt2->bind_param("sss", $user['id'], $token, $expira_em);
        $stmt2->execute();

        // 3) Exibe link para teste local
        $link = "http://localhost/dashboard/backEndWe/workease/redefinir_senha.php?token=$token";
        echo "Clique aqui para redefinir sua senha: <a href='$link'>$link</a>";
    } else {
        echo "<p style='color:red;'>E-mail não encontrado.</p>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR"> <!-- Changed lang to pt-BR -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkEase - Login</title> <!-- Improved Title -->
    <!-- === IMPORTANT: Add Font Awesome === -->
    <link rel="stylesheet" href= "css/style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- === IMPORTANT: Add Google Font (Poppins) === -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="screen login-screen">
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Voltar</a>
        <h1 class="logo">WorkEase</h1>
        <div class="form-container">
        <section style="background-color: var(--accent-gold); color: var(--dark-blue); border-radius: 5px;">
        <?php echo $erro ?? '' ?>
        </section>
            <form action="#" method="POST">
                Insira o email cadastrado:
                <div class="input-group">
                     <input type="email" id="login-email" name="login-email" placeholder="E-mail" required>
                 </div>
                <button type="submit" class="btn btn-primary">Login</button>
                </a>
            </form>
        </div>
    </div>

    <!-- === IMPORTANT: Add JS for Password Toggle (if needed) === -->
    <script>
        document.querySelectorAll('.toggle-password').forEach(item => {
            item.addEventListener('click', event => {
                const icon = item.querySelector('i');
                const input = item.previousElementSibling;
                if (input.type === "password") {
                    input.type = "text";
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = "password";
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    </script>

</body>
</html>