<?php
session_start();
require_once 'db_connect.php';

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate that both fields are submitted
    if(empty($_POST['username']) || empty($_POST['password'])) {
        $error = 'Both username and password are required';
    } else {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($user) {
                if(password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Redirect based on role
                    switch($user['role']) {
                        case 'admin':
                            header('Location: admin.php');
                            break;
                        case 'coach':
                            header('Location: coachdashboard.php');
                            break;
                        case 'player':
                            header('Location: playerdashboard.php');
                            break;
                        default:
                            header('Location: dashboard.php');
                    }
                    exit();
                } else {
                    $error = 'Invalid username or password';
                }
            } else {
                $error = 'Invalid username or password';
            }
        } catch(PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Football Club Management System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <div class="header-container">
            <a href="index.php" class="logo">
                <i class="fas fa-futbol"></i> FCMS
            </a>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="register.php">Register</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="login-container">
            <h2><i class="fas fa-sign-in-alt"></i> Login</h2>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username *</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password *</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn" style="width: 100%;">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </div>
                
                <div style="text-align: center; margin-top: 15px;">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                </div>
            </form>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Football Club Management System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>