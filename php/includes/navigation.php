<?php
// Calculate base path for links (works in both Replit and XAMPP)
$base_path = dirname($_SERVER['PHP_SELF']);
$two_levels_up = substr_count($base_path, '/') >= 2;

// Define the paths based on the current directory depth
if (strpos($base_path, '/php/includes') !== false) {
    // We're in the includes directory
    $dashboard_path = "../index.php";
    $modules_path = "../modules/add_module.php";
    $notes_path = "../notes/view_notes.php";
    $add_note_path = "../notes/add_note.php";
    $profile_path = "../profile/profile.php";
    $admin_path = "../admin/admin_dashboard.php";
    $logout_path = "../auth/logout.php";
} elseif (strpos($base_path, '/php/') !== false && $two_levels_up) {
    // We're in a subdirectory of php (e.g., /php/notes/)
    $dashboard_path = "../index.php";
    $modules_path = "../modules/add_module.php";
    $notes_path = "../notes/view_notes.php";
    $add_note_path = "../notes/add_note.php";
    $profile_path = "../profile/profile.php";
    $admin_path = "../admin/admin_dashboard.php";
    $logout_path = "../auth/logout.php";
} else {
    // We're in the php directory
    $dashboard_path = "index.php";
    $modules_path = "modules/add_module.php";
    $notes_path = "notes/view_notes.php";
    $add_note_path = "notes/add_note.php";
    $profile_path = "profile/profile.php";
    $admin_path = "admin/admin_dashboard.php";
    $logout_path = "auth/logout.php";
}
?>

<!-- Navigation for dashboard pages -->
<div class="dashboard-nav">
    <div class="nav-header">
        <h2>Student Notes</h2>
        <div class="user-info">
            <span><?php echo $_SESSION['username']; ?></span>
        </div>
    </div>
    
    <ul class="nav-menu">
        <li><a href="<?php echo $dashboard_path; ?>">Dashboard</a></li>
        <li><a href="<?php echo $modules_path; ?>">Modules</a></li>
        <li><a href="<?php echo $notes_path; ?>">Notes</a></li>
        <li><a href="<?php echo $add_note_path; ?>">Add Note</a></li>
        <li><a href="<?php echo $profile_path; ?>">My Profile</a></li>
        <?php if(isAdmin()): ?>
            <li><a href="<?php echo $admin_path; ?>">Admin Panel</a></li>
        <?php endif; ?>
        <li><a href="<?php echo $logout_path; ?>">Logout</a></li>
    </ul>
</div>
