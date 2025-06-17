<?php
include 'includes/config.php';
include 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $user_type = $_POST['user_type'];
    
    if (empty($email) || empty($password)) {
        $error = "Please enter email and password!";
    } else {
        // Check if admin or client
        if ($user_type == 'admin') {
            $table = 'ADMINS';
            $redirect = 'admin/dashboard.php';
        } else {
            $table = 'CLIENTS';
            $redirect = 'index.php';
        }
        
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE EMAIL = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['PASSWORD'])) {
            $_SESSION['user_id'] = $user['ID_'.strtoupper($table)];
            $_SESSION['user_type'] = $user_type;
            $_SESSION['user_name'] = $user['FULLNAME'];

            // Store admin role if user is admin
            if ($user_type == 'admin') {
                $_SESSION['admin_role'] = $user['ROLE'];
                $redirect = 'admin/dashboard_enhanced.php'; // Use enhanced dashboard
            }

            header("Location: $redirect");
            exit();
        } else {
            $error = "Invalid credentials!";
        }
    }
}
?>

<div class="container">
    <h2>Login</h2>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Login as:</label>
            <select name="user_type" class="form-control">
                <option value="client">Client</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
    <p>Don't have an account? <a href="register.php">Register here</a></p>
</div>

<?php include 'includes/footer.php'; ?>