<?php
// includes/footer.php
?>
    </div>
    <footer class="bg-base-200 border-t border-neutral-light mt-auto py-12 md:py-16 text-neutral-content/70 print:hidden">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <i data-lucide="cpu" class="w-7 h-7 text-secondary"></i>
                        <span class="font-display text-xl font-semibold text-secondary"><?= SITE_NAME; ?></span>
                    </div>
                    <p class="text-neutral-content text-sm mb-4"><?= SITE_TAGLINE; ?>. We provide cutting-edge IT services to help your business thrive.</p>
                    <div class="flex space-x-3 mt-4">
                        <a href="#" aria-label="Facebook" class="text-neutral-content/50 hover:text-secondary transition-colors"><i data-lucide="facebook" class="w-5 h-5"></i></a>
                        <a href="#" aria-label="Twitter" class="text-neutral-content/50 hover:text-secondary transition-colors"><i data-lucide="twitter" class="w-5 h-5"></i></a>
                        <a href="#" aria-label="LinkedIn" class="text-neutral-content/50 hover:text-secondary transition-colors"><i data-lucide="linkedin" class="w-5 h-5"></i></a>
                        <a href="#" aria-label="Instagram" class="text-neutral-content/50 hover:text-secondary transition-colors"><i data-lucide="instagram" class="w-5 h-5"></i></a>
                    </div>
                </div>
                <div>
                    <h5 class="text-base font-semibold text-text mb-4">Quick Links</h5>
                    <ul class="space-y-2 text-sm text-neutral-content">
                        <li><a href="<?= rtrim(BASE_URL, '/'); ?>/" class="hover:text-secondary transition-colors">Home</a></li>
                        <li><a href="<?= BASE_URL; ?>about" class="hover:text-secondary transition-colors">About Us</a></li>
                        <li><a href="<?= BASE_URL; ?>services_overview" class="hover:text-secondary transition-colors">Services</a></li>
                        <li><a href="<?= BASE_URL; ?>blog" class="hover:text-secondary transition-colors">Blog</a></li>
                        <li><a href="<?= BASE_URL; ?>contact" class="hover:text-secondary transition-colors">Contact Us</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="text-base font-semibold text-text mb-4">Our Services</h5>
                    <ul class="space-y-2 text-sm text-neutral-content">
                        <li><a href="<?= BASE_URL; ?>webDev" class="hover:text-secondary transition-colors">Web Development</a></li>
                        <li><a href="<?= BASE_URL; ?>software" class="hover:text-secondary transition-colors">Software Solutions</a></li>
                        <li><a href="<?= BASE_URL; ?>cloud" class="hover:text-secondary transition-colors">Cloud &amp; DevOps</a></li>
                        <li><a href="<?= BASE_URL; ?>cybersecurity" class="hover:text-secondary transition-colors">Cybersecurity</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="text-base font-semibold text-text mb-4">Legal</h5>
                    <ul class="space-y-2 text-sm text-neutral-content">
                        <li><a href="<?= BASE_URL; ?>privacy" class="hover:text-secondary transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-secondary transition-colors">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-neutral-light pt-8 text-center text-sm text-neutral-content/60">
                <p><?= str_replace('{year}', date("Y"), FOOTER_COPYRIGHT); ?></p>
                <p class="mt-1 text-xs">A Project by <?= SITE_NAME; ?>.</p>
            </div>
        </div>
    </footer>

    <script>
        // Initialize Lucide Icons after DOM is ready
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        } else {
            window.addEventListener('load', () => {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                } else {
                    console.warn("Lucide script still not loaded after window load.");
                }
            });
        }

        // Mobile Menu Toggle
        const mobileMenuButton    = document.getElementById('mobileMenuButton');
        const mobileMenu          = document.getElementById('mobileMenu');
        const mobileMenuIconOpen  = document.getElementById('mobileMenuIconOpen');
        const mobileMenuIconClose = document.getElementById('mobileMenuIconClose');

        if (mobileMenuButton && mobileMenu && mobileMenuIconOpen && mobileMenuIconClose) {
            mobileMenuButton.addEventListener('click', () => {
                const isMenuOpen = !mobileMenu.classList.contains('hidden');
                
                if (isMenuOpen) {
                    mobileMenu.style.maxHeight = '0';
                    setTimeout(() => {
                        if (mobileMenu.style.maxHeight === '0px') {
                            mobileMenu.classList.add('hidden');
                        }
                    }, 500);
                } else {
                    mobileMenu.classList.remove('hidden');
                    mobileMenu.style.maxHeight = mobileMenu.scrollHeight + "px";
                }
                
                mobileMenuIconOpen.classList.toggle('hidden', !isMenuOpen);
                mobileMenuIconClose.classList.toggle('hidden', isMenuOpen);
                mobileMenuButton.setAttribute('aria-expanded', isMenuOpen ? 'false' : 'true');
            });
        } else {
            console.warn("Mobile menu elements not found for JS toggle. Check IDs: mobileMenuButton, mobileMenu, mobileMenuIconOpen, mobileMenuIconClose");
        }

        // Smooth scroll for # links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                if (this.getAttribute('href').length > 1 && this.hash !== "") {
                    e.preventDefault();
                    const targetElement = document.querySelector(this.hash);
                    if (targetElement) {
                        targetElement.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
        });
    </script>
    <script src="<?= BASE_URL; ?>js/script.js"></script>
</body>
</html>
