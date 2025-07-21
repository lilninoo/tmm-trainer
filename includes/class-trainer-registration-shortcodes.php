<?php
/**
 * CORRECTION : Classe shortcodes nettoyée et optimisée
 * ✅ Élimination des doublons avec class-trainer-registration-public.php
 * 
 * Fichier: includes/class-trainer-registration-shortcodes.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class TrainerRegistrationShortcodes {

    public function __construct() {
        // ✅ CORRECTION : Seulement les shortcodes, pas les handlers AJAX
        add_shortcode('trainer_home', array($this, 'display_home_page'));
        add_shortcode('trainer_registration_form', array($this, 'display_registration_form'));
        add_shortcode('trainer_list', array($this, 'display_trainer_list'));
        add_shortcode('trainer_search', array($this, 'display_trainer_search'));
        add_shortcode('trainer_profile', array($this, 'display_trainer_profile'));
        add_shortcode('trainer_stats', array($this, 'display_trainer_stats'));
        
        // ✅ SUPPRIMÉ : Handlers AJAX (gérés dans class-trainer-registration-public.php)
        // add_action('wp_ajax_search_trainers_modern', array($this, 'ajax_search_trainers'));
        // add_action('wp_ajax_nopriv_search_trainers_modern', array($this, 'ajax_search_trainers'));
        
        // Enqueue conditionnel uniquement
        add_action('wp_footer', array($this, 'enqueue_shortcode_assets'));
    }

    /**
     * ✅ CORRECTION : Liste avec récupération BDD réelle et sécurisée
     */
    public function display_trainer_list($atts) {
        global $wpdb;
        
        $atts = shortcode_atts(array(
            'per_page' => 12,
            'show_search' => 'true',
            'show_filters' => 'true',
            'view_mode' => 'grid',
            'show_pagination' => 'true'
        ), $atts);

        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        // ✅ CORRECTION : Vérifier que la table existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            return '<div class="trpro-error">Table de données non trouvée. Veuillez contacter l\'administrateur.</div>';
        }
        
        // Gestion de la pagination
        $page = get_query_var('paged') ? get_query_var('paged') : 1;
        $offset = ($page - 1) * intval($atts['per_page']);
        
        // Gestion des filtres URL avec sécurité
        $where_conditions = array("status = 'approved'");
        $params = array();
        
        // Filtre par spécialité si fourni dans l'URL
        if (isset($_GET['specialty']) && !empty($_GET['specialty'])) {
            $specialty = sanitize_text_field($_GET['specialty']);
            $where_conditions[] = 'specialties LIKE %s';
            $params[] = '%' . $wpdb->esc_like($specialty) . '%';
        }
        
        // ✅ NOUVEAU : Filtre par région
        if (isset($_GET['region']) && !empty($_GET['region'])) {
            $region = sanitize_text_field($_GET['region']);
            $where_conditions[] = 'intervention_regions LIKE %s';
            $params[] = '%' . $wpdb->esc_like($region) . '%';
        }
        
        // Filtre de recherche si fourni dans l'URL
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = sanitize_text_field($_GET['search']);
            $where_conditions[] = '(specialties LIKE %s OR bio LIKE %s OR experience LIKE %s OR company LIKE %s)';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        // Récupérer le nombre total avec sécurité
        $count_query = "SELECT COUNT(*) FROM $table_name $where_clause";
        if (!empty($params)) {
            $count_query = $wpdb->prepare($count_query, $params);
        }
        $total_trainers = intval($wpdb->get_var($count_query));
        
        if ($total_trainers === null) {
            error_log('Trainer List Error: ' . $wpdb->last_error);
            return '<div class="trpro-error">Erreur lors du chargement des formateurs.</div>';
        }
        
        // Récupérer les formateurs pour la page actuelle
        $trainers_query = "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $final_params = array_merge($params, array(intval($atts['per_page']), $offset));
        $trainers_query = $wpdb->prepare($trainers_query, $final_params);
        $trainers = $wpdb->get_results($trainers_query);
        
        if ($trainers === null) {
            error_log('Trainer List Error: ' . $wpdb->last_error);
            return '<div class="trpro-error">Erreur lors du chargement des formateurs.</div>';
        }

        // Log pour debug
        error_log("TRP Shortcode: Found {$total_trainers} total trainers, showing " . count($trainers) . " on page {$page}");

        ob_start();
        
        // Variables pour le template
        $this->set_template_vars(array(
            'trainers' => $trainers,
            'total_trainers' => $total_trainers,
            'atts' => $atts,
            'current_page' => $page
        ));
        
        // ✅ CORRECTION : Utiliser le template avec gestion d'erreur
        $template_path = TRAINER_REGISTRATION_PLUGIN_PATH . 'public/partials/trainer-list-modern.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="trpro-error">Template trainer-list-modern.php non trouvé</div>';
            error_log('TRP Template Error: trainer-list-modern.php not found at ' . $template_path);
        }
        
        return ob_get_clean();
    }

    /**
     * ✅ NOUVEAU : Méthode pour passer des variables aux templates
     */
    private function set_template_vars($vars) {
        foreach ($vars as $key => $value) {
            $GLOBALS["trpro_template_{$key}"] = $value;
        }
    }

    /**
     * ✅ CORRECTION : Shortcode profil individuel sécurisé
     */
    public function display_trainer_profile($atts) {
        global $wpdb;
        
        $atts = shortcode_atts(array(
            'id' => 0,
            'show_contact' => 'true',
            'show_bio' => 'true',
            'show_experience' => 'true'
        ), $atts);

        $trainer_id = intval($atts['id']);
        if ($trainer_id === 0) {
            return '<div class="trpro-error">ID de formateur requis</div>';
        }

        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        // ✅ CORRECTION : Requête sécurisée
        $trainer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND status = 'approved'",
            $trainer_id
        ));

        if (!$trainer) {
            return '<div class="trpro-error">Formateur non trouvé ou non approuvé</div>';
        }
        
        // ✅ Anonymiser le nom
        $trainer->display_name = $this->get_anonymized_name($trainer->first_name, $trainer->last_name);

        ob_start();
        
        $template_path = TRAINER_REGISTRATION_PLUGIN_PATH . 'public/partials/trainer-profile.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Template de fallback amélioré
            $this->display_trainer_profile_fallback($trainer, $atts);
        }
        
        return ob_get_clean();
    }

    /**
     * ✅ UNIQUE fonction d'anonymisation (centralisée)
     */
    private function get_anonymized_name($first_name, $last_name) {
        if (empty($last_name) || empty($first_name)) {
            return 'Formateur Expert';
        }
        return strtoupper(substr($last_name, 0, 1)) . '. ' . $first_name;
    }

    /**
     * Shortcode pour la page d'accueil
     */
    public function display_home_page($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Plateforme de Formateurs IT Excellence',
            'subtitle' => 'Connectons les talents IT avec les entreprises d\'exception',
            'description' => 'Découvrez notre réseau exclusif de formateurs experts en informatique, administration système, cloud, DevOps, sécurité et télécommunications.'
        ), $atts);

        ob_start();
        
        $template_path = TRAINER_REGISTRATION_PLUGIN_PATH . 'public/partials/trainer-home.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="trpro-error">Template trainer-home.php non trouvé</div>';
        }
        
        return ob_get_clean();
    }

    /**
     * Shortcode pour le formulaire d'inscription
     */
    public function display_registration_form($atts) {
        $atts = shortcode_atts(array(
            'show_header' => 'true',
            'redirect_success' => '',
            'form_id' => 'trpro-registration-form'
        ), $atts);

        ob_start();
        
        $template_path = TRAINER_REGISTRATION_PLUGIN_PATH . 'public/partials/trainer-registration-form.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="trpro-error">Template trainer-registration-form.php non trouvé</div>';
        }
        
        return ob_get_clean();
    }

    /**
     * ✅ CORRECTION : Shortcode de recherche unifié
     */
    public function display_trainer_search($atts) {
        $atts = shortcode_atts(array(
            'show_suggestions' => 'true',
            'max_results' => 20,
            'show_filters' => 'true',
            'placeholder' => 'Rechercher par compétence, technologie, certification...'
        ), $atts);

        ob_start();
        
        $template_path = TRAINER_REGISTRATION_PLUGIN_PATH . 'public/partials/trainer-search.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Version simplifiée intégrée
            echo '<div class="trpro-search-simple">';
            echo '<div class="trpro-search-form">';
            echo '<input type="text" id="trpro-quick-search" placeholder="' . esc_attr($atts['placeholder']) . '" class="trpro-search-input">';
            echo '<button type="button" class="trpro-search-btn"><i class="fas fa-search"></i></button>';
            echo '</div>';
            echo '</div>';
        }
        
        return ob_get_clean();
    }

    /**
     * Shortcode pour afficher les statistiques
     */
    public function display_trainer_stats($atts) {
        global $wpdb;
        
        $atts = shortcode_atts(array(
            'show_total' => 'true',
            'show_specialties' => 'true',
            'show_chart' => 'false',
            'style' => 'cards'
        ), $atts);

        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        // ✅ CORRECTION : Vérifier que la table existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            return '<div class="trpro-error">Données statistiques non disponibles</div>';
        }
        
        // Récupérer les statistiques avec gestion d'erreur
        $stats = array(
            'total' => intval($wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'approved'") ?: 0),
            'pending' => intval($wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'") ?: 0),
            'specialties' => intval($wpdb->get_var("SELECT COUNT(DISTINCT specialties) FROM $table_name WHERE status = 'approved'") ?: 0),
            'this_month' => intval($wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'approved' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())") ?: 0)
        );

        ob_start();
        include_once __DIR__ . '/../public/partials/trainer-stats.php';
        return ob_get_clean();
    }

    /**
     * ✅ CORRECTION : Enqueue conditionnel et cohérent
     */
    public function enqueue_shortcode_assets() {
        global $post;
        
        if (!$post) return;
        
        $content = $post->post_content;
        $has_trainer_shortcode = (
            has_shortcode($content, 'trainer_home') ||
            has_shortcode($content, 'trainer_registration_form') ||
            has_shortcode($content, 'trainer_list') ||
            has_shortcode($content, 'trainer_search') ||
            has_shortcode($content, 'trainer_profile') ||
            has_shortcode($content, 'trainer_stats')
        );
        
        if (!$has_trainer_shortcode) return;
        
        // ✅ CORRECTION : Un seul script unifié
        wp_enqueue_script(
            'trpro-unified-script',
            TRAINER_REGISTRATION_PLUGIN_URL . 'public/js/trainer-unified.js',
            array('jquery'),
            TRAINER_REGISTRATION_VERSION,
            true
        );
        
        // Configuration unifiée
        wp_localize_script('trpro-unified-script', 'trainer_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('trainer_registration_nonce'),
            'contact_email' => get_option('trainer_contact_email', get_option('admin_email')),
            'messages' => array(
                'success' => __('Opération réussie', 'trainer-registration'),
                'error' => __('Une erreur s\'est produite', 'trainer-registration'),
                'loading' => __('Chargement...', 'trainer-registration'),
                'no_results' => __('Aucun résultat trouvé', 'trainer-registration')
            ),
            'regions' => array(
                'ile-de-france' => 'Île-de-France',
                'auvergne-rhone-alpes' => 'Auvergne-Rhône-Alpes',
                'nouvelle-aquitaine' => 'Nouvelle-Aquitaine',
                'occitanie' => 'Occitanie',
                'hauts-de-france' => 'Hauts-de-France',
                'grand-est' => 'Grand Est',
                'provence-alpes-cote-azur' => 'Provence-Alpes-Côte d\'Azur',
                'pays-de-la-loire' => 'Pays de la Loire',
                'bretagne' => 'Bretagne',
                'normandie' => 'Normandie',
                'bourgogne-franche-comte' => 'Bourgogne-Franche-Comté',
                'centre-val-de-loire' => 'Centre-Val de Loire',
                'corse' => 'Corse',
                'outre-mer' => 'Outre-mer (DOM-TOM)',
                'europe' => 'Europe',
                'international' => 'International',
                'distanciel' => 'Formation à distance'
            )
        ));
    }

    /**
     * ✅ NOUVEAU : Template de fallback pour le profil
     */
    private function display_trainer_profile_fallback($trainer, $atts) {
        $trainer_id = str_pad($trainer->id, 4, '0', STR_PAD_LEFT);
        $specialties = explode(', ', $trainer->specialties);
        ?>
        <div class="trpro-trainer-profile-modern">
            <div class="trpro-profile-header">
                <div class="trpro-profile-avatar">
                    <?php if (!empty($trainer->photo_file)): ?>
                        <?php 
                        $upload_dir = wp_upload_dir();
                        $photo_url = $upload_dir['baseurl'] . '/' . $trainer->photo_file;
                        ?>
                        <img src="<?php echo esc_url($photo_url); ?>" alt="Photo du formateur">
                    <?php else: ?>
                        <div class="trpro-avatar-placeholder">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="trpro-profile-info">
                    <h1><?php echo esc_html($trainer->display_name); ?> #<?php echo $trainer_id; ?></h1>
                    <?php if (!empty($trainer->company)): ?>
                        <p class="trpro-company"><?php echo esc_html($trainer->company); ?></p>
                    <?php endif; ?>
                    
                    <div class="trpro-profile-meta">
                        <?php if (!empty($trainer->availability)): ?>
                            <span class="trpro-meta-item">
                                <i class="fas fa-calendar-check"></i>
                                <?php echo esc_html(ucfirst(str_replace('-', ' ', $trainer->availability))); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($trainer->hourly_rate)): ?>
                            <span class="trpro-meta-item">
                                <i class="fas fa-euro-sign"></i>
                                <?php echo esc_html($trainer->hourly_rate); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="trpro-profile-content">
                <div class="trpro-specialties-section">
                    <h2><i class="fas fa-cogs"></i> Spécialités</h2>
                    <div class="trpro-specialties-list">
                        <?php foreach ($specialties as $specialty): ?>
                            <span class="trpro-specialty-badge"><?php echo esc_html(ucfirst(str_replace('-', ' ', trim($specialty)))); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php if ($atts['show_experience'] === 'true' && !empty($trainer->experience)): ?>
                    <div class="trpro-experience-section">
                        <h2><i class="fas fa-briefcase"></i> Expérience</h2>
                        <div class="trpro-experience-content">
                            <?php echo nl2br(esc_html($trainer->experience)); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_bio'] === 'true' && !empty($trainer->bio)): ?>
                    <div class="trpro-bio-section">
                        <h2><i class="fas fa-user"></i> Présentation</h2>
                        <div class="trpro-bio-content">
                            <?php echo nl2br(esc_html($trainer->bio)); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_contact'] === 'true'): ?>
                    <div class="trpro-contact-section">
                        <h2><i class="fas fa-envelope"></i> Contact</h2>
                        <div class="trpro-contact-buttons">
                            <?php
                            $contact_email = get_option('trainer_contact_email', get_option('admin_email'));
                            if ($contact_email):
                            ?>
                                <a href="mailto:<?php echo esc_attr($contact_email); ?>?subject=Contact formateur %23<?php echo $trainer_id; ?>" 
                                   class="trpro-btn trpro-btn-primary">
                                    <i class="fas fa-envelope"></i>
                                    Contacter ce formateur
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($trainer->linkedin_url)): ?>
                                <a href="<?php echo esc_url($trainer->linkedin_url); ?>" 
                                   target="_blank" 
                                   class="trpro-btn trpro-btn-outline">
                                    <i class="fab fa-linkedin"></i>
                                    Voir le profil LinkedIn
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .trpro-trainer-profile-modern {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .trpro-profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 48px 32px;
            display: flex;
            align-items: center;
            gap: 32px;
        }
        
        .trpro-profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid rgba(255, 255, 255, 0.2);
        }
        
        .trpro-profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .trpro-avatar-placeholder {
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }
        
        .trpro-profile-info h1 {
            font-size: 2.5rem;
            margin-bottom: 8px;
            font-weight: 700;
        }
        
        .trpro-company {
            font-size: 1.25rem;
            opacity: 0.9;
            margin-bottom: 16px;
        }
        
        .trpro-profile-meta {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }
        
        .trpro-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .trpro-profile-content {
            padding: 32px;
        }
        
        .trpro-profile-content > div {
            margin-bottom: 32px;
        }
        
        .trpro-profile-content h2 {
            color: #0a2540;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .trpro-profile-content h2 i {
            color: #635bff;
        }
        
        .trpro-specialties-list {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .trpro-specialty-badge {
            background: #f8fafc;
            color: #635bff;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
            border: 1px solid #e2e8f0;
        }
        
        .trpro-experience-content,
        .trpro-bio-content {
            background: #f8fafc;
            padding: 24px;
            border-radius: 8px;
            border-left: 4px solid #635bff;
            line-height: 1.6;
            color: #64748b;
        }
        
        .trpro-contact-buttons {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }
        
        .trpro-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }
        
        .trpro-btn-primary {
            background: #635bff;
            color: white;
        }
        
        .trpro-btn-primary:hover {
            background: #524cdc;
            transform: translateY(-1px);
            text-decoration: none;
            color: white;
        }
        
        .trpro-btn-outline {
            background: transparent;
            color: #635bff;
            border-color: #635bff;
        }
        
        .trpro-btn-outline:hover {
            background: #635bff;
            color: white;
            text-decoration: none;
        }
        
        @media (max-width: 768px) {
            .trpro-profile-header {
                flex-direction: column;
                text-align: center;
                padding: 32px 24px;
            }
            
            .trpro-profile-content {
                padding: 24px;
            }
            
            .trpro-contact-buttons {
                flex-direction: column;
            }
        }
        </style>
        <?php
    }

    /**
     * ✅ SUPPRIMÉ : perform_trainer_search() - dupliqué avec class-trainer-registration-public.php
     * ✅ SUPPRIMÉ : ajax_search_trainers() - conflit avec handlers AJAX
     * ✅ SUPPRIMÉ : ajax_get_all_trainers() - conflit avec handlers AJAX
     */
}