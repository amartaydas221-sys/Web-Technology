<section class="hero">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <span class="eyebrow">World's travel discovery platform</span>
        <h1>Discover Your <span>Next Adventure</span></h1>
        <p>Explore approved local destination guides, discover hidden places, estimate your travel budget and plan with confidence.</p>
        <form class="hero-search" method="get" action="<?= e(url()) ?>">
            <input type="hidden" name="route" value="explore">
            <input name="search" placeholder="Search destinations, countries, categories...">
            <button class="btn btn-primary" type="submit">Search</button>
        </form>
    </div>
</section>

<section class="section container">
    <div class="section-head"><div><span class="eyebrow orange">Editor's Picks</span><h2>Featured Destinations</h2></div><a href="<?= e(url('explore')) ?>">View all &rarr;</a></div>
    <div class="card-grid">
        <?php if (!$featured): ?><div class="empty-state">No approved destinations yet.</div><?php endif; ?>
        <?php foreach ($featured as $post): require APP_ROOT.'/app/Views/layouts/post_card.php'; endforeach; ?>
    </div>
</section>

<section class="cta">
    <div class="container cta-inner"><div><h2>Ready to Start Exploring?</h2><p>Browse verified destination content or become part of the TravelGuide community.</p></div><div class="cta-buttons"><?php if(!is_logged_in()): ?><a class="btn btn-primary" href="<?= e(url('register')) ?>">Get Started Free</a><?php endif; ?><a class="btn btn-outline-light" href="<?= e(url('calculator')) ?>">Estimate Trip Cost</a></div></div>
</section>
