<?php
/**
 * Professional Profile Template - FIXED VERSION 5.0
 * Proper UTF-8 encoding and comprehensive CV display
 */

if (!defined('ABSPATH')) exit;

if (!isset($profile_data) || !$profile_data) {
    echo '<p>Nu s-au putut înc?rca datele profilului.</p>';
    return;
}

$user_id = $profile_data['user_id'];
$basic = $profile_data['basic_info'];
$test = $profile_data['test_results'];
$cv = $profile_data['cv_data'];
$skills = $profile_data['soft_skills'];
$ai = $profile_data['ai_interpretation'];
$completion = $profile_data['completion'];
$session = $profile_data['session_data'];
$debug = $profile_data['debug'];

$is_own_profile = (get_current_user_id() === $user_id);
$is_admin = current_user_can('administrator');

// Show debug mode?
$show_debug = ($is_own_profile || $is_admin) && isset($_GET['debug']);

// Critical CSS
$inline_css = '
<style id="sp-profile-critical-css">
.sp-professional-profile-wrapper{font-family:"Raleway",-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;max-width:1200px;margin:0 auto;padding:20px}
.sp-profile-completion-badge{display:flex;align-items:center;gap:15px;background:linear-gradient(135deg,#0292B7 0%,#1AC8DB 100%);padding:15px 25px;border-radius:10px;margin-bottom:30px;color:white;box-shadow:0 4px 12px rgba(2,146,183,0.2);width:fit-content}
.sp-completion-circle{position:relative;width:80px;height:80px}
.sp-completion-circle svg{transform:rotate(-90deg)}
.sp-circle-bg{fill:none;stroke:rgba(255,255,255,0.2);stroke-width:6}
.sp-circle-progress{fill:none;stroke:white;stroke-width:6;stroke-linecap:round;stroke-dasharray:219.8;transition:stroke-dashoffset 1s ease}
.sp-percentage{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:20px;font-weight:700;color:white}
.sp-completion-label{font-size:14px;font-weight:600;color:white}
.sp-profile-grid{display:grid;grid-template-columns:1fr 350px;gap:30px}
.sp-profile-card{background:white;border-radius:12px;padding:25px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,0.08);border:1px solid #e5e7eb}
.sp-card-title{margin:0 0 20px 0;font-size:20px;font-weight:600;color:#1a1a1a;border-bottom:2px solid #C5EEF9;padding-bottom:10px}
.sp-sidebar-title{margin:0 0 15px 0;font-size:16px;font-weight:600;color:#0292B7}
.sp-specialization-main{font-size:24px;font-weight:700;color:#0292B7;line-height:1.3}
.sp-badges-container{display:flex;flex-wrap:wrap;gap:10px}
.sp-badge{display:inline-block;padding:8px 16px;border-radius:20px;font-size:14px;font-weight:600;white-space:nowrap}
.sp-badge-light{background:#e0f2fe;color:#0369a1}
.sp-badge-colored{color:white}
.sp-intelligence-dominant{display:flex;align-items:center;gap:15px;font-size:24px;font-weight:700;margin-bottom:15px}
.sp-intelligence-icon{font-size:48px}
.sp-intelligence-secondary{display:flex;gap:10px;flex-wrap:wrap;margin-top:15px}
.sp-btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;padding:12px 24px;border-radius:10px;font-size:15px;font-weight:700;border:none;cursor:pointer;transition:all 0.3s ease;text-decoration:none}
.sp-btn-download{width:100%;background:linear-gradient(135deg,#0292B7 0%,#1AC8DB 100%);color:white;box-shadow:0 4px 12px rgba(2,146,183,0.3)}
.sp-btn-download:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(2,146,183,0.4)}
.sp-contact-info{display:flex;flex-direction:column;gap:12px}
.sp-contact-item{display:flex;align-items:center;gap:10px;font-size:14px;color:#4a5568}
.sp-intelligence-bars{display:flex;flex-direction:column;gap:15px}
.sp-bar-label{display:flex;justify-content:space-between;font-size:13px;font-weight:600;color:#4a5568}
.sp-bar-track{height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden}
.sp-bar-fill{height:100%;border-radius:4px;transition:width 1s ease}
.sp-notice{display:flex;align-items:center;gap:15px;padding:20px;border-radius:10px;margin-bottom:30px}
.sp-notice-warning{background:#fff3cd;border-left:4px solid #ffc107}
.sp-notice-info{background:#e0f2fe;border-left:4px solid #0292B7}
.sp-notice-debug{background:#f3f4f6;border-left:4px solid #6366f1;font-family:monospace;font-size:12px}
.sp-card-highlighted{background:linear-gradient(135deg,#d1f5d3 0%,#a7f3d0 100%);border:none}
.sp-experience-list{display:flex;flex-direction:column;gap:12px}
.sp-experience-item{padding:12px;background:#f8f9fa;border-radius:8px;border-left:3px solid #0292B7;font-size:14px}
.sp-cv-field-section{margin-bottom:20px}
.sp-cv-field-label{font-size:16px;font-weight:600;color:#0292B7;margin:0 0 10px 0}
.sp-cv-field-value{padding:10px;background:#f8f9fa;border-radius:6px;margin-bottom:8px;font-size:14px;line-height:1.6;white-space:pre-wrap}
.sp-debug-toggle{position:fixed;bottom:20px;right:20px;background:#6366f1;color:white;padding:12px 20px;border-radius:8px;cursor:pointer;z-index:999;box-shadow:0 4px 12px rgba(99,102,241,0.4)}
.sp-ai-text{font-size:15px;line-height:1.7;color:#4a5568;white-space:pre-wrap}
@media (max-width:968px){.sp-profile-grid{grid-template-columns:1fr}.sp-profile-right{order:-1}}
@media (max-width:768px){.sp-profile-card{padding:20px}.sp-card-title{font-size:18px}.sp-specialization-main{font-size:20px}}
</style>
';
echo $inline_css;
?>

<div class="sp-professional-profile-wrapper">
    
    <!-- Debug Toggle -->
    <?php if ($is_own_profile || $is_admin): ?>
    <a href="<?php echo add_query_arg('debug', $show_debug ? '0' : '1'); ?>" class="sp-debug-toggle">
        <?php echo $show_debug ? '? Ascunde Debug' : '? Arat? Debug'; ?>
    </a>
    <?php endif; ?>
    
    <!-- Debug Info -->
    <?php if ($show_debug): ?>
    <div class="sp-notice sp-notice-debug">
        <div style="flex:1">
            <strong style="color:#6366f1">? MOD DEBUG</strong>
            <pre style="margin:10px 0;white-space:pre-wrap;font-size:11px"><?php
echo "User ID: {$user_id}\n";
echo "Test Status: " . ($debug['test_status'] ?: 'NOT SET') . "\n";
echo "CV Status: " . ($debug['cv_status'] ?: 'NOT SET') . "\n";
echo "CV Entries in Table: " . ($debug['cv_entries_count'] ?? 0) . "\n\n";

echo "--- SESSION DATA ---\n";
echo "Has Session: " . ($debug['has_session'] ? 'YES (ID: ' . ($debug['session_id'] ?? '?') . ')' : 'NO') . "\n";
if ($debug['has_session']) {
    echo "Session Completed: " . ($debug['session_completed'] ? 'YES' : 'NO') . "\n";
    echo "Session Step: " . ($debug['session_step'] ?? 'unknown') . "\n";
    if (isset($debug['session_has_cv'])) {
        echo "Session Has CV Data: " . ($debug['session_has_cv'] ? 'YES ?' : 'NO') . "\n";
        if ($debug['session_has_cv']) {
            echo "Session CV Fields: " . ($debug['session_cv_fields'] ?? 0) . "\n";
            if (isset($debug['session_cv_field_names'])) {
                echo "Field Names: " . $debug['session_cv_field_names'] . "\n";
            }
            if (isset($debug['session_cv_preview'])) {
                echo "Data Preview: " . $debug['session_cv_preview'] . "...\n";
            }
        }
        if (isset($debug['session_cv_error'])) {
            echo "CV Error: " . $debug['session_cv_error'] . "\n";
        }
    }
}

echo "\n--- LOADED DATA ---\n";
echo "CV Object Loaded: " . ($cv ? 'YES ?' : 'NO ?') . "\n";
if ($cv) {
    echo "CV Status: " . ($cv['status'] ?? 'UNKNOWN') . "\n";
    echo "CV Fields: " . count($cv['fields'] ?? array()) . "\n";
    echo "CV Entries: " . count($cv['entries'] ?? array()) . "\n";
    if (!empty($cv['fields'])) {
        echo "Field Labels: ";
        $labels = array();
        foreach ($cv['fields'] as $field) {
            $labels[] = $field['field_label'];
        }
        echo implode(', ', $labels) . "\n";
    }
}
echo "Test Results: " . ($test ? 'YES ?' : 'NO') . "\n";
echo "Soft Skills: " . count($skills) . "\n";
echo "AI Interpretation: " . ($ai ? 'YES ?' : 'NO') . "\n";
            ?></pre>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Completion Badge -->
    <div class="sp-profile-completion-badge">
        <div class="sp-completion-circle" data-percentage="<?php echo $completion; ?>">
            <svg width="80" height="80">
                <circle class="sp-circle-bg" cx="40" cy="40" r="35"></circle>
                <circle class="sp-circle-progress" cx="40" cy="40" r="35" 
                        style="stroke-dashoffset: <?php echo 219.8 - (219.8 * $completion / 100); ?>;"></circle>
            </svg>
            <div class="sp-percentage"><?php echo $completion; ?>%</div>
        </div>
        <span class="sp-completion-label">Profil Complet</span>
    </div>

    <?php if (!sp_is_profile_complete($user_id)): ?>
    <div class="sp-notice sp-notice-warning">
        <div style="font-size:32px">??</div>
        <div style="flex:1">
            <strong>Profil Incomplet</strong>
            <p style="margin:5px 0 0 0">
                <?php
                $missing = array();
                if ($debug['test_status'] !== 'completed') $missing[] = 'testul voca?ional';
                if (empty($cv) || ($debug['cv_entries_count'] ?? 0) == 0) $missing[] = 'CV-ul';
                echo 'Completeaz? ' . implode(' ?i ', $missing) . ' pentru a debloca toate func?ionalit??ile.';
                ?>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <div class="sp-profile-grid">
        
        <!-- Left Column -->
        <div class="sp-profile-left">
            
            <!-- Specialization -->
            <?php if ($cv && $cv['specialization']): ?>
            <div class="sp-profile-card sp-card-highlighted">
                <div class="sp-specialization-main"><?php echo esc_html($cv['specialization']); ?></div>
                <?php if ($test && $test['dominant']): ?>
                <p style="margin:10px 0 0 0;font-size:14px;color:#64748b">
                    Bazat pe inteligen?a ta dominant?: <strong><?php echo esc_html($test['dominant']['name']); ?></strong>
                </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Dominant Intelligence -->
            <?php if ($test && $test['dominant']): ?>
            <div class="sp-profile-card">
                <h2 class="sp-card-title">Inteligen?? Dominant?</h2>
                <div class="sp-intelligence-dominant">
                    <span class="sp-intelligence-icon"><?php echo $test['dominant']['icon']; ?></span>
                    <div style="flex:1">
                        <div><?php echo esc_html($test['dominant']['name']); ?></div>
                        <div style="font-size:20px;color:#64748b;font-weight:normal">
                            <?php echo number_format($test['dominant']['score'], 1); ?>%
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($test['top_three']) && count($test['top_three']) > 1): ?>
                <div class="sp-intelligence-secondary">
                    <strong style="width:100%;font-size:14px;color:#64748b">Top 3 Inteligen?e:</strong>
                    <?php 
                    $top_three = array_slice($test['top_three'], 0, 3, true);
                    foreach ($top_three as $intel): 
                    ?>
                    <span class="sp-badge sp-badge-colored" style="background-color:<?php echo $intel['color']; ?>">
                        <?php echo $intel['icon']; ?> <?php echo esc_html($intel['name']); ?> (<?php echo number_format($intel['score'], 0); ?>%)
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- AI Interpretation -->
            <?php if ($ai && $ai['text']): ?>
            <div class="sp-profile-card">
                <h2 class="sp-card-title">Interpretare AI</h2>
                <div class="sp-ai-text"><?php echo esc_html($ai['text']); ?></div>
                
                <?php if ($ai['learning_style']): ?>
                <div style="margin-top:20px;padding:15px;background:#f0f9ff;border-radius:8px;border-left:3px solid #0284c7">
                    <strong style="color:#0284c7;display:block;margin-bottom:8px">? Stilul t?u de înv??are:</strong>
                    <div style="font-size:14px;line-height:1.6"><?php echo esc_html($ai['learning_style']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ($ai['work_environment']): ?>
                <div style="margin-top:15px;padding:15px;background:#f0fdf4;border-radius:8px;border-left:3px solid #10b981">
                    <strong style="color:#10b981;display:block;margin-bottom:8px">? Mediul ideal de lucru:</strong>
                    <div style="font-size:14px;line-height:1.6"><?php echo esc_html($ai['work_environment']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ($ai['generated_at']): ?>
                <p style="margin-top:15px;font-size:12px;color:#94a3b8;font-style:italic">
                    Generat de AI pe <?php echo date('d.m.Y', strtotime($ai['generated_at'])); ?>
                </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Soft Skills -->
            <?php if (!empty($skills)): ?>
            <div class="sp-profile-card">
                <h2 class="sp-card-title">Soft Skills</h2>
                <div class="sp-badges-container">
                    <?php foreach ($skills as $index => $skill): ?>
                    <span class="sp-badge sp-badge-colored" style="background-color:<?php echo sp_get_skill_color($index); ?>">
                        <?php echo esc_html($skill); ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- CV Data - FIXED: Proper display of all fields -->
            <?php if ($cv && !empty($cv['entries'])): ?>
            <div class="sp-profile-card">
                <h2 class="sp-card-title">Date Complete CV</h2>
                <?php foreach ($cv['entries'] as $field_id => $entries): 
                    $field_info = $cv['fields'][$field_id] ?? null;
                    if (!$field_info) continue;
                ?>
                <div class="sp-cv-field-section">
                    <h3 class="sp-cv-field-label"><?php echo esc_html($field_info['field_label']); ?></h3>
                    <?php foreach ($entries as $entry): ?>
                    <div class="sp-cv-field-value">
                        <?php echo nl2br(esc_html($entry['field_value'])); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php elseif ($cv && empty($cv['entries']) && $show_debug): ?>
            <div class="sp-notice sp-notice-warning">
                <div>??</div>
                <div>
                    <strong>DEBUG:</strong> CV object exists but entries are empty. 
                    Check if data is in session: <?php echo $debug['session_has_cv'] ? 'YES' : 'NO'; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Experience -->
            <?php if ($cv && !empty($cv['experience'])): ?>
            <div class="sp-profile-card">
                <h2 class="sp-card-title">Experien?? Profesional?</h2>
                <div class="sp-experience-list">
                    <?php foreach ($cv['experience'] as $exp): ?>
                    <div class="sp-experience-item">
                        <?php echo nl2br(esc_html($exp['field_value'])); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Education -->
            <?php if ($cv && !empty($cv['education'])): ?>
            <div class="sp-profile-card">
                <h2 class="sp-card-title">Educa?ie</h2>
                <div class="sp-experience-list">
                    <?php foreach ($cv['education'] as $edu): ?>
                    <div class="sp-experience-item">
                        <?php echo nl2br(esc_html($edu['field_value'])); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Additional Skills from CV -->
            <?php if ($cv && !empty($cv['skills'])): ?>
            <div class="sp-profile-card">
                <h2 class="sp-card-title">Competen?e Tehnice</h2>
                <div class="sp-experience-list">
                    <?php foreach ($cv['skills'] as $skill): ?>
                    <div class="sp-experience-item">
                        <?php echo nl2br(esc_html($skill['field_value'])); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Languages from CV -->
            <?php if ($cv && !empty($cv['languages'])): ?>
            <div class="sp-profile-card">
                <h2 class="sp-card-title">Limbi Str?ine</h2>
                <div class="sp-experience-list">
                    <?php foreach ($cv['languages'] as $lang): ?>
                    <div class="sp-experience-item">
                        <?php echo nl2br(esc_html($lang['field_value'])); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Download CV -->
            <?php if ($cv && sp_is_profile_complete($user_id)): ?>
            <div class="sp-profile-card">
                <button class="sp-btn sp-btn-download sp-download-cv" data-user-id="<?php echo $user_id; ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="12" y1="18" x2="12" y2="12"/>
                        <line x1="9" y1="15" x2="12" y2="18"/>
                        <line x1="15" y1="15" x2="12" y2="18"/>
                    </svg>
                    DESCARC? CV
                </button>
            </div>
            <?php endif; ?>
            
        </div>
        
        <!-- Right Sidebar -->
        <div class="sp-profile-right">
            
            <!-- Contact -->
            <div class="sp-profile-card" style="background:#f8f9fa">
                <h3 class="sp-sidebar-title">Contact</h3>
                <div class="sp-contact-info">
                    <?php if ($basic['email']): ?>
                    <div class="sp-contact-item">
                        <i class="dashicons dashicons-email" style="color:#0292B7"></i>
                        <a href="mailto:<?php echo esc_attr($basic['email']); ?>" style="color:#0292B7;text-decoration:none">
                            <?php echo esc_html($basic['email']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($basic['phone']): ?>
                    <div class="sp-contact-item">
                        <i class="dashicons dashicons-phone" style="color:#0292B7"></i>
                        <span><?php echo esc_html(sp_format_phone($basic['phone'])); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($basic['city']): ?>
                    <div class="sp-contact-item">
                        <i class="dashicons dashicons-location-alt" style="color:#0292B7"></i>
                        <span><?php echo esc_html($basic['city']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($basic['age']): ?>
                    <div class="sp-contact-item">
                        <i class="dashicons dashicons-calendar-alt" style="color:#0292B7"></i>
                        <span><?php echo esc_html($basic['age']); ?> ani</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($basic['sex']): ?>
                    <div class="sp-contact-item">
                        <i class="dashicons dashicons-admin-users" style="color:#0292B7"></i>
                        <span><?php echo $basic['sex'] === 'M' ? 'Masculin' : 'Feminin'; ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Intelligence Scores -->
            <?php if ($test && !empty($test['scores'])): ?>
            <div class="sp-profile-card" style="background:#f8f9fa">
                <h3 class="sp-sidebar-title">Scoruri Inteligen?e</h3>
                <div class="sp-intelligence-bars">
                    <?php foreach ($test['scores'] as $intel): ?>
                    <div class="sp-intelligence-bar">
                        <div class="sp-bar-label">
                            <span><?php echo $intel['icon']; ?> <?php echo esc_html($intel['name']); ?></span>
                            <span><?php echo number_format($intel['score'], 0); ?>%</span>
                        </div>
                        <div class="sp-bar-track">
                            <div class="sp-bar-fill" 
                                 style="width: <?php echo $intel['score']; ?>%; background-color: <?php echo $intel['color']; ?>;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
        
    </div>
    
</div>