<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle club join request
if (isset($_POST['request_join'])) {
    $club_id = $_POST['club_id'];
    $request_message = trim($_POST['request_message']);
    
    try {
        // First get the coach's ID from their user_id
        $stmt = $pdo->prepare("SELECT id FROM coaches WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $coach = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$coach) {
            throw new Exception("Coach profile not found");
        }
        
        $coach_id = $coach['id'];
        
        // Check if already has a pending request
        $stmt = $pdo->prepare("SELECT * FROM coach_requests 
                             WHERE coach_id = ? AND status = 'pending'");
        $stmt->execute([$coach_id]);
        if ($stmt->fetch()) {
            $message = '<div class="alert alert-warning">You already have a pending request.</div>';
        } else {
            // Insert new request
            $stmt = $pdo->prepare("INSERT INTO coach_requests 
                                 (coach_id, club_id, request_message, status, request_date) 
                                 VALUES (?, ?, ?, 'pending', NOW())");
            $stmt->execute([$coach_id, $club_id, $request_message]);
            $message = '<div class="alert alert-success">Your request has been submitted successfully!</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Database error: ' . $e->getMessage() . '</div>';
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

try {
    // Get coach details with club information
    $stmt = $pdo->prepare("SELECT c.*, cl.name as club_name, cl.id as club_id, u.role
                          FROM coaches c 
                          JOIN users u ON c.user_id = u.id
                          LEFT JOIN clubs cl ON c.club_id = cl.id 
                          WHERE c.user_id = ?");
    $stmt->execute([$user_id]);
    $coach = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$coach) {
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
    // Get team players if coach has a club
    $team_players = [];
    if (!empty($coach['club_id'])) {
        $stmt = $pdo->prepare("SELECT p.id, p.first_name, p.last_name, p.position, p.status 
                              FROM players p 
                              WHERE p.club_id = ?");
        $stmt->execute([$coach['club_id']]);
        $team_players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get training sessions for the club
    $training_sessions = [];
    if (!empty($coach['club_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM training_sessions 
                              WHERE club_id = ? 
                              AND session_date >= CURDATE() 
                              ORDER BY session_date ASC");
        $stmt->execute([$coach['club_id']]);
        $training_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get all clubs for join request form (if coach doesn't have a club)
    $all_clubs = [];
    if (empty($coach['club_id'])) {
        $stmt = $pdo->prepare("SELECT id, name FROM clubs ORDER BY name");
        $stmt->execute();
        $all_clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Check if coach has pending requests
    $pending_requests = [];
    if (empty($coach['club_id'])) {
        $stmt = $pdo->prepare("SELECT cr.*, cl.name as club_name 
                              FROM coach_requests cr
                              JOIN clubs cl ON cr.club_id = cl.id
                              JOIN coaches c ON cr.coach_id = c.id
                              WHERE c.user_id = ? AND cr.status = 'pending'");
        $stmt->execute([$user_id]);
        $pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Function to determine position category
function getPositionClass($position) {
    if (strpos($position, 'Back') !== false) return 'defender';
    if (strpos($position, 'Midfielder') !== false) return 'midfielder';
    if (strpos($position, 'Winger') !== false || strpos($position, 'Forward') !== false) return 'forward';
    return 'goalkeeper';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coach Dashboard - Football Club Management System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Your existing CSS styles here */
        .position-badge {
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
        
        .badge-coach {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            color: white;
        }
        .badge-manager { background-color: #1a2a6c; }
        .badge-assistant { background-color: #b21f1f; }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            color: white;
        }
        .status-active { background-color: #28a745; }
        .status-pending { background-color: #ffc107; color: #343a40; }
        .status-inactive { background-color: #dc3545; }
        .status-approved { background-color: #28a745; }
        .status-rejected { background-color: #dc3545; }
        
        .training-session {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .join-club-form {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .pending-request {
            background: #fff3cd;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            border-left: 4px solid #ffc107;
        }
        
        .response-message {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            border-left: 3px solid #6c757d;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        .alert-warning {
            color: #8a6d3b;
            background-color: #fcf8e3;
            border-color: #faebcc;
        }
        .alert-danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
        .alert-info {
            color: #31708f;
            background-color: #d9edf7;
            border-color: #bce8f1;
        }
        
        .form-control {
            display: block;
            width: 100%;
            padding: 8px 12px;
            margin-bottom: 10px;
            font-size: 14px;
            line-height: 1.42857143;
            color: #555;
            background-color: #fff;
            background-image: none;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin-bottom: 0;
            font-size: 14px;
            font-weight: 400;
            line-height: 1.42857143;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .btn-primary {
            color: #fff;
            background-color: #337ab7;
            border-color: #2e6da4;
        }
        
        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 0.75rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
        }
        
        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table tbody + tbody {
            border-top: 2px solid #dee2e6;
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

    <main>
        <div class="dashboard-grid">
            <div class="sidebar">
                <div class="profile-header">
                    <i class="fas fa-user-tie" style="font-size: 3rem; color: #337ab7;"></i>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']); ?></h2>
                        <p>
                            <span class="badge-coach badge-<?php echo $coach['coach_type'] == 'manager' ? 'manager' : 'assistant'; ?>">
                                <?php echo $coach['coach_type'] == 'manager' ? 'Manager' : 'Assistant Coach'; ?>
                            </span>
                        </p>
                        <?php if (!empty($coach['club_name'])): ?>
                            <p><?php echo htmlspecialchars($coach['club_name']); ?></p>
                        <?php else: ?>
                            <p>No Club Assigned</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <ul class="sidebar-menu">
                    <li><a href="coachdashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <?php if (!empty($coach['club_id'])): ?>
                        <li><a href="#team"><i class="fas fa-users"></i> My Team</a></li>
                        <li><a href="#training"><i class="fas fa-running"></i> Training</a></li>
                        <?php if ($coach['coach_type'] == 'manager'): ?>
                            <li><a href="#management"><i class="fas fa-cog"></i> Club Management</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="dashboard-content">
                <?php echo $message; ?>
                
                <section>
                    <h2><i class="fas fa-tachometer-alt"></i> Coach Dashboard</h2>
                    <p>Welcome back, <?php echo $coach['coach_type'] == 'manager' ? 'Manager' : 'Coach'; ?> <?php echo htmlspecialchars($coach['last_name']); ?>!</p>
                    
                    <div class="profile-details" style="margin-top: 20px;">
                        <div class="profile-section">
                            <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']); ?></p>
                            <p><strong>Coach Type:</strong> 
                                <span class="badge-coach badge-<?php echo $coach['coach_type'] == 'manager' ? 'manager' : 'assistant'; ?>">
                                    <?php echo $coach['coach_type'] == 'manager' ? 'Manager' : 'Assistant Coach'; ?>
                                </span>
                            </p>
                            <p><strong>License:</strong> <?php echo htmlspecialchars($coach['license']); ?></p>
                            <p><strong>Experience:</strong> <?php echo htmlspecialchars($coach['experience']); ?></p>
                        </div>
                        
                        <div class="profile-section">
                            <h3><i class="fas fa-tshirt"></i> Club Information</h3>
                            <?php if (!empty($coach['club_name'])): ?>
                                <p><strong>Club:</strong> <?php echo htmlspecialchars($coach['club_name']); ?></p>
                                <p><strong>Team Members:</strong> <?php echo count($team_players); ?> players</p>
                                <p><strong>Upcoming Training:</strong> <?php echo count($training_sessions); ?> sessions</p>
                            <?php else: ?>
                                <p>You are not currently assigned to any club.</p>
                                
                                <?php if (!empty($pending_requests)): ?>
                                    <div class="pending-request">
                                        <h4><i class="fas fa-clock"></i> Pending Request</h4>
                                        <?php foreach ($pending_requests as $request): ?>
                                            <p>You have requested to join <strong><?php echo htmlspecialchars($request['club_name']); ?></strong> on <?php echo date('M d, Y', strtotime($request['request_date'])); ?>.</p>
                                            <p>Status: <span class="status-badge status-<?php echo htmlspecialchars($request['status']); ?>">
                                                <?php echo ucfirst(htmlspecialchars($request['status'])); ?>
                                            </span></p>
                                            
                                            <?php if (!empty($request['response_message'])): ?>
                                                <div class="response-message">
                                                    <p><strong>Response:</strong> <?php echo htmlspecialchars($request['response_message']); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="join-club-form">
                                        <h4><i class="fas fa-plus-circle"></i> Request to Join a Club</h4>
                                        <form method="post">
                                            <div class="form-group">
                                                <label for="club_id">Select Club:</label>
                                                <select name="club_id" id="club_id" class="form-control" required>
                                                    <option value="">-- Select a Club --</option>
                                                    <?php foreach ($all_clubs as $club): ?>
                                                        <option value="<?php echo $club['id']; ?>"><?php echo htmlspecialchars($club['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="request_message">Message to Club Admin (optional):</label>
                                                <textarea name="request_message" id="request_message" class="form-control" rows="3" placeholder="Briefly introduce yourself and your qualifications"></textarea>
                                            </div>
                                            <button type="submit" name="request_join" class="btn btn-primary">
                                                <i class="fas fa-paper-plane"></i> Submit Request
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
                
                <?php if (!empty($coach['club_id'])): ?>
                    <section id="team" style="margin-top: 30px;">
                        <h2><i class="fas fa-users"></i> My Team Players</h2>
                        
                        <?php if (!empty($team_players)): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Position</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($team_players as $player): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></td>
                                            <td>
                                                <span class="position-badge position-<?php 
                                                    echo getPositionClass($player['position']);
                                                ?>">
                                                    <?php echo htmlspecialchars($player['position']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo htmlspecialchars($player['status']); ?>">
                                                    <?php echo ucfirst(htmlspecialchars($player['status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No players currently in your team.
                            </div>
                        <?php endif; ?>
                    </section>
                    
                    <section id="training" style="margin-top: 30px;">
                        <h2><i class="fas fa-running"></i> Training Schedule</h2>
                        
                        <?php if (!empty($training_sessions)): ?>
                            <?php foreach ($training_sessions as $session): ?>
                                <div class="training-session">
                                    <h3><?php echo htmlspecialchars($session['title']); ?></h3>
                                    <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($session['session_date'])); ?></p>
                                    <p><strong>Time:</strong> <?php echo date('H:i', strtotime($session['start_time'])) . ' - ' . date('H:i', strtotime($session['end_time'])); ?></p>
                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($session['location']); ?></p>
                                    <p><strong>Focus:</strong> <?php echo htmlspecialchars($session['focus_area']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No upcoming training sessions scheduled.
                            </div>
                        <?php endif; ?>
                    </section>
                    
                    <?php if ($coach['coach_type'] == 'manager'): ?>
                        <section id="management" style="margin-top: 30px;">
                            <h2><i class="fas fa-cog"></i> Club Management</h2>
                            <div class="alert alert-info">
                                Manager-only features will be displayed here.
                            </div>
                        </section>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Football Club Management System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>