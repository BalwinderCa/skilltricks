window.onbeforeunload = function () {
    window.scrollTo(0, 0);
};

gsap.registerPlugin(ScrollTrigger);

class ButtonEffect {
    constructor(button) {
        this.button = button;
        this.canvas = button.querySelector('canvas');
        this.ctx = this.canvas.getContext('2d');
        this.mouse = { x: 0, y: 0 };
        this.radius = 0;
        this.targetRadius = 0;
        
        this.init();
    }

    init() {
        this.resize();
        this.setupEventListeners();
        this.render();
    }

    resize() {
        const rect = this.button.getBoundingClientRect();
        this.canvas.width = rect.width;
        this.canvas.height = rect.height;
    }

    setupEventListeners() {
        this.button.addEventListener('mouseenter', (e) => {
            const rect = this.button.getBoundingClientRect();
            this.mouse.x = e.clientX - rect.left;
            this.mouse.y = e.clientY - rect.top;
            this.targetRadius = Math.max(rect.width, rect.height) * 1.5;
            gsap.to(this, {
                radius: this.targetRadius,
                duration: 1,
                ease: 'power2.out'
            });
        });

        this.button.addEventListener('mouseleave', () => {
            this.targetRadius = 0;
            gsap.to(this, {
                radius: 0,
                duration: 0.6,
                ease: 'power2.out'
            });
        });

        window.addEventListener('resize', () => this.resize());
    }

    render() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        if (this.radius > 0) {
            this.ctx.fillStyle = '#FFFFFF';
            this.ctx.beginPath();
            this.ctx.arc(this.mouse.x, this.mouse.y, this.radius, 0, Math.PI * 2);
            this.ctx.fill();
        }
        
        requestAnimationFrame(() => this.render());
    }
}

document.querySelectorAll('.cta-button').forEach(button => {
    new ButtonEffect(button);
});

document.querySelectorAll('.section-cta').forEach(button => {
    new ButtonEffect(button);
});

const initAnimation = () => {
    const section1 = document.querySelector('#section1');
    section1.style.visibility = 'visible';
    
    gsap.to(section1.querySelector('.text-primary'), {
        clipPath: "inset(0 0% 0 0)",
        transform: "translateX(0)",
        opacity: 1,
        duration: 1,
        ease: "power2.out",
        delay: 0.5
    });

    gsap.to(section1.querySelector('.text-subtitle'), {
        clipPath: "inset(0 0% 0 0)",
        transform: "translateX(0)",
        opacity: 1,
        duration: 1,
        ease: "power2.out",
        delay: 0.5
    });
};

const createSectionTransition = () => {
    let currentSection = 1;

    gsap.timeline({
        scrollTrigger: {
            trigger: ".content-container",
            start: "top top",
            end: "+=200%",
            scrub: false,
            onUpdate: (self) => {
                const progress = self.progress;
                const scrollContainer = document.querySelector('.scroll-container');

                if (progress > 0.66) {
                    scrollContainer.style.opacity = '0';
                    scrollContainer.style.visibility = 'hidden';
                } else {
                    scrollContainer.style.opacity = '1';
                    scrollContainer.style.visibility = 'visible';
                }

                if (progress > 0.66 && currentSection !== 3) {
                    transitionToThirdSection();
                    currentSection = 3;
                } else if (progress > 0.33 && progress <= 0.66 && currentSection !== 2) {
                    transitionToSecondSection();
                    currentSection = 2;
                } else if (progress <= 0.33) {
                    if (currentSection === 2) {
                        transitionToFirstSection();
                        currentSection = 1;
                    } else if (currentSection === 3) {
                        transitionToSecondSection();
                        currentSection = 2;
                    }
                }
            }
        }
    });

    const transitionToThirdSection = () => {
        const section2 = document.querySelector('#section2');
        const section3 = document.querySelector('#section3');
        
        gsap.to(section2.querySelector('.text-primary'), {
            opacity: 0,
            duration: 0.3,
            onComplete: () => {
                section2.style.visibility = 'hidden';
                section3.style.visibility = 'visible';
                
                gsap.fromTo([
                    section3.querySelector('.section-title'),
                    section3.querySelector('.section-description'),
                    ...section3.querySelectorAll('.feature-card'),
                    section3.querySelector('.section-cta')
                ], {
                    opacity: 0,
                    x: 100
                }, {
                    opacity: 1,
                    x: 0,
                    duration: 0.5,
                    stagger: 0.1,
                    ease: "power2.out"
                });
            }
        });
    };

    const transitionToSecondSection = () => {
        const section1 = document.querySelector('#section1');
        const section2 = document.querySelector('#section2');
        const section3 = document.querySelector('#section3');
        
        const currentVisibleSection = section1.style.visibility === 'visible' ? section1 : section3;
        const direction = section1.style.visibility === 'visible' ? 1 : -1;

        if (currentVisibleSection === section3) {
            gsap.to([
                section3.querySelector('.section-title'),
                section3.querySelector('.section-description'),
                ...section3.querySelectorAll('.feature-card'),
                section3.querySelector('.section-cta')
            ], {
                opacity: 0,
                duration: 0.3,
                onComplete: animateSection2
            });
        } else {
            gsap.to(currentVisibleSection.querySelector('.text-primary'), {
                opacity: 0,
                duration: 0.3,
                onComplete: animateSection2
            });
        }

        function animateSection2() {
            currentVisibleSection.style.visibility = 'hidden';
            section2.style.visibility = 'visible';
            
            gsap.fromTo(section2.querySelector('.text-primary'), {
                clipPath: "inset(0 100% 0 0)",
                transform: `translateX(${100 * direction}px)`,
                opacity: 0
            }, {
                clipPath: "inset(0 0% 0 0)",
                transform: "translateX(0)",
                opacity: 1,
                duration: 0.5,
                ease: "power2.out"
            });
        }
    };

    const transitionToFirstSection = () => {
        const section2 = document.querySelector('#section2');
        const section1 = document.querySelector('#section1');
        
        gsap.to(section2.querySelector('.text-primary'), {
            opacity: 0,
            duration: 0.3,
            onComplete: () => {
                section2.style.visibility = 'hidden';
                section1.style.visibility = 'visible';
                
                gsap.fromTo(section1.querySelector('.text-primary'), {
                    clipPath: "inset(0 100% 0 0)",
                    transform: "translateX(-100px)",
                    opacity: 0
                }, {
                    clipPath: "inset(0 0% 0 0)",
                    transform: "translateX(0)",
                    opacity: 1,
                    duration: 0.5,
                    ease: "power2.out"
                });
            }
        });
    };
};

const scrollContainer = document.querySelector('.scroll-icon');
gsap.to(scrollContainer, {
    y: -10,
    duration: 0.5,
    repeat: -1,
    yoyo: true,
    ease: "power1.inOut"
});

document.addEventListener('DOMContentLoaded', () => {
    initAnimation();
    createSectionTransition();
});

const lenis = new Lenis({
    duration: 1.2,
    smoothWheel: true,
    wheelMultiplier: 1,
    touchMultiplier: 2,
});

function raf(time) {
    lenis.raf(time);
    requestAnimationFrame(raf);
}

requestAnimationFrame(raf);