# Exemples d'Utilisation - Trainer Registration Pro

Ce document présente des exemples concrets d'utilisation du plugin dans différents contextes.

## 🎯 Cas d'Usage Types

### 1. Centre de Formation IT
**Contexte** : Centre de formation qui veut créer un réseau de formateurs freelance

### 2. ESN (Entreprise de Services du Numérique)
**Contexte** : ESN qui recherche des formateurs pour ses clients

### 3. Plateforme de Formation en Ligne
**Contexte** : Plateforme e-learning qui veut référencer des experts

## 📄 Exemples de Pages

### Page d'Accueil Formateurs

```html
<!-- Page: /formateurs -->
<div class="hero-section">
    [trainer_home title="Experts IT Formation" 
                  subtitle="Rejoignez notre réseau d'experts"
                  description="Plus de 500 formateurs nous font confiance pour développer leurs activités"]
</div>

<div class="content-section">
    <h2>Pourquoi nous rejoindre ?</h2>
    <p>Notre plateforme connecte les meilleurs formateurs IT avec des entreprises qui ont besoin d'expertise technique de qualité.</p>
    
    <!-- Témoignages -->
    <div class="testimonials">
        <blockquote>
            "Grâce à cette plateforme, j'ai pu développer mon activité de formation en sécurité informatique."
            <cite>- Alexandre D., Expert Cybersécurité</cite>
        </blockquote>
    </div>
</div>
```

### Page d'Inscription

```html
<!-- Page: /inscription-formateur -->
<div class="page-header">
    <h1>Inscription Formateur Expert</h1>
    <p>Rejoignez notre communauté de formateurs IT en quelques minutes</p>
</div>

[trainer_registration_form]

<div class="help-section">
    <h3>Besoin d'aide ?</h3>
    <p>Contactez-nous : <a href="mailto:support@formation-it.com">support@formation-it.com</a></p>
</div>
```

### Page de Recherche

```html
<!-- Page: /trouver-formateur -->
<div class="search-page">
    <h1>Trouvez votre formateur IT</h1>
    <p>Recherchez parmi nos experts certifiés</p>
    
    [trainer_search]
    
    <div class="search-results">
        [trainer_list per_page="9" show_search="false"]
    </div>
</div>
```

### Page Catalogue Complet

```html
<!-- Page: /catalogue-formateurs -->
<div class="catalog-header">
    <h1>Catalogue des Formateurs</h1>
    <div class="stats">
        <span class="stat">200+ Formateurs</span>
        <span class="stat">15 Spécialités</span>
        <span class="stat">1000+ Formations</span>
    </div>
</div>

[trainer_list per_page="12" show_search="true"]
```

## 🎨 Personnalisations CSS

### Thème Corporate

```css
/* Variables personnalisées */
:root {
    --corporate-blue: #1E3A8A;
    --corporate-gray: #64748B;
    --corporate-accent: #F59E0B;
}

/* Header personnalisé */
.trainer-home-container .hero-section {
    background: linear-gradient(135deg, var(--corporate-blue) 0%, var(--corporate-gray) 100%);
    padding: 100px 0;
}

.hero-title {
    font-family: 'Roboto', sans-serif;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

/* Cartes formateurs style corporate */
.trainer-card {
    border: none;
    box-shadow: 0 8px 25px rgba(30, 58, 138, 0.1);
    transition: all 0.3s ease;
}

.trainer-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(30, 58, 138, 0.2);
}

.specialty-tag {
    background: var(--corporate-accent);
    color: white;
    font-weight: 600;
}

/* Boutons */
.btn-primary {
    background: var(--corporate-blue);
    border: none;
    padding: 15px 30px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}
```

### Thème Moderne/Tech

```css
/* Variables tech */
:root {
    --tech-primary: #00D4FF;
    --tech-secondary: #FF6B35;
    --tech-dark: #1A1A2E;
    --tech-light: #16213E;
}

/* Fond tech */
.trainer-home-container {
    background: var(--tech-dark);
    color: white;
}

/* Effet néon sur les cartes */
.trainer-card {
    background: var(--tech-light);
    border: 1px solid var(--tech-primary);
    box-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
}

/* Animation de typing pour le titre */
.hero-title {
    font-family: 'Fira Code', monospace;
    overflow: hidden;
    border-right: 3px solid var(--tech-primary);
    white-space: nowrap;
    animation: typing 3s steps(30, end), blink-caret 0.75s step-end infinite;
}

@keyframes typing {
    from { width: 0 }
    to { width: 100% }
}

@keyframes blink-caret {
    from, to { border-color: transparent }
    50% { border-color: var(--tech-primary) }
}

/* Boutons avec effet glow */
.btn-primary {
    background: linear-gradient(45deg, var(--tech-primary), var(--tech-secondary));
    box-shadow: 0 0 30px rgba(0, 212, 255, 0.5);
    text-transform: uppercase;
    letter-spacing: 2px;
}
```

## 🔧 Personnalisations PHP

### Hook pour modifier les données avant affichage

```php
// functions.php du thème

// Ajouter des informations personnalisées aux cartes
add_filter('trainer_card_data', 'customize_trainer_card_data', 10, 1);
function customize_trainer_card_data($trainer_data) {
    // Ajouter un badge "Nouveau" pour les formateurs récents
    $registration_date = new DateTime($trainer_data->created_at);
    $now = new DateTime();
    $interval = $registration_date->diff($now);
    
    if ($interval->days < 30) {
        $trainer_data->is_new = true;
    }
    
    // Calculer un score de complétude
    $completeness = 0;
    if (!empty($trainer_data->bio)) $completeness += 20;
    if (!empty($trainer_data->linkedin_url)) $completeness += 15;
    if (!empty($trainer_data->photo_file)) $completeness += 15;
    if (!empty($trainer_data->hourly_rate)) $completeness += 10;
    
    $trainer_data->completeness_score = $completeness;
    
    return $trainer_data;
}

// Personnaliser l'email de notification
add_filter('trainer_notification_email_content', 'custom_notification_email', 10, 2);
function custom_notification_email($content, $trainer_data) {
    $custom_content = "
    <h2>Nouvelle inscription formateur</h2>
    <p>Bonjour,</p>
    <p>Un nouveau formateur vient de s'inscrire sur votre plateforme :</p>
    
    <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
        <h3>{$trainer_data['first_name']} {$trainer_data['last_name']}</h3>
        <p><strong>Email :</strong> {$trainer_data['email']}</p>
        <p><strong>Téléphone :</strong> {$trainer_data['phone']}</p>
        <p><strong>Spécialités :</strong> " . implode(', ', $trainer_data['specialties']) . "</p>
    </div>
    
    <p>Connectez-vous à votre administration pour valider ce profil.</p>
    <p><a href='" . admin_url('admin.php?page=trainer-registration') . "' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Voir les inscriptions</a></p>
    ";
    
    return $custom_content;
}
```

### Validation personnalisée

```php
// Ajouter des règles de validation personnalisées
add_filter('trainer_registration_validation_rules', 'add_custom_validation_rules');
function add_custom_validation_rules($rules) {
    // Exiger au moins 3 spécialités
    $rules['min_specialties'] = function($data) {
        if (count($data['specialties']) < 3) {
            return 'Veuillez sélectionner au moins 3 spécialités.';
        }
        return true;
    };
    
    // Vérifier la qualité de l'expérience (minimum 100 caractères)
    $rules['experience_quality'] = function($data) {
        if (strlen($data['experience']) < 100) {
            return 'Veuillez détailler davantage votre expérience (minimum 100 caractères).';
        }
        return true;
    };
    
    // Validation du profil LinkedIn
    $rules['linkedin_format'] = function($data) {
        if (!empty($data['linkedin_url']) && !preg_match('/linkedin\.com\/in\//', $data['linkedin_url'])) {
            return 'URL LinkedIn invalide. Format attendu : https://linkedin.com/in/votre-profil';
        }
        return true;
    };
    
    return $rules;
}
```

### Widget personnalisé

```php
// Widget pour afficher les derniers formateurs
class Latest_Trainers_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'latest_trainers',
            'Derniers Formateurs',
            array('description' => 'Affiche les derniers formateurs inscrits')
        );
    }
    
    public function widget($args, $instance) {
        global $wpdb;
        
        $title = apply_filters('widget_title', $instance['title']);
        $count = isset($instance['count']) ? $instance['count'] : 5;
        
        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        
        $table_name = $wpdb->prefix . 'trainer_registrations';
        $trainers = $wpdb->get_results($wpdb->prepare(
            "SELECT first_name, last_name, specialties, created_at 
             FROM $table_name 
             WHERE status = 'approved' 
             ORDER BY created_at DESC 
             LIMIT %d", 
            $count
        ));
        
        if ($trainers) {
            echo '<ul class="latest-trainers-list">';
            foreach ($trainers as $trainer) {
                $time_ago = human_time_diff(strtotime($trainer->created_at), current_time('timestamp'));
                echo '<li>';
                echo '<strong>Formateur Expert #' . substr(md5($trainer->first_name . $trainer->last_name), 0, 4) . '</strong><br>';
                echo '<small>' . esc_html($trainer->specialties) . '</small><br>';
                echo '<em>Il y a ' . $time_ago . '</em>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Aucun formateur inscrit récemment.</p>';
        }
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : 'Derniers Formateurs';
        $count = isset($instance['count']) ? $instance['count'] : 5;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Titre :</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
                   name="<?php echo $this->get_field_name('title'); ?>" type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('count'); ?>">Nombre à afficher :</label>
            <input class="tiny-text" id="<?php echo $this->get_field_id('count'); ?>" 
                   name="<?php echo $this->get_field_name('count'); ?>" type="number" 
                   step="1" min="1" value="<?php echo esc_attr($count); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['count'] = (!empty($new_instance['count'])) ? absint($new_instance['count']) : 5;
        return $instance;
    }
}

// Enregistrer le widget
add_action('widgets_init', function() {
    register_widget('Latest_Trainers_Widget');
});
```

## 🔌 Intégrations avec d'autres plugins

### Contact Form 7

```html
<!-- Formulaire de contact pour recruteur -->
<p>Nom de l'entreprise<br>
[text* company-name placeholder "Votre entreprise"]</p>

<p>Spécialité recherchée<br>
[select speciality "Administration Système" "Cloud Computing" "DevOps" "Sécurité IT" "Réseaux" "Télécommunications"]</p>

<p>Nombre de formateurs souhaité<br>
[number* trainers-count min:1 max:10]</p>

<p>Budget formation (optionnel)<br>
[text budget placeholder "Ex: 5000€"]</p>

<p>Description du besoin<br>
[textarea* description placeholder "Décrivez votre besoin de formation..."]</p>

<p>[submit "Envoyer la demande"]</p>
```

### WooCommerce (formations payantes)

```php
// Créer des produits formation automatiquement
add_action('trainer_approved', 'create_training_products');
function create_training_products($trainer_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'trainer_registrations';
    $trainer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $trainer_id));
    
    if ($trainer) {
        $specialties = explode(', ', $trainer->specialties);
        
        foreach ($specialties as $specialty) {
            // Créer un produit WooCommerce pour chaque spécialité
            $product = new WC_Product_Simple();
            $product->set_name("Formation " . ucfirst(str_replace('-', ' ', $specialty)));
            $product->set_description("Formation dispensée par un expert certifié");
            $product->set_sku("FORM-" . $trainer_id . "-" . strtoupper(substr($specialty, 0, 3)));
            $product->set_price($trainer->hourly_rate ?: 500);
            $product->set_virtual(true);
            $product->save();
            
            // Lier le produit au formateur
            update_post_meta($product->get_id(), '_trainer_id', $trainer_id);
        }
    }
}
```

### Elementor

```php
// Widget Elementor personnalisé
class Elementor_Trainer_List_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'trainer_list';
    }
    
    public function get_title() {
        return 'Liste des Formateurs';
    }
    
    public function get_icon() {
        return 'fa fa-graduation-cap';
    }
    
    public function get_categories() {
        return ['general'];
    }
    
    protected function _register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Contenu',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'trainers_per_page',
            [
                'label' => 'Formateurs par page',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 50,
                'step' => 1,
                'default' => 12,
            ]
        );
        
        $this->add_control(
            'show_search',
            [
                'label' => 'Afficher la recherche',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Oui',
                'label_off' => 'Non',
                'return_value' => 'true',
                'default' => 'true',
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        echo do_shortcode('[trainer_list per_page="' . $settings['trainers_per_page'] . '" show_search="' . $settings['show_search'] . '"]');
    }
}

// Enregistrer le widget
add_action('elementor/widgets/widgets_registered', function() {
    \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Elementor_Trainer_List_Widget());
});
```

## 📊 Analyses et Statistiques

### Google Analytics

```javascript
// Tracking des événements formateurs
document.addEventListener('DOMContentLoaded', function() {
    // Soumission formulaire
    document.getElementById('trainer-registration-form').addEventListener('submit', function() {
        gtag('event', 'form_submit', {
            'event_category': 'Trainer Registration',
            'event_label': 'Registration Form'
        });
    });
    
    // Recherche formateurs
    document.getElementById('search-trainers-btn').addEventListener('click', function() {
        const searchTerm = document.getElementById('trainer-search-input').value;
        gtag('event', 'search', {
            'event_category': 'Trainer Search',
            'search_term': searchTerm
        });
    });
    
    // Contact formateur
    document.querySelectorAll('.contact-btn').forEach(button => {
        button.addEventListener('click', function() {
            gtag('event', 'contact_trainer', {
                'event_category': 'Trainer Contact',
                'method': this.classList.contains('contact-email') ? 'email' : 'phone'
            });
        });
    });
});
```

### Rapport personnalisé

```php
// Fonction pour générer un rapport mensuel
function generate_monthly_trainer_report($month, $year) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'trainer_registrations';
    
    // Nouvelles inscriptions
    $new_registrations = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name 
         WHERE MONTH(created_at) = %d AND YEAR(created_at) = %d",
        $month, $year
    ));
    
    // Approbations
    $approvals = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name 
         WHERE status = 'approved' AND MONTH(updated_at) = %d AND YEAR(updated_at) = %d",
        $month, $year
    ));
    
    // Top spécialités
    $top_specialties = $wpdb->get_results($wpdb->prepare(
        "SELECT specialties, COUNT(*) as count 
         FROM $table_name 
         WHERE status = 'approved' AND MONTH(created_at) = %d AND YEAR(created_at) = %d
         GROUP BY specialties 
         ORDER BY count DESC 
         LIMIT 5",
        $month, $year
    ));
    
    // Générer le rapport
    $report = [
        'period' => sprintf('%02d/%d', $month, $year),
        'new_registrations' => $new_registrations,
        'approvals' => $approvals,
        'approval_rate' => $new_registrations > 0 ? round(($approvals / $new_registrations) * 100, 2) : 0,
        'top_specialties' => $top_specialties
    ];
    
    return $report;
}

// Envoyer le rapport par email
add_action('wp', 'schedule_monthly_report');
function schedule_monthly_report() {
    if (!wp_next_scheduled('send_monthly_trainer_report')) {
        wp_schedule_event(time(), 'monthly', 'send_monthly_trainer_report');
    }
}

add_action('send_monthly_trainer_report', 'send_trainer_report_email');
function send_trainer_report_email() {
    $current_month = date('n');
    $current_year = date('Y');
    
    $report = generate_monthly_trainer_report($current_month, $current_year);
    
    $email_content = "
    <h2>Rapport mensuel - Formateurs IT</h2>
    <p>Période : {$report['period']}</p>
    
    <h3>Résumé</h3>
    <ul>
        <li>Nouvelles inscriptions : {$report['new_registrations']}</li>
        <li>Approbations : {$report['approvals']}</li>
        <li>Taux d'approbation : {$report['approval_rate']}%</li>
    </ul>
    
    <h3>Top spécialités</h3>
    <ol>";
    
    foreach ($report['top_specialties'] as $specialty) {
        $email_content .= "<li>{$specialty->specialties} ({$specialty->count} formateurs)</li>";
    }
    
    $email_content .= "</ol>";
    
    $admin_email = get_option('trainer_notification_email', get_option('admin_email'));
    $subject = 'Rapport mensuel - Plateforme Formateurs IT';
    
    wp_mail($admin_email, $subject, $email_content, ['Content-Type: text/html; charset=UTF-8']);
}
```

## 🎯 Conseils d'Optimisation

### Performance

```php
// Cache des requêtes lourdes
function get_trainer_stats_cached() {
    $cache_key = 'trainer_stats_' . date('Y-m-d');
    $stats = wp_cache_get($cache_key);
    
    if (false === $stats) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        $stats = [
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'approved'"),
            'this_month' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'approved' AND MONTH(created_at) = MONTH(NOW())"),
            'specialties' => $wpdb->get_results("SELECT specialties, COUNT(*) as count FROM $table_name WHERE status = 'approved' GROUP BY specialties ORDER BY count DESC LIMIT 5")
        ];
        
        wp_cache_set($cache_key, $stats, '', 3600); // Cache 1 heure
    }
    
    return $stats;
}
```

### SEO

```php
// Métadonnées personnalisées avec Yoast
add_filter('wpseo_title', 'custom_trainer_seo_title');
function custom_trainer_seo_title($title) {
    if (is_page() && has_shortcode(get_post()->post_content, 'trainer_list')) {
        $stats = get_trainer_stats_cached();
        return "Formateurs IT Experts - {$stats['total']} Professionnels Certifiés | " . get_bloginfo('name');
    }
    return $title;
}

add_filter('wpseo_metadesc', 'custom_trainer_seo_description');
function custom_trainer_seo_description($description) {
    if (is_page() && has_shortcode(get_post()->post_content, 'trainer_list')) {
        return "Trouvez votre formateur IT parmi nos experts certifiés en administration système, cloud, DevOps, sécurité et réseaux. Profils vérifiés et contact direct.";
    }
    return $description;
}
```

Ces exemples montrent la flexibilité du plugin et comment l'adapter à différents besoins spécifiques. N'hésitez pas à les modifier selon vos requirements particuliers !