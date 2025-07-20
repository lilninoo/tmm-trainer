/**
 * JavaScript COMPLET TRAINER REGISTRATION PRO - VERSION AFFICHAGE UNIFORME
 * 
 * ✅ CORRECTION PRINCIPALE: Affichage uniforme entre recherche AJAX et template statique
 * ✅ Formulaire d'inscription modernisé (multi-étapes, validation temps réel)
 * ✅ Recherche de formateurs avec AJAX et filtres corrigés
 * ✅ Cartes de formateurs uniformes (même style pour tous les cas)
 * ✅ Upload de fichiers avec drag & drop
 * ✅ Animations et UX optimisées
 * ✅ Système d'erreurs avancé avec scroll automatique
 * ✅ Validation en temps réel style Stripe
 * ✅ Vue grille/liste avec sauvegarde
 * ✅ Pagination fonctionnelle
 * ✅ Fermeture des modales
 * 
 * Version: 2.3 - AFFICHAGE UNIFORME
 */

(function($) {
    'use strict';

    // ===== VÉRIFICATIONS INITIALES =====
    if (typeof $ === 'undefined') {
        console.error('❌ Trainer Registration Pro: jQuery non trouvé');
        return;
    }

    if (typeof trainer_ajax === 'undefined') {
        console.error('❌ Trainer Registration Pro: Configuration AJAX manquante');
        return;
    }

    $(document).ready(function() {
        console.log('🚀 Trainer Registration Pro: Initialisation complète...');

        // ===== VARIABLES GLOBALES =====
        let currentStep = 1;
        const totalSteps = 4;
        let formSubmitting = false;
        let validationTimeout = null;
        let searchTimeout;
        
        // Configuration pour la recherche/liste
        const CONFIG = {
            view: {
                current: localStorage.getItem('trainer_view') || 'grid',
                animationDuration: 300
            },
            search: {
                timeout: null,
                currentRequest: null,
                delay: 500
            },
            pagination: {
                perPage: 12,
                currentPage: 1
            }
        };
        
        // Cache des éléments DOM - Formulaire
        const formElements = {
            form: $('#trpro-trainer-registration-form'),
            steps: $('.trpro-form-step'),
            progressSteps: $('.trpro-progress-step'),
            nextBtn: $('#trpro-next-step'),
            prevBtn: $('#trpro-prev-step'),
            submitBtn: $('#trpro-submit-form'),
            messages: $('#trpro-form-messages'),
            loading: $('#trpro-form-loading')
        };
        
        // Cache des éléments DOM - Recherche/Liste
        const searchElements = {
            // Recherche et filtres
            searchInput: $('#trpro-live-search, #trpro-trainer-search-input'),
            specialtyFilter: $('#trpro-specialty-filter'),
            regionFilter: $('#trpro-region-filter'),
            experienceFilter: $('#trpro-experience-filter'),
            availabilityFilter: $('#trpro-availability-filter'),
            searchBtn: $('#trpro-search-trainers-btn, .trpro-search-btn'),
            resetBtn: $('#trpro-reset-filters'),
            
            // Vue et affichage
            viewBtns: $('.trpro-view-btn'),
            trainersGrid: $('#trpro-trainers-grid'),
            
            // États
            loading: $('#trpro-search-loading'),
            emptyState: $('#trpro-empty-state'),
            resultsHeader: $('#trpro-results-header'),
            resultsCount: $('#trpro-results-count, .trpro-results-count'),
            
            // Tags et pagination
            popularTags: $('.trpro-tag, .trpro-suggestion-tag'),
            pagination: $('.trpro-pagination')
        };

        // ===== INITIALISATION GLOBALE =====
        
        // Formulaire d'inscription
        if (formElements.form.length > 0) {
            initFormNavigation();
            initRealTimeValidation();
            initFileUpload();
            initCheckboxes();
            initFormAnimations();
            showStep(1);
            console.log('✅ Formulaire d\'inscription initialisé');
        }

        // Recherche et liste de formateurs
        if ($('#trpro-trainer-search').length > 0 || searchElements.trainersGrid.length > 0) {
            initViewSwitcher();
            initSearch();
            initFilters();
            initPagination();
            initTrainerCards();
            initPopularTags();
            loadInitialView();
            console.log('✅ Recherche et liste initialisées - AFFICHAGE UNIFORME');
        }

        // Animations générales
        initGlobalAnimations();

        // ========== PARTIE 1: FORMULAIRE D'INSCRIPTION ==========
        
        // ===== NAVIGATION MULTI-ÉTAPES MODERNE =====
        function initFormNavigation() {
            // Événements des boutons
            formElements.nextBtn.on('click', handleNextStep);
            formElements.prevBtn.on('click', handlePrevStep);
            formElements.submitBtn.on('click', handleSubmit);
            formElements.form.on('submit', function(e) {
                e.preventDefault();
                return false;
            });

            // Navigation clavier
            $(document).on('keydown', function(e) {
                if (formElements.form.is(':visible') && e.key === 'Enter' && !e.shiftKey) {
                    const activeElement = document.activeElement;
                    if (activeElement.tagName !== 'TEXTAREA') {
                        e.preventDefault();
                        if (currentStep < totalSteps) {
                            handleNextStep();
                        } else {
                            handleSubmit();
                        }
                    }
                }
            });
        }

        function handleNextStep() {
            console.log(`🔄 Tentative passage à l'étape ${currentStep + 1}`);
            
            if (validateCurrentStep()) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    showStep(currentStep);
                    console.log(`✅ Passage réussi à l'étape ${currentStep}`);
                }
            } else {
                console.log(`❌ Validation échouée pour l'étape ${currentStep}`);
            }
        }

        function handlePrevStep() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
                console.log(`⬅️ Retour à l'étape ${currentStep}`);
            }
        }

        function handleSubmit() {
            console.log('📤 Tentative de soumission...');
            
            if (formSubmitting) {
                console.log('⚠️ Soumission déjà en cours');
                return;
            }
            
            if (validateCurrentStep()) {
                submitForm();
            }
        }

        function showStep(step) {
            console.log(`📄 Affichage étape ${step}`);
            
            // Masquer toutes les étapes
            formElements.steps.removeClass('active').hide();
            
            // Afficher l'étape courante avec animation
            const $currentStep = $(`.trpro-form-step[data-step="${step}"]`);
            $currentStep.addClass('active').fadeIn(300);
            
            // Mise à jour de la barre de progression
            updateProgressBar(step);
            
            // Gestion des boutons
            formElements.prevBtn.toggle(step > 1);
            
            if (step === totalSteps) {
                formElements.nextBtn.hide();
                formElements.submitBtn.show();
                generateSummary();
            } else {
                formElements.nextBtn.show();
                formElements.submitBtn.hide();
            }
            
            // Scroll fluide vers le formulaire
            scrollToForm();
            
            // Focus sur le premier champ
            setTimeout(() => {
                $currentStep.find('input, textarea, select').first().focus();
            }, 350);
        }

        function updateProgressBar(step) {
            formElements.progressSteps.removeClass('active completed');
            
            for (let i = 1; i <= step; i++) {
                $(`.trpro-progress-step[data-step="${i}"]`).addClass('active');
            }
            
            for (let i = 1; i < step; i++) {
                $(`.trpro-progress-step[data-step="${i}"]`).addClass('completed');
            }
        }

        function scrollToForm() {
            const container = $('.trpro-registration-container');
            if (container.length > 0) {
                $('html, body').animate({
                    scrollTop: container.offset().top - 100
                }, 400, 'easeOutCubic');
            }
        }

        // ===== VALIDATION MODERNE EN TEMPS RÉEL =====
        function initRealTimeValidation() {
            // Validation pendant la saisie (debounced)
            formElements.form.find('input, textarea, select').on('input', function() {
                const $field = $(this);
                clearTimeout(validationTimeout);
                validationTimeout = setTimeout(() => {
                    validateField($field);
                }, 300);
            });

            // Validation à la perte de focus
            formElements.form.find('input, textarea, select').on('blur', function() {
                validateField($(this));
            });

            // Validation des checkboxes
            formElements.form.find('input[type="checkbox"]').on('change', function() {
                const $field = $(this);
                const name = $field.attr('name');
                
                if (name === 'specialties[]') {
                    validateSpecialties();
                } else if (name === 'rgpd_consent') {
                    validateRgpd();
                }
            });
        }

        function validateCurrentStep() {
            const $currentStepElement = $(`.trpro-form-step[data-step="${currentStep}"]`);
            const errors = [];
            
            console.log(`🔍 Validation étape ${currentStep}`);
            
            // Nettoyer les erreurs précédentes
            clearStepErrors();
            
            // Validation selon l'étape
            switch (currentStep) {
                case 1:
                    errors.push(...validateStep1($currentStepElement));
                    break;
                case 2:
                    errors.push(...validateStep2($currentStepElement));
                    break;
                case 3:
                    errors.push(...validateStep3($currentStepElement));
                    break;
                case 4:
                    errors.push(...validateStep4($currentStepElement));
                    break;
            }
            
            if (errors.length > 0) {
                displayErrors(errors);
                scrollToFirstError();
                return false;
            }
            
            return true;
        }

        // ===== VALIDATIONS PAR ÉTAPE =====
        function validateStep1($step) {
            const errors = [];
            
            // Prénom
            const firstName = $step.find('#trpro-first-name').val().trim();
            if (!firstName) {
                errors.push({
                    field: 'first_name',
                    selector: '#trpro-first-name',
                    message: 'Le prénom est obligatoire'
                });
            } else if (firstName.length < 2) {
                errors.push({
                    field: 'first_name',
                    selector: '#trpro-first-name',
                    message: 'Le prénom doit contenir au moins 2 caractères'
                });
            } else if (!/^[a-zA-ZÀ-ÿ\s-']+$/.test(firstName)) {
                errors.push({
                    field: 'first_name',
                    selector: '#trpro-first-name',
                    message: 'Le prénom contient des caractères non autorisés'
                });
            }
            
            // Nom
            const lastName = $step.find('#trpro-last-name').val().trim();
            if (!lastName) {
                errors.push({
                    field: 'last_name',
                    selector: '#trpro-last-name',
                    message: 'Le nom est obligatoire'
                });
            } else if (lastName.length < 2) {
                errors.push({
                    field: 'last_name',
                    selector: '#trpro-last-name',
                    message: 'Le nom doit contenir au moins 2 caractères'
                });
            } else if (!/^[a-zA-ZÀ-ÿ\s-']+$/.test(lastName)) {
                errors.push({
                    field: 'last_name',
                    selector: '#trpro-last-name',
                    message: 'Le nom contient des caractères non autorisés'
                });
            }
            
            // Email
            const email = $step.find('#trpro-email').val().trim();
            if (!email) {
                errors.push({
                    field: 'email',
                    selector: '#trpro-email',
                    message: 'L\'adresse email est obligatoire'
                });
            } else if (!isValidEmail(email)) {
                errors.push({
                    field: 'email',
                    selector: '#trpro-email',
                    message: 'Format d\'email invalide'
                });
            }
            
            // Téléphone
            const phone = $step.find('#trpro-phone').val().trim();
            if (!phone) {
                errors.push({
                    field: 'phone',
                    selector: '#trpro-phone',
                    message: 'Le numéro de téléphone est obligatoire'
                });
            } else if (!isValidPhone(phone)) {
                errors.push({
                    field: 'phone',
                    selector: '#trpro-phone',
                    message: 'Format de téléphone invalide (format français attendu)'
                });
            }
            
            // LinkedIn (optionnel mais si rempli, doit être valide)
            const linkedin = $step.find('#trpro-linkedin-url').val().trim();
            if (linkedin && !linkedin.includes('linkedin.com')) {
                errors.push({
                    field: 'linkedin_url',
                    selector: '#trpro-linkedin-url',
                    message: 'URL LinkedIn invalide'
                });
            }
            
            return errors;
        }

        function validateStep2($step) {
            const errors = [];
            
            // Spécialités
            const $specialties = $step.find('input[name="specialties[]"]:checked');
            if ($specialties.length === 0) {
                errors.push({
                    field: 'specialties',
                    selector: '.trpro-checkbox-grid',
                    message: 'Sélectionnez au moins une spécialité'
                });
            } else if ($specialties.length > 5) {
                errors.push({
                    field: 'specialties',
                    selector: '.trpro-checkbox-grid',
                    message: 'Maximum 5 spécialités recommandées'
                });
            }
            
            // Expérience
            const experience = $step.find('#trpro-experience').val().trim();
            if (!experience) {
                errors.push({
                    field: 'experience',
                    selector: '#trpro-experience',
                    message: 'Description de l\'expérience obligatoire'
                });
            } else if (experience.length < 50) {
                errors.push({
                    field: 'experience',
                    selector: '#trpro-experience',
                    message: `Description trop courte (${experience.length}/50 caractères minimum)`
                });
            } else if (experience.length > 1000) {
                errors.push({
                    field: 'experience',
                    selector: '#trpro-experience',
                    message: `Description trop longue (${experience.length}/1000 caractères maximum)`
                });
            }
            
            return errors;
        }

        function validateStep3($step) {
            const errors = [];
            
            // CV obligatoire
            const cvFile = $step.find('#trpro-cv-file')[0].files[0];
            if (!cvFile) {
                errors.push({
                    field: 'cv_file',
                    selector: '#trpro-cv-file',
                    message: 'Le CV est obligatoire'
                });
            } else {
                // Vérification taille
                if (cvFile.size > 5 * 1024 * 1024) {
                    errors.push({
                        field: 'cv_file',
                        selector: '#trpro-cv-file',
                        message: `CV trop volumineux (${formatFileSize(cvFile.size)}). Maximum: 5MB`
                    });
                }
                
                // Vérification type
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                if (!allowedTypes.includes(cvFile.type)) {
                    errors.push({
                        field: 'cv_file',
                        selector: '#trpro-cv-file',
                        message: 'Format de CV non supporté. Utilisez PDF, DOC ou DOCX'
                    });
                }
            }
            
            // Photo (optionnelle mais si présente, doit être valide)
            const photoFile = $step.find('#trpro-photo-file')[0].files[0];
            if (photoFile) {
                if (photoFile.size > 2 * 1024 * 1024) {
                    errors.push({
                        field: 'photo_file',
                        selector: '#trpro-photo-file',
                        message: `Photo trop volumineuse (${formatFileSize(photoFile.size)}). Maximum: 2MB`
                    });
                }
                
                const allowedPhotoTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedPhotoTypes.includes(photoFile.type)) {
                    errors.push({
                        field: 'photo_file',
                        selector: '#trpro-photo-file',
                        message: 'Format de photo non supporté. Utilisez JPG, PNG ou GIF'
                    });
                }
            }
            
            return errors;
        }

        function validateStep4($step) {
            const errors = [];
            
            // Consentement RGPD obligatoire
            const rgpdConsent = $step.find('#trpro-rgpd-consent').prop('checked');
            if (!rgpdConsent) {
                errors.push({
                    field: 'rgpd_consent',
                    selector: '#trpro-rgpd-consent',
                    message: 'Le consentement RGPD est obligatoire'
                });
            }
            
            return errors;
        }

        // ===== VALIDATION INDIVIDUELLE DES CHAMPS =====
        function validateField($field) {
            const fieldName = $field.attr('name');
            const value = $field.val().trim();
            const $formGroup = $field.closest('.trpro-form-group');
            const $errorMsg = $field.siblings('.trpro-error-message');
            
            // Reset de l'état
            $formGroup.removeClass('error success');
            $errorMsg.text('').css('opacity', 0);
            
            let isValid = true;
            let errorMessage = '';
            
            // Validation selon le type de champ
            switch (fieldName) {
                case 'first_name':
                case 'last_name':
                    if ($field.prop('required') && !value) {
                        isValid = false;
                        errorMessage = `Le ${fieldName === 'first_name' ? 'prénom' : 'nom'} est obligatoire`;
                    } else if (value && value.length < 2) {
                        isValid = false;
                        errorMessage = 'Minimum 2 caractères';
                    } else if (value && !/^[a-zA-ZÀ-ÿ\s-']+$/.test(value)) {
                        isValid = false;
                        errorMessage = 'Caractères non autorisés';
                    }
                    break;
                    
                case 'email':
                    if ($field.prop('required') && !value) {
                        isValid = false;
                        errorMessage = 'Email obligatoire';
                    } else if (value && !isValidEmail(value)) {
                        isValid = false;
                        errorMessage = 'Format email invalide';
                    }
                    break;
                    
                case 'phone':
                    if ($field.prop('required') && !value) {
                        isValid = false;
                        errorMessage = 'Téléphone obligatoire';
                    } else if (value && !isValidPhone(value)) {
                        isValid = false;
                        errorMessage = 'Format téléphone invalide';
                    }
                    break;
                    
                case 'experience':
                    if ($field.prop('required') && !value) {
                        isValid = false;
                        errorMessage = 'Description obligatoire';
                    } else if (value && value.length < 50) {
                        isValid = false;
                        errorMessage = `${value.length}/50 caractères minimum`;
                    } else if (value && value.length > 1000) {
                        isValid = false;
                        errorMessage = `${value.length}/1000 caractères maximum`;
                    }
                    break;
                    
                case 'linkedin_url':
                    if (value && !value.includes('linkedin.com')) {
                        isValid = false;
                        errorMessage = 'URL LinkedIn invalide';
                    }
                    break;
            }
            
            // Application du résultat
            if (isValid && value) {
                $formGroup.addClass('success');
                showSuccessIcon($field);
            } else if (!isValid) {
                $formGroup.addClass('error');
                $errorMsg.text(errorMessage).css('opacity', 1);
                showErrorIcon($field);
            }
            
            return isValid;
        }

        function validateSpecialties() {
            const $checked = $('input[name="specialties[]"]:checked');
            const $container = $('.trpro-checkbox-grid');
            const $errorMsg = $('#trpro-specialties-error');
            
            $container.removeClass('trpro-error-highlight');
            $errorMsg.text('');
            
            if ($checked.length === 0) {
                $container.addClass('trpro-error-highlight');
                $errorMsg.text('Sélectionnez au moins une spécialité');
                return false;
            } else if ($checked.length > 5) {
                $container.addClass('trpro-error-highlight');
                $errorMsg.text('Maximum 5 spécialités recommandées');
                return false;
            }
            
            return true;
        }

        function validateRgpd() {
            const $checkbox = $('#trpro-rgpd-consent');
            const $container = $('.trpro-required-consent');
            const $errorMsg = $('#trpro-rgpd-error');
            
            $container.removeClass('error');
            $errorMsg.text('');
            
            if (!$checkbox.prop('checked')) {
                $container.addClass('error');
                $errorMsg.text('Consentement RGPD obligatoire');
                return false;
            }
            
            return true;
        }

        // ===== UTILITAIRES DE VALIDATION =====
        function isValidEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }

        function isValidPhone(phone) {
            // Format français: 01 23 45 67 89, +33 1 23 45 67 89, etc.
            const cleanPhone = phone.replace(/[\s.-]/g, '');
            const regex = /^(?:(?:\+|00)33|0)[1-9](?:[0-9]{8})$/;
            return regex.test(cleanPhone);
        }

        // ===== AFFICHAGE DES ERREURS MODERNE =====
        function displayErrors(errors) {
            clearStepErrors();
            
            if (errors.length === 0) return;
            
            // Créer le conteneur d'erreurs
            const $errorContainer = $(`
                <div class="trpro-step-errors">
                    <div class="trpro-error-header">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Veuillez corriger les erreurs suivantes :</strong>
                    </div>
                    <ul class="trpro-error-list"></ul>
                </div>
            `);
            
            const $errorList = $errorContainer.find('.trpro-error-list');
            
            errors.forEach(error => {
                const $errorItem = $(`
                    <li class="trpro-error-item">
                        <span class="trpro-error-text">
                            <i class="fas fa-times-circle"></i>
                            ${error.message}
                        </span>
                        <button type="button" class="trpro-error-goto" data-selector="${error.selector}">
                            Corriger
                        </button>
                    </li>
                `);
                
                $errorList.append($errorItem);
                
                // Mettre en évidence le champ
                highlightErrorField(error.selector);
            });
            
            // Insérer le conteneur
            const $currentStep = $(`.trpro-form-step[data-step="${currentStep}"]`);
            $currentStep.prepend($errorContainer);
            
            // Animation d'entrée
            $errorContainer.hide().slideDown(300);
            
            // Gérer les clics "Corriger"
            $errorContainer.find('.trpro-error-goto').on('click', function() {
                const selector = $(this).data('selector');
                scrollToField(selector);
                $(selector).focus();
            });
        }

        function highlightErrorField(selector) {
            const $field = $(selector);
            const $formGroup = $field.closest('.trpro-form-group');
            
            $formGroup.addClass('error');
            $field.addClass('trpro-field-error-highlight');
            
            // Pour les spécialités
            if (selector === '.trpro-checkbox-grid') {
                $(selector).addClass('trpro-error-highlight');
            }
            
            // Pour RGPD
            if (selector === '#trpro-rgpd-consent') {
                $('.trpro-required-consent').addClass('error');
            }
        }

        function clearStepErrors() {
            $('.trpro-step-errors').remove();
            $('.trpro-form-group').removeClass('error success');
            $('.trpro-error-message').text('').css('opacity', 0);
            $('.trpro-field-error-highlight').removeClass('trpro-field-error-highlight');
            $('.trpro-error-highlight').removeClass('trpro-error-highlight');
            $('.trpro-required-consent').removeClass('error');
            $('#trpro-specialties-error, #trpro-rgpd-error').text('');
        }

        function scrollToFirstError() {
            const $firstError = $('.trpro-step-errors');
            if ($firstError.length > 0) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 120
                }, 500, 'easeOutCubic');
            }
        }

        function scrollToField(selector) {
            const $field = $(selector);
            if ($field.length > 0) {
                $('html, body').animate({
                    scrollTop: $field.offset().top - 150
                }, 400, 'easeOutCubic');
            }
        }

        // ===== ICÔNES DE VALIDATION =====
        function showSuccessIcon($field) {
            const $formGroup = $field.closest('.trpro-form-group');
            $formGroup.find('.validation-icon').remove();
            $formGroup.append('<i class="fas fa-check-circle validation-icon success-icon"></i>');
        }

        function showErrorIcon($field) {
            const $formGroup = $field.closest('.trpro-form-group');
            $formGroup.find('.validation-icon').remove();
            $formGroup.append('<i class="fas fa-times-circle validation-icon error-icon"></i>');
        }

        // ===== GESTION DES FICHIERS MODERNE =====
        function initFileUpload() {
            // Clic sur zone d'upload
            $(document).on('click', '.trpro-file-upload-area', function(e) {
                e.preventDefault();
                const targetInput = $(this).data('target');
                if (targetInput) {
                    $(`#${targetInput}`).trigger('click');
                }
            });

            // Drag & Drop avec animations
            $('.trpro-file-upload-area')
                .on('dragover', function(e) {
                    e.preventDefault();
                    $(this).addClass('dragover');
                })
                .on('dragleave', function(e) {
                    e.preventDefault();
                    $(this).removeClass('dragover');
                })
                .on('drop', function(e) {
                    e.preventDefault();
                    $(this).removeClass('dragover');
                    
                    const files = e.originalEvent.dataTransfer.files;
                    const targetInput = $(this).data('target');
                    
                    if (files.length > 0 && targetInput) {
                        const inputElement = $(`#${targetInput}`)[0];
                        if (inputElement) {
                            inputElement.files = files;
                            $(inputElement).trigger('change');
                        }
                    }
                });

            // Changement de fichier
            $(document).on('change', 'input[type="file"]', function() {
                const file = this.files[0];
                const fileId = $(this).attr('id');
                const $preview = $(`#${fileId}-preview`);
                
                if (file) {
                    showFilePreview(file, $preview, fileId);
                    validateFileField($(this), file);
                } else {
                    $preview.removeClass('active').empty();
                }
            });

            // Suppression de fichier
            $(document).on('click', '.trpro-file-remove', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const targetId = $(this).data('target');
                const $input = $(`#${targetId}`);
                $input.val('');
                $(`#${targetId}-preview`).removeClass('active').empty();
                
                // Reset de la validation
                $input.closest('.trpro-form-group').removeClass('error success');
            });
        }

        function validateFileField($input, file) {
            const $formGroup = $input.closest('.trpro-form-group');
            const $errorMsg = $input.siblings('.trpro-error-message');
            const fieldName = $input.attr('name');
            
            $formGroup.removeClass('error success');
            $errorMsg.text('');
            
            let isValid = true;
            let errorMessage = '';
            
            if (fieldName === 'cv_file') {
                if (file.size > 5 * 1024 * 1024) {
                    isValid = false;
                    errorMessage = `Fichier trop volumineux (${formatFileSize(file.size)}). Maximum: 5MB`;
                } else {
                    const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                    if (!allowedTypes.includes(file.type)) {
                        isValid = false;
                        errorMessage = 'Format non supporté. Utilisez PDF, DOC ou DOCX';
                    }
                }
            } else if (fieldName === 'photo_file') {
                if (file.size > 2 * 1024 * 1024) {
                    isValid = false;
                    errorMessage = `Image trop volumineuse (${formatFileSize(file.size)}). Maximum: 2MB`;
                } else {
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!allowedTypes.includes(file.type)) {
                        isValid = false;
                        errorMessage = 'Format non supporté. Utilisez JPG, PNG ou GIF';
                    }
                }
            }
            
            if (isValid) {
                $formGroup.addClass('success');
            } else {
                $formGroup.addClass('error');
                $errorMsg.text(errorMessage);
            }
        }

        function showFilePreview(file, $preview, fileId) {
            let fileIcon = 'fas fa-file';
            if (file.type.includes('pdf')) fileIcon = 'fas fa-file-pdf';
            else if (file.type.includes('image')) fileIcon = 'fas fa-file-image';
            else if (file.type.includes('word')) fileIcon = 'fas fa-file-word';
            
            const fileSize = formatFileSize(file.size);
            
            const previewHtml = `
                <div class="trpro-file-info">
                    <i class="${fileIcon}"></i>
                    <div class="trpro-file-details">
                        <div class="trpro-file-name">${escapeHtml(file.name)}</div>
                        <div class="trpro-file-size">${fileSize}</div>
                    </div>
                    <button type="button" class="trpro-file-remove" data-target="${fileId}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            $preview.html(previewHtml).addClass('active');
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // ===== GESTION DES CHECKBOXES MODERNE =====
        function initCheckboxes() {
            // Checkboxes spécialités
            $(document).on('click', '.trpro-checkbox-item', function(e) {
                if ($(e.target).is('input[type="checkbox"]') || $(e.target).is('label')) {
                    return;
                }
                
                const $checkbox = $(this).find('input[type="checkbox"]');
                $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
            });
            
            // Checkboxes consentement
            $(document).on('click', '.trpro-consent-wrapper', function(e) {
                if ($(e.target).is('input[type="checkbox"]') || $(e.target).is('label')) {
                    return;
                }
                
                const $checkbox = $(this).find('input[type="checkbox"]');
                $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
            });
        }

        // ===== GÉNÉRATION DU RÉSUMÉ =====
        function generateSummary() {
            const $summary = $('#trpro-registration-summary');
            $summary.empty();
            
            console.log('📋 Génération du résumé...');
            
            // Informations personnelles
            addSummaryItem($summary, 'Nom complet', `${$('#trpro-first-name').val()} ${$('#trpro-last-name').val()}`);
            addSummaryItem($summary, 'Email', $('#trpro-email').val());
            addSummaryItem($summary, 'Téléphone', $('#trpro-phone').val());
            
            const company = $('#trpro-company').val();
            if (company) {
                addSummaryItem($summary, 'Entreprise', company);
            }
            
            // Spécialités
            const specialties = [];
            $('input[name="specialties[]"]:checked').each(function() {
                const label = $(this).siblings('label').text().trim();
                specialties.push(label);
            });
            if (specialties.length > 0) {
                addSummaryItem($summary, 'Spécialités', specialties.join(', '));
            }
            
            // Disponibilité
            const availability = $('#trpro-availability').val();
            if (availability) {
                const availabilityText = $('#trpro-availability option:selected').text();
                addSummaryItem($summary, 'Disponibilité', availabilityText);
            }
            
            // Fichiers
            const cvFile = $('#trpro-cv-file')[0].files[0];
            if (cvFile) {
                addSummaryItem($summary, 'CV', `${cvFile.name} (${formatFileSize(cvFile.size)})`);
            }
            
            const photoFile = $('#trpro-photo-file')[0].files[0];
            if (photoFile) {
                addSummaryItem($summary, 'Photo', `${photoFile.name} (${formatFileSize(photoFile.size)})`);
            }
        }

        function addSummaryItem($container, label, value) {
            if (!value) return;
            
            const $item = $(`
                <div class="trpro-summary-item">
                    <div class="trpro-summary-label">${escapeHtml(label)}</div>
                    <div class="trpro-summary-value">${escapeHtml(value)}</div>
                </div>
            `);
            
            $container.append($item);
        }

        // ===== SOUMISSION DU FORMULAIRE =====
        function submitForm() {
            console.log('📤 Soumission du formulaire...');
            formSubmitting = true;
            
            // Afficher le loading
            formElements.loading.fadeIn(200);
            formElements.submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Envoi en cours...');
            
            // Préparer les données
            const formData = new FormData(formElements.form[0]);
            formData.append('action', 'submit_trainer_registration');
            formData.append('nonce', trainer_ajax.nonce);
            
            $.ajax({
                url: trainer_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 30000,
                success: function(response) {
                    console.log('✅ Réponse serveur:', response);
                    handleFormResponse(response);
                },
                error: function(xhr, status, error) {
                    console.error('❌ Erreur AJAX:', {xhr, status, error});
                    handleFormError(xhr, status, error);
                },
                complete: function() {
                    formElements.loading.fadeOut(200);
                    formElements.submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Envoyer ma candidature');
                    formSubmitting = false;
                }
            });
        }

        function handleFormResponse(response) {
            if (response.success) {
                showMessage('success', response.data.message || 'Inscription réussie !');
                
                // Reset du formulaire
                formElements.form[0].reset();
                $('.trpro-file-preview').removeClass('active').empty();
                currentStep = 1;
                showStep(currentStep);
                
                scrollToMessage();
                
                if (response.data.redirect) {
                    setTimeout(() => {
                        window.location.href = response.data.redirect;
                    }, 3000);
                }
            } else {
                const errorMessage = response.data?.message || 'Erreur lors de l\'inscription';
                showMessage('error', errorMessage);
            }
        }

        function handleFormError(xhr, status, error) {
            let errorMessage = 'Erreur de connexion. Veuillez réessayer.';
            
            if (status === 'timeout') {
                errorMessage = 'La requête a expiré. Veuillez réessayer.';
            } else if (xhr.responseJSON?.data?.message) {
                errorMessage = xhr.responseJSON.data.message;
            }
            
            showMessage('error', errorMessage);
        }

        function showMessage(type, message) {
            const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
            
            formElements.messages
                .removeClass('success error')
                .addClass(type)
                .html(`<i class="fas fa-${icon}"></i> ${escapeHtml(message)}`)
                .fadeIn(300);
            
            if (type === 'success') {
                setTimeout(() => {
                    formElements.messages.fadeOut(300);
                }, 5000);
            }
        }

        function scrollToMessage() {
            if (formElements.messages.length > 0) {
                $('html, body').animate({
                    scrollTop: formElements.messages.offset().top - 100
                }, 400);
            }
        }

        // ========== PARTIE 2: RECHERCHE ET LISTE DE FORMATEURS - AFFICHAGE UNIFORME ==========
        
        // ===== SYSTÈME VUE GRILLE/LISTE CORRIGÉ =====
        function initViewSwitcher() {
            console.log('🔧 Init vue switcher...');
            
            // Appliquer la vue sauvegardée
            updateViewDisplay(CONFIG.view.current);
            
            // Gestionnaire d'événement unifié
            searchElements.viewBtns.off('click').on('click', function(e) {
                e.preventDefault();
                
                const newView = $(this).data('view');
                if (!newView || newView === CONFIG.view.current) return;
                
                console.log(`🔄 Changement de vue: ${CONFIG.view.current} → ${newView}`);
                
                CONFIG.view.current = newView;
                localStorage.setItem('trainer_view', newView);
                
                updateViewDisplay(newView);
            });
            
            console.log(`✅ Vue actuelle: ${CONFIG.view.current}`);
        }

        function updateViewDisplay(viewType) {
            // Mettre à jour les boutons
            searchElements.viewBtns.removeClass('active');
            searchElements.viewBtns.filter(`[data-view="${viewType}"]`).addClass('active');
            
            // Animation de transition
            searchElements.trainersGrid.css({
                opacity: 0.7,
                transform: 'scale(0.98)'
            });
            
            setTimeout(() => {
                // Appliquer les classes CSS
                searchElements.trainersGrid
                    .removeClass('trpro-view-grid trpro-view-list')
                    .addClass(`trpro-view-${viewType}`);
                
                // Animation de retour
                searchElements.trainersGrid.css({
                    opacity: 1,
                    transform: 'scale(1)'
                });
                
                // Ajuster l'alignement
                setTimeout(adjustCardsAlignment, 100);
                
            }, CONFIG.view.animationDuration / 2);
        }

        function adjustCardsAlignment() {
            if (CONFIG.view.current === 'list') {
                $('.trpro-trainer-card-compact').css('height', 'auto');
                return;
            }
            
            // Mode grille : égaliser les hauteurs par ligne
            const $cards = $('.trpro-trainer-card-compact');
            if ($cards.length === 0) return;
            
            $cards.css('height', 'auto');
            
            setTimeout(() => {
                const cardWidth = $cards.first().outerWidth();
                const containerWidth = searchElements.trainersGrid.width();
                const cardsPerRow = Math.floor(containerWidth / cardWidth) || 1;
                
                for (let i = 0; i < $cards.length; i += cardsPerRow) {
                    const $rowCards = $cards.slice(i, i + cardsPerRow);
                    
                    let maxHeight = 0;
                    $rowCards.each(function() {
                        const height = $(this).outerHeight();
                        if (height > maxHeight) maxHeight = height;
                    });
                    
                    $rowCards.css('height', maxHeight + 'px');
                }
            }, 50);
        }

        // ===== SYSTÈME DE RECHERCHE CORRIGÉ POUR AFFICHAGE UNIFORME =====
        function initSearch() {
            console.log('🔧 Init recherche...');
            
            // Recherche en temps réel
            searchElements.searchInput.off('input').on('input', debounce(function() {
                const query = $(this).val().trim();
                console.log('🔍 Recherche:', query);
                performSearch();
            }, CONFIG.search.delay));
            
            // Bouton de recherche
            searchElements.searchBtn.off('click').on('click', function(e) {
                e.preventDefault();
                performSearch();
            });
            
            // Recherche sur Enter
            searchElements.searchInput.off('keypress').on('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    performSearch();
                }
            });
            
            // Tags de suggestion (pour la page de recherche)
            $('.trpro-suggestion-tag').off('click').on('click', function(e) {
                e.preventDefault();
                const searchTerm = $(this).data('search');
                const category = $(this).data('category');
                
                searchElements.searchInput.val(searchTerm);
                if (category && category !== 'all') {
                    searchElements.specialtyFilter.val(category);
                }
                
                // Effet visuel
                $(this).css('transform', 'scale(0.95)');
                setTimeout(() => {
                    $(this).css('transform', '');
                    performSearch();
                }, 150);
            });
            
            console.log('✅ Recherche initialisée');
        }

        // ===== SYSTÈME DE FILTRES CORRIGÉ =====
        function initFilters() {
            console.log('🔧 Init filtres...');
            
            // Tous les filtres avec gestionnaires
            searchElements.specialtyFilter.off('change').on('change', handleFilterChange);
            searchElements.regionFilter.off('change').on('change', handleFilterChange);
            searchElements.experienceFilter.off('change').on('change', handleFilterChange);
            searchElements.availabilityFilter.off('change').on('change', handleFilterChange);
            
            // Reset
            searchElements.resetBtn.off('click').on('click', function(e) {
                e.preventDefault();
                resetAllFilters();
            });
            
            console.log('✅ Filtres initialisés');
        }

        function handleFilterChange() {
            console.log('🔄 Changement de filtre');
            clearTimeout(CONFIG.search.timeout);
            CONFIG.search.timeout = setTimeout(performSearch, 300);
        }

        function resetAllFilters() {
            console.log('🧹 Reset filtres');
            
            searchElements.searchInput.val('');
            searchElements.specialtyFilter.val('');
            searchElements.regionFilter.val('');
            searchElements.experienceFilter.val('');
            searchElements.availabilityFilter.val('');
            
            CONFIG.pagination.currentPage = 1;
            performSearch();
        }

        // ===== ✅ RECHERCHE AJAX CORRIGÉE POUR AFFICHAGE UNIFORME =====
        function performSearch() {
            console.log('🔍 Exécution recherche avec affichage uniforme...');
            
            // Annuler requête précédente
            if (CONFIG.search.currentRequest) {
                CONFIG.search.currentRequest.abort();
            }
            
            // ✅ CORRECTION: Utiliser le nouveau handler unifié
            const searchData = {
                action: 'search_trainers', // Utilise le handler unifié
                nonce: trainer_ajax.nonce,
                search_term: searchElements.searchInput.val().trim(),
                specialty_filter: searchElements.specialtyFilter.val() || '',
                region_filter: searchElements.regionFilter.val() || '',
                experience_filter: searchElements.experienceFilter.val() || '',
                availability_filter: searchElements.availabilityFilter.val() || '',
                per_page: CONFIG.pagination.perPage,
                page: CONFIG.pagination.currentPage
            };
            
            console.log('📤 Données recherche (UNIFORME):', searchData);
            
            showSearchLoading();
            
            CONFIG.search.currentRequest = $.ajax({
                url: trainer_ajax.ajax_url,
                type: 'POST',
                data: searchData,
                timeout: 15000,
                success: function(response) {
                    console.log('📥 Réponse UNIFORME:', response);
                    handleSearchSuccess(response);
                },
                error: function(xhr, status, error) {
                    if (status !== 'abort') {
                        console.error('❌ Erreur recherche:', error);
                        handleSearchError();
                    }
                },
                complete: function() {
                    hideSearchLoading();
                    CONFIG.search.currentRequest = null;
                }
            });
        }

        function handleSearchSuccess(response) {
            if (response.success && response.data) {
                const data = response.data;
                
                console.log('✅ Affichage des résultats avec style uniforme');
                
                if (data.total > 0) {
                    displayResults(data);
                    updateResultsInfo(data);
                    updatePagination(data);
                } else {
                    showEmptyState();
                }
            } else {
                showEmptyState();
            }
        }

        function handleSearchError() {
            searchElements.trainersGrid.html(`
                <div class="trpro-search-error">
                    <div class="trpro-error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3>Erreur de recherche</h3>
                    <p>Impossible de charger les formateurs. Veuillez réessayer.</p>
                    <button class="trpro-btn trpro-btn-primary" onclick="location.reload();">
                        <i class="fas fa-redo"></i> Recharger
                    </button>
                </div>
            `);
        }

        function displayResults(data) {
            if (data.html) {
                searchElements.trainersGrid.fadeOut(200, function() {
                    $(this).html(data.html).fadeIn(300, function() {
                        // ✅ CORRECTION: Réappliquer la vue actuelle après affichage uniforme
                        $(this).removeClass('trpro-view-grid trpro-view-list')
                              .addClass(`trpro-view-${CONFIG.view.current}`);
                        
                        setTimeout(adjustCardsAlignment, 100);
                        initTrainerCards(); // Réinitialiser les événements des cartes
                        
                        console.log('✅ Affichage uniforme appliqué - Vue:', CONFIG.view.current);
                    });
                });
            }
            
            searchElements.trainersGrid.show();
            searchElements.emptyState.hide();
        }

        function updateResultsInfo(data) {
            const count = data.total || 0;
            searchElements.resultsCount.text(`${count} formateur${count > 1 ? 's' : ''} trouvé${count > 1 ? 's' : ''}`);
        }

        // ===== PAGINATION CORRIGÉE =====
        function initPagination() {
            console.log('🔧 Init pagination...');
            
            // Délégation d'événements pour pagination dynamique
            $(document).off('click', '.trpro-page-btn').on('click', '.trpro-page-btn', function(e) {
                e.preventDefault();
                
                const page = parseInt($(this).data('page'));
                if (page && page !== CONFIG.pagination.currentPage) {
                    changePage(page);
                }
            });
            
            console.log('✅ Pagination initialisée');
        }

        function changePage(page) {
            console.log(`📄 Changement page: ${CONFIG.pagination.currentPage} → ${page}`);
            
            CONFIG.pagination.currentPage = page;
            
            // Scroll vers le haut
            $('html, body').animate({
                scrollTop: searchElements.resultsHeader.length ? 
                    searchElements.resultsHeader.offset().top - 100 : 0
            }, 400);
            
            performSearch();
        }

        function updatePagination(data) {
            if (!data.total_pages || data.total_pages <= 1) {
                searchElements.pagination.hide();
                return;
            }
            
            let paginationHtml = '<div class="trpro-pagination-wrapper">';
            
            // Bouton précédent
            if (data.page > 1) {
                paginationHtml += `<button class="trpro-page-btn trpro-page-prev" data-page="${data.page - 1}">
                    <i class="fas fa-chevron-left"></i> Précédent
                </button>`;
            }
            
            // Numéros de pages
            for (let i = 1; i <= Math.min(data.total_pages, 10); i++) {
                const activeClass = i === data.page ? 'active' : '';
                paginationHtml += `<button class="trpro-page-btn trpro-page-number ${activeClass}" data-page="${i}">${i}</button>`;
            }
            
            // Bouton suivant
            if (data.page < data.total_pages) {
                paginationHtml += `<button class="trpro-page-btn trpro-page-next" data-page="${data.page + 1}">
                    Suivant <i class="fas fa-chevron-right"></i>
                </button>`;
            }
            
            paginationHtml += '</div>';
            
            searchElements.pagination.html(paginationHtml).show();
        }

        // ===== CARTES FORMATEURS CORRIGÉES AVEC FIX MODAL =====
        function initTrainerCards() {
            console.log('🔧 Init cartes formateurs...');
            
            // IMPORTANT: Nettoyer tous les anciens gestionnaires d'événements
            $(document).off('click', '.trpro-modal-close');
            $(document).off('click', '.trpro-modal-overlay');
            $(document).off('click', '.trpro-modal-backdrop');
            $(document).off('keydown.modal');
            
            // Boutons profil avec modal
            $(document).off('click', '.trpro-btn-profile').on('click', '.trpro-btn-profile', function(e) {
                e.preventDefault();
                const trainerId = $(this).data('trainer-id');
                if (trainerId) {
                    loadTrainerProfile(trainerId);
                }
            });
            
            // ✅ CORRECTION: Boutons contact avec nonce unifié
            $(document).off('click', '.trpro-btn-contact').on('click', '.trpro-btn-contact', function(e) {
                e.preventDefault();
                const trainerId = $(this).data('trainer-id');
                const trainerName = $(this).data('trainer-name');
                
                console.log('📧 Contact depuis affichage uniforme:', {trainerId, trainerName});
                
                if (trainerId && trainerName) {
                    openContactModal(trainerId, trainerName);
                }
            });
            
            // ===== GESTIONNAIRE DE FERMETURE UNIFIÉ =====
            // Fermeture par le bouton close
            $(document).on('click', '.trpro-modal-close', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('🔄 Fermeture modal via bouton');
                closeAllModals();
            });
            
            // Fermeture en cliquant sur l'overlay (fond sombre)
            $(document).on('click', '.trpro-modal-overlay', function(e) {
                if (e.target === this) {
                    console.log('🔄 Fermeture modal via overlay');
                    closeAllModals();
                }
            });
            
            // Fermeture avec Escape
            $(document).on('keydown.modal', function(e) {
                if (e.key === 'Escape') {
                    console.log('🔄 Fermeture modal via Escape');
                    closeAllModals();
                }
            });
            
            console.log('✅ Cartes formateurs initialisées - AFFICHAGE UNIFORME');
        }

        // ===== NOUVELLE FONCTION DE FERMETURE UNIFIÉE =====
        function closeAllModals() {
            // Fermer toutes les modales possibles
            $('.trpro-modal-overlay, .trpro-trainer-modal').fadeOut(300, function() {
                $(this).remove();
            });
            
            // Retirer la classe du body
            $('body').removeClass('modal-open');
            
            // Nettoyer les modales temporaires
            $('#trpro-profile-modal, #trpro-contact-modal, #trpro-success-modal, #trpro-error-modal, #trpro-profile-loading').remove();
        }

        // ===== MODAL PROFIL CORRIGÉE =====
        function loadTrainerProfile(trainerId) {
            console.log('👤 Chargement profil:', trainerId);
            
            showProfileLoadingModal();
            
            $.ajax({
                url: trainer_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_trainer_profile',
                    nonce: trainer_ajax.nonce,
                    trainer_id: trainerId
                },
                success: function(response) {
                    hideProfileLoadingModal();
                    
                    if (response.success && response.data) {
                        showProfileModal(response.data);
                    } else {
                        showErrorModal('Erreur lors du chargement du profil');
                    }
                },
                error: function() {
                    hideProfileLoadingModal();
                    showErrorModal('Erreur de connexion');
                }
            });
        }

        function showProfileLoadingModal() {
            const loadingHTML = `
                <div class="trpro-modal-overlay active" id="trpro-profile-loading">
                    <div class="trpro-modal-container">
                        <div class="trpro-modal-content">
                            <div class="trpro-modal-loading">
                                <div class="trpro-spinner"></div>
                                <p>Chargement du profil...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(loadingHTML).addClass('modal-open');
        }

        function hideProfileLoadingModal() {
            $('#trpro-profile-loading').remove();
            if (!$('.trpro-modal-overlay').length) {
                $('body').removeClass('modal-open');
            }
        }

        function showProfileModal(profileData) {
            const regions = profileData.intervention_regions || [];
            const specialties = profileData.specialties || [];
            
            const modalHTML = `
                <div class="trpro-modal-overlay" id="trpro-profile-modal">
                    <div class="trpro-modal-container">
                        <div class="trpro-modal-header">
                            <div class="trpro-modal-title">
                                <div class="trpro-modal-avatar">
                                    <div class="trpro-modal-avatar-placeholder">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                </div>
                                <div class="trpro-modal-info">
                                    <h4>${escapeHtml(profileData.display_name)}</h4>
                                    <p>Formateur Expert #${String(profileData.id).padStart(4, '0')}</p>
                                    ${profileData.company ? `<p class="trpro-modal-company">${escapeHtml(profileData.company)}</p>` : ''}
                                </div>
                            </div>
                            <button type="button" class="trpro-modal-close" aria-label="Fermer">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="trpro-modal-content">
                            ${regions.length > 0 ? `
                                <div class="trpro-modal-section">
                                    <h5><i class="fas fa-map-marker-alt"></i> Zones d'intervention</h5>
                                    <div class="trpro-modal-zones">
                                        ${regions.map(region => `
                                            <span class="trpro-zone-chip">
                                                <i class="fas fa-map-pin"></i>
                                                ${escapeHtml(region)}
                                            </span>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}
                            
                            <div class="trpro-modal-section">
                                <h5><i class="fas fa-cogs"></i> Compétences techniques</h5>
                                <div class="trpro-detailed-specialties">
                                    ${specialties.map(specialty => `
                                        <div class="trpro-specialty-chip">
                                            <i class="fas fa-cog"></i>
                                            <span>${escapeHtml(specialty)}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                            
                            ${profileData.experience ? `
                                <div class="trpro-modal-section">
                                    <h5><i class="fas fa-briefcase"></i> Expérience professionnelle</h5>
                                    <div class="trpro-experience-full">
                                        ${escapeHtml(profileData.experience).replace(/\n/g, '<br>')}
                                    </div>
                                </div>
                            ` : ''}
                            
                            <div class="trpro-modal-actions">
                                <button class="trpro-btn trpro-btn-primary trpro-btn-large trpro-btn-contact-modal" 
                                        data-trainer-id="${profileData.id}" 
                                        data-trainer-name="${escapeHtml(profileData.display_name)}">
                                    <i class="fas fa-envelope"></i>
                                    Contacter ce formateur
                                </button>
                                
                                ${profileData.linkedin_url ? `
                                    <div class="trpro-linkedin-info">
                                        <i class="fab fa-linkedin"></i>
                                        <span>Profil LinkedIn disponible (contactez-nous pour accès)</span>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Ajouter la modal au DOM
            $('body').append(modalHTML).addClass('modal-open');
            
            // Animer l'apparition
            setTimeout(() => {
                $('#trpro-profile-modal').addClass('active');
            }, 10);
            
            // Réattacher les événements pour le modal
            $('.trpro-btn-contact-modal').off('click').on('click', function() {
                const trainerId = $(this).data('trainer-id');
                const trainerName = $(this).data('trainer-name');
                closeModal();
                setTimeout(() => openContactModal(trainerId, trainerName), 300);
            });
        }

        // ===== ✅ MODAL CONTACT AVEC NONCE UNIFIÉ =====
        function openContactModal(trainerId, trainerName) {
            console.log('📧 Ouverture modal contact (UNIFORME):', {trainerId, trainerName});
            
            const contactHTML = `
                <div class="trpro-modal-overlay active" id="trpro-contact-modal">
                    <div class="trpro-modal-container">
                        <div class="trpro-modal-header">
                            <h4>Contacter ${escapeHtml(trainerName)}</h4>
                            <button type="button" class="trpro-modal-close" aria-label="Fermer">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="trpro-modal-content">
                            <form id="trpro-contact-form">
                                <div class="trpro-form-group">
                                    <label for="contact_name">Votre nom *</label>
                                    <input type="text" id="contact_name" name="contact_name" required>
                                </div>
                                
                                <div class="trpro-form-group">
                                    <label for="contact_email">Votre email *</label>
                                    <input type="email" id="contact_email" name="contact_email" required>
                                </div>
                                
                                <div class="trpro-form-group">
                                    <label for="contact_company">Votre entreprise</label>
                                    <input type="text" id="contact_company" name="contact_company">
                                </div>
                                
                                <div class="trpro-form-group">
                                    <label for="contact_message">Votre message *</label>
                                    <textarea id="contact_message" name="contact_message" rows="4" required 
                                              placeholder="Décrivez votre projet de formation..."></textarea>
                                </div>
                                
                                <div class="trpro-contact-actions">
                                    <button type="submit" class="trpro-btn trpro-btn-primary trpro-btn-large">
                                        <i class="fas fa-paper-plane"></i>
                                        Envoyer le message
                                    </button>
                                </div>
                                
                                <input type="hidden" name="trainer_id" value="${trainerId}">
                                <input type="hidden" name="action" value="contact_trainer">
                            </form>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(contactHTML).addClass('modal-open');
            
            // ✅ CORRECTION: Gestionnaire du formulaire de contact avec nonce principal
            $('#trpro-contact-form').on('submit', function(e) {
                e.preventDefault();
                console.log('📤 Soumission formulaire contact (UNIFORME)...');
                
                const $form = $(this);
                const $submitBtn = $form.find('button[type="submit"]');
                
                // Désactiver le bouton pendant l'envoi
                $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Envoi...');
                
                const formData = new FormData(this);
                // ✅ CORRECTION PRINCIPALE: Utiliser le nonce principal au lieu de contact_nonce
                formData.append('nonce', trainer_ajax.nonce);
                
                console.log('📤 Données formulaire contact (UNIFORME):', Object.fromEntries(formData));
                
                $.ajax({
                    url: trainer_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    timeout: 15000,
                    success: function(response) {
                        console.log('✅ Réponse contact (UNIFORME):', response);
                        if (response.success) {
                            showSuccessModal(response.data.message || 'Message envoyé avec succès !');
                        } else {
                            showErrorModal(response.data?.message || 'Erreur lors de l\'envoi du message');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ Erreur contact (UNIFORME):', {xhr, status, error});
                        showErrorModal('Erreur de connexion. Veuillez réessayer.');
                    },
                    complete: function() {
                        // Réactiver le bouton
                        $submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Envoyer le message');
                    }
                });
            });
        }

        // ===== EXPOSITION GLOBALE DE LA FONCTION CONTACT =====
        window.openContactModal = openContactModal;

        function showSuccessModal(message) {
            closeModal();
            const successHTML = `
                <div class="trpro-modal-overlay active" id="trpro-success-modal">
                    <div class="trpro-modal-container">
                        <div class="trpro-modal-content trpro-success-content">
                            <div class="trpro-success-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3>Succès !</h3>
                            <p>${escapeHtml(message)}</p>
                            <button type="button" class="trpro-btn trpro-btn-primary trpro-btn-close-success">
                                Fermer
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(successHTML);
            
            // Gestionnaire pour fermer
            $('.trpro-btn-close-success').on('click', function() {
                closeAllModals();
            });
            
            setTimeout(() => {
                closeAllModals();
            }, 3000);
        }

        function showErrorModal(message) {
            closeModal();
            const errorHTML = `
                <div class="trpro-modal-overlay active" id="trpro-error-modal">
                    <div class="trpro-modal-container">
                        <div class="trpro-modal-content trpro-error-content">
                            <div class="trpro-error-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h3>Erreur</h3>
                            <p>${escapeHtml(message)}</p>
                            <button type="button" class="trpro-btn trpro-btn-primary trpro-btn-close-error">
                                Fermer
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(errorHTML);
            
            // Gestionnaire pour fermer
            $('.trpro-btn-close-error').on('click', function() {
                closeAllModals();
            });
        }

        // Remplacer la fonction closeModal existante
        function closeModal() {
            closeAllModals();
        }

        // ===== TAGS POPULAIRES =====
        function initPopularTags() {
            searchElements.popularTags.off('click').on('click', function(e) {
                e.preventDefault();
                
                const searchTerm = $(this).data('search');
                const category = $(this).data('category');
                const region = $(this).data('region');
                
                if (searchTerm) {
                    searchElements.searchInput.val(searchTerm);
                }
                
                if (category) {
                    searchElements.specialtyFilter.val(category);
                }
                
                if (region) {
                    searchElements.regionFilter.val(region);
                }
                
                CONFIG.pagination.currentPage = 1;
                performSearch();
            });
        }

        // ===== ÉTATS D'AFFICHAGE =====
        function showSearchLoading() {
            searchElements.loading.fadeIn(200);
            searchElements.trainersGrid.css('opacity', 0.5);
        }

        function hideSearchLoading() {
            searchElements.loading.fadeOut(200);
            searchElements.trainersGrid.css('opacity', 1);
        }

        function showEmptyState() {
            searchElements.trainersGrid.hide();
            searchElements.emptyState.fadeIn(300);
            searchElements.pagination.hide();
        }

        function loadInitialView() {
            // Charger la vue depuis localStorage
            updateViewDisplay(CONFIG.view.current);
            
            // Charger les formateurs si on est sur la page liste
            if (searchElements.trainersGrid.length && searchElements.trainersGrid.children().length === 0) {
                performSearch();
            }
        }

        // ===== ANIMATIONS MODERNES =====
        function initFormAnimations() {
            // Animation d'easing personnalisée
            $.easing.easeOutCubic = function(x, t, b, c, d) {
                return c*((t=t/d-1)*t*t + 1) + b;
            };
            
            // Animation des champs au focus
            formElements.form.find('input, textarea, select').on('focus', function() {
                $(this).closest('.trpro-form-group').addClass('focused');
            }).on('blur', function() {
                $(this).closest('.trpro-form-group').removeClass('focused');
            });
        }

        // ===== ANIMATIONS GÉNÉRALES =====
        function initGlobalAnimations() {
            // Intersection Observer pour les animations
            if (typeof IntersectionObserver !== 'undefined') {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('trpro-fade-in');
                        }
                    });
                }, { threshold: 0.1 });

                // Observer les éléments animables
                $('.trpro-specialty-card, .trpro-card, .trpro-trainer-card').each(function() {
                    observer.observe(this);
                });
            }
            
            // Parallax simple pour le hero
            $(window).on('scroll', function() {
                const scrollTop = $(this).scrollTop();
                const $heroBackground = $('.trpro-hero-background');
                
                if ($heroBackground.length > 0 && scrollTop < $(window).height()) {
                    $heroBackground.css('transform', `translateY(${scrollTop * 0.5}px)`);
                }
            });
        }

        // ===== RESPONSIVE =====
        $(window).on('resize', debounce(adjustCardsAlignment, 250));

        // ===== UTILITAIRES =====
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        function throttle(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text || '').replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        function isMobile() {
            return window.innerWidth <= 768;
        }

        function isTablet() {
            return window.innerWidth <= 1024 && window.innerWidth > 768;
        }

        // ===== DEBUG HELPER (DÉVELOPPEMENT) =====
        if (window.location.hostname === 'localhost' || window.location.hostname.includes('dev')) {
            window.debugModals = {
                checkHandlers: function() {
                    console.log('Handlers attachés:', {
                        closeButtons: $('.trpro-modal-close').length,
                        overlays: $('.trpro-modal-overlay').length,
                        events: $._data(document, 'events')
                    });
                },
                testClose: function() {
                    $('.trpro-modal-close').first().trigger('click');
                },
                forceClose: function() {
                    closeAllModals();
                }
            };
            
            window.trainerDebug = {
                // Formulaire
                currentStep: () => currentStep,
                validateStep: () => validateCurrentStep(),
                formData: () => new FormData(formElements.form[0]),
                resetForm: () => {
                    formElements.form[0].reset();
                    currentStep = 1;
                    showStep(1);
                },
                showStep: (step) => showStep(step),
                
                // Recherche UNIFORME
                performSearch: () => performSearch(),
                
                // Configuration
                config: CONFIG,
                
                // Vues
                switchView: updateViewDisplay,
                
                // Modales
                closeAllModals: () => closeAllModals(),
                debugModals: window.debugModals,
                
                // Contact UNIFORME
                testContact: (trainerId, trainerName) => openContactModal(trainerId, trainerName),
                
                // Utilitaires
                isMobile: () => isMobile(),
                isTablet: () => isTablet(),
                
                // État global
                getState: () => ({
                    currentStep,
                    formSubmitting,
                    searchTimeout,
                    validationTimeout,
                    config: CONFIG
                })
            };
            console.log('🛠️ Debug helper disponible: window.trainerDebug et window.debugModals');
        }

        // ===== INITIALISATION FINALE =====
        console.log('✅ Trainer Registration Pro: Initialisation complète terminée - AFFICHAGE UNIFORME');
        console.log('📊 Composants initialisés:', {
            formulaire: formElements.form.length > 0,
            recherche: searchElements.trainersGrid.length > 0,
            cartes: $('.trpro-trainer-card').length,
            animations: true,
            affichageUniforme: 'ACTIVÉ', // ✅ Indicateur principal
            contactModal: 'NONCE CORRIGÉ'
        });
        
        // Notification de succès d'initialisation
        if (formElements.form.length > 0) {
            console.log('🎯 Formulaire prêt - Navigation multi-étapes activée');
        }
        
        if (searchElements.trainersGrid.length > 0) {
            console.log('🎯 Recherche prête - AFFICHAGE UNIFORME entre AJAX et statique');
        }
        
        // ✅ Vérification spécifique de la correction
        console.log('🔐 Nonce de contact unifié: CORRIGÉ - Utilise trainer_ajax.nonce');
        console.log('🎨 Affichage uniforme: ACTIVÉ - Même style pour recherche AJAX et template statique');
        
        // Vérification de la compatibilité
        const features = {
            intersectionObserver: typeof IntersectionObserver !== 'undefined',
            localStorage: typeof Storage !== 'undefined',
            formData: typeof FormData !== 'undefined',
            fetch: typeof fetch !== 'undefined'
        };
        
        console.log('🔧 Support navigateur:', features);
        
        if (!features.formData) {
            console.warn('⚠️ FormData non supporté - Upload de fichiers limité');
        }
        
        if (!features.intersectionObserver) {
            console.warn('⚠️ IntersectionObserver non supporté - Animations limitées');
        }

    });

})(jQuery);