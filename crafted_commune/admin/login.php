<?php
require_once '../config.php';

// If already logged in, redirect to dashboard
if (isAdminLoggedIn()) {
    redirect('index.php');
}

$error = '';
$debugInfo = [];

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // DEBUG: Store what we're checking
    $debugInfo[] = "Username entered: " . $username;
    $debugInfo[] = "Password entered: " . $password;
    $debugInfo[] = "Password length: " . strlen($password);
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
        $debugInfo[] = "ERROR: Empty username or password";
    } else {
        // Check credentials
        $stmt = $pdo->prepare("
            SELECT id, username, password, full_name, email 
            FROM admin_users 
            WHERE username = ? AND is_active = 1
        ");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        // DEBUG: Check if user was found
        if (!$admin) {
            $debugInfo[] = "ERROR: Username '$username' not found in database";
            
            // Show all usernames in database
            $allUsers = $pdo->query("SELECT username FROM admin_users")->fetchAll();
            $debugInfo[] = "Available usernames: " . json_encode(array_column($allUsers, 'username'));
        } else {
            $debugInfo[] = "✓ User found in database";
            $debugInfo[] = "Username from DB: " . $admin['username'];
            $debugInfo[] = "Password hash from DB: " . substr($admin['password'], 0, 20) . "...";
            
            // Test password verification
            $passwordVerify = password_verify($password, $admin['password']);
            $debugInfo[] = "Password verify result: " . ($passwordVerify ? 'TRUE' : 'FALSE');
            
            // Try manual check
            $manualCheck = ($admin['password'] === '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
            $debugInfo[] = "Password hash matches expected: " . ($manualCheck ? 'TRUE' : 'FALSE');
        }
        
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
            $debugInfo[] = "ERROR: Authentication failed";
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
        
        .debug-box {
            background: #f0f0f0;
            border: 2px solid #ccc;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .debug-box h3 {
            color: #264d2a;
            margin-bottom: 0.5rem;
        }
        
        .debug-box pre {
            white-space: pre-wrap;
            word-wrap: break-word;
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
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($debugInfo)): ?>
            <div class="debug-box">
                <h3>🔍 Debug Information:</h3>
                <pre><?php foreach($debugInfo as $info) echo $info . "\n"; ?></pre>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    value="admin"
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
                    value="admin123"
                    required
                    autocomplete="current-password"
                >
            </div>
            
            <button type="submit" class="login-btn">Login to Dashboard</button>
        </form>
        
        <div class="back-link">
            <a href="../index.php">← Back to Main Site</a>
        </div>
        
        <div class="info-box">
            <strong>🔐 Default Login Credentials:</strong>
            Username: <strong>admin</strong><br>
            Password: <strong>admin123</strong>
        </div>
    </div>
</body>
</html>