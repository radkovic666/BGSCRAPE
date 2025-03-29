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
    $error = "Only one registration allowed per IP address!";
}

if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm = trim($_POST['confirm_password']);

    // Validation
    $errors = [];
    if (empty($username)) $errors[] = "Username is required";
    if (empty($password)) $errors[] = "Password is required";
    if ($password !== $confirm) $errors[] = "Passwords do not match";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
    if (!preg_match('/^[a-z0-9_]+$/i', $username)) $errors[] = "Username can only contain letters, numbers and underscores";

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Create user
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, ip_address) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashed, $ip]);
            $user_id = $pdo->lastInsertId();
            
            // Generate Xtream code
            $xt_user = 'xt_' . bin2hex(random_bytes(4));
            $xt_pass = bin2hex(random_bytes(8));
            
            $stmt = $pdo->prepare("INSERT INTO xtream_codes (user_id, xtream_username, xtream_password) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $xt_user, $xt_pass]);
            
            $pdo->commit();
            
            // Auto-login
            $_SESSION['user_id'] = $user_id;
            header("Location: dashboard.php");
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = $e->getCode() == 23000 ? "Username already exists!" : "Registration error: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .gradient-custom {
            background: #6a11cb;
            background: linear-gradient(to right, rgba(106,17,203,1), rgba(37,117,252,1));
        }
    </style>
</head>
<body class="gradient-custom vh-100">
    <div class="container py-5 h-100">
        <div class="row d-flex justify-content-center align-items-center h-100">
            <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                <div class="card shadow-2-strong">
                    <div class="card-body p-5 text-center">
                        <h3 class="mb-4">Create Account</h3>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        <form method="post">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="username" id="username" 
                                       placeholder="Username" required pattern="[a-zA-Z0-9_]+">
                                <label for="username">Username</label>
                                <small class="form-text text-muted">Letters, numbers and underscores only</small>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" name="password" id="password" 
                                       placeholder="Password" required minlength="8">
                                <label for="password">Password</label>
                                <small class="form-text text-muted">Minimum 8 characters</small>
                            </div>
                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" name="confirm_password" 
                                       id="confirm_password" placeholder="Confirm Password" required>
                                <label for="confirm_password">Confirm Password</label>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg btn-block w-100">Create Account</button>
                        </form>
                        <hr class="my-4">
                        <p class="mb-0">Already have an account? <a href="index.php" class="text-success">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>