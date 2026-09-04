<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\PasswordReset;
use App\Models\User;
use PDOException;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (Auth::check()) redirect('dashboard');
        $this->view('auth/login', ['title' => 'Login']);
    }

    public function login(): void
    {
        verify_csrf();
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        set_old(['email'=>$email]);

        $user = (new User())->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            flash('error','Invalid email or password.');
            redirect('login');
        }

        Auth::login($user);
        clear_old();
        flash('success','Welcome back, ' . $user['name'] . '!');
        redirect('dashboard');
    }

    public function registerForm(): void
    {
        if (Auth::check()) redirect('dashboard');
        $this->view('auth/register', ['title' => 'Register']);
    }

    public function register(): void
    {
        verify_csrf();
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirmation'] ?? '');
        set_old(['name'=>$name,'email'=>$email]);

        $errors = $this->validateRequired(compact('name','email','password'), ['name'=>'Name','email'=>'Email','password'=>'Password']);
        if (!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='Please provide a valid email address.';
        if (strlen($password)<8) $errors[]='Password must be at least 8 characters.';
        if ($password!==$confirm) $errors[]='Password confirmation does not match.';
        if ($errors) {
            flash('error',implode(' ', $errors));
            redirect('register');
        }

        try {
            (new User())->create($name,$email,$password,'user',1);
        } catch (PDOException $e) {
            flash('error',$e->getCode()==='23000' ? 'An account with this email already exists.' : 'Registration failed.');
            redirect('register');
        }
        clear_old();
        flash('success','Registration successful. You can now log in.');
        redirect('login');
    }

    public function logout(): void
    {
        verify_csrf();
        Auth::logout();
        session_start();
        flash('success','You have been logged out.');
        redirect('home');
    }

    public function forgotForm(): void
    {
        $this->view('auth/forgot', ['title'=>'Reset Password']);
    }

    public function forgot(): void
    {
        verify_csrf();
        $email = trim((string)($_POST['email'] ?? ''));
        $user = (new User())->findByEmail($email);
        $demoLink = null;
        if ($user) {
            $token = (new PasswordReset())->create((int)$user['id']);
            if (DEMO_MODE) {
                $demoLink = url('reset-password',['token'=>$token]);
                $_SESSION['_demo_reset_link'] = $demoLink;
            }
        }
        flash('success','If that email exists, a reset request has been created.' . (DEMO_MODE && $user ? ' In demo mode, use the reset link shown below.' : ''));
        redirect('forgot-password');
    }

    public function resetForm(): void
    {
        $token = (string)($_GET['token'] ?? '');
        $valid = $token !== '' ? (new PasswordReset())->findValid($token) : null;
        $this->view('auth/reset', ['title'=>'Choose New Password','token'=>$token,'valid'=>$valid]);
    }

    public function reset(): void
    {
        verify_csrf();
        $token = (string)($_POST['token'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirmation'] ?? '');
        $resetModel = new PasswordReset();
        $reset = $resetModel->findValid($token);
        if (!$reset) {
            flash('error','Reset link is invalid or has expired.');
            redirect('forgot-password');
        }
        if (strlen($password)<8 || $password!==$confirm) {
            flash('error','Password must be at least 8 characters and both password fields must match.');
            redirect('reset-password',['token'=>$token]);
        }
        (new User())->updatePassword((int)$reset['user_id'],$password);
        $resetModel->markUsed((int)$reset['id']);
        flash('success','Password reset successfully. Please log in.');
        redirect('login');
    }
}
