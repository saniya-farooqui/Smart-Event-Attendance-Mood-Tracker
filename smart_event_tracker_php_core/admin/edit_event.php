<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
$user = current_user();

if (!$user || $user['role'] !== 'admin') {
    $_SESSION['flash'] = 'Access denied. Only administrators can edit events.';
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$event_id = intval($_GET['id'] ?? 0);

if (!$event_id) {
    $_SESSION['flash'] = 'Invalid event ID for editing.';
    header('Location: manage_events.php');
    exit;
}

$event = null;

// --- 1. Fetch Existing Event Data ---
try {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        $_SESSION['flash'] = 'Event not found.';
        header('Location: manage_events.php');
        exit;
    }

    // Convert datetime format for the input field
    $datetime_local_val = str_replace(' ', 'T', $event['date']);

} catch (PDOException $e) {
    $_SESSION['flash'] = 'Database error fetching event: ' . $e->getMessage();
    header('Location: manage_events.php');
    exit;
}

// --- 2. Handle POST Request (Update Logic) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $venue = trim($_POST['venue'] ?? '');

    if (empty($title) || empty($description) || empty($date) || empty($venue)) {
        $_SESSION['flash'] = 'Please fill in all required fields.';
    } else {
        try {
            $sql = "UPDATE events SET title = ?, description = ?, date = ?, venue = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $description, $date, $venue, $event_id]);

            $_SESSION['flash'] = 'Event updated successfully!';
            
            // Redirect back to the manage list or view page
            header('Location: manage_events.php');
            exit;

        } catch (PDOException $e) {
            $_SESSION['flash'] = 'Database Error: Could not update event. ' . $e->getMessage();
        }
    }
    // Re-fetch event data after a failed POST to show latest form state
    // We can rely on $event being updated or the loop will fail
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event: <?= htmlspecialchars($event['title']) ?></title>
    <style>
        /* Use the same basic styling as add_event.php/view_event.php for consistency */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #001a33, #003366);
            color: white;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            width: 90%;
            max-width: 600px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
        }
        h2 {
            color: #ffc107;
            border-bottom: 2px solid #ffc107;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        form label {
            display: block;
            margin-top: 10px;
            margin-bottom: 5px;
            font-weight: 600;
        }
        input[type="text"], input[type="datetime-local"], textarea {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 1rem;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 0.8rem;
            border: none;
            background: #ffc107;
            color: #333;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }
        button:hover {
            background: #e0a800;
        }
        .flash-message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            text-align: center;
            color: white;
            background-color: #d9534f;
        }
        .btn-back {
            display: block;
            text-align: center;
            margin-top: 15px;
            padding: 10px;
            background: #17a2b8;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Editing: <?= htmlspecialchars($event['title']) ?></h2>

        <?php
        if (isset($_SESSION['flash'])) {
            $flash_color = (strpos($_SESSION['flash'], 'successfully') !== false) ? '#5cb85c' : '#d9534f';
            echo '<div class="flash-message" style="background-color: ' . $flash_color . ';">' . htmlspecialchars($_SESSION['flash']) . '</div>';
            unset($_SESSION['flash']);
        }
        ?>
        
        <form method="post">
            <label for="title">Event Title</label>
            <input type="text" id="title" name="title" required value="<?= htmlspecialchars($title ?? $event['title']) ?>">

            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5" required><?= htmlspecialchars($description ?? $event['description']) ?></textarea>
            
            <label for="venue">Venue/Location</label>
            <input type="text" id="venue" name="venue" required value="<?= htmlspecialchars($venue ?? $event['venue']) ?>">
            
            <label for="date">Date and Time</label>
            <input type="datetime-local" id="date" name="date" required value="<?= htmlspecialchars($date ?? $datetime_local_val) ?>">
            
            <button type="submit">Save Changes</button>
        </form>
        
        <a href="manage_events.php" class="btn-back">Cancel and Back to List</a>
    </div>

</body>
</html>