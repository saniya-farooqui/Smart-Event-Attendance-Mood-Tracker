<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$stmt0 = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt0->execute([$user_id]);
$user        = $stmt0->fetch(PDO::FETCH_ASSOC);
$user_name   = $user['name'] ?? 'User';
$memberSince = date("F Y", strtotime($user['created_at']));

// Events attended by this user
$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_attended = (int)$stmt->fetchColumn();

// Only count events that have already happened (today or past)
$past_events = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE DATE(date) <= CURDATE()")->fetchColumn();

// Not attended = past events minus attended ones
$not_attended = max(0, $past_events - $total_attended);

// Upcoming events (future only, for info)
$upcoming = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE DATE(date) > CURDATE()")->fetchColumn();

// Recent attended events
$stmt2 = $pdo->prepare("
    SELECT e.id, e.title, e.date, e.venue
    FROM events e
    JOIN attendance a ON a.event_id = e.id
    WHERE a.user_id = ?
    ORDER BY e.date DESC
    LIMIT 5
");
$stmt2->execute([$user_id]);
$recent = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Dashboard | Smart Event Tracker</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #001a33, #003366);
      color: #fff;
      min-height: 100vh;
    }

    header {
      width: 100%;
      background: rgba(255,255,255,0.07);
      backdrop-filter: blur(10px);
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }

    header .brand { font-size: 1.3rem; font-weight: 700; color: #00bfff; text-decoration: none; }

    header nav a {
      color: #cce9ff;
      text-decoration: none;
      margin-left: 1.2rem;
      font-size: 0.9rem;
      font-weight: 600;
      transition: color 0.2s;
    }

    header nav a:hover { color: #00bfff; }

    header nav a.logout {
      background: rgba(255,255,255,0.1);
      padding: 6px 14px;
      border-radius: 20px;
      border: 1px solid rgba(255,255,255,0.2);
    }

    header nav a.logout:hover { background: rgba(255,77,77,0.3); color: #ff6b6b; }

    .wrap { max-width: 1100px; margin: 0 auto; padding: 2.5rem 1.5rem; }

    .flash {
      background: rgba(0,200,100,0.15);
      border: 1px solid rgba(0,200,100,0.4);
      color: #00e676;
      padding: 12px 18px;
      border-radius: 10px;
      margin-bottom: 1.5rem;
      font-size: 0.9rem;
    }

    .welcome-banner {
      background: rgba(255,255,255,0.07);
      border: 1px solid rgba(0,191,255,0.25);
      border-radius: 16px;
      padding: 2rem 2.5rem;
      margin-bottom: 2rem;
    }

    .welcome-banner h2 { font-size: 1.7rem; color: #fff; }
    .welcome-banner h2 span { color: #00bfff; }
    .welcome-banner p { color: #aad4f5; margin-top: 0.4rem; font-size: 0.9rem; }

    /* Stats — 4 cards now */
    .stats {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1.2rem;
      margin-bottom: 2.5rem;
    }

    .stat-card {
      background: rgba(255,255,255,0.07);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 14px;
      padding: 1.4rem 1.2rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      transition: transform 0.2s;
    }

    .stat-card:hover { transform: translateY(-3px); }

    .stat-icon {
      font-size: 1.6rem;
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .stat-icon.blue   { background: rgba(0,191,255,0.15); }
    .stat-icon.green  { background: rgba(0,200,100,0.15); }
    .stat-icon.red    { background: rgba(255,80,80,0.15); }
    .stat-icon.purple { background: rgba(160,100,255,0.15); }

    .stat-info .num { font-size: 1.7rem; font-weight: 700; color: #fff; line-height: 1; }
    .stat-info .lbl { font-size: 0.78rem; color: #aad4f5; margin-top: 4px; }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.2rem;
    }

    .section-header h3 {
      font-size: 1.1rem;
      color: #fff;
      border-left: 4px solid #00bfff;
      padding-left: 0.8rem;
    }

    .section-header a { font-size: 0.85rem; color: #00bfff; text-decoration: none; font-weight: 600; }

    .event-list { display: flex; flex-direction: column; gap: 0.8rem; }

    .event-row {
      background: rgba(255,255,255,0.07);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 12px;
      padding: 1rem 1.4rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 0.5rem;
      transition: border-color 0.2s;
    }

    .event-row:hover { border-color: rgba(0,191,255,0.4); }
    .event-row .title { font-weight: 600; font-size: 0.95rem; color: #fff; text-decoration: none; }
    .event-row .title:hover { color: #00bfff; }
    .event-row .meta { font-size: 0.82rem; color: #aad4f5; margin-top: 2px; }

    .badge-attended {
      background: rgba(0,200,100,0.2);
      color: #00e676;
      border: 1px solid rgba(0,200,100,0.4);
      padding: 3px 12px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      white-space: nowrap;
    }

    .empty {
      text-align: center;
      padding: 2rem;
      color: #aad4f5;
      font-size: 0.9rem;
      background: rgba(255,255,255,0.04);
      border-radius: 12px;
      border: 1px dashed rgba(255,255,255,0.1);
    }

    .actions { display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 2.5rem; }

    .btn { padding: 0.75rem 1.6rem; border-radius: 10px; font-size: 0.9rem; font-weight: 600; text-decoration: none; transition: 0.2s; }
    .btn-primary { background: #00bfff; color: #001a33; }
    .btn-primary:hover { background: #0099cc; }
    .btn-outline { background: transparent; color: #00bfff; border: 1px solid #00bfff; }
    .btn-outline:hover { background: rgba(0,191,255,0.1); }
    .btn-danger { background: transparent; color: #ff6b6b; border: 1px solid #ff6b6b; }
    .btn-danger:hover { background: rgba(255,77,77,0.1); }

    @media (max-width: 900px) { .stats { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 700px) {
      .welcome-banner { padding: 1.5rem; }
      header { flex-direction: column; gap: 0.8rem; }
    }
    @media (max-width: 450px) { .stats { grid-template-columns: 1fr; } }
  </style>
</head>
<body>

  <header>
    <a href="../index.php" class="brand">Smart Event Tracker</a>
    <nav>
      <a href="my_events.php">My Events</a>
      <a href="attend.php">Mark Attendance</a>
      <a href="../admin/leaderboard.php">Leaderboard</a>
      <a href="logout.php" class="logout">Logout</a>
    </nav>
  </header>

  <div class="wrap">

    <?php if (isset($_SESSION['flash'])): ?>
      <div class="flash"><?= htmlspecialchars($_SESSION['flash']) ?></div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Welcome Banner — no points -->
    <div class="welcome-banner">
      <h2>Welcome back, <span><?= htmlspecialchars($user_name) ?></span> 👋</h2>
      <p>Member since <?= $memberSince ?> &nbsp;•&nbsp; Keep attending events to stay on top!</p>
    </div>

    <!-- 4 stat cards — no points card -->
    <div class="stats">
      <div class="stat-card">
        <div class="stat-icon blue">📅</div>
        <div class="stat-info">
          <div class="num"><?= $past_events ?></div>
          <div class="lbl">Past Events</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div class="stat-info">
          <div class="num"><?= $total_attended ?></div>
          <div class="lbl">Attended</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon red">❌</div>
        <div class="stat-info">
          <div class="num"><?= $not_attended ?></div>
          <div class="lbl">Missed</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon purple">🔜</div>
        <div class="stat-info">
          <div class="num"><?= $upcoming ?></div>
          <div class="lbl">Upcoming</div>
        </div>
      </div>
    </div>

    <!-- Recent Attended Events -->
    <div class="section-header">
      <h3>Recently Attended Events</h3>
      <a href="my_events.php">View all →</a>
    </div>

    <?php if (!empty($recent)): ?>
      <div class="event-list">
        <?php foreach ($recent as $e): ?>
          <div class="event-row">
            <div>
              <a href="../view_event.php?id=<?= $e['id'] ?>" class="title">
                <?= htmlspecialchars($e['title']) ?>
              </a>
              <div class="meta">
                📍 <?= htmlspecialchars($e['venue'] ?? 'N/A') ?> &nbsp;|&nbsp;
                🗓 <?= date('M d, Y', strtotime($e['date'])) ?>
              </div>
            </div>
            <span class="badge-attended">✔ Attended</span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty">You haven't attended any events yet. Mark your first attendance below!</div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="actions">
      <a href="attend.php" class="btn btn-primary">+ Mark Attendance</a>
      <a href="my_events.php" class="btn btn-outline">My Events</a>
      <a href="../admin/leaderboard.php" class="btn btn-outline">Leaderboard</a>
      <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

  </div>

</body>
</html>