<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/sentiment.php'; // Sentiment analysis function ke liye require kiya

require_login();
$user = current_user();

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
if ($event_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

// fetch event details
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();
if (!$event) {
    die("Event not found.");
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $emoji = $_POST['emoji'] ?? '';
    $comment = trim($_POST['comment'] ?? '');

    if ($emoji === '') $errors[] = "Please select an emoji.";
    if ($comment === '') $errors[] = "Please write a comment.";

    if (empty($errors)) {
        // Check if feedback already submitted
        $check_fb = $pdo->prepare("SELECT id FROM feedback WHERE event_id = ? AND user_id = ?");
        $check_fb->execute([$event_id, $user['id']]);

        if ($check_fb->fetch()) {
            $_SESSION['flash'] = 'You have already submitted feedback for this event.';
            header("Location: dashboard.php");
            exit;
        }

        // Sentiment analysis
        $res = analyze_sentiment($comment);
        $sentiment = $res['label'];
        $score = $res['score'];

        // Insert feedback into database
        $stmt_ins = $pdo->prepare("INSERT INTO feedback (event_id, user_id, emoji, comment, sentiment, sentiment_score) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_ins->execute([$event_id, $user['id'], $emoji, $comment, $sentiment, $score]);

        // Update user points: 5 base points + 3 bonus points for positive sentiment
        $bonus = 5 + ($sentiment === 'positive' ? 3 : 0);
        $pdo->prepare("UPDATE users SET points = points + ? WHERE id = ?")->execute([$bonus, $user['id']]);

        $_SESSION['flash'] = 'Feedback submitted successfully! You earned ' . $bonus . ' points.';

        // **FIX: Redirection to Dashboard (or success page)**
        // Main reason for 'Not Found' error was a missing redirect after submission.
        header("Location: dashboard.php");
        // Agar aap chahte hain ki success page par jaye, toh is line ko badal kar:
        // header("Location: feedback_success.php");
        // kar sakte hain.
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Attendance Success & Feedback</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body{font-family:'Poppins',sans-serif;margin:0;background:linear-gradient(135deg,#001a33,#003366);color:#fff;display:flex;align-items:center;justify-content:center;height:100vh}
    .card{background:rgba(255,255,255,0.07);backdrop-filter:blur(8px);padding:32px;border-radius:16px;width:550px;box-shadow:0 8px 30px rgba(0,0,0,0.4)}
    h2{margin-top:0;color:#00bfff;font-size:1.8rem}
    .event-details{margin-bottom:20px;padding:15px;background:rgba(0,0,0,0.2);border-radius:8px}
    .event-details strong{display:block;font-size:1.2rem;margin-bottom:5px}
    .emoji-row{display:flex;justify-content:space-around;margin:15px 0}
    .emoji-btn{background:none;border:none;font-size:2.5rem;cursor:pointer;padding:8px;border-radius:8px;transition:transform .2s, background-color .2s}
    .emoji-btn:focus{outline:none}
    .emoji-btn.selected{background-color:rgba(0,191,255,0.3);transform:scale(1.1)}
    textarea{width:100%;padding:10px;margin:10px 0;border-radius:8px;border:1px solid #00bfff;resize:vertical;font-size:1rem;background:#003366;color:#fff}
    .submit{background:#00bfff;color:#fff;font-weight:600;padding:10px;border:none;border-radius:8px;cursor:pointer;width:100%;font-size:1.1rem;margin-top:10px}
    .errors{background:#ff6666;color:#fff;padding:10px;border-radius:8px;margin-bottom:15px;font-weight:600}
  </style>
</head>
<body>
  <div class="card">
    <h2>Attendance Marked!</h2>
    <div class="event-details">
      <strong>Event: <?= htmlspecialchars($event['title']) ?></strong>
      <small>Date: <?= htmlspecialchars($event['date']) ?></small>
    </div>

    <p>Please give quick feedback for this event:</p>

    <?php if (!empty($errors)): ?>
      <div class="errors"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="emoji-row" role="radiogroup" aria-label="Feedback emoji">
        <button type="button" class="emoji-btn" onclick="selectEmoji(this,'😊')" title="Positive">😊</button>
        <button type="button" class="emoji-btn" onclick="selectEmoji(this,'😐')" title="Neutral">😐</button>
        <button type="button" class="emoji-btn" onclick="selectEmoji(this,'😞')" title="Negative">😞</button>
      </div>

      <input type="hidden" name="emoji" id="emojiInput" required value="<?= htmlspecialchars($_POST['emoji'] ?? '') ?>">

      <textarea name="comment" rows="5" placeholder="Write your feedback..." required><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>

      <button class="submit" name="submit_feedback" type="submit">Submit Feedback</button>
    </form>

    <p style="margin-top:12px"><a href="dashboard.php" style="color:#cfe9ff;text-decoration:underline">Back to dashboard</a></p>
  </div>

<script>
function selectEmoji(el, code){
  // remove selected class from all buttons
  document.querySelectorAll('.emoji-btn').forEach(btn => btn.classList.remove('selected'));
  
  // add selected class to the clicked button
  el.classList.add('selected');
  
  // set value to hidden input
  document.getElementById('emojiInput').value = code;
}

// Initial selection if form submitted with error
const initialEmoji = document.getElementById('emojiInput').value;
if (initialEmoji) {
    document.querySelectorAll('.emoji-btn').forEach(btn => {
        if (btn.textContent.trim() === initialEmoji) {
            btn.classList.add('selected');
        }
    });
}
</script>
</body>
</html>   