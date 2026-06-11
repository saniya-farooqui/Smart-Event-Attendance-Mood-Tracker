<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$user = current_user();

// events attended
$stmt = $pdo->prepare("SELECT e.id, e.title, e.date, f.comment AS feedback, f.emoji
                       FROM events e
                       JOIN attendance a ON a.event_id = e.id
                       LEFT JOIN feedback f ON f.event_id = e.id AND f.user_id = a.user_id
                       WHERE a.user_id = ?
                       ORDER BY e.date DESC");
$stmt->execute([$user['id']]);
$attended = $stmt->fetchAll();

// all events (to show other events)
$all = $pdo->query("SELECT id, title, date FROM events ORDER BY date DESC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>My Events</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body{font-family:'Poppins',sans-serif;margin:0;background:linear-gradient(135deg,#001a33,#003366);color:#fff;min-height:100vh}
    .wrap{width:90%;max-width:1000px;margin:20px auto;padding:20px;background:rgba(255,255,255,0.08);border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.5)}
    h1{border-bottom:3px solid #00bfff;padding-bottom:10px;margin-bottom:10px}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px}
    .card{background:rgba(0,0,0,0.2);padding:20px;border-radius:10px}
    .card h3{color:#00bfff;margin-top:0;border-bottom:1px solid rgba(255,255,255,0.2);padding-bottom:8px;margin-bottom:15px}
    .list{list-style:none;padding:0}
    .list li{background:rgba(255,255,255,0.05);padding:10px;margin-bottom:8px;border-radius:6px;line-height:1.4}
    .btn{display:inline-block;padding:8px 12px;background:#00bfff;color:#fff;border-radius:6px;text-decoration:none;font-weight:600;transition:all .2s;font-size:0.9rem}
    .btn:hover{background:#0099cc}
    .small{font-size:0.9rem;color:#cfe9ff}
    @media (max-width: 768px){.grid{grid-template-columns:1fr}}
  </style>
</head>
<body>

  <div class="wrap">
    <h1>My Events</h1>
    <p class="small">Events you have marked attendance for and all events</p>

    <div class="grid">
      <div class="card">
        <h3>Attended Events</h3>
        <?php if(empty($attended)): ?>
          <p>No attendance records yet. 
            <!-- FIX: Path reverted to the correct root-level file -->
            <a class="btn" href="../attend.php">Mark Attendance</a>
          </p>
        <?php else: ?>
          <ul class="list">
            <?php foreach($attended as $a): ?>
              <li>
                <strong><?= htmlspecialchars($a['title']) ?></strong> — <?= htmlspecialchars($a['date']) ?>
                <?php if($a['emoji'] || $a['feedback']): ?>
                  <div style="margin-top:6px;font-size:0.95rem;color:#d6e9ff">
                    <?= $a['emoji'] ? htmlspecialchars($a['emoji']) . ' ' : '' ?><?= $a['feedback'] ? htmlspecialchars($a['feedback']) : '' ?>
                  </div>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <div class="card">
        <h3>All Events</h3>
        <ul class="list">
          <?php foreach($all as $e): ?>
            <li>
              <strong><?= htmlspecialchars($e['title']) ?></strong> — <?= htmlspecialchars($e['date']) ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    
    <p style="text-align:center; margin-top: 30px"><a href="dashboard.php" class="btn">Back to Dashboard</a></p>
  </div>

</body>
</html>