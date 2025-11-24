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
    
    <script src="https://cdn.jsdelivr.net/gh/studio-freight/lenis@1.0.27/bundled/lenis.min.js"></script>
    <script src="https://kit.fontawesome.com/670c39e75d.js" crossorigin="anonymous"></script>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="{{ asset('newfronted/Styles/index.css') }}">

</head>
<body id="body">
    <nav class="nav-desktop">
        <a href="/"><img src="{{ asset('newfronted/Assets/Logo.svg') }}" alt="Logo" class="logo"></a>
        <div class="nav-items">
            <a href="{{ url('/about') }}">WHO WE ARE</a>
            <a href="{{ url('/services') }}">WHAT WE DO</a>
            <a href="{{ url('/products') }}">PRODUCT</a>
            <a href="{{ url('/contact') }}">CONTACT</a>
        </div>
        <button class="menu-toggle">Menu</button>
    </nav>

    <nav class="nav-mobile">
        <a href="{{ url('/about') }}">WHO WE ARE</a>
        <a href="{{ url('/services') }}">WHAT WE DO</a>
        <a href="{{ url('/products') }}">PRODUCT</a>
        <a href="{{ url('/contact') }}">CONTACT</a>
    </nav>

    <div class="about-container contact">
        <section class="contact-container">
            <div class="contact-content">
                <div class="contact-info">
                    <div class="info-text">
                        <h2>Interested in working with us?</h2>
                        <p class="description">Whether you're looking to explore our solutions or ready to take your organization to new heights, we're here to help. Schedule a free strategy session to discuss how StrategyStudio can drive results for you.</p>
                    </div>
                    <div class="contact-address">
                        <h3>SkillTricks</h3>
                        <p>2030 Bristol Cir Suite 210, Oakville,<br>Ontario L6H 6P5, Canada</p>
                        <a href="mailto:collaborate@skilltricksinc.com" target="_blank" >collaborate@skilltricksinc.com</a>
                        <p>+1 (647) 686-1279</p>
                    </div>
                </div>
        
                <div class="contact-form">
                    <h3>CONTACT FORM</h3>
                    <form>
                        <div class="form-group">
                            <input type="text" name="name" placeholder="Full Name" required>
                            <label>Full Name <span class="required">*</span></label>
                        </div>
                        <div class="form-group">
                            <input type="text" name="company" placeholder="Company Name" required>
                            <label>Company Name <span class="required">*</span></label>
                        </div>
                        <div class="form-group">
                            <input type="email" name="email" placeholder="Email" required>
                            <label>Email <span class="required">*</span></label>
                        </div>
                        <div class="form-group">
                            <input type="tel" name="phone" placeholder="Phone Number" required>
                            <label>Phone Number <span class="required">*</span></label>
                        </div>
                        <div class="form-group">
                            <textarea name="message" placeholder="Message" required></textarea>
                            <label>Message <span class="required">*</span></label>
                        </div>
                        <div class="checkbox">
                            <input type="checkbox" id="privacy" required>
                            <label for="privacy">You agree to our friendly <a href="#">Privacy Policy</a></label>
                        </div>
                        <button type="submit">SUBMIT</button>
                        <button type="submit"><a href="https://calendly.com/collaborate-skilltricksinc/30min" target="_blank" >Schedule a Call</a></button>
                    </form>
                </div>
            </div>
        </section>

        <div class="map-container">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2899.955213660632!2d-79.70818392346129!3d43.37413517111767!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x882b5c86e45208a9%3A0x4cb15892cd1434!2s2030%20Bristol%20Cir%20%23210%2C%20Oakville%2C%20ON%20L6H%206P5%2C%20Canada!5e0!3m2!1sen!2sin!4v1708701743744!5m2!1sen!2sin" 
                width="1100" 
                height="450" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
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
    <script src="{{ asset('newfronted/Javascript/index.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/0.160.0/three.min.js"></script>
    <script src="{{ asset('newfronted/Javascript/animation.js') }}"></script>
    <script src="{{ asset('newfronted/Javascript/horizontal.js') }}"></script>
</body>
</html>