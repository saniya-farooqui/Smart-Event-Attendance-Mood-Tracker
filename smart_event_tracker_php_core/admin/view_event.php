<?php
session_start();

/* ==========================
   CORRECT INCLUDE PATHS
========================== */

// If this file is in ROOT folder
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';


$events = [];

try {

    $stmt = $pdo->query("SELECT * FROM events ORDER BY date ASC");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // You can log error if needed
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upcoming Events</title>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

body {
    font-family: 'Poppins', sans-serif;
    background: #f4f4f9;
    color: #333;
    margin: 0;
    padding: 0;
}

.container {
    max-width: 900px;
    margin: 20px auto;
    padding: 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

h1 {
    color: #007bff;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
    margin-bottom: 30px;
    text-align: center;
}

.event-card {
    border: 1px solid #ccc;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 8px;
    background: #fff;
}

.event-card h2 {
    margin-top: 0;
}

.event-card strong {
    color: #007bff;
}

.btn-admin {
    display: inline-block;
    padding: 8px 15px;
    margin-top: 15px;
    background-color: #5cb85c;
    color: white;
    text-decoration: none;
    border-radius: 5px;
}
</style>
</head>
<body>

<div class="container">

<h1>Upcoming Events</h1>

<?php if (!empty($events)): ?>

    <?php foreach ($events as $event): ?>

        <div class="event-card">
            <h2><?= htmlspecialchars($event['title']) ?></h2>
            <p><strong>Date & Time:</strong>
                <?= date('F d, Y h:i A', strtotime($event['date'])) ?>
            </p>
            <p><strong>Venue:</strong>
                <?= htmlspecialchars($event['venue'] ?? 'Not specified') ?>
            </p>
            <p><?= htmlspecialchars($event['description']) ?></p>
        </div>

    <?php endforeach; ?>

<?php else: ?>

    <p style="text-align:center;">No events currently scheduled.</p>

<?php endif; ?>

<a href="<?= BASE_URL ?>/admin/manage_events.php" class="btn-admin">
    Go to Admin Panel
</a>

</div>

</body>
</html>
