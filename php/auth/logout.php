<?php
require_once '../db/db.php';

// Simple logout script
session_start();
session_unset();     // unset $_SESSION variable for the run-time 
session_destroy();   // destroy session data in storage

// Get base URL for redirection
$base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$base_url .= "://" . $_SERVER['HTTP_HOST'];
$project_folder = dirname(dirname(dirname($_SERVER['PHP_SELF'])));
$project_folder = $project_folder === '\\' || $project_folder === '/' ? '' : $project_folder;

// Redirect to home page with full path
header("Location: $base_url$project_folder/index.php");
exit();
?>
