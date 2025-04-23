<?php
require_once '../db/db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php', 'Please login to edit notes', 'error');
}

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

// Check if note ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('view_notes.php', 'Invalid note selection', 'error');
}

$note_id = (int)$_GET['id'];

// Get the note data
try {
    $stmt = $conn->prepare("SELECT n.id, n.title, n.content, n.module_id, m.name as module_name 
                           FROM notes n 
                           JOIN modules m ON n.module_id = m.id 
                           WHERE n.id = :id AND n.user_id = :user_id");
    $stmt->bindParam(':id', $note_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $note = $stmt->fetch();
    
    if (!$note) {
        redirect('view_notes.php', 'Note not found or access denied', 'error');
    }
} catch(PDOException $e) {
    redirect('view_notes.php', 'Database error: ' . $e->getMessage(), 'error');
}

// Get user's modules for dropdown
try {
    $stmt = $conn->prepare("SELECT id, name FROM modules WHERE user_id = :user_id ORDER BY name ASC");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $modules = $stmt->fetchAll();
    
    if (empty($modules)) {
        redirect('../modules/add_module.php', 'Please create a module before editing notes', 'info');
    }
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $modules = [];
}

// Process edit note form
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
                // Update the note
                $stmt = $conn->prepare("UPDATE notes SET title = :title, content = :content, module_id = :module_id, updated_at = NOW() 
                                       WHERE id = :id AND user_id = :user_id");
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':content', $content);
                $stmt->bindParam(':module_id', $module_id);
                $stmt->bindParam(':id', $note_id);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    // Update the note data for display
                    $note['title'] = $title;
                    $note['content'] = $content;
                    $note['module_id'] = $module_id;
                    
                    // Get the new module name if module changed
                    if ($module_id != $note['module_id']) {
                        foreach ($modules as $module) {
                            if ($module['id'] == $module_id) {
                                $note['module_name'] = $module['name'];
                                break;
                            }
                        }
                    }
                    
                    $success = "Note updated successfully";
                } else {
                    $error = "Failed to update note";
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
    <title>Edit Note - Student Notes</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation -->
        <?php include_once '../includes/navigation.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <h1>Edit Note</h1>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Edit Note Form -->
            <div class="form-container">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $note_id; ?>">
                    <div class="form-group">
                        <label for="module_id">Select Module</label>
                        <select name="module_id" id="module_id" required>
                            <?php foreach($modules as $module): ?>
                                <option value="<?php echo $module['id']; ?>" <?php echo ($module['id'] == $note['module_id']) ? 'selected' : ''; ?>>
                                    <?php echo $module['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p><a href="../modules/add_module.php">Create new module</a></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="title">Note Title</label>
                        <input type="text" name="title" id="title" value="<?php echo $note['title']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Note Content</label>
                        <textarea name="content" id="content" rows="10" required><?php echo $note['content']; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Update Note</button>
                        <a href="view_note.php?id=<?php echo $note_id; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
            
            <!-- Quick Links -->
            <div class="quick-links">
                <h3>Quick Links</h3>
                <a href="view_note.php?id=<?php echo $note_id; ?>" class="btn btn-primary">View Note</a>
                <a href="view_notes.php?module_id=<?php echo $note['module_id']; ?>" class="btn btn-secondary">View All Notes in <?php echo $note['module_name']; ?></a>
                <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $note_id; ?>)" class="btn btn-danger">Delete Note</a>
            </div>
        </div>
    </div>
    
    <script>
    function confirmDelete(noteId) {
        if (confirm('Are you sure you want to delete this note? This action cannot be undone.')) {
            window.location.href = 'delete_note.php?id=' + noteId;
        }
    }
    </script>
    
    <script src="../../js/script.js"></script>
</body>
</html>
