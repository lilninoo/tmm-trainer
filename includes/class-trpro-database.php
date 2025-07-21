<?php
/**
 * Classe pour la gestion avancée de la base de données
 * 
 * Fichier: /wp-content/plugins/trainer-registration-plugin/includes/class-trpro-database.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class TrproDatabaseManager {
    
    private static $instance = null;
    private $wpdb;
    private $table_prefix;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_prefix = $wpdb->prefix;
    }
    
    /**
     * Obtenir les statistiques avancées
     */
    public function get_advanced_stats() {
        $trainers_table = $this->table_prefix . 'trainer_registrations';
        
        $stats = array();
        
        // Statistiques générales
        $stats['general'] = array(
            'total' => $this->wpdb->get_var("SELECT COUNT(*) FROM $trainers_table"),
            'approved' => $this->wpdb->get_var("SELECT COUNT(*) FROM $trainers_table WHERE status = 'approved'"),
            'pending' => $this->wpdb->get_var("SELECT COUNT(*) FROM $trainers_table WHERE status = 'pending'"),
            'rejected' => $this->wpdb->get_var("SELECT COUNT(*) FROM $trainers_table WHERE status = 'rejected'")
        );
        
        // Tendances mensuelles
        $stats['monthly_trends'] = $this->wpdb->get_results("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM $trainers_table 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY month
            ORDER BY month ASC
        ");
        
        // Top spécialités
        $stats['top_specialties'] = $this->wpdb->get_results("
            SELECT 
                specialties,
                COUNT(*) as count,
                ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM $trainers_table WHERE status = 'approved'), 2) as percentage
            FROM $trainers_table 
            WHERE status = 'approved' AND specialties IS NOT NULL AND specialties != ''
            GROUP BY specialties 
            ORDER BY count DESC 
            LIMIT 10
        ");
        
        // Taux de conversion
        $total_registrations = $this->wpdb->get_var("SELECT COUNT(*) FROM $trainers_table");
        $approved_registrations = $this->wpdb->get_var("SELECT COUNT(*) FROM $trainers_table WHERE status = 'approved'");
        $stats['conversion_rate'] = $total_registrations > 0 ? round(($approved_registrations / $total_registrations) * 100, 2) : 0;
        
        // Activité récente (7 derniers jours)
        $stats['recent_activity'] = $this->wpdb->get_results("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as registrations
            FROM $trainers_table 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        
        return $stats;
    }
    
    /**
     * Recherche avancée de formateurs
     */
    public function advanced_trainer_search($args = array()) {
        $trainers_table = $this->table_prefix . 'trainer_registrations';
        
        $defaults = array(
            'search' => '',
            'specialty' => '',
            'status' => 'approved',
            'availability' => '',
            'company' => '',
            'date_from' => '',
            'date_to' => '',
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_conditions = array();
        $params = array();
        
        // Filtre par statut
        if (!empty($args['status'])) {
            $where_conditions[] = 'status = %s';
            $params[] = $args['status'];
        }
        
        // Recherche textuelle
        if (!empty($args['search'])) {
            $where_conditions[] = '(first_name LIKE %s OR last_name LIKE %s OR specialties LIKE %s OR bio LIKE %s OR experience LIKE %s)';
            $search_param = '%' . $args['search'] . '%';
            $params = array_merge($params, array($search_param, $search_param, $search_param, $search_param, $search_param));
        }
        
        // Filtre par spécialité
        if (!empty($args['specialty'])) {
            $where_conditions[] = 'specialties LIKE %s';
            $params[] = '%' . $args['specialty'] . '%';
        }
        
        // Filtre par disponibilité
        if (!empty($args['availability'])) {
            $where_conditions[] = 'availability = %s';
            $params[] = $args['availability'];
        }
        
        // Filtre par entreprise
        if (!empty($args['company'])) {
            $where_conditions[] = 'company LIKE %s';
            $params[] = '%' . $args['company'] . '%';
        }
        
        // Filtre par date
        if (!empty($args['date_from'])) {
            $where_conditions[] = 'created_at >= %s';
            $params[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where_conditions[] = 'created_at <= %s';
            $params[] = $args['date_to'] . ' 23:59:59';
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Validation des paramètres ORDER BY pour éviter les injections SQL
        $allowed_orderby = array('created_at', 'first_name', 'last_name', 'email', 'company', 'status');
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        $query = "SELECT * FROM $trainers_table $where_clause ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $params[] = $args['limit'];
        $params[] = $args['offset'];
        
        if (!empty($params)) {
            $query = $this->wpdb->prepare($query, $params);
        }
        
        return $this->wpdb->get_results($query);
    }
    
    /**
     * Obtenir le nombre total pour la recherche (pour la pagination)
     */
    public function get_search_count($args = array()) {
        $trainers_table = $this->table_prefix . 'trainer_registrations';
        
        $where_conditions = array();
        $params = array();
        
        // Reprendre les mêmes conditions que la recherche
        if (!empty($args['status'])) {
            $where_conditions[] = 'status = %s';
            $params[] = $args['status'];
        }
        
        if (!empty($args['search'])) {
            $where_conditions[] = '(first_name LIKE %s OR last_name LIKE %s OR specialties LIKE %s OR bio LIKE %s OR experience LIKE %s)';
            $search_param = '%' . $args['search'] . '%';
            $params = array_merge($params, array($search_param, $search_param, $search_param, $search_param, $search_param));
        }
        
        if (!empty($args['specialty'])) {
            $where_conditions[] = 'specialties LIKE %s';
            $params[] = '%' . $args['specialty'] . '%';
        }
        
        if (!empty($args['availability'])) {
            $where_conditions[] = 'availability = %s';
            $params[] = $args['availability'];
        }
        
        if (!empty($args['company'])) {
            $where_conditions[] = 'company LIKE %s';
            $params[] = '%' . $args['company'] . '%';
        }
        
        if (!empty($args['date_from'])) {
            $where_conditions[] = 'created_at >= %s';
            $params[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where_conditions[] = 'created_at <= %s';
            $params[] = $args['date_to'] . ' 23:59:59';
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "SELECT COUNT(*) FROM $trainers_table $where_clause";
        
        if (!empty($params)) {
            $query = $this->wpdb->prepare($query, $params);
        }
        
        return $this->wpdb->get_var($query);
    }
    
    /**
     * Nettoyer les données anciennes
     */
    public function cleanup_old_data() {
        $trainers_table = $this->table_prefix . 'trainer_registrations';
        $logs_table = $this->table_prefix . 'trainer_logs';
        
        $retention_years = get_option('trainer_data_retention', 3);
        
        // Supprimer les formateurs rejetés anciens
        $deleted_trainers = $this->wpdb->query($this->wpdb->prepare("
            DELETE FROM $trainers_table 
            WHERE status = 'rejected' 
            AND created_at < DATE_SUB(NOW(), INTERVAL %d YEAR)
        ", $retention_years));
        
        // Nettoyer les logs anciens
        $deleted_logs = $this->wpdb->query($this->wpdb->prepare("
            DELETE FROM $logs_table 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL %d YEAR)
        ", $retention_years));
        
        return array(
            'deleted_trainers' => $deleted_trainers,
            'deleted_logs' => $deleted_logs
        );
    }
    
    /**
     * Optimiser les tables
     */
    public function optimize_tables() {
        $trainers_table = $this->table_prefix . 'trainer_registrations';
        $logs_table = $this->table_prefix . 'trainer_logs';
        
        $results = array();
        
        // Optimiser la table des formateurs
        $results['trainers'] = $this->wpdb->query("OPTIMIZE TABLE $trainers_table");
        
        // Optimiser la table des logs si elle existe
        if ($this->wpdb->get_var("SHOW TABLES LIKE '$logs_table'") == $logs_table) {
            $results['logs'] = $this->wpdb->query("OPTIMIZE TABLE $logs_table");
        }
        
        return $results;
    }
    
    /**
     * Exporter les données vers CSV
     */
    public function export_to_csv($args = array()) {
        $trainers = $this->advanced_trainer_search($args);
        
        if (empty($trainers)) {
            return false;
        }
        
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/trainer-files/exports/';
        
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }
        
        $filename = 'trainers_export_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = $export_dir . $filename;
        
        $handle = fopen($filepath, 'w');
        
        if (!$handle) {
            return false;
        }
        
        // Headers CSV
        $headers = array(
            'ID', 'Prénom', 'Nom', 'Email', 'Téléphone', 'Entreprise',
            'Spécialités', 'Disponibilité', 'Statut', 'Date inscription'
        );
        
        fputcsv($handle, $headers);
        
        // Données
        foreach ($trainers as $trainer) {
            $row = array(
                $trainer->id,
                $trainer->first_name,
                $trainer->last_name,
                $trainer->email,
                $trainer->phone,
                $trainer->company,
                $trainer->specialties,
                $trainer->availability,
                $trainer->status,
                $trainer->created_at
            );
            
            fputcsv($handle, $row);
        }
        
        fclose($handle);
        
        return array(
            'filepath' => $filepath,
            'filename' => $filename,
            'url' => $upload_dir['baseurl'] . '/trainer-files/exports/' . $filename,
            'count' => count($trainers)
        );
    }
    
    /**
     * Mettre à jour en masse
     */
    public function bulk_update($trainer_ids, $data) {
        if (empty($trainer_ids) || empty($data)) {
            return false;
        }
        
        $trainers_table = $this->table_prefix . 'trainer_registrations';
        $placeholders = implode(',', array_fill(0, count($trainer_ids), '%d'));
        
        $set_clauses = array();
        $params = array();
        
        foreach ($data as $field => $value) {
            $set_clauses[] = "$field = %s";
            $params[] = $value;
        }
        
        if (empty($set_clauses)) {
            return false;
        }
        
        $set_clause = implode(', ', $set_clauses);
        $params = array_merge($params, $trainer_ids);
        
        $query = "UPDATE $trainers_table SET $set_clause WHERE id IN ($placeholders)";
        
        return $this->wpdb->query($this->wpdb->prepare($query, $params));
    }
    
    /**
     * Vérifier l'intégrité de la base de données
     */
    public function check_database_integrity() {
        $trainers_table = $this->table_prefix . 'trainer_registrations';
        $issues = array();
        
        // Vérifier les emails en doublon
        $duplicate_emails = $this->wpdb->get_results("
            SELECT email, COUNT(*) as count 
            FROM $trainers_table 
            GROUP BY email 
            HAVING count > 1
        ");
        
        if (!empty($duplicate_emails)) {
            $issues['duplicate_emails'] = $duplicate_emails;
        }
        
        // Vérifier les fichiers manquants
        $trainers_with_files = $this->wpdb->get_results("
            SELECT id, cv_file, photo_file 
            FROM $trainers_table 
            WHERE cv_file IS NOT NULL OR photo_file IS NOT NULL
        ");
        
        $missing_files = array();
        $upload_dir = wp_upload_dir();
        
        foreach ($trainers_with_files as $trainer) {
            if (!empty($trainer->cv_file)) {
                $cv_path = $upload_dir['basedir'] . '/' . $trainer->cv_file;
                if (!file_exists($cv_path)) {
                    $missing_files[] = array(
                        'trainer_id' => $trainer->id,
                        'file_type' => 'cv',
                        'file_path' => $trainer->cv_file
                    );
                }
            }
            
            if (!empty($trainer->photo_file)) {
                $photo_path = $upload_dir['basedir'] . '/' . $trainer->photo_file;
                if (!file_exists($photo_path)) {
                    $missing_files[] = array(
                        'trainer_id' => $trainer->id,
                        'file_type' => 'photo',
                        'file_path' => $trainer->photo_file
                    );
                }
            }
        }
        
        if (!empty($missing_files)) {
            $issues['missing_files'] = $missing_files;
        }
        
        // Vérifier les données orphelines
        $orphaned_data = $this->wpdb->get_results("
            SELECT id, email, status 
            FROM $trainers_table 
            WHERE email IS NULL OR email = '' OR first_name IS NULL OR first_name = ''
        ");
        
        if (!empty($orphaned_data)) {
            $issues['orphaned_data'] = $orphaned_data;
        }
        
        return $issues;
    }
}