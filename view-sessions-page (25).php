<?php
/**
 * Admin View: Sessions Page
 * File Location: admin/views/view-sessions-page.php
 * Added: Cleanup incomplete sessions button
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap sp-onboarding-admin">
    <h1>Sesiuni Onboarding</h1>
    <p>Aici puteți vedea toate sesiunile de onboarding ale utilizatorilor.</p>
    
    <?php 
    // Count incomplete sessions
    global $wpdb;
    $incomplete_count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->plugin->table_sessions} WHERE completed_at IS NULL");
    ?>
    
    <div style="margin: 20px 0; padding: 15px; background: #fff; border-left: 4px solid #0292B7; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0;">🧹 Curățare Sesiuni</h3>
        <p>Sesiuni incomplete (nefinalizate): <strong><?php echo $incomplete_count; ?></strong></p>
        <?php if ($incomplete_count > 0): ?>
            <button type="button" id="cleanup-incomplete-sessions" class="button button-secondary" style="background: #dc3545; color: white; border-color: #dc3545;">
                🗑️ Șterge Toate Sesiunile Incomplete (<?php echo $incomplete_count; ?>)
            </button>
            <p class="description">Această acțiune va șterge toate sesiunile care nu au fost finalizate (fără dată de completare).</p>
        <?php else: ?>
            <p style="color: #10b981;">✓ Nu există sesiuni incomplete de șters.</p>
        <?php endif; ?>
    </div>
    
    <?php if (empty($sessions)): ?>
        <p>Nu există sesiuni încă.</p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 200px;">Cheie Sesiune</th>
                    <th style="width: 100px;">Pas Curent</th>
                    <th style="width: 100px;">User ID</th>
                    <th style="width: 150px;">Creat La</th>
                    <th style="width: 150px;">Completat La</th>
                    <th style="width: 100px;">Scor Dominant</th>
                    <th style="width: 150px;">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $session): 
                    $scores = $session->intelligence_scores ? json_decode($session->intelligence_scores, true) : array();
                    $dominant = '';
                    if (!empty($scores)) {
                        arsort($scores);
                        $dominant_type = key($scores);
                        if (isset($intelligence_types[$dominant_type])) {
                            $dominant = $intelligence_types[$dominant_type]['name'];
                        }
                    }
                    
                    $user_info = '';
                    if ($session->user_id) {
                        $user = get_user_by('id', $session->user_id);
                        if ($user) {
                            $user_info = $user->display_name . ' (#' . $session->user_id . ')';
                        }
                    }
                    
                    // Highlight incomplete sessions
                    $row_style = !$session->completed_at ? 'background-color: #fff3cd;' : '';
                ?>
                <tr style="<?php echo $row_style; ?>">
                    <td><?php echo $session->id; ?></td>
                    <td><code style="font-size: 11px;"><?php echo substr($session->session_key, 0, 20) . '...'; ?></code></td>
                    <td>
                        <?php 
                        echo ucfirst($session->current_step);
                        if (!$session->completed_at) {
                            echo ' <span style="color: #dc3545; font-weight: bold;">⚠</span>';
                        }
                        ?>
                    </td>
                    <td><?php echo $user_info ?: '-'; ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($session->created_at)); ?></td>
                    <td>
                        <?php 
                        if ($session->completed_at) {
                            echo date('d.m.Y H:i', strtotime($session->completed_at));
                        } else {
                            echo '<span style="color: #dc3545; font-weight: bold;">Incomplet</span>';
                        }
                        ?>
                    </td>
                    <td><?php echo $dominant ? substr($dominant, 0, 20) . '...' : '-'; ?></td>
                    <td>
                        <button class="button button-small view-session-details" data-id="<?php echo $session->id; ?>">Detalii</button>
                        <a href="<?php echo admin_url('admin.php?page=sp-onboarding-sessions&action=delete&id=' . $session->id); ?>" 
                           class="button button-small" onclick="return confirm('Ștergeți această sesiune?')">Șterge</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Modal for session details -->
        <div id="session-details-modal" style="display: none;">
            <div class="session-details-content"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Cleanup incomplete sessions
            $('#cleanup-incomplete-sessions').on('click', function() {
                if (!confirm('Sigur doriți să ștergeți TOATE sesiunile incomplete? Această acțiune nu poate fi anulată.')) {
                    return;
                }
                
                const btn = $(this);
                const originalText = btn.text();
                btn.prop('disabled', true).text('⏳ Se șterge...');
                
                $.ajax({
                    url: spAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sp_cleanup_incomplete_sessions',
                        nonce: spAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✓ ' + response.data.message + '\n\nSesiuni șterse: ' + response.data.deleted_count);
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
            
            // View session details
            $('.view-session-details').on('click', function() {
                const sessionId = $(this).data('id');
                
                $.ajax({
                    url: spAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sp_view_session',
                        nonce: spAdmin.nonce,
                        session_id: sessionId
                    },
                    success: function(response) {
                        if (response.success) {
                            const modal = $('#session-details-modal');
                            modal.find('.session-details-content').html(response.data.html);
                            
                            // Simple modal display
                            modal.css({
                                'display': 'block',
                                'position': 'fixed',
                                'top': '50%',
                                'left': '50%',
                                'transform': 'translate(-50%, -50%)',
                                'background': 'white',
                                'padding': '20px',
                                'border': '1px solid #ccc',
                                'box-shadow': '0 4px 6px rgba(0,0,0,0.1)',
                                'z-index': '9999',
                                'max-width': '800px',
                                'max-height': '80vh',
                                'overflow': 'auto'
                            });
                            
                            // Add overlay
                            $('body').append('<div class="modal-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9998;"></div>');
                            
                            // Close on overlay click
                            $('.modal-overlay').on('click', function() {
                                modal.hide();
                                $(this).remove();
                            });
                        }
                    }
                });
            });
        });
        </script>
    <?php endif; ?>
</div>