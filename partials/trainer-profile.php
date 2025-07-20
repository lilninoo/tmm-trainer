<?php
/**
 * Template profil formateur individuel
 * 
 * Fichier: public/partials/trainer-profile.php
 * Variables disponibles: $trainer, $atts
 */

if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les paramètres de contact
$contact_email = get_option('trainer_contact_email', get_option('admin_email'));
$contact_phone = get_option('trainer_contact_phone', '');

// Formater l'ID
$trainer_id_formatted = str_pad($trainer->id, 4, '0', STR_PAD_LEFT);

// Nom anonymisé (déjà défini dans $trainer->display_name)
$display_name = $trainer->display_name ?? 'Formateur Expert';

// Parser les spécialités
$specialties = array_map('trim', explode(',', $trainer->specialties));

// Parser les régions d'intervention si disponibles
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
    'distanciel' => 'Formation à distance'
);

// Mapping des icônes par spécialité
$specialty_icons = array(
    'administration-systeme' => 'fas fa-server',
    'reseaux' => 'fas fa-network-wired', 
    'cloud' => 'fab fa-aws',
    'devops' => 'fas fa-infinity',
    'securite' => 'fas fa-shield-alt',
    'telecoms' => 'fas fa-satellite-dish',
    'developpement' => 'fas fa-code',
    'bases-donnees' => 'fas fa-database'
);

// URLs des fichiers
$upload_dir = wp_upload_dir();
$photo_url = !empty($trainer->photo_file) ? $upload_dir['baseurl'] . '/' . $trainer->photo_file : '';
$cv_url = !empty($trainer->cv_file) ? $upload_dir['baseurl'] . '/' . $trainer->cv_file : '';
?>

<div class="trpro-trainer-profile-container">
    
    <!-- En-tête du profil -->
    <div class="trpro-profile-header">
        <div class="trpro-profile-banner">
            <div class="trpro-profile-avatar-section">
                <div class="trpro-profile-avatar">
                    <?php if ($photo_url): ?>
                        <img src="<?php echo esc_url($photo_url); ?>" 
                             alt="Photo du formateur #<?php echo $trainer_id_formatted; ?>" 
                             loading="lazy">
                    <?php else: ?>
                        <div class="trpro-avatar-placeholder">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="trpro-verification-badge">
                        <i class="fas fa-check-circle"></i>
                        <span>Profil Vérifié</span>
                    </div>
                </div>
                
                <div class="trpro-profile-info">
                    <h1 class="trpro-profile-name"><?php echo esc_html($display_name); ?></h1>
                    <div class="trpro-profile-id">Formateur Expert #<?php echo $trainer_id_formatted; ?></div>
                    
                    <?php if (!empty($trainer->company)): ?>
                        <div class="trpro-profile-company">
                            <i class="fas fa-building"></i>
                            <span><?php echo esc_html($trainer->company); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="trpro-profile-meta">
                        <?php if (!empty($trainer->availability)): ?>
                            <div class="trpro-meta-item">
                                <i class="fas fa-calendar-check"></i>
                                <span><?php echo esc_html(ucfirst(str_replace('-', ' ', $trainer->availability))); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($trainer->hourly_rate)): ?>
                            <div class="trpro-meta-item">
                                <i class="fas fa-euro-sign"></i>
                                <span><?php echo esc_html($trainer->hourly_rate); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="trpro-meta-item">
                            <i class="fas fa-calendar-plus"></i>
                            <span>Inscrit <?php echo human_time_diff(strtotime($trainer->created_at)); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contenu principal du profil -->
    <div class="trpro-profile-content">
        <div class="trpro-profile-grid">
            
            <!-- Colonne principale -->
            <div class="trpro-profile-main">
                
                <!-- Zones d'intervention -->
                <?php if (!empty($intervention_regions) && $atts['show_bio'] === 'true'): ?>
                    <div class="trpro-profile-section">
                        <h2 class="trpro-section-title">
                            <i class="fas fa-map-marker-alt"></i>
                            Zones d'intervention
                        </h2>
                        <div class="trpro-intervention-zones">
                            <?php foreach ($intervention_regions as $region): 
                                $region_label = $region_labels[$region] ?? ucfirst(str_replace('-', ' ', $region));
                            ?>
                                <span class="trpro-zone-badge">
                                    <i class="fas fa-map-pin"></i>
                                    <?php echo esc_html($region_label); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Spécialités techniques -->
                <div class="trpro-profile-section">
                    <h2 class="trpro-section-title">
                        <i class="fas fa-cogs"></i>
                        Expertises techniques
                    </h2>
                    <div class="trpro-specialties-detailed">
                        <?php foreach ($specialties as $specialty): 
                            $specialty = trim($specialty);
                            if (!empty($specialty)):
                                $icon = $specialty_icons[$specialty] ?? 'fas fa-cog';
                                $label = ucfirst(str_replace('-', ' ', $specialty));
                        ?>
                            <div class="trpro-specialty-detailed">
                                <div class="trpro-specialty-icon">
                                    <i class="<?php echo esc_attr($icon); ?>"></i>
                                </div>
                                <div class="trpro-specialty-content">
                                    <h3><?php echo esc_html($label); ?></h3>
                                    <p>Expertise confirmée</p>
                                </div>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
                
                <!-- Expérience professionnelle -->
                <?php if ($atts['show_experience'] === 'true' && !empty($trainer->experience)): ?>
                    <div class="trpro-profile-section">
                        <h2 class="trpro-section-title">
                            <i class="fas fa-briefcase"></i>
                            Expérience professionnelle
                        </h2>
                        <div class="trpro-experience-detailed">
                            <div class="trpro-experience-content">
                                <?php echo nl2br(esc_html($trainer->experience)); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Présentation -->
                <?php if ($atts['show_bio'] === 'true' && !empty($trainer->bio)): ?>
                    <div class="trpro-profile-section">
                        <h2 class="trpro-section-title">
                            <i class="fas fa-user"></i>
                            Présentation
                        </h2>
                        <div class="trpro-bio-detailed">
                            <div class="trpro-bio-content">
                                <?php echo nl2br(esc_html($trainer->bio)); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="trpro-profile-sidebar">
                
                <!-- Actions de contact -->
                <?php if ($atts['show_contact'] === 'true'): ?>
                    <div class="trpro-profile-section trpro-contact-section">
                        <h3 class="trpro-section-title">
                            <i class="fas fa-envelope"></i>
                            Contact
                        </h3>
                        
                        <div class="trpro-contact-actions">
                            <?php if (!empty($contact_email)): ?>
                                <a href="mailto:<?php echo esc_attr($contact_email); ?>?subject=Contact formateur %23<?php echo $trainer_id_formatted; ?>&body=Bonjour,%0D%0A%0D%0AJe souhaite entrer en contact avec le formateur %23<?php echo $trainer_id_formatted; ?> (<?php echo esc_attr($display_name); ?>) concernant ses spécialités.%0D%0A%0D%0ACordialement" 
                                   class="trpro-btn trpro-btn-primary trpro-btn-large">
                                    <i class="fas fa-envelope"></i>
                                    Contacter par Email
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($contact_phone)): ?>
                                <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $contact_phone)); ?>" 
                                   class="trpro-btn trpro-btn-outline trpro-btn-large">
                                    <i class="fas fa-phone"></i>
                                    Appeler
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($trainer->linkedin_url)): ?>
                                <a href="<?php echo esc_url($trainer->linkedin_url); ?>" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="trpro-btn trpro-btn-linkedin trpro-btn-large">
                                    <i class="fab fa-linkedin"></i>
                                    Voir le profil LinkedIn
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="trpro-contact-note">
                            <i class="fas fa-info-circle"></i>
                            <p>Pour respecter la confidentialité, les coordonnées directes ne sont pas affichées. Utilisez le formulaire de contact ci-dessus.</p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Informations complémentaires -->
                <div class="trpro-profile-section">
                    <h3 class="trpro-section-title">
                        <i class="fas fa-info-circle"></i>
                        Informations
                    </h3>
                    
                    <div class="trpro-profile-stats">
                        <div class="trpro-stat-item">
                            <div class="trpro-stat-label">Spécialités</div>
                            <div class="trpro-stat-value"><?php echo count($specialties); ?></div>
                        </div>
                        
                        <?php if (!empty($intervention_regions)): ?>
                            <div class="trpro-stat-item">
                                <div class="trpro-stat-label">Zones d'intervention</div>
                                <div class="trpro-stat-value"><?php echo count($intervention_regions); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($cv_url)): ?>
                            <div class="trpro-stat-item">
                                <div class="trpro-stat-label">CV disponible</div>
                                <div class="trpro-stat-value">
                                    <i class="fas fa-check text-success"></i>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Retour à la liste -->
                <div class="trpro-profile-section">
                    <a href="javascript:history.back()" class="trpro-btn trpro-btn-secondary trpro-btn-large">
                        <i class="fas fa-arrow-left"></i>
                        Retour à la liste
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour le profil détaillé */
.trpro-trainer-profile-container {
    max-width: 1200px;
    margin: 0 auto;
    background: #ffffff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border-radius: 12px;
    overflow: hidden;
}

.trpro-profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    position: relative;
}

.trpro-profile-banner {
    padding: 48px 32px;
}

.trpro-profile-avatar-section {
    display: flex;
    align-items: center;
    gap: 32px;
}

.trpro-profile-avatar {
    position: relative;
    width: 120px;
    height: 120px;
}

.trpro-profile-avatar img,
.trpro-avatar-placeholder {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid rgba(255, 255, 255, 0.3);
}

.trpro-avatar-placeholder {
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
}

.trpro-verification-badge {
    position: absolute;
    bottom: -8px;
    right: -8px;
    background: #10b981;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
    border: 2px solid white;
}

.trpro-profile-info {
    flex: 1;
}

.trpro-profile-name {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 8px;
    color: white;
}

.trpro-profile-id {
    font-size: 1rem;
    opacity: 0.9;
    margin-bottom: 16px;
}

.trpro-profile-company {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1.125rem;
    margin-bottom: 24px;
    opacity: 0.95;
}

.trpro-profile-meta {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
}

.trpro-meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.2);
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 500;
}

.trpro-profile-content {
    padding: 48px 32px;
}

.trpro-profile-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 48px;
}

.trpro-profile-section {
    margin-bottom: 40px;
}

.trpro-section-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 24px;
    padding-bottom: 12px;
    border-bottom: 2px solid #e2e8f0;
}

.trpro-section-title i {
    color: #3b82f6;
}

.trpro-intervention-zones {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.trpro-zone-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #e0f2fe;
    color: #0369a1;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 500;
    border: 1px solid #0ea5e9;
}

.trpro-specialties-detailed {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.trpro-specialty-detailed {
    display: flex;
    align-items: center;
    gap: 16px;
    background: #f8fafc;
    padding: 20px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.trpro-specialty-icon {
    width: 48px;
    height: 48px;
    background: #3b82f6;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.trpro-specialty-content h3 {
    color: #1e293b;
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 4px;
}

.trpro-specialty-content p {
    color: #64748b;
    font-size: 0.875rem;
}

.trpro-experience-detailed,
.trpro-bio-detailed {
    background: #f8fafc;
    border-radius: 12px;
    padding: 24px;
    border-left: 4px solid #3b82f6;
}

.trpro-experience-content,
.trpro-bio-content {
    line-height: 1.6;
    color: #374151;
}

.trpro-contact-section {
    background: #f8fafc;
    border-radius: 12px;
    padding: 24px;
    border: 1px solid #e2e8f0;
}

.trpro-contact-actions {
    margin-bottom: 20px;
}

.trpro-contact-actions .trpro-btn {
    display: block;
    margin-bottom: 12px;
    text-align: center;
}

.trpro-btn-linkedin {
    background: #0077b5;
    color: white;
    border-color: #0077b5;
}

.trpro-btn-linkedin:hover {
    background: #005885;
    border-color: #005885;
    color: white;
    text-decoration: none;
}

.trpro-contact-note {
    background: #fffbeb;
    border: 1px solid #fbbf24;
    border-radius: 8px;
    padding: 16px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.trpro-contact-note i {
    color: #f59e0b;
    margin-top: 2px;
}

.trpro-contact-note p {
    margin: 0;
    color: #92400e;
    font-size: 0.875rem;
    line-height: 1.4;
}

.trpro-profile-stats {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.trpro-stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #e2e8f0;
}

.trpro-stat-label {
    color: #64748b;
    font-size: 0.875rem;
}

.trpro-stat-value {
    font-weight: 600;
    color: #1e293b;
}

.text-success {
    color: #10b981;
}

/* Responsive */
@media (max-width: 768px) {
    .trpro-profile-banner {
        padding: 32px 20px;
    }
    
    .trpro-profile-avatar-section {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .trpro-profile-content {
        padding: 32px 20px;
    }
    
    .trpro-profile-grid {
        grid-template-columns: 1fr;
        gap: 32px;
    }
    
    .trpro-profile-meta {
        justify-content: center;
    }
    
    .trpro-specialties-detailed {
        grid-template-columns: 1fr;
    }
}
</style>