<?php
require_once '../db/db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php', 'Please login to add modules', 'error');
}

$error = '';
$success = '';

// Process add module form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize_input($_POST['module_name']);
    $user_id = $_SESSION['user_id'];
    
    // Validate module name
    if (empty($name)) {
        $error = "Module name is required";
    } else {
        try {
            // Check if module with same name already exists for this user
            $stmt = $conn->prepare("SELECT id FROM modules WHERE name = :name AND user_id = :user_id");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = "You already have a module with this name";
            } else {
                // Insert the new module
                $stmt = $conn->prepare("INSERT INTO modules (name, user_id) VALUES (:name, :user_id)");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    $success = "Module added successfully";
                } else {
                    $error = "Failed to add module";
                }
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Get user's modules
try {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, name, created_at FROM modules WHERE user_id = :user_id ORDER BY name ASC");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $modules = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $modules = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Module - Student Notes</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation -->
        <?php include_once '../includes/navigation.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <h1>Add Module</h1>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Add Module Form -->
            <div class="form-container">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label for="module_name">Module Name</label>
                        <input type="text" name="module_name" id="module_name" placeholder="e.g. Mathematics, Web Development" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Add Module</button>
                    </div>
                </form>
            </div>
            
            <!-- Existing Modules -->
            <div class="modules-container">
                <h2>Your Modules</h2>
                
                <?php if(empty($modules)): ?>
                    <p>You don't have any modules yet. Start by adding one above!</p>
                <?php else: ?>
                    <div class="module-grid">
                        <?php foreach($modules as $module): ?>
                            <div class="module-card">
                                <h3><?php echo $module['name']; ?></h3>
                                <p>Created: <?php echo date('M d, Y', strtotime($module['created_at'])); ?></p>
                                <div class="module-actions">
                                    <a href="../notes/view_notes.php?module_id=<?php echo $module['id']; ?>" class="btn btn-sm btn-primary">View Notes</a>
                                    <a href="edit_module.php?id=<?php echo $module['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                    <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $module['id']; ?>)" class="btn btn-sm btn-danger">Delete</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    function confirmDelete(moduleId) {
        if (confirm('Are you sure you want to delete this module? All notes in this module will also be deleted. This action cannot be undone.')) {
            window.location.href = 'delete_module.php?id=' + moduleId;
        }
    }
    </script>
    
    <script src="../../js/script.js"></script>
</body>
</html>
