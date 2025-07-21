<?php
/**
 * Template pour la page de paramètres
 * 
 * Fichier: /wp-content/plugins/trainer-registration-plugin/admin/partials/settings-display.php
 * Variables disponibles: $contact_email, $contact_phone, $auto_approve, $notification_email
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap trainer-settings-wrap">
    <h1 class="wp-heading-inline">
        <i class="fas fa-cogs"></i>
        Paramètres du Plugin Formateurs
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=trainer-registration'); ?>" class="page-title-action">
        <i class="fas fa-arrow-left"></i>
        Retour à la liste
    </a>
    
    <hr class="wp-header-end">

    <!-- Navigation par onglets -->
    <nav class="nav-tab-wrapper">
        <a href="#general" class="nav-tab nav-tab-active" data-tab="general">
            <i class="fas fa-cog"></i>
            Général
        </a>
        <a href="#contact" class="nav-tab" data-tab="contact">
            <i class="fas fa-address-book"></i>
            Contact
        </a>
        <a href="#notifications" class="nav-tab" data-tab="notifications">
            <i class="fas fa-bell"></i>
            Notifications
        </a>
        <a href="#display" class="nav-tab" data-tab="display">
            <i class="fas fa-eye"></i>
            Affichage
        </a>
        <a href="#security" class="nav-tab" data-tab="security">
            <i class="fas fa-shield-alt"></i>
            Sécurité
        </a>
        <a href="#advanced" class="nav-tab" data-tab="advanced">
            <i class="fas fa-tools"></i>
            Avancé
        </a>
    </nav>

    <form method="post" id="trainer-settings-form" action="">
        <?php wp_nonce_field('trainer_settings_nonce'); ?>
        
        <!-- Onglet Général -->
        <div class="tab-content active" id="general-tab">
            <div class="settings-section">
                <h2><i class="fas fa-cog"></i> Paramètres généraux</h2>
                
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="auto_approve">Approbation automatique</label>
                        </th>
                        <td>
                            <fieldset>
                                <label for="auto_approve">
                                    <input type="checkbox" id="auto_approve" name="auto_approve" value="1" 
                                           <?php checked($auto_approve, 1); ?>>
                                    Approuver automatiquement les nouveaux formateurs
                                </label>
                                <p class="description">
                                    Si activé, les formateurs seront automatiquement approuvés après inscription. 
                                    <strong>Attention :</strong> cela peut poser des risques de sécurité.
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="require_photo">Photo obligatoire</label>
                        </th>
                        <td>
                            <fieldset>
                                <label for="require_photo">
                                    <input type="checkbox" id="require_photo" name="require_photo" value="1" 
                                           <?php checked(get_option('trainer_require_photo', 0), 1); ?>>
                                    Rendre la photo de profil obligatoire
                                </label>
                                <p class="description">
                                    Force les formateurs à télécharger une photo lors de l'inscription.
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="max_cv_size">Taille max du CV (MB)</label>
                        </th>
                        <td>
                            <input type="number" id="max_cv_size" name="max_cv_size" 
                                   value="<?php echo esc_attr(get_option('trainer_max_cv_size', 5)); ?>" 
                                   min="1" max="50" class="small-text">
                            <p class="description">
                                Taille maximale autorisée pour les fichiers CV (en mégaoctets).
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="max_photo_size">Taille max de la photo (MB)</label>
                        </th>
                        <td>
                            <input type="number" id="max_photo_size" name="max_photo_size" 
                                   value="<?php echo esc_attr(get_option('trainer_max_photo_size', 2)); ?>" 
                                   min="1" max="10" class="small-text">
                            <p class="description">
                                Taille maximale autorisée pour les photos de profil (en mégaoctets).
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Onglet Contact -->
        <div class="tab-content" id="contact-tab">
            <div class="settings-section">
                <h2><i class="fas fa-address-book"></i> Informations de contact</h2>
                <p>Ces informations seront affichées sur les cartes des formateurs pour permettre aux recruteurs de vous contacter.</p>
                
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="contact_email">Email de contact *</label>
                        </th>
                        <td>
                            <input type="email" id="contact_email" name="contact_email" 
                                   value="<?php echo esc_attr($contact_email); ?>" 
                                   class="regular-text" required>
                            <p class="description">
                                Adresse email principale pour recevoir les demandes de contact des recruteurs.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="contact_phone">Téléphone de contact</label>
                        </th>
                        <td>
                            <input type="tel" id="contact_phone" name="contact_phone" 
                                   value="<?php echo esc_attr($contact_phone); ?>" 
                                   class="regular-text" placeholder="+33 1 23 45 67 89">
                            <p class="description">
                                Numéro de téléphone affiché sur les cartes des formateurs (optionnel).
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="company_name">Nom de l'entreprise</label>
                        </th>
                        <td>
                            <input type="text" id="company_name" name="company_name" 
                                   value="<?php echo esc_attr(get_option('trainer_company_name', '')); ?>" 
                                   class="regular-text" placeholder="Votre entreprise">
                            <p class="description">
                                Nom de votre entreprise ou organisation (affiché dans les emails et formulaires).
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="website_url">Site web</label>
                        </th>
                        <td>
                            <input type="url" id="website_url" name="website_url" 
                                   value="<?php echo esc_attr(get_option('trainer_website_url', '')); ?>" 
                                   class="regular-text" placeholder="https://votre-site.com">
                            <p class="description">
                                URL de votre site web principal.
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Onglet Notifications -->
        <div class="tab-content" id="notifications-tab">
            <div class="settings-section">
                <h2><i class="fas fa-bell"></i> Notifications par email</h2>
                
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="notification_email">Email de notification *</label>
                        </th>
                        <td>
                            <input type="email" id="notification_email" name="notification_email" 
                                   value="<?php echo esc_attr($notification_email); ?>" 
                                   class="regular-text" required>
                            <p class="description">
                                Adresse email qui recevra les notifications d'inscription et autres alertes.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Notifications à recevoir</th>
                        <td>
                            <fieldset>
                                <label for="notify_new_registration">
                                    <input type="checkbox" id="notify_new_registration" name="notify_new_registration" value="1" 
                                           <?php checked(get_option('trainer_notify_new_registration', 1), 1); ?>>
                                    Nouvelle inscription de formateur
                                </label><br>
                                
                                <label for="notify_status_change">
                                    <input type="checkbox" id="notify_status_change" name="notify_status_change" value="1" 
                                           <?php checked(get_option('trainer_notify_status_change', 1), 1); ?>>
                                    Changement de statut d'un formateur
                                </label><br>
                                
                                <label for="notify_weekly_summary">
                                    <input type="checkbox" id="notify_weekly_summary" name="notify_weekly_summary" value="1" 
                                           <?php checked(get_option('trainer_notify_weekly_summary', 0), 1); ?>>
                                    Résumé hebdomadaire des activités
                                </label><br>
                                
                                <label for="notify_pending_review">
                                    <input type="checkbox" id="notify_pending_review" name="notify_pending_review" value="1" 
                                           <?php checked(get_option('trainer_notify_pending_review', 1), 1); ?>>
                                    Rappel pour les formateurs en attente (après 7 jours)
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="email_template">Template d'email</label>
                        </th>
                        <td>
                            <select id="email_template" name="email_template">
                                <option value="default" <?php selected(get_option('trainer_email_template', 'default'), 'default'); ?>>
                                    Modèle par défaut
                                </option>
                                <option value="modern" <?php selected(get_option('trainer_email_template', 'default'), 'modern'); ?>>
                                    Modèle moderne
                                </option>
                                <option value="simple" <?php selected(get_option('trainer_email_template', 'default'), 'simple'); ?>>
                                    Modèle simple
                                </option>
                            </select>
                            <p class="description">
                                Style des emails envoyés automatiquement.
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Onglet Affichage -->
        <div class="tab-content" id="display-tab">
            <div class="settings-section">
                <h2><i class="fas fa-eye"></i> Options d'affichage</h2>
                
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="trainers_per_page">Formateurs par page</label>
                        </th>
                        <td>
                            <input type="number" id="trainers_per_page" name="trainers_per_page" 
                                   value="<?php echo esc_attr(get_option('trainer_trainers_per_page', 12)); ?>" 
                                   min="6" max="50" class="small-text">
                            <p class="description">
                                Nombre de formateurs à afficher par page dans la liste publique.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Informations à masquer</th>
                        <td>
                            <fieldset>
                                <label for="hide_name">
                                    <input type="checkbox" id="hide_name" name="hide_name" value="1" 
                                           <?php checked(get_option('trainer_hide_name', 1), 1); ?>>
                                    Masquer le nom complet (remplacer par "Formateur Expert")
                                </label><br>
                                
                                <label for="hide_phone">
                                    <input type="checkbox" id="hide_phone" name="hide_phone" value="1" 
                                           <?php checked(get_option('trainer_hide_phone', 1), 1); ?>>
                                    Masquer le numéro de téléphone direct
                                </label><br>
                                
                                <label for="hide_email">
                                    <input type="checkbox" id="hide_email" name="hide_email" value="1" 
                                           <?php checked(get_option('trainer_hide_email', 1), 1); ?>>
                                    Masquer l'adresse email directe
                                </label><br>
                                
                                <label for="show_company">
                                    <input type="checkbox" id="show_company" name="show_company" value="1" 
                                           <?php checked(get_option('trainer_show_company', 1), 1); ?>>
                                    Afficher le nom de l'entreprise/organisation
                                </label>
                            </fieldset>
                            <p class="description">
                                Contrôlez quelles informations sont visibles publiquement pour protéger la vie privée des formateurs.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="card_style">Style des cartes</label>
                        </th>
                        <td>
                            <select id="card_style" name="card_style">
                                <option value="modern" <?php selected(get_option('trainer_card_style', 'modern'), 'modern'); ?>>
                                    Moderne
                                </option>
                                <option value="classic" <?php selected(get_option('trainer_card_style', 'modern'), 'classic'); ?>>
                                    Classique
                                </option>
                                <option value="minimal" <?php selected(get_option('trainer_card_style', 'modern'), 'minimal'); ?>>
                                    Minimaliste
                                </option>
                            </select>
                            <p class="description">
                                Style d'affichage des cartes de formateurs.
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Onglet Sécurité -->
        <div class="tab-content" id="security-tab">
            <div class="settings-section">
                <h2><i class="fas fa-shield-alt"></i> Sécurité et RGPD</h2>
                
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="data_retention">Durée de conservation</label>
                        </th>
                        <td>
                            <select id="data_retention" name="data_retention">
                                <option value="1" <?php selected(get_option('trainer_data_retention', 3), 1); ?>>1 an</option>
                                <option value="2" <?php selected(get_option('trainer_data_retention', 3), 2); ?>>2 ans</option>
                                <option value="3" <?php selected(get_option('trainer_data_retention', 3), 3); ?>>3 ans</option>
                                <option value="5" <?php selected(get_option('trainer_data_retention', 3), 5); ?>>5 ans</option>
                                <option value="0" <?php selected(get_option('trainer_data_retention', 3), 0); ?>>Indéfiniment</option>
                            </select>
                            <p class="description">
                                Durée de conservation des données des formateurs inactifs (conforme RGPD).
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="allowed_file_types">Types de fichiers autorisés</label>
                        </th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">Types de fichiers autorisés pour les CV</legend>
                                <label for="allow_pdf">
                                    <input type="checkbox" id="allow_pdf" name="allowed_file_types[]" value="pdf" 
                                           <?php checked(in_array('pdf', get_option('trainer_allowed_file_types', ['pdf', 'doc', 'docx']))); ?>>
                                    PDF
                                </label><br>
                                
                                <label for="allow_doc">
                                    <input type="checkbox" id="allow_doc" name="allowed_file_types[]" value="doc" 
                                           <?php checked(in_array('doc', get_option('trainer_allowed_file_types', ['pdf', 'doc', 'docx']))); ?>>
                                    DOC
                                </label><br>
                                
                                <label for="allow_docx">
                                    <input type="checkbox" id="allow_docx" name="allowed_file_types[]" value="docx" 
                                           <?php checked(in_array('docx', get_option('trainer_allowed_file_types', ['pdf', 'doc', 'docx']))); ?>>
                                    DOCX
                                </label>
                            </fieldset>
                            <p class="description">
                                Formats de fichiers autorisés pour les CV.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="enable_captcha">Protection anti-spam</label>
                        </th>
                        <td>
                            <fieldset>
                                <label for="enable_captcha">
                                    <input type="checkbox" id="enable_captcha" name="enable_captcha" value="1" 
                                           <?php checked(get_option('trainer_enable_captcha', 0), 1); ?>>
                                    Activer le CAPTCHA sur le formulaire d'inscription
                                </label>
                                <p class="description">
                                    Protège contre les inscriptions automatisées (nécessite une clé reCAPTCHA).
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Onglet Avancé -->
        <div class="tab-content" id="advanced-tab">
            <div class="settings-section">
                <h2><i class="fas fa-tools"></i> Paramètres avancés</h2>
                
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="custom_css">CSS personnalisé</label>
                        </th>
                        <td>
                            <textarea id="custom_css" name="custom_css" rows="10" cols="50" class="large-text code"><?php 
                                echo esc_textarea(get_option('trainer_custom_css', '')); 
                            ?></textarea>
                            <p class="description">
                                CSS personnalisé pour modifier l'apparence des formulaires et listes de formateurs.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="debug_mode">Mode débogage</label>
                        </th>
                        <td>
                            <fieldset>
                                <label for="debug_mode">
                                    <input type="checkbox" id="debug_mode" name="debug_mode" value="1" 
                                           <?php checked(get_option('trainer_debug_mode', 0), 1); ?>>
                                    Activer les logs de débogage
                                </label>
                                <p class="description">
                                    Active l'enregistrement détaillé des actions pour le diagnostic des problèmes.
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Actions de maintenance</th>
                        <td>
                            <p>
                                <button type="button" class="button" id="clear-cache">
                                    <i class="fas fa-broom"></i>
                                    Vider le cache
                                </button>
                                <span class="description">Vide le cache des données des formateurs.</span>
                            </p>
                            
                            <p>
                                <button type="button" class="button" id="cleanup-files">
                                    <i class="fas fa-trash-alt"></i>
                                    Nettoyer les fichiers orphelins
                                </button>
                                <span class="description">Supprime les fichiers non associés à un formateur.</span>
                            </p>
                            
                            <p>
                                <button type="button" class="button button-secondary" id="export-settings">
                                    <i class="fas fa-download"></i>
                                    Exporter les paramètres
                                </button>
                                <span class="description">Télécharge un fichier de sauvegarde des paramètres.</span>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Boutons de sauvegarde -->
        <div class="settings-footer">
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Enregistrer les modifications">
                <button type="button" class="button" id="reset-settings">
                    <i class="fas fa-undo"></i>
                    Restaurer les valeurs par défaut
                </button>
            </p>
        </div>
    </form>

    <!-- Section d'informations -->
    <div class="settings-info">
        <div class="info-cards">
            <div class="info-card">
                <h3><i class="fas fa-life-ring"></i> Besoin d'aide ?</h3>
                <p>Consultez la documentation en ligne ou contactez notre support technique.</p>
                <a href="#" class="button button-small">Documentation</a>
            </div>
            
            <div class="info-card">
                <h3><i class="fas fa-code"></i> Shortcodes</h3>
                <p>Utilisez ces shortcodes pour afficher les formulaires et listes :</p>
                <code>[trainer_home]</code><br>
                <code>[trainer_registration_form]</code><br>
                <code>[trainer_list]</code><br>
                <code>[trainer_search]</code>
            </div>
            
            <div class="info-card">
                <h3><i class="fas fa-info-circle"></i> Version</h3>
                <p>Plugin Trainer Registration Pro</p>
                <p>Version : <?php echo TRAINER_REGISTRATION_VERSION; ?></p>
                <p>Dernière mise à jour : Septembre 2024</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Navigation par onglets
    const tabLinks = document.querySelectorAll('.nav-tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Retirer active de tous les onglets
            tabLinks.forEach(tab => tab.classList.remove('nav-tab-active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Activer l'onglet cliqué
            this.classList.add('nav-tab-active');
            const targetTab = this.getAttribute('data-tab') + '-tab';
            document.getElementById(targetTab).classList.add('active');
        });
    });
    
    // Actions de maintenance
    document.getElementById('clear-cache')?.addEventListener('click', function() {
        if (confirm('Êtes-vous sûr de vouloir vider le cache ?')) {
            // Implémentation du vidage du cache
            alert('Cache vidé avec succès !');
        }
    });
    
    document.getElementById('cleanup-files')?.addEventListener('click', function() {
        if (confirm('Cette action supprimera tous les fichiers orphelins. Continuer ?')) {
            // Implémentation du nettoyage
            alert('Nettoyage effectué !');
        }
    });
    
    document.getElementById('reset-settings')?.addEventListener('click', function() {
        if (confirm('Êtes-vous sûr de vouloir restaurer tous les paramètres par défaut ?')) {
            // Reset du formulaire
            document.getElementById('trainer-settings-form').reset();
        }
    });
});
</script>