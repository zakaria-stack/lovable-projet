<?php
require_once '../db/db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php', 'Please login to add notes', 'error');
}

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];
$selected_module_id = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 0;

// Get user's modules for dropdown
try {
    $stmt = $conn->prepare("SELECT id, name FROM modules WHERE user_id = :user_id ORDER BY name ASC");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $modules = $stmt->fetchAll();
    
    if (empty($modules)) {
        redirect('../modules/add_module.php', 'Please create a module before adding notes', 'info');
    }
    
    // Verify if selected module belongs to user
    if ($selected_module_id > 0) {
        $moduleExists = false;
        foreach ($modules as $module) {
            if ($module['id'] == $selected_module_id) {
                $moduleExists = true;
                break;
            }
        }
        
        if (!$moduleExists) {
            $selected_module_id = 0;
        }
    }
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $modules = [];
}

// Process add note form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = sanitize_input($_POST['title']);
    $content = sanitize_input($_POST['content']);
    $module_id = (int)$_POST['module_id'];
    
    // Validate inputs
    if (empty($title) || empty($content) || $module_id <= 0) {
        $error = "All fields are required";
    } else {
        try {
            // Verify module belongs to user
            $stmt = $conn->prepare("SELECT id FROM modules WHERE id = :module_id AND user_id = :user_id");
            $stmt->bindParam(':module_id', $module_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                $error = "Invalid module selection";
            } else {
                // Insert the new note
                $stmt = $conn->prepare("INSERT INTO notes (user_id, module_id, title, content) VALUES (:user_id, :module_id, :title, :content)");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':module_id', $module_id);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':content', $content);
                
                if ($stmt->execute()) {
                    $note_id = $conn->lastInsertId();
                    redirect('view_note.php?id=' . $note_id, 'Note added successfully', 'success');
                } else {
                    $error = "Failed to add note";
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
    <title>Add Note - Student Notes</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation -->
        <?php include_once '../includes/navigation.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <h1>Add New Note</h1>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Add Note Form -->
            <div class="form-container">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label for="module_id">Select Module</label>
                        <select name="module_id" id="module_id" required>
                            <option value="">-- Select Module --</option>
                            <?php foreach($modules as $module): ?>
                                <option value="<?php echo $module['id']; ?>" <?php echo ($module['id'] == $selected_module_id) ? 'selected' : ''; ?>>
                                    <?php echo $module['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p><a href="../modules/add_module.php">Create new module</a></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="title">Note Title</label>
                        <input type="text" name="title" id="title" placeholder="e.g. Chapter 1 Summary" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Note Content</label>
                        <textarea name="content" id="content" rows="10" placeholder="Enter your note content here..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Add Note</button>
                        <a href="view_notes.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../../js/script.js"></script>
</body>
</html>
