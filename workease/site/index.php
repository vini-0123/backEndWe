<?php
session_start();
error_log('Current Session (Landing Page): ' . print_r($_SESSION, true)); // Adicionado identificador

// --- Variáveis de Configuração e Conteúdo ---
$companyName = "WorkEase";
$pageTitle = "Sua Eficiência Vai Além!";
$contactPhone = "+55 (11) 99999-9999";
$contactEmail = "workease.contato@gmail.com";

// Texto "Sobre o Software" (pode vir de um banco de dados ou arquivo no futuro)
$aboutText = "Promovemos a organização eficiente do estoque por meio de estratégias que facilitam o controle e a disposição dos produtos.
Implementamos soluções de automação que reduzem falhas humanas, aumentam a produtividade e agilizam a reposição de
mercadorias. Através da otimização contínua das tarefas, garantimos processos mais ágeis, precisos e econômicos. Com isso,
proporcionamos uma gestão de estoque moderna, inteligente e voltada para melhores resultados.";

$isUserLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $isUserLoggedIn && isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Visitante';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($companyName) ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
        <link rel="stylesheet" href="../site/css/workease.css">
</head>
<body>
    <header>
        <div class="container">
            <a href="../site/index.php" class="logo"><?= htmlspecialchars($companyName) ?> <!-- Alterado href do logo para index.php da raiz -->
            <nav>
                <ul>
                    <?php if ($isUserLoggedIn): ?>
                        <li><a href="../taskflow/index.php">Taskflow</a></li> <!-- Link para o dashboard admin -->
                    <?php endif; ?>
                    <li><a href="#funcionalidades">Funcionalidades</a></li>
                    <li><a href="#contato">Contato</a></li>
                    <li><a href="#sobre">Sobre nós</a></li>
                    <li><a href="flowtaskladding.html">Conheça nosso software</a></li>
                </ul>
            </nav>
            <div class="user-actions">
                <?php if ($isUserLoggedIn): ?>
                    <span class="user-greeting">Olá, <?= $userName ?>!</span>
                    <a href="controle_acesso.php" class="settings-link" title="Configurações de Acesso"><i class="fas fa-cog"></i></a> 
                    <a href="../cadastrar_logar/logout.php" class="logout-btn">Sair</a>
                <?php else: ?>
                    <a href="../cadastrar_logar/cadastro.php" class="btn-cadastrar">Cadastrar</a>
                    <a href="../cadastrar_logar/login.php" class="btn-login">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main>
        <section class="hero1">
            <div class="container">
                <h1>Com a <?= htmlspecialchars($companyName) ?>, sua eficiência vai além!</h1>
                <p>Junte-se à melhor plataforma de tecnologia e otimize seus processos.</p>
            </div>
        </section>

        <section id="sobre" class="about">
            <div class="container">
                <div class="about-content">
                    <h2>Sobre o Software</h2>
                    <p><?= nl2br(htmlspecialchars($aboutText)) ?></p>
                </div>
            </div>
        </section>

        <section class="hero2-cta">
            <div class="container">
                <h2>Controle seu estoque de forma inteligente!</h2>
                <p class="lead">Gestão de estoque fácil, rápida e sem complicações. Visualize tudo em um só lugar.</p>
                <?php if ($isUserLoggedIn): ?>
                    <a href="taskflow/index.php" class="cta-button">Acessar Painel</a>
                <?php else: ?>
                    <a href="../cadastrar_logar/cadastro.php" class="cta-button">Começar já!</a>
                <?php endif; ?>
            </div>
        </section>

        <section id="funcionalidades" class="features">
             <div class="container">
                 <div class="features-grid">
                     <div class="feature-item">
                         <i class="fas fa-box-open"></i>
                         <h3>Controle de estoque<br> automatizado</h3>
                     </div>
                     <div class="feature-item">
                         <i class="fas fa-chart-line"></i>
                         <h3>Alertas de<br> reposição de produtos</h3>
                     </div>
                     <div class="feature-item">
                         <i class="fas fa-bell"></i>
                         <h3>Relatórios<br> inteligentes e detalhados</h3>
                     </div>
                     <div class="feature-item">
                         <i class="fas fa-desktop"></i>
                         <h3>Facilidade de<br> usabilidade</h3>
                     </div>
                 </div>
             </div>
         </section>
    </main>

    <footer id="contato">
        <div class="container">
            <p>Entre em contato conosco</p>
            <p><i class="fas fa-phone"></i> <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $contactPhone)) ?>"><?= htmlspecialchars($contactPhone) ?></a></p>
            <p><i class="fas fa-envelope"></i> <a href="mailto:<?= htmlspecialchars($contactEmail) ?>"><?= htmlspecialchars($contactEmail) ?></a></p>
            <p>© <?= date("Y") ?> <?= htmlspecialchars($companyName) ?>. Todos os direitos reservados.</p>
        </div>
    </footer>

    <!-- Botão Flutuante -->
    <button id="backToTopBtn" title="Voltar ao topo"><i class="fas fa-arrow-up"></i></button>

    <!-- JavaScript Embutido -->
    <script>
        (function() { 
            let mybutton = document.getElementById("backToTopBtn");

            function scrollFunction() {
              if (mybutton) {
                  const scrollTop = (document.documentElement && document.documentElement.scrollTop) || document.body.scrollTop;
                  if (scrollTop > 100) {
                    mybutton.style.display = "block";
                  } else {
                    mybutton.style.display = "none";
                  }
              }
            }
            window.onscroll = scrollFunction;
            if (mybutton) {
                mybutton.addEventListener('click', function(){
                    window.scrollTo({top: 0, behavior: 'smooth'});
                });
            }
        })();
    </script>

</body>
</html>