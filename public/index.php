<?php
// Main entry point for the application
// Set the root path for the application
define('ROOT_PATH', dirname(__DIR__));

// Include configuration
require_once ROOT_PATH . '/config/connection.php';

// Start session
session_start();

// Simple routing logic
$page = isset($_GET['page']) ? $_GET['page'] : 'login';

// Basic security check
$allowed_pages = [
    'login', 'login2', 'loginn', 'logout',
    'studentdash', 'instructordash',
    'superadmindash', 'add_user', 'edit_user', 'delete_user',
    'student_registration', 'instrucctorregistration', 'edit_student',
    'announcement_form', 'delete_announcement', 'drop_module', 'edit_announcement', 'edit_course',
    'post_announcement', 'post_instructor_announcement', 'post_admin_announcement',
    'register_module', 'total_students', 'error_display',
    'view_announcements',
    'manage_courses', 'manage_modules', 'manage_instructors',
];

$allowed_controllers = [
    'login_controller', 'login2_controller', 'student_controller',
    'userinstructor_controller', 'userstudent_controller', 'controller',
    'delete_student_controller'
];

if (in_array($page, $allowed_pages)) {
    // Try .php first, then .html for views
    $php_view = ROOT_PATH . '/views/' . $page . '.php';
    $html_view = ROOT_PATH . '/views/' . $page . '.html';
    if (file_exists($php_view)) {
        include $php_view;
    } elseif (file_exists($html_view)) {
        include $html_view;
    } else {
        echo "Page not found: $page";
    }
} elseif (in_array($page, $allowed_controllers)) {
    // Include the corresponding controller
    $controller_file = ROOT_PATH . '/controllers/' . $page . '.php';
    if (file_exists($controller_file)) {
        include $controller_file;
    } else {
        echo "Controller not found: $page";
    }
} else {
    echo "Invalid page requested: $page";
}
?>
