<?php
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = escape($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi!';
    } else {
        $query = "SELECT * FROM users WHERE username = '$username' AND is_active = 1";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                header('Location: index.php');
                exit();
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'Username tidak ditemukan!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#DAA520">
    <title>Login - <?= APP_NAME ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 50%, #2d2d2d 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(218, 165, 32, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            top: -300px;
            right: -300px;
            animation: float 8s ease-in-out infinite;
        }
        
        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255, 215, 0, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -200px;
            left: -200px;
            animation: float 10s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -30px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
        }
        
        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 450px;
        }
        
        .login-card {
            background: rgba(26, 26, 26, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 40px 35px;
            box-shadow: 
                0 30px 60px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(218, 165, 32, 0.3);
            animation: slideUp 0.6s ease-out;
            border: 2px solid rgba(218, 165, 32, 0.2);
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .logo-icon {
            width: 85px;
            height: 85px;
            background: linear-gradient(135deg, #B8860B 0%, #DAA520 50%, #FFD700 100%);
            border-radius: 25px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 
                0 15px 40px rgba(218, 165, 32, 0.5),
                0 0 0 8px rgba(218, 165, 32, 0.1);
            animation: pulse 2.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .logo-icon i {
            font-size: 40px;
            color: #0a0a0a;
        }
        
        .logo-section h1 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #FFD700 0%, #DAA520 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        
        .logo-section p {
            color: #DAA520;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 2.5px;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: #FFD700;
            margin-bottom: 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: block;
        }
        
        .input-group-custom {
            position: relative;
        }
        
        .input-group-custom i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #DAA520;
            font-size: 17px;
            z-index: 1;
        }
        
        .form-control {
            padding: 14px 18px 14px 50px;
            background: rgba(45, 45, 45, 0.8);
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            color: #ffffff;
            width: 100%;
        }
        
        .form-control:focus {
            background: rgba(45, 45, 45, 0.95);
            border-color: #DAA520;
            box-shadow: 
                0 0 0 0.2rem rgba(218, 165, 32, 0.25),
                0 0 20px rgba(218, 165, 32, 0.3);
            outline: none;
            color: #ffffff;
        }
        
        .form-control::placeholder {
            color: rgba(218, 165, 32, 0.5);
        }
        
        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #DAA520;
            z-index: 2;
            transition: color 0.3s;
        }
        
        .password-toggle:hover {
            color: #FFD700;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #B8860B 0%, #DAA520 50%, #FFD700 100%);
            border: none;
            border-radius: 12px;
            color: #0a0a0a;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(218, 165, 32, 0.4);
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(218, 165, 32, 0.6);
            background: linear-gradient(135deg, #DAA520 0%, #FFD700 50%, #FFED4E 100%);
        }
        
        .btn-login:active {
            transform: translateY(-1px);
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 14px 18px;
            margin-bottom: 20px;
            animation: shake 0.5s;
            backdrop-filter: blur(10px);
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .demo-info {
            background: rgba(45, 45, 45, 0.6);
            border-radius: 12px;
            padding: 20px;
            margin-top: 25px;
            border-left: 4px solid #DAA520;
            backdrop-filter: blur(10px);
        }
        
        .demo-info h6 {
            color: #FFD700;
            font-weight: 700;
            margin-bottom: 12px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .demo-info .credential {
            background: rgba(26, 26, 26, 0.8);
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 12px;
            border: 1px solid rgba(218, 165, 32, 0.2);
        }
        
        .demo-info .credential strong {
            color: #DAA520;
        }
        
        @media (max-width: 576px) {
            .login-card {
                padding: 35px 25px;
            }
            
            .logo-section h1 {
                font-size: 24px;
            }
            
            .logo-icon {
                width: 70px;
                height: 70px;
            }
            
            .logo-icon i {
                font-size: 34px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-section">
                <div class="logo-icon">
                    <i class="fas fa-crown"></i>
                </div>
                <h1><?= APP_NAME ?></h1>
                <p>Premium Restaurant POS</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <div class="input-group-custom">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-group-custom">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Masuk
                </button>
            </form>
            
            <div class="demo-info">
                <h6><i class="fas fa-key me-2"></i>Demo Account</h6>
                <div class="credential">
                    <strong>Admin:</strong> admin / password
                </div>
                <div class="credential">
                    <strong>Kasir:</strong> kasir1 / password
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Password toggle
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>