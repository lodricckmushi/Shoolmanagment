<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../config/connection.php';

// Assume student_id is stored in session
$student_id = $_SESSION['student_id'] ?? 1; // fallback for demo

// Fetch only modules not already enrolled by the student
$modules = [];
$res = $conn->query("SELECT m.module_id, m.module_name, m.module_code FROM modules m WHERE m.module_id NOT IN (SELECT module_id FROM student_module_enrollments WHERE student_id = $student_id)");

if ($res) {
  while ($row = $res->fetch_assoc()) {
    $modules[] = $row;
  }
}


// Handle registration
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['module_id'])) {
    $module_id = intval($_POST['module_id']);
    // Check if already registered
    $check = $conn->query("SELECT * FROM student_module_enrollments WHERE student_id=$student_id AND module_id=$module_id");
    if ($check && $check->num_rows === 0) {
      $conn->query("INSERT INTO student_module_enrollments (student_id, module_id) VALUES ($student_id, $module_id)");
      $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register Module</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .confetti-canvas { position: fixed; pointer-events: none; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 9999; }
  </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
  <!-- Home Button -->
  <a href="?page=studentdash" class="btn btn-primary position-fixed" style="top: 20px; left: 20px; z-index: 1050; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
    <i class="fas fa-home fa-lg"></i>
  </a>
  <div class="content-wrapper p-4">
    <h2>Register Module</h2>
    <?php if ($success): ?>
      <div class="alert alert-success">Module registered successfully!</div>
      <canvas class="confetti-canvas" id="confetti"></canvas>
    <?php endif; ?>
    <form method="post">
      <div class="form-group">
        <label for="module">Select Module</label>
        <select class="form-control" id="module" name="module_id" required>
          <option value="">-- Choose Module --</option>
          <?php foreach ($modules as $mod): ?>
            <option value="<?php echo $mod['module_id']; ?>"><?php echo htmlspecialchars($mod['module_name']) . " (" . htmlspecialchars($mod['module_code']) . ")"; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Register Module</button>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
// Simple confetti animation
function confettiEffect() {
  const canvas = document.getElementById('confetti');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;
  let pieces = [];
  for (let i = 0; i < 120; i++) {
    pieces.push({
      x: Math.random() * canvas.width,
      y: Math.random() * canvas.height - canvas.height,
      r: Math.random() * 6 + 4,
      d: Math.random() * 80 + 40,
      color: `hsl(${Math.random()*360},70%,60%)`,
      tilt: Math.random() * 10 - 10
    });
  }
  function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    for (let p of pieces) {
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, 2 * Math.PI);
      ctx.fillStyle = p.color;
      ctx.fill();
    }
    update();
  }
  let angle = 0;
  function update() {
    angle += 0.01;
    for (let p of pieces) {
      p.y += Math.cos(angle + p.d) + 2 + p.r/2;
      p.x += Math.sin(angle) * 2;
      if (p.y > canvas.height) {
        p.x = Math.random() * canvas.width;
        p.y = -10;
      }
    }
  }
  function loop() {
    draw();
    requestAnimationFrame(loop);
  }
  loop();
  setTimeout(()=>{canvas.style.display='none';}, 3000);
}
if (document.getElementById('confetti')) confettiEffect();
</script>
</body>
</html>
<?php $conn->close(); ?>
