<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../config/connection.php';

// Ensure student is logged in and get their ID
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['email'])) {
    header('Location: ?page=login');
    exit;
}

$student_id = null;
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT student_id FROM students WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $student_id = $row['student_id'];
}
$stmt->close();

if (!$student_id) {
    // Display a relevant error message using the new error page
    $error_title = "Account Error";
    $error_message = "We couldn't find a student profile linked to your account.<br>Please contact support or try logging in again.";
    include(ROOT_PATH . '/views/error_display.php');
    exit;
}

// Fetch only modules not already enrolled by the student
$modules = [];
$stmt = $conn->prepare("SELECT m.module_id, m.module_name, m.module_code FROM modules m WHERE m.module_id NOT IN (SELECT module_id FROM student_module_enrollments WHERE student_id = ?)");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $modules = $result->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();

// Handle registration (multiple modules)
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['module_ids']) && is_array($_POST['module_ids'])) {
    $insert_stmt = $conn->prepare("INSERT INTO student_module_enrollments (student_id, module_id) VALUES (?, ?)");
    foreach ($_POST['module_ids'] as $module_id) {
        $module_id = intval($module_id);
        $insert_stmt->bind_param("ii", $student_id, $module_id);
        // The execute might fail if a unique constraint is violated (already registered), which is fine.
        if ($insert_stmt->execute()) {
            $success = true;
        }
    }
    $insert_stmt->close();
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
    .dual-list-box { display: flex; justify-content: space-between; align-items: center; }
    .list-wrapper { width: 45%; }
    .list-container {
        border: 1px solid #ddd;
        border-radius: 5px;
        height: 400px;
        overflow-y: auto;
    }
    .list-group-item { cursor: pointer; user-select: none; }
    .list-group-item.active { background-color: #007bff; color: white; border-color: #007bff; }
    .controls { width: 10%; text-align: center; }
    .confetti-canvas {
        position: fixed; pointer-events: none; top: 0; left: 0;
        width: 100vw; height: 100vh; z-index: 9999;
    }
  </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
  <!-- Home Button -->
  <a href="?page=studentdash" class="btn btn-primary position-fixed" style="top: 20px; left: 20px; z-index: 1050; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
    <i class="fas fa-home fa-lg"></i>
  </a>
  <div class="content-wrapper p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fas fa-plus-circle text-success"></i> Register Modules</h2>
    </div>
    <?php if ($success): ?>
      <div class="alert alert-success">Module registered successfully!</div>
      <canvas class="confetti-canvas" id="confetti"></canvas>
    <?php endif; ?>
    <form method="post" onsubmit="selectAllInList('selected-modules');">
        <div class="dual-list-box">
            <!-- Available Modules -->
            <div class="list-wrapper">
                <h6>Available Modules</h6>
                <input type="text" id="available-search" class="form-control mb-2" placeholder="Search available...">
                <ul id="available-modules" class="list-group list-container">
                    <?php foreach ($modules as $mod): ?>
                        <li class="list-group-item" data-id="<?= $mod['module_id'] ?>">
                            <?= htmlspecialchars($mod['module_name']) ?> <span class="text-muted">(<?= htmlspecialchars($mod['module_code']) ?>)</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Controls -->
            <div class="controls">
                <button type="button" id="add-module" class="btn btn-secondary mb-2 w-100">&gt;</button>
                <button type="button" id="remove-module" class="btn btn-secondary w-100">&lt;</button>
            </div>

            <!-- Selected Modules -->
            <div class="list-wrapper">
                <h6>Selected for Registration</h6>
                <select multiple name="module_ids[]" id="selected-modules" class="list-group list-container form-control" style="padding: 0;">
                    <!-- Items will be moved here -->
                </select>
            </div>
        </div>
      <button type="submit" class="btn btn-success btn-lg btn-block mt-4"><i class="fas fa-check-circle"></i> Register Selected Modules</button>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
$(document).ready(function() {
    function moveItems(fromList, toList) {
        const selectedItems = fromList.find('.active');
        selectedItems.each(function() {
            const itemId = $(this).data('id');
            const itemText = $(this).text();
            if (toList.is('select')) {
                // Move to select list
                $('<option>', { value: itemId, text: itemText }).appendTo(toList);
            } else {
                // Move to ul list
                $(this).removeClass('active').appendTo(toList);
            }
        });
        if (fromList.is('select')) {
            // Remove from select list
            fromList.find('option:selected').remove();
        } else {
            selectedItems.remove();
        }
    }

    // Toggle active class on click for UL
    $('#available-modules').on('click', '.list-group-item', function(e) {
        if (!e.ctrlKey) { $(this).siblings().removeClass('active'); }
        $(this).toggleClass('active');
    });

    // Move from available to selected
    $('#add-module').on('click', function() {
        moveItems($('#available-modules'), $('#selected-modules'));
    });

    // Move from selected to available
    $('#remove-module').on('click', function() {
        const selectedOptions = $('#selected-modules option:selected');
        selectedOptions.each(function() {
            const itemId = $(this).val();
            const itemText = $(this).text();
            $('<li>', { class: 'list-group-item', 'data-id': itemId, text: itemText }).appendTo('#available-modules');
        });
        selectedOptions.remove();
    });

    // Live search for available modules
    $('#available-search').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('#available-modules li').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(searchTerm));
        });
    });
});

// Select all items in the target list before form submission
function selectAllInList(listId) {
    $('#' + listId + ' option').prop('selected', true);
}

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
