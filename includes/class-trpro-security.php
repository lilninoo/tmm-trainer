<?php
/**
 * Classe pour la gestion de la sécurité
 * 
 * Fichier: /wp-content/plugins/trainer-registration-plugin/includes/class-trpro-security.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class TrproSecurityManager {
    
    private static $instance = null;
    private $blocked_ips = array();
    private $rate_limits = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_security_hooks();
        $this->load_blocked_ips();
    }
    
    /**
     * Initialiser les hooks de sécurité
     */
    private function init_security_hooks() {
        add_action('wp_loaded', array($this, 'check_security'));
        add_action('admin_init', array($this, 'admin_security_checks'));
        add_filter('upload_mimes', array($this, 'restrict_file_types'));
        add_action('wp_ajax_nopriv_submit_trainer_registration', array($this, 'check_registration_security'), 1);
        add_action('wp_ajax_submit_trainer_registration', array($this, 'check_registration_security'), 1);
        
        // Protection contre les tentatives de force brute
        add_action('wp_login_failed', array($this, 'log_failed_login'));
        add_filter('authenticate', array($this, 'check_login_attempts'), 30, 3);
        
        // Nettoyage automatique des logs
        add_action('wp_scheduled_delete', array($this, 'cleanup_security_logs'));
    }
    
    /**
     * Vérifications de sécurité générales
     */
    public function check_security() {
        $ip = $this->get_client_ip();
        
        // Vérifier si l'IP est bloquée
        if ($this->is_ip_blocked($ip)) {
            $this->block_request('IP blocked');
        }
        
        // Vérifier la limitation de taux
        if ($this->is_rate_limited($ip)) {
            $this->block_request('Rate limit exceeded');
        }
        
        // Vérifier les tentatives d'attaque
        $this->check_malicious_requests();
    }
    
    /**
     * Vérifications de sécurité pour l'admin
     */
    public function admin_security_checks() {
        if (!is_admin()) {
            return;
        }
        
        // Vérifier les permissions pour les pages du plugin
        if (isset($_GET['page']) && strpos($_GET['page'], 'trainer-') === 0) {
            if (!current_user_can('manage_options')) {
                wp_die('Accès non autorisé.');
            }
        }
        
        // Vérifier les actions sensibles
        if (isset($_GET['action']) && in_array($_GET['action'], array('delete', 'approve', 'reject'))) {
            if (!wp_verify_nonce($_GET['nonce'] ?? '', 'trainer_admin_nonce')) {
                wp_die('Token de sécurité invalide.');
            }
        }
    }
    
    /**
     * Sécurité pour les inscriptions de formateurs
     */
    public function check_registration_security() {
        $ip = $this->get_client_ip();
        
        // Vérifier la limitation de taux pour les inscriptions
        $registrations_per_hour = $this->get_registration_rate($ip);
        if ($registrations_per_hour > 3) {
            wp_send_json_error(array(
                'message' => 'Trop de tentatives d\'inscription. Veuillez réessayer plus tard.'
            ));
        }
        
        // Vérifier la validité du nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'trainer_registration_nonce')) {
            wp_send_json_error(array(
                'message' => 'Token de sécurité invalide.'
            ));
        }
        
        // Vérifier les champs suspects
        if ($this->detect_spam_content($_POST)) {
            $this->log_security_event('spam_attempt', $ip, $_POST);
            wp_send_json_error(array(
                'message' => 'Contenu suspect détecté.'
            ));
        }
        
        // Enregistrer l'attempt d'inscription
        $this->log_registration_attempt($ip);
    }
    
    /**
     * Restreindre les types de fichiers
     */
    public function restrict_file_types($mimes) {
        $allowed_types = get_option('trainer_allowed_file_types', array('pdf', 'doc', 'docx'));
        $secure_mimes = array();
        
        // Mapping sécurisé des types MIME
        $mime_mapping = array(
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif'
        );
        
        foreach ($allowed_types as $type) {
            if (isset($mime_mapping[$type])) {
                $secure_mimes[$type] = $mime_mapping[$type];
            }
        }
        
        return $secure_mimes;
    }
    
    /**
     * Obtenir l'adresse IP du client
     */
    public function get_client_ip() {
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        );
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Si plusieurs IPs (x-forwarded-for), prendre la première
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Valider l'IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Vérifier si une IP est bloquée
     */
    public function is_ip_blocked($ip) {
        return in_array($ip, $this->blocked_ips);
    }
    
    /**
     * Vérifier la limitation de taux
     */
    public function is_rate_limited($ip) {
        $key = 'trpro_rate_limit_' . $ip;
        $attempts = get_transient($key);
        
        if ($attempts === false) {
            return false;
        }
        
        // Limite : 100 requêtes par heure
        return $attempts > 100;
    }
    
    /**
     * Incrémenter le compteur de taux pour une IP
     */
    public function increment_rate_limit($ip) {
        $key = 'trpro_rate_limit_' . $ip;
        $attempts = get_transient($key) ?: 0;
        $attempts++;
        
        set_transient($key, $attempts, HOUR_IN_SECONDS);
        
        return $attempts;
    }
    
    /**
     * Obtenir le taux d'inscription pour une IP
     */
    private function get_registration_rate($ip) {
        $key = 'trpro_reg_rate_' . $ip;
        return get_transient($key) ?: 0;
    }
    
    /**
     * Enregistrer une tentative d'inscription
     */
    private function log_registration_attempt($ip) {
        $key = 'trpro_reg_rate_' . $ip;
        $attempts = get_transient($key) ?: 0;
        $attempts++;
        
        set_transient($key, $attempts, HOUR_IN_SECONDS);
    }
    
    /**
     * Détecter les requêtes malveillantes
     */
    private function check_malicious_requests() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Patterns suspects
        $malicious_patterns = array(
            '/\b(union|select|insert|delete|drop|create|alter)\b/i',
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi',
            '/javascript:/i',
            '/\beval\s*\(/i',
            '/\.\.\//i'
        );
        
        foreach ($malicious_patterns as $pattern) {
            if (preg_match($pattern, $request_uri) || preg_match($pattern, $user_agent)) {
                $this->log_security_event('malicious_request', $this->get_client_ip(), array(
                    'user_agent' => $user_agent,
                    'request_uri' => $request_uri,
                    'pattern' => $pattern
                ));
                $this->block_request('Malicious request detected');
            }
        }
        
        // User agents suspects
        $blocked_agents = array(
            'sqlmap',
            'nikto',
            'havij',
            'dirbuster',
            'nessus'
        );
        
        foreach ($blocked_agents as $agent) {
            if (stripos($user_agent, $agent) !== false) {
                $this->block_request('Blocked user agent');
            }
        }
    }
    
    /**
     * Détecter le contenu spam
     */
    private function detect_spam_content($data) {
        $spam_indicators = array(
            'viagra', 'cialis', 'pharmacy', 'casino', 'poker',
            'loan', 'mortgage', 'insurance', 'bitcoin', 'crypto',
            'weight loss', 'make money', 'work from home'
        );
        
        $text_fields = array('experience', 'bio', 'first_name', 'last_name');
        
        foreach ($text_fields as $field) {
            if (isset($data[$field])) {
                $content = strtolower($data[$field]);
                foreach ($spam_indicators as $indicator) {
                    if (strpos($content, $indicator) !== false) {
                        return true;
                    }
                }
            }
        }
        
        // Vérifier la longueur excessive (possible spam)
        if (isset($data['experience']) && strlen($data['experience']) > 5000) {
            return true;
        }
        
        // Vérifier les liens suspects
        if (isset($data['bio']) || isset($data['experience'])) {
            $combined_text = ($data['bio'] ?? '') . ' ' . ($data['experience'] ?? '');
            if (preg_match_all('/https?:\/\//', $combined_text) > 3) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Valider la sécurité des fichiers
     */
    public function validate_file_security($file) {
        if (empty($file['tmp_name'])) {
            return array('valid' => false, 'error' => 'Aucun fichier fourni');
        }
        
        // Vérifier la taille
        $max_size = get_option('trainer_max_cv_size', 5) * 1024 * 1024;
        if ($file['size'] > $max_size) {
            return array('valid' => false, 'error' => 'Fichier trop volumineux');
        }
        
        // Vérifier l'extension
        $allowed_extensions = get_option('trainer_allowed_file_types', array('pdf', 'doc', 'docx'));
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            return array('valid' => false, 'error' => 'Type de fichier non autorisé');
        }
        
        // Vérifier le type MIME réel
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mimes = array(
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/jpeg',
            'image/png',
            'image/gif'
        );
        
        if (!in_array($mime_type, $allowed_mimes)) {
            return array('valid' => false, 'error' => 'Type MIME non autorisé: ' . $mime_type);
        }
        
        // Scan basique pour détecter du contenu suspect
        $file_content = file_get_contents($file['tmp_name'], false, null, 0, 1024);
        
        // Vérifier les signatures de fichiers malveillants
        $malicious_signatures = array(
            "\x4D\x5A", // PE executable
            "#!/bin/sh",
            "#!/bin/bash",
            "<?php",
            "<script"
        );
        
        foreach ($malicious_signatures as $signature) {
            if (strpos($file_content, $signature) !== false) {
                return array('valid' => false, 'error' => 'Fichier potentiellement malveillant');
            }
        }
        
        return array('valid' => true);
    }
    
    /**
     * Bloquer une requête
     */
    private function block_request($reason) {
        $ip = $this->get_client_ip();
        
        $this->log_security_event('blocked_request', $ip, array(
            'reason' => $reason,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? ''
        ));
        
        // Ajouter l'IP à la liste temporaire des IPs bloquées
        $this->temporarily_block_ip($ip);
        
        status_header(403);
        wp_die('Accès refusé', 'Sécurité', array('response' => 403));
    }
    
    /**
     * Bloquer temporairement une IP
     */
    private function temporarily_block_ip($ip) {
        $key = 'trpro_blocked_' . $ip;
        set_transient($key, true, 15 * MINUTE_IN_SECONDS); // 15 minutes
    }
    
    /**
     * Charger les IPs bloquées
     */
    private function load_blocked_ips() {
        $this->blocked_ips = get_option('trpro_blocked_ips', array());
    }
    
    /**
     * Ajouter une IP à la liste de blocage permanent
     */
    public function block_ip_permanently($ip) {
        if (!in_array($ip, $this->blocked_ips)) {
            $this->blocked_ips[] = $ip;
            update_option('trpro_blocked_ips', $this->blocked_ips);
        }
    }
    
    /**
     * Débloquer une IP
     */
    public function unblock_ip($ip) {
        $key = array_search($ip, $this->blocked_ips);
        if ($key !== false) {
            unset($this->blocked_ips[$key]);
            update_option('trpro_blocked_ips', array_values($this->blocked_ips));
        }
        
        // Supprimer aussi le blocage temporaire
        delete_transient('trpro_blocked_' . $ip);
    }
    
    /**
     * Logger les événements de sécurité
     */
    public function log_security_event($type, $ip, $data = array()) {
        if (!get_option('trainer_debug_mode', 0)) {
            return;
        }
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'type' => $type,
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'data' => $data
        );
        
        // Stocker dans les options (limiter à 100 entrées)
        $logs = get_option('trpro_security_logs', array());
        array_unshift($logs, $log_entry);
        $logs = array_slice($logs, 0, 100);
        
        update_option('trpro_security_logs', $logs);
        
        // Log également dans error_log si critique
        if (in_array($type, array('malicious_request', 'blocked_request', 'spam_attempt'))) {
            error_log("TrproSecurity [{$type}]: IP {$ip} - " . json_encode($data));
        }
    }
    
    /**
     * Logger les échecs de connexion
     */
    public function log_failed_login($username) {
        $ip = $this->get_client_ip();
        $key = 'trpro_login_fails_' . $ip;
        $attempts = get_transient($key) ?: 0;
        $attempts++;
        
        set_transient($key, $attempts, HOUR_IN_SECONDS);
        
        $this->log_security_event('failed_login', $ip, array(
            'username' => $username,
            'attempts' => $attempts
        ));
        
        // Bloquer après 10 tentatives
        if ($attempts >= 10) {
            $this->temporarily_block_ip($ip);
        }
    }
    
    /**
     * Vérifier les tentatives de connexion
     */
    public function check_login_attempts($user, $username, $password) {
        $ip = $this->get_client_ip();
        
        // Vérifier si l'IP est temporairement bloquée
        if (get_transient('trpro_blocked_' . $ip)) {
            return new WP_Error('too_many_attempts', 'Trop de tentatives de connexion. Réessayez plus tard.');
        }
        
        return $user;
    }
    
    /**
     * Obtenir les statistiques de sécurité
     */
    public function get_security_stats() {
        $logs = get_option('trpro_security_logs', array());
        
        $stats = array(
            'total_events' => count($logs),
            'blocked_requests' => 0,
            'failed_logins' => 0,
            'spam_attempts' => 0,
            'malicious_requests' => 0,
            'blocked_ips' => count($this->blocked_ips),
            'recent_events' => array_slice($logs, 0, 10)
        );
        
        foreach ($logs as $log) {
            if (isset($log['type'])) {
                switch ($log['type']) {
                    case 'blocked_request':
                        $stats['blocked_requests']++;
                        break;
                    case 'failed_login':
                        $stats['failed_logins']++;
                        break;
                    case 'spam_attempt':
                        $stats['spam_attempts']++;
                        break;
                    case 'malicious_request':
                        $stats['malicious_requests']++;
                        break;
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Nettoyer les logs de sécurité
     */
    public function cleanup_security_logs() {
        $logs = get_option('trpro_security_logs', array());
        
        // Garder seulement les logs des 30 derniers jours
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-30 days'));
        $filtered_logs = array();
        
        foreach ($logs as $log) {
            if (isset($log['timestamp']) && $log['timestamp'] > $cutoff_date) {
                $filtered_logs[] = $log;
            }
        }
        
        update_option('trpro_security_logs', $filtered_logs);
    }
    
    /**
     * Générer un token de sécurité
     */
    public function generate_security_token($action, $user_id = null) {
        $user_id = $user_id ?: get_current_user_id();
        $timestamp = time();
        
        $data = $action . '|' . $user_id . '|' . $timestamp;
        $hash = wp_hash($data, 'auth');
        
        return base64_encode($data . '|' . $hash);
    }
    
    /**
     * Vérifier un token de sécurité
     */
    public function verify_security_token($token, $action, $user_id = null, $max_age = 3600) {
        $decoded = base64_decode($token);
        $parts = explode('|', $decoded);
        
        if (count($parts) !== 4) {
            return false;
        }
        
        list($token_action, $token_user_id, $timestamp, $hash) = $parts;
        
        // Vérifier l'action
        if ($token_action !== $action) {
            return false;
        }
        
        // Vérifier l'utilisateur
        $user_id = $user_id ?: get_current_user_id();
        if ($token_user_id != $user_id) {
            return false;
        }
        
        // Vérifier l'âge du token
        if ((time() - $timestamp) > $max_age) {
            return false;
        }
        
        // Vérifier le hash
        $data = $token_action . '|' . $token_user_id . '|' . $timestamp;
        $expected_hash = wp_hash($data, 'auth');
        
        return hash_equals($expected_hash, $hash);
    }
    
    /**
     * Chiffrer des données sensibles
     */
    public function encrypt_data($data, $key = null) {
        if (!extension_loaded('openssl')) {
            return base64_encode($data); // Fallback basique
        }
        
        $key = $key ?: wp_salt('auth');
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Déchiffrer des données
     */
    public function decrypt_data($encrypted_data, $key = null) {
        if (!extension_loaded('openssl')) {
            return base64_decode($encrypted_data); // Fallback basique
        }
        
        $key = $key ?: wp_salt('auth');
        $data = base64_decode($encrypted_data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Test de sécurité général
     */
    public function run_security_tests() {
        $tests = array();
        
        // Test de la configuration SSL
        $tests['ssl_enabled'] = is_ssl();
        
        // Test des permissions de fichiers
        $upload_dir = wp_upload_dir();
        $tests['upload_dir_writable'] = is_writable($upload_dir['basedir']);
        
        // Test de la version de PHP
        $tests['php_version_secure'] = version_compare(PHP_VERSION, '7.4', '>=');
        
        // Test de la version de WordPress
        $tests['wp_version_recent'] = version_compare(get_bloginfo('version'), '5.0', '>=');
        
        // Test de la configuration de sécurité
        $tests['debug_mode_off'] = !get_option('trainer_debug_mode', 0);
        
        return $tests;
    }
}