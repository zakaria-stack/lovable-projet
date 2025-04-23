<?php
require_once '../db/db.php';

// Check if user is already logged in
if (isLoggedIn()) {
    header("Location: ../index.php");
    exit();
}

$error = '';
$success = '';

// Process registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate form data
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password']; // No need to sanitize password yet
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        try {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = "Username already exists";
            } else {
                // Check if email already exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $error = "Email already exists";
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert new user
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, 'user')");
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':password', $hashed_password);
                    
                    if ($stmt->execute()) {
                        $success = "Registration successful! You can now login.";
                    } else {
                        $error = "Registration failed. Please try again.";
                    }
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
    <title>Register - Student Notes</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-page register-page">
        <div class="auth-container">
            <div class="logo" style="text-align: center; margin-bottom: 20px;">
                <img src="../../images/student_notes_logo.svg" alt="Student Notes Logo" style="width: 60px;">
            </div>
            <h1>Create Account</h1>

            <?php if(!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if(!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="auth-form">
                <div class="form-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" id="username" placeholder="Username" required>
                </div>

                <div class="form-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" id="email" placeholder="Email address" required>
                </div>

                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" placeholder="Password" required>
                </div>

                <div class="form-group">
                    <i class="fas fa-check-circle"></i>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm password" required>
                </div>

                <button type="submit" class="btn-auth">Create Account</button>

                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>