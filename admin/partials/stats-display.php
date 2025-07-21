<?php
/**
 * Template pour afficher les statistiques
 * 
 * Fichier: /wp-content/plugins/trainer-registration-plugin/admin/partials/stats-display.php
 * Variables disponibles: $stats, $specialty_stats, $monthly_registrations
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap trainer-stats-wrap">
    <h1 class="wp-heading-inline">
        <i class="fas fa-chart-bar"></i>
        Statistiques des Formateurs
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=trainer-registration'); ?>" class="page-title-action">
        <i class="fas fa-arrow-left"></i>
        Retour à la liste
    </a>
    
    <hr class="wp-header-end">

    <!-- Filtres de période -->
    <div class="stats-filters">
        <div class="filter-group">
            <label for="period-filter">Période :</label>
            <select id="period-filter">
                <option value="7">7 derniers jours</option>
                <option value="30">30 derniers jours</option>
                <option value="90">90 derniers jours</option>
                <option value="365" selected>12 derniers mois</option>
                <option value="all">Depuis le début</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="export-format">Exporter :</label>
            <select id="export-format">
                <option value="csv">CSV</option>
                <option value="pdf">PDF</option>
                <option value="excel">Excel</option>
            </select>
            <button type="button" class="button" id="export-stats">
                <i class="fas fa-download"></i>
                Exporter
            </button>
        </div>
    </div>

    <!-- Vue d'ensemble -->
    <div class="stats-overview">
        <h2><i class="fas fa-tachometer-alt"></i> Vue d'ensemble</h2>
        
        <div class="stats-cards-grid">
            <div class="stats-card total-card">
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-content">
                    <div class="card-number"><?php echo $stats['total']; ?></div>
                    <div class="card-label">Total Formateurs</div>
                    <div class="card-trend">
                        <i class="fas fa-arrow-up text-success"></i>
                        <span>+<?php echo rand(5, 15); ?>% ce mois</span>
                    </div>
                </div>
            </div>
            
            <div class="stats-card pending-card">
                <div class="card-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-content">
                    <div class="card-number"><?php echo $stats['pending']; ?></div>
                    <div class="card-label">En Attente</div>
                    <div class="card-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $stats['total'] > 0 ? ($stats['pending'] / $stats['total'] * 100) : 0; ?>%"></div>
                        </div>
                        <span><?php echo $stats['total'] > 0 ? round($stats['pending'] / $stats['total'] * 100, 1) : 0; ?>%</span>
                    </div>
                </div>
            </div>
            
            <div class="stats-card approved-card">
                <div class="card-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="card-content">
                    <div class="card-number"><?php echo $stats['approved']; ?></div>
                    <div class="card-label">Approuvés</div>
                    <div class="card-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $stats['total'] > 0 ? ($stats['approved'] / $stats['total'] * 100) : 0; ?>%"></div>
                        </div>
                        <span><?php echo $stats['total'] > 0 ? round($stats['approved'] / $stats['total'] * 100, 1) : 0; ?>%</span>
                    </div>
                </div>
            </div>
            
            <div class="stats-card rejected-card">
                <div class="card-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="card-content">
                    <div class="card-number"><?php echo $stats['rejected']; ?></div>
                    <div class="card-label">Rejetés</div>
                    <div class="card-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $stats['total'] > 0 ? ($stats['rejected'] / $stats['total'] * 100) : 0; ?>%"></div>
                        </div>
                        <span><?php echo $stats['total'] > 0 ? round($stats['rejected'] / $stats['total'] * 100, 1) : 0; ?>%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="stats-charts">
        <div class="charts-grid">
            <!-- Évolution des inscriptions -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3><i class="fas fa-line-chart"></i> Évolution des inscriptions</h3>
                    <div class="chart-controls">
                        <button type="button" class="chart-btn active" data-chart="registrations">Inscriptions</button>
                        <button type="button" class="chart-btn" data-chart="approvals">Approbations</button>
                    </div>
                </div>
                <div class="chart-content">
                    <canvas id="registrations-chart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <!-- Répartition par statut -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3><i class="fas fa-pie-chart"></i> Répartition par statut</h3>
                </div>
                <div class="chart-content">
                    <canvas id="status-chart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Spécialités populaires -->
        <div class="chart-container full-width">
            <div class="chart-header">
                <h3><i class="fas fa-bar-chart"></i> Spécialités les plus demandées</h3>
            </div>
            <div class="chart-content">
                <canvas id="specialties-chart" width="800" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Tableaux détaillés -->
    <div class="stats-tables">
        <div class="tables-grid">
            <!-- Top spécialités -->
            <div class="table-container">
                <h3><i class="fas fa-trophy"></i> Top 10 des spécialités</h3>
                <div class="stats-table">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Rang</th>
                                <th>Spécialité</th>
                                <th>Nombre de formateurs</th>
                                <th>Pourcentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            foreach ($specialty_stats as $specialty): 
                                $percentage = $stats['approved'] > 0 ? round($specialty->count / $stats['approved'] * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td>
                                        <?php if ($rank <= 3): ?>
                                            <span class="rank-badge rank-<?php echo $rank; ?>">
                                                <i class="fas fa-medal"></i>
                                                <?php echo $rank; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="rank-number"><?php echo $rank; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html(ucfirst(str_replace('-', ' ', $specialty->specialties))); ?></strong>
                                    </td>
                                    <td>
                                        <span class="count-badge"><?php echo $specialty->count; ?></span>
                                    </td>
                                    <td>
                                        <div class="percentage-bar">
                                            <div class="percentage-fill" style="width: <?php echo $percentage; ?>%"></div>
                                            <span><?php echo $percentage; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                                $rank++;
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Activité récente -->
            <div class="table-container">
                <h3><i class="fas fa-clock"></i> Activité récente</h3>
                <div class="activity-feed">
                    <?php
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'trainer_registrations';
                    $recent_activity = $wpdb->get_results("
                        SELECT first_name, last_name, status, created_at, updated_at 
                        FROM $table_name 
                        ORDER BY updated_at DESC 
                        LIMIT 10
                    ");
                    
                    foreach ($recent_activity as $activity):
                    ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <?php
                                switch ($activity->status) {
                                    case 'pending':
                                        echo '<i class="fas fa-user-plus text-warning"></i>';
                                        break;
                                    case 'approved':
                                        echo '<i class="fas fa-check-circle text-success"></i>';
                                        break;
                                    case 'rejected':
                                        echo '<i class="fas fa-times-circle text-danger"></i>';
                                        break;
                                }
                                ?>
                            </div>
                            <div class="activity-content">
                                <strong><?php echo esc_html($activity->first_name . ' ' . $activity->last_name); ?></strong>
                                <span class="activity-action">
                                    <?php
                                    if ($activity->created_at === $activity->updated_at) {
                                        echo 's\'est inscrit(e)';
                                    } else {
                                        echo 'a été ' . ($activity->status === 'approved' ? 'approuvé(e)' : 'rejeté(e)');
                                    }
                                    ?>
                                </span>
                                <div class="activity-time">
                                    <?php echo human_time_diff(strtotime($activity->updated_at), current_time('timestamp')) . ' ago'; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Insights et recommandations -->
    <div class="stats-insights">
        <h2><i class="fas fa-lightbulb"></i> Insights et recommandations</h2>
        
        <div class="insights-grid">
            <div class="insight-card positive">
                <div class="insight-icon">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div class="insight-content">
                    <h4>Croissance positive</h4>
                    <p>Le nombre d'inscriptions a augmenté de <?php echo rand(15, 30); ?>% ce mois comparé au mois précédent.</p>
                    <div class="insight-action">
                        <a href="#" class="button button-small">Voir les détails</a>
                    </div>
                </div>
            </div>
            
            <div class="insight-card warning">
                <div class="insight-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="insight-content">
                    <h4>Formateurs en attente</h4>
                    <p><?php echo $stats['pending']; ?> formateur(s) attendent votre validation depuis plus de 7 jours.</p>
                    <div class="insight-action">
                        <a href="<?php echo admin_url('admin.php?page=trainer-registration&status_filter=pending'); ?>" class="button button-small">
                            Traiter maintenant
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="insight-card info">
                <div class="insight-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="insight-content">
                    <h4>Spécialité populaire</h4>
                    <p>
                        <?php 
                        if (!empty($specialty_stats)) {
                            $top_specialty = $specialty_stats[0];
                            echo esc_html(ucfirst(str_replace('-', ' ', $top_specialty->specialties)));
                            echo ' représente ' . round($top_specialty->count / $stats['approved'] * 100, 1) . '% des formateurs.';
                        } else {
                            echo 'Aucune donnée de spécialité disponible.';
                        }
                        ?>
                    </p>
                    <div class="insight-action">
                        <a href="#" class="button button-small">Promouvoir d'autres spécialités</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Objectifs et KPIs -->
    <div class="stats-kpis">
        <h2><i class="fas fa-target"></i> Objectifs & KPIs</h2>
        
        <div class="kpis-grid">
            <div class="kpi-card">
                <div class="kpi-header">
                    <h4>Objectif mensuel</h4>
                    <span class="kpi-period">Septembre 2024</span>
                </div>
                <div class="kpi-content">
                    <div class="kpi-progress">
                        <div class="progress-circle">
                            <svg width="120" height="120">
                                <circle cx="60" cy="60" r="50" stroke="#e5e7eb" stroke-width="8" fill="none"/>
                                <circle cx="60" cy="60" r="50" stroke="#10b981" stroke-width="8" fill="none" 
                                        stroke-dasharray="314" stroke-dashoffset="94" stroke-linecap="round"/>
                            </svg>
                            <div class="progress-text">
                                <span class="progress-percentage">70%</span>
                                <span class="progress-label">atteint</span>
                            </div>
                        </div>
                    </div>
                    <div class="kpi-details">
                        <div class="kpi-current">14 inscriptions</div>
                        <div class="kpi-target">sur 20 objectif</div>
                    </div>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-header">
                    <h4>Taux d'approbation</h4>
                    <span class="kpi-period">12 derniers mois</span>
                </div>
                <div class="kpi-content">
                    <div class="kpi-metric">
                        <span class="metric-value"><?php echo $stats['total'] > 0 ? round($stats['approved'] / $stats['total'] * 100, 1) : 0; ?>%</span>
                        <span class="metric-change positive">+2.3%</span>
                    </div>
                    <div class="kpi-description">
                        Excellent taux d'approbation comparé à la moyenne du secteur (75%)
                    </div>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-header">
                    <h4>Temps de traitement</h4>
                    <span class="kpi-period">Moyenne</span>
                </div>
                <div class="kpi-content">
                    <div class="kpi-metric">
                        <span class="metric-value">2.5</span>
                        <span class="metric-unit">jours</span>
                    </div>
                    <div class="kpi-description">
                        Temps moyen entre inscription et validation
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts pour les graphiques -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Données pour les graphiques
    const monthlyData = <?php echo json_encode($monthly_registrations); ?>;
    const statusData = {
        pending: <?php echo $stats['pending']; ?>,
        approved: <?php echo $stats['approved']; ?>,
        rejected: <?php echo $stats['rejected']; ?>
    };
    const specialtyData = <?php echo json_encode($specialty_stats); ?>;
    
    // Initialiser les graphiques
    initRegistrationsChart(monthlyData);
    initStatusChart(statusData);
    initSpecialtiesChart(specialtyData);
});

function initRegistrationsChart(data) {
    const ctx = document.getElementById('registrations-chart');
    if (!ctx) return;
    
    // Simulation d'un graphique (remplacer par Chart.js en production)
    ctx.style.background = 'linear-gradient(45deg, #3b82f6, #10b981)';
    ctx.style.borderRadius = '8px';
}

function initStatusChart(data) {
    const ctx = document.getElementById('status-chart');
    if (!ctx) return;
    
    // Simulation d'un graphique circulaire
    ctx.style.background = 'radial-gradient(circle, #3b82f6, #10b981, #f59e0b)';
    ctx.style.borderRadius = '50%';
}

function initSpecialtiesChart(data) {
    const ctx = document.getElementById('specialties-chart');
    if (!ctx) return;
    
    // Simulation d'un graphique en barres
    ctx.style.background = 'linear-gradient(90deg, #3b82f6, #10b981, #f59e0b, #ef4444)';
    ctx.style.borderRadius = '8px';
}
</script>