<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="QPTEO Systems Portal — Quality Pre-Service Teacher Education Office. Access internal systems, document repositories, issuances, and centers of excellence.">
    <title>QPTEO — Systems Portal</title>
</head>
<body>

    <?php $activeNav = 'home'; include 'includes/navbar.php'; ?>

    <!-- Main Content -->
    <main class="portal-main">

        <!-- Centered Hero Carousel Container -->
        <div class="qpteo-carousel-wrapper">
            <div class="qpteo-carousel-card">
                <div class="qpteo-carousel" id="qpteoHeroCarousel">
                    <div class="qpteo-carousel-inner" id="qpteoCarouselInner">
                        <div class="qpteo-carousel-slide">
                            <img src="imgs/COE-Onboarding.jpg" alt="COE Onboarding 2026">
                            <div class="qpteo-slide-caption">
                                <h3 class="qpteo-slide-title">COE Onboarding 2026</h3>
                                <p class="qpteo-slide-desc">Orientation & capacity building for Centers of Excellence</p>
                            </div>
                        </div>
                        <div class="qpteo-carousel-slide">
                            <img src="imgs/JETMC-1.jpg" alt="JETMC Event">
                            <div class="qpteo-slide-caption">
                                <h3 class="qpteo-slide-title">Joint Education & Training Mission</h3>
                                <p class="qpteo-slide-desc">Strengthening teacher education programs and institutional linkages</p>
                            </div>
                        </div>
                    </div>

                    <!-- Controls -->
                    <button class="qpteo-carousel-btn prev" id="carouselPrevBtn" aria-label="Previous Slide">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" width="20" height="20">
                            <path d="M15 18l-6-6 6-6"/>
                        </svg>
                    </button>
                    <button class="qpteo-carousel-btn next" id="carouselNextBtn" aria-label="Next Slide">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" width="20" height="20">
                            <path d="M9 18l6-6-6-6"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Dots Indicators Below Carousel Frame -->
            <div class="qpteo-carousel-indicators" id="carouselIndicators">
                <span class="qpteo-carousel-dot active" data-index="0"></span>
                <span class="qpteo-carousel-dot" data-index="1"></span>
            </div>
        </div>

        <!-- Mobile Quick Actions Row -->
        <?php include 'includes/mobile/quick_actions.php'; ?>

        <!-- Mobile Recent Content Feed -->
        <?php include 'includes/mobile/recent_feed.php'; ?>

    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const inner = document.getElementById('qpteoCarouselInner');
        const slides = document.querySelectorAll('.qpteo-carousel-slide');
        const dots = document.querySelectorAll('.qpteo-carousel-dot');
        const prevBtn = document.getElementById('carouselPrevBtn');
        const nextBtn = document.getElementById('carouselNextBtn');
        let currentIndex = 0;
        let autoplayTimer = null;

        function updateSlide(index) {
            if (index < 0) index = slides.length - 1;
            if (index >= slides.length) index = 0;
            currentIndex = index;
            inner.style.transform = `translateX(-${currentIndex * 100}%)`;
            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === currentIndex);
            });
        }

        function startAutoplay() {
            stopAutoplay();
            autoplayTimer = setInterval(() => {
                updateSlide(currentIndex + 1);
            }, 5000);
        }

        function stopAutoplay() {
            if (autoplayTimer) clearInterval(autoplayTimer);
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                updateSlide(currentIndex - 1);
                startAutoplay();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                updateSlide(currentIndex + 1);
                startAutoplay();
            });
        }

        dots.forEach(dot => {
            dot.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-index'), 10);
                updateSlide(idx);
                startAutoplay();
            });
        });

        // Touch Swipe Support for Mobile
        let touchStartX = 0;
        let touchEndX = 0;
        const carouselEl = document.getElementById('qpteoHeroCarousel');

        if (carouselEl) {
            carouselEl.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
                stopAutoplay();
            }, { passive: true });

            carouselEl.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
                startAutoplay();
            }, { passive: true });
        }

        function handleSwipe() {
            const diff = touchEndX - touchStartX;
            if (Math.abs(diff) > 40) {
                if (diff < 0) {
                    updateSlide(currentIndex + 1); // Swipe left -> Next
                } else {
                    updateSlide(currentIndex - 1); // Swipe right -> Prev
                }
            }
        }

        startAutoplay();
    });
    </script>

</body>
</html>
