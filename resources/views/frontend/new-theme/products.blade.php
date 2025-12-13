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
                <a class="btn btn-bordered btn-strategistudio" href="{{ route('writebot.dashboard') }}" style="text-transform: none !important;">StrategiStudio</a>
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
                <a class="btn btn-bordered btn-strategistudio" href="{{ route('writebot.dashboard') }}" style="text-transform: none !important;">StrategiStudio</a>
            @else
                <a class="btn btn-bordered" href="{{ url('/register') }}">Register</a>
                <a class="btn btn-bordered" href="{{ url('/login') }}">Login</a>
            @endif
    </nav>

    
    <div class="about-container">
        <div class="globe-container"></div>
        <section class="hero-section">
            <h1 class="text-primary">StrategyStudio: Sync. Lead. Succeed.</h1>
            <p class="text-subtitle">Empower your organization with a holistic, strategic approach to growth. Schedule a demo or contact us today!</p>
            <div class="cta-container">
                <a href="https://calendly.com/collaborate-skilltricksinc/30min" target="_blank" class="services-cta active">Schedule a Call</a>
                <a href="{{ url('/contact') }}" target="_blank" class="services-cta">Contact Us</a>
            </div>
        </section>

        <section class="features-section products">
            <div class="features-content">
                <div class="features-etc">
                    <p class="end"></p>
                    <p><span style="color: #D97C3A;font-weight: bold;font-style: italic;">Your Platform for Achieving Organizational Success</span> 
                    <p class="end"></p>StrategyStudio is more than just a tool—it’s a complete platform for aligning teams, shaping leadership, and building a thriving organizational culture. Designed for simplicity and impact, StrategyStudio helps businesses set clear objectives, track progress, and develop leaders who drive meaningful change.</p>
                    <p class="end"><b>With StrategyStudio, you can achieve…</b></p>
                </div>
                <div class="features-list">
                    <div class="feature-item">
                        <div class="feature-header">
                            <div class="feature-title">
                                <span class="feature-number">01</span>
                                <h3>Strategic Alignment</h3>
                            </div>
                            <button class="toggle-btn">+</button>
                        </div>
                        <p class="feature-description">Align teams and leaders around shared goals for long-term success.</p>
                    </div>
        
                    <div class="feature-item">
                        <div class="feature-header">
                            <div class="feature-title">
                                <span class="feature-number">02</span>
                                <h3>Progress Monitoring</h3>
                            </div>
                            <button class="toggle-btn">+</button>
                        </div>
                        <p class="feature-description">Track progress effortlessly with intuitive dashboards and real-time insights.</p>
                    </div>
        
                    <div class="feature-item">
                        <div class="feature-header">
                            <div class="feature-title">
                                <span class="feature-number">03</span>
                                <h3>Leadership Excellence</h3>
                            </div>
                            <button class="toggle-btn">+</button>
                        </div>
                        <p class="feature-description">Develop leadership capabilities with the integrated Leadership Lab.</p>
                    </div>
        
                    <div class="feature-item">
                        <div class="feature-header">
                            <div class="feature-title">
                                <span class="feature-number">04</span>
                                <h3>Cultural Transformation</h3>
                            </div>
                            <button class="toggle-btn">+</button>
                        </div>
                        <p class="feature-description">Shape workplace culture with Cultural Compass, ensuring values drive results.</p>
                    </div>
        
                    <div class="feature-item">
                        <div class="feature-header">
                            <div class="feature-title">
                                <span class="feature-number">05</span>
                                <h3>Decision Support</h3>
                            </div>
                            <button class="toggle-btn">+</button>
                        </div>
                        <p class="feature-description">Make informed decisions with smart, data-driven recommendations.</p>
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