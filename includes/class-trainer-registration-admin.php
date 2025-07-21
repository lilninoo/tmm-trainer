<?php
/**
 * Classe pour la partie administration - VERSION CORRIGÉE
 * 
 * Fichier: includes/class-trainer-registration-admin.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class TrainerRegistrationAdmin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Handlers AJAX - AJOUT DES HANDLERS MANQUANTS
        add_action('wp_ajax_update_trainer_status', array($this, 'update_trainer_status'));
        add_action('wp_ajax_delete_trainer', array($this, 'delete_trainer'));
        add_action('wp_ajax_download_trainer_file', array($this, 'handle_download_trainer_file')); // AJOUTÉ
    }

    public function add_admin_menu() {
        add_menu_page(
            'Formateurs',
            'Formateurs',
            'manage_options',
            'trainer-registration',
            array($this, 'display_admin_page'),
            'dashicons-groups',
            30
        );

        // AJOUT DU SOUS-MENU STATISTIQUES
        add_submenu_page(
            'trainer-registration',
            'Statistiques',
            'Statistiques',
            'manage_options',
            'trainer-stats',
            array($this, 'display_stats_page')
        );

        add_submenu_page(
            'trainer-registration',
            'Paramètres',
            'Paramètres',
            'manage_options',
            'trainer-settings',
            array($this, 'display_settings_page')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'trainer-') === false) {
            return;
        }

        wp_enqueue_style(
            'trainer-admin-style',
            TRAINER_REGISTRATION_PLUGIN_URL . 'admin/css/admin-style.css',
            array(),
            TRAINER_REGISTRATION_VERSION
        );

        wp_enqueue_script(
            'trainer-admin-script',
            TRAINER_REGISTRATION_PLUGIN_URL . 'admin/js/admin-script.js',
            array('jquery'),
            TRAINER_REGISTRATION_VERSION,
            true
        );

        wp_localize_script('trainer-admin-script', 'trainer_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('trainer_admin_nonce')
        ));
    }

    public function display_admin_page() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        // GESTION DE L'ACTION "VIEW" - AJOUTÉ
        if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['trainer_id'])) {
            $trainer_id = intval($_GET['trainer_id']);
            $trainer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $trainer_id));
            
            if ($trainer) {
                include TRAINER_REGISTRATION_PLUGIN_PATH . 'admin/partials/trainer-details.php';
                return;
            }
        }
        
        // Traitement des actions
        if (isset($_GET['action']) && isset($_GET['trainer_id'])) {
            $this->handle_admin_actions();
        }
        
        // Filtres
        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : 'all';
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        
        // Construction de la requête
        $where_conditions = array();
        $params = array();
        
        if ($status_filter !== 'all') {
            $where_conditions[] = 'status = %s';
            $params[] = $status_filter;
        }
        
        if (!empty($search)) {
            $where_conditions[] = '(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s)';
            $search_param = '%' . $search . '%';
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Récupérer les formateurs
        $query = "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC";
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        $trainers = $wpdb->get_results($query);
        
        // Statistiques
        $total_trainers = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $pending_trainers = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
        $approved_trainers = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'approved'");
        $rejected_trainers = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'rejected'");
        
        // Afficher la vue
        include TRAINER_REGISTRATION_PLUGIN_PATH . 'admin/partials/admin-display.php';
    }

    // MÉTHODE POUR AFFICHER LES STATISTIQUES - AJOUTÉE
    public function display_stats_page() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        // Statistiques générales
        $stats = array(
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
            'pending' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'"),
            'approved' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'approved'"),
            'rejected' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'rejected'")
        );
        
        // Top spécialités
        $specialty_stats = $wpdb->get_results("
            SELECT specialties, COUNT(*) as count 
            FROM $table_name 
            WHERE status = 'approved' AND specialties != ''
            GROUP BY specialties 
            ORDER BY count DESC 
            LIMIT 10
        ");
        
        // Inscriptions par mois (12 derniers mois)
        $monthly_registrations = array();
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM $table_name 
                WHERE DATE_FORMAT(created_at, '%%Y-%%m') = %s
            ", $month));
            $monthly_registrations[] = array(
                'month' => $month,
                'count' => intval($count)
            );
        }
        
        include TRAINER_REGISTRATION_PLUGIN_PATH . 'admin/partials/stats-display.php';
    }

    public function display_settings_page() {
        // Traitement du formulaire
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        // Récupérer les options
        $contact_email = get_option('trainer_contact_email', get_option('admin_email'));
        $contact_phone = get_option('trainer_contact_phone', '');
        $auto_approve = get_option('trainer_auto_approve', 0);
        $notification_email = get_option('trainer_notification_email', get_option('admin_email'));
        
        include TRAINER_REGISTRATION_PLUGIN_PATH . 'admin/partials/settings-display.php';
    }

    private function handle_admin_actions() {
        if (!wp_verify_nonce($_GET['nonce'], 'trainer_admin_nonce')) {
            wp_die('Token de sécurité invalide.');
        }

        $action = sanitize_text_field($_GET['action']);
        $trainer_id = intval($_GET['trainer_id']);

        switch ($action) {
            case 'approve':
                $this->update_trainer_status_direct($trainer_id, 'approved');
                break;
            case 'reject':
                $this->update_trainer_status_direct($trainer_id, 'rejected');
                break;
            case 'delete':
                $this->delete_trainer_direct($trainer_id);
                break;
        }

        wp_redirect(admin_url('admin.php?page=trainer-registration&message=' . $action));
        exit;
    }

    public function update_trainer_status() {
        if (!wp_verify_nonce($_POST['nonce'], 'trainer_admin_nonce')) {
            wp_send_json_error(array('message' => 'Token de sécurité invalide'));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissions insuffisantes'));
        }

        $trainer_id = intval($_POST['trainer_id']);
        $status = sanitize_text_field($_POST['status']);

        // Convertir l'action en statut
        $new_status = ($status === 'approve') ? 'approved' : 'rejected';

        if ($this->update_trainer_status_direct($trainer_id, $new_status)) {
            // Envoyer email de notification
            $this->send_status_notification_email($trainer_id, $new_status);
            
            wp_send_json_success(array(
                'message' => $new_status === 'approved' ? 'Formateur approuvé avec succès' : 'Formateur rejeté'
            ));
        } else {
            wp_send_json_error(array('message' => 'Erreur lors de la mise à jour'));
        }
    }

    private function update_trainer_status_direct($trainer_id, $status) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        return $wpdb->update(
            $table_name,
            array(
                'status' => $status,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $trainer_id),
            array('%s', '%s'),
            array('%d')
        );
    }

    public function delete_trainer() {
        if (!wp_verify_nonce($_POST['nonce'], 'trainer_admin_nonce')) {
            wp_send_json_error(array('message' => 'Token de sécurité invalide'));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissions insuffisantes'));
        }

        $trainer_id = intval($_POST['trainer_id']);

        if ($this->delete_trainer_direct($trainer_id)) {
            wp_send_json_success(array('message' => 'Formateur supprimé avec succès'));
        } else {
            wp_send_json_error(array('message' => 'Erreur lors de la suppression'));
        }
    }

    private function delete_trainer_direct($trainer_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        // Récupérer les infos du formateur pour supprimer les fichiers
        $trainer = $wpdb->get_row($wpdb->prepare(
            "SELECT cv_file, photo_file FROM $table_name WHERE id = %d",
            $trainer_id
        ));
        
        if ($trainer) {
            // Supprimer les fichiers
            $upload_dir = wp_upload_dir();
            
            if (!empty($trainer->cv_file)) {
                $cv_path = $upload_dir['basedir'] . '/' . $trainer->cv_file;
                if (file_exists($cv_path)) {
                    wp_delete_file($cv_path);
                }
            }
            
            if (!empty($trainer->photo_file)) {
                $photo_path = $upload_dir['basedir'] . '/' . $trainer->photo_file;
                if (file_exists($photo_path)) {
                    wp_delete_file($photo_path);
                }
            }
        }
        
        // Supprimer l'enregistrement
        return $wpdb->delete(
            $table_name,
            array('id' => $trainer_id),
            array('%d')
        );
    }

    // HANDLER POUR LE TÉLÉCHARGEMENT DE FICHIERS - AJOUTÉ
    public function handle_download_trainer_file() {
        // Vérification du nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'trainer_admin_nonce')) {
            wp_die('Erreur de sécurité - Token invalide');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Permissions insuffisantes');
        }
        
        $trainer_id = intval($_GET['trainer_id']);
        $file_type = sanitize_text_field($_GET['file_type']);
        
        if (!in_array($file_type, array('cv', 'photo'))) {
            wp_die('Type de fichier invalide');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'trainer_registrations';
        $trainer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $trainer_id));
        
        if (!$trainer) {
            wp_die('Formateur non trouvé');
        }
        
        $file_field = $file_type . '_file';
        $file_path = $trainer->$file_field;
        
        if (empty($file_path)) {
            wp_die('Aucun fichier ' . $file_type . ' pour ce formateur');
        }
        
        $upload_dir = wp_upload_dir();
        $full_path = $upload_dir['basedir'] . '/' . $file_path;
        
        if (!file_exists($full_path)) {
            wp_die('Fichier physique non trouvé : ' . $file_path);
        }
        
        // Téléchargement sécurisé
        $file_info = pathinfo($full_path);
        $filename = sanitize_file_name(
            $trainer->first_name . '_' . $trainer->last_name . '_' . $file_type . '.' . $file_info['extension']
        );
        
        // Headers pour le téléchargement
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($full_path));
        header('Cache-Control: private');
        header('Pragma: private');
        header('Expires: 0');
        
        // Lire et envoyer le fichier
        readfile($full_path);
        exit;
    }

    // MÉTHODE POUR ENVOYER EMAIL DE NOTIFICATION - AJOUTÉE
    private function send_status_notification_email($trainer_id, $status) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        $trainer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $trainer_id));
        
        if (!$trainer) {
            return false;
        }
        
        $subject = $status === 'approved' ? 'Candidature approuvée' : 'Mise à jour de votre candidature';
        
        $message = "Bonjour {$trainer->first_name},\n\n";
        
        if ($status === 'approved') {
            $message .= "Nous avons le plaisir de vous informer que votre candidature a été approuvée.\n";
            $message .= "Votre profil est maintenant visible par les recruteurs.\n\n";
        } else {
            $message .= "Nous vous remercions pour votre candidature.\n";
            $message .= "Après examen, nous ne pouvons malheureusement pas l'accepter en l'état.\n\n";
        }
        
        $message .= "Cordialement,\nL'équipe " . get_option('trainer_company_name', get_bloginfo('name'));
        
        return wp_mail($trainer->email, $subject, $message);
    }

    private function save_settings() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'trainer_settings_nonce')) {
            wp_die('Token de sécurité invalide.');
        }

        // Liste complète des options à sauvegarder
        $options_mapping = array(
            'contact_email' => 'trainer_contact_email',
            'contact_phone' => 'trainer_contact_phone',
            'auto_approve' => 'trainer_auto_approve',
            'notification_email' => 'trainer_notification_email',
            'company_name' => 'trainer_company_name',
            'require_photo' => 'trainer_require_photo',
            'max_cv_size' => 'trainer_max_cv_size',
            'max_photo_size' => 'trainer_max_photo_size',
            'notify_new_registration' => 'trainer_notify_new_registration',
            'notify_status_change' => 'trainer_notify_status_change',
            'notify_weekly_summary' => 'trainer_notify_weekly_summary',
            'notify_pending_review' => 'trainer_notify_pending_review',
            'trainers_per_page' => 'trainer_trainers_per_page',
            'data_retention' => 'trainer_data_retention',
            'enable_captcha' => 'trainer_enable_captcha',
            'debug_mode' => 'trainer_debug_mode',
            'website_url' => 'trainer_website_url'
        );

        foreach ($options_mapping as $post_key => $option_name) {
            if (isset($_POST[$post_key])) {
                $value = $_POST[$post_key];
                
                // Validation selon le type
                if (strpos($post_key, 'email') !== false) {
                    $value = sanitize_email($value);
                } elseif (strpos($post_key, 'url') !== false) {
                    $value = esc_url_raw($value);
                } elseif (is_array($value)) {
                    $value = array_map('sanitize_text_field', $value);
                } else {
                    $value = sanitize_text_field($value);
                }
                
                update_option($option_name, $value);
            } else {
                // Pour les checkboxes non cochées
                if (in_array($post_key, array('auto_approve', 'require_photo', 'notify_new_registration', 'notify_status_change', 'notify_weekly_summary', 'notify_pending_review', 'enable_captcha', 'debug_mode'))) {
                    update_option($options_mapping[$post_key], 0);
                }
            }
        }

        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Paramètres sauvegardés avec succès.</p></div>';
        });
    }
}