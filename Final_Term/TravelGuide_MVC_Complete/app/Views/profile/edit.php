<section class="page-hero compact"><div class="container"><span class="eyebrow">Account Management</span><h1>Edit Profile</h1></div></section>
<section class="section container settings-grid">
<div class="panel"><h2>Profile Information</h2><form method="post" action="<?= e(url('profile/update')) ?>"><?= csrf_field() ?><label>Full name<input name="name" value="<?= e($user['name']) ?>" required></label><label>Email<input type="email" name="email" value="<?= e($user['email']) ?>" required></label><label>Profile image URL <small>(optional)</small><input type="url" name="profile_image" value="<?= e($user['profile_image'] ?? '') ?>" placeholder="https://..."></label><button class="btn btn-primary" type="submit">Save Profile</button></form></div>
<div class="panel"><h2>Change Password</h2><form method="post" action="<?= e(url('profile/password')) ?>"><?= csrf_field() ?><label>Current password<input type="password" name="current_password" required></label><label>New password<input type="password" name="password" minlength="8" required></label><label>Confirm new password<input type="password" name="password_confirmation" minlength="8" required></label><button class="btn btn-dark" type="submit">Change Password</button></form></div>
<?php if($user['role_name']!=='admin'): ?><div class="panel danger-panel"><h2>Delete Account</h2><p>This permanently deletes your account and related personal data. Approved Scout posts are preserved without the deleted account.</p><form method="post" action="<?= e(url('profile/delete')) ?>" onsubmit="return confirm('Permanently delete your account?')"><?= csrf_field() ?>
    <label>Confirm your password<input type="password" name="password" required></label>
    <button class="btn btn-danger" type="submit">Delete My Account </button>
</form>
</div><?php endif; ?>
</section>

