<?php
session_start();
require_once 'db_connect.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user details
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$user) {
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
    // Get additional info based on role
    if($role == 'player') {
        $stmt = $pdo->prepare("SELECT p.*, c.name as club_name FROM players p LEFT JOIN clubs c ON p.club_id = c.id WHERE p.user_id = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif($role == 'coach') {
        $stmt = $pdo->prepare("SELECT c.*, cl.name as club_name FROM coaches c LEFT JOIN clubs cl ON c.club_id = cl.id WHERE c.user_id = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Redirect to specific dashboard if not admin
if($role == 'admin') {
    header('Location: admin.php');
    exit();
} elseif($role == 'player') {
    header('Location: playerdashboard.php');
    exit();
} elseif($role == 'coach') {
    header('Location: coachdashboard.php');
    exit();
}
?>