
<?php
/**
 * Section Hero Style Stripe - Template PHP
 * 
 * À intégrer dans: public/partials/trainer-home.php (remplacer la section hero existante)
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Hero Section Style Stripe -->
<section class="trpro-stripe-hero" itemscope itemtype="https://schema.org/Organization">
    <!-- Image de fond responsive avec overlay -->
    <div class="trpro-hero-background" role="img" aria-label="Illustration formateurs IT experts"></div>
    <div class="trpro-hero-overlay"></div>
    
    <div class="trpro-hero-container">
        <div class="trpro-hero-grid">
            <!-- Contenu principal -->
            <div class="trpro-hero-content">
                <!-- Badge de qualité -->
                <div class="trpro-hero-badge">
                    <span class="trpro-badge-icon">✨</span>
                    <span>Réseau d'Excellence IT</span>
                </div>
                
                <!-- Titre principal SEO optimisé -->
                <h1 class="trpro-hero-title" itemprop="name">
                    <?php echo esc_html($atts['title'] ?? 'Catalogue des Formateurs IT Experts'); ?>
                </h1>
                
                <!-- Sous-titre -->
                <p class="trpro-hero-subtitle">
                    <?php echo esc_html($atts['subtitle'] ?? 'Connectez votre expertise avec les meilleurs recruteurs spécialisés'); ?>
                </p>
                
                <!-- Description détaillée -->
                <p class="trpro-hero-description" itemprop="description">
                    <?php echo esc_html($atts['description'] ?? 'Rejoignez notre plateforme exclusive de formateurs spécialisés en informatique et télécommunications. Développez votre réseau professionnel et accédez aux meilleures opportunités de formation.'); ?>
                </p>
                
                <!-- Actions CTA -->
                <div class="trpro-hero-actions">
                    <a href="<?php echo esc_url(get_permalink(get_page_by_path('inscription-formateur'))); ?>" 
                       class="trpro-cta-primary" 
                       aria-label="S'inscrire comme formateur expert">
                        <span class="trpro-cta-text">Devenir Formateur</span>
                        <span class="trpro-cta-icon">
                            <i class="fas fa-arrow-right"></i>
                        </span>
                    </a>
                    
                    <a href="<?php echo esc_url(get_permalink(get_page_by_path('catalogue-formateurs'))); ?>" 
                       class="trpro-cta-secondary"
                       aria-label="Explorer le catalogue des formateurs">
                        <span class="trpro-cta-text">Trouver un Expert</span>
                        <span class="trpro-cta-icon">
                            <i class="fas fa-search"></i>
                        </span>
                    </a>
                </div>
                
                <!-- Indicateurs de confiance -->
                <div class="trpro-hero-trust">
                    <div class="trpro-trust-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>100% Sécurisé</span>
                    </div>
                    <div class="trpro-trust-item">
                        <i class="fas fa-certificate"></i>
                        <span>Profils Certifiés</span>
                    </div>
                    <div class="trpro-trust-item">
                        <i class="fas fa-users"></i>
                        <span><?php echo $total_trainers ?: '150+'; ?> Experts</span>
                    </div>
                </div>
            </div>
            
            <!-- Statistiques visuelles -->
            <div class="trpro-hero-stats">
                <div class="trpro-stat-card">
                    <div class="trpro-stat-number"><?php echo $total_trainers ?: '150'; ?>+</div>
                    <div class="trpro-stat-label">Experts Certifiés</div>
                    <div class="trpro-stat-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>
                
                <div class="trpro-stat-card">
                    <div class="trpro-stat-number">8</div>
                    <div class="trpro-stat-label">Spécialités IT</div>
                    <div class="trpro-stat-icon">
                        <i class="fas fa-code-branch"></i>
                    </div>
                </div>
                
                <div class="trpro-stat-card">
                    <div class="trpro-stat-number">100%</div>
                    <div class="trpro-stat-label">Profils Vérifiés</div>
                    <div class="trpro-stat-icon">
                        <i class="fas fa-badge-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Éléments décoratifs animés -->
    <div class="trpro-hero-decorations" aria-hidden="true">
        <div class="trpro-decoration-element" style="--delay: 0s; --x: 10%; --y: 20%;">
            <i class="fas fa-server"></i>
        </div>
        <div class="trpro-decoration-element" style="--delay: 1s; --x: 90%; --y: 30%;">
            <i class="fas fa-cloud"></i>
        </div>
        <div class="trpro-decoration-element" style="--delay: 2s; --x: 15%; --y: 70%;">
            <i class="fas fa-shield-alt"></i>
        </div>
        <div class="trpro-decoration-element" style="--delay: 3s; --x: 85%; --y: 80%;">
            <i class="fas fa-database"></i>
        </div>
    </div>
</section>

<script>
// JavaScript pour animations et interactions
document.addEventListener('DOMContentLoaded', function() {
    // Animation d'apparition progressive
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observer les éléments à animer
    const elementsToAnimate = document.querySelectorAll('.trpro-stat-card');
    elementsToAnimate.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
    
    // Analytics tracking pour les CTA
    const ctaButtons = document.querySelectorAll('.trpro-cta-primary, .trpro-cta-secondary');
    ctaButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Tracking Google Analytics si disponible
            if (typeof gtag !== 'undefined') {
                gtag('event', 'click', {
                    'event_category': 'CTA',
                    'event_label': this.querySelector('.trpro-cta-text').textContent
                });
            }
        });
    });
});
</script>

