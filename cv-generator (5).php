<?php
/**
 * CV Generator
 * File: includes/cv-generator.php
 */

if (!defined('ABSPATH')) exit;

/**
 * Generate CV PDF for user
 */
function sp_generate_cv_pdf($user_id) {
    // Check if user has completed CV
    $cv_status = get_user_meta($user_id, 'sp_cv_status', true);
    if ($cv_status !== 'completed') {
        return array(
            'success' => false,
            'message' => 'CV-ul nu a fost completat încă.'
        );
    }
    
    // Load profile data
    require_once dirname(__FILE__) . '/data-loader.php';
    $profile_data = sp_load_profile_data($user_id);
    
    if (!$profile_data) {
        return array(
            'success' => false,
            'message' => 'Nu s-au putut încărca datele profilului.'
        );
    }
    
    // Check if we have a PDF library available
    if (class_exists('TCPDF')) {
        return sp_generate_cv_with_tcpdf($profile_data);
    } elseif (class_exists('Dompdf\Dompdf')) {
        return sp_generate_cv_with_dompdf($profile_data);
    } else {
        // Fallback: Generate HTML CV that can be printed to PDF
        return sp_generate_cv_html($profile_data);
    }
}

/**
 * Generate CV using TCPDF (if available)
 */
function sp_generate_cv_with_tcpdf($profile_data) {
    // TCPDF implementation would go here
    // For now, fallback to HTML
    return sp_generate_cv_html($profile_data);
}

/**
 * Generate CV using Dompdf (if available)
 */
function sp_generate_cv_with_dompdf($profile_data) {
    // Dompdf implementation would go here
    // For now, fallback to HTML
    return sp_generate_cv_html($profile_data);
}

/**
 * Generate HTML CV (printable to PDF)
 */
function sp_generate_cv_html($profile_data) {
    $user_id = $profile_data['user_id'];
    $basic = $profile_data['basic_info'];
    $test = $profile_data['test_results'];
    $cv = $profile_data['cv_data'];
    $skills = $profile_data['soft_skills'];
    
    // Generate unique filename
    $filename = 'cv-' . sanitize_file_name($basic['display_name']) . '-' . time() . '.html';
    $upload_dir = wp_upload_dir();
    $cv_dir = $upload_dir['basedir'] . '/cv-files/';
    
    // Create directory if it doesn't exist
    if (!file_exists($cv_dir)) {
        wp_mkdir_p($cv_dir);
    }
    
    $filepath = $cv_dir . $filename;
    $fileurl = $upload_dir['baseurl'] . '/cv-files/' . $filename;
    
    // Start output buffering
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="ro">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CV - <?php echo esc_html($basic['display_name']); ?></title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: 'Arial', sans-serif;
                font-size: 12pt;
                line-height: 1.6;
                color: #333;
                max-width: 800px;
                margin: 0 auto;
                padding: 40px 20px;
            }
            .cv-header {
                background: linear-gradient(135deg, #0292B7 0%, #1AC8DB 100%);
                color: white;
                padding: 30px;
                margin-bottom: 30px;
                border-radius: 10px;
            }
            .cv-header h1 {
                font-size: 28pt;
                margin-bottom: 5px;
            }
            .cv-header p {
                font-size: 14pt;
                opacity: 0.9;
            }
            .cv-section {
                margin-bottom: 30px;
            }
            .cv-section h2 {
                font-size: 18pt;
                color: #0292B7;
                border-bottom: 2px solid #C5EEF9;
                padding-bottom: 8px;
                margin-bottom: 15px;
            }
            .cv-section h3 {
                font-size: 14pt;
                color: #1a1a1a;
                margin-bottom: 8px;
            }
            .cv-contact {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                margin-bottom: 20px;
            }
            .cv-contact-item {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .cv-skills {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
            .cv-skill-badge {
                background: #e0f2fe;
                color: #0369a1;
                padding: 6px 12px;
                border-radius: 15px;
                font-size: 11pt;
                font-weight: 600;
            }
            .cv-intelligence {
                background: #f0f9ff;
                padding: 15px;
                border-radius: 8px;
                border-left: 4px solid #0292B7;
            }
            .cv-intelligence strong {
                color: #0292B7;
                font-size: 14pt;
            }
            .cv-entry {
                margin-bottom: 15px;
                padding-left: 20px;
                border-left: 2px solid #C5EEF9;
            }
            @media print {
                body {
                    padding: 0;
                }
                .cv-header {
                    background: none;
                    color: black;
                    border: 2px solid #0292B7;
                }
                .no-print {
                    display: none;
                }
            }
        </style>
    </head>
    <body>
        
        <!-- Header -->
        <div class="cv-header">
            <h1><?php echo esc_html($basic['display_name']); ?></h1>
            <?php if ($cv && $cv['specialization']): ?>
                <p><?php echo esc_html($cv['specialization']); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Contact Information -->
        <div class="cv-section">
            <h2>Date de Contact</h2>
            <div class="cv-contact">
                <?php if ($basic['email']): ?>
                    <div class="cv-contact-item">
                        <strong>Email:</strong> <?php echo esc_html($basic['email']); ?>
                    </div>
                <?php endif; ?>
                <?php if ($basic['phone']): ?>
                    <div class="cv-contact-item">
                        <strong>Telefon:</strong> <?php echo esc_html(sp_format_phone($basic['phone'])); ?>
                    </div>
                <?php endif; ?>
                <?php if ($basic['city']): ?>
                    <div class="cv-contact-item">
                        <strong>Oraș:</strong> <?php echo esc_html($basic['city']); ?>
                    </div>
                <?php endif; ?>
                <?php if ($basic['age']): ?>
                    <div class="cv-contact-item">
                        <strong>Vârstă:</strong> <?php echo esc_html($basic['age']); ?> ani
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Profile Description -->
        <?php if ($basic['bio']): ?>
        <div class="cv-section">
            <h2>Profil Profesional</h2>
            <p><?php echo nl2br(esc_html($basic['bio'])); ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Intelligence Type -->
        <?php if ($test && $test['dominant']): ?>
        <div class="cv-section">
            <h2>Tip de Inteligență Dominantă</h2>
            <div class="cv-intelligence">
                <strong><?php echo esc_html($test['dominant']['name']); ?></strong>
                (<?php echo number_format($test['dominant']['score'], 1); ?>%)
                <p style="margin-top: 10px; font-size: 11pt; color: #64748b;">
                    <?php echo sp_get_intelligence_description($test['dominant']['slug']); ?>
                </p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Soft Skills -->
        <?php if (!empty($skills)): ?>
        <div class="cv-section">
            <h2>Soft Skills</h2>
            <div class="cv-skills">
                <?php foreach ($skills as $skill): ?>
                    <span class="cv-skill-badge"><?php echo esc_html($skill); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Experience -->
        <?php if ($cv && !empty($cv['experience'])): ?>
        <div class="cv-section">
            <h2>Experiență Profesională</h2>
            <?php foreach ($cv['experience'] as $exp): ?>
                <div class="cv-entry">
                    <h3><?php echo esc_html($exp['field_value'] ?? ''); ?></h3>
                    <?php if (isset($exp['company'])): ?>
                        <p><strong>Companie:</strong> <?php echo esc_html($exp['company']); ?></p>
                    <?php endif; ?>
                    <?php if (isset($exp['period'])): ?>
                        <p><strong>Perioada:</strong> <?php echo esc_html($exp['period']); ?></p>
                    <?php endif; ?>
                    <?php if (isset($exp['description'])): ?>
                        <p><?php echo nl2br(esc_html($exp['description'])); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Education -->
        <?php if ($cv && !empty($cv['education'])): ?>
        <div class="cv-section">
            <h2>Educație</h2>
            <?php foreach ($cv['education'] as $edu): ?>
                <div class="cv-entry">
                    <h3><?php echo esc_html($edu['field_value'] ?? ''); ?></h3>
                    <?php if (isset($edu['institution'])): ?>
                        <p><strong>Instituție:</strong> <?php echo esc_html($edu['institution']); ?></p>
                    <?php endif; ?>
                    <?php if (isset($edu['period'])): ?>
                        <p><strong>Perioada:</strong> <?php echo esc_html($edu['period']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="cv-section no-print" style="margin-top: 50px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
            <p style="text-align: center; color: #94a3b8; font-size: 10pt;">
                CV generat pe <?php echo date('d.m.Y'); ?> | 
                <button onclick="window.print()" style="padding: 8px 16px; background: #0292B7; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    Printează / Salvează PDF
                </button>
            </p>
        </div>
        
    </body>
    </html>
    <?php
    
    $html = ob_get_clean();
    
    // Save HTML file
    file_put_contents($filepath, $html);
    
    return array(
        'success' => true,
        'message' => 'CV generat cu succes!',
        'download_url' => $fileurl,
        'file_path' => $filepath
    );
}

/**
 * Get intelligence type description
 */
function sp_get_intelligence_description($slug) {
    $descriptions = array(
        'logico-matematica' => 'Capacitate excelentă de rezolvare a problemelor, gândire analitică și abilități matematice avansate.',
        'verbal-lingvistica' => 'Abilități puternice de comunicare, exprimare verbală și scrisă, precum și capacitate de învățare a limbilor străine.',
        'vizual-spatiala' => 'Gândire vizuală dezvoltată, abilități artistice și capacitatea de a vizualiza concepte complexe.',
        'kinestezic-corporala' => 'Coordonare motorie excelentă, abilități fizice și capacitatea de a învăța prin mișcare.',
        'muzical-ritmica' => 'Sensibilitate muzicală, simț ritmic dezvoltat și abilități de compoziție sau interpretare.',
        'interpersonala' => 'Abilități sociale excepționale, empatie și capacitatea de a lucra eficient în echipă.',
        'intrapersonala' => 'Autocunoaștere profundă, auto-motivare și capacitatea de reflecție și auto-analiză.',
        'naturalista' => 'Conexiune puternică cu natura, abilități de observare și clasificare a elementelor naturale.'
    );
    
    return $descriptions[$slug] ?? '';
}