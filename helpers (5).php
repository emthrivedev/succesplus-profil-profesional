<?php
/**
 * Helper Functions - ENHANCED VERSION 2.1 with CV Fix
 */

if (!defined('ABSPATH')) exit;

function sp_get_intelligence_types() {
    return array(
        1 => array(
            'name' => 'Verbal-lingvistică',
            'slug' => 'verbal-lingvistica',
            'icon' => '📝',
            'color' => '#3b82f6'
        ),
        2 => array(
            'name' => 'Logico-matematică',
            'slug' => 'logico-matematica',
            'icon' => '🔢',
            'color' => '#0292B7'
        ),
        3 => array(
            'name' => 'Vizual-spațială',
            'slug' => 'vizual-spatiala',
            'icon' => '🎨',
            'color' => '#8b5cf6'
        ),
        4 => array(
            'name' => 'Kinestezic-corporală',
            'slug' => 'kinestezic-corporala',
            'icon' => '🤸',
            'color' => '#f59e0b'
        ),
        5 => array(
            'name' => 'Muzical-ritmică',
            'slug' => 'muzical-ritmica',
            'icon' => '🎵',
            'color' => '#ec4899'
        ),
        6 => array(
            'name' => 'Interpersonală',
            'slug' => 'interpersonala',
            'icon' => '👥',
            'color' => '#10b981'
        ),
        7 => array(
            'name' => 'Intrapersonală',
            'slug' => 'intrapersonala',
            'icon' => '🧘',
            'color' => '#6366f1'
        ),
        8 => array(
            'name' => 'Naturalistă',
            'slug' => 'naturalista',
            'icon' => '🌿',
            'color' => '#059669'
        )
    );
}

function sp_get_dominant_intelligence($user_id) {
    $types = sp_get_intelligence_types();
    $scores = array();
    
    $scores_array = get_user_meta($user_id, 'sp_intelligence_scores', true);
    
    if (!empty($scores_array) && is_array($scores_array)) {
        foreach ($scores_array as $id => $count) {
            $percentage = ($count / 10) * 100;
            $scores[$id] = floatval($percentage);
        }
    } else {
        foreach ($types as $id => $type) {
            $score = get_user_meta($user_id, 'sp_intelligence_' . $id . '_score', true);
            if ($score) {
                $scores[$id] = floatval($score);
            }
        }
    }
    
    if (empty($scores)) {
        return null;
    }
    
    arsort($scores);
    $dominant_id = key($scores);
    
    return array(
        'id' => $dominant_id,
        'name' => $types[$dominant_id]['name'],
        'slug' => $types[$dominant_id]['slug'],
        'icon' => $types[$dominant_id]['icon'],
        'color' => $types[$dominant_id]['color'],
        'score' => $scores[$dominant_id],
        'all_scores' => $scores
    );
}

function sp_get_top_intelligences($user_id, $limit = 3) {
    $types = sp_get_intelligence_types();
    $scores = array();
    
    $scores_array = get_user_meta($user_id, 'sp_intelligence_scores', true);
    
    if (!empty($scores_array) && is_array($scores_array)) {
        foreach ($scores_array as $id => $count) {
            if (isset($types[$id])) {
                $percentage = ($count / 10) * 100;
                $scores[$id] = array(
                    'name' => $types[$id]['name'],
                    'slug' => $types[$id]['slug'],
                    'score' => floatval($percentage),
                    'icon' => $types[$id]['icon'],
                    'color' => $types[$id]['color']
                );
            }
        }
    } else {
        foreach ($types as $id => $type) {
            $score = get_user_meta($user_id, 'sp_intelligence_' . $id . '_score', true);
            if ($score) {
                $scores[$id] = array(
                    'name' => $type['name'],
                    'slug' => $type['slug'],
                    'score' => floatval($score),
                    'icon' => $type['icon'],
                    'color' => $type['color']
                );
            }
        }
    }
    
    if (empty($scores)) {
        return array();
    }
    
    uasort($scores, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    
    return array_slice($scores, 0, $limit, true);
}

function sp_get_skill_color($index) {
    $colors = array(
        '#f97316', '#84cc16', '#a855f7', '#06b6d4', '#ef4444',
        '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#14b8a6',
    );
    return $colors[$index % count($colors)];
}

function sp_format_phone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($phone) == 10) {
        return substr($phone, 0, 4) . ' ' . substr($phone, 4, 3) . ' ' . substr($phone, 7);
    }
    
    return $phone;
}

function sp_get_user_age($user_id) {
    $birthdate = get_user_meta($user_id, 'psihoprofile_birthdate', true);
    
    if (!$birthdate) {
        $birthdate = get_user_meta($user_id, 'birth_date', true);
    }
    
    if (!$birthdate) {
        return null;
    }
    
    try {
        $birth = new DateTime($birthdate);
        $today = new DateTime('today');
        $age = $birth->diff($today)->y;
        return $age;
    } catch (Exception $e) {
        return null;
    }
}

function sp_truncate_text($text, $length = 150, $append = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $append;
}

/**
 * ENHANCED: Check if profile is complete by also checking actual data
 */
function sp_is_profile_complete($user_id) {
    global $wpdb;
    
    // Check test status
    $test_status = get_user_meta($user_id, 'sp_test_completed_status', true);
    $test_complete = ($test_status === 'completed');
    
    // Check CV - both status AND actual data
    $cv_status = get_user_meta($user_id, 'sp_cv_status', true);
    $cv_complete = ($cv_status === 'completed');
    
    // If status says not complete, check if there's actual CV data
    if (!$cv_complete) {
        $table_cv = $wpdb->prefix . 'sp_cv_fields';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_cv'") == $table_cv) {
            $cv_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_cv WHERE user_id = %d",
                $user_id
            ));
            $cv_complete = ($cv_count > 0);
        }
        
        // If still not found, check session for CV data
        if (!$cv_complete) {
            $table_session = $wpdb->prefix . 'sp_onboarding_sessions';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_session'") == $table_session) {
                $session = $wpdb->get_row($wpdb->prepare(
                    "SELECT cv_data FROM $table_session WHERE user_id = %d ORDER BY completed_at DESC LIMIT 1",
                    $user_id
                ));
                
                if ($session && !empty($session->cv_data)) {
                    $cv_data = json_decode($session->cv_data, true);
                    $cv_complete = !empty($cv_data);
                }
            }
        }
    }
    
    return ($test_complete && $cv_complete);
}

/**
 * ENHANCED: Get completion percentage with better CV detection
 */
function sp_get_completion_percentage($user_id) {
    global $wpdb;
    
    $total = 0;
    $completed = 0;
    
    // Test (40%)
    $total += 40;
    $test_status = get_user_meta($user_id, 'sp_test_completed_status', true);
    if ($test_status === 'completed') {
        $completed += 40;
    }
    
    // CV (40%) - Check BOTH status and actual data
    $total += 40;
    $cv_status = get_user_meta($user_id, 'sp_cv_status', true);
    $cv_complete = ($cv_status === 'completed');
    
    // If status not complete, check database
    if (!$cv_complete) {
        $table_cv = $wpdb->prefix . 'sp_cv_fields';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_cv'") == $table_cv) {
            $cv_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_cv WHERE user_id = %d",
                $user_id
            ));
            $cv_complete = ($cv_count > 0);
        }
        
        // If still not found, check session
        if (!$cv_complete) {
            $table_session = $wpdb->prefix . 'sp_onboarding_sessions';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_session'") == $table_session) {
                $session = $wpdb->get_row($wpdb->prepare(
                    "SELECT cv_data FROM $table_session WHERE user_id = %d ORDER BY completed_at DESC LIMIT 1",
                    $user_id
                ));
                
                if ($session && !empty($session->cv_data)) {
                    $cv_data = json_decode($session->cv_data, true);
                    $cv_complete = !empty($cv_data);
                }
            }
        }
    }
    
    if ($cv_complete) {
        $completed += 40;
    }
    
    // Soft skills (10%)
    $total += 10;
    $soft_skills = get_user_meta($user_id, 'sp_soft_skills', true);
    $ai_analysis = get_user_meta($user_id, 'sp_ai_analysis', true);
    
    if (!empty($soft_skills)) {
        $completed += 10;
    } elseif ($ai_analysis) {
        if (is_string($ai_analysis)) {
            $ai_analysis = json_decode($ai_analysis, true);
        }
        if (is_array($ai_analysis) && isset($ai_analysis['soft_skills']) && !empty($ai_analysis['soft_skills'])) {
            $completed += 10;
        }
    }
    
    // AI interpretation (10%)
    $total += 10;
    $interpretation = get_user_meta($user_id, 'sp_ai_profile_interpretation', true);
    
    if (!empty($interpretation)) {
        $completed += 10;
    } else {
        $ai_analysis = get_user_meta($user_id, 'sp_ai_analysis', true);
        if ($ai_analysis) {
            if (is_string($ai_analysis)) {
                $ai_analysis = json_decode($ai_analysis, true);
            }
            if (is_array($ai_analysis) && isset($ai_analysis['profile_description']) && !empty($ai_analysis['profile_description'])) {
                $completed += 10;
            }
        }
    }
    
    return round(($completed / $total) * 100);
}