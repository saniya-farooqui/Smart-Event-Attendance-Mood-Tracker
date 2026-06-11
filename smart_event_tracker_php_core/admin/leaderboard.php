<?php
session_start();
require_once '../includes/db.php';

$stmt = $pdo->query("
    SELECT users.name, users.points, COUNT(attendance.id) AS total
    FROM users
    LEFT JOIN attendance ON users.id = attendance.user_id
    WHERE users.role = 'student'
    GROUP BY users.id
    ORDER BY total DESC, users.points DESC
");

$leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Leaderboard | Smart Event Tracker</title>
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

    .wrap {
      max-width: 800px;
      margin: 0 auto;
      padding: 2.5rem 1.5rem;
    }

    .page-title {
      text-align: center;
      margin-bottom: 2.5rem;
    }

    .page-title h2 {
      font-size: 2rem;
      font-weight: 700;
      color: #fff;
    }

    .page-title h2 span { color: #00bfff; }

    .page-title p {
      color: #aad4f5;
      font-size: 0.9rem;
      margin-top: 0.4rem;
    }

    /* ── PODIUM ── */
    .podium {
      display: flex;
      justify-content: center;
      align-items: flex-end;
      gap: 1rem;
      margin-bottom: 2.5rem;
    }

    .podium-card {
      background: rgba(255,255,255,0.07);
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 16px;
      padding: 1.5rem 1rem;
      text-align: center;
      flex: 1;
      max-width: 200px;
      transition: transform 0.2s;
    }

    .podium-card:hover { transform: translateY(-4px); }

    .podium-card.first {
      border-color: rgba(0,191,255,0.5);
      background: rgba(0,191,255,0.08);
      transform: translateY(-12px);
    }

    .podium-card.second { border-color: rgba(255,255,255,0.25); }
    .podium-card.third  { border-color: rgba(255,255,255,0.15); }

    /* Numbered rank badge replacing medal */
    .rank-badge {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      font-weight: 700;
      margin: 0 auto 0.8rem;
    }

    .rank-badge.r1 { background: rgba(0,191,255,0.25); color: #00bfff; border: 2px solid #00bfff; }
    .rank-badge.r2 { background: rgba(255,255,255,0.12); color: #cce9ff; border: 2px solid rgba(255,255,255,0.3); }
    .rank-badge.r3 { background: rgba(255,255,255,0.07); color: #aad4f5; border: 2px solid rgba(255,255,255,0.15); }

    .podium-name {
      font-size: 1rem;
      font-weight: 600;
      color: #fff;
      margin-bottom: 0.3rem;
    }

    .podium-count {
      font-size: 0.82rem;
      color: #aad4f5;
    }

    .podium-pts {
      display: inline-block;
      margin-top: 0.6rem;
      background: rgba(0,191,255,0.15);
      border: 1px solid rgba(0,191,255,0.3);
      color: #00bfff;
      font-size: 0.78rem;
      font-weight: 600;
      padding: 2px 10px;
      border-radius: 20px;
    }

    /* ── TABLE ── */
    .table-wrap {
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 16px;
      overflow: hidden;
    }

    table { width: 100%; border-collapse: collapse; }

    thead tr {
      background: rgba(0,191,255,0.1);
      border-bottom: 1px solid rgba(0,191,255,0.2);
    }

    thead th {
      padding: 1rem 1.4rem;
      font-size: 0.82rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #00bfff;
      font-weight: 600;
      text-align: left;
    }

    tbody tr {
      border-bottom: 1px solid rgba(255,255,255,0.06);
      transition: background 0.15s;
    }

    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: rgba(255,255,255,0.05); }

    tbody td { padding: 1rem 1.4rem; font-size: 0.9rem; color: #e0f0ff; }

    /* Inline numbered badge for table */
    .tbl-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 30px;
      height: 30px;
      border-radius: 50%;
      font-size: 0.82rem;
      font-weight: 700;
    }

    .tbl-badge.r1 { background: rgba(0,191,255,0.2);  color: #00bfff;  border: 1.5px solid #00bfff; }
    .tbl-badge.r2 { background: rgba(255,255,255,0.1); color: #cce9ff;  border: 1.5px solid rgba(255,255,255,0.3); }
    .tbl-badge.r3 { background: rgba(255,255,255,0.07);color: #aad4f5;  border: 1.5px solid rgba(255,255,255,0.15); }
    .tbl-badge.rn { background: transparent;           color: #7aa8cc;  border: 1.5px solid rgba(255,255,255,0.1); }

    /* Progress bar */
    .bar-wrap { display: flex; align-items: center; gap: 10px; }

    .bar-bg {
      flex: 1;
      height: 8px;
      background: rgba(255,255,255,0.08);
      border-radius: 99px;
      overflow: hidden;
    }

    .bar-fill {
      height: 100%;
      border-radius: 99px;
      background: linear-gradient(90deg, #005bb5, #00bfff);
      transition: width 0.4s ease;
    }

    .bar-label { font-size: 0.82rem; color: #aad4f5; white-space: nowrap; }

    .pts-pill {
      display: inline-block;
      background: rgba(0,191,255,0.12);
      border: 1px solid rgba(0,191,255,0.25);
      color: #00bfff;
      font-size: 0.8rem;
      font-weight: 600;
      padding: 2px 12px;
      border-radius: 20px;
    }

    .empty {
      text-align: center;
      padding: 2.5rem;
      color: #aad4f5;
      font-size: 0.9rem;
    }

    .back-wrap { margin-top: 2rem; text-align: center; }

    .btn-back {
      display: inline-block;
      padding: 0.7rem 1.8rem;
      border: 1px solid #00bfff;
      border-radius: 10px;
      color: #00bfff;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 600;
      transition: 0.2s;
    }

    .btn-back:hover { background: rgba(0,191,255,0.1); }

    @media (max-width: 600px) {
      .podium { gap: 0.6rem; }
      .podium-card { padding: 1rem 0.6rem; }
    }
  </style>
</head>
<body>

  <header>
    <a href="../index.php" class="brand">Smart Event Tracker</a>
    <nav>
      <a href="manage_events.php">Manage Events</a>
      <a href="add_event.php">Add Event</a>
    </nav>
  </header>

  <div class="wrap">

    <div class="page-title">
      <h2>Student <span>Leaderboard</span></h2>
      <p>Ranked by events attended</p>
    </div>

    <?php
      // Max attended for progress bar scaling
      $max = !empty($leaders) ? max(array_column($leaders, 'total')) : 1;
      $max = $max ?: 1;
    ?>

    <?php if (count($leaders) >= 1): ?>
      <!-- PODIUM: visual order 2nd, 1st, 3rd -->
      <div class="podium">
        <?php
          $podium_order = [1, 0, 2];
          $badge_class  = ['r1', 'r2', 'r3'];
          $card_class   = ['second', 'first', 'third'];
          foreach ($podium_order as $pos => $i):
            if (!isset($leaders[$i])) continue;
            $l = $leaders[$i];
        ?>
          <div class="podium-card <?= $card_class[$pos] ?>">
            <div class="rank-badge <?= $badge_class[$i] ?>"><?= $i + 1 ?></div>
            <div class="podium-name"><?= htmlspecialchars($l['name']) ?></div>
            <div class="podium-count"><?= $l['total'] ?> event<?= $l['total'] != 1 ? 's' : '' ?></div>
            <span class="podium-pts"><?= (int)$l['points'] ?> pts</span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- FULL TABLE -->
    <div class="table-wrap">
      <?php if (count($leaders) > 0): ?>
        <table>
          <thead>
            <tr>
              <th>Rank</th>
              <th>Student</th>
              <th>Attendance</th>
              <th>Points</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($leaders as $i => $row):
              $badge = $i === 0 ? 'r1' : ($i === 1 ? 'r2' : ($i === 2 ? 'r3' : 'rn'));
              $pct   = $max > 0 ? round(($row['total'] / $max) * 100) : 0;
            ?>
              <tr>
                <td><span class="tbl-badge <?= $badge ?>"><?= $i + 1 ?></span></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td>
                  <div class="bar-wrap">
                    <div class="bar-bg">
                      <div class="bar-fill" style="width: <?= $pct ?>%"></div>
                    </div>
                    <span class="bar-label"><?= (int)$row['total'] ?></span>
                  </div>
                </td>
                <td><span class="pts-pill"><?= (int)$row['points'] ?> pts</span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty">No students registered yet.</div>
      <?php endif; ?>
    </div>

    <div class="back-wrap">
      <a href="manage_events.php" class="btn-back">← Back to Admin Panel</a>
    </div>

  </div>

</body>
</html>