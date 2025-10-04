<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../config/connection.php';

// Fetch all announcements
$sql = "SELECT id, title FROM announcements ORDER BY created_at DESC";
$result = $conn->query($sql);
$announcements = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['announcement_id'])) {
    $id = intval($_POST['announcement_id']);
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = true;
    }
    $stmt->close();
    // Refresh announcements
    $result = $conn->query($sql);
    $announcements = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $announcements[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Delete Announcement</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    #confetti-canvas { position: fixed; pointer-events: none; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 9999; display: none; }
  </style>
</head>
<body class="hold-transition sidebar-mini">
<canvas id="confetti-canvas"></canvas>
<div class="wrapper">
  <!-- Home Button -->
  <a href="?page=instructordash" class="btn btn-danger position-fixed" style="top: 20px; left: 20px; z-index: 1050; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
    <i class="fas fa-home fa-lg"></i>
  </a>
  <div class="content-wrapper p-4">
    <h2>Delete Announcement</h2>
    <?php if ($success): ?>
      <div class="alert alert-success">Announcement deleted successfully!</div>
      <script>window.addEventListener('DOMContentLoaded',function(){document.getElementById('confetti-canvas').style.display='block';confettiRain();setTimeout(()=>{document.getElementById('confetti-canvas').style.display='none';},3000);});</script>
    <?php endif; ?>
    <form method="POST" action="?page=delete_announcement">
      <div class="form-group">
        <label for="delete-announcement">Select Announcement</label>
        <select class="form-control" id="delete-announcement" name="announcement_id" required>
          <option value="">-- Choose Announcement --</option>
          <?php foreach ($announcements as $a): ?>
            <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['title']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Delete Announcement</button>
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
