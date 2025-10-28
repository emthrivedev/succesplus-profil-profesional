<?php
/**
 * SP_Popup_Manager Class - FIXED: CV Redirect with Hash
 * File Location: includes/popup/class-sp-popup-manager.php
 * Version: 4.5 - Fixed CV popup redirect
 */

if (!defined('ABSPATH')) exit;

class SP_Popup_Manager {
    
    private $plugin;
    
    public function __construct($plugin) {
        $this->plugin = $plugin;
        
        add_action('wp_footer', array($this, 'check_and_show_reminder_popup'));
    }
    
    /**
     * Check and show reminder popup for users who skipped test/CV or have test in progress
     */
    public function check_and_show_reminder_popup() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Check test status
        $test_status = get_user_meta($user_id, 'sp_test_completed_status', true);
        
        // Check CV status
        $cv_status = get_user_meta($user_id, 'sp_cv_status', true);
        
        // Determine what popup to show
        $popup_type = null;
        
        if ($test_status === 'skipped' || $test_status === 'in_progress') {
            $popup_type = 'test';
        } elseif ($test_status === 'completed' && $cv_status === 'skipped') {
            $popup_type = 'cv';
        } else {
            // No popup needed
            return;
        }
        
        $debug_mode = get_option('sp_onboarding_popup_debug_mode', 0);
        
        if (!$debug_mode) {
            $transient_key = 'sp_reminder_popup_shown_' . $user_id . '_' . $popup_type;
            if (get_transient($transient_key)) {
                return;
            }
            
            set_transient($transient_key, true, 24 * HOUR_IN_SECONDS);
        }
        
        $user_info = get_userdata($user_id);
        $first_name = $user_info->first_name ?: 'Bine ai venit';
        
        if ($popup_type === 'test') {
            $this->render_test_reminder_popup($first_name, $test_status, $debug_mode);
        } elseif ($popup_type === 'cv') {
            $this->render_cv_reminder_popup($first_name, $debug_mode);
        }
    }
    
    /**
     * Render test reminder popup
     */
    private function render_test_reminder_popup($first_name, $test_status, $debug_mode) {
        $test_page_url = home_url('/inregistrare/');
        
        $has_progress = false;
        $answered_count = 0;
        
        if ($test_status === 'in_progress') {
            $saved_progress = get_user_meta(get_current_user_id(), 'sp_test_progress', true);
            if ($saved_progress && is_array($saved_progress) && isset($saved_progress['answers'])) {
                $has_progress = true;
                $answered_count = count($saved_progress['answers']);
            }
        }
        
        ?>
        <div id="sp-reminder-popup-overlay" class="sp-popup-overlay">
            <div class="sp-popup-container">
                <button type="button" class="sp-popup-close" aria-label="Close">&times;</button>
                
                <div class="sp-popup-content">
                    <div class="sp-popup-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                            <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    
                    <h2 class="sp-popup-title">Bună, <?php echo esc_html($first_name); ?>!</h2>
                    
                    <?php if ($has_progress): ?>
                    <p class="sp-popup-message">
                        Ai început testul vocațional!<br>
                        <strong>Ai răspuns deja la <?php echo $answered_count; ?> întrebări.</strong><br>
                        Continuă de unde ai rămas!
                    </p>
                    <?php else: ?>
                    <p class="sp-popup-message">
                        Nu ai uitat de testul vocațional, nu?<br>
                        Completează-l astăzi și descoperă-ți potențialul!
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($debug_mode): ?>
                    <div style="background: #fff3cd; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; color: #856404;">
                        <strong>🔧 DEBUG MODE ACTIV</strong> - Acest popup va apărea la fiecare încărcare de pagină pentru testare.
                    </div>
                    <?php endif; ?>
                    
                    <div class="sp-popup-buttons">
                        <a href="<?php echo esc_url($test_page_url); ?>" class="sp-popup-btn sp-popup-btn-primary">
                            <?php echo $has_progress ? 'Continuă Testul' : 'Fă Testul Acum'; ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </a>
                        <button type="button" class="sp-popup-btn sp-popup-btn-secondary sp-popup-dismiss">
                            Mai Târziu
                        </button>
                    </div>
                    
                    <p class="sp-popup-footer">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Te vom reaminti mâine
                    </p>
                </div>
            </div>
        </div>
        
        <?php 
        include(dirname(__FILE__) . '/popup-style.css.php');
        include(dirname(__FILE__) . '/popup-script.js.php');
    }
    
    /**
     * FIXED: Render CV reminder popup with proper redirect
     */
    private function render_cv_reminder_popup($first_name, $debug_mode) {
        // FIXED: Add #cv hash to redirect directly to CV step
        $cv_page_url = home_url('/inregistrare/#cv');
        
        ?>
        <div id="sp-reminder-popup-overlay" class="sp-popup-overlay">
            <div class="sp-popup-container">
                <button type="button" class="sp-popup-close" aria-label="Close">&times;</button>
                
                <div class="sp-popup-content">
                    <div class="sp-popup-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                    </div>
                    
                    <h2 class="sp-popup-title">Completează-ți CV-ul, <?php echo esc_html($first_name); ?>!</h2>
                    
                    <p class="sp-popup-message">
                        Ai finalizat testul vocațional! 🎉<br>
                        <strong>Construiește-ți profilul profesional</strong> pentru a fi gata de următorul pas în carieră.
                    </p>
                    
                    <?php if ($debug_mode): ?>
                    <div style="background: #fff3cd; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; color: #856404;">
                        <strong>🔧 DEBUG MODE ACTIV</strong> - Popup CV în mod testare.
                    </div>
                    <?php endif; ?>
                    
                    <div class="sp-popup-buttons">
                        <a href="<?php echo esc_url($cv_page_url); ?>" class="sp-popup-btn sp-popup-btn-primary">
                            Completează CV-ul Acum
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </a>
                        <button type="button" class="sp-popup-btn sp-popup-btn-secondary sp-popup-dismiss">
                            Mai Târziu
                        </button>
                    </div>
                    
                    <p class="sp-popup-footer">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        Îți reamintim într-o zi
                    </p>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // FIXED: If URL has #cv hash, go directly to CV step
            if (window.location.hash === '#cv') {
                setTimeout(function() {
                    // Try to use the goToStep function if available
                    if (typeof window.goToStep === 'function') {
                        window.goToStep('cv');
                    } else {
                        // Fallback: trigger click on CV progress step
                        $('.sp-progress-step[data-step="cv"]').trigger('click');
                        
                        // Or directly show CV step
                        $('.sp-step').removeClass('active');
                        $('#sp-step-cv').addClass('active');
                        $('.sp-progress-step').removeClass('active').addClass('completed');
                        $('.sp-progress-step[data-step="cv"]').addClass('active').removeClass('completed');
                    }
                }, 500);
            }
        });
        </script>
        
        <?php 
        include(dirname(__FILE__) . '/popup-style.css.php');
        include(dirname(__FILE__) . '/popup-script.js.php');
    }
}