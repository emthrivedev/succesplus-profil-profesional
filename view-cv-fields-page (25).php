<?php
/**
 * Admin View: CV Fields Page - DYNAMIC CV BUILDER - FIXED
 * File Location: admin/views/view-cv-fields-page.php
 * FIX: Added parent_id hidden input to form
 */

if (!defined('ABSPATH')) exit;

// Get parent filter from URL
$parent_filter = isset($_GET['parent']) ? intval($_GET['parent']) : 0;
?>

<div class="wrap sp-onboarding-admin">
    <h1>Câmpuri CV - Constructor Dinamic</h1>
    <p class="description">Creează și gestionează structura CV-ului. Poți adăuga secțiuni simple, repetabile sau checkbox-uri.</p>
    
    <h2><?php echo $edit_field ? 'Editează Câmp/Secțiune' : 'Adaugă Câmp/Secțiune Nouă'; ?></h2>
    <form method="post" id="sp-cv-field-form">
        <?php wp_nonce_field('sp_cv_field_nonce'); ?>
        <input type="hidden" name="field_id" value="<?php echo $edit_field ? $edit_field->id : ''; ?>" />
        
        <!-- FIX: Added parent_id hidden input -->
        <input type="hidden" name="parent_id" value="<?php echo $parent_filter; ?>" />
        
        <table class="form-table">
            <tr>
                <th>Nume Câmp (intern)</th>
                <td>
                    <input type="text" name="field_name" value="<?php echo $edit_field ? esc_attr($edit_field->field_name) : ''; ?>" class="regular-text" required />
                    <p class="description">Folosiți lowercase, fără spații (ex: experienta_munca, educatie)</p>
                </td>
            </tr>
            
            <tr>
                <th>Etichetă Câmp (afișată)</th>
                <td>
                    <input type="text" name="field_label" value="<?php echo $edit_field ? esc_attr($edit_field->field_label) : ''; ?>" class="regular-text" required />
                    <p class="description">Textul afișat utilizatorului (ex: Experiență profesională)</p>
                </td>
            </tr>
            
            <tr>
                <th>Tip Secțiune</th>
                <td>
                    <select name="section_type" id="section_type" required>
                        <option value="single" <?php echo $edit_field && $edit_field->section_type === 'single' ? 'selected' : ''; ?>>
                            Câmp Simplu (pentru date precum "Profil profesional", "Competențe")
                        </option>
                        <option value="repeatable" <?php echo $edit_field && $edit_field->section_type === 'repeatable' ? 'selected' : ''; ?>>
                            Secțiune Repetabilă (pentru "Experiență", "Educație", "Limbi")
                        </option>
                        <option value="checkbox" <?php echo $edit_field && $edit_field->section_type === 'checkbox' ? 'selected' : ''; ?>>
                            Checkbox (pentru "Referințe disponibile")
                        </option>
                    </select>
                </td>
            </tr>
            
            <!-- Single field options -->
            <tr class="field-type-row">
                <th>Tip Câmp</th>
                <td>
                    <select name="field_type">
                        <option value="text" <?php echo $edit_field && $edit_field->field_type === 'text' ? 'selected' : ''; ?>>Text</option>
                        <option value="textarea" <?php echo $edit_field && $edit_field->field_type === 'textarea' ? 'selected' : ''; ?>>Textarea</option>
                        <option value="number" <?php echo $edit_field && $edit_field->field_type === 'number' ? 'selected' : ''; ?>>Număr</option>
                        <option value="email" <?php echo $edit_field && $edit_field->field_type === 'email' ? 'selected' : ''; ?>>Email</option>
                        <option value="tel" <?php echo $edit_field && $edit_field->field_type === 'tel' ? 'selected' : ''; ?>>Telefon</option>
                        <option value="select" <?php echo $edit_field && $edit_field->field_type === 'select' ? 'selected' : ''; ?>>Select</option>
                        <option value="container" <?php echo $edit_field && $edit_field->field_type === 'container' ? 'selected' : ''; ?>>Container (pentru repeatable)</option>
                        <option value="checkbox" <?php echo $edit_field && $edit_field->field_type === 'checkbox' ? 'selected' : ''; ?>>Checkbox</option>
                    </select>
                    <p class="description">Tipul de câmp HTML</p>
                </td>
            </tr>
            
            <tr class="field-options-row">
                <th>Opțiuni Câmp (JSON)</th>
                <td>
                    <textarea name="field_options" rows="3" class="large-text"><?php echo $edit_field ? esc_textarea($edit_field->field_options) : ''; ?></textarea>
                    <p class="description">Pentru select: ["Opțiune 1", "Opțiune 2"]. Doar pentru type="select".</p>
                </td>
            </tr>
            
            <tr>
                <th>Descriere</th>
                <td>
                    <textarea name="field_description" rows="2" class="large-text"><?php echo $edit_field ? esc_textarea($edit_field->field_description) : ''; ?></textarea>
                    <p class="description">Text explicativ afișat sub etichetă (ex: "Scrie clar, concis și doar informații relevante")</p>
                </td>
            </tr>
            
            <tr class="field-placeholder-row">
                <th>Placeholder</th>
                <td>
                    <input type="text" name="field_placeholder" value="<?php echo $edit_field ? esc_attr($edit_field->field_placeholder) : ''; ?>" class="regular-text" />
                    <p class="description">Exemplu de text în câmp (ex: "ex: Manager vânzări")</p>
                </td>
            </tr>
            
            <!-- Repeatable section options -->
            <tr class="add-button-text-row" style="display:none;">
                <th>Text Buton "Adaugă"</th>
                <td>
                    <input type="text" name="add_button_text" value="<?php echo $edit_field ? esc_attr($edit_field->add_button_text) : ''; ?>" class="regular-text" />
                    <p class="description">Textul pentru butonul de adăugare (ex: "Adaugă o nouă experiență")</p>
                </td>
            </tr>
            
            <tr>
                <th>Buton de Negare</th>
                <td>
                    <input type="text" name="negation_button_text" value="<?php echo $edit_field ? esc_attr($edit_field->negation_button_text) : ''; ?>" class="regular-text" placeholder="Ex: Nu știu încă, Nu am" />
                    <p class="description">Textul pentru butonul care permite utilizatorului să sară peste acest câmp. Lasă gol dacă nu este nevoie de buton.</p>
                </td>
            </tr>
            
            <tr>
                <th>Obligatoriu</th>
                <td>
                    <label>
                        <input type="checkbox" name="is_required" value="1" <?php echo ($edit_field && $edit_field->is_required) ? 'checked' : ''; ?> />
                        Acest câmp este obligatoriu
                    </label>
                </td>
            </tr>
            
            <tr>
                <th>Ordine</th>
                <td>
                    <input type="number" name="sort_order" value="<?php echo $edit_field ? $edit_field->sort_order : 0; ?>" />
                    <p class="description">Ordinea de afișare în formular</p>
                </td>
            </tr>
            
            <tr>
                <th>Activ</th>
                <td>
                    <label>
                        <input type="checkbox" name="is_active" value="1" <?php echo (!$edit_field || $edit_field->is_active) ? 'checked' : ''; ?> />
                        Afișează acest câmp în formularul CV
                    </label>
                </td>
            </tr>
        </table>
        
        <p>
            <button type="submit" name="sp_save_cv_field" class="button button-primary"><?php echo $edit_field ? 'Actualizează' : 'Adaugă'; ?> Câmpul</button>
            <?php if ($edit_field): ?>
                <a href="<?php echo admin_url('admin.php?page=sp-onboarding-cv-fields' . ($parent_filter ? '&parent=' . $parent_filter : '')); ?>" class="button">Anulează</a>
            <?php endif; ?>
        </p>
    </form>
    
    <?php if ($edit_field && $edit_field->section_type === 'repeatable'): ?>
    <hr>
    <div class="sp-admin-card" style="background: #f0f9ff; border-left: 4px solid #0292B7; padding: 20px; margin: 20px 0;">
        <h3>⚙️ Câmpuri Copil pentru: <?php echo esc_html($edit_field->field_label); ?></h3>
        <p>Această secțiune este <strong>repetabilă</strong>. Adaugă câmpurile care vor apărea în fiecare intrare repetată.</p>
        <a href="<?php echo admin_url('admin.php?page=sp-onboarding-cv-fields&parent=' . $edit_field->id); ?>" class="button button-secondary">
            Gestionează Câmpurile Copil
        </a>
    </div>
    <?php endif; ?>
    
    <?php if ($parent_filter > 0): 
        $parent_field = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->plugin->table_cv_fields} WHERE id = %d", $parent_filter));
        if ($parent_field):
    ?>
    <hr>
    <div class="sp-admin-card" style="background: #dcfce7; border-left: 4px solid #10b981; padding: 20px; margin: 20px 0;">
        <h3>📋 Editezi câmpuri copil pentru: <?php echo esc_html($parent_field->field_label); ?></h3>
        <p>Acestea sunt câmpurile care vor apărea în fiecare intrare din secțiunea repetabilă.</p>
        <a href="<?php echo admin_url('admin.php?page=sp-onboarding-cv-fields'); ?>" class="button">
            ← Înapoi la secțiuni principale
        </a>
    </div>
    <?php endif; endif; ?>
    
    <hr>
    
    <h2>Câmpuri CV Existente <?php echo $parent_filter > 0 ? '(Copil)' : '(Principale)'; ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 60px;">Ordine</th>
                <th>Etichetă</th>
                <th>Nume</th>
                <th style="width: 120px;">Tip Secțiune</th>
                <th style="width: 100px;">Tip Câmp</th>
                <th style="width: 80px;">Obligatoriu</th>
                <th style="width: 80px;">Activ</th>
                <th style="width: 200px;">Acțiuni</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            foreach ($cv_fields as $f): 
                if ($f->parent_id != $parent_filter) continue;
            ?>
            <tr>
                <td><?php echo $f->sort_order; ?></td>
                <td>
                    <?php echo esc_html($f->field_label); ?>
                    <?php if ($f->section_type === 'repeatable'): ?>
                        <span style="background: #dbeafe; color: #1e40af; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 600;">REPEATABLE</span>
                    <?php endif; ?>
                </td>
                <td><code><?php echo esc_html($f->field_name); ?></code></td>
                <td><?php echo esc_html($f->section_type); ?></td>
                <td><?php echo esc_html($f->field_type); ?></td>
                <td><?php echo $f->is_required ? '✔' : '✗'; ?></td>
                <td><?php echo $f->is_active ? '✔' : '✗'; ?></td>
                <td>
                    <a href="<?php echo admin_url('admin.php?page=sp-onboarding-cv-fields&edit=' . $f->id . ($parent_filter ? '&parent=' . $parent_filter : '')); ?>">Editează</a> |
                    <?php if ($f->section_type === 'repeatable'): ?>
                        <a href="<?php echo admin_url('admin.php?page=sp-onboarding-cv-fields&parent=' . $f->id); ?>">Câmpuri Copil</a> |
                    <?php endif; ?>
                    <a href="<?php echo admin_url('admin.php?page=sp-onboarding-cv-fields&action=delete&id=' . $f->id . ($parent_filter ? '&parent=' . $parent_filter : '')); ?>" onclick="return confirm('Ștergi acest câmp?')" style="color: #dc3545;">Șterge</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($cv_fields) || !array_filter($cv_fields, function($f) use ($parent_filter) { return $f->parent_id == $parent_filter; })): ?>
                <tr><td colspan="8" style="text-align: center;">Nu există câmpuri adăugate. <?php echo $parent_filter ? 'Adaugă câmpuri copil mai sus.' : 'Adaugă primul câmp CV.'; ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    function toggleFieldOptions() {
        var sectionType = $('#section_type').val();
        
        if (sectionType === 'repeatable') {
            $('.field-type-row').hide();
            $('.field-options-row').hide();
            $('.field-placeholder-row').hide();
            $('.add-button-text-row').show();
            $('select[name="field_type"]').val('container');
        } else if (sectionType === 'checkbox') {
            $('.field-type-row').hide();
            $('.field-options-row').hide();
            $('.field-placeholder-row').hide();
            $('.add-button-text-row').hide();
            $('select[name="field_type"]').val('checkbox');
        } else {
            $('.field-type-row').show();
            $('.field-options-row').show();
            $('.field-placeholder-row').show();
            $('.add-button-text-row').hide();
        }
    }
    
    $('#section_type').on('change', toggleFieldOptions);
    toggleFieldOptions();
});
</script>

<style>
.sp-admin-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.sp-admin-card h3 {
    margin-top: 0;
    color: #0292B7;
}
</style>