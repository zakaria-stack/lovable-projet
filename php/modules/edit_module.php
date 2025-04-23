<?php
require_once '../db/db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php', 'Please login to edit modules', 'error');
}

$error = '';
$success = '';

// Check if module ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('add_module.php', 'Invalid module selection', 'error');
}

$module_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Check if module belongs to current user
try {
    $stmt = $conn->prepare("SELECT id, name FROM modules WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(':id', $module_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $module = $stmt->fetch();
    
    if (!$module) {
        redirect('add_module.php', 'Module not found or access denied', 'error');
    }
} catch(PDOException $e) {
    redirect('add_module.php', 'Database error: ' . $e->getMessage(), 'error');
}

// Process edit module form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize_input($_POST['module_name']);
    
    // Validate module name
    if (empty($name)) {
        $error = "Module name is required";
    } else {
        try {
            // Check if module with same name already exists for this user (excluding current module)
            $stmt = $conn->prepare("SELECT id FROM modules WHERE name = :name AND user_id = :user_id AND id != :module_id");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':module_id', $module_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = "You already have a module with this name";
            } else {
                // Update the module
                $stmt = $conn->prepare("UPDATE modules SET name = :name, updated_at = NOW() WHERE id = :id AND user_id = :user_id");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':id', $module_id);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    $success = "Module updated successfully";
                    $module['name'] = $name; // Update the module name for display
                } else {
                    $error = "Failed to update module";
                }
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Get note count for this module
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as note_count FROM notes WHERE module_id = :module_id AND user_id = :user_id");
    $stmt->bindParam(':module_id', $module_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $noteCount = $stmt->fetch()['note_count'];
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $noteCount = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Module - Student Notes</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation -->
        <?php include_once '../includes/navigation.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <h1>Edit Module</h1>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Module Info -->
            <div class="module-info">
                <p><strong>Module:</strong> <?php echo $module['name']; ?></p>
                <p><strong>Notes in this module:</strong> <?php echo $noteCount; ?></p>
            </div>
            
            <!-- Edit Module Form -->
            <div class="form-container">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $module_id; ?>">
                    <div class="form-group">
                        <label for="module_name">Module Name</label>
                        <input type="text" name="module_name" id="module_name" value="<?php echo $module['name']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Update Module</button>
                        <a href="add_module.php" class="btn btn-secondary">Back to Modules</a>
                    </div>
                </form>
            </div>
            
            <!-- Quick Links -->
            <div class="quick-links">
                <h3>Quick Links</h3>
                <a href="../notes/view_notes.php?module_id=<?php echo $module_id; ?>" class="btn btn-primary">View Notes in this Module</a>
                <a href="../notes/add_note.php?module_id=<?php echo $module_id; ?>" class="btn btn-success">Add Note to this Module</a>
                <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $module_id; ?>)" class="btn btn-danger">Delete Module</a>
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
