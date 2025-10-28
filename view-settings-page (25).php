<?php
/**
 * Admin View: Settings Page
 * UPDATED: Added Popup Debug Mode option
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap sp-onboarding-admin">
    <h1>Setări SuccessPlus Onboarding</h1>
    
    <div class="card" style="max-width: 600px; margin-bottom: 20px;">
        <h2>Testul de Inteligență Multiplă</h2>
        <p><strong>Întrebări încărcate:</strong> <?php echo $questions_count; ?> / 80</p>
        <?php if ($questions_count != 80): ?>
            <p style="color: #dc3545;">⚠️ Testul nu este complet! Ar trebui să existe 80 de întrebări.</p>
        <?php else: ?>
            <p style="color: #10b981;">✓ Testul este complet și funcțional.</p>
        <?php endif; ?>
        <button type="button" id="reload-test-questions" class="button button-secondary">🔄 Reîncarcă Testul (80 întrebări)</button>
        <p class="description">Folosiți acest buton dacă întrebările nu sunt corecte sau testul nu funcționează.</p>
    </div>
    
    <form method="post">
        <?php wp_nonce_field('sp_settings_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">Cheie API OpenAI</th>
                <td>
                    <input type="text" name="sp_onboarding_openai_key" value="<?php echo esc_attr(get_option('sp_onboarding_openai_key')); ?>" class="regular-text" />
                    <p class="description">Introduceți cheia dvs. API OpenAI pentru analiza AI.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Model OpenAI</th>
                <td>
                    <select name="sp_onboarding_openai_model">
                        <option value="gpt-3.5-turbo" <?php selected(get_option('sp_onboarding_openai_model'), 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo (Recomandat - Rapid)</option>
                        <option value="gpt-4o-mini" <?php selected(get_option('sp_onboarding_openai_model'), 'gpt-4o-mini'); ?>>GPT-4o Mini (Mai rapid, detaliat)</option>
                        <option value="gpt-4o" <?php selected(get_option('sp_onboarding_openai_model'), 'gpt-4o'); ?>>GPT-4o (Cel mai bun)</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">Roluri Utilizator după Înregistrare</th>
                <td>
                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px; background: #f9f9f9;">
                        <?php
                        $roles = wp_roles()->get_names();
                        foreach ($roles as $role_key => $role_name) {
                            $checked = in_array($role_key, $selected_roles) ? 'checked' : '';
                            echo '<label style="display: block; margin-bottom: 8px;">';
                            echo '<input type="checkbox" name="sp_onboarding_user_roles[]" value="' . esc_attr($role_key) . '" ' . $checked . ' />';
                            echo ' ' . esc_html($role_name);
                            echo '</label>';
                        }
                        ?>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row">URL Redirecționare după Finalizare</th>
                <td><input type="url" name="sp_onboarding_redirect_url" value="<?php echo esc_url(get_option('sp_onboarding_redirect_url')); ?>" class="regular-text" /></td>
            </tr>
        </table>
        
        <h2>Opțiuni Omitere Test (Skip Test)</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Activează Opțiunea de a Omite Testul</th>
                <td>
                    <label>
                        <input type="checkbox" name="sp_onboarding_enable_skip_test" value="1" <?php checked(get_option('sp_onboarding_enable_skip_test'), 1); ?> />
                        Permite utilizatorilor să omită testul și să îl completeze mai târziu
                    </label>
                    <p class="description">Dacă este activat, utilizatorii vor vedea un buton "Fă testul mai târziu" și progresul lor va fi salvat automat.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">URL Redirecționare după Omitere</th>
                <td>
                    <input type="url" name="sp_onboarding_skip_redirect_url" value="<?php echo esc_url(get_option('sp_onboarding_skip_redirect_url')); ?>" class="regular-text" placeholder="<?php echo home_url(); ?>" />
                    <p class="description">URL-ul unde va fi redirecționat utilizatorul după ce omite testul. Dacă este lăsat gol, utilizatorul va fi trimis la pagina principală.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Pagina Testului</th>
                <td>
                    <div style="background: #e7f3ff; border-left: 4px solid #0292B7; padding: 15px; border-radius: 4px;">
                        <p style="margin: 0;"><strong>ℹ️ Pagina pentru shortcode-ul testului:</strong></p>
                        <p style="margin: 8px 0 0 0;">Asigurați-vă că aveți o pagină WordPress cu URL-ul <code>/inregistrare/</code> care conține shortcode-ul <code>[sp_onboarding_start]</code>.</p>
                        <p style="margin: 8px 0 0 0; font-size: 13px; color: #666;">Această pagină va fi folosită pentru a afișa testul și pentru redirecționările popup-ului.</p>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row">🔧 Mod Debug Popup</th>
                <td>
                    <label>
                        <input type="checkbox" name="sp_onboarding_popup_debug_mode" value="1" <?php checked(get_option('sp_onboarding_popup_debug_mode'), 1); ?> />
                        <strong>Activează modul debug pentru popup</strong>
                    </label>
                    <p class="description" style="background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin-top: 10px;">
                        <strong>⚠️ ATENȚIE:</strong> Când este activat, popup-ul va apărea la <strong>fiecare încărcare de pagină</strong> pentru utilizatorii cu test în progres sau omis, ignorând limita de 24 ore. Util pentru testare, dar dezactivați-l în producție!
                    </p>
                </td>
            </tr>
        </table>
        
        <h2>Culori Personalizate</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Culoare Primară</th>
                <td><input type="color" name="sp_onboarding_primary_color" value="<?php echo esc_attr(get_option('sp_onboarding_primary_color', '#0292B7')); ?>" /></td>
            </tr>
            <tr>
                <th scope="row">Culoare Secundară</th>
                <td><input type="color" name="sp_onboarding_secondary_color" value="<?php echo esc_attr(get_option('sp_onboarding_secondary_color', '#1AC8DB')); ?>" /></td>
            </tr>
            <tr>
                <th scope="row">Culoare Accent</th>
                <td><input type="color" name="sp_onboarding_accent_color" value="<?php echo esc_attr(get_option('sp_onboarding_accent_color', '#C5EEF9')); ?>" /></td>
            </tr>
        </table>
        
        <h2>Conținut Formular</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Titlu Test</th>
                <td>
                    <input type="text" name="sp_onboarding_test_title" value="<?php echo esc_attr(get_option('sp_onboarding_test_title', 'Test de Inteligență Multiplă')); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">Culoare Titlu Test</th>
                <td><input type="color" name="sp_onboarding_test_title_color" value="<?php echo esc_attr(get_option('sp_onboarding_test_title_color', '#1a1a1a')); ?>" /></td>
            </tr>
            <tr>
                <th scope="row">Titlu Pas CV</th>
                <td>
                    <input type="text" name="sp_onboarding_cv_title" value="<?php echo esc_attr(get_option('sp_onboarding_cv_title', 'Construiește-ți Profilul Profesional')); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">Descriere Pas CV</th>
                <td>
                    <textarea name="sp_onboarding_cv_intro" rows="2" class="large-text"><?php echo esc_textarea(get_option('sp_onboarding_cv_intro', 'Spune-ne despre experiența și competențele tale.')); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row">Titlu Pas Înregistrare</th>
                <td>
                    <input type="text" name="sp_onboarding_register_title" value="<?php echo esc_attr(get_option('sp_onboarding_register_title', 'Creează-ți Contul')); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">Descriere Pas Înregistrare</th>
                <td>
                    <textarea name="sp_onboarding_register_intro" rows="2" class="large-text"><?php echo esc_textarea(get_option('sp_onboarding_register_intro', 'Începe prin a-ți crea contul pentru a accesa testul vocațional.')); ?></textarea>
                </td>
            </tr>
        </table>
        
        <h2>Autentificare Google (Opțional)</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Activează Google Login</th>
                <td>
                    <label>
                        <input type="checkbox" name="sp_onboarding_enable_google_login" value="1" <?php checked(get_option('sp_onboarding_enable_google_login'), 1); ?> />
                        Permite utilizatorilor să se autentifice cu Google
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">Google Client ID</th>
                <td>
                    <input type="text" name="sp_onboarding_google_client_id" value="<?php echo esc_attr(get_option('sp_onboarding_google_client_id')); ?>" class="regular-text" />
                    <p class="description">Obține de la <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></p>
                </td>
            </tr>
            <tr>
                <th scope="row">Google Client Secret</th>
                <td>
                    <input type="text" name="sp_onboarding_google_client_secret" value="<?php echo esc_attr(get_option('sp_onboarding_google_client_secret')); ?>" class="regular-text" />
                </td>
            </tr>
        </table>
        
        <h2>Integrare MailerLite (Opțional)</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Cheie API MailerLite</th>
                <td>
                    <input type="text" name="sp_onboarding_mailerlite_api_key" value="<?php echo esc_attr(get_option('sp_onboarding_mailerlite_api_key')); ?>" class="regular-text" placeholder="Introdu cheia API MailerLite" />
                    <p class="description">
                        Găsește cheia API în contul tău MailerLite la <strong>Integrări → Developer API</strong>.
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">ID Grup MailerLite</th>
                <td>
                    <input type="text" name="sp_onboarding_mailerlite_group_id" value="<?php echo esc_attr(get_option('sp_onboarding_mailerlite_group_id')); ?>" class="regular-text" placeholder="ex: 12345678" />
                    <p class="description">
                        ID-ul numeric al grupului în care vor fi adăugați utilizatorii noi.
                    </p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" name="sp_save_settings" class="button button-primary">Salvează Setările</button>
        </p>
    </form>
    
    <hr>
    <h2>Utilizare Shortcode</h2>
    <p><strong>Pentru testul de inteligență multiplă și onboarding:</strong> <code>[sp_onboarding_start]</code></p>
</div>

<script>
jQuery(document).ready(function($) {
    $('#reload-test-questions').on('click', function() {
        const btn = $(this);
        const originalText = btn.text();
        btn.prop('disabled', true).text('⏳ Se reîncarcă...');
        
        $.ajax({
            url: spAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'sp_reload_test_questions',
                nonce: spAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('✓ ' + response.data.message + '\n\nÎntrebări încărcate: ' + response.data.questions_count);
                    location.reload();
                } else {
                    alert('✗ ' + response.data.message);
                    btn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                alert('✗ Eroare de conexiune.');
                btn.prop('disabled', false).text(originalText);
            }
        });
    });
});
</script>