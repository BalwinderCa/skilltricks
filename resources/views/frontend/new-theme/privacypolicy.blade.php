<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillTricks</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('newfronted/Logo.svg') }}">
    <link rel="icon" type="image/png" href="{{ asset('newfronted/Logo.png') }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/gh/studio-freight/lenis@1.0.27/bundled/lenis.min.js"></script>
    
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

    <div class="about-container extra">
    <div class="use-container">
        <h1>Privacy Policy</h1>
        <span>Last Updated: [17-11-2024]</span>
        <p>SkillTricks ("we", "our", or "us") values your privacy and is committed to protecting your personal information. This Privacy Policy outlines how we collect, use, disclose, and protect your information under the laws of Canada, including the Personal Information Protection and Electronic Documents Act (PIPEDA).</p>
        <hr>
        <h3>1. Scope of This Policy</h3>
        <p>This Privacy Policy applies to all visitors, users, and others ("you") who access or interact with our website at [website URL].</p>
        <hr>
        <h3>2. Information We Collect</h3>
        <p>We collect the following types of information:</p>
        <h5>A. Personal Information</h5>
        <p>Personal information is any information about an identifiable individual, such as:</p>
        <ul>
            <li>Full Name</li>
            <li>Email Address</li>
            <li>Phone Number</li>
            <li>Company Name</li>
            <li>Mailing Address</li>
            <li>Any information you provide via our contact forms</li>
        </ul>
        <h5>B. Non-Personal Information</h5>
        <p>Non-personal information does not identify you as an individual, such as:</p>
        <ul>
            <li>Browser type and version</li>
            <li>IP Address</li>
            <li>Pages visited and time spent on the site</li>
            <li>Referring website</li>
        </ul>
        <h5>C. Cookies and Tracking Technologies</h5>
        <p>We use cookies and similar technologies to collect information about your interactions with our site. (See our Cookies Policy below for details.)</p>
        <hr>
        <h3>3. How We Use Your Information</h3>
        <p>We may use your information for the following purposes:</p>
        <ul>
            <li>To respond to inquiries or service requests.</li>
            <li>To provide and improve our services.</li>
            <li>To communicate with you about updates, promotions, or marketing materials.</li>
            <li>To analyze website usage for improving user experience.</li>
            <li>To comply with legal obligations.</li>
        </ul>
        <hr>
        <h3>4. Sharing Your Information</h3>
        <p>We do not sell your personal information to third parties. However, we may share it under the following circumstances:</p>
        <h5>A. Service Providers</h5>
        <p>We may share your data with third-party providers, such as:</p>
        <ul>
            <li>Email services (e.g., EmailJS, SendGrid)</li>
        </ul>
        <h5>B. Legal Requirements</h5>
        <p>We may disclose your information if required by law or in response to valid legal requests, such as subpoenas or court orders.</p>
        <h5>C. Business Transfers</h5>
        <p>In the event of a merger, acquisition, or sale of assets, your personal information may be transferred as part of the transaction.</p>
        <hr>
        <h3>5. Your Rights Under Canadian Law</h3>
        <p>Under PIPEDA, you have the following rights:</p>
        <ul>
            <li><b>Access and Correction: </b>You can request access to your personal information and correct inaccuracies.</li>
            <li><b>Withdraw Consent: </b>You may withdraw your consent for us to collect, use, or share your data, except where required by law.</li>
            <li><b>Data Portability: </b>Request a copy of your data in a structured, machine-readable format.</li>
        </ul>
        <p>To exercise your rights, contact us at <a href="#">contact@skilltricks.com</a>.</p>
        <hr>
        <h3>6. Data Retention</h3>
        <p>We retain your personal information only for as long as necessary to fulfill the purposes outlined in this policy or to comply with legal obligations.</p>
        <hr>
        <h3>7. Data Security</h3>
        <p>We implement appropriate physical, technical, and administrative safeguards to protect your data from unauthorized access, loss, or misuse.</p>
        <hr>
        <h3>8. Cross-Border Data Transfers</h3>
        <p>Your data may be transferred to and processed in countries outside of Canada. We ensure that appropriate safeguards are in place to protect your data during such transfers.</p>
        <hr>
        <h3>9. Updates to This Policy</h3>
        <p>We may update this Privacy Policy from time to time. Changes will be posted on this page with a "Last Updated" date.</p>
        <hr>
        <h3>10. Contact Us</h3>
        <p>For questions or concerns about this Privacy Policy, please contact us:</p>
        <ul>
            <li><b>Email:</b> <a href="mailto:collaborate@skilltricksinc.com" target="_blank" >collaborate@skilltricksinc.com</a></li>
            <li><b>Phone:</b> <a href="#">(631) 651-8811</a></li>
        </ul>
        <hr>
    </div>

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
    <script src="{{ asset('newfronted/Javascript/animation.js') }}"></script>
</body>
</html>