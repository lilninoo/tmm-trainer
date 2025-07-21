<?php
/**
 * Fonctions utilitaires pour Trainer Registration Pro
 * 
 * Fichier: /wp-content/plugins/trainer-registration-plugin/includes/trpro-functions.php
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtenir les couleurs du thème
 */
function trpro_get_theme_colors() {
    return array(
        'primary' => get_option('trpro_primary_color', '#6366f1'),
        'secondary' => get_option('trpro_secondary_color', '#0f172a'),
        'accent' => get_option('trpro_accent_color', '#06b6d4'),
        'success' => '#10b981',
        'warning' => '#f59e0b',
        'error' => '#ef4444'
    );
}

/**
 * Générer du CSS personnalisé avec les couleurs du thème
 */
function trpro_generate_custom_css() {
    $colors = trpro_get_theme_colors();
    
    $css = ":root {";
    foreach ($colors as $name => $color) {
        $css .= "--trpro-{$name}: {$color};";
    }
    $css .= "}";
    
    return $css;
}

/**
 * Formater la taille de fichier
 */
function trpro_format_file_size($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }
    
    return $bytes;
}

/**
 * Nettoyer et valider une URL
 */
function trpro_clean_url($url) {
    if (empty($url)) {
        return '';
    }
    
    // Ajouter http:// si pas de protocole
    if (!preg_match('/^https?:\/\//', $url)) {
        $url = 'http://' . $url;
    }
    
    return esc_url_raw($url);
}

/**
 * Générer un ID unique pour les formateurs
 */
function trpro_generate_trainer_id($trainer_id) {
    return 'TR' . str_pad($trainer_id, 6, '0', STR_PAD_LEFT);
}

/**
 * Obtenir les spécialités disponibles
 */
function trpro_get_specialties() {
    return array(
        'administration-systeme' => __('Administration Système', 'trainer-registration-pro'),
        'reseaux' => __('Réseaux & Infrastructure', 'trainer-registration-pro'),
        'cloud' => __('Cloud Computing', 'trainer-registration-pro'),
        'devops' => __('DevOps & CI/CD', 'trainer-registration-pro'),
        'securite' => __('Sécurité Informatique', 'trainer-registration-pro'),
        'telecoms' => __('Télécommunications', 'trainer-registration-pro'),
        'developpement' => __('Développement', 'trainer-registration-pro'),
        'bases-donnees' => __('Bases de Données', 'trainer-registration-pro')
    );
}

/**
 * Obtenir l'icône pour une spécialité
 */
function trpro_get_specialty_icon($specialty) {
    $icons = array(
        'administration-systeme' => 'fas fa-server',
        'reseaux' => 'fas fa-network-wired',
        'cloud' => 'fas fa-cloud',
        'devops' => 'fas fa-infinity',
        'securite' => 'fas fa-shield-alt',
        'telecoms' => 'fas fa-satellite-dish',
        'developpement' => 'fas fa-code',
        'bases-donnees' => 'fas fa-database'
    );
    
    return isset($icons[$specialty]) ? $icons[$specialty] : 'fas fa-cog';
}

/**
 * Valider un email
 */
function trpro_validate_email($email) {
    return is_email($email) && !empty($email);
}

/**
 * Valider un téléphone (format français)
 */
function trpro_validate_phone($phone) {
    // Supprimer tous les caractères non numériques sauf +
    $clean_phone = preg_replace('/[^\d+]/', '', $phone);
    
    // Vérifier le format (au moins 10 chiffres)
    return preg_match('/^(\+33|0)[1-9](\d{8})$/', $clean_phone);
}

/**
 * Logger les actions pour audit
 */
function trpro_log_action($action, $description = '', $trainer_id = null) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'trainer_logs';
    
    // Vérifier si la table existe
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        return false;
    }
    
    $data = array(
        'trainer_id' => $trainer_id,
        'action' => sanitize_text_field($action),
        'description' => sanitize_text_field($description),
        'user_id' => get_current_user_id(),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    );
    
    return $wpdb->insert($table_name, $data);
}

/**
 * Obtenir les statistiques des formateurs
 */
function trpro_get_trainer_stats() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'trainer_registrations';
    
    return array(
        'total' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'approved'"),
        'pending' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'"),
        'rejected' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'rejected'"),
        'this_month' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'approved' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")
    );
}

/**
 * Envoyer un email avec template
 */
function trpro_send_email($to, $subject, $message, $headers = array()) {
    $default_headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_option('trainer_company_name', get_bloginfo('name')) . ' <' . get_option('trainer_contact_email', get_option('admin_email')) . '>'
    );
    
    $headers = array_merge($default_headers, $headers);
    
    // Template HTML simple
    $html_message = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #6366f1; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f8f9fa; }
            .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>' . get_option('trainer_company_name', get_bloginfo('name')) . '</h1>
            </div>
            <div class="content">
                ' . wpautop($message) . '
            </div>
            <div class="footer">
                <p>Cet email a été envoyé automatiquement, merci de ne pas répondre.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return wp_mail($to, $subject, $html_message, $headers);
}

/**
 * Vérifier les permissions utilisateur
 */
function trpro_user_can_manage_trainers() {
    return current_user_can('manage_options') || current_user_can('edit_others_posts');
}

/**
 * Nettoyer les données avant insertion
 */
function trpro_sanitize_trainer_data($data) {
    $sanitized = array();
    
    $fields = array(
        'first_name' => 'sanitize_text_field',
        'last_name' => 'sanitize_text_field',
        'email' => 'sanitize_email',
        'phone' => 'sanitize_text_field',
        'company' => 'sanitize_text_field',
        'specialties' => 'sanitize_text_field',
        'experience' => 'sanitize_textarea_field',
        'bio' => 'sanitize_textarea_field',
        'linkedin_url' => 'esc_url_raw',
        'availability' => 'sanitize_text_field',
        'hourly_rate' => 'sanitize_text_field'
    );
    
    foreach ($fields as $field => $sanitize_function) {
        if (isset($data[$field])) {
            $sanitized[$field] = $sanitize_function($data[$field]);
        }
    }
    
    return $sanitized;
}

/**
 * Générer un token unique pour la sécurité
 */
function trpro_generate_token($length = 32) {
    return wp_generate_password($length, false, false);
}

/**
 * Vérifier si un fichier est sécurisé
 */
function trpro_is_file_safe($file_path) {
    if (!file_exists($file_path)) {
        return false;
    }
    
    $file_info = pathinfo($file_path);
    $allowed_extensions = array('pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif');
    
    if (!in_array(strtolower($file_info['extension']), $allowed_extensions)) {
        return false;
    }
    
    // Vérifier la taille
    $max_size = 5 * 1024 * 1024; // 5MB
    if (filesize($file_path) > $max_size) {
        return false;
    }
    
    return true;
}

/**
 * Obtenir l'URL de téléchargement sécurisée
 */
function trpro_get_secure_download_url($file_path, $trainer_id) {
    return add_query_arg(array(
        'action' => 'trpro_download',
        'file' => base64_encode($file_path),
        'trainer' => $trainer_id,
        'nonce' => wp_create_nonce('trpro_download_' . $trainer_id)
    ), admin_url('admin-ajax.php'));
}

/**
 * Convertir les spécialités en tableau
 */
function trpro_parse_specialties($specialties_string) {
    if (empty($specialties_string)) {
        return array();
    }
    
    return array_map('trim', explode(',', $specialties_string));
}

/**
 * Obtenir le statut traduit
 */
function trpro_get_status_label($status) {
    $labels = array(
        'pending' => __('En attente', 'trainer-registration-pro'),
        'approved' => __('Approuvé', 'trainer-registration-pro'),
        'rejected' => __('Rejeté', 'trainer-registration-pro'),
        'suspended' => __('Suspendu', 'trainer-registration-pro')
    );
    
    return isset($labels[$status]) ? $labels[$status] : $status;
}

/**
 * Vérifier si le plugin est configuré
 */
function trpro_is_configured() {
    $required_options = array(
        'trainer_contact_email',
        'trainer_company_name'
    );
    
    foreach ($required_options as $option) {
        if (empty(get_option($option))) {
            return false;
        }
    }
    
    return true;
}

/**
 * Vérifier l'intégrité de la base de données
 */
function trpro_check_database_integrity() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'trainer_registrations';
    
    // Vérifier si la table existe
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        return array(
            'status' => 'error',
            'message' => 'La table principale n\'existe pas'
        );
    }
    
    // Vérifier les colonnes importantes
    $required_columns = array(
        'id', 'first_name', 'last_name', 'email', 'phone',
        'specialties', 'intervention_regions', 'experience',
        'cv_file', 'status', 'created_at'
    );
    
    $missing_columns = array();
    
    foreach ($required_columns as $column) {
        $result = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM $table_name LIKE %s",
            $column
        ));
        
        if (empty($result)) {
            $missing_columns[] = $column;
        }
    }
    
    if (!empty($missing_columns)) {
        return array(
            'status' => 'warning',
            'message' => 'Colonnes manquantes : ' . implode(', ', $missing_columns)
        );
    }
    
    return array(
        'status' => 'success',
        'message' => 'Structure de la base de données correcte'
    );
}

// Ajouter une page de diagnostic dans l'admin
add_action('admin_menu', function() {
    add_submenu_page(
        'trainer-registration',
        'Diagnostic DB',
        'Diagnostic DB',
        'manage_options',
        'trainer-db-diagnostic',
        'trpro_display_db_diagnostic'
    );
});

function trpro_display_db_diagnostic() {
    $check = trpro_check_database_integrity();
    ?>
    <div class="wrap">
        <h1>Diagnostic de la Base de Données</h1>
        
        <div class="notice notice-<?php echo $check['status'] === 'success' ? 'success' : 'warning'; ?>">
            <p><?php echo esc_html($check['message']); ?></p>
        </div>
        
        <?php if ($check['status'] !== 'success'): ?>
            <form method="post" action="">
                <?php wp_nonce_field('repair_database', 'repair_nonce'); ?>
                <p>
                    <button type="submit" name="repair_database" class="button button-primary">
                        Réparer la base de données
                    </button>
                </p>
            </form>
        <?php endif; ?>
        
        <h2>Structure actuelle</h2>
        <?php
        global $wpdb;
        $table_name = $wpdb->prefix . 'trainer_registrations';
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        ?>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Colonne</th>
                    <th>Type</th>
                    <th>Null</th>
                    <th>Clé</th>
                    <th>Par défaut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($columns as $column): ?>
                    <tr>
                        <td><strong><?php echo esc_html($column->Field); ?></strong></td>
                        <td><?php echo esc_html($column->Type); ?></td>
                        <td><?php echo esc_html($column->Null); ?></td>
                        <td><?php echo esc_html($column->Key); ?></td>
                        <td><?php echo esc_html($column->Default ?? 'NULL'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}