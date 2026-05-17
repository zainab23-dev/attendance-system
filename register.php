<?php
declare(strict_types=1);

require_once 'includes/helpers.php';
guest_only();

page_header('Create Teacher Account');
?>

<section class="auth-layout">
    <div class="auth-copy">
        <h1>Start with a clean attendance workflow.</h1>
        <p>Create a teacher profile, then add classes and students with secure session-based access to your own records.</p>
    </div>

    <section class="panel auth-card">
        <h1>Create Account</h1>
        <form method="POST" action="auth.php">
            <input type="hidden" name="action" value="register">
            <div class="field">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" placeholder="Your name" required autocomplete="name">
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="teacher@example.com" required autocomplete="email">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Minimum 6 characters" required autocomplete="new-password">
            </div>
            <button type="submit">Create Account</button>
        </form>
        <p class="auth-switch">Already registered? <a href="login.php">Login</a></p>
    </section>
</section>

<?php page_footer(); ?>
