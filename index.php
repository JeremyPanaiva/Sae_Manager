<?php

/**
 * Application Entry Point (index.php)
 *
 * This file serves as the main entry point and router for the application.
 * It initializes the environment, loads controllers, and routes incoming requests
 * to the appropriate controller based on the URL path and HTTP method.
 *
 * Responsibilities:
 * - Configure timezone and error reporting
 * - Initialize autoloader for class loading
 * - Start user session
 * - Register all application controllers
 * - Route incoming requests to matching controllers
 * - Handle 404 fallback to home page
 *
 * Routing Logic:
 * The router iterates through registered controllers and calls their support()
 * method to determine if they can handle the current request.  The first matching
 * controller's control() method is executed.
 *
 * @package Root
 */

// Set application timezone from environment variable or default to Europe/Paris
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Europe/Paris');

// Include and register the autoloader for automatic class loading
require_once "Autoloader.php";
\Autoloader::register();

// Placed early to ensure $_SESSION is available before any controller is instantiated.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Import all controller classes
use Controllers\Dashboard\TodoController;
use Controllers\Home\HomeController;
use Controllers\Legal\ContactController;
use Controllers\Legal\PlanDuSiteController;
use Controllers\Sae\AttribuerSaeController;
use Controllers\Sae\DeadlineReminderController;
use Controllers\Sae\DeleteSaeController;
use Controllers\Sae\SaeController;
use Controllers\Sae\UnassignSaeController;
use Controllers\Sae\UpdateContentSaeController;
use Controllers\Sae\UpdateSaeDateController;
use Controllers\User\Login;
use Controllers\User\LoginPost;
use Controllers\User\ProfileController;
use Controllers\User\VerifyEmailController;
use Controllers\User\Register;
use Controllers\User\RegisterPost;
use Controllers\User\Logout;
use Controllers\User\ForgotPassword;
use Controllers\User\ForgotPasswordPost;
use Controllers\User\ResetPassword;
use Controllers\User\ResetPasswordPost;
use Controllers\User\ListUsers;
use Controllers\Legal\MentionsLegalesController;
use Controllers\Dashboard\DashboardController;
use Controllers\Sae\CreateSaeController;
use Controllers\Legal\ContactPost;
use Controllers\Sae\AvisController;
use Controllers\User\ChangePassword;
use Controllers\User\ChangePasswordPost;

// Start PHP session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable detailed error reporting for local development environments
if (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

/**
 * Application Controllers Registry
 *
 * Array of all controller instances that handle different routes in the application.
 * Controllers are organized by feature area:
 * - Home: Landing page
 * - User: Authentication, registration, profile, password management
 * - Legal: Legal pages, contact, site map
 * - Dashboard: User dashboard and todos
 * - SAE: SAE management (create, assign, update, delete)
 *
 * @var array
 */
$controllers = [
    new HomeController(),
    new Login(),
    new LoginPost(),
    new Register(),
    new RegisterPost(),
    new VerifyEmailController(),
    new Logout(),
    new ForgotPassword(),
    new ForgotPasswordPost(),
    new ResetPassword(),
    new ResetPasswordPost(),
    new ListUsers(),
    new MentionsLegalesController(),
    new PlanDuSiteController(),
    new DashboardController(),
    new SaeController(),
    new CreateSaeController(),
    new AttribuerSaeController(),
    new DeleteSaeController(),
    new TodoController(),
    new UpdateSaeDateController(),
    new UnassignSaeController(),
    new ContactController(),
    new ContactPost(),
    new AvisController(),
    new UpdateContentSaeController(),
    new ProfileController(),
    new ChangePassword(),
    new ChangePasswordPost(),
    new DeadlineReminderController(),
];

// Extract the path from the request URI (without query string parameters)
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

/**
 * Route Matching and Controller Execution
 *
 * Iterate through all registered controllers and find the first one that supports
 * the current request path and HTTP method.  Once found, execute its control()
 * method and terminate the script.
 */
foreach ($controllers as $controller) {
    if ($controller::support($path, $method)) {
        error_log(sprintf("Controller utilisé: %s", $controller::class));
        $controller->control();
        exit();
    }
}

/**
 * 404 Fallback Handler
 *
 * If no controller matches the request, display the home page as a fallback.
 * This prevents showing error pages for unmatched routes.
 */
$home = new HomeController();
$home->control();
exit();
