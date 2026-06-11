<?php
require_once 'includes/auth.php';
require_login();
$user = current_user();

$events = $pdo->query('SELECT * FROM events ORDER BY date DESC')->fetchAll();

$stmt = $pdo->prepare('SELECT event_id FROM attendance WHERE user_id=?');
$stmt->execute([$user['id']]);
$att = $stmt->fetchAll(PDO::FETCH_COLUMN);

$total_events   = count($events);
$attended_count = count($att);
$pending_count  = $total_events - $attended_count;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | Smart Event Tracker</title>
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

    header .brand {
      font-size: 1.3rem;
      font-weight: 700;
      color: #00bfff;
      text-decoration: none;
    }

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

    .wrap {
      max-width: 1100px;
      margin: 0 auto;
      padding: 2.5rem 1.5rem;
    }

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
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .welcome-banner h2 { font-size: 1.7rem; color: #fff; }
    .welcome-banner h2 span { color: #00bfff; }
    .welcome-banner p { color: #aad4f5; margin-top: 0.3rem; font-size: 0.95rem; }

    .points-badge {
      background: linear-gradient(135deg, #003d80, #005bb5);
      border: 1px solid #00bfff;
      border-radius: 12px;
      padding: 1rem 1.8rem;
      text-align: center;
    }

    .points-badge .pts-num { font-size: 2rem; font-weight: 700; color: #00bfff; display: block; }
    .points-badge .pts-label { font-size: 0.8rem; color: #aad4f5; text-transform: uppercase; letter-spacing: 0.05em; }

    .stats {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.2rem;
      margin-bottom: 2.5rem;
    }

    .stat-card {
      background: rgba(255,255,255,0.07);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 14px;
      padding: 1.4rem 1.6rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      transition: transform 0.2s;
    }

    .stat-card:hover { transform: translateY(-3px); }

    .stat-icon {
      font-size: 1.8rem;
      width: 52px;
      height: 52px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .stat-icon.blue  { background: rgba(0,191,255,0.15); }
    .stat-icon.green { background: rgba(0,200,100,0.15); }
    .stat-icon.amber { background: rgba(255,180,0,0.15); }

    .stat-info .num { font-size: 1.8rem; font-weight: 700; color: #fff; line-height: 1; }
    .stat-info .lbl { font-size: 0.8rem; color: #aad4f5; margin-top: 4px; }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.2rem;
    }

    .section-header h3 {
      font-size: 1.2rem;
      color: #fff;
      border-left: 4px solid #00bfff;
      padding-left: 0.8rem;
    }

    .section-header a { font-size: 0.85rem; color: #00bfff; text-decoration: none; font-weight: 600; }

    .events-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 1.2rem;
    }

    .event-card {
      background: rgba(255,255,255,0.07);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 14px;
      padding: 1.4rem;
      transition: transform 0.2s, border-color 0.2s;
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .event-card:hover { transform: translateY(-4px); border-color: rgba(0,191,255,0.4); }

    .event-card .event-title {
      font-size: 1rem;
      font-weight: 600;
      color: #fff;
      text-decoration: none;
    }

    .event-card .event-title:hover { color: #00bfff; }
    .event-card .event-meta { font-size: 0.82rem; color: #aad4f5; line-height: 1.7; }

    .badge {
      display: inline-block;
      padding: 3px 12px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      margin-top: 0.3rem;
      align-self: flex-start;
    }

    .badge.attended { background: rgba(0,200,100,0.2); color: #00e676; border: 1px solid rgba(0,200,100,0.4); }
    .badge.pending  { background: rgba(255,180,0,0.15); color: #ffd54f; border: 1px solid rgba(255,180,0,0.3); }

    .actions { display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 2.5rem; }

    .btn {
      padding: 0.75rem 1.6rem;
      border-radius: 10px;
      font-size: 0.9rem;
      font-weight: 600;
      text-decoration: none;
      transition: 0.2s;
    }

    .btn-primary { background: #00bfff; color: #001a33; }
    .btn-primary:hover { background: #0099cc; }
    .btn-outline { background: transparent; color: #00bfff; border: 1px solid #00bfff; }
    .btn-outline:hover { background: rgba(0,191,255,0.1); }

    .empty {
      text-align: center;
      padding: 2.5rem;
      color: #aad4f5;
      font-size: 0.95rem;
      background: rgba(255,255,255,0.04);
      border-radius: 14px;
      border: 1px dashed rgba(255,255,255,0.1);
    }

    @media (max-width: 700px) {
      .stats { grid-template-columns: 1fr 1fr; }
      .welcome-banner { flex-direction: column; }
      header { flex-direction: column; gap: 0.8rem; }
    }

    @media (max-width: 450px) {
      .stats { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

  <header>
    <a href="index.php" class="brand">Smart Event Tracker</a>
    <nav>
      <a href="user/my_events.php">My Events</a>
      <a href="user/attend.php">Mark Attendance</a>
      <?php if ($user['role'] === 'admin'): ?>
        <a href="admin/manage_events.php">Admin Panel</a>
      <?php endif; ?>
      <a href="logout.php" class="logout">Logout</a>
    </nav>
  </header>

  <div class="wrap">

    <?php if (isset($_SESSION['flash'])): ?>
      <div class="flash"><?= htmlspecialchars($_SESSION['flash']) ?></div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="welcome-banner">
      <div>
        <h2>Welcome back, <span><?= htmlspecialchars($user['name']) ?></span> 👋</h2>
        <p>Here's an overview of your event activity.</p>
      </div>
      <div class="points-badge">
        <span class="pts-num"><?= (int)$user['points'] ?></span>
        <span class="pts-label">Total Points</span>
      </div>
    </div>

    <div class="stats">
      <div class="stat-card">
        <div class="stat-icon blue">📅</div>
        <div class="stat-info">
          <div class="num"><?= $total_events ?></div>
          <div class="lbl">Total Events</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div class="stat-info">
          <div class="num"><?= $attended_count ?></div>
          <div class="lbl">Attended</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon amber">⏳</div>
        <div class="stat-info">
          <div class="num"><?= $pending_count ?></div>
          <div class="lbl">Not Yet Attended</div>
        </div>
      </div>
    </div>

    <div class="section-header">
      <h3>All Events</h3>
      <a href="user/my_events.php">View my attended events →</a>
    </div>

    <?php if (count($events) > 0): ?>
      <div class="events-grid">
        <?php foreach ($events as $e): ?>
          <div class="event-card">
            <a href="view_event.php?id=<?= $e['id'] ?>" class="event-title">
              <?= htmlspecialchars($e['title']) ?>
            </a>
            <div class="event-meta">
              📍 <?= htmlspecialchars($e['venue'] ?? 'N/A') ?><br>
              🗓 <?= date('M d, Y @ h:i A', strtotime($e['date'])) ?>
            </div>
            <?php if (in_array($e['id'], $att)): ?>
              <span class="badge attended">✔ Attended</span>
            <?php else: ?>
              <span class="badge pending">⏳ Not attended</span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty">No events have been created yet. Check back soon!</div>
    <?php endif; ?>

    <div class="actions">
      <a href="user/attend.php" class="btn btn-primary">+ Mark Attendance</a>
      <a href="user/my_events.php" class="btn btn-outline">My Events</a>
      <?php if ($user['role'] === 'admin'): ?>
        <a href="admin/add_event.php" class="btn btn-outline">+ Add Event</a>
      <?php endif; ?>
    </div>

  </div>

</body>
</html>