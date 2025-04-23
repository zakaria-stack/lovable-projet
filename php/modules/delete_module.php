<?php
require_once '../db/db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php', 'Please login to delete modules', 'error');
}

// Check if module ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('add_module.php', 'Invalid module selection', 'error');
}

$module_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Check if module belongs to current user before deletion
try {
    $stmt = $conn->prepare("SELECT id FROM modules WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(':id', $module_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        redirect('add_module.php', 'Module not found or access denied', 'error');
    }
    
    // Begin transaction for safe deletion
    $conn->beginTransaction();
    
    // Delete all notes in this module
    $stmt = $conn->prepare("DELETE FROM notes WHERE module_id = :module_id AND user_id = :user_id");
    $stmt->bindParam(':module_id', $module_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    // Delete the module
    $stmt = $conn->prepare("DELETE FROM modules WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(':id', $module_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    // Commit the transaction
    $conn->commit();
    
    redirect('add_module.php', 'Module and all its notes deleted successfully', 'success');
} catch(PDOException $e) {
    // Rollback the transaction if something went wrong
    $conn->rollBack();
    redirect('add_module.php', 'Database error: ' . $e->getMessage(), 'error');
}
?>
