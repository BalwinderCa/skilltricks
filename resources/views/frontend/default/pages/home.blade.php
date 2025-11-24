@extends('frontend.default.layouts.master')

@section('title')
    {{ localize('Home') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('contents')
    
<div id="body">
    <div class="globe-container"></div>
    
    <div class="content-container">
        <div class="text-section" id="section1">
            <div class="round-border-block">
                <span class="round-animtion"></span>
                <h1 class="h1-ttl text-primary text-white">Transform Your Organization’s Future with StrategyStudio</h1>
            </div>
        </div>
        
        <div class="text-section" id="section2">
            <div class="round-border-block">
                <span class="round-animtion"></span>
                <div class="d-flex flex-column">
                    <h2 class="h2-ttl text-primary text-white">Harnessing the power of AI to transform leadership and organizational culture</h2>
                    <div class="btn-block text-center">
                        <a href="https://calendly.com/collaborate-skilltricksinc/30min" target="_blank" class="cta-button">
                            <span>Get Started with a Free Strategy Session</span>
                            <canvas class="hover-effect"></canvas>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-section" id="section3">
            <div class="section-header">
                <h2 class="section-title text-left">Empowering Organizations through Unified Insights</h2>
                <p class="section-description">
                    SkillTricks is dedicated to empowering organizations to reach their full potential through strategic innovation and intelligent insights. With StrategyStudio, our flagship solution, we combine data-driven strategies with actionable insights to enhance organizational competitiveness, foster leadership, and create impactful transformations.
                </p>
            </div>

            <div class="cards-container">
                <div class="feature-card">
                    <img src="assets/images/AI.png" alt="Strategic Innovation" class="card-icon">
                    <h3 class="card-title">Strategic Innovation</h3>
                    <p class="card-description">
                        AI-driven strategies tailored to meet evolving business needs and stay ahead in dynamic markets.
                    </p>
                </div>

                <div class="feature-card">
                    <img src="assets/images/Nodes.png" alt="Talent Engagement" class="card-icon">
                    <h3 class="card-title">Talent Engagement</h3>
                    <p class="card-description">
                        Enhance workforce engagement and unlock potential through customized growth and learning frameworks.
                    </p>
                </div>

                <div class="feature-card">
                    <img src="assets/images/Graph.png" alt="Sustainable Growth" class="card-icon">
                    <h3 class="card-title">Sustainable Growth</h3>
                    <p class="card-description">
                        Ensure long-term success with adaptable solutions designed to grow alongside your organization.
                    </p>
                </div>
            </div>

            <a href="https://calendly.com/collaborate-skilltricksinc/30min" target="_blank" class="section-cta">
                <span>Schedule a Call</span>
                <canvas class="hover-effect"></canvas>
            </a>
        </div>
        
        <div class="scroll-container">
            <img src="assets/images/ChevronDown.png" alt="Scroll" class="scroll-icon" id="scrollIcon">
            <p class="scroll-text">Keep Scrolling</p>
        </div>
        

        <footer class="footer index">
            <div class="footer-content">
                <div class="footer-column">
                    <h4>SkillTricks</h4>
                    <p>792 W 8th Street,<br> Minot 82884, Canada</p>
                    <ul>
                        <li><a href="mailto:contact@skilltricks.com">contact@skilltricks.com</a></li>
                        <li><a href="tel:(631) 651-8811">(631) 651-8811</a></li>
                    </ul>
                    <a href="#" class="linkedin">LinkedIn</a>
                    <p class="copyright">© SkillTricks Copyrights</p>
                </div>
        
                <div class="footer-column links">
                    <a href="about.html">Who We Are ↗</a>
                    <a href="services.html">What We Do ↗</a>
                    <a href="contact.html">Contact ↗</a>
                </div>
        
                <div class="footer-column legal">
                    <div>
                        <a href="privacy-policy.html">Privacy Policy</a>
                        <a href="cookies.html">Cookies</a>
                        <a href="terms-of-use.html">Terms of Use</a>
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

    <section class="parallax-services SERVICES1" id="parallax">
        <div class="services-content">
            <h3 class="service-heading">OUR CORE SERVICES</h3>
            <h2 class="service-title">Strategic Excellence Delivered Through <br> Custom Solutions and Measurable Impact</h2>
            
            <div class="services-cards">
                <div class="service-card unfocused">
                    <img src="assets/images/Redirect2.png" alt="" id="redirect" class="card-redirect" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight">
                    <h3 class="card-heading">StrategyStudio Implementation</h3>
                    <div class="img-wrap">
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
                                <circle class="circle" cx="100" cy="100" r="85" stroke="#fff" stroke-width="10" fill="none"/>
                            
                                <!-- Chess Knight Piece (Slight Movement) -->
                                <path class="knight" d="M80 140 C60 120, 70 90, 90 70 C110 50, 130 60, 120 80 C110 100, 90 110, 85 120 L80 140 Z" 
                                      fill="#fff" stroke="#ec883f" stroke-width="2"/>
                            
                                <!-- Text: StrategyStudio (Fading Effect) -->
                                <text class="text" x="50" y="155" font-size="14" font-weight="bold" fill="#36839b" font-family="Arial">StrategyStudio</text>
                            </svg>
                            
                            
                        </div>
                    </div>
                    <p class="card-content">Our primary offering, StrategyStudio, equips businesses with a structured, AI-powered tool to analyze, strategize, and implement impactful changes. Through a seamless onboarding process, we ensure that you get the most out of your investment.</p>
                </div>
                
                <div class="service-card unfocused">
                    <img src="assets/images/Redirect2.png" alt="" class="card-redirect" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight">
                    <h3 class="card-heading">Leadership Coaching</h3>
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
                            <circle cx="100" cy="100" r="80" stroke="#36839b" stroke-width="10" fill="none"/>
                        
                            <!-- Leader Figure (Pulsing Effect) -->
                            <circle class="leader" cx="100" cy="60" r="15" fill="#36839b"/> 
                            <path d="M85 120 C85 100, 115 100, 115 120 L110 140 L90 140 L85 120 Z" fill="#36839b"/>
                        
                            <!-- Two People Representing Coaching (Bouncing Effect) -->
                            <circle class="people" cx="60" cy="120" r="10" fill="#36839b"/>
                            <circle class="people" cx="140" cy="120" r="10" fill="#36839b"/>
                            <path d="M50 140 C50 130, 70 130, 70 140" stroke="#36839b" stroke-width="5"/>
                            <path d="M130 140 C130 130, 150 130, 150 140" stroke="#36839b" stroke-width="5"/>
                        
                            <!-- Text: Leadership Coaching (Fading Animation) -->
                            <text class="text" x="60" y="155" font-size="14" font-weight="bold" fill="#36839b" font-family="Arial">Leadership</text>
                        </svg>
                    </div>
                    <p class="card-content">Tailored coaching sessions that help leaders develop critical thinking, resilience, and adaptability—key skills for guiding teams through change.</p>
                </div>
                
                <div class="service-card focused">
                    <img src="assets/images/Redirect.png" alt="" class="card-redirect" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight">
                    <h3 class="card-heading">Organizational Development & Culture Transformation</h3>
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
                            <circle cx="100" cy="100" r="80" stroke="#36839b" stroke-width="10" fill="none"/>
                        
                            <!-- Central Growth Symbol (Upward Arrow for Development) -->
                            <g class="arrow">
                                <path d="M90 130 L100 110 L110 130" stroke="#36839b" stroke-width="6" fill="none"/>
                                <rect x="95" y="130" width="10" height="30" fill="#36839b"/>
                            </g>
                        
                            <!-- Three People Figures (Representing Organization & Culture) -->
                            <circle class="people" cx="60" cy="120" r="10" fill="#36839b" style="animation-delay: 0.2s;"/>
                            <circle class="people" cx="100" cy="80" r="12" fill="#36839b" style="animation-delay: 0.4s;"/>
                            <circle class="people" cx="140" cy="120" r="10" fill="#36839b" style="animation-delay: 0.6s;"/>
                        
                            <!-- Connecting Lines (Teamwork & Transformation) -->
                            <path d="M60 120 Q80 90, 100 80" stroke="#36839b" stroke-width="5"/>
                            <path d="M140 120 Q120 90, 100 80" stroke="#36839b" stroke-width="5"/>
                            
                            <!-- Text Following Curve -->
                            <path id="curve" d="M40 160 Q100 200, 160 160" fill="transparent"/>
                            
                        </svg>
                    </div>
                    <p class="card-content">Drive cultural transformation with strategies designed to boost engagement, align values, and foster a positive, productive environment.</p>
                </div>
            </div>            
            <a href="https://calendly.com/collaborate-skilltricksinc/30min" target="_blank" class="services-cta">Get Started with a Free Strategy Session</a>
            <div class="cards-navigation mt_5">
                
            &nbsp; &nbsp;
                <div class="inline-btn d-flex align-items-center">
                    <button class="nav-arrow prev">
                        <img src="assets/images/ArrowLeft.png" alt="Previous">
                    </button>
                    <button class="nav-arrow next">
                        <img src="assets/images/ArrowRight.png" alt="Next">
                    </button>
                </div>
            </div>
        </div>
    </section>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
        <div class="offcanvas-header">
          <h5 id="offcanvasRightLabel"></h5>
          <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-lg" viewBox="0 0 16 16">
                <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"></path>
              </svg>
            </button>
        </div>
        <div class="offcanvas-body">
          <ul>
            <li>
                <div class="offcanvas-block">
                    <h6>Lorem Ipsum 1:</h6>
                    <p>Figma ipsum component variant main layer. Figjam bullet flatten move follower. Select flows flatten invite content main export polygon. Prototype duplicate scrolling object pencil. Font star export stroke bullet asset mask rectangle pen. Layout layout component move line.</p>
                </div>
            </li>
            <li>
                <div class="offcanvas-block">
                    <h6>Lorem Ipsum 2:</h6>
                    <p>Figma ipsum component variant main layer. Figjam bullet flatten move follower. Select flows flatten invite content main export polygon. Prototype duplicate scrolling object pencil. Font star export stroke bullet asset mask rectangle pen. Layout layout component move line.</p>
                </div>
            </li>
            <li>
                <div class="offcanvas-block">
                    <h6>Lorem Ipsum 3:</h6>
                    <p>Figma ipsum component variant main layer. Figjam bullet flatten move follower. Select flows flatten invite content main export polygon. Prototype duplicate scrolling object pencil. Font star export stroke bullet asset mask rectangle pen. Layout layout component move line.</p>
                </div>
            </li>
          </ul>
        </div>
      </div>
</div>


<script src="https://login2design.in/htmls/skilltricksinc-new/assets/js/index.js"></script>
<script src="assets/js/globe.js"></script>
    <script src="assets/js/animation.js"></script>
    <script src="assets/js/horizontal.js"></script>
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
@endsection
