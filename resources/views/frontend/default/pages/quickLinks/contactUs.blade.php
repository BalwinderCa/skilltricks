@extends('frontend.default.layouts.master')

@section('title')
    {{ localize('Contact Us') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection

@section('page-header-title')
    {{ localize('Contact Us') }}
@endsection
<style>
    .bg-theme{background:transparent !important;}
</style>

@section('contents')
    <!--page header-->
    @include('frontend.default.inc.page-header')

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
                        <p>792 W 8th Street,<br> Minot 82884, Canada</p>
                        <p><a href="mailto:contact@skilltricks.com" class="text-white">contact@skilltricks.com</a></p>
                        <p><a href="tel:(631) 651-8811" class="text-white">(631) 651-8811</a></p>
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

      
    </div>
@endsection
