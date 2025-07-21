<?php
/**
 * Script de migration de la base de données - CORRIGÉ avec experience_level
 * 
 * Fichier: includes/migration-database.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class TrainerRegistrationMigration {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Effectuer toutes les migrations nécessaires
     */
    public function run_migrations() {
        $current_version = get_option('trainer_registration_db_version', '1.0.0');
        
        error_log('Trainer Registration Pro: Migration démarrée. Version actuelle: ' . $current_version);
        
        // Migration 1.1.0 : Ajout des régions d'intervention
        if (version_compare($current_version, '1.1.0', '<')) {
            $this->migrate_to_1_1_0();
        }
        
        // ✅ NOUVEAU : Migration 1.2.0 : Ajout du niveau d'expérience
        if (version_compare($current_version, '1.2.0', '<')) {
            $this->migrate_to_1_2_0();
        }
        
        // Marquer comme migré
        update_option('trainer_registration_db_version', TRAINER_REGISTRATION_VERSION);
        
        error_log('Trainer Registration Pro: Migration terminée vers la version ' . TRAINER_REGISTRATION_VERSION);
    }
    
    /**
     * Migration vers la version 1.1.0
     * - Ajout de la colonne intervention_regions
     * - Ajout de la colonne hourly_rate si manquante
     * - Ajout de la colonne bio si manquante
     * - Migration des données existantes
     */
    private function migrate_to_1_1_0() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        error_log('Trainer Registration Pro: Migration vers 1.1.0 - Ajout des colonnes');
        
        // Vérifier l'existence de la table
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            error_log('Trainer Registration Pro: Table principale non trouvée, création...');
            $this->create_main_table();
            return;
        }
        
        // 1. Ajouter la colonne intervention_regions
        if (!$this->column_exists($table_name, 'intervention_regions')) {
            $result = $wpdb->query("ALTER TABLE $table_name ADD COLUMN intervention_regions TEXT AFTER specialties");
            
            if ($result === false) {
                error_log('Trainer Registration Pro: Erreur lors de l\'ajout de la colonne intervention_regions: ' . $wpdb->last_error);
            } else {
                error_log('Trainer Registration Pro: Colonne intervention_regions ajoutée avec succès');
                
                // Ajouter un index pour les recherches
                $wpdb->query("ALTER TABLE $table_name ADD INDEX idx_intervention_regions (intervention_regions(100))");
            }
        }
        
        // 2. Ajouter la colonne hourly_rate si manquante
        if (!$this->column_exists($table_name, 'hourly_rate')) {
            $result = $wpdb->query("ALTER TABLE $table_name ADD COLUMN hourly_rate varchar(20) AFTER availability");
            
            if ($result === false) {
                error_log('Trainer Registration Pro: Erreur lors de l\'ajout de la colonne hourly_rate: ' . $wpdb->last_error);
            } else {
                error_log('Trainer Registration Pro: Colonne hourly_rate ajoutée avec succès');
            }
        }
        
        // 3. Ajouter la colonne bio si manquante
        if (!$this->column_exists($table_name, 'bio')) {
            $result = $wpdb->query("ALTER TABLE $table_name ADD COLUMN bio TEXT AFTER experience");
            
            if ($result === false) {
                error_log('Trainer Registration Pro: Erreur lors de l\'ajout de la colonne bio: ' . $wpdb->last_error);
            } else {
                error_log('Trainer Registration Pro: Colonne bio ajoutée avec succès');
            }
        }
        
        // 4. Migrer les données existantes
        $this->migrate_existing_trainer_data();
        
        // 5. Optimiser la table
        $wpdb->query("OPTIMIZE TABLE $table_name");
        
        error_log('Trainer Registration Pro: Migration vers 1.1.0 terminée');
    }
    
    /**
     * ✅ NOUVEAU : Migration vers la version 1.2.0
     * - Ajout de la colonne experience_level
     * - Migration des données existantes basées sur l'expérience
     */
    private function migrate_to_1_2_0() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        error_log('Trainer Registration Pro: Migration vers 1.2.0 - Ajout du niveau d\'expérience');
        
        // Vérifier l'existence de la table
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            error_log('Trainer Registration Pro: Table principale non trouvée, création...');
            $this->create_main_table();
            return;
        }
        
        // 1. Ajouter la colonne experience_level
        if (!$this->column_exists($table_name, 'experience_level')) {
            $result = $wpdb->query("ALTER TABLE $table_name ADD COLUMN experience_level varchar(20) DEFAULT 'intermediaire' AFTER intervention_regions");
            
            if ($result === false) {
                error_log('Trainer Registration Pro: Erreur lors de l\'ajout de la colonne experience_level: ' . $wpdb->last_error);
            } else {
                error_log('Trainer Registration Pro: Colonne experience_level ajoutée avec succès');
                
                // Ajouter un index pour les recherches
                $wpdb->query("ALTER TABLE $table_name ADD INDEX idx_experience_level (experience_level)");
                
                // Migrer les niveaux d'expérience basés sur le texte existant
                $this->migrate_experience_levels();
            }
        }
        
        // 2. Optimiser la table
        $wpdb->query("OPTIMIZE TABLE $table_name");
        
        error_log('Trainer Registration Pro: Migration vers 1.2.0 terminée');
    }
    
    /**
     * ✅ NOUVEAU : Migrer les niveaux d'expérience basés sur le texte
     */
    private function migrate_experience_levels() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        // Récupérer tous les formateurs avec leur expérience
        $trainers = $wpdb->get_results("SELECT id, experience FROM $table_name WHERE experience_level IS NULL OR experience_level = ''");
        
        foreach ($trainers as $trainer) {
            $experience_text = strtolower($trainer->experience);
            $experience_level = 'intermediaire'; // Par défaut
            
            // Analyser le texte pour déterminer le niveau
            if (preg_match('/\b(15|16|17|18|19|20|\d{2})\s*(ans?|années?)\b/', $experience_text, $matches)) {
                $years = intval($matches[1]);
                if ($years >= 15) {
                    $experience_level = 'expert';
                } elseif ($years >= 7) {
                    $experience_level = 'senior';
                } elseif ($years >= 3) {
                    $experience_level = 'intermediaire';
                } else {
                    $experience_level = 'junior';
                }
            } elseif (preg_match('/\b([5-9]|1[0-4])\s*(ans?|années?)\b/', $experience_text, $matches)) {
                $years = intval($matches[1]);
                if ($years >= 7) {
                    $experience_level = 'senior';
                } else {
                    $experience_level = 'intermediaire';
                }
            } elseif (preg_match('/\b([1-2])\s*(ans?|années?)\b/', $experience_text)) {
                $experience_level = 'junior';
            } elseif (preg_match('/(expert|expertise|experte?|senior|lead|architect|directeur|manager|chef)/i', $experience_text)) {
                $experience_level = 'expert';
            } elseif (preg_match('/(junior|débutant|commence|début|apprenti)/i', $experience_text)) {
                $experience_level = 'junior';
            }
            
            // Mettre à jour le niveau
            $wpdb->update(
                $table_name,
                array('experience_level' => $experience_level),
                array('id' => $trainer->id),
                array('%s'),
                array('%d')
            );
        }
        
        error_log('Trainer Registration Pro: Migration des niveaux d\'expérience terminée pour ' . count($trainers) . ' formateur(s)');
    }
    
    /**
     * Vérifier si une colonne existe dans une table
     */
    private function column_exists($table_name, $column_name) {
        global $wpdb;
        
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s 
             AND TABLE_NAME = %s 
             AND COLUMN_NAME = %s",
            DB_NAME,
            $table_name,
            $column_name
        ));
        
        return !empty($column_exists);
    }
    
    /**
     * ✅ CORRIGÉ : Créer la table principale avec toutes les colonnes (y compris experience_level)
     */
    private function create_main_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(50) NOT NULL,
            company varchar(200),
            specialties text NOT NULL,
            intervention_regions text,
            experience_level varchar(20) DEFAULT 'intermediaire',
            availability varchar(50),
            hourly_rate varchar(20),
            experience text NOT NULL,
            bio text,
            cv_file varchar(255) NOT NULL,
            photo_file varchar(255),
            linkedin_url varchar(255),
            rgpd_consent tinyint(1) NOT NULL DEFAULT 0,
            marketing_consent tinyint(1) NOT NULL DEFAULT 0,
            status varchar(20) DEFAULT 'pending',
            admin_notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY status (status),
            KEY created_at (created_at),
            KEY specialties (specialties(100)),
            KEY intervention_regions (intervention_regions(100)),
            KEY experience_level (experience_level)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('Trainer Registration Pro: Table principale créée avec toutes les colonnes');
    }
    
    /**
     * Migrer les données des formateurs existants
     */
    private function migrate_existing_trainer_data() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        // Compter les formateurs sans régions d'intervention
        $trainers_without_regions = $wpdb->get_var("
            SELECT COUNT(*) FROM $table_name 
            WHERE (intervention_regions IS NULL OR intervention_regions = '')
        ");
        
        if ($trainers_without_regions > 0) {
            error_log("Trainer Registration Pro: $trainers_without_regions formateur(s) sans région d'intervention détecté(s)");
            
            // Assigner des régions par défaut basées sur des heuristiques
            $this->assign_default_regions();
        }
        
        // Nettoyer les données inconsistantes
        $this->clean_inconsistent_data();
    }
    
    /**
     * Assigner des régions par défaut aux formateurs existants
     */
    private function assign_default_regions() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        // 1. Formateurs avec des spécialités cloud/distanciel -> Formation à distance
        $cloud_trainers = $wpdb->query("
            UPDATE $table_name 
            SET intervention_regions = 'distanciel,ile-de-france' 
            WHERE (intervention_regions IS NULL OR intervention_regions = '') 
            AND (specialties LIKE '%cloud%' OR specialties LIKE '%devops%')
        ");
        
        if ($cloud_trainers > 0) {
            error_log("Trainer Registration Pro: $cloud_trainers formateur(s) cloud/devops mis à jour avec région distanciel");
        }
        
        // 2. Autres formateurs -> Île-de-France + distanciel
        $other_trainers = $wpdb->query("
            UPDATE $table_name 
            SET intervention_regions = 'ile-de-france,distanciel' 
            WHERE (intervention_regions IS NULL OR intervention_regions = '')
        ");
        
        if ($other_trainers > 0) {
            error_log("Trainer Registration Pro: $other_trainers autre(s) formateur(s) mis à jour avec régions par défaut");
        }
        
        $total_updated = $cloud_trainers + $other_trainers;
        
        // Log du résultat
        if ($total_updated > 0) {
            error_log("Trainer Registration Pro: Total de $total_updated formateur(s) mis à jour avec des régions d'intervention");
        }
    }
    
    /**
     * Nettoyer les données inconsistantes
     */
    private function clean_inconsistent_data() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        // 1. Normaliser les spécialités (retirer les espaces en trop)
        $wpdb->query("
            UPDATE $table_name 
            SET specialties = TRIM(REPLACE(REPLACE(specialties, '  ', ' '), ', ,', ','))
            WHERE specialties LIKE '%  %' OR specialties LIKE '%, ,%'
        ");
        
        // 2. Normaliser les régions (retirer les espaces en trop)
        $wpdb->query("
            UPDATE $table_name 
            SET intervention_regions = TRIM(REPLACE(REPLACE(intervention_regions, '  ', ' '), ', ,', ','))
            WHERE intervention_regions IS NOT NULL 
            AND (intervention_regions LIKE '%  %' OR intervention_regions LIKE '%, ,%')
        ");
        
        // 3. Corriger les statuts invalides
        $wpdb->query("
            UPDATE $table_name 
            SET status = 'pending' 
            WHERE status NOT IN ('pending', 'approved', 'rejected', 'suspended')
        ");
        
        // 4. Mettre à jour les timestamps manquants
        $wpdb->query("
            UPDATE $table_name 
            SET updated_at = created_at 
            WHERE updated_at IS NULL OR updated_at = '0000-00-00 00:00:00'
        ");
        
        // ✅ NOUVEAU : 5. Normaliser les niveaux d'expérience
        $wpdb->query("
            UPDATE $table_name 
            SET experience_level = 'intermediaire' 
            WHERE experience_level IS NULL 
            OR experience_level = '' 
            OR experience_level NOT IN ('junior', 'intermediaire', 'senior', 'expert')
        ");
        
        error_log('Trainer Registration Pro: Nettoyage des données inconsistantes terminé');
    }
    
    /**
     * Vérifier l'intégrité des données après migration
     */
    public function verify_migration() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        $issues = array();
        
        // Vérifier que toutes les colonnes existent
        $required_columns = array(
            'intervention_regions',
            'hourly_rate',
            'bio',
            'experience_level' // ✅ NOUVEAU
        );
        
        foreach ($required_columns as $column) {
            if (!$this->column_exists($table_name, $column)) {
                $issues[] = "Colonne manquante: $column";
            }
        }
        
        // Vérifier les formateurs sans régions
        $trainers_without_regions = $wpdb->get_var("
            SELECT COUNT(*) FROM $table_name 
            WHERE (intervention_regions IS NULL OR intervention_regions = '') 
            AND status = 'approved'
        ");
        
        if ($trainers_without_regions > 0) {
            $issues[] = "$trainers_without_regions formateur(s) approuvé(s) sans région d'intervention";
        }
        
        // ✅ NOUVEAU : Vérifier les formateurs sans niveau d'expérience
        $trainers_without_level = $wpdb->get_var("
            SELECT COUNT(*) FROM $table_name 
            WHERE (experience_level IS NULL OR experience_level = '') 
            AND status = 'approved'
        ");
        
        if ($trainers_without_level > 0) {
            $issues[] = "$trainers_without_level formateur(s) approuvé(s) sans niveau d'expérience";
        }
        
        // Vérifier les doublons d'email
        $duplicate_emails = $wpdb->get_var("
            SELECT COUNT(*) - COUNT(DISTINCT email) FROM $table_name
        ");
        
        if ($duplicate_emails > 0) {
            $issues[] = "$duplicate_emails email(s) en doublon";
        }
        
        // Log des résultats
        if (empty($issues)) {
            error_log('Trainer Registration Pro: Vérification de migration - Aucun problème détecté');
        } else {
            error_log('Trainer Registration Pro: Problèmes détectés après migration: ' . implode(', ', $issues));
        }
        
        return $issues;
    }
    
    /**
     * Rollback de la migration (si nécessaire)
     */
    public function rollback_migration() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        error_log('Trainer Registration Pro: Rollback de la migration demandé');
        
        // Sauvegarder les données avant rollback
        $backup_table = $table_name . '_backup_' . date('Y_m_d_H_i_s');
        $wpdb->query("CREATE TABLE $backup_table AS SELECT * FROM $table_name");
        
        // Supprimer les colonnes ajoutées (attention : perte de données)
        $columns_to_remove = array('intervention_regions', 'hourly_rate', 'bio', 'experience_level');
        
        foreach ($columns_to_remove as $column) {
            if ($this->column_exists($table_name, $column)) {
                $wpdb->query("ALTER TABLE $table_name DROP COLUMN $column");
                error_log("Trainer Registration Pro: Colonne $column supprimée");
            }
        }
        
        // Restaurer la version précédente
        update_option('trainer_registration_db_version', '1.0.0');
        
        error_log("Trainer Registration Pro: Rollback terminé. Sauvegarde dans $backup_table");
    }
    
    /**
     * Obtenir des statistiques sur la migration
     */
    public function get_migration_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        $stats = array(
            'total_trainers' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
            'trainers_with_regions' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE intervention_regions IS NOT NULL AND intervention_regions != ''"),
            'trainers_with_rates' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE hourly_rate IS NOT NULL AND hourly_rate != ''"),
            'trainers_with_bio' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE bio IS NOT NULL AND bio != ''"),
            'trainers_with_experience_level' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE experience_level IS NOT NULL AND experience_level != ''"), // ✅ NOUVEAU
            'approved_trainers' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'approved'"),
            'db_version' => get_option('trainer_registration_db_version', '1.0.0')
        );
        
        // Régions les plus populaires
        $popular_regions = $wpdb->get_results("
            SELECT 
                SUBSTRING_INDEX(SUBSTRING_INDEX(intervention_regions, ',', n.digit+1), ',', -1) as region,
                COUNT(*) as count
            FROM $table_name
            CROSS JOIN (
                SELECT 0 as digit UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL 
                SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
            ) n
            WHERE intervention_regions IS NOT NULL 
            AND CHAR_LENGTH(intervention_regions) - CHAR_LENGTH(REPLACE(intervention_regions, ',', '')) >= n.digit
            GROUP BY region
            HAVING region != ''
            ORDER BY count DESC
            LIMIT 10
        ");
        
        $stats['popular_regions'] = $popular_regions;
        
        // ✅ NOUVEAU : Statistiques par niveau d'expérience
        $experience_stats = $wpdb->get_results("
            SELECT experience_level, COUNT(*) as count
            FROM $table_name
            WHERE experience_level IS NOT NULL 
            AND experience_level != ''
            AND status = 'approved'
            GROUP BY experience_level
            ORDER BY count DESC
        ");
        
        $stats['experience_levels'] = $experience_stats;
        
        return $stats;
    }
}

// Fonction d'aide pour exécuter la migration manuellement
function run_trainer_registration_migration() {
    $migration = TrainerRegistrationMigration::get_instance();
    $migration->run_migrations();
    
    // Vérifier la migration
    $issues = $migration->verify_migration();
    
    if (empty($issues)) {
        return array('success' => true, 'message' => 'Migration réussie');
    } else {
        return array('success' => false, 'message' => 'Migration avec problèmes', 'issues' => $issues);
    }
}

// Hook pour exécuter la migration automatiquement lors de l'activation
add_action('plugins_loaded', function() {
    if (is_admin()) {
        $current_version = get_option('trainer_registration_db_version', '1.0.0');
        
        if (version_compare($current_version, TRAINER_REGISTRATION_VERSION, '<')) {
            $migration = TrainerRegistrationMigration::get_instance();
            $migration->run_migrations();
        }
    }
});

// Ajouter une page d'administration pour la migration (debug)
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('admin_menu', function() {
        add_management_page(
            'Migration Trainer Registration',
            'Migration Trainer Registration',
            'manage_options',
            'trainer-migration',
            'trainer_migration_admin_page'
        );
    });
    
    function trainer_migration_admin_page() {
        if (isset($_POST['run_migration'])) {
            $result = run_trainer_registration_migration();
            echo '<div class="notice notice-' . ($result['success'] ? 'success' : 'error') . '"><p>' . $result['message'] . '</p></div>';
            
            if (!empty($result['issues'])) {
                echo '<div class="notice notice-warning"><p>Problèmes détectés:</p><ul>';
                foreach ($result['issues'] as $issue) {
                    echo '<li>' . esc_html($issue) . '</li>';
                }
                echo '</ul></div>';
            }
        }
        
        $migration = TrainerRegistrationMigration::get_instance();
        $stats = $migration->get_migration_stats();
        
        ?>
        <div class="wrap">
            <h1>Migration Trainer Registration Pro</h1>
            
            <h2>Statistiques actuelles</h2>
            <table class="widefat">
                <tr><td>Version BDD</td><td><?php echo $stats['db_version']; ?></td></tr>
                <tr><td>Total formateurs</td><td><?php echo $stats['total_trainers']; ?></td></tr>
                <tr><td>Avec régions</td><td><?php echo $stats['trainers_with_regions']; ?></td></tr>
                <tr><td>Avec tarifs</td><td><?php echo $stats['trainers_with_rates']; ?></td></tr>
                <tr><td>Avec bio</td><td><?php echo $stats['trainers_with_bio']; ?></td></tr>
                <tr><td>Avec niveau d'expérience</td><td><?php echo $stats['trainers_with_experience_level']; ?></td></tr>
                <tr><td>Approuvés</td><td><?php echo $stats['approved_trainers']; ?></td></tr>
            </table>
            
            <h2>Niveaux d'expérience</h2>
            <table class="widefat">
                <?php foreach ($stats['experience_levels'] as $level): ?>
                    <tr>
                        <td><?php echo esc_html(ucfirst($level->experience_level)); ?></td>
                        <td><?php echo $level->count; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            
            <h2>Régions populaires</h2>
            <table class="widefat">
                <?php foreach ($stats['popular_regions'] as $region): ?>
                    <tr>
                        <td><?php echo esc_html(ucwords(str_replace('-', ' ', trim($region->region)))); ?></td>
                        <td><?php echo $region->count; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            
            <form method="post">
                <p class="submit">
                    <input type="submit" name="run_migration" class="button button-primary" value="Exécuter la migration" onclick="return confirm('Êtes-vous sûr ?')">
                </p>
            </form>
        </div>
        <?php
    }
}