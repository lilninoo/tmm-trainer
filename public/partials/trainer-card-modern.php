<?php
/**
 * Template carte formateur moderne avec nom anonymisé et régions - VERSION CORRIGÉE NONCE
 * 
 * Fichier: public/partials/trainer-card-modern.php
 * Variable disponible: $trainer (objet avec les données du formateur)
 * ✅ CORRECTION: Nonce unifié pour système de contact
 */

if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les paramètres de contact
$contact_email = get_option('trainer_contact_email', get_option('admin_email'));
$contact_phone = get_option('trainer_contact_phone', '');

// Formater l'ID
$trainer_id_formatted = str_pad($trainer->id, 4, '0', STR_PAD_LEFT);

// ✅ ANONYMISATION DU NOM : Première lettre du nom + point + prénom complet
$display_name = '';
if (!empty($trainer->last_name) && !empty($trainer->first_name)) {
    $display_name = strtoupper(substr($trainer->last_name, 0, 1)) . '. ' . $trainer->first_name;
} else {
    $display_name = 'Formateur Expert';
}

// Parser les spécialités
$specialties = array_map('trim', explode(',', $trainer->specialties));
$main_specialties = array_slice($specialties, 0, 3);
$remaining_count = count($specialties) - 3;

// ✅ Parser les régions d'intervention si disponibles
$intervention_regions = array();
if (!empty($trainer->intervention_regions)) {
    $intervention_regions = array_map('trim', explode(',', $trainer->intervention_regions));
}

// Mapping des régions avec labels français
$region_labels = array(
    'auvergne-rhone-alpes' => 'Auvergne-Rhône-Alpes',
    'bourgogne-franche-comte' => 'Bourgogne-Franche-Comté',
    'bretagne' => 'Bretagne',
    'centre-val-de-loire' => 'Centre-Val de Loire',
    'corse' => 'Corse',
    'grand-est' => 'Grand Est',
    'hauts-de-france' => 'Hauts-de-France',
    'ile-de-france' => 'Île-de-France',
    'normandie' => 'Normandie',
    'nouvelle-aquitaine' => 'Nouvelle-Aquitaine',
    'occitanie' => 'Occitanie',
    'pays-de-la-loire' => 'Pays de la Loire',
    'provence-alpes-cote-azur' => 'Provence-Alpes-Côte d\'Azur',
    'outre-mer' => 'DOM-TOM',
    'europe' => 'Europe',
    'international' => 'International',
    'distanciel' => 'Distanciel'
);

// Calcul de l'ancienneté
$registration_date = new DateTime($trainer->created_at);
$now = new DateTime();
$interval = $registration_date->diff($now);

// Badge de statut
$status_badge = '';
$status_class = '';
if ($interval->days > 365) {
    $status_badge = 'Expert';
    $status_class = 'expert';
} elseif ($interval->days > 90) {
    $status_badge = 'Confirmé';
    $status_class = 'confirmed';
} else {
    $status_badge = 'Nouveau';
    $status_class = 'new';
}

// Mapping des icônes par spécialité
$specialty_icons = [
    'administration-systeme' => 'fas fa-server',
    'reseaux' => 'fas fa-network-wired', 
    'cloud' => 'fab fa-aws',
    'devops' => 'fas fa-infinity',
    'securite' => 'fas fa-shield-alt',
    'telecoms' => 'fas fa-satellite-dish',
    'developpement' => 'fas fa-code',
    'bases-donnees' => 'fas fa-database'
];
?>

<article class="trpro-trainer-card-modern" 
         itemscope 
         itemtype="https://schema.org/Person"
         data-trainer-id="<?php echo esc_attr($trainer->id); ?>"
         data-specialties="<?php echo esc_attr($trainer->specialties); ?>"
         data-regions="<?php echo esc_attr($trainer->intervention_regions ?? ''); ?>">
    
    <!-- Card Header avec photo et badge -->
    <div class="trpro-card-header">
        <div class="trpro-trainer-avatar">
            <div class="trpro-trainer-avatar">
                <div class="trpro-avatar-placeholder" style="display:flex;">
                    <i class="fas fa-user-graduate"></i>
                </div>
            
                <?php if (!empty($trainer->photo_file)): ?>
                    <?php 
                    $upload_dir = wp_upload_dir();
                    $photo_url = $upload_dir['baseurl'] . '/' . $trainer->photo_file;
                    ?>
                    <img src="<?php echo esc_url($photo_url); ?>" 
                         alt="Photo du formateur #<?php echo $trainer_id_formatted; ?>" 
                         loading="lazy"
                         itemprop="image"
                         onload="this.previousElementSibling.style.display='none';"
                         onerror="this.style.display='none'; this.previousElementSibling.style.display='flex';">
                <?php endif; ?>
            </div>
            
                        
            <!-- Badge de statut -->
            <div class="trpro-status-badge trpro-badge-<?php echo $status_class; ?>">
                <span><?php echo $status_badge; ?></span>
            </div>
        </div>
        
        <!-- Badges de vérification -->
        <div class="trpro-verification-badges">
            <div class="trpro-badge trpro-verified" title="Profil vérifié">
                <i class="fas fa-check-circle"></i>
            </div>
            <?php if (!empty($trainer->cv_file)): ?>
                <div class="trpro-badge trpro-cv-badge" title="CV disponible">
                    <i class="fas fa-file-pdf"></i>
                </div>
            <?php endif; ?>
            <?php if (!empty($intervention_regions)): ?>
                <div class="trpro-badge trpro-location-badge" title="<?php echo count($intervention_regions); ?> zone(s) d'intervention">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Card Body -->
    <div class="trpro-card-body">
        <!-- ✅ Informations principales avec nom anonymisé -->
        <div class="trpro-trainer-identity">
            <h3 class="trpro-trainer-name" itemprop="name">
                <?php echo esc_html($display_name); ?>
            </h3>
            <div class="trpro-trainer-id">Formateur Expert #<?php echo $trainer_id_formatted; ?></div>
            
            <?php if (!empty($trainer->company)): ?>
                <div class="trpro-trainer-company" itemprop="worksFor">
                    <i class="fas fa-building"></i>
                    <span><?php echo esc_html($trainer->company); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- ✅ Zones d'intervention -->
        <?php if (!empty($intervention_regions)): ?>
            <div class="trpro-intervention-zones">
                <div class="trpro-zones-header">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Zones d'intervention</span>
                </div>
                <div class="trpro-zones-list">
                    <?php 
                    $displayed_regions = array_slice($intervention_regions, 0, 2);
                    $remaining_regions = count($intervention_regions) - 2;
                    
                    foreach ($displayed_regions as $region): 
                        $region_label = $region_labels[$region] ?? ucfirst(str_replace('-', ' ', $region));
                    ?>
                        <span class="trpro-zone-tag"><?php echo esc_html($region_label); ?></span>
                    <?php endforeach; ?>
                    
                    <?php if ($remaining_regions > 0): ?>
                        <span class="trpro-zone-tag trpro-zone-more">+<?php echo $remaining_regions; ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Spécialités avec icônes -->
        <div class="trpro-specialties-section" itemprop="knowsAbout">
            <div class="trpro-specialties-grid">
                <?php foreach ($main_specialties as $specialty): 
                    $specialty = trim($specialty);
                    if (!empty($specialty)):
                        $icon = $specialty_icons[$specialty] ?? 'fas fa-cog';
                        $label = ucfirst(str_replace('-', ' ', $specialty));
                ?>
                    <div class="trpro-specialty-item">
                        <i class="<?php echo esc_attr($icon); ?>"></i>
                        <span><?php echo esc_html($label); ?></span>
                    </div>
                <?php 
                    endif;
                endforeach; 
                
                if ($remaining_count > 0):
                ?>
                    <div class="trpro-specialty-item trpro-specialty-more">
                        <i class="fas fa-plus"></i>
                        <span>+<?php echo $remaining_count; ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Extrait d'expérience -->
        <?php if (!empty($trainer->experience)): ?>
            <div class="trpro-experience-preview" itemprop="description">
                <div class="trpro-experience-text">
                    <?php echo esc_html(wp_trim_words($trainer->experience, 25, '...')); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Métadonnées -->
        <div class="trpro-trainer-meta">
            <?php if (!empty($trainer->availability)): ?>
                <div class="trpro-meta-item">
                    <i class="fas fa-calendar-check"></i>
                    <span><?php echo esc_html(ucfirst(str_replace('-', ' ', $trainer->availability))); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($trainer->hourly_rate)): ?>
                <span class="trpro-meta-item">
                    <i class="fas fa-euro-sign"></i>
                    <span class="trpro-hourly-value"><?php echo esc_html($trainer->hourly_rate); ?></span>
                </span>
            <?php endif; ?>

            
            <div class="trpro-meta-item">
                <i class="fas fa-calendar-plus"></i>
                <span>Inscrit <?php echo human_time_diff(strtotime($trainer->created_at)); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Card Footer avec actions -->
    <div class="trpro-card-footer">
        <div class="trpro-action-buttons">
            <?php if (!empty($contact_email)): ?>
                <a href="mailto:<?php echo esc_attr($contact_email); ?>?subject=Contact formateur %23<?php echo $trainer_id_formatted; ?>&body=Bonjour,%0D%0A%0D%0AJe souhaite entrer en contact avec le formateur %23<?php echo $trainer_id_formatted; ?> (<?php echo esc_attr($display_name); ?>) concernant ses spécialités en <?php echo esc_attr($trainer->specialties); ?>.%0D%0A%0D%0ACordialement" 
                   class="trpro-btn trpro-btn-primary"
                   title="Contacter ce formateur">
                    <i class="fas fa-envelope"></i>
                    <span>Contacter</span>
                </a>
            <?php endif; ?>
            
            <!-- ✅ Bouton pour voir le profil détaillé -->
            <button class="trpro-btn trpro-btn-outline trpro-btn-profile" 
                    data-trainer-id="<?php echo esc_attr($trainer->id); ?>"
                    title="Voir le profil détaillé">
                <i class="fas fa-user"></i>
                <span>Profil</span>
            </button>
        </div>
        
        <!-- Liens supplémentaires -->
        <div class="trpro-additional-links">
            <?php if (!empty($trainer->linkedin_url)): ?>
                <a href="<?php echo esc_url($trainer->linkedin_url); ?>" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="trpro-social-link"
                   title="Voir le profil LinkedIn">
                    <i class="fab fa-linkedin"></i>
                </a>
            <?php endif; ?>
            
            <?php if (!empty($contact_phone)): ?>
                <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $contact_phone)); ?>" 
                   class="trpro-social-link"
                   title="Appeler">
                    <i class="fas fa-phone"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Indicateur de popularité/matching (simulé) -->
    <div class="trpro-popularity-indicator">
        <div class="trpro-popularity-bar" style="width: <?php echo rand(60, 95); ?>%;"></div>
    </div>
</article>

<!-- ✅ Modal de profil détaillé améliorée -->
<div class="trpro-modal-overlay" id="trpro-modal-<?php echo esc_attr($trainer->id); ?>" style="display: none;">
    <div class="trpro-modal-container">
        <div class="trpro-modal-header">
            <div class="trpro-modal-title">
                <div class="trpro-modal-avatar">
                    <?php if (!empty($trainer->photo_file)): ?>
                        <img src="<?php echo esc_url($upload_dir['baseurl'] . '/' . $trainer->photo_file); ?>" alt="Photo du formateur">
                    <?php else: ?>
                        <div class="trpro-modal-avatar-placeholder">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="trpro-modal-info">
                    <h4><?php echo esc_html($display_name); ?></h4>
                    <p>Formateur Expert #<?php echo $trainer_id_formatted; ?></p>
                    <?php if (!empty($trainer->company)): ?>
                        <p class="trpro-modal-company"><?php echo esc_html($trainer->company); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <button class="trpro-modal-close" data-trainer-id="<?php echo esc_attr($trainer->id); ?>">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="trpro-modal-content">
            <!-- Zones d'intervention complètes -->
            <?php if (!empty($intervention_regions)): ?>
                <div class="trpro-modal-section">
                    <h5>
                        <i class="fas fa-map-marker-alt"></i>
                        Zones d'intervention
                    </h5>
                    <div class="trpro-modal-zones">
                        <?php foreach ($intervention_regions as $region): 
                            $region_label = $region_labels[$region] ?? ucfirst(str_replace('-', ' ', $region));
                        ?>
                            <span class="trpro-zone-chip">
                                <i class="fas fa-map-pin"></i>
                                <?php echo esc_html($region_label); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="trpro-modal-section">
                <h5>
                    <i class="fas fa-cogs"></i>
                    Compétences techniques
                </h5>
                <div class="trpro-detailed-specialties">
                    <?php foreach ($specialties as $specialty): 
                        $specialty = trim($specialty);
                        if (!empty($specialty)):
                            $icon = $specialty_icons[$specialty] ?? 'fas fa-cog';
                            $label = ucfirst(str_replace('-', ' ', $specialty));
                    ?>
                        <div class="trpro-specialty-chip">
                            <i class="<?php echo esc_attr($icon); ?>"></i>
                            <span><?php echo esc_html($label); ?></span>
                        </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
            
            <!-- Disponibilité et tarif -->
            <div class="trpro-modal-section">
                <h5>
                    <i class="fas fa-clock"></i>
                    Disponibilité & Tarifs
                </h5>
                <div class="trpro-availability-info">
                    <?php if (!empty($trainer->availability)): ?>
                        <div class="trpro-info-item">
                            <strong>Disponibilité :</strong>
                            <span><?php echo esc_html(ucfirst(str_replace('-', ' ', $trainer->availability))); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($trainer->hourly_rate)): ?>
                        <div class="trpro-info-item">
                            <strong>Tarif horaire :</strong>
                            <span><?php echo esc_html($trainer->hourly_rate); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($trainer->experience)): ?>
                <div class="trpro-modal-section">
                    <h5>
                        <i class="fas fa-briefcase"></i>
                        Expérience professionnelle
                    </h5>
                    <div class="trpro-experience-full">
                        <?php echo nl2br(esc_html($trainer->experience)); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($trainer->bio)): ?>
                <div class="trpro-modal-section">
                    <h5>
                        <i class="fas fa-user"></i>
                        Présentation
                    </h5>
                    <div class="trpro-bio-full">
                        <?php echo nl2br(esc_html($trainer->bio)); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="trpro-modal-actions">
                <!-- ✅ CORRECTION: Bouton contact modal avec données pour nonce unifié -->
                <button class="trpro-btn trpro-btn-primary trpro-btn-large trpro-btn-contact-modal" 
                        data-trainer-id="<?php echo esc_attr($trainer->id); ?>" 
                        data-trainer-name="<?php echo esc_attr($display_name); ?>">
                    <i class="fas fa-envelope"></i>
                    Contacter par formulaire
                </button>
                
                <?php if (!empty($contact_email)): ?>
                    <a href="mailto:<?php echo esc_attr($contact_email); ?>?subject=Contact formateur %23<?php echo $trainer_id_formatted; ?>" 
                       class="trpro-btn trpro-btn-outline trpro-btn-large">
                        <i class="fas fa-envelope"></i>
                        Contacter par Email
                    </a>
                <?php endif; ?>
                
                <?php if (!empty($trainer->linkedin_url)): ?>
                    <a href="<?php echo esc_url($trainer->linkedin_url); ?>" 
                       target="_blank"
                       rel="noopener noreferrer"
                       class="trpro-btn trpro-btn-outline trpro-btn-large">
                        <i class="fab fa-linkedin"></i>
                        Voir LinkedIn
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== STYLES POUR LES NOUVELLES FONCTIONNALITÉS ===== */

/* Nom anonymisé */
.trpro-trainer-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0a2540;
    margin-bottom: 4px;
    text-align: center;
}

.trpro-trainer-id {
    font-size: 0.75rem;
    color: #8b92a6;
    font-weight: 500;
    text-align: center;
    margin-bottom: 8px;
}

/* Zones d'intervention */
.trpro-intervention-zones {
    margin-bottom: 16px;
    padding: 12px;
    background: #f0f9ff;
    border-radius: 8px;
    border: 1px solid #e0f2fe;
}

.trpro-zones-header {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #0369a1;
}

.trpro-zones-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.trpro-zone-tag {
    background: #0ea5e9;
    color: white;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 500;
}

.trpro-zone-more {
    background: #64748b;
}

/* Badge de localisation */
.trpro-location-badge {
    background: #0ea5e9;
    color: white;
}

/* Modal améliorée */
.trpro-modal-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.trpro-modal-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
}

.trpro-modal-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.trpro-modal-avatar-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #635bff, #7c73ff);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.trpro-modal-info h4 {
    margin: 0;
    color: #0a2540;
    font-size: 1.25rem;
    font-weight: 700;
}

.trpro-modal-info p {
    margin: 2px 0 0 0;
    color: #6b7280;
    font-size: 0.9rem;
}

.trpro-modal-company {
    color: #635bff !important;
    font-weight: 500;
}

/* Zones dans la modal */
.trpro-modal-zones {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.trpro-zone-chip {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #f0f9ff;
    color: #0369a1;
    padding: 8px 12px;
    border-radius: 20px;
    border: 1px solid #0ea5e9;
    font-size: 0.875rem;
    font-weight: 500;
}

.trpro-zone-chip i {
    font-size: 0.8rem;
}

/* Informations de disponibilité */
.trpro-availability-info {
    background: #f8fafc;
    padding: 16px;
    border-radius: 8px;
    border-left: 3px solid #635bff;
}

.trpro-info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.trpro-info-item:last-child {
    margin-bottom: 0;
}

.trpro-info-item strong {
    color: #374151;
}

.trpro-info-item span {
    color: #6b7280;
    font-weight: 500;
}

/* ✅ Styles pour bouton contact modal */
.trpro-btn-contact-modal {
    transition: all 0.2s ease;
}

.trpro-btn-contact-modal:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

/* Responsive pour les nouvelles fonctionnalités */
@media (max-width: 768px) {
    .trpro-intervention-zones {
        padding: 8px;
    }
    
    .trpro-zones-list {
        justify-content: center;
    }
    
    .trpro-modal-title {
        flex-direction: column;
        text-align: center;
        gap: 8px;
    }
    
    .trpro-modal-zones {
        justify-content: center;
    }
    
    .trpro-info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
}

/* Animation pour les nouvelles zones */
.trpro-zone-tag,
.trpro-zone-chip {
    animation: fadeInScale 0.3s ease-out;
}

@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Amélioration de l'espacement des cartes */
.trpro-trainer-identity {
    margin-bottom: 16px;
    text-align: center;
}

.trpro-trainer-company {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: #635bff;
    font-size: 0.875rem;
    font-weight: 500;
    margin-top: 8px;
}

.trpro-trainer-company i {
    font-size: 0.75rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== GESTION DES MODALS DE PROFIL AVEC NONCE CORRIGÉ =====
    const profileButtons = document.querySelectorAll('.trpro-btn-profile');
    const modalCloses = document.querySelectorAll('.trpro-modal-close');
    const modalOverlays = document.querySelectorAll('.trpro-modal-overlay');

    // Ouvrir les modals
    profileButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const trainerId = this.dataset.trainerId;
            const modal = document.getElementById(`trpro-modal-${trainerId}`);
            
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                
                // Animation d'entrée
                setTimeout(() => {
                    modal.classList.add('active');
                }, 10);

                // Analytics tracking
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'view_trainer_profile', {
                        'trainer_id': trainerId,
                        'event_category': 'engagement'
                    });
                }
            }
        });
    });

    // Fermer les modals
    modalCloses.forEach(button => {
        button.addEventListener('click', function() {
            const trainerId = this.dataset.trainerId;
            const modal = document.getElementById(`trpro-modal-${trainerId}`);
            
            if (modal) {
                modal.classList.remove('active');
                
                setTimeout(() => {
                    modal.style.display = 'none';
                    document.body.style.overflow = '';
                }, 300);
            }
        });
    });

    // Fermer en cliquant sur l'overlay
    modalOverlays.forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                const closeButton = this.querySelector('.trpro-modal-close');
                if (closeButton) {
                    closeButton.click();
                }
            }
        });
    });

    // Fermer avec Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.trpro-modal-overlay.active');
            if (activeModal) {
                const closeButton = activeModal.querySelector('.trpro-modal-close');
                if (closeButton) {
                    closeButton.click();
                }
            }
        }
    });

    // ✅ CORRECTION: Gestionnaire bouton contact modal avec nonce unifié
    document.addEventListener('click', function(e) {
        if (e.target.closest('.trpro-btn-contact-modal')) {
            e.preventDefault();
            const button = e.target.closest('.trpro-btn-contact-modal');
            const trainerId = button.dataset.trainerId;
            const trainerName = button.dataset.trainerName;
            
            console.log('🎯 Contact modal clicked:', {trainerId, trainerName});
            
            // Fermer la modal actuelle
            const activeModal = document.querySelector('.trpro-modal-overlay.active');
            if (activeModal) {
                activeModal.classList.remove('active');
                setTimeout(() => {
                    activeModal.style.display = 'none';
                }, 300);
            }
            
            // Ouvrir la modal de contact (utilise le nonce unifié dans le JS principal)
            setTimeout(() => {
                if (typeof window.openContactModal === 'function') {
                    window.openContactModal(trainerId, trainerName);
                } else {
                    console.warn('⚠️ openContactModal function not available');
                }
            }, 350);
        }
    });

    // ===== AMÉLIORATION DES INTERACTIONS =====
    
    // Effet hover sur les cartes
    const trainerCards = document.querySelectorAll('.trpro-trainer-card-modern');
    
    trainerCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px)';
            this.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.12), 0 4px 10px rgba(0, 0, 0, 0.08)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 2px 4px rgba(0, 0, 0, 0.06), 0 4px 6px rgba(0, 0, 0, 0.04)';
        });
    });

    // Animation d'apparition progressive des cartes
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const cardObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observer toutes les cartes
    trainerCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
        cardObserver.observe(card);
    });

    // ===== TRACKING DES INTERACTIONS =====
    
    // Track contact buttons
    const contactButtons = document.querySelectorAll('.trpro-btn-primary[href^="mailto:"]');
    contactButtons.forEach(button => {
        button.addEventListener('click', function() {
            const trainerId = this.closest('.trpro-trainer-card-modern')?.dataset.trainerId;
            
            if (typeof gtag !== 'undefined' && trainerId) {
                gtag('event', 'contact_trainer', {
                    'trainer_id': trainerId,
                    'method': 'email',
                    'event_category': 'engagement'
                });
            }
        });
    });

    // Track LinkedIn links
    const linkedinLinks = document.querySelectorAll('a[href*="linkedin"]');
    linkedinLinks.forEach(link => {
        link.addEventListener('click', function() {
            const trainerId = this.closest('.trpro-trainer-card-modern')?.dataset.trainerId ||
                              this.closest('.trpro-modal-overlay')?.id.replace('trpro-modal-', '');
            
            if (typeof gtag !== 'undefined' && trainerId) {
                gtag('event', 'view_linkedin', {
                    'trainer_id': trainerId,
                    'event_category': 'external_link'
                });
            }
        });
    });
});
</script>