<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    if ($role == "admin") {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    }

    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $role;

        if ($role == "admin") {
            header("Location: admin/manage_events.php");
        } else {
            header("Location: dashboard.php");
        }

        exit();

    } else {
        $error = "Invalid credentials.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Login - Smart Event Tracker</title>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: #001a33;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

.container {
    width: 900px;
    height: 500px;
    display: flex;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(0,0,0,0.4);
}

/* LEFT IMAGE SECTION */
.left {
    flex: 1;
    background: url('assets/image/uim.jpg') center/cover no-repeat;
    position: relative;
}

.left::before {
    content: "";
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.4);
}

.left-content {
    position: absolute;
    bottom: 40px;
    left: 30px;
    color: white;
}

.left-content h2 {
    margin: 0;
    font-size: 28px;
}

.left-content p {
    margin-top: 10px;
    font-size: 14px;
}

/* RIGHT LOGIN SECTION */
.right {
    flex: 1;
    background: white;
    padding: 40px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.right h2 {
    margin-bottom: 20px;
    color: #003366;
}

input, select {
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 14px;
}

button {
    padding: 12px;
    background: #003366;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
}

button:hover {
    background: #001a33;
}

.error {
    background: #ff4d4d;
    color: white;
    padding: 8px;
    border-radius: 6px;
    margin-bottom: 15px;
    font-size: 13px;
}
</style>
</head>

<body>

<div class="container">

    <div class="left">
        <div class="left-content">
            <h2>Welcome Back</h2>
            <p>Login to manage events and attendance easily.</p>
        </div>
    </div>

    <div class="right">
        <h2>Login</h2>

        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <select name="role" required>
                <option value="">Select Role</option>
                <option value="admin">Administrator</option>
                <option value="student">Student</option>
            </select>

            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>

            <button type="submit">Log In</button>
        </form>
    </div>

</div>

</body>
</html>
