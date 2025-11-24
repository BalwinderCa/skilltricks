/*const isAboutPage = () => {
    return window.location.pathname.includes('about.html');
};

if (!isAboutPage()) {
gsap.registerPlugin(ScrollTrigger);
gsap.to(".parallax-services", {
    scrollTrigger: {
        trigger: ".parallax-services",
        start: "top bottom",
        end: "bottom top",
        scrub: 1,
        onUpdate: (self) => {
            const progress = self.progress;
            gsap.set(".services-content", {
                y: progress * -100,
                ease: "none"
            });
        }
    }
});

ScrollTrigger.create({
    trigger: ".parallax-services",
    start: "top 80%",
    end: "bottom 0%",
    onEnter: () => {
        gsap.to(".scroll-container", {
            opacity: 0,
            duration: 0.3,
            onComplete: () => {
                document.querySelector('.scroll-container').style.visibility = 'hidden';
            }
        });
    },
    onLeaveBack: () => {
        document.querySelector('.scroll-container').style.visibility = 'visible';
        gsap.to(".scroll-container", {
            opacity: 1,
            duration: 0.3
        });
    }
});


ScrollTrigger.create({
    trigger: ".main-wrapper",
    start: "top top",
    end: "bottom bottom",
    onUpdate: (self) => {
        
    }
});

ScrollTrigger.create({
    trigger: ".parallax-services",
    start: "top bottom",
    end: "bottom top",
    scrub: 1,
    onUpdate: (self) => {
        gsap.set(".parallax-services", {
            y: self.progress * -50
        });
    }
});

ScrollTrigger.create({
    trigger: ".parallax-services",
    start: "top bottom",
    end: "bottom bottom", 
    scrub: 1,
    onUpdate: (self) => {
        gsap.set(".services-content", {
            y: self.progress * -100,
        });
    }
});

ScrollTrigger.create({
    trigger: ".parallax-services",
    start: "center center",
    end: "bottom bottom",
    onUpdate: (self) => {
        const progress = self.progress;
        
        // Only start revealing footer after halfway through services section
        if (progress > 0.5) {
            // Make footer visible
            document.querySelector('.footer-parallax').style.visibility = 'visible';
            
            // Calculate reveal progress
            const revealProgress = (progress - 0.5) * 2; // Convert 0.5-1 to 0-1
            
            gsap.to(".footer-parallax", {
                opacity: revealProgress,
                duration: 0.1,
                ease: "none"
            });
        } else {
            document.querySelector('.footer-parallax').style.visibility = 'hidden';
        }
    }
});
}

const lenis = new Lenis({
    duration: isAboutPage() ? 1 : 1.2,  // Faster duration for about page
    smoothWheel: true,
    wheelMultiplier: 1,
    touchMultiplier: 2,
});

if (!isAboutPage()) {
lenis.on('scroll', ScrollTrigger.update);

gsap.ticker.add((time) => {
    lenis.raf(time * 1000);
});

gsap.ticker.lagSmoothing(0);
}*/