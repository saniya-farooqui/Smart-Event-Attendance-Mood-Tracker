<?php
session_start();
include __DIR__ . '/includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "All fields are required.";
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    }
    elseif (!in_array($role, ['student', 'admin'])) {
        $error = "Invalid role selected.";
    }
    else {
        try {

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $error = "Email already registered!";
            } 
            else {

                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare(
                    "INSERT INTO users (name, email, password, role, created_at)
                     VALUES (?, ?, ?, ?, NOW())"
                );

                $stmt->execute([$name, $email, $hashed_password, $role]);

                /* AUTO LOGIN AFTER REGISTRATION */

                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['user_email'] = $email;
                $_SESSION['role'] = $role;

                if ($role === 'admin') {
                    header("Location: admin/manage_events.php");
                } else {
                    header("Location: dashboard.php");
                }

                exit;
            }

        } catch (Exception $e) {
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
<title>Register | Smart Event Tracker</title>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #001a33, #003366);
    color: #fff;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

.container {
    display: flex;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    width: 90%;
    max-width: 760px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}

.left {
    flex: 1;
    background: url('./assets/image/uim.jpg') center/cover;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 2rem;
    min-height: 380px;
}

.left h2 { color: white; margin-bottom: 0.5rem; }
.left p { color: #f0f0f0; text-align: center; }

.right {
    flex: 1;
    padding: 2rem;
    background: white;
    color: #001a33;
}

.right h3 {
    margin-top: 0;
    color: #003366;
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
}

form {
    display: flex;
    flex-direction: column;
}

input, select {
    padding: 0.8rem;
    margin-bottom: 1rem;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 1rem;
}

button {
    padding: 0.9rem;
    border: none;
    border-radius: 8px;
    background-color: #003366;
    color: white;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
}

button:hover {
    background-color: #004d99;
}

.link {
    margin-top: 1rem;
    text-align: center;
}

.link a {
    color: #003366;
    text-decoration: none;
    font-weight: 600;
}

.error {
    color: #ff3333;
    margin-bottom: 1rem;
    text-align: center;
    font-size: 0.9rem;
}

@media (max-width: 600px) {
    .container {
        flex-direction: column;
        max-width: 380px;
    }
    .left {
        min-height: 150px;
    }
}
</style>
</head>

<body>

<div class="container">

    <div class="left">
        <h2>Create your Account</h2>
        <p>Join the Smart Event Tracker and manage your attendance easily!</p>
    </div>

    <div class="right">
        <h3>Sign Up</h3>

        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required
                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">

            <input type="email" name="email" placeholder="Email" required
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

            <input type="password" name="password"
                placeholder="Password (Minimum 6 characters)" required minlength="6">

            <select name="role" required>
                <option value="">Select Role</option>
                <option value="student"
                    <?= (($_POST['role'] ?? '') === 'student') ? 'selected' : '' ?>>
                    Student
                </option>
                <option value="admin"
                    <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>
                    Administrator
                </option>
            </select>

            <button type="submit">Register</button>
        </form>

        <div class="link">
            Already have an account?
            <a href="login.php">Login here</a>
        </div>
    </div>

</div>

</body>
</html>