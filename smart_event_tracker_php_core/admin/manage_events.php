<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$current_user = current_user();
if (!$current_user || $current_user['role'] !== 'admin') {
    $_SESSION['flash'] = 'Access denied.';
    header('Location: ../login.php');
    exit;
}

/* ==============================
   DELETE EVENT
============================== */
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    try {

        $pdo->beginTransaction();

        $stmt1 = $pdo->prepare("DELETE FROM attendance WHERE event_id = ?");
        $stmt1->execute([$id]);

        $stmt2 = $pdo->prepare("DELETE FROM feedback WHERE event_id = ?");
        $stmt2->execute([$id]);

        $stmt3 = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt3->execute([$id]);

        $pdo->commit();

        $_SESSION['flash'] = "Event deleted successfully!";

    } catch (PDOException $e) {

        $pdo->rollBack();
        $_SESSION['flash'] = "Error deleting event: " . $e->getMessage();
    }

    header("Location: manage_events.php");
    exit;
}

/* ==============================
   FETCH ALL EVENTS
============================== */
try {

    $stmt = $pdo->query("SELECT * FROM events ORDER BY date DESC");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $events = [];
    $_SESSION['flash'] = "Error loading events: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Events</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #001a33;
            color: white;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1100px;
            margin: auto;
            background: #002b55;
            padding: 20px;
            border-radius: 10px;
        }

        h2 {
            margin-top: 0;
            color: #00bfff;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .add-btn {
            background: #00bfff;
            padding: 8px 15px;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 10px;
            border-bottom: 1px solid #444;
        }

        th {
            background: #003d73;
        }

        .btn {
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 5px;
        }

        .btn-edit { background: orange; color: black; }
        .btn-delete { background: red; color: white; }
        .btn-view { background: teal; color: white; }

        .flash {
            padding: 10px;
            margin-bottom: 15px;
            background: green;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<div class="container">

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="flash">
            <?= htmlspecialchars($_SESSION['flash']) ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="top-bar">
        <h2>Manage Events</h2>
        <a href="add_event.php" class="add-btn">+ Add New Event</a>
    </div>

    <?php if (count($events) > 0): ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>

                <?php foreach ($events as $event): ?>

                <tr>
                    <td><?= htmlspecialchars($event['id']) ?></td>
                    <td><?= htmlspecialchars($event['title']) ?></td>
                    <td><?= htmlspecialchars($event['description']) ?></td>
                    <td><?= date('M d, Y', strtotime($event['date'])) ?></td>
                    <td>
                        <a href="view_event.php?id=<?= $event['id'] ?>" class="btn btn-view">View</a>
                        <a href="edit_event.php?id=<?= $event['id'] ?>" class="btn btn-edit">Edit</a>
                        <a href="?delete=<?= $event['id'] ?>" class="btn btn-delete"
                           onclick="return confirm('Are you sure you want to delete this event?')">
                           Delete
                        </a>
                    </td>
                </tr>

                <?php endforeach; ?>

            </tbody>
        </table>

    <?php else: ?>

        <p style="margin-top:20px;">No events have been created yet.</p>

    <?php endif; ?>

</div>

</body>
</html>
