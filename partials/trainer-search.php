/**
 * SCRIPT D'INITIALISATION DU CATALOGUE DE FORMATEURS
 * ✅ Corrige tous les problèmes identifiés
 * 
 * À ajouter dans le footer de la page du catalogue
 * Assure le bon fonctionnement de tous les composants
 */

(function($) {
    'use strict';

    // ===== CONFIGURATION GLOBALE =====
    const CATALOG_CONFIG = {
        view: {
            current: 'grid',
            transition: 400
        },
        pagination: {
            perPage: 12,
            currentPage: 1
        },
        search: {
            delay: 500,
            minLength: 2
        },
        filters: {
            autoSubmit: true
        }
    };

    // ===== ÉLÉMENTS DOM =====
    const elements = {
        // Contrôles de vue
        viewBtns: $('.trpro-view-btn'),
        trainersGrid: $('#trpro-trainers-grid'),
        
        // Filtres
        filterForm: $('#trpro-filter-form'),
        searchInput: $('#trpro-live-search'),
        specialtyFilter: $('#trpro-specialty-filter'),
        regionFilter: $('#trpro-region-filter'),
        experienceFilter: $('#trpro-experience-filter'),
        availabilityFilter: $('#trpro-availability-filter'),
        sortSelect: $('#trpro-sort-select'),
        resetBtn: $('#trpro-reset-filters'),
        clearBtn: $('#trpro-clear-search'),
        
        // États
        loading: $('#trpro-search-loading'),
        emptyState: $('#trpro-empty-state'),
        resultsHeader: $('#trpro-results-header'),
        
        // Compteurs
        countNumber: $('#trpro-count-number'),
        filterStatus: $('#trpro-filter-status'),
        
        // Tags populaires
        popularTags: $('.trpro-tag')
    };

    // ===== INITIALISATION PRINCIPALE =====
    $(document).ready(function() {
        console.log('🚀 Catalogue des formateurs : Initialisation...');
        
        // Vérifier que les éléments requis existent
        if (!elements.trainersGrid.length) {
            console.warn('⚠️ Grid des formateurs non trouvée');
            return;
        }

        // Initialiser tous les composants
        initViewSwitcher();
        initFilters();
        initSearch();
        initSort();
        initPopularTags();
        initPagination();
        initCardInteractions();
        
        // Charger l'état initial depuis l'URL
        loadInitialState();
        
        // Ajuster l'alignement des cartes
        adjustCardsAlignment();
        
        console.log('✅ Catalogue des formateurs : Prêt');
    });

    // ===== SYSTÈME VUE GRILLE/LISTE (CORRECTION PRINCIPALE) =====
    function initViewSwitcher() {
        console.log('🔧 Initialisation du commutateur de vue...');
        
        // Détecter la vue actuelle
        if (elements.trainersGrid.hasClass('trpro-view-list')) {
            CATALOG_CONFIG.view.current = 'list';
            elements.viewBtns.removeClass('active');
            elements.viewBtns.filter('[data-view="list"]').addClass('active');
        }
        
        // Gestionnaire de clic sur les boutons de vue
        elements.viewBtns.off('click').on('click', function(e) {
            e.preventDefault();
            
            const newView = $(this).data('view');
            if (newView === CATALOG_CONFIG.view.current) return;
            
            switchView(newView);
        });
        
        console.log('✅ Commutateur de vue initialisé - Vue actuelle:', CATALOG_CONFIG.view.current);
    }

    function switchView(viewType) {
        console.log(`🔄 Changement de vue vers: ${viewType}`);
        
        // Mettre à jour l'état
        CATALOG_CONFIG.view.current = viewType;
        
        // Mettre à jour l'interface des boutons
        elements.viewBtns.removeClass('active');
        elements.viewBtns.filter(`[data-view="${viewType}"]`).addClass('active');
        
        // Animation de transition
        elements.trainersGrid.css({
            opacity: 0.5,
            transform: 'scale(0.98)'
        });
        
        setTimeout(() => {
            // Changer la classe CSS
            elements.trainersGrid
                .removeClass('trpro-view-grid trpro-view-list')
                .addClass(`trpro-view-${viewType}`);
            
            // Animation d'entrée
            elements.trainersGrid.css({
                opacity: 1,
                transform: 'scale(1)'
            });
            
            // Ajuster l'alignement
            setTimeout(adjustCardsAlignment, 100);
            
        }, CATALOG_CONFIG.view.transition / 2);
        
        // Sauvegarder la préférence
        localStorage.setItem('trainer_catalog_view', viewType);
        
        // Analytics
        trackEvent('view_change', { view: viewType });
    }

    // ===== ALIGNEMENT DES CARTES (CORRECTION CRITIQUE) =====
    function adjustCardsAlignment() {
        console.log('📏 Ajustement de l\'alignement des cartes...');
        
        const $cards = $('.trpro-trainer-card-modern');
        
        if (CATALOG_CONFIG.view.current === 'grid') {
            // Mode grille : hauteurs uniformes
            setTimeout(() => {
                // Reset des hauteurs
                $cards.css('height', 'auto');
                
                // Calculer la hauteur maximale par ligne
                const cardWidth = $cards.first().outerWidth();
                const containerWidth = elements.trainersGrid.width();
                const cardsPerRow = Math.floor(containerWidth / cardWidth);
                
                for (let i = 0; i < $cards.length; i += cardsPerRow) {
                    const $rowCards = $cards.slice(i, i + cardsPerRow);
                    
                    let maxHeight = 0;
                    $rowCards.each(function() {
                        const height = $(this).outerHeight();
                        if (height > maxHeight) maxHeight = height;
                    });
                    
                    $rowCards.css('height', maxHeight + 'px');
                }
                
                console.log(`✅ Alignement grille: ${$cards.length} cartes alignées`);
                
            }, 50);
            
        } else {
            // Mode liste : hauteur automatique
            $cards.css('height', 'auto');
            console.log(`✅ Alignement liste: hauteurs automatiques`);
        }
    }

    // ===== SYSTÈME DE FILTRES (CORRECTION EXPERIENCE/DISPONIBILITE) =====
    function initFilters() {
        console.log('🔧 Initialisation des filtres...');
        
        // Gestionnaires d'événements pour tous les filtres
        elements.specialtyFilter.on('change', handleFilterChange);
        elements.regionFilter.on('change', handleFilterChange);
        elements.experienceFilter.on('change', handleFilterChange); // ✅ CORRIGÉ
        elements.availabilityFilter.on('change', handleFilterChange); // ✅ CORRIGÉ
        
        // Soumission du formulaire
        elements.filterForm.on('submit', function(e) {
            e.preventDefault();
            performSearch();
        });
        
        // Reset des filtres
        elements.resetBtn.on('click', resetAllFilters);
        
        // Marquer les filtres actifs visuellement
        updateActiveFilters();
        
        console.log('✅ Filtres initialisés');
    }

    function handleFilterChange() {
        console.log('🔄 Changement de filtre détecté');
        
        // Marquer les filtres actifs
        updateActiveFilters();
        
        // Auto-submit si activé
        if (CATALOG_CONFIG.filters.autoSubmit) {
            clearTimeout(window.filterTimeout);
            window.filterTimeout = setTimeout(() => {
                performSearch();
            }, 300);
        }
    }

    function updateActiveFilters() {
        // Marquer visuellement les filtres actifs
        $('.trpro-filter-group').removeClass('active');
        
        elements.specialtyFilter.closest('.trpro-filter-group').toggleClass('active', !!elements.specialtyFilter.val());
        elements.regionFilter.closest('.trpro-filter-group').toggleClass('active', !!elements.regionFilter.val());
        elements.experienceFilter.closest('.trpro-filter-group').toggleClass('active', !!elements.experienceFilter.val());
        elements.availabilityFilter.closest('.trpro-filter-group').toggleClass('active', !!elements.availabilityFilter.val());
    }

    function resetAllFilters() {
        console.log('🧹 Reset de tous les filtres...');
        
        // Vider tous les champs
        elements.searchInput.val('');
        elements.specialtyFilter.val('');
        elements.regionFilter.val('');
        elements.experienceFilter.val(''); // ✅ CORRIGÉ
        elements.availabilityFilter.val(''); // ✅ CORRIGÉ
        
        if (elements.sortSelect.length) {
            elements.sortSelect.val('date_desc');
        }
        
        // Masquer le bouton clear
        elements.clearBtn.hide();
        
        // Mettre à jour les marqueurs visuels
        updateActiveFilters();
        
        // Animation du bouton
        elements.resetBtn.addClass('trpro-btn-loading');
        setTimeout(() => {
            elements.resetBtn.removeClass('trpro-btn-loading');
        }, 500);
        
        // Recharger sans filtres
        performSearch();
    }

    // ===== SYSTÈME DE RECHERCHE AMÉLIORÉ =====
    function initSearch() {
        console.log('🔧 Initialisation de la recherche...');
        
        let searchTimeout;
        
        // Recherche en temps réel
        elements.searchInput.on('input', function() {
            const query = $(this).val().trim();
            
            // Afficher/masquer le bouton clear
            elements.clearBtn.toggle(query.length > 0);
            
            // Recherche avec debounce
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (query.length >= CATALOG_CONFIG.search.minLength || query.length === 0) {
                    performSearch();
                }
            }, CATALOG_CONFIG.search.delay);
        });
        
        // Bouton clear
        elements.clearBtn.on('click', function() {
            elements.searchInput.val('').trigger('input');
            elements.searchInput.focus();
        });
        
        // Recherche sur Enter
        elements.searchInput.on('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });
        
        console.log('✅ Recherche initialisée');
    }

    // ===== RECHERCHE AJAX OPTIMISÉE =====
    function performSearch() {
        console.log('🔍 Exécution de la recherche...');
        
        // Annuler la requête précédente
        if (window.currentSearchRequest) {
            window.currentSearchRequest.abort();
        }
        
        // Préparer les données
        const searchData = {
            action: 'search_trainers',
            nonce: trainer_ajax?.nonce || '',
            search_term: elements.searchInput.val().trim(),
            specialty_filter: elements.specialtyFilter.val(),
            region_filter: elements.regionFilter.val(),
            experience_filter: elements.experienceFilter.val(), // ✅ CORRIGÉ
            availability_filter: elements.availabilityFilter.val(), // ✅ CORRIGÉ
            sort_order: elements.sortSelect.val() || 'date_desc',
            per_page: CATALOG_CONFIG.pagination.perPage,
            page: CATALOG_CONFIG.pagination.currentPage
        };
        
        console.log('📤 Données de recherche:', searchData);
        
        // Vérifier la configuration AJAX
        if (!trainer_ajax?.ajax_url) {
            console.error('❌ Configuration AJAX manquante');
            showError('Configuration AJAX manquante');
            return;
        }
        
        // Afficher le loading
        showLoading();
        
        // Exécuter la requête
        window.currentSearchRequest = $.ajax({
            url: trainer_ajax.ajax_url,
            type: 'POST',
            data: searchData,
            timeout: 15000,
            beforeSend: function() {
                updateSearchStatus('Recherche en cours...');
            },
            success: function(response) {
                console.log('📥 Réponse reçue:', response);
                handleSearchSuccess(response);
            },
            error: function(xhr, status, error) {
                console.error('❌ Erreur de recherche:', { xhr, status, error });
                handleSearchError(status, error);
            },
            complete: function() {
                hideLoading();
                window.currentSearchRequest = null;
            }
        });
    }

    function handleSearchSuccess(response) {
        if (response.success && response.data) {
            const data = response.data;
            
            // Mettre à jour les informations de pagination
            CATALOG_CONFIG.pagination.currentPage = data.current_page || 1;
            
            if (data.total > 0) {
                displayResults(data);
                updateResultsInfo(data);
                adjustCardsAlignment();
            } else {
                showEmptyState();
            }
            
        } else {
            showEmptyState();
        }
    }

    function handleSearchError(status, error) {
        if (status === 'abort') return;
        
        console.error('Erreur de recherche:', status, error);
        showError('Erreur lors de la recherche. Veuillez réessayer.');
    }

    function displayResults(data) {
        // Si on a du HTML, l'utiliser directement
        if (data.html) {
            elements.trainersGrid.fadeOut(200, function() {
                $(this).html(data.html).fadeIn(300, function() {
                    // Réajuster après affichage
                    setTimeout(adjustCardsAlignment, 100);
                });
            });
        } else if (data.trainers && Array.isArray(data.trainers)) {
            // Générer le HTML à partir des données
            const html = data.trainers.map(generateTrainerCard).join('');
            elements.trainersGrid.fadeOut(200, function() {
                $(this).html(html).fadeIn(300, function() {
                    setTimeout(adjustCardsAlignment, 100);
                });
            });
        }
        
        // Afficher les résultats
        elements.trainersGrid.show();
        elements.emptyState.hide();
        elements.resultsHeader.show();
    }

    // ===== GESTION DES ÉTATS =====
    function showLoading() {
        elements.loading.fadeIn(200);
        elements.trainersGrid.css('opacity', 0.5);
        elements.emptyState.hide();
    }

    function hideLoading() {
        elements.loading.fadeOut(200);
        elements.trainersGrid.css('opacity', 1);
    }

    function showEmptyState() {
        elements.trainersGrid.hide();
        elements.emptyState.fadeIn(300);
        updateSearchStatus('Aucun résultat');
    }

    function showError(message) {
        elements.trainersGrid.html(`
            <div class="trpro-search-error">
                <div class="trpro-error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3>Erreur</h3>
                <p>${message}</p>
                <button class="trpro-btn trpro-btn-primary" onclick="location.reload();">
                    <i class="fas fa-redo"></i>
                    Réessayer
                </button>
            </div>
        `).show();
        
        elements.emptyState.hide();
    }

    function updateResultsInfo(data) {
        const count = data.total || 0;
        
        // Mettre à jour les compteurs
        elements.countNumber.text(count);
        
        // Mettre à jour le statut
        let status = count > 1 ? 'disponibles' : 'disponible';
        if (hasActiveFilters()) {
            status = count > 1 ? 'trouvés' : 'trouvé';
        }
        elements.filterStatus.text(status);
    }

    function updateSearchStatus(message) {
        console.log('📊 Statut:', message);
    }

    function hasActiveFilters() {
        return !!(
            elements.searchInput.val().trim() ||
            elements.specialtyFilter.val() ||
            elements.regionFilter.val() ||
            elements.experienceFilter.val() ||
            elements.availabilityFilter.val()
        );
    }

    // ===== TAGS POPULAIRES =====
    function initPopularTags() {
        elements.popularTags.off('click').on('click', function(e) {
            e.preventDefault();
            
            const searchTerm = $(this).data('search');
            const category = $(this).data('category');
            const region = $(this).data('region');
            
            // Remplir les champs appropriés
            if (searchTerm) {
                elements.searchInput.val(searchTerm);
            }
            
            if (category) {
                elements.specialtyFilter.val(category);
            }
            
            if (region) {
                elements.regionFilter.val(region);
            }
            
            // Animation du tag
            $(this).addClass('trpro-tag-active');
            setTimeout(() => {
                $(this).removeClass('trpro-tag-active');
            }, 300);
            
            // Mettre à jour les filtres actifs
            updateActiveFilters();
            
            // Effectuer la recherche
            performSearch();
        });
    }

    // ===== TRI =====
    function initSort() {
        if (elements.sortSelect.length) {
            elements.sortSelect.on('change', function() {
                performSearch();
            });
        }
    }

    // ===== PAGINATION =====
    function initPagination() {
        // Délégation d'événements pour les liens de pagination dynamiques
        $(document).on('click', '.trpro-page-btn', function(e) {
            e.preventDefault();
            
            const url = $(this).attr('href');
            if (!url) return;
            
            // Extraire le numéro de page
            const urlParams = new URLSearchParams(url.split('?')[1] || '');
            const page = parseInt(urlParams.get('paged')) || 1;
            
            changePage(page);
        });
    }

    function changePage(page) {
        if (page === CATALOG_CONFIG.pagination.currentPage) return;
        
        CATALOG_CONFIG.pagination.currentPage = page;
        
        // Scroll vers le haut
        $('html, body').animate({
            scrollTop: elements.resultsHeader.offset().top - 100
        }, 400);
        
        // Effectuer la recherche
        performSearch();
    }

    // ===== INTERACTIONS DES CARTES =====
    function initCardInteractions() {
        // Gestionnaire pour les boutons "Voir le profil"
        $(document).on('click', '.trpro-btn-details', function(e) {
            e.preventDefault();
            const trainerId = $(this).data('trainer-id');
            if (trainerId) {
                openTrainerProfile(trainerId);
            }
        });
        
        // Gestionnaire pour les modals
        $(document).on('click', '.trpro-modal-close, .trpro-modal-overlay', function(e) {
            if (e.target === this || $(e.target).hasClass('trpro-modal-close')) {
                $('.trpro-modal-overlay').fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });
    }

    function openTrainerProfile(trainerId) {
        // Cette fonction serait implémentée selon vos besoins
        console.log('Ouverture du profil du formateur:', trainerId);
    }

    // ===== ÉTAT INITIAL =====
    function loadInitialState() {
        // Charger la vue préférée depuis localStorage
        const savedView = localStorage.getItem('trainer_catalog_view');
        if (savedView && savedView !== CATALOG_CONFIG.view.current) {
            switchView(savedView);
        }
        
        // Charger les filtres depuis l'URL si nécessaire
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.toString()) {
            console.log('📥 Chargement des filtres depuis l\'URL');
            // Ici vous pourriez charger les filtres depuis l'URL
        }
    }

    // ===== GÉNÉRATION HTML =====
    function generateTrainerCard(trainer) {
        // Template basique - à adapter selon vos besoins
        const trainerId = String(trainer.id).padStart(4, '0');
        const displayName = trainer.display_name || 'Formateur Expert';
        
        return `
            <article class="trpro-trainer-card-modern" data-trainer-id="${trainer.id}">
                <div class="trpro-card-header">
                    <div class="trpro-trainer-avatar">
                        ${trainer.photo_url ? 
                            `<img src="${trainer.photo_url}" alt="Photo formateur" loading="lazy">` :
                            `<div class="trpro-avatar-placeholder"><i class="fas fa-user-graduate"></i></div>`
                        }
                    </div>
                </div>
                <div class="trpro-card-body">
                    <h3 class="trpro-trainer-title">${escapeHtml(displayName)} <span class="trpro-trainer-id">#${trainerId}</span></h3>
                    ${trainer.company ? `<div class="trpro-trainer-company">${escapeHtml(trainer.company)}</div>` : ''}
                </div>
                <div class="trpro-card-footer">
                    <div class="trpro-action-buttons">
                        <a href="mailto:${trainer_ajax?.contact_email || ''}?subject=Contact formateur %23${trainerId}" class="trpro-btn trpro-btn-primary">
                            <i class="fas fa-envelope"></i> Contacter
                        </a>
                        <button class="trpro-btn trpro-btn-outline trpro-btn-details" data-trainer-id="${trainer.id}">
                            <i class="fas fa-user"></i> Profil
                        </button>
                    </div>
                </div>
            </article>
        `;
    }

    // ===== UTILITAIRES =====
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

    function trackEvent(action, data = {}) {
        if (typeof gtag !== 'undefined') {
            gtag('event', action, {
                event_category: 'trainer_catalog',
                ...data
            });
        }
        console.log('📊 Event:', action, data);
    }

    // ===== REDIMENSIONNEMENT DE FENÊTRE =====
    $(window).on('resize', debounce(function() {
        adjustCardsAlignment();
    }, 250));

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

    // ===== CSS DYNAMIQUE =====
    $('<style>').html(`
        .trpro-btn-loading {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .trpro-tag-active {
            transform: scale(0.95) !important;
            background: var(--trpro-accent) !important;
            color: var(--trpro-primary) !important;
        }
        
        .trpro-search-error {
            text-align: center;
            padding: 60px 20px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            color: #991b1b;
        }
        
        .trpro-error-icon {
            font-size: 3rem;
            color: #dc2626;
            margin-bottom: 20px;
        }
    `).appendTo('head');

    // ===== EXPOSITION GLOBALE (DEBUG) =====
    window.TrainerCatalog = {
        config: CATALOG_CONFIG,
        elements: elements,
        switchView: switchView,
        performSearch: performSearch,
        resetAllFilters: resetAllFilters,
        adjustCardsAlignment: adjustCardsAlignment
    };

    console.log('🛠️ Debug disponible: window.TrainerCatalog');

})(jQuery);