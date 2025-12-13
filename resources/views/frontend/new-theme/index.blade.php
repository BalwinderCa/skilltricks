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

    <style>
         .globe-container canvas:nth-child(1) {
  z-index: 9999;
}
.globe-container canvas:nth-child(2) {
width: 90% !important;height: 90% !important;
}
    </style>
</head>
<body id="body">
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

    <div class="globe-container"></div>
    
    <div class="content-container">
        <div class="text-section" id="section1">
            <h1 class="text-primary">Make Smarter Decisions, Faster</h1>
            <p class="text-subtitle">Use our simple tool to quickly align your plans with your team’s goals</p>
        </div>
        
        <div class="text-section" id="section2">
            <h2 class="text-primary">Harnessing the power of AI to transform leadership and organizational culture</h2>
            <a href="https://calendly.com/collaborate-skilltricksinc/30min" target="_blank" class="cta-button">
                <span>Get Started with a Free Strategy Session</span>
                <canvas class="hover-effect"></canvas>
            </a>
        </div>

        <div class="text-section" id="section3">
            <div class="section-header">
                <h2 class="section-title">Empowering Organizations through Unified Insights</h2>
                <p class="section-description">
                    SkillTricks bridges strategic alignment with cultural dynamics, empowering leaders with data-driven decision tools.
                </p>
            </div>

            <div class="cards-container">
                <div class="feature-card">
                    <img src="{{ asset('newfronted/Assets/AI.png') }}" alt="Strategic Innovation" class="card-icon">
                    <h3 class="card-title">Quick and Smart Planning</h3>
                    <p class="card-description">
                        Adjust your plans quickly to keep up with changes around you. Make sure your team's goals are always matched up with what's happening now.
                    </p>
                </div>

                <div class="feature-card">
                    <img src="{{ asset('newfronted/Assets/Nodes.png') }}" alt="Talent Engagement" class="card-icon">
                    <h3 class="card-title">Better Predictions</h3>
                    <p class="card-description">
                        Use our smart tools to make good guesses about the future. They help you avoid problems and do better by learning from past events and current trends.
                    </p>
                </div>

                <div class="feature-card">
                    <img src="{{ asset('newfronted/Assets/Graph.png') }}" alt="Sustainable Growth" class="card-icon">
                    <h3 class="card-title">Work Smoother and Faster</h3>
                    <p class="card-description">
                        Make sure every part of your work is doing the best it can. Our smart tools help you plan better and keep track of everything easily.
                    </p>
                </div>
            </div>

            <a href="https://calendly.com/collaborate-skilltricksinc/30min" target="_blank" class="section-cta">
                <span>Schedule a Call</span>
                <canvas class="hover-effect"></canvas>
            </a>
        </div>
        
        <div class="scroll-container">
            <img src="{{ asset('newfronted/Assets/ChevronDown.png') }}" alt="Scroll" class="scroll-icon" id="scrollIcon">
            <p class="scroll-text">Keep Scrolling</p>
        </div>

        <footer class="footer index">
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
    </div>

    <section class="parallax-services" id="parallax">
        <div class="services-content">
            <h3 class="service-heading">OUR CORE SERVICES</h3>
            <h2 class="service-title">SkillTricks: A Unique Strategic Enabler</h2>
            <p class="service-description">We revolutionize leadership dynamics, blending emotional intelligence with strategic execution to foster enduring organizational success.</p>
            <p class="service-description">SkillTricks is a platform that blends emotional intelligence with strategic data for consistent success.</p>
            
            <div class="services-cards">
                <div class="service-card unfocused">
                    <img src="{{ asset('newfronted/Assets/Redirect2.png') }}" alt="" id="redirect" class="card-redirect">
                    <h3 class="card-heading">Integrated Platform</h3>
                    <!-- <div class="lottie-container" id="AILottie">
                        <img src="{{ asset('newfronted/Assets/svg1.svg') }}" alt="">
                    </div> -->
                    <div class="img-wrap">
                        <svg width="200" height="200" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" fill="none">
                            <style>
                                /* Pulsing Background Circle */
                                @keyframes pulse {
                                    0%, 100% { transform: scale(1); opacity: 1; }
                                    50% { transform: scale(1.05); opacity: 0.8; }
                                }
                        
                                /* Slight Movement for Knight Piece */
                                @keyframes moveKnight {
                                    0%, 100% { transform: translateX(0); }
                                    50% { transform: translateX(5px); }
                                }
                        
                                /* Text Fading Animation */
                                @keyframes fadeInOut {
                                    0%, 100% { opacity: 0; }
                                    50% { opacity: 1; }
                                }
                        
                                /* Applying Animations */
                                .circle { animation: pulse 3s infinite ease-in-out; transform-origin: center; }
                                .knight { animation: moveKnight 2s infinite ease-in-out; }
                                .text { animation: fadeInOut 3s infinite ease-in-out; }
                            </style>
                        
                            <!-- Background Circle (Pulsing Effect) -->
                            <circle class="circle" cx="100" cy="100" r="85" stroke="#fff" stroke-width="10" fill="none"></circle>
                        
                            <!-- Chess Knight Piece (Slight Movement) -->
                            <path class="knight" d="M80 140 C60 120, 70 90, 90 70 C110 50, 130 60, 120 80 C110 100, 90 110, 85 120 L80 140 Z" fill="#fff" stroke="#ec883f" stroke-width="2"></path>
                        
                            <!-- Text: StrategyStudio (Fading Effect) -->
                            <text class="text" x="50" y="155" font-size="14" font-weight="bold" fill="#36839b" font-family="Arial">StrategyStudio</text>
                        </svg>
                        
                        
                    </div>
                    <p class="card-content">Combines siloed data into a single actionable view.</p>
                </div>
                
                <div class="service-card unfocused">
                    <img src="{{ asset('newfronted/Assets/Redirect2.png') }}" alt="" class="card-redirect">
                    <h3 class="card-heading">Real-Time Decision Making</h3>
                    <!-- <div class="lottie-container" id="StrategyLottie">
                        <img src="{{ asset('newfronted/Assets/svg2.svg') }}" alt="">
                    </div> -->
                    <div class="img-wrap" id="">
                            <svg width="200" height="200" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" fill="none">
                                <style>
                                    /* Pulsing Animation for Leader */
                                    @keyframes pulse {
                                        0%, 100% { transform: scale(1); }
                                        50% { transform: scale(1.1); }
                                    }
                            
                                    /* Bouncing Animation for People */
                                    @keyframes bounce {
                                        0%, 100% { transform: translateY(0); }
                                        50% { transform: translateY(-10px); }
                                    }
                            
                                    /* Text Fade Animation */
                                    @keyframes fadeInOut {
                                        0%, 100% { opacity: 0; }
                                        50% { opacity: 1; }
                                    }
                            
                                    /* Apply Animations */
                                    .leader { animation: pulse 2s infinite ease-in-out; transform-origin: center; }
                                    .people { animation: bounce 2s infinite ease-in-out; }
                                    .text { animation: fadeInOut 3s infinite ease-in-out; }
                                </style>
                            
                                <!-- Background Circle -->
                                <circle cx="100" cy="100" r="80" stroke="#36839b" stroke-width="10" fill="none"></circle>
                            
                                <!-- Leader Figure (Pulsing Effect) -->
                                <circle class="leader" cx="100" cy="60" r="15" fill="#36839b"></circle> 
                                <path d="M85 120 C85 100, 115 100, 115 120 L110 140 L90 140 L85 120 Z" fill="#36839b"></path>
                            
                                <!-- Two People Representing Coaching (Bouncing Effect) -->
                                <circle class="people" cx="60" cy="120" r="10" fill="#36839b"></circle>
                                <circle class="people" cx="140" cy="120" r="10" fill="#36839b"></circle>
                                <path d="M50 140 C50 130, 70 130, 70 140" stroke="#36839b" stroke-width="5"></path>
                                <path d="M130 140 C130 130, 150 130, 150 140" stroke="#36839b" stroke-width="5"></path>
                            
                                <!-- Text: Leadership Coaching (Fading Animation) -->
                                <text class="text" x="60" y="155" font-size="14" font-weight="bold" fill="#36839b" font-family="Arial">Leadership</text>
                            </svg>
                        </div>
                    <p class="card-content">Equips leaders with predictive analytics to act faster and smarter.</p>
                </div>
                
                <div class="service-card focused">
                    <img src="{{ asset('newfronted/Assets/Redirect.png') }}" alt="" class="card-redirect">
                    <h3 class="card-heading">Cultural Alignment Tools</h3>
                    <!-- <div class="lottie-container" id="LeadershipLottie">
                        <img src="{{ asset('newfronted/Assets/svg3.svg') }}" alt="">
                    </div> -->
                    <div class="img-wrap" id="">
                            <svg width="200" height="200" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" fill="none">
                                <style>
                                    /* Arrow Growing Animation (Looping) */
                                    @keyframes growArrow {
                                        0% { transform: scaleY(0); opacity: 0; }
                                        50% { transform: scaleY(1); opacity: 1; }
                                        100% { transform: scaleY(0); opacity: 0; }
                                    }
                                    
                                    /* People Bouncing In (Looping) */
                                    @keyframes bounceIn {
                                        0%, 100% { transform: translateY(20px); opacity: 0; }
                                        50% { transform: translateY(0); opacity: 1; }
                                    }
                            
                                    /* Fade-in Text (Looping) */
                                    @keyframes fadeIn {
                                        0%, 100% { opacity: 0; }
                                        50% { opacity: 1; }
                                    }
                            
                                    .arrow { animation: growArrow 3s ease-in-out infinite; transform-origin: center bottom; }
                                    .people { animation: bounceIn 3s ease-in-out infinite; }
                                    .text { animation: fadeIn 3s ease-in-out infinite; }
                                </style>
                            
                                <!-- Background Circle -->
                                <circle cx="100" cy="100" r="80" stroke="#36839b" stroke-width="10" fill="none"></circle>
                            
                                <!-- Central Growth Symbol (Upward Arrow for Development) -->
                                <g class="arrow">
                                    <path d="M90 130 L100 110 L110 130" stroke="#36839b" stroke-width="6" fill="none"></path>
                                    <rect x="95" y="130" width="10" height="30" fill="#36839b"></rect>
                                </g>
                            
                                <!-- Three People Figures (Representing Organization & Culture) -->
                                <circle class="people" cx="60" cy="120" r="10" fill="#36839b" style="animation-delay: 0.2s;"></circle>
                                <circle class="people" cx="100" cy="80" r="12" fill="#36839b" style="animation-delay: 0.4s;"></circle>
                                <circle class="people" cx="140" cy="120" r="10" fill="#36839b" style="animation-delay: 0.6s;"></circle>
                            
                                <!-- Connecting Lines (Teamwork & Transformation) -->
                                <path d="M60 120 Q80 90, 100 80" stroke="#36839b" stroke-width="5"></path>
                                <path d="M140 120 Q120 90, 100 80" stroke="#36839b" stroke-width="5"></path>
                                
                                <!-- Text Following Curve -->
                                <path id="curve" d="M40 160 Q100 200, 160 160" fill="transparent"></path>
                                
                            </svg>
                        </div>
                    <p class="card-content">Ensures organizational culture reinforces strategic goals.</p>
                </div>
            </div>            
            
            <div class="cards-navigation">
                <button class="nav-arrow prev">
                    <img src="{{ asset('newfronted/Assets/ArrowLeft.png') }}" alt="Previous">
                </button>
                <button class="nav-arrow next">
                    <img src="{{ asset('newfronted/Assets/ArrowRight.png') }}" alt="Next">
                </button>
            </div>
            
            <a  href="https://calendly.com/collaborate-skilltricksinc/30min" target="_blank" class="services-cta">Get Started with a Free Strategy Session</a>
        </div>
    </section>

    <div id="digital-sidebar" class="sidebar close">
        <ul>
            <li>
                <h1 class="heading">Integrated Platform</h1>
                <button class="toggle-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
                </button>
            </li>
            <li class="active">
                <p>SkillTricks consolidates fragmented data from across your organization into a unified, actionable dashboard. This holistic view enables strategic clarity and decision-making efficiency. SkillTricks integrates sales, inventory, and customer feedback data to optimize stock levels and enhance overall customer satisfaction.</p>
            </li>
        </ul>
    </div>

    <div id="strategy-sidebar" class="sidebar close">
        <ul>
            <li>
                <h1 class="heading">Real-Time Decision Making</h1>
                <button class="toggle-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
                </button>
            </li>
            <li class="active">
                <p>Our platform provides leaders with predictive analytics, allowing immediate, informed decisions based on current data trends. This agility is crucial in dynamic market conditions. SkillTricks is used to adjust strategies instantly in response to sudden market shifts, enabling data-driven decision-making with confidence.</p>
            </li>
        </ul>
    </div>

    <div id="leadership-sidebar" class="sidebar close">
        <ul>
            <li>
                <h1 class="heading">Cultural Alignment Tools</h1>
                <button class="toggle-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
                </button>
            </li>
            <li class="active">
                <p>SkillTricks ensures that every strategic decision supports and is supported by your company’s culture, fostering alignment that drives organizational goals and employee engagement. SkillTricks is utilized to tailor leadership communication and HR policies to resonate with the core values and culture of any company, promoting a cohesive work environment.</p>
            </li>
        </ul>
    </div>

    <script src="{{ asset('newfronted/Javascript/index.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/0.160.0/three.min.js"></script>
    <script src="https://d3js.org/d3-geo.v3.min.js"></script>
    <script src="{{ asset('newfronted/Javascript/globe.js') }}"></script>
    <script src="{{ asset('newfronted/Javascript/animation.js') }}"></script>
    <script src="{{ asset('newfronted/Javascript/horizontal.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>

    <script>
        class Globe {
            constructor(container) {
                this.container = container;
                this.scene = new THREE.Scene();
                this.camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
                this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
                this.renderer.setSize(window.innerWidth, window.innerHeight);
                this.container.appendChild(this.renderer.domElement);
                this.init();
            }

            init() {
                this.createGlobe();
                this.camera.position.z = 5;
                window.addEventListener('resize', this.onWindowResize.bind(this));
                this.animate();
            }

            createGlobe() {
                const geometry = new THREE.SphereGeometry(2, 64, 64);
                const textureLoader = new THREE.TextureLoader();    
                const texture = textureLoader.load('https://threejs.org/examples/textures/land_ocean_ice_cloud_2048.jpg');  
                // const texture = textureLoader.load('https://www.shutterstock.com/image-vector/global-connection-network-background-world-260nw-2359662395.jpg');
                const material = new THREE.MeshStandardMaterial({ map: texture });
                this.globe = new THREE.Mesh(geometry, material);
                this.scene.add(this.globe);

                const light = new THREE.DirectionalLight(0xffffff, 1);
                light.position.set(5, 3, 5).normalize();
                this.scene.add(light);
            }

            onWindowResize() {
                this.camera.aspect = window.innerWidth / window.innerHeight;
                this.camera.updateProjectionMatrix();
                this.renderer.setSize(window.innerWidth, window.innerHeight);
            }

            animate() {
                requestAnimationFrame(this.animate.bind(this));
                this.globe.rotation.y += 0.002;
                this.renderer.render(this.scene, this.camera);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const globeContainer = document.querySelector('.globe-container');
            if (globeContainer) {
                new Globe(globeContainer);
            }
        });
    </script>
</body>
</html>