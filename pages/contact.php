<?php
// pages/contact.php
global $site_settings;
$contact_email_address = CONTACT_EMAIL; // From index.php

// Retrieve previous form data/errors/success from session
$form_data = $_SESSION['contact_form_data'] ?? [];
$form_errors = $_SESSION['contact_form_errors'] ?? [];
$form_success_message = $_SESSION['contact_form_success'] ?? null;

unset($_SESSION['contact_form_data'], $_SESSION['contact_form_errors'], $_SESSION['contact_form_success']);
?>
<section class="bg-background py-16 md:py-20 animate-fade-in-up">
  <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-4xl">
    <header class="text-center mb-12 md:mb-16">
      <h1 class="font-display text-4xl sm:text-5xl font-bold text-text mb-3">Get In Touch</h1>
      <p class="text-lg text-accent max-w-xl mx-auto">
        We’d love to hear from you! Choose your inquiry type below.
      </p>
      <span class="section-title-underline"></span>
    </header>

    <div class="grid md:grid-cols-2 gap-8 md:gap-12 items-start">
      <!-- Contact Form Card -->
      <div class="bg-base-100 p-6 sm:p-8 rounded-xl shadow-xl">
        <h3 class="font-display text-2xl font-semibold text-secondary mb-6">Send Us a Message</h3>
        <form action="<?= BASE_URL; ?>actions/contact_process.php" method="POST" enctype="multipart/form-data" class="space-y-5" id="contactForm">
          <?= generate_csrf_input(); ?>

          <!-- Inquiry Type -->
          <div>
            <label class="block text-sm font-medium text-text mb-1">Inquiry Type <span class="text-error">*</span></label>
            <div class="flex items-center space-x-6">
              <label class="inline-flex items-center">
                <input
                  type="radio"
                  name="inquiry_type"
                  value="general"
                  required
                  <?= (isset($form_data['inquiry_type']) && $form_data['inquiry_type'] === 'general') ? 'checked' : '' ?>
                  class="form-radio text-secondary focus:ring-secondary"
                />
                <span class="ml-2 text-text">General Inquiry</span>
              </label>
              <label class="inline-flex items-center">
                <input
                  type="radio"
                  name="inquiry_type"
                  value="professional"
                  <?= (isset($form_data['inquiry_type']) && $form_data['inquiry_type'] === 'professional') ? 'checked' : '' ?>
                  class="form-radio text-secondary focus:ring-secondary"
                />
                <span class="ml-2 text-text">Professional Service</span>
              </label>
            </div>
          </div>

          <!-- General Fields -->
          <div id="generalFields" class="<?= (isset($form_data['inquiry_type']) && $form_data['inquiry_type'] === 'professional') ? 'hidden' : '' ?>">
            <!-- Full Name -->
            <div>
              <label for="contact_name" class="block text-sm font-medium text-text mb-1">
                Full Name <span class="text-error">*</span>
              </label>
              <input
                type="text"
                name="contact_name"
                id="contact_name"
                required
                value="<?= esc_html($form_data['contact_name'] ?? ''); ?>"
                placeholder="John Doe"
                class="w-full px-4 py-3 bg-base-200 border border-neutral-light rounded-md text-text focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
              />
            </div>

            <!-- Email Address -->
            <div>
              <label for="contact_email" class="block text-sm font-medium text-text mb-1">
                Email Address <span class="text-error">*</span>
              </label>
              <input
                type="email"
                name="contact_email"
                id="contact_email"
                required
                value="<?= esc_html($form_data['contact_email'] ?? ''); ?>"
                placeholder="you@example.com"
                class="w-full px-4 py-3 bg-base-200 border border-neutral-light rounded-md text-text focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
              />
            </div>

            <!-- Subject -->
            <div>
              <label for="contact_subject" class="block text-sm font-medium text-text mb-1">
                Subject
              </label>
              <input
                type="text"
                name="contact_subject"
                id="contact_subject"
                value="<?= esc_html($form_data['contact_subject'] ?? ''); ?>"
                placeholder="e.g., Project Inquiry"
                class="w-full px-4 py-3 bg-base-200 border border-neutral-light rounded-md text-text focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
              />
            </div>

            <!-- Message -->
            <div>
              <label for="contact_message" class="block text-sm font-medium text-text mb-1">
                Message <span class="text-error">*</span>
              </label>
              <textarea
                name="contact_message"
                id="contact_message"
                rows="5"
                required
                placeholder="Your message here..."
                class="w-full px-4 py-3 bg-base-200 border border-neutral-light rounded-md text-text focus:ring-2 focus:ring-primary focus:border-primary transition-colors resize-y"
              ><?= esc_html($form_data['contact_message'] ?? ''); ?></textarea>
            </div>
          </div>

          <!-- Professional Service Fields -->
          <div id="professionalFields" class="<?= (isset($form_data['inquiry_type']) && $form_data['inquiry_type'] === 'professional') ? '' : 'hidden' ?>">
            <p class="text-text/80 mb-4">
              Please note: professional services may incur a fee. Provide details below.
            </p>

            <!-- Service Selection -->
            <div>
              <label for="service_select" class="block text-sm font-medium text-text mb-1">
                Select Service <span class="text-error">*</span>
              </label>
              <select
                name="service_select"
                id="service_select"
                required
                class="w-full px-4 py-3 bg-base-200 border border-neutral-light rounded-md text-text focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
              >
                <option value="">-- Choose a Service --</option>
                <option value="Web Development" <?= (isset($form_data['service_select']) && $form_data['service_select'] === 'Web Development') ? 'selected' : '' ?>>Web Development</option>
                <option value="Software Solutions" <?= (isset($form_data['service_select']) && $form_data['service_select'] === 'Software Solutions') ? 'selected' : '' ?>>Software Solutions</option>
                <option value="Cloud & DevOps" <?= (isset($form_data['service_select']) && $form_data['service_select'] === 'Cloud & DevOps') ? 'selected' : '' ?>>Cloud & DevOps</option>
                <option value="Cybersecurity" <?= (isset($form_data['service_select']) && $form_data['service_select'] === 'Cybersecurity') ? 'selected' : '' ?>>Cybersecurity</option>
                <option value="IT Consulting" <?= (isset($form_data['service_select']) && $form_data['service_select'] === 'IT Consulting') ? 'selected' : '' ?>>IT Consulting</option>
                <option value="Digital Marketing" <?= (isset($form_data['service_select']) && $form_data['service_select'] === 'Digital Marketing') ? 'selected' : '' ?>>Digital Marketing</option>
              </select>
            </div>

            <!-- File Upload -->
            <div>
              <label for="attachment" class="block text-sm font-medium text-text mb-1">
                Attach File (PDF, DOCX, JPEG, PNG; max 2MB)
              </label>
              <input
                type="file"
                name="attachment"
                id="attachment"
                accept=".pdf,.doc,.docx,.jpeg,.jpg,.png"
                class="w-full text-text"
              />
            </div>

            <!-- Detailed Message -->
            <div>
              <label for="professional_message" class="block text-sm font-medium text-text mb-1">
                Detailed Requirements <span class="text-error">*</span>
              </label>
              <textarea
                name="professional_message"
                id="professional_message"
                rows="5"
                required
                placeholder="Describe your project requirements..."
                class="w-full px-4 py-3 bg-base-200 border border-neutral-light rounded-md text-text focus:ring-2 focus:ring-primary focus:border-primary transition-colors resize-y"
              ><?= esc_html($form_data['professional_message'] ?? ''); ?></textarea>
            </div>
          </div>

          <!-- Submit Button -->
          <div>
            <button
              type="submit"
              name="submit_contact_form"
              class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3 bg-secondary hover:bg-secondary-hover text-white font-medium rounded-lg shadow-lg transition-transform hover:scale-105 transform duration-150 ease-out"
            >
              <i data-lucide="send" class="w-5 h-5 mr-2"></i>Send Message
            </button>
            <?php if ($form_success_message): ?>
              <p class="mt-4 text-success font-medium"><?= esc_html($form_success_message); ?></p>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <!-- Contact Details Card -->
      <div class="bg-base-100 p-6 sm:p-8 rounded-xl shadow-xl space-y-6 mt-8 md:mt-0">
        <h3 class="font-display text-2xl font-semibold text-secondary mb-4">Our Contact Details</h3>

        <div class="flex items-start space-x-4">
          <i data-lucide="phone-call" class="w-6 h-6 text-secondary mt-1 shrink-0"></i>
          <div class="text-text">
            <span class="font-semibold block">Phone:</span>
            +256 7XX XXX XXX <span class="text-xs text-text/60">(Mon-Fri, 9am-5pm EAT)</span>
          </div>
        </div>

        <div class="flex items-start space-x-4">
          <i data-lucide="mail" class="w-6 h-6 text-secondary mt-1 shrink-0"></i>
          <div class="text-text">
            <span class="font-semibold block">Email:</span>
            <a href="mailto:<?= esc_html($contact_email_address); ?>" class="hover:text-secondary transition-colors">
              <?= esc_html($contact_email_address); ?>
            </a>
          </div>
        </div>

        <div class="flex items-start space-x-4">
          <i data-lucide="map-pin" class="w-6 h-6 text-secondary mt-1 shrink-0"></i>
          <div class="text-text">
            <span class="font-semibold block">Address:</span>
            Innovation Village, Ntinda, Kampala, Uganda<br>
            <span class="text-xs text-text/60">(Appointments preferred)</span>
          </div>
        </div>

        <div class="h-56 bg-base-200 rounded-md flex items-center justify-center overflow-hidden shadow-inner mt-6">
          <img
            src="https://source.unsplash.com/random/800x400/?kampala,map,office"
            alt="Map Location Placeholder"
            class="w-full h-full object-cover"
            onError="this.style.display='none'; this.parentElement.innerHTML='Map loading...';"
          />
        </div>
      </div>
    </div>
  </div>
</section>

<script>
  // Toggle visibility of fields based on inquiry type
  document.addEventListener('DOMContentLoaded', function() {
    const generalFields = document.getElementById('generalFields');
    const professionalFields = document.getElementById('professionalFields');
    const inquiryRadios = document.querySelectorAll('input[name="inquiry_type"]');

    function toggleFields() {
      const selected = document.querySelector('input[name="inquiry_type"]:checked').value;
      if (selected === 'professional') {
        generalFields.classList.add('hidden');
        professionalFields.classList.remove('hidden');
      } else {
        professionalFields.classList.add('hidden');
        generalFields.classList.remove('hidden');
      }
    }

    inquiryRadios.forEach(radio => {
      radio.addEventListener('change', toggleFields);
    });

    // Initialize on page load
    if (document.querySelector('input[name="inquiry_type"]:checked')) {
      toggleFields();
    }
  });
</script>
