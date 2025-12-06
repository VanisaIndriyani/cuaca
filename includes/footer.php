    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="<?php echo base_url('assets/js/main.js'); ?>"></script>
    <?php if (isset($_SESSION['user_id'])): ?>
    <script src="<?php echo base_url('assets/js/notifications.js'); ?>"></script>
    <?php endif; ?>
</body>
</html>

