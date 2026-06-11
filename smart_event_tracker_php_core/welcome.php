<?php
session_start();
require_once 'includes/db.php';

// check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch name from DB since it is not stored in session
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$userName = $row['name'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Welcome | Smart Event Tracker</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
    body {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      height: 100vh;
      background: linear-gradient(135deg, #001a33, #003366);
      display: flex;
      justify-content: center;
      align-items: center;
      color: white;
      text-align: center;
    }

    .container {
      background: rgba(255,255,255,0.1);
      backdrop-filter: blur(12px);
      padding: 3rem 4rem;
      border-radius: 20px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.3);
      animation: fadeIn 1s ease-in-out;
    }

    h1 {
      font-size: 2rem;
      margin-bottom: 1rem;
    }

    p {
      font-size: 1.1rem;
      margin-bottom: 2rem;
    }

    a {
      display: inline-block;
      background-color: #00bfff;
      color: white;
      text-decoration: none;
      padding: 0.8rem 1.5rem;
      border-radius: 8px;
      font-weight: 600;
      transition: 0.3s;
    }

    a:hover {
      background-color: #0099cc;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>🎉 You’ve Logged in Successfully!</h1>
    <p>Welcome back, <strong><?= htmlspecialchars($userName) ?></strong>!</p>
    <a href="index.php">Go to Dashboard</a> &nbsp;
    <a href="logout.php" style="background-color:#ff3333;">Logout</a>
  </div>
</body>
</html>
