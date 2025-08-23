<?php
session_start();
require 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Невалидно потребителско име или парола!";
        }
    } else {
        $error = "Please fill in all fields!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPTV Login</title>
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
        /* Footer Styles */
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
    </style>
</head>
<body class="gradient-custom">
    <div class="wrapper">
        <div class="container py-5 flex-grow-1">
            <div class="row d-flex justify-content-center align-items-center h-100">
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">
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
                        <div class="card-body p-5 text-center">
                            <h3 class="mb-4">Добре дошъл</h3>
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>
                            <form method="post">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" name="username" id="username" placeholder="Username" required>
                                    <label for="username">Потребителско име</label>
                                </div>
                                <div class="form-floating mb-4">
                                    <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                                    <label for="password">Парола</label>
                                </div>
                                <button class="btn btn-primary btn-lg btn-block w-100" type="submit">Влез</button>
                            </form>
                            <hr class="my-4">
                            <p class="mb-0">Ако нямаш регистрация, <a href="register.php" class="text-primary">направи го оттук</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <footer class="footer mt-auto">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-logo">Проект реализиран от 3Design, Драгомир Димитров</div>
                    <div class="footer-bulgaria">България над всичко!</div>
                    <div class="footer-rights">Nyama Fun &copy; <?php echo date('Y'); ?> Всички права запазени</div>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
