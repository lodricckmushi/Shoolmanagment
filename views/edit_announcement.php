<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../config/connection.php';

// Fetch all announcements
$sql = "SELECT id, title, content FROM announcements ORDER BY created_at DESC";
$result = $conn->query($sql);
$announcements = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['announcement_id'], $_POST['edit_title'], $_POST['edit_content'])) {
    $id = intval($_POST['announcement_id']);
    $title = $_POST['edit_title'];
    $content = $_POST['edit_content'];
    $stmt = $conn->prepare("UPDATE announcements SET title=?, content=? WHERE id=?");
    $stmt->bind_param("ssi", $title, $content, $id);
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
  <title>Edit Announcement</title>
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
  <a href="?page=instructordash" class="btn btn-warning position-fixed" style="top: 20px; left: 20px; z-index: 1050; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
    <i class="fas fa-home fa-lg"></i>
  </a>
  <div class="content-wrapper p-4">
    <h2>Edit Announcement</h2>
    <?php if ($success): ?>
      <div class="alert alert-success">Announcement updated successfully!</div>
      <script>window.addEventListener('DOMContentLoaded',function(){document.getElementById('confetti-canvas').style.display='block';confettiRain();setTimeout(()=>{document.getElementById('confetti-canvas').style.display='none';},3000);});</script>
    <?php endif; ?>
    <form method="POST" action="?page=edit_announcement">
      <div class="form-group">
        <label for="select-announcement">Select Announcement</label>
        <select class="form-control" id="select-announcement" name="announcement_id" required onchange="fillEditFields()">
          <option value="">-- Choose Announcement --</option>
          <?php foreach ($announcements as $a): ?>
            <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['title']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="edit-title">Title</label>
        <input type="text" class="form-control" id="edit-title" name="edit_title" placeholder="Edit title" required>
      </div>
      <div class="form-group">
        <label for="edit-content">Content</label>
        <textarea class="form-control" id="edit-content" name="edit_content" rows="5" placeholder="Edit content" required></textarea>
      </div>
      <button type="submit" class="btn btn-warning"><i class="fas fa-edit"></i> Update Announcement</button>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
  // Fill edit fields when announcement is selected
  const announcements = <?php echo json_encode($announcements); ?>;
  function fillEditFields() {
    const sel = document.getElementById('select-announcement');
    const id = sel.value;
    const found = announcements.find(a => a.id == id);
    if (found) {
      document.getElementById('edit-title').value = found.title;
      document.getElementById('edit-content').value = found.content;
    } else {
      document.getElementById('edit-title').value = '';
      document.getElementById('edit-content').value = '';
    }
  }
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
