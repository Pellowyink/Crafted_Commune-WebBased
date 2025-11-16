<?php
/**
 * Admin Login Page
 * Access: yoursite.com/admin/login.php
 * Default credentials: admin / admin123
 */
require_once '../config.php';

// If already logged in, redirect to dashboard
if (isAdminLoggedIn()) {
    redirect('index.php');
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Check credentials
        $stmt = $pdo->prepare("
            SELECT id, username, password, full_name, email 
            FROM admin_users 
            WHERE username = ? AND is_active = 1
        ");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            // Login successful
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_email'] = $admin['email'];
            
            // Update last login
            $updateStmt = $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$admin['id']]);
            
            // Log activity
            logActivity($admin['id'], 'login', 'Admin logged in');
            
            // Redirect to dashboard
            redirect('index.php');
        } else {
            $error = 'Invalid username or password.';
            
            // Log failed attempt
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt = $pdo->prepare("
                INSERT INTO activity_log (admin_id, action, description, ip_address) 
                VALUES (NULL, 'login_failed', ?, ?)
            ");
            $stmt->execute(["Failed login attempt for username: $username", $ip]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Calistoga&family=Cabin+Condensed:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cabin Condensed', sans-serif;
            background: linear-gradient(135deg, #264d2a 0%, #3d5a3d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 420px;
            padding: 3rem;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        h1 {
            font-family: 'Calistoga', serif;
            color: #264d2a;
            font-size: 2rem;
            text-align: center;
            margin-bottom: 0.5rem;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 0.8rem 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            border-left: 4px solid #c33;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Cabin Condensed', sans-serif;
            transition: border-color 0.3s ease;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #3d5a3d;
        }
        
        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #264d2a 0%, #3d5a3d 100%);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            font-family: 'Cabin Condensed', sans-serif;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(38, 77, 42, 0.4);
        }
        
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-link a {
            color: #3d5a3d;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .back-link a:hover {
            color: #264d2a;
            text-decoration: underline;
        }
        
        .info-box {
            background: #f0f7f0;
            border: 2px solid #3d5a3d;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: #264d2a;
        }
        
        .info-box strong {
            display: block;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">☕</div>
            <h1>Admin Panel</h1>
            <p class="subtitle"><?= SITE_NAME ?></p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?= e($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    required 
                    autofocus
                    autocomplete="username"
                >
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    autocomplete="current-password"
                >
            </div>
            
            <button type="submit" class="login-btn">Login to Dashboard</button>
        </form>
        
        <div class="back-link">
            <a href="../index.php">← Back to Main Site</a>
        </div>
        
        <!-- <div class="info-box">
            <strong>🔐 Default Login Credentials:</strong>
            Username: <strong>admin</strong><br>
            Password: <strong>password</strong>
        </div> -->
    </div>
</body>
</html>