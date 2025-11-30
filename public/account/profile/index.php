<?php

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../api/user.php';

$basePath = '/etydict/public/';
$edit_response = "";

sessionHandler();

if (!($_SESSION['user'])) {
    header('Location: ' . $basePath . 'account/login/');
    exit();
} else {
    $user = $_SESSION['user'];
    $date_created = date('F j, Y', strtotime($user['date_created'] ?? ''));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'editUsername':
            apiHandler();
            $response = json_decode(file_get_contents('php://input'), true);
            if (isset($response) && ($response['success'] ?? false)) {
                $_SESSION['user']['username'] = trim((string) ($_POST['editUsername']));
                $edit_response = 'Username updated successfully.';
            } else {
                $edit_response = 'Failed to update username.';
            }
            break;
        case 'editEmail':
            apiHandler();
            $response = json_decode(file_get_contents('php://input'), true);
            if (isset($response) && ($response['success'] ?? false)) {
                $_SESSION['user']['email'] = trim((string) ($_POST['editEmail']));
                $edit_response = 'Email updated successfully.';
            } else {
                $edit_response = 'Failed to update email.';
            }
            break;
        case 'changePassword':
            apiHandler();
            $response = json_decode(file_get_contents('php://input'), true);
            if (isset($response) && ($response['success'] ?? false)) {
                $edit_response = 'Password changed successfully.';
                userLogout();
                header('Location: ' . $basePath . 'account/login/');
                exit();
            } else {
                $edit_response = 'Failed to change password.';
            }
            break;
        case 'sendMessage':
            apiHandler();
            $response = json_decode(file_get_contents('php://input'), true);
            if (isset($response) && ($response['success'] ?? false)) {
                $edit_response = 'Message sent successfully.';
            } else {
                $edit_response = 'Failed to send message.';
            }
            break;
        case 'deactivateAccount':
            apiHandler();
            $response = json_decode(file_get_contents('php://input'), true);
            if (isset($response) && ($response['success'] ?? false)) {
                userLogout();
                header('Location: ' . $basePath);
                exit();
            } else {
                $edit_response = 'Failed to deactivate account.';
            }
            break;
        default:
            break;
    }
}


$loader = new FilesystemLoader(__DIR__ . '/../../../templates');
$twig = new Environment($loader, [
    'cache' => false,
    'autoescape' => 'html',
]);

echo $twig->render('profile.html.twig', [
    'url' => $basePath,
    'user' => $user,
    'date_created' => $date_created,
    'edit_response' => $edit_response
]);
