<?php
require_once __DIR__ . '/config/bootstrap.php';

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\ProfileController;
use App\Controllers\DashboardController;
use App\Controllers\PostController;
use App\Controllers\WishlistController;
use App\Controllers\CommentController;
use App\Controllers\CalculatorController;
use App\Controllers\ScoutController;
use App\Controllers\AdminController;

$router = new Router();

$router->get('', [HomeController::class, 'index']);
$router->get('home', [HomeController::class, 'index']);
$router->get('explore', [HomeController::class, 'explore']);
$router->get('post', [PostController::class, 'show']);

$router->get('login', [AuthController::class, 'loginForm']);
$router->post('login', [AuthController::class, 'login']);
$router->get('register', [AuthController::class, 'registerForm']);
$router->post('register', [AuthController::class, 'register']);
$router->post('logout', [AuthController::class, 'logout']);
$router->get('forgot-password', [AuthController::class, 'forgotForm']);
$router->post('forgot-password', [AuthController::class, 'forgot']);
$router->get('reset-password', [AuthController::class, 'resetForm']);
$router->post('reset-password', [AuthController::class, 'reset']);

$router->get('dashboard', [DashboardController::class, 'index']);
$router->get('profile', [ProfileController::class, 'show']);
$router->get('profile/edit', [ProfileController::class, 'edit']);
$router->post('profile/update', [ProfileController::class, 'update']);
$router->post('profile/password', [ProfileController::class, 'password']);
$router->post('profile/delete', [ProfileController::class, 'delete']);

$router->get('wishlist', [WishlistController::class, 'index']);
$router->post('wishlist/toggle', [WishlistController::class, 'toggle']);
$router->post('comment/store', [CommentController::class, 'store']);
$router->post('comment/delete', [CommentController::class, 'deleteOwn']);

$router->get('calculator', [CalculatorController::class, 'index']);
$router->post('calculator/calculate', [CalculatorController::class, 'calculate']);

$router->get('scout/requests', [ScoutController::class, 'requests']);
$router->get('scout/request/create', [ScoutController::class, 'create']);
$router->post('scout/request/store', [ScoutController::class, 'store']);
$router->get('scout/request/edit', [ScoutController::class, 'edit']);
$router->post('scout/request/update', [ScoutController::class, 'update']);
$router->post('scout/request/delete', [ScoutController::class, 'delete']);

$router->get('admin/dashboard', [AdminController::class, 'dashboard']);
$router->get('admin/users', [AdminController::class, 'users']);
$router->post('admin/user/add', [AdminController::class, 'addUser']);
$router->post('admin/user/verify', [AdminController::class, 'verifyUser']);
$router->post('admin/user/delete', [AdminController::class, 'deleteUser']);
$router->get('admin/requests', [AdminController::class, 'requests']);
$router->post('admin/request/review', [AdminController::class, 'reviewRequest']);
$router->get('admin/posts', [AdminController::class, 'posts']);
$router->get('admin/post/edit', [AdminController::class, 'editPost']);
$router->post('admin/post/update', [AdminController::class, 'updatePost']);
$router->post('admin/post/delete', [AdminController::class, 'deletePost']);
$router->get('admin/comments', [AdminController::class, 'comments']);
$router->post('admin/comment/delete', [AdminController::class, 'deleteComment']);

$router->dispatch();
