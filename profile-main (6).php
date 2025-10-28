<?php
/**
 * Professional Profile Template - Modern Business View v6.0
 * Clean, professional design for business viewing
 * Features: Simplified layout, beautiful negation display, enhanced CV presentation
 */

if (!defined('ABSPATH')) exit;

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
$session = $profile_data['session_data'];
$debug = $profile_data['debug'];

$is_own_profile = (get_current_user_id() === $user_id);
$is_admin = current_user_can('administrator');

// Show debug mode?
$show_debug = ($is_own_profile || $is_admin) && isset($_GET['debug']);

// Modern Professional CSS
$inline_css = '
<style id="sp-profile-modern-css">
.sp-professional-profile-wrapper{font-family:"Inter",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;max-width:1200px;margin:0 auto;padding:30px 20px;background:#f8f9fa}
.sp-profile-header{background:linear-gradient(135deg,#0292B7 0%,#1AC8DB 100%);padding:40px 30px;border-radius:16px;margin-bottom:30px;color:white;box-shadow:0 4px 20px rgba(2,146,183,0.2)}
.sp-profile-header h1{margin:0 0 10px 0;font-size:32px;font-weight:700;color:white}
.sp-profile-subtitle{font-size:18px;opacity:0.95;font-weight:500}
.sp-completion-inline{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.2);padding:8px 16px;border-radius:8px;margin-top:15px;font-size:14px;font-weight:600}
.sp-profile-grid{display:grid;grid-template-columns:2fr 1fr;gap:25px;margin-top:25px}
.sp-profile-card{background:white;border-radius:12px;padding:30px;margin-bottom:20px;box-shadow:0 2px 12px rgba(0,0,0,0.06);border:1px solid #e5e7eb;transition:box-shadow 0.3s ease}
.sp-profile-card:hover{box-shadow:0 4px 16px rgba(0,0,0,0.1)}
.sp-card-title{margin:0 0 20px 0;font-size:22px;font-weight:700;color:#1a1a1a;display:flex;align-items:center;gap:10px}
.sp-card-title-icon{font-size:24px}
.sp-sidebar-card{background:#f8fafc;border-radius:12px;padding:25px;margin-bottom:20px;border:1px solid #e2e8f0}
.sp-sidebar-title{margin:0 0 15px 0;font-size:16px;font-weight:700;color:#0292B7;text-transform:uppercase;letter-spacing:0.5px}
.sp-contact-list{display:flex;flex-direction:column;gap:12px}
.sp-contact-item{display:flex;align-items:center;gap:12px;font-size:14px;color:#4a5568;padding:8px 0}
.sp-contact-icon{font-size:18px;color:#0292B7;width:20px}
.sp-intelligence-dominant{display:flex;align-items:center;gap:20px;padding:25px;background:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 100%);border-radius:12px;margin-bottom:20px}
.sp-intelligence-icon{font-size:56px}
.sp-intelligence-name{font-size:26px;font-weight:700;color:#0369a1}
.sp-intelligence-score{font-size:20px;color:#64748b;font-weight:600;margin-top:5px}
.sp-top-three{display:flex;gap:12px;flex-wrap:wrap;margin-top:15px}
.sp-intelligence-badge{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border-radius:10px;font-size:14px;font-weight:600;color:white}
.sp-cv-section{margin-bottom:30px}
.sp-cv-section-title{font-size:18px;font-weight:700;color:#0292B7;margin:0 0 15px 0;display:flex;align-items:center;gap:8px;border-bottom:2px solid #C5EEF9;padding-bottom:8px}
.sp-cv-entry{background:#f8f9fa;padding:18px 20px;border-radius:10px;margin-bottom:12px;border-left:4px solid #0292B7;font-size:15px;line-height:1.7;color:#374151}
.sp-cv-empty{padding:15px;background:#fff3cd;border-radius:8px;color:#856404;font-size:14px;text-align:center}
.sp-negation-item{display:flex;align-items:center;gap:12px;padding:15px 20px;background:#fef3c7;border-left:4px solid #f59e0b;border-radius:8px;margin-bottom:12px}
.sp-negation-icon{font-size:20px;color:#f59e0b}
.sp-negation-text{font-size:15px;color:#92400e;font-weight:500}
.sp-negation-label{font-weight:700;color:#78350f}
.sp-skills-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px}
.sp-skill-badge{padding:12px 16px;background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:white;border-radius:8px;font-size:14px;font-weight:600;text-align:center;box-shadow:0 2px 8px rgba(16,185,129,0.2)}
.sp-ai-card{background:linear-gradient(135deg,#fefce8 0%,#fef3c7 100%);padding:25px;border-radius:12px;border-left:4px solid #f59e0b}
.sp-ai-text{font-size:15px;line-height:1.8;color:#4a5568;margin-bottom:15px}
.sp-ai-meta{display:flex;gap:20px;flex-wrap:wrap;margin-top:20px}
.sp-ai-meta-item{flex:1;min-width:200px;padding:15px;background:white;border-radius:8px;border-left:3px solid #0284c7}
.sp-ai-meta-title{font-size:13px;font-weight:700;color:#0284c7;text-transform:uppercase;margin-bottom:8px}
.sp-ai-meta-value{font-size:14px;color:#4a5568;line-height:1.6}
.sp-btn-download{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:15px 24px;background:linear-gradient(135deg,#0292B7 0%,#1AC8DB 100%);color:white;border:none;border-radius:10px;font-size:16px;font-weight:700;cursor:pointer;transition:all 0.3s ease;box-shadow:0 4px 12px rgba(2,146,183,0.3)}
.sp-btn-download:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(2,146,183,0.4)}
.sp-intelligence-bars{display:flex;flex-direction:column;gap:12px}
.sp-bar-item{display:flex;flex-direction:column;gap:6px}
.sp-bar-label{display:flex;justify-content:space-between;font-size:13px;font-weight:600;color:#4a5568}
.sp-bar-track{height:10px;background:#e5e7eb;border-radius:6px;overflow:hidden}
.sp-bar-fill{height:100%;border-radius:6px;transition:width 1.2s ease}
.sp-notice-warning{display:flex;align-items:center;gap:15px;padding:20px;background:#fff3cd;border-left:4px solid#ffc107;border-radius:10px;margin-bottom:30px}
.sp-debug-toggle{position:fixed;bottom:20px;right:20px;background:#6366f1;color:white;padding:12px 20px;border-radius:8px;cursor:pointer;z-index:999;box-shadow:0 4px 12px rgba(99,102,241,0.4);text-decoration:none}
.sp-notice-debug{background:#f3f4f6;border-left:4px solid #6366f1;padding:20px;border-radius:10px;margin-bottom:30px;font-family:monospace;font-size:11px}
@media (max-width:968px){.sp-profile-grid{grid-template-columns:1fr}.sp-profile-right{order:-1}}
@media (max-width:768px){.sp-profile-card{padding:20px}.sp-card-title{font-size:20px}.sp-intelligence-name{font-size:22px}}
</style>
';
echo $inline_css;
?>

<div class="sp-professional-profile-wrapper">

    <!-- Debug Toggle -->
    <?php if ($is_own_profile || $is_admin): ?>
    <a href="<?php echo add_query_arg('debug', $show_debug ? '0' : '1'); ?>" class="sp-debug-toggle">
        <?php echo $show_debug ? '🐛 Ascunde Debug' : '🐛 Arată Debug'; ?>
    </a>
    <?php endif; ?>

    <!-- Debug Info -->
    <?php if ($show_debug): ?>
    <div class="sp-notice-debug">
        <strong style="color:#6366f1">🐛 MOD DEBUG</strong>
        <pre style="margin:10px 0;white-space:pre-wrap"><?php
echo "User ID: {$user_id}\n";
echo "Test Status: " . ($debug['test_status'] ?: 'NOT SET') . "\n";
echo "CV Status: " . ($debug['cv_status'] ?: 'NOT SET') . "\n";
echo "CV Entries: " . ($debug['cv_entries_count'] ?? 0) . "\n";
echo "Negations: " . count($negations) . "\n";
if (!empty($negations)) {
    echo "Negated Fields: " . implode(', ', array_keys($negations)) . "\n";
}
        ?></pre>
    </div>
    <?php endif; ?>

    <!-- Profile Header -->
    <div class="sp-profile-header">
        <h1><?php echo esc_html($basic['display_name']); ?></h1>
        <?php if ($cv && $cv['specialization']): ?>
        <div class="sp-profile-subtitle"><?php echo esc_html($cv['specialization']); ?></div>
        <?php endif; ?>
        <div class="sp-completion-inline">
            <span>📊 Profil Completat: <?php echo $completion; ?>%</span>
        </div>
    </div>

    <?php if (!sp_is_profile_complete($user_id)): ?>
    <div class="sp-notice-warning">
        <div style="font-size:32px">⚠️</div>
        <div style="flex:1">
            <strong>Profil Incomplet</strong>
            <p style="margin:5px 0 0 0">
                <?php
                $missing = array();
                if ($debug['test_status'] !== 'completed') $missing[] = 'testul vocațional';
                if (empty($cv) || ($debug['cv_entries_count'] ?? 0) == 0) $missing[] = 'CV-ul';
                echo 'Completează ' . implode(' și ', $missing) . ' pentru a debloca toate funcționalitățile.';
                ?>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <div class="sp-profile-grid">

        <!-- Main Content -->
        <div class="sp-profile-main">

            <!-- Dominant Intelligence -->
            <?php if ($test && $test['dominant']): ?>
            <div class="sp-profile-card">
                <h2 class="sp-card-title">
                    <span class="sp-card-title-icon">🎯</span>
                    Inteligență Dominantă
                </h2>
                <div class="sp-intelligence-dominant">
                    <span class="sp-intelligence-icon"><?php echo $test['dominant']['icon']; ?></span>
                    <div style="flex:1">
                        <div class="sp-intelligence-name"><?php echo esc_html($test['dominant']['name']); ?></div>
                        <div class="sp-intelligence-score"><?php echo number_format($test['dominant']['score'], 1); ?>% profil</div>
                    </div>
                </div>

                <?php if (!empty($test['top_three']) && count($test['top_three']) > 1): ?>
                <div style="margin-top:20px">
                    <strong style="font-size:14px;color:#64748b;display:block;margin-bottom:12px">Top 3 Inteligențe:</strong>
                    <div class="sp-top-three">
                        <?php
                        $top_three = array_slice($test['top_three'], 0, 3, true);
                        foreach ($top_three as $intel):
                        ?>
                        <span class="sp-intelligence-badge" style="background-color:<?php echo $intel['color']; ?>">
                            <?php echo $intel['icon']; ?> <?php echo esc_html($intel['name']); ?> (<?php echo number_format($intel['score'], 0); ?>%)
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- AI Interpretation -->
            <?php if ($ai && $ai['text']): ?>
            <div class="sp-ai-card">
                <h3 style="margin:0 0 15px 0;font-size:18px;font-weight:700;color:#92400e">💡 Interpretare AI</h3>
                <div class="sp-ai-text"><?php echo nl2br(esc_html($ai['text'])); ?></div>

                <?php if ($ai['learning_style'] || $ai['work_environment']): ?>
                <div class="sp-ai-meta">
                    <?php if ($ai['learning_style']): ?>
                    <div class="sp-ai-meta-item">
                        <div class="sp-ai-meta-title">📚 Stil de Învățare</div>
                        <div class="sp-ai-meta-value"><?php echo esc_html($ai['learning_style']); ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if ($ai['work_environment']): ?>
                    <div class="sp-ai-meta-item">
                        <div class="sp-ai-meta-title">🏢 Mediu Ideal de Lucru</div>
                        <div class="sp-ai-meta-value"><?php echo esc_html($ai['work_environment']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Soft Skills -->
            <?php if (!empty($skills)): ?>
            <div class="sp-profile-card">
                <h2 class="sp-card-title">
                    <span class="sp-card-title-icon">⭐</span>
                    Soft Skills
                </h2>
                <div class="sp-skills-grid">
                    <?php foreach ($skills as $skill): ?>
                    <div class="sp-skill-badge"><?php echo esc_html($skill); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- CV Data with Negations -->
            <?php if ($cv || !empty($negations)): ?>
            <div class="sp-profile-card">
                <h2 class="sp-card-title">
                    <span class="sp-card-title-icon">📄</span>
                    Date Curriculum Vitae
                </h2>

                <?php
                // Display CV entries
                if ($cv && !empty($cv['entries'])):
                    foreach ($cv['entries'] as $field_id => $entries):
                        $field_info = $cv['fields'][$field_id] ?? null;
                        if (!$field_info) continue;

                        // Check if this field is negated
                        $is_negated = isset($negations[$field_info['field_name']]) && $negations[$field_info['field_name']]['is_negated'];
                ?>
                <div class="sp-cv-section">
                    <h3 class="sp-cv-section-title">
                        <span>📌</span> <?php echo esc_html($field_info['field_label']); ?>
                    </h3>

                    <?php if ($is_negated): ?>
                    <div class="sp-negation-item">
                        <span class="sp-negation-icon">🚫</span>
                        <div class="sp-negation-text">
                            <span class="sp-negation-label"><?php echo esc_html($field_info['field_label']); ?>:</span>
                            Nu a completat această secțiune
                        </div>
                    </div>
                    <?php else: ?>
                        <?php foreach ($entries as $entry): ?>
                        <div class="sp-cv-entry">
                            <?php echo nl2br(esc_html($entry['field_value'])); ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php
                    endforeach;
                endif;

                // Display standalone negations (fields that were negated but have no entries)
                if (!empty($negations)):
                    foreach ($negations as $negation):
                        // Check if this negation was already displayed above
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
                    <h3 class="sp-cv-section-title">
                        <span>📌</span> <?php echo esc_html($negation['field_label']); ?>
                    </h3>
                    <div class="sp-negation-item">
                        <span class="sp-negation-icon">🚫</span>
                        <div class="sp-negation-text">
                            <span class="sp-negation-label"><?php echo esc_html($negation['field_label']); ?>:</span>
                            Nu a completat această secțiune
                        </div>
                    </div>
                </div>
                <?php
                        endif;
                    endforeach;
                endif;

                // If no CV data and no negations
                if ((!$cv || empty($cv['entries'])) && empty($negations)):
                ?>
                <div class="sp-cv-empty">
                    Datele de CV nu au fost încă completate
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Download CV Button -->
            <?php if ($cv && sp_is_profile_complete($user_id)): ?>
            <div class="sp-profile-card">
                <button class="sp-btn-download sp-download-cv" data-user-id="<?php echo $user_id; ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="12" y1="18" x2="12" y2="12"/>
                        <line x1="9" y1="15" x2="12" y2="18"/>
                        <line x1="15" y1="15" x2="12" y2="18"/>
                    </svg>
                    DESCARCĂ CV COMPLET
                </button>
            </div>
            <?php endif; ?>

        </div>

        <!-- Sidebar -->
        <div class="sp-profile-sidebar">

            <!-- Contact Info -->
            <div class="sp-sidebar-card">
                <h3 class="sp-sidebar-title">📞 Contact</h3>
                <div class="sp-contact-list">
                    <?php if ($basic['email']): ?>
                    <div class="sp-contact-item">
                        <span class="sp-contact-icon">✉️</span>
                        <a href="mailto:<?php echo esc_attr($basic['email']); ?>" style="color:#0292B7;text-decoration:none;font-weight:500">
                            <?php echo esc_html($basic['email']); ?>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if ($basic['phone']): ?>
                    <div class="sp-contact-item">
                        <span class="sp-contact-icon">📱</span>
                        <span style="font-weight:500"><?php echo esc_html(sp_format_phone($basic['phone'])); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($basic['city']): ?>
                    <div class="sp-contact-item">
                        <span class="sp-contact-icon">📍</span>
                        <span style="font-weight:500"><?php echo esc_html($basic['city']); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($basic['age']): ?>
                    <div class="sp-contact-item">
                        <span class="sp-contact-icon">🎂</span>
                        <span style="font-weight:500"><?php echo esc_html($basic['age']); ?> ani</span>
                    </div>
                    <?php endif; ?>

                    <?php if ($basic['sex']): ?>
                    <div class="sp-contact-item">
                        <span class="sp-contact-icon">👤</span>
                        <span style="font-weight:500"><?php echo $basic['sex'] === 'M' ? 'Masculin' : 'Feminin'; ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Intelligence Scores -->
            <?php if ($test && !empty($test['scores'])): ?>
            <div class="sp-sidebar-card">
                <h3 class="sp-sidebar-title">📊 Scoruri Inteligențe</h3>
                <div class="sp-intelligence-bars">
                    <?php foreach ($test['scores'] as $intel): ?>
                    <div class="sp-bar-item">
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
