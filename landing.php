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
    <title>OkoStream • Hi-Res Lossless Streaming ($19.99/mo)</title>
    <link rel="icon" href="data:,">
    <script src="assets/alpine.min.js" defer></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;600;700;800&display=swap');
        
        :root {
            --bg-gradient: linear-gradient(135deg, #060911 0%, #110e24 50%, #080612 100%);
            --glass-card: rgba(18, 14, 36, 0.65);
            --glass-border: rgba(255, 255, 255, 0.1);
            --accent-pink: #FF007F;
            --accent-purple: #7928CA;
            --accent-gradient: linear-gradient(135deg, #FF007F 0%, #7928CA 100%);
            --text-main: #FFFFFF;
            --text-sub: #A0A5B5;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-gradient); color: var(--text-main); min-height: 100vh; overflow-x: hidden; position: relative; }

        /* Background Aura */
        .bg-aura {
            position: fixed; inset: 0; pointer-events: none; z-index: 0;
            background: radial-gradient(circle at 50% 20%, rgba(255,0,127,0.15), transparent 60%),
                        radial-gradient(circle at 80% 80%, rgba(121,40,202,0.12), transparent 50%);
        }

        /* Top Navigation Header */
        .header {
            position: relative; z-index: 10; height: 72px; padding: 0 32px;
            display: flex; align-items: center; justify-content: space-between;
            background: rgba(6, 9, 17, 0.4); backdrop-filter: blur(20px); border-bottom: 1px solid var(--glass-border);
        }
        .brand-logo { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .nav-btn {
            padding: 10px 22px; border-radius: 30px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.25s ease;
        }
        .btn-ghost { background: transparent; color: #fff; border: 1px solid var(--glass-border); }
        .btn-ghost:hover { border-color: rgba(255,0,127,0.5); background: rgba(255,255,255,0.05); }
        .btn-accent { background: var(--accent-gradient); color: #fff; border: none; box-shadow: 0 8px 24px rgba(255,0,127,0.35); }
        .btn-accent:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(255,0,127,0.5); }

        /* Hero Section */
        .hero { position: relative; z-index: 1; max-width: 1000px; margin: 80px auto 40px; padding: 0 24px; text-align: center; }
        .badge {
            display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1.5px; background: rgba(255,0,127,0.12); border: 1px solid rgba(255,0,127,0.3); color: var(--accent-pink); margin-bottom: 24px;
        }
        .hero h1 { font-family: 'Outfit', sans-serif; font-size: clamp(2.5rem, 5vw, 4.2rem); font-weight: 800; line-height: 1.15; margin-bottom: 20px; letter-spacing: -1px; }
        .hero h1 span { background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero p { font-size: 1.15rem; color: var(--text-sub); max-width: 680px; margin: 0 auto 36px; line-height: 1.6; }

        /* Paywall Card */
        .paywall-card {
            background: var(--glass-card); backdrop-filter: blur(24px); border: 1px solid var(--glass-border);
            border-radius: 28px; padding: 40px 48px; max-width: 480px; margin: 0 auto; box-shadow: 0 32px 80px rgba(0,0,0,0.6); text-align: center;
        }
        .price { font-family: 'Outfit', sans-serif; font-size: 3rem; font-weight: 800; color: #fff; margin: 12px 0 4px; }
        .price span { font-size: 1rem; color: var(--text-sub); font-weight: 500; }
        
        .features-list { list-style: none; margin: 24px 0 32px; text-align: left; display: flex; flex-direction: column; gap: 12px; font-size: 14px; color: #e2e8f0; }
        .features-list li { display: flex; align-items: center; gap: 10px; }
        .features-list li::before { content: "✓"; color: var(--accent-pink); font-weight: 800; }

        /* Modal Overlay */
        .modal-overlay {
            position: fixed; inset: 0; z-index: 100; background: rgba(0,0,0,0.75); backdrop-filter: blur(12px);
            display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        .modal-box {
            background: rgba(16, 12, 32, 0.95); border: 1px solid var(--glass-border); border-radius: 24px;
            padding: 36px; width: 100%; max-width: 420px; box-shadow: 0 30px 90px rgba(0,0,0,0.8); position: relative;
        }
        .modal-box h2 { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 700; margin-bottom: 8px; }
        .modal-box p { font-size: 14px; color: var(--text-sub); margin-bottom: 24px; }

        .form-group { margin-bottom: 18px; text-align: left; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--text-sub); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-input {
            width: 100%; padding: 14px 16px; border-radius: 12px; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border);
            color: #fff; font-size: 15px; outline: none; transition: border-color 0.2s ease;
        }
        .form-input:focus { border-color: var(--accent-pink); }

        .error-msg { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #fca5a5; padding: 10px; border-radius: 10px; font-size: 13px; margin-bottom: 16px; }

        /* Grid for Captcha node selection */
        .captcha-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin: 20px 0; }
        .captcha-node {
            background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 16px;
            padding: 20px; text-align: center; cursor: pointer; transition: all 0.2s ease;
        }
        .captcha-node:hover { background: rgba(255,0,127,0.1); border-color: var(--accent-pink); transform: scale(1.02); }
        .captcha-node-icon { font-size: 24px; margin-bottom: 6px; }
        .captcha-node-title { font-size: 12px; font-weight: 700; color: #fff; }
    </style>
</head>
<body x-data="{
    showLoginModal: false,
    showSignUpToast: false,
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
            <button class="nav-btn btn-ghost" @click="showSignUpToast = true">Try Free</button>
            <button class="nav-btn btn-accent" @click="showLoginModal = true">Sign In</button>
        </div>
    </header>

    <main class="hero">
        <div class="badge">Next-Gen Audio Infrastructure</div>
        <h1>Pure Lossless Sound. <span>Master Quality Stream.</span></h1>
        <p>Stream over 100 Million tracks in uncompressed 24-bit/192kHz Spatial Audio. Designed for audiophiles, producers, and sound engineers.</p>

        <div class="paywall-card">
            <div style="font-size: 12px; font-weight: 700; color: var(--accent-pink); text-transform: uppercase; letter-spacing: 1px;">Pro Pass Membership</div>
            <div class="price">$19.99 <span>/ month</span></div>
            <p style="font-size: 13px; color: var(--text-sub);">Unlimited access on all desktop & mobile devices</p>

            <ul class="features-list">
                <li>Uncompressed FLAC & Spatial Audio Engine</li>
                <li>Zero Compression & Custom Equalizer DSP</li>
                <li>Exclusive Studio Master Stems & Direct Sync</li>
                <li>Encrypted Offline Storage Protocol</li>
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
                    <input type="email" x-model="email" class="form-input" placeholder="admin@okotunes.com" required />
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
            <h2>Security Verification</h2>
            <p style="margin-bottom: 16px;">Confirm you are not an automated robot. Select the <strong style="color: var(--accent-pink);">44.1kHz Lossless Frequency Node</strong> to unlock session authorization.</p>

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

            <div style="font-size: 11px; color: var(--text-sub);">
                Hint: Standard CD Quality Frequency Calibration
            </div>
        </div>
    </div>

    <!-- Registration Toast -->
    <div class="modal-overlay" x-show="showSignUpToast" x-cloak style="display: none;">
        <div class="modal-box" style="text-align: center;">
            <div style="font-size: 40px; margin-bottom: 12px;">🔒</div>
            <h2>Subscribers Only</h2>
            <p>New subscriber registrations are currently closed. OkoStream access is strictly by invitation only.</p>
            <button class="nav-btn btn-ghost" style="width: 100%; padding: 12px; margin-top: 12px;" @click="showSignUpToast = false">Close</button>
        </div>
    </div>
</body>
</html>
