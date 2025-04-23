<?php
require_once '../db/db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php', 'Please login to view notes', 'error');
}

// Check if note ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('view_notes.php', 'Invalid note selection', 'error');
}

$note_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Get the note data
try {
    $stmt = $conn->prepare("SELECT n.id, n.title, n.content, n.created_at, n.updated_at, 
                           m.id as module_id, m.name as module_name 
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $note['title']; ?> - Student Notes</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation -->
        <?php include_once '../includes/navigation.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <?php if(isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                    <?php 
                        echo $_SESSION['message']; 
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- Note Header -->
            <div class="note-header">
                <h1><?php echo $note['title']; ?></h1>
                <div class="note-meta">
                    <p><strong>Module:</strong> <?php echo $note['module_name']; ?></p>
                    <p><strong>Created:</strong> <?php echo date('M d, Y \a\t h:i A', strtotime($note['created_at'])); ?></p>
                    <p><strong>Last Updated:</strong> <?php echo date('M d, Y \a\t h:i A', strtotime($note['updated_at'])); ?></p>
                </div>
                <div class="note-actions">
                    <a href="edit_note.php?id=<?php echo $note['id']; ?>" class="btn btn-primary">Edit Note</a>
                    <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $note['id']; ?>)" class="btn btn-danger">Delete Note</a>
                    <a href="view_notes.php?module_id=<?php echo $note['module_id']; ?>" class="btn btn-secondary">Back to Module Notes</a>
                </div>
            </div>
            
            <!-- Note Content -->
            <div class="note-content">
                <?php 
                // Display the content with line breaks preserved
                echo nl2br(htmlspecialchars($note['content'])); 
                ?>
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
