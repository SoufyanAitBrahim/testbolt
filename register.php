<?php
include 'includes/config.php';
include 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $phone = trim($_POST['phone']);
    $location = trim($_POST['location']);
    
    // Validate inputs
    if (empty($fullname) || empty($email) || empty($password) || empty($phone)) {
        $error = "Please fill all required fields!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT * FROM CLIENTS WHERE EMAIL = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Email already exists!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new client
            $stmt = $pdo->prepare("INSERT INTO CLIENTS (FULLNAME, PHONE_NUMBER, EMAIL, PASSWORD, LOCATION) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$fullname, $phone, $email, $hashed_password, $location])) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Something went wrong. Please try again!";
            }
        }
    }
}
?>

<div class="container">
    <h2>Register</h2>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="fullname" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Location (Optional)</label>
            <input type="text" name="location" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a></p>
</div>

<?php include 'includes/footer.php'; ?>