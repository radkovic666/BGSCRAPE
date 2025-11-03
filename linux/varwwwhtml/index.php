<?php
session_start();
require 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';
$showEmailModal = false;

// Get total registered users count
$totalUsers = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    $totalUsers = $result['count'];
} catch (Exception $e) {
    // Silently fail - we don't want to break the login page
    $totalUsers = "N/A";
}

// Get active viewers from sessions.json
$activeViewers = 0;
try {
    if (file_exists('sessions.json')) {
        $sessionsData = file_get_contents('sessions.json');
        $sessions = json_decode($sessionsData, true);
        if (is_array($sessions)) {
            $activeViewers = count($sessions);
        }
    }
} catch (Exception $e) {
    // Silently fail - we don't want to break the login page
    $activeViewers = "N/A";
}

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $username = trim($_POST['username']);
    
    if (!empty($username)) {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Check if user has an email
            if (empty($user['email'])) {
                // Show modal to collect email
                $showEmailModal = true;
                $_SESSION['reset_username'] = $username;
            } else {
                // Generate new password and send to email
                $newPassword = generateRandomPassword();
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Update password in database
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
                $stmt->execute([$hashedPassword, $username]);
                
                // Send email (you'll need to implement your email sending function)
                $emailSent = sendPasswordEmail($user['email'], $username, $newPassword);
                
                if ($emailSent) {
                    $success = "Нова парола беше изпратена на вашия имейл адрес.";
                } else {
                    $error = "Възникна грешка при изпращането на имейл. Моля, опитайте по-късно.";
                }
            }
        } else {
            $error = "Потребителското име не съществува.";
        }
    } else {
        $error = "Моля, въведете потребителско име.";
    }
}

// Handle email submission for users without email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_email'])) {
    $email = trim($_POST['email']);
    $username = $_SESSION['reset_username'];
    
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Update user's email in database
        $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE username = ?");
        $stmt->execute([$email, $username]);
        
        // Generate new password and send to email
        $newPassword = generateRandomPassword();
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password in database
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashedPassword, $username]);
        
        // Send email
        $emailSent = sendPasswordEmail($email, $username, $newPassword);
        
        if ($emailSent) {
            $success = "Нова парола беше изпратена на вашия имейл адрес.";
            unset($_SESSION['reset_username']);
        } else {
            $error = "Възникна грешка при изпращането на имейл. Моля, опитайте по-късно.";
        }
    } else {
        $error = "Моля, въведете валиден имейл адрес.";
    }
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['reset_password']) && !isset($_POST['submit_email'])) {
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
        $error = "Моля, попълнете всички полета!";
    }
}

// Function to generate random password (8 characters, letters and numbers only)
function generateRandomPassword() {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < 8; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

// Function to send password email using PHPMailer
function sendPasswordEmail($to, $username, $newPassword) {
    // Define the path to PHPMailer
    $phpmailerPath = '/home/bgtv/Desktop/html/PHPMailer/';
    
    // Check if PHPMailer files exist
    if (!file_exists($phpmailerPath . 'src/Exception.php') || 
        !file_exists($phpmailerPath . 'src/PHPMailer.php') || 
        !file_exists($phpmailerPath . 'src/SMTP.php')) {
        error_log("PHPMailer files not found at: $phpmailerPath");
        return false;
    }
    
    // Load PHPMailer classes
    require_once $phpmailerPath . 'src/Exception.php';
    require_once $phpmailerPath . 'src/PHPMailer.php';
    require_once $phpmailerPath . 'src/SMTP.php';
    
    // Create a new PHPMailer instance
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.abv.bg';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'nyamafun@abv.bg';
        $mail->Password   = 'H0rnbow12';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        
        // Set charset to support Bulgarian characters
        $mail->CharSet = 'UTF-8';
        
        // Recipients
        $mail->setFrom('nyamafun@abv.bg', 'Nyama Fun IPTV');
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Нова парола за вашия IPTV акаунт';
        $mail->Body    = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <title>Нова парола</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(to right, #6a11cb, #2575fc); color: white; padding: 20px; text-align: center; }
                    .content { background: #f9f9f9; padding: 20px; }
                    .password { font-size: 24px; font-weight: bold; color: #2575fc; text-align: center; margin: 20px 0; }
                    .footer { background: #ddd; padding: 10px; text-align: center; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Nyama Fun IPTV</h1>
                    </div>
                    <div class='content'>
                        <h2>Здравейте,</h2>
                        <p>Вашата нова парола за потребителско име <strong>$username</strong> е:</p>
                        <div class='password'>$newPassword</div>
                        <p>Моля, влезте в системата и сменете паролата си след като влезете.</p>
                        <p>Ако не сте поискали нова парола, моля свържете се с нас незабавно.</p>
                    </div>
                    <div class='footer'>
                        <p>Това е автоматично генерирано съобщение. Моля, не отговаряйте на този имейл.</p>
                        <p>© " . date('Y') . " Nyama Fun. Всички права запазени.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $mail->AltBody = "Здравейте,\n\nВашата нова парола за потребителско име '$username' е: $newPassword\n\nМоля, влезте в системата и сменете паролата си след като влезете.\n\nПоздрави,\nЕкипът на Nyama Fun";
        
        // Send the email
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $e->getMessage());
        return false;
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
        .terms-link {
            background-color: white;
            color: black !important;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .terms-link:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .user-stats {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 10px 0;
            flex-wrap: wrap;
        }
        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
            background: rgba(255, 255, 255, 0.1);
            padding: 5px 10px;
            border-radius: 15px;
        }
        .stat-icon {
            font-size: 14px;
        }
        .stat-value {
            font-weight: bold;
            color: #ffeb3b;
        }
        /* Buy Me a Coffee Widget positioning - Adjusted as requested */
        .bmc-container {
            position: fixed !important;
            bottom: 100px !important; /* Moved up from 80px */
            right: 10px !important;   /* Moved left from 30px */
            z-index: 1000 !important;
            transition: all 0.3s ease !important;
        }
        .bmc-container:hover {
            transform: translateY(-5px) !important;
        }
        /* Override any styles from the BMC script */
        #bmc-wbtn + div {
            position: fixed !important;
            bottom: 100px !important; /* Moved up from 80px */
            right: 10px !important;   /* Moved left from 30px */
            z-index: 1000 !important;
        }
        @media (max-width: 768px) {
            .bmc-container, #bmc-wbtn + div {
                bottom: 90px !important; /* Adjusted for mobile */
                right: 5px !important;   /* Adjusted for mobile */
            }
        }
        .forgot-password {
            display: block;
            text-align: right;
            margin-top: 5px;
            font-size: 0.9rem;
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
                            <?php if ($success): ?>
                                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                            <?php endif; ?>
                            <form method="post">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" name="username" id="username" placeholder="Username" required>
                                    <label for="username">Потребителско име</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                                    <label for="password">Парола</label>
                                    <a href="#" class="forgot-password" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Забравена парола?</a>
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
                    <div class="user-stats">
                        <div class="stat-item">
                            <span class="stat-icon"><i class="fas fa-users"></i></span>
                            <span>Регистрирани: </span>
                            <span class="stat-value"><?php echo $totalUsers; ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-icon"><i class="fas fa-eye"></i></span>
                            <span>Активни: </span>
                            <span class="stat-value"><?php echo $activeViewers; ?></span>
                        </div>
                    </div>
                    <div class="footer-bulgaria">България над всичко!</div>
                    <div class="footer-rights">Nyama Fun &copy; <?php echo date('Y'); ?> Всички права запазени</div>
                    <div class="mt-2">
                        <a href="faq.php" class="terms-link">Общи условия</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordModalLabel">Забравена парола</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" id="forgotPasswordForm">
                        <div class="mb-3">
                            <label for="reset_username" class="form-label">Потребителско име</label>
                            <input type="text" class="form-control" id="reset_username" name="username" required>
                            <div class="form-text">Въведете вашето потребителско име и ще ви изпратим нова парола на имейла, свързан с акаунта.</div>
                        </div>
                        <button type="submit" name="reset_password" class="btn btn-primary">Изпрати нова парола</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Modal (shown when user doesn't have an email) -->
    <div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="emailModalLabel">Въведи имейл адрес</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" id="emailForm">
                        <div class="mb-3">
                            <label for="email" class="form-label">Имейл адрес</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="form-text">Моля, въведете вашия имейл адрес, за да получите нова парола.</div>
                        </div>
                        <button type="submit" name="submit_email" class="btn btn-primary">Изпрати нова парола</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Buy Me a Coffee Widget Container -->
    <div class="bmc-container">
        <script data-name="BMC-Widget" data-cfasync="false" src="https://cdnjs.buymeacoffee.com/1.0.0/widget.prod.min.js" data-id="nyamafun" data-description="Support me on Buy me a coffee!" data-message="Ако желаете можете да ме подкрепите с едно кафе :)" data-color="#5F7FFF" data-position="" data-x_margin="0" data-y_margin="0"></script>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($showEmailModal): ?>
            // Show email modal if needed
            document.addEventListener('DOMContentLoaded', function() {
                var emailModal = new bootstrap.Modal(document.getElementById('emailModal'));
                emailModal.show();
            });
        <?php endif; ?>
    </script>
</body>
</html>
