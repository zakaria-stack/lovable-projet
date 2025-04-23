<?php
require_once '../db/db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php', 'Please login to view your profile', 'error');
}

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

// Get user data
try {
    $stmt = $conn->prepare("SELECT id, username, email, created_at FROM users WHERE id = :id");
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    
    $user = $stmt->fetch();
    
    if (!$user) {
        redirect('../auth/logout.php', 'User not found', 'error');
    }
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Get user statistics
try {
    // Count user's modules
    $stmt = $conn->prepare("SELECT COUNT(*) as module_count FROM modules WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $moduleCount = $stmt->fetch()['module_count'];
    
    // Count user's notes
    $stmt = $conn->prepare("SELECT COUNT(*) as note_count FROM notes WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $noteCount = $stmt->fetch()['note_count'];
    
    // Get recent notes
    $stmt = $conn->prepare("SELECT n.id, n.title, n.updated_at, m.name as module_name 
                           FROM notes n 
                           JOIN modules m ON n.module_id = m.id 
                           WHERE n.user_id = :user_id 
                           ORDER BY n.updated_at DESC LIMIT 5");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $recentNotes = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Process profile update form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($username) || empty($email)) {
        $error = "Username and email are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        try {
            // Check if username already exists for other users
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username AND id != :id");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = "Username already exists";
            } else {
                // Check if email already exists for other users
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $error = "Email already exists";
                } else {
                    // Get current password from database
                    $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id");
                    $stmt->bindParam(':id', $user_id);
                    $stmt->execute();
                    $current_password_hash = $stmt->fetch()['password'];
                    
                    // Check if current password is provided and is correct
                    if (!empty($current_password)) {
                        if (!password_verify($current_password, $current_password_hash)) {
                            $error = "Current password is incorrect";
                        } else {
                            // Check if new password is provided
                            if (!empty($new_password)) {
                                // Validate new password
                                if (strlen($new_password) < 6) {
                                    $error = "New password must be at least 6 characters long";
                                } elseif ($new_password !== $confirm_password) {
                                    $error = "New passwords do not match";
                                } else {
                                    // Update username, email and password
                                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                                    
                                    $stmt = $conn->prepare("UPDATE users SET username = :username, email = :email, password = :password WHERE id = :id");
                                    $stmt->bindParam(':username', $username);
                                    $stmt->bindParam(':email', $email);
                                    $stmt->bindParam(':password', $new_password_hash);
                                    $stmt->bindParam(':id', $user_id);
                                    
                                    if ($stmt->execute()) {
                                        $_SESSION['username'] = $username; // Update session variable
                                        $user['username'] = $username; // Update display variable
                                        $user['email'] = $email; // Update display variable
                                        $success = "Profile updated successfully with new password";
                                    } else {
                                        $error = "Failed to update profile";
                                    }
                                }
                            } else {
                                // Update username and email only
                                $stmt = $conn->prepare("UPDATE users SET username = :username, email = :email WHERE id = :id");
                                $stmt->bindParam(':username', $username);
                                $stmt->bindParam(':email', $email);
                                $stmt->bindParam(':id', $user_id);
                                
                                if ($stmt->execute()) {
                                    $_SESSION['username'] = $username; // Update session variable
                                    $user['username'] = $username; // Update display variable
                                    $user['email'] = $email; // Update display variable
                                    $success = "Profile updated successfully";
                                } else {
                                    $error = "Failed to update profile";
                                }
                            }
                        }
                    } else {
                        // No password change, just update username and email
                        $stmt = $conn->prepare("UPDATE users SET username = :username, email = :email WHERE id = :id");
                        $stmt->bindParam(':username', $username);
                        $stmt->bindParam(':email', $email);
                        $stmt->bindParam(':id', $user_id);
                        
                        if ($stmt->execute()) {
                            $_SESSION['username'] = $username; // Update session variable
                            $user['username'] = $username; // Update display variable
                            $user['email'] = $email; // Update display variable
                            $success = "Profile updated successfully";
                        } else {
                            $error = "Failed to update profile";
                        }
                    }
                }
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Student Notes</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation -->
        <?php include_once '../includes/navigation.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <h1>My Profile</h1>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="profile-container">
                <!-- User Statistics -->
                <div class="user-stats">
                    <h2>Your Statistics</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3>Modules</h3>
                            <p class="stat-number"><?php echo $moduleCount; ?></p>
                            <a href="../modules/add_module.php" class="btn btn-sm btn-primary">Manage Modules</a>
                        </div>
                        
                        <div class="stat-card">
                            <h3>Notes</h3>
                            <p class="stat-number"><?php echo $noteCount; ?></p>
                            <a href="../notes/view_notes.php" class="btn btn-sm btn-primary">View Notes</a>
                        </div>
                        
                        <div class="stat-card">
                            <h3>Member Since</h3>
                            <p><?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="recent-activity">
                    <h2>Recent Notes</h2>
                    <?php if(empty($recentNotes)): ?>
                        <p>You haven't created any notes yet. <a href="../notes/add_note.php">Create your first note</a>.</p>
                    <?php else: ?>
                        <ul class="activity-list">
                            <?php foreach($recentNotes as $note): ?>
                                <li>
                                    <strong><a href="../notes/view_note.php?id=<?php echo $note['id']; ?>"><?php echo $note['title']; ?></a></strong>
                                    <span class="note-module"><?php echo $note['module_name']; ?></span>
                                    <span class="note-date"><?php echo date('M d, Y', strtotime($note['updated_at'])); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                
                <!-- Edit Profile Form -->
                <div class="edit-profile">
                    <h2>Edit Profile</h2>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" value="<?php echo $user['username']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" value="<?php echo $user['email']; ?>" required>
                        </div>
                        
                        <h3>Change Password (Optional)</h3>
                        
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" name="current_password" id="current_password">
                            <small>Enter your current password to confirm changes or to set a new password</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" name="new_password" id="new_password">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../../js/script.js"></script>
</body>
</html>
