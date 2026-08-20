    </main>
</div>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.APP = {
    csrf: <?= json_encode(csrf_token()) ?>,
    base: <?= json_encode(rtrim(app_url(), '/')) ?>,
    timezone: <?= json_encode((string) app_config('timezone')) ?>
};
</script>
<script src="<?= e(app_url('assets/js/app.js')) ?>"></script>
<?php if (!empty($pageScripts) && is_array($pageScripts)): ?>
    <?php foreach ($pageScripts as $src): ?>
        <script src="<?= e(app_url($src)) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
