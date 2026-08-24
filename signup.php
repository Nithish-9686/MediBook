<?php
session_start();
include 'config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: /medibook/index.php");
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $email_safe = mysqli_real_escape_string($conn, $email);

        // Check if email already exists
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email_safe'");

        if (mysqli_num_rows($check) > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $name_safe = mysqli_real_escape_string($conn, $name);
            $hashed    = password_hash($password, PASSWORD_DEFAULT);

            mysqli_query($conn, "
                INSERT INTO users (name, email, password)
                VALUES ('$name_safe', '$email_safe', '$hashed')
            ");

            // Auto login after signup
            $new_id   = mysqli_insert_id($conn);
            $_SESSION['user_id']       = $new_id;
            $_SESSION['user_name']     = $name;
            $_SESSION['is_subscribed'] = 0;

            header("Location: /medibook/index.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up — MediBook</title>
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

        <h1 class="auth-title">Create an account</h1>
        <p class="auth-sub">Start finding top doctors today</p>

        <!-- Error -->
        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Full name</label>
                <input
                    type="text"
                    name="name"
                    class="form-input"
                    placeholder="Your full name"
                    value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label class="form-label">Email address</label>
                <input
                    type="email"
                    name="email"
                    class="form-input"
                    placeholder="you@example.com"
                    value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input
                    type="password"
                    name="password"
                    class="form-input"
                    placeholder="Minimum 6 characters"
                    required
                >
                <p class="form-hint">At least 6 characters</p>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm password</label>
                <input
                    type="password"
                    name="confirm"
                    class="form-input"
                    placeholder="Repeat your password"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary btn-full" style="margin-top: 8px;">
                Create account
            </button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="/medibook/login.php">Log in</a>
        </div>

    </div>
</div>

</body>
</html>
