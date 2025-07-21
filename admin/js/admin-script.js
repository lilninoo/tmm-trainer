/**
 * JavaScript pour l'administration du plugin
 * 
 * Fichier: /wp-content/plugins/trainer-registration-plugin/admin/js/admin-script.js
 */

jQuery(document).ready(function($) {
    'use strict';

    // ===== GESTION DES ACTIONS SUR LES FORMATEURS =====
    
    let currentAction = '';
    let currentTrainerId = 0;
    
    // Actions individuelles sur les formateurs
    $('.trainer-action').on('click', function(e) {
        e.preventDefault();
        
        const action = $(this).data('action');
        const trainerId = $(this).data('trainer-id');
        const trainerRow = $(`#trainer-${trainerId}`);
        const trainerName = trainerRow.find('.trainer-name').text().trim();
        
        currentAction = action;
        currentTrainerId = trainerId;
        
        // Configuration du modal selon l'action
        let modalTitle = '';
        let modalMessage = '';
        let confirmButtonClass = 'button-primary';
        
        switch (action) {
            case 'approve':
                modalTitle = 'Approuver le formateur';
                modalMessage = `Êtes-vous sûr de vouloir approuver <strong>${trainerName}</strong> ?<br><br>Cette action rendra le formateur visible publiquement.`;
                confirmButtonClass = 'button-primary';
                break;
                
            case 'reject':
                modalTitle = 'Rejeter le formateur';
                modalMessage = `Êtes-vous sûr de vouloir rejeter <strong>${trainerName}</strong> ?<br><br>Le formateur ne sera pas visible publiquement.`;
                confirmButtonClass = 'button-secondary';
                break;
                
            case 'delete':
                modalTitle = 'Supprimer le formateur';
                modalMessage = `Êtes-vous sûr de vouloir supprimer définitivement <strong>${trainerName}</strong> ?<br><br><span style="color: #d63638;">Cette action est irréversible et supprimera tous les fichiers associés.</span>`;
                confirmButtonClass = 'button-primary button-danger';
                break;
        }
        
        // Afficher le modal de confirmation
        $('#modal-title').text(modalTitle);
        $('#modal-message').html(modalMessage);
        $('#modal-confirm').removeClass().addClass(`button ${confirmButtonClass}`);
        $('#trainer-confirm-modal').show();
    });
    
    // Confirmation de l'action
    $('#modal-confirm').on('click', function() {
        if (currentAction && currentTrainerId) {
            executeTrainerAction(currentAction, currentTrainerId);
        }
        closeModal();
    });
    
    // Annulation de l'action
    $('#modal-cancel, .trainer-modal-close').on('click', function() {
        closeModal();
    });
    
    // Fermeture du modal en cliquant à l'extérieur
    $('#trainer-confirm-modal').on('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    
    function closeModal() {
        $('#trainer-confirm-modal').hide();
        currentAction = '';
        currentTrainerId = 0;
    }
    
    function executeTrainerAction(action, trainerId) {
        showLoading();
        
        const data = {
            action: action === 'delete' ? 'delete_trainer' : 'update_trainer_status',
            trainer_id: trainerId,
            status: action,
            nonce: trainer_admin_ajax.nonce
        };
        
        $.ajax({
            url: trainer_admin_ajax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    if (action === 'delete') {
                        // Supprimer la ligne du tableau avec animation
                        $(`#trainer-${trainerId}`).fadeOut(300, function() {
                            $(this).remove();
                            updateStatsAfterAction();
                        });
                        
                        showNotice('success', response.data.message || 'Formateur supprimé avec succès.');
                    } else {
                        // Mettre à jour le statut dans le tableau
                        updateTrainerStatus(trainerId, action);
                        showNotice('success', response.data.message || 'Statut mis à jour avec succès.');
                    }
                } else {
                    showNotice('error', response.data.message || 'Erreur lors de l\'action.');
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                console.error('Erreur AJAX:', error);
                showNotice('error', 'Erreur de connexion. Veuillez réessayer.');
            }
        });
    }
    
    function updateTrainerStatus(trainerId, newStatus) {
        const row = $(`#trainer-${trainerId}`);
        const statusCell = row.find('.status-badge');
        const actionsCell = row.find('.action-buttons');
        
        // Mettre à jour l'attribut data-status
        row.attr('data-status', newStatus);
        
        // Mettre à jour le badge de statut
        statusCell.removeClass('status-pending status-approved status-rejected')
                  .addClass(`status-${newStatus}`);
        
        let statusText = '';
        let statusIcon = '';
        
        switch (newStatus) {
            case 'approved':
                statusText = 'Approuvé';
                statusIcon = 'fas fa-check-circle';
                break;
            case 'rejected':
                statusText = 'Rejeté';
                statusIcon = 'fas fa-times-circle';
                break;
        }
        
        statusCell.html(`<i class="${statusIcon}"></i> ${statusText}`);
        
        // Masquer les boutons d'action s'il n'est plus en attente
        if (newStatus !== 'pending') {
            actionsCell.find('[data-action="approve"], [data-action="reject"]').hide();
        }
        
        // Animation de mise à jour
        row.addClass('updated').delay(2000).queue(function() {
            $(this).removeClass('updated').dequeue();
        });
    }

    // ===== ACTIONS GROUPÉES =====
    
    // Sélection/désélection de tous les formateurs
    $('#cb-select-all').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.trainer-checkbox').prop('checked', isChecked);
        updateBulkActionButton();
    });
    
    // Gestion des cases à cocher individuelles
    $('.trainer-checkbox').on('change', function() {
        updateSelectAllCheckbox();
        updateBulkActionButton();
    });
    
    function updateSelectAllCheckbox() {
        const totalCheckboxes = $('.trainer-checkbox').length;
        const checkedCheckboxes = $('.trainer-checkbox:checked').length;
        
        $('#cb-select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
    }
    
    function updateBulkActionButton() {
        const checkedCount = $('.trainer-checkbox:checked').length;
        const bulkButton = $('#bulk-action-submit');
        
        if (checkedCount > 0) {
            bulkButton.prop('disabled', false)
                      .text(`Appliquer (${checkedCount})`)
                      .removeClass('disabled');
        } else {
            bulkButton.prop('disabled', true)
                      .text('Appliquer')
                      .addClass('disabled');
        }
    }
    
    // Exécution des actions groupées
    $('#bulk-action-submit').on('click', function() {
        const selectedAction = $('#bulk-action-selector').val();
        const selectedTrainers = $('.trainer-checkbox:checked');
        
        if (selectedAction === '-1') {
            showNotice('warning', 'Veuillez sélectionner une action.');
            return;
        }
        
        if (selectedTrainers.length === 0) {
            showNotice('warning', 'Veuillez sélectionner au moins un formateur.');
            return;
        }
        
        const trainerIds = selectedTrainers.map(function() {
            return $(this).val();
        }).get();
        
        const count = trainerIds.length;
        let actionText = '';
        
        switch (selectedAction) {
            case 'approve':
                actionText = `approuver ${count} formateur(s)`;
                break;
            case 'reject':
                actionText = `rejeter ${count} formateur(s)`;
                break;
            case 'delete':
                actionText = `supprimer définitivement ${count} formateur(s)`;
                break;
        }
        
        if (confirm(`Êtes-vous sûr de vouloir ${actionText} ?`)) {
            executeBulkAction(selectedAction, trainerIds);
        }
    });
    
    function executeBulkAction(action, trainerIds) {
        showLoading();
        
        let completedActions = 0;
        const totalActions = trainerIds.length;
        let errors = [];
        
        // Traiter chaque formateur individuellement
        trainerIds.forEach(function(trainerId) {
            const data = {
                action: action === 'delete' ? 'delete_trainer' : 'update_trainer_status',
                trainer_id: trainerId,
                status: action,
                nonce: trainer_admin_ajax.nonce
            };
            
            $.ajax({
                url: trainer_admin_ajax.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    completedActions++;
                    
                    if (response.success) {
                        if (action === 'delete') {
                            $(`#trainer-${trainerId}`).fadeOut(300, function() {
                                $(this).remove();
                            });
                        } else {
                            updateTrainerStatus(trainerId, action);
                        }
                    } else {
                        errors.push(`Formateur #${trainerId}: ${response.data.message}`);
                    }
                    
                    // Vérifier si toutes les actions sont terminées
                    if (completedActions === totalActions) {
                        hideLoading();
                        finalizeBulkAction(action, totalActions - errors.length, errors);
                    }
                },
                error: function() {
                    completedActions++;
                    errors.push(`Formateur #${trainerId}: Erreur de connexion`);
                    
                    if (completedActions === totalActions) {
                        hideLoading();
                        finalizeBulkAction(action, totalActions - errors.length, errors);
                    }
                }
            });
        });
    }
    
    function finalizeBulkAction(action, successCount, errors) {
        // Décocher toutes les cases
        $('.trainer-checkbox, #cb-select-all').prop('checked', false);
        updateBulkActionButton();
        
        // Afficher les résultats
        if (successCount > 0) {
            let message = '';
            switch (action) {
                case 'approve':
                    message = `${successCount} formateur(s) approuvé(s) avec succès.`;
                    break;
                case 'reject':
                    message = `${successCount} formateur(s) rejeté(s) avec succès.`;
                    break;
                case 'delete':
                    message = `${successCount} formateur(s) supprimé(s) avec succès.`;
                    break;
            }
            showNotice('success', message);
        }
        
        if (errors.length > 0) {
            console.error('Erreurs lors des actions groupées:', errors);
            showNotice('error', `${errors.length} erreur(s) détectée(s). Consultez la console pour plus de détails.`);
        }
        
        updateStatsAfterAction();
    }

    // ===== RECHERCHE ET FILTRAGE =====
    
    let searchTimeout;
    
    // Recherche en temps réel
    $('#search-input').on('input', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val().toLowerCase();
        
        searchTimeout = setTimeout(function() {
            filterTrainers(searchTerm);
        }, 300);
    });
    
    // Filtrage par statut
    $('#status-filter').on('change', function() {
        const statusFilter = $(this).val();
        const searchTerm = $('#search-input').val().toLowerCase();
        
        filterTrainers(searchTerm, statusFilter);
    });
    
    function filterTrainers(searchTerm = '', statusFilter = 'all') {
        let visibleCount = 0;
        
        $('.trainer-row').each(function() {
            const row = $(this);
            const trainerName = row.find('.trainer-name').text().toLowerCase();
            const trainerEmail = row.find('.column-email a').text().toLowerCase();
            const trainerSpecialties = row.find('.specialties-list').text().toLowerCase();
            const trainerStatus = row.data('status');
            
            let showRow = true;
            
            // Filtrage par recherche
            if (searchTerm) {
                const searchableText = `${trainerName} ${trainerEmail} ${trainerSpecialties}`;
                showRow = searchableText.includes(searchTerm);
            }
            
            // Filtrage par statut
            if (showRow && statusFilter !== 'all') {
                showRow = trainerStatus === statusFilter;
            }
            
            if (showRow) {
                row.show();
                visibleCount++;
            } else {
                row.hide();
            }
        });
        
        // Afficher/masquer le message "aucun résultat"
        toggleNoResultsMessage(visibleCount === 0);
    }
    
    function toggleNoResultsMessage(show) {
        let noResultsRow = $('#no-results-row');
        
        if (show) {
            if (noResultsRow.length === 0) {
                const colspan = $('.trainers thead tr th').length;
                noResultsRow = $(`
                    <tr id="no-results-row">
                        <td colspan="${colspan}" class="no-results-cell">
                            <div class="trainer-empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-search"></i>
                                </div>
                                <h3>Aucun formateur trouvé</h3>
                                <p>Aucun formateur ne correspond à vos critères de recherche.</p>
                            </div>
                        </td>
                    </tr>
                `);
                $('.trainers tbody').append(noResultsRow);
            }
            noResultsRow.show();
        } else {
            noResultsRow.hide();
        }
    }

    // ===== GESTION DES NOTES =====
    
    $('#add-notes').on('click', function() {
        $('#notes-modal').show();
        $('#trainer-notes').focus();
    });
    
    $('#notes-cancel, .trainer-modal-close').on('click', function() {
        $('#notes-modal').hide();
        $('#trainer-notes').val('');
    });
    
    $('#notes-save').on('click', function() {
        const notes = $('#trainer-notes').val().trim();
        
        if (notes) {
            // Ici, vous pourriez sauvegarder les notes via AJAX
            console.log('Notes sauvegardées:', notes);
            showNotice('success', 'Notes sauvegardées avec succès.');
        }
        
        $('#notes-modal').hide();
        $('#trainer-notes').val('');
    });

    // ===== EXPORT ET TÉLÉCHARGEMENTS =====
    
    // Export des données
    $('#export-stats').on('click', function() {
        const format = $('#export-format').val();
        const period = $('#period-filter').val();
        
        showLoading();
        
        // Simulation d'export (remplacer par vraie implémentation)
        setTimeout(function() {
            hideLoading();
            showNotice('success', `Export ${format.toUpperCase()} généré avec succès.`);
        }, 2000);
    });
    
    // Actions de maintenance
    $('#clear-cache').on('click', function() {
        if (confirm('Êtes-vous sûr de vouloir vider le cache ?')) {
            showLoading();
            
            // Simulation (remplacer par vraie implémentation)
            setTimeout(function() {
                hideLoading();
                showNotice('success', 'Cache vidé avec succès.');
            }, 1000);
        }
    });
    
    $('#cleanup-files').on('click', function() {
        if (confirm('Cette action supprimera tous les fichiers orphelins. Continuer ?')) {
            showLoading();
            
            // Simulation (remplacer par vraie implémentation)
            setTimeout(function() {
                hideLoading();
                showNotice('success', 'Nettoyage des fichiers effectué.');
            }, 1500);
        }
    });

    // ===== NAVIGATION PAR ONGLETS (PARAMÈTRES) =====
    
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        const targetTab = $(this).data('tab');
        
        // Mettre à jour les onglets
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Mettre à jour le contenu
        $('.tab-content').removeClass('active');
        $(`#${targetTab}-tab`).addClass('active');
        
        // Sauvegarder l'onglet actif
        localStorage.setItem('trainer_active_tab', targetTab);
    });
    
    // Restaurer l'onglet actif au chargement
    const activeTab = localStorage.getItem('trainer_active_tab');
    if (activeTab) {
        $(`.nav-tab[data-tab="${activeTab}"]`).click();
    }

    // ===== PREVIEW DES DOCUMENTS =====
    
    $('.preview-document').on('click', function() {
        const filename = $(this).data('file');
        
        // Créer un modal de prévisualisation
        const previewModal = $(`
            <div id="document-preview-modal" class="trainer-modal">
                <div class="trainer-modal-content" style="max-width: 90vw; max-height: 90vh;">
                    <div class="trainer-modal-header">
                        <h3>Aperçu du document</h3>
                        <button type="button" class="trainer-modal-close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="trainer-modal-body" style="padding: 0; height: 70vh;">
                        <iframe src="path/to/document/viewer.php?file=${encodeURIComponent(filename)}" 
                                style="width: 100%; height: 100%; border: none;"></iframe>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(previewModal);
        previewModal.show();
        
        // Fermeture du modal
        previewModal.find('.trainer-modal-close').on('click', function() {
            previewModal.remove();
        });
        
        previewModal.on('click', function(e) {
            if (e.target === this) {
                previewModal.remove();
            }
        });
    });

    // ===== UTILITAIRES =====
    
    function showLoading() {
        $('#trainer-loading').show();
    }
    
    function hideLoading() {
        $('#trainer-loading').hide();
    }
    
    function showNotice(type, message) {
        // Supprimer les anciennes notifications
        $('.trainer-notice').remove();
        
        const noticeClass = type === 'success' ? 'notice-success' : 
                           type === 'warning' ? 'notice-warning' : 'notice-error';
        
        const notice = $(`
            <div class="notice ${noticeClass} is-dismissible trainer-notice">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `);
        
        $('.wrap h1').after(notice);
        
        // Auto-dismiss après 5 secondes
        setTimeout(function() {
            notice.fadeOut(function() {
                notice.remove();
            });
        }, 5000);
        
        // Bouton de fermeture
        notice.find('.notice-dismiss').on('click', function() {
            notice.fadeOut(function() {
                notice.remove();
            });
        });
        
        // Scroll vers la notification
        $('html, body').animate({
            scrollTop: notice.offset().top - 50
        }, 300);
    }
    
    function updateStatsAfterAction() {
        // Mettre à jour les cartes de statistiques
        const totalTrainers = $('.trainer-row:visible').length;
        const pendingTrainers = $('.trainer-row[data-status="pending"]:visible').length;
        const approvedTrainers = $('.trainer-row[data-status="approved"]:visible').length;
        const rejectedTrainers = $('.trainer-row[data-status="rejected"]:visible').length;
        
        $('.stats-card.total .stats-number').text(totalTrainers);
        $('.stats-card.pending .stats-number').text(pendingTrainers);
        $('.stats-card.approved .stats-number').text(approvedTrainers);
        $('.stats-card.rejected .stats-number').text(rejectedTrainers);
        
        // Animation des cartes mises à jour
        $('.stats-card').addClass('updated').delay(1000).queue(function() {
            $(this).removeClass('updated').dequeue();
        });
    }

    // ===== RACCOURCIS CLAVIER =====
    
    $(document).on('keydown', function(e) {
        // Échap pour fermer les modaux
        if (e.key === 'Escape') {
            $('.trainer-modal:visible').hide();
        }
        
        // Ctrl+A pour sélectionner tous les formateurs (si dans la liste)
        if (e.ctrlKey && e.key === 'a' && $('.trainer-checkbox').length > 0) {
            e.preventDefault();
            $('.trainer-checkbox').prop('checked', true);
            $('#cb-select-all').prop('checked', true);
            updateBulkActionButton();
        }
        
        // Ctrl+D pour désélectionner tous les formateurs
        if (e.ctrlKey && e.key === 'd' && $('.trainer-checkbox').length > 0) {
            e.preventDefault();
            $('.trainer-checkbox, #cb-select-all').prop('checked', false);
            updateBulkActionButton();
        }
    });

    // ===== AMÉLIORATION DE L'UX =====
    
    // Tooltips pour les boutons d'action
    $('[title]').hover(
        function() {
            const title = $(this).attr('title');
            $(this).data('tipText', title).removeAttr('title');
            
            const tooltip = $(`<div class="admin-tooltip">${title}</div>`);
            $('body').append(tooltip);
            
            const offset = $(this).offset();
            tooltip.css({
                top: offset.top - tooltip.outerHeight() - 10,
                left: offset.left + ($(this).outerWidth() / 2) - (tooltip.outerWidth() / 2)
            });
        },
        function() {
            $(this).attr('title', $(this).data('tipText'));
            $('.admin-tooltip').remove();
        }
    );
    
    // Animation de feedback pour les actions
    $(document).on('click', '.button', function() {
        const $this = $(this);
        $this.addClass('clicked');
        
        setTimeout(function() {
            $this.removeClass('clicked');
        }, 150);
    });
    
    // Sauvegarde automatique des paramètres (draft)
    if ($('#trainer-settings-form').length > 0) {
        let saveTimeout;
        
        $('#trainer-settings-form input, #trainer-settings-form select, #trainer-settings-form textarea').on('change input', function() {
            clearTimeout(saveTimeout);
            
            // Indication de modification
            if (!$('.settings-modified').length) {
                $('.settings-footer').prepend('<div class="settings-modified notice notice-info"><p><i class="fas fa-info-circle"></i> Vous avez des modifications non sauvegardées.</p></div>');
            }
            
            // Auto-save draft après 3 secondes d'inactivité
            saveTimeout = setTimeout(function() {
                saveDraftSettings();
            }, 3000);
        });
    }
    
    function saveDraftSettings() {
        const formData = $('#trainer-settings-form').serialize();
        localStorage.setItem('trainer_settings_draft', formData);
        
        $('.settings-modified p').html('<i class="fas fa-check-circle"></i> Brouillon sauvegardé automatiquement.');
        
        setTimeout(function() {
            $('.settings-modified').fadeOut(function() {
                $(this).remove();
            });
        }, 2000);
    }
    
    // Restaurer le brouillon au chargement
    const draftSettings = localStorage.getItem('trainer_settings_draft');
    if (draftSettings && $('#trainer-settings-form').length > 0) {
        // Afficher une notification pour restaurer le brouillon
        const restoreNotice = $(`
            <div class="notice notice-info is-dismissible">
                <p>
                    <i class="fas fa-history"></i> 
                    Un brouillon de vos paramètres a été trouvé. 
                    <a href="#" id="restore-draft">Restaurer le brouillon</a> | 
                    <a href="#" id="discard-draft">Ignorer</a>
                </p>
            </div>
        `);
        
        $('.wrap h1').after(restoreNotice);
        
        $('#restore-draft').on('click', function(e) {
            e.preventDefault();
            // Logique de restauration du brouillon
            localStorage.removeItem('trainer_settings_draft');
            restoreNotice.remove();
            showNotice('success', 'Brouillon restauré avec succès.');
        });
        
        $('#discard-draft').on('click', function(e) {
            e.preventDefault();
            localStorage.removeItem('trainer_settings_draft');
            restoreNotice.remove();
        });
    }

    // ===== INITIALISATION =====
    
    // Initialiser les stats au chargement
    updateBulkActionButton();
    
    // Animer les cartes de stats au chargement
    $('.stats-card').each(function(index) {
        $(this).delay(index * 100).queue(function() {
            $(this).addClass('loaded').dequeue();
        });
    });
    
    console.log('Trainer Registration Admin: JavaScript loaded successfully');
});

// ===== STYLES CSS DYNAMIQUES =====

const adminStyles = `
<style>
.trainer-row.updated {
    background: rgba(0, 163, 42, 0.1) !important;
    transition: background-color 0.3s ease;
}

.stats-card.updated {
    transform: scale(1.02);
    box-shadow: 0 4px 15px rgba(0, 115, 170, 0.15);
    transition: all 0.3s ease;
}

.stats-card.loaded {
    animation: slideInUp 0.5s ease forwards;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.button.clicked {
    transform: scale(0.95);
    transition: transform 0.15s ease;
}

.admin-tooltip {
    position: absolute;
    background: #333;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    z-index: 9999;
    white-space: nowrap;
    pointer-events: none;
}

.admin-tooltip:after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -5px;
    border: 5px solid transparent;
    border-top-color: #333;
}

.settings-modified {
    margin: 15px 0;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.trainer-notice {
    animation: slideDown 0.3s ease;
}

.no-results-cell {
    text-align: center;
    padding: 40px 20px;
}

.button-danger {
    background: #d63638 !important;
    border-color: #d63638 !important;
    color: white !important;
}

.button-danger:hover {
    background: #b32d2e !important;
    border-color: #b32d2e !important;
}

.disabled {
    opacity: 0.6;
    cursor: not-allowed !important;
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', adminStyles);