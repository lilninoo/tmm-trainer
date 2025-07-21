<?php
/**
 * Classe pour la gestion avancée des emails - VERSION SÉCURISÉE
 * 
 * Fichier: /wp-content/plugins/trainer-registration-plugin/includes/class-trpro-email.php
 * ✅ SÉCURISÉ: Validation renforcée et sanitisation des données
 * ✅ CORRECTION: Gestion robuste des erreurs et logging
 */

if (!defined('ABSPATH')) {
    exit;
}

class TrproEmailManager {
    
    private static $instance = null;
    private $email_templates = array();
    private $email_queue = array();
    private $max_queue_size = 100;
    private $max_email_length = 10000; // Limite pour éviter les abus
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_email_templates();
        add_action('wp_loaded', array($this, 'process_email_queue'));
        add_action('trpro_send_scheduled_email', array($this, 'send_scheduled_email'), 10, 3);
        
        // ✅ SÉCURITÉ: Hooks de nettoyage automatique
        add_action('wp_scheduled_delete', array($this, 'cleanup_email_logs'));
        add_action('init', array($this, 'validate_email_settings'));
    }
    
    /**
     * ✅ SÉCURITÉ: Validation des paramètres email au démarrage
     */
    public function validate_email_settings() {
        $contact_email = get_option('trainer_contact_email', '');
        if (!empty($contact_email) && !is_email($contact_email)) {
            error_log('TrproEmailManager: Invalid contact email configured: ' . $contact_email);
            update_option('trainer_contact_email', get_option('admin_email'));
        }
        
        $company_name = get_option('trainer_company_name', '');
        if (!empty($company_name)) {
            // Nettoyer le nom de l'entreprise
            $clean_name = sanitize_text_field($company_name);
            if ($clean_name !== $company_name) {
                update_option('trainer_company_name', $clean_name);
            }
        }
    }
    
    /**
     * Initialiser les templates d'email
     */
    private function init_email_templates() {
        $this->email_templates = array(
            'trainer_registration_confirmation' => array(
                'subject' => 'Confirmation de votre inscription - Plateforme Formateurs IT',
                'template' => 'registration-confirmation',
                'description' => 'Email de confirmation envoyé au formateur après inscription'
            ),
            'trainer_approved' => array(
                'subject' => 'Félicitations ! Votre candidature a été approuvée',
                'template' => 'trainer-approved',
                'description' => 'Email envoyé quand le formateur est approuvé'
            ),
            'trainer_rejected' => array(
                'subject' => 'Mise à jour de votre candidature',
                'template' => 'trainer-rejected',
                'description' => 'Email envoyé quand le formateur est rejeté'
            ),
            'admin_new_trainer' => array(
                'subject' => 'Nouvelle inscription formateur - Action requise',
                'template' => 'admin-new-trainer',
                'description' => 'Email envoyé à l\'admin pour nouvelle inscription'
            ),
            'trainer_contact_request' => array(
                'subject' => 'Nouvelle demande de contact',
                'template' => 'contact-request',
                'description' => 'Email pour demandes de contact'
            ),
            'weekly_summary' => array(
                'subject' => 'Résumé hebdomadaire - Plateforme Formateurs',
                'template' => 'weekly-summary',
                'description' => 'Résumé hebdomadaire des activités'
            ),
            'pending_reminder' => array(
                'subject' => 'Formateurs en attente de validation',
                'template' => 'pending-reminder',
                'description' => 'Rappel pour formateurs en attente'
            )
        );
    }
    
    /**
     * ✅ SÉCURISÉ: Envoyer un email avec template et validation renforcée
     */
    public function send_template_email($template_key, $to, $data = array(), $schedule = false) {
        // ✅ VALIDATION: Template existe
        if (!isset($this->email_templates[$template_key])) {
            error_log("TrproEmailManager: Template $template_key not found");
            return false;
        }
        
        // ✅ VALIDATION: Email destinataire
        if (!is_email($to)) {
            error_log("TrproEmailManager: Invalid email address: $to");
            return false;
        }
        
        // ✅ SÉCURITÉ: Sanitiser les données
        $data = $this->sanitize_email_data($data);
        
        // ✅ SÉCURITÉ: Vérifier la taille des données
        if ($this->get_data_size($data) > $this->max_email_length) {
            error_log("TrproEmailManager: Email data too large");
            return false;
        }
        
        $template = $this->email_templates[$template_key];
        
        try {
            // Générer le contenu de l'email
            $email_content = $this->generate_email_content($template['template'], $data);
            $subject = $this->parse_template_vars($template['subject'], $data);
            
            if ($schedule) {
                return $this->schedule_email($to, $subject, $email_content, $data);
            } else {
                return $this->send_email($to, $subject, $email_content);
            }
        } catch (Exception $e) {
            error_log("TrproEmailManager: Error sending template email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ✅ SÉCURISÉ: Sanitiser les données d'email
     */
    private function sanitize_email_data($data) {
        if (!is_array($data)) {
            return array();
        }
        
        $sanitized = array();
        foreach ($data as $key => $value) {
            $clean_key = sanitize_key($key);
            
            if (is_string($value)) {
                // Longueur maximale par champ
                if (strlen($value) > 2000) {
                    $value = substr($value, 0, 2000) . '...';
                }
                $sanitized[$clean_key] = sanitize_text_field($value);
            } elseif (is_array($value)) {
                $sanitized[$clean_key] = $this->sanitize_email_data($value);
            } elseif (is_numeric($value)) {
                $sanitized[$clean_key] = $value;
            } else {
                // Ignorer les autres types
                continue;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * ✅ SÉCURITÉ: Calculer la taille des données
     */
    private function get_data_size($data) {
        return strlen(serialize($data));
    }
    
    /**
     * ✅ SÉCURISÉ: Envoyer un email immédiatement avec validation
     */
    public function send_email($to, $subject, $message, $headers = array()) {
        // ✅ VALIDATION: Paramètres
        if (!is_email($to)) {
            error_log("TrproEmailManager: Invalid recipient email: $to");
            return false;
        }
        
        if (empty($subject) || empty($message)) {
            error_log("TrproEmailManager: Empty subject or message");
            return false;
        }
        
        // ✅ SÉCURITÉ: Sanitiser le sujet et le message
        $subject = sanitize_text_field($subject);
        $message = wp_kses_post($message); // Autorise seulement les balises HTML sûres
        
        // ✅ SÉCURITÉ: Limiter la longueur
        if (strlen($subject) > 200) {
            $subject = substr($subject, 0, 200);
        }
        
        if (strlen($message) > $this->max_email_length) {
            $message = substr($message, 0, $this->max_email_length) . "\n\n[Message tronqué pour des raisons de sécurité]";
        }
        
        // ✅ SÉCURITÉ: Headers par défaut sécurisés
        $default_headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->get_from_name() . ' <' . $this->get_from_email() . '>',
            'X-Mailer: Trainer Registration Pro'
        );
        
        // ✅ SÉCURITÉ: Valider les headers personnalisés
        $validated_headers = array();
        foreach ($headers as $header) {
            if ($this->is_valid_email_header($header)) {
                $validated_headers[] = $header;
            }
        }
        
        $final_headers = array_merge($default_headers, $validated_headers);
        
        // Wrapper HTML sécurisé
        $html_message = $this->wrap_email_content($message, $subject);
        
        // ✅ SÉCURITÉ: Vérifier les limites de taux
        if (!$this->check_rate_limit($to)) {
            error_log("TrproEmailManager: Rate limit exceeded for: $to");
            return false;
        }
        
        // Log de l'envoi
        $this->log_email_sent($to, $subject);
        
        // Envoi sécurisé
        try {
            $result = wp_mail($to, $subject, $html_message, $final_headers);
            
            if ($result) {
                $this->increment_rate_limit($to);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("TrproEmailManager: Error sending email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ✅ SÉCURITÉ: Valider un header d'email
     */
    private function is_valid_email_header($header) {
        // Interdire les headers dangereux
        $dangerous_headers = array('to:', 'cc:', 'bcc:', 'subject:', 'content-type:', 'mime-version:');
        
        foreach ($dangerous_headers as $dangerous) {
            if (stripos($header, $dangerous) === 0) {
                return false;
            }
        }
        
        // Vérifier les caractères dangereux
        if (preg_match('/[\r\n\0]/', $header)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * ✅ SÉCURITÉ: Vérifier la limitation de taux d'envoi
     */
    private function check_rate_limit($email) {
        $key = 'trpro_email_rate_' . md5($email);
        $count = get_transient($key);
        
        // Limite: 5 emails par heure par destinataire
        return $count === false || $count < 5;
    }
    
    /**
     * ✅ SÉCURITÉ: Incrémenter le compteur de taux
     */
    private function increment_rate_limit($email) {
        $key = 'trpro_email_rate_' . md5($email);
        $count = get_transient($key) ?: 0;
        $count++;
        
        set_transient($key, $count, HOUR_IN_SECONDS);
    }
    
    /**
     * Programmer un email
     */
    public function schedule_email($to, $subject, $message, $data = array(), $delay = 0) {
        // ✅ VALIDATION
        if (!is_email($to) || empty($subject) || empty($message)) {
            return false;
        }
        
        $scheduled_time = time() + max(0, intval($delay));
        
        return wp_schedule_single_event(
            $scheduled_time,
            'trpro_send_scheduled_email',
            array($to, $subject, $message)
        );
    }
    
    /**
     * Envoyer un email programmé
     */
    public function send_scheduled_email($to, $subject, $message) {
        return $this->send_email($to, $subject, $message);
    }
    
    /**
     * ✅ SÉCURISÉ: Générer le contenu d'un email depuis un template
     */
    private function generate_email_content($template_name, $data = array()) {
        ob_start();
        
        try {
            switch ($template_name) {
                case 'registration-confirmation':
                    echo $this->get_registration_confirmation_template($data);
                    break;
                    
                case 'trainer-approved':
                    echo $this->get_trainer_approved_template($data);
                    break;
                    
                case 'trainer-rejected':
                    echo $this->get_trainer_rejected_template($data);
                    break;
                    
                case 'admin-new-trainer':
                    echo $this->get_admin_new_trainer_template($data);
                    break;
                    
                case 'contact-request':
                    echo $this->get_contact_request_template($data);
                    break;
                    
                case 'weekly-summary':
                    echo $this->get_weekly_summary_template($data);
                    break;
                    
                case 'pending-reminder':
                    echo $this->get_pending_reminder_template($data);
                    break;
                    
                default:
                    echo '<p>Template non trouvé.</p>';
            }
        } catch (Exception $e) {
            error_log("TrproEmailManager: Error generating template: " . $e->getMessage());
            echo '<p>Erreur lors de la génération du contenu.</p>';
        }
        
        return ob_get_clean();
    }
    
    /**
     * ✅ SÉCURISÉ: Template de confirmation d'inscription
     */
    private function get_registration_confirmation_template($data) {
        $trainer_name = !empty($data['first_name']) ? esc_html($data['first_name']) : 'Formateur';
        $company_name = esc_html(get_option('trainer_company_name', get_bloginfo('name')));
        $contact_email = esc_attr(get_option('trainer_contact_email', get_option('admin_email')));
        
        return "
        <div style='background: #f8fafc; padding: 40px 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                <div style='background: linear-gradient(135deg, #6366f1, #8b5cf6); padding: 40px; text-align: center; color: white;'>
                    <h1 style='margin: 0; font-size: 28px; font-weight: 700;'>Bienvenue {$trainer_name} !</h1>
                    <p style='margin: 16px 0 0 0; font-size: 18px; opacity: 0.9;'>Votre inscription a été reçue avec succès</p>
                </div>
                
                <div style='padding: 40px;'>
                    <div style='text-align: center; margin-bottom: 32px;'>
                        <div style='width: 80px; height: 80px; background: #e0e7ff; border-radius: 50%; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center;'>
                            <span style='font-size: 32px;'>✅</span>
                        </div>
                        <h2 style='color: #1f2937; margin: 0 0 8px 0;'>Inscription Confirmée</h2>
                        <p style='color: #6b7280; margin: 0;'>Nous examinons actuellement votre candidature</p>
                    </div>
                    
                    <div style='background: #f9fafb; border-radius: 8px; padding: 24px; margin-bottom: 32px;'>
                        <h3 style='color: #374151; margin: 0 0 16px 0; font-size: 18px;'>Prochaines étapes :</h3>
                        <div style='space-y: 12px;'>
                            <div style='display: flex; align-items: center; margin-bottom: 12px;'>
                                <span style='width: 24px; height: 24px; background: #10b981; border-radius: 50%; color: white; display: flex; align-items: center; justify-content: center; margin-right: 12px; font-size: 12px;'>1</span>
                                <span style='color: #4b5563;'>Vérification de votre profil et documents</span>
                            </div>
                            <div style='display: flex; align-items: center; margin-bottom: 12px;'>
                                <span style='width: 24px; height: 24px; background: #f59e0b; border-radius: 50%; color: white; display: flex; align-items: center; justify-content: center; margin-right: 12px; font-size: 12px;'>2</span>
                                <span style='color: #4b5563;'>Validation par notre équipe sous 48h</span>
                            </div>
                            <div style='display: flex; align-items: center;'>
                                <span style='width: 24px; height: 24px; background: #6366f1; border-radius: 50%; color: white; display: flex; align-items: center; justify-content: center; margin-right: 12px; font-size: 12px;'>3</span>
                                <span style='color: #4b5563;'>Activation de votre profil formateur</span>
                            </div>
                        </div>
                    </div>
                    
                    <div style='text-align: center; margin-bottom: 32px;'>
                        <p style='color: #6b7280; margin: 0 0 20px 0;'>En attendant, n'hésitez pas à nous contacter pour toute question</p>
                        <a href='mailto:{$contact_email}' style='background: #6366f1; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600;'>Nous contacter</a>
                    </div>
                </div>
                
                <div style='background: #f9fafb; padding: 24px; text-align: center; border-top: 1px solid #e5e7eb;'>
                    <p style='margin: 0; color: #6b7280; font-size: 14px;'>
                        Cet email a été envoyé par {$company_name}<br>
                        Si vous n'êtes pas à l'origine de cette inscription, veuillez ignorer cet email.
                    </p>
                </div>
            </div>
        </div>";
    }
    
    /**
     * ✅ SÉCURISÉ: Template de formateur approuvé
     */
    private function get_trainer_approved_template($data) {
        $trainer_name = !empty($data['first_name']) ? esc_html($data['first_name']) : 'Formateur';
        $contact_email = esc_attr(get_option('trainer_contact_email', get_option('admin_email')));
        
        return "
        <div style='background: #f0fdf4; padding: 40px 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                <div style='background: linear-gradient(135deg, #10b981, #059669); padding: 40px; text-align: center; color: white;'>
                    <div style='font-size: 48px; margin-bottom: 16px;'>🎉</div>
                    <h1 style='margin: 0; font-size: 28px; font-weight: 700;'>Félicitations {$trainer_name} !</h1>
                    <p style='margin: 16px 0 0 0; font-size: 18px; opacity: 0.9;'>Votre candidature a été approuvée</p>
                </div>
                
                <div style='padding: 40px;'>
                    <div style='text-align: center; margin-bottom: 32px;'>
                        <h2 style='color: #1f2937; margin: 0 0 16px 0;'>Bienvenue dans notre réseau !</h2>
                        <p style='color: #6b7280; line-height: 1.6;'>
                            Votre profil de formateur expert est maintenant actif sur notre plateforme. 
                            Les recruteurs peuvent désormais consulter vos compétences et vous contacter 
                            pour des opportunités de formation.
                        </p>
                    </div>
                    
                    <div style='background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 24px; margin-bottom: 32px;'>
                        <h3 style='color: #065f46; margin: 0 0 16px 0; font-size: 18px;'>Avantages de votre adhésion :</h3>
                        <ul style='color: #047857; margin: 0; padding-left: 20px;'>
                            <li style='margin-bottom: 8px;'>Visibilité auprès de recruteurs qualifiés</li>
                            <li style='margin-bottom: 8px;'>Accès prioritaire aux missions de formation</li>
                            <li style='margin-bottom: 8px;'>Support dédié pour vos candidatures</li>
                            <li style='margin-bottom: 8px;'>Réseau d'experts pour échanger</li>
                        </ul>
                    </div>
                    
                    <div style='text-align: center;'>
                        <p style='color: #6b7280; margin: 0 0 20px 0;'>Votre profil est maintenant visible par les recruteurs</p>
                        <div style='display: inline-block; margin: 0 8px;'>
                            <a href='mailto:{$contact_email}' style='background: #10b981; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-right: 12px;'>Nous contacter</a>
                        </div>
                    </div>
                </div>
                
                <div style='background: #f9fafb; padding: 24px; text-align: center; border-top: 1px solid #e5e7eb;'>
                    <p style='margin: 0; color: #6b7280; font-size: 14px;'>
                        Merci de faire partie de notre réseau d'excellence !
                    </p>
                </div>
            </div>
        </div>";
    }
    
    /**
     * ✅ SÉCURISÉ: Template de formateur rejeté
     */
    private function get_trainer_rejected_template($data) {
        $trainer_name = !empty($data['first_name']) ? esc_html($data['first_name']) : 'Formateur';
        $reason = !empty($data['rejection_reason']) ? esc_html($data['rejection_reason']) : '';
        $contact_email = esc_attr(get_option('trainer_contact_email', get_option('admin_email')));
        
        return "
        <div style='background: #fef2f2; padding: 40px 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                <div style='background: linear-gradient(135deg, #f59e0b, #d97706); padding: 40px; text-align: center; color: white;'>
                    <h1 style='margin: 0; font-size: 28px; font-weight: 700;'>Mise à jour de votre candidature</h1>
                    <p style='margin: 16px 0 0 0; font-size: 18px; opacity: 0.9;'>Bonjour {$trainer_name}</p>
                </div>
                
                <div style='padding: 40px;'>
                    <p style='color: #6b7280; line-height: 1.6; margin-bottom: 24px;'>
                        Nous vous remercions pour l'intérêt que vous portez à notre plateforme de formateurs.
                        Après examen de votre candidature, nous ne pouvons malheureusement pas 
                        l'accepter en l'état actuel.
                    </p>
                    
                    " . (!empty($reason) ? "
                    <div style='background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 20px; margin-bottom: 24px;'>
                        <h3 style='color: #92400e; margin: 0 0 12px 0;'>Motif :</h3>
                        <p style='color: #a16207; margin: 0;'>{$reason}</p>
                    </div>
                    " : "") . "
                    
                    <div style='background: #f0f9ff; border: 1px solid #7dd3fc; border-radius: 8px; padding: 20px; margin-bottom: 24px;'>
                        <h3 style='color: #0c4a6e; margin: 0 0 12px 0;'>Vous pouvez :</h3>
                        <ul style='color: #0369a1; margin: 0; padding-left: 20px;'>
                            <li style='margin-bottom: 8px;'>Compléter votre profil avec plus d'informations</li>
                            <li style='margin-bottom: 8px;'>Ajouter des certifications récentes</li>
                            <li style='margin-bottom: 8px;'>Mettre à jour votre CV avec vos dernières expériences</li>
                            <li>Nous recontacter dans quelques mois</li>
                        </ul>
                    </div>
                    
                    <div style='text-align: center;'>
                        <p style='color: #6b7280; margin: 0 0 20px 0;'>
                            N'hésitez pas à nous contacter pour plus d'informations
                        </p>
                        <a href='mailto:{$contact_email}' style='background: #f59e0b; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600;'>Nous contacter</a>
                    </div>
                </div>
                
                <div style='background: #f9fafb; padding: 24px; text-align: center; border-top: 1px solid #e5e7eb;'>
                    <p style='margin: 0; color: #6b7280; font-size: 14px;'>
                        Merci pour votre compréhension.
                    </p>
                </div>
            </div>
        </div>";
    }
    
    /**
     * ✅ SÉCURISÉ: Template pour notification admin
     */
    private function get_admin_new_trainer_template($data) {
        $trainer_name = '';
        if (!empty($data['first_name']) && !empty($data['last_name'])) {
            $trainer_name = esc_html($data['first_name']) . ' ' . esc_html($data['last_name']);
        } else {
            $trainer_name = 'Nouveau formateur';
        }
        
        $trainer_id = !empty($data['trainer_id']) ? intval($data['trainer_id']) : '';
        $trainer_email = !empty($data['email']) ? esc_html($data['email']) : '';
        $trainer_phone = !empty($data['phone']) ? esc_html($data['phone']) : '';
        $trainer_specialties = !empty($data['specialties']) ? esc_html($data['specialties']) : '';
        
        $admin_url = admin_url('admin.php?page=trainer-registration&action=view&trainer_id=' . $trainer_id);
        
        return "
        <div style='background: #f8fafc; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                <div style='background: #6366f1; color: white; padding: 24px; border-radius: 8px 8px 0 0;'>
                    <h1 style='margin: 0; font-size: 24px;'>Nouvelle inscription formateur</h1>
                    <p style='margin: 8px 0 0 0; opacity: 0.9;'>Action requise</p>
                </div>
                
                <div style='padding: 24px;'>
                    <div style='background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; margin-bottom: 24px;'>
                        <p style='margin: 0; color: #92400e; font-weight: 600;'>
                            ⚠️ Un nouveau formateur attend votre validation
                        </p>
                    </div>
                    
                    <h2 style='color: #1f2937; margin: 0 0 16px 0;'>{$trainer_name}</h2>
                    
                    <div style='background: #f9fafb; padding: 16px; border-radius: 6px; margin-bottom: 20px;'>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 8px 0; color: #6b7280; width: 120px;'>ID :</td>
                                <td style='padding: 8px 0; color: #1f2937; font-weight: 600;'>#{$trainer_id}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #6b7280;'>Email :</td>
                                <td style='padding: 8px 0; color: #1f2937;'>{$trainer_email}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #6b7280;'>Téléphone :</td>
                                <td style='padding: 8px 0; color: #1f2937;'>{$trainer_phone}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #6b7280; vertical-align: top;'>Spécialités :</td>
                                <td style='padding: 8px 0; color: #1f2937;'>{$trainer_specialties}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div style='text-align: center; margin: 32px 0;'>
                        <a href='{$admin_url}' style='background: #10b981; color: white; padding: 14px 28px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-right: 12px;'>Examiner le profil</a>
                        <a href='" . admin_url('admin.php?page=trainer-registration') . "' style='background: #6b7280; color: white; padding: 14px 28px; border-radius: 6px; text-decoration: none; font-weight: 600;'>Voir tous les formateurs</a>
                    </div>
                </div>
            </div>
        </div>";
    }
    
    /**
     * ✅ SÉCURISÉ: Template pour demande de contact
     */
    private function get_contact_request_template($data) {
        $trainer_name = !empty($data['trainer_name']) ? esc_html($data['trainer_name']) : 'Formateur';
        $contact_name = !empty($data['contact_name']) ? esc_html($data['contact_name']) : '';
        $contact_email = !empty($data['contact_email']) ? esc_html($data['contact_email']) : '';
        $contact_company = !empty($data['contact_company']) ? esc_html($data['contact_company']) : '';
        $contact_message = !empty($data['contact_message']) ? esc_html($data['contact_message']) : '';
        
        return "
        <div style='background: #f8fafc; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                <div style='background: #059669; color: white; padding: 24px; border-radius: 8px 8px 0 0;'>
                    <h1 style='margin: 0; font-size: 24px;'>Nouvelle demande de contact</h1>
                    <p style='margin: 8px 0 0 0; opacity: 0.9;'>Pour le formateur {$trainer_name}</p>
                </div>
                
                <div style='padding: 24px;'>
                    <div style='background: #f0fdf4; border-left: 4px solid #10b981; padding: 16px; margin-bottom: 24px;'>
                        <p style='margin: 0; color: #065f46; font-weight: 600;'>
                            📧 Une nouvelle demande de contact a été reçue
                        </p>
                    </div>
                    
                    <div style='background: #f9fafb; padding: 16px; border-radius: 6px; margin-bottom: 20px;'>
                        <h3 style='color: #374151; margin: 0 0 12px 0;'>Informations du demandeur :</h3>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 8px 0; color: #6b7280; width: 120px;'>Nom :</td>
                                <td style='padding: 8px 0; color: #1f2937; font-weight: 600;'>{$contact_name}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #6b7280;'>Email :</td>
                                <td style='padding: 8px 0; color: #1f2937;'>{$contact_email}</td>
                            </tr>
                            " . (!empty($contact_company) ? "
                            <tr>
                                <td style='padding: 8px 0; color: #6b7280;'>Entreprise :</td>
                                <td style='padding: 8px 0; color: #1f2937;'>{$contact_company}</td>
                            </tr>
                            " : "") . "
                        </table>
                    </div>
                    
                    " . (!empty($contact_message) ? "
                    <div style='background: #f0f9ff; border: 1px solid #7dd3fc; border-radius: 8px; padding: 20px; margin-bottom: 24px;'>
                        <h3 style='color: #0c4a6e; margin: 0 0 12px 0;'>Message :</h3>
                        <div style='color: #0369a1; line-height: 1.6;'>{$contact_message}</div>
                    </div>
                    " : "") . "
                    
                    <div style='text-align: center; margin: 32px 0;'>
                        <p style='color: #6b7280; margin: 0 0 20px 0;'>Répondez directement à cet email pour mettre en relation.</p>
                        <a href='mailto:{$contact_email}' style='background: #059669; color: white; padding: 14px 28px; border-radius: 6px; text-decoration: none; font-weight: 600;'>Répondre par email</a>
                    </div>
                </div>
            </div>
        </div>";
    }
    
    /**
     * Template pour résumé hebdomadaire
     */
    private function get_weekly_summary_template($data) {
        $new_registrations = intval($data['new_registrations'] ?? 0);
        $approved_this_week = intval($data['approved_this_week'] ?? 0);
        $pending_total = intval($data['pending_total'] ?? 0);
        
        return "
        <div style='background: #f8fafc; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                <div style='background: #6366f1; color: white; padding: 24px; border-radius: 8px 8px 0 0;'>
                    <h1 style='margin: 0; font-size: 24px;'>Résumé hebdomadaire</h1>
                    <p style='margin: 8px 0 0 0; opacity: 0.9;'>Activité de la semaine</p>
                </div>
                
                <div style='padding: 24px;'>
                    <div style='display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;'>
                        <div style='background: #f0f9ff; padding: 16px; border-radius: 8px; text-align: center;'>
                            <div style='font-size: 32px; font-weight: 700; color: #1d4ed8;'>{$new_registrations}</div>
                            <div style='color: #6b7280; font-size: 14px;'>Nouvelles inscriptions</div>
                        </div>
                        <div style='background: #f0fdf4; padding: 16px; border-radius: 8px; text-align: center;'>
                            <div style='font-size: 32px; font-weight: 700; color: #059669;'>{$approved_this_week}</div>
                            <div style='color: #6b7280; font-size: 14px;'>Formateurs approuvés</div>
                        </div>
                        <div style='background: #fef3c7; padding: 16px; border-radius: 8px; text-align: center;'>
                            <div style='font-size: 32px; font-weight: 700; color: #d97706;'>{$pending_total}</div>
                            <div style='color: #6b7280; font-size: 14px;'>En attente</div>
                        </div>
                    </div>
                    
                    <div style='text-align: center;'>
                        <a href='" . admin_url('admin.php?page=trainer-registration') . "' style='background: #6366f1; color: white; padding: 14px 28px; border-radius: 6px; text-decoration: none; font-weight: 600;'>Voir le tableau de bord</a>
                    </div>
                </div>
            </div>
        </div>";
    }
    
    /**
     * Template pour rappel formateurs en attente
     */
    private function get_pending_reminder_template($data) {
        $pending_count = intval($data['pending_count'] ?? 0);
        
        return "
        <div style='background: #fef3c7; padding: 20px; border-radius: 8px; border: 1px solid #fcd34d;'>
            <h2 style='color: #92400e; margin: 0 0 16px 0;'>Formateurs en attente</h2>
            <p style='color: #a16207; margin: 0 0 16px 0;'>
                Vous avez <strong>{$pending_count} formateur(s)</strong> en attente de validation depuis plus de 7 jours.
            </p>
            <a href='" . admin_url('admin.php?page=trainer-registration&status_filter=pending') . "' 
               style='background: #f59e0b; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600;'>
                Traiter maintenant
            </a>
        </div>";
    }
    
    /**
     * ✅ SÉCURISÉ: Wrapper HTML pour les emails
     */
    private function wrap_email_content($content, $subject) {
        $company_name = esc_html(get_option('trainer_company_name', get_bloginfo('name')));
        $company_email = esc_attr(get_option('trainer_contact_email', get_option('admin_email')));
        $safe_subject = esc_html($subject);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$safe_subject}</title>
            <style>
                body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; }
                .email-container { max-width: 600px; margin: 0 auto; }
                @media only screen and (max-width: 600px) {
                    .email-container { width: 100% !important; }
                }
            </style>
        </head>
        <body>
            <div class='email-container'>
                {$content}
            </div>
        </body>
        </html>";
    }
    
    /**
     * ✅ SÉCURISÉ: Remplacer les variables dans les templates
     */
    private function parse_template_vars($text, $data) {
        if (!is_array($data)) {
            return $text;
        }
        
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $safe_value = esc_html($value);
                $text = str_replace('{{' . $key . '}}', $safe_value, $text);
            }
        }
        return $text;
    }
    
    /**
     * ✅ SÉCURISÉ: Obtenir l'email expéditeur
     */
    private function get_from_email() {
        $email = get_option('trainer_contact_email', get_option('admin_email'));
        return is_email($email) ? $email : get_option('admin_email');
    }
    
    /**
     * ✅ SÉCURISÉ: Obtenir le nom expéditeur
     */
    private function get_from_name() {
        $name = get_option('trainer_company_name', get_bloginfo('name'));
        return sanitize_text_field($name);
    }
    
    /**
     * Logger les emails envoyés
     */
    private function log_email_sent($to, $subject) {
        if (get_option('trainer_debug_mode', 0)) {
            error_log("TrproEmailManager: Email sent to {$to} - Subject: {$subject}");
        }
        
        // ✅ SÉCURITÉ: Garder un historique limité
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'to' => wp_hash($to), // Hash pour la confidentialité
            'subject' => sanitize_text_field($subject),
            'status' => 'sent'
        );
        
        $logs = get_option('trpro_email_logs', array());
        array_unshift($logs, $log_entry);
        
        // Limiter à 50 entrées
        $logs = array_slice($logs, 0, 50);
        update_option('trpro_email_logs', $logs);
    }
    
    /**
     * ✅ SÉCURITÉ: Nettoyer les logs d'emails
     */
    public function cleanup_email_logs() {
        $logs = get_option('trpro_email_logs', array());
        
        // Garder seulement les logs des 30 derniers jours
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-30 days'));
        $filtered_logs = array();
        
        foreach ($logs as $log) {
            if (isset($log['timestamp']) && $log['timestamp'] > $cutoff_date) {
                $filtered_logs[] = $log;
            }
        }
        
        update_option('trpro_email_logs', $filtered_logs);
    }
    
    /**
     * Traiter la queue d'emails
     */
    public function process_email_queue() {
        // Traitement de la queue d'emails si nécessaire
        // Pour l'instant, les emails sont envoyés directement
    }
    
    /**
     * ✅ SÉCURISÉ: Envoyer un résumé hebdomadaire
     */
    public function send_weekly_summary() {
        if (!get_option('trainer_notify_weekly_summary', 0)) {
            return false;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        // Statistiques de la semaine avec requêtes sécurisées
        $stats = array(
            'new_registrations' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            )),
            'approved_this_week' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE status = %s AND updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
                'approved'
            )),
            'pending_total' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE status = %s",
                'pending'
            ))
        );
        
        $admin_email = get_option('trainer_notification_email', get_option('admin_email'));
        
        if (!is_email($admin_email)) {
            error_log('TrproEmailManager: Invalid admin email for weekly summary');
            return false;
        }
        
        return $this->send_template_email('weekly_summary', $admin_email, $stats);
    }
    
    /**
     * ✅ SÉCURISÉ: Envoyer des rappels pour formateurs en attente
     */
    public function send_pending_reminders() {
        if (!get_option('trainer_notify_pending_review', 1)) {
            return false;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'trainer_registrations';
        
        $pending_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$table_name} 
            WHERE status = %s 
            AND created_at <= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ", 'pending'));
        
        if ($pending_count > 0) {
            $admin_email = get_option('trainer_notification_email', get_option('admin_email'));
            
            if (!is_email($admin_email)) {
                error_log('TrproEmailManager: Invalid admin email for pending reminders');
                return false;
            }
            
            return $this->send_template_email('pending_reminder', $admin_email, array('pending_count' => $pending_count));
        }
        
        return false;
    }
    
    /**
     * ✅ SÉCURISÉ: Test de l'envoi d'email
     */
    public function test_email_sending($to = null) {
        $test_email = $to ?: get_option('admin_email');
        
        if (!is_email($test_email)) {
            return false;
        }
        
        $subject = 'Test Email - Trainer Registration Pro';
        $message = '
        <div style="padding: 20px; background: #f8fafc;">
            <div style="max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px;">
                <h2 style="color: #6366f1;">Test Email Réussi !</h2>
                <p>Ceci est un email de test pour vérifier que le système d\'envoi fonctionne correctement.</p>
                <p><strong>Heure du test :</strong> ' . esc_html(current_time('d/m/Y H:i:s')) . '</p>
                <div style="background: #f0fdf4; padding: 15px; border-radius: 6px; border-left: 4px solid #10b981;">
                    <p style="margin: 0; color: #065f46;">✅ Configuration email opérationnelle</p>
                </div>
            </div>
        </div>';
        
        return $this->send_email($test_email, $subject, $message);
    }
    
    /**
     * ✅ SÉCURITÉ: Obtenir les statistiques d'emails
     */
    public function get_email_stats() {
        $logs = get_option('trpro_email_logs', array());
        
        $stats = array(
            'total_sent' => count($logs),
            'sent_today' => 0,
            'sent_this_week' => 0,
            'recent_logs' => array_slice($logs, 0, 10)
        );
        
        $today = date('Y-m-d');
        $week_ago = date('Y-m-d', strtotime('-7 days'));
        
        foreach ($logs as $log) {
            if (isset($log['timestamp'])) {
                $log_date = date('Y-m-d', strtotime($log['timestamp']));
                
                if ($log_date === $today) {
                    $stats['sent_today']++;
                }
                
                if ($log_date >= $week_ago) {
                    $stats['sent_this_week']++;
                }
            }
        }
        
        return $stats;
    }
}