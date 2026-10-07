<section class="page-heading"><span class="eyebrow">EVERY GOOD THING IS WORTH THE WAIT</span><h1>Follow your order</h1><p>Enter your Foodplan order reference to see its latest status and rider location.</p></section>
<form class="track-form section" data-track-form><label>Order reference<input name="code" value="<?= e($_GET['order'] ?? '') ?>" placeholder="FF-…" required></label><button class="button">Track order</button><p class="form-message" data-message></p></form>
<section class="tracking-card section" data-track-result></section>
