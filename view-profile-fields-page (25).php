<?php
/**
 * Admin View: Profile Fields Page - COMPLETE WITH DESCRIPTIONS
 * File Location: admin/views/view-profile-fields-page.php
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap sp-onboarding-admin">
    <h1>Câmpuri Profil (Înregistrare)</h1>
    <p class="description">Aceste câmpuri apar în formularul de înregistrare. Ordinea și setările lor sunt gestionate aici.</p>
    
    <div style="background: #e0f2fe; border-left: 4px solid #0284c7; padding: 15px; margin: 20px 0; border-radius: 4px;">
        <h3 style="margin-top: 0; color: #0284c7;">ℹ️ Tipuri Speciale de Câmpuri</h3>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li><strong>birthdate</strong> - Câmp dată nașterii, automat conectat cu PsihoProfile (psihoprofile_birthdate)</li>
            <li><strong>sex</strong> - Selector M/F, automat conectat cu PsihoProfile (psihoprofile_sex)</li>
            <li><strong>password</strong> - Câmp parolă cu validare (minimum 8 caractere)</li>
            <li><strong>email</strong> - Câmp email cu validare automată</li>
            <li><strong>tel</strong> - Câmp telefon cu formatare</li>
        </ul>
    </div>
    
    <h2><?php echo $edit_field ? 'Editează Câmp' : 'Adaugă Câmp Nou'; ?></h2>
    <form method="post">
        <?php wp_nonce_field('sp_profile_field_nonce'); ?>
        <input type="hidden" name="field_id" value="<?php echo $edit_field ? $edit_field->id : ''; ?>" />
        
        <table class="form-table">
            <tr>
                <th>Nume Câmp (intern)</th>
                <td>
                    <input type="text" name="field_name" value="<?php echo $edit_field ? esc_attr($edit_field->field_name) : ''; ?>" class="regular-text" required />
                    <p class="description">Lowercase, fără spații (ex: phone, city, bio, birth_date, sex)</p>
                </td>
            </tr>
            <tr>
                <th>Etichetă Câmp</th>
                <td>
                    <input type="text" name="field_label" value="<?php echo $edit_field ? esc_attr($edit_field->field_label) : ''; ?>" class="regular-text" required />
                    <p class="description">Textul afișat utilizatorului (ex: "Data nașterii", "Sex", "Telefon")</p>
                </td>
            </tr>
            <tr>
                <th>Tip Câmp</th>
                <td>
                    <select name="field_type" id="field_type">
                        <optgroup label="Tipuri Standard">
                            <option value="text" <?php echo $edit_field && $edit_field->field_type === 'text' ? 'selected' : ''; ?>>Text</option>
                            <option value="textarea" <?php echo $edit_field && $edit_field->field_type === 'textarea' ? 'selected' : ''; ?>>Textarea</option>
                            <option value="number" <?php echo $edit_field && $edit_field->field_type === 'number' ? 'selected' : ''; ?>>Număr</option>
                            <option value="email" <?php echo $edit_field && $edit_field->field_type === 'email' ? 'selected' : ''; ?>>Email</option>
                            <option value="tel" <?php echo $edit_field && $edit_field->field_type === 'tel' ? 'selected' : ''; ?>>Telefon</option>
                            <option value="select" <?php echo $edit_field && $edit_field->field_type === 'select' ? 'selected' : ''; ?>>Select</option>
                            <option value="password" <?php echo $edit_field && $edit_field->field_type === 'password' ? 'selected' : ''; ?>>Parolă</option>
                        </optgroup>
                        <optgroup label="Tipuri Speciale (PsihoProfile)">
                            <option value="birthdate" <?php echo $edit_field && $edit_field->field_type === 'birthdate' ? 'selected' : ''; ?>>📅 Dată Nașterii (PsihoProfile)</option>
                            <option value="sex" <?php echo $edit_field && $edit_field->field_type === 'sex' ? 'selected' : ''; ?>>👤 Sex M/F (PsihoProfile)</option>
                        </optgroup>
                    </select>
                    <p class="description">
                        <strong>Notă:</strong> Tipurile "birthdate" și "sex" sunt automat sincronizate cu plugin-ul PsihoProfile.<br>
                        Acestea vor salva datele ca <code>psihoprofile_birthdate</code> și <code>psihoprofile_sex</code> în user meta.
                    </p>
                </td>
            </tr>
            <tr class="field-options-row" style="display: none;">
                <th>Opțiuni (JSON)</th>
                <td>
                    <textarea name="field_options" rows="3" class="large-text"><?php echo $edit_field ? esc_textarea($edit_field->field_options) : ''; ?></textarea>
                    <p class="description">Pentru select: ["Opțiune 1", "Opțiune 2"]</p>
                </td>
            </tr>
            <tr>
                <th>Descriere</th>
                <td>
                    <textarea name="field_description" rows="2" class="large-text"><?php echo $edit_field && $edit_field->field_description ? esc_textarea($edit_field->field_description) : ''; ?></textarea>
                    <p class="description">Text explicativ afișat sub câmp (ex: "Necesară pentru testele PsihoProfile", "Minimum 8 caractere")</p>
                </td>
            </tr>
            <tr class="field-placeholder-row">
                <th>Placeholder</th>
                <td>
                    <input type="text" name="field_placeholder" value="<?php echo $edit_field && $edit_field->field_placeholder ? esc_attr($edit_field->field_placeholder) : ''; ?>" class="regular-text" />
                    <p class="description">Exemplu de text în câmp (ex: "ex: 0721 234 567", "ex: nume@email.ro")</p>
                </td>
            </tr>
            <tr>
                <th>Obligatoriu</th>
                <td>
                    <label>
                        <input type="checkbox" name="is_required" value="1" <?php echo ($edit_field && $edit_field->is_required) ? 'checked' : ''; ?> />
                        Câmp obligatoriu - utilizatorul trebuie să completeze
                    </label>
                </td>
            </tr>
            <tr>
                <th>Ordine</th>
                <td>
                    <input type="number" name="sort_order" value="<?php echo $edit_field ? $edit_field->sort_order : 0; ?>" style="width: 100px;" />
                    <p class="description">Ordinea de afișare în formular (ex: 1, 2, 3...)</p>
                </td>
            </tr>
            <tr>
                <th>Activ</th>
                <td>
                    <label>
                        <input type="checkbox" name="is_active" value="1" <?php echo (!$edit_field || $edit_field->is_active) ? 'checked' : ''; ?> />
                        Afișează în formularul de înregistrare
                    </label>
                </td>
            </tr>
        </table>
        
        <p>
            <button type="submit" name="sp_save_profile_field" class="button button-primary"><?php echo $edit_field ? 'Actualizează' : 'Adaugă'; ?> Câmpul</button>
            <?php if ($edit_field): ?>
                <a href="<?php echo admin_url('admin.php?page=sp-onboarding-profile-fields'); ?>" class="button">Anulează</a>
            <?php endif; ?>
        </p>
    </form>
    
    <hr style="margin: 40px 0;">
    
    <h2>Câmpuri Existente (<?php echo count($profile_fields); ?>)</h2>
    
    <?php if (empty($profile_fields)): ?>
        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
            <p style="margin: 0;"><strong>⚠️ Atenție:</strong> Nu există câmpuri de profil configurate. Adaugă câmpurile necesare pentru înregistrare.</p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 60px;">Ordine</th>
                    <th>Etichetă</th>
                    <th>Nume Intern</th>
                    <th style="width: 120px;">Tip</th>
                    <th style="width: 250px;">Descriere</th>
                    <th style="width: 80px;">Obligatoriu</th>
                    <th style="width: 80px;">Activ</th>
                    <th style="width: 150px;">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($profile_fields as $f): ?>
                <tr>
                    <td><strong><?php echo $f->sort_order; ?></strong></td>
                    <td>
                        <strong><?php echo esc_html($f->field_label); ?></strong>
                        <?php if ($f->field_type === 'birthdate' || $f->field_type === 'sex'): ?>
                            <span style="background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; margin-left: 8px;">
                                🔗 PSIHOPROFILE
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <code style="background: #f3f4f6; padding: 4px 8px; border-radius: 3px; font-size: 12px;">
                            <?php echo esc_html($f->field_name); ?>
                        </code>
                    </td>
                    <td>
                        <?php 
                        $type_display = $f->field_type;
                        if ($f->field_type === 'birthdate') {
                            $type_display = '<span style="color: #0284c7; font-weight: 600;">📅 birthdate</span>';
                        } elseif ($f->field_type === 'sex') {
                            $type_display = '<span style="color: #0284c7; font-weight: 600;">👤 sex</span>';
                        }
                        echo $type_display;
                        ?>
                    </td>
                    <td>
                        <?php if ($f->field_description): ?>
                            <span style="font-size: 12px; color: #666;"><?php echo esc_html(substr($f->field_description, 0, 50)); ?><?php echo strlen($f->field_description) > 50 ? '...' : ''; ?></span>
                        <?php else: ?>
                            <em style="color: #999;">-</em>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;">
                        <?php if ($f->is_required): ?>
                            <span style="color: #10b981; font-size: 18px; font-weight: bold;">✓</span>
                        <?php else: ?>
                            <span style="color: #cbd5e0;">✗</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;">
                        <?php if ($f->is_active): ?>
                            <span style="color: #10b981; font-size: 18px; font-weight: bold;">✓</span>
                        <?php else: ?>
                            <span style="color: #ef4444;">✗</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=sp-onboarding-profile-fields&edit=' . $f->id); ?>" class="button button-small">Editează</a>
                        <a href="<?php echo admin_url('admin.php?page=sp-onboarding-profile-fields&action=delete&id=' . $f->id); ?>" 
                           class="button button-small" 
                           style="color: #dc3545;" 
                           onclick="return confirm('Ștergi acest câmp? Această acțiune nu poate fi anulată.')">Șterge</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="background: #f0fdf4; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0;">
            <h4 style="margin-top: 0; color: #10b981;">✓ Recomandări pentru Câmpuri</h4>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li><strong>first_name</strong> și <strong>last_name</strong> - Obligatorii pentru crearea contului</li>
                <li><strong>email</strong> - Obligatoriu, folosit ca username pentru autentificare</li>
                <li><strong>password</strong> și <strong>password_confirm</strong> - Obligatorii pentru securitate</li>
                <li><strong>birth_date</strong> (tip: birthdate) - Necesar pentru PsihoProfile</li>
                <li><strong>sex</strong> (tip: sex) - Necesar pentru PsihoProfile</li>
                <li><strong>phone</strong> - Recomandat pentru contact</li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    function toggleFieldOptions() {
        var fieldType = $('#field_type').val();
        
        if (fieldType === 'select') {
            $('.field-options-row').show();
            $('.field-options-row textarea').prop('required', true);
        } else {
            $('.field-options-row').hide();
            $('.field-options-row textarea').prop('required', false);
        }
        
        // Show/hide placeholder for certain types
        if (fieldType === 'select' || fieldType === 'birthdate' || fieldType === 'sex') {
            $('.field-placeholder-row').hide();
        } else {
            $('.field-placeholder-row').show();
        }
    }
    
    $('#field_type').on('change', toggleFieldOptions);
    toggleFieldOptions(); // Run on page load
});
</script>

<style>
.sp-onboarding-admin .form-table th {
    width: 200px;
    font-weight: 600;
}

.sp-onboarding-admin code {
    background: #f3f4f6;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    color: #0292B7;
    font-weight: 600;
}

.sp-onboarding-admin .wp-list-table {
    border: 1px solid #ddd;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.sp-onboarding-admin .wp-list-table th {
    background: #f9fafb;
    font-weight: 600;
    color: #0292B7;
}
</style>