document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('.menu-toggle');
    const mobileNav = document.querySelector('.nav-mobile');

    if (menuToggle && mobileNav) {
        menuToggle.addEventListener('click', () => {
            const isOpen = mobileNav.classList.contains('active');
            mobileNav.classList.toggle('active');
            menuToggle.textContent = isOpen ? 'Menu' : 'Close';
        });
    } else {
        console.error('Menu toggle or mobile navigation element not found in the DOM.');
    }
});

document.addEventListener("DOMContentLoaded", () => {
    const footer = document.querySelector(".footer-parallax");
    const services = document.querySelector(".parallax-services");

    if (footer && services) {
        const servicesHeight = services.offsetHeight;
        footer.style.top = `${servicesHeight + 50}px`;
    }

    if (footer) {
        const totalHeight = document.body.scrollHeight + footer.offsetHeight;
        document.body.style.minHeight = `${totalHeight}px`;
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const allSidebars = document.querySelectorAll('.sidebar');
    const redirectButtons = document.querySelectorAll('.card-redirect');
    const body = document.getElementById('body');

    const toggleBodyScroll = (disable) => {
        if (disable) {
            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
        }
    };

    const closeAllSidebars = () => {
        allSidebars.forEach(sidebar => {
            sidebar.classList.add('close');
        });
        body.classList.remove('overlay');
        toggleBodyScroll(false);
    };

    redirectButtons.forEach((btn, index) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            
            closeAllSidebars();
            
            const sidebarId = `${['digital', 'strategy', 'leadership', 'organizational', 'analytics'][index]}-sidebar`;
            const targetSidebar = document.getElementById(sidebarId);
            
            if (targetSidebar) {
                targetSidebar.classList.remove('close');
                body.classList.add('overlay');
                toggleBodyScroll(true); 
            }
        });
    });

    document.querySelectorAll('.toggle-btn').forEach(btn => {
        btn.addEventListener('click', closeAllSidebars);
    });

    document.addEventListener('click', (e) => {
        if (body.classList.contains('overlay') && 
            !e.target.closest('.sidebar') && 
            !e.target.closest('.card-redirect')) {
            closeAllSidebars();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && body.classList.contains('overlay')) {
            closeAllSidebars();
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.sidebar').forEach(sidebar => {
        sidebar.addEventListener('wheel', (e) => {
            const canScroll = sidebar.scrollHeight > sidebar.clientHeight;
            
            if (canScroll) {
                e.stopPropagation();
                
                const atTop = sidebar.scrollTop === 0;
                const atBottom = sidebar.scrollTop + sidebar.clientHeight === sidebar.scrollHeight;
                
                if ((atTop && e.deltaY < 0) || (atBottom && e.deltaY > 0)) {
                    e.preventDefault();
                }
            }
        }, { passive: false });
    });
});

document.querySelectorAll('.feature-header').forEach(header => {
    header.addEventListener('click', () => {
        const item = header.closest('.feature-item');
        const wasActive = item.classList.contains('active');
        
        document.querySelectorAll('.feature-item').forEach(feature => {
            feature.classList.remove('active');
        });
        
        if (!wasActive) {
            item.classList.add('active');
        }
    });
});

document.querySelectorAll('.mission-links a').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const targetId = link.getAttribute('href').substring(1);
        const targetSection = document.getElementById(targetId);
        
        if (targetSection) {
            lenis.scrollTo(targetSection, {
                duration: 1.2,
                easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t))
            });
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.querySelectorAll('.progress').forEach(bar => {
                    const width = bar.dataset.width;
                    bar.style.width = `${width}%`;
                });

                entry.target.querySelectorAll('[data-value]').forEach(element => {
                    const target = parseInt(element.dataset.value);
                    let current = 0;
                    const duration = 1500;
                    const steps = 60;
                    const increment = target / steps;
                    const interval = duration / steps;

                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            current = target;
                            clearInterval(timer);
                        }
                        element.textContent = Math.round(current) + (element.classList.contains('counter') ? '' : '%');
                    }, interval);
                });

                const circle = entry.target.querySelector('circle.progress');
                if (circle) {
                    setTimeout(() => {
                        const circumference = 2 * Math.PI * 90;
                        const offset = circumference - (45 / 100) * circumference;
                        circle.style.strokeDashoffset = offset;
                    }, 100);
                }

                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.5
    });

    const statsSection = document.querySelector('.stats-section');
    if (statsSection) {
        observer.observe(statsSection);
    }
});

document.addEventListener("DOMContentLoaded", () => {
    function handleJourneySection() {
        const isMobile = window.innerWidth <= 480;
        const steps = document.querySelectorAll(".step");
        const descriptions = document.querySelectorAll(".step-description");

        if (isMobile) {
            steps.forEach((step, index) => {
                const description = descriptions[index];
                if (description) {
                    const clonedDesc = description.cloneNode(true);
                    clonedDesc.classList.remove('active');
                    clonedDesc.style.display = 'block';
                    step.appendChild(clonedDesc);
                }
            });

            const journeyRight = document.querySelector(".journey-right");
            if (journeyRight) {
                journeyRight.style.display = 'none';
            }
        }
    }

    handleJourneySection();
    window.addEventListener('resize', handleJourneySection);

    if (window.innerWidth <= 480) {
        const steps = document.querySelectorAll(".step");
        
        steps.forEach(step => {
            const descriptions = step.querySelectorAll(".step-description");
            
            if (descriptions.length > 1) {
                console.log(descriptions.length)
                for (let i = 1; i < descriptions.length; i++) {
                    descriptions[i].remove();
                }
            }
        });
    }


});

document.addEventListener("DOMContentLoaded", () => {
    const steps = document.querySelectorAll(".steps .step");
    const descriptions = document.querySelectorAll(".journey-right .step-description");
    const journeySection = document.querySelector(".journey-section");

    if (!journeySection) return;

    window.addEventListener("scroll", () => {
        const sectionRect = journeySection.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        const startOffset = viewportHeight * 0.55; 

        if (sectionRect.top <= startOffset && sectionRect.bottom >= viewportHeight) {
            const adjustedTop = sectionRect.top - startOffset;
            const totalScrollDistance = sectionRect.height - startOffset;
            const scrollProgress = Math.abs(adjustedTop) / totalScrollDistance;
            
            const stepCount = steps.length;
            const stepSize = 1 / stepCount;
            const activeIndex = Math.min(
                stepCount - 1,
                Math.floor(scrollProgress / stepSize)
            );

            steps.forEach((step, index) => {
                const isActive = index === activeIndex;
                if (step.classList.contains("active") !== isActive) {
                    step.classList.toggle("active");
                }
            });

            descriptions.forEach((desc, index) => {
                const isActive = index === activeIndex;
                if (desc.classList.contains("active") !== isActive) {
                    desc.classList.toggle("active");
                }
            });
        }
    });

    if (steps.length > 0 && descriptions.length > 0) {
        steps[0].classList.add("active");
        descriptions[0].classList.add("active");
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form');
    if (!form) return;
    
    const submitButton = form.querySelector('button[type="submit"]');
    const inputs = form.querySelectorAll('input:not([type="checkbox"]), textarea');
    const checkbox = form.querySelector('input[type="checkbox"]');

    function validateForm() {
        let isValid = true;
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
            }
        });

        if (!checkbox.checked) {
            isValid = false;
        }

        submitButton.disabled = !isValid;
    }

    inputs.forEach(input => {
        input.addEventListener('input', validateForm);
    });

    checkbox.addEventListener('change', validateForm);

    validateForm();
});

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form');
    if (!form) return;
    
    const submitButton = form.querySelector('button[type="submit"]');
    const checkbox = form.querySelector('input[type="checkbox"]');

    const patterns = {
        name: /^[a-zA-Z\s]{2,30}$/,
        company: /^[a-zA-Z0-9\s&-]{2,50}$/,
        email: /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/,
        phone: /^\+?[\d\s-]{10,}$/
    };

    function validateField(input, showError = false) {
        const field = input.name;
        const value = input.value.trim();
        const formGroup = input.closest('.form-group');
        
        if (!value) {
            if (showError) {
                formGroup.classList.add('error');
            }
            return false;
        }

        if (patterns[field] && !patterns[field].test(value)) {
            if (showError) {
                formGroup.classList.add('error');
            }
            return false;
        }

        formGroup.classList.remove('error');
        return true;
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const inputs = form.querySelectorAll('input:not([type="checkbox"]), textarea');
        let isValid = true;

        inputs.forEach(input => {
            if (!validateField(input, true)) {
                isValid = false;
            }
        });

        if (!checkbox.checked) {
            isValid = false;
        }

        if (isValid) {
            const formData = {
                name: form.name.value.trim(),
                company_name: form.company.value.trim(),
                email: form.email.value.trim(),
                mobile: form.phone.value.trim(),
                message: form.message.value.trim()
            };

            submitButton.disabled = true;

            Swal.fire({
                title: 'Sending Email...',
                html: '<div class="swal-loading"></div>',
                showConfirmButton: false,
                allowOutsideClick: false,
                customClass: {
                    popup: 'custom-swal-popup'
                },
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            emailjs.send("service_yk855mu", "template_8yj18k6", formData)
            .then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Email Sent',
                    text: 'Your message has been sent successfully!',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#36839B',
                    backdrop: `rgba(0,0,0,0.5)`,
                    showClass: {
                        popup: `
                          animate__animated
                          animate__fadeInUp
                          animate__faster
                        `
                    },
                    hideClass: {
                        popup: `
                            animate__animated
                            animate__fadeOutDown
                            animate__faster
                    `
                    },
                    customClass: {
                        popup: 'custom-swal-popup'
                    }
                });
                form.reset();
            })
            .catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to send email. Please try again later.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#36839B',
                    backdrop: `rgba(0,0,0,0.5)`,
                    showClass: {
                        popup: `
                          animate__animated
                          animate__fadeInUp
                          animate__faster
                        `
                    },
                    hideClass: {
                        popup: `
                            animate__animated
                            animate__fadeOutDown
                            animate__faster
                    `
                    },
                    customClass: {
                        popup: 'custom-swal-popup'
                    }
                });
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Data',
                text: 'Please fill out all fields correctly before submitting.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#36839B',
                backdrop: `rgba(0,0,0,0.5)`,
                    showClass: {
                        popup: `
                          animate__animated
                          animate__fadeInUp
                          animate__faster
                        `
                    },
                    hideClass: {
                        popup: `
                            animate__animated
                            animate__fadeOutDown
                            animate__faster
                    `
                    },
                    customClass: {
                        popup: 'custom-swal-popup'
                    }
            });
        }
    });

    function updateSubmitButton() {
        const inputs = form.querySelectorAll('input:not([type="checkbox"]), textarea');
        let isComplete = true;

        inputs.forEach(input => {
            if (!input.value.trim()) {
                isComplete = false;
            }
        });

        if (!checkbox.checked) {
            isComplete = false;
        }

        submitButton.disabled = !isComplete;
    }

    form.querySelectorAll('input:not([type="checkbox"]), textarea').forEach(input => {
        input.addEventListener('input', updateSubmitButton);
    });

    checkbox.addEventListener('change', updateSubmitButton);

    updateSubmitButton();
});

document.addEventListener('DOMContentLoaded', function() {
    const track = document.querySelector('.testimonials-track');
    const cards = document.querySelectorAll('.testimonial-card');
    const prevButton = document.querySelector('.nav-button.prev');
    const nextButton = document.querySelector('.nav-button.next');
    
    if (!track || !cards || cards.length === 0) return;
    
    let currentIndex = 1;
    let isAnimating = false;

    const firstCardClone = cards[0].cloneNode(true);
    const lastCardClone = cards[cards.length - 1].cloneNode(true);
    track.appendChild(firstCardClone);
    track.insertBefore(lastCardClone, cards[0]);

    function updateCards(smooth = true) {
        if (isAnimating && smooth) return;
        isAnimating = smooth;

        track.style.transition = smooth ? 'transform 0.3s ease' : 'none';

        const allCards = track.querySelectorAll('.testimonial-card');
        allCards.forEach(card => card.classList.remove('active'));
        allCards[currentIndex + 1].classList.add('active');

        const cardWidth = cards[0].offsetWidth;
        const gap = 24;
        const wrapperWidth = track.parentElement.offsetWidth;
        const translation = -(currentIndex + 1) * (cardWidth + gap) + (wrapperWidth - cardWidth) / 2;
        
        track.style.transform = `translateX(${translation}px)`;

        if (smooth) {
            setTimeout(() => {
                const totalRealCards = cards.length;
                if (currentIndex === totalRealCards) {
                    currentIndex = 0;
                    updateCards(false);
                } else if (currentIndex === -1) {
                    currentIndex = totalRealCards - 1;
                    updateCards(false);
                }
                isAnimating = false;
            }, 300);
        }
    }

    updateCards(false);

    function moveToNext() {
        if (isAnimating) return;
        currentIndex++;
        updateCards(true);
    }

    function moveToPrev() {
        if (isAnimating) return;
        currentIndex--;
        updateCards(true);
    }

    nextButton.addEventListener('click', moveToNext);
    prevButton.addEventListener('click', moveToPrev);

    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            isAnimating = false;
            updateCards(false);
        }, 100);
    });

    let autoplayInterval = setInterval(moveToNext, 2000);

    track.parentElement.addEventListener('mouseenter', () => {
        clearInterval(autoplayInterval);
    });

    track.parentElement.addEventListener('mouseleave', () => {
        if (!isAnimating) {
            autoplayInterval = setInterval(moveToNext, 2000);
        }
    });

    let touchStartX = 0;
    let touchEndX = 0;

    track.addEventListener('touchstart', e => {
        clearInterval(autoplayInterval);
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    track.addEventListener('touchend', e => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
        if (!isAnimating) {
            autoplayInterval = setInterval(moveToNext, 2000);
        }
    }, { passive: true });

    function handleSwipe() {
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;

        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                moveToNext();
            } else {
                moveToPrev();
            }
        }
    }
});

class Chatbot {
    constructor() {
        this.container = document.querySelector('.chatbot-container');
        this.toggleButton = document.querySelector('.chatbot-toggle');
        this.messagesContainer = document.querySelector('.chat-messages');
        this.currentStep = 0;
        this.userResponses = {};
        this.isOpen = false;
        this.chatStarted = false; 
        this.isScrollable = true;
        this.setupScrollHandling();
        this.typingIndicator = this.container.querySelector('.typing-indicator');
        this.pendingQuestion = null;

        this.validationMessages = {
            name: "Please enter a valid name",
            company: "Company name is required",
            email: "Please enter a valid email address",
            phone: "Please enter a valid phone number (minimum 10 digits)"
        };

        this.emailConfig = {
            serviceID: 'service_yk855mu',    
            templateID: 'template_bbwu4l1',  
            publicKey: 'dPiUhN5PJAjdP2xAZ'    
        };

        this.chatFlow = [
            {
                type: 'input',
                message: "Hi! I'm the SkillTricks Assistant. What's your name?",
                field: 'name',
                validation: (value) => value.length > 0
            },
            {
                type: 'input',
                message: "Which country are you based in?",
                field: 'location',
                validation: (value) => value.length > 0
            },
            {
                type: 'input',
                message: 'Please share your business email:',
                field: 'email',
                validation: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
            },
            {
                type: 'input',
                message: 'What is your company/organization name?',
                field: 'company',
                validation: (value) => value.length > 0
            },
            {
                type: 'input',
                message: 'Your phone number (optional):',
                field: 'phone',
                validation: (value) => value === '' || /^\d{10,}$/.test(value)
            },
            {
                type: 'options',
                message: 'Select your industry:',
                options: [
                    'Healthcare',
                    'Education & Training', 
                    'Technology',
                    'Financial Services',
                    'Manufacturing',
                    'Retail & E-commerce',
                    'Professional Services',
                    'Real Estate',
                    'Hospitality & Travel',
                    'Logistics & Transportation',
                    'Energy & Utilities',
                    'Non-Profit/NGO',
                    'Media & Entertainment', 
                    'Government',
                    'Agriculture & Food',
                    'Pharmaceuticals & Biotechnology',
                    'Consumer Goods',
                    'Telecommunications',
                    'Automotive',
                    'Other (please specify)'
                ],
                requiresInput: 'Other (please specify)'
            },
            {
                type: 'options',
                message: 'What is your company size?',
                options: [
                    '1-10 employees',
                    '11-50 employees',
                    '51-200 employees',
                    '201-500 employees',
                    '501+ employees'
                ]
            },
            {
                type: 'options',
                message: 'What is your primary business goal?',
                options: [
                    'Scaling operations',
                    'Improving team alignment',
                    'Increasing profitability',
                    'Entering new markets',
                    'Enhancing data-driven decision-making'
                ]
            },         
            {
                type: 'options',
                message: 'What is your preferred follow-up method?',
                options: [
                    'Email',
                    'Phone',
                    'No Immediate Follow-Up'
                ]
            },         
            {
                type: 'options',
                message: 'Welcome to SkillTricks! We empower leaders to make smarter, faster, and more aligned decisions through actionable insights. Let’s explore how we can help you achieve your goals. Where would you like to start?',
                options: [
                    'I want to align my strategy with execution.',
                    'I need help making better data-driven decisions.',
                    'I’m curious how SkillTricks can help me.'
                ]
            },         
            {
                type: 'options',
                message: 'Great! Let’s uncover your priorities. Which of these leadership challenges resonate with you most?',
                options: [
                    'Aligning operational decisions with strategic goals.',
                    'Breaking down data silos for better decision-making.',
                    'Making consistent and confident decisions under uncertainty.',
                    'All of the above.'
                ]
            },         
            {
                type: 'options',
                message: 'Understood. What’s your primary goal right now?',
                options: [
                    'Improving my team’s alignment with business goals.',
                    'Optimizing resource allocation for better ROI.',
                    'Exploring new growth opportunities (e.g., markets, products).',
                    'Improving our agility in decision-making.'
                ]
            },         
            {
                type: 'options',
                message: `SkillTricks provides four actionable insights to help you:
                        - Decision Dashboards: Visualize outcomes of key decisions.
                        - Lead Conversion Reports: Maximize growth opportunities.
                        - Resource Optimizers: Prioritize for maximum ROI.
                        - Competitor Insights: Benchmark and stay ahead.

                        Which of these would be most valuable for you?`,
                options: [
                    'All of them sound valuable!',
                    'I want to visualize and simulate decisions.',
                    'I need help optimizing my resources.',
                    'I’d like to benchmark against competitors.'
                ]
            },         
            {
                type: 'options',
                message: 'How are you currently addressing these challenges?',
                options: [
                    'We’re relying on manual methods and spreadsheets.',
                    'Using basic tools like CRM or analytics dashboards.',
                    'We’ve invested in some systems but lack integrated insights.',
                    'We don’t have a clear solution in place.'
                ]
            },            
            {
                type: 'options',
                message: `Thank you for sharing! Based on your responses, SkillTricks can help by providing a unified platform for smarter decisions. Here’s how we typically help leaders like you:
                    •	Align strategies with execution for measurable results.
                    •	Turn fragmented data into actionable insights.
                    •	Boost decision confidence with real-time simulations.
                    Would you like to schedule a demo or explore case studies on how we’ve helped others?
                        `,
                options: [
                    'Schedule a Demo.',
                    'Explore Case Studies.',
                    'Let’s connect—I have more questions.'
                ]
            },        
            {
                type: 'options',
                message: 'Great choice! Signing up takes just a moment to unlock tailored insights and access your SkillTricks dashboard. Shall we get started?',
                options: [
                    {
                        text: "Yes, let's sign up!",
                        link: 'https://calendly.com/collaborate-skilltricksinc/30min'
                    },
                    "I'd like to learn more before signing up"
                ]
            }   
        ];

        this.checkSessionType();
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadChatState();

        this.messagesContainer.addEventListener('scroll', () => {
            const threshold = 100; 
            const isNearBottom = this.messagesContainer.scrollHeight - this.messagesContainer.scrollTop - this.messagesContainer.clientHeight < threshold;
            this.isScrollable = isNearBottom;
        });
    }

    setupEventListeners() {
        this.toggleButton.addEventListener('click', () => this.toggleChat());
        document.querySelector('.close-chat').addEventListener('click', () => this.closeChat());
        document.querySelector('.restart-chat').addEventListener('click', () => this.restartChat());
    }

    showTypingIndicator() {
        this.typingIndicator.style.display = 'flex';
        this.scrollToBottom(true);
    }

    hideTypingIndicator() {
        this.typingIndicator.style.display = 'none';
    }

    setupScrollHandling() {
        this.messagesContainer.addEventListener('wheel', (e) => {
            const isScrollingPossible = 
                (this.messagesContainer.scrollTop > 0 && e.deltaY < 0) || 
                (this.messagesContainer.scrollTop < (this.messagesContainer.scrollHeight - this.messagesContainer.clientHeight) && e.deltaY > 0); 

            if (isScrollingPossible) {
                e.stopPropagation();
            }
        }, { passive: true });

        this.messagesContainer.addEventListener('touchstart', (e) => {
            this.touchStartY = e.touches[0].clientY;
        }, { passive: true });

        this.messagesContainer.addEventListener('touchmove', (e) => {
            if (!this.touchStartY) {
                return;
            }

            const touchY = e.touches[0].clientY;
            const scrollTop = this.messagesContainer.scrollTop;
            const scrollHeight = this.messagesContainer.scrollHeight;
            const clientHeight = this.messagesContainer.clientHeight;

            const isScrollingUp = touchY > this.touchStartY;
            const isScrollingDown = touchY < this.touchStartY;

            const canScrollUp = scrollTop > 0;
            const canScrollDown = scrollTop < (scrollHeight - clientHeight);

            if ((isScrollingUp && canScrollUp) || (isScrollingDown && canScrollDown)) {
                e.stopPropagation();
            }
        }, { passive: false });

        this.messagesContainer.addEventListener('touchend', () => {
            this.touchStartY = null;
        }, { passive: true });
    }

    preventBodyScroll(prevent) {
        document.body.style.overflow = prevent ? 'hidden' : '';
    }

    toggleChat() {
        this.isOpen = !this.isOpen;
        this.container.classList.toggle('active');
        
        if (this.isOpen) {
            const existingOptions = this.messagesContainer.querySelectorAll('.option-buttons');
            existingOptions.forEach(opt => opt.remove());
            
            this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
            
            if (!this.chatStarted) {
                this.startChat();
            } else if (this.pendingQuestion) {
                if (this.chatFlow[this.currentStep].type === 'options') {
                    this.displayOptions(this.chatFlow[this.currentStep].options);
                } else if (this.chatFlow[this.currentStep].type === 'input') {
                    this.enableTextInput();
                }
            }
        }
    }

    closeChat() {
        this.saveScrollPosition(this.messagesContainer.scrollTop);
        this.isOpen = false;
        this.container.classList.remove('active');
        this.saveChatState();
    }

    restartChat() {
        this.currentStep = 0;
        this.userResponses = {};
        this.chatStarted = false;
        this.messagesContainer.innerHTML = '';

        const input = document.querySelector('.input-container input');
        input.value = '';
        input.disabled = true;
        document.querySelector('.send-message').disabled = true;

        this.startChat();
    }

    startChat() {
        if (!this.chatStarted) {
            this.chatStarted = true;
            const firstQuestion = this.chatFlow[0].message;
            this.pendingQuestion = firstQuestion;
            this.saveChatState();
            
            this.displayBotMessage(firstQuestion).then(() => {
                if (this.chatFlow[0].type === 'input') {
                    this.enableTextInput();
                } else {
                    this.displayOptions(this.chatFlow[0].options);
                }
            });
        }
    }

    async displayBotMessage(message) {
        this.showTypingIndicator();

        const typingDuration = Math.random() * 1000 + 1000;
        await new Promise(resolve => setTimeout(resolve, typingDuration));

        this.hideTypingIndicator();

        const messageDiv = document.createElement('div');
        messageDiv.className = 'message bot-message';
        messageDiv.textContent = message;
        this.messagesContainer.appendChild(messageDiv);
        this.scrollToBottom(true);
    }

    displayUserMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message user-message';
        messageDiv.textContent = message;
        this.messagesContainer.appendChild(messageDiv);
        this.scrollToBottom(true);
    }

    displayOptions(options) {
        const optionsDiv = document.createElement('div');
        optionsDiv.className = 'option-buttons';
        
        options.forEach(option => {
            const button = document.createElement('button');
            button.className = 'option-button';
            
            if (typeof option === 'object' && option.link) {
                button.textContent = option.text;
                button.addEventListener('click', () => this.handleResponse(option));
            } else {
                button.textContent = option;
                button.addEventListener('click', () => {
                    if (this.chatFlow[this.currentStep]?.requiresInput && option === this.chatFlow[this.currentStep]?.requiresInput) {
                        this.clearOptions();
                        this.enableTextInput();
                        this.pendingQuestion = 'Please specify your industry:'; // Set the prompt for input
                    } else {
                        this.handleResponse(option);
                    }
                });
            }
            optionsDiv.appendChild(button);
        });
        
        this.messagesContainer.appendChild(optionsDiv);
        this.scrollToBottom(true);
    }   

    clearOptions() {
        const existingOptions = this.messagesContainer.querySelectorAll('.option-buttons');
        existingOptions.forEach(opt => opt.remove());
    }

    checkSessionType() {
        if (performance.navigation.type === performance.navigation.TYPE_RELOAD) {
            localStorage.removeItem('chatState');
            this.currentStep = 0;
            this.userResponses = {};
            this.chatStarted = false;
        }
    }

    enableTextInput() {
        const input = document.querySelector('.input-container input');
        const sendButton = document.querySelector('.send-message');
        
        input.disabled = false;
        sendButton.disabled = false;
        input.focus();
    
        const handleSend = () => {
            const value = input.value.trim();
            const currentField = this.chatFlow[this.currentStep]?.field || this.pendingQuestion;
    
            if (currentField === 'phone' && value === '') {
                // Set "Not Provided" for optional phone number
                this.handleResponse('Not Provided');
                input.value = '';
                input.disabled = true;
                sendButton.disabled = true;
                return;
            }

            // Handle the "Other (please specify)" scenario
            if (this.pendingQuestion && this.pendingQuestion.startsWith("Please specify")) {
                if (value.length === 0) {
                    this.displayBotMessage("Please provide a valid response.");
                    return;
                }
                this.handleResponse(value);
                input.value = '';
                input.disabled = true;
                sendButton.disabled = true;
                return;
            }
    
            // Validate input normally
            if (this.chatFlow[this.currentStep]?.validation(value)) {
                this.handleResponse(value);
                input.value = '';
                input.disabled = true;
                sendButton.disabled = true;
            } else {
                this.displayBotMessage(this.validationMessages[currentField] || "Invalid input, please try again.");
            }
        };
    
        input.onkeypress = (e) => {
            if (e.key === 'Enter') handleSend();
        };
        sendButton.onclick = handleSend;
    }    

    handleResponse(response) {
        if (typeof response === 'object' && response.link) {
            window.open(response.link, '_blank');
            this.displayUserMessage(response.text);
            this.finishChat();
            return;
        }
    
        const currentQuestion = this.chatFlow[this.currentStep];
        this.displayUserMessage(response);
        this.userResponses[currentQuestion.field || `question_${this.currentStep}`] = response;

        if (currentQuestion.field === 'phone') {
            // If phone number is not provided, remove the "Phone" follow-up option
            const followUpQuestion = this.chatFlow.find(q => q.message === 'What is your preferred follow-up method?');
            if (followUpQuestion) {
                if (response === 'Not Provided') {
                    followUpQuestion.options = followUpQuestion.options.filter(opt => opt !== 'Phone');
                } else if (!followUpQuestion.options.includes('Phone')) {
                    // Add "Phone" back if it's missing and a phone number is provided
                    followUpQuestion.options.push('Phone');
                }
            }
        }

        this.pendingQuestion = null;
    
        const existingOptions = this.messagesContainer.querySelectorAll('.option-buttons');
        existingOptions.forEach(opt => opt.remove());
    
        this.currentStep++;
    
        if (this.currentStep >= this.chatFlow.length) {
            this.finishChat();
            return;
        }
    
        const nextQuestion = this.chatFlow[this.currentStep];
        setTimeout(() => {
            this.displayBotMessage(nextQuestion.message).then(() => {
                if (nextQuestion.type === 'options') {
                    this.displayOptions(nextQuestion.options);
                } else if (nextQuestion.type === 'input') {
                    this.enableTextInput();
                }
            });
        }, 500);
    }
    
    async sendTranscript() {
        try {
            const transcript = this.formatTranscript();

            const templateParams = {
                name: this.userResponses.name,
                company_name: this.userResponses.company,
                email: this.userResponses.email,
                mobile: this.userResponses.phone,
                message: transcript
            };

            await emailjs.send(
                this.emailConfig.serviceID,
                this.emailConfig.templateID,
                templateParams,
                this.emailConfig.publicKey
            );

            console.log('Chat transcript sent successfully!');
        } catch (error) {
            console.error('Failed to send chat transcript:', error);
        }
    }

    formatTranscript() {
        let transcript = 'Chat Transcript:\n\n';
        
        transcript += 'User Information:\n';
        transcript += `Name: ${this.userResponses.name || 'Not Provided'}\n`;
        transcript += `Company: ${this.userResponses.company || 'Not Provided'}\n`;
        transcript += `Email: ${this.userResponses.email || 'Not Provided'}\n`;
        transcript += `Phone: ${this.userResponses.phone || 'Not Provided'}\n\n`;
        
        transcript += 'Chat Responses:\n';
        this.chatFlow.forEach((step, index) => {
            if (index < this.currentStep) {
                transcript += `Q: ${step.message}\n`;
                transcript += `A: ${this.userResponses[step.field || `question_${index}`]}\n\n`;
            }
        });

        return transcript;
    }

    async finishChat() {
        await this.displayBotMessage("Thank you for chatting with us! We'll contact you soon with more information.");
        this.sendTranscript();
    }

    saveChatState() {
        const state = {
            currentStep: this.currentStep,
            userResponses: this.userResponses,
            messages: this.messagesContainer.innerHTML,
            chatStarted: this.chatStarted,
            pendingQuestion: this.pendingQuestion,
            scrollPosition: this.messagesContainer.scrollTop,
            timestamp: new Date().getTime()
        };
        
        sessionStorage.setItem('chatState', JSON.stringify(state));
        localStorage.setItem('chatState', JSON.stringify(state));
    }

    loadChatState() {
        let savedState = sessionStorage.getItem('chatState');
        
        if (!savedState) {
            savedState = localStorage.getItem('chatState');
        }

        if (savedState) {
            const state = JSON.parse(savedState);
            const isCurrentSession = sessionStorage.getItem('chatState') !== null;
            
            if (isCurrentSession) {
                this.currentStep = state.currentStep;
                this.userResponses = state.userResponses;
                this.messagesContainer.innerHTML = state.messages;
                this.chatStarted = state.chatStarted;
                this.pendingQuestion = state.pendingQuestion;
                this.hideTypingIndicator();

                const existingOptions = this.messagesContainer.querySelectorAll('.option-buttons');
                existingOptions.forEach(opt => opt.remove());

                if (this.pendingQuestion) {
                    setTimeout(() => {
                        this.displayBotMessage(this.pendingQuestion).then(() => {
                            if (this.chatFlow[this.currentStep].type === 'options') {
                                this.displayOptions(this.chatFlow[this.currentStep].options);
                            } else if (this.chatFlow[this.currentStep].type === 'input') {
                                this.enableTextInput();
                            }
                        });
                    }, 500);
                } else if (this.currentStep < this.chatFlow.length) {
                    if (this.chatFlow[this.currentStep].type === 'options') {
                        this.displayOptions(this.chatFlow[this.currentStep].options);
                    } else if (this.chatFlow[this.currentStep].type === 'input') {
                        this.enableTextInput();
                    }
                }
            }
        }
    }

    validateInput(type, value) {
        switch(type) {
            case 'name':
                return value.length >= 2 && /^[a-zA-Z\s'-]+$/.test(value);
            case 'company':
                return value.length >= 2;
            case 'email':
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
            case 'phone':
                return /^\d{10,}$/.test(value.replace(/[-()\s]/g, ''));
            default:
                return true;
        }
    }

    scrollToBottom(force = false) {
        if (this.isScrollable || force) {
            this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
        }
    }

    saveScrollPosition(position) {
        this.lastScrollPosition = position;
        const state = JSON.parse(sessionStorage.getItem('chatState') || '{}');
        state.scrollPosition = position;
        sessionStorage.setItem('chatState', JSON.stringify(state));
    }

    restoreScrollPosition() {
        const state = JSON.parse(sessionStorage.getItem('chatState') || '{}');
        if (state.scrollPosition) {
            this.messagesContainer.scrollTop = state.scrollPosition;
        } else {
            this.scrollToBottom();
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new Chatbot();
});

document.querySelector('.back-to-top').addEventListener('click', (e) => {
    e.preventDefault();
    lenis.scrollTo(0, {
        duration: 1.2,
        easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t))
    });
});