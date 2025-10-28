<?php
/**
 * Plugin Name: SP Professional Profile Complete
 * Description: Complete professional profile tab for BuddyBoss with modern design - FIXED v2.2
 * Version: 2.3
 * Author: SuccessPlus
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

/**
 * Main Professional Profile Class
 */
class SP_Professional_Profile_Complete {
    
    private $plugin_path;
    private $plugin_url;
    private $version = '6.3.2';
    private $db_version = '6.3.2';
    
    public function __construct() {
        // Use __FILE__ for correct path resolution
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // CRITICAL: Set proper charset on init
        add_action('init', array($this, 'set_charset'), 1);
        
        // Initialize hooks
        add_action('plugins_loaded', array($this, 'init'), 1);
        add_action('plugins_loaded', array($this, 'check_version'), 2);
        add_action('bp_init', array($this, 'setup_nav'), 100);
        
        // Admin notices
        if (is_admin()) {
            add_action('admin_notices', array($this, 'admin_notice'));
            add_action('admin_bar_menu', array($this, 'admin_bar_link'), 100);
        }
        
        // AJAX handlers for CV download
        add_action('wp_ajax_sp_download_cv', array($this, 'handle_cv_download'));
        add_action('wp_ajax_nopriv_sp_download_cv', array($this, 'handle_cv_download'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        global $wpdb;
        
        // Set proper charset on activation
        $wpdb->query("SET NAMES 'utf8mb4'");
        
        // Save plugin version
        update_option('sp_professional_profile_version', $this->version);
        update_option('sp_professional_profile_db_version', $this->db_version);
        update_option('sp_professional_profile_activated', current_time('mysql'));
        
        // Ensure database tables use utf8mb4
        $tables = array(
            $wpdb->prefix . 'sp_cv_fields',
            $wpdb->prefix . 'sp_onboarding_sessions'
        );
        
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
                $wpdb->query("ALTER TABLE $table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
        }
        
        // Create uploads directory for CV files if needed
        $upload_dir = wp_upload_dir();
        $cv_dir = $upload_dir['basedir'] . '/cv-files/';
        
        if (!file_exists($cv_dir)) {
            wp_mkdir_p($cv_dir);
            
            // Add .htaccess for security
            $htaccess_content = "Options -Indexes\n<Files *.php>\nDeny from all\n</Files>";
            file_put_contents($cv_dir . '.htaccess', $htaccess_content);
        }
        
        // Log activation
        error_log('SP Professional Profile Complete v' . $this->version . ' activated with UTF-8mb4 support');
        
        // Set activation notice flag
        set_transient('sp_professional_profile_activation_notice', true, 60);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Update last deactivation time
        update_option('sp_professional_profile_deactivated', current_time('mysql'));
        
        // Log deactivation
        error_log('SP Professional Profile Complete v' . $this->version . ' deactivated');
        
        // Clean up transients
        delete_transient('sp_professional_profile_activation_notice');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Check version and run upgrades if needed
     */
    public function check_version() {
        $installed_version = get_option('sp_professional_profile_version', '0.0.0');
        
        if (version_compare($installed_version, $this->version, '<')) {
            $this->upgrade($installed_version);
        }
    }
    
    /**
     * Handle plugin upgrades
     */
    private function upgrade($from_version) {
        global $wpdb;
        
        // Set charset for upgrade
        $wpdb->query("SET NAMES 'utf8mb4'");
        
        // Run upgrade routines based on version
        if (version_compare($from_version, '2.0', '<')) {
            $this->upgrade_to_20();
        }
        
        // Update version numbers
        update_option('sp_professional_profile_version', $this->version);
        update_option('sp_professional_profile_db_version', $this->db_version);
        update_option('sp_professional_profile_upgraded_from', $from_version);
        update_option('sp_professional_profile_last_upgrade', current_time('mysql'));
        
        // Set upgrade notice
        set_transient('sp_professional_profile_upgraded_notice', array(
            'from' => $from_version,
            'to' => $this->version
        ), 300);
        
        error_log(sprintf(
            'SP Professional Profile Complete upgraded from v%s to v%s (UTF-8mb4 + CV Array fix)',
            $from_version,
            $this->version
        ));
    }
    
    /**
     * Upgrade to version 2.0+
     */
    private function upgrade_to_20() {
        global $wpdb;
        
        error_log('SP Professional Profile: Upgrading to v2.2 - UTF-8mb4 + CV Array fix');
        
        // Convert tables to UTF-8mb4
        $tables = array(
            $wpdb->prefix . 'sp_cv_fields',
            $wpdb->prefix . 'sp_onboarding_sessions',
            $wpdb->prefix . 'usermeta'
        );
        
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
                $wpdb->query("ALTER TABLE $table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
        }
        
        // Clear any cached profile data
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_sp_profile_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_sp_profile_%'");
    }
    
    /**
     * CRITICAL: Set proper charset for Romanian diacritics
     */
    public function set_charset() {
        global $wpdb;
        
        // Set database charset
        if (method_exists($wpdb, 'set_charset')) {
            $wpdb->set_charset($wpdb->dbh, 'utf8mb4', 'utf8mb4_unicode_ci');
        }
        
        // Set PHP charset
        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding('UTF-8');
        }
        
        // Set header charset (for AJAX and page output)
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        if (!function_exists('bp_is_active')) {
            return;
        }
        
        // Load helper functions with error checking
        $helpers_file = $this->plugin_path . 'includes/helpers.php';
        if (file_exists($helpers_file)) {
            require_once $helpers_file;
        } else {
            error_log('SP Professional Profile: helpers.php not found at ' . $helpers_file);
        }
        
        // Load text domain for translations
        load_plugin_textdomain('sp-professional-profile', false, 
            dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Setup BuddyPress/BuddyBoss navigation
     */
    public function setup_nav() {
        if (!function_exists('bp_core_new_nav_item')) {
            return;
        }
        
        bp_core_new_nav_item(array(
            'name'                    => __('Profil Profesional', 'sp-professional'),
            'slug'                    => 'professional-profile',
            'position'                => 50,
            'screen_function'         => array($this, 'screen_function'),
            'default_subnav_slug'     => 'view',
            'item_css_id'             => 'professional-profile',
            'show_for_displayed_user' => true
        ));
    }
    
    /**
     * Screen function - loads the template
     */
    public function screen_function() {
        add_action('bp_template_title', array($this, 'template_title'));
        add_action('bp_template_content', array($this, 'template_content'));
        
        // Force enqueue assets here
        $this->force_enqueue_assets();
        
        bp_core_load_template(apply_filters('bp_core_template_plugin', 'members/single/plugins'));
    }
    
    /**
     * Force enqueue assets when screen loads
     */
    public function force_enqueue_assets() {
        // Enqueue dashicons for icons
        wp_enqueue_style('dashicons');
        
        // Add Raleway font
        wp_enqueue_style(
            'raleway-font',
            'https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700&display=swap',
            array(),
            null
        );
        
        // Enqueue styles
        wp_enqueue_style(
            'sp-professional-profile',
            $this->plugin_url . 'assets/css/profile-style.css',
            array('dashicons'),
            $this->version
        );
        
        // Enqueue scripts
        wp_enqueue_script(
            'sp-professional-profile',
            $this->plugin_url . 'assets/js/profile-script.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script('sp-professional-profile', 'spProfile', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sp_profile_nonce'),
            'userId' => bp_displayed_user_id(),
            'version' => $this->version
        ));
    }
    
    /**
     * Template title - REMOVED for cleaner business view
     */
    public function template_title() {
        // Title removed for modern, business-focused display
        echo '';
    }
    
    /**
     * Template content - loads the main template
     */
    public function template_content() {
        $user_id = bp_displayed_user_id();
        
        // Load data helper with error checking
        $data_loader = $this->plugin_path . 'includes/data-loader.php';
        if (!file_exists($data_loader)) {
            echo '<p>Error: Data loader not found. Please check plugin installation.</p>';
            return;
        }
        require_once $data_loader;
        
        $profile_data = sp_load_profile_data($user_id);
        
        // Load the main template with error checking
        $template = $this->plugin_path . 'templates/profile-main.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<p>Error: Profile template not found. Please reinstall the plugin.</p>';
        }
    }
    
    /**
     * Handle CV download AJAX
     */
    public function handle_cv_download() {
        check_ajax_referer('sp_profile_nonce', 'nonce');
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'Invalid user ID'));
        }
        
        // Check permissions
        if (!current_user_can('edit_user', $user_id) && get_current_user_id() != $user_id) {
            wp_send_json_error(array('message' => 'Nu ai permisiunea de a descărca acest CV.'));
        }
        
        // Load CV generator with error checking
        $cv_generator = $this->plugin_path . 'includes/cv-generator.php';
        if (!file_exists($cv_generator)) {
            wp_send_json_error(array('message' => 'CV generator not found. Please reinstall the plugin.'));
            return;
        }
        require_once $cv_generator;
        
        // Load data loader for CV generation
        require_once $this->plugin_path . 'includes/data-loader.php';
        
        $result = sp_generate_cv_pdf($user_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Admin notice
     */
    public function admin_notice() {
        if (!current_user_can('administrator')) return;
        
        $screen = get_current_screen();
        
        // Show activation notice
        if (get_transient('sp_professional_profile_activation_notice')) {
            delete_transient('sp_professional_profile_activation_notice');
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>✅ SP Professional Profile Complete v<?php echo $this->version; ?></strong> has been activated successfully!
                    <?php if (function_exists('bp_is_active')): ?>
                        <a href="<?php echo bp_loggedin_user_domain() . 'professional-profile/'; ?>">View your profile</a>
                    <?php endif; ?>
                </p>
                <p style="font-size: 12px; color: #666;">
                    <strong>New in v2.2:</strong> ✅ Romanian diacritics fix | ✅ CV array display fix | ✅ Repeatable fields support
                </p>
            </div>
            <?php
            return;
        }
        
        // Show upgrade notice
        $upgrade_info = get_transient('sp_professional_profile_upgraded_notice');
        if ($upgrade_info) {
            delete_transient('sp_professional_profile_upgraded_notice');
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong>🔄 SP Professional Profile Complete</strong> has been upgraded from v<?php echo esc_html($upgrade_info['from']); ?> to v<?php echo esc_html($upgrade_info['to']); ?>.
                </p>
                <p style="font-size: 12px;">
                    <strong>Fixes:</strong> Romanian diacritics (ă,â,î,ș,ț) | CV data display | Repeatable fields
                </p>
            </div>
            <?php
            return;
        }
        
        // Show plugin status only on plugins page
        if (!$screen || $screen->id !== 'plugins') return;
        
        // Check if BuddyBoss is active
        if (!function_exists('bp_is_active')) {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong>⚠️ SP Professional Profile Complete</strong> requires BuddyBoss/BuddyPress to be active!
                </p>
            </div>
            <?php
            return;
        }
        
        // Check if required files exist
        $required_files = array(
            'includes/helpers.php',
            'includes/data-loader.php',
            'includes/cv-generator.php',
            'templates/profile-main.php',
            'assets/css/profile-style.css',
            'assets/js/profile-script.js'
        );
        
        $missing_files = array();
        foreach ($required_files as $file) {
            if (!file_exists($this->plugin_path . $file)) {
                $missing_files[] = $file;
            }
        }
        
        if (!empty($missing_files)) {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong>⚠️ SP Professional Profile Complete</strong> - Missing required files:
                </p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <?php foreach ($missing_files as $file): ?>
                        <li><code><?php echo esc_html($file); ?></code></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php
            return;
        }
    }
    
    /**
     * Add admin bar link
     */
    public function admin_bar_link($wp_admin_bar) {
        if (!is_user_logged_in() || !function_exists('bp_loggedin_user_domain')) return;
        
        $wp_admin_bar->add_node(array(
            'id' => 'sp-professional-profile',
            'title' => '👤 Profil Profesional',
            'href' => bp_loggedin_user_domain() . 'professional-profile/',
            'parent' => 'my-account-buddypress',
            'meta' => array(
                'title' => 'View your professional profile (v' . $this->version . ')'
            )
        ));
    }
    
    /**
     * Get plugin info
     */
    public function get_plugin_info() {
        return array(
            'version' => $this->version,
            'db_version' => $this->db_version,
            'plugin_path' => $this->plugin_path,
            'plugin_url' => $this->plugin_url,
            'activated' => get_option('sp_professional_profile_activated'),
            'last_upgrade' => get_option('sp_professional_profile_last_upgrade'),
            'buddyboss_active' => function_exists('bp_is_active'),
            'charset' => 'utf8mb4',
            'fixes' => array(
                'diacritics' => true,
                'cv_array' => true,
                'repeatable_fields' => true
            )
        );
    }
}

// Initialize the plugin
$GLOBALS['sp_professional_profile'] = new SP_Professional_Profile_Complete();

/**
 * Get plugin instance
 */
function sp_professional_profile() {
    return $GLOBALS['sp_professional_profile'];
}

/**
 * Get plugin version
 */
function sp_professional_profile_version() {
    return sp_professional_profile()->get_plugin_info()['version'];
}