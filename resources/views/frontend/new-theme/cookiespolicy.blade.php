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
                <a class="btn btn-bordered" href="{{ route('writebot.dashboard') }}">StrategicStudio</a>
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
                <a class="btn btn-bordered" href="{{ route('writebot.dashboard') }}">StrategicStudio</a>
            @else
                <a class="btn btn-bordered" href="{{ url('/register') }}">Register</a>
                <a class="btn btn-bordered" href="{{ url('/login') }}">Login</a>
            @endif
    </nav>

    <div class="about-container extra">
    <div class="use-container">
        <h1>Cookies Policy</h1>
        <span>Last Updated: [17-11-2024]</span>
        <p>This Cookies Policy explains how SkillTricks ("we", "our", or "us") uses cookies and similar technologies.</p>
        <hr>
        <h3>1. What Are Cookies?</h3>
        <p>Cookies are small text files stored on your device when you visit a website. They help us understand how you interact with our site and enhance your experience.</p>
        <hr>
        <h3>2. Types of Cookies We Use</h3>
        <p></p>
        <h5>A. Essential Cookies</h5>
        <p>These cookies are necessary for the website to function properly, such as navigation and access to secure areas.</p>
        <h5>B. Analytics Cookies</h5>
        <p>We use these cookies to understand how visitors use our site and improve functionality.</p>
        <h5>C. Marketing Cookies</h5>
        <p>These cookies are used to track your behavior and display relevant ads.</p>
        <hr>
        <h3>3. Managing Cookies</h3>
        <p>You can control or disable cookies through your browser settings. However, disabling certain cookies may limit the functionality of the website.</p>
        <hr>
        <h3>4. Updates to This Policy</h3>
        <p>We may revise this Cookies Policy from time to time. Check this page periodically for updates.</p>
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