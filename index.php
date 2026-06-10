<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sigma ByteCraft Past Papers Portal - Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #1976D2;
            --primary-dark: #1565C0;
            --secondary: #26A69A;
            --accent: #FF6B6B;
            --text: #1C1B1F;
            --text-light: #666;
            --surface: #FFFFFF;
            --background: #F8F9FA;
        }

        body {
            font-family: 'Roboto', sans-serif;
            color: var(--text);
            background: var(--background);
        }

        /* Navigation */
        nav {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            padding: 20px 0;
            transition: all 0.3s ease;
        }

        nav.scrolled {
            padding: 15px 0;
            box-shadow: 0 2px 30px rgba(0, 0, 0, 0.15);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }

        .logo .material-icons {
            font-size: 32px;
        }

        .nav-links {
            display: flex;
            gap: 40px;
            align-items: center;
        }

        .nav-links a {
            color: var(--text);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .btn {
            padding: 12px 28px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            font-size: 15px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(25, 118, 210, 0.3);
        }

        .btn-outline {
            border: 2px solid var(--primary);
            color: var(--primary);
            background: transparent;
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
            padding-top: 80px;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -250px;
            right: -250px;
            animation: float 6s ease-in-out infinite;
        }

        .hero::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            bottom: -200px;
            left: -200px;
            animation: float 8s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-30px); }
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-content h1 {
            font-size: 56px;
            font-weight: 900;
            color: white;
            margin-bottom: 20px;
            line-height: 1.2;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .hero-content .highlight {
            color: #FFD700;
        }

        .hero-content p {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .hero-buttons .btn {
            font-size: 16px;
            padding: 15px 35px;
        }

        .btn-white {
            background: white;
            color: var(--primary);
        }

        .btn-white:hover {
            background: var(--background);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .hero-image {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .hero-image-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            transform: rotate(-5deg);
            transition: transform 0.3s ease;
        }

        .hero-image-card:hover {
            transform: rotate(0deg);
        }

        .hero-image .material-icons {
            font-size: 200px;
            color: var(--primary);
        }

        /* Stats Section */
        .stats {
            padding: 80px 20px;
            background: white;
        }

        .stats-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px;
        }

        .stat-card {
            text-align: center;
            padding: 30px;
            border-radius: 16px;
            background: var(--background);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 48px;
            font-weight: 900;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 16px;
            color: var(--text-light);
            font-weight: 500;
        }

        /* Features Section */
        .features {
            padding: 100px 20px;
            background: var(--background);
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-header h2 {
            font-size: 42px;
            font-weight: 900;
            color: var(--text);
            margin-bottom: 15px;
        }

        .section-header p {
            font-size: 18px;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
        }

        .feature-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .feature-icon .material-icons {
            font-size: 36px;
            color: white;
        }

        .feature-card h3 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--text);
        }

        .feature-card p {
            color: var(--text-light);
            line-height: 1.6;
        }

        /* How It Works */
        .how-it-works {
            padding: 100px 20px;
            background: white;
        }

        .steps {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            position: relative;
        }

        .step {
            text-align: center;
            padding: 30px;
            position: relative;
        }

        .step-number {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            font-weight: 900;
            color: white;
            box-shadow: 0 10px 30px rgba(25, 118, 210, 0.3);
        }

        .step h3 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .step p {
            color: var(--text-light);
            line-height: 1.6;
        }

        /* CTA Section */
        .cta {
            padding: 100px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -300px;
            right: -300px;
        }

        .cta-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
        }

        .cta h2 {
            font-size: 48px;
            font-weight: 900;
            color: white;
            margin-bottom: 20px;
        }

        .cta p {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 40px;
        }

        /* Footer */
        footer {
            background: #1a1a1a;
            color: white;
            padding: 60px 20px 20px;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.2fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-logo {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-about {
            color: #999;
            line-height: 1.6;
        }

        .footer-section h4 {
            font-size: 18px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: #999;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid #333;
            color: #666;
        }

        /* Mobile Menu */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            border-radius: 8px;
        }

        .mobile-menu-btn .material-icons {
            font-size: 32px;
            color: var(--text);
        }

        .contact-link {
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 20px;
                right: 20px;
                background: white;
                border-radius: 12px;
                padding: 16px;
                box-shadow: 0 12px 32px rgba(0, 0, 0, 0.18);
                flex-direction: column;
                align-items: stretch;
                gap: 4px;
            }

            .nav-links.mobile-open {
                display: flex;
            }

            .nav-links a {
                padding: 12px;
            }

            .nav-links a::after {
                display: none;
            }

            .nav-links .btn {
                justify-content: center;
                margin-top: 8px;
            }

            .mobile-menu-btn {
                display: block;
            }

            .hero-container {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-content h1 {
                font-size: 40px;
            }

            .hero-image {
                display: none;
            }

            .stats-container,
            .features-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 24px;
            }

            .steps {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 20px;
            }

            .footer-container {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 640px) {
            nav {
                padding: 14px 0;
            }

            .logo {
                font-size: 20px;
            }

            .logo .material-icons {
                font-size: 28px;
            }

            .hero {
                min-height: auto;
                padding: 110px 0 56px;
            }

            .hero-content h1 {
                font-size: 32px;
            }

            .hero-content p {
                font-size: 16px;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .hero-buttons .btn,
            .cta .btn {
                width: 100%;
                justify-content: center;
            }

            .stats,
            .features,
            .how-it-works,
            .cta {
                padding: 56px 20px;
            }

            .stats-container,
            .features-grid,
            .steps,
            .footer-container {
                grid-template-columns: 1fr;
            }

            .stat-card,
            .feature-card,
            .step {
                padding: 24px;
            }

            .section-header {
                margin-bottom: 36px;
            }

            .section-header h2 {
                font-size: 32px;
            }

            .cta h2 {
                font-size: 32px;
            }

            .cta p {
                font-size: 16px;
            }

            footer {
                padding: 48px 20px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav id="navbar">
        <div class="nav-container">
            <a href="#home" class="logo">
                <span class="material-icons">school</span>
                <span>Bahria Papers</span>
            </a>
            <div class="nav-links" id="navLinks">
                <a href="#features">Features</a>
                <a href="#how-it-works">How It Works</a>
                <a href="#about">About</a>
                <a href="login/#login" class="btn btn-primary">
                    <span class="material-icons">login</span>
                    Login
                </a>
            </div>
            <button class="mobile-menu-btn" type="button" aria-label="Toggle navigation menu" aria-controls="navLinks" aria-expanded="false">
                <span class="material-icons">menu</span>
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-container">
            <div class="hero-content">
                <h1>Access <span class="highlight">Past Papers</span> Anytime, Anywhere</h1>
                <p>The complete repository of Bahria University past examination papers. Upload, share, and download papers with ease.</p>
                <div class="hero-buttons">
                    <a href="login/#signup" class="btn btn-white">
                        <span class="material-icons">login</span>
                        Get Started
                    </a>
                    <a href="#features" class="btn btn-outline" style="border-color: white; color: white;">
                        Learn More
                    </a>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-image-card">
                    <span class="material-icons">description</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number">1000+</div>
                <div class="stat-label">Papers Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">500+</div>
                <div class="stat-label">Active Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">50+</div>
                <div class="stat-label">Courses Covered</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">3</div>
                <div class="stat-label">Campuses</div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="section-header">
            <h2>Why Choose Our Portal?</h2>
            <p>Everything you need to access and share past papers efficiently</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <span class="material-icons">search</span>
                </div>
                <h3>Smart Search</h3>
                <p>Find papers quickly with advanced filters by campus, department, teacher, course, and semester.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <span class="material-icons">visibility</span>
                </div>
                <h3>Preview Papers</h3>
                <p>View papers directly in your browser before downloading. Support for PDFs and images.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <span class="material-icons">cloud_upload</span>
                </div>
                <h3>Easy Upload</h3>
                <p>Contribute to the community by uploading papers with simple drag-and-drop functionality.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <span class="material-icons">security</span>
                </div>
                <h3>Secure Access</h3>
                <p>Email verification and secure authentication ensure only verified students can access.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <span class="material-icons">devices</span>
                </div>
                <h3>Responsive Design</h3>
                <p>Access from any device - desktop, tablet, or mobile. Optimized for all screen sizes.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <span class="material-icons">trending_up</span>
                </div>
                <h3>Track Activity</h3>
                <p>See most viewed and downloaded papers. Track your contributions and downloads.</p>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works" id="how-it-works">
        <div class="section-header">
            <h2>How It Works</h2>
            <p>Get started in just three simple steps</p>
        </div>
        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <h3>Sign Up</h3>
                <p>Register with your Bahria University enrollment number. We'll send a verification link to your university email.</p>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <h3>Browse & Search</h3>
                <p>Use our smart filters to find papers by campus, department, course, teacher, or semester.</p>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <h3>View & Download</h3>
                <p>Preview papers in your browser or download them instantly. Upload your own papers to help others!</p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="cta-content">
            <h2>Ready to Get Started?</h2>
            <p>Join hundreds of students already using Bahria Papers Portal to ace their exams</p>
            <a href="login/#signup" class="btn btn-white" style="font-size: 18px; padding: 18px 40px;">
                <span class="material-icons">login</span>
                Sign Up Now - It's Free!
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer id="about">
        <div class="footer-container">
            <div class="footer-col">
                <div class="footer-logo">
                    <span class="material-icons">school</span>
                    <span>Bahria Papers Portal</span>
                </div>
                <p class="footer-about">
                    Your trusted platform for accessing Bahria University past examination papers. Built by students, for students.
                </p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="login/#login">Login</a></li>
                    <li><a href="login/#signup">Sign Up</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Resources</h4>
                <ul class="footer-links">
                    <li><a href="#how-it-works">FAQ</a></li>
                    <li><a href="#features">Guidelines</a></li>
                    <li><a href="#about">Support</a></li>
                    <li><a href="#about">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Contact Us</h4>
                <ul class="footer-links">
                    <li><a href="mailto:support@example.com" class="contact-link">
                        <span class="material-icons" style="font-size: 18px;">email</span>
                        support@example.com
                    </a></li>
                    <li><a href="#about" class="contact-link">
                        <span class="material-icons" style="font-size: 18px;">phone</span>
                        Add your support contact
                    </a></li>
                    <li style="color: #999; margin-top: 10px; font-size: 14px;">
                        <span class="material-icons" style="font-size: 18px; vertical-align: middle;">schedule</span>
                        Available 24/7
                    </li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 Bahria University Past Papers Portal By ByteCraftSoft. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                if (this.getAttribute('href') === '#') {
                    return;
                }
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        const mobileMenuButton = document.querySelector('.mobile-menu-btn');
        const navLinks = document.getElementById('navLinks');

        mobileMenuButton.addEventListener('click', function () {
            const isOpen = navLinks.classList.toggle('mobile-open');
            mobileMenuButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            mobileMenuButton.querySelector('.material-icons').textContent = isOpen ? 'close' : 'menu';
        });

        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function () {
                navLinks.classList.remove('mobile-open');
                mobileMenuButton.setAttribute('aria-expanded', 'false');
                mobileMenuButton.querySelector('.material-icons').textContent = 'menu';
            });
        });
    </script>
</body>
</html>
