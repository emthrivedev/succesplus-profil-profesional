<?php
/**
 * Admin View: Questions Page
 * File Location: admin/views/view-questions-page.php
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap sp-onboarding-admin">
    <h1>Întrebări Test</h1>
    
    <h2><?php echo $edit_question ? 'Editează Întrebare' : 'Adaugă Întrebare Nouă'; ?></h2>
    <form method="post">
        <?php wp_nonce_field('sp_question_nonce'); ?>
        <input type="hidden" name="question_id" value="<?php echo $edit_question ? $edit_question->id : ''; ?>" />
        
        <table class="form-table">
            <tr>
                <th>Text Întrebare</th>
                <td>
                    <textarea name="question_text" rows="3" class="large-text" required><?php echo $edit_question ? esc_textarea($edit_question->question_text) : ''; ?></textarea>
                </td>
            </tr>
            <tr>
                <th>Categorie Inteligență</th>
                <td>
                    <select name="intelligence_category" required>
                        <option value="">-- Selectează --</option>
                        <?php foreach ($intelligence_types as $key => $type): ?>
                        <option value="<?php echo $key; ?>" <?php echo $edit_question && $edit_question->intelligence_category == $key ? 'selected' : ''; ?>>
                            <?php echo $key . '. ' . $type['name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>Ordine</th>
                <td>
                    <input type="number" name="sort_order" value="<?php echo $edit_question ? $edit_question->sort_order : 0; ?>" />
                </td>
            </tr>
            <tr>
                <th>Activă</th>
                <td>
                    <label>
                        <input type="checkbox" name="is_active" value="1" <?php echo (!$edit_question || $edit_question->is_active) ? 'checked' : ''; ?> />
                        Afișează această întrebare în test
                    </label>
                </td>
            </tr>
        </table>
        
        <p>
            <button type="submit" name="sp_save_question" class="button button-primary"><?php echo $edit_question ? 'Actualizează' : 'Adaugă'; ?> Întrebarea</button>
            <?php if ($edit_question): ?>
                <a href="<?php echo admin_url('admin.php?page=sp-onboarding-questions'); ?>" class="button">Anulează</a>
            <?php endif; ?>
        </p>
    </form>
    
    <hr>
    
    <h2>Întrebări Existente (<?php echo count($questions); ?>)</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 60px;">Ordine</th>
                <th>Întrebare</th>
                <th style="width: 80px;">Categorie</th>
                <th style="width: 80px;">Activă</th>
                <th style="width: 150px;">Acțiuni</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($questions)): ?>
                <tr><td colspan="5" style="text-align: center;">Nu există întrebări. Folosiți butonul de reîncărcare din Setări.</td></tr>
            <?php else: ?>
                <?php foreach ($questions as $q): ?>
                <tr>
                    <td><?php echo $q->sort_order; ?></td>
                    <td><?php echo esc_html($q->question_text); ?></td>
                    <td><?php echo $q->intelligence_category; ?></td>
                    <td><?php echo $q->is_active ? '✓' : '✗'; ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=sp-onboarding-questions&edit=' . $q->id); ?>">Editează</a> |
                        <a href="<?php echo admin_url('admin.php?page=sp-onboarding-questions&action=delete&id=' . $q->id); ?>" onclick="return confirm('Ștergi această întrebare?')">Șterge</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>