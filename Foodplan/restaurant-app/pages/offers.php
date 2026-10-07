<section class="page-heading"><span class="eyebrow">GOOD THINGS, MADE EVEN BETTER</span><h1>A little treat for your table</h1><p>Use a coupon at checkout and save on fresh favorites.</p></section>
<section class="section"><div class="coupon-grid" data-coupons></div></section>
<?php if ($user && $user['role'] === 'admin'): ?>
<section class="promo"><div><span class="eyebrow">RESERVATION MANAGEMENT</span><h2>Review table requests.</h2></div><a class="button button-light" href="?page=admin#reservations">View reservations</a></section>
<?php else: ?>
<section class="promo"><div><span class="eyebrow">WE’LL SAVE YOU A SEAT</span><h2>Make room for a longer lunch.</h2><p>Set a table for friends, family, and something delicious.</p></div><a class="button button-light" href="?page=reservation">Book a table</a></section>
<?php endif; ?>
