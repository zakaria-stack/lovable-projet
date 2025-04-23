<?php
require_once '../db/db.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../../index.php', 'You must be an admin to access this page', 'error');
}

$error = '';
$success = '';

// Handle user deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    try {
        // Delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = :id AND role != 'admin'");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $success = "User deleted successfully";
        } else {
            $error = "Failed to delete user or user is an admin";
        }
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Process user edit form
if (isset($_POST['update_user'])) {
    $id = (int)$_POST['id'];
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $role = sanitize_input($_POST['role']);
    
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
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = "Username already exists";
            } else {
                // Check if email already exists for other users
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $error = "Email already exists";
                } else {
                    // Update user
                    $stmt = $conn->prepare("UPDATE users SET username = :username, email = :email, role = :role WHERE id = :id");
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':role', $role);
                    $stmt->bindParam(':id', $id);
                    
                    if ($stmt->execute()) {
                        $success = "User updated successfully";
                    } else {
                        $error = "Failed to update user";
                    }
                }
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Process new user form
if (isset($_POST['create_user'])) {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $role = sanitize_input($_POST['role']);
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        try {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = "Username already exists";
            } else {
                // Check if email already exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $error = "Email already exists";
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert new user
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)");
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':password', $hashed_password);
                    $stmt->bindParam(':role', $role);
                    
                    if ($stmt->execute()) {
                        $success = "User created successfully";
                    } else {
                        $error = "Failed to create user";
                    }
                }
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Get user data if editing
$editUser = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    
    try {
        $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $editUser = $stmt->fetch();
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Get all users for display
try {
    $stmt = $conn->prepare("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Student Notes</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <div class="admin-container">
        <!-- Admin Sidebar -->
        <div class="admin-sidebar">
            <h2>Admin Panel</h2>
            <ul>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li class="active"><a href="user_manage.php">Manage Users</a></li>
                <li><a href="../index.php">View Site</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
        
        <!-- Admin Content -->
        <div class="admin-content">
            <h1>Manage Users</h1>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- User Form (Edit/Create) -->
            <div class="admin-form">
                <?php if($editUser): ?>
                    <h2>Edit User</h2>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
                        
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" value="<?php echo $editUser['username']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" value="<?php echo $editUser['email']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select name="role" id="role">
                                <option value="user" <?php echo ($editUser['role'] === 'user') ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo ($editUser['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                            <a href="user_manage.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                <?php else: ?>
                    <h2>Create New User</h2>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select name="role" id="role">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="create_user" class="btn btn-primary">Create User</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            
            <!-- User Table -->
            <div class="admin-table-container">
                <h2>All Users</h2>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Join Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($users)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No users found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo $user['username']; ?></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td><?php echo ucfirst($user['role']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="user_manage.php?edit=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <?php if($user['id'] != $_SESSION['user_id'] && $user['role'] != 'admin'): ?>
                                            <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $user['id']; ?>)" class="btn btn-sm btn-danger">Delete</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
    function confirmDelete(userId) {
        if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            window.location.href = 'user_manage.php?delete=' + userId;
        }
    }
    </script>
    
    <script src="../../js/script.js"></script>
</body>
</html>
