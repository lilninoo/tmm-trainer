<?php
/**
 * Fonctions utilitaires pour les régions d'intervention et l'anonymisation
 * 
 * Fichier: includes/regions-utilities.php
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtenir la liste complète des régions françaises disponibles
 */
function trpro_get_french_regions() {
    return array(
        'ile-de-france' => array(
            'name' => 'Île-de-France',
            'departments' => array('75', '77', '78', '91', '92', '93', '94', '95'),
            'icon' => 'fas fa-landmark',
            'color' => '#3b82f6',
            'population' => 12000000
        ),
        'auvergne-rhone-alpes' => array(
            'name' => 'Auvergne-Rhône-Alpes',
            'departments' => array('01', '03', '07', '15', '26', '38', '42', '43', '63', '69', '73', '74'),
            'icon' => 'fas fa-mountain',
            'color' => '#10b981',
            'population' => 8000000
        ),
        'nouvelle-aquitaine' => array(
            'name' => 'Nouvelle-Aquitaine',
            'departments' => array('16', '17', '19', '23', '24', '33', '40', '47', '64', '79', '86', '87'),
            'icon' => 'fas fa-water',
            'color' => '#06b6d4',
            'population' => 6000000
        ),
        'occitanie' => array(
            'name' => 'Occitanie',
            'departments' => array('09', '11', '12', '30', '31', '32', '34', '46', '48', '65', '66', '81', '82'),
            'icon' => 'fas fa-sun',
            'color' => '#f59e0b',
            'population' => 5900000
        ),
        'hauts-de-france' => array(
            'name' => 'Hauts-de-France',
            'departments' => array('02', '59', '60', '62', '80'),
            'icon' => 'fas fa-industry',
            'color' => '#6366f1',
            'population' => 6000000
        ),
        'grand-est' => array(
            'name' => 'Grand Est',
            'departments' => array('08', '10', '51', '52', '54', '55', '57', '67', '68', '88'),
            'icon' => 'fas fa-flag',
            'color' => '#8b5cf6',
            'population' => 5500000
        ),
        'provence-alpes-cote-azur' => array(
            'name' => 'Provence-Alpes-Côte d\'Azur',
            'departments' => array('04', '05', '06', '13', '83', '84'),
            'icon' => 'fas fa-umbrella-beach',
            'color' => '#f97316',
            'population' => 5100000
        ),
        'pays-de-la-loire' => array(
            'name' => 'Pays de la Loire',
            'departments' => array('44', '49', '53', '72', '85'),
            'icon' => 'fas fa-ship',
            'color' => '#14b8a6',
            'population' => 3800000
        ),
        'bretagne' => array(
            'name' => 'Bretagne',
            'departments' => array('22', '29', '35', '56'),
            'icon' => 'fas fa-anchor',
            'color' => '#0ea5e9',
            'population' => 3300000
        ),
        'normandie' => array(
            'name' => 'Normandie',
            'departments' => array('14', '27', '50', '61', '76'),
            'icon' => 'fas fa-chess-rook',
            'color' => '#84cc16',
            'population' => 3300000
        ),
        'bourgogne-franche-comte' => array(
            'name' => 'Bourgogne-Franche-Comté',
            'departments' => array('21', '25', '39', '58', '70', '71', '89', '90'),
            'icon' => 'fas fa-wine-glass',
            'color' => '#a855f7',
            'population' => 2800000
        ),
        'centre-val-de-loire' => array(
            'name' => 'Centre-Val de Loire',
            'departments' => array('18', '28', '36', '37', '41', '45'),
            'icon' => 'fas fa-crown',
            'color' => '#eab308',
            'population' => 2600000
        ),
        'corse' => array(
            'name' => 'Corse',
            'departments' => array('2A', '2B'),
            'icon' => 'fas fa-island-tropical',
            'color' => '#ef4444',
            'population' => 340000
        ),
        'outre-mer' => array(
            'name' => 'Outre-mer (DOM-TOM)',
            'departments' => array('971', '972', '973', '974', '975', '976', '977', '978', '984', '986', '987', '988'),
            'icon' => 'fas fa-globe',
            'color' => '#06b6d4',
            'population' => 2800000
        ),
        'europe' => array(
            'name' => 'Europe (hors France)',
            'departments' => array(),
            'icon' => 'fas fa-globe-europe',
            'color' => '#3b82f6',
            'population' => 0
        ),
        'international' => array(
            'name' => 'International',
            'departments' => array(),
            'icon' => 'fas fa-globe-americas',
            'color' => '#10b981',
            'population' => 0
        ),
        'distanciel' => array(
            'name' => 'Formation à distance',
            'departments' => array(),
            'icon' => 'fas fa-laptop-house',
            'color' => '#8b5cf6',
            'population' => 0,
            'featured' => true
        )
    );
}

/**
 * Obtenir les informations d'une région spécifique
 */
function trpro_get_region_info($region_key) {
    $regions = trpro_get_french_regions();
    return isset($regions[$region_key]) ? $regions[$region_key] : null;
}

/**
 * Obtenir le nom d'affichage d'une région
 */
function trpro_get_region_display_name($region_key) {
    $region_info = trpro_get_region_info($region_key);
    return $region_info ? $region_info['name'] : ucwords(str_replace('-', ' ', $region_key));
}

/**
 * Obtenir l'icône d'une région
 */
function trpro_get_region_icon($region_key) {
    $region_info = trpro_get_region_info($region_key);
    return $region_info ? $region_info['icon'] : 'fas fa-map-marker-alt';
}

/**
 * Obtenir la couleur d'une région
 */
function trpro_get_region_color($region_key) {
    $region_info = trpro_get_region_info($region_key);
    return $region_info ? $region_info['color'] : '#64748b';
}

/**
 * Vérifier si une région est mise en avant
 */
function trpro_is_region_featured($region_key) {
    $region_info = trpro_get_region_info($region_key);
    return $region_info && isset($region_info['featured']) && $region_info['featured'];
}

/**
 * Obtenir les régions les plus populaires basées sur les inscriptions
 */
function trpro_get_popular_regions($limit = 5) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'trainer_registrations';
    
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT 
            TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(intervention_regions, ',', n.digit+1), ',', -1)) as region,
            COUNT(*) as count
        FROM $table_name
        CROSS JOIN (
            SELECT 0 as digit UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL 
            SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6
        ) n
        WHERE intervention_regions IS NOT NULL 
        AND intervention_regions != ''
        AND status = 'approved'
        AND CHAR_LENGTH(intervention_regions) - CHAR_LENGTH(REPLACE(intervention_regions, ',', '')) >= n.digit
        GROUP BY region
        HAVING region != '' AND region IS NOT NULL
        ORDER BY count DESC
        LIMIT %d
    ", $limit));
    
    $popular_regions = array();
    foreach ($results as $result) {
        $region_key = trim($result->region);
        if (!empty($region_key)) {
            $popular_regions[] = array(
                'key' => $region_key,
                'name' => trpro_get_region_display_name($region_key),
                'count' => intval($result->count),
                'icon' => trpro_get_region_icon($region_key),
                'color' => trpro_get_region_color($region_key)
            );
        }
    }
    
    return $popular_regions;
}

/**
 * Normaliser une liste de régions
 */
function trpro_normalize_regions($regions_string) {
    if (empty($regions_string)) {
        return array();
    }
    
    $regions = array_map('trim', explode(',', $regions_string));
    $valid_regions = array_keys(trpro_get_french_regions());
    
    // Filtrer uniquement les régions valides
    $normalized = array_filter($regions, function($region) use ($valid_regions) {
        return in_array(trim($region), $valid_regions);
    });
    
    return array_unique($normalized);
}

/**
 * Convertir une liste de régions en chaîne
 */
function trpro_regions_to_string($regions_array) {
    if (empty($regions_array) || !is_array($regions_array)) {
        return '';
    }
    
    return implode(', ', array_map('trim', $regions_array));
}

/**
 * Obtenir les statistiques par région
 */
function trpro_get_regions_statistics() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'trainer_registrations';
    $regions = trpro_get_french_regions();
    $stats = array();
    
    foreach ($regions as $region_key => $region_info) {
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_name 
            WHERE intervention_regions LIKE %s 
            AND status = 'approved'
        ", '%' . $region_key . '%'));
        
        $stats[$region_key] = array(
            'name' => $region_info['name'],
            'trainers_count' => intval($count),
            'icon' => $region_info['icon'],
            'color' => $region_info['color']
        );
    }
    
    // Trier par nombre de formateurs
    uasort($stats, function($a, $b) {
        return $b['trainers_count'] - $a['trainers_count'];
    });
    
    return $stats;
}

/**
 * ✅ ANONYMISATION : Générer un nom anonymisé
 */
function trpro_anonymize_trainer_name($first_name, $last_name) {
    if (empty($first_name) || empty($last_name)) {
        return 'Formateur Expert';
    }
    
    // Format : Première lettre du nom en majuscule + point + prénom complet
    return strtoupper(substr($last_name, 0, 1)) . '. ' . $first_name;
}

/**
 * ✅ ANONYMISATION : Générer un identifiant anonyme unique
 */
function trpro_generate_anonymous_id($trainer_id, $prefix = 'TR') {
    return $prefix . str_pad($trainer_id, 4, '0', STR_PAD_LEFT);
}

/**
 * ✅ ANONYMISATION : Vérifier si l'anonymisation est activée
 */
function trpro_is_anonymization_enabled() {
    return get_option('trainer_enable_anonymization', 1);
}

/**
 * ✅ ANONYMISATION : Obtenir le nom d'affichage pour un formateur
 */
function trpro_get_trainer_display_name($trainer) {
    if (!trpro_is_anonymization_enabled()) {
        return $trainer->first_name . ' ' . $trainer->last_name;
    }
    
    // Vérifier si le nom anonymisé est déjà stocké
    if (!empty($trainer->display_name)) {
        return $trainer->display_name;
    }
    
    return trpro_anonymize_trainer_name($trainer->first_name, $trainer->last_name);
}

/**
 * Rechercher des formateurs par région
 */
function trpro_search_trainers_by_region($region_key, $additional_filters = array()) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'trainer_registrations';
    
    $where_conditions = array("status = 'approved'");
    $params = array();
    
    // Filtre par région
    if (!empty($region_key)) {
        $where_conditions[] = 'intervention_regions LIKE %s';
        $params[] = '%' . $region_key . '%';
    }
    
    // Filtres additionnels
    if (!empty($additional_filters['specialty'])) {
        $where_conditions[] = 'specialties LIKE %s';
        $params[] = '%' . $additional_filters['specialty'] . '%';
    }
    
    if (!empty($additional_filters['availability'])) {
        $where_conditions[] = 'availability = %s';
        $params[] = $additional_filters['availability'];
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    $limit = isset($additional_filters['limit']) ? intval($additional_filters['limit']) : 10;
    
    $query = "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC LIMIT %d";
    $params[] = $limit;
    
    if (!empty($params)) {
        $query = $wpdb->prepare($query, $params);
    }
    
    return $wpdb->get_results($query);
}

/**
 * Obtenir des suggestions de régions basées sur la saisie
 */
function trpro_get_region_suggestions($search_term, $limit = 5) {
    $regions = trpro_get_french_regions();
    $suggestions = array();
    
    $search_term = strtolower($search_term);
    
    foreach ($regions as $key => $region) {
        $name_lower = strtolower($region['name']);
        $key_lower = strtolower(str_replace('-', ' ', $key));
        
        if (strpos($name_lower, $search_term) !== false || 
            strpos($key_lower, $search_term) !== false) {
            $suggestions[] = array(
                'key' => $key,
                'name' => $region['name'],
                'icon' => $region['icon'],
                'color' => $region['color']
            );
        }
        
        if (count($suggestions) >= $limit) {
            break;
        }
    }
    
    return $suggestions;
}

/**
 * Valider une liste de régions
 */
function trpro_validate_regions($regions_input) {
    if (empty($regions_input)) {
        return array(
            'valid' => false,
            'error' => 'Veuillez sélectionner au moins une zone d\'intervention'
        );
    }
    
    if (!is_array($regions_input)) {
        $regions_input = array_map('trim', explode(',', $regions_input));
    }
    
    $valid_regions = array_keys(trpro_get_french_regions());
    $invalid_regions = array();
    
    foreach ($regions_input as $region) {
        if (!in_array(trim($region), $valid_regions)) {
            $invalid_regions[] = $region;
        }
    }
    
    if (!empty($invalid_regions)) {
        return array(
            'valid' => false,
            'error' => 'Régions invalides: ' . implode(', ', $invalid_regions)
        );
    }
    
    if (count($regions_input) > 8) {
        return array(
            'valid' => false,
            'error' => 'Maximum 8 zones d\'intervention autorisées'
        );
    }
    
    return array(
        'valid' => true,
        'regions' => $regions_input
    );
}

/**
 * Générer le HTML pour les checkboxes de régions
 */
function trpro_generate_regions_checkboxes($selected_regions = array(), $name = 'intervention_regions[]') {
    $regions = trpro_get_french_regions();
    $output = '<div class="trpro-regions-grid">';
    
    foreach ($regions as $key => $region) {
        $checked = in_array($key, $selected_regions) ? 'checked' : '';
        $featured_class = isset($region['featured']) && $region['featured'] ? 'trpro-featured' : '';
        
        $output .= sprintf(
            '<div class="trpro-checkbox-item %s">
                <input type="checkbox" id="region-%s" name="%s" value="%s" %s>
                <label for="region-%s">
                    <i class="%s"></i>
                    %s
                </label>
            </div>',
            $featured_class,
            esc_attr($key),
            esc_attr($name),
            esc_attr($key),
            $checked,
            esc_attr($key),
            esc_attr($region['icon']),
            esc_html($region['name'])
        );
    }
    
    $output .= '</div>';
    return $output;
}

/**
 * Obtenir la distance approximative entre deux régions (pour les suggestions)
 */
function trpro_get_regions_proximity($region1, $region2) {
    // Matrice de proximité simplifiée (à améliorer avec de vraies coordonnées)
    $proximity_matrix = array(
        'ile-de-france' => array('grand-est' => 1, 'hauts-de-france' => 1, 'centre-val-de-loire' => 1),
        'auvergne-rhone-alpes' => array('bourgogne-franche-comte' => 1, 'provence-alpes-cote-azur' => 1, 'occitanie' => 1),
        'nouvelle-aquitaine' => array('pays-de-la-loire' => 1, 'centre-val-de-loire' => 1, 'occitanie' => 1),
        'occitanie' => array('nouvelle-aquitaine' => 1, 'auvergne-rhone-alpes' => 1, 'provence-alpes-cote-azur' => 1),
        'provence-alpes-cote-azur' => array('auvergne-rhone-alpes' => 1, 'occitanie' => 1),
        'pays-de-la-loire' => array('bretagne' => 1, 'centre-val-de-loire' => 1, 'nouvelle-aquitaine' => 1),
        'bretagne' => array('pays-de-la-loire' => 1, 'normandie' => 1),
        'normandie' => array('bretagne' => 1, 'hauts-de-france' => 1, 'ile-de-france' => 1),
        'hauts-de-france' => array('normandie' => 1, 'ile-de-france' => 1, 'grand-est' => 1),
        'grand-est' => array('hauts-de-france' => 1, 'ile-de-france' => 1, 'bourgogne-franche-comte' => 1),
        'bourgogne-franche-comte' => array('grand-est' => 1, 'centre-val-de-loire' => 1, 'auvergne-rhone-alpes' => 1),
        'centre-val-de-loire' => array('ile-de-france' => 1, 'pays-de-la-loire' => 1, 'nouvelle-aquitaine' => 1, 'bourgogne-franche-comte' => 1)
    );
    
    if (isset($proximity_matrix[$region1][$region2]) || isset($proximity_matrix[$region2][$region1])) {
        return 1; // Régions adjacentes
    }
    
    return 0; // Régions non adjacentes
}

/**
 * Logger les actions liées aux régions
 */
function trpro_log_region_action($action, $region, $trainer_id = null, $additional_data = array()) {
    if (!get_option('trainer_debug_mode', 0)) {
        return;
    }
    
    $log_data = array(
        'action' => $action,
        'region' => $region,
        'trainer_id' => $trainer_id,
        'timestamp' => current_time('mysql'),
        'additional_data' => $additional_data
    );
    
    error_log('Trainer Registration Pro - Region Action: ' . json_encode($log_data));
}