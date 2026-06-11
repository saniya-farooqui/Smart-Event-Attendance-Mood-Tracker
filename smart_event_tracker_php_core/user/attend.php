<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$user = current_user();
$message = '';

// Initialize event data variables
$event_id = 0; 
$event_data = null; 

// --- NEW: Handle Token (QR Code Scan) ---
// If a token is provided in the URL (e.g., from a QR code scan), auto-mark attendance
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    // 1. Find event by token
    $stmt = $pdo->prepare("SELECT id FROM events WHERE token = ?");
    $stmt->execute([$token]);
    $event_data = $stmt->fetch();

    if ($event_data) {
        $event_id = $event_data['id'];
        
        // 2. Check if already marked
        $check = $pdo->prepare("SELECT id FROM attendance WHERE event_id = ? AND user_id = ?");
        $check->execute([$event_id, $user['id']]);
        
        if ($check->fetch()) {
            // Already marked, redirect to success/feedback page
            header("Location: attendance_success.php?event_id={$event_id}&status=already_marked");
            exit;
        } else {
            // 3. Mark attendance
            $ins = $pdo->prepare("INSERT INTO attendance (event_id, user_id) VALUES (?, ?)");
            $ins->execute([$event_id, $user['id']]);
            
            // 4. Add points
            $pdo->prepare("UPDATE users SET points = points + 10 WHERE id = ?")->execute([$user['id']]);
            
            // 5. Redirect to success/feedback page
            header("Location: attendance_success.php?event_id={$event_id}");
            exit;
        }
    } else {
        $message = "Invalid or expired event QR code token. Please select the event manually below.";
    }
}
// --- END NEW: Handle Token (QR Code Scan) ---

// handle POST (mark attendance manually via dropdown) - Only runs if no token was successful
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = intval($_POST['event_id'] ?? 0);
    if ($event_id <= 0) {
        $message = "Please select a valid event.";
    } else {
        // check already marked
        $check = $pdo->prepare("SELECT id FROM attendance WHERE event_id = ? AND user_id = ?");
        $check->execute([$event_id, $user['id']]);
        if ($check->fetch()) {
            $message = "You have already marked attendance for this event.";
        } else {
            $ins = $pdo->prepare("INSERT INTO attendance (event_id, user_id) VALUES (?, ?)");
            $ins->execute([$event_id, $user['id']]);
            // optionally add points
            $pdo->prepare("UPDATE users SET points = points + 10 WHERE id = ?")->execute([$user['id']]);
            header("Location: attendance_success.php?event_id={$event_id}");
            exit;
        }
    }
}

// fetch events (upcoming and past) for the dropdown
$events = $pdo->query("SELECT id, title, date FROM events ORDER BY date DESC")->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mark Attendance</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body{font-family:'Poppins',sans-serif;margin:0;background:linear-gradient(135deg,#001a33,#003366);color:#fff;display:flex;align-items:center;justify-content:center;height:100vh}
    .box{background:rgba(255,255,255,0.06);padding:26px;border-radius:14px;backdrop-filter:blur(8px);width:760px;box-shadow:0 8px 30px rgba(0,0,0,0.4);display:flex;gap:20px}
    .img{flex:1;background:url('../assets/image/UIM.jpg') center/cover;border-radius:10px;min-height:260px}
    .form{flex:1;padding:6px 10px}
    h2{margin-top:0}
    select,button{width:100%;padding:10px;border-radius:10px;border:none;font-size:1rem;outline:none}
    select{margin:10px 0;background:#fff;color:#000}
    button{background:#00bfff;color:#fff;font-weight:600;cursor:pointer}
    button:hover { background: #0099cc; }
    .msg{margin-top:10px;color:#ffd966;font-weight:600}
  </style>
</head>
<body>
  <div class="box">
    <div class="img"></div>
    <div class="form">
      <h2>Mark Attendance</h2>
      <p>Select an event below and press <strong>Mark Attendance</strong>.</p>

      <?php if ($message): ?>
        <p class="msg"><?= htmlspecialchars($message) ?></p>
      <?php endif; ?>

      <form method="post">
        <select name="event_id" required>
          <option value="">-- Select Event --</option>
          <?php foreach ($events as $event_item): ?>
            <?php
              // Format date nicely
              $event_date = date('M d, Y', strtotime($event_item['date']));
              $event_title = htmlspecialchars($event_item['title']);
            ?>
            <option value="<?= $event_item['id'] ?>"><?= $event_title ?> (<?= $event_date ?>)</option>
          <?php endforeach; ?>
        </select>
        <button type="submit">Mark Attendance</button>
      </form>
    </div>
  </div>
</body>
</html>