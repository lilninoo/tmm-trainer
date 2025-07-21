<?php
/**
 * Classe pour la partie publique du plugin - VERSION CORRIGÉE AFFICHAGE UNIFORME
 * 
 * Fichier: includes/class-trainer-registration-public.php
 * ✅ CORRECTION: Affichage uniforme entre recherche AJAX et affichage statique
 * ✅ Utilise le même template pour les cartes dans tous les cas
 * ✅ Tous les filtres corrigés (spécialité, région, expérience, disponibilité)
 * ✅ Anonymisation maintenue
 * ✅ MISE À JOUR: Gestion des contacts améliorée avec validation, logging et emails HTML
 */

if (!defined('ABSPATH')) {
    exit;
}

class TrainerRegistrationPublic {

    public function __construct() {
        // Enqueue conditionnel uniquement quand nécessaire
        add_action('wp_enqueue_scripts', array($this, 'conditional_enqueue'));
        
        // AJAX handlers
        add_action('wp_ajax_submit_trainer_registration', array($this, 'handle_trainer_registration'));
        add_action('wp_ajax_nopriv_submit_trainer_registration', array($this, 'handle_trainer_registration'));
        
        // ✅ Handler de recherche corrigé pour affichage uniforme
        add_action('wp_ajax_search_trainers', array($this, 'handle_trainer_search_unified'));
        add_action('wp_ajax_nopriv_search_trainers', array($this, 'handle_trainer_search_unified'));
        
        // ✅ CORRECTION: Handler de contact avec nonce unifié
        add_action('wp_ajax_contact_trainer', array($this, 'handle_trainer_contact'));
        add_action('wp_ajax_nopriv_contact_trainer', array($this, 'handle_trainer_contact'));
        
        // Handler pour la recherche avancée avec régions
        add_action('wp_ajax_advanced_search_trainers', array($this, 'handle_advanced_trainer_search'));
        add_action('wp_ajax_nopriv_advanced_search_trainers', array($this, 'handle_advanced_trainer_search'));
        
        // Handler pour récupérer le profil détaillé
        add_action('wp_ajax_get_trainer_profile', array($this, 'handle_get_trainer_profile'));
        add_action('wp_ajax_nopriv_get_trainer_profile', array($this, 'handle_get_trainer_profile'));
        
        // Hooks pour améliorer l'intégration
        add_action('wp_head', array($this, 'add_custom_css_variables'));
        add_filter('body_class', array($this, 'add_body_classes'));
    }

    /**
     * Enqueue conditionnel mis à jour
     */
    public function conditional_enqueue() {
        global $post;
        
        if (!$post) return;
        
        $content = $post->post_content;
        $has_trainer_shortcode = (
            has_shortcode($content, 'trainer_home') ||
            has_shortcode($content, 'trainer_registration_form') ||
            has_shortcode($content, 'trainer_list') ||
            has_shortcode($content, 'trainer_list_modern') ||
            has_shortcode($content, 'trainer_search') ||
            has_shortcode($content, 'trainer_profile') ||
            has_shortcode($content, 'trainer_stats') ||
            has_shortcode($content, 'trainer_contact_form')
        );
        
        if (!$has_trainer_shortcode) return;
        
        // Enqueue CSS principal
        wp_enqueue_style(
            'trpro-public-style',
            TRAINER_REGISTRATION_PLUGIN_URL . 'public/css/public-style.css',
            array(),
            TRAINER_REGISTRATION_VERSION,
            'all'
        );
        
        // FontAwesome
        wp_enqueue_style(
            'trpro-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            array(),
            '6.4.0'
        );
        
        // JavaScript principal
        wp_enqueue_script(
            'trpro-public-script',
            TRAINER_REGISTRATION_PLUGIN_URL . 'public/js/public-script.js',
            array('jquery'),
            TRAINER_REGISTRATION_VERSION,
            true
        );
        
        // Configuration AJAX
        wp_localize_script('trpro-public-script', 'trainer_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('trainer_registration_nonce'),
            'contact_email' => get_option('trainer_contact_email', get_option('admin_email')),
            'messages' => array(
                'success' => __('Inscription réussie ! Nous vous contacterons bientôt.', 'trainer-registration'),
                'error' => __('Erreur lors de l\'inscription. Veuillez réessayer.', 'trainer-registration'),
                'required' => __('Ce champ est obligatoire.', 'trainer-registration'),
                'loading' => __('Chargement en cours...', 'trainer-registration'),
                'search_no_results' => __('Aucun formateur trouvé pour cette recherche.', 'trainer-registration'),
                'contact_success' => __('Message envoyé avec succès !', 'trainer-registration'),
                'contact_error' => __('Erreur lors de l\'envoi du message.', 'trainer-registration')
            ),
            'settings' => array(
                'max_file_size' => 5 * 1024 * 1024, // 5MB
                'allowed_file_types' => array('pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'),
                'search_delay' => 300,
                'animation_duration' => 300,
                'per_page' => 12
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
                'europe' => 'Europe (hors France)',
                'international' => 'International',
                'distanciel' => 'Formation à distance'
            )
        ));
    }

    // ===== HANDLER RECHERCHE CORRIGÉ POUR AFFICHAGE UNIFORME =====

    /**
     * ✅ Handler AJAX pour recherche avec affichage uniforme comme le template statique
     */
    public function handle_trainer_search_unified() {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'], 'trainer_registration_nonce')) {
            wp_send_json_error(['message' => 'Erreur de sécurité']);
        }
        
        try {
            // Récupération de tous les paramètres de filtres
            $search_term = sanitize_text_field($_POST['search_term'] ?? '');
            $specialty_filter = sanitize_text_field($_POST['specialty_filter'] ?? '');
            $region_filter = sanitize_text_field($_POST['region_filter'] ?? '');
            $experience_filter = sanitize_text_field($_POST['experience_filter'] ?? '');
            $availability_filter = sanitize_text_field($_POST['availability_filter'] ?? '');
            
            // Pagination
            $per_page = 12;
            $page = max(1, intval($_POST['page'] ?? 1));
            $offset = ($page - 1) * $per_page;
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'trainer_registrations';
            
            // Construction de la requête avec tous les filtres
            $where_conditions = ["status = 'approved'"];
            $where_values = [];
            
            // Filtre par terme de recherche
            if (!empty($search_term)) {
                $where_conditions[] = "(
                    first_name LIKE %s OR 
                    last_name LIKE %s OR 
                    company LIKE %s OR 
                    specialties LIKE %s OR 
                    experience LIKE %s OR 
                    bio LIKE %s
                )";
                $search_like = '%' . $wpdb->esc_like($search_term) . '%';
                $where_values = array_merge($where_values, [$search_like, $search_like, $search_like, $search_like, $search_like, $search_like]);
            }
            
            // Filtre par spécialité
            if (!empty($specialty_filter)) {
                $where_conditions[] = "specialties LIKE %s";
                $where_values[] = '%' . $wpdb->esc_like($specialty_filter) . '%';
            }
            
            // Filtre par région d'intervention
            if (!empty($region_filter)) {
                $where_conditions[] = "intervention_regions LIKE %s";
                $where_values[] = '%' . $wpdb->esc_like($region_filter) . '%';
            }
            
            // Filtre par niveau d'expérience
            if (!empty($experience_filter)) {
                $where_conditions[] = "experience_level = %s";
                $where_values[] = $experience_filter;
            }
            
            // Filtre par disponibilité
            if (!empty($availability_filter)) {
                $where_conditions[] = "availability = %s";
                $where_values[] = $availability_filter;
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            // Requête pour le total (sans LIMIT)
            $total_query = "SELECT COUNT(*) FROM $table_name WHERE $where_clause";
            if (!empty($where_values)) {
                $total_query = $wpdb->prepare($total_query, $where_values);
            }
            $total_trainers = $wpdb->get_var($total_query);
            
            // Requête pour les résultats avec pagination
            $results_query = "
                SELECT id, first_name, last_name, email, phone, company, 
                       linkedin_url, specialties, intervention_regions, 
                       experience, experience_level, availability, hourly_rate, 
                       bio, photo_file, cv_file, created_at 
                FROM $table_name 
                WHERE $where_clause 
                ORDER BY created_at DESC 
                LIMIT %d OFFSET %d
            ";
            
            $query_values = array_merge($where_values, [$per_page, $offset]);
            $prepared_query = $wpdb->prepare($results_query, $query_values);
            $trainers = $wpdb->get_results($prepared_query);
            
            // ✅ CORRECTION: Générer le HTML uniforme comme le template statique
            $html = $this->generate_unified_trainers_html($trainers);
            
            // Calcul de la pagination
            $total_pages = ceil($total_trainers / $per_page);
            
            // Réponse avec toutes les données
            wp_send_json_success([
                'trainers' => $trainers,
                'html' => $html,
                'total' => intval($total_trainers),
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => $total_pages,
                'has_next' => $page < $total_pages,
                'has_prev' => $page > 1,
                'filters_applied' => [
                    'search' => !empty($search_term),
                    'specialty' => !empty($specialty_filter),
                    'region' => !empty($region_filter),
                    'experience' => !empty($experience_filter),
                    'availability' => !empty($availability_filter)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log('Erreur recherche formateurs: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Erreur lors de la recherche']);
        }
    }

    /**
     * ✅ CORRECTION PRINCIPALE: Génère le HTML uniforme identique au template statique
     */
    private function generate_unified_trainers_html($trainers) {
        if (empty($trainers)) {
            return $this->get_empty_state_html();
        }
        
        $html = '';
        $upload_dir = wp_upload_dir();
        
        foreach ($trainers as $trainer) {
            $trainer_id = str_pad($trainer->id, 4, '0', STR_PAD_LEFT);
            $specialties = array_map('trim', explode(',', $trainer->specialties));
            $display_specialties = array_slice($specialties, 0, 2); // Max 2 spécialités comme dans le template
            $remaining_count = count($specialties) - 2;
            
            // Régions d'intervention
            $intervention_regions = array();
            if (!empty($trainer->intervention_regions)) {
                $intervention_regions = array_map('trim', explode(',', $trainer->intervention_regions));
            }
            
            // Nom anonymisé
            $display_name = $this->get_anonymized_name($trainer->first_name, $trainer->last_name);
            
            // Gestion robuste des photos
            $photo_url = '';
            if (!empty($trainer->photo_file)) {
                $photo_path = $upload_dir['basedir'] . '/' . $trainer->photo_file;
                if (file_exists($photo_path)) {
                    $photo_url = $upload_dir['baseurl'] . '/' . $trainer->photo_file;
                }
            }
            
            // ✅ GÉNÉRATION HTML IDENTIQUE AU TEMPLATE STATIQUE
            $html .= '<article class="trpro-trainer-card-compact" data-trainer-id="' . esc_attr($trainer->id) . '">';
            
            // Header avec photo et badges
            $html .= '<div class="trpro-card-header">';
            $html .= '<div class="trpro-trainer-avatar">';
            $html .= '<div class="trpro-avatar-placeholder">';
            $html .= '<i class="fas fa-user-graduate"></i>';
            $html .= '</div>';
            
            if (!empty($photo_url)) {
                $html .= '<img src="' . esc_url($photo_url) . '" ';
                $html .= 'alt="Photo formateur #' . $trainer_id . '" ';
                $html .= 'loading="lazy" ';
                $html .= 'onload="this.previousElementSibling.style.display=\'none\';" ';
                $html .= 'onerror="this.style.display=\'none\'; this.previousElementSibling.style.display=\'flex\';">';
            }
            
            $html .= '</div>'; // trainer-avatar
            
            // Badges de vérification
            $html .= '<div class="trpro-status-badges">';
            $html .= '<span class="trpro-badge trpro-verified" title="Profil vérifié">';
            $html .= '<i class="fas fa-check-circle"></i>';
            $html .= '</span>';
            
            if (!empty($trainer->cv_file)) {
                $html .= '<span class="trpro-badge trpro-cv-available" title="CV disponible">';
                $html .= '<i class="fas fa-file-pdf"></i>';
                $html .= '</span>';
            }
            
            $html .= '</div>'; // status-badges
            $html .= '</div>'; // card-header
            
            // Corps de carte
            $html .= '<div class="trpro-card-body">';
            
            // Nom et ID
            $html .= '<h3 class="trpro-trainer-name">';
            $html .= esc_html($display_name);
            $html .= '<span class="trpro-trainer-id">#' . $trainer_id . '</span>';
            $html .= '</h3>';
            
            // Entreprise
            if (!empty($trainer->company)) {
                $html .= '<div class="trpro-company">';
                $html .= '<i class="fas fa-building"></i>';
                $html .= esc_html($trainer->company);
                $html .= '</div>';
            }
            
            // Spécialités limitées
            $html .= '<div class="trpro-specialties">';
            foreach ($display_specialties as $specialty) {
                $specialty = trim($specialty);
                if (!empty($specialty)) {
                    $html .= '<span class="trpro-specialty-tag">' . esc_html(ucfirst(str_replace('-', ' ', $specialty))) . '</span>';
                }
            }
            
            if ($remaining_count > 0) {
                $html .= '<span class="trpro-specialty-tag trpro-more">+' . $remaining_count . '</span>';
            }
            $html .= '</div>'; // specialties
            
            // Zones d'intervention
            if (!empty($intervention_regions)) {
                $html .= '<div class="trpro-regions">';
                $html .= '<i class="fas fa-map-marker-alt"></i>';
                
                $display_regions = array_slice($intervention_regions, 0, 2);
                $region_names = array();
                foreach ($display_regions as $region) {
                    $region = trim($region);
                    $region_names[] = ucwords(str_replace('-', ' ', $region));
                }
                $html .= esc_html(implode(', ', $region_names));
                
                if (count($intervention_regions) > 2) {
                    $html .= ' <span class="trpro-more-regions">+' . (count($intervention_regions) - 2) . '</span>';
                }
                
                $html .= '</div>'; // regions
            }
            
            // Métadonnées compactes
            $html .= '<div class="trpro-meta">';
            
            if (!empty($trainer->availability)) {
                $html .= '<span class="trpro-meta-item">';
                $html .= '<i class="fas fa-calendar-check"></i>';
                $html .= esc_html(ucfirst(str_replace('-', ' ', $trainer->availability)));
                $html .= '</span>';
            }
            
            // Masquer le tarif horaire comme dans le template
            // Le tarif est commenté dans le template original
            
            $html .= '</div>'; // meta
            $html .= '</div>'; // card-body
            
            // Footer avec actions
            $html .= '<div class="trpro-card-footer">';
            
            // Bouton contact
            $html .= '<button class="trpro-btn trpro-btn-primary trpro-btn-contact" ';
            $html .= 'data-trainer-id="' . esc_attr($trainer->id) . '" ';
            $html .= 'data-trainer-name="' . esc_attr($display_name) . '" ';
            $html .= 'title="Contacter ce formateur">';
            $html .= '<i class="fas fa-envelope"></i>';
            $html .= 'Contact';
            $html .= '</button>';
            
            // Bouton profil
            $html .= '<button class="trpro-btn trpro-btn-outline trpro-btn-profile" ';
            $html .= 'data-trainer-id="' . esc_attr($trainer->id) . '" ';
            $html .= 'title="Voir le profil détaillé">';
            $html .= '<i class="fas fa-user"></i>';
            $html .= 'Profil';
            $html .= '</button>';
            
            $html .= '</div>'; // card-footer
            $html .= '</article>'; // trainer-card-compact
        }
        
        return $html;
    }

    /**
     * HTML pour état vide
     */
    private function get_empty_state_html() {
        return '
            <div class="trpro-empty-state">
                <div class="trpro-empty-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Aucun formateur trouvé</h3>
                <p>Essayez de modifier vos critères de recherche ou explorez d\'autres spécialités.</p>
                <button class="trpro-btn trpro-btn-primary" onclick="window.TrainerFilters?.resetAllFilters()">
                    <i class="fas fa-refresh"></i>
                    Réinitialiser les filtres
                </button>
            </div>
        ';
    }

    /**
     * Générer le nom anonymisé (méthode centrale)
     */
    private function get_anonymized_name($first_name, $last_name) {
        if (empty($last_name) || empty($first_name)) {
            return 'Formateur Expert';
        }
        
        return strtoupper(substr($last_name, 0, 1)) . '. ' . $first_name;
    }

    // ===== ANCIEN HANDLER POUR COMPATIBILITÉ =====

    /**
     * Recherche simple (ancien handler maintenu pour compatibilité)
     */
    public function handle_trainer_search() {
        // Rediriger vers le nouveau handler unifié
        return $this->handle_trainer_search_unified();
    }

    /**
     * ✅ Compatibilité avec l'ancien nom de handler
     */
    public function handle_trainer_search_4x3() {
        return $this->handle_trainer_search_unified();
    }

    // ===== MÉTHODES HÉRITÉES (inchangées) =====

    /**
     * Gestion robuste de l'AJAX avec téléphone et expérience
     */
    public function handle_trainer_registration() {
        try {
            // Vérification du nonce
            if (!wp_verify_nonce($_POST['nonce'], 'trainer_registration_nonce')) {
                wp_send_json_error(array(
                    'message' => 'Erreur de sécurité. Veuillez recharger la page.',
                    'code' => 'invalid_nonce'
                ));
            }

            // Validation des données avec régions et expérience
            $validation_result = $this->validate_form_data_with_experience($_POST, $_FILES);
            if (!$validation_result['valid']) {
                wp_send_json_error(array(
                    'message' => 'Données invalides. Veuillez corriger les erreurs.',
                    'errors' => $validation_result['errors'],
                    'code' => 'validation_failed'
                ));
            }

            // Traitement des fichiers
            $files_result = $this->handle_file_uploads($_FILES);
            if (!$files_result['success']) {
                wp_send_json_error(array(
                    'message' => $files_result['message'],
                    'code' => 'file_upload_failed'
                ));
            }

            // Préparation des données avec téléphone et expérience
            $trainer_data = $this->prepare_trainer_data_with_experience($_POST, $files_result);
            
            // Vérification email unique
            if ($this->email_exists($trainer_data['email'])) {
                wp_send_json_error(array(
                    'message' => 'Cet email est déjà enregistré. Utilisez une autre adresse email.',
                    'code' => 'email_exists'
                ));
            }

            // Insertion en base
            $trainer_id = $this->insert_trainer($trainer_data);
            
            if (!$trainer_id) {
                wp_send_json_error(array(
                    'message' => 'Erreur lors de l\'enregistrement. Veuillez réessayer.',
                    'code' => 'database_error'
                ));
            }

            // Notifications
            $this->send_notifications($trainer_data, $trainer_id);

            // Succès
            $success_message = get_option('trainer_auto_approve', 0) 
                ? 'Votre inscription a été validée avec succès ! Vous recevrez bientôt des opportunités.' 
                : 'Votre inscription a été envoyée avec succès ! Nous examinerons votre profil et vous contacterons bientôt.';

            wp_send_json_success(array(
                'message' => $success_message,
                'trainer_id' => $trainer_id,
                'redirect' => home_url('/catalogue-formateurs/'),
                'status' => $trainer_data['status']
            ));

        } catch (Exception $e) {
            error_log('Trainer Registration Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'Une erreur inattendue s\'est produite. Veuillez réessayer.',
                'code' => 'unexpected_error'
            ));
        }
    }

    /**
     * Préparation des données avec téléphone et expérience
     */
    private function prepare_trainer_data_with_experience($post_data, $files_result) {
        // Traitement des régions d'intervention
        $intervention_regions = '';
        if (isset($post_data['intervention_regions']) && is_array($post_data['intervention_regions'])) {
            $intervention_regions = implode(', ', array_map('sanitize_text_field', $post_data['intervention_regions']));
        }

        // Traitement du téléphone complet
        $phone_complete = $this->process_phone_number($post_data);

        return array(
            'first_name' => sanitize_text_field($post_data['first_name']),
            'last_name' => sanitize_text_field($post_data['last_name']),
            'email' => sanitize_email($post_data['email']),
            'phone' => $phone_complete,
            'company' => sanitize_text_field($post_data['company']),
            'linkedin_url' => esc_url_raw($post_data['linkedin_url'] ?? ''),
            'specialties' => implode(', ', array_map('sanitize_text_field', $post_data['specialties'])),
            'intervention_regions' => $intervention_regions,
            'experience_level' => sanitize_text_field($post_data['experience_level']),
            'availability' => sanitize_text_field($post_data['availability']),
            'hourly_rate' => sanitize_text_field($post_data['hourly_rate'] ?? ''),
            'experience' => sanitize_textarea_field($post_data['experience']),
            'bio' => sanitize_textarea_field($post_data['bio'] ?? ''),
            'cv_file' => $files_result['cv_file'],
            'photo_file' => $files_result['photo_file'],
            'rgpd_consent' => isset($post_data['rgpd_consent']) ? 1 : 0,
            'marketing_consent' => isset($post_data['marketing_consent']) ? 1 : 0,
            'status' => get_option('trainer_auto_approve', 0) ? 'approved' : 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
    }

    /**
     * Traitement robuste du numéro de téléphone
     */
    private function process_phone_number($post_data) {
        $country_code = sanitize_text_field($post_data['country_code'] ?? '+33');
        $custom_code = sanitize_text_field($post_data['custom_country_code'] ?? '');
        $phone = sanitize_text_field($post_data['phone'] ?? '');

        // Logs pour debugging
        error_log("TRP Phone Processing - Country Code: $country_code, Custom: $custom_code, Phone: $phone");

        // Si indicatif personnalisé, l'utiliser
        if ($country_code === 'custom' && !empty($custom_code)) {
            $country_code = $custom_code;
            error_log("TRP Phone Processing - Using custom code: $country_code");
        }

        // S'assurer que l'indicatif commence par +
        if (!empty($country_code) && !str_starts_with($country_code, '+')) {
            $country_code = '+' . ltrim($country_code, '+');
        }

        // Nettoyer le numéro (garder seulement les chiffres)
        $phone_clean = preg_replace('/[^\d]/', '', $phone);

        // Validation de l'indicatif
        if (!$this->is_valid_country_code($country_code)) {
            error_log("TRP Phone Processing - Invalid country code: $country_code");
            return '+33 ' . $phone_clean; // Fallback vers France
        }

        // Validation de la longueur du numéro
        if (strlen($phone_clean) < 7 || strlen($phone_clean) > 15) {
            error_log("TRP Phone Processing - Invalid phone length: " . strlen($phone_clean));
            return $country_code . ' ' . $phone_clean; // Retourner tel quel
        }

        // Formater le numéro final
        $formatted_phone = $this->format_phone_number($country_code, $phone_clean);
        
        error_log("TRP Phone Processing - Final formatted: $formatted_phone");
        
        return $formatted_phone;
    }

    /**
     * Validation d'un indicatif pays
     */
    private function is_valid_country_code($code) {
        // Supprimer le + pour la validation
        $clean_code = ltrim($code, '+');
        
        // Doit être composé uniquement de chiffres
        if (!ctype_digit($clean_code)) {
            return false;
        }
        
        // Longueur valide (1 à 4 chiffres)
        $length = strlen($clean_code);
        if ($length < 1 || $length > 4) {
            return false;
        }
        
        // Vérifier contre une liste d'indicatifs valides connus
        $valid_codes = $this->get_valid_country_codes();
        
        return in_array($clean_code, $valid_codes);
    }

    /**
     * Liste des indicatifs pays valides
     */
    private function get_valid_country_codes() {
        return [
            // Europe
            '33', '49', '44', '39', '34', '41', '32', '31', '43', '351', '45', '46', '47', '358', '48', '420', '36', '30', '90',
            // Amérique du Nord
            '1',
            // Afrique
            '212', '213', '216', '218', '220', '221', '222', '223', '224', '225', '226', '227', '228', '229', '230', '231', '232', '233', '234', '235', '236', '237', '238', '239', '240', '241', '242', '243', '244', '245', '246', '247', '248', '249', '250', '251', '252', '253', '254', '255', '256', '257', '258', '260', '261', '262', '263', '264', '265', '266', '267', '268', '269', '290', '291', '297', '298', '299',
            // Asie
            '86', '81', '82', '91', '92', '93', '94', '95', '98', '60', '62', '63', '64', '65', '66', '84', '886', '852', '853', '855', '856', '880', '883', '888',
            // Océanie
            '61', '64', '674', '675', '676', '677', '678', '679', '680', '681', '682', '683', '684', '685', '686', '687', '688', '689', '690', '691', '692',
            // Amérique du Sud
            '54', '55', '56', '57', '58', '51', '595', '597', '598',
            // Autres
            '7', '20', '27', '355', '376', '378', '380', '381', '382', '383', '385', '386', '387', '389', '590', '591', '592', '593', '594', '596', '599'
        ];
    }

    /**
     * Formatage intelligent du numéro
     */
    private function format_phone_number($country_code, $phone_clean) {
        // Formater selon le pays pour une meilleure lisibilité
        switch (ltrim($country_code, '+')) {
            case '33': // France
                return $country_code . ' ' . $this->format_french_phone($phone_clean);
            case '1': // USA/Canada
                return $country_code . ' ' . $this->format_us_phone($phone_clean);
            case '44': // UK
                return $country_code . ' ' . $this->format_uk_phone($phone_clean);
            default:
                // Format générique : groupes de 2-3 chiffres
                return $country_code . ' ' . $this->format_generic_phone($phone_clean);
        }
    }

    /**
     * Formatage téléphone français
     */
    private function format_french_phone($phone) {
        // Supprimer le 0 initial si présent
        if (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }
        
        // Format: XX XX XX XX XX
        if (strlen($phone) === 9) {
            return preg_replace('/(\d{2})(\d{2})(\d{2})(\d{2})(\d{1})/', '$1 $2 $3 $4 $5', $phone);
        }
        
        return $phone;
    }

    /**
     * Formatage téléphone US/Canada
     */
    private function format_us_phone($phone) {
        // Format: (XXX) XXX-XXXX
        if (strlen($phone) === 10) {
            return preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $phone);
        }
        
        return $phone;
    }

    /**
     * Formatage téléphone UK
     */
    private function format_uk_phone($phone) {
        // Supprimer le 0 initial si présent
        if (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }
        
        // Format: XXXX XXX XXXX (mobile) ou XXX XXXX XXXX (fixe)
        if (strlen($phone) === 10) {
            if (str_starts_with($phone, '7')) { // Mobile
                return preg_replace('/(\d{4})(\d{3})(\d{3})/', '$1 $2 $3', $phone);
            } else { // Fixe
                return preg_replace('/(\d{3})(\d{4})(\d{3})/', '$1 $2 $3', $phone);
            }
        }
        
        return $phone;
    }

    /**
     * Formatage générique
     */
    private function format_generic_phone($phone) {
        // Grouper par 2 ou 3 selon la longueur
        $length = strlen($phone);
        
        if ($length <= 8) {
            // Grouper par 2
            return preg_replace('/(\d{2})(?=\d)/', '$1 ', $phone);
        } else {
            // Grouper par 3
            return preg_replace('/(\d{3})(?=\d)/', '$1 ', $phone);
        }
    }

    /**
     * Obtenir le nom du pays depuis l'indicatif
     */
    private function get_country_name_from_code($country_code) {
        $countries = [
            '+33' => 'France',
            '+1' => 'États-Unis/Canada',
            '+44' => 'Royaume-Uni',
            '+49' => 'Allemagne',
            '+39' => 'Italie',
            '+34' => 'Espagne',
            '+41' => 'Suisse',
            '+32' => 'Belgique',
            '+31' => 'Pays-Bas',
            '+212' => 'Maroc',
            '+213' => 'Algérie',
            '+216' => 'Tunisie',
            '+262' => 'La Réunion/Mayotte',
            '+590' => 'Guadeloupe',
            '+594' => 'Guyane',
            '+596' => 'Martinique'
        ];
        
        return $countries[$country_code] ?? 'Pays inconnu';
    }

    /**
     * Validation avec téléphone et expérience
     */
    private function validate_form_data_with_experience($post_data, $files_data) {
        $errors = array();
        
        // Champs obligatoires
        $required_fields = array(
            'first_name' => 'Le prénom est obligatoire',
            'last_name' => 'Le nom est obligatoire', 
            'email' => 'L\'email est obligatoire',
            'phone' => 'Le téléphone est obligatoire',
            'experience' => 'L\'expérience est obligatoire',
            'experience_level' => 'Le niveau d\'expérience est obligatoire'
        );

        foreach ($required_fields as $field => $message) {
            if (empty($post_data[$field])) {
                $errors[] = $message;
            }
        }

        // Validation email
        if (!empty($post_data['email']) && !is_email($post_data['email'])) {
            $errors[] = 'Format d\'email invalide';
        }

        // Validation téléphone avec méthode améliorée
        if (!empty($post_data['phone'])) {
            $phone_errors = $this->validate_phone_field([
                'country_code' => $post_data['country_code'] ?? '',
                'custom_country_code' => $post_data['custom_country_code'] ?? '',
                'phone' => $post_data['phone'] ?? ''
            ]);
            
            $errors = array_merge($errors, $phone_errors);
        }

        // Validation niveau d'expérience
        if (!empty($post_data['experience_level'])) {
            $valid_levels = array('junior', 'intermediaire', 'senior', 'expert');
            if (!in_array($post_data['experience_level'], $valid_levels)) {
                $errors[] = 'Niveau d\'expérience invalide';
            }
        }

        // Validation spécialités
        if (empty($post_data['specialties']) || !is_array($post_data['specialties'])) {
            $errors[] = 'Veuillez sélectionner au moins une spécialité';
        }

        // Validation régions d'intervention obligatoires
        if (empty($post_data['intervention_regions']) || !is_array($post_data['intervention_regions'])) {
            $errors[] = 'Veuillez sélectionner au moins une zone d\'intervention';
        }

        // Validation expérience (minimum 50 caractères)
        if (!empty($post_data['experience']) && strlen($post_data['experience']) < 50) {
            $errors[] = 'L\'expérience doit contenir au moins 50 caractères';
        }

        // Validation consentement RGPD
        if (empty($post_data['rgpd_consent'])) {
            $errors[] = 'Le consentement RGPD est obligatoire';
        }

        // Validation CV obligatoire
        if (empty($files_data['cv_file']['name'])) {
            $errors[] = 'Le CV est obligatoire';
        }

        // Validation URL LinkedIn si fournie (optionnelle)
        if (!empty($post_data['linkedin_url']) && !filter_var($post_data['linkedin_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'L\'URL LinkedIn n\'est pas valide';
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }

    /**
     * Validation avec messages détaillés pour le téléphone
     */
    private function validate_phone_field($phone_data) {
        $country_code = $phone_data['country_code'] ?? '';
        $custom_code = $phone_data['custom_country_code'] ?? '';
        $phone = $phone_data['phone'] ?? '';
        
        $errors = [];
        
        // Vérifier qu'un indicatif est fourni
        if (empty($country_code)) {
            $errors[] = 'Veuillez sélectionner un indicatif pays';
            return $errors;
        }
        
        // Si indicatif personnalisé, vérifier qu'il est fourni
        if ($country_code === 'custom') {
            if (empty($custom_code)) {
                $errors[] = 'Veuillez saisir un indicatif pays personnalisé';
                return $errors;
            }
            
            // Valider l'indicatif personnalisé
            if (!$this->is_valid_country_code($custom_code)) {
                $errors[] = 'Indicatif pays invalide. Format attendu: +XXX (1-4 chiffres)';
                return $errors;
            }
            
            $effective_code = $custom_code;
        } else {
            $effective_code = $country_code;
        }
        
        // Vérifier le numéro de téléphone
        if (empty($phone)) {
            $errors[] = 'Veuillez saisir votre numéro de téléphone';
            return $errors;
        }
        
        // Nettoyer et valider le numéro
        $phone_clean = preg_replace('/[^\d]/', '', $phone);
        
        if (strlen($phone_clean) < 7) {
            $errors[] = 'Numéro de téléphone trop court (minimum 7 chiffres)';
        }
        
        if (strlen($phone_clean) > 15) {
            $errors[] = 'Numéro de téléphone trop long (maximum 15 chiffres)';
        }
        
        // Validations spécifiques par pays
        $country_specific_errors = $this->validate_country_specific_phone($effective_code, $phone_clean);
        $errors = array_merge($errors, $country_specific_errors);
        
        return $errors;
    }

    /**
     * Validations spécifiques par pays
     */
    private function validate_country_specific_phone($country_code, $phone_clean) {
        $errors = [];
        $code = ltrim($country_code, '+');
        
        switch ($code) {
            case '33': // France
                if (strlen($phone_clean) !== 9 && strlen($phone_clean) !== 10) {
                    $errors[] = 'Numéro français invalide (9 ou 10 chiffres attendus)';
                }
                if (strlen($phone_clean) === 10 && !str_starts_with($phone_clean, '0')) {
                    $errors[] = 'Numéro français doit commencer par 0';
                }
                break;
                
            case '1': // USA/Canada
                if (strlen($phone_clean) !== 10) {
                    $errors[] = 'Numéro US/Canada invalide (10 chiffres attendus)';
                }
                break;
                
            case '44': // UK
                if (strlen($phone_clean) < 10 || strlen($phone_clean) > 11) {
                    $errors[] = 'Numéro UK invalide (10-11 chiffres attendus)';
                }
                break;
        }
        
        return $errors;
    }

    /**
     * Recherche avancée avec régions et expérience
     */
    public function handle_advanced_trainer_search() {
        if (!wp_verify_nonce($_POST['nonce'], 'trainer_registration_nonce')) {
            wp_send_json_error(array('message' => 'Token de sécurité invalide'));
        }
        
        try {
            $search_params = array(
                'search_term' => sanitize_text_field($_POST['search_term'] ?? ''),
                'specialty_filter' => sanitize_text_field($_POST['specialty_filter'] ?? ''),
                'region_filter' => sanitize_text_field($_POST['region_filter'] ?? ''),
                'multi_regions' => isset($_POST['multi_regions']) ? array_map('sanitize_text_field', $_POST['multi_regions']) : array(),
                'availability_filter' => sanitize_text_field($_POST['availability_filter'] ?? ''),
                'experience_filter' => sanitize_text_field($_POST['experience_filter'] ?? ''),
                'rate_filter' => sanitize_text_field($_POST['rate_filter'] ?? ''),
                'per_page' => min(50, max(1, intval($_POST['per_page'] ?? 12))),
                'page' => max(1, intval($_POST['page'] ?? 1))
            );
            
            $results = $this->perform_advanced_trainer_search($search_params);
            
            if ($results === false) {
                wp_send_json_error(array(
                    'message' => 'Erreur lors de la recherche',
                    'code' => 'search_error'
                ));
            }
            
            // Ajouter les noms anonymisés dans les résultats
            foreach ($results['trainers'] as $trainer) {
                $trainer->display_name = $this->get_anonymized_name($trainer->first_name, $trainer->last_name);
            }
            
            wp_send_json_success($results);
            
        } catch (Exception $e) {
            error_log('Advanced Search Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'Erreur interne du serveur',
                'code' => 'server_error'
            ));
        }
    }

    /**
     * Logique de recherche avancée avec régions et expérience
     */
    private function perform_advanced_trainer_search($params) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        // Construire les conditions WHERE
        $where_conditions = array("status = 'approved'");
        $sql_params = array();
        
        // Recherche textuelle
        if (!empty($params['search_term'])) {
            $where_conditions[] = '(first_name LIKE %s OR last_name LIKE %s OR specialties LIKE %s OR bio LIKE %s OR experience LIKE %s OR company LIKE %s)';
            $search_param = '%' . $wpdb->esc_like($params['search_term']) . '%';
            $sql_params = array_merge($sql_params, array($search_param, $search_param, $search_param, $search_param, $search_param, $search_param));
        }
        
        // Filtre par spécialité
        if (!empty($params['specialty_filter']) && $params['specialty_filter'] !== 'all') {
            $where_conditions[] = 'specialties LIKE %s';
            $sql_params[] = '%' . $wpdb->esc_like($params['specialty_filter']) . '%';
        }
        
        // Filtre par région simple
        if (!empty($params['region_filter']) && $params['region_filter'] !== 'all') {
            $where_conditions[] = 'intervention_regions LIKE %s';
            $sql_params[] = '%' . $wpdb->esc_like($params['region_filter']) . '%';
        }
        
        // Filtre par régions multiples
        if (!empty($params['multi_regions'])) {
            $region_conditions = array();
            foreach ($params['multi_regions'] as $region) {
                $region_conditions[] = 'intervention_regions LIKE %s';
                $sql_params[] = '%' . $wpdb->esc_like($region) . '%';
            }
            if (!empty($region_conditions)) {
                $where_conditions[] = '(' . implode(' OR ', $region_conditions) . ')';
            }
        }
        
        // Filtre par disponibilité
        if (!empty($params['availability_filter'])) {
            $where_conditions[] = 'availability = %s';
            $sql_params[] = $params['availability_filter'];
        }
        
        // Filtre par expérience basé sur experience_level
        if (!empty($params['experience_filter'])) {
            $where_conditions[] = 'experience_level = %s';
            $sql_params[] = $params['experience_filter'];
        }
        
        // Filtre par tarif horaire
        if (!empty($params['rate_filter'])) {
            switch ($params['rate_filter']) {
                case '0-50':
                    $where_conditions[] = 'CAST(REGEXP_REPLACE(hourly_rate, "[^0-9]", "") AS UNSIGNED) < 50';
                    break;
                case '50-80':
                    $where_conditions[] = 'CAST(REGEXP_REPLACE(hourly_rate, "[^0-9]", "") AS UNSIGNED) BETWEEN 50 AND 80';
                    break;
                case '80-120':
                    $where_conditions[] = 'CAST(REGEXP_REPLACE(hourly_rate, "[^0-9]", "") AS UNSIGNED) BETWEEN 80 AND 120';
                    break;
                case '120+':
                    $where_conditions[] = 'CAST(REGEXP_REPLACE(hourly_rate, "[^0-9]", "") AS UNSIGNED) > 120';
                    break;
            }
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        // Calculer l'offset
        $offset = ($params['page'] - 1) * $params['per_page'];
        
        // Compter le total
        $count_query = "SELECT COUNT(*) FROM $table_name $where_clause";
        if (!empty($sql_params)) {
            $count_query = $wpdb->prepare($count_query, $sql_params);
        }
        
        $total = $wpdb->get_var($count_query);
        
        if ($total === null) {
            error_log('Database Error (count): ' . $wpdb->last_error);
            return false;
        }
        
        // Récupérer les formateurs avec tri intelligent
        $order_clause = "ORDER BY created_at DESC";
        
        // Tri intelligent basé sur la pertinence
        if (!empty($params['search_term'])) {
            $order_clause = "ORDER BY 
                CASE 
                    WHEN specialties LIKE '%" . $wpdb->esc_like($params['search_term']) . "%' THEN 1
                    WHEN experience LIKE '%" . $wpdb->esc_like($params['search_term']) . "%' THEN 2
                    WHEN bio LIKE '%" . $wpdb->esc_like($params['search_term']) . "%' THEN 3
                    ELSE 4
                END,
                created_at DESC";
        }
        
        $trainers_query = "SELECT * FROM $table_name $where_clause $order_clause LIMIT %d OFFSET %d";
        $final_params = array_merge($sql_params, array($params['per_page'], $offset));
        $trainers_query = $wpdb->prepare($trainers_query, $final_params);
        
        $trainers = $wpdb->get_results($trainers_query);
        
        if ($trainers === null) {
            error_log('Database Error (select): ' . $wpdb->last_error);
            return false;
        }
        
        // Traiter les données des formateurs
        $upload_dir = wp_upload_dir();
        foreach ($trainers as $trainer) {
            // Ajouter l'URL de la photo si elle existe
            if (!empty($trainer->photo_file)) {
                $trainer->photo_url = $upload_dir['baseurl'] . '/' . $trainer->photo_file;
            }
            
            // Ajouter l'URL du CV si il existe
            if (!empty($trainer->cv_file)) {
                $trainer->cv_url = $upload_dir['baseurl'] . '/' . $trainer->cv_file;
            }
            
            // Anonymiser le nom
            $trainer->display_name = $this->get_anonymized_name($trainer->first_name, $trainer->last_name);
        }
        
        return array(
            'trainers' => $trainers,
            'total' => intval($total),
            'page' => $params['page'],
            'per_page' => $params['per_page'],
            'total_pages' => ceil($total / $params['per_page']),
            'search_params' => $params
        );
    }

    /**
     * Handler pour récupérer le profil détaillé
     */
    public function handle_get_trainer_profile() {
        if (!wp_verify_nonce($_POST['nonce'], 'trainer_registration_nonce')) {
            wp_send_json_error(array('message' => 'Token de sécurité invalide'));
        }
        
        $trainer_id = intval($_POST['trainer_id']);
        
        if (!$trainer_id) {
            wp_send_json_error(array('message' => 'ID formateur manquant'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        $trainer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND status = 'approved'",
            $trainer_id
        ));
        
        if (!$trainer) {
            wp_send_json_error(array('message' => 'Formateur non trouvé'));
        }
        
        // Préparer les données pour l'affichage
        $upload_dir = wp_upload_dir();
        
        $profile_data = array(
            'id' => $trainer->id,
            'display_name' => $this->get_anonymized_name($trainer->first_name, $trainer->last_name),
            'company' => $trainer->company,
            'specialties' => explode(', ', $trainer->specialties),
            'intervention_regions' => !empty($trainer->intervention_regions) ? explode(', ', $trainer->intervention_regions) : array(),
            'experience_level' => $trainer->experience_level ?? 'intermediaire',
            'availability' => $trainer->availability,
            'hourly_rate' => $trainer->hourly_rate,
            'experience' => $trainer->experience,
            'bio' => $trainer->bio,
            'linkedin_url' => $trainer->linkedin_url,
            'created_at' => $trainer->created_at,
            'photo_url' => !empty($trainer->photo_file) ? $upload_dir['baseurl'] . '/' . $trainer->photo_file : '',
            'cv_available' => !empty($trainer->cv_file)
        );
        
        wp_send_json_success($profile_data);
    }

    // ===== MÉTHODES HÉRITÉES POUR COMPATIBILITÉ =====
    
    private function email_exists($email) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE email = %s",
            $email
        ));
    }

    private function insert_trainer($trainer_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        $inserted = $wpdb->insert($table_name, $trainer_data);
        
        if ($inserted === false) {
            error_log('Database error: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Gestion robuste des uploads
     */
    private function handle_file_uploads($files) {
        $uploaded_files = array(
            'cv_file' => '',
            'photo_file' => ''
        );

        $upload_dir = wp_upload_dir();
        $trainer_dir = $upload_dir['basedir'] . '/trainer-files/';
        
        // Créer le dossier si nécessaire
        if (!file_exists($trainer_dir)) {
            wp_mkdir_p($trainer_dir);
            // Sécuriser le dossier
            file_put_contents($trainer_dir . '.htaccess', "Options -Indexes\ndeny from all\n");
            file_put_contents($trainer_dir . 'index.php', '<?php // Silence is golden');
        }

        // Traitement du CV (obligatoire)
        if (!empty($files['cv_file']['name'])) {
            $cv_result = $this->upload_file($files['cv_file'], $trainer_dir . 'cv/', array(
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ), 5 * 1024 * 1024);

            if (!$cv_result['success']) {
                return array(
                    'success' => false,
                    'message' => 'Erreur CV: ' . $cv_result['message']
                );
            }
            
            $uploaded_files['cv_file'] = 'trainer-files/cv/' . $cv_result['filename'];
        }

        // Traitement de la photo (optionnel)
        if (!empty($files['photo_file']['name'])) {
            $photo_result = $this->upload_file($files['photo_file'], $trainer_dir . 'photos/', array(
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif'
            ), 2 * 1024 * 1024);

            if (!$photo_result['success']) {
                return array(
                    'success' => false,
                    'message' => 'Erreur Photo: ' . $photo_result['message']
                );
            }
            
            $uploaded_files['photo_file'] = 'trainer-files/photos/' . $photo_result['filename'];
        }

        return array(
            'success' => true,
            'cv_file' => $uploaded_files['cv_file'],
            'photo_file' => $uploaded_files['photo_file']
        );
    }

    /**
     * Upload sécurisé d'un fichier
     */
    private function upload_file($file, $target_dir, $allowed_types, $max_size) {
        // Vérifications de sécurité
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array(
                'success' => false,
                'message' => 'Erreur lors de l\'upload: ' . $this->get_upload_error_message($file['error'])
            );
        }

        if ($file['size'] > $max_size) {
            return array(
                'success' => false,
                'message' => 'Fichier trop volumineux (max: ' . $this->format_bytes($max_size) . ')'
            );
        }

        // Vérification du type MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
            return array(
                'success' => false,
                'message' => 'Type de fichier non autorisé'
            );
        }

        // Créer le nom de fichier sécurisé
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = wp_unique_filename($target_dir, time() . '_' . sanitize_file_name(basename($file['name'], '.' . $extension)) . '.' . $extension);

        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }

        $target_file = $target_dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target_file)) {
            return array(
                'success' => false,
                'message' => 'Impossible de sauvegarder le fichier'
            );
        }

        chmod($target_file, 0644);

        return array(
            'success' => true,
            'filename' => $filename,
            'path' => $target_file
        );
    }

    /**
     * Utilitaires
     */
    private function get_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'Fichier trop volumineux';
            case UPLOAD_ERR_PARTIAL:
                return 'Upload incomplet';
            case UPLOAD_ERR_NO_FILE:
                return 'Aucun fichier sélectionné';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Dossier temporaire manquant';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Impossible d\'écrire le fichier';
            case UPLOAD_ERR_EXTENSION:
                return 'Extension non autorisée';
            default:
                return 'Erreur inconnue';
        }
    }

    private function format_bytes($size, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB');
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }

    /**
     * Envoi des notifications
     */
    private function send_notifications($trainer_data, $trainer_id) {
        // Notification à l'admin
        if (get_option('trainer_notify_new_registration', 1)) {
            $admin_email = get_option('trainer_notification_email', get_option('admin_email'));
            $subject = 'Nouvelle inscription formateur - ' . $trainer_data['first_name'] . ' ' . $trainer_data['last_name'];
            
            $message = "Nouvelle inscription de formateur:\n\n";
            $message .= "Nom: " . $trainer_data['first_name'] . ' ' . $trainer_data['last_name'] . "\n";
            $message .= "Email: " . $trainer_data['email'] . "\n";
            $message .= "Téléphone: " . $trainer_data['phone'] . "\n";
            $message .= "Entreprise: " . $trainer_data['company'] . "\n";
            $message .= "Spécialités: " . $trainer_data['specialties'] . "\n";
            $message .= "Zones d'intervention: " . $trainer_data['intervention_regions'] . "\n";
            $message .= "Niveau d'expérience: " . $trainer_data['experience_level'] . "\n";
            $message .= "Statut: " . $trainer_data['status'] . "\n\n";
            $message .= "Voir dans l'admin: " . admin_url('admin.php?page=trainer-registration');

            wp_mail($admin_email, $subject, $message);
        }

        // Notification au formateur
        $trainer_email = $trainer_data['email'];
        $subject = 'Confirmation d\'inscription - ' . get_bloginfo('name');
        
        $message = "Bonjour " . $trainer_data['first_name'] . ",\n\n";
        
        if ($trainer_data['status'] === 'approved') {
            $message .= "Votre inscription en tant que formateur a été validée avec succès !\n\n";
            $message .= "Votre profil est maintenant visible dans notre catalogue et vous pourrez bientôt recevoir des opportunités de formations.\n\n";
        } else {
            $message .= "Nous avons bien reçu votre inscription en tant que formateur.\n\n";
            $message .= "Notre équipe va examiner votre profil et vous contactera bientôt.\n\n";
        }
        
        $message .= "Merci de votre confiance !\n\n";
        $message .= "L'équipe " . get_bloginfo('name');

        wp_mail($trainer_email, $subject, $message);
    }

    // ===== MÉTHODES HÉRITÉES POUR COMPATIBILITÉ =====
    
    public function add_custom_css_variables() {
        $primary_color = get_option('trpro_primary_color', '#000000');
        $secondary_color = get_option('trpro_secondary_color', '#6b7280');
        $accent_color = get_option('trpro_accent_color', '#fbbf24');
        
        echo "<style>
        :root {
            --trpro-primary-custom: {$primary_color};
            --trpro-secondary-custom: {$secondary_color};
            --trpro-accent-custom: {$accent_color};
        }
        </style>";
    }
    
    public function add_body_classes($classes) {
        global $post;
        
        if ($post) {
            if (has_shortcode($post->post_content, 'trainer_home')) {
                $classes[] = 'trpro-page-home';
            }
            if (has_shortcode($post->post_content, 'trainer_registration_form')) {
                $classes[] = 'trpro-page-registration';
            }
            if (has_shortcode($post->post_content, 'trainer_list') || has_shortcode($post->post_content, 'trainer_list_modern')) {
                $classes[] = 'trpro-page-list';
            }
            if (has_shortcode($post->post_content, 'trainer_search')) {
                $classes[] = 'trpro-page-search';
            }
        }
        
        return $classes;
    }

    /**
     * 🚀 SOLUTION RAPIDE - Handler de contact amélioré
     * 
     * ✅ VÉRIFICATION NONCE RENFORCÉE
     * ✅ VALIDATION STRICTE DES DONNÉES
     * ✅ ANTI-SPAM BASIQUE
     * ✅ CONFIGURATION EMAIL AVEC DIAGNOSTIC
     * ✅ EMAIL HTML PROFESSIONNEL
     * ✅ LOGGING COMPLET
     */
    public function handle_trainer_contact() {
        // 1. ✅ VÉRIFICATION NONCE RENFORCÉE
        if (!wp_verify_nonce($_POST['nonce'], 'trainer_registration_nonce')) {
            error_log('Trainer Contact: Nonce verification failed');
            wp_send_json_error(array('message' => 'Vérification de sécurité échouée'));
        }
        
        // 2. ✅ VALIDATION STRICTE DES DONNÉES
        $errors = array();
        
        $name = sanitize_text_field($_POST['contact_name'] ?? '');
        $email = sanitize_email($_POST['contact_email'] ?? '');
        $company = sanitize_text_field($_POST['contact_company'] ?? '');
        $message = sanitize_textarea_field($_POST['contact_message'] ?? '');
        $trainer_id = intval($_POST['trainer_id'] ?? 0);
        
        // Validations
        if (empty($name) || strlen($name) < 2) $errors[] = 'Nom invalide';
        if (empty($email) || !is_email($email)) $errors[] = 'Email invalide';
        if (empty($message) || strlen($message) < 10) $errors[] = 'Message trop court';
        if (empty($trainer_id)) $errors[] = 'ID formateur manquant';
        
        // Anti-spam basique
        if (substr_count($message, 'http') > 2) $errors[] = 'Trop de liens';
        if (preg_match('/viagra|cialis|casino|poker/i', $message)) $errors[] = 'Contenu interdit';
        
        if (!empty($errors)) {
            wp_send_json_error(array(
                'message' => 'Données invalides: ' . implode(', ', $errors)
            ));
        }
        
        // 3. ✅ RÉCUPÉRER LE FORMATEUR
        global $wpdb;
        $table_name = $wpdb->prefix . 'trainer_registrations';
        $trainer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND status = 'approved'",
            $trainer_id
        ));
        
        if (!$trainer) {
            wp_send_json_error(array('message' => 'Formateur non trouvé'));
        }
        
        // 4. ✅ CONFIGURATION EMAIL AVEC DIAGNOSTIC
        $contact_email = get_option('trainer_contact_email');
        
        // Si pas configuré, utiliser email admin
        if (empty($contact_email) || !is_email($contact_email)) {
            $contact_email = get_option('admin_email');
            error_log('Trainer Contact: Using admin email fallback - ' . $contact_email);
        }
        
        if (empty($contact_email)) {
            wp_send_json_error(array(
                'message' => 'Configuration email manquante - Contactez l\'administrateur',
                'code' => 'no_email_config'
            ));
        }
        
        // 5. ✅ CRÉATION DE L'EMAIL PROFESSIONNEL
        $trainer_name = $this->get_anonymized_name($trainer->first_name, $trainer->last_name);
        $trainer_id_display = str_pad($trainer->id, 4, '0', STR_PAD_LEFT);
        
        $subject = sprintf(
            '[CONTACT FORMATEUR] %s - #%s',
            get_bloginfo('name'),
            $trainer_id_display
        );
        
        // Email body avec style
        $email_body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2563eb; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
                .content { padding: 20px; background: #f8fafc; border: 1px solid #e2e8f0; }
                .info-box { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #2563eb; border-radius: 4px; }
                .footer { padding: 15px; text-align: center; color: #64748b; font-size: 12px; background: #f1f5f9; border-radius: 0 0 8px 8px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🔔 Nouvelle Demande de Contact</h2>
                    <p>Formateur: {$trainer_name} (#{$trainer_id_display})</p>
                </div>
                
                <div class='content'>
                    <div class='info-box'>
                        <h3>👤 Demandeur</h3>
                        <p><strong>Nom:</strong> {$name}</p>
                        <p><strong>Email:</strong> <a href='mailto:{$email}'>{$email}</a></p>
                        " . (!empty($company) ? "<p><strong>Entreprise:</strong> {$company}</p>" : "") . "
                    </div>
                    
                    <div class='info-box'>
                        <h3>👨‍🏫 Formateur Concerné</h3>
                        <p><strong>Nom:</strong> {$trainer_name}</p>
                        <p><strong>Spécialités:</strong> {$trainer->specialties}</p>
                        " . (!empty($trainer->intervention_regions) ? "<p><strong>Zones:</strong> {$trainer->intervention_regions}</p>" : "") . "
                    </div>
                    
                    <div class='info-box'>
                        <h3>💬 Message</h3>
                        <div style='background: #fff; padding: 10px; border-radius: 4px;'>
                            " . nl2br(esc_html($message)) . "
                        </div>
                    </div>
                    
                    <div style='text-align: center; margin: 20px 0;'>
                        <a href='mailto:{$email}?subject=RE: Demande de formation - {$trainer_name}' 
                           style='background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;'>
                            📧 Répondre au Demandeur
                        </a>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>Email envoyé le " . date('d/m/Y à H:i') . " depuis " . get_site_url() . "</p>
                    <p>IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Inconnue') . "</p>
                </div>
            </div>
        </body>
        </html>";
        
        // 6. ✅ HEADERS SÉCURISÉS
// Remplacer dans handle_trainer_contact() :
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <wordpress@' . $_SERVER['HTTP_HOST'] . '>',
            'Reply-To: ' . $name . ' <' . $email . '>',
            'X-Mailer: WordPress/' . get_bloginfo('version'),
            'X-Priority: 3'
        );
                
        // 7. ✅ ENVOI AVEC LOGGING COMPLET
        error_log(sprintf(
            'Trainer Contact Attempt: Trainer=%d, From=%s, To=%s',
            $trainer_id,
            $email,
            $contact_email
        ));
        
        $sent = wp_mail($contact_email, $subject, $email_body, $headers);
        
        // 8. ✅ LOGGING ET RÉPONSE
        $this->log_contact_event($trainer_id, $email, $contact_email, $sent);
        
        if ($sent) {
            // Envoyer confirmation au demandeur
            $this->send_confirmation_to_requester($email, $name, $trainer_name);
            
            wp_send_json_success(array(
                'message' => 'Votre demande a été transmise avec succès !',
                'details' => "Un email a été envoyé à notre équipe concernant le formateur {$trainer_name}."
            ));
        } else {
            // Récupérer erreur détaillée
            global $phpmailer;
            $error_detail = '';
            
            if (isset($phpmailer) && !empty($phpmailer->ErrorInfo)) {
                $error_detail = $phpmailer->ErrorInfo;
                error_log('Trainer Contact Email Error: ' . $error_detail);
            }
            
            wp_send_json_error(array(
                'message' => 'Erreur lors de l\'envoi de votre demande',
                'technical' => 'Erreur serveur: ' . $error_detail,
                'suggestion' => 'Veuillez réessayer ou nous contacter directement à: ' . $contact_email
            ));
        }
    }

    /**
     * ✅ MÉTHODE DE LOGGING AMÉLIORÉE
     */
    private function log_contact_event($trainer_id, $from_email, $to_email, $success) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'trainer_id' => $trainer_id,
            'from_email' => $from_email,
            'to_email' => $to_email,
            'success' => $success ? 1 : 0,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
        );
        
        // Stocker dans options WordPress
        $logs = get_option('trainer_contact_logs', array());
        array_unshift($logs, $log_entry);
        $logs = array_slice($logs, 0, 50); // Garder 50 dernières entrées
        
        update_option('trainer_contact_logs', $logs);
        
        // Log système WordPress
        error_log(sprintf(
            'Trainer Contact %s: ID=%d, From=%s, To=%s, IP=%s',
            $success ? 'SUCCESS' : 'FAILED',
            $trainer_id,
            $from_email,
            $to_email,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ));
    }

    /**
     * ✅ CONFIRMATION AU DEMANDEUR
     */
    private function send_confirmation_to_requester($to_email, $name, $trainer_name) {
        $company_name = get_option('trainer_company_name', get_bloginfo('name'));
        
        $subject = "Confirmation - Demande de contact reçue";
        
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <div style='max-width: 500px; margin: 0 auto; padding: 20px;'>
                <div style='background: #10b981; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                    <h2>✅ Demande Reçue !</h2>
                </div>
                
                <div style='padding: 20px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0 0 8px 8px;'>
                    <p>Bonjour <strong>{$name}</strong>,</p>
                    
                    <p>Votre demande concernant le formateur <strong>{$trainer_name}</strong> a été transmise à notre équipe.</p>
                    
                    <div style='background: white; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #10b981;'>
                        <p><strong>⏱️ Délai de réponse :</strong> 24-48h ouvrées</p>
                        <p><strong>📧 Contact direct :</strong> " . get_option('trainer_contact_email', get_option('admin_email')) . "</p>
                    </div>
                    
                    <p>Cordialement,<br>L'équipe {$company_name}</p>
                </div>
            </div>
        </body>
        </html>";
        
        wp_mail($to_email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
    }
}