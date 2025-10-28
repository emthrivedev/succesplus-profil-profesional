/**
 * SuccessPlus Onboarding - Enhanced Admin Script
 */

jQuery(document).ready(function($) {
    
    /**
     * JSON Validation for Soft Skills Fields
     */
    $('textarea[name="soft_skills"], textarea[name="field_options"]').on('blur', function() {
        const value = $(this).val().trim();
        
        if (value === '') {
            $(this).css('border-color', '');
            $(this).siblings('.json-error').remove();
            return;
        }
        
        try {
            JSON.parse(value);
            $(this).css('border-color', '#10b981');
            $(this).siblings('.json-error').remove();
            $(this).after('<p class="json-success" style="color: #10b981; font-size: 12px; margin-top: 5px;">✓ Valid JSON format</p>');
            
            // Remove success message after 2 seconds
            setTimeout(function() {
                $('.json-success').fadeOut(function() {
                    $(this).remove();
                });
            }, 2000);
        } catch (e) {
            $(this).css('border-color', '#ef4444');
            $(this).siblings('.json-error, .json-success').remove();
            $(this).after('<p class="json-error" style="color: #ef4444; font-size: 12px; margin-top: 5px;">✗ Invalid JSON format: ' + e.message + '</p>');
        }
    });
    
    /**
     * Confirm Delete Actions
     */
    $('a.delete-link, a[href*="action=delete"]').on('click', function(e) {
        const itemName = $(this).closest('tr').find('td:nth-child(2)').text().trim();
        const confirmMessage = itemName 
            ? 'Are you sure you want to delete "' + itemName + '"? This action cannot be undone.'
            : 'Are you sure you want to delete this item? This action cannot be undone.';
            
        if (!confirm(confirmMessage)) {
            e.preventDefault();
            return false;
        }
    });
    
    /**
     * Copy Shortcode
     */
    $('.copy-shortcode').on('click', function(e) {
        e.preventDefault();
        const shortcode = $(this).data('shortcode') || $(this).text();
        
        // Create temporary input
        const temp = $('<input>');
        $('body').append(temp);
        temp.val(shortcode).select();
        
        try {
            document.execCommand('copy');
            temp.remove();
            
            // Show feedback
            const originalText = $(this).text();
            const originalBg = $(this).css('background-color');
            $(this).text('✓ Copied!').css('background-color', '#10b981').css('color', '#fff');
            
            setTimeout(() => {
                $(this).text(originalText).css('background-color', originalBg).css('color', '');
            }, 2000);
        } catch (err) {
            temp.remove();
            alert('Failed to copy. Please copy manually: ' + shortcode);
        }
    });
    
    /**
     * Test OpenAI Connection
     */
    $('#test-openai-connection').on('click', function(e) {
        e.preventDefault();
        const button = $(this);
        const apiKey = $('input[name="sp_onboarding_openai_key"]').val();
        
        if (!apiKey) {
            showAdminNotice('Please enter an OpenAI API key first.', 'error');
            return;
        }
        
        const originalText = button.text();
        button.prop('disabled', true).text('Testing Connection...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'sp_test_openai_connection',
                api_key: apiKey,
                nonce: typeof spAdminNonce !== 'undefined' ? spAdminNonce : ''
            },
            success: function(response) {
                if (response.success) {
                    showAdminNotice('✓ Connection successful! OpenAI API is working correctly.', 'success');
                } else {
                    showAdminNotice('✗ Connection failed: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
                button.prop('disabled', false).text(originalText);
            },
            error: function(xhr, status, error) {
                showAdminNotice('✗ Connection error: ' + error + '. Please check your internet connection and API key.', 'error');
                button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    /**
     * Show Admin Notice
     */
    function showAdminNotice(message, type) {
        // Remove existing notices
        $('.sp-admin-notice').remove();
        
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const notice = $('<div class="notice ' + noticeClass + ' is-dismissible sp-admin-notice"><p>' + message + '</p></div>');
        
        $('.wrap h1').after(notice);
        
        // Scroll to notice
        $('html, body').animate({
            scrollTop: notice.offset().top - 50
        }, 300);
        
        // Auto-dismiss success notices after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                notice.fadeOut(function() {
                    notice.remove();
                });
            }, 5000);
        }
    }
    
    /**
     * Bulk Actions for Sessions/Questions/Fields
     */
    $('.bulk-action-apply').on('click', function() {
        const action = $('.bulk-action-select').val();
        const selected = $('.item-checkbox:checked');
        
        if (selected.length === 0) {
            showAdminNotice('Please select at least one item.', 'error');
            return;
        }
        
        if (action === '') {
            showAdminNotice('Please select an action.', 'error');
            return;
        }
        
        if (!confirm('Are you sure you want to perform this action on ' + selected.length + ' item(s)?')) {
            return;
        }
        
        const ids = selected.map(function() {
            return $(this).val();
        }).get();
        
        // Perform bulk action via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'sp_bulk_action',
                bulk_action: action,
                ids: ids,
                nonce: typeof spAdminNonce !== 'undefined' ? spAdminNonce : ''
            },
            success: function(response) {
                if (response.success) {
                    showAdminNotice('Bulk action completed successfully.', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showAdminNotice('Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                }
            },
            error: function() {
                showAdminNotice('Connection error. Please try again.', 'error');
            }
        });
    });
    
    /**
     * Select All Checkboxes
     */
    $('#select-all').on('change', function() {
        $('.item-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    /**
     * Session Details Viewer (if implementing modal)
     */
    $('.view-session-details').on('click', function(e) {
        e.preventDefault();
        const sessionId = $(this).data('session-id');
        
        // Load session details via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'sp_get_session_details',
                session_id: sessionId,
                nonce: typeof spAdminNonce !== 'undefined' ? spAdminNonce : ''
            },
            success: function(response) {
                if (response.success) {
                    showSessionModal(response.data);
                } else {
                    showAdminNotice('Failed to load session details.', 'error');
                }
            },
            error: function() {
                showAdminNotice('Connection error. Please try again.', 'error');
            }
        });
    });
    
    /**
     * Show Session Modal
     */
    function showSessionModal(data) {
        const modal = $('<div class="sp-session-modal"></div>');
        const content = $('<div class="sp-session-modal-content"></div>');
        
        content.html(
            '<span class="sp-session-modal-close">&times;</span>' +
            '<h2>Session Details</h2>' +
            '<div class="session-data">' + data.html + '</div>'
        );
        
        modal.append(content);
        $('body').append(modal);
        modal.fadeIn();
        
        // Close modal
        $('.sp-session-modal-close').on('click', function() {
            modal.fadeOut(function() {
                modal.remove();
            });
        });
        
        $('.sp-session-modal').on('click', function(e) {
            if (e.target === this) {
                modal.fadeOut(function() {
                    modal.remove();
                });
            }
        });
        
        // Close on ESC key
        $(document).on('keyup.modal', function(e) {
            if (e.key === 'Escape') {
                modal.fadeOut(function() {
                    modal.remove();
                });
                $(document).off('keyup.modal');
            }
        });
    }
    
    /**
     * Sortable Questions/Fields (if implementing drag-and-drop)
     */
    if ($.fn.sortable) {
        $('.sortable-list').sortable({
            handle: '.sort-handle',
            placeholder: 'sortable-placeholder',
            update: function(event, ui) {
                const order = $(this).sortable('toArray', { attribute: 'data-id' });
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sp_update_sort_order',
                        order: order,
                        type: $(this).data('type'),
                        nonce: typeof spAdminNonce !== 'undefined' ? spAdminNonce : ''
                    },
                    success: function(response) {
                        if (response.success) {
                            showAdminNotice('Order updated successfully.', 'success');
                        }
                    }
                });
            }
        });
    }
    
    /**
     * Help Tooltips
     */
    $('.sp-help-tooltip').on('click', function(e) {
        e.stopPropagation();
        $(this).find('.sp-help-text').toggle();
    });
    
    // Close tooltips when clicking outside
    $(document).on('click', function() {
        $('.sp-help-text').hide();
    });
    
    /**
     * Color Picker Preview
     */
    $('input[type="color"]').on('change', function() {
        const color = $(this).val();
        const preview = $(this).siblings('.color-preview');
        
        if (preview.length === 0) {
            $(this).after('<span class="color-preview" style="display: inline-block; width: 20px; height: 20px; border-radius: 3px; margin-left: 10px; vertical-align: middle; border: 1px solid #ddd;"></span>');
        }
        
        $(this).siblings('.color-preview').css('background-color', color);
    });
    
    // Initialize color previews
    $('input[type="color"]').trigger('change');
    
    /**
     * Form Field Type Change Handler
     */
    $('select[name="field_type"]').on('change', function() {
        const selectedType = $(this).val();
        const optionsRow = $(this).closest('table').find('textarea[name="field_options"]').closest('tr');
        
        if (selectedType === 'select' || selectedType === 'checkbox' || selectedType === 'radio') {
            optionsRow.show();
            optionsRow.find('textarea').prop('required', true);
        } else {
            optionsRow.hide();
            optionsRow.find('textarea').prop('required', false);
        }
    });
    
    // Trigger on page load
    $('select[name="field_type"]').trigger('change');
    
    /**
     * Auto-save indicator for forms
     */
    let formChanged = false;
    
    $('form input, form textarea, form select').on('change', function() {
        formChanged = true;
    });
    
    // Warn before leaving if form has unsaved changes
    $(window).on('beforeunload', function(e) {
        if (formChanged && !$(document.activeElement).is('[type="submit"]')) {
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
    
    // Reset flag on form submission
    $('form').on('submit', function() {
        formChanged = false;
    });
    
    /**
     * Character counter for textareas
     */
    $('textarea.large-text').each(function() {
        const maxLength = $(this).attr('maxlength');
        if (maxLength) {
            const counter = $('<div class="char-counter" style="text-align: right; font-size: 12px; color: #666; margin-top: 5px;">0 / ' + maxLength + '</div>');
            $(this).after(counter);
            
            $(this).on('input', function() {
                const currentLength = $(this).val().length;
                counter.text(currentLength + ' / ' + maxLength);
                
                if (currentLength > maxLength * 0.9) {
                    counter.css('color', '#ef4444');
                } else {
                    counter.css('color', '#666');
                }
            });
        }
    });
    
    /**
     * Enhanced table search/filter
     */
    $('#table-search').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.wp-list-table tbody tr').each(function() {
            const rowText = $(this).text().toLowerCase();
            
            if (rowText.indexOf(searchTerm) === -1) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
    });
    
});