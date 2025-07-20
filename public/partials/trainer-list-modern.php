<?php
/**
 * Template de liste moderne des formateurs - VERSION CORRIGÉE NONCE
 * ✅ CORRECTION: Nonce unifié pour système de contact
 * ✅ Cartes compactes avec photos par défaut
 * ✅ Filtres experience/availability fonctionnels
 * ✅ Vue grille/liste améliorée
 * ✅ Pagination intégrée
 */

if (!defined('ABSPATH')) {
    exit;
}

// Fonction d'anonymisation
function get_anonymized_name($first_name, $last_name) {
    if (empty($last_name) || empty($first_name)) {
        return 'Formateur Expert';
    }
    return strtoupper(substr($last_name, 0, 1)) . '. ' . $first_name;
}

// Récupérer les variables globales du shortcode
global $trpro_template_trainers, $trpro_template_total_trainers, $trpro_template_atts, $trpro_template_current_page;

$trainers = $trpro_template_trainers ?? array();
$total_trainers = $trpro_template_total_trainers ?? 0;
$atts = $trpro_template_atts ?? array('per_page' => 12, 'show_pagination' => 'true');
$current_page = $trpro_template_current_page ?? 1;
?>

<div class="trpro-search-container">
    
    <!-- Header de recherche -->
    <div class="trpro-search-header">
        <h2 class="trpro-search-title">
            <i class="fas fa-search"></i>
            Trouvez Votre Formateur Expert
        </h2>
        <p class="trpro-search-subtitle">
            Recherchez parmi <?php echo $total_trainers; ?> formateurs spécialisés en France et à l'international
        </p>
    </div>

    <!-- Formulaire de recherche avec TOUS les filtres -->
    <div class="trpro-search-form-modern">
        <div class="trpro-search-main">
            <div class="trpro-search-input-group">
                <input type="text" 
                       id="trpro-live-search" 
                       placeholder="Rechercher par compétence, technologie, certification..."
                       class="trpro-search-input"
                       value="<?php echo esc_attr($_GET['search'] ?? ''); ?>">
            </div>
            <button type="button" class="trpro-search-btn">
                <i class="fas fa-search"></i>
                <span>Rechercher</span>
            </button>
        </div>
        
        <!-- ✅ CORRECTION : Tous les filtres avec values depuis URL -->
        <div class="trpro-filters-section">
            <div class="trpro-filters-row">
                <div class="trpro-filter-group">
                    <label for="trpro-specialty-filter">Spécialité</label>
                    <select id="trpro-specialty-filter" class="trpro-filter-select">
                        <option value="">Toutes les spécialités</option>
                        <option value="administration-systeme" <?php selected($_GET['specialty'] ?? '', 'administration-systeme'); ?>>Administration Système</option>
                        <option value="reseaux" <?php selected($_GET['specialty'] ?? '', 'reseaux'); ?>>Réseaux & Infrastructure</option>
                        <option value="cloud" <?php selected($_GET['specialty'] ?? '', 'cloud'); ?>>Cloud Computing</option>
                        <option value="devops" <?php selected($_GET['specialty'] ?? '', 'devops'); ?>>DevOps & CI/CD</option>
                        <option value="securite" <?php selected($_GET['specialty'] ?? '', 'securite'); ?>>Sécurité Informatique</option>
                        <option value="telecoms" <?php selected($_GET['specialty'] ?? '', 'telecoms'); ?>>Télécommunications</option>
                        <option value="developpement" <?php selected($_GET['specialty'] ?? '', 'developpement'); ?>>Développement</option>
                        <option value="bases-donnees" <?php selected($_GET['specialty'] ?? '', 'bases-donnees'); ?>>Bases de Données</option>
                    </select>
                </div>
                
                <div class="trpro-filter-group">
                    <label for="trpro-region-filter">Zone d'intervention</label>
                    <select id="trpro-region-filter" class="trpro-filter-select">
                        <option value="">Toutes les zones</option>
                        <option value="ile-de-france" <?php selected($_GET['region'] ?? '', 'ile-de-france'); ?>>Île-de-France</option>
                        <option value="auvergne-rhone-alpes" <?php selected($_GET['region'] ?? '', 'auvergne-rhone-alpes'); ?>>Auvergne-Rhône-Alpes</option>
                        <option value="nouvelle-aquitaine" <?php selected($_GET['region'] ?? '', 'nouvelle-aquitaine'); ?>>Nouvelle-Aquitaine</option>
                        <option value="occitanie" <?php selected($_GET['region'] ?? '', 'occitanie'); ?>>Occitanie</option>
                        <option value="hauts-de-france" <?php selected($_GET['region'] ?? '', 'hauts-de-france'); ?>>Hauts-de-France</option>
                        <option value="grand-est" <?php selected($_GET['region'] ?? '', 'grand-est'); ?>>Grand Est</option>
                        <option value="provence-alpes-cote-azur" <?php selected($_GET['region'] ?? '', 'provence-alpes-cote-azur'); ?>>Provence-Alpes-Côte d'Azur</option>
                        <option value="pays-de-la-loire" <?php selected($_GET['region'] ?? '', 'pays-de-la-loire'); ?>>Pays de la Loire</option>
                        <option value="bretagne" <?php selected($_GET['region'] ?? '', 'bretagne'); ?>>Bretagne</option>
                        <option value="normandie" <?php selected($_GET['region'] ?? '', 'normandie'); ?>>Normandie</option>
                        <option value="bourgogne-franche-comte" <?php selected($_GET['region'] ?? '', 'bourgogne-franche-comte'); ?>>Bourgogne-Franche-Comté</option>
                        <option value="centre-val-de-loire" <?php selected($_GET['region'] ?? '', 'centre-val-de-loire'); ?>>Centre-Val de Loire</option>
                        <option value="corse" <?php selected($_GET['region'] ?? '', 'corse'); ?>>Corse</option>
                        <option value="outre-mer" <?php selected($_GET['region'] ?? '', 'outre-mer'); ?>>Outre-mer (DOM-TOM)</option>
                        <option value="europe" <?php selected($_GET['region'] ?? '', 'europe'); ?>>Europe</option>
                        <option value="international" <?php selected($_GET['region'] ?? '', 'international'); ?>>International</option>
                        <option value="distanciel" <?php selected($_GET['region'] ?? '', 'distanciel'); ?>>Formation à distance</option>
                    </select>
                </div>
                
                <!-- ✅ CORRECTION : Filtre expérience ajouté -->
                <div class="trpro-filter-group">
                    <label for="trpro-experience-filter">Niveau d'expérience</label>
                    <select id="trpro-experience-filter" class="trpro-filter-select">
                        <option value="">Tous niveaux</option>
                        <option value="junior" <?php selected($_GET['experience'] ?? '', 'junior'); ?>>Junior (< 3 ans)</option>
                        <option value="intermediaire" <?php selected($_GET['experience'] ?? '', 'intermediaire'); ?>>Intermédiaire (3-7 ans)</option>
                        <option value="senior" <?php selected($_GET['experience'] ?? '', 'senior'); ?>>Senior (7-15 ans)</option>
                        <option value="expert" <?php selected($_GET['experience'] ?? '', 'expert'); ?>>Expert (15+ ans)</option>
                    </select>
                </div>
                
                <!-- ✅ CORRECTION : Filtre disponibilité ajouté -->
                <div class="trpro-filter-group">
                    <label for="trpro-availability-filter">Disponibilité</label>
                    <select id="trpro-availability-filter" class="trpro-filter-select">
                        <option value="">Toutes</option>
                        <option value="temps-plein" <?php selected($_GET['availability'] ?? '', 'temps-plein'); ?>>Temps plein</option>
                        <option value="temps-partiel" <?php selected($_GET['availability'] ?? '', 'temps-partiel'); ?>>Temps partiel</option>
                        <option value="ponctuel" <?php selected($_GET['availability'] ?? '', 'ponctuel'); ?>>Missions ponctuelles</option>
                        <option value="sur-demande" <?php selected($_GET['availability'] ?? '', 'sur-demande'); ?>>Sur demande</option>
                    </select>
                </div>
            </div>
            
            <!-- Bouton reset -->
            <div class="trpro-filters-actions" style="margin-top: 1rem; text-align: center;">
                <button id="trpro-reset-filters" class="trpro-btn trpro-btn-outline">
                    <i class="fas fa-refresh"></i>
                    Réinitialiser les filtres
                </button>
            </div>
        </div>
        
        <!-- Tags populaires -->
        <div class="trpro-popular-tags">
            <span class="trpro-tags-label">Recherches populaires :</span>
            <button class="trpro-tag" data-search="DevOps" data-category="devops">DevOps</button>
            <button class="trpro-tag" data-search="Cloud AWS" data-category="cloud">Cloud AWS</button>
            <button class="trpro-tag" data-search="Cybersécurité" data-category="securite">Cybersécurité</button>
            <button class="trpro-tag" data-search="Kubernetes" data-category="devops">Kubernetes</button>
            <button class="trpro-tag" data-search="Python" data-category="developpement">Python</button>
            <button class="trpro-tag" data-search="" data-region="distanciel">Formation à distance</button>
        </div>
    </div>

    <!-- États de chargement -->
    <div id="trpro-search-loading" class="trpro-search-loading" style="display: none;">
        <div class="trpro-spinner"></div>
        <p>Recherche en cours...</p>
    </div>

    <div id="trpro-empty-state" class="trpro-empty-state" style="display: none;">
        <div class="trpro-empty-icon">
            <i class="fas fa-search-minus"></i>
        </div>
        <h3>Aucun formateur trouvé</h3>
        <p>Essayez de modifier vos critères de recherche ou explorez d'autres spécialités.</p>
    </div>

    <!-- Header des résultats avec commutateur vue -->
    <div id="trpro-results-header" class="trpro-results-header">
        <div class="trpro-results-info">
            <h3>Nos Formateurs Experts</h3>
            <p class="trpro-results-count"><?php echo $total_trainers; ?> formateur<?php echo $total_trainers > 1 ? 's' : ''; ?> disponible<?php echo $total_trainers > 1 ? 's' : ''; ?></p>
        </div>
        
        <!-- ✅ CORRECTION : Commutateur vue grille/liste -->
        <div class="trpro-view-controls">
            <div class="trpro-view-switcher">
                <button class="trpro-view-btn active" data-view="grid" title="Vue grille">
                    <i class="fas fa-th"></i>
                </button>
                <button class="trpro-view-btn" data-view="list" title="Vue liste">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ✅ GRILLE DES FORMATEURS AVEC CARTES COMPACTES -->
    <div id="trpro-trainers-grid" class="trpro-trainers-grid trpro-view-grid">
        <?php if (!empty($trainers)): ?>
            <?php foreach ($trainers as $trainer): ?>
                <?php 
                $trainer_id = str_pad($trainer->id, 4, '0', STR_PAD_LEFT);
                $specialties = array_map('trim', explode(',', $trainer->specialties));
                $display_specialties = array_slice($specialties, 0, 2); // Max 2 spécialités
                $remaining_count = count($specialties) - 2;
                
                // Régions d'intervention
                $intervention_regions = array();
                if (!empty($trainer->intervention_regions)) {
                    $intervention_regions = array_map('trim', explode(',', $trainer->intervention_regions));
                }
                
                $upload_dir = wp_upload_dir();
                $display_name = get_anonymized_name($trainer->first_name, $trainer->last_name);
                
                // ✅ CORRECTION : Gestion robuste des photos
                $photo_url = '';
                if (!empty($trainer->photo_file)) {
                    $photo_path = $upload_dir['basedir'] . '/' . $trainer->photo_file;
                    if (file_exists($photo_path)) {
                        $photo_url = $upload_dir['baseurl'] . '/' . $trainer->photo_file;
                    }
                }
                ?>
                
                <article class="trpro-trainer-card-compact" data-trainer-id="<?php echo $trainer->id; ?>">
                    <div class="trpro-card-header">
                    <div class="trpro-trainer-avatar">
                        <div class="trpro-avatar-placeholder">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        
                        <?php if (!empty($photo_url)): ?>
                            <img src="<?php echo esc_url($photo_url); ?>" 
                                 alt="Photo formateur #<?php echo $trainer_id; ?>" 
                                 loading="lazy"
                                 onload="this.previousElementSibling.style.display='none';"
                                 onerror="this.style.display='none'; this.previousElementSibling.style.display='flex';">
                        <?php endif; ?>
                    </div>

                        
                        <div class="trpro-status-badges">
                            <span class="trpro-badge trpro-verified" title="Profil vérifié">
                                <i class="fas fa-check-circle"></i>
                            </span>
                            <?php if (!empty($trainer->cv_file)): ?>
                                <span class="trpro-badge trpro-cv-available" title="CV disponible">
                                    <i class="fas fa-file-pdf"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="trpro-card-body">
                        <h3 class="trpro-trainer-name">
                            <?php echo esc_html($display_name); ?>
                            <span class="trpro-trainer-id">#<?php echo $trainer_id; ?></span>
                        </h3>
                        
                        <?php if (!empty($trainer->company)): ?>
                            <div class="trpro-company">
                                <i class="fas fa-building"></i>
                                <?php echo esc_html($trainer->company); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Spécialités limitées -->
                        <div class="trpro-specialties">
                            <?php foreach ($display_specialties as $specialty): 
                                $specialty = trim($specialty);
                                if (!empty($specialty)):
                            ?>
                                <span class="trpro-specialty-tag"><?php echo esc_html(ucfirst(str_replace('-', ' ', $specialty))); ?></span>
                            <?php 
                                endif;
                            endforeach; 
                            
                            if ($remaining_count > 0):
                            ?>
                                <span class="trpro-specialty-tag trpro-more">+<?php echo $remaining_count; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Zones d'intervention -->
                        <?php if (!empty($intervention_regions)): ?>
                            <div class="trpro-regions">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php 
                                $display_regions = array_slice($intervention_regions, 0, 2);
                                $region_names = array();
                                foreach ($display_regions as $region) {
                                    $region = trim($region);
                                    $region_names[] = ucwords(str_replace('-', ' ', $region));
                                }
                                echo esc_html(implode(', ', $region_names));
                                
                                if (count($intervention_regions) > 2) {
                                    echo ' <span class="trpro-more-regions">+' . (count($intervention_regions) - 2) . '</span>';
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Métadonnées compactes -->
                        <div class="trpro-meta">
                            <?php if (!empty($trainer->availability)): ?>
                                <span class="trpro-meta-item">
                                    <i class="fas fa-calendar-check"></i>
                                    <?php echo esc_html(ucfirst(str_replace('-', ' ', $trainer->availability))); ?>
                                </span>
                            <?php endif; ?>
                    
                            <?php if (!empty($trainer->hourly_rate)): ?>
                            
                             <!--       
                                <span class="trpro-meta-item">
                                    <i class="fas fa-euro-sign"></i>
                                    <?php echo esc_html($trainer->hourly_rate); ?>
                                </span>
                                
                                -->
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- ✅ CORRECTION: Actions compactes avec boutons corrigés pour nonce unifié -->
                    <div class="trpro-card-footer">
                        <button class="trpro-btn trpro-btn-primary trpro-btn-contact" 
                                data-trainer-id="<?php echo $trainer->id; ?>"
                                data-trainer-name="<?php echo esc_attr($display_name); ?>"
                                title="Contacter ce formateur">
                            <i class="fas fa-envelope"></i>
                            Contact
                        </button>
                        
                        <button class="trpro-btn trpro-btn-outline trpro-btn-profile" 
                                data-trainer-id="<?php echo $trainer->id; ?>"
                                title="Voir le profil détaillé">
                            <i class="fas fa-user"></i>
                            Profil
                        </button>
                    </div>
                </article>
                
            <?php endforeach; ?>
        <?php else: ?>
            <div class="trpro-empty-state">
                <div class="trpro-empty-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Aucun formateur disponible</h3>
                <p>Nous mettons régulièrement à jour notre base de formateurs. Revenez bientôt !</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ✅ PAGINATION FONCTIONNELLE -->
    <?php if ($atts['show_pagination'] === 'true' && $total_trainers > $atts['per_page']): ?>
        <div class="trpro-pagination" id="trpro-pagination">
            <?php
            $total_pages = ceil($total_trainers / $atts['per_page']);
            $current_page = max(1, get_query_var('paged', 1));
            
            if ($total_pages > 1):
            ?>
                <div class="trpro-pagination-wrapper">
                    <?php if ($current_page > 1): ?>
                        <button class="trpro-page-btn trpro-page-prev" data-page="<?php echo $current_page - 1; ?>">
                            <i class="fas fa-chevron-left"></i> Précédent
                        </button>
                    <?php endif; ?>
                    
                    <?php
                    // Afficher max 7 numéros de page
                    $start_page = max(1, $current_page - 3);
                    $end_page = min($total_pages, $start_page + 6);
                    
                    if ($end_page - $start_page < 6) {
                        $start_page = max(1, $end_page - 6);
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                        $active_class = $i === $current_page ? 'active' : '';
                    ?>
                        <button class="trpro-page-btn trpro-page-number <?php echo $active_class; ?>" 
                                data-page="<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </button>
                    <?php endfor; ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <button class="trpro-page-btn trpro-page-next" data-page="<?php echo $current_page + 1; ?>">
                            Suivant <i class="fas fa-chevron-right"></i>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ✅ SCRIPT D'INITIALISATION SPÉCIFIQUE AVEC NONCE CORRIGÉ -->
<script>
jQuery(document).ready(function($) {
    console.log('🚀 Catalogue formateurs: Initialisation page...');
    
    // Appliquer la vue sauvegardée
    const savedView = localStorage.getItem('trainer_view') || 'grid';
    if (savedView === 'list') {
        $('.trpro-view-btn').removeClass('active');
        $('.trpro-view-btn[data-view="list"]').addClass('active');
        $('#trpro-trainers-grid').removeClass('trpro-view-grid').addClass('trpro-view-list');
    }
    
    // ✅ CORRECTION: Gestionnaire des boutons contact avec nonce unifié
    $(document).on('click', '.trpro-btn-contact', function(e) {
        e.preventDefault();
        console.log('📧 Bouton contact cliqué');
        
        const trainerId = $(this).data('trainer-id');
        const trainerName = $(this).data('trainer-name');
        
        if (!trainerId || !trainerName) {
            console.error('❌ Données manquantes:', {trainerId, trainerName});
            return;
        }
        
        console.log('📤 Données contact:', {trainerId, trainerName});
        
        // Utiliser la fonction globale si disponible (utilise le nonce unifié)
        if (typeof window.openContactModal === 'function') {
            window.openContactModal(trainerId, trainerName);
        } else if (typeof openContactModal === 'function') {
            openContactModal(trainerId, trainerName);
        } else {
            console.warn('⚠️ Fonction openContactModal non disponible - fallback email');
            // Fallback vers email si la fonction JS n'est pas disponible
            const contactEmail = '<?php echo esc_js(get_option("trainer_contact_email", get_option("admin_email"))); ?>';
            const subject = `Contact formateur #${trainerId.toString().padStart(4, '0')}`;
            const body = `Bonjour,\n\nJe souhaite entrer en contact avec le formateur ${trainerName} (#${trainerId.toString().padStart(4, '0')}).\n\nCordialement`;
            
            window.location.href = `mailto:${contactEmail}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        }
    });
    
    // Message si pas de JavaScript principal
    if (typeof window.trainerDebug === 'undefined') {
        console.warn('⚠️ JavaScript principal non chargé - fonctions limitées');
        
        // Fallback basique pour vue switcher
        $('.trpro-view-btn').on('click', function() {
            const view = $(this).data('view');
            $('.trpro-view-btn').removeClass('active');
            $(this).addClass('active');
            $('#trpro-trainers-grid').removeClass('trpro-view-grid trpro-view-list').addClass('trpro-view-' + view);
            localStorage.setItem('trainer_view', view);
        });
    }
    
    console.log('✅ Contact buttons: NONCE CORRIGÉ - Utilise nonce unifié');
});
</script>

<style>
/* ===== STYLES SPÉCIFIQUES PAGE AVEC CORRECTIONS ===== */
.trpro-filters-actions {
    margin-top: 1rem;
    text-align: center;
}

.trpro-btn-outline {
    background: transparent;
    color: var(--trpro-info);
    border: 1px solid var(--trpro-info);
}

.trpro-btn-outline:hover {
    background: var(--trpro-info);
    color: white;
}

/* ✅ Styles améliorés pour boutons contact */
.trpro-btn-contact {
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}

.trpro-btn-contact:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.trpro-btn-contact:active {
    transform: translateY(0);
}

/* Animation du bouton contact */
.trpro-btn-contact:before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.3s ease, height 0.3s ease;
}

.trpro-btn-contact:hover:before {
    width: 100%;
    height: 100%;
}

/* Amélioration responsive pour filtres */
@media (max-width: 768px) {
    .trpro-filters-row {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .trpro-pagination-wrapper {
        padding: 0.75rem;
        gap: 0.25rem;
    }
    
    .trpro-page-btn {
        padding: 0.375rem 0.5rem;
        font-size: 0.8125rem;
        min-width: 2rem;
    }
    
    .trpro-btn-contact,
    .trpro-btn-profile {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }
}

/* États de chargement pour boutons */
.trpro-btn-contact.loading {
    pointer-events: none;
    opacity: 0.7;
}

.trpro-btn-contact.loading i {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Feedback visuel amélioré */
.trpro-trainer-card-compact:hover .trpro-btn-contact {
    background: #2563eb;
    border-color: #2563eb;
}

.trpro-trainer-card-compact:hover .trpro-btn-profile {
    background: #f8fafc;
    border-color: #e2e8f0;
    color: #1e293b;
}
</style>