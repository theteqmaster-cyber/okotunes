<?php
/**
 * landing.php - Wall 1 (Commercial Paywall Landing Page) & Wall 2 (Interactive CAPTCHA Challenge).
 * Inspired by modern high-converting bento layouts with crisp wallpaper background and sleek typography.
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
    <link rel="icon" type="image/png" href="assets/okotunes%20logo.png" />
    <link rel="apple-touch-icon" href="assets/okotunes%20logo.png" />
    <script src="assets/alpine.min.js" defer></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@1&family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;600;700;800;900&display=swap');
        
        :root {
            --bg-gradient: linear-gradient(180deg, #05030a 0%, #0d071b 40%, #070512 100%);
            --hero-gradient: linear-gradient(135deg, #7928CA 0%, #FF007F 50%, #FFB800 100%);
            --glass-card: rgba(255, 255, 255, 0.04);
            --glass-border: rgba(255, 255, 255, 0.08);
            --accent-pink: #FF007F;
            --accent-gold: #FFB800;
            --text-main: #FFFFFF;
            --text-sub: #94A3B8;
            --text-dark: #1E293B;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: #070512; color: var(--text-main); min-height: 100vh; overflow-x: hidden; position: relative; }

        /* Top Navigation Header */
        .header {
            position: relative; z-index: 10; height: 80px; padding: 0 40px;
            display: flex; align-items: center; justify-content: space-between;
            background: rgba(7, 5, 18, 0.6); backdrop-filter: blur(24px); border-bottom: 1px solid var(--glass-border);
        }
        .brand-logo-img { height: 36px; object-fit: contain; }
        .nav-btn {
            padding: 12px 26px; border-radius: 30px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.25s ease;
        }
        .btn-ghost { background: transparent; color: rgba(255,255,255,0.7); border: 1px solid var(--glass-border); }
        .btn-accent {
            background: linear-gradient(135deg, #FF007F 0%, #7928CA 100%); color: #fff; border: none;
            box-shadow: 0 8px 24px rgba(255,0,127,0.35);
        }
        .btn-accent:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(255,0,127,0.5); }

        /* Hero Section (Wallpaper Image Background) */
        .hero-banner {
            position: relative; padding: 110px 40px 130px;
            background: linear-gradient(to bottom, rgba(5, 3, 10, 0.45), rgba(7, 5, 18, 0.85)),
                        url('assets/wall%201%20hero.png') center/cover no-repeat;
            border-bottom: 1px solid var(--glass-border); overflow: hidden; text-align: center;
        }

        .hero-container { max-width: 860px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; }
        
        .serif-italic { font-family: 'Instrument Serif', serif; font-style: italic; font-size: 2.2rem; color: rgba(255,255,255,0.9); margin-bottom: 10px; }
        .hero-title { font-family: 'Outfit', sans-serif; font-size: clamp(3rem, 6vw, 5.2rem); font-weight: 800; line-height: 1.08; letter-spacing: -1.5px; margin-bottom: 24px; }
        .hero-desc { font-size: 1.25rem; color: rgba(255,255,255,0.85); line-height: 1.6; max-width: 680px; margin-bottom: 36px; }

        /* Trusted By Section (White Pill Cloud) */
        .trusted-section { background: #FFFFFF; color: var(--text-dark); padding: 60px 40px; text-align: center; }
        .trusted-header { display: flex; justify-content: space-between; align-items: center; max-width: 1100px; margin: 0 auto 32px; }
        .trusted-title { font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #64748B; }

        .trusted-grid {
            max-width: 1100px; margin: 0 auto; display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;
        }
        .trusted-pill {
            background: #F1F5F9; border-radius: 12px; padding: 18px 24px; font-size: 15px; font-weight: 700;
            color: #334155; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s ease;
        }
        .trusted-pill:hover { background: #E2E8F0; transform: translateY(-2px); }

        /* Section 2: Bento Grid Section */
        .bento-section { max-width: 1200px; margin: 90px auto; padding: 0 30px; }
        .bento-header { margin-bottom: 50px; }
        .bento-header h2 { font-family: 'Outfit', sans-serif; font-size: clamp(2.2rem, 4vw, 3.5rem); font-weight: 800; letter-spacing: -1px; margin-top: 6px; }
        .bento-header p { font-size: 1.1rem; color: var(--text-sub); max-width: 560px; margin-top: 12px; }

        .bento-grid { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 24px; margin-bottom: 24px; }
        
        .bento-card {
            border-radius: 28px; padding: 36px; position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;
            transition: all 0.35s ease; border: 1px solid var(--glass-border);
        }
        .bento-card:hover { transform: translateY(-5px); box-shadow: 0 24px 60px rgba(0,0,0,0.4); }

        /* Bento 1: Large Neon Card */
        .bento-card-neon {
            background: linear-gradient(135deg, rgba(255, 184, 0, 0.2), rgba(255, 0, 127, 0.4), rgba(121, 40, 202, 0.6)),
                        url('assets/wallpaper%20background%20placeholder.png') center/cover no-repeat;
            color: #FFF; min-height: 380px;
        }

        /* Bento 2: Light Glass Card */
        .bento-card-glass {
            background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); min-height: 380px;
        }
        .monitor-graph {
            width: 100%; height: 160px; background: rgba(0, 0, 0, 0.35); border-radius: 18px; border: 1px solid rgba(255,255,255,0.1);
            padding: 20px; margin-top: 20px; display: flex; flex-direction: column; justify-content: space-between;
        }
        .graph-line { height: 4px; background: linear-gradient(90deg, #00FF88, #00F2FE); border-radius: 2px; width: 85%; animation: pulse 2s ease infinite alternate; }

        /* Bento Row 2 */
        .bento-grid-2 { display: grid; grid-template-columns: 0.8fr 1.2fr; gap: 24px; }
        
        .bento-card-dark { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); min-height: 340px; }
        .avatar-cluster { display: flex; gap: -10px; margin-top: 20px; }
        .avatar-img { width: 44px; height: 44px; border-radius: 50%; border: 3px solid #070512; margin-left: -12px; object-fit: cover; }
        .avatar-img:first-child { margin-left: 0; }

        .bento-card-purple {
            background: linear-gradient(135deg, rgba(121, 40, 202, 0.6), rgba(36, 0, 70, 0.8)),
                        url('assets/wallpaper%20background%20placeholder.png') center/cover no-repeat;
            min-height: 340px; color: #FFF; position: relative;
        }

        /* Pricing Paywall Card */
        .paywall-wrapper { max-width: 540px; margin: 90px auto; padding: 0 20px; text-align: center; }
        .paywall-card {
            background: rgba(18, 14, 36, 0.65); backdrop-filter: blur(28px); border: 1px solid var(--glass-border);
            border-radius: 32px; padding: 48px; box-shadow: 0 32px 90px rgba(0,0,0,0.6);
        }
        .price { font-family: 'Outfit', sans-serif; font-size: 3.8rem; font-weight: 800; color: #fff; margin: 12px 0 4px; }
        .price span { font-size: 1.1rem; color: var(--text-sub); font-weight: 500; }
        
        .features-list { list-style: none; margin: 28px 0 36px; text-align: left; display: flex; flex-direction: column; gap: 14px; font-size: 15px; color: #e2e8f0; }
        .features-list li::before { content: "★"; color: var(--accent-pink); margin-right: 10px; }

        /* Modal Overlay */
        .modal-overlay {
            position: fixed; inset: 0; z-index: 100; background: rgba(0,0,0,0.82); backdrop-filter: blur(18px);
            display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        .modal-box {
            background: rgba(14, 10, 28, 0.95); border: 1px solid var(--glass-border); border-radius: 24px;
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

    <header class="header">
        <img src="assets/okotunes%20logo.png" class="brand-logo-img" alt="OkoStream Logo" />
        <div style="display: flex; gap: 12px;">
            <button class="nav-btn btn-ghost" style="cursor: default;" @click.prevent>Try Free →</button>
            <button class="nav-btn btn-accent" @click="showLoginModal = true">Sign In</button>
        </div>
    </header>

    <!-- Hero Section with Asset Wallpaper Background -->
    <section class="hero-banner">
        <div class="hero-container">
            <div class="serif-italic">No compression. Just flow.</div>
            <h1 class="hero-title">Ready to Reclaim Your Audio?</h1>
            <p class="hero-desc">Stream over 150 Million uncompressed 24-bit/192kHz Spatial Master tracks. Calibrated for audiophiles, producers, and sound engineers.</p>
            <div style="display: flex; gap: 16px;">
                <button class="nav-btn btn-accent" style="padding: 18px 40px; font-size: 17px; font-weight: 700; border-radius: 36px;" @click="showLoginModal = true">Start Master Stream</button>
            </div>
        </div>
    </section>

    <!-- Trusted By Logos Cloud -->
    <section class="trusted-section">
        <div class="trusted-header">
            <span class="trusted-title">Trusted by 10,000+ Studios & Sound Engineers</span>
            <span style="font-size: 13px; color: #94A3B8;">Global Lossless Infrastructure</span>
        </div>
        <div class="trusted-grid">
            <div class="trusted-pill"><span>⚡</span> Frame Blox</div>
            <div class="trusted-pill"><span>⭕</span> Supa Blox</div>
            <div class="trusted-pill"><span>⏳</span> Hype Blox</div>
            <div class="trusted-pill"><span>🌓</span> Ultra Blox</div>
            <div class="trusted-pill"><span>⏩</span> Ship Blox</div>
            <div class="trusted-pill"><span>🎧</span> Dolby Atmos</div>
            <div class="trusted-pill"><span>💎</span> FLAC Master</div>
            <div class="trusted-pill"><span>🌐</span> Sony 360</div>
        </div>
    </section>

    <!-- Bento Feature Section -->
    <section class="bento-section">
        <div class="bento-header">
            <div class="serif-italic">Make Sound Work for You</div>
            <h2>Stream smarter. Listen deeper. Hear everything.</h2>
            <p>Our master streaming platform helps you isolate stems, eliminate compression, and stay in the flow zone with zero stress.</p>
        </div>

        <div class="bento-grid">
            <!-- Bento 1: Large Neon Card -->
            <div class="bento-card bento-card-neon">
                <div style="position: relative; z-index: 2; max-width: 320px;">
                    <h3 style="font-family: 'Outfit'; font-size: 28px; font-weight: 800; margin-bottom: 12px;">Stem Isolator & Real-Time Mixing</h3>
                    <p style="font-size: 15px; opacity: 0.95; line-height: 1.5;">Isolate vocals, drums, and bass in real-time with our neural multitrack audio DSP engine.</p>
                </div>
            </div>

            <!-- Bento 2: Monitor Graph Card -->
            <div class="bento-card bento-card-glass">
                <div>
                    <h3 style="font-family: 'Outfit'; font-size: 24px; font-weight: 700; color: #fff; margin-bottom: 8px;">Focus Bitrate Monitor</h3>
                    <p style="font-size: 13px; color: var(--text-sub);">Real-time 24-bit/192kHz frequency spectrum graph with zero stream latency.</p>
                </div>
                <div class="monitor-graph">
                    <div style="font-size: 12px; color: #00FF88; font-weight: 700;">FLAC MASTER STREAM • 9216 kbps</div>
                    <div class="graph-line"></div>
                    <div style="display: flex; justify-content: space-between; font-size: 11px; color: #94A3B8;">
                        <span>20Hz</span><span>10kHz</span><span>44.1kHz</span><span>192kHz</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bento-grid-2">
            <!-- Bento 3: Community Card -->
            <div class="bento-card bento-card-dark">
                <div>
                    <h3 style="font-family: 'Outfit'; font-size: 22px; font-weight: 700; color: #fff; margin-bottom: 8px;">Audiophile Cloud Sync</h3>
                    <p style="font-size: 13px; color: var(--text-sub);">Connect your studio gear with seamless peer-to-peer cloud syncing.</p>
                </div>
                <div class="avatar-cluster">
                    <img src="assets/splash_bg.jpg" class="avatar-img" />
                    <img src="assets/album%20art%20new%20place%20holder.png" class="avatar-img" />
                    <img src="assets/wallpaper%20background%20placeholder.png" class="avatar-img" />
                </div>
            </div>

            <!-- Bento 4: Analog Tube Card -->
            <div class="bento-card bento-card-purple">
                <div style="position: relative; z-index: 2; max-width: 320px;">
                    <h3 style="font-family: 'Outfit'; font-size: 26px; font-weight: 800; margin-bottom: 12px;">Analog Vinyl Tube Remaster</h3>
                    <p style="font-size: 15px; opacity: 0.95; line-height: 1.5;">Harmonic tube warmth, analog tape saturation, and vintage preamp acoustics on demand.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Paywall Pricing Card -->
    <div class="paywall-wrapper">
        <div class="paywall-card">
            <div style="font-size: 12px; font-weight: 700; color: var(--accent-pink); text-transform: uppercase; letter-spacing: 1.5px;">Pro Master Membership</div>
            <div class="price">$29.99 <span>/ month</span></div>
            <p style="font-size: 13px; color: var(--text-sub);">Full unrestricted access for studios, audiophiles & creators</p>

            <ul class="features-list">
                <li>Uncompressed 24-Bit / 192kHz Master FLAC Stream</li>
                <li>AI Vocal & Multitrack Instrument Separator</li>
                <li>Dolby Atmos 3D & Vintage Analog Tube DSP</li>
                <li>Unlimited P2P Satellite Lossless Cloud Vault</li>
            </ul>

            <button class="nav-btn btn-accent" style="width: 100%; padding: 18px; font-size: 16px; font-weight: 700; border-radius: 18px;" @click="showLoginModal = true">
                Member Sign In
            </button>
        </div>
    </div>

    <!-- Wall 1: Login Modal -->
    <div class="modal-overlay" x-show="showLoginModal" x-cloak style="display: none;">
        <div class="modal-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h2 style="display: flex; align-items: center; gap: 10px;">
                    <img src="assets/okotunes%20logo.png" alt="okotunes" style="height: 28px; border-radius: 8px; object-fit: contain;" />
                    Member Sign In
                </h2>
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
