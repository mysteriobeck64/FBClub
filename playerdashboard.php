<?php
// [Previous code remains the same until the player details query]

// Get player details
try {
    $stmt = $pdo->prepare("SELECT p.*, c.name as club_name, c.id as club_id 
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
    
    // Get player statistics
    $stmt = $pdo->prepare("SELECT * FROM player_stats WHERE player_id = ?");
    $stmt->execute([$player['id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get all clubs for join request
    $stmt = $pdo->query("SELECT * FROM clubs");
    $clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // [Rest of the existing code remains the same]
?>

<!DOCTYPE html>
<html lang="en">
<!-- [Head section remains the same] -->
<body>
    <!-- [Header and sidebar remain the same] -->
    
    <div class="dashboard-content">
        <!-- [Existing sections remain the same] -->
        
        <!-- Add Statistics Section -->
        <section id="stats" style="margin-top: 30px;">
            <h2><i class="fas fa-chart-line"></i> Player Statistics</h2>
            
            <?php if($stats): ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Season Summary</h3>
                        <table class="stats-table">
                            <tr>
                                <th>Appearances</th>
                                <td><?php echo $stats['appearances'] ?? 0; ?></td>
                            </tr>
                            <tr>
                                <th>Goals</th>
                                <td><?php echo $stats['goals'] ?? 0; ?></td>
                            </tr>
                            <tr>
                                <th>Assists</th>
                                <td><?php echo $stats['assists'] ?? 0; ?></td>
                            </tr>
                            <tr>
                                <th>Yellow Cards</th>
                                <td><?php echo $stats['yellow_cards'] ?? 0; ?></td>
                            </tr>
                            <tr>
                                <th>Red Cards</th>
                                <td><?php echo $stats['red_cards'] ?? 0; ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Performance Metrics</h3>
                        <table class="stats-table">
                            <tr>
                                <th>Minutes Played</th>
                                <td><?php echo $stats['minutes_played'] ?? 0; ?></td>
                            </tr>
                            <tr>
                                <th>Pass Accuracy</th>
                                <td><?php echo $stats['pass_accuracy'] ?? 0; ?>%</td>
                            </tr>
                            <tr>
                                <th>Tackle Success</th>
                                <td><?php echo $stats['tackle_success'] ?? 0; ?>%</td>
                            </tr>
                            <tr>
                                <th>Shot Accuracy</th>
                                <td><?php echo $stats['shot_accuracy'] ?? 0; ?>%</td>
                            </tr>
                            <tr>
                                <th>Duels Won</th>
                                <td><?php echo $stats['duels_won'] ?? 0; ?>%</td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="recent-performance">
                    <h3>Recent Form</h3>
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM match_performance 
                                         WHERE player_id = ? 
                                         ORDER BY match_date DESC LIMIT 5");
                    $stmt->execute([$player['id']]);
                    $recent_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if($recent_performance): ?>
                        <div class="performance-bars">
                            <?php foreach($recent_performance as $match): ?>
                                <div class="match-performance">
                                    <div class="match-info">
                                        <span><?php echo date('M d', strtotime($match['match_date'])); ?></span>
                                        <span><?php echo htmlspecialchars($match['opponent']); ?></span>
                                        <span><?php echo $match['goals']; ?>⚽</span>
                                        <span><?php echo $match['assists']; ?>🅰️</span>
                                        <span class="rating"><?php echo $match['rating']; ?>/10</span>
                                    </div>
                                    <div class="performance-bar" style="width: <?php echo $match['rating'] * 10; ?>%"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No recent match data available.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No statistics available yet. Stats will appear after your first match.
                </div>
            <?php endif; ?>
        </section>
        
        <!-- [Rest of the existing code remains the same] -->
    </div>
    
    <!-- [Footer remains the same] -->
</body>
</html>