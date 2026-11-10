<?php
/**
 * booking-modal-include.php
 * Include on every page to enable "Book Now" → scroll to #booking-section behavior.
 * CSRF token is made available globally.
 */
?>
<!-- CSRF Token (hidden, readable by JS) -->
<div id="csrf-token-container" data-csrf="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>" style="display:none;"></div>

<script>
/* ── Global: smoothScrollToBooking ──────────────────────
   Available on every page.
   If we're already on index.php → scroll to #booking-section.
   If on another page → navigate to index.php#booking-section.
─────────────────────────────────────────────────────── */
if (typeof window.smoothScrollToBooking === 'undefined') {
    window.smoothScrollToBooking = function() {
        var section = document.getElementById('booking-section');
        if (section) {
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            setTimeout(function() {
                section.classList.add('booking-highlight');
                setTimeout(function() { section.classList.remove('booking-highlight'); }, 900);
            }, 500);
        } else {
            window.location.href = 'index.php#booking-section';
        }
    };
}

/* ── Hook all "احجز" / "Book Now" buttons ── */
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll(
        '.nav-book-btn, .btn-primary, .quick-book-btn, [data-book-now]'
    ).forEach(function(el) {
        var href = el.getAttribute('href') || '';
        var txt  = (el.textContent || '').trim();

        if (href.indexOf('wa.me') !== -1) return;          // skip WhatsApp
        if (!txt.match(/احجز|حجز|book/i)) return;         // only booking buttons

        el.addEventListener('click', function(e) {
            e.preventDefault();
            window.smoothScrollToBooking();
        });
    });

    // Nav book button
    var navBtn = document.querySelector('.nav-book-btn');
    if (navBtn) {
        navBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.smoothScrollToBooking();
        });
    }
});
</script>
