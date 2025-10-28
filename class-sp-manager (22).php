<?php
/**
 * SP_Manager Class - FIXED: Complete Profile Message + All Features
 * Version: 4.5 - Fixed completion screen
 * 
 * FIXES:
 * 1. Shows completion message when both test and CV are done
 * 2. Ensures learning_style and work_environment are ALWAYS displayed
 * 3. All AI interpretation fields visible in completed test view
 * 4. Maintained all existing functionality
 */

if (!defined('ABSPATH')) exit;

class SP_Manager {
    
    private $plugin;
    
    public function __construct($plugin) {
        $this->plugin = $plugin;
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        add_shortcode('sp_onboarding_start', array($this, 'render_onboarding_flow'));
    }
    
    public function add_admin_menu() {
        add_menu_page('SuccessPlus Onboarding', 'SP Onboarding', 'manage_options', 'sp-onboarding', array($this, 'render_settings_page'), 'dashicons-welcome-learn-more', 30);
        add_submenu_page('sp-onboarding', 'Setări', 'Setări', 'manage_options', 'sp-onboarding', array($this, 'render_settings_page'));
        add_submenu_page('sp-onboarding', 'Întrebări Test', 'Întrebări Test', 'manage_options', 'sp-onboarding-questions', array($this, 'render_questions_page'));
        add_submenu_page('sp-onboarding', 'Câmpuri CV', 'Câmpuri CV', 'manage_options', 'sp-onboarding-cv-fields', array($this, 'render_cv_fields_page'));
        add_submenu_page('sp-onboarding', 'Câmpuri Profil', 'Câmpuri Profil', 'manage_options', 'sp-onboarding-profile-fields', array($this, 'render_profile_fields_page'));
        add_submenu_page('sp-onboarding', 'Sesiuni', 'Sesiuni', 'manage_options', 'sp-onboarding-sessions', array($this, 'render_sessions_page'));
    }
    
    public function register_settings() {
        register_setting('sp_onboarding_settings', 'sp_onboarding_openai_key');
        register_setting('sp_onboarding_settings', 'sp_onboarding_openai_model', array('default' => 'gpt-3.5-turbo'));
        register_setting('sp_onboarding_settings', 'sp_onboarding_user_roles', array('type' => 'array', 'default' => array('subscriber')));
        register_setting('sp_onboarding_settings', 'sp_onboarding_redirect_url');
        register_setting('sp_onboarding_settings', 'sp_onboarding_primary_color', array('default' => '#0292B7'));
        register_setting('sp_onboarding_settings', 'sp_onboarding_secondary_color', array('default' => '#1AC8DB'));
        register_setting('sp_onboarding_settings', 'sp_onboarding_accent_color', array('default' => '#C5EEF9'));
        register_setting('sp_onboarding_settings', 'sp_onboarding_test_title', array('default' => 'Test de Inteligență Multiplă'));
        register_setting('sp_onboarding_settings', 'sp_onboarding_test_title_color', array('default' => '#1a1a1a'));
        register_setting('sp_onboarding_settings', 'sp_onboarding_google_client_id');
        register_setting('sp_onboarding_settings', 'sp_onboarding_google_client_secret');
        register_setting('sp_onboarding_settings', 'sp_onboarding_enable_google_login', array('default' => 0));
        register_setting('sp_onboarding_settings', 'sp_onboarding_cv_title', array('default' => 'Construiește-ți Profilul Profesional'));
        register_setting('sp_onboarding_settings', 'sp_onboarding_cv_intro', array('default' => 'Spune-ne despre experiența și competențele tale.'));
        register_setting('sp_onboarding_settings', 'sp_onboarding_register_title', array('default' => 'Creează-ți Contul'));
        register_setting('sp_onboarding_settings', 'sp_onboarding_register_intro', array('default' => 'Începe prin a-ți crea contul pentru a accesa testul vocațional.'));
        register_setting('sp_onboarding_settings', 'sp_onboarding_mailerlite_api_key');
        register_setting('sp_onboarding_settings', 'sp_onboarding_mailerlite_group_id');
        register_setting('sp_onboarding_settings', 'sp_onboarding_enable_skip_test', array('default' => 0));
        register_setting('sp_onboarding_settings', 'sp_onboarding_skip_redirect_url');
        register_setting('sp_onboarding_settings', 'sp_onboarding_popup_debug_mode', array('default' => 0));
    }
    
    public function admin_scripts($hook) {
        if (strpos($hook, 'sp-onboarding') === false) return;
        wp_enqueue_style('sp-onboarding-admin', $this->plugin->plugin_url . 'admin/assets/css/admin-style.css', array(), $this->plugin->version);
        wp_enqueue_script('sp-onboarding-admin', $this->plugin->plugin_url . 'admin/assets/js/admin-script.js', array('jquery'), $this->plugin->version, true);
        wp_localize_script('sp-onboarding-admin', 'spAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sp_admin_nonce')
        ));
    }
    
    public function frontend_scripts() {
        if (!is_singular()) return;
        global $post;
        if (!has_shortcode($post->post_content, 'sp_onboarding_start')) return;
        
        wp_enqueue_style('raleway-font', 'https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;600;700&display=swap');
        wp_enqueue_style('sp-onboarding-style', $this->plugin->plugin_url . 'assets/css/style.css', array(), $this->plugin->version);
        wp_enqueue_script('sp-onboarding-script', $this->plugin->plugin_url . 'assets/js/script.js', array('jquery'), $this->plugin->version, true);
        
        $colors = array(
            'primary' => get_option('sp_onboarding_primary_color', '#0292B7'),
            'secondary' => get_option('sp_onboarding_secondary_color', '#1AC8DB'),
            'accent' => get_option('sp_onboarding_accent_color', '#C5EEF9')
        );
        
        wp_localize_script('sp-onboarding-script', 'spOnboarding', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sp_onboarding_nonce'),
            'colors' => $colors
        ));
        
        $custom_css = ":root {--sp-primary: {$colors['primary']};--sp-secondary: {$colors['secondary']};--sp-accent: {$colors['accent']};}";
        wp_add_inline_style('sp-onboarding-style', $custom_css);
    }
    
    public function render_settings_page() {
        global $wpdb;
        
        if (isset($_POST['sp_save_settings']) && check_admin_referer('sp_settings_nonce')) {
            update_option('sp_onboarding_openai_key', sanitize_text_field($_POST['sp_onboarding_openai_key']));
            update_option('sp_onboarding_openai_model', sanitize_text_field($_POST['sp_onboarding_openai_model']));
            $selected_roles = isset($_POST['sp_onboarding_user_roles']) ? array_map('sanitize_text_field', $_POST['sp_onboarding_user_roles']) : array('subscriber');
            update_option('sp_onboarding_user_roles', $selected_roles);
            update_option('sp_onboarding_redirect_url', esc_url_raw($_POST['sp_onboarding_redirect_url']));
            update_option('sp_onboarding_primary_color', sanitize_hex_color($_POST['sp_onboarding_primary_color']));
            update_option('sp_onboarding_secondary_color', sanitize_hex_color($_POST['sp_onboarding_secondary_color']));
            update_option('sp_onboarding_accent_color', sanitize_hex_color($_POST['sp_onboarding_accent_color']));
            update_option('sp_onboarding_test_title', sanitize_text_field($_POST['sp_onboarding_test_title']));
            update_option('sp_onboarding_test_title_color', sanitize_hex_color($_POST['sp_onboarding_test_title_color']));
            update_option('sp_onboarding_google_client_id', sanitize_text_field($_POST['sp_onboarding_google_client_id']));
            update_option('sp_onboarding_google_client_secret', sanitize_text_field($_POST['sp_onboarding_google_client_secret']));
            update_option('sp_onboarding_enable_google_login', isset($_POST['sp_onboarding_enable_google_login']) ? 1 : 0);
            update_option('sp_onboarding_cv_title', sanitize_text_field($_POST['sp_onboarding_cv_title']));
            update_option('sp_onboarding_cv_intro', sanitize_textarea_field($_POST['sp_onboarding_cv_intro']));
            update_option('sp_onboarding_register_title', sanitize_text_field($_POST['sp_onboarding_register_title']));
            update_option('sp_onboarding_register_intro', sanitize_textarea_field($_POST['sp_onboarding_register_intro']));
            update_option('sp_onboarding_mailerlite_api_key', sanitize_text_field($_POST['sp_onboarding_mailerlite_api_key']));
            update_option('sp_onboarding_mailerlite_group_id', sanitize_text_field($_POST['sp_onboarding_mailerlite_group_id']));
            update_option('sp_onboarding_enable_skip_test', isset($_POST['sp_onboarding_enable_skip_test']) ? 1 : 0);
            update_option('sp_onboarding_skip_redirect_url', esc_url_raw($_POST['sp_onboarding_skip_redirect_url']));
            update_option('sp_onboarding_popup_debug_mode', isset($_POST['sp_onboarding_popup_debug_mode']) ? 1 : 0);
            
            echo '<div class="notice notice-success"><p>Setări salvate cu succes!</p></div>';
        }
        
        $questions_count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->plugin->table_questions}");
        $selected_roles = get_option('sp_onboarding_user_roles', array('subscriber'));
        if (!is_array($selected_roles)) $selected_roles = array($selected_roles);
        
        require $this->plugin->plugin_dir . 'admin/views/view-settings-page.php';
    }
    
    public function render_sessions_page() {
        global $wpdb;
        
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            $wpdb->delete($this->plugin->table_sessions, array('id' => intval($_GET['id'])));
            echo '<div class="notice notice-success"><p>Sesiune ștearsă!</p></div>';
        }
        
        $sessions = $wpdb->get_results("SELECT * FROM {$this->plugin->table_sessions} ORDER BY created_at DESC");
        $intelligence_types = $this->plugin->intelligence_types;
        
        require $this->plugin->plugin_dir . 'admin/views/view-sessions-page.php';
    }
    
    public function render_cv_fields_page() {
        global $wpdb;
        
        if (isset($_POST['sp_save_cv_field']) && check_admin_referer('sp_cv_field_nonce')) {
            $field_id = isset($_POST['field_id']) ? intval($_POST['field_id']) : 0;
            
            $data = array(
                'field_name' => sanitize_key($_POST['field_name']),
                'field_label' => sanitize_text_field($_POST['field_label']),
                'field_type' => sanitize_text_field($_POST['field_type']),
                'field_options' => sanitize_textarea_field($_POST['field_options']),
                'field_description' => !empty($_POST['field_description']) ? sanitize_textarea_field($_POST['field_description']) : NULL,
                'field_placeholder' => !empty($_POST['field_placeholder']) ? sanitize_text_field($_POST['field_placeholder']) : NULL,
                'section_type' => sanitize_text_field($_POST['section_type']),
                'parent_id' => isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0,
                'add_button_text' => !empty($_POST['add_button_text']) ? sanitize_text_field($_POST['add_button_text']) : NULL,
                'negation_button_text' => !empty($_POST['negation_button_text']) ? sanitize_text_field($_POST['negation_button_text']) : NULL,
                'is_required' => isset($_POST['is_required']) ? 1 : 0,
                'sort_order' => intval($_POST['sort_order']),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            );
            
            if ($field_id > 0) {
                $wpdb->update($this->plugin->table_cv_fields, $data, array('id' => $field_id));
                echo '<div class="notice notice-success"><p>Câmp CV actualizat cu succes!</p></div>';
            } else {
                $wpdb->insert($this->plugin->table_cv_fields, $data);
                echo '<div class="notice notice-success"><p>Câmp CV adăugat cu succes!</p></div>';
            }
        }
        
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            $wpdb->delete($this->plugin->table_cv_fields, array('id' => intval($_GET['id'])));
            echo '<div class="notice notice-success"><p>Câmp CV șters!</p></div>';
        }
        
        $cv_fields = $wpdb->get_results("SELECT * FROM {$this->plugin->table_cv_fields} ORDER BY sort_order ASC");
        $editing = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $edit_field = $editing > 0 ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->plugin->table_cv_fields} WHERE id = %d", $editing)) : null;
        
        require $this->plugin->plugin_dir . 'admin/views/view-cv-fields-page.php';
    }
    
    public function render_questions_page() {
        global $wpdb;
        
        if (isset($_POST['sp_save_question']) && check_admin_referer('sp_question_nonce')) {
            $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
            $data = array(
                'question_text' => sanitize_textarea_field($_POST['question_text']),
                'intelligence_category' => intval($_POST['intelligence_category']),
                'sort_order' => intval($_POST['sort_order']),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            );
            
            if ($question_id > 0) {
                $wpdb->update($this->plugin->table_questions, $data, array('id' => $question_id));
                echo '<div class="notice notice-success"><p>Întrebare actualizată!</p></div>';
            } else {
                $wpdb->insert($this->plugin->table_questions, $data);
                echo '<div class="notice notice-success"><p>Întrebare adăugată!</p></div>';
            }
        }
        
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            $wpdb->delete($this->plugin->table_questions, array('id' => intval($_GET['id'])));
            echo '<div class="notice notice-success"><p>Întrebare ștearsă!</p></div>';
        }
        
        $questions = $wpdb->get_results("SELECT * FROM {$this->plugin->table_questions} ORDER BY sort_order ASC");
        $editing = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $edit_question = $editing > 0 ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->plugin->table_questions} WHERE id = %d", $editing)) : null;
        $intelligence_types = $this->plugin->intelligence_types;
        
        require $this->plugin->plugin_dir . 'admin/views/view-questions-page.php';
    }
    
    public function render_profile_fields_page() {
        global $wpdb;
        
        if (isset($_POST['sp_save_profile_field']) && check_admin_referer('sp_profile_field_nonce')) {
            $field_id = isset($_POST['field_id']) ? intval($_POST['field_id']) : 0;
            $data = array(
                'field_name' => sanitize_key($_POST['field_name']),
                'field_label' => sanitize_text_field($_POST['field_label']),
                'field_type' => sanitize_text_field($_POST['field_type']),
                'field_options' => sanitize_textarea_field($_POST['field_options']),
                'field_description' => !empty($_POST['field_description']) ? sanitize_textarea_field($_POST['field_description']) : NULL,
                'field_placeholder' => !empty($_POST['field_placeholder']) ? sanitize_text_field($_POST['field_placeholder']) : NULL,
                'is_required' => isset($_POST['is_required']) ? 1 : 0,
                'sort_order' => intval($_POST['sort_order']),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            );
            
            if ($field_id > 0) {
                $wpdb->update($this->plugin->table_profile_fields, $data, array('id' => $field_id));
                echo '<div class="notice notice-success"><p>Câmp Profil actualizat!</p></div>';
            } else {
                $wpdb->insert($this->plugin->table_profile_fields, $data);
                echo '<div class="notice notice-success"><p>Câmp Profil adăugat!</p></div>';
            }
        }
        
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            $wpdb->delete($this->plugin->table_profile_fields, array('id' => intval($_GET['id'])));
            echo '<div class="notice notice-success"><p>Câmp Profil șters!</p></div>';
        }
        
        $profile_fields = $wpdb->get_results("SELECT * FROM {$this->plugin->table_profile_fields} ORDER BY sort_order ASC");
        $editing = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $edit_field = $editing > 0 ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->plugin->table_profile_fields} WHERE id = %d", $editing)) : null;
        
        require $this->plugin->plugin_dir . 'admin/views/view-profile-fields-page.php';
    }
    
    public function render_onboarding_flow($atts) {
        $current_user_id = get_current_user_id();
        
        // FIXED: Check if user has completed EVERYTHING (test + CV)
        if ($current_user_id > 0) {
            $test_status = get_user_meta($current_user_id, 'sp_test_completed_status', true);
            $cv_data = get_user_meta($current_user_id, 'sp_cv_data', true);
            
            // If both test and CV are completed, show completion message
            if ($test_status === 'completed' && !empty($cv_data)) {
                return $this->render_completion_screen();
            }
        }
        
        $session_key = '';
        $saved_progress = array();
        
        $test_status = '';
        if ($current_user_id > 0) {
            $test_status = get_user_meta($current_user_id, 'sp_test_completed_status', true);
            
            if ($test_status === 'completed') {
                return $this->render_completed_test_results($current_user_id);
            }
            
            if ($test_status === 'in_progress' || $test_status === 'skipped') {
                $progress_data = get_user_meta($current_user_id, 'sp_test_progress', true);
                if ($progress_data && is_array($progress_data)) {
                    $saved_progress = $progress_data;
                }
            }
        }
        
        if ($current_user_id > 0) {
            global $wpdb;
            $existing_session = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->plugin->table_sessions} WHERE user_id = %d ORDER BY created_at DESC LIMIT 1",
                $current_user_id
            ));
            
            if ($existing_session) {
                $session_key = $existing_session->session_key;
            } else {
                $session_key = wp_generate_password(32, false);
                $wpdb->insert($this->plugin->table_sessions, array(
                    'session_key' => $session_key,
                    'user_id' => $current_user_id,
                    'current_step' => 'test',
                    'created_at' => current_time('mysql')
                ));
            }
            setcookie('sp_session_key', $session_key, time() + (86400 * 30), '/');
        } else {
            $session_key = isset($_COOKIE['sp_session_key']) ? sanitize_text_field($_COOKIE['sp_session_key']) : '';
            if (empty($session_key)) {
                $session_key = wp_generate_password(32, false);
                setcookie('sp_session_key', $session_key, time() + (86400 * 30), '/');
            }
        }
        
        ob_start();
        ?>
        <div class="sp-onboarding-container" 
             data-session="<?php echo esc_attr($session_key); ?>" 
             data-logged-in="<?php echo $current_user_id > 0 ? '1' : '0'; ?>"
             data-saved-progress='<?php echo !empty($saved_progress) ? esc_attr(json_encode($saved_progress)) : ''; ?>'>
            
            <div class="sp-progress-bar">
                <div class="sp-progress-step <?php echo $current_user_id > 0 ? 'completed' : 'active'; ?>" data-step="register">Înregistrare</div>
                <div class="sp-progress-step <?php echo $current_user_id > 0 ? 'active' : ''; ?>" data-step="test">Test</div>
                <div class="sp-progress-step" data-step="results">Rezultate</div>
                <div class="sp-progress-step" data-step="cv">CV</div>
            </div>
            
            <div class="sp-onboarding-content">
                <?php if ($current_user_id > 0): ?>
                    <div id="sp-step-register" class="sp-step">
                        <h2>✓ Cont creat cu succes!</h2>
                        <p class="sp-intro">Bine ai venit, <?php echo esc_html(wp_get_current_user()->display_name); ?>!</p>
                    </div>
                    <div id="sp-step-test" class="sp-step active">
                        <?php $this->render_test_step($session_key, $saved_progress); ?>
                    </div>
                <?php else: ?>
                    <div id="sp-step-register" class="sp-step active">
                        <?php $this->render_registration_step($session_key); ?>
                    </div>
                    <div id="sp-step-test" class="sp-step">
                        <?php $this->render_test_step($session_key, array()); ?>
                    </div>
                <?php endif; ?>
                
                <div id="sp-step-results" class="sp-step">
                    <div class="sp-loading">Analizăm răspunsurile tale...</div>
                    <div class="sp-results-content"></div>
                </div>
                
                <div id="sp-step-cv" class="sp-step">
                    <?php $this->render_cv_step($session_key); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * FIXED: Render completion screen when everything is done
     */
    private function render_completion_screen() {
        $user = wp_get_current_user();
        $first_name = $user->first_name ?: 'Felicitări';
        
        ob_start();
        ?>
        <div class="sp-onboarding-container sp-completed-profile">
            <div class="sp-completion-message">
                <div class="sp-completion-icon">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <h2 style="color: #10b981; margin: 20px 0 10px 0;">Felicitări, <?php echo esc_html($first_name); ?>!</h2>
                <p style="font-size: 18px; color: #666; margin-bottom: 30px; line-height: 1.6;">
                    Ai completat cu succes profilul tău!<br>
                    <strong>Testul vocațional</strong> și <strong>CV-ul</strong> sunt finalizate.
                </p>
                <!-- FIXED: Button now has sp-btn-back-home class for consistent styling -->
                <a href="<?php echo esc_url(home_url('/')); ?>" class="sp-btn sp-btn-back-home">
                    ← Înapoi la pagina principală
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * FIXED: Render completed test results with ALL AI fields
     */
    private function render_completed_test_results($user_id) {
        $ai_analysis = get_user_meta($user_id, 'sp_ai_analysis', true);
        $intelligence_scores = get_user_meta($user_id, 'sp_intelligence_scores', true);
        $dominant_type = get_user_meta($user_id, 'sp_intelligence_type', true);
        
        if (is_string($ai_analysis)) {
            $ai_analysis = json_decode($ai_analysis, true);
        }
        
        global $wpdb;
        $existing_session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->plugin->table_sessions} WHERE user_id = %d ORDER BY created_at DESC LIMIT 1",
            $user_id
        ));
        
        $session_key = $existing_session ? $existing_session->session_key : wp_generate_password(32, false);
        
        ob_start();
        ?>
        <div class="sp-onboarding-container sp-test-completed" 
             data-session="<?php echo esc_attr($session_key); ?>" 
             data-logged-in="1"
             data-test-completed="1">
            <div class="sp-progress-bar">
                <div class="sp-progress-step completed" data-step="register">Înregistrare</div>
                <div class="sp-progress-step completed" data-step="test">Test</div>
                <div class="sp-progress-step active" data-step="results">Rezultate</div>
                <div class="sp-progress-step" data-step="cv">CV</div>
            </div>
            <div class="sp-onboarding-content">
                <div id="sp-step-results" class="sp-step active">
                    <h2>Rezultatele Tale</h2>
                    <p class="sp-intro">Ai finalizat deja testul vocațional. Iată rezultatele tale:</p>
                    
                    <div class="sp-results-content">
                        <?php echo $this->format_completed_test_results($intelligence_scores, $dominant_type, $ai_analysis, $user_id); ?>
                    </div>
                </div>
                
                <div id="sp-step-cv" class="sp-step">
                    <?php $this->render_cv_step($session_key); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * FIXED: Format completed test results with ALL AI fields
     */
    private function format_completed_test_results($scores, $dominant_type, $ai_analysis, $user_id) {
        $html = '';
        
        // Profile Description
        if ($ai_analysis && isset($ai_analysis['profile_description'])) {
            $html .= '<div class="sp-results-section sp-profile-description-section">';
            $html .= '<h3>Profilul Tău Profesional</h3>';
            $html .= '<div class="sp-collapsible-section">';
            $html .= '<div class="sp-analysis sp-analysis-detailed">';
            $html .= '<div class="sp-collapsible-content sp-collapsed">';
            $html .= '<div class="sp-analysis-content">' . nl2br(esc_html($ai_analysis['profile_description'])) . '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<button type="button" class="sp-read-more-btn"><span class="sp-arrow">▼</span> Citește mai mult</button>';
            $html .= '</div></div>';
        }
        
        // Learning Style - ALWAYS DISPLAY
        if ($ai_analysis && isset($ai_analysis['learning_style']) && !empty($ai_analysis['learning_style'])) {
            $html .= '<div class="sp-results-section">';
            $html .= '<h3>Stilul Tău de Învățare</h3>';
            $html .= '<div class="sp-learning-style-box">';
            $html .= '<p>' . nl2br(esc_html($ai_analysis['learning_style'])) . '</p>';
            $html .= '</div></div>';
        }
        
        // Work Environment - ALWAYS DISPLAY
        if ($ai_analysis && isset($ai_analysis['work_environment']) && !empty($ai_analysis['work_environment'])) {
            $html .= '<div class="sp-results-section">';
            $html .= '<h3>Mediul de Lucru Ideal</h3>';
            $html .= '<div class="sp-work-environment-box">';
            $html .= '<p>' . nl2br(esc_html($ai_analysis['work_environment'])) . '</p>';
            $html .= '</div></div>';
        }
        
        // Soft Skills
        if ($ai_analysis && isset($ai_analysis['soft_skills']) && !empty($ai_analysis['soft_skills'])) {
            $html .= '<div class="sp-results-section sp-soft-skills-section">';
            $html .= '<h3>Competențe Soft Identificate</h3>';
            $html .= '<div class="sp-skills-grid">';
            
            $test_count = 1;
            if ($user_id) {
                $test_count = intval(get_user_meta($user_id, 'sp_test_count', true));
                if ($test_count < 1) $test_count = 1;
            }
            
            $max_expected_points = 25 * $test_count;
            
            foreach ($ai_analysis['soft_skills'] as $skill) {
                if (is_array($skill) && isset($skill['skill']) && isset($skill['points'])) {
                    $skill_name = $skill['skill'];
                    $skill_points = intval($skill['points']);
                    $skill_color = isset($skill['color']) ? $skill['color'] : '#0292B7';
                } else {
                    continue;
                }
                
                $bar_width = min(($skill_points / $max_expected_points) * 100, 100);
                
                $mastery_class = $skill_points >= 100 ? ' sp-skill-mastery' : '';
                $mastery_badge = $skill_points >= 100 ? ' <span class="sp-mastery-badge">★</span>' : '';
                
                $html .= '<div class="sp-skill-card sp-soft-skill' . $mastery_class . '" style="--skill-color: ' . esc_attr($skill_color) . ';">';
                $html .= '<h4>' . esc_html($skill_name) . $mastery_badge . '</h4>';
                $html .= '<div class="sp-skill-bar"><div class="sp-skill-bar-fill" data-width="' . $bar_width . '" style="width: ' . $bar_width . '%"></div></div>';
                $html .= '<div class="sp-skill-percentage">' . $skill_points . ' puncte</div>';
                $html .= '</div>';
            }
            
            $html .= '</div></div>';
        }
        
        // Intelligence Distribution
        $html .= '<div class="sp-results-section"><h3>Distribuția Tipurilor de Inteligență</h3>';
        $html .= '<div class="sp-skills-grid">';
        
        arsort($scores);
        foreach ($scores as $type => $score) {
            if (!isset($this->plugin->intelligence_types[$type])) continue;
            
            $percentage = intval($score * (100 / $this->plugin->intelligence_category_count));
            
            $html .= '<div class="sp-skill-card">';
            $html .= '<h4>' . esc_html($this->plugin->intelligence_types[$type]['name']) . '</h4>';
            $html .= '<div class="sp-skill-bar"><div class="sp-skill-bar-fill" data-width="' . $percentage . '" style="width: ' . $percentage . '%"></div></div>';
            $html .= '<div class="sp-skill-percentage">' . $score . '/' . $this->plugin->intelligence_category_count . ' DA (' . $percentage . '%)</div>';
            $html .= '</div>';
        }
        
        $html .= '</div></div>';
        
        // Dominant Type
        if (isset($this->plugin->intelligence_types[$dominant_type])) {
            $percentage = intval($scores[$dominant_type] * (100 / $this->plugin->intelligence_category_count));
            $html .= '<div class="sp-results-section"><h3>Tipul Tău Dominant de Inteligență</h3>';
            $html .= '<div class="sp-career-card sp-dominant-card">';
            $html .= '<div class="sp-career-header">';
            $html .= '<div class="sp-career-title">' . esc_html($this->plugin->intelligence_types[$dominant_type]['name']) . '</div>';
            $html .= '<div class="sp-career-match">' . $percentage . '%</div>';
            $html .= '</div>';
            $html .= '<div class="sp-career-description">Specializări recomandate: ' . esc_html($this->plugin->intelligence_types[$dominant_type]['specializations']) . '</div>';
            $html .= '</div></div>';
        }
        
        // Specialization Recommendations
        if ($ai_analysis && isset($ai_analysis['specialization_recommendations'])) {
            $html .= '<div class="sp-results-section">';
            $html .= '<h3>Recomandări de Specializări</h3>';
            $html .= '<div class="sp-collapsible-section">';
            $html .= '<div class="sp-specialization-text">';
            $html .= '<div class="sp-collapsible-content sp-collapsed">';
            $html .= '<p>' . nl2br(esc_html($ai_analysis['specialization_recommendations'])) . '</p>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<button type="button" class="sp-read-more-btn"><span class="sp-arrow">▼</span> Citește mai mult</button>';
            $html .= '</div></div>';
        }
        
        $html .= '<div class="sp-nav-buttons" style="margin-top: 40px;">';
        $html .= '<button class="sp-btn sp-btn-primary sp-continue-btn">Continuă spre Profil</button>';
        $html .= '</div>';
        
        return $html;
    }
    
    private function render_test_step($session_key, $saved_progress = array()) {
        global $wpdb;
        $questions = $wpdb->get_results("SELECT * FROM {$this->plugin->table_questions} WHERE is_active = 1 ORDER BY sort_order ASC");
        
        if (empty($questions)) {
            echo '<div class="sp-error-message">Nu există întrebări configurate. Vă rugăm contactați administratorul.</div>';
            return;
        }
        
        $skip_enabled = get_option('sp_onboarding_enable_skip_test', 0);
        $current_user_id = get_current_user_id();
        $test_status = '';
        
        if ($current_user_id > 0) {
            $test_status = get_user_meta($current_user_id, 'sp_test_completed_status', true);
        }
        
        $show_skip = $skip_enabled && $current_user_id > 0 && $test_status !== 'completed';
        
        $test_title = get_option('sp_onboarding_test_title', 'Test de Inteligență Multiplă');
        $test_title_color = get_option('sp_onboarding_test_title_color', '#1a1a1a');
        
        $has_progress = !empty($saved_progress) && isset($saved_progress['answers']) && count($saved_progress['answers']) > 0;
        ?>
        
        <?php if ($show_skip): ?>
        <div class="sp-skip-test-container">
            <button type="button" class="sp-btn-skip-test" id="sp-skip-test-btn">
                Fă testul mai târziu
            </button>
        </div>
        <?php endif; ?>
        
        <h2 style="color: <?php echo esc_attr($test_title_color); ?>;"><?php echo esc_html($test_title); ?></h2>
        <p class="sp-intro">Răspundeți la următoarele <?php echo count($questions); ?> întrebări care vă caracterizează cu "DA" sau "NU".</p>
        
        <div class="sp-question-progress">
            <span class="sp-current-question">1</span> / <span class="sp-total-questions"><?php echo count($questions); ?></span>
        </div>
        
        <form id="sp-test-form">
            <div class="sp-questions-container">
                <?php foreach ($questions as $i => $q): ?>
                <div class="sp-question <?php echo $i === 0 ? 'sp-question-active' : ''; ?>" 
                     data-question-id="<?php echo $q->id; ?>" 
                     data-category="<?php echo $q->intelligence_category; ?>" 
                     data-index="<?php echo $i; ?>">
                    <label class="sp-question-label">
                        <span class="sp-question-number"><?php echo ($i + 1); ?></span>
                        <?php echo esc_html($q->question_text); ?>
                    </label>
                    <div class="sp-options sp-yes-no-options">
                        <label class="sp-option sp-option-yes">
                            <input type="radio" name="question_<?php echo $q->id; ?>" value="da" />
                            <span>DA</span>
                        </label>
                        <label class="sp-option sp-option-no">
                            <input type="radio" name="question_<?php echo $q->id; ?>" value="nu" />
                            <span>NU</span>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="sp-nav-buttons">
                <button type="button" class="sp-btn sp-btn-back sp-question-prev" style="display: none;">Înapoi</button>
                <button type="submit" class="sp-btn sp-btn-primary sp-test-submit" style="display: none;">Trimite Testul</button>
            </div>
        </form>
        <?php
    }
    
    private function render_cv_step($session_key) {
        global $wpdb;
        
        $sections = $wpdb->get_results("SELECT * FROM {$this->plugin->table_cv_fields} WHERE is_active = 1 AND parent_id = 0 ORDER BY sort_order ASC");
        
        $cv_title = get_option('sp_onboarding_cv_title', 'Construiește-ți Profilul Profesional');
        $cv_intro = get_option('sp_onboarding_cv_intro', 'Spune-ne despre experiența și competențele tale.');
        
        $skip_enabled = get_option('sp_onboarding_enable_skip_test', 0);
        $current_user_id = get_current_user_id();
        $show_skip = $skip_enabled && $current_user_id > 0;
        ?>
        
        <?php if ($show_skip): ?>
        <div class="sp-skip-test-container">
            <button type="button" class="sp-btn-skip-test" id="sp-skip-cv-btn">
                Completează CV-ul mai târziu
            </button>
        </div>
        <?php endif; ?>
        
        <h2><?php echo esc_html($cv_title); ?></h2>
        <p class="sp-intro"><?php echo esc_html($cv_intro); ?></p>
        
        <form id="sp-cv-form">
            <?php foreach ($sections as $section): ?>
                <?php $this->render_cv_section($section); ?>
            <?php endforeach; ?>
            
            <div class="sp-nav-buttons">
                <button type="submit" class="sp-btn sp-btn-primary">Finalizează</button>
            </div>
        </form>
        <?php
    }
    
    private function render_cv_section($section) {
        global $wpdb;
        
        $field_name = 'cv_' . $section->field_name;
        $has_negation = !empty($section->negation_button_text);
        
        if ($section->section_type === 'single') {
            ?>
            <div class="sp-form-group" data-field-name="<?php echo esc_attr($section->field_name); ?>">
                <div class="sp-field-label-row">
                    <label for="<?php echo esc_attr($field_name); ?>">
                        <?php echo esc_html($section->field_label); ?>
                        <?php if ($section->is_required && !$has_negation): ?><span class="sp-required">*</span><?php endif; ?>
                    </label>
                    <?php if ($has_negation): ?>
                    <button type="button" class="sp-btn-negation" data-field="<?php echo esc_attr($field_name); ?>">
                        <?php echo esc_html($section->negation_button_text); ?>
                    </button>
                    <?php endif; ?>
                </div>
                
                <?php if ($section->field_description): ?>
                <p class="sp-field-description"><?php echo esc_html($section->field_description); ?></p>
                <?php endif; ?>
                
                <?php
                $placeholder = $section->field_placeholder ? 'placeholder="' . esc_attr($section->field_placeholder) . '"' : '';
                $required = ($section->is_required && !$has_negation) ? 'required' : '';
                
                if ($section->field_type === 'textarea'): ?>
                    <textarea id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field_name); ?>" rows="4" <?php echo $placeholder; ?> <?php echo $required; ?>></textarea>
                <?php elseif ($section->field_type === 'select'): 
                    $options = json_decode($section->field_options, true); ?>
                    <select id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field_name); ?>" <?php echo $required; ?>>
                        <option value="">-- Selectează --</option>
                        <?php if (is_array($options)): foreach ($options as $opt): ?>
                            <option value="<?php echo esc_attr($opt); ?>"><?php echo esc_html($opt); ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                <?php else: ?>
                    <input type="<?php echo esc_attr($section->field_type); ?>" id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field_name); ?>" <?php echo $placeholder; ?> <?php echo $required; ?> />
                <?php endif; ?>
                
                <input type="hidden" name="<?php echo esc_attr($field_name); ?>_negated" value="0" class="negation-state" />
                <div class="sp-negation-message" style="display: none;">
                    <span class="sp-checkmark">✓</span> Am înțeles
                </div>
            </div>
            <?php
            
        } elseif ($section->section_type === 'repeatable') {
            $child_fields = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$this->plugin->table_cv_fields} WHERE parent_id = %d AND is_active = 1 ORDER BY sort_order ASC",
                $section->id
            ));
            
            ?>
            <div class="sp-repeatable-section" data-field-name="<?php echo esc_attr($section->field_name); ?>">
                <div class="sp-field-label-row">
                    <h3 class="sp-section-title"><?php echo esc_html($section->field_label); ?></h3>
                    <?php if ($has_negation): ?>
                    <button type="button" class="sp-btn-negation sp-section-negation" data-section="<?php echo esc_attr($section->field_name); ?>">
                        <?php echo esc_html($section->negation_button_text); ?>
                    </button>
                    <?php endif; ?>
                </div>
                
                <?php if ($section->field_description): ?>
                <p class="sp-field-description"><?php echo esc_html($section->field_description); ?></p>
                <?php endif; ?>
                
                <div class="sp-repeatable-container" id="container-<?php echo esc_attr($section->field_name); ?>">
                </div>
                
                <button type="button" class="sp-btn sp-btn-secondary sp-add-repeatable" 
                        data-section="<?php echo esc_attr($section->field_name); ?>"
                        data-template="template-<?php echo esc_attr($section->field_name); ?>">
                    + <?php echo $section->add_button_text ? esc_html($section->add_button_text) : 'Adaugă'; ?>
                </button>
                
                <input type="hidden" name="<?php echo esc_attr($field_name); ?>_negated" value="0" class="section-negation-state" />
                <div class="sp-negation-message sp-section-negation-message" style="display: none;">
                    <span class="sp-checkmark">✓</span> Am înțeles
                </div>
                
                <template id="template-<?php echo esc_attr($section->field_name); ?>">
                    <div class="sp-repeatable-entry">
                        <div class="sp-repeatable-entry-header">
                            <span class="sp-entry-number">#<span class="entry-index">1</span></span>
                            <button type="button" class="sp-btn-remove-entry">✕ Șterge</button>
                        </div>
                        <div class="sp-repeatable-entry-body">
                            <?php foreach ($child_fields as $child): 
                                $child_name = 'cv_' . $section->field_name . '[INDEX][' . $child->field_name . ']';
                                $placeholder = $child->field_placeholder ? 'placeholder="' . esc_attr($child->field_placeholder) . '"' : '';
                                $required = $child->is_required ? 'required' : '';
                            ?>
                            <div class="sp-form-group">
                                <label>
                                    <?php echo esc_html($child->field_label); ?>
                                    <?php if ($child->is_required): ?><span class="sp-required">*</span><?php endif; ?>
                                </label>
                                
                                <?php if ($child->field_description): ?>
                                <p class="sp-field-description"><?php echo esc_html($child->field_description); ?></p>
                                <?php endif; ?>
                                
                                <?php if ($child->field_type === 'textarea'): ?>
                                    <textarea name="<?php echo esc_attr($child_name); ?>" rows="3" <?php echo $placeholder; ?> <?php echo $required; ?>></textarea>
                                <?php elseif ($child->field_type === 'select'): 
                                    $options = json_decode($child->field_options, true); ?>
                                    <select name="<?php echo esc_attr($child_name); ?>" <?php echo $required; ?>>
                                        <option value="">-- Selectează --</option>
                                        <?php if (is_array($options)): foreach ($options as $opt): ?>
                                            <option value="<?php echo esc_attr($opt); ?>"><?php echo esc_html($opt); ?></option>
                                        <?php endforeach; endif; ?>
                                    </select>
                                <?php else: ?>
                                    <input type="<?php echo esc_attr($child->field_type); ?>" name="<?php echo esc_attr($child_name); ?>" <?php echo $placeholder; ?> <?php echo $required; ?> />
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </template>
            </div>
            <?php
            
        } elseif ($section->section_type === 'checkbox') {
            ?>
            <div class="sp-form-group sp-checkbox-group">
                <label class="sp-checkbox-label">
                    <input type="checkbox" name="<?php echo esc_attr($field_name); ?>" value="1" />
                    <span><?php echo esc_html($section->field_label); ?></span>
                </label>
                <?php if ($section->field_description): ?>
                <p class="sp-field-description"><?php echo esc_html($section->field_description); ?></p>
                <?php endif; ?>
            </div>
            <?php
        }
    }
    
    private function render_registration_step($session_key) {
        global $wpdb;
        
        $google_enabled = get_option('sp_onboarding_enable_google_login', 0);
        $google_client_id = get_option('sp_onboarding_google_client_id');
        
        $register_title = get_option('sp_onboarding_register_title', 'Creează-ți Contul');
        $register_intro = get_option('sp_onboarding_register_intro', 'Începe prin a-ți crea contul pentru a accesa testul vocațional.');
        
        $profile_fields = $wpdb->get_results(
            "SELECT * FROM {$this->plugin->table_profile_fields} WHERE is_active = 1 ORDER BY sort_order ASC"
        );
        ?>
        <h2><?php echo esc_html($register_title); ?></h2>
        <p class="sp-intro"><?php echo esc_html($register_intro); ?></p>
        
        <?php if ($google_enabled && !empty($google_client_id)): ?>
        <button type="button" class="sp-btn sp-btn-google" id="google-signin-btn">
            <span class="sp-google-icon">G</span>
            Continuă cu Google
        </button>
        <div class="sp-divider"><span>SAU</span></div>
        <?php endif; ?>
        
        <form id="sp-register-form">
            <?php foreach ($profile_fields as $field): ?>
                <div class="sp-form-group">
                    <label for="reg_<?php echo esc_attr($field->field_name); ?>">
                        <?php echo esc_html($field->field_label); ?>
                        <?php if ($field->is_required): ?><span class="sp-required">*</span><?php endif; ?>
                    </label>
                    
                    <?php
                    $field_name = 'reg_' . $field->field_name;
                    $required = $field->is_required ? 'required' : '';
                    $placeholder = $field->field_placeholder ? 'placeholder="' . esc_attr($field->field_placeholder) . '"' : '';
                    
                    if ($field->field_type === 'textarea'): ?>
                        <textarea id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field->field_name); ?>" rows="4" <?php echo $placeholder; ?> <?php echo $required; ?>></textarea>
                    
                    <?php elseif ($field->field_type === 'select'): 
                        $options = json_decode($field->field_options, true); ?>
                        <select id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field->field_name); ?>" <?php echo $required; ?>>
                            <option value="">-- Selectează --</option>
                            <?php if (is_array($options)): foreach ($options as $opt): ?>
                                <option value="<?php echo esc_attr($opt); ?>"><?php echo esc_html($opt); ?></option>
                            <?php endforeach; endif; ?>
                        </select>
                    
                    <?php elseif ($field->field_type === 'sex'): ?>
                        <select id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field->field_name); ?>" <?php echo $required; ?> class="sp-full-width">
                            <option value="">-- Selectează --</option>
                            <option value="M">Masculin</option>
                            <option value="F">Feminin</option>
                        </select>
                    
                    <?php elseif ($field->field_type === 'birthdate'): ?>
                        <input type="date" id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field->field_name); ?>" <?php echo $required; ?> max="<?php echo date('Y-m-d'); ?>" class="sp-full-width" />
                    
                    <?php elseif ($field->field_type === 'password'): ?>
                        <div class="sp-password-input-wrapper">
                            <input type="password" id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field->field_name); ?>" <?php echo $placeholder; ?> <?php echo $required; ?> minlength="8" class="sp-password-field" />
                            <button type="button" class="sp-toggle-password" data-target="<?php echo esc_attr($field_name); ?>">
                                <svg class="sp-eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg class="sp-eye-closed" style="display: none;" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                    
                    <?php else: 
                        $is_wide = in_array($field->field_type, array('email', 'tel', 'date'));
                        $class = $is_wide ? 'sp-full-width' : '';
                    ?>
                        <input type="<?php echo esc_attr($field->field_type); ?>" id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field->field_name); ?>" <?php echo $placeholder; ?> <?php echo $required; ?> class="<?php echo $class; ?>" />
                    <?php endif; ?>
                    
                    <?php if ($field->field_description): ?>
                        <small><?php echo esc_html($field->field_description); ?></small>
                    <?php endif; ?>
                    
                    <?php if ($field->field_name === 'password'): ?>
                        <div id="password-strength-indicator" class="sp-password-strength"></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <div class="sp-nav-buttons">
                <button type="submit" class="sp-btn sp-btn-primary">Creează Contul și Continuă</button>
            </div>
        </form>
        <?php
    }
}