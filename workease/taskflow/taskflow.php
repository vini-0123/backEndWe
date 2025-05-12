<?php
session_start();

// Verificar se usuário está logado
$isUserLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Redirecionar para login se não estiver logado
if (!$isUserLoggedIn) {
    header('Location: login.php');
    exit;
}

// No início do arquivo que está gerando o erro
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Variáveis de Configuração e Conteúdo ---
$companyName = "WorkEase";
$pageTitle = "Soluções Inteligentes para Sua Empresa"; // Título mais genérico para a landing page
$contactPhone = "+55 (11) 99999-9999";
$contactEmail = "contato@workease.com"; // Email genérico de contato

// Textos (podem vir de um banco de dados ou arquivo no futuro)
$heroHeadline = "Transforme sua Rotina com Automação Inteligente TaskFlow";
$heroSubheadline = "Otimize processos, reduza custos e impulsione a produtividade da sua equipe com nossas soluções de automação.";

$aboutTaskFlowText = "O TaskFlow é a nossa plataforma robusta de automação de sistemas, projetada para simplificar tarefas complexas e repetitivas. Desde a integração de diferentes softwares até a automação de fluxos de trabalho personalizados, o TaskFlow permite que sua empresa opere com máxima eficiência, liberando sua equipe para focar em atividades estratégicas que realmente agregam valor.";

$stockSystemHeadline = "Conheça Nosso Sistema de Estoque Inteligente";
$stockSystemText = "Além da automação, oferecemos um sistema de gerenciamento de estoque completo e intuitivo. Controle suas mercadorias, monitore níveis de estoque em tempo real, gerencie entradas e saídas, e obtenha relatórios precisos para tomar decisões mais assertivas. Simplifique sua logística e evite perdas!";
$stockSystemButtonLink = "dashboard.php"; // COLOQUE AQUI O LINK PARA SEU SISTEMA DE ESTOQUE

// Features do TaskFlow (Automação)
$taskflowFeatures = [
    ["icon" => "fas fa-cogs", "title" => "Automação de Processos", "description" => "Configure fluxos de trabalho automatizados para tarefas manuais e repetitivas."],
    ["icon" => "fas fa-network-wired", "title" => "Integração de Sistemas", "description" => "Conecte diferentes softwares e plataformas para um fluxo de dados unificado."],
    ["icon" => "fas fa-robot", "title" => "Bots Personalizados", "description" => "Desenvolvemos bots para executar tarefas específicas conforme suas necessidades."],
    ["icon" => "fas fa-chart-pie", "title" => "Monitoramento e Relatórios", "description" => "Acompanhe o desempenho dos processos automatizados e gere insights valiosos."]
];

// Features do Sistema de Estoque (pode ser similar ao que já tínhamos)
$stockFeatures = [
    ["icon" => "fas fa-box-open", "title" => "Controle Preciso", "description" => "Gerenciamento detalhado de entradas, saídas e níveis de estoque."],
    ["icon" => "fas fa-bell", "title" => "Alertas Inteligentes", "description" => "Notificações para estoque baixo, garantindo reposição eficiente."],
    ["icon" => "fas fa-file-invoice-dollar", "title" => "Valorização de Estoque", "description" => "Cálculo automático do valor total dos seus produtos em estoque."],
    ["icon" => "fas fa-truck-loading", "title" => "Gestão de Movimentações", "description" => "Rastreamento completo de todas as movimentações de produtos."]
];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($companyName) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

    <style>
        :root {
            --dark-blue: #0a192f;
            --medium-blue: #112240;
            --light-blue-accent: #172a45; /* Um azul um pouco mais claro para cards e seções */
            --light-text: #ccd6f6;
            --gray-text: #8892b0;
            --accent-gold: #d4af37; /* Um dourado mais clássico, pode ajustar */
            --accent-gold-hover: #e6c040;
            --white: #ffffff;
            --shadow-color: rgba(2, 12, 27, 0.7);
            --primary-font: 'Poppins', sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: var(--primary-font);
            background-color: var(--dark-blue);
            color: var(--light-text);
            line-height: 1.7;
            font-size: 16px;
            -webkit-font-smoothing: antialiased; /* Melhora renderização de fontes */
            -moz-osx-font-smoothing: grayscale;
        }
        .container { max-width: 1170px; margin: 0 auto; padding: 0 20px; }

        h1, h2, h3, h4 { color: var(--white); font-weight: 600; line-height: 1.3; margin-bottom: 0.75em; }
        h1 { font-size: clamp(2.2rem, 5vw, 3.2rem); font-weight: 700;}
        h2 { font-size: clamp(1.8rem, 4vw, 2.6rem); margin-bottom: 1em;}
        h3 { font-size: clamp(1.3rem, 3vw, 1.6rem); font-weight: 500; }
        h4 { font-size: 1.15rem; font-weight: 500; color: var(--accent-gold); margin-bottom: 0.5em;}

        p { color: var(--gray-text); margin-bottom: 1.2em; font-size: 1rem;}
        p.lead { font-size: 1.15rem; color: var(--light-text); margin-bottom: 1.5em; max-width: 700px;}
        p.section-subtitle { text-align: center; max-width: 750px; margin-left: auto; margin-right: auto; margin-bottom: 50px; font-size: 1.1rem; color: var(--gray-text);}

        a { color: var(--accent-gold); text-decoration: none; transition: color 0.3s ease; }
        a:hover { color: var(--accent-gold-hover); }
        section { padding: 90px 0; }
        .text-center { text-align: center; }
        .mb-small { margin-bottom: 15px !important; }
        .mb-medium { margin-bottom: 30px !important; }

        /* --- Header --- */
        header {
            background-color: rgba(10, 25, 47, 0.85);
            padding: 18px 0; /* Aumentado padding */
            position: sticky; top: 0; z-index: 1000;
            width: 100%;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 15px var(--shadow-color);
        }
        header .container { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.9em; font-weight: 700; color: var(--white); letter-spacing: -1px;}
        nav ul { list-style: none; display: flex; }
        nav ul li { margin-left: 30px; }
        nav ul li a { color: var(--light-text); font-size: 0.9rem; font-weight: 500; padding: 8px 0; position: relative; transition: color 0.3s ease; text-transform: uppercase; letter-spacing: 0.5px; }
        nav ul li a::after { content: ''; position: absolute; width: 0; height: 2px; display: block; margin-top: 4px; right: 0; background: var(--accent-gold); transition: width 0.3s ease; }
        nav ul li a:hover { color: var(--white); }
        nav ul li a:hover::after, nav ul li a.active::after { width: 100%; left: 0; background-color: var(--accent-gold); }
        nav ul li a.active { color: var(--white); }

        .user-actions a {
            color: var(--light-text); margin-left: 15px; font-size: 0.9rem; font-weight: 500;
            padding: 9px 18px; border: 1px solid var(--gray-text); border-radius: 5px;
            transition: all 0.3s ease;
        }
        .user-actions a:hover { background-color: var(--medium-blue); border-color: var(--light-text); color: var(--white); }
        .user-actions a.btn-login { border-color: var(--accent-gold); color: var(--accent-gold); }
        .user-actions a.btn-login:hover { background-color: var(--accent-gold); color: var(--dark-blue); }

        /* --- Hero Section --- */
        .hero {
            padding: 120px 0 100px;
            /* background: linear-gradient(to bottom, var(--dark-blue) 0%, var(--medium-blue) 100%); */
            /* Imagem de fundo sutil - substitua 'path/to/your/image.jpg' */
            background-image: linear-gradient(rgba(10, 25, 47, 0.9), rgba(10, 25, 47, 0.95)), url('https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mnx8Y29tcHV0ZXIlMjBkYXRhfGVufDB8fDB8fA%3D%3D&auto=format&fit=crop&w=800&q=60');
            background-size: cover;
            background-position: center;
            background-attachment: fixed; /* Efeito Parallax */
        }
        .hero .container { text-align: center; }
        .hero h1 { color: var(--white); margin-bottom: 20px; }
        .hero p.lead { max-width: 750px; margin-left: auto; margin-right: auto; color: var(--light-text); }
        .cta-button {
            display: inline-block; background-color: var(--accent-gold); color: var(--dark-blue);
            padding: 16px 40px; font-size: 1.05rem; font-weight: 600; text-decoration: none;
            border-radius: 6px; cursor: pointer; transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(212, 177, 55, 0.25); margin-top: 20px;
        }
        .cta-button:hover { background-color: var(--accent-gold-hover); transform: translateY(-3px); box-shadow: 0 6px 20px rgba(212, 177, 55, 0.35); }
        .cta-button i { margin-right: 8px; }

        /* --- Section: Services (TaskFlow) --- */
        #servicos { background-color: var(--medium-blue); }
        .services-intro { margin-bottom: 60px; text-align: center; }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
            gap: 35px;
        }
        .feature-item {
            background-color: var(--light-blue-accent);
            padding: 35px 30px; border-radius: 8px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 5px 20px rgba(2,12,27,0.5);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .feature-item:hover { transform: translateY(-8px); box-shadow: 0 10px 30px rgba(2,12,27,0.6); }
        .feature-item .icon-container {
            font-size: 2.5em; color: var(--accent-gold); margin-bottom: 25px;
            width: 70px; height: 70px; line-height: 70px; text-align: center;
            background-color: rgba(212, 175, 55, 0.1); /* Fundo sutil para o ícone */
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }
        .feature-item h3 { font-size: 1.25rem; margin-bottom: 10px; color: var(--white); font-weight: 500;}
        .feature-item p { font-size: 0.95rem; color: var(--gray-text); line-height: 1.6; }

        /* --- Section: Our Stock System --- */
        #sistema-estoque { background-color: var(--dark-blue); /* Ou var(--medium-blue) se preferir */ }
        .stock-system-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
        }
        .stock-system-image img {
            width: 100%;
            max-width: 500px; /* Limita o tamanho da imagem */
            border-radius: 10px;
            box-shadow: 0 10px 30px var(--shadow-color);
            /* Imagem de placeholder - substitua */
            object-fit: cover; /* Garante que a imagem cubra a área sem distorcer */
            aspect-ratio: 16/10; /* Proporção da imagem */
        }
        .stock-system-details h2 { margin-bottom: 20px; }
        .stock-system-details p { margin-bottom: 25px; font-size: 1.05rem; }
        .stock-system-details ul { list-style: none; padding-left: 0; margin-bottom: 30px; }
        .stock-system-details ul li {
            font-size: 1rem; color: var(--light-text); margin-bottom: 12px;
            display: flex; align-items: flex-start;
        }
        .stock-system-details ul li i {
            color: var(--accent-gold); margin-right: 12px; font-size: 1.1rem;
            margin-top: 4px; /* Alinhamento vertical do ícone */
        }


        /* --- Footer --- */
        footer {
            background-color: var(--light-blue-accent);
            padding: 60px 0 40px; margin-top: 0; /* Removido margin-top anterior */
            border-top: 4px solid var(--accent-gold);
            text-align: center;
        }
        footer .footer-logo { font-size: 1.7em; font-weight: 700; color: var(--white); margin-bottom: 20px; display: block;}
        footer p { margin-bottom: 10px; font-size: 0.9rem; color: var(--gray-text); }
        footer p.contact-info i { margin-right: 8px; }
        footer .social-icons { margin-top: 20px; margin-bottom: 25px; }
        footer .social-icons a {
            color: var(--gray-text); font-size: 1.5rem; margin: 0 12px;
            transition: color 0.3s ease, transform 0.3s ease;
        }
        footer .social-icons a:hover { color: var(--accent-gold); transform: translateY(-3px); }
        footer .copyright { font-size: 0.85rem; color: var(--gray-text); margin-top: 30px; border-top: 1px solid var(--medium-blue); padding-top: 25px;}

        /* --- Botão Flutuante "Voltar ao Topo" --- */
        #backToTopBtn {
            display: none; position: fixed; bottom: 25px; right: 25px; z-index: 999;
            border: none; outline: none; background-color: var(--accent-gold); color: var(--dark-blue);
            cursor: pointer; padding: 0; border-radius: 50%; font-size: 1.5rem;
            width: 50px; height: 50px; box-shadow: 0 4px 12px rgba(212, 177, 55, 0.3);
            transition: all 0.3s ease; opacity: 0.9;
            line-height: 50px; text-align: center;
        }
        #backToTopBtn:hover { background-color: var(--accent-gold-hover); opacity: 1; transform: scale(1.1); }

        /* --- Ajustes Responsivos --- */
        @media (max-width: 992px) {
            .stock-system-content { grid-template-columns: 1fr; text-align: center; }
            .stock-system-image { margin-bottom: 40px; }
            .stock-system-image img { margin-left: auto; margin-right: auto; display: block; }
            .stock-system-details ul { display: inline-block; text-align: left; } /* Para centralizar a lista */
            .hero p.lead { max-width: 90%; }
            nav ul li { margin-left: 20px; }
        }

        @media (max-width: 768px) {
            header .container { flex-direction: column; }
            nav { margin-top: 15px; width: 100%; }
            nav ul { justify-content: center; flex-wrap: wrap; }
            nav ul li { margin: 5px 10px; }
            .user-actions { margin-top: 15px; }
            section { padding: 70px 0; }
            h1 { font-size: 2rem; }
            h2 { font-size: 1.7rem; }
            .cta-button { padding: 14px 35px; font-size: 1rem; }
            .features-grid { grid-template-columns: 1fr; gap: 30px; }
            .feature-item { padding: 30px 25px; }
            #backToTopBtn { width: 45px; height: 45px; font-size: 1.3rem; line-height: 45px; bottom: 20px; right: 20px; }
            p.section-subtitle { margin-bottom: 40px; font-size: 1rem; }
        }
         @media (max-width: 480px) {
             nav ul li { margin: 5px 8px; }
             nav ul li a { font-size: 0.85rem; }
             .user-actions a { font-size: 0.85rem; padding: 8px 12px;}
             h1 { font-size: 1.8rem; }
             h2 { font-size: 1.6rem; }
             .cta-button { padding: 12px 30px; font-size: 0.95rem;}
             footer { padding: 50px 0 30px; }
             footer .social-icons a { font-size: 1.3rem; margin: 0 10px;}
         }
    </style>
</head>
<body>

    <header>
        <div class="container">
            <a href="#" class="logo"><?= htmlspecialchars($companyName) ?></a>
            <nav>
                <ul>
                    <?php if ($isUserLoggedIn): ?>
                        <?php if ($_SESSION['nivel_acesso'] === 'admin'): ?>
                            <li><a href="categorias.php">Categorias</a></li>
                            <li><a href="movimentacoes.php">Movimentações</a></li>
                            <li><a href="relatorios.php">Relatórios</a></li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li><a href="#funcionalidades">Funcionalidades</a></li>
                    <?php endif; ?>
                    <li><a href="#sobre">Sobre nós</a></li>
                    <li><a href="#contato">Contato</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section id="hero" class="hero">
            <div class="container">
                <h1><?= htmlspecialchars($heroHeadline) ?></h1>
                <p class="lead"><?= htmlspecialchars($heroSubheadline) ?></p>
                <a href="#servicos" class="cta-button"><i class="fas fa-arrow-down"></i> Descubra Como</a>
            </div>
        </section>

        <section id="servicos">
            <div class="container">
                <div class="services-intro text-center">
                    <h2>Potencialize seu Negócio com Automação TaskFlow</h2>
                    <p class="section-subtitle"><?= nl2br(htmlspecialchars($aboutTaskFlowText)) ?></p>
                </div>

                <div class="features-grid">
                    <?php foreach ($taskflowFeatures as $feature): ?>
                    <div class="feature-item">
                        <div class="icon-container"><i class="<?= htmlspecialchars($feature['icon']) ?>"></i></div>
                        <h3><?= htmlspecialchars($feature['title']) ?></h3>
                        <p><?= htmlspecialchars($feature['description']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section id="sistema-estoque">
            <div class="container">
                 <div class="text-center mb-medium">
                    <h2><?= htmlspecialchars($stockSystemHeadline) ?></h2>
                </div>
                <div class="stock-system-content">
                    <div class="stock-system-image">
                        <!-- Substitua pela URL da sua imagem ou use um placeholder -->
                        <img src="https://images.unsplash.com/photo-1586528116311-069241122651?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTB8fHdhcmVob3VzZSUyMHNvZnR3YXJlfGVufDB8fDB8fA%3D%3D&auto=format&fit=crop&w=600&q=60" alt="Interface do Sistema de Estoque WorkEase">
                    </div>
                    <div class="stock-system-details">
                        <p class="lead" style="max-width:100%;"><?= htmlspecialchars($stockSystemText) ?></p>
                        <h4>Principais Funcionalidades:</h4>
                        <ul>
                            <?php foreach ($stockFeatures as $feature): ?>
                            <li><i class="<?= htmlspecialchars($feature['icon']) ?>"></i> <?= htmlspecialchars($feature['title']) ?>: <?= htmlspecialchars($feature['description']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="index.php"<?= htmlspecialchars($stockSystemButtonLink) ?>" class="cta-button" target="_blank">
                            <i class="fas fa-dolly-flatbed"></i> Acessar Sistema de Estoque
                        </a>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <footer id="contato">
        <div class="container">
            <a href="#" class="footer-logo"><?= htmlspecialchars($companyName) ?></a>
            <p class="mb-small">Soluções inteligentes para otimizar sua gestão.</p>
            <p class="contact-info"><i class="fas fa-phone"></i> <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $contactPhone)) ?>"><?= htmlspecialchars($contactPhone) ?></a>
                <span style="margin: 0 10px;">|</span>
                <i class="fas fa-envelope"></i> <a href="mailto:<?= htmlspecialchars($contactEmail) ?>"><?= htmlspecialchars($contactEmail) ?></a>
            </p>
            <div class="social-icons">
                <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
            </div>
            <p class="copyright">© <?= date("Y") ?> <?= htmlspecialchars($companyName) ?>. Todos os direitos reservados.</p>
        </div>
    </footer>

    <button id="backToTopBtn" title="Voltar ao topo"><i class="fas fa-arrow-up"></i></button>

    <script>
        // --- Script para Botão "Voltar ao Topo" ---
        (function() {
            let mybutton = document.getElementById("backToTopBtn");
            function scrollFunction() {
              if (mybutton) {
                  const scrollTop = (document.documentElement && document.documentElement.scrollTop) || document.body.scrollTop;
                  mybutton.style.display = (scrollTop > 100) ? "block" : "none";
              }
            }
            window.onscroll = scrollFunction;
            if (mybutton) {
                mybutton.addEventListener('click', function(){ window.scrollTo({top: 0, behavior: 'smooth'}); });
            }
        })();

        // --- Script para Navegação Ativa ---
        (function() {
            const sections = document.querySelectorAll("section[id]");
            const navLi = document.querySelectorAll("header nav ul li a");

            window.addEventListener("scroll", () => {
                let current = "";
                sections.forEach((section) => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.clientHeight;
                    // Ajuste o offset -150 para ativar um pouco antes de chegar no topo da seção
                    if (pageYOffset >= (sectionTop - sectionHeight / 3 - 150)) {
                         current = section.getAttribute("id");
                    }
                });

                navLi.forEach((a) => {
                    a.classList.remove("active");
                    if (a.getAttribute("href").includes(current)) {
                        a.classList.add("active");
                    }
                });
                // Caso especial para o topo da página (seção hero)
                if (pageYOffset < sections[0].offsetTop -150 && sections[0].id === current ) {
                     navLi.forEach(a => a.classList.remove("active"));
                     const homeLink = document.querySelector("header nav ul li a[href='#hero']");
                     if(homeLink) homeLink.classList.add("active");
                } else if (!current && pageYOffset < 200){ // Se estiver bem no topo e nenhuma seção ativa
                     navLi.forEach(a => a.classList.remove("active"));
                     const homeLink = document.querySelector("header nav ul li a[href='#hero']");
                     if(homeLink) homeLink.classList.add("active");
                }
            });
        })();
    </script>

</body>
</html>