<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$user = current_user();

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$points = isset($_GET['points']) ? intval($_GET['points']) : 0;
$status = $_GET['status'] ?? 'new'; // 'new' or 'already_submitted'

// fetch event details
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();
if (!$event) {
    // Fallback if event is not found, though unlikely
    header("Location: ../dashboard.php");
    exit;
}

$message = '';
if ($status === 'already_submitted') {
    $message = "You have already submitted feedback for <strong>" . htmlspecialchars($event['title']) . "</strong>. Thank you for your continued participation!";
    $title = "Feedback Already Submitted";
} else {
    $message = "Your feedback for <strong>" . htmlspecialchars($event['title']) . "</strong> has been submitted successfully!";
    $title = "Feedback Submitted";
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title) ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body{font-family:'Poppins',sans-serif;margin:0;background:linear-gradient(135deg,#001a33,#003366);color:#fff;display:flex;align-items:center;justify-content:center;height:100vh}
    .card{background:rgba(255,255,255,0.07);backdrop-filter:blur(8px);padding:35px;border-radius:16px;width:640px;box-shadow:0 8px 30px rgba(0,0,0,0.4);text-align:center}
    .icon{font-size:3rem;margin-bottom:15px}
    h2{margin:0 0 10px 0;font-size:1.8rem;color:#00bfff}
    p{margin:0 0 25px 0;font-size:1.1rem;color:#cfe9ff}
    .points{font-size:2.2rem;font-weight:600;color:#ffd966;margin:15px 0;padding:15px;border:2px solid #ffd966;border-radius:12px;display:inline-block}
    .btn{display:inline-block;padding:12px 24px;background:#00bfff;color:#fff;border-radius:10px;text-decoration:none;font-weight:600;transition:all .18s;margin:0 10px}
    .btn:hover{transform:translateY(-3px);background:#0099cc}
    .event-info{margin-top:20px;font-size:0.95rem}
    .event-info strong{display:block;margin-bottom:5px}
  </style>
</head>
<body>
  <div class="card">
    <div class="icon">🎉</div>
    <h2><?= htmlspecialchars($title) ?></h2>
    <p><?= $message ?></p>

    <?php if ($points > 0): ?>
        <p class="points">+<?= $points ?> Points Earned!</p>
        <p style="margin-top: 10px; color: #fff;">
            Keep attending and giving feedback to climb the <a href="../admin/leaderboard.php">Leaderboard</a>!
        </p>
    <?php elseif ($status !== 'already_submitted'): ?>
         <p style="color: #fff;">
            Thank you for your feedback. We appreciate your input!
        </p>
    <?php endif; ?>

    <div class="buttons">
      <a href="../dashboard.php" class="btn">Go to Dashboard</a>
      <a href="../attend.php" class="btn">Mark Another Attendance</a>
    </div>
    <div class="event-info">
        Event: <strong><?= htmlspecialchars($event['title']) ?></strong> | Date: <?= htmlspecialchars($event['date']) ?>
    </div>
  </div>
</body>
</html>