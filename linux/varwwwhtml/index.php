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
    <style>
        .gradient-custom {
            background: #6a11cb;
            background: linear-gradient(to right, rgba(106,17,203,1), rgba(37,117,252,1));
        }
        .card {
            border-radius: 1rem;
        }
    </style>
</head>
<body class="gradient-custom vh-100">
    <div class="container py-5 h-100">
        <div class="row d-flex justify-content-center align-items-center h-100">
            <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                <div class="card shadow-2-strong">
                    <div class="card-body p-5 text-center">
                        <h3 class="mb-4">Добре дошъл в Nyama Fun</h3>
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
</body>
</html>
