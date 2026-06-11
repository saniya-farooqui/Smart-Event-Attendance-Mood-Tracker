<?php 
require_once __DIR__.'/../includes/config.php'; // ADDED: Ensures BASE_URL is available
require_once __DIR__.'/../includes/auth.php'; 
require_login(); 
$user=current_user(); 
if($user['role']!=='admin'){ 
    $_SESSION['flash']='Only admin can access'; 
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit; 
} 
$id=intval($_GET['id']??0); 
$stmt=$pdo->prepare('SELECT * FROM events WHERE id=?'); 
$stmt->execute([$id]); 
$event=$stmt->fetch(); 

// Ensure db is included if not via auth.php (it is via auth.php, but check if pdo is available)
// auth.php includes db.php, so $pdo is globally available here.

$total=$pdo->prepare('SELECT COUNT(*) FROM attendance WHERE event_id=?'); 
$total->execute([$id]); 
$total_att=$total->fetchColumn(); 

$fb=$pdo->prepare('SELECT sentiment, COUNT(*) as cnt FROM feedback WHERE event_id=? GROUP BY sentiment'); 
$fb->execute([$id]); 
$sent=$fb->fetchAll(PDO::FETCH_KEY_PAIR); 

include __DIR__.'/../includes/header.php'; 
?>
<h2>Analytics — <?=htmlspecialchars($event['title'])?></h2>
<p>Total attendance: <?= (int)$total_att ?></p>

<h3>Sentiment counts</h3>
<ul>
<?php foreach(['positive','neutral','negative'] as $k): ?>
<li><?= $k ?>: <?= (int)($sent[$k] ?? 0) ?></li>
<?php endforeach; ?>
</ul>

<?php include __DIR__.'/../includes/footer.php'; ?>