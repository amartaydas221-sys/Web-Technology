<section class="auth-shell">
    <div class="auth-art register-art"><div><span class="eyebrow">Join TravelGuide</span><h2>Save favorite places, comment on verified guides and build a smarter travel plan.</h2></div></div>
    <div class="auth-card"><span class="logo large">TG</span><h1>Create account</h1><p>Public registration creates a Registered User account.</p>
        <form method="post" action="<?= e(url('register')) ?>"><?= csrf_field() ?>
            <label>Full name<input type="text" name="name" value="<?= e(old('name')) ?>" required></label>
            <label>Email address<input type="email" name="email" value="<?= e(old('email')) ?>" required></label>
            <label>Password<input type="password" name="password" minlength="8" required></label>
            <label>Confirm password<input type="password" name="password_confirmation" minlength="8" required></label>
            <button class="btn btn-primary btn-block" type="submit">Create Account</button>
        </form>
        <div class="auth-footer">Already registered? <a href="<?= e(url('login')) ?>">Sign in</a></div>
    </div>
</section>
