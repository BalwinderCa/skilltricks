window.onbeforeunload = function () {
    window.scrollTo(0, 0);
};

class ParticleGlobe {
    constructor(container) {
        this.container = container;
        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        this.renderer = new THREE.WebGLRenderer({
            antialias: true,
            alpha: true
        });

        this.outerRadius = this.calculateRadius();
        this.innerRadius = this.outerRadius - 1; 
        this.outerParticleCount = 2000;
        this.oceanParticleCount = 6000;
        this.landDensityMultiplier = 5; 
        this.rotationSpeed = 0.002;

        this.clippingPlane = new THREE.Plane(new THREE.Vector3(0, 0, 1), 0); 

        this.init();
    }

    calculateRadius() {
        const maxRadius = 9;
        const minRadius = 4; 
        const width = window.innerWidth;
        return width > 768 ? maxRadius : minRadius; 
    }

    updateGlobeSize() {
        this.outerRadius = this.calculateRadius();
        this.innerRadius = this.outerRadius - 1;
        this.camera.position.z = this.outerRadius * 2.5;

        this.scene.clear(); 
        this.createOuterSphere();
        this.createInnerGlobeWithGeoJSON('/newfronted/JSON/countries.json'); 
    }

    init() {
        this.renderer.setSize(window.innerWidth, window.innerHeight);
        this.renderer.setClearColor(0x000000, 0);
        this.renderer.localClippingEnabled = true;
        this.container.appendChild(this.renderer.domElement);
        const isAboutPage = window.location.pathname.includes("about");
        const isProductsPage = window.location.pathname.includes("products");

        
        if (isAboutPage || isProductsPage) {
            this.camera.position.z = this.outerRadius * 1;
        } else {
            this.camera.position.z = this.outerRadius * 2.5;
        }

        this.createOuterSphere();
        //this.createInnerGlobeWithGeoJSON('/newfronted/JSON/countries.json');

        if (!window.location.pathname.includes("about") && !window.location.pathname.includes("products")) {
            this.createInnerGlobeWithGeoJSON('/newfronted/JSON/countries.json');
        }

        window.addEventListener('resize', this.onWindowResize.bind(this), false);
        window.addEventListener('resize', this.updateGlobeSize.bind(this)); 
        this.animate();
    }

    createOuterSphere() {
        const geometry = new THREE.BufferGeometry();
        const positions = [];

        for (let i = 0; i < this.outerParticleCount; i++) {
            const radius = this.outerRadius * (0.95 + Math.random() * 0.1);
            const theta = Math.random() * Math.PI * 2;
            const phi = Math.acos((Math.random() * 2) - 1);

            const x = radius * Math.sin(phi) * Math.cos(theta);
            const y = radius * Math.sin(phi) * Math.sin(theta);
            const z = radius * Math.cos(phi);

            positions.push(x, y, z);
        }

        geometry.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
        const isAboutPage = window.location.pathname.includes("about");
        const isProductsPage = window.location.pathname.includes("products");
        const material = new THREE.PointsMaterial({
            color: 0xFFFFFF,
            size: 0.06,
            transparent: true,
            opacity: 0.4,
            blending: THREE.AdditiveBlending,
            clippingPlanes: isAboutPage || isProductsPage ? [] : [this.clippingPlane]
        });

        this.outerSphere = new THREE.Points(geometry, material);
        this.scene.add(this.outerSphere);
    }

    updateGlobeSize() {
        this.outerRadius = this.calculateRadius();
        this.innerRadius = this.outerRadius - 1;
        this.camera.position.z = this.outerRadius * 2.5;
    
        this.scene.clear();
        this.createOuterSphere();
        
        if (!window.location.pathname.includes("about") && !window.location.pathname.includes("products")) {
            this.createInnerGlobeWithGeoJSON('/newfronted/JSON/countries.json');
        }
    }

    async createInnerGlobeWithGeoJSON(geoJSONPath) {
        const response = await fetch(geoJSONPath);
        const geojson = await response.json();

        const landGeometry = new THREE.BufferGeometry();
        const oceanGeometry = new THREE.BufferGeometry();
        const landPositions = [];
        const oceanPositions = [];

        const latLngToVector3 = (lat, lng, radius) => {
            const phi = (90 - lat) * (Math.PI / 180);
            const theta = (lng + 180) * (Math.PI / 180);
            return {
                x: -radius * Math.sin(phi) * Math.cos(theta),
                y: radius * Math.cos(phi),
                z: radius * Math.sin(phi) * Math.sin(theta),
            };
        };

        geojson.features.forEach(feature => {
            if (feature.geometry.type === "Polygon" || feature.geometry.type === "MultiPolygon") {
                const polygons = feature.geometry.type === "Polygon"
                    ? [feature.geometry.coordinates]
                    : feature.geometry.coordinates;

                polygons.forEach(polygon => {
                    polygon.forEach(path => {
                        const randomSamples = Math.floor(path.length * this.landDensityMultiplier);
                        for (let i = 0; i < randomSamples; i++) {
                            const randIndex1 = Math.floor(Math.random() * path.length);
                            const randIndex2 = Math.floor(Math.random() * path.length);

                            const [lng1, lat1] = path[randIndex1];
                            const [lng2, lat2] = path[randIndex2];

                            const t = Math.random();
                            const lat = lat1 * (1 - t) + lat2 * t;
                            const lng = lng1 * (1 - t) + lng2 * t;

                            const point = latLngToVector3(lat, lng, this.innerRadius);
                            landPositions.push(point.x, point.y, point.z);
                        }
                    });
                });
            }
        });

        const goldenRatio = (1 + Math.sqrt(5)) / 2;
        const increment = Math.PI * 2 * goldenRatio;
        for (let i = 0; i < this.oceanParticleCount; i++) {
            const phi = Math.acos(1 - (2 * (i + 0.5)) / this.oceanParticleCount);
            const theta = increment * i;

            const x = this.innerRadius * Math.sin(phi) * Math.cos(theta);
            const y = this.innerRadius * Math.sin(phi) * Math.sin(theta);
            const z = this.innerRadius * Math.cos(phi);

            oceanPositions.push(x, y, z);
        }

        landGeometry.setAttribute('position', new THREE.Float32BufferAttribute(landPositions, 3));
        oceanGeometry.setAttribute('position', new THREE.Float32BufferAttribute(oceanPositions, 3));

        const landMaterial = new THREE.PointsMaterial({
            color: 0x36839B,
            size: 0.1,
            transparent: true,
            opacity: 0.9,
            blending: THREE.AdditiveBlending,
            clippingPlanes: [this.clippingPlane]
        });

        const oceanMaterial = new THREE.PointsMaterial({
            color: 0x1E90FF,
            size: 0.05,
            transparent: true,
            opacity: 0.3,
            blending: THREE.AdditiveBlending,
            clippingPlanes: [this.clippingPlane]
        });

        this.landParticles = new THREE.Points(landGeometry, landMaterial);
        this.oceanParticles = new THREE.Points(oceanGeometry, oceanMaterial);

        this.innerGlobe = new THREE.Group();
        this.innerGlobe.add(this.landParticles);
        this.innerGlobe.add(this.oceanParticles);

        this.innerGlobe.rotation.x = Math.PI / 8; 
        this.scene.add(this.innerGlobe);
    }

    onWindowResize() {
        this.camera.aspect = window.innerWidth / window.innerHeight;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(window.innerWidth, window.innerHeight);
    }

    animate() {
        requestAnimationFrame(this.animate.bind(this));

        if (this.innerGlobe) {
            this.innerGlobe.rotation.y += this.rotationSpeed;
        }

        this.outerSphere.rotation.y += 0.0075;

        this.renderer.render(this.scene, this.camera);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const globeContainer = document.querySelector('.globe-container');
    if (globeContainer) {
        new ParticleGlobe(globeContainer);
    }
});