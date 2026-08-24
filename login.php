<?php
require_once 'bootstrap.php';
session_start();
include 'config/db.php';

use Nithish\Medibook\Auth\JwtHandler;

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: /medibook/index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $email  = mysqli_real_escape_string($conn, $email);
        $result = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");

        if (mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']       = $user['id'];
                $_SESSION['user_name']     = $user['name'];
                $_SESSION['is_subscribed'] = $user['is_subscribed'];

                // Generate JWT and set as httpOnly cookie
                $jwtHandler = new JwtHandler();
                $token = $jwtHandler->generateToken($user['id'], $user['email']);

                setcookie('auth_token', $token, [
                    'expires'  => time() + 3600,
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);

                // Redirect to intended page or dashboard
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '/medibook/index.php';
                header("Location: $redirect");
                exit;
            } else {
                $error = 'Incorrect password. Please try again.';
            }
        } else {
            $error = 'No account found with that email.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In — MediBook</title>
    <link rel="stylesheet" href="/medibook/assets/css/style.css">
</head>
<body>

<div class="auth-page">
    <div class="auth-card">

        <!-- Logo -->
        <div class="auth-logo">
            <a href="/medibook/index.php" class="logo">
                <div class="logo-mark">M</div>
                MediBook
            </a>
        </div>

        <h1 class="auth-title">Welcome back</h1>
        <p class="auth-sub">Log in to your MediBook account</p>

        <!-- Error -->
        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email address</label>
                <input
                    type="email"
                    name="email"
                    class="form-input"
                    placeholder="you@example.com"
                    value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input
                    type="password"
                    name="password"
                    class="form-input"
                    placeholder="Enter your password"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary btn-full" style="margin-top: 8px;">
                Log in
            </button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="/medibook/signup.php">Sign up</a>
        </div>

    </div>
</div>

</body>
</html>
