<?php // index.php - okotunes Studio Pro & Mobile
require_once __DIR__ . '/auth.php';

$authStatus = get_auth_status();

if ($authStatus === 'decoy' || (isset($_GET['mode']) && $_GET['mode'] === 'pit' && $authStatus !== 'authenticated')) {
    require __DIR__ . '/decoy.php';
    exit;
}

if ($authStatus !== 'authenticated') {
    require __DIR__ . '/landing.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="theme-color" content="#FF007F" />
    <title>okotunes • Studio Pro & Mobile</title>
    <link rel="manifest" href="manifest.json" />
    <link rel="icon" href="data:,">
    
    <script src="assets/alpine.min.js" defer></script>
    <style>
        /* ── SPLASH SCREEN ──────────────────────────────────────────── */
        #okotunes-splash {
            position: fixed; inset: 0; z-index: 9999;
            display: flex; align-items: center; justify-content: center;
            background: #060911;
            transition: opacity 0.7s ease, visibility 0.7s ease;
        }
        #okotunes-splash.hidden { opacity: 0; visibility: hidden; pointer-events: none; }
        .splash-bg {
            position: absolute; inset: 0;
            background: url('assets/splash_bg.jpg') center/cover no-repeat;
            filter: blur(8px) brightness(0.35);
            transform: scale(1.08);
        }
        .splash-card {
            position: relative; z-index: 1;
            background: rgba(6, 9, 17, 0.55);
            backdrop-filter: blur(24px) saturate(1.8);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 28px;
            padding: 48px 56px;
            display: flex; flex-direction: column; align-items: center;
            gap: 28px;
            box-shadow: 0 32px 80px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,0,127,0.08);
            min-width: 280px;
        }
        .splash-logo-ring {
            width: 88px; height: 88px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FF007F, #7928CA);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 40px rgba(255,0,127,0.45);
            animation: splash-pulse 2s ease-in-out infinite;
        }
        @keyframes splash-pulse {
            0%,100% { box-shadow: 0 0 30px rgba(255,0,127,0.4); }
            50% { box-shadow: 0 0 60px rgba(255,0,127,0.7), 0 0 100px rgba(121,40,202,0.3); }
        }
        .splash-logo-icon { font-size: 2.4rem; }
        .splash-brand { text-align: center; }
        .splash-brand h1 {
            font-size: 2rem; font-weight: 800;
            background: linear-gradient(135deg, #FF007F, #7928CA);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }
        .splash-brand p { font-size: 0.8rem; color: rgba(255,255,255,0.4); margin-top: 4px; letter-spacing: 1.5px; text-transform: uppercase; }
        .splash-progress-track {
            width: 100%; height: 3px;
            background: rgba(255,255,255,0.08);
            border-radius: 99px; overflow: hidden;
        }
        .splash-progress-fill {
            height: 100%; width: 0%;
            background: linear-gradient(90deg, #FF007F, #7928CA);
            border-radius: 99px;
            transition: width 0.1s linear;
        }
        /* ── COVER ART MODAL ─────────────────────────────────────── */
        .art-modal-overlay {
            position: fixed; inset: 0; z-index: 900;
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(6px);
            display: flex; align-items: center; justify-content: center;
        }
        .art-modal-card {
            background: rgba(12,16,28,0.97);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            padding: 40px 44px;
            min-width: 320px; max-width: 380px;
            display: flex; flex-direction: column; align-items: center;
            gap: 20px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.5);
            text-align: center;
        }
        .art-modal-icon { font-size: 2.4rem; line-height: 1; }
        .art-modal-card h3 { font-size: 1.25rem; font-weight: 700; color: #fff; }
        .art-modal-card p { font-size: 0.88rem; color: var(--text-secondary); line-height: 1.5; }
        .art-modal-actions { display: flex; gap: 12px; margin-top: 4px; }
        .art-btn-cancel {
            padding: 10px 24px; border-radius: 12px;
            background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12);
            color: var(--text-secondary); cursor: pointer; font-size: 0.9rem;
        }
        .art-btn-go {
            padding: 10px 28px; border-radius: 12px;
            background: linear-gradient(135deg, #FF007F, #7928CA);
            border: none; color: #fff; cursor: pointer;
            font-size: 0.9rem; font-weight: 600;
        }
        .art-spinner-wrap { display: flex; flex-direction: column; align-items: center; gap: 14px; }
        .art-spinner {
            width: 52px; height: 52px;
            border: 3px solid rgba(255,255,255,0.1);
            border-top-color: #FF007F;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── FAINT MINIMAL DELETE BUTTON ──────────────────────────── */
        .track-del-btn {
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.22);
            opacity: 0.3;
            cursor: pointer;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        .track-del-btn:hover {
            color: #FF4D6D;
            opacity: 1;
            background: rgba(255, 0, 85, 0.12);
        }
        .delete-action-btn {
            background: rgba(255, 255, 255, 0.03) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            color: rgba(255, 255, 255, 0.35) !important;
            opacity: 0.55;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .delete-action-btn:hover {
            background: rgba(255, 0, 85, 0.15) !important;
            border-color: rgba(255, 0, 85, 0.35) !important;
            color: #FF4D6D !important;
            opacity: 1;
        }
        .art-status-text { font-size: 0.88rem; color: var(--text-secondary); min-height: 20px; }
        .art-result-ok { color: #00FF88; font-weight: 600; font-size: 0.95rem; }
        .art-result-fail { color: rgba(255,100,100,0.9); font-size: 0.88rem; }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-dark: #060911;
            --bg-glass: rgba(6, 9, 17, 0.15);
            --bg-card: rgba(255, 255, 255, 0.025);
            --bg-card-hover: rgba(255, 255, 255, 0.07);
            --border-color: rgba(255, 255, 255, 0.04);
            --accent-pink: #FF007F;
            --accent-purple: #7928CA;
            --accent-cyan: #00F2FE;
            --accent-green: #00FF88;
            --text-primary: #FFFFFF;
            --text-secondary: #94A3B8;
            --font-family: 'Outfit', 'Inter', system-ui, -apple-system, sans-serif;
        }

        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

        html, body {
            height: 100%;
            overflow: hidden;
            background: var(--bg-dark);
            color: var(--text-primary);
            font-family: var(--font-family);
        }

        /* ── CRISP MINIMAL BLURRED ARTWORK WALLPAPER ─────────────── */
        .ambient-mesh-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            background-image: url('assets/wallpaper%20background%20placeholder.png');
            background-size: cover;
            background-position: center;
            filter: blur(20px) brightness(0.6);
            opacity: 0.95;
            transition: background-image 0.8s ease-in-out, opacity 0.8s ease-in-out;
            pointer-events: none;
        }


        .ambient-overlay {
            position: fixed;
            inset: 0;
            z-index: 1;
            background: radial-gradient(circle at 50% 30%, rgba(121, 40, 202, 0.12), transparent 70%),
                        linear-gradient(to bottom, rgba(6, 9, 17, 0.15), rgba(6, 9, 17, 0.7));
            pointer-events: none;
        }

        /* ───────────────────────────────────────────────────────────── */
        /* 🖥️ DESKTOP MODE: MSPOT STUDIO PRO (min-width: 1025px)         */
        /* ───────────────────────────────────────────────────────────── */
        @media (min-width: 1025px) {
            #studio-app {
                display: grid;
                grid-template-columns: 64px 340px 1fr 300px;
                grid-template-rows: 68px 1fr 82px;
                grid-template-areas:
                    "dock header header header"
                    "dock lib    stage  queue"
                    "player player player player";
                height: 100vh;
                width: 100vw;
                position: relative;
                z-index: 10;
            }

            /* Zone 1: Left Action Dock (64px) */
            .studio-dock {
                grid-area: dock;
                background: var(--bg-glass);
                backdrop-filter: blur(24px) saturate(1.8);
                -webkit-backdrop-filter: blur(24px) saturate(1.8);
                border-right: none;
                box-shadow: 4px 0 24px rgba(0,0,0,0.15);
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 20px 0;
                gap: 20px;
                z-index: 20;
            }


            .dock-logo {
                font-size: 1.5rem;
                font-weight: 800;
                color: var(--accent-pink);
                margin-bottom: 10px;
            }

            .dock-btn {
                width: 44px;
                height: 44px;
                border-radius: 12px;
                background: transparent;
                border: 1px solid transparent;
                color: var(--text-secondary);
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.2s ease;
                outline: none;
            }

            .dock-btn:hover, .dock-btn.active {
                background: rgba(255, 0, 127, 0.2);
                border-color: rgba(255, 0, 127, 0.4);
                color: var(--accent-pink);
            }

            .dock-btn svg {
                width: 22px;
                height: 22px;
                stroke: currentColor;
                fill: none;
                stroke-width: 2;
                stroke-linecap: round;
                stroke-linejoin: round;
            }

            /* Header Bar */
            .studio-header {
                grid-area: header;
                background: var(--bg-glass);
                backdrop-filter: blur(24px) saturate(1.8);
                -webkit-backdrop-filter: blur(24px) saturate(1.8);
                border-bottom: none;
                display: flex;
                align-items: center;
                padding: 0 28px;
                gap: 20px;
            }

            .header-title {
                font-size: 1.25rem;
                font-weight: 800;
                letter-spacing: 1px;
            }

            .header-search {
                flex: 1;
                max-width: 420px;
                background: rgba(255, 255, 255, 0.04);
                border: 1px solid rgba(255, 255, 255, 0.05);
                border-radius: 20px;
                padding: 10px 18px;
                color: #FFF;
                font-family: inherit;
                font-size: 0.9rem;
                outline: none;
                transition: all 0.3s ease;
            }

            .header-search:focus {
                border-color: var(--accent-pink);
                box-shadow: 0 0 15px rgba(255, 0, 127, 0.2);
            }

            .header-badge {
                font-size: 0.8rem;
                color: var(--accent-green);
                background: rgba(0, 255, 136, 0.12);
                border: 1px solid rgba(0, 255, 136, 0.25);
                padding: 6px 14px;
                border-radius: 16px;
                font-weight: 600;
            }

            .header-clock-badge {
                font-size: 0.8rem;
                font-weight: 700;
                color: var(--accent-cyan);
                background: rgba(0, 242, 254, 0.12);
                border: 1px solid rgba(0, 242, 254, 0.25);
                padding: 6px 14px;
                border-radius: 16px;
                letter-spacing: 1px;
            }

            /* Zone 2: Dedicated Library Panel (340px) */
            .studio-lib {
                grid-area: lib;
                background: var(--bg-glass);
                backdrop-filter: blur(24px) saturate(1.8);
                -webkit-backdrop-filter: blur(24px) saturate(1.8);
                border-right: none;
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }


            .lib-track-list {
                flex: 1;
                overflow-y: auto;
                list-style: none;
                padding: 12px;
            }

            .lib-track-item {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 10px 12px;
                border-radius: 12px;
                margin-bottom: 6px;
                cursor: pointer;
                transition: all 0.2s ease;
                border: 1px solid transparent;
                background: var(--bg-card);
            }

            .lib-track-item:hover {
                background: var(--bg-card-hover);
                transform: translateX(4px);
                border-color: rgba(255, 255, 255, 0.1);
            }

            .lib-track-item.active {
                background: linear-gradient(90deg, rgba(255, 0, 127, 0.22), rgba(121, 40, 202, 0.22));
                border-color: rgba(255, 0, 127, 0.4);
            }

            .track-art-thumb {
                width: 44px;
                height: 44px;
                border-radius: 8px;
                object-fit: cover;
                background: linear-gradient(135deg, #1E1B4B 0%, #31103F 100%);
            }

            .track-meta-title {
                font-size: 0.88rem;
                font-weight: 600;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 180px;
            }

            .track-format-badge {
                font-size: 0.65rem;
                font-weight: 700;
                color: var(--accent-cyan);
                background: rgba(0, 242, 254, 0.12);
                padding: 2px 6px;
                border-radius: 4px;
            }

            /* Zone 3: Spatial Visual Stage */
            .studio-stage {
                grid-area: stage;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                position: relative;
                padding: 20px;
                text-align: center;
                overflow: hidden;
            }

            .hero-artwork-card {
                max-height: 35vh;
                max-width: 35vh;
                width: 280px;
                height: 280px;
                aspect-ratio: 1;
                border-radius: 24px;
                object-fit: cover;
                box-shadow: 0 30px 60px rgba(0, 0, 0, 0.75), 0 0 40px rgba(255, 0, 127, 0.3);
                border: 1px solid rgba(255, 255, 255, 0.15);
                transition: transform 0.3s ease;
                background: linear-gradient(135deg, #1E1B4B 0%, #31103F 100%);
            }

            .hero-artwork-card:hover {
                transform: scale(1.02);
            }

            .hero-track-title {
                font-size: 1.6rem;
                font-weight: 800;
                margin-top: 16px;
                max-width: 520px;
                background: linear-gradient(to bottom, #FFFFFF, #E2E8F0);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 12px;
            }

            .subtle-eq {
                display: inline-flex;
                align-items: flex-end;
                gap: 3px;
                height: 16px;
            }

            .subtle-eq span {
                width: 3px;
                background: var(--accent-pink);
                border-radius: 2px;
                animation: eqPulse 1s ease-in-out infinite alternate;
            }

            .subtle-eq span:nth-child(1) { animation-delay: 0.1s; height: 12px; }
            .subtle-eq span:nth-child(2) { animation-delay: 0.3s; height: 16px; }
            .subtle-eq span:nth-child(3) { animation-delay: 0.2s; height: 8px; }

            @keyframes eqPulse {
                0% { height: 4px; }
                100% { height: 16px; }
            }

            .like-btn {
                background: transparent;
                border: none;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 6px;
                outline: none;
            }

            .like-btn svg {
                width: 24px;
                height: 24px;
                fill: transparent;
                stroke: var(--text-secondary);
                stroke-width: 2;
                transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
            }

            .like-btn svg.liked {
                fill: #FF007F;
                stroke: #FF007F;
                filter: drop-shadow(0 0 12px rgba(255, 0, 127, 0.8));
                transform: scale(1.15);
            }

            .stage-action-row {
                display: flex;
                gap: 14px;
                margin-top: 14px;
                align-items: center;
            }

            .stage-action-btn {
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid var(--border-color);
                color: #FFF;
                padding: 8px 18px;
                border-radius: 20px;
                font-weight: 600;
                font-size: 0.85rem;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 8px;
                transition: all 0.2s ease;
            }

            .stage-action-btn:hover {
                background: var(--accent-pink);
                border-color: var(--accent-pink);
            }

            /* Zone 4: Simplified Recently Played Drawer (300px) */
            .studio-queue {
                grid-area: queue;
                background: var(--bg-glass);
                backdrop-filter: blur(24px) saturate(1.8);
                -webkit-backdrop-filter: blur(24px) saturate(1.8);
                border-left: none;
                padding: 20px;
                display: flex;
                flex-direction: column;
                gap: 14px;
                overflow-y: auto;
            }

            .queue-history-header {
                font-size: 0.78rem;
                font-weight: 800;
                letter-spacing: 1px;
                color: var(--text-secondary);
                text-transform: uppercase;
                margin-bottom: 4px;
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .history-card {
                background: var(--bg-card);
                border: 1px solid rgba(255, 255, 255, 0.03);
                border-radius: 14px;
                padding: 10px 12px;
                display: flex;
                align-items: center;
                gap: 10px;
                cursor: pointer;
                transition: all 0.25s ease;
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            }

                border-radius: 12px;
                padding: 8px 10px;
                display: flex;
                align-items: center;
                gap: 10px;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .history-card:hover {
                background: var(--bg-card-hover);
                border-color: rgba(255, 0, 127, 0.3);
                transform: translateX(3px);
            }

            .history-art {
                width: 38px;
                height: 38px;
                border-radius: 8px;
                object-fit: cover;
                background: linear-gradient(135deg, #1E1B4B 0%, #31103F 100%);
            }

            /* Fixed Bottom Player Control Bar */
            .studio-player {
                grid-area: player;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: 82px;
                background: rgba(10, 14, 26, 0.92);
                backdrop-filter: blur(20px);
                border-top: 1px solid var(--border-color);
                display: grid;
                grid-template-columns: 300px 1fr 260px;
                align-items: center;
                padding: 0 32px;
                gap: 20px;
                z-index: 100;
                box-shadow: 0 -10px 30px rgba(0,0,0,0.6);
            }

            #mobile-app { display: none !important; }
        }

        /* ───────────────────────────────────────────────────────────── */
        /* 📱 MOBILE MODE: MSPOT GO (max-width: 1024px)                  */
        /* ───────────────────────────────────────────────────────────── */
        @media (max-width: 1024px) {
            #studio-app { display: none !important; }

            #mobile-app {
                display: flex;
                flex-direction: column;
                height: 100vh;
                height: 100dvh;
                width: 100vw;
                position: relative;
                z-index: 10;
            }

            .go-header {
                height: 60px;
                background: rgba(14, 20, 36, 0.65);
                backdrop-filter: blur(12px);
                border-bottom: 1px solid var(--border-color);
                display: flex;
                align-items: center;
                padding: 0 20px;
                justify-content: space-between;
            }

            .go-content {
                flex: 1;
                overflow-y: auto;
                position: relative;
            }

            .go-player-card {
                height: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 24px 20px;
                text-align: center;
            }

            .go-hero-art {
                width: 220px;
                height: 220px;
                border-radius: 20px;
                object-fit: cover;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6), 0 0 30px rgba(255, 0, 127, 0.3);
                background: linear-gradient(135deg, #1E1B4B 0%, #31103F 100%);
            }

            .go-track-name {
                font-size: 1.3rem;
                font-weight: 800;
                margin-top: 16px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 86vw;
                padding: 0 10px;
            }

            .go-action-row {
                display: flex;
                gap: 16px;
                margin-top: 12px;
                align-items: center;
            }

            .go-action-btn {
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid var(--border-color);
                color: #FFF;
                padding: 6px 14px;
                border-radius: 16px;
                font-size: 0.8rem;
                font-weight: 600;
                cursor: pointer;
            }

            .go-seek-box {
                width: 100%;
                max-width: 320px;
                margin-top: 16px;
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .go-mini-player {
                position: fixed;
                bottom: 70px;
                left: 10px;
                right: 10px;
                height: 56px;
                background: rgba(16, 22, 38, 0.94);
                backdrop-filter: blur(16px);
                border: 1px solid rgba(255, 0, 127, 0.3);
                border-radius: 16px;
                display: flex;
                align-items: center;
                padding: 0 12px;
                gap: 12px;
                z-index: 90;
                box-shadow: 0 10px 25px rgba(0,0,0,0.6);
            }

            /* Icon-Only Thumb Navigation Dock */
            .go-thumb-dock {
                height: 64px;
                background: rgba(14, 20, 36, 0.92);
                backdrop-filter: blur(16px);
                border-top: 1px solid var(--border-color);
                display: flex;
                justify-content: space-around;
                align-items: center;
            }

            .go-thumb-btn {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100%;
                color: var(--text-secondary);
                background: none;
                border: none;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .go-thumb-btn.active {
                color: var(--accent-pink);
            }

            .go-thumb-btn svg {
                width: 26px;
                height: 26px;
                stroke: currentColor;
                fill: none;
                stroke-width: 2.2;
            }

            /* Control Button Active State Glow */
            .ctrl-active {
                color: var(--accent-pink) !important;
                background: rgba(255, 0, 127, 0.15) !important;
                border-radius: 50%;
                box-shadow: 0 0 12px rgba(255, 0, 127, 0.6);
            }

            /* Toast Notification Badge */
            .toast-pill {
                position: fixed;
                top: 70px;
                left: 50%;
                transform: translateX(-50%);
                background: rgba(255, 0, 127, 0.9);
                color: #FFFFFF;
                padding: 6px 18px;
                border-radius: 20px;
                font-size: 0.8rem;
                font-weight: 700;
                z-index: 999;
                box-shadow: 0 4px 15px rgba(255, 0, 127, 0.5);
                pointer-events: none;
            }
        }

        /* ── MODALS STYLING ────────────────────────────────────── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.82);
            backdrop-filter: blur(12px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-card {
            background: #101626;
            border: 1px solid rgba(255, 0, 127, 0.35);
            border-radius: 20px;
            width: 100%;
            max-width: 520px;
            padding: 28px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.7);
            color: var(--text-primary);
        }

        /* ── SPATIAL AUDIO MODAL STYLING ───────────────────────── */
        .spatial-modal-card {
            background: rgba(14, 18, 28, 0.96) !important;
            backdrop-filter: blur(24px) !important;
            -webkit-backdrop-filter: blur(24px) !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            max-width: 440px !important;
            padding: 22px !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8) !important;
            max-height: 88vh;
            display: flex;
            flex-direction: column;
        }

        .spatial-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .spatial-title-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .spatial-back-btn {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-size: 1.4rem;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            transition: color 0.2s ease;
        }

        .spatial-back-btn:hover {
            color: #FFF;
        }

        .spatial-list {
            display: flex;
            flex-direction: column;
            gap: 2px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .spatial-list::-webkit-scrollbar {
            width: 5px;
        }

        .spatial-list::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 3px;
        }

        .spatial-option-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 12px 14px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }

        .spatial-option-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .spatial-option-item.active {
            background: rgba(0, 242, 254, 0.08);
        }

        .spatial-radio-outer {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 2px;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }

        .spatial-option-item.active .spatial-radio-outer {
            border-color: var(--accent-cyan);
            box-shadow: 0 0 10px rgba(0, 242, 254, 0.4);
        }

        .spatial-radio-inner {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--accent-cyan);
            opacity: 0;
            transform: scale(0.5);
            transition: all 0.2s ease;
        }

        .spatial-option-item.active .spatial-radio-inner {
            opacity: 1;
            transform: scale(1);
        }

        .spatial-option-title {
            font-size: 0.98rem;
            font-weight: 600;
            color: #E2E8F0;
        }

        .spatial-option-item.active .spatial-option-title {
            color: var(--accent-cyan);
            font-weight: 700;
        }

        .spatial-option-subtitle {
            font-size: 0.78rem;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        .drop-zone {
            border: 2px dashed rgba(255, 0, 127, 0.4);
            border-radius: 16px;
            padding: 32px 20px;
            text-align: center;
            background: rgba(255, 255, 255, 0.03);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .drop-zone:hover, .drop-zone.dragover {
            border-color: var(--accent-pink);
            background: rgba(255, 0, 127, 0.08);
        }

        /* Multi-File Upload Queue Styling */
        .upload-queue-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 250px;
            overflow-y: auto;
            margin-top: 14px;
            padding-right: 4px;
        }

        .upload-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 10px 14px;
            gap: 12px;
            position: relative;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .upload-item.completed {
            border-color: rgba(0, 255, 136, 0.4);
            background: rgba(0, 255, 136, 0.06);
        }

        .upload-item.error {
            border-color: rgba(255, 51, 102, 0.4);
            background: rgba(255, 51, 102, 0.06);
        }

        .upload-item-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-pink), var(--accent-green));
            transition: width 0.2s ease;
        }

        .upload-remove-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.1rem;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .upload-remove-btn:hover {
            color: #FF3366;
            background: rgba(255, 51, 102, 0.15);
        }
    </style>
</head>
<body>

<!-- ── SPLASH SCREEN (once per session) ──────────────────────────────── -->
<div id="okotunes-splash">
    <div class="splash-bg"></div>
    <div class="splash-card">
        <div class="splash-logo-ring">
            <span class="splash-logo-icon">🎵</span>
        </div>
        <div class="splash-brand">
            <h1>okotunes</h1>
            <p>Studio Pro &amp; Mobile</p>
        </div>
        <div class="splash-progress-track" style="width: 200px;">
            <div class="splash-progress-fill" id="splash-bar"></div>
        </div>
    </div>
</div>

<div x-data="okotunesApp()" x-init="boot()">

    <!-- Toast Notification Badge -->
    <div class="toast-pill" x-show="toastMsg" x-text="toastMsg" x-transition x-cloak></div>

    <!-- Crisp Dynamic Soft Blurred Artwork Wallpaper -->
    <div class="ambient-mesh-bg" :style="currentArt ? 'background-image:url(' + currentArt + ')' : 'background-image:url(assets/wallpaper%20background%20placeholder.png)'"></div>
    <div class="ambient-overlay"></div>


    <!-- ─────────────────────────────────────────────────────────────── -->
    <!-- 🖥️ DESKTOP WORKSTATION: MSPOT STUDIO PRO                        -->
    <!-- ─────────────────────────────────────────────────────────────── -->
    <div id="studio-app">
        <!-- Zone 1: Left Action Dock (64px) -->
        <aside class="studio-dock">
            <div class="dock-logo">🎧</div>
            <a class="dock-btn" href="/index.php" title="Gateway Home">
                <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </a>
            <button class="dock-btn" :class="{active: studioTab === 'library'}" @click="studioTab = 'library'" title="Music Library">
                <svg viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
            </button>
            <button class="dock-btn" @click="showUploadModal = true" title="Upload Music">
                <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </button>
            <button class="dock-btn" @click="downloadCurrentTrack()" :disabled="!current" title="Download Song">
                <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </button>
            <button class="dock-btn" @click="openAnalyticsModal()" title="AI Song Analytics">
                <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            </button>
            <button class="dock-btn" :class="{active: spatialMode !== 'off'}" @click="showSpatialModal = true" title="Spatial Audio & EQ">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 10v4M6 6v12M10 3v18M14 6v12M18 10v4M22 12v0"/></svg>
            </button>
        </aside>

        <!-- Header -->
        <header class="studio-header">
            <div class="header-title">okotunes Studio Pro</div>
            <input class="header-search" type="text" placeholder="🔍  Search 2,970+ tracks..." x-model="query" @input="filter()" />
            <div class="header-badge" x-text="tracks.length + ' tracks loaded'"></div>
            <div class="header-clock-badge" x-text="clockTime"></div>
        </header>

        <!-- Zone 2: Dedicated Library Panel (340px) -->
        <nav class="studio-lib">
            <ul class="lib-track-list">
                <template x-for="(t, i) in filtered" :key="t.url">
                    <li class="lib-track-item" :class="{active: current && current.url === t.url}" @click="play(i)">
                        <img class="track-art-thumb" :src="t.artUrl || 'assets/album_placeholder.png'" @error="$el.onerror=null; $el.src='assets/album_placeholder.png'" alt="" />
                        <div style="flex: 1; overflow: hidden;">
                            <div class="track-meta-title" x-text="cleanTitle(t.name)"></div>
                            <div style="display: flex; gap: 6px; align-items: center; margin-top: 2px;">
                                <span class="track-format-badge">MP3</span>
                                <span style="font-size: 0.72rem; color: var(--accent-cyan); font-weight: 600;" x-show="statsCounts[t.url]" x-text="(statsCounts[t.url] || 0) + ' plays'"></span>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 4px;">
                            <button class="like-btn" @click.stop="toggleLike(t.url)" title="Like Track">
                                <svg viewBox="0 0 24 24" :class="{liked: isLiked(t.url)}">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l8.78-8.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                </svg>
                            </button>
                            <button class="track-del-btn" @click.stop="promptDeleteTrack(t)" title="Delete Track">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 2 0 0 1-2 2H7a2 2 2 0 0 1-2-2V6m3 0V4a2 2 2 0 0 1 2-2h4a2 2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </li>
                </template>
            </ul>
        </nav>

        <!-- Zone 3: Spatial Visual Stage -->
        <main class="studio-stage">
            <template x-if="current">
                <div style="display: flex; flex-direction: column; align-items: center;">
                    <img class="hero-artwork-card" :src="currentArt" @error="$el.onerror=null; $el.src='assets/album_placeholder.png'" alt="Artwork" />
                    
                    <div class="hero-track-title">
                        <span x-text="cleanTitle(current ? current.name : '')"></span>
                        <button class="like-btn" @click="toggleLike(current ? current.url : '')" title="Like Track">
                            <svg viewBox="0 0 24 24" :class="{liked: isLiked(current ? current.url : '')}">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l8.78-8.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                        </button>
                        <div class="subtle-eq" x-show="isPlaying">
                            <span></span><span></span><span></span>
                        </div>
                    </div>

                    <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px;">
                        okotunes Studio • High Quality Audio
                    </div>

                    <div class="stage-action-row">
                        <button class="stage-action-btn" @click="downloadCurrentTrack()">⬇️ Download Track</button>
                        <button class="stage-action-btn" @click="showUploadModal = true">☁️ Upload Music</button>
                        <button class="stage-action-btn" @click="showCoverArtModal = true" title="Get Cover Art" style="padding: 10px 14px; font-size: 1.1rem; min-width: unset;">✨</button>
                        <button class="stage-action-btn delete-action-btn" @click="promptDeleteTrack(current)" title="Delete Current Song">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 2 0 0 1-2 2H7a2 2 2 0 0 1-2-2V6m3 0V4a2 2 2 0 0 1 2-2h4a2 2 2 0 0 1 2 2v2"></path>
                            </svg>
                            <span>Delete</span>
                        </button>
                    </div>
                </div>
            </template>
            <template x-if="!current">
                <div style="text-align: center; color: var(--text-secondary);">
                    <div style="font-size: 4rem; margin-bottom: 12px;">🎧</div>
                    <h2>Select a track from the library to begin</h2>
                </div>
            </template>
        </main>

        <!-- Zone 4: Simplified Recently Played Drawer (300px) -->
        <aside class="studio-queue">
            <div class="queue-history-header">🕒 RECENTLY PLAYED</div>
            <template x-for="(rName, idx) in recent" :key="idx">
                <div class="history-card" @click="playByName(rName)">
                    <img class="history-art" :src="getTrackArtByName(rName)" @error="$el.onerror=null; $el.src='assets/album_placeholder.png'" alt="" />
                    <div style="flex: 1; overflow: hidden;">
                        <div style="font-size: 0.82rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" x-text="cleanTitle(rName)"></div>
                        <div style="font-size: 0.68rem; color: var(--accent-cyan);">Played Recently</div>
                    </div>
                </div>
            </template>
            <template x-if="recent.length === 0">
                <div style="font-size: 0.8rem; color: var(--text-secondary); padding: 12px;">No history yet — pick a song to play!</div>
            </template>
        </aside>

        <!-- Fixed Bottom Player Control Bar -->
        <footer class="studio-player">
            <div style="display: flex; align-items: center; gap: 12px; overflow: hidden;">
                <img :src="currentArt" @error="$el.onerror=null; $el.src='assets/album_placeholder.png'" style="width: 50px; height: 50px; border-radius: 10px; object-fit: cover; flex-shrink: 0;" x-show="current" />
                <div style="min-width: 0; overflow: hidden;">
                    <div style="font-weight: 700; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" x-text="cleanTitle(current ? current.name : 'No track selected')"></div>
                    <div style="font-size: 0.75rem; color: var(--accent-cyan); font-weight: 600;">okotunes Workstation</div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; align-items: center; gap: 6px;">
                <div style="display: flex; gap: 24px; align-items: center;">
                    <button style="background:none; border:none; color:var(--text-secondary); cursor:pointer; font-size: 1.1rem; padding:6px;" @click="toggleShuffle()" :class="{'ctrl-active': shuffle}">🔀</button>
                    <button style="background:none; border:none; color:#FFF; cursor:pointer; font-size: 1.2rem;" @click="prev()">⏮</button>
                    <button style="background: var(--accent-pink); border:none; color:#FFF; width:46px; height:46px; border-radius:50%; font-size:1.3rem; cursor:pointer; box-shadow: 0 4px 15px rgba(255, 0, 127, 0.4);" @click="togglePlay()">
                        <span x-text="isPlaying ? '⏸' : '▶'"></span>
                    </button>
                    <button style="background:none; border:none; color:#FFF; cursor:pointer; font-size: 1.2rem;" @click="next()">⏭</button>
                    <button style="background:none; border:none; color:var(--text-secondary); cursor:pointer; font-size: 1.1rem; padding:6px;" @click="cycleLoop()" :class="{'ctrl-active': loopMode !== 'none'}">🔁</button>
                </div>
                <div style="display: flex; gap: 12px; width: 100%; max-width: 520px; align-items: center;">
                    <span style="font-size: 0.75rem; color: var(--text-secondary);" x-text="elapsed"></span>
                    <input type="range" min="0" max="100" step="0.1" x-ref="seekBar" @input="seek($event)" style="flex: 1;" />
                    <span style="font-size: 0.75rem; color: var(--text-secondary);" x-text="duration"></span>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; align-items: center; gap: 12px;">
                <button class="like-btn" @click="toggleLike(current ? current.url : '')" title="Like Track">
                    <svg viewBox="0 0 24 24" :class="{liked: isLiked(current ? current.url : '')}">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l8.78-8.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                </button>
                <span style="color: var(--text-secondary);">🔊</span>
                <input type="range" min="0" max="1" step="0.01" value="1" @input="setVolume($event)" style="width: 80px;" />
                <button style="background: rgba(255,255,255,0.08); border: 1px solid var(--border-color); color: #FFF; padding: 6px 12px; border-radius: 12px; font-size: 0.78rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.2s ease;" :style="spatialMode !== 'off' ? 'border-color: var(--accent-cyan); color: var(--accent-cyan); background: rgba(0, 242, 254, 0.12);' : ''" @click="showSpatialModal = true" title="Spatial Audio & DSP Presets">
                    <svg style="width:15px; height:15px; fill:none; stroke:currentColor; stroke-width:2;" viewBox="0 0 24 24"><path d="M2 10v4M6 6v12M10 3v18M14 6v12M18 10v4"/></svg>
                    <span x-text="spatialModeLabel"></span>
                </button>
                <button style="background: rgba(255,255,255,0.1); border: 1px solid var(--border-color); color: #FFF; padding: 6px 14px; border-radius: 12px; font-size: 0.78rem; font-weight: 600; cursor: pointer;" @click="downloadCurrentTrack()" :disabled="!current">⬇️ Save</button>
            </div>
        </footer>
    </div>

    <!-- ─────────────────────────────────────────────────────────────── -->
    <!-- 📱 MOBILE APP: OKOTUNES GO                                         -->
    <!-- ─────────────────────────────────────────────────────────────── -->
    <div id="mobile-app">
        <header class="go-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <a href="/index.php" style="color: var(--text-secondary); display: flex; align-items: center; text-decoration: none;" title="Gateway Home">
                    <svg style="width: 22px; height: 22px; stroke: currentColor; fill: none; stroke-width: 2;" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                </a>
                <span style="font-weight: 800; color: var(--accent-pink);">MSpot Go</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <input type="text" placeholder="Search 2,970+..." x-model="query" @input="filter()" style="background: rgba(255,255,255,0.1); border:1px solid var(--border-color); border-radius: 14px; padding: 6px 12px; color:#FFF; width: 120px; font-size: 0.8rem; outline:none;" />
                <button @click="showUploadModal = true" style="background: rgba(255, 0, 127, 0.2); border: 1px solid var(--accent-pink); color: #FFF; border-radius: 14px; padding: 6px 10px; cursor: pointer; display: flex; align-items: center; justify-content: center;" title="Upload Music">
                    <svg style="width: 18px; height: 18px; stroke: currentColor; fill: none; stroke-width: 2;" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                </button>
            </div>
        </header>

        <main class="go-content">
            <!-- Mobile Tab 0: Player View -->
            <div class="go-player-card" x-show="mobileTab === 'player'"
                 @touchstart="handleTouchStart($event)" @touchend="handleTouchEnd($event)">
                
                <img class="go-hero-art" :src="currentArt" @error="$el.onerror=null; $el.src='assets/album_placeholder.png'" alt="Cover" />
                
                <div class="go-track-name" x-text="cleanTitle(current ? current.name : 'Pick a song')"></div>

                <div class="go-action-row" x-show="current">
                    <button class="like-btn" @click="toggleLike(current ? current.url : '')">
                        <svg viewBox="0 0 24 24" :class="{liked: isLiked(current ? current.url : '')}">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l8.78-8.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                    </button>
                    <button class="go-action-btn" @click="downloadCurrentTrack()">⬇️ Save Song</button>
                </div>

                <!-- Touch Seekbar & Timers -->
                <div class="go-seek-box" x-show="current">
                    <input type="range" min="0" max="100" step="0.1" x-ref="mobileSeekBar" @input="seek($event)" style="width: 100%; accent-color: var(--accent-pink);" />
                    <div style="display: flex; justify-content: space-between; font-size: 0.72rem; color: var(--text-secondary);">
                        <span x-text="elapsed"></span>
                        <span x-text="duration"></span>
                    </div>
                </div>

                <!-- Mobile Controls with Shuffle, Prev, Play/Pause, Next, Loop -->
                <div style="display: flex; gap: 16px; margin-top: 18px; align-items: center; justify-content: center;">
                    <button style="background:none; border:none; color:var(--text-secondary); cursor:pointer; font-size: 1.2rem; padding: 6px;" @click="toggleShuffle()" :class="{'ctrl-active': shuffle}">🔀</button>
                    <button style="background:none; border:none; color:#FFF; cursor:pointer; font-size: 1.5rem;" @click="prev()">⏮</button>
                    <button style="background: var(--accent-pink); border:none; color:#FFF; width:60px; height:60px; border-radius:50%; font-size:1.6rem; cursor:pointer; box-shadow: 0 4px 20px rgba(255, 0, 127, 0.45); display:flex; align-items:center; justify-content:center;" @click="togglePlay()">
                        <span x-text="isPlaying ? '⏸' : '▶'"></span>
                    </button>
                    <button style="background:none; border:none; color:#FFF; cursor:pointer; font-size: 1.5rem;" @click="next()">⏭</button>
                    <button style="background:none; border:none; color:var(--text-secondary); cursor:pointer; font-size: 1.2rem; padding: 6px;" @click="cycleLoop()" :class="{'ctrl-active': loopMode !== 'none'}">🔁</button>
                </div>
            </div>

            <!-- Mobile Tab 1: Library View -->
            <div x-show="mobileTab === 'library'" style="padding: 14px 14px 80px 14px;">
                <template x-for="(t, i) in filtered" :key="t.url">
                    <div style="display: flex; align-items: center; gap: 12px; padding: 10px 12px; background: rgba(255,255,255,0.04); border-radius: 12px; margin-bottom: 8px; border: 1px solid var(--border-color);" @click="play(i)">
                        <img :src="t.artUrl || 'assets/album_placeholder.png'" @error="$el.onerror=null; $el.src='assets/album_placeholder.png'" style="width: 44px; height: 44px; border-radius: 8px; object-fit: cover;" />
                        <div style="flex: 1; overflow: hidden;">
                            <div style="font-weight: 600; font-size: 0.88rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" x-text="cleanTitle(t.name)"></div>
                            <div style="display: flex; gap: 6px; align-items: center; margin-top: 2px;">
                                <span class="track-format-badge">MP3</span>
                                <span style="font-size: 0.72rem; color: var(--accent-cyan); font-weight: 600;" x-show="statsCounts[t.url]" x-text="(statsCounts[t.url] || 0) + ' plays'"></span>
                            </div>
                        </div>
                        <button class="like-btn" @click.stop="toggleLike(t.url)">
                            <svg viewBox="0 0 24 24" :class="{liked: isLiked(t.url)}">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l8.78-8.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>

            <!-- Mobile Tab 2: Recently Played View (Replacing Upload) -->
            <div x-show="mobileTab === 'recent'" style="padding: 14px 14px 80px 14px;">
                <div style="font-size: 0.9rem; font-weight: 800; color: var(--accent-pink); margin-bottom: 12px;">🕒 RECENTLY PLAYED</div>
                <template x-for="(rName, idx) in recent" :key="idx">
                    <div style="display: flex; align-items: center; gap: 12px; padding: 10px 12px; background: rgba(255,255,255,0.04); border-radius: 12px; margin-bottom: 8px; border: 1px solid var(--border-color);" @click="playByName(rName)">
                        <img :src="getTrackArtByName(rName)" @error="$el.onerror=null; $el.src='assets/album_placeholder.png'" style="width: 44px; height: 44px; border-radius: 8px; object-fit: cover;" />
                        <div style="flex: 1; overflow: hidden;">
                            <div style="font-weight: 600; font-size: 0.88rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" x-text="cleanTitle(rName)"></div>
                            <div style="font-size: 0.7rem; color: var(--accent-cyan);">Played Recently</div>
                        </div>
                    </div>
                </template>
                <template x-if="recent.length === 0">
                    <div style="font-size: 0.85rem; color: var(--text-secondary); text-align: center; padding: 30px;">No recently played tracks yet!</div>
                </template>
            </div>
        </main>

        <!-- Mini Floating Player Strip (Visible when browsing Library or Recent tab) -->
        <div class="go-mini-player" x-show="(mobileTab === 'library' || mobileTab === 'recent') && current" @click="mobileTab = 'player'">
            <img :src="currentArt" @error="$el.onerror=null; $el.src='assets/album_placeholder.png'" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;" />
            <div style="flex: 1; overflow: hidden;">
                <div style="font-weight: 700; font-size: 0.84rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" x-text="cleanTitle(current ? current.name : '')"></div>
                <div style="font-size: 0.68rem; color: var(--accent-cyan);">Tap to expand player</div>
            </div>
            <button style="background: var(--accent-pink); border:none; color:#FFF; width:36px; height:36px; border-radius:50%; font-size:1rem; cursor:pointer;" @click.stop="togglePlay()">
                <span x-text="isPlaying ? '⏸' : '▶'"></span>
            </button>
        </div>

        <!-- Minimalist Icon-Only Thumb Navigation Dock -->
        <nav class="go-thumb-dock">
            <button class="go-thumb-btn" :class="{active: mobileTab === 'player'}" @click="mobileTab = 'player'" title="Player">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
            </button>
            <button class="go-thumb-btn" :class="{active: mobileTab === 'library'}" @click="mobileTab = 'library'" title="Library">
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            </button>
            <button class="go-thumb-btn" @click="showUploadModal = true" title="Upload Music">
                <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </button>
            <button class="go-thumb-btn" :class="{active: mobileTab === 'recent'}" @click="mobileTab = 'recent'" title="Recently Played">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 15"/></svg>
            </button>
            <button class="go-thumb-btn" :class="{active: spatialMode !== 'off'}" @click="showSpatialModal = true" title="Spatial Audio">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 10v4M6 6v12M10 3v18M14 6v12M18 10v4"/></svg>
            </button>
        </nav>
    </div>

    <!-- ── COVER ART MODAL ──────────────────────────────────────────────────── -->
    <div class="art-modal-overlay" x-show="showCoverArtModal" x-cloak @click.self="if(coverArtState !== 'fetching') { showCoverArtModal = false; coverArtState = 'idle'; }">
        <div class="art-modal-card">
            <template x-if="coverArtState === 'idle'">
                <div style="display:flex;flex-direction:column;align-items:center;gap:20px;">
                    <div class="art-modal-icon">🎨</div>
                    <h3>Get Cover Art?</h3>
                    <p>okotunes will scan your library and automatically fetch official album artwork for tracks that don't have one.</p>
                    <div class="art-modal-actions">
                        <button class="art-btn-cancel" @click="showCoverArtModal = false">Cancel</button>
                        <button class="art-btn-go" @click="fetchCoverArt()">Yes, fetch art</button>
                    </div>
                </div>
            </template>
            <template x-if="coverArtState === 'fetching'">
                <div class="art-spinner-wrap">
                    <div class="art-spinner"></div>
                    <p class="art-status-text" x-text="coverArtMsg || 'Searching...'"></p>
                </div>
            </template>
            <template x-if="coverArtState === 'done'">
                <div style="display:flex;flex-direction:column;align-items:center;gap:18px;">
                    <div style="font-size:2.4rem">✅</div>
                    <p class="art-result-ok" x-text="coverArtMsg"></p>
                    <button class="art-btn-go" @click="showCoverArtModal = false; coverArtState = 'idle';">Done</button>
                </div>
            </template>
            <template x-if="coverArtState === 'error'">
                <div style="display:flex;flex-direction:column;align-items:center;gap:18px;">
                    <div style="font-size:2.4rem">😕</div>
                    <p class="art-result-fail" x-text="coverArtMsg"></p>
                    <button class="art-btn-cancel" @click="showCoverArtModal = false; coverArtState = 'idle';">Close</button>
                </div>
            </template>
        </div>
    </div>

    <!-- ── DELETE CONFIRMATION MODAL ────────────────────────────────────── -->
    <div class="art-modal-overlay" x-show="showDeleteModal" x-cloak @click.self="if(!isDeleting) { showDeleteModal = false; trackToDelete = null; }">
        <div class="art-modal-card" style="max-width: 400px;">
            <template x-if="!isDeleting && trackToDelete">
                <div style="display:flex;flex-direction:column;align-items:center;gap:18px;">
                    <div style="width: 56px; height: 56px; border-radius: 50%; background: rgba(255,0,85,0.12); border: 1px solid rgba(255,0,85,0.3); display: flex; align-items: center; justify-content: center; color: #FF4D6D;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 2 0 0 1-2 2H7a2 2 2 0 0 1-2-2V6m3 0V4a2 2 2 0 0 1 2-2h4a2 2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </div>
                    <h3 style="font-size: 1.2rem;">Delete Song?</h3>
                    <p style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.5;">
                        Are you sure you want to delete <strong style="color: #fff;" x-text="trackToDelete ? cleanTitle(trackToDelete.name) : ''"></strong>? This will permanently remove the song from your cloud library.
                    </p>
                    <div class="art-modal-actions" style="margin-top: 8px;">
                        <button class="art-btn-cancel" @click="showDeleteModal = false; trackToDelete = null;">Cancel</button>
                        <button class="art-btn-go" style="background: linear-gradient(135deg, #FF0055, #D32F2F); font-weight: 700;" @click="confirmDeleteTrack()">Yes, Delete</button>
                    </div>
                </div>
            </template>
            <template x-if="isDeleting">
                <div class="art-spinner-wrap">
                    <div class="art-spinner" style="border-top-color: #FF0055;"></div>
                    <p class="art-status-text">Deleting song from cloud storage...</p>
                </div>
            </template>
        </div>
    </div>

    <!-- ── UPLOAD MODAL UI ────────────────────────────────────────────────── -->
    <div class="modal-overlay" x-show="showUploadModal" x-cloak @click.self="showUploadModal = false">
        <div class="modal-card" style="max-width: 580px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <h3 style="font-size: 1.25rem; font-weight: 800; display: flex; align-items: center; gap: 10px; margin: 0;">
                    ☁️ Upload Music Files
                </h3>
                <button style="background:none; border:none; color:var(--text-secondary); font-size:1.5rem; cursor:pointer;" @click="showUploadModal = false">&times;</button>
            </div>

            <!-- Drag & Drop Zone -->
            <div class="drop-zone"
                 @click="$refs.fileInput.click()"
                 @dragover.prevent="$el.classList.add('dragover')"
                 @dragleave.prevent="$el.classList.remove('dragover')"
                 @drop.prevent="$el.classList.remove('dragover'); handleFileDrop($event)">
                <div style="font-size: 2.5rem; margin-bottom: 8px;">☁️</div>
                <div style="font-weight: 700; font-size: 1rem; margin-bottom: 4px;">Drag & Drop Audio Files Here</div>
                <div style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 14px;">Supports MP3, FLAC, WAV, OGG, AAC, M4A, OPUS, WMA</div>
                <button style="background: var(--accent-pink); color: #FFF; border: none; padding: 9px 20px; border-radius: 16px; font-weight: 700; cursor: pointer;">Browse Files</button>
                <input type="file" x-ref="fileInput" accept=".mp3,.flac,.wav,.ogg,.aac,.m4a,.opus,.mp4,.wma" multiple style="display: none;" @change="handleFileSelect($event)" />
            </div>

            <!-- Queue Header & Actions -->
            <div x-show="uploadQueue.length > 0" style="display: flex; justify-content: space-between; align-items: center; margin-top: 18px; font-size: 0.85rem; font-weight: 700;">
                <span style="color: var(--accent-cyan);" x-text="'Queue (' + completedUploadsCount + '/' + uploadQueue.length + ' completed)'"></span>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button style="background: rgba(255,255,255,0.08); border: 1px solid var(--border-color); color: #FFF; padding: 4px 12px; border-radius: 10px; cursor: pointer;" @click="$refs.fileInput.click()">+ Add More</button>
                    <button style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 0.8rem;" @click="clearUploadQueue()">Clear List</button>
                </div>
            </div>

            <!-- Upload Queue File List -->
            <div class="upload-queue-list" x-show="uploadQueue.length > 0">
                <template x-for="item in uploadQueue" :key="item.id">
                    <div class="upload-item" :class="item.status">
                        <div style="display: flex; align-items: center; gap: 10px; overflow: hidden; flex: 1;">
                            <span style="font-size: 1.2rem; flex-shrink: 0;">🎵</span>
                            <div style="min-width: 0; overflow: hidden; flex: 1;">
                                <div style="font-weight: 600; font-size: 0.85rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" x-text="item.name"></div>
                                <div style="display: flex; gap: 8px; font-size: 0.72rem; color: var(--text-secondary); margin-top: 2px;">
                                    <span x-text="item.sizeFormatted"></span>
                                    <span style="color: var(--accent-pink);" x-show="item.status === 'uploading'" x-text="'Uploading ' + item.progress + '%'"></span>
                                    <span style="color: var(--accent-green);" x-show="item.status === 'completed'">✓ Done</span>
                                    <span style="color: #FF3366;" x-show="item.status === 'error'" x-text="item.errorMsg || 'Failed'"></span>
                                    <span style="color: var(--text-secondary);" x-show="item.status === 'pending'">Queued</span>
                                </div>
                            </div>
                        </div>

                        <button class="upload-remove-btn" @click="removeQueueItem(item.id)" title="Remove track">✕</button>

                        <!-- Individual Progress Bar -->
                        <div class="upload-item-progress" x-show="item.status === 'uploading' || item.status === 'completed'" :style="'width: ' + item.progress + '%'"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- ── SPATIAL AUDIO & DSP PRESETS MODAL ──────────────────────────────── -->
    <div class="modal-overlay" x-show="showSpatialModal" x-cloak @click.self="showSpatialModal = false">
        <div class="modal-card spatial-modal-card">
            <div class="spatial-header">
                <div class="spatial-title-group">
                    <button class="spatial-back-btn" @click="showSpatialModal = false" title="Close">
                        ‹
                    </button>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <svg style="width: 20px; height: 20px; stroke: #FFF; fill: none; stroke-width: 2;" viewBox="0 0 24 24"><path d="M2 10v4M6 6v12M10 3v18M14 6v12M18 10v4"/></svg>
                        <h3 style="font-size: 1.15rem; font-weight: 700; color: #FFF; margin: 0;">Spatial Audio</h3>
                    </div>
                </div>
                <div style="font-size: 0.84rem; color: var(--accent-cyan); font-weight: 600;" x-text="spatialModeLabel"></div>
            </div>

            <div class="spatial-list">
                <template x-for="preset in spatialPresets" :key="preset.id">
                    <div class="spatial-option-item" :class="{active: spatialMode === preset.id}" @click="setSpatialMode(preset.id)">
                        <div class="spatial-radio-outer">
                            <div class="spatial-radio-inner"></div>
                        </div>
                        <div style="flex: 1;">
                            <div class="spatial-option-title" x-text="preset.title"></div>
                            <div class="spatial-option-subtitle" x-show="preset.subtitle" x-text="preset.subtitle"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- ── SONG ANALYTICS TELEMETRY MODAL ──────────────────────────────────── -->
    <div class="modal-overlay" x-show="showAnalyticsModal" x-cloak @click.self="showAnalyticsModal = false">
        <div class="modal-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 1.25rem; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                    📊 Song Analytics Engine
                </h3>
                <button style="background:none; border:none; color:var(--text-secondary); font-size:1.5rem; cursor:pointer;" @click="showAnalyticsModal = false">&times;</button>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div style="background: rgba(255,255,255,0.05); padding: 16px; border-radius: 14px; text-align: center;">
                    <div style="font-size: 1.6rem; font-weight: 800; color: var(--accent-pink);" x-text="analyticsData.total_hours_listened || 0"></div>
                    <div style="font-size: 0.72rem; color: var(--text-secondary); text-transform: uppercase;">Hours Listened</div>
                </div>
                <div style="background: rgba(255,255,255,0.05); padding: 16px; border-radius: 14px; text-align: center;">
                    <div style="font-size: 1.6rem; font-weight: 800; color: var(--accent-cyan);" x-text="analyticsData.total_events || 0"></div>
                    <div style="font-size: 0.72rem; color: var(--text-secondary); text-transform: uppercase;">Logged Events</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden audio element -->
    <audio id="audio-el" x-ref="audio" crossorigin="anonymous" preload="metadata"></audio>
</div>

<script>
class SpatialAudioEngine {
    constructor(audioElement) {
        this.audioEl = audioElement;
        this.ctx = null;
        this.source = null;
        this.eqBass = null;
        this.eqMid = null;
        this.eqHigh = null;
        this.panner = null;
        this.convolver = null;
        this.reverbGain = null;
        this.dryGain = null;
        this.compressor = null;
        this.masterGain = null;
        this.isInitialized = false;
        this.currentMode = 'off';
    }

    init() {
        if (this.isInitialized) return;
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            this.ctx = new AudioContext();

            this.source = this.ctx.createMediaElementSource(this.audioEl);

            this.eqBass = this.ctx.createBiquadFilter();
            this.eqBass.type = 'lowshelf';
            this.eqBass.frequency.value = 100;
            this.eqBass.gain.value = 0;

            this.eqMid = this.ctx.createBiquadFilter();
            this.eqMid.type = 'peaking';
            this.eqMid.frequency.value = 1000;
            this.eqMid.Q.value = 1.0;
            this.eqMid.gain.value = 0;

            this.eqHigh = this.ctx.createBiquadFilter();
            this.eqHigh.type = 'highshelf';
            this.eqHigh.frequency.value = 8000;
            this.eqHigh.gain.value = 0;

            if (this.ctx.createPanner) {
                this.panner = this.ctx.createPanner();
                this.panner.panningModel = 'HRTF';
                this.panner.distanceModel = 'inverse';
                this.panner.refDistance = 1;
                this.panner.maxDistance = 10000;
                this.panner.rolloffFactor = 1;
                this.panner.coneInnerAngle = 360;
                this.panner.coneOuterAngle = 0;
                this.panner.coneOuterGain = 0;
                this.panner.setPosition(0, 0, 0);
            }

            this.convolver = this.ctx.createConvolver();
            this.reverbGain = this.ctx.createGain();
            this.reverbGain.gain.value = 0;

            this.dryGain = this.ctx.createGain();
            this.dryGain.gain.value = 1;

            this.hallBuffer = this._generateImpulseResponse(2.4, 2.0);
            this.stadiumBuffer = this._generateImpulseResponse(4.5, 3.5);

            this.compressor = this.ctx.createDynamicsCompressor();
            this.compressor.threshold.value = -24;
            this.compressor.knee.value = 30;
            this.compressor.ratio.value = 1; // gentle default
            this.compressor.attack.value = 0.003;
            this.compressor.release.value = 0.25;

            this.masterGain = this.ctx.createGain();

            // Connect Graph:
            // source -> eqBass -> eqMid -> eqHigh
            // eqHigh -> dryGain & convolver -> reverbGain
            // dryGain & reverbGain -> compressor -> panner -> masterGain -> destination
            this.source.connect(this.eqBass);
            this.eqBass.connect(this.eqMid);
            this.eqMid.connect(this.eqHigh);

            this.eqHigh.connect(this.dryGain);
            this.dryGain.connect(this.compressor);

            this.eqHigh.connect(this.convolver);
            this.convolver.connect(this.reverbGain);
            this.reverbGain.connect(this.compressor);

            if (this.panner) {
                this.compressor.connect(this.panner);
                this.panner.connect(this.masterGain);
            } else {
                this.compressor.connect(this.masterGain);
            }

            this.masterGain.connect(this.ctx.destination);

            this.isInitialized = true;
        } catch(e) {
            console.warn('Web Audio Spatial Engine init notice:', e);
        }
    }

    _generateImpulseResponse(duration, decay) {
        if (!this.ctx) return null;
        const sampleRate = this.ctx.sampleRate;
        const length = sampleRate * duration;
        const impulse = this.ctx.createBuffer(2, length, sampleRate);
        const left = impulse.getChannelData(0);
        const right = impulse.getChannelData(1);

        for (let i = 0; i < length; i++) {
            const n = i / length;
            const e = Math.pow(1 - n, decay);
            left[i] = (Math.random() * 2 - 1) * e;
            right[i] = (Math.random() * 2 - 1) * e;
        }
        return impulse;
    }

    setMode(mode) {
        if (!this.isInitialized) this.init();
        if (this.ctx && this.ctx.state === 'suspended') {
            this.ctx.resume();
        }
        this.currentMode = mode;
        if (!this.isInitialized) return;

        const now = this.ctx.currentTime;
        const ramp = 0.05;

        // Reset Parameters
        this.eqBass.gain.setTargetAtTime(0, now, ramp);
        this.eqMid.gain.setTargetAtTime(0, now, ramp);
        this.eqHigh.gain.setTargetAtTime(0, now, ramp);
        
        this.dryGain.gain.setTargetAtTime(1, now, ramp);
        this.reverbGain.gain.setTargetAtTime(0, now, ramp);

        this.compressor.threshold.setTargetAtTime(-24, now, ramp);
        this.compressor.ratio.setTargetAtTime(1, now, ramp);

        if (this.panner) {
            this.panner.setPosition(0, 0, 0);
        }

        switch (mode) {
            case 'off':
                break;

            case 'spatial_headphones':
                if (this.panner) {
                    this.panner.setPosition(0, 0.2, -0.8);
                }
                this.convolver.buffer = this.hallBuffer;
                this.reverbGain.gain.setTargetAtTime(0.08, now, ramp);
                this.eqHigh.gain.setTargetAtTime(1.5, now, ramp);
                break;

            case 'surround_51':
                if (this.panner) {
                    this.panner.setPosition(0, 0.5, -1.2);
                }
                this.eqBass.frequency.setTargetAtTime(80, now, ramp);
                this.eqBass.gain.setTargetAtTime(3.5, now, ramp);
                this.eqHigh.gain.setTargetAtTime(2.0, now, ramp);
                break;

            case 'surround_71':
                if (this.panner) {
                    this.panner.setPosition(0, 0.8, -1.5);
                }
                this.eqBass.frequency.setTargetAtTime(60, now, ramp);
                this.eqBass.gain.setTargetAtTime(5.0, now, ramp);
                this.eqMid.gain.setTargetAtTime(1.0, now, ramp);
                this.eqHigh.gain.setTargetAtTime(3.0, now, ramp);
                break;

            case 'cinema':
                this.eqBass.frequency.setTargetAtTime(90, now, ramp);
                this.eqBass.gain.setTargetAtTime(4.5, now, ramp);
                this.eqHigh.gain.setTargetAtTime(2.5, now, ramp);
                this.compressor.threshold.setTargetAtTime(-18, now, ramp);
                this.compressor.ratio.setTargetAtTime(4.0, now, ramp);
                if (this.panner) {
                    this.panner.setPosition(0, 0, -0.5);
                }
                break;

            case 'concert_hall':
                this.convolver.buffer = this.hallBuffer;
                this.reverbGain.gain.setTargetAtTime(0.35, now, ramp);
                this.dryGain.gain.setTargetAtTime(0.85, now, ramp);
                this.eqMid.gain.setTargetAtTime(1.5, now, ramp);
                break;

            case 'stadium':
                this.convolver.buffer = this.stadiumBuffer;
                this.reverbGain.gain.setTargetAtTime(0.55, now, ramp);
                this.dryGain.gain.setTargetAtTime(0.75, now, ramp);
                this.eqBass.gain.setTargetAtTime(2.0, now, ramp);
                break;

            case 'club_edm':
                this.eqBass.frequency.setTargetAtTime(60, now, ramp);
                this.eqBass.gain.setTargetAtTime(6.5, now, ramp);
                this.eqMid.gain.setTargetAtTime(-2.5, now, ramp);
                this.eqHigh.gain.setTargetAtTime(5.0, now, ramp);
                break;

            case 'bass_boost':
                this.eqBass.frequency.setTargetAtTime(50, now, ramp);
                this.eqBass.gain.setTargetAtTime(8.5, now, ramp);
                this.eqMid.gain.setTargetAtTime(-1.0, now, ramp);
                break;

            case 'night_mode':
                this.compressor.threshold.setTargetAtTime(-30, now, ramp);
                this.compressor.ratio.setTargetAtTime(8.0, now, ramp);
                this.eqBass.gain.setTargetAtTime(-3.0, now, ramp);
                this.eqMid.gain.setTargetAtTime(3.0, now, ramp);
                break;
        }
    }
}

function okotunesApp() {
    return {
        tracks: [], filtered: [], query: '',
        current: null, currentArt: 'assets/album_placeholder.png', isPlaying: false,
        shuffle: false, loopMode: 'none',
        studioTab: 'library', mobileTab: 'player',
        recent: JSON.parse(localStorage.getItem('mspot_recent') || '[]'),
        likedTracks: JSON.parse(localStorage.getItem('mspot_liked') || '[]'),
        spatialMode: localStorage.getItem('mspot_spatial_mode') || 'off',
        showSpatialModal: false,
        audioEngine: null,
        spatialPresets: [
            { id: 'off', title: 'Off / Stereo', subtitle: '' },
            { id: 'spatial_headphones', title: 'Spatial (Headphones)', subtitle: 'HRTF binaural + room' },
            { id: 'surround_51', title: 'Virtual Surround 5.1', subtitle: '5-channel HRTF placement' },
            { id: 'surround_71', title: 'Virtual Surround 7.1', subtitle: '7.1 + LFE sub channel' },
            { id: 'cinema', title: 'Cinema', subtitle: 'Wide stereo + bass + compressor' },
            { id: 'concert_hall', title: 'Concert Hall', subtitle: 'Lush hall reverb' },
            { id: 'stadium', title: 'Stadium', subtitle: 'Huge open-space reverb' },
            { id: 'club_edm', title: 'Club / EDM', subtitle: 'V-shaped EQ, punchy' },
            { id: 'bass_boost', title: 'Bass Boost', subtitle: 'Deep sub + punch' },
            { id: 'night_mode', title: 'Night Mode', subtitle: 'Tames loud peaks, lifts quiet dialog' }
        ],
        clockTime: '', toastMsg: '', toastTimer: null,
        elapsed: '0:00', duration: '0:00',
        showAnalyticsModal: false, analyticsData: {},
        showUploadModal: false, uploadStatus: '',
        uploadQueue: [], isUploading: false,
        statsCounts: {},
        touchStartX: 0, touchEndX: 0,
        showCoverArtModal: false,
        coverArtState: 'idle', // idle | fetching | done | error
        coverArtMsg: '',
        showDeleteModal: false,
        trackToDelete: null,
        isDeleting: false,

        promptDeleteTrack(track) {
            if (!track) return;
            this.trackToDelete = track;
            this.showDeleteModal = true;
        },

        async confirmDeleteTrack() {
            if (!this.trackToDelete) return;
            this.isDeleting = true;
            const targetId = this.trackToDelete.id;
            const targetUrl = this.trackToDelete.url;

            try {
                const res = await fetch('delete.php?id=' + encodeURIComponent(targetId), { method: 'POST' });
                const data = await res.json();

                if (data.success) {
                    if (this.current && (this.current.id === targetId || this.current.url === targetUrl)) {
                        this.$refs.audio.pause();
                        this.isPlaying = false;
                        this.current = null;
                    }
                    this.tracks = this.tracks.filter(t => t.id !== targetId && t.url !== targetUrl);
                    this.filter();
                    this.showToast('Song deleted');
                } else {
                    this.showToast(data.error || 'Failed deleting song');
                }
            } catch (e) {
                this.showToast('Network error deleting song');
            } finally {
                this.isDeleting = false;
                this.showDeleteModal = false;
                this.trackToDelete = null;
            }
        },

        get spatialModeLabel() {
            const found = this.spatialPresets.find(p => p.id === this.spatialMode);
            return found ? found.title : 'Off / Stereo';
        },

        setSpatialMode(mode) {
            this.spatialMode = mode;
            localStorage.setItem('mspot_spatial_mode', mode);
            if (!this.audioEngine) {
                this.audioEngine = new SpatialAudioEngine(this.$refs.audio);
            }
            this.audioEngine.setMode(mode);
            const found = this.spatialPresets.find(p => p.id === mode);
            if (found) {
                this.showToast('🔊 Spatial: ' + found.title);
            }
        },

        boot() {
            const audio = this.$refs.audio;
            this.reloadTracks();
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);

            // Splash screen - once per session
            const splashEl = document.getElementById('okotunes-splash');
            const splashBar = document.getElementById('splash-bar');
            const SPLASH_DURATION = 5000;
            if (splashEl && !sessionStorage.getItem('okt_splashed')) {
                let elapsed = 0;
                const step = 80;
                const timer = setInterval(() => {
                    elapsed += step;
                    const pct = Math.min((elapsed / SPLASH_DURATION) * 100, 100);
                    if (splashBar) splashBar.style.width = pct + '%';
                    if (elapsed >= SPLASH_DURATION) {
                        clearInterval(timer);
                        splashEl.classList.add('hidden');
                        sessionStorage.setItem('okt_splashed', '1');
                    }
                }, step);
            } else if (splashEl) {
                splashEl.classList.add('hidden');
            }

            audio.addEventListener('timeupdate', () => {
                if (!audio.duration) return;
                const p = (audio.currentTime / audio.duration) * 100;
                if (this.$refs.seekBar) this.$refs.seekBar.value = p;
                if (this.$refs.mobileSeekBar) this.$refs.mobileSeekBar.value = p;
                this.elapsed = this.fmt(audio.currentTime);
                this.duration = this.fmt(audio.duration);
                this.updateMediaPositionState();
            });
            audio.addEventListener('ended', () => { this.next(); });
            audio.addEventListener('pause', () => {
                this.isPlaying = false;
                if ('mediaSession' in navigator) navigator.mediaSession.playbackState = 'paused';
            });
            audio.addEventListener('play', () => {
                this.isPlaying = true;
                if (!this.audioEngine) this.audioEngine = new SpatialAudioEngine(audio);
                this.audioEngine.setMode(this.spatialMode);
                if ('mediaSession' in navigator) navigator.mediaSession.playbackState = 'playing';
            });
        },

        async fetchCoverArt() {
            this.coverArtState = 'fetching';
            this.coverArtMsg = 'Resetting artwork register...';
            let totalFetched = 0;
            let isFirst = true;

            const runBatch = async () => {
                try {
                    // First call uses force=1 to reset all art_status to pending for a real re-fetch
                    const url = isFirst
                        ? 'fetch_art.php?batch=4&force=1'
                        : 'fetch_art.php?batch=4';
                    isFirst = false;

                    this.coverArtMsg = totalFetched === 0
                        ? 'Searching iTunes & Deezer...'
                        : `Found ${totalFetched} so far, searching more...`;

                    const res = await fetch(url);
                    const data = await res.json();
                    totalFetched += (data.fetched || 0);

                    if (data.fetched > 0) {
                        this.coverArtMsg = `Found ${totalFetched} cover${totalFetched !== 1 ? 's' : ''} so far...`;
                        this.reloadTracksSilent();
                    }

                    if (data.remaining_pending > 0) {
                        await new Promise(r => setTimeout(r, 500));
                        return runBatch();
                    }

                    // All done
                    if (totalFetched > 0) {
                        this.coverArtState = 'done';
                        this.coverArtMsg = `${totalFetched} cover art image${totalFetched !== 1 ? 's' : ''} fetched!`;
                        this.reloadTracksSilent();
                    } else {
                        this.coverArtState = 'done';
                        this.coverArtMsg = 'No matching artwork found for your tracks.';
                    }
                } catch (e) {
                    this.coverArtState = 'error';
                    this.coverArtMsg = 'Could not reach the cover art service.';
                }
            };
            await runBatch();
        },



        updateMediaSession() {
            if (!('mediaSession' in navigator) || !this.current) return;
            try {
                navigator.mediaSession.metadata = new MediaMetadata({
                    title: this.cleanTitle(this.current.name),
                    artist: 'MSpot Studio',
                    album: 'MSpot Local Music',
                    artwork: [
                        { src: this.currentArt, sizes: '512x512', type: 'image/png' },
                        { src: this.currentArt, sizes: '256x256', type: 'image/png' }
                    ]
                });

                navigator.mediaSession.setActionHandler('play', () => this.togglePlay());
                navigator.mediaSession.setActionHandler('pause', () => this.togglePlay());
                navigator.mediaSession.setActionHandler('previoustrack', () => this.prev());
                navigator.mediaSession.setActionHandler('nexttrack', () => this.next());
                navigator.mediaSession.setActionHandler('seekto', (details) => {
                    if (details.seekTime && this.$refs.audio) {
                        this.$refs.audio.currentTime = details.seekTime;
                    }
                });
            } catch (e) {}
        },

        updateMediaPositionState() {
            if (!('mediaSession' in navigator) || !this.$refs.audio) return;
            const audio = this.$refs.audio;
            if (audio.duration && !isNaN(audio.duration) && 'setPositionState' in navigator.mediaSession) {
                try {
                    navigator.mediaSession.setPositionState({
                        duration: audio.duration,
                        playbackRate: audio.playbackRate || 1.0,
                        position: audio.currentTime || 0
                    });
                } catch (e) {}
            }
        },

        showToast(msg) {
            this.toastMsg = msg;
            if (this.toastTimer) clearTimeout(this.toastTimer);
            this.toastTimer = setTimeout(() => { this.toastMsg = ''; }, 1600);
        },

        updateClock() {
            const now = new Date();
            this.clockTime = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        },

        toggleLike(url) {
            if (!url) return;
            if (this.likedTracks.includes(url)) {
                this.likedTracks = this.likedTracks.filter(u => u !== url);
                this.showToast('Removed from Liked Songs');
            } else {
                this.likedTracks.push(url);
                this.showToast('❤️ Added to Liked Songs');
            }
            localStorage.setItem('mspot_liked', JSON.stringify(this.likedTracks));
        },

        isLiked(url) {
            return url ? this.likedTracks.includes(url) : false;
        },

        cleanTitle(raw) {
            if (!raw) return '';
            let name = raw.replace(/\[-?[a-zA-Z0-9_-]{11}\]/g, '');
            name = name.replace(/\(Official\s*(Video|Audio|Music Video)\)/gi, '');
            name = name.replace(/\[(Official|HD|4K|Lyrics)\]/gi, '');
            name = name.replace(/\(\s*\)/g, '').trim();
            return name || raw;
        },

        reloadTracks() {
            Promise.all([
                fetch('api.php').then(r => r.json()),
                fetch('stats.php').then(r => r.json()).catch(() => ({ counts: {} })),
                fetch('analytics.php').then(r => r.json()).catch(() => ({}))
            ]).then(([tData, sData, aData]) => {
                this.tracks = tData.tracks || [];
                this.statsCounts = sData.counts || {};
                this.analyticsData = aData || {};
                this.sortTracksByPlayCount();
                this.filter();
                this.triggerBatchArtFetcher();
            });
        },

        triggerBatchArtFetcher() {
            fetch('fetch_art.php?batch=4')
                .then(r => r.json())
                .then(data => {
                    if (data && data.fetched > 0) {
                        this.reloadTracksSilent();
                    }
                    if (data && data.remaining_pending > 0) {
                        setTimeout(() => this.triggerBatchArtFetcher(), 1500);
                    }
                })
                .catch(err => console.warn('Art fetcher notice:', err));
        },

        reloadTracksSilent() {
            Promise.all([
                fetch('api.php').then(r => r.json()),
                fetch('stats.php').then(r => r.json()).catch(() => ({ counts: {} }))
            ]).then(([tData, sData]) => {
                this.tracks = tData.tracks || [];
                this.statsCounts = sData.counts || {};
                this.sortTracksByPlayCount();
                this.filter();
                if (this.current) {
                    const match = this.tracks.find(t => t.id === this.current.id || t.url === this.current.url);
                    if (match && match.artUrl && !match.artUrl.includes('art.php?id=')) {
                        this.current.artUrl = match.artUrl;
                        this.currentArt = match.artUrl;
                    }
                }
            });
        },

        sortTracksByPlayCount() {
            const counts = this.statsCounts || {};
            this.tracks.sort((a, b) => {
                const countA = counts[a.url] || 0;
                const countB = counts[b.url] || 0;
                if (countB !== countA) {
                    return countB - countA;
                }
                return a.name.localeCompare(b.name);
            });
        },

        filter() {
            const q = this.query.toLowerCase();
            this.filtered = q ? this.tracks.filter(t => t.name.toLowerCase().includes(q)) : [...this.tracks];
        },

        play(idx) {
            const t = this.filtered[idx];
            if (!t) return;
            this.current = t;
            this.currentArt = t.artUrl || 'assets/album_placeholder.png';

            const audio = this.$refs.audio;
            audio.src = t.url;
            audio.play();

            this.addRecent(t.name);
            this.updateMediaSession();

            // Log telemetry event
            fetch('analytics.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ event: 'play_start', track_name: t.name, url: t.url })
            }).catch(() => {});

            // Increment persistent play count in stats.php
            fetch('stats.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ url: t.url, name: t.name })
            }).then(r => r.json()).then(res => {
                if (res && res.count) {
                    this.statsCounts[t.url] = res.count;
                    this.sortTracksByPlayCount();
                    this.filter();
                }
            }).catch(() => {});
        },

        playByName(name) {
            const idx = this.tracks.findIndex(t => t.name === name);
            if (idx >= 0) this.play(idx);
        },

        getTrackArtByName(name) {
            const t = this.tracks.find(tr => tr.name === name);
            return (t && t.artUrl) ? t.artUrl : 'assets/album_placeholder.png';
        },

        openAnalyticsModal() {
            this.showAnalyticsModal = true;
            fetch('analytics.php')
                .then(r => r.json())
                .then(data => { this.analyticsData = data; })
                .catch(() => {});
        },

        togglePlay() {
            if (!this.current) return;
            const audio = this.$refs.audio;
            if (this.isPlaying) audio.pause(); else audio.play();
        },

        toggleShuffle() {
            this.shuffle = !this.shuffle;
            this.showToast(this.shuffle ? '🔀 Shuffle ON' : '🔀 Shuffle OFF');
        },

        cycleLoop() {
            const modes = ['none', 'all', 'one'];
            this.loopMode = modes[(modes.indexOf(this.loopMode) + 1) % modes.length];
            this.$refs.audio.loop = (this.loopMode === 'one');
            
            const labels = { 'none': '🔁 Loop OFF', 'all': '🔁 Loop ALL', 'one': '🔂 Loop ONE' };
            this.showToast(labels[this.loopMode]);
        },

        prev() {
            if (this.filtered.length === 0) return;
            if (this.shuffle) {
                const currentIdx = this.filtered.findIndex(t => this.current && t.url === this.current.url);
                if (this.filtered.length === 1) { this.play(0); return; }
                let randIdx;
                do { randIdx = Math.floor(Math.random() * this.filtered.length); } while (randIdx === currentIdx);
                this.play(randIdx);
                return;
            }
            const i = this.filtered.findIndex(t => this.current && t.url === this.current.url);
            if (i > 0) {
                this.play(i - 1);
            } else if (this.loopMode === 'all' && this.filtered.length > 0) {
                this.play(this.filtered.length - 1);
            }
        },

        next() {
            if (this.filtered.length === 0) return;
            if (this.shuffle) {
                const currentIdx = this.filtered.findIndex(t => this.current && t.url === this.current.url);
                if (this.filtered.length === 1) { this.play(0); return; }
                let randIdx;
                do { randIdx = Math.floor(Math.random() * this.filtered.length); } while (randIdx === currentIdx);
                this.play(randIdx);
                return;
            }
            const i = this.filtered.findIndex(t => this.current && t.url === this.current.url);
            if (i >= 0 && i < this.filtered.length - 1) {
                this.play(i + 1);
            } else if (this.loopMode === 'all' && this.filtered.length > 0) {
                this.play(0);
            }
        },

        seek(e) {
            const audio = this.$refs.audio;
            if (audio.duration) audio.currentTime = (e.target.value / 100) * audio.duration;
        },

        setVolume(e) { this.$refs.audio.volume = e.target.value; },

        downloadCurrentTrack() {
            if (!this.current || !this.current.url) return;
            let trackUrl = this.current.url;
            if (!trackUrl.includes('download=1')) {
                trackUrl += (trackUrl.includes('?') ? '&download=1' : '?download=1');
            }
            const a = document.createElement('a');
            a.href = trackUrl;
            a.download = this.cleanTitle(this.current.name) + '.mp3';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        },

        get completedUploadsCount() {
            return this.uploadQueue.filter(q => q.status === 'completed').length;
        },

        formatBytes(bytes) {
            if (!bytes || bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        handleFileSelect(e) {
            if (e.target.files && e.target.files.length) {
                this.addFilesToQueue(Array.from(e.target.files));
                e.target.value = '';
            }
        },

        handleFileDrop(e) {
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                this.addFilesToQueue(Array.from(e.dataTransfer.files));
            }
        },

        addFilesToQueue(files) {
            const allowedExt = ['mp3', 'flac', 'wav', 'ogg', 'aac', 'm4a', 'opus', 'mp4', 'wma'];
            files.forEach(file => {
                const ext = file.name.split('.').pop().toLowerCase();
                if (!allowedExt.includes(ext)) {
                    this.showToast('Skipped unsupported file: ' + file.name);
                    return;
                }
                if (!this.uploadQueue.some(item => item.name === file.name && item.size === file.size)) {
                    this.uploadQueue.push({
                        id: 'up_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6),
                        file: file,
                        name: file.name,
                        size: file.size,
                        sizeFormatted: this.formatBytes(file.size),
                        status: 'pending',
                        progress: 0,
                        errorMsg: '',
                        xhr: null
                    });
                }
            });

            this.processUploadQueue();
        },

        removeQueueItem(id) {
            const idx = this.uploadQueue.findIndex(item => item.id === id);
            if (idx !== -1) {
                const item = this.uploadQueue[idx];
                if (item.xhr) {
                    try { item.xhr.abort(); } catch(e) {}
                }
                this.uploadQueue.splice(idx, 1);
                this.processUploadQueue();
            }
        },

        clearUploadQueue() {
            this.uploadQueue.forEach(item => {
                if (item.xhr) {
                    try { item.xhr.abort(); } catch(e) {}
                }
            });
            this.uploadQueue = [];
        },

        async processUploadQueue() {
            if (this.isUploading) return;

            const nextItem = this.uploadQueue.find(item => item.status === 'pending');
            if (!nextItem) return;

            this.isUploading = true;
            nextItem.status = 'uploading';
            nextItem.progress = 10;

            const formData = new FormData();
            formData.append('file', nextItem.file);

            try {
                const res = await new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    nextItem.xhr = xhr;

                    xhr.upload.addEventListener('progress', (e) => {
                        if (e.lengthComputable) {
                            nextItem.progress = Math.round((e.loaded / e.total) * 100);
                        }
                    });

                    xhr.addEventListener('load', () => {
                        if (xhr.status === 200) {
                            try {
                                resolve(JSON.parse(xhr.responseText));
                            } catch(err) {
                                reject(new Error('Invalid response'));
                            }
                        } else if (xhr.status === 401) {
                            reject(new Error('Gateway session expired'));
                        } else {
                            reject(new Error('HTTP ' + xhr.status));
                        }
                    });

                    xhr.addEventListener('error', () => reject(new Error('Network error')));
                    xhr.addEventListener('abort', () => reject(new Error('Cancelled')));

                    xhr.open('POST', 'upload.php', true);
                    xhr.send(formData);
                });

                if (res.success) {
                    nextItem.status = 'completed';
                    nextItem.progress = 100;
                    this.showToast('Uploaded: ' + nextItem.name);
                    this.reloadTracks();
                } else {
                    nextItem.status = 'error';
                    nextItem.errorMsg = res.error || 'Upload failed';
                }
            } catch(err) {
                if (nextItem.status !== 'completed') {
                    nextItem.status = 'error';
                    nextItem.errorMsg = err.message || 'Upload error';
                }
            } finally {
                nextItem.xhr = null;
                this.isUploading = false;
                this.processUploadQueue();
            }
        },

        addRecent(name) {
            this.recent = this.recent.filter(n => n !== name);
            this.recent.unshift(name);
            if (this.recent.length > 15) this.recent.pop();
            localStorage.setItem('mspot_recent', JSON.stringify(this.recent));
        },

        handleTouchStart(e) { this.touchStartX = e.changedTouches[0].screenX; },
        handleTouchEnd(e) {
            this.touchEndX = e.changedTouches[0].screenX;
            if (this.touchEndX < this.touchStartX - 50) this.next();
            if (this.touchEndX > this.touchStartX + 50) this.prev();
        },

        fmt(s) {
            if (!s || isNaN(s)) return '0:00';
            const m = Math.floor(s / 60), sec = Math.floor(s % 60);
            return `${m}:${sec.toString().padStart(2,'0')}`;
        }
    };
}
</script>
</body>
</html>
