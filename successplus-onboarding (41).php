<?php
/**
 * Plugin Name: SuccessPlus Onboarding
 * Description: Test vocațional complet și constructor CV dinamic cu analiză AI
 * Version: 4.9.5
 * Author: Dragos
 * Text Domain: successplus-onboarding
 */

if (!defined('ABSPATH')) exit;

class SuccessPlus_Onboarding {
    
    public $version = '4.9.5';
    public $plugin_dir;
    public $plugin_url;
    public $table_sessions;
    public $table_questions;
    public $table_cv_fields;
    public $table_profile_fields;
    public $intelligence_category_count;
    
    public $intelligence_types = array(
        1 => array('name' => 'Inteligența verbală/lingvistică', 'specializations' => 'jurnaliști, poeți, avocați, profesori, scriitori'),
        2 => array('name' => 'Inteligența logico-matematică', 'specializations' => 'matematicieni, contabili, oameni de știință, programatori'),
        3 => array('name' => 'Inteligența vizuală/spațială', 'specializations' => 'pictori, arhitecți, fotografi, artiști, piloți, ingineri'),
        4 => array('name' => 'Inteligența corporală/kinestezică', 'specializations' => 'atleți, dansatori, chirurgi, sculptori, meștesugari'),
        5 => array('name' => 'Inteligența muzicală/ritmică', 'specializations' => 'muzicieni, cântăreți, compozitori, poeți'),
        6 => array('name' => 'Inteligența socială/interpersonală', 'specializations' => 'profesori, directori, politicieni, consilieri, lideri'),
        7 => array('name' => 'Inteligența intrapersonală', 'specializations' => 'teologi, întreprinzători, psihologi'),
        8 => array('name' => 'Inteligența naturalistă', 'specializations' => 'astronomi, biologi, ecologi, fermieri, veterinari')
    );
    
    public function __construct() {
        global $wpdb;
        
        $this->plugin_dir = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);
        
        $this->table_sessions = $wpdb->prefix . 'sp_onboarding_sessions';
        $this->table_questions = $wpdb->prefix . 'sp_onboarding_questions';
        $this->table_cv_fields = $wpdb->prefix . 'sp_onboarding_cv_fields';
        $this->table_profile_fields = $wpdb->prefix . 'sp_onboarding_profile_fields';
        $this->intelligence_category_count = count($this->intelligence_types);
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        add_action('plugins_loaded', array($this, 'check_version_update'));
        
        $this->load_dependencies();
        
        new SP_Manager($this);
        new SP_Ajax_Handler($this);
        new SP_Popup_Manager($this);
        
        do_action('sp_onboarding_loaded', $this);
    }
    
    private function load_dependencies() {
        require_once $this->plugin_dir . 'includes/class-sp-manager.php';
        require_once $this->plugin_dir . 'includes/class-sp-ajax-handler.php';
        require_once $this->plugin_dir . 'includes/popup/class-sp-popup-manager.php';
    }
    
    /**
     * Check for version updates and run migrations
     */
    public function check_version_update() {
        $installed_version = get_option('sp_onboarding_version', '0');
        
        if (version_compare($installed_version, $this->version, '<')) {
            $this->run_migrations($installed_version);
            update_option('sp_onboarding_version', $this->version);
        }
    }
    
    /**
     * Run database migrations for version updates
     */
    private function run_migrations($from_version) {
        global $wpdb;
        
        // Migration to 4.0.0 - Add new CV fields columns
        if (version_compare($from_version, '4.0.0', '<')) {
            $table = $this->table_cv_fields;
            
            $columns = $wpdb->get_col("DESCRIBE {$table}", 0);
            
            if (!in_array('field_description', $columns)) {
                $wpdb->query("ALTER TABLE {$table} ADD COLUMN field_description TEXT NULL AFTER field_options");
            }
            
            if (!in_array('field_placeholder', $columns)) {
                $wpdb->query("ALTER TABLE {$table} ADD COLUMN field_placeholder VARCHAR(255) NULL AFTER field_description");
            }
            
            if (!in_array('section_type', $columns)) {
                $wpdb->query("ALTER TABLE {$table} ADD COLUMN section_type VARCHAR(50) DEFAULT 'single' AFTER field_placeholder");
            }
            
            if (!in_array('parent_id', $columns)) {
                $wpdb->query("ALTER TABLE {$table} ADD COLUMN parent_id BIGINT(20) DEFAULT 0 AFTER section_type");
                $wpdb->query("ALTER TABLE {$table} ADD INDEX parent_id (parent_id)");
            }
            
            if (!in_array('add_button_text', $columns)) {
                $wpdb->query("ALTER TABLE {$table} ADD COLUMN add_button_text VARCHAR(255) NULL AFTER parent_id");
            }
        }
        
        // Migration to 4.3.0 - Add field_description and field_placeholder to profile_fields
        if (version_compare($from_version, '4.3.0', '<')) {
            $table = $this->table_profile_fields;
            
            $columns = $wpdb->get_col("DESCRIBE {$table}", 0);
            
            if (!in_array('field_description', $columns)) {
                $wpdb->query("ALTER TABLE {$table} ADD COLUMN field_description TEXT NULL AFTER field_options");
            }
            
            if (!in_array('field_placeholder', $columns)) {
                $wpdb->query("ALTER TABLE {$table} ADD COLUMN field_placeholder VARCHAR(255) NULL AFTER field_description");
            }
            
            // Update existing profile fields if needed
            $this->update_default_profile_fields();
        }
    }
    
    /**
     * Update default profile fields with descriptions and placeholders
     */
    private function update_default_profile_fields() {
        global $wpdb;
        
        // Update birth_date field if it exists
        $birth_date_field = $wpdb->get_row("SELECT * FROM {$this->table_profile_fields} WHERE field_name = 'birth_date' OR field_name = 'birthdate'");
        if ($birth_date_field) {
            $wpdb->update(
                $this->table_profile_fields,
                array(
                    'field_type' => 'birthdate',
                    'field_description' => 'Necesară pentru testele PsihoProfile',
                    'field_placeholder' => ''
                ),
                array('id' => $birth_date_field->id)
            );
        }
        
        // Update sex field if it exists
        $sex_field = $wpdb->get_row("SELECT * FROM {$this->table_profile_fields} WHERE field_name = 'sex'");
        if ($sex_field) {
            $wpdb->update(
                $this->table_profile_fields,
                array(
                    'field_type' => 'sex',
                    'field_description' => 'Necesar pentru testele PsihoProfile',
                    'field_placeholder' => ''
                ),
                array('id' => $sex_field->id)
            );
        }
    }
    
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Sessions table
        $sql_sessions = "CREATE TABLE {$this->table_sessions} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_key varchar(64) NOT NULL,
            current_step varchar(50) DEFAULT 'test',
            test_data longtext,
            cv_data longtext,
            cv_negations longtext,
            ai_analysis longtext,
            intelligence_scores longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime NULL,
            user_id bigint(20) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY session_key (session_key),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql_sessions);
        
        // Questions table
        $sql_questions = "CREATE TABLE {$this->table_questions} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            question_text text NOT NULL,
            intelligence_category int(11) DEFAULT 0,
            sort_order int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_questions);
        
        // CV Fields table
        $sql_cv_fields = "CREATE TABLE {$this->table_cv_fields} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            field_name varchar(100) NOT NULL,
            field_label varchar(255) NOT NULL,
            field_type varchar(50) DEFAULT 'text',
            field_options longtext,
            field_description TEXT NULL,
            field_placeholder VARCHAR(255) NULL,
            section_type VARCHAR(50) DEFAULT 'single',
            parent_id BIGINT(20) DEFAULT 0,
            add_button_text VARCHAR(255) NULL,
            negation_button_text varchar(100) DEFAULT NULL,
            is_required tinyint(1) DEFAULT 0,
            sort_order int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY parent_id (parent_id)
        ) $charset_collate;";
        dbDelta($sql_cv_fields);
        
        // Profile Fields table
        $sql_profile_fields = "CREATE TABLE {$this->table_profile_fields} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            field_name varchar(100) NOT NULL,
            field_label varchar(255) NOT NULL,
            field_type varchar(50) DEFAULT 'text',
            field_options longtext,
            field_description TEXT NULL,
            field_placeholder VARCHAR(255) NULL,
            is_required tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_profile_fields);
        
        $this->load_intelligence_test(false);
        $this->add_default_cv_structure();
        $this->add_complete_profile_fields();
        
        update_option('sp_onboarding_version', $this->version);
    }
    
    /**
     * Add default CV structure
     */
    private function add_default_cv_structure() {
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_cv_fields} WHERE parent_id = 0");
        if ($count > 0) return;
        
        $sort = 0;
        
        // 1. Date personale (single fields)
        $wpdb->insert($this->table_cv_fields, array(
            'field_name' => 'telefon',
            'field_label' => 'Telefon',
            'field_type' => 'tel',
            'field_description' => 'Număr la care poți fi contactat ușor',
            'field_placeholder' => 'ex: 0721 234 567',
            'section_type' => 'single',
            'parent_id' => 0,
            'is_required' => 1,
            'sort_order' => ++$sort,
            'is_active' => 1
        ));
        
        $wpdb->insert($this->table_cv_fields, array(
            'field_name' => 'oras',
            'field_label' => 'Oraș',
            'field_type' => 'text',
            'field_description' => 'Orașul în care locuiești',
            'field_placeholder' => 'ex: București',
            'section_type' => 'single',
            'parent_id' => 0,
            'is_required' => 1,
            'sort_order' => ++$sort,
            'is_active' => 1
        ));
        
        // 2. Profil profesional
        $wpdb->insert($this->table_cv_fields, array(
            'field_name' => 'profil_profesional',
            'field_label' => 'Profil profesional / Obiectiv',
            'field_type' => 'textarea',
            'field_description' => 'Un scurt rezumat despre cine ești ca profesionist (3-5 rânduri)',
            'field_placeholder' => 'ex: Specialist în marketing digital cu 3 ani experiență...',
            'section_type' => 'single',
            'parent_id' => 0,
            'negation_button_text' => 'Nu știu încă',
            'is_required' => 0,
            'sort_order' => ++$sort,
            'is_active' => 1
        ));
        
        // 3. Experiență profesională (REPEATABLE)
        $wpdb->insert($this->table_cv_fields, array(
            'field_name' => 'experienta_profesionala',
            'field_label' => 'Experiență profesională',
            'field_type' => 'container',
            'field_description' => 'Adaugă experiențele tale profesionale în ordine cronologică inversă',
            'section_type' => 'repeatable',
            'parent_id' => 0,
            'add_button_text' => 'Adaugă o nouă experiență',
            'negation_button_text' => 'Nu am experiență',
            'is_required' => 0,
            'sort_order' => ++$sort,
            'is_active' => 1
        ));
        $exp_parent_id = $wpdb->insert_id;
        
        // Child fields for Experiență
        $wpdb->insert($this->table_cv_fields, array(
            'field_name' => 'perioada',
            'field_label' => 'Perioada',
            'field_type' => 'text',
            'field_placeholder' => 'ex: 05.2022 – prezent',
            'section_type' => 'single',
            'parent_id' => $exp_parent_id,
            'is_required' => 1,
            'sort_order' => 1,
            'is_active' => 1
        ));
        
        $wpdb->insert($this->table_cv_fields, array(
            'field_name' => 'pozitia',
            'field_label' => 'Funcția/Poziția',
            'field_type' => 'text',
            'field_placeholder' => 'ex: Manager vânzări',
            'section_type' => 'single',
            'parent_id' => $exp_parent_id,
            'is_required' => 1,
            'sort_order' => 2,
            'is_active' => 1
        ));
        
        $wpdb->insert($this->table_cv_fields, array(
            'field_name' => 'compania',
            'field_label' => 'Compania/Organizația',
            'field_type' => 'text',
            'field_placeholder' => 'ex: SC Example SRL, București',
            'section_type' => 'single',
            'parent_id' => $exp_parent_id,
            'is_required' => 1,
            'sort_order' => 3,
            'is_active' => 1
        ));
        
        $wpdb->insert($this->table_cv_fields, array(
            'field_name' => 'responsabilitati',
            'field_label' => 'Responsabilități & realizări',
            'field_type' => 'textarea',
            'field_placeholder' => 'Scrie 4-6 bullet points cu responsabilități și realizări măsurabile',
            'section_type' => 'single',
            'parent_id' => $exp_parent_id,
            'is_required' => 1,
            'sort_order' => 4,
            'is_active' => 1
        ));
        
        // 4. Educație (REPEATABLE)
        $wpdb->insert($this->table_cv_fields, array(
            'field_name' => 'educatie',
            'field_label' => 'Educație și formare',
            'field_type' => 'container',
            'field_description' => 'Adaugă informații despre educația ta',
            'section_type' => 'repeatable',
            'parent_id' => 0,
            'add_button_text' => 'Adaugă educație',
            'negation_button_text' => NULL,
            'is_required' => 1,
            'sort_order' => ++$sort,
            'is_active' => 1
        ));
        $edu_parent_id = $wpdb->insert_id;
        
        $wpdb->insert($this->table_cv_fields, array(
            'field_name' => 'institutia',
            'field_label' => 'Instituția',
            'field_type' => 'text',
            'field_placeholder' => 'ex: Universitatea București',
            'section_type' => 'single',
            'parent_id' => $edu_parent_id,
            'is_required' => 1,
            'sort_order' => 1,
            'is_active' => 1
        ));
        
        $wpdb->insert($this->table_cv_fields, array(
            'field_name' => 'perioada_edu',
            'field_label' => 'Perioada',
            'field_type' => 'text',
            'field_placeholder' => 'ex: 2018 – 2021',
            'section_type' => 'single',
            'parent_id' => $edu_parent_id,
            'is_required' => 1,
            'sort_order' => 2,
            'is_active' => 1
        ));
        
        $wpdb->insert($this->table_cv_fields, array(
            'field_name' => 'specializare',
            'field_label' => 'Specializare / Diplomă',
            'field_type' => 'text',
            'field_placeholder' => 'ex: Licență în Informatică',
            'section_type' => 'single',
            'parent_id' => $edu_parent_id,
            'is_required' => 1,
            'sort_order' => 3,
            'is_active' => 1
        ));
        
        // 5. Competențe
        $wpdb->insert($this->table_cv_fields, array(
            'field_name' => 'competente',
            'field_label' => 'Competențe / Abilități',
            'field_type' => 'textarea',
            'field_description' => 'Lista de competențe tehnice și soft skills',
            'field_placeholder' => 'ex: MS Office, Photoshop, Comunicare, Leadership',
            'section_type' => 'single',
            'parent_id' => 0,
            'is_required' => 1,
            'sort_order' => ++$sort,
            'is_active' => 1
        ));
        
        // 6. Limbi străine (REPEATABLE)
        $wpdb->insert($this->table_cv_fields, array(
            'field_name' => 'limbi_straine',
            'field_label' => 'Limbi străine',
            'field_type' => 'container',
            'section_type' => 'repeatable',
            'parent_id' => 0,
            'add_button_text' => 'Adaugă limbă',
            'negation_button_text' => 'Nu vorbesc limbi străine',
            'is_required' => 0,
            'sort_order' => ++$sort,
            'is_active' => 1
        ));
        $limbi_parent_id = $wpdb->insert_id;
        
        $wpdb->insert($this->table_cv_fields, array(
            'field_name' => 'limba',
            'field_label' => 'Limba',
            'field_type' => 'text',
            'field_placeholder' => 'ex: Engleză',
            'section_type' => 'single',
            'parent_id' => $limbi_parent_id,
            'is_required' => 1,
            'sort_order' => 1,
            'is_active' => 1
        ));
        
        $wpdb->insert($this->table_cv_fields, array(
            'field_name' => 'nivel',
            'field_label' => 'Nivel',
            'field_type' => 'select',
            'field_options' => json_encode(array('Începător', 'Mediu', 'Avansat', 'Fluent', 'Native')),
            'section_type' => 'single',
            'parent_id' => $limbi_parent_id,
            'is_required' => 1,
            'sort_order' => 2,
            'is_active' => 1
        ));
        
        // 7. Referințe (CHECKBOX)
        $wpdb->insert($this->table_cv_fields, array(
            'field_name' => 'referinte',
            'field_label' => 'Referințe disponibile la cerere',
            'field_type' => 'checkbox',
            'field_description' => 'Bifează dacă ai referințe disponibile',
            'section_type' => 'checkbox',
            'parent_id' => 0,
            'is_required' => 0,
            'sort_order' => ++$sort,
            'is_active' => 1
        ));
    }
    
    /**
     * COMPLETE PROFILE FIELDS
     */
    private function add_complete_profile_fields() {
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_profile_fields}");
        if ($count > 0) return;
        
        $profile_fields = array(
            array(
                'field_name' => 'first_name',
                'field_label' => 'Prenume',
                'field_type' => 'text',
                'field_options' => '',
                'field_description' => '',
                'field_placeholder' => 'ex: Ion',
                'is_required' => 1,
                'sort_order' => 1
            ),
            array(
                'field_name' => 'last_name',
                'field_label' => 'Nume',
                'field_type' => 'text',
                'field_options' => '',
                'field_description' => '',
                'field_placeholder' => 'ex: Popescu',
                'is_required' => 1,
                'sort_order' => 2
            ),
            array(
                'field_name' => 'birth_date',
                'field_label' => 'Data nașterii',
                'field_type' => 'birthdate',
                'field_options' => '',
                'field_description' => 'Necesară pentru testele PsihoProfile',
                'field_placeholder' => '',
                'is_required' => 1,
                'sort_order' => 3
            ),
            array(
                'field_name' => 'sex',
                'field_label' => 'Sex',
                'field_type' => 'sex',
                'field_options' => '',
                'field_description' => 'Necesar pentru testele PsihoProfile',
                'field_placeholder' => '',
                'is_required' => 1,
                'sort_order' => 4
            ),
            array(
                'field_name' => 'phone',
                'field_label' => 'Telefon',
                'field_type' => 'tel',
                'field_options' => '',
                'field_description' => '',
                'field_placeholder' => 'ex: 0721 234 567',
                'is_required' => 1,
                'sort_order' => 5
            ),
            array(
                'field_name' => 'email',
                'field_label' => 'Email',
                'field_type' => 'email',
                'field_options' => '',
                'field_description' => '',
                'field_placeholder' => 'ex: nume@email.ro',
                'is_required' => 1,
                'sort_order' => 6
            ),
            array(
                'field_name' => 'password',
                'field_label' => 'Parolă',
                'field_type' => 'password',
                'field_options' => '',
                'field_description' => 'Minimum 8 caractere',
                'field_placeholder' => '',
                'is_required' => 1,
                'sort_order' => 7
            ),
            array(
                'field_name' => 'password_confirm',
                'field_label' => 'Confirmă Parola',
                'field_type' => 'password',
                'field_options' => '',
                'field_description' => '',
                'field_placeholder' => '',
                'is_required' => 1,
                'sort_order' => 8
            )
        );
        
        foreach ($profile_fields as $f) {
            $wpdb->insert($this->table_profile_fields, array(
                'field_name' => $f['field_name'],
                'field_label' => $f['field_label'],
                'field_type' => $f['field_type'],
                'field_options' => $f['field_options'],
                'field_description' => $f['field_description'],
                'field_placeholder' => $f['field_placeholder'],
                'is_required' => $f['is_required'],
                'sort_order' => $f['sort_order'],
                'is_active' => 1
            ));
        }
    }
    
    public function load_intelligence_test($force = false) {
        global $wpdb;
        
        if (!$force) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_questions}");
            if ($count > 0) return;
        }
        
        if ($force) {
            $wpdb->query("DELETE FROM {$this->table_questions}");
        }
        
        $questions_file = $this->plugin_dir . 'intelligence-test-data.php';
        if (!file_exists($questions_file)) return;
        
        $questions_data = include($questions_file);
        if (!is_array($questions_data) || empty($questions_data)) return;
        
        foreach ($questions_data as $question) {
            $wpdb->insert($this->table_questions, array(
                'question_text' => $question[0],
                'intelligence_category' => $question[1],
                'sort_order' => $question[2],
                'is_active' => 1
            ));
        }
    }
    
    public function get_user_intelligence_data($user_id) {
        return array(
            'scores' => get_user_meta($user_id, 'sp_intelligence_scores', true),
            'dominant_type' => get_user_meta($user_id, 'sp_intelligence_type', true),
            'intelligence_types' => $this->intelligence_types
        );
    }
    
    public function get_user_cv_data($user_id) {
        return array(
            'cv_data' => get_user_meta($user_id, 'sp_cv_data', true),
            'negations' => get_user_meta($user_id, 'sp_cv_negations', true),
            'ai_analysis' => get_user_meta($user_id, 'sp_ai_analysis', true)
        );
    }
    
    public function get_cv_fields($parent_id = 0) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_cv_fields} WHERE is_active = 1 AND parent_id = %d ORDER BY sort_order ASC",
            $parent_id
        ));
    }
}

$sp_onboarding = new SuccessPlus_Onboarding();

function sp_onboarding() {
    global $sp_onboarding;
    return $sp_onboarding;
}