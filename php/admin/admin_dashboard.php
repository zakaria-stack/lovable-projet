<?php
require_once '../db/db.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../../index.php', 'You must be an admin to access this page', 'error');
}

// Get total counts for statistics
try {
    // Count total users (excluding admins)
    $stmt = $conn->prepare("SELECT COUNT(*) as user_count FROM users WHERE role = 'user'");
    $stmt->execute();
    $userCount = $stmt->fetch()['user_count'];
    
    // Count total modules
    $stmt = $conn->prepare("SELECT COUNT(*) as module_count FROM modules");
    $stmt->execute();
    $moduleCount = $stmt->fetch()['module_count'];
    
    // Count total notes
    $stmt = $conn->prepare("SELECT COUNT(*) as note_count FROM notes");
    $stmt->execute();
    $noteCount = $stmt->fetch()['note_count'];
    
    // Get recent users
    $stmt = $conn->prepare("SELECT id, username, email, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recentUsers = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Notes</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <div class="admin-container">
        <!-- Admin Sidebar -->
        <div class="admin-sidebar">
            <h2>Admin Panel</h2>
            <ul>
                <li class="active"><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="user_manage.php">Manage Users</a></li>
                <li><a href="../index.php">View Site</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
        
        <!-- Admin Content -->
        <div class="admin-content">
            <h1>Admin Dashboard</h1>
            
            <?php if(isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                    <?php 
                        echo $_SESSION['message']; 
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="admin-stats">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <p class="stat-number"><?php echo $userCount; ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Total Modules</h3>
                    <p class="stat-number"><?php echo $moduleCount; ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Total Notes</h3>
                    <p class="stat-number"><?php echo $noteCount; ?></p>
                </div>
            </div>
            
            <!-- Recent Users -->
            <div class="admin-recent">
                <h2>Recent Users</h2>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Join Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($recentUsers)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No users found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($recentUsers as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo $user['username']; ?></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="user_manage.php?edit=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div class="admin-actions">
                    <a href="user_manage.php" class="btn btn-primary">Manage All Users</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../../js/script.js"></script>
</body>
</html>
