<?php
session_start();
require 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$ip = $_SERVER['REMOTE_ADDR'];

// Check IP restriction
$stmt = $pdo->prepare("SELECT id FROM users WHERE ip_address = ?");
$stmt->execute([$ip]);
if ($stmt->rowCount() > 0) {
    $error = "Вече си регистриран на това устройство! ";
}

// Rate limiting - check if this IP has made too many registration attempts
$stmt = $pdo->prepare("SELECT COUNT(*) as attempt_count, MAX(attempt_time) as last_attempt 
                       FROM registration_attempts 
                       WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$stmt->execute([$ip]);
$rateData = $stmt->fetch();

if ($rateData && $rateData['attempt_count'] >= 5) {
    $error = "Твърде много опити за регистрация. Моля, опитайте отново след 1 час.";
}

// Generate a simple math question for bot protection
$num1 = rand(1, 10);
$num2 = rand(1, 10);
$mathAnswer = $num1 + $num2;
$_SESSION['math_answer'] = $mathAnswer; // Store in session for validation

if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify honeypot field
    if (!empty($_POST['honeypot'])) {
        $error = "Неуспешен опит за регистрация.";
        // Log this attempt as suspicious
        $stmt = $pdo->prepare("INSERT INTO registration_attempts (ip_address, is_suspicious) VALUES (?, 1)");
        $stmt->execute([$ip]);
    } else {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $confirm = trim($_POST['confirm_password']);
        $userMathAnswer = (int)$_POST['math_answer'];
        
        // Log the registration attempt
        $stmt = $pdo->prepare("INSERT INTO registration_attempts (ip_address) VALUES (?)");
        $stmt->execute([$ip]);

        // Validation
        $errors = [];
        if (empty($username)) $errors[] = "Потребителското име е задължително";
        if (empty($password)) $errors[] = "Паролата е задължителна";
        if ($password !== $confirm) $errors[] = "Паролите не съвпадат";
        if (strlen($password) < 8) $errors[] = "Паролата трябва да е поне 8 символа";
        if (!preg_match('/^[a-z0-9_]+$/i', $username)) $errors[] = "Потребителското име може да съдържа само букви, цифри и долна черта";
        if ($userMathAnswer !== $_SESSION['math_answer']) $errors[] = "Грешен отговор на математическия въпрос";
        
        // Check username length
        if (strlen($username) < 3) $errors[] = "Потребителското име трябва да е поне 3 символа";
        if (strlen($username) > 20) $errors[] = "Потребителското име не може да надвишава 20 символа";

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                // Create user
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, ip_address) VALUES (?, ?, ?)");
                $stmt->execute([$username, $hashed, $ip]);
                $user_id = $pdo->lastInsertId();
                
                // Generate Xtream code
                $xt_user = $username;
                $xt_pass = bin2hex(random_bytes(16)); // Secure random password
                
                $stmt = $pdo->prepare("INSERT INTO xtream_codes (user_id, xtream_username, xtream_password) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $xt_user, $xt_pass]);
                
                $pdo->commit();
                
                // Auto-login
                $_SESSION['user_id'] = $user_id;
                header("Location: dashboard.php");
                exit();
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = $e->getCode() == 23000 ? "Потребителското име вече съществува!" : "Грешка при регистрация: " . $e->getMessage();
            }
        } else {
            $error = implode("<br>", $errors);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .gradient-custom {
            background: #6a11cb;
            background: linear-gradient(to right, rgba(106,17,203,1), rgba(37,117,252,1));
        }
        .honeypot {
            position: absolute;
            left: -9999px;
        }
        .math-question {
            font-weight: bold;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
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
        .card {
            border-radius: 1rem;
        }
    </style>
</head>
<body class="gradient-custom vh-100">
    <div class="container py-5 h-100">
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
                        <h3 class="mb-4">Регистрирай акаунт</h3>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        <form method="post" id="registrationForm">
                            <!-- Honeypot field for bots -->
                            <div class="honeypot">
                                <label for="honeypot">Не попълвайте това поле</label>
                                <input type="text" id="honeypot" name="honeypot" tabindex="-1">
                            </div>
                            
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="username" id="username" 
                                       placeholder="Потребителско име" required pattern="[a-zA-Z0-9_]+" 
                                       minlength="3" maxlength="20">
                                <label for="username">Потребителско име</label>
                                <small class="form-text text-muted">Само букви, цифри и _ (3-20 символа)</small>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" name="password" id="password" 
                                       placeholder="Парола" required minlength="8">
                                <label for="password">Парола</label>
                                <small class="form-text text-muted">Минимум 8 символа</small>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" name="confirm_password" 
                                       id="confirm_password" placeholder="Потвърди паролата" required>
                                <label for="confirm_password">Потвърди паролата</label>
                            </div>
                            
                            <!-- Simple math question for bot protection -->
                            <div class="math-question">
                                <p>На колко е равно <?= $num1 ?> + <?= $num2 ?>?</p>
                                <input type="number" class="form-control" name="math_answer" id="math_answer" 
                                       required placeholder="Въведете отговора">
                            </div>
                            
                            <button type="submit" class="btn btn-success btn-lg btn-block w-100">Готово</button>
                        </form>
                        <hr class="my-4">
                        <p class="mb-0">Ако вече имаш регистрация <a href="index.php" class="text-success">логни се оттук</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Add a small delay to form submission to prevent rapid automated submissions
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            var submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Моля изчакайте...';
            
            // Small delay to allow the button state to update
            setTimeout(function() {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Готово';
            }, 2000);
        });
    </script>
</body>
</html>
