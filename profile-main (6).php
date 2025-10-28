<?php
/**
 * Professional Profile Template - Clean Business View v6.2
 * Removed debug, moved completion badge, added negation sidebar display
 */

if (!defined('ABSPATH')) exit;

// Force UTF-8 output
header('Content-Type: text/html; charset=UTF-8');

if (!isset($profile_data) || !$profile_data) {
    echo '<p>Nu s-au putut încărca datele profilului.</p>';
    return;
}

$user_id = $profile_data['user_id'];
$basic = $profile_data['basic_info'];
$test = $profile_data['test_results'];
$cv = $profile_data['cv_data'];
$negations = $profile_data['cv_negations'];
$skills = $profile_data['soft_skills'];
$ai = $profile_data['ai_interpretation'];
$completion = $profile_data['completion'];

$is_own_profile = (get_current_user_id() === $user_id);

// Clean Professional CSS
$inline_css = '
<style id="sp-profile-clean-css">
.sp-professional-profile-wrapper{font-family:"Raleway",-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;max-width:1200px;margin:0 auto;padding:20px;background:#fff}
.sp-professional-profile-wrapper *:not(.dashicons){font-family:"Raleway",-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
.sp-profile-grid{display:grid;grid-template-columns:1fr 350px;gap:30px;margin-top:20px}
.sp-profile-card{background:white;border-radius:12px;padding:25px;margin-bottom:20px;border:1px solid #e5e7eb;box-shadow:none!important}
.sp-card-title{margin:0 0 20px 0;font-size:20px;font-weight:600;color:#1a1a1a;border-bottom:2px solid #C5EEF9;padding-bottom:10px;display:flex;align-items:center;gap:8px}
.sp-card-title .dashicons{color:#0292B7;font-size:24px;width:24px;height:24px}
.sp-sidebar-title{margin:0 0 15px 0;font-size:16px;font-weight:600;color:#0292B7;display:flex;align-items:center;gap:6px}
.sp-sidebar-title .dashicons{font-size:20px;width:20px;height:20px}
.sp-specialization-main{font-size:24px;font-weight:700;color:#0292B7;line-height:1.3;padding:20px;background:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 100%);border-radius:10px;margin-bottom:20px}
.sp-skills-grid{display:flex;flex-wrap:wrap;gap:12px}
.sp-skill-badge{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:20px;color:white;font-size:14px;font-weight:600;white-space:nowrap}
.sp-skill-name{font-size:14px}
.sp-skill-points{background:rgba(0,0,0,0.15);padding:3px 8px;border-radius:10px;font-size:12px;font-weight:700}
.sp-intelligence-dominant{display:flex;align-items:center;gap:15px;font-size:24px;font-weight:700;margin-bottom:15px;padding:25px;background:linear-gradient(135deg,#0292B7 0%,#1AC8DB 100%);border-radius:12px;color:white}
.sp-intelligence-icon-wrapper{width:80px;height:80px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.sp-intelligence-emoji{font-size:48px;line-height:1}
.sp-intelligence-secondary{display:flex;gap:10px;flex-wrap:wrap;margin-top:15px}
.sp-badge{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:20px;font-size:14px;font-weight:600;white-space:nowrap;color:white}
.sp-btn-download{width:100%;background:linear-gradient(135deg,#0292B7 0%,#1AC8DB 100%);color:white;border:none;padding:15px 24px;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;transition:all 0.3s ease;display:inline-flex;align-items:center;justify-content:center;gap:10px}
.sp-btn-download:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(2,146,183,0.3)}
.sp-btn-download .dashicons{font-size:20px;width:20px;height:20px}
.sp-contact-info{display:flex;flex-direction:column;gap:12px}
.sp-contact-item{display:flex;align-items:center;gap:10px;font-size:14px;color:#4a5568}
.sp-contact-item .dashicons{color:#0292B7;font-size:18px;width:18px;height:18px}
.sp-intelligence-bars{display:flex;flex-direction:column;gap:15px}
.sp-bar-label{display:flex;justify-content:space-between;font-size:13px;font-weight:600;color:#4a5568;align-items:center}
.sp-bar-track{height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden}
.sp-bar-fill{height:100%;border-radius:4px;transition:width 1s ease}
.sp-cv-section{margin-bottom:25px}
.sp-cv-field-label{font-size:16px;font-weight:700;color:#0292B7;margin:0 0 12px 0}
.sp-cv-field-value{padding:15px;background:#f8f9fa;border-radius:8px;margin-bottom:10px;font-size:14px;line-height:1.7;color:#374151;border-left:3px solid #0292B7}
.sp-negation-notice{display:flex;align-items:center;gap:12px;padding:12px 16px;background:#fef3c7;border-left:3px solid #f59e0b;border-radius:6px;margin-bottom:10px}
.sp-negation-notice .dashicons{color:#f59e0b;font-size:20px;width:20px;height:20px}
.sp-negation-text{font-size:14px;color:#92400e;font-weight:500}
.sp-ai-text{font-size:15px;line-height:1.7;color:#4a5568;white-space:pre-wrap}
.sp-ai-meta-box{margin-top:20px;padding:15px;background:#f0f9ff;border-radius:8px;border-left:3px solid #0284c7}
.sp-ai-meta-title{font-size:13px;font-weight:700;color:#0284c7;margin-bottom:8px;display:flex;align-items:center;gap:6px}
.sp-ai-meta-title .dashicons{font-size:16px;width:16px;height:16px}
.sp-ai-meta-value{font-size:14px;color:#4a5568;line-height:1.6}
.sp-negations-sidebar{background:#fff9e6;border-radius:10px;padding:20px;margin-bottom:20px;border:1px solid #fde68a}
.sp-negations-list{display:flex;flex-direction:column;gap:10px;margin-top:12px}
.sp-negation-item{display:flex;align-items:center;gap:8px;font-size:13px;color:#92400e;padding:8px 12px;background:#fef3c7;border-radius:6px}
.sp-negation-item .dashicons{color:#f59e0b;font-size:16px;width:16px;height:16px;flex-shrink:0}
.sp-profile-completion-badge{display:flex;align-items:center;gap:15px;background:linear-gradient(135deg,#fbbf24 0%,#f59e0b 100%);padding:15px 20px;border-radius:10px;margin-bottom:20px;color:white}
.sp-completion-circle{position:relative;width:60px;height:60px}
.sp-completion-circle svg{transform:rotate(-90deg)}
.sp-circle-bg{fill:none;stroke:rgba(255,255,255,0.3);stroke-width:5}
.sp-circle-progress{fill:none;stroke:white;stroke-width:5;stroke-linecap:round;stroke-dasharray:164.85;transition:stroke-dashoffset 1s ease}
.sp-percentage{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:16px;font-weight:700;color:white}
.sp-completion-label{font-size:13px;font-weight:600;color:white;line-height:1.4}
@media (max-width:968px){.sp-profile-grid{grid-template-columns:1fr}.sp-profile-right{order:-1}}
@media (max-width:768px){.sp-profile-card{padding:20px}.sp-card-title{font-size:18px}.sp-specialization-main{font-size:20px}}
</style>
';
echo $inline_css;
?>

<div class="sp-professional-profile-wrapper">

    <div class="sp-profile-grid">

        <!-- Left Column -->
        <div class="sp-profile-left">

            <!-- Specialization -->
            <?php if ($cv && $cv['specialization']): ?>
            <div class="sp-specialization-main">
                <?php echo esc_html($cv['specialization']); ?>
                <?php if ($test && $test['dominant']): ?>
                <div style="font-size:14px;color:#64748b;font-weight:normal;margin-top:8px">
                    Bazat pe inteligența ta dominantă: <strong><?php echo esc_html($test['dominant']['name']); ?></strong>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Dominant Intelligence -->
            <?php if ($test && $test['dominant']): ?>
            <div class="sp-profile-card">
                <h2 class="sp-card-title">
                    <span class="dashicons dashicons-star-filled"></span>
                    Inteligență Dominantă
                </h2>
                <div class="sp-intelligence-dominant">
                    <div class="sp-intelligence-icon-wrapper">
                        <span class="sp-intelligence-emoji"><?php echo $test['dominant']['icon']; ?></span>
                    </div>
                    <div style="flex:1">
                        <div style="color:white"><?php echo esc_html($test['dominant']['name']); ?></div>
                        <div style="font-size:20px;color:rgba(255,255,255,0.9);font-weight:normal">
                            <?php echo number_format($test['dominant']['score'], 1); ?>%
                        </div>
                    </div>
                </div>

                <?php if (!empty($test['top_three']) && count($test['top_three']) > 1): ?>
                <div class="sp-intelligence-secondary">
                    <strong style="width:100%;font-size:14px;color:#64748b">Top 3 Inteligențe:</strong>
                    <?php
                    $top_three = array_slice($test['top_three'], 0, 3, true);
                    foreach ($top_three as $intel):
                    ?>
                    <span class="sp-badge" style="background-color:<?php echo $intel['color']; ?>">
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
                <h2 class="sp-card-title">
                    <span class="dashicons dashicons-lightbulb"></span>
                    Interpretare AI
                </h2>
                <div class="sp-ai-text"><?php echo nl2br(esc_html($ai['text'])); ?></div>

                <?php if ($ai['learning_style']): ?>
                <div class="sp-ai-meta-box">
                    <div class="sp-ai-meta-title">
                        <span class="dashicons dashicons-welcome-learn-more"></span>
                        Stilul tău de învățare
                    </div>
                    <div class="sp-ai-meta-value"><?php echo esc_html($ai['learning_style']); ?></div>
                </div>
                <?php endif; ?>

                <?php if ($ai['work_environment']): ?>
                <div class="sp-ai-meta-box" style="background:#f0fdf4;border-left-color:#10b981;margin-top:15px">
                    <div class="sp-ai-meta-title" style="color:#10b981">
                        <span class="dashicons dashicons-building"></span>
                        Mediul ideal de lucru
                    </div>
                    <div class="sp-ai-meta-value"><?php echo esc_html($ai['work_environment']); ?></div>
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
                <h2 class="sp-card-title">
                    <span class="dashicons dashicons-awards"></span>
                    Soft Skills
                </h2>
                <div class="sp-skills-grid">
                    <?php foreach ($skills as $skill_data):
                        $skill_name = is_array($skill_data) ? $skill_data['skill'] : $skill_data;
                        $skill_points = is_array($skill_data) && isset($skill_data['points']) ? intval($skill_data['points']) : 5;
                        $skill_color = is_array($skill_data) && isset($skill_data['color']) ? $skill_data['color'] : '#0292B7';
                    ?>
                    <div class="sp-skill-badge" style="background-color:<?php echo esc_attr($skill_color); ?>">
                        <span class="sp-skill-name"><?php echo esc_html($skill_name); ?></span>
                        <span class="sp-skill-points"><?php echo $skill_points; ?> pt</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- CV Data -->
            <?php if ($cv && !empty($cv['entries'])): ?>
            <div class="sp-profile-card">
                <h2 class="sp-card-title">
                    <span class="dashicons dashicons-media-document"></span>
                    Date Complete
                </h2>
                <?php foreach ($cv['entries'] as $field_id => $entries):
                    $field_info = $cv['fields'][$field_id] ?? null;
                    if (!$field_info) continue;

                    // Check if this field is negated
                    $is_negated = isset($negations[$field_info['field_name']]) && $negations[$field_info['field_name']]['is_negated'];
                ?>
                <div class="sp-cv-section">
                    <h3 class="sp-cv-field-label">
                        <?php echo esc_html($field_info['field_label']); ?>
                    </h3>
                    <?php if ($is_negated): ?>
                    <div class="sp-negation-notice">
                        <span class="dashicons dashicons-dismiss"></span>
                        <span class="sp-negation-text">Nu a completat această secțiune</span>
                    </div>
                    <?php else: ?>
                        <?php foreach ($entries as $entry): ?>
                        <div class="sp-cv-field-value">
                            <?php echo nl2br(esc_html($entry['field_value'])); ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <?php
                // Display standalone negations
                if (!empty($negations)):
                    foreach ($negations as $negation):
                        $already_shown = false;
                        if ($cv && !empty($cv['fields'])) {
                            foreach ($cv['fields'] as $field) {
                                if ($field['field_name'] === $negation['field_name']) {
                                    $already_shown = true;
                                    break;
                                }
                            }
                        }
                        if (!$already_shown):
                ?>
                <div class="sp-cv-section">
                    <h3 class="sp-cv-field-label">
                        <?php echo esc_html($negation['field_label']); ?>
                    </h3>
                    <div class="sp-negation-notice">
                        <span class="dashicons dashicons-dismiss"></span>
                        <span class="sp-negation-text">Nu a completat această secțiune</span>
                    </div>
                </div>
                <?php
                        endif;
                    endforeach;
                endif;
                ?>
            </div>
            <?php elseif (!empty($negations)): ?>
            <div class="sp-profile-card">
                <h2 class="sp-card-title">
                    <span class="dashicons dashicons-media-document"></span>
                    Date Complete
                </h2>
                <?php foreach ($negations as $negation): ?>
                <div class="sp-cv-section">
                    <h3 class="sp-cv-field-label">
                        <?php echo esc_html($negation['field_label']); ?>
                    </h3>
                    <div class="sp-negation-notice">
                        <span class="dashicons dashicons-dismiss"></span>
                        <span class="sp-negation-text">Nu a completat această secțiune</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Download CV -->
            <?php if ($cv && sp_is_profile_complete($user_id)): ?>
            <div class="sp-profile-card">
                <button class="sp-btn-download sp-download-cv" data-user-id="<?php echo $user_id; ?>">
                    <span class="dashicons dashicons-download"></span>
                    DESCARCĂ CV
                </button>
            </div>
            <?php endif; ?>

        </div>

        <!-- Right Sidebar -->
        <div class="sp-profile-right">

            <!-- Contact -->
            <div class="sp-profile-card" style="background:#f8f9fa">
                <h3 class="sp-sidebar-title">
                    <span class="dashicons dashicons-phone"></span>
                    Contact
                </h3>
                <div class="sp-contact-info">
                    <?php if ($basic['email']): ?>
                    <div class="sp-contact-item">
                        <span class="dashicons dashicons-email"></span>
                        <a href="mailto:<?php echo esc_attr($basic['email']); ?>" style="color:#0292B7;text-decoration:none">
                            <?php echo esc_html($basic['email']); ?>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if ($basic['phone']): ?>
                    <div class="sp-contact-item">
                        <span class="dashicons dashicons-smartphone"></span>
                        <span><?php echo esc_html(sp_format_phone($basic['phone'])); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($basic['city']): ?>
                    <div class="sp-contact-item">
                        <span class="dashicons dashicons-location"></span>
                        <span><?php echo esc_html($basic['city']); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($basic['age']): ?>
                    <div class="sp-contact-item">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <span><?php echo esc_html($basic['age']); ?> ani</span>
                    </div>
                    <?php endif; ?>

                    <?php if ($basic['sex']): ?>
                    <div class="sp-contact-item">
                        <span class="dashicons dashicons-admin-users"></span>
                        <span><?php echo $basic['sex'] === 'M' ? 'Masculin' : 'Feminin'; ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Intelligence Scores -->
            <?php if ($test && !empty($test['scores'])): ?>
            <div class="sp-profile-card" style="background:#f8f9fa">
                <h3 class="sp-sidebar-title">
                    <span class="dashicons dashicons-chart-bar"></span>
                    Scoruri Inteligențe
                </h3>
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

            <!-- Completion Badge (only if not 100%) -->
            <?php if ($completion < 100): ?>
            <div class="sp-profile-completion-badge">
                <div class="sp-completion-circle" data-percentage="<?php echo $completion; ?>">
                    <svg width="60" height="60">
                        <circle class="sp-circle-bg" cx="30" cy="30" r="26"></circle>
                        <circle class="sp-circle-progress" cx="30" cy="30" r="26"
                                style="stroke-dashoffset: <?php echo 164.85 - (164.85 * $completion / 100); ?>;"></circle>
                    </svg>
                    <div class="sp-percentage"><?php echo $completion; ?>%</div>
                </div>
                <div style="flex:1">
                    <div class="sp-completion-label">Profil<br>Incomplet</div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Negations List -->
            <?php
            // DEBUG: Show negation count
            if ($is_own_profile) {
                echo '<!-- DEBUG: Negations count: ' . count($negations) . ' -->';
                if (!empty($negations)) {
                    echo '<!-- DEBUG: Negation fields: ' . implode(', ', array_keys($negations)) . ' -->';
                }
            }
            ?>
            <?php if (!empty($negations)): ?>
            <div class="sp-negations-sidebar">
                <h3 class="sp-sidebar-title" style="margin:0 0 12px 0">
                    <span class="dashicons dashicons-info"></span>
                    Secțiuni Nesemnate
                </h3>
                <div class="sp-negations-list">
                    <?php foreach ($negations as $negation): ?>
                    <div class="sp-negation-item">
                        <span class="dashicons dashicons-dismiss"></span>
                        <span><?php echo esc_html($negation['field_label']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>

    </div>

</div>
