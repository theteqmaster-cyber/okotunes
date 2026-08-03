import * as THREE from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

window.OceanScene = (() => {
    let renderer, scene, camera;
    let shipGroup, water;
    let logicalW = 600, logicalH = 190;
    let isZenMode = false;
    let perspective = 0; // 0=side, 1=bow, 2=stern, 3=center, 4=cabin
    let clock = new THREE.Clock();
    let shipLoaded = false;
    let isPlaying = false;
    
    // Animation states
    let time = 0;
    let worldZ = 0;
    let currentSpeed = 0;
    let currentBobAmp = 0.3;
    let currentRollAmp = 0.02;
    let currentPitchAmp = 0.01;
    
    // Islands
    const islandGroup = new THREE.Group();
    const islands = [];
    let islandModels = {};

    function init() {
        const bgWrap = document.querySelector('.main-hero');
        if (!bgWrap) return;
        
        // Cleanup old canvas if exists
        const oldC = document.getElementById('ocean-canvas-3d');
        if (oldC) oldC.remove();

        const canvas3d = document.createElement('canvas');
        canvas3d.id = 'ocean-canvas-3d';
        canvas3d.style.position = 'absolute';
        canvas3d.style.top = '0';
        canvas3d.style.left = '0';
        canvas3d.style.width = '100%';
        canvas3d.style.height = '100%';
        canvas3d.style.zIndex = '0';
        canvas3d.style.borderRadius = '24px';
        bgWrap.appendChild(canvas3d);

        // Hide the old 2D canvas but keep it in DOM just in case
        const old2d = document.getElementById('ocean-canvas');
        if (old2d) old2d.style.display = 'none';

        renderer = new THREE.WebGLRenderer({ canvas: canvas3d, antialias: true, alpha: true });
        renderer.setPixelRatio(window.devicePixelRatio);
        
        scene = new THREE.Scene();
        scene.background = new THREE.Color(0x0a1b2a); // Deep twilight blue
        scene.fog = new THREE.FogExp2(0x0a1b2a, 0.003);

        camera = new THREE.PerspectiveCamera(60, 1, 0.1, 2000);

        // Lighting (Moonlight + Accent)
        const ambient = new THREE.AmbientLight(0x404060, 2.5); // Boosted ambient light
        scene.add(ambient);
        
        const moonLight = new THREE.DirectionalLight(0xcceeff, 2.5);
        moonLight.position.set(100, 200, -100);
        scene.add(moonLight);

        const fillLight = new THREE.DirectionalLight(0xffaa55, 1.5); // Boosted fill light
        fillLight.position.set(-100, 50, 100);
        scene.add(fillLight);

        // Ship
        shipGroup = new THREE.Group();
        scene.add(shipGroup);

        const loader = new GLTFLoader();
        loader.load('assets/new ship assets/source/pirate ship.glb', (gltf) => {
            const ship = gltf.scene;
            
            // New ship has smaller bounds, scale to 7 to match scene
            ship.scale.set(7, 7, 7); 
            
            // Fix orientation: Natively it faces +Z (left). Rotate 180 (Math.PI) to face -Z (right) so it sails forward!
            ship.rotation.y = Math.PI; 
            
            // The ship's bottom is at Y=0. Lower it so hull is in the water
            ship.position.y = -5;
            
            // Apply Specific Textures to Specific Parts
            const texLoader = new THREE.TextureLoader();
            const loadTex = (file, repeat = 1) => {
                const tex = texLoader.load('assets/new ship assets/textures/' + file);
                tex.wrapS = THREE.RepeatWrapping;
                tex.wrapT = THREE.RepeatWrapping;
                tex.repeat.set(repeat, repeat);
                return tex;
            };

            const texHull = loadTex('cartoon-wooden-texture-background-wood-cut.jpg', 3);
            const texDeck = loadTex('6264b5848e2e7a748be4a6252320d220.jpg', 4);
            const texBarrel = loadTex('istockphoto-1366528710-612x612_1.jpg', 1);
            const texCrate = loadTex('istockphoto-1366528710-612x612_2.jpg', 1);
            const texCabin = loadTex('Screenshot_20240902-130306.jpg', 2);
            const texDetails = loadTex('Screenshot_20240629-194250.jpg', 1);

            ship.traverse((child) => {
                if (child.isMesh) {
                    child.geometry.computeBoundingBox();
                    const box = child.geometry.boundingBox;
                    const center = box.getCenter(new THREE.Vector3());
                    const sizeX = box.max.x - box.min.x;
                    const sizeY = box.max.y - box.min.y;
                    const sizeZ = box.max.z - box.min.z;
                    
                    let map = texHull; // default
                    let color = 0xffffff;
                    
                    // Identify parts by bounding box size/proportions
                    if (sizeY > 2.5 && sizeX < 0.5 && sizeZ < 0.5) {
                        // Masts
                        map = texHull;
                    } else if (sizeX > 1.0 && sizeZ > 1.0 && sizeY < 0.8) {
                        // Deck flooring (flat and wide)
                        map = texDeck;
                    } else if (Math.abs(sizeX - 0.35) < 0.1 && Math.abs(sizeY - 0.35) < 0.1 && Math.abs(sizeZ - 0.35) < 0.1) {
                        // Barrels (size ~ 0.35)
                        map = texBarrel;
                    } else if (Math.abs(sizeX - 0.22) < 0.05 && Math.abs(sizeY - 0.22) < 0.05 && Math.abs(sizeZ - 0.22) < 0.05) {
                        // Crates (size ~ 0.22)
                        map = texCrate;
                    } else if (center.y > 1.5 && sizeZ > 3.0 && sizeX > 1.0) {
                        // Cabin / upper large structures
                        map = texCabin;
                    } else if (sizeX < 0.5 && sizeZ < 0.5 && sizeY < 1.0) {
                        // Small details, railings, wheel
                        map = texDetails;
                    }
                    
                    // Heuristic: Sails (high up, wide, but not a thin mast)
                    if (center.y > 2.5 && (sizeX > 0.8 || sizeZ > 0.8)) {
                        if (!(sizeX < 0.5 && sizeZ < 0.5)) {
                            map = null;
                            color = 0x151515; // Dark black sails
                        }
                    }

                    child.material = new THREE.MeshStandardMaterial({
                        map: map,
                        color: color,
                        roughness: 0.8,
                        metalness: 0.1,
                        flatShading: false
                    });
                }
            });
            
            shipGroup.add(ship);
            shipLoaded = true;
        });

        // Low-Poly Animated Water
        const waterGeo = new THREE.PlaneGeometry(2000, 2000, 128, 128);
        waterGeo.rotateX(-Math.PI / 2);
        
        // Save original vertices for wave calculation
        waterGeo.userData.origVerts = [];
        const posAttribute = waterGeo.attributes.position;
        for (let i = 0; i < posAttribute.count; i++) {
            waterGeo.userData.origVerts.push(
                posAttribute.getX(i),
                posAttribute.getY(i),
                posAttribute.getZ(i)
            );
        }

        const waterMat = new THREE.MeshStandardMaterial({
            color: 0x0e5a8a, // Lighter ocean blue to see waves better
            emissive: 0x021626, // Slight glow to prevent pitch black areas
            roughness: 0.3,
            metalness: 0.4, // Less metallic to show diffuse color
            flatShading: true,
            transparent: true,
            opacity: 0.95
        });
        water = new THREE.Mesh(waterGeo, waterMat);
        water.position.y = 0;
        scene.add(water);

        // Islands setup
        scene.add(islandGroup);
        
        // Preload island pieces
        loader.load('assets/rocks-sand-a.glb', g => islandModels.rock = g.scene);
        loader.load('assets/palm-bend.glb', g => islandModels.palm = g.scene);
        loader.load('assets/tower-watch.glb', g => islandModels.tower = g.scene);

        // Create 8 reusable islands
        for (let i = 0; i < 8; i++) {
            const isl = new THREE.Group();
            isl.userData = { zOffset: -300 - (i * 250), side: (i % 2 === 0 ? 1 : -1) };
            islands.push(isl);
            islandGroup.add(isl);
        }

        window.addEventListener('resize', resize);
        resize();

        renderer.setAnimationLoop(animate);
    }

    function populateIsland(isl) {
        // Clear old children
        while(isl.children.length > 0){ 
            isl.remove(isl.children[0]); 
        }
        
        if (!islandModels.rock) return;

        const applyColor = (model, baseColor, isPalm = false) => {
            model.traverse((child) => {
                if (child.isMesh) {
                    // For palm trees, try to color leaves green and trunk brown based on position/name
                    let color = baseColor;
                    if (isPalm) {
                        if (child.position.y > 0.5 || child.name.toLowerCase().includes('leaf') || child.name.toLowerCase().includes('foliage')) {
                            color = 0x2d4c1e; // Dark green
                        } else {
                            color = 0x4a3b2c; // Brown trunk
                        }
                    }
                    child.material = new THREE.MeshStandardMaterial({
                        color: color,
                        roughness: 0.9,
                        metalness: 0.0,
                        flatShading: true
                    });
                }
            });
        };

        // Build a random island
        const rock = islandModels.rock.clone();
        rock.scale.set(15, 10, 15);
        applyColor(rock, 0xd2b48c); // Tan/Sand color
        isl.add(rock);

        if (islandModels.palm && Math.random() > 0.3) {
            const palm = islandModels.palm.clone();
            palm.scale.set(10, 10, 10);
            palm.position.set(0, 5, 0);
            applyColor(palm, 0x2d4c1e, true);
            isl.add(palm);
        }

        if (islandModels.tower && Math.random() > 0.7) {
            const tower = islandModels.tower.clone();
            tower.scale.set(5, 5, 5);
            tower.position.set((Math.random()-0.5)*10, 8, (Math.random()-0.5)*10);
            applyColor(tower, 0x808080); // Gray stone
            isl.add(tower);
        }
    }

    function resize() {
        const bgWrap = document.querySelector('.main-hero');
        if (!bgWrap) return;
        logicalW = bgWrap.clientWidth || 600;
        logicalH = bgWrap.clientHeight || 190;
        
        if (renderer && camera) {
            renderer.setSize(logicalW, logicalH, false);
            camera.aspect = logicalW / logicalH;
            camera.updateProjectionMatrix();
        }
    }

    function setPerspective(p) {
        perspective = p;
    }

    function cyclePerspective() {
        perspective = (perspective + 1) % 5;
    }

    function getPerspective() {
        return perspective;
    }

    function setZenMode(val) { 
        isZenMode = val;
        // Adjust logical height for zen mode vs standard mode if needed
        setTimeout(resize, 50);
    }

    function setPlaying(val) {
        isPlaying = val;
    }

    function animate() {
        const dt = clock.getDelta();
        time += dt;
        
        // Ship speed
        const targetSpeed = isPlaying ? 30 : 0; 
        currentSpeed += (targetSpeed - currentSpeed) * Math.min(dt * 1.0, 1); // Smooth transition
        worldZ -= currentSpeed * dt;

        // Animate Low-Poly Waves
        if (water) {
            const posAttr = water.geometry.attributes.position;
            const orig = water.geometry.userData.origVerts;
            for (let i = 0; i < posAttr.count; i++) {
                const ox = orig[i * 3];
                const oz = orig[i * 3 + 2];
                // Complex overlapping sine waves with increased amplitude
                const wave1 = Math.sin(ox * 0.02 + time * 1.5) * 2.0;
                const wave2 = Math.cos(oz * 0.03 + time * 2.0 - worldZ * 0.05) * 2.0;
                const wave3 = Math.sin((ox + oz) * 0.01 + time) * 1.5;
                
                posAttr.setY(i, wave1 + wave2 + wave3);
            }
            posAttr.needsUpdate = true;
            water.geometry.computeVertexNormals(); // Crucial for flat shading lighting
        }

        // Animate Ship Bobbing/Rolling
        if (shipGroup) {
            const targetBobAmp = isPlaying ? 1.0 : 0.3;
            const targetRollAmp = isPlaying ? 0.08 : 0.02;
            const targetPitchAmp = isPlaying ? 0.04 : 0.01;

            currentBobAmp += (targetBobAmp - currentBobAmp) * Math.min(dt * 1.0, 1);
            currentRollAmp += (targetRollAmp - currentRollAmp) * Math.min(dt * 1.0, 1);
            currentPitchAmp += (targetPitchAmp - currentPitchAmp) * Math.min(dt * 1.0, 1);

            const bob = Math.sin(time * 2.0) * currentBobAmp;
            const rollVal = Math.sin(time * 1.5) * currentRollAmp;
            const pitchVal = Math.cos(time * 2.2) * currentPitchAmp;
            
            shipGroup.position.y = bob;
            shipGroup.rotation.z = rollVal;
            shipGroup.rotation.x = pitchVal;
        }

        // Manage Islands (Endless side-scrolling)
        islands.forEach(isl => {
            // Move island relative to ship
            let actualZ = isl.userData.zOffset - worldZ;
            
            // If it falls far behind the camera (e.g. actualZ > 150), recycle it ahead
            if (actualZ > 150) {
                isl.userData.zOffset -= 2000; // push far ahead
                actualZ = isl.userData.zOffset - worldZ;
                populateIsland(isl); // Re-roll random island features
                
                // Randomize distance to the side
                isl.userData.sideDistance = 60 + Math.random() * 100;
            }

            // Initial population
            if (isl.children.length === 0 && islandModels.rock) {
                populateIsland(isl);
                isl.userData.sideDistance = 60 + Math.random() * 100;
            }
            
            isl.position.z = actualZ;
            isl.position.x = isl.userData.sideDistance * isl.userData.side;
            
            // Islands should be firmly planted, not floating
            isl.position.y = -5;
        });

        // Camera Perspectives
        if (perspective === 0) {
            // Side View (Camera detached, flying alongside)
            scene.add(camera);
            camera.position.set(70, 15, 0); // Closer for a better view
            camera.lookAt(0, 10, 0);
        } else {
            // Camera attached to Ship (Inherits bob & roll!)
            shipGroup.add(camera);
            
            // With Math.PI rotation: Bow is at -Z, Stern is at +Z
            if (perspective === 1) {
                // Bow (Front deck looking forward)
                camera.position.set(0, 14, -18);
                camera.lookAt(0, 14, -200);
            } else if (perspective === 2) {
                // Stern (Back deck looking forward over the ship)
                camera.position.set(0, 18, 28);
                camera.lookAt(0, 15, -200);
            } else if (perspective === 3) {
                // Center Deck (Looking forward)
                camera.position.set(0, 14, 5);
                camera.lookAt(0, 14, -200);
            } else if (perspective === 4) {
                // Cabin (Inside looking out the back)
                camera.position.set(0, 10, 26);
                camera.lookAt(0, 10, 200);
            }
        }

        renderer.render(scene, camera);
    }

    return { init, setZenMode, cyclePerspective, getPerspective, setPerspective, setPlaying };
})();
