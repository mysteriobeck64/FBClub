<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Get request ID from URL if specified
$request_id = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;

// Handle request approval/rejection
if (isset($_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];
    $response_message = trim($_POST['response_message']);
    
    try {
        // Get the request details first
        $stmt = $pdo->prepare("SELECT * FROM coach_requests WHERE id = ?");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            throw new Exception("Request not found");
        }
        
        if ($action == 'approve') {
            // Update coach's club_id
            $stmt = $pdo->prepare("UPDATE coaches SET club_id = ? WHERE id = ?");
            $stmt->execute([$request['club_id'], $request['coach_id']]);
            
            // Update request status
            $stmt = $pdo->prepare("UPDATE coach_requests SET status = 'approved', 
                                 response_message = ?, response_date = NOW() 
                                 WHERE id = ?");
            $stmt->execute([$response_message, $request_id]);
            
            $_SESSION['message'] = '<div class="alert alert-success">Request approved successfully!</div>';
        } elseif ($action == 'reject') {
            // Update request status
            $stmt = $pdo->prepare("UPDATE coach_requests SET status = 'rejected', 
                                 response_message = ?, response_date = NOW() 
                                 WHERE id = ?");
            $stmt->execute([$response_message, $request_id]);
            
            $_SESSION['message'] = '<div class="alert alert-success">Request rejected successfully!</div>';
        }
        
        header("Location: admin_coach_requests.php");
        exit();
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Get the specific request if ID is provided
$current_request = null;
if ($request_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT cr.*, c.first_name, c.last_name, c.coach_type, 
                              cl.name as club_name, u.email
                              FROM coach_requests cr
                              JOIN coaches c ON cr.coach_id = c.id
                              JOIN clubs cl ON cr.club_id = cl.id
                              JOIN users u ON c.user_id = u.id
                              WHERE cr.id = ?");
        $stmt->execute([$request_id]);
        $current_request = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Get all pending coach requests if no specific request
if (!$current_request) {
    try {
        $stmt = $pdo->prepare("SELECT cr.*, c.first_name, c.last_name, c.coach_type, 
                              cl.name as club_name, u.email
                              FROM coach_requests cr
                              JOIN coaches c ON cr.coach_id = c.id
                              JOIN clubs cl ON cr.club_id = cl.id
                              JOIN users u ON c.user_id = u.id
                              WHERE cr.status = 'pending'
                              ORDER BY cr.request_date DESC");
        $stmt->execute();
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Coach Requests</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* [Previous CSS styles remain the same] */
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="index.php" class="logo">
                <i class="fas fa-futbol"></i> FCMS Admin
            </a>
            <nav>
                <ul>
                    <li><a href="admin.php">Dashboard</a></li>
                    <li><a href="admin_coach_requests.php">Coach Requests</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <h1><i class="fas fa-user-tie"></i> Coach Join Requests</h1>
        
        <?php if (isset($_SESSION['message'])) {
            echo $_SESSION['message'];
            unset($_SESSION['message']);
        } ?>
        
        <?php if ($current_request): ?>
            <a href="admin_coach_requests.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to all requests
            </a>
            
            <div class="request-card">
                <h3>
                    <?= htmlspecialchars($current_request['first_name'] . ' ' . $current_request['last_name']) ?>
                    <span class="badge-coach badge-<?= $current_request['coach_type'] == 'manager' ? 'manager' : 'assistant' ?>">
                        <?= $current_request['coach_type'] == 'manager' ? 'Manager' : 'Assistant Coach' ?>
                    </span>
                </h3>
                
                <p><strong>Requested to join:</strong> <?= htmlspecialchars($current_request['club_name']) ?></p>
                <p><strong>Request Date:</strong> <?= date('M d, Y H:i', strtotime($current_request['request_date'])) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($current_request['email']) ?></p>
                
                <?php if (!empty($current_request['request_message'])): ?>
                    <div class="message-box">
                        <p><strong>Coach's Message:</strong></p>
                        <p><?= htmlspecialchars($current_request['request_message']) ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="post" class="request-form">
                    <input type="hidden" name="request_id" value="<?= $current_request['id'] ?>">
                    
                    <div class="form-group">
                        <label for="response_message">Response Message:</label>
                        <textarea name="response_message" id="response_message" 
                                  class="form-control" rows="3" 
                                  placeholder="Optional message to the coach"></textarea>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" name="action" value="approve" class="btn btn-success">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button type="submit" name="action" value="reject" class="btn btn-danger">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <?php if (empty($requests)): ?>
                <div class="alert alert-info">
                    No pending coach requests at this time.
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Coach</th>
                            <th>Type</th>
                            <th>Club</th>
                            <th>Request Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?= htmlspecialchars($request['first_name'] . ' ' . $request['last_name']) ?></td>
                                <td><?= htmlspecialchars(ucfirst($request['coach_type'])) ?></td>
                                <td><?= htmlspecialchars($request['club_name']) ?></td>
                                <td><?= date('M d, Y', strtotime($request['request_date'])) ?></td>
                                <td>
                                    <a href="admin_coach_requests.php?request_id=<?= $request['id'] ?>" class="btn btn-primary">
                                        Manage Request
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> Football Club Management System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>