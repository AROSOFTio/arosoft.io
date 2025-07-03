<?php
// pages/privacy.php
// This file provides the content for the "Privacy Policy" page. It is designed to be included
// within a broader site layout (e.g., layouts/app.php) that handles
// the global <head>, <body>, header, footer, and CSS/JS inclusions.
//
// This ensures that the page inherits the website's established theming, colors, fonts,
// and animations without redundant code.

// Assume SITE_NAME is defined globally (e.g., in config/site_settings.php or index.php)
// Assume BASE_URL is defined globally for generating URLs.
?>
<section class="bg-background py-16 md:py-20 animate-fade-in-up">
  <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-5xl">
    <header class="text-center mb-12 md:mb-16">
      <!-- Main heading using font-display and themed colors, adjusted font size -->
      <h1 class="font-display text-3xl sm:text-4xl font-bold text-text mb-3">Privacy <span class="text-secondary">Policy</span></h1>
      <!-- Subtitle using themed colors, adjusted font size -->
      <p class="text-base text-accent max-w-xl mx-auto">
        Your privacy is critically important to us.
      </p>
      <!-- Horizontal line for visual separation, styled by global CSS -->
      <span class="section-title-underline"></span>
    </header>

    <article class="bg-base-100 p-6 sm:p-8 md:p-10 rounded-xl shadow-xl prose prose-text max-w-none">
      <p class="text-base text-text/80 mb-6">
        At <?= htmlspecialchars(SITE_NAME); ?>, we have a few fundamental principles:
      </p>
      <ul class="list-disc list-inside text-text/80 mb-6">
        <li>We are thoughtful about the personal information we ask you to provide and the personal information that we collect about you through the operation of our services.</li>
        <li>We store personal information for only as long as we have a reason to keep it.</li>
        <li>We aim for full transparency on how we gather, use, and share your personal information.</li>
      </ul>

      <h2 class="font-display text-2xl font-bold text-primary mt-10 mb-4">Who We Are</h2>
      <p class="text-base text-text/80 mb-6">Our website address is: <code class="text-accent"><?= htmlspecialchars(BASE_URL); ?></code>.</p>

      <h2 class="font-display text-2xl font-bold text-primary mt-10 mb-4">What Personal Data We Collect and Why We Collect It</h2>

      <h3 class="font-display text-xl font-semibold text-secondary mt-8 mb-3">Comments</h3>
      <p class="text-base text-text/80 mb-4">When visitors leave comments on the site, we collect the data shown in the comments form, and also the visitor’s IP address and browser user agent string to help spam detection.</p>
      <p class="text-base text-text/80 mb-6">An anonymized string created from your email address (also called a hash) may be provided to the Gravatar service to see if you are using it. The Gravatar service privacy policy is available here: <a href="https://automattic.com/privacy/" target="_blank" rel="nofollow" class="text-secondary hover:underline">https://automattic.com/privacy/</a>. After approval of your comment, your profile picture is visible to the public in the context of your comment.</p>

      <h3 class="font-display text-xl font-semibold text-secondary mt-8 mb-3">Media</h3>
      <p class="text-base text-text/80 mb-6">If you upload images to the website, you should avoid uploading images with embedded location data (EXIF GPS) included. Visitors to the website can download and extract any location data from images on the website.</p>

      <h3 class="font-display text-xl font-semibold text-secondary mt-8 mb-3">Contact forms</h3>
      <p class="text-base text-text/80 mb-6">If you use our contact form, we collect the information you provide (name, email, subject, message) to respond to your inquiry. This data is not used for marketing purposes and is kept for a limited period for customer service purposes.</p>

      <h3 class="font-display text-xl font-semibold text-secondary mt-8 mb-3">Cookies</h3>
      <p class="text-base text-text/80 mb-4">If you leave a comment on our site, you may opt-in to saving your name, email address, and website in cookies. These are for your convenience so that you do not have to fill in your details again when you leave another comment. These cookies will last for one year.</p>
      <p class="text-base text-text/80 mb-4">If you visit our login page, we will set a temporary cookie to determine if your browser accepts cookies. This cookie contains no personal data and is discarded when you close your browser.</p>
      <p class="text-base text-text/80 mb-6">When you log in, we will also set up several cookies to save your login information and your screen display choices. Login cookies last for two days, and screen options cookies last for a year. If you select "Remember Me", your login will persist for two weeks. If you log out of your account, the login cookies will be removed.</p>

      <h3 class="font-display text-xl font-semibold text-secondary mt-8 mb-3">Embedded content from other websites</h3>
      <p class="text-base text-text/80 mb-4">Articles on this site may include embedded content (e.g. videos, images, articles, etc.). Embedded content from other websites behaves in the exact same way as if the visitor has visited the other website.</p>
      <p class="text-base text-text/80 mb-6">These websites may collect data about you, use cookies, embed additional third-party tracking, and monitor your interaction with that embedded content, including tracking your interaction with the embedded content if you have an account and are logged in to that website.</p>

      <h3 class="font-display text-xl font-semibold text-secondary mt-8 mb-3">Analytics</h3>
      <p class="text-base text-text/80 mb-6">We may use third-party analytics services (e.g., Google Analytics) to track and report website traffic. These services may collect anonymous data about your usage patterns to help us improve our content and user experience.</p>

      <h2 class="font-display text-2xl font-bold text-primary mt-10 mb-4">Who we share your data with</h2>
      <p class="text-base text-text/80 mb-6">We do not share your personal data with any third parties except where required by law, to protect our rights, or to fulfill services you have requested (e.g., payment processors).</p>

      <h2 class="font-display text-2xl font-bold text-primary mt-10 mb-4">How long we retain your data</h2>
      <p class="text-base text-text/80 mb-4">If you leave a comment, the comment and its metadata are retained indefinitely. This is so we can recognize and approve any follow-up comments automatically instead of holding them in a moderation queue.</p>
      <p class="text-base text-text/80 mb-6">For users that register on our website (if any), we also store the personal information they provide in their user profile. All users can see, edit, or delete their personal information at any time (except they cannot change their username). Website administrators can also see and edit that information.</p>

      <h2 class="font-display text-2xl font-bold text-primary mt-10 mb-4">What rights you have over your data</h2>
      <p class="text-base text-text/80 mb-6">If you have an account on this site, or have left comments, you can request to receive an exported file of the personal data we hold about you, including any data you have provided to us. You can also request that we erase any personal data we hold about you. This does not include any data we are obliged to keep for administrative, legal, or security purposes.</p>

      <h2 class="font-display text-2xl font-bold text-primary mt-10 mb-4">Where your data is sent</h2>
      <p class="text-base text-text/80 mb-6">Visitor comments may be checked through an automated spam detection service.</p>

      <p class="mt-8 text-sm text-text/60 text-center">
        Last updated: June 3, 2025
      </p>
    </article>
  </div>
</section>
