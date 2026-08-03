<?php
/**
 * landing.php - Wall 1 (Commercial Paywall Landing Page) & Wall 2 (Interactive CAPTCHA Challenge).
 * Serves as the public-facing camouflage for okotunes.
 */
$pendingChallenge = (get_auth_status() === 'pending_challenge');
$captchaTarget = $_SESSION['captcha_target'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>OkoStream • Hi-Res Master Audio Studio ($29.99/mo)</title>
    <link rel="icon" href="data:,">
    <script src="assets/alpine.min.js" defer></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;600;700;800&display=swap');
        
        :root {
            --bg-gradient: linear-gradient(135deg, #050711 0%, #100b24 50%, #070512 100%);
            --glass-card: rgba(18, 14, 36, 0.45);
            --glass-border: rgba(255, 255, 255, 0.06);
            --accent-pink: #FF007F;
            --accent-purple: #7928CA;
            --accent-gradient: linear-gradient(135deg, #FF007F 0%, #7928CA 100%);
            --text-main: #FFFFFF;
            --text-sub: #94A3B8;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-gradient); color: var(--text-main); min-height: 100vh; overflow-x: hidden; position: relative; }

        /* Background Aura */
        .bg-aura {
            position: fixed; inset: 0; pointer-events: none; z-index: 0;
            background: radial-gradient(circle at 50% 20%, rgba(255,0,127,0.18), transparent 65%),
                        radial-gradient(circle at 80% 80%, rgba(121,40,202,0.15), transparent 55%);
        }

        /* Top Navigation Header */
        .header {
            position: relative; z-index: 10; height: 72px; padding: 0 32px;
            display: flex; align-items: center; justify-content: space-between;
            background: rgba(5, 7, 17, 0.35); backdrop-filter: blur(20px); border-bottom: 1px solid var(--glass-border);
        }
        .brand-logo { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .nav-btn {
            padding: 10px 22px; border-radius: 30px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.25s ease;
        }
        .btn-ghost { background: transparent; color: rgba(255,255,255,0.7); border: 1px solid var(--glass-border); }
        .btn-ghost:hover { color: #fff; border-color: rgba(255,0,127,0.4); }
        .btn-accent { background: var(--accent-gradient); color: #fff; border: none; box-shadow: 0 8px 24px rgba(255,0,127,0.35); }
        .btn-accent:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(255,0,127,0.5); }

        /* Hero Section */
        .hero { position: relative; z-index: 1; max-width: 1080px; margin: 60px auto 40px; padding: 0 24px; text-align: center; }
        .badge {
            display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1.5px; background: rgba(255,0,127,0.12); border: 1px solid rgba(255,0,127,0.3); color: var(--accent-pink); margin-bottom: 24px;
        }
        .hero h1 { font-family: 'Outfit', sans-serif; font-size: clamp(2.5rem, 5.5vw, 4.5rem); font-weight: 800; line-height: 1.15; margin-bottom: 20px; letter-spacing: -1px; }
        .hero h1 span { background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero p { font-size: 1.15rem; color: var(--text-sub); max-width: 760px; margin: 0 auto 36px; line-height: 1.6; }

        /* Features Grid */
        .hero-features-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;
            margin: 40px 0 50px; text-align: left;
        }
        .feature-card {
            background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(16px); border: 1px solid var(--glass-border);
            border-radius: 20px; padding: 24px; transition: all 0.3s ease;
        }
        .feature-card:hover { transform: translateY(-4px); background: rgba(255, 255, 255, 0.04); border-color: rgba(255,0,127,0.3); }
        .feature-icon { font-size: 28px; margin-bottom: 12px; }
        .feature-card h3 { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 8px; }
        .feature-card p { font-size: 13px; color: var(--text-sub); line-height: 1.5; }

        /* Paywall Card */
        .paywall-card {
            background: var(--glass-card); backdrop-filter: blur(28px); border: 1px solid var(--glass-border);
            border-radius: 28px; padding: 44px 52px; max-width: 520px; margin: 0 auto; box-shadow: 0 32px 90px rgba(0,0,0,0.5); text-align: center;
        }
        .price { font-family: 'Outfit', sans-serif; font-size: 3.4rem; font-weight: 800; color: #fff; margin: 12px 0 4px; }
        .price span { font-size: 1.1rem; color: var(--text-sub); font-weight: 500; }
        
        .features-list { list-style: none; margin: 24px 0 32px; text-align: left; display: flex; flex-direction: column; gap: 14px; font-size: 14px; color: #e2e8f0; }
        .features-list li { display: flex; align-items: center; gap: 12px; }
        .features-list li::before { content: "★"; color: var(--accent-pink); font-size: 12px; }

        /* Modal Overlay */
        .modal-overlay {
            position: fixed; inset: 0; z-index: 100; background: rgba(0,0,0,0.8); backdrop-filter: blur(16px);
            display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        .modal-box {
            background: rgba(14, 10, 28, 0.92); border: 1px solid var(--glass-border); border-radius: 24px;
            padding: 36px; width: 100%; max-width: 420px; box-shadow: 0 30px 90px rgba(0,0,0,0.8); position: relative;
        }
        .modal-box h2 { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 700; margin-bottom: 8px; }
        .modal-box p { font-size: 14px; color: var(--text-sub); margin-bottom: 24px; }

        .form-group { margin-bottom: 18px; text-align: left; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--text-sub); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-input {
            width: 100%; padding: 14px 16px; border-radius: 12px; background: rgba(255,255,255,0.04); border: 1px solid var(--glass-border);
            color: #fff; font-size: 15px; outline: none; transition: border-color 0.2s ease;
        }
        .form-input:focus { border-color: var(--accent-pink); }

        .error-msg { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #fca5a5; padding: 10px; border-radius: 10px; font-size: 13px; margin-bottom: 16px; }

        /* Grid for Captcha node selection */
        .captcha-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin: 20px 0; }
        .captcha-node {
            background: rgba(255,255,255,0.04); border: 1px solid var(--glass-border); border-radius: 16px;
            padding: 20px; text-align: center; cursor: pointer; transition: all 0.2s ease;
        }
        .captcha-node:hover { background: rgba(255,0,127,0.1); border-color: var(--accent-pink); transform: scale(1.02); }
        .captcha-node-icon { font-size: 24px; margin-bottom: 6px; }
        .captcha-node-title { font-size: 12px; font-weight: 700; color: #fff; }
    </style>
</head>
<body x-data="{
    showLoginModal: false,
    showChallenge: <?= $pendingChallenge ? 'true' : 'false' ?>,
    email: '',
    password: '',
    errorMsg: '',
    targetNode: <?= $captchaTarget ?>,
    loading: false,

    async submitLogin() {
        if (!this.email || !this.password) {
            this.errorMsg = 'Please enter both email and password.';
            return;
        }
        this.loading = true;
        this.errorMsg = '';

        try {
            let formData = new FormData();
            formData.append('auth_action', 'login');
            formData.append('email', this.email);
            formData.append('password', this.password);

            let res = await fetch('auth.php', { method: 'POST', body: formData });
            let data = await res.json();
            this.loading = false;

            if (data.success) {
                this.targetNode = data.target;
                this.showLoginModal = false;
                this.showChallenge = true;
            } else {
                this.errorMsg = data.error || 'Authentication failed.';
            }
        } catch(e) {
            this.loading = false;
            this.errorMsg = 'Network error. Please try again.';
        }
    },

    async submitChallenge(nodeId) {
        this.loading = true;
        try {
            let formData = new FormData();
            formData.append('auth_action', 'verify_challenge');
            formData.append('selected_target', nodeId);

            let res = await fetch('auth.php', { method: 'POST', body: formData });
            let data = await res.json();
            
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        } catch(e) {
            window.location.href = 'index.php?mode=pit';
        }
    }
}">
    <div class="bg-aura"></div>

    <header class="header">
        <div class="brand-logo">OkoStream Pro</div>
        <div style="display: flex; gap: 12px;">
            <button class="nav-btn btn-ghost" style="cursor: default;" @click.prevent>Try Free</button>
            <button class="nav-btn btn-accent" @click="showLoginModal = true">Sign In</button>
        </div>
    </header>

    <main class="hero">
        <div class="badge">Next-Gen Audio Infrastructure</div>
        <h1>Pure Lossless Sound. <span>Master Quality Stream.</span></h1>
        <p>Stream over 150 Million uncompressed 24-bit/192kHz Spatial Master tracks. Equipped with real-time AI stem separation, holographic Dolby soundstaging, and studio tube emulation.</p>

        <div class="hero-features-grid">
            <div class="feature-card">
                <div class="feature-icon">🎛️</div>
                <h3>AI Real-Time Stem Isolator</h3>
                <p>Isolate vocals, drums, bass, and synth tracks instantly on any song with live studio multitrack mixing.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🌌</div>
                <h3>Holographic Dolby Atmos 3D</h3>
                <p>Experience ultra-immersive binaural acoustic positioning calibrated for professional monitoring gear.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📼</div>
                <h3>Analog Vinyl Tube Preamp</h3>
                <p>Add rich harmonic warmth, subtle tape saturation, and vintage analog tube acoustic resonance on the fly.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📡</div>
                <h3>Direct Lossless Cloud Vault</h3>
                <p>Encrypted peer-to-peer audio buffer streaming with zero latency and studio FLAC master output.</p>
            </div>
        </div>

        <div class="paywall-card">
            <div style="font-size: 12px; font-weight: 700; color: var(--accent-pink); text-transform: uppercase; letter-spacing: 1px;">Pro Master Tier</div>
            <div class="price">$29.99 <span>/ month</span></div>
            <p style="font-size: 13px; color: var(--text-sub);">Full unrestricted access for studios, audiophiles & creators</p>

            <ul class="features-list">
                <li>Uncompressed 24-Bit / 192kHz Master FLAC Stream</li>
                <li>AI Vocal & Multitrack Instrument Separator</li>
                <li>Dolby Atmos 3D & Vintage Analog Tube DSP</li>
                <li>Unlimited P2P Satellite Lossless Cloud Vault</li>
            </ul>

            <button class="nav-btn btn-accent" style="width: 100%; padding: 16px; font-size: 16px; font-weight: 700; border-radius: 16px;" @click="showLoginModal = true">
                Member Sign In
            </button>
        </div>
    </main>

    <!-- Wall 1: Login Modal -->
    <div class="modal-overlay" x-show="showLoginModal" x-cloak style="display: none;">
        <div class="modal-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h2>Member Sign In</h2>
                <button @click="showLoginModal = false" style="background: none; border: none; color: var(--text-sub); font-size: 20px; cursor: pointer;">✕</button>
            </div>
            <p>Access your high-resolution OkoStream library.</p>

            <div x-show="errorMsg" class="error-msg" x-text="errorMsg"></div>

            <form @submit.prevent="submitLogin()">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" x-model="email" class="form-input" placeholder="subscriber@domain.com" required />
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" x-model="password" class="form-input" placeholder="••••••••" required />
                </div>
                <button type="submit" class="nav-btn btn-accent" style="width: 100%; padding: 14px; margin-top: 8px;" :disabled="loading">
                    <span x-show="!loading">Continue to Verification</span>
                    <span x-show="loading">Authenticating Credentials...</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Wall 2: Robot Verification CAPTCHA Challenge Modal -->
    <div class="modal-overlay" x-show="showChallenge" x-cloak style="display: none;">
        <div class="modal-box" style="text-align: center;">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: rgba(255,0,127,0.15); border: 1px solid rgba(255,0,127,0.3); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: var(--accent-pink); font-size: 24px;">
                🤖
            </div>
            <h2>Confirm you are not a bot</h2>

            <div class="captcha-grid">
                <div class="captcha-node" @click="submitChallenge(1)">
                    <div class="captcha-node-icon">⚡</div>
                    <div class="captcha-node-title">Node 44.1 kHz</div>
                </div>
                <div class="captcha-node" @click="submitChallenge(2)">
                    <div class="captcha-node-icon">🌀</div>
                    <div class="captcha-node-title">Node 22.0 kHz</div>
                </div>
                <div class="captcha-node" @click="submitChallenge(3)">
                    <div class="captcha-node-icon">🌐</div>
                    <div class="captcha-node-title">Node 96.0 kHz</div>
                </div>
                <div class="captcha-node" @click="submitChallenge(4)">
                    <div class="captcha-node-icon">💎</div>
                    <div class="captcha-node-title">Node 192.0 kHz</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
