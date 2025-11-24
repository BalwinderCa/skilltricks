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

    <div class="about-container extra">
    <div class="use-container">
        <h1>Terms of Use</h1>
        <span>Last Updated: [Insert Date]</span>
        <p>Welcome to SkillTricks! By accessing or using our website, you agree to comply with these Terms of Use.</p>
        <hr>
        <h3>1. Use of the Website</h3>
        <p>You agree to:</p>
        <ul>
            <li>Use the website only for lawful purposes.</li>
            <li>Refrain from engaging in harmful activities, such as hacking or spreading malware.</li>
        </ul>
        <hr>
        <h3>2. Intellectual Property</h3>
        <p>All content on this website, including text, images, and logos, is the property of SkillTricks. Unauthorized use is prohibited.</p>
        <hr>
        <h3>3. User Submissions</h3>
        <p>By submitting information (e.g., via our contact form), you grant us permission to use this data for business purposes in accordance with our Privacy Policy.</p>
        <hr>
        <h3>4. Limitation of Liability</h3>
        <p>SkillTricks is not liable for damages resulting from the use of this website, including:</p>
        <ul>
            <li>Loss of data</li>
            <li>Service interruptions</li>
            <li>Unauthorized access</li>
        </ul>
        <hr>
        <h3>5. Governing Law</h3>
        <p>These Terms are governed by the laws of Canada.</p>
        <hr>
        <h3>6. Contact Us</h3>
        <p>If you have questions about these Terms, contact us at <a href="mailto:collaborate@skilltricksinc.com" target="_blank" >collaborate@skilltricksinc.com</a>.</p>
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
    <script src="{{ asset('newfronted/Javascript/animation.js') }}"></script>
    <script src="{{ asset('newfronted/Javascript/index.js') }}"></script>
</body>
</html>