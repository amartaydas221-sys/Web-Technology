<section class="page-hero compact"><div class="container"><span class="eyebrow">Registered User</span><h1>My Wishlist</h1><p>Save and remove approved destination posts for later planning.</p></div></section>
<section class="section container"><div class="card-grid">
<?php if(!$posts): ?><div class="empty-state"><h3>Your wishlist is empty.</h3><p>Browse approved destinations and save the places you want to remember.</p><a class="btn btn-primary" href="<?= e(url('explore')) ?>">Explore Destinations</a></div><?php endif; ?>
<?php foreach($posts as $post): ?><div><?php require APP_ROOT.'/app/Views/layouts/post_card.php'; ?><form class="card-action-form" method="post" action="<?= e(url('wishlist/toggle')) ?>"><?= csrf_field() ?><input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>"><input type="hidden" name="return" value="wishlist"><button class="btn btn-danger btn-sm" type="submit">Remove from Wishlist</button></form></div><?php endforeach; ?>
</div></section>
