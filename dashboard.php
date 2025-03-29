<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get user data
$stmt = $pdo->prepare("SELECT users.username, xtream_codes.* 
                      FROM users 
                      JOIN xtream_codes ON users.id = xtream_codes.user_id 
                      WHERE users.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$data = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Welcome, <?= htmlspecialchars($data['username']) ?>!</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h5 class="alert-heading">Your Xtream Codes</h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Username:</strong>
                                    <div class="font-monospace"><?= htmlspecialchars($data['xtream_username']) ?></div>
                                </div>
                                <div class="col-md-6">
                                    <strong>Password:</strong>
                                    <div class="font-monospace"><?= htmlspecialchars($data['xtream_password']) ?></div>
                                </div>
                            </div>
                        </div>
                        <a href="logout.php" class="btn btn-danger">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>