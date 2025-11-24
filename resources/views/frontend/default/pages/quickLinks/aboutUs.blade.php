@extends('frontend.default.layouts.master')

@section('title')
    {{ localize('About Us') }} {{ getSetting('title_separator') }} {{ getSetting('system_title') }}
@endsection


@section('page-header-title')
    {{ localize('About Us') }}
@endsection

<style>
    .bg-theme{background:transparent !important;}
</style>
@section('contents')
    <!--page header-->
    @include('frontend.default.inc.page-header')

    <div class="about-container">
        <div class="globe-container"></div>
        <section class="hero-section">
            <div class="round-border-block">
                <span class="round-animtion"></span>
                <h1 class="h1-ttl text-primary text-white mt-0">We are growth catalysts 
                    specializing in strategic innovation and intelligent solutions</h1>
            </div>
        </section>
        
        <div class="transform-highlight">
            <p class="highlight-text">
                <span class="emphasis">We are more than a platform; we are leading a movement to empower leaders and organizations worldwide.</span>
                <p>Whether adapting, growing, optimizing, innovating, or leading, SkillTricks is your partner in success in a dynamic business environment.</p>
                <a href="https://calendly.com/collaborate-skilltricksinc/30min" target="_blank" class="cta-button transform">
                    <span>Transform With Us</span>
                    <canvas class="hover-effect"></canvas>
                </a>
            </p>
        </div>

        <section class="mission-section" id="mission">
            <div class="mission-links">
                <a href="#mission">Our Mission</a>
                <a href="#what">What We Do</a>
                <a href="#choose">Why Choose Us</a>
            </div>
            
            <div class="mission-content">
                <p>
                    At SkillTricks, we envision a world where organizations continuously evolve and thrive. Our mission is to drive meaningful, measurable impact by aligning strategic goals with intelligent solutions, empowering leaders to navigate complex challenges with confidence.
                </p>
                <p>
                    We believe in the power of combining human expertise with AI-driven insights to create transformative strategies. By fostering a culture of continuous improvement and strategic innovation, we help organizations not just adapt to change, but lead it.
                </p>
            </div>
        </section>

        <section class="features-section" id="what">
            <div class="services-section bg-theme white-bg-section">
                <div class="gray">
                    <div class="container">
                        <div class="head-block">
                            <h4>SkillTricks delivers innovative AI-powered strategic <br>
                                solutions for organizations seeking transformative <br>
                                growth and sustainable success.</h4>
                        </div>
                        <div class="row gx-0">
                            <div class="col-lg-6">
                                
                            </div>
                            <div class="col-lg-6">
                                <div class="txt">
                                    <p>Through StrategyStudio, we provide a platform that offers customized, dialogue-based strategies, helping leaders overcome disruptions, accelerate growth, and build resilient organizations. Designed with versatility in mind, StrategyStudio adapts to your unique needs, making it the ideal partner for long-term success.</p>
                                    <div class="btn-block">
                                        <a href="{{ route('home.pages.contactUs') }}" class="btn btn-bordered ">Contact Us</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="video-section">
            <div class="video-container">
                <video 
                    autoplay
                    muted
                    loop
                    playsinline
                    width="100%"
                    height="100%"
                >
                <source src="https://login2design.in/htmls/skilltricksinc-new/assets/images/video.qt" type="video/mp4">        
        </section>
        
        <section class="features-section" id="choose">
            <div class="services-section why-choose-section w-100">
                <div class="white">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-10 mx-auto">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="ttl-block">
                                            <h4 class="ttl">Why Choose Us</h4>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 accordion-tab">
                                        <div class="accordion-sec">
                                            <div class="accordion" id="accordionPanelsStayOpenExample">
                                                <div class="accordion-item">
                                                  <h2 class="accordion-header" id="panelsStayOpen-headingOne">
                                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true" aria-controls="panelsStayOpen-collapseOne">
                                                        <sup>1.</sup> Innovative Thinking
                                                    </button>
                                                  </h2>
                                                  <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse show" aria-labelledby="panelsStayOpen-headingOne">
                                                    <div class="accordion-body">
                                                        <p>Lorem ipsum dolor sit amet.</p>
                                                    </div>
                                                  </div>
                                                </div>
                                                <div class="accordion-item">
                                                  <h2 class="accordion-header" id="panelsStayOpen-headingTwo">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseTwo" aria-expanded="false" aria-controls="panelsStayOpen-collapseTwo">
                                                        <sup>2.</sup> AI-Driven Insights
                                                    </button>
                                                  </h2>
                                                  <div id="panelsStayOpen-collapseTwo" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-headingTwo">
                                                    <div class="accordion-body">
                                                        <p>Lorem ipsum dolor sit amet.</p>
                                                    </div>
                                                  </div>
                                                </div>
                                                <div class="accordion-item">
                                                  <h2 class="accordion-header" id="panelsStayOpen-headingThree">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseThree" aria-expanded="false" aria-controls="panelsStayOpen-collapseThree">
                                                        <sup>3.</sup>Long-Term Partnership
                                                    </button>
                                                  </h2>
                                                  <div id="panelsStayOpen-collapseThree" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-headingThree">
                                                    <div class="accordion-body">
                                                      <p>We’re more than a service; we’re a strategic partner dedicated to your sustained success.</p>
                                                    </div>
                                                  </div>
                                                </div>
                                              </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="quote-section">
            <div class="quote-info">
                <h2>Our Mission & Vision</h2>
            </div>
            <div class="quote-container">
                <div class="quote-card">
                    <i class="fa-solid fa-quote-left quote-icon"></i>
                    <h3>Our Vision</h3>
                    <p>To Revolutionize Leadership, Globally.</p>
                    <div class="quote-footer">
                        <i class="fa-solid fa-quote-right quote-icon"></i>
                    </div>
                </div>
        
                <div class="quote-card">
                    <i class="fa-solid fa-quote-left quote-icon"></i>
                    <h3>Our Mission</h3>
                    <p>Our mission is to drive meaningful, measurable impact by aligning strategic goals with intelligent solutions, empowering leaders to navigate complex challenges with confidence.</p>
                    <div class="quote-footer">
                        <i class="fa-solid fa-quote-right quote-icon"></i>
                    </div>
                </div>
            </div>
        </section>

       
    </div>
@endsection
