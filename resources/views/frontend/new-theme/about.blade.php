<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Tricks</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('newfronted/Logo.svg') }}">
    <link rel="icon" type="image/png" href="{{ asset('newfronted/Logo.png') }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script type="text/javascript"
        src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js">
    </script>
    <script type="text/javascript">
    (function(){
        emailjs.init({
            publicKey: "dPiUhN5PJAjdP2xAZ",
        });
    })();
    </script>
    <script src="https://cdn.jsdelivr.net/gh/studio-freight/lenis@1.0.27/bundled/lenis.min.js"></script>
    <script src="https://kit.fontawesome.com/670c39e75d.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="{{ asset('newfronted/Styles/index.css') }}">

</head>
<body>
    <nav class="nav-desktop">
        <a href="/"><img src="{{ asset('newfronted/Assets/Logo.svg') }}" alt="Logo" class="logo"></a>
        <div class="nav-items">
            <a href="{{ url('/about') }}">WHO WE ARE</a>
            <a href="{{ url('/services') }}">WHAT WE DO</a>
            <a href="{{ url('/products') }}">PRODUCT</a>
            <a href="{{ url('/contact') }}">CONTACT</a>
            @if(Auth::check())
                <a class="btn btn-bordered btn-strategistudio" href="{{ route('writebot.dashboard') }}">StrategiStudio</a>
            @else
                <a class="btn btn-bordered" href="{{ url('/register') }}">Register</a>
                <a class="btn btn-bordered" href="{{ url('/login') }}">Login</a>
            @endif
        </div>
        <button class="menu-toggle">Menu</button>
    </nav>

    <nav class="nav-mobile">
        <a href="{{ url('/about') }}">WHO WE ARE</a>
        <a href="{{ url('/services') }}">WHAT WE DO</a>
        <a href="{{ url('/products') }}">PRODUCT</a>
        <a href="{{ url('/contact') }}">CONTACT</a>
        @if(Auth::check())
            <a class="btn btn-bordered btn-strategistudio" href="{{ route('writebot.dashboard') }}">StrategiStudio</a>
        @else
            <a class="btn btn-bordered" href="{{ url('/register') }}">Register</a>
            <a class="btn btn-bordered" href="{{ url('/login') }}">Login</a>
        @endif
    </nav>

    
    <div class="about-container">
        <div class="globe-container"></div>
        <section class="hero-section">
            <h1 class="text-primary">SkillTricks: Shaping the Future of Strategic Execution</h1>
            <p class="text-subtitle">We are the innovators behind PivotalPoint, transforming complex strategies into clear paths to success. With a deep understanding of technology and organizational behavior, our team empowers your entire company to achieve outstanding results.</p>
            <p class="text-subtitle"></p>
        </section>
        
        <div class="transform-highlight">
            <p class="highlight-text">
                <span class="emphasis">SkillTricks: Your Partner in Smart Strategy</span>
                <p>We are more than just a platform — it's your smart partner in crafting winning strategies. We mix understanding people with clever data use to help you succeed again and again.</p>
                <a href="https://calendly.com/collaborate-skilltricksinc/30min" target="_blank" class="cta-button transform">
                    <span>Transform With Us</span>
                    <canvas class="hover-effect"></canvas>
                </a>
            </p>
        </div>
        
        <section class="mission-section" id="our">
            <div class="mission-links">
                <a href="#our">Why we are the best?</a>
                <a href="#what">Building the Future Team</a>
                <a href="#why">What Drives us?</a>
            </div>
            
            <div class="mission-content">
                <p class="founder">Dr. Venkat Adivi – Founder & CEO</p>
                <p>
                    Dr. Venkat Adivi is the visionary behind SkillTricks, combining over 20 years of experience in transforming organizational strategy with cutting-edge research in leadership and cultural alignment. With a PhD in Leadership-Culture Synergy, Dr. Adivi’s work bridges academic insights and practical applications, offering solutions that drive measurable improvements in organizational efficiency and leadership effectiveness.
                </p>
                <p>
                    Dr. Adivi’s journey began with an ambitious goal: to address the universal challenges leaders face—misalignment, data silos, and inconsistent decision-making. His doctoral research revealed actionable strategies to align culture with strategy, improving productivity and fostering innovation. Today, that passion drives SkillTricks as a leader in transforming organizations through data-driven decision-making and cultural coherence.
                </p>
            </div>
        </section>

        <section class="features-section" id="what">
            <div class="features-content">
                <h2 class="features-heading">Building the Future Team</h2>
                <div class="features-etc">
                    <p class="end"></p>
                    <p>At SkillTricks, <span style="color: #D97C3A;font-weight: bold;font-style: italic;">we understand that great solutions require a great team.</span> 
                    <p class="end"></p>While our journey began as a solo vision, we are actively building a team of experts to scale SkillTricks to new heights. Our focus is on recruiting talent in AI, machine learning, and organizational psychology to create solutions that redefine leadership dynamics and transform organizations globally.</p>
                    <p class="end"><b>Together we bring…</b></p>
                </div>
                <div class="features-list">
                    <div class="feature-item">
                        <div class="feature-header">
                            <div class="feature-title">
                                <span class="feature-number">01</span>
                                <h3>Deep Expertise</h3>
                            </div>
                            <button class="toggle-btn">+</button>
                        </div>
                        <p class="feature-description">Over two decades in leadership and organizational strategy, enhanced by a PhD focusing on Leadership-Culture Synergy.</p>
                    </div>
        
                    <div class="feature-item">
                        <div class="feature-header">
                            <div class="feature-title">
                                <span class="feature-number">02</span>
                                <h3>Innovative Solutions</h3>
                            </div>
                            <button class="toggle-btn">+</button>
                        </div>
                        <p class="feature-description">SkillTricks emerges from rigorous academic research and real-world applications, designed to seamlessly integrate strategic goals with organizational culture.</p>
                    </div>
        
                    <div class="feature-item">
                        <div class="feature-header">
                            <div class="feature-title">
                                <span class="feature-number">03</span>
                                <h3>Committed Leadership</h3>
                            </div>
                            <button class="toggle-btn">+</button>
                        </div>
                        <p class="feature-description">Driven to transform traditional leadership paradigms, SkillTricks is poised to lead market evolution with cutting-edge, data-driven solutions.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="video-section">
            <div class="video-container">
                <video 
                    autoplay
                    muted
                    loop
                    playsinline
                    width="100%"
                    height="100%"
                >
                <source src="{{ asset('newfronted/Assets/video.mov') }}" type="video/mp4">        
        </section>
        
        <section class="features-section" id="why">
            <div class="features-content">
                <h2 class="features-heading">What Drives us?</h2>
                <div class="features-etc">
                    <p class="end"><b style="color: #D97C3A;font-weight: bold;font-style: italic;">At SkillTricks, our foundational principles set us apart, each driving distinct, impactful outcomes.</b></p>
                    <p>These core values inspire every action at SkillTricks, guiding our mission to transform leadership and organizational culture into a cohesive, data-driven framework for success.</p>
                </div>
                <div class="features-list">
                    <div class="feature-item">
                        <div class="feature-header">
                            <div class="feature-title">
                                <span class="feature-number">01</span>
                                <h3 class="last">Problem-Solving Expertise</h3>
                            </div>
                            <button class="toggle-btn">+</button>
                        </div>
                        <p class="feature-description">Our deep-rooted knack for addressing complex challenges translates into tangible results. We harness analytical precision and creative solutions to turn potential obstacles into opportunities for growth.</p>
                    </div>
        
                    <div class="feature-item">
                        <div class="feature-header">
                            <div class="feature-title">
                                <span class="feature-number">02</span>
                                <h3 class="last">Adaptive Leadership</h3>
                            </div>
                            <button class="toggle-btn">+</button>
                        </div>
                        <p class="feature-description">We bring fresh perspectives to traditional challenges. Our leadership style is flexible and responsive, adapting to new trends and altering strategies to meet evolving organizational needs effectively.</p>
                    </div>
        
                    <div class="feature-item">
                        <div class="feature-header">
                            <div class="feature-title">
                                <span class="feature-number">03</span>
                                <h3 class="last">Commitment to Excellence</h3>
                            </div>
                            <button class="toggle-btn">+</button>
                        </div>
                        <p class="feature-description">Our relentless pursuit of excellence is more than a goal—it's a journey. We strive to elevate every project we touch, paving a pathway to success through continuous improvement and dedication to quality.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="quote-section">
            <div class="quote-info">
                <h2>Our Mission & Vision</h2>
            </div>
            <div class="quote-container">
                <div class="quote-card">
                    <i class="fa-solid fa-quote-left quote-icon"></i>
                    <h3>Our Vision</h3>
                    <p>To become one of the defining organizations in decision intelligence technology.</p>
                    <div class="quote-footer">
                        <i class="fa-solid fa-quote-right quote-icon"></i>
                    </div>
                </div>
        
                <div class="quote-card">
                    <i class="fa-solid fa-quote-left quote-icon"></i>
                    <h3>Our Mission</h3>
                    <p>Our mission is to redefine strategic decision-making through cutting-edge technology, providing leaders with advanced tools that enhance accuracy, efficiency, and adaptability. By integrating decision intelligence into every level of an organization, we empower leaders to achieve unparalleled success and drive their businesses forward.</p>
                    <div class="quote-footer">
                        <i class="fa-solid fa-quote-right quote-icon"></i>
                    </div>
                </div>
            </div>
        </section>

        <div class="chatbot-container">
            <div class="chatbot-header">
                <h3>SkillTricks Assistant</h3>
                <div class="header-buttons">
                    <button class="restart-chat">
                        <i class="fas fa-redo"></i>
                    </button>
                    <button class="close-chat">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            
            <div class="chat-messages">
                <div class="option-buttons">
                </div>
            </div>

            <div class="typing-indicator" style="display: none;">
                <div class="dot"></div>
                <div class="dot"></div>
                <div class="dot"></div>
            </div>
            
            <div class="user-input">
                <div class="input-container">
                    <input type="text" placeholder="Type your message..." disabled>
                    <button class="send-message" disabled>
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <button class="chatbot-toggle">
            <i class="fas fa-comments"></i>
        </button>

        <footer class="footer">
            <div class="footer-content">
                <div class="footer-column">
                    <h2>SkillTricks</h2>
                    <p class="address">2030 Bristol Cir Suite 210, Oakville,<br>Ontario L6H 6P5, Canada</p>
                    <a href="mailto:collaborate@skilltricksinc.com" target="_blank" >collaborate@skilltricksinc.com</a>
                    <p class="phone">+1 (647) 686-1279</p>
                    <a href="#" class="linkedin">LinkedIn</a>
                    <p class="copyright">© SkillTricks Copyrights</p>
                </div>
        
                <div class="footer-column links">
                    <a href="{{ url('/about') }}">Who We Are ↗</a>
                    <a href="{{ url('/services') }}">What We Do ↗</a>
                    <a href="{{ url('/products') }}">Product ↗</a>
                    <a href="{{ url('/contact') }}">Contact ↗</a>
                </div>
        
                <div class="footer-column legal">
                    <div>
                        <a href="{{ url('/privacypolicy') }}">Privacy Policy</a>
                        <a href="{{ url('/cookiespolicy') }}">Cookies</a>
                        <a href="{{ url('/terms') }}">Terms of Use</a>
                    </div>
                    <a href="#" class="back-to-top">Back to Top ↑</a>
                </div>
            </div>
        </footer>
    </div>

    <script src="{{ asset('newfronted/Javascript/index.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/0.160.0/three.min.js"></script>
    <script src="https://d3js.org/d3-geo.v3.min.js"></script>
    <script src="{{ asset('newfronted/Javascript/globe.js') }}"></script>
    <script src="{{ asset('newfronted/Javascript/animation.js') }}"></script>
</body>
</html>