<?php
/**
 * Data Loader - FIXED VERSION 5.0
 * Comprehensive profile data loading with proper UTF-8 encoding
 * Pulls ALL data from sessions and displays it correctly
 */

if (!defined('ABSPATH')) exit;

function sp_load_profile_data($user_id) {
    global $wpdb;
    
    $user = get_userdata($user_id);
    if (!$user) {
        return null;
    }
    
    $data = array(
        'user_id' => $user_id,
        'user' => $user,
        'basic_info' => sp_load_basic_info($user_id),
        'test_results' => sp_load_test_results($user_id),
        'cv_data' => sp_load_cv_data($user_id),
        'cv_negations' => sp_load_cv_negations($user_id),
        'soft_skills' => sp_load_soft_skills($user_id),
        'ai_interpretation' => sp_load_ai_interpretation($user_id),
        'completion' => sp_get_completion_percentage($user_id),
        'session_data' => sp_load_session_data($user_id),
        'debug' => sp_get_debug_info($user_id)
    );

    return $data;
}

function sp_load_basic_info($user_id) {
    $user = get_userdata($user_id);
    
    // Load from multiple possible meta key variations
    $phone = get_user_meta($user_id, 'phone', true) ?: get_user_meta($user_id, 'telefon', true) ?: get_user_meta($user_id, 'phone_number', true);
    $city = get_user_meta($user_id, 'city', true) ?: get_user_meta($user_id, 'oras', true) ?: get_user_meta($user_id, 'localitate', true);
    $birth_date = get_user_meta($user_id, 'psihoprofile_birthdate', true) ?: get_user_meta($user_id, 'birth_date', true) ?: get_user_meta($user_id, 'data_nasterii', true);
    $sex = get_user_meta($user_id, 'psihoprofile_sex', true) ?: get_user_meta($user_id, 'sex', true) ?: get_user_meta($user_id, 'gen', true);
    
    return array(
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'display_name' => $user->display_name,
        'email' => $user->user_email,
        'phone' => $phone,
        'city' => $city,
        'birth_date' => $birth_date,
        'sex' => $sex,
        'age' => sp_get_user_age($user_id),
        'bio' => get_user_meta($user_id, 'description', true),
        'avatar' => get_avatar_url($user_id, array('size' => 150))
    );
}

function sp_load_test_results($user_id) {
    $test_status = get_user_meta($user_id, 'sp_test_completed_status', true);
    
    if ($test_status !== 'completed') {
        return null;
    }
    
    $types = sp_get_intelligence_types();
    $scores = array();
    
    // TRY 1: sp_intelligence_scores array
    $scores_array = get_user_meta($user_id, 'sp_intelligence_scores', true);
    
    if (!empty($scores_array) && is_array($scores_array)) {
        foreach ($scores_array as $type_id => $count) {
            if (isset($types[$type_id])) {
                $percentage = ($count / 10) * 100;
                $scores[$type_id] = array(
                    'name' => $types[$type_id]['name'],
                    'slug' => $types[$type_id]['slug'],
                    'icon' => $types[$type_id]['icon'],
                    'color' => $types[$type_id]['color'],
                    'score' => floatval($percentage),
                    'count' => $count
                );
            }
        }
    }
    
    // TRY 2: Individual meta keys
    if (empty($scores)) {
        foreach ($types as $id => $type) {
            $score = get_user_meta($user_id, 'sp_intelligence_' . $id . '_score', true);
            if ($score) {
                $scores[$id] = array(
                    'name' => $type['name'],
                    'slug' => $type['slug'],
                    'icon' => $type['icon'],
                    'color' => $type['color'],
                    'score' => floatval($score),
                    'count' => null
                );
            }
        }
    }
    
    // TRY 3: Session data
    if (empty($scores)) {
        global $wpdb;
        $table = $wpdb->prefix . 'sp_onboarding_sessions';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
            $session = $wpdb->get_row($wpdb->prepare(
                "SELECT intelligence_scores FROM $table WHERE user_id = %d AND completed_at IS NOT NULL ORDER BY completed_at DESC LIMIT 1",
                $user_id
            ));
            
            if ($session && $session->intelligence_scores) {
                $session_scores = json_decode($session->intelligence_scores, true);
                if (is_array($session_scores)) {
                    foreach ($session_scores as $type_id => $count) {
                        if (isset($types[$type_id])) {
                            $percentage = ($count / 10) * 100;
                            $scores[$type_id] = array(
                                'name' => $types[$type_id]['name'],
                                'slug' => $types[$type_id]['slug'],
                                'icon' => $types[$type_id]['icon'],
                                'color' => $types[$type_id]['color'],
                                'score' => floatval($percentage),
                                'count' => $count
                            );
                        }
                    }
                }
            }
        }
    }
    
    if (empty($scores)) {
        return null;
    }
    
    uasort($scores, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    
    return array(
        'status' => 'completed',
        'scores' => $scores,
        'dominant' => reset($scores),
        'top_three' => array_slice($scores, 0, 3, true)
    );
}

/**
 * FIXED v5.1: Load CV data - ALWAYS check session first, ignore status
 * This fixes the issue where CV status is "skipped" but session has data
 */
function sp_load_cv_data($user_id) {
    global $wpdb;
    
    // CRITICAL FIX: Try session data FIRST, regardless of status
    // Many users have status="skipped" but session contains CV data
    $session_cv = sp_load_cv_from_session($user_id);
    if ($session_cv !== null) {
        return $session_cv; // Session has data, use it!
    }
    
    // If no session data, try CV fields table
    $table_cv = $wpdb->prefix . 'sp_cv_fields';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_cv'") == $table_cv) {
        $cv_entries = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_cv WHERE user_id = %d ORDER BY field_id, entry_index",
            $user_id
        ), ARRAY_A);
        
        if (!empty($cv_entries)) {
            return sp_process_cv_entries($cv_entries, $user_id);
        }
    }
    
    // No data found anywhere
    return null;
}

/**
 * Process CV entries from database
 */
function sp_process_cv_entries($cv_entries, $user_id) {
    global $wpdb;
    
    // Organize by field
    $cv_data = array();
    foreach ($cv_entries as $entry) {
        $field_id = $entry['field_id'];
        if (!isset($cv_data[$field_id])) {
            $cv_data[$field_id] = array();
        }
        
        // FIXED: Ensure proper UTF-8 decoding
        $entry['field_value'] = sp_fix_utf8($entry['field_value']);
        $cv_data[$field_id][] = $entry;
    }
    
    // Get field definitions
    $table_fields = $wpdb->prefix . 'sp_cv_field_definitions';
    $fields = array();
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_fields'") == $table_fields) {
        $field_defs = $wpdb->get_results(
            "SELECT * FROM $table_fields ORDER BY sort_order",
            ARRAY_A
        );
        
        foreach ($field_defs as $field) {
            // FIXED: Ensure proper UTF-8 decoding
            $field['field_label'] = sp_fix_utf8($field['field_label']);
            $fields[$field['id']] = $field;
        }
    }
    
    return array(
        'status' => 'completed',
        'entries' => $cv_data,
        'fields' => $fields,
        'specialization' => sp_extract_field_value($cv_data, $fields, array('specialization', 'job_title', 'profesie', 'specializare', 'functie', 'titlu_post')),
        'experience' => sp_extract_field_entries($cv_data, $fields, array('experienta_munca', 'experience', 'work_experience', 'experienta', 'experienta_profesionala')),
        'education' => sp_extract_field_entries($cv_data, $fields, array('educatie', 'education', 'studii', 'pregatire')),
        'skills' => sp_extract_field_entries($cv_data, $fields, array('competente', 'skills', 'abilitati')),
        'languages' => sp_extract_field_entries($cv_data, $fields, array('limbi_straine', 'languages', 'limbi'))
    );
}

/**
 * Load CV data from session (fallback)
 * ENHANCED v5.1: Better handling of cv_data field
 */
function sp_load_cv_from_session($user_id) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'sp_onboarding_sessions';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        return null;
    }
    
    // Get the most recent session (completed or not)
    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT cv_data FROM $table WHERE user_id = %d ORDER BY id DESC LIMIT 1",
        $user_id
    ));
    
    if (!$session) {
        return null;
    }
    
    // Try to get CV data
    $cv_data_raw = null;
    
    if (!empty($session->cv_data)) {
        // If cv_data is string, decode it
        if (is_string($session->cv_data)) {
            $cv_data_raw = json_decode($session->cv_data, true);
        } else {
            $cv_data_raw = $session->cv_data;
        }
    }
    
    // Validate we have usable data
    if (empty($cv_data_raw) || !is_array($cv_data_raw)) {
        return null;
    }
    
    // Transform session CV data to expected format
    $cv_entries = array();
    $fields = array();
    $field_counter = 1;
    
    foreach ($cv_data_raw as $field_name => $field_values) {
        // Skip empty or invalid fields
        if (empty($field_values)) {
            continue;
        }
        
        // Handle both single values and arrays
        if (!is_array($field_values)) {
            $field_values = array($field_values);
        }
        
        $entry_index = 0;
        $field_has_data = false;
        
        foreach ($field_values as $value) {
            // Handle nested arrays (sometimes CV data is double-wrapped)
            if (is_array($value)) {
                // Check if it's an associative array with data
                if (isset($value['value'])) {
                    $value = $value['value'];
                } elseif (isset($value['field_value'])) {
                    $value = $value['field_value'];
                } else {
                    // If it's just an array of values, implode them
                    $value = implode(', ', array_filter($value, 'is_string'));
                }
            }
            
            // Convert to string and clean
            $value = is_string($value) ? trim($value) : strval($value);
            
            // Skip empty values
            if (empty($value) || $value === '' || $value === 'null') {
                continue;
            }
            
            // FIXED: Ensure proper UTF-8 decoding
            $value = sp_fix_utf8($value);
            
            if (!isset($cv_entries[$field_counter])) {
                $cv_entries[$field_counter] = array();
            }
            
            $cv_entries[$field_counter][] = array(
                'field_id' => $field_counter,
                'field_name' => $field_name,
                'field_value' => $value,
                'entry_index' => $entry_index++
            );
            
            $field_has_data = true;
        }
        
        // Only create field definition if we have data
        if ($field_has_data) {
            $fields[$field_counter] = array(
                'id' => $field_counter,
                'field_name' => $field_name,
                'field_label' => sp_humanize_field_name($field_name),
                'field_type' => 'text',
                'section_type' => (count($cv_entries[$field_counter]) > 1) ? 'multiple' : 'single'
            );
            $field_counter++;
        }
    }
    
    if (empty($cv_entries)) {
        return null;
    }
    
    return array(
        'status' => 'completed',
        'entries' => $cv_entries,
        'fields' => $fields,
        'specialization' => sp_extract_field_value($cv_entries, $fields, array('specialization', 'job_title', 'profesie', 'specializare', 'functie', 'titlu_post')),
        'experience' => sp_extract_field_entries($cv_entries, $fields, array('experienta_munca', 'experience', 'work_experience', 'experienta', 'experienta_profesionala')),
        'education' => sp_extract_field_entries($cv_entries, $fields, array('educatie', 'education', 'studii', 'pregatire')),
        'skills' => sp_extract_field_entries($cv_entries, $fields, array('competente', 'skills', 'abilitati')),
        'languages' => sp_extract_field_entries($cv_entries, $fields, array('limbi_straine', 'languages', 'limbi'))
    );
}

/**
 * Extract single field value
 */
function sp_extract_field_value($cv_data, $fields, $possible_names) {
    foreach ($fields as $field) {
        if (in_array($field['field_name'], $possible_names)) {
            if (isset($cv_data[$field['id']])) {
                return $cv_data[$field['id']][0]['field_value'] ?? null;
            }
        }
    }
    return null;
}

/**
 * Extract multiple field entries
 */
function sp_extract_field_entries($cv_data, $fields, $possible_names) {
    foreach ($fields as $field) {
        if (in_array($field['field_name'], $possible_names)) {
            if (isset($cv_data[$field['id']])) {
                return $cv_data[$field['id']];
            }
        }
    }
    return array();
}

/**
 * Load CV negations - fields that user marked as "N/A" or "Don't have"
 */
function sp_load_cv_negations($user_id) {
    global $wpdb;

    $negations = array();

    // Check session for negation data
    $table = $wpdb->prefix . 'sp_onboarding_sessions';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        return $negations;
    }

    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT cv_negations FROM $table WHERE user_id = %d ORDER BY id DESC LIMIT 1",
        $user_id
    ));

    if (!$session || empty($session->cv_negations)) {
        return $negations;
    }

    $cv_negations_raw = json_decode($session->cv_negations, true);
    if (!is_array($cv_negations_raw)) {
        return $negations;
    }

    // Build negations array from the cv_negations column
    foreach ($cv_negations_raw as $field_name => $is_negated) {
        if ($is_negated === true || $is_negated === 1 || $is_negated === '1') {
            $negations[$field_name] = array(
                'field_name' => $field_name,
                'field_label' => sp_humanize_field_name($field_name),
                'is_negated' => true
            );
        }
    }

    return $negations;
}

/**
 * Humanize field name (convert underscores to spaces, capitalize)
 */
function sp_humanize_field_name($field_name) {
    // Strip cv_ prefix if present
    $field_name = preg_replace('/^cv_/', '', $field_name);

    $translations = array(
        'experienta_munca' => 'Experiență Profesională',
        'experienta_profesionala' => 'Experiență Profesională',
        'experienta' => 'Experiență',
        'experience' => 'Experiență',
        'work_experience' => 'Experiență Profesională',
        'educatie' => 'Educație',
        'education' => 'Educație',
        'studii' => 'Studii',
        'specializare' => 'Specializare',
        'specialization' => 'Specializare',
        'competente' => 'Competențe',
        'skills' => 'Competențe',
        'limbi_straine' => 'Limbi Străine',
        'languages' => 'Limbi Străine',
        'telefon' => 'Telefon',
        'phone' => 'Telefon',
        'oras' => 'Oraș',
        'city' => 'Oraș',
        'adresa' => 'Adresă',
        'address' => 'Adresă'
    );

    if (isset($translations[$field_name])) {
        return $translations[$field_name];
    }

    return ucwords(str_replace('_', ' ', $field_name));
}

function sp_load_soft_skills($user_id) {
    // TRY 1: AI analysis - return full skill objects with points and colors
    $ai_analysis = get_user_meta($user_id, 'sp_ai_analysis', true);

    if ($ai_analysis) {
        if (is_string($ai_analysis)) {
            $ai_analysis = json_decode($ai_analysis, true);
        }

        if (is_array($ai_analysis) && isset($ai_analysis['soft_skills']) && is_array($ai_analysis['soft_skills'])) {
            $skills = array();
            foreach ($ai_analysis['soft_skills'] as $skill_data) {
                if (is_array($skill_data) && isset($skill_data['skill'])) {
                    // Return full skill object with points and color
                    $skills[] = array(
                        'skill' => sp_fix_utf8($skill_data['skill']),
                        'points' => isset($skill_data['points']) ? intval($skill_data['points']) : 5,
                        'color' => isset($skill_data['color']) ? $skill_data['color'] : '#0292B7'
                    );
                } elseif (is_string($skill_data)) {
                    // Legacy format: just skill name
                    $skills[] = array(
                        'skill' => sp_fix_utf8($skill_data),
                        'points' => 5,
                        'color' => '#0292B7'
                    );
                }
            }

            if (!empty($skills)) {
                return $skills;
            }
        }
    }

    // TRY 2: Direct meta (legacy format)
    $soft_skills = get_user_meta($user_id, 'sp_soft_skills', true);

    if (!$soft_skills) {
        return array();
    }

    $skills_array = array();
    if (is_string($soft_skills)) {
        $decoded = json_decode($soft_skills, true);
        if (is_array($decoded)) {
            $skills_array = $decoded;
        } else {
            $skills_array = array_map('trim', explode(',', $soft_skills));
        }
    } elseif (is_array($soft_skills)) {
        $skills_array = $soft_skills;
    }

    // Convert to full format
    $result = array();
    foreach ($skills_array as $skill) {
        if (is_string($skill)) {
            $result[] = array(
                'skill' => sp_fix_utf8($skill),
                'points' => 5,
                'color' => '#0292B7'
            );
        }
    }

    return $result;
}

function sp_load_ai_interpretation($user_id) {
    // TRY 1: AI analysis (new format)
    $ai_analysis = get_user_meta($user_id, 'sp_ai_analysis', true);
    
    if ($ai_analysis) {
        if (is_string($ai_analysis)) {
            $ai_analysis = json_decode($ai_analysis, true);
        }
        
        if (is_array($ai_analysis) && isset($ai_analysis['profile_description'])) {
            return array(
                'text' => sp_fix_utf8($ai_analysis['profile_description']),
                'generated_at' => get_user_meta($user_id, 'sp_test_completed_date', true),
                'learning_style' => isset($ai_analysis['learning_style']) ? sp_fix_utf8($ai_analysis['learning_style']) : null,
                'work_environment' => isset($ai_analysis['work_environment']) ? sp_fix_utf8($ai_analysis['work_environment']) : null,
                'career_paths' => isset($ai_analysis['career_paths']) ? $ai_analysis['career_paths'] : null
            );
        }
    }
    
    // TRY 2: Old meta key
    $interpretation = get_user_meta($user_id, 'sp_ai_profile_interpretation', true);
    
    if (!$interpretation) {
        return null;
    }
    
    return array(
        'text' => sp_fix_utf8($interpretation),
        'generated_at' => get_user_meta($user_id, 'sp_ai_interpretation_date', true),
        'learning_style' => null,
        'work_environment' => null,
        'career_paths' => null
    );
}

/**
 * Load comprehensive session data
 */
function sp_load_session_data($user_id) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'sp_onboarding_sessions';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        return null;
    }
    
    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d ORDER BY completed_at DESC LIMIT 1",
        $user_id
    ), ARRAY_A);
    
    if (!$session) {
        return null;
    }
    
    // Decode JSON fields
    if (!empty($session['intelligence_scores'])) {
        $session['intelligence_scores'] = json_decode($session['intelligence_scores'], true);
    }
    if (!empty($session['answers'])) {
        $session['answers'] = json_decode($session['answers'], true);
    }
    if (!empty($session['cv_data'])) {
        $session['cv_data'] = json_decode($session['cv_data'], true);
    }
    
    return $session;
}

/**
 * CRITICAL: Fix UTF-8 encoding issues
 */
function sp_fix_utf8($text) {
    if (!is_string($text)) {
        return $text;
    }
    
    // If already valid UTF-8, return as is
    if (mb_check_encoding($text, 'UTF-8')) {
        return $text;
    }
    
    // Try to convert from various encodings
    $encodings = array('ISO-8859-1', 'ISO-8859-2', 'Windows-1252', 'Windows-1250');
    
    foreach ($encodings as $encoding) {
        $converted = @iconv($encoding, 'UTF-8//IGNORE', $text);
        if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
            return $converted;
        }
    }
    
    // Last resort: use mb_convert_encoding
    return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
}

function sp_get_debug_info($user_id) {
    global $wpdb;
    
    $debug = array(
        'test_status' => get_user_meta($user_id, 'sp_test_completed_status', true),
        'cv_status' => get_user_meta($user_id, 'sp_cv_status', true),
        'has_intelligence_scores' => !empty(get_user_meta($user_id, 'sp_intelligence_scores', true)),
        'has_ai_analysis' => !empty(get_user_meta($user_id, 'sp_ai_analysis', true)),
        'has_soft_skills' => !empty(get_user_meta($user_id, 'sp_soft_skills', true)),
    );
    
    // Check CV table
    $table_cv = $wpdb->prefix . 'sp_cv_fields';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_cv'") == $table_cv) {
        $cv_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_cv WHERE user_id = %d",
            $user_id
        ));
        $debug['cv_entries_count'] = intval($cv_count);
    }
    
    // Check session - ENHANCED with CV data preview
    $table_session = $wpdb->prefix . 'sp_onboarding_sessions';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_session'") == $table_session) {
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_session WHERE user_id = %d ORDER BY id DESC LIMIT 1",
            $user_id
        ), ARRAY_A);
        
        if ($session) {
            $debug['has_session'] = true;
            $debug['session_id'] = $session['id'];
            $debug['session_completed'] = !empty($session['completed_at']);
            $debug['session_step'] = $session['current_step'] ?? null;
            
            // Check if session has CV data - ENHANCED
            if (!empty($session['cv_data'])) {
                $cv_from_session = json_decode($session['cv_data'], true);
                $debug['session_has_cv'] = !empty($cv_from_session);
                
                if ($cv_from_session && is_array($cv_from_session)) {
                    $debug['session_cv_fields'] = count($cv_from_session);
                    $debug['session_cv_field_names'] = implode(', ', array_keys($cv_from_session));
                    
                    // Preview first field's data
                    $first_field = reset($cv_from_session);
                    if (is_array($first_field) && !empty($first_field)) {
                        $preview = is_array($first_field[0]) ? json_encode($first_field[0]) : $first_field[0];
                        $debug['session_cv_preview'] = substr($preview, 0, 100);
                    }
                } else {
                    $debug['session_cv_fields'] = 0;
                    $debug['session_cv_error'] = 'Invalid JSON or not an array';
                }
            } else {
                $debug['session_has_cv'] = false;
            }
        } else {
            $debug['has_session'] = false;
        }
    }
    
    return $debug;
}