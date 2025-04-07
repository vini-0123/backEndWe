<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/style.css"> 
        <!-- === IMPORTANT: Add Font Awesome === -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <!-- === IMPORTANT: Add Google Font (Poppins) === -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Tela de Cadastro (Sign Up) -->
    <div class="screen signup-screen">
        <a href="http://localhost:8080/project/backEndWe/workease/index.php" class="back-link"><i class="fas fa-arrow-left"></i> Voltar</a>
        <h1 class="logo">WorkEase</h1>

        <div class="form-container">
            <form action="#">
                <h2>Cadastre-se</h2>

                <div class="input-group">
                    <input type="text" id="company-name" name="company-name" placeholder="Nome da empresa" required>
                </div>

                <div class="input-group">
                    <input type="email" id="signup-email" name="signup-email" placeholder="Email" required>
                </div>

                <div class="input-group password-wrapper">
                    <input type="password" id="signup-password" name="signup-password" placeholder="Senha" required>
                    <span class="toggle-password"><i class="fas fa-eye"></i></span>
                </div>

                <button type="submit" class="btn btn-primary">Cadastrar</button>

                <p class="separator">ou</p>

                <a href="#linkedin-login" class="btn btn-social">
                    Cadastrar com <i class="fab fa-linkedin"></i>
                </a>
            </form>
        </div>
    </div>

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