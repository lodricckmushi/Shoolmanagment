<?php
// Show all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'instructor') {
  header("Location: ?page=instructordash");
        exit;
    } elseif ($_SESSION['role'] === 'student') {
  header("Location: ?page=studentdash");
        exit;
    } elseif ($_SESSION['role'] === 'manager') {
  header("Location: ?page=hostel_manager47dash");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Course Management - Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">
  <style>
    body {
      min-height: 100vh;
      background: url('/assets/images/course1.jpg') center/cover no-repeat, linear-gradient(135deg, rgba(29, 43, 56, 0.95) 0%, rgba(255, 111, 60, 0.85) 100%);
      font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
      margin: 0;
      font-family: 'Poppins', 'Segoe UI', 'Roboto', sans-serif;
      padding: 0;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
    }
    .login-main {
      flex: 1 0 auto;
      display: flex;
      min-height: 100vh;
      align-items: center;
      justify-content: center;
      padding-top: 2.5vh;
      padding-bottom: 2.5vh;
    }
    .login-container-box {
      display: flex;
      flex-direction: row;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 2.5rem;
      box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.13);
      border: 1.5px solid rgba(255, 255, 255, 0.18);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      overflow: hidden;
      max-width: 1000px;
      width: 100%;
      min-height: 520px;
      margin-bottom: 2.5vh;
    }
    .login-left {
      flex: 1.2 1 0; /* Increase flex-grow to take more space */
      min-width: 220px;
      max-width: 550px; /* Increase max-width to allow expansion */
      background: transparent;
      color: #f8fafc;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: space-between; /* Positions title at top, footer at bottom */
      position: relative;
      text-align: center;
      padding: 2.5rem 1.2rem 1.5rem 1.2rem;
      overflow: hidden;
    }
    .login-bg-video {
      position: absolute;
      top: 50%;
      left: 50%;
      width: 100%;
      height: 100%;
      object-fit: cover;
      transform: translate(-50%, -50%);
      z-index: 1;
    }
    .video-overlay {
      position: absolute; top: 0; left: 0; width: 100%; height: 100%;
      background-color: rgba(29, 43, 56, 0.6); z-index: 2;
    }
    .login-left h1 {
      font-size: 1.8rem;
      font-weight: 700;
      letter-spacing: 1.5px;
      margin: 0;
      text-shadow: 0 2px 12px #2228;
      position: relative;
      z-index: 3;
      color: #f75b22ff; /* Changed color to match button accent */
    }
    .login-left-footer {
        margin-top: auto; /* Pushes footer to the bottom */
        font-size: 0.9rem;
        color: rgba(255, 255, 255, 0.7);
        position: relative;
        z-index: 3;
    }
    .login-divider {
      width: 6px;
      background: linear-gradient(180deg, #fff0 0%, #3a3f47 50%, #fff0 100%);
      border-radius: 8px;
      margin: 0 0.5rem;
      min-height: 80%;
      align-self: center;
      box-shadow: 0 0 16px #3a3f4744;
    }
    .login-right {
      flex: 1 1 0;
      min-width: 220px;
      max-width: 450px; /* Slightly reduce max-width */
      display: flex;
      align-items: center;      justify-content: center;
      margin: 0;
      position: relative;
    }
    .login-form-box {
      width: 100%;
      max-width: 340px;
      margin: 0 auto;
      padding: 2.2rem 1.1rem 1.2rem 1.1rem;
      background: rgba(255,255,255,0.15);
      border-radius: 1.5rem;
      box-shadow: 0 4px 24px rgba(0,0,0,0.1);
      border: 1px solid rgba(255,255,255,0.2);
      text-align: center;
    }
    .login-title {
      font-size: 2rem;
      font-weight: 700;
      color: #2a3a4a;
      margin-bottom: 0.5rem;
      letter-spacing: 1px;
    }
    .login-desc {
      color: #4a5a6a;
      font-size: 1.1rem;
      margin-bottom: 2rem;
    }
    .form-control {
      background: #f8fafc;
      border: 1.5px solid #e0e7ef;
      color: #222;
      border-radius: 1.5rem;
      font-size: 1.1rem;
      margin-bottom: 1.2rem;
      box-shadow: none;
    }
    .form-control:focus {
      background: #fff;
      color: #222;
      box-shadow: 0 0 0 2px #6b7a8f55;
    }
    .input-group-text {
      background: transparent;
      border: none;
      color: #3a4a5a;
    }
    .btn-advanced {
      background: linear-gradient(90deg, #3a4a5a 60%, #6b7a8f 100%);
      color: #fff;
      border: none;
      border-radius: 2rem;
      padding: 0.85rem 2.8rem;
      font-size: 1.15rem;
      font-weight: 600;
      box-shadow: 0 4px 16px #6b7a8f33;
      transition: background 0.2s, transform 0.2s;
      text-shadow: 0 1px 4px #0004;
      letter-spacing: 0.5px;
    }
    .btn-advanced:hover {
      background: linear-gradient(90deg, #6b7a8f 60%, #3a4a5a 100%);
      color: #fff;
      transform: scale(1.05);
    }
    .toggle-eye {
      cursor: pointer;
      color: #ff6f3c;
      font-size: 1.2rem;
    }

    @media (max-width: 600px) {
      body, .login-main {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        padding: 1rem;
      }
      .login-main { 
        width: 100%;
        min-height: auto;
      }
      .login-container-box { 
        flex-direction: column; 
        min-height: 0; 
        border-radius: 1.5rem; /* Consistent rounding */
        box-shadow: 0 4px 16px rgba(0,0,0,0.1); 
        width: 100%;
        max-width: 400px; /* Set a max-width for a centered card look */
        margin: 0; 
        backdrop-filter: none; /* Disable blur on mobile for performance */
        background: transparent; /* Make container transparent */
      }
      .login-divider { display: none; }
      .login-left, .login-right { 
        max-width: 100%; border-radius: 0; margin: 0; min-width: 0; 
        display: flex; flex-direction: column; justify-content: center;
      }
      .login-left { flex: 1; padding: 1.5rem 1rem; text-align: center; border-radius: 1.5rem 1.5rem 0 0; background: rgba(29, 43, 56, 0.7); }
      .login-left h1 { font-size: 1.7rem; margin-bottom: 0; margin-top: 0; font-weight: 700; }
      .login-right { 
        flex: 2; /* Make this panel twice as tall as the left one */
        padding: 2rem 1.5rem; background: #f8fafc; border-radius: 0 0 1.5rem 1.5rem; 
      }
      .login-form-box { max-width: 100%; padding: 0; border-radius: 0; background: transparent; border: none; box-shadow: none; color: #2a3a4a; }
      .login-title { font-size: 1.8rem; }
      .login-desc { font-size: 1rem; }
      .form-control { font-size: 1rem; border-radius: 1.2rem; margin-bottom: 1.2rem; }
      .btn-advanced { font-size: 1.1rem; padding: 0.8rem 1.5rem; border-radius: 1.5rem; }
    }
    @media (max-width: 400px) {
      .login-form-box { padding: 0.5rem 0.1rem 0.5rem 0.1rem; }
      .login-title { font-size: 0.98rem; }
      .login-desc { font-size: 0.9rem; }
      .btn-advanced { font-size: 0.95rem; padding: 0.5rem 0.7rem; }
    }

    /* Modal Error Popup */
    .modal-dialog {
      max-width: 340px; /* Reduced max-width for a smaller popup */
      margin: 0 auto;
    }
    .modal-content {
      border-radius: 1.5rem;
      border: none;
      box-shadow: 0 8px 32px rgba(0,0,0,0.15);
      background: #fff;
    }
    .modal-body {
      text-align: center;
      padding: 2rem 1.5rem; /* Reduced padding */
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    .modal-body i {
      font-size: 2.2rem; /* Reduced icon size */
      color: #f25c54; /* More modern, softer red */
      margin-bottom: 1rem;
      animation: bounceIn 0.6s ease;
    }
    .modal-body #loginErrorMsg {
      font-size: 1.05rem; /* Reduced font size */
      font-weight: 600;
      color: #2a3a4a;
    }

    /* Shake animation for error feedback */
    .shake {
      animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
      transform: translate3d(0, 0, 0);
      backface-visibility: hidden;
      perspective: 1000px;
    }
    @keyframes shake {
      10%, 90% { transform: translate3d(-1px, 0, 0); }
      20%, 80% { transform: translate3d(2px, 0, 0); }
      30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
      40%, 60% { transform: translate3d(4px, 0, 0); }
    }
    @keyframes bounceIn {
      0% { transform: scale(0.5); opacity: 0; }
      50% { transform: scale(1.1); }
      100% { transform: scale(1); opacity: 1; }
    }
    @media (max-width: 600px) {
      .modal-dialog {
        max-width: 300px; /* Capped max-width on mobile */
      }
      .modal-body { padding: 1.5rem 1rem; }
      .modal-body i { font-size: 2rem; }
      .modal-body #loginErrorMsg { font-size: 1rem; }
    }
  </style>
</head>
<body>
  <div class="login-main">
    <div class="login-container-box">
      <div class="login-left">
        <video autoplay muted loop class="login-bg-video"><source src="/assets/videos/wellcome.mp4" type="video/mp4">Your browser does not support the video tag.</video>
        <div class="video-overlay"></div>
        <h1>COURSE MANAGEMENT SYSTEM</h1>
        <div class="login-left-footer">
            &copy; 2025 DIT Institute of Technology
        </div>
      </div>
      <div class="login-divider"></div>
      <div class="login-right">
        <div class="login-form-box">
          <div class="login-title">LOGIN</div>
          <div class="login-desc">Sign in to your account</div>
          <form id="loginForm" action="?page=login_controller" method="POST" autocomplete="off">
            <div class="input-group mb-3">
              <input type="email" name="email" class="form-control" placeholder="Email" required>
              <div class="input-group-append">
                <div class="input-group-text"><span class="fas fa-envelope"></span></div>
              </div>
            </div>
            <div class="input-group mb-3">
              <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
              <div class="input-group-append">
                <div class="input-group-text">
                  <span class="fas fa-eye toggle-eye" id="togglePassword"></span>
                </div>
              </div>
            </div>
            <button type="submit" class="btn btn-advanced btn-block">Login</button>
          </form>
          <!-- Error Modal -->
          <div id="loginErrorPopup" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
              <div class="modal-content">
                <div class="modal-body">
                  <i class="fas fa-times-circle"></i>
                  <div id="loginErrorMsg"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <div style="width:100vw;text-align:center;padding:0.7rem 0;font-size:0.9rem;font-weight:500;letter-spacing:0.5px;color:#fff;opacity:0.8;position:fixed;left:0;bottom:0;background:rgba(29, 43, 56, 0.7);z-index:10;backdrop-filter: blur(5px);-webkit-backdrop-filter: blur(5px);">
    &copy; 2025 <span style="color:#ff6f3c;font-weight:600;">Course Management System</span> | All Rights Reserved.
  </div>

  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.0/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Password toggle functionality
    const togglePassword = document.querySelector("#togglePassword");
    const password = document.querySelector("#password");
    togglePassword.addEventListener("click", function () {
      const type = password.getAttribute("type") === "password" ? "text" : "password";
      password.setAttribute("type", type);
      this.classList.toggle("fa-eye-slash");
    });

    // Show login error modal (Example)
    $(document).ready(function () {
      const error = new URLSearchParams(window.location.search).get('error');
      let errorMessage = '';

      if (error) {
        if (error === 'invalid' || error === 'nouser') {
            errorMessage = 'Invalid email or password!';
        } else if (error === 'role') {
            errorMessage = 'User role is not configured.';
        } else if (error === 'suspended') {
            errorMessage = 'Your account is suspended. Please contact an administrator.';
        } else {
            errorMessage = 'An unknown error occurred.';
        }

        $('#loginErrorMsg').text(errorMessage);
        $('.login-form-box').addClass('shake');
        $('#loginErrorPopup').modal({ backdrop: false, show: true });

        setTimeout(function() {
            $('#loginErrorPopup').modal('hide');
        }, 4500); // Set timeout to 4.5 seconds
      }
    });
  </script>
</body>
</html>
