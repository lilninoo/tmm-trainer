<?php
/**
 * Template pour la page d'administration principale
 * 
 * Fichier: /wp-content/plugins/trainer-registration-plugin/admin/partials/admin-display.php
 * Variables disponibles: $trainers, $total_trainers, $pending_trainers, $approved_trainers, $rejected_trainers
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap trainer-admin-wrap">
    <h1 class="wp-heading-inline">
        <i class="fas fa-users"></i>
        Gestion des Formateurs IT
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=trainer-registration&action=export'); ?>" class="page-title-action">
        <i class="fas fa-download"></i>
        Exporter CSV
    </a>
    
    <hr class="wp-header-end">

    <!-- Messages de notification -->
    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                switch ($_GET['message']) {
                    case 'approved':
                        echo 'Formateur approuvé avec succès.';
                        break;
                    case 'rejected':
                        echo 'Formateur rejeté.';
                        break;
                    case 'deleted':
                        echo 'Formateur supprimé.';
                        break;
                    default:
                        echo 'Action effectuée avec succès.';
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Statistiques rapides -->
    <div class="trainer-stats-cards">
        <div class="stats-card total">
            <div class="stats-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stats-content">
                <div class="stats-number"><?php echo $total_trainers; ?></div>
                <div class="stats-label">Total Formateurs</div>
            </div>
        </div>
        
        <div class="stats-card pending">
            <div class="stats-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stats-content">
                <div class="stats-number"><?php echo $pending_trainers; ?></div>
                <div class="stats-label">En Attente</div>
            </div>
        </div>
        
        <div class="stats-card approved">
            <div class="stats-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stats-content">
                <div class="stats-number"><?php echo $approved_trainers; ?></div>
                <div class="stats-label">Approuvés</div>
            </div>
        </div>
        
        <div class="stats-card rejected">
            <div class="stats-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stats-content">
                <div class="stats-number"><?php echo $rejected_trainers; ?></div>
                <div class="stats-label">Rejetés</div>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" id="trainer-filter-form">
                <input type="hidden" name="page" value="trainer-registration">
                
                <label for="status-filter" class="screen-reader-text">Filtrer par statut</label>
                <select name="status_filter" id="status-filter">
                    <option value="all" <?php selected($status_filter, 'all'); ?>>Tous les statuts</option>
                    <option value="pending" <?php selected($status_filter, 'pending'); ?>>En attente</option>
                    <option value="approved" <?php selected($status_filter, 'approved'); ?>>Approuvés</option>
                    <option value="rejected" <?php selected($status_filter, 'rejected'); ?>>Rejetés</option>
                </select>
                
                <label for="search-input" class="screen-reader-text">Rechercher</label>
                <input type="search" id="search-input" name="search" value="<?php echo esc_attr($search); ?>" placeholder="Rechercher un formateur...">
                
                <input type="submit" class="button" value="Filtrer">
                
                <?php if (!empty($search) || $status_filter !== 'all'): ?>
                    <a href="<?php echo admin_url('admin.php?page=trainer-registration'); ?>" class="button">
                        <i class="fas fa-times"></i>
                        Réinitialiser
                    </a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="alignright actions">
            <div class="bulk-actions">
                <select id="bulk-action-selector">
                    <option value="-1">Actions groupées</option>
                    <option value="approve">Approuver</option>
                    <option value="reject">Rejeter</option>
                    <option value="delete">Supprimer</option>
                </select>
                <button type="button" id="bulk-action-submit" class="button action">Appliquer</button>
            </div>
        </div>
    </div>

    <!-- Liste des formateurs -->
    <?php if (!empty($trainers)): ?>
        <div class="trainer-list-container">
            <form id="trainers-form">
                <table class="wp-list-table widefat fixed striped trainers">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input id="cb-select-all" type="checkbox">
                            </td>
                            <th scope="col" class="manage-column column-name column-primary">
                                <a href="#" class="sortable">
                                    <span>Formateur</span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                            <th scope="col" class="manage-column column-email">Email</th>
                            <th scope="col" class="manage-column column-specialties">Spécialités</th>
                            <th scope="col" class="manage-column column-status">Statut</th>
                            <th scope="col" class="manage-column column-date">Date d'inscription</th>
                            <th scope="col" class="manage-column column-actions">Actions</th>
                        </tr>
                    </thead>
                    
                    <tbody id="the-list">
                        <?php foreach ($trainers as $trainer): ?>
                            <tr id="trainer-<?php echo $trainer->id; ?>" class="trainer-row" data-status="<?php echo esc_attr($trainer->status); ?>">
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="trainer_ids[]" value="<?php echo $trainer->id; ?>" class="trainer-checkbox">
                                </th>
                                
                                <td class="column-name column-primary" data-colname="Formateur">
                                    <div class="trainer-info">
                                        <div class="trainer-avatar">
                                            <?php if (!empty($trainer->photo_file)): ?>
                                                <?php 
                                                $upload_dir = wp_upload_dir();
                                                $photo_url = $upload_dir['baseurl'] . '/' . $trainer->photo_file;
                                                ?>
                                                <img src="<?php echo esc_url($photo_url); ?>" alt="Photo de profil" width="40" height="40">
                                            <?php else: ?>
                                                <div class="avatar-placeholder">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="trainer-details">
                                            <strong class="trainer-name">
                                                <?php echo esc_html($trainer->first_name . ' ' . $trainer->last_name); ?>
                                            </strong>
                                            <?php if (!empty($trainer->company)): ?>
                                                <div class="trainer-company"><?php echo esc_html($trainer->company); ?></div>
                                            <?php endif; ?>
                                            <div class="trainer-phone"><?php echo esc_html($trainer->phone); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="row-actions">
                                        <span class="view">
                                            <a href="<?php echo admin_url('admin.php?page=trainer-registration&action=view&trainer_id=' . $trainer->id); ?>" 
                                               title="Voir le profil complet">
                                                Voir
                                            </a> |
                                        </span>
                                        
                                        <?php if ($trainer->status === 'pending'): ?>
                                            <span class="approve">
                                                <a href="#" class="trainer-action" data-action="approve" data-trainer-id="<?php echo $trainer->id; ?>" 
                                                   title="Approuver ce formateur">
                                                    Approuver
                                                </a> |
                                            </span>
                                            <span class="reject">
                                                <a href="#" class="trainer-action" data-action="reject" data-trainer-id="<?php echo $trainer->id; ?>" 
                                                   title="Rejeter ce formateur">
                                                    Rejeter
                                                </a> |
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($trainer->cv_file)): ?>
                                            <span class="download">
                                                <a href="<?php echo admin_url('admin-ajax.php?action=download_trainer_file&trainer_id=' . $trainer->id . '&file_type=cv&nonce=' . wp_create_nonce('trainer_admin_nonce')); ?>" 
                                                   title="Télécharger le CV">
                                                    CV
                                                </a> |
                                            </span>
                                        <?php endif; ?>
                                        
                                        <span class="delete">
                                            <a href="#" class="trainer-action delete-trainer" data-action="delete" data-trainer-id="<?php echo $trainer->id; ?>" 
                                               title="Supprimer ce formateur">
                                                Supprimer
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                
                                <td class="column-email" data-colname="Email">
                                    <a href="mailto:<?php echo esc_attr($trainer->email); ?>" title="Envoyer un email">
                                        <?php echo esc_html($trainer->email); ?>
                                    </a>
                                </td>
                                
                                <td class="column-specialties" data-colname="Spécialités">
                                    <div class="specialties-list">
                                        <?php 
                                        $specialties = explode(', ', $trainer->specialties);
                                        foreach ($specialties as $specialty): 
                                            $specialty = trim($specialty);
                                            if (!empty($specialty)):
                                        ?>
                                            <span class="specialty-badge"><?php echo esc_html(ucfirst(str_replace('-', ' ', $specialty))); ?></span>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                </td>
                                
                                <td class="column-status" data-colname="Statut">
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
                                            default:
                                                echo '<i class="fas fa-question-circle"></i> ' . esc_html($trainer->status);
                                        }
                                        ?>
                                    </span>
                                </td>
                                
                                <td class="column-date" data-colname="Date d'inscription">
                                    <span title="<?php echo esc_attr(date_i18n('d/m/Y à H:i', strtotime($trainer->created_at))); ?>">
                                        <?php echo esc_html(date_i18n('d/m/Y', strtotime($trainer->created_at))); ?>
                                    </span>
                                    <?php
                                    $time_diff = human_time_diff(strtotime($trainer->created_at), current_time('timestamp'));
                                    echo '<br><small>Il y a ' . $time_diff . '</small>';
                                    ?>
                                </td>
                                
                                <td class="column-actions" data-colname="Actions">
                                    <div class="action-buttons">
                                        <?php if ($trainer->status === 'pending'): ?>
                                            <button type="button" class="button button-small button-primary trainer-action" 
                                                    data-action="approve" data-trainer-id="<?php echo $trainer->id; ?>" 
                                                    title="Approuver">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="button button-small trainer-action" 
                                                    data-action="reject" data-trainer-id="<?php echo $trainer->id; ?>" 
                                                    title="Rejeter">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <a href="<?php echo admin_url('admin.php?page=trainer-registration&action=view&trainer_id=' . $trainer->id); ?>" 
                                           class="button button-small" title="Voir le profil">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <button type="button" class="button button-small delete-trainer trainer-action" 
                                                data-action="delete" data-trainer-id="<?php echo $trainer->id; ?>" 
                                                title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        </div>
    <?php else: ?>
        <!-- État vide -->
        <div class="trainer-empty-state">
            <div class="empty-icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <h3>Aucun formateur trouvé</h3>
            <p>
                <?php if (!empty($search) || $status_filter !== 'all'): ?>
                    Aucun formateur ne correspond à vos critères de recherche.
                    <br>
                    <a href="<?php echo admin_url('admin.php?page=trainer-registration'); ?>">Voir tous les formateurs</a>
                <?php else: ?>
                    Aucun formateur ne s'est encore inscrit sur votre plateforme.
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Actions rapides -->
    <div class="trainer-quick-actions">
        <div class="quick-action-card">
            <h3><i class="fas fa-plus-circle"></i> Ajouter un formateur</h3>
            <p>Inscrivez manuellement un nouveau formateur expert</p>
            <a href="#" class="button button-primary" id="add-trainer-manually">
                <i class="fas fa-user-plus"></i>
                Ajouter manuellement
            </a>
        </div>
        
        <div class="quick-action-card">
            <h3><i class="fas fa-envelope"></i> Notifications</h3>
            <p>Configurez les notifications automatiques</p>
            <a href="<?php echo admin_url('admin.php?page=trainer-settings'); ?>" class="button">
                <i class="fas fa-cog"></i>
                Paramètres
            </a>
        </div>
        
        <div class="quick-action-card">
            <h3><i class="fas fa-chart-bar"></i> Statistiques</h3>
            <p>Analysez les données de votre plateforme</p>
            <a href="<?php echo admin_url('admin.php?page=trainer-stats'); ?>" class="button">
                <i class="fas fa-analytics"></i>
                Voir les stats
            </a>
        </div>
    </div>
</div>

<!-- Modal de confirmation -->
<div id="trainer-confirm-modal" class="trainer-modal" style="display: none;">
    <div class="trainer-modal-content">
        <div class="trainer-modal-header">
            <h3 id="modal-title">Confirmer l'action</h3>
            <button type="button" class="trainer-modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="trainer-modal-body">
            <p id="modal-message">Êtes-vous sûr de vouloir effectuer cette action ?</p>
        </div>
        <div class="trainer-modal-footer">
            <button type="button" class="button" id="modal-cancel">Annuler</button>
            <button type="button" class="button button-primary" id="modal-confirm">Confirmer</button>
        </div>
    </div>
</div>

<!-- Loading overlay -->
<div id="trainer-loading" class="trainer-loading-overlay" style="display: none;">
    <div class="trainer-loading-content">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Traitement en cours...</p>
    </div>
</div>