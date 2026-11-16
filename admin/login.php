<?php
require_once __DIR__ . '/auth.php';

if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, username, password_hash, name FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Regenerate session ID for security
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_name'] = $user['name'];

                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $error = 'Login error: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | The ALT HQ</title>
    <link rel="icon" href="../assets/seal.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #F8FAFC;
            color: #1E2229;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .login-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 59, 99, 0.08);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
        }
        .login-header {
            background: #FFFFFF;
            color: #1E2229;
            padding: 36px 32px 24px;
            text-align: center;
            border-bottom: 1px solid #E2E8F0;
        }
        .login-header img {
            height: 44px;
            width: auto;
            margin-bottom: 14px;
        }
        .login-header h1 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 22px;
            margin-bottom: 6px;
            color: #005B94;
        }
        .login-header p {
            font-size: 14px;
            color: #64748B;
        }
        .login-body {
            padding: 32px;
        }
        .alert-error {
            background: #FEE2E2;
            border: 1px solid #F87171;
            color: #B91C1C;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #1E2229;
            margin-bottom: 8px;
        }
        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-wrapper i {
            position: absolute;
            left: 14px;
            color: #94A3B8;
            font-size: 15px;
        }
        .input-wrapper input {
            width: 100%;
            height: 48px;
            padding: 0 16px 0 42px;
            border: 1px solid #CBD5E1;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            color: #1E2229;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-wrapper input:focus {
            outline: none;
            border-color: #005B94;
            box-shadow: 0 0 0 3px rgba(0, 91, 148, 0.15);
        }
        .btn-submit {
            width: 100%;
            height: 50px;
            border: none;
            border-radius: 999px;
            background: #FFD200;
            color: #1E2229;
            font-size: 16px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }
        .btn-submit:hover {
            background: #ECC200;
            transform: translateY(-2px);
        }
        .login-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 14px;
            color: #64748B;
        }
        .login-footer a {
            color: #005B94;
            font-weight: 600;
            text-decoration: none;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <img src="../assets/wordmark.png" alt="The ALT HQ">
        <h1>Admin Dashboard</h1>
        <p>Manage Insights and Videos Carousel</p>
    </div>
    <div class="login-body">
        <?php if (!empty($error)): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <i class="fa-regular fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="admin" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <span>Sign In</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>

        <div class="login-footer">
            <a href="../index.php"><i class="fa-solid fa-arrow-left"></i> Back to Website</a>
        </div>
    </div>
</div>

</body>
</html>

