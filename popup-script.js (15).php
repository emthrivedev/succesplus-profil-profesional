<?php
/**
 * Popup JavaScript - FIXED
 * File Location: includes/popup/popup-script.js.php
 */
if (!defined('ABSPATH')) exit;
?>
<script type="text/javascript">
(function($) {
    'use strict';
    
    $(document).ready(function() {
        var $overlay = $('#sp-reminder-popup-overlay');
        
        if (!$overlay.length) return;
        
        // Show popup after a short delay
        setTimeout(function() {
            $overlay.show();
        }, 800);
        
        // Close popup function
        function closePopup() {
            $overlay.addClass('sp-popup-closing');
            setTimeout(function() {
                $overlay.remove();
            }, 300);
        }
        
        // Close on X button or dismiss button
        $('.sp-popup-close, .sp-popup-dismiss').on('click', function(e) {
            e.preventDefault();
            closePopup();
        });
        
        // Close on overlay click (outside popup)
        $overlay.on('click', function(e) {
            if (e.target === this) {
                closePopup();
            }
        });
        
        // Close on ESC key
        $(document).on('keydown.sp-popup', function(e) {
            if (e.key === 'Escape') {
                closePopup();
                $(document).off('keydown.sp-popup');
            }
        });
    });
    
})(jQuery);
</script>