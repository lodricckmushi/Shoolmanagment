<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../config/connection.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("❌ You must be logged in to post an announcement.");
}

$user_id = $_SESSION['user_id'];
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];

    // Insert with user_id and created_at
    $sql = "INSERT INTO announcements (title, content, user_id, created_at) 
            VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $message = "Prepare failed: " . $conn->error;
    } else {
        $stmt->bind_param("ssi", $title, $content, $user_id);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>✅ Announcement posted successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>❌ Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Post Announcement</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="hold-transition sidebar-mini">
<canvas id="confetti-canvas" style="position:fixed;pointer-events:none;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:none;"></canvas>
<div class="wrapper">
  <!-- Home Button -->
  <a href="?page=instructordash" class="btn btn-primary position-fixed" style="top: 20px; left: 20px; z-index: 1050; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
    <i class="fas fa-home fa-lg"></i>
  </a>
  <div class="content-wrapper p-4">
    <h2>Post Announcement</h2>
    <?php if ($message) {
      echo $message;
      if (strpos($message, 'successfully')) {
        echo "<script>window.addEventListener('DOMContentLoaded',function(){document.getElementById('confetti-canvas').style.display='block';confettiRain();setTimeout(()=>{document.getElementById('confetti-canvas').style.display='none';},3000);});</script>";
      }
    } ?>
    <form action="?page=post_announcement" method="POST">
      <div class="form-group">
        <label for="title">Announcement Title</label>
        <input type="text" class="form-control" id="title" name="title" placeholder="Enter title" required>
      </div>
      <div class="form-group">
        <label for="content">Announcement Content</label>
        <textarea class="form-control" id="content" name="content" rows="5" placeholder="Enter content" required></textarea>
      </div>
      <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> Post Announcement</button>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
  // Confetti animation (simple JS)
  function confettiRain() {
    const canvas = document.getElementById('confetti-canvas');
    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    let pieces = [];
    for(let i=0;i<150;i++){
      pieces.push({x:Math.random()*canvas.width,y:Math.random()*-canvas.height,w:8+Math.random()*8,h:8+Math.random()*8,vy:2+Math.random()*4,color:'hsl('+Math.random()*360+',70%,60%)',angle:Math.random()*2*Math.PI});
    }
    let frame=0;
    function draw(){
      ctx.clearRect(0,0,canvas.width,canvas.height);
      for(let p of pieces){
        ctx.save();
        ctx.translate(p.x,p.y);
        ctx.rotate(p.angle);
        ctx.fillStyle=p.color;
        ctx.fillRect(-p.w/2,-p.h/2,p.w,p.h);
        ctx.restore();
        p.y+=p.vy;
        p.angle+=0.02;
        if(p.y>canvas.height)p.y=-10;
      }
      frame++;
      if(frame<90)requestAnimationFrame(draw);
    }
    draw();
  }
</script>
</body>
</html>
<?php $conn->close(); ?>
