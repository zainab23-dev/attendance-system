<?php
declare(strict_types=1);

require_once 'includes/helpers.php';

if (current_teacher_id()) {
    redirect('dashboard.php');
}

page_header('Attendance System');
?>

<section class="hero">
    <div class="hero-copy">
        <h1>Attendance tracking built for busy teachers.</h1>
        <p>Manage classes, add students, mark weekly attendance, and review reports from one clean PHP and MySQL dashboard.</p>
        <div class="hero-actions">
            <a class="button" href="register.php">Create Teacher Account</a>
            <a class="button button-secondary" href="login.php">Login</a>
        </div>
    </div>

    <aside class="hero-panel" aria-label="Attendance dashboard preview">
        <div class="hero-panel-header">
            <h2>Today at a glance</h2>
            <span class="status-dot" aria-hidden="true"></span>
        </div>
        <div class="preview-card">
            <strong>92%</strong>
            <span>Average attendance this week</span>
        </div>
        <div class="preview-card">
            <strong>6 classes</strong>
            <span>Active class sections managed</span>
        </div>
        <div class="mini-chart" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
        </div>
    </aside>
</section>

<?php page_footer(); ?>
