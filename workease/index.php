<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkEase - Sua Eficiência Vai Além!</title>
    <!-- Link to Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Basic Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            background-color: #0a192f; /* Dark Blue Background */
            color: #ccd6f6; /* Light text color */
            line-height: 1.6;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        header {
            background-color: #0a192f; /* Same as body or slightly different if needed */
            padding: 20px 0;
            border-bottom: 1px solid #133050; /* Subtle border */
        }

        header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 2em;
            font-weight: bold;
            color: #ffffff; /* White logo */
            text-decoration: none;
        }

        nav ul {
            list-style: none;
            display: flex;
        }

        nav ul li {
            margin-left: 20px;
        }

        nav ul li a {
            color: #ccd6f6;
            text-decoration: none;
            font-size: 0.9em;
            padding: 5px 0;
            position: relative;
        }
         /* Simple hover effect for nav links */
        nav ul li a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            display: block;
            margin-top: 5px;
            right: 0;
            background: #ffffff;
            transition: width 0.3s ease;
            -webkit-transition: width 0.3s ease;
        }
         nav ul li a:hover::after {
            width: 100%;
            left: 0;
            background-color: #ffffff;
        }


        .user-actions a {
            color: #ccd6f6;
            text-decoration: none;
            margin-left: 15px;
            font-size: 0.9em;
        }

        /* Hero Section 1 */
        .hero1 {
            padding: 60px 0 40px;
            text-align: center;
        }

        .hero1 h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            color: #ffffff;
        }

        .hero1 p {
            font-size: 1.1em;
            color: #8892b0; /* Slightly dimmer text */
        }

        /* About Section */
        .about {
            padding: 40px 0;
        }

        .about-content {
            background-color: #6c757d; /* Gray background */
            color: #ffffff; /* White text inside the gray box */
            padding: 40px;
            border-radius: 8px;
            max-width: 900px; /* Limit width */
            margin: 20px auto; /* Center the box */
        }

        .about-content h2 {
            font-size: 1.8em;
            margin-bottom: 20px;
            color: #ffffff;
        }

        .about-content p {
            font-size: 1em;
            line-height: 1.7;
            color: #e6f1ff; /* Slightly lighter than default body text if needed */
        }

        /* Hero Section 2 - CTA */
        .hero2-cta {
            padding: 60px 0;
            text-align: center;
        }

        .hero2-cta h2 {
            font-size: 2.8em;
            margin-bottom: 15px;
            color: #ffffff;
        }

        .hero2-cta p {
            font-size: 1.2em;
            margin-bottom: 30px;
            color: #8892b0;
        }

        .cta-button {
            display: inline-block;
            background-color: #d4b106; /* Gold/Yellow color */
            color: #0a192f; /* Dark text on button */
            padding: 15px 35px;
            font-size: 1.1em;
            font-weight: bold;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .cta-button:hover {
            background-color: #e6c630; /* Lighter gold on hover */
        }

        /* Features Section */
        .features {
            padding: 50px 0;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr); /* 2 columns */
            gap: 40px 60px; /* Row and column gap */
            max-width: 900px;
            margin: 0 auto;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 20px; /* Space between icon and text */
        }

        .feature-item i {
            font-size: 2.5em; /* Icon size */
            color: #ffffff; /* Icon color */
            width: 50px; /* Fixed width for alignment */
            text-align: center;
        }

        .feature-item .feature-text h3 {
            font-size: 1.4em;
            margin-bottom: 5px;
            color: #ffffff;
        }
         /* In the image, feature text is multi-line, adjust styling */
         .feature-item .feature-text {
            font-size: 1.1em;
             line-height: 1.4;
             color: #ccd6f6;
         }


        /* Footer */
        footer {
            background-color: #0a192f; /* Match header/body or slightly different */
            padding: 40px 0;
            margin-top: 60px; /* Space above footer */
            border-top: 1px solid #133050; /* Subtle border */
        }

        footer .container {
             /* Can be centered or left-aligned */
             text-align: left; /* As per image */
             max-width: 900px; /* Match other content width */

        }

        footer p {
            margin-bottom: 5px;
            font-size: 0.9em;
            color: #8892b0;
        }
         footer p:first-child { /* "Entre em contato conosco" */
            font-weight: bold;
             color: #ccd6f6;
             margin-bottom: 10px;
         }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            header .container {
                flex-direction: column;
                text-align: center;
            }
             nav ul {
                margin-top: 15px;
                justify-content: center;
             }
             nav ul li {
                 margin: 0 10px;
             }
             .user-actions {
                 margin-top: 10px;
             }

             .hero1 h1 { font-size: 2em; }
             .hero2-cta h2 { font-size: 2.2em; }

             .features-grid {
                grid-template-columns: 1fr; /* Stack features vertically */
                gap: 30px;
             }
             .feature-item {
                 flex-direction: column; /* Stack icon above text */
                 text-align: center;
                 gap: 10px;
             }
             .feature-item i {
                 width: auto; /* Reset fixed width */
             }

             footer .container {
                 text-align: center;
             }
        }
         @media (max-width: 480px) {
             nav ul li { margin: 0 5px; }
             nav ul li a { font-size: 0.8em; }
             .user-actions a { font-size: 0.8em; }
             .hero1 h1 { font-size: 1.8em; }
             .hero1 p { font-size: 1em; }
             .hero2-cta h2 { font-size: 1.8em; }
             .hero2-cta p { font-size: 1em; }
             .cta-button { padding: 12px 25px; font-size: 1em;}
             .feature-item .feature-text { font-size: 1em; }
             .about-content { padding: 25px; }

         }

    </style>
</head>
<body>

    <header>
        <div class="container">
            <a href="#" class="logo">WorkEase</a>
            <nav>
                <ul>
                    <li><a href="#">Produtos</a></li>
                    <li><a href="#">Parcerias</a></li>
                    <li><a href="#">Contato</a></li>
                    <li><a href="#">Sobre nós</a></li>
                </ul>
            </nav>
            <div class="user-actions">
                <a href="#">Cadastrar</a>
                <a href="#">Log in</a>
            </div>
        </div>
    </header>

    <main>
        <section class="hero1">
            <div class="container">
                <h1>Com a WorkEase, sua eficiência vai além!</h1>
                <p>Junte-se a melhor plataforma de tecnologia!</p>
            </div>
        </section>

        <section class="about">
            <div class="container">
                <div class="about-content">
                    <h2>Sobre o software</h2>
                    <p>"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum."</p>
                </div>
            </div>
        </section>

        <section class="hero2-cta">
            <div class="container">
                <h2>Controle seu estoque<br> de forma inteligente!</h2>
                <p>Gestão de estoque fácil, rápida e sem complicações.</p>
                <a href="#" class="cta-button">Começar já!</a>
            </div>
        </section>

        <section class="features">
            <div class="container">
                <div class="features-grid">
                    <div class="feature-item">
                        <i class="fas fa-box-open"></i> <!-- Icon for stock control -->
                        <div class="feature-text">Controle de estoque<br> automatizado</div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-chart-line"></i> <!-- Icon for alerts/reporting -->
                        <div class="feature-text">Alertas de<br> reposição de produtos</div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-bell"></i> <!-- Icon for reports/notifications -->
                        <div class="feature-text">Relatórios<br> inteligentes e detalhados</div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-desktop"></i> <!-- Icon for usability/interface -->
                        <div class="feature-text">Facilidade de<br> usabilidade</div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>Entre em contato conosco</p>
            <p>+55 (11) 99999-9999</p>
            <p>workease@gmail.com</p>
        </div>
    </footer>

</body>
</html>