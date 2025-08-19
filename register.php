<?php
session_start();
require_once 'db_connect.php';

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $date_of_birth = $_POST['date_of_birth'];
    
    // Handle file upload
    $photo = '';
    if(isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if(!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = uniqid() . '_' . basename($_FILES['photo']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if(move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
            $photo = $targetPath;
        }
    }
    
    // Validate inputs
    if(empty($username) || empty($password) || empty($email) || empty($first_name) || empty($last_name)) {
        $error = 'Please fill in all required fields';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if($stmt->rowCount() > 0) {
                $error = 'Username or email already exists';
            } else {
                // Insert into users table with the selected role
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $hashedPassword, $email, $role]);
                $user_id = $pdo->lastInsertId();
                
                // Insert into players table if role is player
                if($role == 'player') {
                    $position = $_POST['position'];
                    $skills = trim($_POST['skills']);
                    
                    $stmt = $pdo->prepare("INSERT INTO players (user_id, first_name, last_name, date_of_birth, position, skills, photo) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $first_name, $last_name, $date_of_birth, $position, $skills, $photo]);
                } elseif($role == 'coach') {
                    $license = trim($_POST['license']);
                    $experience = trim($_POST['experience']);
                    $coach_type = trim($_POST['coach_type']);
                    
                    $stmt = $pdo->prepare("INSERT INTO coaches (user_id, first_name, last_name, license, coach_type, experience) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $first_name, $last_name, $license, $coach_type, $experience]);
                }
                
                $pdo->commit();
                $success = 'Registration successful! You can now <a href="login.php">login</a>.';
            }
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Football Club Management System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            color: #2c3e50;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .logo i {
            margin-right: 0.5rem;
        }
        
        nav ul {
            list-style: none;
            display: flex;
            gap: 1rem;
        }
        
        nav a {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 500;
        }
        
        main {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .register-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .register-container h2 {
            color: #2c3e50;
            margin-top: 0;
            text-align: center;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            flex: 1;
            margin-bottom: 1rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            display: inline-block;
            text-align: center;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        footer {
            background-color: #2c3e50;
            color: white;
            text-align: center;
            padding: 1rem;
            margin-top: 2rem;
        }
        
        footer .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
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
                    <li><a href="login.php">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="register-container">
            <h2><i class="fas fa-user-plus"></i> Register</h2>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php else: ?>
                <form action="register.php" method="post" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username"><i class="fas fa-user"></i> Username *</label>
                            <input type="text" id="username" name="username" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email *</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password"><i class="fas fa-lock"></i> Password *</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="role"><i class="fas fa-user-tag"></i> Role *</label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="">Select Role</option>
                                <option value="player">Player</option>
                                <option value="coach">Coach</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name"><i class="fas fa-id-card"></i> First Name *</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name"><i class="fas fa-id-card"></i> Last Name *</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_of_birth"><i class="fas fa-birthday-cake"></i> Date of Birth *</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="photo"><i class="fas fa-camera"></i> Profile Photo</label>
                            <input type="file" id="photo" name="photo" class="form-control">
                        </div>
                    </div>
                    
                    <div id="player-fields">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="position"><i class="fas fa-running"></i> Position *</label>
                                <select id="position" name="position" class="form-control">
                                    <option value="">Select Position</option>
                                    <optgroup label="Goalkeeper">
                                        <option value="Goalkeeper">Goalkeeper</option>
                                    </optgroup>
                                    <optgroup label="Defenders">
                                        <option value="Left Back">Left Back</option>
                                        <option value="Center Back">Center Back</option>
                                        <option value="Right Back">Right Back</option>
                                    </optgroup>
                                    <optgroup label="Midfielders">
                                        <option value="Left Midfielder">Left Midfielder</option>
                                        <option value="Center Midfielder">Center Midfielder</option>
                                        <option value="Right Midfielder">Right Midfielder</option>
                                    </optgroup>
                                    <optgroup label="Forwards">
                                        <option value="Left Winger">Left Winger</option>
                                        <option value="Center Forward">Center Forward</option>
                                        <option value="Right Winger">Right Winger</option>
                                    </optgroup>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="skills"><i class="fas fa-star"></i> Skills</label>
                                <textarea id="skills" name="skills" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div id="coach-fields" style="display: none;">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="coach_type"><i class="fas fa-user-tie"></i> Coach Type *</label>
                                <select id="coach_type" name="coach_type" class="form-control">
                                    <option value="">Select Coach Type</option>
                                    <option value="manager">Manager (Head Coach)</option>
                                    <option value="assistant">Assistant Coach</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="license"><i class="fas fa-certificate"></i> Coaching License *</label>
                                <input type="text" id="license" name="license" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="experience"><i class="fas fa-briefcase"></i> Experience *</label>
                            <textarea id="experience" name="experience" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-top: 20px;">
                        <button type="submit" class="btn" style="width: 100%;">
                            <i class="fas fa-user-plus"></i> Register
                        </button>
                    </div>
                    
                    <div style="text-align: center; margin-top: 15px;">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Football Club Management System. All rights reserved.</p>
        </div>
    </footer>

    <script>
        document.getElementById('role').addEventListener('change', function() {
            const role = this.value;
            document.getElementById('player-fields').style.display = role === 'player' ? 'block' : 'none';
            document.getElementById('coach-fields').style.display = role === 'coach' ? 'block' : 'none';
            
            // Make fields required/not required based on selection
            document.getElementById('position').required = role === 'player';
            document.getElementById('skills').required = false; // Skills are optional
            
            // Coach fields
            document.getElementById('coach_type').required = role === 'coach';
            document.getElementById('license').required = role === 'coach';
            document.getElementById('experience').required = role === 'coach';
        });
    </script>
</body>
</html>