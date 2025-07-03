</div>
            </div>
        </main>
    </div>
</div>

<!-- Admin Scripts -->
<script src="<?php echo BASE_URL; ?>js/admin_script.js?v=<?php echo filemtime(BASE_URL . 'js/admin_script.js'); ?>"></script>

<!-- Initialize Lucide Icons -->
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>

<!-- Flash Messages Auto-hide -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const flashMessages = document.querySelectorAll('[role="alert"]');
    flashMessages.forEach(function(message) {
        setTimeout(function() {
            message.style.transition = 'opacity 0.5s ease-in-out';
            message.style.opacity = '0';
            setTimeout(function() {
                message.remove();
            }, 500);
        }, 5000);
    });
});
</script>

</body>
</html>
