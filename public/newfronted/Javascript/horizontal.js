let lottieInstances = {};
let currentIndex = 0;

const initCardsNavigation = () => {
    const cardsContainer = document.querySelector('.services-cards');
    const cards = document.querySelectorAll('.service-card');
    const prevBtn = document.querySelector('.nav-arrow.prev');
    const nextBtn = document.querySelector('.nav-arrow.next');
    let currentIndex = 0;

    const updateCards = () => {
        const isMobile = window.innerWidth <= 768;
        
        cards.forEach((card, index) => {
            card.classList.remove('focused', 'unfocused');
            if (index === currentIndex) {
                card.classList.add('focused');
            } else {
                card.classList.add('unfocused');
            }
        });

        if (isMobile) {
            const cardWidth = cards[0].offsetWidth + 20;
            const scrollPosition = currentIndex * cardWidth;
    
            gsap.to(cardsContainer, {
                scrollLeft: scrollPosition,
                duration: 0.5,
                ease: "power2.out",
            });
        } 
        
        else {
            const containerWidth = cardsContainer.offsetWidth;
            const cardWidth = 380;
            const focusedWidth = 570;
            const gap = 30;
            const totalCards = cards.length;

            let baseOffset = (containerWidth / 2) - (focusedWidth / 2);
            let additionalOffset = currentIndex * (cardWidth + gap);
            
            if (currentIndex > 0) {
                additionalOffset += (focusedWidth - cardWidth) * (currentIndex);
            }

            const totalWidth = (totalCards - 1) * (cardWidth + gap) + focusedWidth;
            const maxAllowedOffset = (totalWidth - 80) - containerWidth;
            let finalOffset = additionalOffset - baseOffset;

            if (currentIndex >= totalCards - 2) {
                finalOffset = Math.min(finalOffset, maxAllowedOffset);
                if (currentIndex === totalCards - 1) {
                    finalOffset = maxAllowedOffset;
                }
            }

            gsap.to(cardsContainer, {
                duration: 0.5,
                x: -Math.max(0, finalOffset),
                ease: "power2.out"
            });
        }

        if (prevBtn) prevBtn.style.opacity = currentIndex === 0 ? '0.5' : '1';
        if (nextBtn) nextBtn.style.opacity = currentIndex === cards.length - 1 ? '0.5' : '1';
    };

    prevBtn?.addEventListener('click', () => {
        if (currentIndex > 0) {
            currentIndex--;
            updateCards();
        }
    });

    nextBtn?.addEventListener('click', () => {
        if (currentIndex < cards.length - 1) {
            currentIndex++;
            updateCards();
        }
    });

    window.addEventListener('resize', () => {
        updateCards();
    });

    updateCards();
};

const initLottieAnimations = () => {
    Object.values(lottieInstances).forEach(anim => {
        if (anim) {
            anim.destroy();
        }
    });
    lottieInstances = {};

    const animations = {
        AILottie: '../JSON/AI.json',
        StrategyLottie: '../JSON/Strategy.json',
        LeadershipLottie: '../JSON/Leadership.json',
        GrowthLottie: '../JSON/Growth.json',
        AnalyticsLottie: '../JSON/Analytics.json'
    };

    Object.entries(animations).forEach(([id, path], index) => {
        const container = document.getElementById(id);
        if (container) {
            container.innerHTML = '';
            
            const anim = lottie.loadAnimation({
                container: container,
                renderer: 'svg',
                loop: true,
                autoplay: false,
                path: path,
                rendererSettings: {
                    preserveAspectRatio: 'xMidYMid slice'
                }
            });

            lottieInstances[id] = anim;

            const card = container.closest('.service-card');
            if (card) {
                if (card.classList.contains('focused')) {
                    anim.play();
                }

                const updateColor = () => {
                    const svg = container.querySelector('svg');
                    if (svg && (index === 0 || index === 1)) {
                        if (card.classList.contains('focused')) {
                            svg.style.filter = 'brightness(0) invert(1)';
                        } else {
                            svg.style.filter = 'brightness(0)';
                        }
                    }
                };

                if (index === 0 || index === 1) {
                    anim.addEventListener('DOMLoaded', updateColor);
                }

                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        if (mutation.attributeName === 'class') {
                            const anim = lottieInstances[id];
                            if (anim) {
                                if (card.classList.contains('focused')) {
                                    anim.play();
                                } else {
                                    anim.pause();
                                }
                                if (index === 0 || index === 1) {
                                    updateColor();
                                }
                            }
                        }
                    });
                });

                observer.observe(card, {
                    attributes: true
                });
            }
        }
    });
};

const addColorStyles = () => {
    const style = document.createElement('style');
    style.textContent = `
        .services-cards .service-card:nth-child(-n+2).focused .lottie-container svg {
            filter: brightness(0) invert(1) !important;
        }
        .services-cards .service-card:nth-child(-n+2).unfocused .lottie-container svg {
            filter: brightness(0) !important;
        }
    `;
    document.head.appendChild(style);
};

let resizeTimeout;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
        window.updateCards && window.updateCards();
    }, 100);
});

document.addEventListener('DOMContentLoaded', () => {
    initCardsNavigation();
    addColorStyles();
    initLottieAnimations();
});

window.addEventListener('load', () => {
    initCardsNavigation();
});

window.addEventListener('unload', () => {
    Object.values(lottieInstances).forEach(anim => {
        if (anim) {
            anim.destroy();
        }
    });
});