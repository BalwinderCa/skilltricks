<!--footer section start-->
<!-- <footer class="tt-footer bg-dark py-3 mt-auto bg-image-header">
    <div class="container">
        <div class="row g-3 align-items-center">
            <div class="col-md-4 order-last order-md-first">
                <div class="copyright text-center text-md-start">
                    <p class="fs-md mb-0">
                        {!! systemSettingsLocalization('copyright_text') !!}
                    </p>
                </div>
            </div>
            <div class="col-md-5">
                <div class="d-flex justify-content-center">
                    @php
                        $quick_links = getSetting('quick_links') != null ? json_decode(getSetting('quick_links')) : [];
                        $pages = \App\Models\Page::whereIn('id', $quick_links)->get();
                    @endphp
                    @foreach ($pages as $page)
                        <a href="{{ route('home.pages.show', $page->slug) }}"
                            class="fs-md">{{ $page->collectLocalization('title') }}</a>
                    @endforeach
                </div>
            </div>
            <div class="col-md-3">
                <form action="{{ route('subscribe.store') }}" method="POST">
                    @csrf
                    <div class="input-group text-end">
                        <input class="form-control" placeholder="{{ localize('Enter Email Address') }}" type="email"
                            name="email" required>
                        <button type="submit" class="btn btn-primary py-2">{{ localize('Subscribe') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</footer> -->
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
            <a href="{{ route('home.pages.aboutUs') }}">Who We Are ↗</a>
            <a href="#">What We Do ↗</a>
            <a href="{{ route('home.pages.contactUs') }}">Contact ↗</a>
        </div>

        <div class="footer-column legal">
            <div>
                <a href="https://aquamarine-rail-506472.hostingersite.com/privacy-policy">Privacy Policy</a>
                <a href="https://aquamarine-rail-506472.hostingersite.com/pages/cookies">Cookies</a>
                <a href="https://aquamarine-rail-506472.hostingersite.com/pages/terms-of-use">Terms of Use</a>
            </div>
            <a href="#" class="back-to-top">Back to Top ↑</a>
        </div>
    </div>
</footer>
<!--footer section end-->
<script></script>