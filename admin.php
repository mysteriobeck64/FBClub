<?php
session_start();
require_once 'db_connect.php';

// Redirect if not admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Function to determine position category
function getPositionClass($position) {
    if (strpos($position, 'Back') !== false) return 'defender';
    if (strpos($position, 'Midfielder') !== false) return 'midfielder';
    if (strpos($position, 'Winger') !== false || strpos($position, 'Forward') !== false) return 'forward';
    return 'goalkeeper';
}

// Handle form actions
if($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Handle join request approval
    if(isset($_GET['approve']) && is_numeric($_GET['approve'])) {
        $request_id = (int)$_GET['approve'];
        
        try {
            $pdo->beginTransaction();
            
            // Get the request details
            $stmt = $pdo->prepare("SELECT * FROM join_requests WHERE id = ?");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($request) {
                // Update player's club
                $stmt = $pdo->prepare("UPDATE players SET club_id = ?, status = 'active' WHERE id = ?");
                $stmt->execute([$request['club_id'], $request['player_id']]);
                
                // Update request status
                $stmt = $pdo->prepare("UPDATE join_requests SET status = 'approved' WHERE id = ?");
                $stmt->execute([$request_id]);
                
                $pdo->commit();
                $_SESSION['success'] = 'Join request approved successfully';
            }
        } catch(PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error approving request: ' . $e->getMessage();
        }
        
        header('Location: admin.php');
        exit();
    }

    // Handle join request rejection
    if(isset($_GET['reject']) && is_numeric($_GET['reject'])) {
        $request_id = (int)$_GET['reject'];
        
        try {
            $stmt = $pdo->prepare("UPDATE join_requests SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$request_id]);
            $_SESSION['success'] = 'Join request rejected successfully';
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Error rejecting request: ' . $e->getMessage();
        }
        
        header('Location: admin.php');
        exit();
    }
}

// Handle POST requests (club creation)
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_club'])) {
    $name = trim($_POST['name']);
    $founded_year = trim($_POST['founded_year']);
    $stadium = trim($_POST['stadium']);
    $location = trim($_POST['location']);
    
    // Basic validation
    if(empty($name) || empty($founded_year) || empty($stadium) || empty($location)) {
        $_SESSION['error'] = 'All fields are required';
        header('Location: admin.php');
        exit();
    }
    
    if(!is_numeric($founded_year) || $founded_year < 1800 || $founded_year > date('Y')) {
        $_SESSION['error'] = 'Invalid founded year';
        header('Location: admin.php');
        exit();
    }
    
    // Handle file upload
    $logoPath = null;
    if(isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/club_logos/';
        if(!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('club_') . '.' . $extension;
        $destination = $uploadDir . $filename;
        
        // Validate image
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if(in_array($_FILES['logo']['type'], $allowedTypes)) {
            if(move_uploaded_file($_FILES['logo']['tmp_name'], $destination)) {
                $logoPath = $destination;
            } else {
                $_SESSION['error'] = 'Failed to upload logo';
                header('Location: admin.php');
                exit();
            }
        } else {
            $_SESSION['error'] = 'Invalid file type. Only JPG, PNG, and GIF are allowed';
            header('Location: admin.php');
            exit();
        }
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO clubs (name, founded_year, stadium, location, logo) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $founded_year, $stadium, $location, $logoPath]);
        
        $_SESSION['success'] = 'Club created successfully';
        header('Location: admin.php');
        exit();
    } catch(PDOException $e) {
        // Delete the uploaded file if database insertion fails
        if($logoPath && file_exists($logoPath)) {
            unlink($logoPath);
        }
        $_SESSION['error'] = 'Error creating club: ' . $e->getMessage();
        header('Location: admin.php');
        exit();
    }
}

// Get all data for dashboard
try {
    // Pending join requests
    $stmt = $pdo->query("SELECT jr.*, p.first_name, p.last_name, c.name as club_name 
                        FROM join_requests jr 
                        JOIN players p ON jr.player_id = p.id 
                        JOIN clubs c ON jr.club_id = c.id 
                        WHERE jr.status = 'pending'");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // All clubs
    $clubs = $pdo->query("SELECT * FROM clubs")->fetchAll(PDO::FETCH_ASSOC);
    
    // All players with club info
    $players = $pdo->query("SELECT p.*, u.username, c.name as club_name 
                           FROM players p 
                           JOIN users u ON p.user_id = u.id 
                           LEFT JOIN clubs c ON p.club_id = c.id")
                   ->fetchAll(PDO::FETCH_ASSOC);
    
    // All coaches with club info
    $coaches = $pdo->query("SELECT c.*, u.username, cl.name as club_name 
                           FROM coaches c 
                           JOIN users u ON c.user_id = u.id 
                           LEFT JOIN clubs cl ON c.club_id = cl.id")
                   ->fetchAll(PDO::FETCH_ASSOC);
    
    // Stats for dashboard
    $stats = [
        'players' => count($players),
        'coaches' => count($coaches),
        'clubs' => count($clubs),
        'requests' => count($requests)
    ];
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Football Club Management System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Improved admin-specific styles */
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 1rem;
            margin-bottom: 10px;
        }
        
        .stat-card p {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .position-badge, .status-badge, .badge-coach {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            color: white;
        }
        
        .position-goalkeeper { background-color: #ff6b6b; }
        .position-defender { background-color: #4ecdc4; }
        .position-midfielder { background-color: #45aaf2; }
        .position-forward { background-color: #a55eea; }
        
        .badge-manager { background-color: #1a2a6c; }
        .badge-assistant { background-color: #b21f1f; }
        
        .status-active { background-color: #28a745; }
        .status-pending { background-color: #ffc107; color: #343a40; }
        .status-inactive { background-color: #dc3545; }
        
        .club-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .club-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .action-btns {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .section-title {
            color: var(--primary-color);
            margin: 30px 0 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #eee;
        }
        
        .create-club-form {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .create-club-form h3 {
            margin-top: 0;
            color: var(--primary-color);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .club-logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 15px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #eee;
        }
        
        .club-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .no-logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 15px;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            border: 3px solid #eee;
        }
        
        .create-club-form small {
            display: block;
            font-size: 0.8rem;
            color: #666;
            margin-top: 5px;
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
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-user-shield"></i> Admin Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
        </div>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <section id="dashboard">
            <h2 class="section-title"><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Players</h3>
                    <p><?php echo $stats['players']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Coaches</h3>
                    <p><?php echo $stats['coaches']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Clubs</h3>
                    <p><?php echo $stats['clubs']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Pending Requests</h3>
                    <p><?php echo $stats['requests']; ?></p>
                </div>
            </div>
        </section>

        <section id="requests">
            <h2 class="section-title"><i class="fas fa-user-plus"></i> Pending Join Requests</h2>
            
            <?php if(!empty($requests)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Player</th>
                            <th>Club</th>
                            <th>Request Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['club_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                <td class="action-btns">
                                    <a href="admin.php?approve=<?php echo $request['id']; ?>" class="btn btn-success">Approve</a>
                                    <a href="admin.php?reject=<?php echo $request['id']; ?>" class="btn btn-danger">Reject</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <p>No pending join requests.</p>
                </div>
            <?php endif; ?>
        </section>

        <section id="players">
            <h2 class="section-title"><i class="fas fa-users"></i> All Players</h2>
            
            <?php if(!empty($players)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Club</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($players as $player): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></td>
                                <td>
                                    <span class="position-badge position-<?php echo getPositionClass($player['position']); ?>">
                                        <?php echo htmlspecialchars($player['position']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($player['club_name'] ?? 'No Club'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo htmlspecialchars($player['status']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($player['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <p>No players registered.</p>
                </div>
            <?php endif; ?>
        </section>

        <section id="coaches">
            <h2 class="section-title"><i class="fas fa-chalkboard-teacher"></i> All Coaches</h2>
            
            <?php if(!empty($coaches)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>License</th>
                            <th>Experience</th>
                            <th>Club</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($coaches as $coach): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']); ?></td>
                                <td>
                                    <span class="badge-coach badge-<?php echo $coach['coach_type'] == 'manager' ? 'manager' : 'assistant'; ?>">
                                        <?php echo $coach['coach_type'] == 'manager' ? 'Manager' : 'Assistant'; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($coach['license']); ?></td>
                                <td><?php echo htmlspecialchars($coach['experience']); ?></td>
                                <td><?php echo htmlspecialchars($coach['club_name'] ?? 'No Club'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <p>No coaches registered.</p>
                </div>
            <?php endif; ?>
        </section>

        <section id="clubs">
            <h2 class="section-title"><i class="fas fa-tshirt"></i> All Clubs</h2>
            
            <div class="create-club-form">
                <h3><i class="fas fa-plus-circle"></i> Create New Club</h3>
                <form method="POST" action="admin.php" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Club Name:</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="founded_year">Founded Year:</label>
                        <input type="number" id="founded_year" name="founded_year" min="1800" max="<?php echo date('Y'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stadium">Stadium Name:</label>
                        <input type="text" id="stadium" name="stadium" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location:</label>
                        <input type="text" id="location" name="location" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="logo">Club Logo:</label>
                        <input type="file" id="logo" name="logo" accept="image/*">
                        <small>Recommended size: 200x200 pixels</small>
                    </div>
                    
                    <button type="submit" name="create_club" class="btn btn-primary">Create Club</button>
                </form>
            </div>
            
            <?php if(!empty($clubs)): ?>
                <div class="club-grid">
                    <?php foreach($clubs as $club): ?>
                        <div class="club-card">
                            <?php if(!empty($club['logo'])): ?>
                                <div class="club-logo">
                                    <img src="<?php echo htmlspecialchars($club['logo']); ?>" alt="<?php echo htmlspecialchars($club['name']); ?> logo">
                                </div>
                            <?php else: ?>
                                <div class="no-logo">
                                    <i class="fas fa-image"></i>
                                </div>
                            <?php endif; ?>
                            <h3><?php echo htmlspecialchars($club['name']); ?></h3>
                            <p><strong>Founded:</strong> <?php echo htmlspecialchars($club['founded_year']); ?></p>
                            <p><strong>Stadium:</strong> <?php echo htmlspecialchars($club['stadium']); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($club['location']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <p>No clubs registered.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Football Club Management System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>