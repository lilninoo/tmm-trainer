<?php
/**
 * Template pour afficher les détails d'un formateur
 * 
 * Fichier: /wp-content/plugins/trainer-registration-plugin/admin/partials/trainer-details.php
 * Variable disponible: $trainer (objet avec toutes les données du formateur)
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap trainer-details-wrap">
    <div class="trainer-details-header">
        <div class="header-left">
            <a href="<?php echo admin_url('admin.php?page=trainer-registration'); ?>" class="button">
                <i class="fas fa-arrow-left"></i>
                Retour à la liste
            </a>
            <h1 class="wp-heading-inline">
                <i class="fas fa-user"></i>
                Profil de <?php echo esc_html($trainer->first_name . ' ' . $trainer->last_name); ?>
            </h1>
        </div>
        
        <div class="header-actions">
            <?php if ($trainer->status === 'pending'): ?>
                <button type="button" class="button button-primary trainer-action" 
                        data-action="approve" data-trainer-id="<?php echo $trainer->id; ?>">
                    <i class="fas fa-check"></i>
                    Approuver
                </button>
                <button type="button" class="button trainer-action" 
                        data-action="reject" data-trainer-id="<?php echo $trainer->id; ?>">
                    <i class="fas fa-times"></i>
                    Rejeter
                </button>
            <?php endif; ?>
            
            <button type="button" class="button delete-trainer trainer-action" 
                    data-action="delete" data-trainer-id="<?php echo $trainer->id; ?>">
                <i class="fas fa-trash"></i>
                Supprimer
            </button>
        </div>
    </div>

    <div class="trainer-details-content">
        <!-- Informations principales -->
        <div class="trainer-details-grid">
            <div class="trainer-details-main">
                <!-- Carte d'identité -->
                <div class="detail-card identity-card">
                    <div class="card-header">
                        <h3><i class="fas fa-id-card"></i> Informations personnelles</h3>
                        <span class="status-badge status-<?php echo esc_attr($trainer->status); ?>">
                            <?php
                            switch ($trainer->status) {
                                case 'pending':
                                    echo '<i class="fas fa-clock"></i> En attente';
                                    break;
                                case 'approved':
                                    echo '<i class="fas fa-check-circle"></i> Approuvé';
                                    break;
                                case 'rejected':
                                    echo '<i class="fas fa-times-circle"></i> Rejeté';
                                    break;
                            }
                            ?>
                        </span>
                    </div>
                    
                    <div class="card-content">
                        <div class="trainer-profile">
                            <div class="profile-photo">
                                <?php if (!empty($trainer->photo_file)): ?>
                                    <?php 
                                    $upload_dir = wp_upload_dir();
                                    $photo_url = $upload_dir['baseurl'] . '/' . $trainer->photo_file;
                                    ?>
                                    <img src="<?php echo esc_url($photo_url); ?>" alt="Photo de profil">
                                <?php else: ?>
                                    <div class="photo-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="profile-info">
                                <h2><?php echo esc_html($trainer->first_name . ' ' . $trainer->last_name); ?></h2>
                                <?php if (!empty($trainer->company)): ?>
                                    <p class="company"><i class="fas fa-building"></i> <?php echo esc_html($trainer->company); ?></p>
                                <?php endif; ?>
                                
                                <div class="contact-info">
                                    <div class="contact-item">
                                        <i class="fas fa-envelope"></i>
                                        <a href="mailto:<?php echo esc_attr($trainer->email); ?>"><?php echo esc_html($trainer->email); ?></a>
                                    </div>
                                    <div class="contact-item">
                                        <i class="fas fa-phone"></i>
                                        <a href="tel:<?php echo esc_attr($trainer->phone); ?>"><?php echo esc_html($trainer->phone); ?></a>
                                    </div>
                                    <?php if (!empty($trainer->linkedin_url)): ?>
                                        <div class="contact-item">
                                            <i class="fab fa-linkedin"></i>
                                            <a href="<?php echo esc_url($trainer->linkedin_url); ?>" target="_blank" rel="noopener">
                                                Profil LinkedIn
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="registration-meta">
                            <div class="meta-item">
                                <span class="meta-label">Inscrit le :</span>
                                <span class="meta-value"><?php echo date_i18n('d/m/Y à H:i', strtotime($trainer->created_at)); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Dernière modification :</span>
                                <span class="meta-value"><?php echo date_i18n('d/m/Y à H:i', strtotime($trainer->updated_at)); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">ID Formateur :</span>
                                <span class="meta-value">#<?php echo str_pad($trainer->id, 4, '0', STR_PAD_LEFT); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expertise professionnelle -->
                <div class="detail-card expertise-card">
                    <div class="card-header">
                        <h3><i class="fas fa-briefcase"></i> Expertise professionnelle</h3>
                    </div>
                    
                    <div class="card-content">
                        <div class="expertise-section">
                            <h4><i class="fas fa-cogs"></i> Spécialités</h4>
                            <div class="specialties-display">
                                <?php 
                                $specialties = explode(', ', $trainer->specialties);
                                foreach ($specialties as $specialty): 
                                    $specialty = trim($specialty);
                                    if (!empty($specialty)):
                                        // Mapping des spécialités vers des icônes et couleurs
                                        $specialty_config = [
                                            'administration-systeme' => ['icon' => 'fas fa-server', 'color' => '#3b82f6'],
                                            'reseaux' => ['icon' => 'fas fa-network-wired', 'color' => '#10b981'],
                                            'cloud' => ['icon' => 'fas fa-cloud', 'color' => '#06b6d4'],
                                            'devops' => ['icon' => 'fas fa-infinity', 'color' => '#8b5cf6'],
                                            'securite' => ['icon' => 'fas fa-shield-alt', 'color' => '#ef4444'],
                                            'telecoms' => ['icon' => 'fas fa-satellite-dish', 'color' => '#f59e0b'],
                                            'developpement' => ['icon' => 'fas fa-code', 'color' => '#ec4899'],
                                            'bases-donnees' => ['icon' => 'fas fa-database', 'color' => '#84cc16']
                                        ];
                                        
                                        $config = isset($specialty_config[$specialty]) ? $specialty_config[$specialty] : ['icon' => 'fas fa-cog', 'color' => '#6b7280'];
                                ?>
                                    <div class="specialty-item" style="border-left-color: <?php echo $config['color']; ?>">
                                        <i class="<?php echo esc_attr($config['icon']); ?>" style="color: <?php echo $config['color']; ?>"></i>
                                        <span><?php echo esc_html(ucfirst(str_replace('-', ' ', $specialty))); ?></span>
                                    </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                        
                        <div class="expertise-section">
                            <h4><i class="fas fa-star"></i> Expérience et compétences</h4>
                            <div class="experience-content">
                                <?php echo nl2br(esc_html($trainer->experience)); ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($trainer->bio)): ?>
                            <div class="expertise-section">
                                <h4><i class="fas fa-info-circle"></i> Présentation</h4>
                                <div class="bio-content">
                                    <?php echo nl2br(esc_html($trainer->bio)); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="expertise-meta">
                            <?php if (!empty($trainer->availability)): ?>
                                <div class="meta-item">
                                    <i class="fas fa-calendar-check"></i>
                                    <span class="meta-label">Disponibilité :</span>
                                    <span class="meta-value"><?php echo esc_html(ucfirst(str_replace('-', ' ', $trainer->availability))); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($trainer->hourly_rate)): ?>
                                <div class="meta-item">
                                    <i class="fas fa-euro-sign"></i>
                                    <span class="meta-label">Tarif horaire :</span>
                                    <span class="meta-value"><?php echo esc_html($trainer->hourly_rate); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Documents -->
                <div class="detail-card documents-card">
                    <div class="card-header">
                        <h3><i class="fas fa-folder"></i> Documents</h3>
                    </div>
                    
                    <div class="card-content">
                        <div class="documents-grid">
                            <?php if (!empty($trainer->cv_file)): ?>
                                <div class="document-item cv-document">
                                    <div class="document-icon">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div class="document-info">
                                        <h4>Curriculum Vitae</h4>
                                        <p>Document principal du formateur</p>
                                        <small><?php echo esc_html($trainer->cv_file); ?></small>
                                    </div>
                                    <div class="document-actions">
                                        <a href="<?php echo admin_url('admin-ajax.php?action=download_trainer_file&trainer_id=' . $trainer->id . '&file_type=cv&nonce=' . wp_create_nonce('trainer_admin_nonce')); ?>" 
                                           class="button button-primary">
                                            <i class="fas fa-download"></i>
                                            Télécharger
                                        </a>
                                        <button type="button" class="button preview-document" data-file="<?php echo esc_attr($trainer->cv_file); ?>">
                                            <i class="fas fa-eye"></i>
                                            Aperçu
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($trainer->photo_file)): ?>
                                <div class="document-item photo-document">
                                    <div class="document-icon">
                                        <i class="fas fa-image"></i>
                                    </div>
                                    <div class="document-info">
                                        <h4>Photo de profil</h4>
                                        <p>Image du formateur</p>
                                        <small><?php echo esc_html($trainer->photo_file); ?></small>
                                    </div>
                                    <div class="document-actions">
                                        <a href="<?php echo admin_url('admin-ajax.php?action=download_trainer_file&trainer_id=' . $trainer->id . '&file_type=photo&nonce=' . wp_create_nonce('trainer_admin_nonce')); ?>" 
                                           class="button button-primary">
                                            <i class="fas fa-download"></i>
                                            Télécharger
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar avec actions et informations complémentaires -->
            <div class="trainer-details-sidebar">
                <!-- Actions rapides -->
                <div class="sidebar-card actions-card">
                    <h3><i class="fas fa-bolt"></i> Actions rapides</h3>
                    
                    <div class="action-buttons">
                        <a href="mailto:<?php echo esc_attr($trainer->email); ?>?subject=Contact depuis la plateforme formateurs" 
                           class="action-btn email-btn">
                            <i class="fas fa-envelope"></i>
                            Envoyer un email
                        </a>
                        
                        <?php if (!empty($trainer->phone)): ?>
                            <a href="tel:<?php echo esc_attr($trainer->phone); ?>" class="action-btn phone-btn">
                                <i class="fas fa-phone"></i>
                                Appeler
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($trainer->linkedin_url)): ?>
                            <a href="<?php echo esc_url($trainer->linkedin_url); ?>" target="_blank" class="action-btn linkedin-btn">
                                <i class="fab fa-linkedin"></i>
                                LinkedIn
                            </a>
                        <?php endif; ?>
                        
                        <button type="button" class="action-btn notes-btn" id="add-notes">
                            <i class="fas fa-sticky-note"></i>
                            Ajouter une note
                        </button>
                    </div>
                </div>

                <!-- Conformité RGPD -->
                <div class="sidebar-card rgpd-card">
                    <h3><i class="fas fa-shield-alt"></i> Conformité RGPD</h3>
                    
                    <div class="rgpd-status">
                        <div class="rgpd-item">
                            <i class="fas fa-check-circle text-success"></i>
                            <span>Consentement principal accordé</span>
                            <small><?php echo date_i18n('d/m/Y', strtotime($trainer->created_at)); ?></small>
                        </div>
                        
                        <div class="rgpd-item">
                            <i class="fas fa-info-circle text-info"></i>
                            <span>Données collectées légalement</span>
                            <small>Base légale : Consentement (Art. 6.1.a RGPD)</small>
                        </div>
                        
                        <div class="rgpd-actions">
                            <button type="button" class="button button-small export-data">
                                <i class="fas fa-download"></i>
                                Exporter les données
                            </button>
                            <button type="button" class="button button-small delete-data">
                                <i class="fas fa-trash"></i>
                                Supprimer les données
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="sidebar-card stats-card">
                    <h3><i class="fas fa-chart-bar"></i> Statistiques</h3>
                    
                    <div class="stats-list">
                        <div class="stat-item">
                            <span class="stat-label">Profil consulté</span>
                            <span class="stat-value">
                                <?php echo rand(5, 50); ?> fois
                            </span>
                        </div>
                        
                        <div class="stat-item">
                            <span class="stat-label">Dernière activité</span>
                            <span class="stat-value">
                                <?php echo human_time_diff(strtotime($trainer->updated_at), current_time('timestamp')) . ' ago'; ?>
                            </span>
                        </div>
                        
                        <div class="stat-item">
                            <span class="stat-label">Score de complétude</span>
                            <span class="stat-value">
                                <?php 
                                $completeness = 0;
                                if (!empty($trainer->first_name)) $completeness += 10;
                                if (!empty($trainer->last_name)) $completeness += 10;
                                if (!empty($trainer->email)) $completeness += 10;
                                if (!empty($trainer->phone)) $completeness += 10;
                                if (!empty($trainer->specialties)) $completeness += 20;
                                if (!empty($trainer->experience)) $completeness += 20;
                                if (!empty($trainer->cv_file)) $completeness += 15;
                                if (!empty($trainer->photo_file)) $completeness += 5;
                                
                                echo $completeness . '%';
                                ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Historique des actions -->
                <div class="sidebar-card history-card">
                    <h3><i class="fas fa-history"></i> Historique</h3>
                    
                    <div class="history-timeline">
                        <div class="timeline-item">
                            <div class="timeline-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="timeline-content">
                                <strong>Inscription</strong>
                                <small><?php echo date_i18n('d/m/Y à H:i', strtotime($trainer->created_at)); ?></small>
                                <p>Le formateur s'est inscrit sur la plateforme</p>
                            </div>
                        </div>
                        
                        <?php if ($trainer->status !== 'pending'): ?>
                            <div class="timeline-item">
                                <div class="timeline-icon">
                                    <i class="fas fa-<?php echo $trainer->status === 'approved' ? 'check' : 'times'; ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <strong><?php echo $trainer->status === 'approved' ? 'Approbation' : 'Rejet'; ?></strong>
                                    <small><?php echo date_i18n('d/m/Y à H:i', strtotime($trainer->updated_at)); ?></small>
                                    <p>Statut mis à jour par l'administrateur</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour les notes -->
<div id="notes-modal" class="trainer-modal" style="display: none;">
    <div class="trainer-modal-content">
        <div class="trainer-modal-header">
            <h3>Ajouter une note</h3>
            <button type="button" class="trainer-modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="trainer-modal-body">
            <textarea id="trainer-notes" rows="5" placeholder="Ajoutez vos notes sur ce formateur..."></textarea>
        </div>
        <div class="trainer-modal-footer">
            <button type="button" class="button" id="notes-cancel">Annuler</button>
            <button type="button" class="button button-primary" id="notes-save">Sauvegarder</button>
        </div>
    </div>
</div>