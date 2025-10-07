<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../config/connection.php';

// Fetch all announcements
$sql = "SELECT id, title, content, created_at FROM announcements ORDER BY created_at DESC";
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
        // Set a session flash message for success
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
    .confetti-canvas { position: fixed; pointer-events: none; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 9999; }
    .list-group-item-action { cursor: pointer; }
  </style>
</head>
<body class="hold-transition sidebar-mini">
<?php if ($success): ?><canvas id="confetti-canvas"></canvas><?php endif; ?>
<div class="wrapper">
  <!-- Home Button -->
  <a href="?page=instructordash" class="btn btn-warning position-fixed" style="top: 20px; left: 20px; z-index: 1050; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
    <i class="fas fa-home fa-lg"></i>
  </a>
  <div class="content-wrapper p-4">
    <h2>Edit Announcement</h2>
    
    <?php if ($success): ?>
      <div class="alert alert-success">The announcement was updated successfully.</div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-list-ul"></i> Select an Announcement to Edit</h5>
        </div>
        <div class="list-group list-group-flush">
            <?php if (empty($announcements)): ?>
                <div class="list-group-item">No announcements found.</div>
            <?php else: ?>
                <?php foreach ($announcements as $a): ?>
                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="text-primary"><?= htmlspecialchars($a['title']) ?></strong>
                            <br>
                            <small class="text-muted">Posted on: <?= date('M d, Y', strtotime($a['created_at'])) ?></small>
                        </div>
                        <button class="btn btn-warning btn-sm edit-btn" data-id="<?= $a['id'] ?>" data-title="<?= htmlspecialchars($a['title']) ?>" data-content="<?= htmlspecialchars($a['content']) ?>">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form method="POST" action="?page=edit_announcement">
        <div class="modal-header bg-warning text-white">
          <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Announcement</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit-announcement-id" name="announcement_id">
          <div class="form-group">
            <label for="edit-title">Title</label>
            <input type="text" class="form-control" id="edit-title" name="edit_title" required>
          </div>
          <div class="form-group">
            <label for="edit-content">Content</label>
            <textarea class="form-control" id="edit-content" name="edit_content" rows="8" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
  $(document).ready(function() {
    $('.edit-btn').on('click', function() {
      const id = $(this).data('id');
      const title = $(this).data('title');
      const content = $(this).data('content');

      $('#edit-announcement-id').val(id);
      $('#edit-title').val(title);
      $('#edit-content').val(content);

      $('#editModal').modal('show');
    });
  });

  // Advanced Confetti animation
  function confettiRain() {
    const canvas = document.getElementById('confetti-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    let pieces = [];
    for(let i=0;i<150;i++){
      pieces.push({x:Math.random()*canvas.width,y:Math.random()*-canvas.height,w:8+Math.random()*8,h:8+Math.random()*8,vy:2+Math.random()*4,color:'hsl('+Math.random()*360+',70%,60%)',angle:Math.random()*2*Math.PI});
    }
    let frame=0;
    function draw(){
      if (!canvas) return;
      ctx.clearRect(0,0,canvas.width,canvas.height);
      for(let p of pieces){
        ctx.save();
        ctx.translate(p.x,p.y);
        ctx.rotate(p.angle);
        ctx.fillStyle=p.color;
        ctx.fillRect(0, 0, p.w, p.h);
        ctx.restore();
        p.y+=p.vy;
        p.angle+=0.02;
        if(p.y > canvas.height) {
            p.y = -20;
            p.x = Math.random() * canvas.width;
        }
      }
      frame++;
      if(frame < 180) requestAnimationFrame(draw);
    }
    draw();
    // Remove the canvas after 3 seconds to prevent it from covering the page
    setTimeout(() => {
        if (canvas) canvas.remove();
    }, 3000);
  }
  <?php if ($success): ?>confettiRain();<?php endif; ?>
</script>
</body>
</html>
<?php $conn->close(); ?>
