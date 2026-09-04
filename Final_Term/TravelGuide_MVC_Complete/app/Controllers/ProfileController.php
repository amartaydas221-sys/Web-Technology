<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\User;
use PDOException;

class ProfileController extends Controller
{
    public function show(): void
    {
        Auth::requireLogin();
        $user = (new User())->findById(Auth::id());
        $this->view('profile/show',['title'=>'My Profile','user'=>$user]);
    }

    public function edit(): void
    {
        Auth::requireLogin();
        $user = (new User())->findById(Auth::id());
        $this->view('profile/edit',['title'=>'Edit Profile','user'=>$user]);
    }

    public function update(): void
    {
        Auth::requireLogin(); verify_csrf();
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $profileImage = trim((string)($_POST['profile_image'] ?? ''));
        if ($name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL)) {
            flash('error','Name and a valid email are required.'); redirect('profile/edit');
        }
        try {
            $users = new User();
            $users->updateProfile(Auth::id(),$name,$email,$profileImage ?: null);
            $fresh = $users->findById(Auth::id());
            Auth::refresh($fresh);
            flash('success','Profile updated successfully.');
        } catch (PDOException $e) {
            flash('error',$e->getCode()==='23000' ? 'That email is already in use.' : 'Could not update profile.');
        }
        redirect('profile');
    }

    public function password(): void
    {
        Auth::requireLogin(); verify_csrf();
        $current = (string)($_POST['current_password'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirmation'] ?? '');
        $users = new User();
        $user = $users->findById(Auth::id());
        if (!password_verify($current,$user['password_hash'])) {
            flash('error','Current password is incorrect.'); redirect('profile/edit');
        }
        if (strlen($password)<8 || $password!==$confirm) {
            flash('error','New password must be at least 8 characters and match the confirmation.'); redirect('profile/edit');
        }
        $users->updatePassword(Auth::id(),$password);
        flash('success','Password changed successfully.'); redirect('profile');
    }

    public function delete(): void
    {
        Auth::requireLogin(); verify_csrf();
        $password = (string)($_POST['password'] ?? '');
        $users = new User();
        $user = $users->findById(Auth::id());
        if ($user['role_name']==='admin') {
            flash('error','The administrator account cannot delete itself from the profile screen.'); redirect('profile/edit');
        }
        if (!password_verify($password,$user['password_hash'])) {
            flash('error','Password confirmation is incorrect.'); redirect('profile/edit');
        }
        $users->delete(Auth::id());
        Auth::logout();
        session_start();
        flash('success','Your account was deleted.');
        redirect('home');
    }
}
