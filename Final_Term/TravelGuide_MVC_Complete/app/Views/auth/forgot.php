<section class="simple-page"><div class="narrow-card"><h1>Reset password</h1><p>Enter the email connected to your account.</p>
<form method="post" action="<?= e(url('forgot-password')) ?>"><?= csrf_field() ?><label>Email address<input type="email" name="email" required></label><button class="btn btn-primary btn-block" type="submit">Create Reset Request</button></form>
<?php if(!empty($_SESSION['_demo_reset_link'])): $demo=$_SESSION['_demo_reset_link']; unset($_SESSION['_demo_reset_link']); ?><div class="demo-box"><strong>Demo-mode reset link</strong><p>No SMTP server is required for the university demo. Click the generated link:</p><a href="<?= e($demo) ?>">Open password reset form</a></div><?php endif; ?>
<p><a href="<?= e(url('login')) ?>">&larr; Back to login</a></p></div></section>
