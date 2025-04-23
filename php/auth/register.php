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
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <img src="../../images/student_notes_logo.svg" alt="Student Notes Logo" class="site-logo">
                <h1>Student Note</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="../../index.php">Home</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li class="active"><a href="register.php">Register</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="auth-container">
            <h1>Register</h1>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Register</button>
                </div>
                
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </form>
        </div>
    </div>

    <!-- Modern animated footer -->
    <footer class="footer-animate" role="contentinfo">
        <div class="waves" aria-hidden="true">
            <div class="wave" id="wave1"></div>
            <div class="wave" id="wave2"></div>
            <div class="wave" id="wave3"></div>
            <div class="wave" id="wave4"></div>
        </div>
        <ul class="social-icon" aria-label="Social media">
            <li class="social-icon__item">
                <a class="social-icon__link" href="https://www.linkedin.com/in/zakaria-khattar-231262341" target="_blank" rel="noopener" aria-label="LinkedIn">
                    <svg width="35" height="35" fill="currentColor" aria-hidden="true" viewBox="0 0 24 24"><path d="M4.98 3.5C3.33 3.5 2 4.82 2 6.48c0 1.64 1.32 2.98 2.97 2.98h.02C6.62 9.46 7.95 8.12 7.95 6.48 7.94 4.82 6.61 3.5 4.98 3.5zM2.4 20.4h5.1V9.76H2.4V20.4zM9.58 9.76h4.89v1.44h.07c.68-1.17 2.33-2.4 4.8-2.4 5.13 0 6.08 3.38 6.08 7.78v4.82h-5.09v-4.27c0-1.02-.02-2.34-1.42-2.34-1.42 0-1.64 1.11-1.64 2.26v4.35h-5.1V9.76z"/></svg>
                </a>
            </li>
            <li class="social-icon__item">
                <a class="social-icon__link" href="https://github.com/zakaria-stack" target="_blank" rel="noopener" aria-label="GitHub">
                    <svg width="35" height="35" fill="currentColor" aria-hidden="true" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.58 2 12.26c0 4.5 2.87 8.32 6.84 9.67.5.09.68-.22.68-.48 0-.24-.01-.87-.01-1.71-2.78.62-3.37-1.36-3.37-1.36-.45-1.18-1.1-1.5-1.1-1.5-.9-.63.07-.62.07-.62 1 .07 1.53 1.03 1.53 1.03.89 1.56 2.34 1.11 2.91.85.09-.65.35-1.11.63-1.36-2.22-.26-4.56-1.14-4.56-5.08 0-1.12.39-2.03 1.03-2.75-.1-.26-.45-1.31.09-2.72 0 0 .84-.28 2.75 1.04a9.149 9.149 0 012.5-.3c.85 0 1.7.1 2.5.3 1.91-1.32 2.75-1.04 2.75-1.04.54 1.41.2 2.46.1 2.72.64.72 1.03 1.63 1.03 2.75 0 3.94-2.34 4.82-4.57 5.07.36.32.68.94.68 1.9 0 1.37-.01 2.48-.01 2.82 0 .26.18.58.69.48A10.26 10.26 0 0022 12.26C22 6.58 17.52 2 12 2z"/></svg>
                </a>
            </li>
            <li class="social-icon__item">
                <a class="social-icon__link" href="mailto:khattarzakaria@gmail.com" aria-label="Email">
                    <svg width="35" height="35" fill="currentColor" aria-hidden="true" viewBox="0 0 24 24"><path d="M20 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2zm-1.4 2L12 13.25 5.4 6h13.2zM4 18V8.34l7.2 7.59c.4.43 1.1.43 1.5 0L20 8.34V18H4z"/></svg>
                </a>
            </li>
        </ul>
        <ul class="menu" aria-label="Footer menu">
            <li class="menu__item"><a class="menu__link" href="../../index.php">Home</a></li>
            <li class="menu__item"><a class="menu__link" href="mailto:khattarzakaria@gmail.com">Contact Us</a></li>
        </ul>
        <p>&copy; 2025 Student Note. All rights reserved.</p>
    </footer>
</body>
</html>
