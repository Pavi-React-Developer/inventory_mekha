<?php
// app/controllers/AuthController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;

class AuthController extends Controller {
    public function showLogin(): void {
        if (Auth::check()) {
            if (Auth::isSuperAdmin()) {
                $this->redirect('/admin/dashboard');
            } else {
                $this->redirect('/dashboard');
            }
        }

        $this->render('auth/login', [
            'pageTitle' => 'Sign In',
            'noLayout'  => true,
        ]);
    }

    public function login(): void {
        $this->validateCsrf();

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->flash('error', 'Please enter your email and password.');
            $this->redirect('/login');
        }

        if (Auth::login($email, $password)) {
            $this->flash('success', 'Welcome back, ' . Auth::user()['name'] . '!');
            if (Auth::isSuperAdmin()) {
                $this->redirect('/admin/dashboard');
            } else {
                $this->redirect('/dashboard');
            }
        } else {
            $this->flash('error', 'Invalid email or password, or your organization account is inactive.');
            $this->redirect('/login');
        }
    }

    public function logout(): void {
        Auth::logout();
        $this->redirect('/login');
    }
}
