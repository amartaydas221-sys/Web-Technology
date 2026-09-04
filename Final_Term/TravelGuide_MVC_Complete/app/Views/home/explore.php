<section class="page-hero compact"><div class="container"><span class="eyebrow">Approved Scout Posts</span><h1>Explore Destinations</h1><p>Search and filter destination posts that have been approved by an administrator.</p></div></section>
<section class="section container">
    <form class="filter-bar" method="get" action="<?= e(url()) ?>">
        <input type="hidden" name="route" value="explore">
        <input name="search" value="<?= e($filters['search']) ?>" placeholder="Search title, country or history">
        <select name="country"><option value="">All countries</option><?php foreach($countries as $v): ?><option <?= $filters['country']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select>
        <select name="category"><option value="">All categories</option><?php foreach($categories as $v): ?><option <?= $filters['category']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select>
        <select name="cost_level"><option value="">All budgets</option><?php foreach($costLevels as $v): ?><option <?= $filters['cost_level']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select>
        <button class="btn btn-primary" type="submit">Filter</button>
        <a class="btn btn-soft" href="<?= e(url('explore')) ?>">Clear</a>
    </form>
    <div class="result-line"><strong><?= count($posts) ?></strong> approved destination<?= count($posts)===1?'':'s' ?> found.</div>
    <div class="card-grid">
        <?php if (!$posts): ?><div class="empty-state">No destination matched your search/filter.</div><?php endif; ?>
        <?php foreach ($posts as $post): require APP_ROOT.'/app/Views/layouts/post_card.php'; endforeach; ?>
    </div>
</section>
