<?php
/**
 * Professional Profile Template - Clean Business View v6.4
 * Integrated onboarding plugin design system with CSS variables and animations
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

// Professional Profile CSS with Onboarding Design System
$inline_css = '
<style id="sp-profile-clean-css">
@import url("https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;600;700&display=swap");

/* CSS Variables - Onboarding Design System */
:root {
    --sp-primary: #0292B7;
    --sp-secondary: #1AC8DB;
    --sp-accent: #C5EEF9;
    --sp-dark: #1a1a1a;
    --sp-gray: #666;
    --sp-light-gray: #f9fafb;
    --sp-border: #e5e7eb;
    --sp-white: #ffffff;
    --sp-success: #10b981;
    --sp-error: #ef4444;
    --sp-warning: #f59e0b;
    --sp-shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
    --sp-shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
    --sp-shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.12);
    --sp-radius: 12px;
    --sp-radius-sm: 8px;
    --sp-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Wrapper */
.sp-professional-profile-wrapper{
    font-family:"Raleway",-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
    max-width:1200px;
    margin:0 auto;
    padding:20px;
    background:var(--sp-white);
}
.sp-professional-profile-wrapper *:not(.dashicons){
    font-family:"Raleway",-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
}

/* Grid Layout */
.sp-profile-grid{
    display:grid;
    grid-template-columns:1fr 350px;
    gap:30px;
    margin-top:20px;
}

/* Cards with Onboarding Styling */
.sp-profile-card{
    background:var(--sp-white);
    border-radius:var(--sp-radius);
    padding:25px;
    margin-bottom:20px;
    border:1px solid var(--sp-border);
    box-shadow:var(--sp-shadow-md);
    transition:var(--sp-transition);
    animation:fadeInUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.sp-profile-card:hover{
    box-shadow:var(--sp-shadow-lg);
    transform:translateY(-2px);
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Card Titles */
.sp-card-title{
    margin:0 0 20px 0;
    font-size:20px;
    font-weight:700;
    color:var(--sp-dark);
    border-bottom:2px solid var(--sp-accent);
    padding-bottom:10px;
    display:flex;
    align-items:center;
    gap:8px;
}
.sp-card-title .dashicons{
    color:var(--sp-primary);
    font-size:24px;
    width:24px;
    height:24px;
}

/* Sidebar Titles */
.sp-sidebar-title{
    margin:0 0 15px 0;
    font-size:16px;
    font-weight:700;
    color:var(--sp-primary);
    display:flex;
    align-items:center;
    gap:6px;
}
.sp-sidebar-title .dashicons{
    font-size:20px;
    width:20px;
    height:20px;
}

/* Specialization Header */
.sp-specialization-main{
    font-size:24px;
    font-weight:700;
    color:var(--sp-primary);
    line-height:1.3;
    padding:25px;
    background:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 100%);
    border-radius:var(--sp-radius);
    margin-bottom:20px;
    box-shadow:var(--sp-shadow-sm);
    animation:fadeInUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Skills Grid */
.sp-skills-grid{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
}
.sp-skill-badge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 16px;
    border-radius:20px;
    color:white;
    font-size:14px;
    font-weight:600;
    white-space:nowrap;
    transition:var(--sp-transition);
}
.sp-skill-badge:hover{
    transform:translateY(-2px);
    box-shadow:var(--sp-shadow-md);
}
.sp-skill-name{font-size:14px;}
.sp-skill-points{
    background:rgba(0,0,0,0.15);
    padding:3px 8px;
    border-radius:10px;
    font-size:12px;
    font-weight:700;
}

/* Intelligence - Dominant */
.sp-intelligence-dominant{
    display:flex;
    align-items:center;
    gap:15px;
    font-size:24px;
    font-weight:700;
    margin-bottom:15px;
    padding:25px;
    background:linear-gradient(135deg,var(--sp-primary) 0%,var(--sp-secondary) 100%);
    border-radius:var(--sp-radius);
    color:white;
    box-shadow:var(--sp-shadow-md);
    transition:var(--sp-transition);
}
.sp-intelligence-dominant:hover{
    box-shadow:0 6px 18px rgba(2, 146, 183, 0.25);
    transform:translateY(-2px);
}
.sp-intelligence-icon-wrapper{
    width:80px;
    height:80px;
    background:rgba(255,255,255,0.2);
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
}
.sp-intelligence-emoji{
    font-size:48px;
    line-height:1;
}

/* Intelligence - Secondary */
.sp-intelligence-secondary{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:15px;
}
.sp-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:8px 16px;
    border-radius:20px;
    font-size:14px;
    font-weight:600;
    white-space:nowrap;
    color:white;
    transition:var(--sp-transition);
}
.sp-badge:hover{
    transform:translateY(-2px);
    box-shadow:var(--sp-shadow-sm);
}

/* Buttons - Onboarding Style */
.sp-btn-download{
    width:100%;
    background:linear-gradient(135deg,var(--sp-primary) 0%,var(--sp-secondary) 100%);
    color:white;
    border:none;
    padding:15px 24px;
    border-radius:10px;
    font-size:15px;
    font-weight:700;
    cursor:pointer;
    transition:var(--sp-transition);
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
}
.sp-btn-download:hover{
    transform:translateY(-2px);
    box-shadow:0 6px 16px rgba(2, 146, 183, 0.3);
}
.sp-btn-download .dashicons{
    font-size:20px;
    width:20px;
    height:20px;
}

/* Contact Info */
.sp-contact-info{
    display:flex;
    flex-direction:column;
    gap:12px;
}
.sp-contact-item{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:14px;
    color:var(--sp-gray);
    transition:var(--sp-transition);
}
.sp-contact-item:hover{
    color:var(--sp-primary);
    transform:translateX(3px);
}
.sp-contact-item .dashicons{
    color:var(--sp-primary);
    font-size:18px;
    width:18px;
    height:18px;
}

/* Intelligence Bars */
.sp-intelligence-bars{
    display:flex;
    flex-direction:column;
    gap:15px;
}
.sp-bar-label{
    display:flex;
    justify-content:space-between;
    font-size:13px;
    font-weight:600;
    color:var(--sp-gray);
    align-items:center;
}
.sp-bar-track{
    height:8px;
    background:var(--sp-light-gray);
    border-radius:4px;
    overflow:hidden;
}
.sp-bar-fill{
    height:100%;
    border-radius:4px;
    transition:width 1s ease;
}

/* CV Sections */
.sp-cv-section{
    margin-bottom:25px;
    animation:fadeInUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.sp-cv-field-label{
    font-size:16px;
    font-weight:700;
    color:var(--sp-primary);
    margin:0 0 12px 0;
}
.sp-cv-field-value{
    padding:15px;
    background:var(--sp-light-gray);
    border-radius:var(--sp-radius-sm);
    margin-bottom:10px;
    font-size:14px;
    line-height:1.7;
    color:var(--sp-dark);
    border-left:3px solid var(--sp-primary);
    transition:var(--sp-transition);
}
.sp-cv-field-value:hover{
    background:#f3f4f6;
    transform:translateX(3px);
}

/* Negation Notices */
.sp-negation-notice{
    display:flex;
    align-items:center;
    gap:12px;
    padding:12px 16px;
    background:linear-gradient(135deg,#fef3c7 0%,#fde68a 100%);
    border-left:3px solid var(--sp-warning);
    border-radius:var(--sp-radius-sm);
    margin-bottom:10px;
    box-shadow:var(--sp-shadow-sm);
}
.sp-negation-notice .dashicons{
    color:var(--sp-warning);
    font-size:20px;
    width:20px;
    height:20px;
}
.sp-negation-text{
    font-size:14px;
    color:#92400e;
    font-weight:500;
}

/* AI Interpretation */
.sp-ai-text{
    font-size:15px;
    line-height:1.7;
    color:var(--sp-gray);
    white-space:pre-wrap;
}
.sp-ai-meta-box{
    margin-top:20px;
    padding:15px;
    background:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 100%);
    border-radius:var(--sp-radius-sm);
    border-left:3px solid #0284c7;
    box-shadow:var(--sp-shadow-sm);
}
.sp-ai-meta-title{
    font-size:13px;
    font-weight:700;
    color:#0284c7;
    margin-bottom:8px;
    display:flex;
    align-items:center;
    gap:6px;
}
.sp-ai-meta-title .dashicons{
    font-size:16px;
    width:16px;
    height:16px;
}
.sp-ai-meta-value{
    font-size:14px;
    color:var(--sp-gray);
    line-height:1.6;
}

/* Negations Sidebar */
.sp-negations-sidebar{
    background:linear-gradient(135deg,#fff9e6 0%,#fef3c7 100%);
    border-radius:var(--sp-radius);
    padding:20px;
    margin-bottom:20px;
    border:1px solid #fde68a;
    box-shadow:var(--sp-shadow-sm);
    animation:fadeInUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.sp-negations-list{
    display:flex;
    flex-direction:column;
    gap:10px;
    margin-top:12px;
}
.sp-negation-item{
    display:flex;
    align-items:center;
    gap:8px;
    font-size:13px;
    color:#92400e;
    padding:8px 12px;
    background:#fef3c7;
    border-radius:var(--sp-radius-sm);
    transition:var(--sp-transition);
}
.sp-negation-item:hover{
    background:#fde68a;
    transform:translateX(3px);
}
.sp-negation-item .dashicons{
    color:var(--sp-warning);
    font-size:16px;
    width:16px;
    height:16px;
    flex-shrink:0;
}

/* Completion Badge */
.sp-profile-completion-badge{
    display:flex;
    align-items:center;
    gap:15px;
    background:linear-gradient(135deg,#fbbf24 0%,var(--sp-warning) 100%);
    padding:15px 20px;
    border-radius:var(--sp-radius);
    margin-bottom:20px;
    color:white;
    box-shadow:var(--sp-shadow-md);
    animation:fadeInUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.sp-completion-circle{
    position:relative;
    width:60px;
    height:60px;
}
.sp-completion-circle svg{
    transform:rotate(-90deg);
}
.sp-circle-bg{
    fill:none;
    stroke:rgba(255,255,255,0.3);
    stroke-width:5;
}
.sp-circle-progress{
    fill:none;
    stroke:white;
    stroke-width:5;
    stroke-linecap:round;
    stroke-dasharray:164.85;
    transition:stroke-dashoffset 1s ease;
}
.sp-percentage{
    position:absolute;
    top:50%;
    left:50%;
    transform:translate(-50%,-50%);
    font-size:16px;
    font-weight:700;
    color:white;
}
.sp-completion-label{
    font-size:13px;
    font-weight:600;
    color:white;
    line-height:1.4;
}

/* Responsive */
@media (max-width:968px){
    .sp-profile-grid{grid-template-columns:1fr;}
    .sp-profile-right{order:-1;}
}
@media (max-width:768px){
    .sp-profile-card{padding:20px;}
    .sp-card-title{font-size:18px;}
    .sp-specialization-main{font-size:20px;}
}
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
