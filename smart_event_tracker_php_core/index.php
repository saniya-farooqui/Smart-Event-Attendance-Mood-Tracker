<?php
include __DIR__ . '/includes/db.php';
include __DIR__ . '/includes/auth.php'; // Include auth to check login state

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = current_user(); // Check if user is logged in
$current_datetime = date('Y-m-d H:i:s');

// Fetch upcoming events
$upcoming_events = $pdo->prepare("SELECT * FROM events WHERE date >= ? ORDER BY date ASC");
$upcoming_events->execute([$current_datetime]);
$upcoming_events = $upcoming_events->fetchAll(PDO::FETCH_ASSOC);

// Fetch past events
$past_events = $pdo->prepare("SELECT * FROM events WHERE date < ? ORDER BY date DESC");
$past_events->execute([$current_datetime]);
$past_events = $past_events->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Event Attendance & Mood Tracker</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #001a33, #003366);
      color: #fff;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    header {
      width: 100%;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
    }

    header h1 a {
        color: #00bfff;
        text-decoration: none;
        font-size: 1.5rem;
    }

    header nav a {
        color: white;
        text-decoration: none;
        margin-left: 1rem;
        font-weight: 600;
        transition: color 0.3s;
    }
    
    header nav a:hover {
        color: #00bfff;
    }

    .hero {
      width: 100%;
      height: 40vh;
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      position: relative;
      overflow: hidden;
      margin-bottom: 2rem;
    }
    
    .hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('./assets/image/uim.jpg') center/cover no-repeat;
        filter: blur(3px);
        opacity: 0.6;
        z-index: 0;
    }

    .hero-content {
      position: relative;
      z-index: 1;
      animation: textFade 1.5s ease-out;
      padding: 2rem;
    }

    .hero h2 {
      font-size: 3rem;
      margin-bottom: 0.5rem;
      color: #00bfff;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
    }

    .hero p {
      font-size: 1.2rem;
      color: #fff;
      text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.7);
    }
    
    .content-wrap {
        width: 100%;
        max-width: 1200px;
        padding: 0 2rem;
    }

    .events-section {
      width: 100%;
      margin-bottom: 3rem;
    }

    .events-section h3 {
      border-bottom: 2px solid #00bfff;
      display: inline-block;
      margin-bottom: 1.5rem;
      padding-bottom: 0.3rem;
      font-size: 1.8rem;
    }

    .events-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 2rem;
    }

    .event-card {
      background: rgba(255, 255, 255, 0.15);
      border-radius: 10px;
      padding: 1.5rem;
      color: #fff;
      box-shadow: 0 4px 15px rgba(0,0,0,0.3);
      animation: fadeUp 0.8s ease-out forwards;
      opacity: 0; /* Start hidden for animation */
      transition: transform 0.3s ease;
    }
    
    .event-card:hover {
        transform: translateY(-5px);
    }

    .event-card h4 {
      margin: 0 0 0.5rem 0;
      color: #00bfff;
      font-size: 1.3rem;
    }

    .event-card p {
      font-size: 0.95rem;
      opacity: 0.9;
    }
    
    .event-card small {
        display: block;
        margin-top: 10px;
        font-style: italic;
        color: #ccc;
    }
    
    .no-events {
        font-style: italic;
        opacity: 0.8;
    }

    footer {
      width: 100%;
      padding: 1rem 0;
      text-align: center;
      background: rgba(0, 0, 0, 0.2);
      margin-top: auto;
    }

    @keyframes fadeUp {
      0% {opacity: 0; transform: translateY(30px);}
      100% {opacity: 1; transform: translateY(0);}
    }

    @keyframes textFade {
      0% {opacity: 0; transform: translateY(-20px);}
      100% {opacity: 1; transform: translateY(0);}
    }
    
    /* Responsive adjustments */
    @media (max-width: 600px) {
        .hero h2 {
            font-size: 2rem;
        }
        .hero p {
            font-size: 1rem;
        }
        header {
            flex-direction: column;
            gap: 0.5rem;
            padding: 1rem;
        }
        .content-wrap {
            padding: 0 1rem;
        }
    }
    
    /* Animation delay for cards */
    <?php foreach ($upcoming_events as $i => $event): ?>
    .event-card:nth-child(<?= $i + 1 ?>) {
        animation-delay: <?= 0.1 * $i ?>s;
    }
    <?php endforeach; ?>

  </style>
</head>
<body>

  <header>
    <h1><a href="index.php">Smart Event Tracker</a></h1>
    <nav>
      <?php if ($user): ?>
        <a href="user/dashboard.php">Dashboard</a>
        <?php if ($user['role'] === 'admin'): ?>
          <a href="admin/manage_events.php">Manage Events</a>
        <?php endif; ?>
        <a href="user/logout.php">Logout</a>
      <?php else: ?>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
      <?php endif; ?>
    </nav>
  </header>

  <div class="hero">
    <div class="hero-content">
      <h2>Welcome to UIM Events</h2>
      <p>Track attendance and gather real-time feedback effortlessly.</p>
    </div>
  </div>
  
  <div class="content-wrap">
    
    <?php if (isset($_SESSION['flash'])): ?>
        <div style="background-color: #28a745; color: white; padding: 15px; border-radius: 8px; margin-bottom: 2rem;">
            <?= htmlspecialchars($_SESSION['flash']) ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="events-section">
      <h3>Upcoming Events</h3>
      <div class="events-grid">
        <?php if (count($upcoming_events) > 0): ?>
          <?php foreach ($upcoming_events as $event): ?>
            <div class="event-card">
              <h4><?= htmlspecialchars($event['title']) ?></h4>
              <p><?= nl2br(htmlspecialchars(substr($event['description'], 0, 100))) ?><?php if (strlen($event['description']) > 100) echo '...'; ?></p>
              <small>
                Date: <?= date('F d, Y @ h:i A', strtotime($event['date'])) ?><br>
                Venue: <?= htmlspecialchars($event['venue'] ?? 'N/A') ?>
              </small>
              <?php if ($user): ?>
                  <a href="attend.php" style="display: block; margin-top: 1rem; color: #00bfff; font-weight: 600;">Mark Attendance &rarr;</a>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="no-events">No upcoming events scheduled right now. Check back soon!</p>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Optional: Display Past Events -->
    <div class="events-section" style="margin-top: 3rem;">
      <h3>Past Events</h3>
      <div class="events-grid">
        <?php if (count($past_events) > 0): ?>
          <?php foreach ($past_events as $event): ?>
            <div class="event-card" style="opacity: 0.7;">
              <h4><?= htmlspecialchars($event['title']) ?></h4>
              <p><?= nl2br(htmlspecialchars(substr($event['description'], 0, 100))) ?><?php if (strlen($event['description']) > 100) echo '...'; ?></p>
              <small>
                Date: <?= date('F d, Y', strtotime($event['date'])) ?><br>
                Venue: <?= htmlspecialchars($event['venue'] ?? 'N/A') ?>
              </small>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="no-events">No past events recorded.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <footer>
    <small>&copy; <?= date('Y') ?> Smart Event Attendance & Mood Tracker. All rights reserved.</small>
  </footer>

</body>
</html>