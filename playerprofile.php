<?php
session_start();
require_once 'db_connect.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'player') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get player details
try {
    $stmt = $pdo->prepare("SELECT p.*, c.name as club_name 
                          FROM players p 
                          LEFT JOIN clubs c ON p.club_id = c.id 
                          WHERE p.user_id = ?");
    $stmt->execute([$user_id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$player) {
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
    // Function to determine position category and color
    function getPositionClass($position) {
        if (strpos($position, 'Back') !== false) return 'defender';
        if (strpos($position, 'Midfielder') !== false) return 'midfielder';
        if (strpos($position, 'Winger') !== false || strpos($position, 'Forward') !== false) return 'forward';
        return 'goalkeeper';
    }
    
    // Calculate age
    $age = date_diff(date_create($player['date_of_birth']), date_create('today'))->y;
    
    // Get player statistics
    $stmt = $pdo->prepare("SELECT * FROM player_stats WHERE player_id = ?");
    $stmt->execute([$player['id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Football Club Management System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .position-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-right: 5px;
        }
        .position-goalkeeper {
            background-color: #ff6b6b;
            color: white;
        }
        .position-defender {
            background-color: #4ecdc4;
            color: white;
        }
        .position-midfielder {
            background-color: #45aaf2;
            color: white;
        }
        .position-forward {
            background-color: #a55eea;
            color: white;
        }
        .skill-tag {
            display: inline-block;
            background-color: #f1f1f1;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        .stats-container {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
        }
        .stat-card {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            flex: 1;
            margin: 0 10px;
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }
        .stats-details {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }
        .performance-chart {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
                    <li><a href="playerdashboard.php">Dashboard</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="dashboard-grid">
            <div class="sidebar">
                <div class="profile-header">
                    <?php if($player['photo']): ?>
                        <img src="<?php echo htmlspecialchars($player['photo']); ?>" alt="Player Photo" class="avatar">
                    <?php else: ?>
                        <i class="fas fa-user-circle" style="font-size: 3rem; color: var(--primary-color);"></i>
                    <?php endif; ?>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></h2>
                        <p>
                            <span class="position-badge position-<?php echo getPositionClass($player['position']); ?>">
                                <?php echo htmlspecialchars($player['position']); ?>
                            </span>
                        </p>
                        <?php if($player['club_name']): ?>
                            <p><?php echo htmlspecialchars($player['club_name']); ?></p>
                        <?php else: ?>
                            <p>No Club</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <ul class="sidebar-menu">
                    <li><a href="playerdashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="playerprofile.php" class="active"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="#stats"><i class="fas fa-chart-line"></i> Stats</a></li>
                </ul>
            </div>
            
            <div class="dashboard-content">
                <section>
                    <h2><i class="fas fa-user"></i> My Profile</h2>
                    
                    <div class="profile-details">
                        <div class="profile-section">
                            <h3><i class="fas fa-id-card"></i> Personal Information</h3>
                            <p><strong>First Name:</strong> <?php echo htmlspecialchars($player['first_name']); ?></p>
                            <p><strong>Last Name:</strong> <?php echo htmlspecialchars($player['last_name']); ?></p>
                            <p><strong>Date of Birth:</strong> <?php echo date('M d, Y', strtotime($player['date_of_birth'])); ?></p>
                            <p><strong>Age:</strong> <?php echo $age; ?></p>
                        </div>
                        
                        <div class="profile-section">
                            <h3><i class="fas fa-running"></i> Football Information</h3>
                            <p><strong>Position:</strong> 
                                <span class="position-badge position-<?php echo getPositionClass($player['position']); ?>">
                                    <?php echo htmlspecialchars($player['position']); ?>
                                </span>
                            </p>
                            <?php if(!empty($player['skills'])): ?>
                                <p><strong>Skills:</strong><br>
                                    <?php 
                                    $skills = explode(',', $player['skills']);
                                    foreach($skills as $skill) {
                                        echo '<span class="skill-tag">' . htmlspecialchars(trim($skill)) . '</span>';
                                    }
                                    ?>
                                </p>
                            <?php endif; ?>
                            <p><strong>Status:</strong> 
                                <span class="<?php echo $player['status'] == 'active' ? 'text-success' : ($player['status'] == 'pending' ? 'text-warning' : 'text-danger'); ?>">
                                    <?php echo htmlspecialchars(ucfirst($player['status'])); ?>
                                </span>
                            </p>
                            <?php if($player['club_name']): ?>
                                <p><strong>Club:</strong> <?php echo htmlspecialchars($player['club_name']); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if($player['photo']): ?>
                            <div class="profile-section">
                                <h3><i class="fas fa-camera"></i> Profile Photo</h3>
                                <img src="<?php echo htmlspecialchars($player['photo']); ?>" alt="Player Photo" style="max-width: 200px; border-radius: 8px; border: 3px solid var(--primary-color);">
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
                
                <section id="stats" style="margin-top: 30px;">
                    <h2><i class="fas fa-chart-line"></i> Player Statistics</h2>
                    
                    <?php if($stats): ?>
                        <div class="stats-container">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $stats['goals'] ?? 0; ?></div>
                                <div class="stat-label">Goals</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $stats['assists'] ?? 0; ?></div>
                                <div class="stat-label">Assists</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $stats['appearances'] ?? 0; ?></div>
                                <div class="stat-label">Appearances</div>
                            </div>
                        </div>
                        
                        <div class="stats-details">
                            <h3>Detailed Statistics</h3>
                            <div class="stats-grid">
                                <div>
                                    <h4>Attacking</h4>
                                    <table class="stats-table">
                                        <tr>
                                            <th>Goals per match</th>
                                            <td><?php echo $stats['appearances'] > 0 ? round($stats['goals']/$stats['appearances'], 2) : 0; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Shots on target</th>
                                            <td><?php echo $stats['shots_on_target'] ?? 0; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Shot accuracy</th>
                                            <td><?php echo $stats['shot_accuracy'] ?? 0; ?>%</td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <div>
                                    <h4>Defensive</h4>
                                    <table class="stats-table">
                                        <tr>
                                            <th>Tackles</th>
                                            <td><?php echo $stats['tackles'] ?? 0; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Tackle success</th>
                                            <td><?php echo $stats['tackle_success'] ?? 0; ?>%</td>
                                        </tr>
                                        <tr>
                                            <th>Interceptions</th>
                                            <td><?php echo $stats['interceptions'] ?? 0; ?></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <div>
                                    <h4>Passing</h4>
                                    <table class="stats-table">
                                        <tr>
                                            <th>Pass accuracy</th>
                                            <td><?php echo $stats['pass_accuracy'] ?? 0; ?>%</td>
                                        </tr>
                                        <tr>
                                            <th>Key passes</th>
                                            <td><?php echo $stats['key_passes'] ?? 0; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Cross accuracy</th>
                                            <td><?php echo $stats['cross_accuracy'] ?? 0; ?>%</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="performance-chart">
                            <h3>Performance Trend</h3>
                            <canvas id="performanceChart" width="400" height="200"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No statistics available yet. Stats will appear after your first match.
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Football Club Management System. All rights reserved.</p>
        </div>
    </footer>

    <!-- Add Chart.js for performance chart -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        <?php if($stats): ?>
            // Get performance data for chart
            const ctx = document.getElementById('performanceChart').getContext('2d');
            const performanceChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Goals', 'Assists', 'Pass %', 'Tackles', 'Rating'],
                    datasets: [{
                        label: 'Performance Metrics',
                        data: [
                            <?php echo $stats['goals'] ?? 0; ?>,
                            <?php echo $stats['assists'] ?? 0; ?>,
                            <?php echo $stats['pass_accuracy'] ?? 0; ?>,
                            <?php echo $stats['tackles'] ?? 0; ?>,
                            <?php echo ($stats['rating'] ?? 0) * 10; ?>
                        ],
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(54, 162, 235, 1)'
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>