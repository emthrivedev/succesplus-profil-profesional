/**
 * SuccessPlus Onboarding - Complete Frontend Script v4.6
 * FIXED: Proper CV form validation - cannot submit without completing required fields or negating them
 * FIXED: Validation now properly checks all required fields and negation states
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        const container = $('.sp-onboarding-container');
        if (!container.length) {
            console.log('SP Onboarding container not found');
            return;
        }
        
        const sessionKey = container.data('session') || '';
        const isLoggedIn = container.data('logged-in') === 1;
        const savedProgress = container.data('saved-progress');
        const testCompleted = container.data('test-completed') === 1;
        
        console.log('Initialized - Session:', sessionKey, 'Logged in:', isLoggedIn);
        
        const formDataStore = {
            test: {},
            cv: {}
        };
        
        let currentQuestionIndex = 0;
        let totalQuestions = 0;
        const testAnswers = {};
        
        let isRevalidatingAfterSubmit = false;
        let missedQuestionsList = [];
        
        let cooldownActive = false;
        let cachedResultsHtml = null;
        
        const repeatableCounters = {};
        
        /**
         * ================================================
         * IMPROVED GOOGLE-STYLE DATE PICKER
         * ================================================
         */
        
        const monthNames = [
            'Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie',
            'Iulie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'
        ];
        
        const monthNamesShort = [
            'Ian', 'Feb', 'Mar', 'Apr', 'Mai', 'Iun',
            'Iul', 'Aug', 'Sep', 'Oct', 'Noi', 'Dec'
        ];
        
        const dayNames = ['Lu', 'Ma', 'Mi', 'Jo', 'Vi', 'Sâ', 'Du'];
        
        function initGoogleDatePicker() {
            $('input[type="date"]').each(function() {
                const $input = $(this);
                const isRequired = $input.prop('required');
                const fieldName = $input.attr('name') || 'birth_date';
                const fieldId = $input.attr('id') || 'birth_date';
                
                const currentYear = new Date().getFullYear();
                const defaultYear = currentYear - 25;
                let yearOptions = '';
                for (let year = currentYear - 10; year >= currentYear - 100; year--) {
                    const selected = year === defaultYear ? ' selected' : '';
                    yearOptions += `<option value="${year}"${selected}>${year}</option>`;
                }
                
                const pickerHTML = `
                    <div class="sp-google-datepicker">
                        <div class="sp-date-input-wrapper">
                            <input type="text" 
                                   class="sp-date-display" 
                                   placeholder="zz/ll/aaaa" 
                                   maxlength="10"
                                   ${isRequired ? 'required' : ''} />
                            <span class="sp-calendar-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            </span>
                        </div>
                        <input type="hidden" 
                               name="${fieldName}" 
                               id="${fieldId}"
                               class="sp-date-value" 
                               ${isRequired ? 'required' : ''} />
                        <div class="sp-date-picker-modal" style="display: none;">
                            <div class="sp-picker-header">
                                <button type="button" class="sp-month-nav sp-prev-month" aria-label="Luna anterioară">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <polyline points="15 18 9 12 15 6"></polyline>
                                    </svg>
                                </button>
                                <div class="sp-month-year-selectors">
                                    <select class="sp-month-select">
                                        ${monthNames.map((month, index) => 
                                            `<option value="${index}">${month}</option>`
                                        ).join('')}
                                    </select>
                                    <select class="sp-year-select">
                                        ${yearOptions}
                                    </select>
                                </div>
                                <button type="button" class="sp-month-nav sp-next-month" aria-label="Luna următoare">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </button>
                            </div>
                            <div class="sp-picker-days-header"></div>
                            <div class="sp-picker-days-grid"></div>
                        </div>
                    </div>
                `;
                
                $input.replaceWith(pickerHTML);
            });
            
            $('.sp-google-datepicker').each(function() {
                initDatePickerInstance($(this));
            });
        }
        
        function initDatePickerInstance($picker) {
            const $display = $picker.find('.sp-date-display');
            const $modal = $picker.find('.sp-date-picker-modal');
            const $hiddenInput = $picker.find('.sp-date-value');
            const $monthSelect = $picker.find('.sp-month-select');
            const $yearSelect = $picker.find('.sp-year-select');
            const $daysHeader = $picker.find('.sp-picker-days-header');
            const $daysGrid = $picker.find('.sp-picker-days-grid');
            const $calendarIcon = $picker.find('.sp-calendar-icon');
            
            const currentYear = new Date().getFullYear();
            let currentDate = new Date();
            currentDate.setFullYear(currentYear - 25);
            let selectedDate = null;
            
            $daysHeader.html(dayNames.map(day => `<div class="sp-day-name">${day}</div>`).join(''));
            
            $display.on('input', function(e) {
                let value = $(this).val().replace(/[^\d]/g, '');
                
                if (value.length >= 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2);
                }
                if (value.length >= 5) {
                    value = value.substring(0, 5) + '/' + value.substring(5, 9);
                }
                
                $(this).val(value);
                
                if (value.length === 10) {
                    const parts = value.split('/');
                    const day = parseInt(parts[0]);
                    const month = parseInt(parts[1]);
                    const year = parseInt(parts[2]);
                    
                    if (day >= 1 && day <= 31 && month >= 1 && month <= 12 && year >= 1900 && year <= new Date().getFullYear()) {
                        const date = new Date(year, month - 1, day);
                        const today = new Date();
                        
                        if (date <= today && date.getMonth() === month - 1) {
                            selectedDate = date;
                            currentDate = new Date(date);
                            $hiddenInput.val(`${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`);
                            $(this).css('border-color', 'var(--sp-success)');
                        } else {
                            $(this).css('border-color', 'var(--sp-error)');
                            $hiddenInput.val('');
                        }
                    } else {
                        $(this).css('border-color', 'var(--sp-error)');
                        $hiddenInput.val('');
                    }
                }
            });
            
            $calendarIcon.on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                $('.sp-date-picker-modal').not($modal).hide();
                
                if ($modal.is(':visible')) {
                    $modal.fadeOut(200);
                } else {
                    $modal.fadeIn(200);
                    renderCalendar();
                }
            });
            
            $picker.find('.sp-prev-month').on('click', function(e) {
                e.stopPropagation();
                currentDate.setMonth(currentDate.getMonth() - 1);
                renderCalendar();
            });
            
            $picker.find('.sp-next-month').on('click', function(e) {
                e.stopPropagation();
                currentDate.setMonth(currentDate.getMonth() + 1);
                renderCalendar();
            });
            
            $monthSelect.on('change', function(e) {
                e.stopPropagation();
                currentDate.setMonth(parseInt($(this).val()));
                renderCalendar();
            });
            
            $yearSelect.on('change', function(e) {
                e.stopPropagation();
                currentDate.setFullYear(parseInt($(this).val()));
                renderCalendar();
            });
            
            function renderCalendar() {
                const year = currentDate.getFullYear();
                const month = currentDate.getMonth();
                
                $monthSelect.val(month);
                $yearSelect.val(year);
                
                const firstDay = new Date(year, month, 1);
                const lastDay = new Date(year, month + 1, 0);
                const prevLastDay = new Date(year, month, 0);
                
                const firstDayOfWeek = (firstDay.getDay() + 6) % 7;
                const daysInMonth = lastDay.getDate();
                const daysInPrevMonth = prevLastDay.getDate();
                
                let daysHTML = '';
                
                for (let i = firstDayOfWeek - 1; i >= 0; i--) {
                    const day = daysInPrevMonth - i;
                    daysHTML += `<button type="button" class="sp-day sp-day-other" disabled>${day}</button>`;
                }
                
                const today = new Date();
                const isCurrentMonth = today.getMonth() === month && today.getFullYear() === year;
                
                for (let day = 1; day <= daysInMonth; day++) {
                    const date = new Date(year, month, day);
                    const isToday = isCurrentMonth && day === today.getDate();
                    const isFuture = date > today;
                    const isSelected = selectedDate && 
                        selectedDate.getDate() === day && 
                        selectedDate.getMonth() === month && 
                        selectedDate.getFullYear() === year;
                    
                    let classes = 'sp-day';
                    if (isToday) classes += ' sp-day-today';
                    if (isSelected) classes += ' sp-day-selected';
                    if (isFuture) classes += ' sp-day-disabled';
                    
                    daysHTML += `<button type="button" class="${classes}" data-day="${day}" ${isFuture ? 'disabled' : ''}>${day}</button>`;
                }
                
                const totalCells = Math.ceil((firstDayOfWeek + daysInMonth) / 7) * 7;
                const remainingCells = totalCells - (firstDayOfWeek + daysInMonth);
                for (let day = 1; day <= remainingCells; day++) {
                    daysHTML += `<button type="button" class="sp-day sp-day-other" disabled>${day}</button>`;
                }
                
                $daysGrid.html(daysHTML);
                
                $daysGrid.find('.sp-day:not(.sp-day-disabled):not(.sp-day-other)').on('click', function() {
                    const day = parseInt($(this).data('day'));
                    selectDate(year, month, day);
                });
            }
            
            function selectDate(year, month, day) {
                selectedDate = new Date(year, month, day);
                
                const d = String(day).padStart(2, '0');
                const m = String(month + 1).padStart(2, '0');
                const y = year;
                
                $display.val(`${d}/${m}/${y}`);
                $hiddenInput.val(`${y}-${m}-${d}`);
                $modal.fadeOut(200);
                $display.css('border-color', 'var(--sp-success)');
                $picker.find('.sp-date-error').remove();
            }
            
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.sp-google-datepicker').length) {
                    $modal.fadeOut(200);
                }
            });
            
            $modal.on('click', function(e) {
                e.stopPropagation();
            });
        }
        
        initGoogleDatePicker();
        
        /**
         * PASSWORD VISIBILITY TOGGLE
         */
        $(document).on('click', '.sp-toggle-password', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const targetId = $(this).data('target');
            const $input = $('#' + targetId);
            const $eyeOpen = $(this).find('.sp-eye-open');
            const $eyeClosed = $(this).find('.sp-eye-closed');
            
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $eyeOpen.hide();
                $eyeClosed.show();
            } else {
                $input.attr('type', 'password');
                $eyeOpen.show();
                $eyeClosed.hide();
            }
        });
        
        /**
         * PASSWORD STRENGTH INDICATOR
         */
        $('input[type="password"]').on('input', function() {
            const password = $(this).val();
            const $indicator = $(this).closest('.sp-form-group').find('.sp-password-strength');
            
            if (!$indicator.length) return;
            
            if (password.length === 0) {
                $indicator.removeClass('active weak medium strong');
                return;
            }
            
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            $indicator.addClass('active').removeClass('weak medium strong');
            
            if (strength <= 2) {
                $indicator.addClass('weak');
            } else if (strength <= 3) {
                $indicator.addClass('medium');
            } else {
                $indicator.addClass('strong');
            }
        });
        
        /**
         * PASSWORD CONFIRMATION VALIDATION
         */
        $('#reg_password_confirm').on('input', function() {
            const password = $('#reg_password').val();
            const confirm = $(this).val();
            
            if (confirm.length > 0) {
                if (password === confirm) {
                    $(this).css('border-color', 'var(--sp-success)');
                } else {
                    $(this).css('border-color', 'var(--sp-error)');
                }
            } else {
                $(this).css('border-color', 'var(--sp-border)');
            }
        });
        
        /**
         * EMAIL VALIDATION
         */
        $('#reg_email').on('blur', function() {
            const email = $(this).val();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email && !emailRegex.test(email)) {
                $(this).css('border-color', 'var(--sp-error)');
            } else {
                $(this).css('border-color', 'var(--sp-border)');
            }
        });
        
        /**
         * REGISTRATION FORM SUBMISSION
         */
        $('#sp-register-form').on('submit', function(e) {
            e.preventDefault();
            
            const password = $('#reg_password').val();
            const passwordConfirm = $('#reg_password_confirm').val();
            const phone = $('#reg_phone').val();
            
            let birthDate = '';
            let sex = '';
            
            if (password !== passwordConfirm) {
                showError('Parolele nu se potrivesc');
                return;
            }
            
            if (password.length < 8) {
                showError('Parola trebuie să aibă cel puțin 8 caractere');
                return;
            }
            
            if (!phone) {
                showError('Numărul de telefon este obligatoriu');
                return;
            }
            
            if ($('#reg_birth_date').length > 0) {
                birthDate = $('#reg_birth_date').val();
                if ($('#reg_birth_date').prop('required') && !birthDate) {
                    showError('Data nașterii este obligatorie');
                    return;
                }
            }
            
            if ($('#reg_sex').length > 0) {
                sex = $('#reg_sex').val();
                if ($('#reg_sex').prop('required') && (!sex || (sex !== 'M' && sex !== 'F'))) {
                    showError('Selectați sexul (Masculin sau Feminin)');
                    return;
                }
            }
            
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.text();
            submitBtn.prop('disabled', true).text('Se creează contul...');
            
            $.ajax({
                url: spOnboarding.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sp_complete_registration',
                    nonce: spOnboarding.nonce,
                    session_key: sessionKey,
                    first_name: $('#reg_first_name').val(),
                    last_name: $('#reg_last_name').val(),
                    email: $('#reg_email').val(),
                    password: password,
                    phone: phone,
                    birth_date: birthDate,
                    sex: sex
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess('✔ Înregistrare finalizată! Te redirecționăm către test...');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showError(response.data.message);
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    showError('Eroare de conexiune.');
                    submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
        
        /**
         * Load saved progress
         */
        function loadSavedProgress() {
            if (!savedProgress || typeof savedProgress !== 'object') {
                return false;
            }
            
            console.log('Loading saved progress:', savedProgress);
            
            if (savedProgress.answers && typeof savedProgress.answers === 'object') {
                Object.keys(savedProgress.answers).forEach(function(questionId) {
                    testAnswers[questionId] = savedProgress.answers[questionId];
                    
                    const $radio = $('[name="question_' + questionId + '"][value="' + savedProgress.answers[questionId] + '"]');
                    if ($radio.length) {
                        $radio.prop('checked', true);
                        $radio.closest('.sp-option').addClass('sp-option-selected');
                    }
                });
                
                console.log('Loaded', Object.keys(testAnswers).length, 'answers');
            }
            
            if (savedProgress.current_question !== undefined && savedProgress.current_question !== null) {
                currentQuestionIndex = parseInt(savedProgress.current_question);
                
                const maxIndex = $('.sp-question').length - 1;
                if (currentQuestionIndex > maxIndex) {
                    currentQuestionIndex = maxIndex;
                } else if (currentQuestionIndex < 0) {
                    currentQuestionIndex = 0;
                }
                
                console.log('Resuming from question index:', currentQuestionIndex);
                return true;
            }
            
            return false;
        }
        
        /**
         * Handle skip test button
         */
        $('#sp-skip-test-btn').on('click', function(e) {
            e.preventDefault();
            
            const answeredCount = Object.keys(testAnswers).length;
            
            let confirmMessage = 'Sigur doriți să omiteți testul?';
            if (answeredCount > 0) {
                confirmMessage = `Ai răspuns la ${answeredCount} întrebări.\n\nProgresul tău va fi salvat automat.\nContinuă mai târziu?`;
            }
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            const btn = $(this);
            const originalText = btn.html();
            btn.prop('disabled', true).html('<span style="font-size: 24px;">⏳ </span> Se salvează...');
            
            $.ajax({
                url: spOnboarding.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sp_skip_test',
                    nonce: spOnboarding.nonce,
                    current_question: currentQuestionIndex,
                    answers: testAnswers
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess(response.data.message);
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url;
                        }, 2000);
                    } else {
                        showError(response.data.message);
                        btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function() {
                    showError('Eroare de conexiune. Vă rugăm încercați din nou.');
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });
        
        /**
         * Handle CV skip button
         */
        $('#sp-skip-cv-btn').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Sigur doriți să omiteți completarea CV-ului?\n\nPoți să-l completezi mai târziu.')) {
                return;
            }
            
            const btn = $(this);
            const originalText = btn.html();
            btn.prop('disabled', true).html('<span style="font-size: 24px;">⏳ </span> Se salvează...');
            
            $.ajax({
                url: spOnboarding.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sp_skip_cv',
                    nonce: spOnboarding.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess(response.data.message);
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url;
                        }, 2000);
                    } else {
                        showError(response.data.message);
                        btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function() {
                    showError('Eroare de conexiune. Vă rugăm încercați din nou.');
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });
        
        function goToStep(step, skipAnimation) {
            console.log('Going to step:', step);
            
            if (step === 'results' && cachedResultsHtml) {
                displayCachedResults();
                return;
            }
            
            $('.sp-step').removeClass('active');
            $('.sp-progress-step').removeClass('active').addClass('completed');
            
            $('#sp-step-' + step).addClass('active');
            $('.sp-progress-step[data-step="' + step + '"]').addClass('active').removeClass('completed');
            $('.sp-progress-step[data-step="' + step + '"]').nextAll().removeClass('completed');
            
            if (!testCompleted) {
                updateBackButton(step);
            } else {
                $('.sp-btn-back').not('.sp-question-prev').remove();
            }
            
            if (!skipAnimation) {
                $('html, body').animate({ scrollTop: container.offset().top - 50 }, 500);
            }
            
            if (step === 'test' && Object.keys(formDataStore.test).length > 0) {
                restoreTestData();
            }
            if (step === 'cv' && Object.keys(formDataStore.cv).length > 0) {
                restoreCVData();
            }
        }
        
        function displayCachedResults() {
            console.log('Displaying cached results');
            
            $('.sp-step').removeClass('active');
            $('.sp-progress-step').removeClass('active').addClass('completed');
            
            $('#sp-step-results').addClass('active');
            $('.sp-progress-step[data-step="results"]').addClass('active').removeClass('completed');
            $('.sp-progress-step[data-step="results"]').nextAll().removeClass('completed');
            
            const resultsContainer = $('.sp-results-content');
            resultsContainer.html(cachedResultsHtml);
            
            $('.sp-loading').hide();
            resultsContainer.show();
            
            setTimeout(function() {
                $('.sp-skill-bar-fill').each(function() {
                    const targetWidth = $(this).data('width');
                    $(this).css('width', '0');
                    $(this).animate({ width: targetWidth + '%' }, 1200, 'swing');
                });
            }, 300);
            
            if (!testCompleted) {
                updateBackButton('results');
            }
            
            $('html, body').animate({ scrollTop: container.offset().top - 50 }, 500);
        }
        
        function updateBackButton(currentStep) {
            $('.sp-btn-back').not('.sp-question-prev').remove();
            
            if (testCompleted) {
                return;
            }
            
            let backStep = null;
            let targetStepElement = null;
            
            if (currentStep === 'results') {
                backStep = 'test';
                targetStepElement = '#sp-step-results';
            } else if (currentStep === 'cv') {
                backStep = 'results';
                targetStepElement = '#sp-step-cv';
            }
            
            if (!backStep || !targetStepElement) {
                return;
            }
            
            const backButton = $('<button type="button" class="sp-btn sp-btn-back sp-step-back">Înapoi</button>');
            
            backButton.on('click', function(e) {
                e.preventDefault();
                goToStep(backStep);
                return false;
            });
            
            const stepElement = $(targetStepElement);
            let navContainer = stepElement.find('.sp-nav-buttons');
            
            if (navContainer.length > 0) {
                navContainer.prepend(backButton);
            }
        }
        
        function initQuestionNavigation() {
            const questions = $('.sp-question');
            totalQuestions = questions.length;
            
            if (totalQuestions === 0) return;
            
            console.log('Total questions found:', totalQuestions);
            
            const hasProgress = loadSavedProgress();
            
            questions.hide();
            
            if (hasProgress && currentQuestionIndex > 0) {
                questions.eq(currentQuestionIndex).show().addClass('sp-question-active');
            } else {
                questions.eq(0).show().addClass('sp-question-active');
                currentQuestionIndex = 0;
            }
            
            updateQuestionProgress();
            updateQuestionNavButtons();
        }
        
        function updateQuestionProgress() {
            $('.sp-current-question').text(currentQuestionIndex + 1);
            $('.sp-total-questions').text(totalQuestions);
            
            const answeredCount = Object.keys(testAnswers).length;
            console.log('Progress: Question', (currentQuestionIndex + 1), '/', totalQuestions, '| Answered:', answeredCount);
        }
        
        function updateQuestionNavButtons() {
            const $prevBtn = $('.sp-question-prev');
            const $submitBtn = $('.sp-test-submit');
            
            if (currentQuestionIndex === 0) {
                $prevBtn.hide();
            } else {
                $prevBtn.show();
            }
            
            const onLastQuestion = currentQuestionIndex === totalQuestions - 1;
            const allQuestionsAnswered = Object.keys(testAnswers).length === totalQuestions;
            
            if (onLastQuestion || allQuestionsAnswered) {
                $submitBtn.show();
            } else {
                $submitBtn.hide();
            }
        }
        
        function showQuestion(index, autoAdvance = false) {
            const questions = $('.sp-question');
            
            if (index < 0 || index >= totalQuestions) {
                return;
            }
            
            questions.removeClass('sp-question-active').hide();
            
            if (autoAdvance) {
                setTimeout(function() {
                    questions.eq(index).fadeIn(300).addClass('sp-question-active');
                }, 100);
            } else {
                questions.eq(index).show().addClass('sp-question-active');
            }
            
            currentQuestionIndex = index;
            updateQuestionProgress();
            updateQuestionNavButtons();
            
            if (autoAdvance) {
                $('html, body').animate({ 
                    scrollTop: $('.sp-question-progress').offset().top - 100 
                }, 300);
            }
        }
        
        /**
         * AUTO-ADVANCE ON ANSWER SELECTION
         */
        $(document).on('click', '.sp-option', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (cooldownActive) {
                return false;
            }
            
            const $label = $(this);
            const $input = $label.find('input[type="radio"]');
            
            if ($input.prop('disabled')) {
                return false;
            }
            
            const question = $label.closest('.sp-question');
            const questionId = question.data('question-id');
            const selectedAnswer = $input.val();
            
            $input.prop('checked', true);
            
            testAnswers[questionId] = selectedAnswer;
            question.removeClass('sp-unanswered');
            
            question.find('.sp-option').removeClass('sp-option-selected');
            $label.addClass('sp-option-selected');
            
            const allQuestionsAnswered = Object.keys(testAnswers).length === totalQuestions;
            
            if (allQuestionsAnswered) {
                updateQuestionNavButtons();
                if (isRevalidatingAfterSubmit) {
                    checkAndNavigateToNextMissed();
                }
                return false;
            }
            
            let nextIndex;
            if (isRevalidatingAfterSubmit && missedQuestionsList.length > 0) {
                nextIndex = getNextMissedQuestionIndex();
                if (nextIndex === -1) {
                    isRevalidatingAfterSubmit = false;
                    missedQuestionsList = [];
                    updateQuestionNavButtons();
                    return false;
                }
            } else {
                nextIndex = currentQuestionIndex + 1;
            }
            
            setTimeout(function() {
                if (nextIndex < totalQuestions) {
                    cooldownActive = true;
                    
                    showQuestion(nextIndex, true);
                    
                    const nextQuestion = $('.sp-question').eq(nextIndex);
                    const radioInputs = nextQuestion.find('input[type="radio"]');
                    radioInputs.prop('disabled', true);
                    nextQuestion.find('.sp-option').css('opacity', '0.6').css('cursor', 'not-allowed');
                    
                    setTimeout(function() {
                        radioInputs.prop('disabled', false);
                        nextQuestion.find('.sp-option').css('opacity', '1').css('cursor', 'pointer');
                        cooldownActive = false;
                    }, 2000);
                } else {
                    updateQuestionNavButtons();
                }
            }, 300);
            
            return false;
        });
        
        function getNextMissedQuestionIndex() {
            for (let i = 0; i < missedQuestionsList.length; i++) {
                const missedQuestion = missedQuestionsList[i];
                if (!testAnswers[missedQuestion.id]) {
                    return missedQuestion.number - 1;
                }
            }
            return -1;
        }
        
        function checkAndNavigateToNextMissed() {
            const nextMissedIndex = getNextMissedQuestionIndex();
            if (nextMissedIndex === -1) {
                isRevalidatingAfterSubmit = false;
                missedQuestionsList = [];
                updateQuestionNavButtons();
            } else {
                showQuestion(nextMissedIndex, true);
            }
        }
        
        $(document).on('click', '.sp-question-prev', function(e) {
            e.preventDefault();
            if (currentQuestionIndex > 0) {
                showQuestion(currentQuestionIndex - 1, false);
            }
        });
        
        function saveTestData() {
            formDataStore.test = Object.assign({}, testAnswers);
        }
        
        function restoreTestData() {
            Object.keys(formDataStore.test).forEach(function(questionId) {
                const value = formDataStore.test[questionId];
                testAnswers[questionId] = value;
                $('[name="question_' + questionId + '"][value="' + value + '"]').prop('checked', true)
                    .closest('.sp-option').addClass('sp-option-selected');
            });
        }
        
        function saveCVData() {
            const formData = new FormData($('#sp-cv-form')[0]);
            formDataStore.cv = {};
            for (let [key, value] of formData.entries()) {
                if (key.endsWith('[]')) {
                    const fieldName = key.replace('[]', '');
                    if (!formDataStore.cv[fieldName]) {
                        formDataStore.cv[fieldName] = [];
                    }
                    formDataStore.cv[fieldName].push(value);
                } else {
                    formDataStore.cv[key] = value;
                }
            }
        }
        
        function restoreCVData() {
            Object.keys(formDataStore.cv).forEach(function(fieldName) {
                const value = formDataStore.cv[fieldName];
                const input = $('[name="' + fieldName + '"], [name="' + fieldName + '[]"]');
                if (input.is(':checkbox')) {
                    if (Array.isArray(value)) {
                        value.forEach(function(v) {
                            $('[name="' + fieldName + '[]"][value="' + v + '"]').prop('checked', true);
                        });
                    }
                } else {
                    input.val(value);
                }
            });
        }
        
        /**
         * Test Form Submission
         */
        $('#sp-test-form').on('submit', function(e) {
            e.preventDefault();
            
            isRevalidatingAfterSubmit = false;
            missedQuestionsList = [];
            
            const allQuestions = [];
            $('.sp-question').each(function() {
                allQuestions.push({
                    id: $(this).data('question-id'),
                    index: $(this).data('index')
                });
            });
            
            const missingQuestions = [];
            allQuestions.forEach(function(q, index) {
                if (!testAnswers[q.id]) {
                    missingQuestions.push({ 
                        number: index + 1, 
                        id: q.id,
                        index: index
                    });
                }
            });
            
            if (missingQuestions.length > 0) {
                const missingText = missingQuestions.map(q => 'Întrebarea ' + q.number).join(', ');
                showError('Vă rugăm să răspundeți la toate întrebările. Lipsesc răspunsuri la: ' + missingText);
                
                isRevalidatingAfterSubmit = true;
                missedQuestionsList = missingQuestions;
                
                showQuestion(missingQuestions[0].index);
                updateQuestionNavButtons();
                
                return false;
            }
            
            saveTestData();
            goToStep('results');
            
            $.ajax({
                url: spOnboarding.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'sp_save_test_answers',
                    nonce: spOnboarding.nonce,
                    session_key: sessionKey,
                    answers: testAnswers
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.ai_analysis_fallback === true) {
                            showError('Notă: Analiza completă AI nu este disponibilă temporar. Am generat o analiză de bază a rezultatelor tale.');
                        }
                        
                        cachedResultsHtml = response.data.results_html;
                        displayIntelligenceResults(response.data);
                    } else {
                        showError('Eroare: ' + (response.data && response.data.message ? response.data.message : 'Eroare necunoscută'));
                        goToStep('test');
                    }
                },
                error: function(xhr, status, error) {
                    showError('Eroare de conexiune. Verificați internetul și încercați din nou.');
                    goToStep('test');
                }
            });
        });
        
        function displayIntelligenceResults(data) {
            const resultsContainer = $('.sp-results-content');
            
            if (data.results_html) {
                resultsContainer.html(data.results_html);
                
                setTimeout(function() {
                    $('.sp-skill-bar-fill').each(function() {
                        const targetWidth = $(this).data('width');
                        $(this).css('width', '0');
                        $(this).animate({ width: targetWidth + '%' }, 1200, 'swing');
                    });
                }, 300);
            } else {
                resultsContainer.html('<p class="sp-error-message">Nu s-au putut genera rezultatele.</p>');
            }
            
            $('.sp-loading').hide();
            resultsContainer.show();
        }
        
        /**
         * Continue button
         */
        $(document).on('click', '.sp-continue-btn', function() {
            goToStep('cv');
        });
        
        $(document).on('click', '.sp-read-more-btn', function() {
            const section = $(this).closest('.sp-collapsible-section');
            const content = section.find('.sp-collapsible-content');
            
            if (content.hasClass('sp-collapsed')) {
                content.removeClass('sp-collapsed');
                $(this).html('<span class="sp-arrow">▲</span> Citește mai puțin');
            } else {
                content.addClass('sp-collapsed');
                $(this).html('<span class="sp-arrow">▼</span> Citește mai mult');
            }
        });
        
        $(document).on('click', '.sp-btn-negation', function() {
            const fieldName = $(this).data('field');
            const formGroup = $(this).closest('.sp-form-group');
            const input = formGroup.find('input, textarea, select').not('.negation-state');
            const negationState = formGroup.find('.negation-state');
            const negationMessage = formGroup.find('.sp-negation-message');
            
            if (negationState.val() === '1') {
                // Deactivating negation - reactivate field
                negationState.val('0');
                input.prop('disabled', false).removeClass('sp-field-negated');
                negationMessage.slideUp(200);
                $(this).removeClass('sp-btn-negation-active');
            } else {
                // Activating negation - disable and clear field
                negationState.val('1');
                input.prop('disabled', true).addClass('sp-field-negated').val('');
                negationMessage.slideDown(200);
                $(this).addClass('sp-btn-negation-active');
            }
        });
        
        /**
         * REPEATABLE SECTIONS HANDLERS
         */
        $(document).on('click', '.sp-add-repeatable', function(e) {
            e.preventDefault();
            
            const sectionName = $(this).data('section');
            const templateId = $(this).data('template');
            const container = $('#container-' + sectionName);
            
            if (!repeatableCounters[sectionName]) {
                repeatableCounters[sectionName] = 0;
            }
            
            const currentIndex = repeatableCounters[sectionName];
            
            const template = document.getElementById(templateId);
            if (!template) {
                console.error('Template not found:', templateId);
                return;
            }
            
            const clone = template.content.cloneNode(true);
            
            $(clone).find('[name]').each(function() {
                const oldName = $(this).attr('name');
                const newName = oldName.replace('[INDEX]', '[' + currentIndex + ']');
                $(this).attr('name', newName);
            });
            
            $(clone).find('.entry-index').text(currentIndex + 1);
            
            container.append(clone);
            repeatableCounters[sectionName]++;
            
            container.children().last().hide().slideDown(300);
            
            $('html, body').animate({
                scrollTop: container.children().last().offset().top - 100
            }, 300);
        });
        
        $(document).on('click', '.sp-btn-remove-entry', function(e) {
            e.preventDefault();
            
            if (!confirm('Sigur doriți să ștergi această intrare?')) {
                return;
            }
            
            const entry = $(this).closest('.sp-repeatable-entry');
            const container = entry.parent();
            
            entry.slideUp(300, function() {
                $(this).remove();
                
                container.find('.sp-repeatable-entry').each(function(index) {
                    $(this).find('.entry-index').text(index + 1);
                    
                    $(this).find('[name]').each(function() {
                        const name = $(this).attr('name');
                        const newName = name.replace(/\[\d+\]/, '[' + index + ']');
                        $(this).attr('name', newName);
                    });
                });
            });
        });
        
        $(document).on('click', '.sp-section-negation', function(e) {
            e.preventDefault();
            
            const sectionName = $(this).data('section');
            const section = $(this).closest('.sp-repeatable-section');
            const container = section.find('.sp-repeatable-container');
            const addButton = section.find('.sp-add-repeatable');
            const negationState = section.find('.section-negation-state');
            const negationMessage = section.find('.sp-section-negation-message');
            
            if (negationState.val() === '1') {
                // Deactivating section negation
                negationState.val('0');
                container.show();
                addButton.show();
                negationMessage.slideUp(200);
                $(this).removeClass('sp-btn-negation-active');
            } else {
                // Activating section negation - clear and hide section
                negationState.val('1');
                container.html(''); // Clear all entries
                repeatableCounters[sectionName] = 0;
                container.hide();
                addButton.hide();
                negationMessage.slideDown(200);
                $(this).addClass('sp-btn-negation-active');
            }
        });
        
        /**
         * ================================================
         * CV FORM VALIDATION & SUBMISSION - COMPLETELY FIXED
         * Users CANNOT submit unless required fields are filled OR negated
         * ================================================
         */
        $('#sp-cv-form').on('submit', function(e) {
            e.preventDefault();
            console.log('=== CV FORM SUBMISSION - COMPREHENSIVE VALIDATION ===');
            
            const validationErrors = [];
            let hasValidationIssues = false;
            let totalRequiredFields = 0;
            let completedOrNegatedFields = 0;
            
            // STEP 1: Check all single required fields (not in repeatable sections)
            console.log('STEP 1: Validating single required fields...');
            $(this).find('.sp-form-group').each(function() {
                const $formGroup = $(this);
                
                // Skip if inside repeatable section (handled later)
                if ($formGroup.closest('.sp-repeatable-entry').length > 0) {
                    return;
                }
                
                const $negationState = $formGroup.find('.negation-state');
                const isNegated = $negationState.length > 0 && $negationState.val() === '1';
                
                // Check all required fields in this group
                $formGroup.find('input[required], textarea[required], select[required]').not('.negation-state').each(function() {
                    const $field = $(this);
                    totalRequiredFields++;
                    
                    const value = $field.val();
                    const fieldLabel = $formGroup.find('label').first().text().replace('*', '').trim() || $field.attr('name');
                    
                    console.log(`Checking field: ${fieldLabel}, Negated: ${isNegated}, Has value: ${!!value}`);
                    
                    // Field is valid if negated OR has value
                    if (isNegated) {
                        completedOrNegatedFields++;
                        $field.css('border-color', '');
                        console.log(`  ✓ Field is NEGATED: ${fieldLabel}`);
                    } else if (value && value.trim() !== '') {
                        completedOrNegatedFields++;
                        $field.css('border-color', '');
                        console.log(`  ✓ Field has VALUE: ${fieldLabel}`);
                    } else {
                        hasValidationIssues = true;
                        validationErrors.push(fieldLabel);
                        $field.css('border-color', '#ef4444');
                        console.log(`  ❌ Field EMPTY: ${fieldLabel}`);
                    }
                });
            });
            
            // STEP 2: Check all repeatable sections
            console.log('STEP 2: Validating repeatable sections...');
            $('.sp-repeatable-section').each(function() {
                const $section = $(this);
                const sectionLabel = $section.find('h3, .sp-section-title').first().text().trim();
                const $negationState = $section.find('.section-negation-state');
                const isNegated = $negationState.length > 0 && $negationState.val() === '1';
                
                // Check if section has required attribute or marker
                const hasRequiredMarker = sectionLabel.includes('*') || $section.hasClass('required');
                
                if (!hasRequiredMarker) {
                    console.log(`  Section ${sectionLabel} is OPTIONAL - skipping`);
                    return;
                }
                
                totalRequiredFields++;
                
                const $container = $section.find('.sp-repeatable-container');
                const entries = $container.find('.sp-repeatable-entry');
                
                console.log(`Checking section: ${sectionLabel}, Negated: ${isNegated}, Entries: ${entries.length}`);
                
                // Section is valid if negated
                if (isNegated) {
                    completedOrNegatedFields++;
                    console.log(`  ✓ Section is NEGATED: ${sectionLabel}`);
                    return;
                }
                
                // Section must have at least one complete entry
                let hasValidEntry = false;
                
                entries.each(function(index) {
                    const $entry = $(this);
                    let entryIsComplete = true;
                    let entryHasRequiredFields = false;
                    
                    $entry.find('input[required], textarea[required], select[required]').each(function() {
                        entryHasRequiredFields = true;
                        const $field = $(this);
                        const value = $field.val();
                        
                        if (!value || value.trim() === '') {
                            entryIsComplete = false;
                            $field.css('border-color', '#ef4444');
                        } else {
                            $field.css('border-color', '');
                        }
                    });
                    
                    if (entryIsComplete && entryHasRequiredFields) {
                        hasValidEntry = true;
                        console.log(`  ✓ Entry ${index + 1} is COMPLETE`);
                    }
                });
                
                if (hasValidEntry) {
                    completedOrNegatedFields++;
                    console.log(`  ✓ Section has VALID entry: ${sectionLabel}`);
                } else {
                    hasValidationIssues = true;
                    validationErrors.push(`${sectionLabel} - completați cel puțin o intrare SAU folosiți butonul de negare`);
                    $section.css('border', '2px solid #ef4444');
                    console.log(`  ❌ Section has NO valid entries: ${sectionLabel}`);
                }
            });
            
            console.log(`=== VALIDATION SUMMARY: ${completedOrNegatedFields}/${totalRequiredFields} fields completed ===`);
            
            // CRITICAL: Block submission if validation failed
            if (hasValidationIssues) {
                console.log('❌ VALIDATION FAILED - BLOCKING SUBMISSION');
                
                let errorMessage = '❌ NU PUTEȚI FINALIZA PROFILUL!\n\n';
                errorMessage += 'Trebuie să completați TOATE câmpurile obligatorii (marcate cu *)\n';
                errorMessage += 'SAU să folosiți butoanele "Nu am" / "Nu știu încă" pentru câmpurile pe care nu doriți să le completați.\n\n';
                errorMessage += '📋 Câmpuri incomplete sau neselectate:\n\n';
                
                const uniqueErrors = [...new Set(validationErrors)];
                errorMessage += '• ' + uniqueErrors.slice(0, 8).join('\n• ');
                
                if (uniqueErrors.length > 8) {
                    errorMessage += `\n\n... și încă ${uniqueErrors.length - 8} câmp(uri)`;
                }
                
                errorMessage += '\n\n💡 SOLUȚIE:\n';
                errorMessage += '1. Completați câmpurile marcate cu roșu\n';
                errorMessage += '2. SAU apăsați butoanele de negare "Nu am" / "Nu știu încă"';
                
                showError(errorMessage);
                
                // Scroll to first error
                const $firstError = $(this).find('input, textarea, select').filter(function() {
                    const borderColor = $(this).css('border-color');
                    return borderColor === 'rgb(239, 68, 68)' || borderColor.includes('239, 68, 68');
                }).first();
                
                if ($firstError.length) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 150
                    }, 500);
                } else {
                    // Scroll to first section with error
                    const $firstSection = $('.sp-repeatable-section').filter(function() {
                        const borderStyle = $(this).css('border');
                        return borderStyle && borderStyle.includes('239, 68, 68');
                    }).first();
                    
                    if ($firstSection.length) {
                        $('html, body').animate({
                            scrollTop: $firstSection.offset().top - 150
                        }, 500);
                    }
                }
                
                return false; // PREVENT SUBMISSION
            }
            
            // Final check: Ensure we have at least some completed fields
            if (totalRequiredFields > 0 && completedOrNegatedFields === 0) {
                console.log('❌ NO FIELDS COMPLETED AT ALL - BLOCKING');
                showError('❌ Trebuie să completați cel puțin un câmp sau să folosiți butoanele de negare înainte de a finaliza profilul.');
                return false;
            }
            
            // If we got here, validation passed!
            console.log('✅ VALIDATION PASSED - Proceeding with submission');
            saveCVData();
            
            const formData = new FormData(this);
            const cvData = {};
            
            for (let [key, value] of formData.entries()) {
                console.log('CV Field:', key, '=', value);
                
                if (key.match(/\[\d+\]\[.+\]/)) {
                    const matches = key.match(/^(.+?)\[(\d+)\]\[(.+?)\]$/);
                    if (matches) {
                        const sectionName = matches[1];
                        const index = parseInt(matches[2]);
                        const fieldName = matches[3];
                        
                        if (!cvData[sectionName]) {
                            cvData[sectionName] = [];
                        }
                        if (!cvData[sectionName][index]) {
                            cvData[sectionName][index] = {};
                        }
                        cvData[sectionName][index][fieldName] = value;
                    }
                }
                else if (key.endsWith('[]')) {
                    const fieldName = key.replace('[]', '');
                    if (!cvData[fieldName]) {
                        cvData[fieldName] = [];
                    }
                    cvData[fieldName].push(value);
                }
                else {
                    cvData[key] = value;
                }
            }
            
            console.log('Processed CV Data:', cvData);
            
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.text();
            submitBtn.prop('disabled', true).text('Se salvează...');
            
            $.ajax({
                url: spOnboarding.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sp_save_cv_data',
                    nonce: spOnboarding.nonce,
                    session_key: sessionKey,
                    cv_data: cvData
                },
                success: function(response) {
                    console.log('CV Save Response:', response);
                    if (response.success) {
                        showSuccess('✔ Profil completat cu succes! Redirecționare...');
                        
                        setTimeout(function() {
                            if (response.data && response.data.redirect_url) {
                                window.location.href = response.data.redirect_url;
                            } else {
                                window.location.reload();
                            }
                        }, 2000);
                    } else {
                        showError('Eroare la salvarea datelor CV: ' + (response.data ? response.data.message : 'Unknown error'));
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    showError('Eroare de conexiune. Încercați din nou.');
                    submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
        
        function showError(message) {
            $('.sp-error-message, .sp-success-message').remove();
            const errorHtml = '<div class="sp-error-message">' + message + '</div>';
            $('.sp-step.active').prepend(errorHtml);
            $('html, body').animate({ scrollTop: $('.sp-error-message').offset().top - 100 }, 300);
            
            setTimeout(function() {
                $('.sp-error-message').fadeOut(function() { $(this).remove(); });
            }, 8000);
        }
        
        function showSuccess(message) {
            $('.sp-error-message, .sp-success-message').remove();
            const successHtml = '<div class="sp-success-message">' + message + '</div>';
            $('.sp-step.active').prepend(successHtml);
            $('html, body').animate({ scrollTop: $('.sp-success-message').offset().top - 100 }, 300);
            
            setTimeout(function() {
                $('.sp-success-message').fadeOut(function() { $(this).remove(); });
            }, 5000);
        }
        
        /**
         * Initialize
         */
        if ($('#sp-step-test').hasClass('active') && !testCompleted) {
            initQuestionNavigation();
        }
        
        const currentActiveStep = $('.sp-step.active').attr('id');
        if (currentActiveStep) {
            const stepName = currentActiveStep.replace('sp-step-', '');
            if (!testCompleted) {
                updateBackButton(stepName);
            }
        }
        
        if (testCompleted) {
            $('.sp-btn-back').not('.sp-question-prev').hide();
        }
        
        console.log('SuccessPlus Onboarding v4.6 - CV Validation FIXED');
        
    });
    
})(jQuery);