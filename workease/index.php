<?php
// --- Variáveis de Configuração e Conteúdo ---
$companyName = "WorkEase";
$pageTitle = "Sua Eficiência Vai Além!";
$contactPhone = "+55 (11) 99999-9999";
$contactEmail = "workease.contato@gmail.com";

// Texto "Sobre o Software" (pode vir de um banco de dados ou arquivo no futuro)
$aboutText = "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";

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

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

    <!-- CSS Embutido -->
    <style>
        /* --- Variáveis de Cor (Baseado na Imagem) --- */
        :root {
            --dark-blue: #0a192f;
            --medium-blue: #112240; /* Um tom ligeiramente mais claro para contraste */
            --light-text: #ccd6f6;
            --gray-text: #8892b0;
            --accent-gold: #d4b106;
            --accent-gold-hover: #e6c630;
            --white: #ffffff;
            --shadow-color: rgba(2, 12, 27, 0.7);
        }

        /* --- Reset Básico & Configurações Globais --- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
             scroll-behavior: smooth; /* Rolagem suave */
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--dark-blue);
            color: var(--light-text);
            line-height: 1.7;
            font-size: 16px; /* Base font size */
        }

        .container {
            max-width: 1140px;
            margin: 0 auto;
            padding: 0 25px;
        }

        h1, h2, h3 {
            color: var(--white);
            font-weight: 700;
            line-height: 1.3;
             margin-bottom: 0.75em;
        }

        h1 { font-size: clamp(2rem, 5vw, 3rem); } /* Responsivo */
        h2 { font-size: clamp(1.8rem, 4vw, 2.5rem); }
        h3 { font-size: 1.4rem; }

        p {
            color: var(--gray-text);
            margin-bottom: 1em;
        }
         p.lead { /* Para parágrafos de destaque */
             font-size: 1.15rem;
             color: var(--light-text);
         }


        a {
            color: var(--accent-gold);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        a:hover {
            color: var(--accent-gold-hover);
        }

        section {
            padding: 80px 0;
        }

        /* --- Header --- */
        header {
            background-color: rgba(10, 25, 47, 0.85); /* Fundo semi-transparente */
            padding: 15px 0;
            position: sticky; /* Fixa o header no topo */
            top: 0;
            z-index: 100;
            width: 100%;
            backdrop-filter: blur(10px); /* Efeito de vidro fosco */
            box-shadow: 0 2px 15px var(--shadow-color);
            transition: background-color 0.3s ease;
        }

        header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8em;
            font-weight: bold;
            color: var(--white);
            text-decoration: none;
        }

        nav ul {
            list-style: none;
            display: flex;
        }

        nav ul li {
            margin-left: 25px;
        }

        nav ul li a {
            color: var(--light-text);
            text-decoration: none;
            font-size: 0.95em;
            padding: 8px 0;
            position: relative;
             transition: color 0.3s ease;
        }

        nav ul li a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            display: block;
            margin-top: 4px;
            right: 0;
            background: var(--accent-gold);
            transition: width 0.3s ease;
        }
        nav ul li a:hover {
            color: var(--white);
        }
         nav ul li a:hover::after {
            width: 100%;
            left: 0;
            background-color: var(--accent-gold);
        }


        .user-actions a {
            color: var(--light-text);
            text-decoration: none;
            margin-left: 20px;
            font-size: 0.95em;
             padding: 8px 15px;
             border: 1px solid var(--gray-text);
             border-radius: 5px;
             transition: background-color 0.3s ease, border-color 0.3s ease;
        }
         .user-actions a:hover {
             background-color: var(--medium-blue);
             border-color: var(--light-text);
             color: var(--white);
         }
         .user-actions a:last-child { /* Botão Log in */
             border-color: var(--accent-gold);
             color: var(--accent-gold);
         }
         .user-actions a:last-child:hover {
             background-color: var(--accent-gold);
             color: var(--dark-blue);
             border-color: var(--accent-gold);
         }


        /* --- Hero Section 1 --- */
        .hero1 {
            padding: 100px 0 60px;
            text-align: center;
            background: linear-gradient(rgba(10, 25, 47, 0.1), var(--dark-blue)), url('...') no-repeat center center/cover; /* Adicione um fundo sutil se desejar */
        }

        .hero1 h1 {
            margin-bottom: 15px;
        }

        .hero1 p {
            font-size: 1.2em;
            color: var(--gray-text);
            max-width: 600px;
            margin: 0 auto;
        }

        /* --- About Section --- */
        .about {
            padding: 60px 0;
        }

        .about-content {
            background-color: var(--medium-blue);
            color: var(--light-text);
            padding: 50px;
            border-radius: 10px;
            max-width: 900px;
            margin: 30px auto;
            box-shadow: 0 5px 25px var(--shadow-color);
        }

        .about-content h2 {
            margin-bottom: 25px;
            text-align: center;
             color: var(--white);
        }

        .about-content p {
            font-size: 1.05em;
            line-height: 1.8;
            color: var(--light-text); /* Texto mais claro dentro da caixa */
            text-align: justify; /* Ou left, se preferir */
        }

        /* --- Hero Section 2 - CTA --- */
        .hero2-cta {
            padding: 100px 0;
            text-align: center;
            background-color: var(--medium-blue); /* Fundo contrastante */
        }

        .hero2-cta h2 {
            margin-bottom: 20px;
        }

        .hero2-cta p {
            font-size: 1.25em;
            margin-bottom: 40px;
            color: var(--light-text);
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-button {
            display: inline-block;
            background-color: var(--accent-gold);
            color: var(--dark-blue);
            padding: 18px 45px;
            font-size: 1.15em;
            font-weight: 500; /* Medium weight */
            text-decoration: none;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 15px rgba(212, 177, 6, 0.3);
        }

        .cta-button:hover {
            background-color: var(--accent-gold-hover);
            transform: translateY(-3px); /* Efeito de levantar */
            box-shadow: 0 6px 20px rgba(212, 177, 6, 0.4);
        }

        /* --- Features Section --- */
        .features {
            padding: 80px 0;
        }
         .features h2 { /* Título opcional para a seção */
             text-align: center;
             margin-bottom: 60px;
             color: var(--white);
         }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Responsivo */
            gap: 40px;
            max-width: 1000px; /* Ajuste conforme necessário */
            margin: 0 auto;
        }

        .feature-item {
            background-color: var(--medium-blue);
            padding: 35px 30px;
            border-radius: 10px;
            display: flex;
            flex-direction: column; /* Empilhar ícone e texto */
            align-items: center; /* Centralizar conteúdo */
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 5px 20px var(--shadow-color);
        }
        .feature-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px var(--shadow-color);
        }

        .feature-item i {
            font-size: 2.8em;
            color: var(--accent-gold); /* Ícones dourados */
            margin-bottom: 25px; /* Espaço entre ícone e texto */
            width: 60px;
            height: 60px;
            line-height: 60px; /* Centralizar verticalmente se necessário */
            text-align: center;
        }

        .feature-item h3 { /* Nome da feature */
            font-size: 1.3em;
            margin-bottom: 10px;
             font-weight: 500;
            color: var(--white);
        }


        /* --- Footer --- */
        footer {
            background-color: var(--medium-blue);
            padding: 50px 0;
            margin-top: 80px;
            border-top: 3px solid var(--accent-gold);
        }

        footer .container {
             text-align: center; /* Centralizar conteúdo do rodapé */
        }

        footer p {
            margin-bottom: 8px;
            font-size: 0.95em;
            color: var(--gray-text);
        }
         footer p:first-child { /* "Entre em contato conosco" */
            font-weight: 500;
             color: var(--light-text);
             margin-bottom: 15px;
             font-size: 1.1em;
         }
         footer a { /* Estilizar links no footer se houver */
             color: var(--light-text);
         }
         footer a:hover {
             color: var(--accent-gold);
         }

        /* --- Botão Flutuante "Voltar ao Topo" --- */
        #backToTopBtn {
            display: none; /* Oculto por padrão */
            position: fixed;
            bottom: 25px;
            right: 25px;
            z-index: 999;
            border: none;
            outline: none;
            background-color: var(--accent-gold);
            color: var(--dark-blue);
            cursor: pointer;
            padding: 0; /* Removido para controlar tamanho com width/height */
            border-radius: 50%; /* Círculo */
            font-size: 1.5rem; /* Tamanho do ícone/seta */
            width: 50px; /* Tamanho fixo */
            height: 50px; /* Tamanho fixo */
            box-shadow: 0 4px 10px rgba(212, 177, 6, 0.4);
            transition: background-color 0.3s ease, opacity 0.3s ease, visibility 0.3s ease;
            opacity: 0.8;
             line-height: 50px; /* Centralizar seta verticalmente */
             text-align: center; /* Centralizar seta horizontalmente */
        }

        #backToTopBtn:hover {
            background-color: var(--accent-gold-hover);
            opacity: 1;
        }


        /* --- Ajustes Responsivos --- */
        @media (max-width: 992px) {
            .features-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 30px;
            }
            h1 { font-size: 2.5rem; }
            h2 { font-size: 2rem; }
             header .container { /* Ajuste para manter logo e menu na mesma linha em tablet */
                 flex-direction: row; /* Mantém lado a lado */
                 flex-wrap: wrap; /* Permite quebrar linha se não couber */
                 justify-content: space-between;
             }
             nav { /* Garante que a navegação não empurre tudo */
                 flex-basis: auto; /* Tamanho automático */
                 margin-top: 10px; /* Espaço se quebrar linha */
             }
             .user-actions {
                  margin-top: 10px; /* Espaço se quebrar linha */
                  margin-left: 0; /* Remove margem extra */
             }
        }

        @media (max-width: 768px) {
            header .container {
                flex-direction: column;
                text-align: center;
            }
             nav {
                 margin-top: 15px;
             }
             nav ul {
                 justify-content: center;
                 padding-left: 0; /* Remover padding se houver */
                 flex-wrap: wrap; /* Permite que itens do menu quebrem linha */
             }
             nav ul li {
                 margin: 5px 12px; /* Ajuste margem para wrap */
             }
             .user-actions {
                 margin-top: 15px;
             }
             section {
                 padding: 60px 0;
             }
            .about-content { padding: 30px; }
             .features-grid {
                 grid-template-columns: 1fr; /* Uma coluna */
             }
             #backToTopBtn {
                 width: 45px;
                 height: 45px;
                 font-size: 1.3rem;
                 line-height: 45px;
                 bottom: 20px;
                 right: 20px;
             }
        }

         @media (max-width: 480px) {
             nav ul li { margin: 5px 8px; }
             nav ul li a { font-size: 0.9em; }
             .user-actions a { font-size: 0.9em; padding: 6px 10px;}
             h1 { font-size: 2rem; }
             h2 { font-size: 1.8rem; }
             .cta-button { padding: 15px 35px; font-size: 1.1em;}
             .about-content { padding: 25px 20px; }
             footer { padding: 40px 0; }
         }
    </style>

</head>
<body>

    <header>
        <div class="container">
            <a href="#" class="logo"><?= htmlspecialchars($companyName) ?></a>
            <nav>
                <ul>
                    <li><a href="#">Produtos</a></li>
                    <li><a href="#">Parcerias</a></li>
                    <li><a href="#contato">Contato</a></li>
                    <li><a href="#sobre">Sobre nós</a></li>
                </ul>
            </nav>
            <div class="user-actions">
                <a href="backEndWe/cadastro.php">Cadastrar</a>
                <a href="login.php">Log in</a>
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
                    <p><?= nl2br(htmlspecialchars($aboutText)) ?></p> <!-- nl2br opcional se o texto tiver quebras de linha -->
                </div>
            </div>
        </section>

        <section class="hero2-cta">
            <div class="container">
                <h2>Controle seu estoque de forma inteligente!</h2>
                <p class="lead">Gestão de estoque fácil, rápida e sem complicações. Visualize tudo em um só lugar.</p>
                <a href="#" class="cta-button">Começar já!</a>
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
        </div>
    </footer>

    <!-- Botão Flutuante -->
    <button id="backToTopBtn" title="Voltar ao topo"><i class="fas fa-arrow-up"></i></button>

    <!-- JavaScript Embutido -->
    <script>
        // --- Script para Botão "Voltar ao Topo" ---
        (function() { // IIFE para encapsular o escopo
            let mybutton = document.getElementById("backToTopBtn");

            function scrollFunction() {
              // Verifica se o elemento existe antes de tentar acessar style
              if (mybutton) {
                  // Verifica se document.body ou document.documentElement existe antes de ler scrollTop
                  const scrollTop = (document.documentElement && document.documentElement.scrollTop) || document.body.scrollTop;
                  if (scrollTop > 100) {
                    mybutton.style.display = "block";
                  } else {
                    mybutton.style.display = "none";
                  }
              }
            }

            // Mostra/Esconde botão no scroll
            window.onscroll = scrollFunction;

            // Ação de clique
            if (mybutton) {
                mybutton.addEventListener('click', function(){
                    window.scrollTo({top: 0, behavior: 'smooth'});
                });
            }
        })(); // Fim da IIFE
    </script>

</body>
</html>