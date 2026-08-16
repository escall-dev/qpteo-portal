<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quality Pre-Service Teacher Education Office - Welcome</title>
    <!-- Google Fonts: Outfit for modern, clean typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        /* CSS Reset & Variables */
        :root {
            --primary-color: #00138d;
            /* Logo deep navy blue */
            --accent-color: #e5a93b;
            /* Logo gold/yellow */
            --text-dark: #070d30;
            /* Soft dark for readable typography */
            --bg-color-start: #ffffff;
            --bg-color-end: #f4f6fc;
            /* Subtle cool grey/blue background */
            --transition-speed: 0.3s;
            --font-sans: 'Outfit', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-sans);
            min-height: 100vh;
            background: radial-gradient(circle at center, var(--bg-color-start) 0%, var(--bg-color-end) 100%);
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            overflow-x: hidden;
            line-height: 1.5;
        }

        /* Page entrance animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulseScale {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        /* Main Layout */
        .welcome-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 2rem;
            max-width: 1100px;
            width: 100%;
        }

        /* Logo Styling */
        .logo-wrapper {
            margin-bottom: -3.5rem;
            animation: fadeInUp 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .logo-link {
            display: inline-block;
            text-decoration: none;
            outline: none;
        }

        .logo {
            max-width: 920px;
            height: auto;
            display: block;
            user-select: none;
            -webkit-user-drag: none;
            transition: transform var(--transition-speed) ease;
        }

        .logo:hover {
            transform: scale(1.02);
        }

        /* Welcome Title */
        .welcome-title {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: 0.15em;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            opacity: 0;
            animation: fadeInUp 1s cubic-bezier(0.16, 1, 0.3, 1) 0.2s forwards;
        }

        /* Enter Button (Chevrons) */
        .enter-button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 64px;
            height: 64px;
            border-radius: 50%;
            color: var(--primary-color);
            background: transparent;
            text-decoration: none;
            border: 2px solid transparent;
            transition: all var(--transition-speed) cubic-bezier(0.16, 1, 0.3, 1);
            opacity: 0;
            animation: fadeInUp 1s cubic-bezier(0.16, 1, 0.3, 1) 0.4s forwards;
            cursor: pointer;
            position: relative;
            outline: none;
        }

        /* SVG icon styling and micro-animation on hover */
        .chevron-svg {
            width: 32px;
            height: 32px;
            transition: transform var(--transition-speed) cubic-bezier(0.16, 1, 0.3, 1);
        }

        .enter-button:hover {
            color: #ffffff;
            background: var(--primary-color);
            box-shadow: 0 10px 25px rgba(0, 19, 141, 0.25);
            transform: scale(1.1);
        }

        .enter-button:hover .chevron-svg {
            transform: translateX(3px);
        }

        .enter-button:active {
            transform: scale(0.95);
            background: #000c5c;
            box-shadow: 0 5px 15px rgba(0, 19, 141, 0.2);
        }

        /* Footer Styling */
        .footer {
            padding: 1.5rem 1rem;
            width: 100%;
            text-align: center;
            font-size: 1.2rem;
            font-weight: 400;
            color: #7a829e;
            letter-spacing: 0.02em;
            opacity: 0;
            animation: fadeInUp 1.2s cubic-bezier(0.16, 1, 0.3, 1) 0.6s forwards;
        }

        /* Responsive Design adjustments */
        @media (max-width: 480px) {
            .logo {
                max-width: 500px;
            }

            .logo-wrapper {
                margin-bottom: -2rem;
            }

            .welcome-title {
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }

            .enter-button {
                width: 56px;
                height: 56px;
            }

            .chevron-svg {
                width: 28px;
                height: 28px;
            }

            .footer {
                font-size: 0.75rem;
            }
        }

        /* Native View Transition & Moving Animation Styles */
        @view-transition {
            navigation: auto;
        }

        .logo-wrapper, .logo {
            view-transition-name: qpteo-brand-logo;
        }

        .footer {
            transition: opacity 0.4s ease;
        }

        body.page-exit .footer {
            opacity: 0;
        }

        .logo-wrapper {
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            z-index: 100;
            will-change: transform;
        }

        .logo-wrapper.logo-gliding {
            transform: translateY(-24vh) scale(0.413);
            opacity: 1 !important;
        }
    </style>
</head>

<body>
    <main class="welcome-container">
        <!-- Logo Section -->
        <div class="logo-wrapper">
            <a href="home.php" class="logo-link" aria-label="Go to homepage">
                <img src="branding/qpteo logo unfinalized-jukebox-bg-removed.png" alt="QPTEO Logo" class="logo">
            </a>
        </div>

        <!-- Welcome Text (Commented out for a while)
        <h1 class="welcome-title">WELCOME</h1>
        -->

        <!-- Interactive Double Arrow Button (Commented out for a while)
        <a href="#" class="enter-button" aria-label="Enter site">
            <svg class="chevron-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M6 18L12 12L6 6" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                    stroke-linejoin="round" />
                <path d="M13 18L19 12L13 6" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </a>
        -->
    </main>

    <!-- Footer Credits -->
    <footer class="footer">
        <p>&copy; 2026 Teacher Education Council, Quality Pre-Service Teacher Education Office.</p>
    </footer>

    <!-- Smooth Logo Glide & View Transition Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const logoLink = document.querySelector('.logo-link');
            if (logoLink) {
                logoLink.addEventListener('click', function (e) {
                    const href = this.getAttribute('href');
                    if (!href || href === '#') return;
                    e.preventDefault();

                    const logoWrapper = document.querySelector('.logo-wrapper');
                    if (logoWrapper) {
                        logoWrapper.classList.add('logo-gliding');
                    }
                    document.body.classList.add('page-exit');

                    if (document.startViewTransition) {
                        fetch(href)
                            .then(res => res.text())
                            .then(html => {
                                document.startViewTransition(() => {
                                    document.open();
                                    document.write(html);
                                    document.close();
                                    history.pushState(null, '', href);
                                });
                            })
                            .catch(() => {
                                setTimeout(() => {
                                    window.location.href = href;
                                }, 550);
                            });
                    } else {
                        setTimeout(function () {
                            window.location.href = href;
                        }, 550);
                    }
                });
            }
        });
    </script>
</body>

</html>