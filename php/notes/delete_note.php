<?php
require_once '../db/db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php', 'Please login to delete notes', 'error');
}

// Check if note ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('view_notes.php', 'Invalid note selection', 'error');
}

$note_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Get the module ID before deleting the note (for redirect)
try {
    $stmt = $conn->prepare("SELECT module_id FROM notes WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(':id', $note_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $note = $stmt->fetch();
    
    if (!$note) {
        redirect('view_notes.php', 'Note not found or access denied', 'error');
    }
    
    $module_id = $note['module_id'];
    
    // Delete the note
    $stmt = $conn->prepare("DELETE FROM notes WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(':id', $note_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        redirect('view_notes.php?module_id=' . $module_id, 'Note deleted successfully', 'success');
    } else {
        redirect('view_notes.php', 'Failed to delete note', 'error');
    }
} catch(PDOException $e) {
    redirect('view_notes.php', 'Database error: ' . $e->getMessage(), 'error');
}
?>
