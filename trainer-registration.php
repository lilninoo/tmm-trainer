<?php
/**
 * Plugin Name: Trainer Registration Pro
 * Plugin URI: https://yoursite.com/trainer-registration-pro
 * Description: Plugin pour gérer les inscriptions des formateurs IT avec conformité RGPD
 * Version: 1.2.0
 * Author: Votre Nom
 * License: GPL v2 or later
 * Text Domain: trainer-registration-pro
 */

// Sécurité - Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Constantes du plugin
define('TRAINER_REGISTRATION_VERSION', '1.2.0');
define('TRAINER_REGISTRATION_DB_VERSION', '1.2');
define('TRAINER_REGISTRATION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TRAINER_REGISTRATION_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('TRAINER_REGISTRATION_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Classe principale du plugin
 */
class TrainerRegistrationPlugin {

    private static $instance = null;
    private $admin = null;
    private $public = null;
    private $shortcodes = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Hooks d'activation/désactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Initialisation du plugin
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // ✅ CORRECTION : Migration automatique au chargement si nécessaire
        add_action('admin_init', array($this, 'maybe_migrate_database'));
    }

    public function init() {
        // Charger les dépendances
        $this->load_dependencies();
        
        // Initialiser les classes
        if (class_exists('TrainerRegistrationPublic')) {
            $this->public = new TrainerRegistrationPublic();
        }
        
        if (class_exists('TrainerRegistrationShortcodes')) {
            $this->shortcodes = new TrainerRegistrationShortcodes();
        }
        
        if (is_admin() && class_exists('TrainerRegistrationAdmin')) {
            $this->admin = new TrainerRegistrationAdmin();
        }
        
        // Hooks additionnels
        add_filter('upload_mimes', array($this, 'add_upload_mimes'));
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'trainer-registration-pro',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    private function load_dependencies() {
        // Classes principales
        $required_files = array(
            'includes/class-trainer-registration-admin.php',
            'includes/class-trainer-registration-public.php',
            'includes/class-trainer-registration-shortcodes.php',
            'includes/functions.php'
        );
        
        foreach ($required_files as $file) {
            $file_path = TRAINER_REGISTRATION_PLUGIN_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }

    public function activate() {
        // Créer les tables avec la structure complète
        $this->create_tables();
        
        // Créer les dossiers d'upload
        $this->create_upload_folders();
        
        // Définir les options par défaut
        $this->set_default_options();
        
        // ✅ CORRECTION : Migration seulement lors de l'activation
        $this->check_and_migrate_database();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
        
        // ✅ CORRECTION : Nettoyer les événements programmés
        wp_clear_scheduled_hook('trpro_gdpr_cleanup');
    }

    /**
     * ✅ CORRECTION : Structure de table COMPLÈTE avec tous les champs
     */
    private function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
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
            experience text NOT NULL,
            cv_file varchar(255) NOT NULL,
            photo_file varchar(255),
            linkedin_url varchar(255),
            bio text,
            availability varchar(50),
            hourly_rate varchar(20),
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
            KEY experience_level (experience_level)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // ✅ Vérifier que la table a été créée correctement
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if ($table_exists) {
            error_log('✅ Table trainer_registrations créée/mise à jour avec succès');
        } else {
            error_log('❌ Erreur lors de la création de la table: ' . $wpdb->last_error);
        }
    }
    
    /**
     * ✅ CORRECTION : Migration robuste avec gestion des versions
     */
    private function check_and_migrate_database() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        $current_version = get_option('trainer_registration_db_version', '1.0');
        $target_version = TRAINER_REGISTRATION_DB_VERSION;
        
        // ✅ Éviter les migrations répétées
        if (version_compare($current_version, $target_version, '>=')) {
            return true;
        }
        
        // Vérifier si la table existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            error_log('❌ Table trainer_registrations n\'existe pas pour la migration');
            return false;
        }
        
        $migrations_applied = 0;
        
        // ✅ Migration vers version 1.1 - Ajout intervention_regions
        if (version_compare($current_version, '1.1', '<')) {
            $column_exists = $wpdb->get_results(
                "SHOW COLUMNS FROM $table_name LIKE 'intervention_regions'"
            );
            
            if (empty($column_exists)) {
                $result = $wpdb->query(
                    "ALTER TABLE $table_name ADD COLUMN intervention_regions TEXT AFTER specialties"
                );
                
                if ($result !== false) {
                    $migrations_applied++;
                    error_log('✅ Migration 1.1: intervention_regions ajouté');
                } else {
                    error_log('❌ Erreur migration 1.1: ' . $wpdb->last_error);
                    return false;
                }
            }
        }
        
        // ✅ Migration vers version 1.2 - Ajout experience_level
        if (version_compare($current_version, '1.2', '<')) {
            $column_exists = $wpdb->get_results(
                "SHOW COLUMNS FROM $table_name LIKE 'experience_level'"
            );
            
            if (empty($column_exists)) {
                $result = $wpdb->query(
                    "ALTER TABLE $table_name 
                     ADD COLUMN experience_level VARCHAR(20) DEFAULT 'intermediaire' 
                     AFTER intervention_regions"
                );
                
                if ($result !== false) {
                    $migrations_applied++;
                    error_log('✅ Migration 1.2: experience_level ajouté');
                    
                    // ✅ Mettre à jour les enregistrements existants intelligemment
                    $updated = $wpdb->query("
                        UPDATE $table_name 
                        SET experience_level = CASE 
                            WHEN CHAR_LENGTH(experience) < 200 THEN 'junior'
                            WHEN CHAR_LENGTH(experience) < 500 THEN 'intermediaire' 
                            WHEN CHAR_LENGTH(experience) < 1000 THEN 'senior'
                            ELSE 'expert'
                        END 
                        WHERE experience_level = 'intermediaire' OR experience_level IS NULL
                    ");
                    
                    if ($updated !== false) {
                        error_log("✅ {$updated} enregistrements mis à jour avec experience_level");
                    }
                } else {
                    error_log('❌ Erreur migration 1.2: ' . $wpdb->last_error);
                    return false;
                }
            }
        }
        
        // ✅ Mettre à jour la version seulement si des migrations ont été appliquées
        if ($migrations_applied > 0 || version_compare($current_version, $target_version, '<')) {
            update_option('trainer_registration_db_version', $target_version);
            error_log("✅ Version DB mise à jour vers {$target_version} ({$migrations_applied} migrations appliquées)");
        }
        
        return true;
    }
    
    /**
     * ✅ CORRECTION : Migration conditionnelle en admin seulement
     */
    public function maybe_migrate_database() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $current_version = get_option('trainer_registration_db_version', '1.0');
        $target_version = TRAINER_REGISTRATION_DB_VERSION;
        
        // ✅ Migration seulement si nécessaire
        if (version_compare($current_version, $target_version, '<')) {
            $this->check_and_migrate_database();
        }
    }

    private function create_upload_folders() {
        $upload_dir = wp_upload_dir();
        $folders = array(
            '/trainer-files/',
            '/trainer-files/cv/',
            '/trainer-files/photos/',
            '/trainer-files/exports/' // ✅ AJOUT : Dossier pour exports RGPD
        );
        
        foreach ($folders as $folder) {
            $dir_path = $upload_dir['basedir'] . $folder;
            
            if (!file_exists($dir_path)) {
                wp_mkdir_p($dir_path);
                
                // ✅ Sécurité renforcée
                $htaccess_content = "Options -Indexes\nOrder deny,allow\nDeny from all\n";
                if ($folder === '/trainer-files/exports/') {
                    $htaccess_content .= "# Exports RGPD - Accès admin uniquement\n";
                }
                
                file_put_contents($dir_path . '.htaccess', $htaccess_content);
                file_put_contents($dir_path . 'index.php', '<?php // Silence is golden');
            }
        }
    }

    private function set_default_options() {
        $defaults = array(
            'trainer_auto_approve' => 0,
            'trainer_require_photo' => 0,
            'trainer_max_cv_size' => 5, // MB
            'trainer_max_photo_size' => 2, // MB
            'trainer_notification_email' => get_option('admin_email'),
            'trainer_notify_new_registration' => 1,
            'trainer_contact_email' => get_option('admin_email'),
            'trainer_contact_phone' => '',
            'trainer_company_name' => get_bloginfo('name'),
            'trainer_data_retention' => 3, // ✅ AJOUT : Rétention RGPD
            'trainer_enable_anonymization' => 1 // ✅ AJOUT : Anonymisation
        );

        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }

    public function add_upload_mimes($mimes) {
        // Ajouter les types MIME pour les CV
        $mimes['doc'] = 'application/msword';
        $mimes['docx'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        
        return $mimes;
    }
}

// ✅ CORRECTION : Initialisation sécurisée
add_action('plugins_loaded', function() {
    TrainerRegistrationPlugin::get_instance();
});

/**
 * ✅ CORRECTION : Migration manuelle sécurisée
 */
function trainer_registration_manual_migration() {
    if (!current_user_can('manage_options')) {
        wp_die('Permissions insuffisantes');
    }
    
    check_ajax_referer('trainer_migration_nonce', 'nonce');
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'trainer_registrations';
    
    $results = array();
    
    // Vérifier intervention_regions
    $column_exists = $wpdb->get_results(
        "SHOW COLUMNS FROM $table_name LIKE 'intervention_regions'"
    );
    
    if (empty($column_exists)) {
        $result = $wpdb->query(
            "ALTER TABLE $table_name ADD COLUMN intervention_regions TEXT AFTER specialties"
        );
        
        $results[] = $result !== false 
            ? '✅ Champ intervention_regions ajouté avec succès !' 
            : '❌ Erreur lors de l\'ajout d\'intervention_regions : ' . $wpdb->last_error;
    } else {
        $results[] = 'ℹ️ Le champ intervention_regions existe déjà.';
    }
    
    // Vérifier experience_level
    $column_exists = $wpdb->get_results(
        "SHOW COLUMNS FROM $table_name LIKE 'experience_level'"
    );
    
    if (empty($column_exists)) {
        $result = $wpdb->query(
            "ALTER TABLE $table_name ADD COLUMN experience_level VARCHAR(20) DEFAULT 'intermediaire' AFTER intervention_regions"
        );
        
        $results[] = $result !== false 
            ? '✅ Champ experience_level ajouté avec succès !' 
            : '❌ Erreur lors de l\'ajout d\'experience_level : ' . $wpdb->last_error;
    } else {
        $results[] = 'ℹ️ Le champ experience_level existe déjà.';
    }
    
    // Mettre à jour la version
    update_option('trainer_registration_db_version', TRAINER_REGISTRATION_DB_VERSION);
    
    wp_send_json_success(array(
        'message' => 'Migration terminée',
        'results' => $results
    ));
}

// ✅ Hook AJAX sécurisé
add_action('wp_ajax_trainer_manual_migration', 'trainer_registration_manual_migration');

/**
 * ✅ CORRECTION : Notification admin si migration nécessaire
 */
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    
    $current_version = get_option('trainer_registration_db_version', '1.0');
    $target_version = TRAINER_REGISTRATION_DB_VERSION;
    
    if (version_compare($current_version, $target_version, '<')) {
        ?>
        <div class="notice notice-warning is-dismissible" id="trainer-migration-notice">
            <p>
                <strong>Trainer Registration Pro:</strong> 
                Mise à jour de la base de données requise (v<?php echo $current_version; ?> → v<?php echo $target_version; ?>).
                <button type="button" class="button button-primary" id="run-trainer-migration">
                    Mettre à jour maintenant
                </button>
                <span class="spinner" style="float: none; margin: 0 0 0 10px;"></span>
            </p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#run-trainer-migration').on('click', function() {
                var $button = $(this);
                var $spinner = $button.siblings('.spinner');
                var $notice = $('#trainer-migration-notice');
                
                $button.prop('disabled', true);
                $spinner.addClass('is-active');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'trainer_manual_migration',
                        nonce: '<?php echo wp_create_nonce('trainer_migration_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $notice.removeClass('notice-warning').addClass('notice-success');
                            $notice.find('p').html('<strong>✅ Migration réussie !</strong> La base de données a été mise à jour.');
                            setTimeout(function() {
                                $notice.fadeOut();
                            }, 3000);
                        } else {
                            alert('Erreur : ' + (response.data || 'Migration échouée'));
                        }
                    },
                    error: function() {
                        alert('Erreur de connexion lors de la migration');
                    },
                    complete: function() {
                        $spinner.removeClass('is-active');
                        $button.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }
});
?>