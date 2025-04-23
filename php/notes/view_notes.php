<?php
require_once '../db/db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php', 'Please login to view notes', 'error');
}

$error = '';
$user_id = $_SESSION['user_id'];
$selected_module_id = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 0;

// Get user's modules
try {
    $stmt = $conn->prepare("SELECT id, name FROM modules WHERE user_id = :user_id ORDER BY name ASC");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $modules = $stmt->fetchAll();
    
    if (empty($modules)) {
        redirect('../modules/add_module.php', 'Please create a module before viewing notes', 'info');
    }
    
    // If no module is selected or invalid module, default to the first one
    if ($selected_module_id <= 0 || !array_filter($modules, function($m) use ($selected_module_id) { return $m['id'] == $selected_module_id; })) {
        $selected_module_id = $modules[0]['id'];
    }
    
    // Get the selected module name
    $selected_module_name = '';
    foreach ($modules as $module) {
        if ($module['id'] == $selected_module_id) {
            $selected_module_name = $module['name'];
            break;
        }
    }
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $modules = [];
}

// Get notes for the selected module
try {
    $stmt = $conn->prepare("SELECT id, title, SUBSTRING(content, 1, 150) as preview, created_at, updated_at 
                           FROM notes 
                           WHERE user_id = :user_id AND module_id = :module_id 
                           ORDER BY updated_at DESC");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':module_id', $selected_module_id);
    $stmt->execute();
    $notes = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $notes = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Notes - Student Notes</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation -->
        <?php include_once '../includes/navigation.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <h1>My Notes</h1>
            
            <?php if(isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                    <?php 
                        echo $_SESSION['message']; 
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Module Selection -->
            <div class="module-selector">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label for="module_id">Select Module</label>
                        <select name="module_id" id="module_id" onchange="this.form.submit()">
                            <?php foreach($modules as $module): ?>
                                <option value="<?php echo $module['id']; ?>" <?php echo ($module['id'] == $selected_module_id) ? 'selected' : ''; ?>>
                                    <?php echo $module['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
                
                <div class="module-actions">
                    <a href="../modules/edit_module.php?id=<?php echo $selected_module_id; ?>" class="btn btn-secondary">Edit Module</a>
                    <a href="add_note.php?module_id=<?php echo $selected_module_id; ?>" class="btn btn-primary">Add Note to <?php echo $selected_module_name; ?></a>
                </div>
            </div>
            
            <!-- Notes List -->
            <div class="notes-container">
                <h2>Notes in <?php echo $selected_module_name; ?></h2>
                
                <?php if(empty($notes)): ?>
                    <p>No notes found in this module. <a href="add_note.php?module_id=<?php echo $selected_module_id; ?>">Add your first note</a>.</p>
                <?php else: ?>
                    <div class="notes-grid">
                        <?php foreach($notes as $note): ?>
                            <div class="note-card">
                                <h3><a href="view_note.php?id=<?php echo $note['id']; ?>"><?php echo $note['title']; ?></a></h3>
                                <div class="note-preview">
                                    <?php 
                                    // Display preview with ellipsis if content is truncated
                                    echo nl2br(htmlspecialchars($note['preview']));
                                    if (strlen($note['preview']) >= 150) {
                                        echo '...';
                                    }
                                    ?>
                                </div>
                                <div class="note-meta">
                                    <p>Updated: <?php echo date('M d, Y', strtotime($note['updated_at'])); ?></p>
                                </div>
                                <div class="note-card-actions">
                                    <a href="view_note.php?id=<?php echo $note['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    <a href="edit_note.php?id=<?php echo $note['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                    <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $note['id']; ?>)" class="btn btn-sm btn-danger">Delete</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
