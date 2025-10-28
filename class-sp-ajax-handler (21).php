<?php
/**
 * SP_Ajax_Handler Class - FIXED: Full AI Interpretation Display
 * Version: 4.4.7+
 * 
 * FIXES:
 * 1. Enhanced admin session view with ALL AI fields
 * 2. Better CV negations display
 * 3. Ensured all AI data is saved and displayed
 */

if (!defined('ABSPATH')) exit;

class SP_Ajax_Handler {
    
    private $plugin;
    
    public function __construct($plugin) {
        $this->plugin = $plugin;
        
        // Frontend AJAX hooks
        add_action('wp_ajax_sp_complete_registration', array($this, 'ajax_complete_registration'));
        add_action('wp_ajax_nopriv_sp_complete_registration', array($this, 'ajax_complete_registration'));
        add_action('wp_ajax_sp_save_test_answers', array($this, 'ajax_save_test_answers'));
        add_action('wp_ajax_nopriv_sp_save_test_answers', array($this, 'ajax_save_test_answers'));
        add_action('wp_ajax_sp_save_cv_data', array($this, 'ajax_save_cv_data'));
        add_action('wp_ajax_nopriv_sp_save_cv_data', array($this, 'ajax_save_cv_data'));
        
        // Skip test with progress save
        add_action('wp_ajax_sp_skip_test', array($this, 'ajax_skip_test'));
        add_action('wp_ajax_nopriv_sp_skip_test', array($this, 'ajax_skip_test'));
        
        // Skip CV
        add_action('wp_ajax_sp_skip_cv', array($this, 'ajax_skip_cv'));
        add_action('wp_ajax_nopriv_sp_skip_cv', array($this, 'ajax_skip_cv'));
        
        // Admin AJAX hooks
        add_action('wp_ajax_sp_reload_test_questions', array($this, 'ajax_reload_test_questions'));
        add_action('wp_ajax_sp_delete_session', array($this, 'ajax_delete_session'));
        add_action('wp_ajax_sp_view_session', array($this, 'ajax_view_session'));
        add_action('wp_ajax_sp_cleanup_incomplete_sessions', array($this, 'ajax_cleanup_incomplete_sessions'));
    }
    
    /**
     * Handle skip test with PROGRESS SAVING
     */
    public function ajax_skip_test() {
        check_ajax_referer('sp_onboarding_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Trebuie să fiți autentificat.'));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Check if already completed
        $test_status = get_user_meta($user_id, 'sp_test_completed_status', true);
        if ($test_status === 'completed') {
            wp_send_json_error(array('message' => 'Ați finalizat deja testul.'));
            return;
        }
        
        $current_question = isset($_POST['current_question']) ? intval($_POST['current_question']) : 0;
        $answers = isset($_POST['answers']) ? $_POST['answers'] : array();
        
        $answered_count = count($answers);
        
        // Save progress if there are answers
        if ($answered_count > 0) {
            $progress_data = array(
                'current_question' => $current_question,
                'answers' => $answers,
                'saved_at' => current_time('mysql')
            );
            
            update_user_meta($user_id, 'sp_test_progress', $progress_data);
            update_user_meta($user_id, 'sp_test_completed_status', 'in_progress');
            
            $message = sprintf('✓ Progres salvat! %d răspunsuri salvate. Vei putea continua de unde ai rămas.', $answered_count);
        } else {
            update_user_meta($user_id, 'sp_test_completed_status', 'skipped');
            $message = 'Testul a fost omis. Vă puteți întoarce oricând.';
        }
        
        // Clear reminder transient
        $debug_mode = get_option('sp_onboarding_popup_debug_mode', 0);
        if (!$debug_mode) {
            delete_transient('sp_reminder_popup_shown_' . $user_id);
        }
        
        $redirect_url = get_option('sp_onboarding_skip_redirect_url');
        if (empty($redirect_url)) {
            $redirect_url = home_url();
        }
        
        do_action('sp_test_skipped', $user_id, $answered_count);
        
        wp_send_json_success(array(
            'message' => $message,
            'redirect_url' => $redirect_url,
            'answered_count' => $answered_count
        ));
    }
    
    /**
     * Generate unique username from first name and last name
     */
    private function generate_unique_username($first_name, $last_name) {
        $last = sanitize_user(strtolower(remove_accents($last_name)), true);
        $first = sanitize_user(strtolower(remove_accents($first_name)), true);
        
        $last = preg_replace('/[^a-z0-9.]/', '', $last);
        $first = preg_replace('/[^a-z0-9.]/', '', $first);
        
        $username = $last . '.' . $first;
        
        if (!username_exists($username)) {
            return $username;
        }
        
        $max_attempts = 100;
        $attempt = 0;
        
        while ($attempt < $max_attempts) {
            $random = rand(100, 999);
            $new_username = $username . $random;
            
            if (!username_exists($new_username)) {
                return $new_username;
            }
            
            $attempt++;
        }
        
        return $username . time();
    }
    
    /**
     * Registration handler with MailerLite and PsihoProfile integration
     */
    public function ajax_complete_registration() {
        check_ajax_referer('sp_onboarding_nonce', 'nonce');
        
        $session_key = sanitize_text_field($_POST['session_key']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        
        // PsihoProfile fields
        $birth_date = isset($_POST['birth_date']) ? sanitize_text_field($_POST['birth_date']) : '';
        $sex = isset($_POST['sex']) ? sanitize_text_field($_POST['sex']) : '';
        
        // Validation
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Adresă email invalidă'));
            return;
        }
        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'Acest email este deja înregistrat'));
            return;
        }
        
        // Generate unique username
        $username = $this->generate_unique_username($first_name, $last_name);
        
        // Create user
        $user_id = wp_create_user($username, $password, $email);
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
            return;
        }
        
        // Update user basic info
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name
        ));
        
        // Save phone number
        if (!empty($phone)) {
            update_user_meta($user_id, 'phone_number', $phone);
            update_user_meta($user_id, 'billing_phone', $phone);
        }
        
        // Save birthdate for PsihoProfile
        if (!empty($birth_date)) {
            update_user_meta($user_id, 'psihoprofile_birthdate', $birth_date);
            update_user_meta($user_id, 'sp_birthdate', $birth_date);
            error_log('SP Onboarding: Saved birthdate for user ' . $user_id . ': ' . $birth_date);
        }
        
        // Save sex for PsihoProfile
        if (!empty($sex) && in_array($sex, array('M', 'F'))) {
            update_user_meta($user_id, 'psihoprofile_sex', $sex);
            update_user_meta($user_id, 'sp_sex', $sex);
            error_log('SP Onboarding: Saved sex for user ' . $user_id . ': ' . $sex);
        }
        
        // Set user role
        $user = new WP_User($user_id);
        $roles = get_option('sp_onboarding_user_roles', array('subscriber'));
        if (!is_array($roles)) $roles = array($roles);
        if (!empty($roles)) {
            $user->set_role($roles[0]);
            for ($i = 1; $i < count($roles); $i++) {
                $user->add_role($roles[$i]);
            }
        }
        
        // Update session
        global $wpdb;
        $existing_session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->plugin->table_sessions} WHERE session_key = %s",
            $session_key
        ));
        
        if ($existing_session) {
            $wpdb->update(
                $this->plugin->table_sessions,
                array('user_id' => $user_id, 'current_step' => 'test'),
                array('session_key' => $session_key)
            );
        } else {
            $wpdb->insert($this->plugin->table_sessions, array(
                'session_key' => $session_key,
                'user_id' => $user_id,
                'current_step' => 'test',
                'created_at' => current_time('mysql')
            ));
        }
        
        // Log in the user
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        
        do_action('sp_user_registered', $user_id, $session_key);
        
        // Add to MailerLite
        $this->add_user_to_mailerlite($user_id);
        
        wp_send_json_success(array(
            'message' => 'Înregistrare finalizată!',
            'user_id' => $user_id,
            'continue_to_test' => true
        ));
    }
    
    private function add_user_to_mailerlite($user_id) {
        $api_key = get_option('sp_onboarding_mailerlite_api_key');
        $group_id = get_option('sp_onboarding_mailerlite_group_id');
        
        if (empty($api_key) || empty($group_id)) {
            return;
        }
        
        $user_data = get_userdata($user_id);
        if (!$user_data) return;
        
        $email = $user_data->user_email;
        $name = trim($user_data->first_name . ' ' . $user_data->last_name);
        if (empty($name)) $name = $user_data->display_name;
        
        $api_url = 'https://api.mailerlite.com/api/v2/groups/' . $group_id . '/subscribers';
        
        $response = wp_remote_post($api_url, array(
            'method' => 'POST',
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-MailerLite-ApiKey' => $api_key
            ),
            'body' => json_encode(array(
                'email' => $email,
                'name' => $name
            ))
        ));
        
        if (!is_wp_error($response)) {
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code === 200 || $response_code === 201) {
                error_log('SP Onboarding MailerLite: Successfully added ' . $email);
            }
        }
    }
    
    /**
     * Save test answers with FULL AI ANALYSIS
     */
    public function ajax_save_test_answers() {
        check_ajax_referer('sp_onboarding_nonce', 'nonce');
        
        $session_key = sanitize_text_field($_POST['session_key']);
        $answers = $_POST['answers'];
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Trebuie să fiți autentificat pentru a trimite testul.'));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Check if already completed
        $test_status = get_user_meta($user_id, 'sp_test_completed_status', true);
        if ($test_status === 'completed') {
            wp_send_json_error(array('message' => 'Ați finalizat deja testul vocațional.'));
            return;
        }
        
        global $wpdb;
        
        // Calculate scores
        $scores = array_fill(1, $this->plugin->intelligence_category_count, 0);
        foreach ($answers as $question_id => $answer) {
            if ($answer === 'da') {
                $question = $wpdb->get_row($wpdb->prepare(
                    "SELECT intelligence_category FROM {$this->plugin->table_questions} WHERE id = %d", 
                    $question_id
                ));
                if ($question && $question->intelligence_category >= 1 && $question->intelligence_category <= $this->plugin->intelligence_category_count) {
                    $scores[$question->intelligence_category]++;
                }
            }
        }
        
        arsort($scores);
        $dominant_type = key($scores);
        
        // Generate AI analysis with ALL fields
        $ai_analysis_fallback = false;
        $ai_analysis = $this->generate_ai_analysis_fast($session_key, $scores, $dominant_type, $answers, $ai_analysis_fallback);
        
        // CRITICAL: Save with JSON_UNESCAPED_UNICODE to preserve Romanian diacritics
        $ai_analysis_json = is_array($ai_analysis) ? json_encode($ai_analysis, JSON_UNESCAPED_UNICODE) : $ai_analysis;
        
        // Save to sessions table
        $wpdb->update(
            $this->plugin->table_sessions,
            array(
                'test_data' => json_encode($answers, JSON_UNESCAPED_UNICODE),
                'intelligence_scores' => json_encode($scores, JSON_UNESCAPED_UNICODE),
                'ai_analysis' => $ai_analysis_json,
                'current_step' => 'results'
            ),
            array('session_key' => $session_key)
        );
        
        // Save to user meta
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id FROM {$this->plugin->table_sessions} WHERE session_key = %s", 
            $session_key
        ));
        
        if ($session && $session->user_id) {
            update_user_meta($session->user_id, 'sp_intelligence_scores', $scores);
            update_user_meta($session->user_id, 'sp_intelligence_type', $dominant_type);
            
            // Accumulate soft skills
            if (is_array($ai_analysis) && isset($ai_analysis['soft_skills'])) {
                $accumulated_skills = $this->accumulate_soft_skills($session->user_id, $ai_analysis['soft_skills']);
                $ai_analysis['soft_skills'] = $accumulated_skills;
                $ai_analysis_json = json_encode($ai_analysis, JSON_UNESCAPED_UNICODE);
            }
            
            $test_count = intval(get_user_meta($session->user_id, 'sp_test_count', true));
            update_user_meta($session->user_id, 'sp_test_count', $test_count + 1);
            
            // Mark as completed and DELETE progress
            update_user_meta($session->user_id, 'sp_test_completed_status', 'completed');
            delete_user_meta($session->user_id, 'sp_test_progress');
            
            // Save FULL AI analysis with ALL fields
            update_user_meta($session->user_id, 'sp_ai_analysis', $ai_analysis_json);
            
            // Log for debugging
            error_log('SP Onboarding: Saved FULL AI analysis for user ' . $session->user_id);
            error_log('AI Analysis fields: ' . implode(', ', array_keys($ai_analysis)));
        }
        
        do_action('sp_test_completed', $session_key, $scores, $dominant_type, $ai_analysis);
        
        // Format results with ALL AI fields
        $results_html = $this->format_intelligence_results($scores, $dominant_type, $ai_analysis, $session && $session->user_id ? $session->user_id : null);
        
        wp_send_json_success(array(
            'message' => 'Test salvat cu succes',
            'scores' => $scores,
            'dominant_type' => $dominant_type,
            'ai_analysis' => $ai_analysis,
            'ai_analysis_fallback' => $ai_analysis_fallback,
            'results_html' => $results_html
        ));
    }
    
    /**
     * Generate AI analysis - COMPLETE with all fields
     */
    private function generate_ai_analysis_fast($session_key, $scores, $dominant_type, $answers, &$ai_analysis_fallback = false) {
        $api_key = get_option('sp_onboarding_openai_key');
        
        if (empty($api_key)) {
            $ai_analysis_fallback = true;
            return $this->generate_basic_analysis($scores, $dominant_type);
        }
        
        $model = get_option('sp_onboarding_openai_model', 'gpt-3.5-turbo');
        
        arsort($scores);
        $top3 = array_slice($scores, 0, 3, true);
        $score_text = "";
        foreach ($top3 as $type => $score) {
            $percentage = intval($score * (100 / $this->plugin->intelligence_category_count));
            $score_text .= $this->plugin->intelligence_types[$type]['name'] . ": " . $percentage . "% | ";
        }
        
        $prompt = "Analiză rapidă profil vocațional:\n\n";
        $prompt .= "Top 3: " . rtrim($score_text, " | ") . "\n";
        $prompt .= "Dominant: " . $this->plugin->intelligence_types[$dominant_type]['name'] . "\n\n";
        $prompt .= "JSON (română, detaliat, concis):\n";
        $prompt .= "profile_description: 2 paragrafe despre profil, puncte forte, stil lucru (max 350 cuvinte)\n";
        $prompt .= "soft_skills: array de 8-10 obiecte cu format [{\"skill\": \"nume competență\", \"points\": număr între 3-8, \"color\": \"o culoare hexazecimală vibrantă dar plăcută (ex: #87CEEB)\"}]\n";
        $prompt .= "specialization_recommendations: 2 paragrafe despre cariere potrivite (max 200 cuvinte)\n";
        $prompt .= "learning_style: 1 paragraf scurt stil învățare (max 100 cuvinte)\n";
        $prompt .= "work_environment: 1 paragraf scurt mediu ideal (max 100 cuvinte)\n\n";
        $prompt .= "Răspuns JSON pur, fără markdown.";
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => $model,
                'messages' => array(
                    array('role' => 'system', 'content' => 'Expert psiholog vocațional. Răspunzi doar JSON valid, detaliat, concis, în română.'),
                    array('role' => 'user', 'content' => $prompt)
                ),
                'temperature' => 0.8,
                'max_tokens' => 900
            ))
        ));
        
        if (is_wp_error($response)) {
            $ai_analysis_fallback = true;
            return $this->generate_basic_analysis($scores, $dominant_type);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['choices'][0]['message']['content'])) {
            $ai_analysis_fallback = true;
            return $this->generate_basic_analysis($scores, $dominant_type);
        }
        
        $ai_content = trim($data['choices'][0]['message']['content']);
        $ai_content = preg_replace('/```json\s*/', '', $ai_content);
        $ai_content = preg_replace('/```\s*$/', '', $ai_content);
        
        $analysis = json_decode($ai_content, true);
        
        if (!is_array($analysis)) {
            $ai_analysis_fallback = true;
            return $this->generate_basic_analysis($scores, $dominant_type);
        }
        
        // Ensure ALL fields are present
        if (!isset($analysis['learning_style']) || empty($analysis['learning_style'])) {
            $analysis['learning_style'] = 'Înveți cel mai bine printr-o abordare practică și interactivă, combinând teoria cu aplicații concrete.';
        }
        
        if (!isset($analysis['work_environment']) || empty($analysis['work_environment'])) {
            $analysis['work_environment'] = 'Mediul ideal de lucru pentru tine include oportunități de colaborare și provocări care stimulează gândirea ta unică.';
        }
        
        return $analysis;
    }
    
    private function generate_basic_analysis($scores, $dominant_type) {
        $colors = array('#4fc3f7', '#66bb6a', '#ab47bc', '#ff7043', '#26a69a', '#ec407a', '#5c6bc0', '#ffa726', '#8d6e63', '#78909c');
        
        arsort($scores);
        $top_types = array_slice(array_keys($scores), 0, 3, true);
        
        $dom_intel = $this->plugin->intelligence_types[$dominant_type];
        
        $profile_desc = "Profilul tău vocațional este dominat de " . $dom_intel['name'] . ". ";
        $profile_desc .= "Această dominanță sugerează că excelezi în activități care implică " . strtolower($dom_intel['specializations']) . ". ";
        $profile_desc .= "Stilul tău de abordare a provocărilor profesionale este caracterizat de o combinație între gândirea analitică și intuiția creativă.";
        
        $soft_skills = array();
        $skill_index = 0;
        
        if (in_array(1, $top_types)) {
            $soft_skills[] = array('skill' => 'Comunicare eficientă', 'points' => 8, 'color' => $colors[$skill_index++ % count($colors)]);
            $soft_skills[] = array('skill' => 'Scriere creativă', 'points' => 7, 'color' => $colors[$skill_index++ % count($colors)]);
        }
        if (in_array(2, $top_types)) {
            $soft_skills[] = array('skill' => 'Gândire analitică', 'points' => 8, 'color' => $colors[$skill_index++ % count($colors)]);
            $soft_skills[] = array('skill' => 'Rezolvare de probleme', 'points' => 7, 'color' => $colors[$skill_index++ % count($colors)]);
        }
        if (in_array(3, $top_types)) {
            $soft_skills[] = array('skill' => 'Vizualizare', 'points' => 7, 'color' => $colors[$skill_index++ % count($colors)]);
            $soft_skills[] = array('skill' => 'Creativitate vizuală', 'points' => 6, 'color' => $colors[$skill_index++ % count($colors)]);
        }
        if (in_array(6, $top_types)) {
            $soft_skills[] = array('skill' => 'Empatie', 'points' => 8, 'color' => $colors[$skill_index++ % count($colors)]);
            $soft_skills[] = array('skill' => 'Leadership', 'points' => 7, 'color' => $colors[$skill_index++ % count($colors)]);
        }
        
        return array(
            'profile_description' => $profile_desc,
            'soft_skills' => $soft_skills,
            'specialization_recommendations' => "Specializările recomandate pentru tine includ: " . $dom_intel['specializations'] . ". Aceste domenii valorifică punctele tale forte naturale.",
            'learning_style' => 'Înveți cel mai bine printr-o abordare practică și interactivă, combinând teoria cu aplicații concrete.',
            'work_environment' => 'Mediul ideal de lucru pentru tine include oportunități de colaborare și provocări care stimulează gândirea ta unică.'
        );
    }
    
    private function accumulate_soft_skills($user_id, $new_soft_skills) {
        $existing_analysis = get_user_meta($user_id, 'sp_ai_analysis', true);
        
        if (is_string($existing_analysis) && !empty($existing_analysis)) {
            $existing_analysis = json_decode($existing_analysis, true);
        }
        
        $existing_soft_skills = array();
        if (is_array($existing_analysis) && isset($existing_analysis['soft_skills'])) {
            $existing_soft_skills = $existing_analysis['soft_skills'];
        }
        
        $skills_map = array();
        foreach ($existing_soft_skills as $skill) {
            if (isset($skill['skill']) && isset($skill['points'])) {
                $skills_map[$skill['skill']] = array(
                    'points' => intval($skill['points']),
                    'color' => isset($skill['color']) ? $skill['color'] : '#0292B7'
                );
            }
        }
        
        foreach ($new_soft_skills as $new_skill) {
            if (isset($new_skill['skill']) && isset($new_skill['points'])) {
                $skill_name = $new_skill['skill'];
                $new_points = intval($new_skill['points']);
                $new_color = isset($new_skill['color']) ? $new_skill['color'] : '#0292B7';
                
                if (isset($skills_map[$skill_name])) {
                    $skills_map[$skill_name]['points'] += $new_points;
                    $skills_map[$skill_name]['color'] = $new_color;
                } else {
                    $skills_map[$skill_name] = array(
                        'points' => $new_points,
                        'color' => $new_color
                    );
                }
            }
        }
        
        $accumulated_skills = array();
        foreach ($skills_map as $skill_name => $skill_data) {
            $accumulated_skills[] = array(
                'skill' => $skill_name,
                'points' => $skill_data['points'],
                'color' => $skill_data['color']
            );
        }
        
        usort($accumulated_skills, function($a, $b) {
            return $b['points'] - $a['points'];
        });
        
        return $accumulated_skills;
    }
    
    /**
     * Format intelligence results - COMPLETE with ALL AI fields
     */
    private function format_intelligence_results($scores, $dominant_type, $ai_analysis = null, $user_id = null) {
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
                $html .= '<div class="sp-skill-bar"><div class="sp-skill-bar-fill" data-width="' . $bar_width . '"></div></div>';
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
            $html .= '<div class="sp-skill-bar"><div class="sp-skill-bar-fill" data-width="' . $percentage . '"></div></div>';
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
    
    /**
     * Save CV data with proper JSON encoding AND SERVER-SIDE VALIDATION
     * CRITICAL FIX: Validates that required fields are either filled OR negated
     */
    public function ajax_save_cv_data() {
        check_ajax_referer('sp_onboarding_nonce', 'nonce');
        
        $session_key = sanitize_text_field($_POST['session_key']);
        $cv_data = $_POST['cv_data'];
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Trebuie să fiți autentificat.'));
            return;
        }
        
        error_log('=== SP ONBOARDING: SAVING CV DATA WITH VALIDATION ===');
        error_log('Session Key: ' . $session_key);
        error_log('User ID: ' . get_current_user_id());
        
        // Separate negations and clean data
        $cv_negations = array();
        $clean_cv_data = array();
        
        foreach ($cv_data as $key => $value) {
            if (strpos($key, '_negated') !== false) {
                // This is a negation flag - remove both cv_ and _negated
                $field_name = str_replace(array('cv_', '_negated'), '', $key);
                $cv_negations[$field_name] = ($value === '1' || $value === 1 || $value === true);
                error_log("✓ Negation detected: {$field_name} = " . ($cv_negations[$field_name] ? 'YES' : 'NO'));
            } else {
                // Regular data - handle repeatable sections (arrays of objects)
                if (is_array($value)) {
                    // Filter out empty entries
                    $filtered = array_filter($value, function($entry) {
                        if (!is_array($entry)) return !empty($entry);
                        // Entry is valid if it has at least one non-empty field
                        foreach ($entry as $field_value) {
                            if (!empty($field_value)) return true;
                        }
                        return false;
                    });
                    
                    // Reindex array to remove gaps
                    $clean_cv_data[$key] = array_values($filtered);
                } else {
                    $clean_cv_data[$key] = $value;
                }
            }
        }
        
        error_log('Cleaned CV Data: ' . print_r($clean_cv_data, true));
        error_log('CV Negations: ' . print_r($cv_negations, true));
        error_log('Negations received - Keys: ' . implode(', ', array_keys($cv_negations)));
        error_log('Negations received - Values: ' . implode(', ', array_map(function($v) { return $v ? 'true' : 'false'; }, $cv_negations)));
        
        // ====================================================================
        // CRITICAL: SERVER-SIDE VALIDATION
        // ====================================================================
        global $wpdb;
        
        // Get all CV fields from database to check which are required
        $cv_fields = $wpdb->get_results("SELECT * FROM {$this->plugin->table_cv_fields} WHERE is_active = 1 AND parent_id = 0 ORDER BY sort_order ASC");
        
        $validation_errors = array();
        $total_required = 0;
        $completed_or_negated = 0;
        
        error_log('Starting server-side CV validation...');
        
        foreach ($cv_fields as $field) {
            // Skip non-required fields
            if (!$field->is_required) {
                continue;
            }
            
            $total_required++;
            $field_key = 'cv_' . $field->field_name;
            
            // Check for negation with multiple possible keys
            $is_negated = false;
            $possible_negation_keys = array(
                $field->field_name,                    // e.g., "specializare"
                'cv_' . $field->field_name,            // e.g., "cv_specializare"
                str_replace('_', '', $field->field_name) // e.g., "specializare" if field has underscores
            );
            
            foreach ($possible_negation_keys as $neg_key) {
                if (isset($cv_negations[$neg_key]) && $cv_negations[$neg_key] === true) {
                    $is_negated = true;
                    break;
                }
            }
            
            error_log("Validating field: {$field->field_label} (Key: {$field_key}, Negated: " . ($is_negated ? 'YES' : 'NO') . ")");
            
            // Check if field is negated
            if ($is_negated) {
                $completed_or_negated++;
                error_log("  ✓ Field is NEGATED: {$field->field_label}");
                continue;
            }
            
            // Check based on field type
            if ($field->section_type === 'repeatable') {
                // Repeatable section - must have at least one complete entry
                $has_valid_entry = false;
                
                if (isset($clean_cv_data[$field_key]) && is_array($clean_cv_data[$field_key]) && count($clean_cv_data[$field_key]) > 0) {
                    foreach ($clean_cv_data[$field_key] as $entry) {
                        if (is_array($entry)) {
                            // Check if entry has at least one non-empty value
                            foreach ($entry as $value) {
                                if (!empty($value)) {
                                    $has_valid_entry = true;
                                    break 2; // Break both loops
                                }
                            }
                        }
                    }
                }
                
                if ($has_valid_entry) {
                    $completed_or_negated++;
                    error_log("  ✓ Repeatable section has VALID entries: {$field->field_label}");
                } else {
                    $validation_errors[] = $field->field_label;
                    error_log("  ❌ Repeatable section EMPTY: {$field->field_label}");
                }
                
            } else {
                // Single field - check if it has a value
                $has_value = false;
                
                if (isset($clean_cv_data[$field_key])) {
                    $value = $clean_cv_data[$field_key];
                    
                    if (is_array($value)) {
                        // Check if array has non-empty values
                        foreach ($value as $v) {
                            if (!empty($v)) {
                                $has_value = true;
                                break;
                            }
                        }
                    } else {
                        // Simple value
                        $has_value = !empty($value);
                    }
                }
                
                if ($has_value) {
                    $completed_or_negated++;
                    error_log("  ✓ Field has VALUE: {$field->field_label}");
                } else {
                    $validation_errors[] = $field->field_label;
                    error_log("  ❌ Field EMPTY and NOT negated: {$field->field_label}");
                }
            }
        }
        
        error_log("Validation summary: {$completed_or_negated}/{$total_required} required fields completed or negated");
        
        // CRITICAL: If validation failed, block the save
        if (!empty($validation_errors)) {
            // Create a SHORT, user-friendly error message
            $error_count = count($validation_errors);
            
            if ($error_count <= 3) {
                // Show all fields if 3 or fewer
                $error_message = "❌ Următoarele câmpuri trebuie completate sau negate:\n\n";
                foreach ($validation_errors as $err) {
                    $error_message .= "• " . $err . "\n";
                }
            } else {
                // Show only first 3 if more than 3
                $error_message = "❌ " . $error_count . " câmpuri trebuie completate sau negate:\n\n";
                for ($i = 0; $i < min(3, $error_count); $i++) {
                    $error_message .= "• " . $validation_errors[$i] . "\n";
                }
                if ($error_count > 3) {
                    $error_message .= "• ... și încă " . ($error_count - 3) . " câmp(uri)\n";
                }
            }
            
            $error_message .= "\n💡 Completați fiecare câmp SAU apăsați butonul verde 'Nu știu încă' / 'Nu am'.";
            
            error_log('❌ SERVER-SIDE VALIDATION FAILED - BLOCKING SAVE');
            error_log('Validation errors: ' . print_r($validation_errors, true));
            
            wp_send_json_error(array(
                'message' => $error_message,
                'validation_errors' => $validation_errors,
                'error_count' => $error_count
            ));
            return;
        }
        
        // Final check: ensure we have at least some data
        if ($total_required > 0 && $completed_or_negated === 0) {
            error_log('❌ NO REQUIRED FIELDS COMPLETED - BLOCKING');
            wp_send_json_error(array('message' => 'Trebuie să completați cel puțin un câmp obligatoriu sau să folosiți butoanele de negare.'));
            return;
        }
        
        error_log('✅ SERVER-SIDE VALIDATION PASSED');
        // ====================================================================
        // END VALIDATION
        // ====================================================================
        
        // Save to sessions table with JSON_UNESCAPED_UNICODE
        $wpdb->update(
            $this->plugin->table_sessions,
            array(
                'cv_data' => json_encode($clean_cv_data, JSON_UNESCAPED_UNICODE),
                'cv_negations' => json_encode($cv_negations, JSON_UNESCAPED_UNICODE),
                'current_step' => 'completed',
                'completed_at' => current_time('mysql')
            ),
            array('session_key' => $session_key)
        );
        
        // Get session and save to user meta
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->plugin->table_sessions} WHERE session_key = %s",
            $session_key
        ));
        
        if ($session && $session->user_id) {
            update_user_meta($session->user_id, 'sp_cv_data', $clean_cv_data);
            update_user_meta($session->user_id, 'sp_cv_negations', $cv_negations);
            
            // Mark CV as completed
            update_user_meta($session->user_id, 'sp_cv_status', 'completed');
            
            error_log('CV data saved for user ID: ' . $session->user_id);
        }
        
        do_action('sp_cv_data_saved', $session_key, $clean_cv_data, $cv_negations);
        do_action('sp_onboarding_completed', $session_key, $session ? $session->user_id : 0);
        
        $redirect_url = get_option('sp_onboarding_redirect_url');
        if (empty($redirect_url)) {
            $redirect_url = home_url();
        }
        
        error_log('✅ CV SAVE SUCCESSFUL - Redirecting to: ' . $redirect_url);
        
        wp_send_json_success(array(
            'message' => 'Date CV salvate și profil finalizat!',
            'redirect_url' => $redirect_url
        ));
    }
    
    /**
     * Handle CV skip with redirect
     */
    public function ajax_skip_cv() {
        check_ajax_referer('sp_onboarding_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Trebuie să fiți autentificat.'));
            return;
        }
        
        $user_id = get_current_user_id();
        update_user_meta($user_id, 'sp_cv_status', 'skipped');
        
        $redirect_url = get_option('sp_onboarding_skip_redirect_url');
        if (empty($redirect_url)) {
            $redirect_url = home_url();
        }
        
        do_action('sp_cv_skipped', $user_id);
        
        wp_send_json_success(array(
            'message' => 'CV-ul a fost omis. Îl poți completa mai târziu.',
            'redirect_url' => $redirect_url
        ));
    }
    
    /**
     * ENHANCED: View session with COMPLETE AI analysis and prominent CV negations
     */
    public function ajax_view_session() {
        check_ajax_referer('sp_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Nu aveți permisiuni.'));
            return;
        }
        
        $session_id = intval($_POST['session_id']);
        global $wpdb;
        $session = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->plugin->table_sessions} WHERE id = %d", $session_id));
        
        if (!$session) {
            wp_send_json_error(array('message' => 'Sesiunea nu există.'));
            return;
        }
        
        $html = '<div style="max-width: 900px;">';
        $html .= '<h2>Detalii Sesiune #' . $session->id . '</h2>';
        
        // Basic Info Table
        $html .= '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
        $html .= '<tr style="background: #f9f9f9;"><th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Câmp</th><th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Valoare</th></tr>';
        
        $html .= '<tr><td style="padding: 10px; border: 1px solid #ddd;"><strong>Session Key</strong></td><td style="padding: 10px; border: 1px solid #ddd;"><code>' . esc_html($session->session_key) . '</code></td></tr>';
        $html .= '<tr><td style="padding: 10px; border: 1px solid #ddd;"><strong>User ID</strong></td><td style="padding: 10px; border: 1px solid #ddd;">' . ($session->user_id ? $session->user_id : 'N/A') . '</td></tr>';
        $html .= '<tr><td style="padding: 10px; border: 1px solid #ddd;"><strong>Pas Curent</strong></td><td style="padding: 10px; border: 1px solid #ddd;">' . esc_html($session->current_step) . '</td></tr>';
        $html .= '<tr><td style="padding: 10px; border: 1px solid #ddd;"><strong>Creat La</strong></td><td style="padding: 10px; border: 1px solid #ddd;">' . esc_html($session->created_at) . '</td></tr>';
        $html .= '<tr><td style="padding: 10px; border: 1px solid #ddd;"><strong>Completat La</strong></td><td style="padding: 10px; border: 1px solid #ddd;">' . ($session->completed_at ? esc_html($session->completed_at) : '<em>Incomplet</em>') . '</td></tr>';
        
        // Intelligence Scores
        if ($session->intelligence_scores) {
            $scores = json_decode($session->intelligence_scores, true);
            if ($scores && is_array($scores)) {
                arsort($scores);
                $html .= '<tr><td style="padding: 10px; border: 1px solid #ddd; vertical-align: top;"><strong>Scoruri Inteligență</strong></td><td style="padding: 10px; border: 1px solid #ddd;">';
                foreach ($scores as $type => $score) {
                    if (isset($this->plugin->intelligence_types[$type])) {
                        $percentage = intval($score * (100 / $this->plugin->intelligence_category_count));
                        $html .= '<div style="margin: 5px 0;"><strong>' . esc_html($this->plugin->intelligence_types[$type]['name']) . ':</strong> ' . $score . '/' . $this->plugin->intelligence_category_count . ' (' . $percentage . '%)</div>';
                    }
                }
                $html .= '</td></tr>';
            }
        }
        
        // Test Answers Summary
        if ($session->test_data) {
            $answers = json_decode($session->test_data, true);
            if ($answers && is_array($answers)) {
                $da_count = 0;
                $nu_count = 0;
                foreach ($answers as $answer) {
                    if ($answer === 'da') $da_count++;
                    else if ($answer === 'nu') $nu_count++;
                }
                $html .= '<tr><td style="padding: 10px; border: 1px solid #ddd; vertical-align: top;"><strong>Răspunsuri Test</strong></td><td style="padding: 10px; border: 1px solid #ddd;">';
                $html .= '<strong>Total răspunsuri:</strong> ' . count($answers) . ' (DA: ' . $da_count . ', NU: ' . $nu_count . ')';
                $html .= '</td></tr>';
            }
        }
        
        $html .= '</table>';
        
        // =====================================================
        // COMPLETE AI ANALYSIS DISPLAY - ALL FIELDS
        // =====================================================
        if ($session->ai_analysis) {
            $analysis = json_decode($session->ai_analysis, true);
            if ($analysis && is_array($analysis)) {
                $html .= '<div style="margin-top: 30px; padding: 25px; background: #f0fdf4; border: 2px solid #10b981; border-radius: 8px;">';
                $html .= '<h3 style="margin-top: 0; color: #10b981;">🤖 Analiză AI Completă</h3>';
                
                // Profile Description
                if (isset($analysis['profile_description']) && !empty($analysis['profile_description'])) {
                    $html .= '<div style="margin-bottom: 20px; padding: 15px; background: white; border-radius: 6px; border-left: 4px solid #10b981;">';
                    $html .= '<strong style="color: #10b981; font-size: 15px;">📋 Profilul Profesional:</strong><br>';
                    $html .= '<p style="margin: 10px 0; line-height: 1.6; color: #333;">' . nl2br(esc_html($analysis['profile_description'])) . '</p>';
                    $html .= '</div>';
                }
                
                // Learning Style - ALWAYS DISPLAY
                if (isset($analysis['learning_style']) && !empty($analysis['learning_style'])) {
                    $html .= '<div style="margin-bottom: 20px; padding: 15px; background: #dbeafe; border-radius: 6px; border-left: 4px solid #3b82f6;">';
                    $html .= '<strong style="color: #1e40af; font-size: 15px;">📚 Stil de Învățare:</strong><br>';
                    $html .= '<p style="margin: 10px 0; line-height: 1.6; color: #333;">' . nl2br(esc_html($analysis['learning_style'])) . '</p>';
                    $html .= '</div>';
                } else {
                    $html .= '<div style="margin-bottom: 20px; padding: 12px; background: #fef2f2; border-radius: 6px; border-left: 4px solid #ef4444;">';
                    $html .= '<em style="color: #991b1b;">⚠️ Stil de învățare nu este disponibil</em>';
                    $html .= '</div>';
                }
                
                // Work Environment - ALWAYS DISPLAY
                if (isset($analysis['work_environment']) && !empty($analysis['work_environment'])) {
                    $html .= '<div style="margin-bottom: 20px; padding: 15px; background: #fef3c7; border-radius: 6px; border-left: 4px solid #f59e0b;">';
                    $html .= '<strong style="color: #92400e; font-size: 15px;">🏢 Mediu de Lucru Ideal:</strong><br>';
                    $html .= '<p style="margin: 10px 0; line-height: 1.6; color: #333;">' . nl2br(esc_html($analysis['work_environment'])) . '</p>';
                    $html .= '</div>';
                } else {
                    $html .= '<div style="margin-bottom: 20px; padding: 12px; background: #fef2f2; border-radius: 6px; border-left: 4px solid #ef4444;">';
                    $html .= '<em style="color: #991b1b;">⚠️ Mediu de lucru ideal nu este disponibil</em>';
                    $html .= '</div>';
                }
                
                // Soft Skills
                if (isset($analysis['soft_skills']) && is_array($analysis['soft_skills']) && !empty($analysis['soft_skills'])) {
                    $html .= '<div style="margin-bottom: 20px; padding: 15px; background: white; border-radius: 6px;">';
                    $html .= '<strong style="color: #10b981; font-size: 15px;">💪 Competențe Soft:</strong><br>';
                    $html .= '<div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px;">';
                    foreach ($analysis['soft_skills'] as $skill) {
                        if (is_array($skill) && isset($skill['skill'])) {
                            $skill_color = isset($skill['color']) ? $skill['color'] : '#0292B7';
                            $skill_points = isset($skill['points']) ? $skill['points'] : 0;
                            $html .= '<span style="background: ' . esc_attr($skill_color) . '20; color: ' . esc_attr($skill_color) . '; padding: 6px 12px; border-radius: 15px; font-size: 13px; border: 1px solid ' . esc_attr($skill_color) . '50; font-weight: 600;">';
                            $html .= esc_html($skill['skill']) . ' (' . $skill_points . 'p)';
                            $html .= '</span>';
                        }
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                }
                
                // Specialization Recommendations
                if (isset($analysis['specialization_recommendations']) && !empty($analysis['specialization_recommendations'])) {
                    $html .= '<div style="margin-bottom: 20px; padding: 15px; background: white; border-radius: 6px; border-left: 4px solid #8b5cf6;">';
                    $html .= '<strong style="color: #6d28d9; font-size: 15px;">🎯 Recomandări Specializări:</strong><br>';
                    $html .= '<p style="margin: 10px 0; line-height: 1.6; color: #333;">' . nl2br(esc_html($analysis['specialization_recommendations'])) . '</p>';
                    $html .= '</div>';
                }
                
                $html .= '</div>';
            }
        }
        
        // =====================================================
        // CV DATA with PROMINENT NEGATIONS
        // =====================================================
        $cv_data = null;
        $cv_negations = null;
        
        if ($session->cv_data && $session->cv_data !== '[]' && $session->cv_data !== '{}' && $session->cv_data !== 'null') {
            $cv_data = json_decode($session->cv_data, true);
        }
        
        if ($session->cv_negations && $session->cv_negations !== '[]' && $session->cv_negations !== '{}' && $session->cv_negations !== 'null') {
            $cv_negations = json_decode($session->cv_negations, true);
        }
        
        if ($cv_data && is_array($cv_data) && count($cv_data) > 0) {
            $html .= '<div style="margin-top: 30px; padding: 20px; background: #f0f9ff; border: 2px solid #0292B7; border-radius: 8px;">';
            $html .= '<h3 style="margin-top: 0; color: #0292B7;">📋 Date CV Complete</h3>';
            
            foreach ($cv_data as $field_key => $field_value) {
                if (empty($field_value) || (is_array($field_value) && count($field_value) === 0)) {
                    continue;
                }
                
                $field_label = $this->format_field_name(str_replace('cv_', '', $field_key));
                
                $is_negated = false;
                $clean_field_name = str_replace('cv_', '', $field_key);
                if ($cv_negations && isset($cv_negations[$clean_field_name]) && $cv_negations[$clean_field_name] === true) {
                    $is_negated = true;
                }
                
                if (is_array($field_value) && isset($field_value[0]) && is_array($field_value[0])) {
                    // Repeatable section
                    $html .= '<div style="margin: 20px 0; padding: 15px; background: white; border-left: 4px solid #0292B7; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">';
                    $html .= '<h4 style="margin-top: 0; color: #0292B7;">📂 ' . esc_html($field_label);
                    
                    if ($is_negated) {
                        $html .= ' <span style="background: #ffc107; color: #000; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 700; margin-left: 10px;">⚠️ NEGAT - Nu a completat</span>';
                    }
                    
                    $html .= '</h4>';
                    
                    $entry_count = 0;
                    foreach ($field_value as $index => $entry) {
                        if (empty($entry) || !is_array($entry)) continue;
                        
                        $has_content = false;
                        foreach ($entry as $val) {
                            if (!empty($val)) {
                                $has_content = true;
                                break;
                            }
                        }
                        
                        if (!$has_content) continue;
                        
                        $entry_count++;
                        $html .= '<div style="margin: 10px 0 10px 20px; padding: 12px; background: #f9f9f9; border-left: 3px solid #10b981; border-radius: 4px;">';
                        $html .= '<strong style="color: #10b981;">Intrarea ' . $entry_count . ':</strong><br>';
                        
                        foreach ($entry as $sub_field => $sub_value) {
                            if (!empty($sub_value)) {
                                $sub_label = $this->format_field_name($sub_field);
                                $html .= '<div style="margin: 8px 0 8px 15px; padding: 8px; border-left: 2px solid #ddd;">';
                                $html .= '<strong style="color: #666;">' . esc_html($sub_label) . ':</strong><br>';
                                $html .= '<span style="margin-left: 10px; color: #333; display: block; margin-top: 4px;">' . nl2br(esc_html($sub_value)) . '</span>';
                                $html .= '</div>';
                            }
                        }
                        $html .= '</div>';
                    }
                    
                    $html .= '</div>';
                } else {
                    // Simple field
                    if (!empty($field_value)) {
                        $html .= '<div style="margin: 15px 0; padding: 12px; background: white; border-left: 4px solid #0292B7; border-radius: 5px;">';
                        $html .= '<strong style="color: #0292B7;">📌 ' . esc_html($field_label) . ':</strong>';
                        
                        if ($is_negated) {
                            $html .= ' <span style="background: #ffc107; color: #000; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 700; margin-left: 10px;">⚠️ NEGAT - Nu a completat</span>';
                        }
                        
                        $html .= '<br>';
                        $display_value = is_array($field_value) ? implode(', ', array_filter($field_value)) : $field_value;
                        $html .= '<span style="margin-left: 20px; display: block; margin-top: 8px; color: #333; line-height: 1.6;">' . nl2br(esc_html($display_value)) . '</span>';
                        $html .= '</div>';
                    }
                }
            }
            
            // Show list of ALL negated fields prominently
            if ($cv_negations && is_array($cv_negations) && count(array_filter($cv_negations)) > 0) {
                $html .= '<div style="margin-top: 25px; padding: 15px; background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px;">';
                $html .= '<h4 style="margin-top: 0; color: #856404;">⚠️ Câmpuri Negate (Utilizatorul a ales să NU completeze)</h4>';
                $html .= '<ul style="margin: 10px 0; padding-left: 20px;">';
                foreach ($cv_negations as $neg_field => $is_neg) {
                    if ($is_neg) {
                        $formatted = $this->format_field_name($neg_field);
                        $html .= '<li style="color: #856404; font-weight: 600; margin: 5px 0;">' . esc_html($formatted) . '</li>';
                    }
                }
                $html .= '</ul>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        } else {
            $html .= '<div style="margin-top: 20px; padding: 15px; background: #f0f0f0; border-left: 4px solid #999; border-radius: 5px;">';
            $html .= '<em>ℹ️ Nu există date CV salvate pentru această sesiune</em>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * Helper: Format field name for display
     */
    private function format_field_name($field_name) {
        $formatted = str_replace(array('cv_', '_'), array('', ' '), $field_name);
        $formatted = ucwords($formatted);
        
        $replacements = array(
            'Experienta Profesionala' => 'Experiență Profesională',
            'Educatie' => 'Educație',
            'Competente' => 'Competențe',
            'Limbi Straine' => 'Limbi Străine',
            'Referinte' => 'Referințe',
            'Telefon' => 'Telefon',
            'Oras' => 'Oraș',
            'Profil Profesional' => 'Profil Profesional',
            'Perioada' => 'Perioada',
            'Pozitia' => 'Poziția',
            'Compania' => 'Compania',
            'Responsabilitati' => 'Responsabilități',
            'Institutia' => 'Instituția',
            'Perioada Edu' => 'Perioada Educație',
            'Specializare' => 'Specializare',
            'Limba' => 'Limba',
            'Nivel' => 'Nivel'
        );
        
        foreach ($replacements as $search => $replace) {
            if (stripos($formatted, $search) !== false) {
                $formatted = str_ireplace($search, $replace, $formatted);
            }
        }
        
        return $formatted;
    }
    
    // Admin methods
    public function ajax_reload_test_questions() {
        check_ajax_referer('sp_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Nu aveți permisiuni suficiente.'));
            return;
        }
        $this->plugin->load_intelligence_test(true);
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->plugin->table_questions}");
        wp_send_json_success(array('message' => 'Testul a fost reîncărcat cu succes!', 'questions_count' => $count));
    }
    
    public function ajax_delete_session() {
        check_ajax_referer('sp_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Nu aveți permisiuni.'));
            return;
        }
        $session_id = intval($_POST['session_id']);
        global $wpdb;
        $result = $wpdb->delete($this->plugin->table_sessions, array('id' => $session_id));
        if ($result) {
            wp_send_json_success(array('message' => 'Sesiune ștearsă cu succes.'));
        } else {
            wp_send_json_error(array('message' => 'Eroare la ștergerea sesiunii.'));
        }
    }
    
    public function ajax_cleanup_incomplete_sessions() {
        check_ajax_referer('sp_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Nu aveți permisiuni suficiente.'));
            return;
        }
        global $wpdb;
        $result = $wpdb->query("DELETE FROM {$this->plugin->table_sessions} WHERE completed_at IS NULL");
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Sesiuni incomplete șterse cu succes!', 'deleted_count' => $result));
        } else {
            wp_send_json_error(array('message' => 'Eroare la ștergerea sesiunilor incomplete.'));
        }
    }
}
?>