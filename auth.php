<?php
declare(strict_types=1);

require_once 'includes/helpers.php';
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}

$action = $_POST['action'] ?? '';
$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($action === 'register') {
    $name = trim((string) ($_POST['name'] ?? ''));

    if ($name === '' || $email === '' || $password === '') {
        flash('error', 'All fields are required.');
        redirect('register.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Please enter a valid email address.');
        redirect('register.php');
    }

    if (strlen($password) < 6) {
        flash('error', 'Password must be at least 6 characters.');
        redirect('register.php');
    }

    $stmt = $conn->prepare('SELECT id FROM teachers WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $existing = $stmt->get_result();
    $stmt->close();

    if ($existing->num_rows > 0) {
        flash('error', 'An account with this email already exists.');
        redirect('register.php');
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare('INSERT INTO teachers (name, email, password) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $name, $email, $hashedPassword);
    $stmt->execute();
    $stmt->close();

    flash('success', 'Account created successfully. You can log in now.');
    redirect('login.php');
}

if ($action === 'login') {
    if ($email === '' || $password === '') {
        flash('error', 'Email and password are required.');
        redirect('login.php');
    }

    $stmt = $conn->prepare('SELECT id, name, password FROM teachers WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacher = $result->fetch_assoc();
    $stmt->close();

    if (!$teacher || !password_verify($password, $teacher['password'])) {
        flash('error', 'Invalid email or password.');
        redirect('login.php');
    }

    session_regenerate_id(true);
    $_SESSION['teacher_id'] = (int) $teacher['id'];
    $_SESSION['teacher_name'] = $teacher['name'];

    flash('success', 'Welcome back, ' . $teacher['name'] . '.');
    redirect('dashboard.php');
}

flash('error', 'Unsupported authentication action.');
redirect('login.php');
