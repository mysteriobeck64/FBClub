<?php
session_start();
require_once 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Football Club Management System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <div class="header-container">
            <a href="index.php" class="logo">
                <i class="fas fa-futbol"></i>  Football Club Management System
            </a>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#clubs">Clubs</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero" style="background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1574629810360-7efbbe195018?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80'); background-size: cover; background-position: center; color: white; padding: 100px 20px; text-align: center; border-radius: 8px; margin-bottom: 30px;">
            <h1 style="font-size: 3rem; margin-bottom: 20px;">Football Club Management System</h1>
            <p style="font-size: 1.2rem; max-width: 800px; margin: 0 auto 30px;">Manage your football club efficiently with our comprehensive management system. Track players, coaches, and teams all in one place.</p>
            <?php if(!isset($_SESSION['user_id'])): ?>
                <a href="register.php" class="btn" style="margin-right: 10px;">Register Now</a>
                <a href="login.php" class="btn btn-secondary">Login</a>
            <?php else: ?>
                <a href="dashboard.php" class="btn">Go to Dashboard</a>
            <?php endif; ?>
        </section>

        <section id="about" class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px; text-align: center;">About Our System</h2>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <div style="text-align: center; padding: 20px;">
                    <i class="fas fa-users" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 15px;"></i>
                    <h3>Player Management</h3>
                    <p>Efficiently manage player profiles, stats, and team assignments with our intuitive interface.</p>
                </div>
                <div style="text-align: center; padding: 20px;">
                    <i class="fas fa-clipboard-list" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 15px;"></i>
                    <h3>Team Coordination</h3>
                    <p>Keep track of team schedules, training sessions, and match fixtures all in one place.</p>
                </div>
                <div style="text-align: center; padding: 20px;">
                    <i class="fas fa-chart-line" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 15px;"></i>
                    <h3>Performance Analytics</h3>
                    <p>Analyze player and team performance with detailed statistics and reporting tools.</p>
                </div>
            </div>
        </section>

        <section id="clubs" class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px; text-align: center;">Featured Clubs</h2>
            <?php
            try {
                $stmt = $pdo->query("SELECT * FROM clubs LIMIT 3");
                $clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if($clubs) {
                    echo '<div class="team-grid">';
                    foreach($clubs as $club) {
                        echo '<div class="team-card">';
                        echo '<i class="fas fa-tshirt" style="font-size: 4rem; color: var(--primary-color); margin-bottom: 15px;"></i>';
                        echo '<h3>' . htmlspecialchars($club['name']) . '</h3>';
                        echo '<p>Founded: ' . htmlspecialchars($club['founded_year']) . '</p>';
                        echo '<p>Stadium: ' . htmlspecialchars($club['stadium']) . '</p>';
                        echo '</div>';
                    }
                    echo '</div>';
                    echo '<div style="text-align: center; margin-top: 20px;">';
                    echo '<a href="register.php" class="btn">Join a Club</a>';
                    echo '</div>';
                } else {
                    echo '<p style="text-align: center;">No clubs available at the moment.</p>';
                }
            } catch(PDOException $e) {
                echo '<p style="text-align: center;">Error loading clubs: ' . $e->getMessage() . '</p>';
            }
            ?>
        </section>

        <section id="contact" class="card">
            <h2 style="color: var(--primary-color); margin-bottom: 20px; text-align: center;">Contact Us</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <div>
                    <h3 style="margin-bottom: 15px;">Get in Touch</h3>
                    <p style="margin-bottom: 20px;">Have questions about our football club management system? Reach out to our team for more information.</p>
                    <div style="margin-bottom: 15px;">
                        <i class="fas fa-envelope" style="margin-right: 10px; color: var(--primary-color);"></i>
                        <span>info@fcms.com</span>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <i class="fas fa-phone" style="margin-right: 10px; color: var(--primary-color);"></i>
                        <span>+1 (555) 123-4567</span>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <i class="fas fa-map-marker-alt" style="margin-right: 10px; color: var(--primary-color);"></i>
                        <span>123 Soccer Street, Sports City</span>
                    </div>
                </div>
                <div>
                    <form action="#" method="post" style="display: grid; gap: 15px;">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" class="form-control" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn">Send Message</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Football Club Management System. All rights reserved.</p>
            <div style="margin-top: 10px;">
                <a href="#" style="color: white; margin: 0 10px;"><i class="fab fa-facebook-f"></i></a>
                <a href="#" style="color: white; margin: 0 10px;"><i class="fab fa-twitter"></i></a>
                <a href="#" style="color: white; margin: 0 10px;"><i class="fab fa-instagram"></i></a>
                <a href="#" style="color: white; margin: 0 10px;"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>
    </footer>
</body>
</html>