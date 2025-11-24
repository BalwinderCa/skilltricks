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


</head>
<body  id="body">
    <nav class="nav-desktop">
        <a href="/"><img src="{{ asset('newfronted/Assets/Logo.svg') }}" alt="Logo" class="logo"></a>
        <div class="nav-items">
            <a href="{{ url('/about') }}">WHO WE ARE</a>
            <a href="{{ url('/services') }}">WHAT WE DO</a>
            <a href="{{ url('/products') }}">PRODUCT</a>
            <a href="{{ url('/contact') }}">CONTACT</a>
            @if(Auth::check())
                <a class="btn btn-bordered" href="{{ route('writebot.dashboard') }}">Dashboard</a>
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
                <a class="btn btn-bordered" href="{{ route('writebot.dashboard') }}">Dashboard</a>
            @else
                <a class="btn btn-bordered" href="{{ url('/register') }}">Register</a>
                <a class="btn btn-bordered" href="{{ url('/login') }}">Login</a>
            @endif
    </nav>
    
    <div class="about-container services">
        <section class="heros-section">
            <div class="video-background">
                <video autoplay muted loop playsinline>
                    <source src="{{ asset('newfronted/Assets/video.mov') }}" type="video/mp4">
                </video>
            </div>
            <h1 class="text-primary">Transforming Strategy into Success</h1>
            <p class="desc">Simplifying decision-making with smart, sensitive solutions that empower every level of your organization.</p>
        </section>
        
        <section class="parallax-services services" id="parallax">
            <div class="services-content">
                <h3 class="service-heading">OUR CORE SERVICES</h3>
                <h2 class="service-title">SkillTricks: Simplifying Strategy with Smarts and Sensitivity</h2>
                <p class="service-description">At SkillTricks, we transform how your organization sets and achieves its most critical goals. We believe in making strategic goal-setting simple, understandable, and accessible for everyone in your company, from the CEO to new hires. <br/><h2 style="color: black;">Here’s how we help:</h2></p>

                <div class="services-cards">
                    <div class="service-card unfocused">
                        <img src="{{ asset('newfronted/Assets/Redirect2.png') }}" alt="" id="redirect" class="card-redirect">
                        <h3 class="card-heading">Simplify Your Strategy</h3>
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
                        <p class="card-content">Clear and Simple Goal-Setting.</p>
                    </div>
                    
                    <div class="service-card unfocused">
                        <img src="{{ asset('newfronted/Assets/Redirect2.png') }}" alt="" class="card-redirect">
                        <h3 class="card-heading">Make Decision with Confidence</h3>
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
                        <p class="card-content">Real-time insights at Your Fingertips.</p>
                    </div>
                    
                    <div class="service-card focused">
                        <img src="{{ asset('newfronted/Assets/Redirect.png') }}" alt="" class="card-redirect">
                        <h3 class="card-heading">Build a unified team</h3>
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
                        <p class="card-content">Align your Culture with Your Goals.</p>
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

        <div class="transform-highlight services">
            <p class="highlight-text">
                <span class="emphasis">Why It Matters</span>
                <p style="max-width: 1200px;">SkillTricks isn’t just another tool—it’s a strategic partner in your organization's growth. By making the goal-setting process clearer, decision-making quicker, and aligning your team’s efforts with your company's objectives, we help you turn plans into action and goals into achievements. Discover the power of streamlined strategy with our PivotalPoint. Let us help you align, act, and achieve like never before.</p>
                <a href="{{ url('/contact') }}" class="cta-button transform">
                    <span>Contact Us</span>
                    <canvas class="hover-effect"></canvas>
                </a>
            </p>
        </div>

        <div class="testimonials-section">
            <div class="testimonials-container">
              <h2 class="title">Don't take our word for it.</h2>
              <h3 class="subtitle">Listen to what others say about us.</h3>
          
              <div class="testimonials-wrapper">
                <div class="testimonials-track">

                  <div class="testimonial-card">
                    <p class="testimonial-text">The Integrated Platform from SkillTricks has been a game-changer for our sales strategy. It's streamlined our data management and given us actionable insights that have directly boosted our performance. I can't imagine going back to our old systems.</p>
                    <div class="testimonial-footer">
                      <div class="testimonial-author">
                        <div class="author-avatar"><img src="{{ asset('newfronted/Assets/avatar1.webp') }}" alt=""></div>
                        <div class="author-info">
                          <p class="author-name">Sales Operations Manager</p>
                          <!--p class="author-role">Sales Operations Manager</p-->
                        </div>
                      </div>
                    </div>
                  </div>
          
                  <div class="testimonial-card">
                    <p class="testimonial-text">SkillTricks has revolutionized how we handle project analytics. With Real-time Decision Making, our team responds proactively to project shifts, staying ahead of potential issues. It's indispensable for dynamic program management.</p>
                    <div class="testimonial-footer">
                      <div class="testimonial-author">
                        <div class="author-avatar"><img src="{{ asset('newfronted/Assets/avatar2.webp') }}" alt=""></div>
                        <div class="author-info">
                          <p class="author-name">Program Manager</p>
                          <!--p class="author-role">Program Manager</p-->
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="testimonial-card">
                    <p class="testimonial-text">This tool has been pivotal in enhancing our operational workflow. The real-time analytics allow us to make smarter decisions quickly, which has been crucial in improving our operational efficiency and reducing downtime.</p>
                    <div class="testimonial-footer">
                      <div class="testimonial-author">
                        <div class="author-avatar"><img src="{{ asset('newfronted/Assets/avatar1.webp') }}" alt=""></div>
                        <div class="author-info">
                          <p class="author-name">AVP Operations</p>
                          <!--p class="author-role">AVP Operations</p-->
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="testimonial-card">
                    <p class="testimonial-text">As a startup co-founder, aligning our fast-paced growth with our cultural values has been challenging. SkillTricks’ tools have been instrumental in helping us maintain our core values while scaling effectively.</p>
                    <div class="testimonial-footer">
                      <div class="testimonial-author">
                        <div class="author-avatar"><img src="{{ asset('newfronted/Assets/avatar2.webp') }}" alt=""></div>
                        <div class="author-info">
                          <p class="author-name">CoFounder</p>
                          <!--p class="author-role">CoFounder</p-->
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="testimonial-card">
                    <p class="testimonial-text">Implementing SkillTricks' tools has led to noticeable improvements in how we manage and align our human resources. The insights provided by the platform have enabled us to enhance our HR strategies and employee satisfaction significantly.</p>
                    <div class="testimonial-footer">
                      <div class="testimonial-author">
                        <div class="author-avatar"><img src="{{ asset('newfronted/Assets/avatar1.webp') }}" alt=""></div>
                        <div class="author-info">
                          <p class="author-name">AVP HR</p>
                          <!--p class="author-role">AVP HR</p-->
                        </div>
                      </div>
                    </div>
                  </div>
          
                  <div class="testimonial-card">
                    <p class="testimonial-text">The Cultural Alignment Tools offered by SkillTricks have transformed our approach to team collaboration and culture building. Our teams are more aligned and our service delivery has never been smoother or more efficient.</p>
                    <div class="testimonial-footer">
                      <div class="testimonial-author">
                        <div class="author-avatar"><img src="{{ asset('newfronted/Assets/avatar2.webp') }}" alt=""></div>
                        <div class="author-info">
                          <p class="author-name">Service Delivery Manager</p>
                          <!--p class="author-role">Service Delivery Manager</p-->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
          
                <div class="navigation-buttons">
                  <button class="nav-button prev">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#36839B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                  </button>
                  <button class="nav-button next">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#36839B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

    

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
    <div id="digital-sidebar" class="sidebar close">
        <ul>
            <li>
                <h1 class="heading">Simplify Your Strategy</h1>
                <button class="toggle-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
                </button>
            </li>
            <li class="active">
                <p>Forget about the complex strategic planning tools of the past. GoalBridge simplifies the goal-setting process by integrating all your data sources into one easy-to-understand platform. This means you can see the big picture or dive into details without getting lost in the data.</p>
            </li>
        </ul>
    </div>
    
    <div id="strategy-sidebar" class="sidebar close">
        <ul>
            <li>
                <h1 class="heading">Make Decision with Confidence</h1>
                <button class="toggle-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
                </button>
            </li>
            <li class="active">
                <p>With GoalBridge, uncertainty is a thing of the past. Our platform uses the latest in predictive analytics to provide you with real-time insights. This means you can make informed decisions quickly, staying one step ahead in today’s fast-paced market.</p>
            </li>
        </ul>
    </div>
    
    <div id="leadership-sidebar" class="sidebar close">
        <ul>
            <li>
                <h1 class="heading">Build a unified team</h1>
                <button class="toggle-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
                </button>
            </li>
            <li class="active">
                <p>Our tools do more than just track numbers; they help ensure that your entire organization’s culture and daily activities reinforce your strategic goals. With GoalBridge, every team member understands how their actions contribute to the company’s success, creating a cohesive and motivated workforce.</p>
            </li>
        </ul>
    </div>
    
    <script src="{{ asset('newfronted/Javascript/index.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/0.160.0/three.min.js"></script>
    <script src="{{ asset('newfronted/Javascript/animation.js') }}"></script>
    <script src="{{ asset('newfronted/Javascript/horizontal.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
</body>
</html>