<?php
/**
 * Classe pour la gestion de la conformité RGPD
 * 
 * Fichier: /wp-content/plugins/trainer-registration-plugin/includes/class-trpro-gdpr.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class TrproGdprManager {
    
    private static $instance = null;
    private $consent_types = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_consent_types();
        $this->init_gdpr_hooks();
    }
    
    /**
     * Initialiser les types de consentement
     */
    private function init_consent_types() {
        $this->consent_types = array(
            'rgpd_consent' => array(
                'label' => 'Consentement principal RGPD',
                'description' => 'Traitement des données personnelles pour la gestion du profil formateur',
                'required' => true,
                'purpose' => 'Gestion des inscriptions et mise en relation avec recruteurs',
                'legal_basis' => 'Consentement (Art. 6.1.a RGPD)',
                'retention' => '3 ans après dernière activité'
            ),
            'marketing_consent' => array(
                'label' => 'Communications marketing',
                'description' => 'Réception d\'informations sur nouvelles opportunités et actualités',
                'required' => false,
                'purpose' => 'Marketing et communication',
                'legal_basis' => 'Consentement (Art. 6.1.a RGPD)',
                'retention' => '3 ans ou jusqu\'à retrait du consentement'
            )
        );
    }
    
    /**
     * Initialiser les hooks RGPD
     */
    private function init_gdpr_hooks() {
        // Hooks pour les demandes de données personnelles
        add_action('wp_ajax_trpro_export_personal_data', array($this, 'handle_data_export_request'));
        add_action('wp_ajax_trpro_delete_personal_data', array($this, 'handle_data_deletion_request'));
        
        // Hook pour le nettoyage automatique
        add_action('trpro_gdpr_cleanup', array($this, 'automatic_data_cleanup'));
        
        // Programmer le nettoyage automatique
        if (!wp_next_scheduled('trpro_gdpr_cleanup')) {
            wp_schedule_event(time(), 'daily', 'trpro_gdpr_cleanup');
        }
        
        // Hook pour les demandes de portabilité
        add_action('wp_ajax_trpro_data_portability', array($this, 'handle_data_portability_request'));
        
        // Intégration avec le système WordPress de protection des données
        add_filter('wp_privacy_personal_data_exporters', array($this, 'register_data_exporter'));
        add_filter('wp_privacy_personal_data_erasers', array($this, 'register_data_eraser'));
        
        // Hook pour audit trail
        add_action('trpro_consent_given', array($this, 'log_consent_action'), 10, 3);
        add_action('trpro_consent_withdrawn', array($this, 'log_consent_action'), 10, 3);
    }
    
    /**
     * Enregistrer le consentement
     */
    public function record_consent($trainer_id, $consent_type, $given = true, $details = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        // Mettre à jour le consentement dans la table principale
        $update_data = array();
        if ($consent_type === 'rgpd_consent') {
            $update_data['rgpd_consent'] = $given ? 1 : 0;
        } elseif ($consent_type === 'marketing_consent') {
            $update_data['marketing_consent'] = $given ? 1 : 0;
        }
        
        if (!empty($update_data)) {
            $wpdb->update($table_name, $update_data, array('id' => $trainer_id), array('%d'), array('%d'));
        }
        
        // Enregistrer dans l'audit trail
        $this->log_consent_action($trainer_id, $consent_type, $given, $details);
        
        // Déclencher action
        $action = $given ? 'trpro_consent_given' : 'trpro_consent_withdrawn';
        do_action($action, $trainer_id, $consent_type, $details);
        
        return true;
    }
    
    /**
     * Obtenir le statut du consentement
     */
    public function get_consent_status($trainer_id, $consent_type = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        if ($consent_type) {
            $column = $consent_type === 'marketing_consent' ? 'marketing_consent' : 'rgpd_consent';
            return (bool) $wpdb->get_var($wpdb->prepare(
                "SELECT $column FROM $table_name WHERE id = %d",
                $trainer_id
            ));
        } else {
            $trainer = $wpdb->get_row($wpdb->prepare(
                "SELECT rgpd_consent, marketing_consent FROM $table_name WHERE id = %d",
                $trainer_id
            ));
            
            return array(
                'rgpd_consent' => (bool) $trainer->rgpd_consent,
                'marketing_consent' => (bool) $trainer->marketing_consent
            );
        }
    }
    
    /**
     * Retirer le consentement
     */
    public function withdraw_consent($trainer_id, $consent_type, $reason = '') {
        return $this->record_consent($trainer_id, $consent_type, false, array(
            'reason' => $reason,
            'withdrawal_date' => current_time('mysql')
        ));
    }
    
    /**
     * Exporter les données personnelles d'un formateur
     */
    public function export_trainer_data($trainer_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        $trainer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $trainer_id
        ));
        
        if (!$trainer) {
            return new WP_Error('trainer_not_found', 'Formateur non trouvé');
        }
        
        // Préparer les données pour export
        $export_data = array(
            'personal_info' => array(
                'id' => $trainer->id,
                'first_name' => $trainer->first_name,
                'last_name' => $trainer->last_name,
                'email' => $trainer->email,
                'phone' => $trainer->phone,
                'company' => $trainer->company,
                'linkedin_url' => $trainer->linkedin_url
            ),
            'professional_info' => array(
                'specialties' => $trainer->specialties,
                'experience' => $trainer->experience,
                'bio' => $trainer->bio,
                'availability' => $trainer->availability,
                'hourly_rate' => $trainer->hourly_rate
            ),
            'consent_info' => array(
                'rgpd_consent' => (bool) $trainer->rgpd_consent,
                'marketing_consent' => (bool) $trainer->marketing_consent,
                'consent_date' => $trainer->created_at
            ),
            'files' => array(
                'cv_file' => $trainer->cv_file,
                'photo_file' => $trainer->photo_file
            ),
            'metadata' => array(
                'status' => $trainer->status,
                'registration_date' => $trainer->created_at,
                'last_update' => $trainer->updated_at,
                'admin_notes' => $trainer->admin_notes
            )
        );
        
        // Ajouter l'historique des consentements
        $export_data['consent_history'] = $this->get_consent_history($trainer_id);
        
        // Log de l'export
        $this->log_gdpr_action('data_export', $trainer_id, array(
            'requester_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'export_date' => current_time('mysql')
        ));
        
        return $export_data;
    }
    
    /**
     * Supprimer les données personnelles d'un formateur
     */
    public function delete_trainer_data($trainer_id, $anonymize = false) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        // Vérifier que le formateur existe
        $trainer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $trainer_id
        ));
        
        if (!$trainer) {
            return new WP_Error('trainer_not_found', 'Formateur non trouvé');
        }
        
        // Log avant suppression
        $this->log_gdpr_action('data_deletion', $trainer_id, array(
            'email' => $trainer->email,
            'deletion_type' => $anonymize ? 'anonymization' : 'complete_deletion',
            'requester_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'deletion_date' => current_time('mysql')
        ));
        
        if ($anonymize) {
            // Anonymisation : garder les données statistiques mais supprimer l'identité
            $anonymous_data = array(
                'first_name' => 'Formateur',
                'last_name' => 'Anonyme',
                'email' => 'anonyme_' . $trainer_id . '@example.com',
                'phone' => 'ANONYMISÉ',
                'company' => 'ANONYMISÉ',
                'linkedin_url' => '',
                'bio' => 'Données anonymisées conformément au RGPD',
                'cv_file' => '',
                'photo_file' => '',
                'admin_notes' => 'Données anonymisées le ' . current_time('mysql')
            );
            
            $result = $wpdb->update($table_name, $anonymous_data, array('id' => $trainer_id));
        } else {
            // Suppression complète
            $result = $wpdb->delete($table_name, array('id' => $trainer_id), array('%d'));
        }
        
        if ($result !== false) {
            // Supprimer les fichiers associés
            $this->delete_trainer_files($trainer);
            
            // Supprimer de l'historique des consentements
            $this->delete_consent_history($trainer_id);
            
            return true;
        }
        
        return new WP_Error('deletion_failed', 'Échec de la suppression');
    }
    
    /**
     * Supprimer les fichiers d'un formateur
     */
    private function delete_trainer_files($trainer) {
        $upload_dir = wp_upload_dir();
        
        $files_to_delete = array();
        if (!empty($trainer->cv_file)) {
            $files_to_delete[] = $upload_dir['basedir'] . '/' . $trainer->cv_file;
        }
        if (!empty($trainer->photo_file)) {
            $files_to_delete[] = $upload_dir['basedir'] . '/' . $trainer->photo_file;
        }
        
        foreach ($files_to_delete as $file_path) {
            if (file_exists($file_path)) {
                wp_delete_file($file_path);
            }
        }
    }
    
    /**
     * Logger les actions RGPD
     */
    public function log_gdpr_action($action, $trainer_id, $details = array()) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'action' => $action,
            'trainer_id' => $trainer_id,
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'details' => $details
        );
        
        // Stocker dans les options (limiter à 500 entrées)
        $logs = get_option('trpro_gdpr_logs', array());
        array_unshift($logs, $log_entry);
        $logs = array_slice($logs, 0, 500);
        
        update_option('trpro_gdpr_logs', $logs);
        
        // Log également dans error_log pour traçabilité
        error_log("TrproGDPR [{$action}]: Trainer {$trainer_id} - " . json_encode($details));
    }
    
    /**
     * Logger les actions de consentement
     */
    public function log_consent_action($trainer_id, $consent_type, $given, $details = array()) {
        $consent_log = array(
            'timestamp' => current_time('mysql'),
            'trainer_id' => $trainer_id,
            'consent_type' => $consent_type,
            'action' => $given ? 'given' : 'withdrawn',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'details' => $details
        );
        
        // Stocker l'historique des consentements
        $consent_history = get_option('trpro_consent_history', array());
        $consent_history[] = $consent_log;
        
        // Limiter à 1000 entrées
        if (count($consent_history) > 1000) {
            $consent_history = array_slice($consent_history, -1000);
        }
        
        update_option('trpro_consent_history', $consent_history);
    }
    
    /**
     * Obtenir l'historique des consentements pour un formateur
     */
    public function get_consent_history($trainer_id) {
        $consent_history = get_option('trpro_consent_history', array());
        
        return array_filter($consent_history, function($entry) use ($trainer_id) {
            return isset($entry['trainer_id']) && $entry['trainer_id'] == $trainer_id;
        });
    }
    
    /**
     * Supprimer l'historique des consentements d'un formateur
     */
    private function delete_consent_history($trainer_id) {
        $consent_history = get_option('trpro_consent_history', array());
        
        $filtered_history = array_filter($consent_history, function($entry) use ($trainer_id) {
            return !isset($entry['trainer_id']) || $entry['trainer_id'] != $trainer_id;
        });
        
        update_option('trpro_consent_history', array_values($filtered_history));
    }
    
    /**
     * Nettoyage automatique des données expirées
     */
    public function automatic_data_cleanup() {
        $retention_years = get_option('trainer_data_retention', 3);
        
        if ($retention_years == 0) {
            return; // Conservation indéfinie
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        // Identifier les formateurs inactifs depuis la période de rétention
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_years} years"));
        
        $inactive_trainers = $wpdb->get_results($wpdb->prepare("
            SELECT id, email, cv_file, photo_file 
            FROM $table_name 
            WHERE (last_login IS NULL OR last_login < %s) 
            AND updated_at < %s 
            AND status IN ('rejected', 'pending')
        ", $cutoff_date, $cutoff_date));
        
        $deleted_count = 0;
        foreach ($inactive_trainers as $trainer) {
            // Log avant suppression automatique
            $this->log_gdpr_action('automatic_cleanup', $trainer->id, array(
                'email' => $trainer->email,
                'reason' => 'Data retention policy',
                'retention_period' => $retention_years . ' years'
            ));
            
            // Supprimer les fichiers
            $this->delete_trainer_files($trainer);
            
            // Supprimer l'enregistrement
            $wpdb->delete($table_name, array('id' => $trainer->id), array('%d'));
            
            $deleted_count++;
        }
        
        if ($deleted_count > 0) {
            error_log("TrproGDPR: Automatic cleanup removed {$deleted_count} inactive trainer records");
        }
        
        // Nettoyer aussi les logs anciens
        $this->cleanup_old_logs();
    }
    
    /**
     * Nettoyer les anciens logs
     */
    private function cleanup_old_logs() {
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-2 years'));
        
        // Nettoyer les logs GDPR
        $gdpr_logs = get_option('trpro_gdpr_logs', array());
        $filtered_gdpr_logs = array_filter($gdpr_logs, function($log) use ($cutoff_date) {
            return isset($log['timestamp']) && $log['timestamp'] > $cutoff_date;
        });
        update_option('trpro_gdpr_logs', array_values($filtered_gdpr_logs));
        
        // Nettoyer l'historique des consentements
        $consent_history = get_option('trpro_consent_history', array());
        $filtered_consent_history = array_filter($consent_history, function($log) use ($cutoff_date) {
            return isset($log['timestamp']) && $log['timestamp'] > $cutoff_date;
        });
        update_option('trpro_consent_history', array_values($filtered_consent_history));
    }
    
    /**
     * Générer un rapport de conformité RGPD
     */
    public function generate_compliance_report() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        // Statistiques générales
        $total_trainers = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $with_rgpd_consent = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE rgpd_consent = 1");
        $with_marketing_consent = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE marketing_consent = 1");
        
        // Données par statut
        $status_breakdown = $wpdb->get_results("
            SELECT status, COUNT(*) as count 
            FROM $table_name 
            GROUP BY status
        ", OBJECT_K);
        
        // Données anciennes
        $retention_years = get_option('trainer_data_retention', 3);
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_years} years"));
        
        $old_data_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_name 
            WHERE updated_at < %s AND status IN ('rejected', 'pending')
        ", $cutoff_date));
        
        // Historique des actions GDPR
        $gdpr_logs = get_option('trpro_gdpr_logs', array());
        $recent_actions = array_slice($gdpr_logs, 0, 20);
        
        $report = array(
            'generated_at' => current_time('mysql'),
            'data_controller' => get_option('trainer_company_name', get_bloginfo('name')),
            'contact_email' => get_option('trainer_contact_email', get_option('admin_email')),
            'statistics' => array(
                'total_data_subjects' => $total_trainers,
                'with_primary_consent' => $with_rgpd_consent,
                'with_marketing_consent' => $with_marketing_consent,
                'consent_rate' => $total_trainers > 0 ? round(($with_rgpd_consent / $total_trainers) * 100, 2) : 0,
                'status_breakdown' => $status_breakdown,
                'data_subject_categories' => array(
                    'active_trainers' => $status_breakdown['approved']->count ?? 0,
                    'pending_trainers' => $status_breakdown['pending']->count ?? 0,
                    'inactive_trainers' => $status_breakdown['rejected']->count ?? 0
                )
            ),
            'data_retention' => array(
                'policy_period' => $retention_years . ' années',
                'old_data_count' => $old_data_count,
                'next_cleanup' => wp_next_scheduled('trpro_gdpr_cleanup') ? date('d/m/Y H:i', wp_next_scheduled('trpro_gdpr_cleanup')) : 'Non programmé'
            ),
            'recent_gdpr_activities' => $recent_actions,
            'compliance_checks' => array(
                'privacy_policy_exists' => !empty(get_privacy_policy_url()),
                'consent_tracking_active' => true,
                'data_retention_configured' => $retention_years > 0,
                'automatic_cleanup_scheduled' => wp_next_scheduled('trpro_gdpr_cleanup') !== false
            )
        );
        
        return $report;
    }
    
    /**
     * Gérer les demandes d'export de données
     */
    public function handle_data_export_request() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'trpro_gdpr_nonce')) {
            wp_send_json_error(array('message' => 'Token de sécurité invalide'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissions insuffisantes'));
        }
        
        $trainer_id = intval($_POST['trainer_id']);
        $export_data = $this->export_trainer_data($trainer_id);
        
        if (is_wp_error($export_data)) {
            wp_send_json_error(array('message' => $export_data->get_error_message()));
        }
        
        // Générer un fichier JSON temporaire
        $upload_dir = wp_upload_dir();
        $filename = 'trainer_data_export_' . $trainer_id . '_' . time() . '.json';
        $filepath = $upload_dir['basedir'] . '/trainer-files/exports/' . $filename;
        
        if (!file_exists(dirname($filepath))) {
            wp_mkdir_p(dirname($filepath));
        }
        
        file_put_contents($filepath, wp_json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $download_url = $upload_dir['baseurl'] . '/trainer-files/exports/' . $filename;
        
        wp_send_json_success(array(
            'download_url' => $download_url,
            'filename' => $filename,
            'message' => 'Export généré avec succès'
        ));
    }
    
    /**
     * Gérer les demandes de suppression de données
     */
    public function handle_data_deletion_request() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'trpro_gdpr_nonce')) {
            wp_send_json_error(array('message' => 'Token de sécurité invalide'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissions insuffisantes'));
        }
        
        $trainer_id = intval($_POST['trainer_id']);
        $anonymize = isset($_POST['anonymize']) && $_POST['anonymize'] === 'true';
        
        $result = $this->delete_trainer_data($trainer_id, $anonymize);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        $message = $anonymize ? 'Données anonymisées avec succès' : 'Données supprimées avec succès';
        wp_send_json_success(array('message' => $message));
    }
    
    /**
     * Gérer les demandes de portabilité
     */
    public function handle_data_portability_request() {
        // Similaire à l'export mais avec format standardisé pour portabilité
        $this->handle_data_export_request();
    }
    
    /**
     * Enregistrer l'exportateur de données pour WordPress
     */
    public function register_data_exporter($exporters) {
        $exporters['trainer-registration'] = array(
            'exporter_friendly_name' => 'Données Formateurs IT',
            'callback' => array($this, 'wp_data_exporter'),
        );
        return $exporters;
    }
    
    /**
     * Enregistrer l'effaceur de données pour WordPress
     */
    public function register_data_eraser($erasers) {
        $erasers['trainer-registration'] = array(
            'eraser_friendly_name' => 'Données Formateurs IT',
            'callback' => array($this, 'wp_data_eraser'),
        );
        return $erasers;
    }
    
    /**
     * Callback pour l'exportateur WordPress
     */
    public function wp_data_exporter($email_address, $page = 1) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        $trainer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE email = %s",
            $email_address
        ));
        
        $export_items = array();
        
        if ($trainer) {
            $export_items[] = array(
                'group_id' => 'trainer-data',
                'group_label' => 'Données de formateur',
                'item_id' => 'trainer-' . $trainer->id,
                'data' => array(
                    array('name' => 'ID', 'value' => $trainer->id),
                    array('name' => 'Prénom', 'value' => $trainer->first_name),
                    array('name' => 'Nom', 'value' => $trainer->last_name),
                    array('name' => 'Email', 'value' => $trainer->email),
                    array('name' => 'Téléphone', 'value' => $trainer->phone),
                    array('name' => 'Entreprise', 'value' => $trainer->company),
                    array('name' => 'Spécialités', 'value' => $trainer->specialties),
                    array('name' => 'Date d\'inscription', 'value' => $trainer->created_at),
                    array('name' => 'Statut', 'value' => $trainer->status)
                )
            );
        }
        
        return array(
            'data' => $export_items,
            'done' => true
        );
    }
    
    /**
     * Callback pour l'effaceur WordPress
     */
    public function wp_data_eraser($email_address, $page = 1) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        $trainer = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_name WHERE email = %s",
            $email_address
        ));
        
        $items_removed = false;
        $items_retained = false;
        $messages = array();
        
        if ($trainer) {
            $result = $this->delete_trainer_data($trainer->id, true); // Anonymisation
            
            if (!is_wp_error($result)) {
                $items_removed = true;
                $messages[] = 'Données du formateur anonymisées.';
            } else {
                $items_retained = true;
                $messages[] = 'Erreur lors de l\'anonymisation: ' . $result->get_error_message();
            }
        }
        
        return array(
            'items_removed' => $items_removed,
            'items_retained' => $items_retained,
            'messages' => $messages,
            'done' => true
        );
    }
    
    /**
     * Obtenir le texte de la politique de confidentialité suggérée
     */
    public function get_privacy_policy_content() {
        $company_name = get_option('trainer_company_name', '[Nom de votre organisation]');
        $contact_email = get_option('trainer_contact_email', '[email de contact]');
        $retention_years = get_option('trainer_data_retention', 3);
        
        return "
        <h2>Gestion des données des formateurs</h2>
        
        <h3>Responsable du traitement</h3>
        <p>{$company_name} est responsable du traitement de vos données personnelles dans le cadre de votre inscription en tant que formateur.</p>
        
        <h3>Données collectées</h3>
        <p>Nous collectons les informations suivantes :</p>
        <ul>
            <li>Informations personnelles : nom, prénom, adresse email, numéro de téléphone</li>
            <li>Informations professionnelles : entreprise, spécialités, expérience, CV, photo professionnelle</li>
            <li>Informations techniques : adresse IP, données de navigation</li>
        </ul>
        
        <h3>Finalités du traitement</h3>
        <p>Vos données sont traitées pour :</p>
        <ul>
            <li>Gérer votre inscription en tant que formateur</li>
            <li>Faciliter la mise en relation avec des recruteurs</li>
            <li>Communiquer avec vous concernant votre profil</li>
            <li>Améliorer nos services</li>
        </ul>
        
        <h3>Base légale</h3>
        <p>Le traitement de vos données repose sur votre consentement (Art. 6.1.a du RGPD).</p>
        
        <h3>Durée de conservation</h3>
        <p>Vos données sont conservées pendant {$retention_years} ans à compter de votre dernière activité ou jusqu'au retrait de votre consentement.</p>
        
        <h3>Vos droits</h3>
        <p>Conformément au RGPD, vous disposez des droits suivants :</p>
        <ul>
            <li>Droit d'accès à vos données</li>
            <li>Droit de rectification</li>
            <li>Droit à l'effacement</li>
            <li>Droit à la portabilité</li>
            <li>Droit de limitation du traitement</li>
            <li>Droit d'opposition</li>
            <li>Droit de retrait du consentement</li>
        </ul>
        
        <h3>Contact</h3>
        <p>Pour exercer vos droits ou pour toute question relative à la protection de vos données, contactez-nous à : {$contact_email}</p>
        
        <h3>Réclamation</h3>
        <p>Vous avez le droit d'introduire une réclamation auprès de la CNIL si vous estimez que le traitement de vos données ne respecte pas la réglementation.</p>
        ";
    }
}