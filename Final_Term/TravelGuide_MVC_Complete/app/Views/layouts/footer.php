</main>
<footer class="footer">
    <div><strong>TravelGuide</strong><p>Interactive Travel Guidance & Scout Management System</p></div>
    <div class="footer-links"><a href="<?= e(url('explore')) ?>">Destinations</a><a href="<?= e(url('calculator')) ?>">Calculator</a><?php if(is_logged_in()): ?><a href="<?= e(url('profile')) ?>">Profile</a><?php endif; ?></div>
    <div>&copy; <?= date('Y') ?> TravelGuide</div>
</footer>
<div id="toast" class="toast"></div>
<script src="<?= e(asset('assets/js/app.js')) ?>"></script>
</body>
</html>
