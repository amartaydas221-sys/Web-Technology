<section class="auth-shell">
    <div class="auth-art"><div><span class="eyebrow">TravelGuide</span><h2>The world is a book, and every approved destination is a new page.</h2></div></div>
    <div class="auth-card"><span class="logo large">TG</span><h1>Welcome back</h1><p>Sign in to access your dashboard and role-based features.</p>
        <form method="post" action="<?= e(url('login')) ?>"><?= csrf_field() ?>
            <label>Email address<input type="email" name="email" value="<?= e(old('email')) ?>" required></label>
            <label>Password<input type="password" name="password" required></label>
            <div class="form-row between"><a href="<?= e(url('forgot-password')) ?>">Forgot password?</a></div>
            <button class="btn btn-primary btn-block" type="submit">Sign In</button>
        </form>
        <div class="auth-note">Demo: user@travelguide.com / User@123 &middot; scout@travelguide.com / Scout@123 &middot; admin@travelguide.com / Admin@123</div>
        <div class="auth-footer">Don't have an account? <a href="<?= e(url('register')) ?>">Register</a></div>
    </div>
</section>
