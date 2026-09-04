<article class="destination-card">
    <div class="destination-media <?= $post['image_url'] ? 'has-image' : '' ?>">
        <?php if ($post['image_url']): ?><img src="<?= e($post['image_url']) ?>" alt="<?= e($post['title']) ?>"><?php else: ?><div class="media-fallback"><span><?= e(strtoupper(substr($post['country'],0,2))) ?></span></div><?php endif; ?>
        <span class="cost-tag cost-<?= e(strtolower($post['cost_level'])) ?>"><?= e($post['cost_level']) ?></span>
    </div>
    <div class="destination-body">
        <h3><?= e($post['title']) ?></h3>
        <div class="meta">&#128205; <?= e($post['country']) ?> &middot; <?= e($post['category']) ?></div>
        <p><?= e((strlen($post['short_history'])>145 ? substr($post['short_history'],0,145).'...' : $post['short_history'])) ?></p>
        <div class="card-footer"><small>Scout: <?= e($post['scout_name'] ?? 'TravelGuide') ?></small><a class="btn btn-dark btn-sm" href="<?= e(url('post',['id'=>$post['id']])) ?>">View Details</a></div>
    </div>
</article>
