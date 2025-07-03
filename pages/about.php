<?php
// pages/about.php
// This file provides the content for the "About Us" page. It is designed to be included
// within a broader site layout (e.g., layouts/app.php or index.php) that handles
// the global <head>, <body>, header, footer, and CSS/JS inclusions.
//
// This ensures that the page inherits the website's established theming, colors, fonts,
// and animations without redundant code.

// Assume SITE_NAME is defined globally (e.g., in config/site_settings.php or index.php)
// Assume BASE_URL is defined globally for navigation links.
?>
<section class="bg-background py-16 md:py-20 animate-fade-in-up">
  <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-5xl">
    <header class="text-center mb-12 md:mb-16">
      <!-- Main heading using font-display and themed colors -->
      <h1 class="font-display text-4xl sm:text-5xl font-bold text-text mb-3">About <span class="text-secondary"><?= htmlspecialchars(SITE_NAME); ?></span></h1>
      <!-- Subtitle using themed colors -->
      <p class="text-lg text-accent max-w-xl mx-auto">
        Our commitment to innovation, quality, and your success.
      </p>
      <!-- Horizontal line for visual separation, styled by global CSS -->
      <span class="section-title-underline"></span>
    </header>

    <div class="grid md:grid-cols-2 gap-12 items-center">
      <!-- Image Section with animation -->
      <div class="animate-slide-in-left">
        <img
          src="https://placehold.co/800x600/0056B3/FFFFFF?text=Our+Team+Working&font=Inter"
          alt="<?= htmlspecialchars(SITE_NAME); ?> Team Collaborating"
          class="rounded-xl shadow-2xl object-cover w-full aspect-[4/3]"
          onerror="this.onerror=null; this.src='https://placehold.co/800x600/4B5563/E5E7EB?text=Team+Placeholder'; this.alt='Team Placeholder';"
        />
      </div>

      <!-- Mission Section with animation delay -->
      <div class="animate-fade-in-up" style="animation-delay: 150ms;">
        <h2 class="font-display text-3xl md:text-4xl font-bold text-text mb-5">Our Mission</h2>
        <p class="text-text/80 mb-4 leading-relaxed">
          At <?= htmlspecialchars(SITE_NAME); ?>, our mission is to empower individuals and businesses through cutting-edge technology. We are dedicated to crafting innovative digital solutions, from dynamic web platforms to robust software, that drive efficiency, foster growth, and solve real-world challenges.
        </p>
        <p class="text-text/80 leading-relaxed">
          We believe in the transformative power of technology and are committed to delivering excellence with every project, ensuring our clients are equipped for success in the digital age.
        </p>
      </div>
    </div>

    <!-- What Drives Us Section -->
    <section class="py-16 md:py-20 bg-base-200 rounded-xl shadow-xl mt-16 md:mt-24">
      <h2 class="font-display text-3xl sm:text-4xl font-bold text-text text-center mb-10">What Drives Us</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 px-6 sm:px-8">
        <!-- Value Card: Innovation -->
        <div class="bg-base-100 p-6 rounded-xl shadow-md flex flex-col items-center text-center transition-all duration-300 hover:shadow-primary/20 hover:-translate-y-1 animate-fade-in-up">
          <div class="p-3 mb-4 bg-primary/10 rounded-full text-primary ring-2 ring-primary/30">
            <i data-lucide="lightbulb" class="w-8 h-8"></i>
          </div>
          <h3 class="font-display text-xl font-semibold text-text mb-3">Innovation</h3>
          <p class="text-text/70 text-sm">We constantly explore new technologies and creative approaches to deliver groundbreaking solutions.</p>
        </div>
        <!-- Value Card: Quality -->
        <div class="bg-base-100 p-6 rounded-xl shadow-md flex flex-col items-center text-center transition-all duration-300 hover:shadow-secondary/20 hover:-translate-y-1 animate-fade-in-up" style="animation-delay: 100ms;">
          <div class="p-3 mb-4 bg-secondary/10 rounded-full text-secondary ring-2 ring-secondary/30">
            <i data-lucide="shield-check" class="w-8 h-8"></i>
          </div>
          <h3 class="font-display text-xl font-semibold text-text mb-3">Quality</h3>
          <p class="text-text/70 text-sm">Our commitment to excellence ensures robust, reliable, and high-performing products and services.</p>
        </div>
        <!-- Value Card: Partnership -->
        <div class="bg-base-100 p-6 rounded-xl shadow-md flex flex-col items-center text-center transition-all duration-300 hover:shadow-accent/20 hover:-translate-y-1 animate-fade-in-up" style="animation-delay: 200ms;">
          <div class="p-3 mb-4 bg-accent/10 rounded-full text-accent ring-2 ring-accent/30">
            <i data-lucide="handshake" class="w-8 h-8"></i>
          </div>
          <h3 class="font-display text-xl font-semibold text-text mb-3">Partnership</h3>
          <p class="text-text/70 text-sm">We build strong, collaborative relationships with our clients, working together to achieve shared goals.</p>
        </div>
      </div>
    </section>

    <!-- Call to Action Section -->
    <div class="text-center mt-16 md:mt-24">
      <h2 class="font-display text-3xl font-bold text-text mb-4">Ready to Collaborate?</h2>
      <p class="text-lg text-accent max-w-xl mx-auto mb-8">
        Discover how our expertise can transform your digital presence.
      </p>
      <a href="<?= htmlspecialchars(BASE_URL); ?>index.php?page=contact"
         class="inline-flex items-center justify-center px-8 py-3 text-lg font-medium text-white bg-primary hover:bg-primary-hover rounded-lg shadow-lg transition-transform hover:scale-105 transform duration-150 ease-out">
        <i data-lucide="mail" class="w-5 h-5 mr-2"></i>Get In Touch
      </a>
    </div>

  </div>
</section>
