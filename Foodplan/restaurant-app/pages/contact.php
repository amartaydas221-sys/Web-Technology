<section class="page-heading"><span class="eyebrow">A REAL PERSON IS HERE TO HELP</span><h1>Say hello.</h1><p>Questions about an order or planning a visit? We’re just a message away.</p></section>
<section class="section contact-grid"><article><span>⌖</span><h2>Come by</h2><p>742 Evergreen Terrace, Dhaka</p></article><article><span>☎</span><h2>Give us a call</h2><a href="tel:+8801700000000">+880 1700-000000</a></article><article><span>✉</span><h2>Send a note</h2><a href="mailto:help@foodplan.bd">help@foodplan.bd</a></article><article><span>◷</span><h2>We are open</h2><p>Every day · 8 AM to 10 PM</p></article></section>
<?php if (isset($user) && is_array($user) && $user['role'] === 'admin'): ?>
<section class="promo"><div><span class="eyebrow">RESERVATION MANAGEMENT</span><h2>Review table requests.</h2></div><a class="button button-light" href="?page=admin#reservations">View reservations</a></section>
<?php else: ?>
<section class="promo"><div><span class="eyebrow">WE’LL SAVE YOU A SEAT</span><h2>Come share a meal with us.</h2></div><a class="button button-light" href="?page=reservation">Book a table</a></section>
<?php endif; ?>
