<?php
/**
 * Template statistiques des formateurs
 * 
 * Fichier: public/partials/trainer-stats.php
 * Variables disponibles: $stats, $atts
 */

if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les statistiques depuis les variables globales ou les calculer
if (!isset($stats)) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'trainer_registrations';
    
    // Vérifier que la table existe
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if ($table_exists) {
        $stats = array(
            'total' => intval($wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'approved'") ?: 0),
            'pending' => intval($wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'") ?: 0),
            'specialties' => intval($wpdb->get_var("SELECT COUNT(DISTINCT specialties) FROM $table_name WHERE status = 'approved' AND specialties != ''") ?: 0),
            'this_month' => intval($wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'approved' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())") ?: 0)
        );
        
        // Statistiques supplémentaires
        $stats['this_week'] = intval($wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'approved' AND WEEK(created_at) = WEEK(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())") ?: 0);
        $stats['regions'] = intval($wpdb->get_var("SELECT COUNT(DISTINCT intervention_regions) FROM $table_name WHERE status = 'approved' AND intervention_regions IS NOT NULL AND intervention_regions != ''") ?: 0);
    } else {
        $stats = array(
            'total' => 0,
            'pending' => 0,
            'specialties' => 0,
            'this_month' => 0,
            'this_week' => 0,
            'regions' => 0
        );
    }
}

// Configuration par défaut des attributs
if (!isset($atts)) {
    $atts = array(
        'show_total' => 'true',
        'show_specialties' => 'true',
        'show_chart' => 'false',
        'style' => 'cards'
    );
}
?>

<div class="trpro-stats-container">
    
    <?php if ($atts['style'] === 'cards'): ?>
        
        <!-- Statistiques en cartes -->
        <div class="trpro-stats-grid">
            
            <?php if ($atts['show_total'] === 'true'): ?>
                <!-- Formateurs actifs -->
                <div class="trpro-stat-card trpro-stat-primary">
                    <div class="trpro-stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="trpro-stat-content">
                        <div class="trpro-stat-number"><?php echo number_format($stats['total']); ?></div>
                        <div class="trpro-stat-label">Formateurs Actifs</div>
                    </div>
                    <div class="trpro-stat-trend">
                        <i class="fas fa-arrow-up"></i>
                        <span>+<?php echo $stats['this_month']; ?> ce mois</span>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show_specialties'] === 'true'): ?>
                <!-- Spécialités -->
                <div class="trpro-stat-card trpro-stat-success">
                    <div class="trpro-stat-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="trpro-stat-content">
                        <div class="trpro-stat-number"><?php echo number_format($stats['specialties']); ?></div>
                        <div class="trpro-stat-label">Spécialités Couvertes</div>
                    </div>
                    <div class="trpro-stat-trend">
                        <i class="fas fa-star"></i>
                        <span>Expertise variée</span>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Zones d'intervention -->
            <div class="trpro-stat-card trpro-stat-info">
                <div class="trpro-stat-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="trpro-stat-content">
                    <div class="trpro-stat-number"><?php echo number_format($stats['regions']); ?></div>
                    <div class="trpro-stat-label">Zones d'Intervention</div>
                </div>
                <div class="trpro-stat-trend">
                    <i class="fas fa-globe"></i>
                    <span>France entière</span>
                </div>
            </div>
            
            <!-- Nouveaux cette semaine -->
            <div class="trpro-stat-card trpro-stat-warning">
                <div class="trpro-stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="trpro-stat-content">
                    <div class="trpro-stat-number"><?php echo number_format($stats['this_week']); ?></div>
                    <div class="trpro-stat-label">Nouveaux cette Semaine</div>
                </div>
                <div class="trpro-stat-trend">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Croissance continue</span>
                </div>
            </div>
            
        </div>
        
    <?php else: ?>
        
        <!-- Statistiques en liste -->
        <div class="trpro-stats-list">
            
            <?php if ($atts['show_total'] === 'true'): ?>
                <div class="trpro-stat-item">
                    <div class="trpro-stat-item-content">
                        <span class="trpro-stat-item-label">Formateurs Actifs</span>
                        <span class="trpro-stat-item-value"><?php echo number_format($stats['total']); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show_specialties'] === 'true'): ?>
                <div class="trpro-stat-item">
                    <div class="trpro-stat-item-content">
                        <span class="trpro-stat-item-label">Spécialités Couvertes</span>
                        <span class="trpro-stat-item-value"><?php echo number_format($stats['specialties']); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="trpro-stat-item">
                <div class="trpro-stat-item-content">
                    <span class="trpro-stat-item-label">Zones d'Intervention</span>
                    <span class="trpro-stat-item-value"><?php echo number_format($stats['regions']); ?></span>
                </div>
            </div>
            
            <div class="trpro-stat-item">
                <div class="trpro-stat-item-content">
                    <span class="trpro-stat-item-label">Nouveaux ce Mois</span>
                    <span class="trpro-stat-item-value"><?php echo number_format($stats['this_month']); ?></span>
                </div>
            </div>
            
        </div>
        
    <?php endif; ?>
    
    <!-- Graphique optionnel -->
    <?php if ($atts['show_chart'] === 'true'): ?>
        <div class="trpro-stats-chart">
            <h3 class="trpro-chart-title">
                <i class="fas fa-chart-bar"></i>
                Évolution des inscriptions
            </h3>
            <div class="trpro-chart-container">
                <div class="trpro-chart-placeholder">
                    <i class="fas fa-chart-line"></i>
                    <p>Graphique des inscriptions sur les 6 derniers mois</p>
                    <small>Fonctionnalité à venir</small>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Informations complémentaires -->
    <div class="trpro-stats-info">
        <div class="trpro-stats-note">
            <i class="fas fa-info-circle"></i>
            <div class="trpro-note-content">
                <h4>À propos de ces statistiques</h4>
                <p>Ces données reflètent le nombre de formateurs actifs et vérifiés sur notre plateforme. 
                Les statistiques sont mises à jour en temps réel et incluent uniquement les profils approuvés.</p>
            </div>
        </div>
        
        <div class="trpro-stats-actions">
            <a href="<?php echo esc_url(home_url('/catalogue-formateurs/')); ?>" 
               class="trpro-btn trpro-btn-primary">
                <i class="fas fa-search"></i>
                Parcourir les Formateurs
            </a>
            
            <a href="<?php echo esc_url(home_url('/inscription-formateur/')); ?>" 
               class="trpro-btn trpro-btn-outline">
                <i class="fas fa-user-plus"></i>
                Devenir Formateur
            </a>
        </div>
    </div>
    
</div>

<style>
/* Styles pour les statistiques */
.trpro-stats-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 32px 20px;
}

.trpro-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.trpro-stat-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border: 1px solid #e2e8f0;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.trpro-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

.trpro-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--card-color, #3b82f6);
}

.trpro-stat-primary { --card-color: #3b82f6; }
.trpro-stat-success { --card-color: #10b981; }
.trpro-stat-info { --card-color: #06b6d4; }
.trpro-stat-warning { --card-color: #f59e0b; }

.trpro-stat-icon {
    width: 60px;
    height: 60px;
    background: var(--card-color, #3b82f6);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    margin-bottom: 16px;
    opacity: 0.9;
}

.trpro-stat-content {
    margin-bottom: 16px;
}

.trpro-stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1;
    margin-bottom: 8px;
}

.trpro-stat-label {
    font-size: 1rem;
    color: #64748b;
    font-weight: 500;
}

.trpro-stat-trend {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--card-color, #3b82f6);
    font-size: 0.875rem;
    font-weight: 500;
}

.trpro-stat-trend i {
    font-size: 0.75rem;
}

/* Style liste */
.trpro-stats-list {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    margin-bottom: 40px;
}

.trpro-stat-item {
    padding: 20px 24px;
    border-bottom: 1px solid #e2e8f0;
}

.trpro-stat-item:last-child {
    border-bottom: none;
}

.trpro-stat-item-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.trpro-stat-item-label {
    color: #64748b;
    font-weight: 500;
}

.trpro-stat-item-value {
    color: #1e293b;
    font-size: 1.25rem;
    font-weight: 600;
}

/* Graphique */
.trpro-stats-chart {
    background: #ffffff;
    border-radius: 12px;
    padding: 32px;
    border: 1px solid #e2e8f0;
    margin-bottom: 40px;
}

.trpro-chart-title {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #1e293b;
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 24px;
}

.trpro-chart-title i {
    color: #3b82f6;
}

.trpro-chart-container {
    height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.trpro-chart-placeholder {
    text-align: center;
    color: #64748b;
}

.trpro-chart-placeholder i {
    font-size: 3rem;
    color: #cbd5e1;
    margin-bottom: 16px;
}

.trpro-chart-placeholder p {
    margin-bottom: 8px;
    font-weight: 500;
}

.trpro-chart-placeholder small {
    color: #94a3b8;
}

/* Informations complémentaires */
.trpro-stats-info {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 32px;
    align-items: start;
}

.trpro-stats-note {
    background: #f8fafc;
    border-radius: 12px;
    padding: 24px;
    border: 1px solid #e2e8f0;
    display: flex;
    gap: 16px;
}

.trpro-stats-note i {
    color: #3b82f6;
    font-size: 1.25rem;
    margin-top: 2px;
    flex-shrink: 0;
}

.trpro-note-content h4 {
    color: #1e293b;
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 8px;
}

.trpro-note-content p {
    color: #64748b;
    line-height: 1.5;
    margin: 0;
}

.trpro-stats-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.trpro-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    text-align: center;
}

.trpro-btn-primary {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.trpro-btn-primary:hover {
    background: #2563eb;
    border-color: #2563eb;
    transform: translateY(-1px);
    text-decoration: none;
    color: white;
}

.trpro-btn-outline {
    background: transparent;
    color: #3b82f6;
    border-color: #3b82f6;
}

.trpro-btn-outline:hover {
    background: #3b82f6;
    color: white;
    text-decoration: none;
}

/* Responsive */
@media (max-width: 768px) {
    .trpro-stats-container {
        padding: 24px 16px;
    }
    
    .trpro-stats-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .trpro-stat-card {
        padding: 20px;
    }
    
    .trpro-stat-number {
        font-size: 2rem;
    }
    
    .trpro-stats-info {
        grid-template-columns: 1fr;
        gap: 24px;
    }
    
    .trpro-stats-note {
        padding: 20px;
    }
}

/* Animations */
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

.trpro-stat-card {
    animation: slideInUp 0.6s ease-out;
}

.trpro-stat-card:nth-child(1) { animation-delay: 0.1s; }
.trpro-stat-card:nth-child(2) { animation-delay: 0.2s; }
.trpro-stat-card:nth-child(3) { animation-delay: 0.3s; }
.trpro-stat-card:nth-child(4) { animation-delay: 0.4s; }
</style>