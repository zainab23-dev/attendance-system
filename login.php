<?php
declare(strict_types=1);

require_once 'includes/helpers.php';
guest_only();

page_header('Teacher Login', 'login');
?>

<section class="auth-layout">
    <div class="auth-copy">
        <h1>Welcome back to your classroom command center.</h1>
        <p>Sign in to create classes, manage student lists, mark attendance sessions, and review attendance history.</p>
    </div>

    <section class="panel auth-card">
        <h1>Teacher Login</h1>
        <form method="POST" action="auth.php">
            <input type="hidden" name="action" value="login">
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="teacher@example.com" required autocomplete="email">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
            </div>
            <button type="submit">Login</button>
        </form>
        <p class="auth-switch">New teacher? <a href="register.php">Create an account</a></p>
    </section>
</section>

<?php page_footer(); ?>
