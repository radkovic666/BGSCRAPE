<?php
session_start();
require 'config.php';
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Общи условия - Nyama Fun</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-custom {
            background: #6a11cb;
            background: linear-gradient(to right, rgba(106,17,203,1), rgba(37,117,252,1));
        }
        .card {
            border-radius: 1rem;
        }
        .ascii-art {
            font-family: monospace;
            white-space: pre;
            color: white;
            text-align: center;
            font-size: 10px;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            letter-spacing: -0.5px;
            text-shadow: 0 0 10px rgba(255,255,255,0.3);
        }
        @media (min-width: 768px) {
            .ascii-art {
                font-size: 12px;
            }
        }
        .footer {
            background: rgba(0, 0, 0, 0.2);
            color: white;
            padding: 15px 0;
            margin-top: auto;
            backdrop-filter: blur(5px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        .footer-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .footer-logo {
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 5px;
            color: #fff;
            text-shadow: 0 0 5px rgba(255, 255, 255, 0.5);
        }
        .footer-text {
            font-size: 0.9rem;
            margin-bottom: 3px;
        }
        .footer-bulgaria {
            font-weight: bold;
            color: #ffeb3b;
            text-shadow: 0 0 3px rgba(255, 235, 59, 0.5);
        }
        .footer-rights {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        .wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .terms-content {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            color: #333;
        }
        .terms-content h2 {
            color: #6a11cb;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid #6a11cb;
            padding-bottom: 0.5rem;
        }
        .terms-content h4 {
            color: #6a11cb;
            margin-top: 1.5rem;
        }
        .terms-content ul {
            padding-left: 1.5rem;
        }
        .terms-content li {
            margin-bottom: 0.5rem;
        }
        .back-btn {
            background: #6a11cb;
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }
        .back-btn:hover {
            background: #4d0da5;
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="gradient-custom">
    <div class="wrapper">
        <div class="container py-5 flex-grow-1">
            <div class="row d-flex justify-content-center">
                <div class="col-12 col-md-10 col-lg-8">
                    <div class="text-center mb-4">
                        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left me-2"></i>Назад</a>
                    </div>
                    
                    <div class="ascii-art">
    )      )            *              (              ) 
 ( /(   ( /(   (      (  `     (       )\ )        ( /( 
 )\())  )\())  )\     )\))(    )\     (()/(    (   )\())
((_)\  ((_)\((((_)(  ((_)()\((((_)(    /(_))   )\ ((_)\ 
 _((_)__ ((_))\ _ )\ (_()((_))\ _ )\  (_))_|_ ((_) _((_)
| \| |\ \ / /(_)_\(_)|  \/  |(_)_\(_) | |_ | | | || \| |
| .` | \ V /  / _ \  | |\/| | / _ \   | __|| |_| || .` |
|_|\_|  |_|  /_/ \_\ |_|  |_|/_/ \_\  |_|   \___/ |_|\_|

Няма пълно щастие !
                    </div>
                    
                    <div class="card shadow-2-strong">
                        <div class="card-body p-0">
                            <div class="terms-content">
                                <h2 class="text-center mb-4">Общи условия за ползване</h2>
                                
                                <h4>Авторски права</h4>
                                <p><strong>nyama.fun</strong> не носи отговорност за съдържанието на телевизионните предавания, както и за използваните лога – правата върху тях принадлежат на съответните собственици. Сайтът е създаден с цел да обедини на едно място различни канали, които се излъчват от техните официални платформи, използвайки вградена технология.</p>
                                
                                <h4>Правна отговорност</h4>
                                <ul>
                                    <li><strong>nyama.fun</strong> не съхранява и не излъчва на своите сървъри видеа или предавания на живо. Всички потоци се зареждат чрез код за вграждане от външни сайтове – собственици или институции. Поради това <strong>nyama.fun</strong> не носи правна отговорност, ако дадено съдържание нарушава авторски права, закони, насърчава престъпления, нелоялна конкуренция или разпространение на нелегален софтуер. При установяване на подобно съдържание, то се премахва.</li>
                                    <li>Отговорност на потребителя е да осигури необходимото оборудване и услуги, за да достъпи сайта – интернет връзка, модем, хардуер, софтуер и други средства. Също така потребителят трябва да гарантира, че те са съвместими с предлаганите услуги.</li>
                                    <li>С влизането си в сайта, потребителят декларира пред <strong>nyama.fun</strong>, че е навършил 18 години и използва услугите като физическо лице. Декларира още, че е упълномощен да достъпва съдържанието и носи отговорност за избора и начина на употреба. В случай на нарушение на закона, членството може да бъде прекратено.</li>
                                    <li><strong>nyama.fun</strong> не гарантира постоянна достъпност или качество на съдържанието. Ако даден поток бъде премахнат от сайта-доставчик или има проблем с хостинга, потребителят няма право да предявява претенции.</li>
                                    <li>Същото важи и при използване на софтуера на <strong>nyama.fun</strong> – компанията не гарантира постоянно и високо качество на стриймовете, тъй като това зависи от сървърите, на които са хоствани.</li>
                                </ul>
                                
                                <h4>Контакт и премахване на съдържание</h4>
                                <p>Ако сте носител на авторски права или считате, че даден линк в сайта нарушава вашите права, моля свържете се с нас
                                <strong><a href="mailto:nyamafun@abv.bg">по имейл</a></strong>. Ще разгледаме сигнала Ви и ще премахнем съдържанието в разумен срок.</p>
                                
                                <h4>Специални случаи</h4>
                                <p>В определени ситуации държавни органи могат да поискат информация за потребители. Ако <strong>nyama.fun</strong> счете за необходимо, може да предостави такава информация без предварително съгласие, например когато:</p>
                                <ol>
                                    <li>това е задължително по закон;</li>
                                    <li>намалява потенциална правна отговорност;</li>
                                    <li>защитава правата и интересите на <strong>nyama.fun</strong>.</li>
                                </ol>
                                
                                <h4>Актуализации</h4>
                                <p>Условията могат да бъдат променяни и обновявани от екипа на <strong>nyama.fun</strong>. При всяко посещение на сайта или използване на софтуера се приема, че потребителят е съгласен с актуалната версия на договора.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <footer class="footer mt-auto">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-bulgaria">България над всичко!</div>
                    <div class="footer-rights">
                        Nyama Fun &copy; <?php echo date('Y'); ?> Всички права запазени 
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
