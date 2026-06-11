<?php
// Set error reporting for development (REMOVE THIS LINE IN PRODUCTION)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Ensure session is started before using $_SESSION
// FIX: Use session_status() to prevent "session_start(): Ignoring..." notice
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
include __DIR__ . '/../includes/auth.php';  // Contains require_login() and session_start() protection
include __DIR__ . '/../includes/db.php';    // Contains $pdo connection
require_once __DIR__ . '/../includes/config.php'; // Contains BASE_URL

// Security and authorization check
require_login();
$user = current_user();

if (!$user || $user['role'] !== 'admin') {
    $_SESSION['flash'] = 'Access denied. Only administrators can add events.';
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}


// --- Form Submission Handling ---
// Initialize variables to retain post data
$title_val = '';
$description_val = '';
$venue_val = '';
$date_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $venue = trim($_POST['venue'] ?? '');

    // Retain POST data for redisplay on error
    $title_val = htmlspecialchars($title);
    $description_val = htmlspecialchars($description);
    $venue_val = htmlspecialchars($venue);
    // Note: $date_val retains the required datetime-local format 'YYYY-MM-DDT00:00'
    $date_val = htmlspecialchars($date); 

    if (empty($title) || empty($description) || empty($date) || empty($venue)) {
        $_SESSION['flash'] = 'Please fill in all required fields.';
    } else {
        try {
            $token = bin2hex(random_bytes(32));

            $sql = "INSERT INTO events (title, description, date, venue, token) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $description, $date, $venue, $token]);

            $_SESSION['flash'] = 'Event added successfully!';
            
            // FIX: Use relative path to manage_events.php (in the same folder) 
            // to avoid the double /admin/admin/ error we saw before.
            header('Location: manage_events.php');
            exit;

        } catch (PDOException $e) {
            // Note: If this error occurs, $date_val retains the submitted value to help re-submission
            $_SESSION['flash'] = 'Database Error: Could not add event. ' . $e->getMessage();
        }
    }
}
// --- End Form Submission Handling ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #001a33, #003366);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
        }
        .container {
            display: flex;
            width: 90%;
            max-width: 700px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        .left {
            flex: 1;
            /* NOTE: Check path to asset image is correct relative to /admin/ */
            background: url('../assets/image/uim.jpg') center/cover no-repeat;
            position: relative;
            min-height: 350px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .left h1 {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.6);
            z-index: 1;
            font-size: 2rem;
            font-weight: 600;
        }
        .right {
            flex: 1;
            background: white;
            color: #001a33;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        .right h2 {
            margin-bottom: 1rem;
            font-size: 1.8rem;
            color: #003366;
        }
        form {
            width: 100%;
            max-width: 300px;
        }
        input, textarea {
            width: 100%;
            padding: 0.8rem;
            margin: 0.5rem 0;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 1rem;
        }
        button {
            width: 100%;
            padding: 0.8rem;
            border: none;
            background: #003366;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: #004080;
        }
        /* Flash message style */
        .flash-message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            color: white;
            text-align: center;
        }
        
        /* New Styles for Action Buttons */
        .action-buttons {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
            max-width: 300px;
            margin-top: 1.5rem;
        }
        .action-buttons a {
            flex: 1;
            padding: 0.6rem 0.5rem;
            border: none;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
            text-align: center;
            text-decoration: none;
        }
        .btn-manage {
            background: #008080; 
            color: white;
        }
        .btn-view {
            background: #f0ad4e; 
            color: white;
        }
        .btn-logout {
            background: #d9534f; 
            color: white;
        }
        .btn-manage:hover { background: #006666; }
        .btn-view:hover { background: #ec971f; }
        .btn-logout:hover { background: #c9302c; }

        @media (max-width: 600px) {
            .container {
                flex-direction: column;
                width: 95%;
            }
            .left {
                min-height: 150px;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="left">
            <h1>New Event</h1>
        </div>

        <div class="right">
            <h2>Add Event Details</h2>

            <?php
            // Display flash messages (success or error)
            if (isset($_SESSION['flash'])) {
                $flash_color = (strpos($_SESSION['flash'], 'successfully') !== false) ? '#5cb85c' : '#d9534f';
                echo '<div class="flash-message" style="background-color: ' . $flash_color . ';">' . htmlspecialchars($_SESSION['flash']) . '</div>';
                unset($_SESSION['flash']);
            }
            ?>
            
            <form method="post">
                <input type="text" name="title" placeholder="Event Title" required value="<?= $title_val ?>">
                <textarea name="description" placeholder="Description" rows="3" required><?= $description_val ?></textarea>
                
                <input type="text" name="venue" placeholder="Venue/Location" required value="<?= $venue_val ?>">
                
                <input type="datetime-local" name="date" required value="<?= $date_val ?>">
                <button type="submit">Create Event</button>
            </form>
            
            <div class="action-buttons">
                <a href="manage_events.php" class="btn-manage">Manage Events</a>
                
                <a href="<?= BASE_URL ?>/view_event.php" class="btn-view">View Event</a>
                
                <a href="<?= BASE_URL ?>/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </div>

</body>
</html>