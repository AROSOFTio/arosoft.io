<?php
// pages/home.php
?>
<div class="animate-fade-in-up">

  <!-- Hero Section -->
  <div class="relative bg-gradient-to-br from-primary to-secondary text-white pt-20 pb-24 md:pt-32 md:pb-36 px-4 sm:px-6 lg:px-8 overflow-hidden">
    <div class="absolute inset-0 opacity-10">
      <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <pattern id="heroPattern" patternUnits="userSpaceOnUse" width="60" height="60" patternTransform="scale(1) rotate(45)">
            <rect x="0" y="0" width="100%" height="100%" fill="hsla(215, 28%, 17%,0)"/>
            <path
              d="M10-6c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm30 6c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zM10 24c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm30 6c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6z"
              stroke-width="1" stroke="hsla(0, 0%, 100%, 0.1)" fill="none">
            </path>
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#heroPattern)"></rect>
      </svg>
    </div>
    <div class="absolute inset-0 bg-gradient-to-t from-primary/20 via-transparent opacity-50"></div>

    <div class="relative container mx-auto text-center z-10">
      <h1 class="font-display text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold tracking-tight mb-6">
        <span class="block">Digital Innovation.</span>
        <span class="block">Expert <span class="text-accent">Programming</span>.</span>
      </h1>
      <p class="max-w-xl md:max-w-2xl mx-auto text-lg sm:text-xl text-white/90 mb-10">
        Welcome to <?= SITE_NAME; ?>. We craft cutting-edge digital solutions, from dynamic websites to robust software, empowering your business for tomorrow’s challenges.
      </p>
      <div class="flex flex-col sm:flex-row justify-center items-center space-y-4 sm:space-y-0 sm:space-x-6">
        <a href="<?= BASE_URL; ?>index.php?page=services_overview"
           class="inline-flex items-center justify-center px-8 py-3 text-lg font-medium text-white bg-secondary hover:bg-secondary-hover rounded-lg shadow-lg transition-transform hover:scale-105 transform duration-150 ease-out">
          <i data-lucide="zap" class="w-5 h-5 mr-2"></i>Explore Our Services
        </a>
        <a href="<?= BASE_URL; ?>index.php?page=contact"
           class="inline-flex items-center justify-center px-8 py-3 text-lg font-medium text-white bg-primary hover:bg-primary-hover rounded-lg shadow-lg transition-transform hover:scale-105 transform duration-150 ease-out">
          <i data-lucide="mail" class="w-5 h-5 mr-2"></i>Get In Touch
        </a>
      </div>
    </div>
    <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 translate-y-1/3 w-72 h-72 md:w-96 md:h-96 bg-secondary/10 rounded-full filter blur-3xl opacity-60"></div>
  </div>

  <!-- Core Services Section -->
  <section class="py-16 md:py-20 bg-base-200/30">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12 md:mb-16">
        <h2 class="font-display text-3xl sm:text-4xl md:text-5xl font-bold text-text mb-3">Our Core Services</h2>
        <p class="text-lg text-accent max-w-2xl mx-auto">Empowering Your Business with Innovative Technology</p>
        <span class="section-title-underline"></span>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php
        $services = [
          ["title" => "Web Development",     "icon" => "code-2",           "description" => "Bespoke websites and dynamic web applications tailored to your brand and business goals, ensuring a strong online presence.", "page" => "webDev"],
          ["title" => "Software Solutions",  "icon" => "binary",           "description" => "Custom software development to streamline complex operations, enhance productivity, and drive innovation within your organization.", "page" => "software"],
          ["title" => "Cloud & DevOps",      "icon" => "cloud-cog",        "description" => "Scalable cloud infrastructure design, migration, and management, coupled with efficient DevOps practices for rapid deployment.", "page" => "cloud"],
          ["title" => "Cybersecurity",       "icon" => "shield-half",      "description" => "Robust security measures, threat assessments, and data protection strategies to safeguard your valuable digital assets.", "page" => "cybersecurity"],
          ["title" => "IT Consulting",       "icon" => "message-circle-code","description" => "Strategic IT advice and consultation to align technology with your business objectives and overcome technical challenges.", "page" => "support"],
          ["title" => "Digital Marketing",   "icon" => "trending-up",      "description" => "Data-driven digital marketing strategies including SEO, SEM, and content marketing to boost your online visibility and growth.", "page" => "services_overview"]
        ];
        foreach ($services as $idx => $service): ?>
          <div class="bg-base-100 p-8 rounded-xl shadow-xl hover:shadow-accent/20 transition-all duration-300 transform hover:-translate-y-1.5 flex flex-col items-start animate-fade-in-up" style="animation-delay: <?= $idx * 100; ?>ms">
            <div class="p-3.5 mb-5 bg-secondary/10 rounded-full text-secondary ring-2 ring-secondary/30">
              <i data-lucide="<?= $service['icon']; ?>" class="w-8 h-8"></i>
            </div>
            <h3 class="font-display text-2xl font-semibold text-text mb-3"><?= esc_html($service['title']); ?></h3>
            <p class="text-text/70 text-sm mb-6 flex-grow"><?= esc_html($service['description']); ?></p>
            <a href="<?= BASE_URL; ?>index.php?page=<?= $service['page']; ?>"
               class="inline-flex items-center mt-auto px-5 py-2.5 text-sm font-medium text-text bg-base-100 hover:bg-secondary rounded-lg shadow transition-colors self-start group">
              Learn More <i data-lucide="arrow-right" class="w-4 h-4 ml-1.5 group-hover:translate-x-1 transition-transform"></i>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Trusted By Leading Companies -->
  <section class="py-16 md:py-20 bg-base-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12 md:mb-16">
        <h2 class="font-display text-3xl sm:text-4xl font-bold text-text mb-3">Trusted By Leading Companies</h2>
        <p class="text-lg text-accent max-w-2xl mx-auto">We collaborate with industry leaders to deliver exceptional value.</p>
        <span class="section-title-underline"></span>
      </div>
      <div class="mt-12 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-x-6 gap-y-10 items-center">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <div class="flex justify-center items-center p-4 grayscale hover:grayscale-0 transition-all duration-300 opacity-60 hover:opacity-100 transform hover:scale-105">
            <img src="https://placehold.co/200x100/4B5563/F4F4F4?text=Partner+<?= $i; ?>&font=poppins" alt="Partner <?= $i; ?> Logo" class="h-10 md:h-12 object-contain" onError="this.alt='Partner Logo Placeholder'"/>
          </div>
        <?php endfor; ?>
      </div>
    </div>
  </section>

  <!-- Recent Work -->
  <section class="py-16 md:py-20">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12 md:mb-16">
        <h2 class="font-display text-3xl sm:text-4xl font-bold text-text mb-3">Our Recent Work</h2>
        <p class="text-lg text-accent max-w-2xl mx-auto">Delivering impactful solutions that drive results.</p>
        <span class="section-title-underline"></span>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mt-12">
        <?php
        $projects = [
          ["title" => "AI-Powered Analytics Platform",    "category" => "Software Solutions",    "image" => "https://source.unsplash.com/random/600x400/?analytics,dashboard,ai",           "page" => "portfolio"],
          ["title" => "Global E-learning Portal",         "category" => "Web Development",      "image" => "https://source.unsplash.com/random/600x400/?elearning,onlinecourse,platform", "page" => "portfolio"],
          ["title" => "Secure Cloud Infrastructure",      "category" => "Cloud & Cybersecurity","image" => "https://source.unsplash.com/random/600x400/?cloudsecurity,server,network",    "page" => "portfolio"],
        ];
        foreach ($projects as $idx => $project): ?>
          <a href="<?= BASE_URL; ?>index.php?page=<?= $project['page']; ?>"
             class="group relative bg-base-100 rounded-xl shadow-lg overflow-hidden animate-fade-in-up"
             style="animation-delay: <?= $idx * 150; ?>ms">
            <div class="overflow-hidden">
              <img src="<?= $project['image']; ?>" alt="<?= esc_html($project['title']); ?>"
                   class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300"
                   onError="this.src='https://placehold.co/600x400/4B5563/E5E7EB?text=Project+Image'; this.alt='Project Placeholder'" />
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"></div>
            <div class="absolute bottom-0 left-0 p-6">
              <span class="text-xs font-semibold uppercase tracking-wider text-accent"><?= esc_html($project['category']); ?></span>
              <h3 class="font-display text-xl font-semibold text-white mt-1 group-hover:text-accent transition-colors"><?= esc_html($project['title']); ?></h3>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
      <div class="text-center mt-12 md:mt-16">
        <a href="<?= BASE_URL; ?>index.php?page=portfolio"
           class="inline-flex items-center px-6 py-2.5 text-sm font-medium text-text bg-base-100 hover:bg-secondary rounded-lg shadow transition-colors group">
          <i data-lucide="briefcase" class="w-4 h-4 mr-2"></i>View Full Portfolio <i data-lucide="arrow-right" class="w-4 h-4 ml-1.5 group-hover:translate-x-1 transition-transform"></i>
        </a>
      </div>
    </div>
  </section>

  <!-- Testimonials -->
  <section class="py-16 md:py-20 bg-base-200/30">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12 md:mb-16">
        <h2 class="font-display text-3xl sm:text-4xl font-bold text-text mb-3">What Our Clients Say</h2>
        <p class="text-lg text-accent max-w-2xl mx-auto">Real stories from satisfied partners who trust <?= SITE_NAME; ?>.</p>
        <span class="section-title-underline"></span>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mt-12">
        <?php
        $testimonials = [
          ["name" => "Aisha K.", "company" => "Director, Innovate Ltd.", "quote" => "<?= SITE_NAME; ?> delivered beyond our expectations. Their innovative approach and technical prowess are truly commendable.", "avatar" => "https://source.unsplash.com/random/100x100/?woman,business,african"],
          ["name" => "David M.", "company" => "Tech Lead, StartUpX",       "quote" => "The custom software solution has revolutionized our workflow. The <?= SITE_NAME; ?> team is professional and highly skilled.", "avatar" => "https://source.unsplash.com/random/100x100/?man,tech,coder"],
          ["name" => "Fatuma A.", "company" => "Manager, EduSphere",       "quote" => "Our e-learning platform is a massive success, thanks to <?= SITE_NAME; ?>'s web development expertise. Fantastic support!", "avatar" => "https://source.unsplash.com/random/100x100/?person,educator,female"],
        ];
        foreach ($testimonials as $idx => $testimonial): ?>
          <div class="bg-base-200 p-8 rounded-xl shadow-lg flex flex-col animate-fade-in-up" style="animation-delay: <?= $idx * 100; ?>ms">
            <div class="flex items-center mb-4">
              <img src="<?= $testimonial['avatar']; ?>" alt="<?= esc_html($testimonial['name']); ?>"
                   class="w-14 h-14 rounded-full object-cover mr-4 border-2 border-secondary"
                   onError="this.src='https://placehold.co/100x100/4B5563/E5E7EB?text=Client'; this.alt='Client Avatar'" />
              <div>
                <h4 class="text-lg font-semibold text-text"><?= esc_html($testimonial['name']); ?></h4>
                <p class="text-sm text-accent"><?= esc_html($testimonial['company']); ?></p>
              </div>
            </div>
            <div class="flex items-center space-x-0.5 text-highlight mb-3">
              <?php for($i = 0; $i < 5; $i++): ?><i data-lucide="star" class="w-4 h-4 fill-current"></i><?php endfor; ?>
            </div>
            <p class="text-text/80 italic mt-1 text-sm flex-grow">"<?= esc_html($testimonial['quote']); ?>"</p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Who We Are -->
  <section class="py-16 md:py-20">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12 lg:gap-16">
        <div class="md:w-1/2 animate-slide-in-left">
          <img src="https://source.unsplash.com/random/800x600/?office,modern,programmers,uganda" alt="<?= SITE_NAME; ?> Team Collaborating"
               class="rounded-xl shadow-2xl object-cover w-full aspect-[4/3]"
               onError="this.src='https://placehold.co/800x600/4B5563/E5E7EB?text=Our+Team'; this.alt='Team Placeholder'" />
        </div>
        <div class="md:w-1/2 animate-fade-in-up" style="animation-delay: 200ms;">
          <span class="text-sm font-semibold uppercase tracking-wider text-accent mb-2 block">Who We Are</span>
          <h2 class="font-display text-3xl md:text-4xl font-bold text-text mb-5">Your Partner in Digital Innovation & Programming</h2>
          <p class="text-text/80 mb-4 leading-relaxed">
            At <?= SITE_NAME; ?>, we are a dynamic team of creative thinkers, skilled programmers, and digital strategists based in the heart of innovation. We are passionate about leveraging technology to solve complex problems and drive growth for businesses in Uganda and beyond.
          </p>
          <p class="text-text/80 mb-6 leading-relaxed">
            Our commitment is to deliver excellence, foster collaboration, and build long-lasting partnerships through transparent communication and tailored solutions.
          </p>
          <a href="<?= BASE_URL; ?>index.php?page=about"
             class="inline-flex items-center px-6 py-2.5 text-sm font-medium text-text bg-base-100 hover:bg-secondary rounded-lg shadow transition-colors group">
            <i data-lucide="users-2" class="w-4 h-4 mr-2"></i>Learn More About Us <i data-lucide="arrow-right" class="w-4 h-4 ml-1.5 group-hover:translate-x-1 transition-transform"></i>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Latest Insights -->
  <section class="py-16 md:py-20 bg-base-200/30">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12 md:mb-16">
        <h2 class="font-display text-3xl sm:text-4xl font-bold text-text mb-3">Latest Insights</h2>
        <p class="text-lg text-accent max-w-2xl mx-auto">Our thoughts on tech, business, and digital growth.</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mt-12">
        <?php foreach ($recent_posts as $idx => $post): ?>
          <a href="<?= BASE_URL; ?>index.php?page=blog&post=<?= $post['slug']; ?>"
             class="group bg-base-100 rounded-xl shadow-lg overflow-hidden animate-fade-in-up"
             style="animation-delay: <?= $idx * 100; ?>ms">
            <div class="overflow-hidden">
              <img src="<?= $post['image']; ?>" alt="<?= esc_html($post['title']); ?>"
                   class="w-full h-52 object-cover group-hover:scale-105 transition-transform duration-300" />
            </div>
            <div class="p-6">
              <span class="text-xs font-semibold uppercase tracking-wider text-accent"><?= esc_html($post['category']); ?></span>
              <h3 class="font-display text-lg font-semibold text-white mt-1 group-hover:text-accent transition-colors"><?= esc_html($post['title']); ?></h3>
              <p class="mt-2 text-text/80 text-sm"><?= esc_html($post['excerpt']); ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
      <div class="text-center mt-12 md:mt-16">
        <a href="<?= BASE_URL; ?>index.php?page=blog"
           class="inline-flex items-center px-6 py-2.5 text-sm font-medium text-text bg-base-100 hover:bg-secondary rounded-lg shadow transition-colors group">
          <i data-lucide="book-open" class="w-4 h-4 mr-2"></i>Visit Our Blog <i data-lucide="arrow-right" class="w-4 h-4 ml-1.5 group-hover:translate-x-1 transition-transform"></i>
        </a>
      </div>
    </div>
  </section>

  <!-- Final CTA -->
  <section class="bg-gradient-to-r from-secondary via-primary to-accent py-16 md:py-20 text-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <h2 class="font-display text-3xl md:text-4xl font-bold mb-4">Ready to Start Your Digital Transformation?</h2>
      <p class="text-lg md:text-xl text-text-light/90 max-w-2xl mx-auto mb-8">
        Let's discuss your project and how <?= SITE_NAME; ?> can help you achieve your business goals.
        Contact us today for a free, no-obligation consultation.
      </p>
      <a href="<?= BASE_URL; ?>index.php?page=contact"
         class="inline-flex items-center justify-center px-10 py-3.5 text-lg font-medium text-primary bg-base-100 hover:bg-neutral-light rounded-lg shadow-xl transition-transform hover:scale-105 transform duration-150 ease-out group">
        <i data-lucide="phone-call" class="w-5 h-5 mr-2 group-hover:animate-subtle-pulse"></i>Schedule a Consultation
      </a>
    </div>
  </section>

</div>
