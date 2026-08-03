<?php
/**
 * decoy.php - The "Bottomless Pit" Honeypot View for okotunes.
 * Displays a complete, convincing dark glassmorphic player UI, but stays trapped in an infinite buffering loop with 0 real streams.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>OkoStream • Hi-Res Cloud Player</title>
    <link rel="icon" href="data:,">
    <script src="assets/alpine.min.js" defer></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;600;700;800&display=swap');
        
        :root {
            --bg-gradient: linear-gradient(135deg, #090814 0%, #151128 50%, #0d0a1b 100%);
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
            --glass-hover: rgba(255, 255, 255, 0.07);
            --accent-gradient: linear-gradient(135deg, #ff007f 0%, #7928ca 100%);
            --text-main: #f8fafc;
            --text-sub: #94a3b8;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; user-select: none; }
        body { background: var(--bg-gradient); color: var(--text-main); height: 100vh; overflow: hidden; display: flex; flex-direction: column; }

        .app-header {
            height: 64px; padding: 0 24px; display: flex; align-items: center; justify-content: space-between;
            background: rgba(10, 8, 20, 0.7); backdrop-filter: blur(20px); border-bottom: 1px solid var(--glass-border);
        }
        .logo { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .main-layout { display: flex; flex: 1; overflow: hidden; }
        
        .sidebar { width: 320px; background: rgba(8, 6, 16, 0.5); border-right: 1px solid var(--glass-border); padding: 20px; display: flex; flex-direction: column; gap: 16px; }
        .sidebar-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-sub); }
        
        .track-item {
            padding: 12px 16px; border-radius: 12px; background: var(--glass-bg); border: 1px solid var(--glass-border);
            display: flex; align-items: center; gap: 12px; cursor: pointer; transition: all 0.2s ease;
        }
        .track-item:hover { background: var(--glass-hover); border-color: rgba(255, 0, 127, 0.3); }

        .stage { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; text-align: center; position: relative; }
        
        .disc-wrapper { position: relative; width: 240px; height: 240px; margin-bottom: 32px; }
        .disc-cover { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 4px solid rgba(255,0,127,0.3); box-shadow: 0 20px 50px rgba(0,0,0,0.8); animation: spin 12s linear infinite; }
        
        @keyframes spin { 100% { transform: rotate(360deg); } }

        .status-pill {
            display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 30px;
            background: rgba(255, 0, 127, 0.1); border: 1px solid rgba(255, 0, 127, 0.3); color: #ff007f;
            font-size: 13px; font-weight: 600; margin-bottom: 16px;
        }
        .spinner { width: 14px; height: 14px; border: 2px solid rgba(255,0,127,0.3); border-top-color: #ff007f; border-radius: 50%; animation: spin 0.8s linear infinite; }

        .player-bar {
            height: 90px; background: rgba(10, 8, 20, 0.85); backdrop-filter: blur(24px); border-top: 1px solid var(--glass-border);
            display: flex; align-items: center; justify-content: space-between; padding: 0 28px;
        }
        .progress-container { width: 100%; max-width: 500px; }
        .progress-bar { width: 100%; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden; position: relative; }
        .progress-fill { height: 100%; width: 25%; background: var(--accent-gradient); position: relative; animation: pulse 2s ease-in-out infinite alternate; }
        
        @keyframes pulse { 0% { opacity: 0.5; } 100% { opacity: 1; } }
    </style>
</head>
<body x-data="{ currentTrack: 'Connecting to OkoStream Server...', status: 'Buffering stream signal (0%)...' }">
    <header class="app-header">
        <div class="logo">OkoStream Pro</div>
        <div style="display: flex; align-items: center; gap: 12px; font-size: 13px; color: var(--text-sub);">
            <div class="spinner"></div>
            <span>Encrypted Node Session</span>
        </div>
    </header>

    <div class="main-layout">
        <aside class="sidebar">
            <div class="sidebar-title">Cloud Library</div>
            <div class="track-item" @click="currentTrack = 'Connecting to OkoStream Server...'">
                <div class="spinner"></div>
                <div>
                    <div style="font-weight: 600; font-size: 14px;">Connecting to OkoStream...</div>
                    <div style="font-size: 12px; color: var(--text-sub);">Lossless Audio Stream</div>
                </div>
            </div>
            <div class="track-item" @click="currentTrack = 'Buffering Lossless Signal...'">
                <div class="spinner"></div>
                <div>
                    <div style="font-weight: 600; font-size: 14px;">Buffering Signal...</div>
                    <div style="font-size: 12px; color: var(--text-sub);">Spatial Master FLAC</div>
                </div>
            </div>
            <div class="track-item" @click="currentTrack = 'Authenticating Cipher Handshake...'">
                <div class="spinner"></div>
                <div>
                    <div style="font-weight: 600; font-size: 14px;">Cipher Handshake...</div>
                    <div style="font-size: 12px; color: var(--text-sub);">Security Node</div>
                </div>
            </div>
        </aside>

        <main class="stage">
            <div class="status-pill">
                <div class="spinner"></div>
                <span x-text="status"></span>
            </div>
            <div class="disc-wrapper">
                <img src="assets/splash_bg.jpg" class="disc-cover" alt="Album Cover" />
            </div>
            <h2 style="font-family: 'Outfit'; font-size: 26px; font-weight: 700;" x-text="currentTrack"></h2>
            <p style="color: var(--text-sub); margin-top: 8px; font-size: 14px;">Synchronizing server buffers • Please keep window open...</p>
        </main>
    </div>

    <footer class="player-bar">
        <div>
            <div style="font-weight: 600; font-size: 14px;" x-text="currentTrack"></div>
            <div style="font-size: 12px; color: var(--text-sub);">Buffer Rate: 0.0 KB/s</div>
        </div>
        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-sub); margin-top: 6px;">
                <span>0:00</span>
                <span>BUFFERING</span>
                <span>3:00</span>
            </div>
        </div>
        <div>
            <a href="index.php?action=logout" style="color: var(--text-sub); font-size: 12px; text-decoration: none;">Sign Out</a>
        </div>
    </footer>
</body>
</html>
