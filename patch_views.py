import re

with open('assets/ocean.js', 'r') as f:
    content = f.read()

# Replace drawFirstPersonBow
start_marker = "  function drawFirstPersonBow() {"
end_marker = "  function drawFirstPersonStern() {"
bow_new = """  function drawFirstPersonBow() {
    const W = logicalW, H = logicalH;
    ctx.save();
    ctx.translate(0, sh.bobY * 1.5);
    ctx.rotate(sh.roll * 0.2);

    let deckGrad = ctx.createLinearGradient(0, H - 150, 0, H);
    deckGrad.addColorStop(0, '#1c1006');
    deckGrad.addColorStop(1, '#3a2311');
    ctx.fillStyle = deckGrad;
    ctx.beginPath();
    ctx.moveTo(-20, H); ctx.lineTo(-20, H - 60); ctx.lineTo(W * 0.5, H - 60);
    ctx.quadraticCurveTo(W * 0.9, H - 60, W + 20, H - 250);
    ctx.lineTo(W + 20, H); ctx.fill();

    ctx.strokeStyle = '#110802'; ctx.lineWidth = 2;
    for(let i=0; i<10; i++) {
        let xBottom = -20 + (W*0.6 + 20) * (i/10);
        let xTop = -20 + (W*0.5 + 20) * (i/10);
        ctx.beginPath(); ctx.moveTo(xBottom, H); ctx.lineTo(xTop, H - 60); ctx.stroke();
    }
    for(let i=0; i<6; i++) {
        let xBottom = W*0.6 + (W*0.4 + 20) * (i/6);
        let xTop = W*0.5 + (W*0.4 + 20) * (i/6);
        let yTop = (H - 60) * (1 - Math.pow(i/6, 2)) + (H - 250) * Math.pow(i/6, 2);
        ctx.beginPath(); ctx.moveTo(xBottom, H); ctx.lineTo(xTop, yTop); ctx.stroke();
    }

    ctx.strokeStyle = '#382515'; ctx.lineWidth = 24; ctx.lineCap = 'round';
    ctx.beginPath(); ctx.moveTo(-20, H - 180); ctx.lineTo(W * 0.5, H - 180);
    ctx.quadraticCurveTo(W * 0.95, H - 180, W + 40, H - 380); ctx.stroke();
    
    ctx.strokeStyle = '#5c351b'; ctx.lineWidth = 6;
    ctx.beginPath(); ctx.moveTo(-20, H - 188); ctx.lineTo(W * 0.5, H - 188);
    ctx.quadraticCurveTo(W * 0.93, H - 188, W + 36, H - 382); ctx.stroke();

    ctx.strokeStyle = '#2b1b0d'; ctx.lineWidth = 16;
    for (let x = 40; x < W + 50; x += 160) {
        let railY = H - 180, deckY = H - 60;
        if (x > W * 0.5) {
            let p = (x - W * 0.5) / (W * 0.5);
            railY = (H - 180) * (1 - p*p) + (H - 380) * (p*p);
            deckY = (H - 60) * (1 - p*p) + (H - 250) * (p*p);
        }
        ctx.beginPath(); ctx.moveTo(x, railY); ctx.lineTo(x, deckY); ctx.stroke();
        ctx.strokeStyle = '#4a2e15'; ctx.lineWidth = 4;
        ctx.beginPath(); ctx.moveTo(x-4, railY); ctx.lineTo(x-4, deckY); ctx.stroke();
        ctx.strokeStyle = '#2b1b0d'; ctx.lineWidth = 16;
    }
    
    let lanX = W * 0.2; 
    let lanY = H - 25; // Base of lantern
    
    ctx.fillStyle = '#111'; ctx.fillRect(lanX - 4, lanY, 8, 50);
    ctx.fillStyle = '#1a1a1a';
    ctx.beginPath(); ctx.moveTo(lanX - 16, lanY - 30); ctx.lineTo(lanX + 16, lanY - 30);
    ctx.lineTo(lanX + 22, lanY + 10); ctx.lineTo(lanX - 22, lanY + 10); ctx.fill();
    ctx.fillStyle = 'rgba(255, 200, 50, 0.9)';
    ctx.beginPath(); ctx.moveTo(lanX - 12, lanY - 25); ctx.lineTo(lanX + 12, lanY - 25);
    ctx.lineTo(lanX + 16, lanY + 5); ctx.lineTo(lanX - 16, lanY + 5); ctx.fill();
    ctx.strokeStyle = '#111'; ctx.lineWidth = 3;
    ctx.beginPath(); ctx.moveTo(lanX, lanY - 30); ctx.lineTo(lanX, lanY + 10); ctx.stroke();
    ctx.fillStyle = '#111';
    ctx.beginPath(); ctx.moveTo(lanX - 20, lanY - 30); ctx.lineTo(lanX + 20, lanY - 30);
    ctx.lineTo(lanX, lanY - 50); ctx.fill();
    
    let glow = ctx.createRadialGradient(lanX, lanY - 10, 0, lanX, lanY - 10, 100);
    glow.addColorStop(0, 'rgba(255, 180, 50, 0.5)');
    glow.addColorStop(1, 'rgba(255, 180, 50, 0)');
    ctx.fillStyle = glow; ctx.fillRect(lanX - 100, lanY - 110, 200, 200);

    let bowGrad = ctx.createLinearGradient(W - 40, H - 280, W + 150, H - 400);
    bowGrad.addColorStop(0, '#22150a'); bowGrad.addColorStop(1, '#4a2e15');
    ctx.strokeStyle = bowGrad; ctx.lineWidth = 28; ctx.lineCap = 'round';
    ctx.beginPath(); ctx.moveTo(W - 40, H - 280); ctx.lineTo(W + 160, H - 410); ctx.stroke();

    ctx.strokeStyle = 'rgba(0,0,0,0.5)'; ctx.lineWidth = 4;
    ctx.beginPath(); ctx.moveTo(W - 100, H - 200); ctx.lineTo(W + 150, H - 500); ctx.stroke();
    ctx.beginPath(); ctx.moveTo(W * 0.5, H - 180); ctx.lineTo(W + 100, H - 500); ctx.stroke();

    ctx.restore();
  }
"""
content = content.replace(content[content.find(start_marker):content.find(end_marker)], bow_new)


# Replace drawFirstPersonStern
start_marker = "  function drawFirstPersonStern() {"
end_marker = "  function drawFirstPersonCenter() {"
stern_new = """  function drawFirstPersonStern() {
    ctx.save();
    ctx.translate(0, sh.bobY * 1.5);
    ctx.rotate(sh.roll * 0.2);

    let deckGrad = ctx.createLinearGradient(0, logicalH - 120, 0, logicalH);
    deckGrad.addColorStop(0, '#1c1006');
    deckGrad.addColorStop(1, '#3a2311');
    ctx.fillStyle = deckGrad;
    ctx.beginPath();
    ctx.moveTo(-50, logicalH); ctx.lineTo(-50, logicalH - 80);
    ctx.lineTo(logicalW * 0.7, logicalH - 130); ctx.lineTo(logicalW + 50, logicalH - 130); ctx.lineTo(logicalW + 50, logicalH); ctx.fill();

    ctx.strokeStyle = '#110802'; ctx.lineWidth = 2;
    for(let i=1; i<10; i++) {
        let xBottom = -50 + (logicalW + 100) * (i/10);
        let xTop = logicalW * 0.7 + (logicalW * 0.3 + 50) * (i/10); 
        ctx.beginPath(); ctx.moveTo(xBottom, logicalH); ctx.lineTo(xTop, logicalH - 130); ctx.stroke();
    }

    let mastGrad = ctx.createLinearGradient(logicalW * 0.55, 0, logicalW * 0.55 + 30, 0);
    mastGrad.addColorStop(0, '#1c1006'); mastGrad.addColorStop(0.5, '#4a2a14'); mastGrad.addColorStop(1, '#110802');
    ctx.fillStyle = mastGrad; ctx.fillRect(logicalW * 0.55, logicalH - 400, 30, 300);
    ctx.fillStyle = '#221105'; ctx.fillRect(logicalW * 0.53, logicalH - 400, 34, 15);

    if (sh.sailAmt > 0.01) {
       let sailGrad = ctx.createLinearGradient(logicalW * 0.4, 0, logicalW * 0.7, 0);
       sailGrad.addColorStop(0, `rgba(210,190,170,${sh.sailAmt})`);
       sailGrad.addColorStop(0.5, `rgba(240,230,210,${sh.sailAmt})`);
       sailGrad.addColorStop(1, `rgba(180,160,140,${sh.sailAmt})`);
       ctx.fillStyle = sailGrad;
       
       let billow = Math.sin(t * 1.5) * 15 * sh.sailAmt;
       ctx.beginPath();
       ctx.moveTo(logicalW * 0.35, logicalH - 320);
       ctx.quadraticCurveTo(logicalW * 0.55 + billow, logicalH - 280, logicalW * 0.75, logicalH - 320);
       ctx.quadraticCurveTo(logicalW * 0.8 + billow, logicalH - 220, logicalW * 0.75, logicalH - 140);
       ctx.quadraticCurveTo(logicalW * 0.55 + billow*1.5, logicalH - 100, logicalW * 0.35, logicalH - 140);
       ctx.quadraticCurveTo(logicalW * 0.3 + billow, logicalH - 220, logicalW * 0.35, logicalH - 320);
       ctx.fill();
       
       ctx.strokeStyle = '#221105'; ctx.lineWidth = 8; ctx.lineCap = 'round';
       ctx.beginPath(); ctx.moveTo(logicalW * 0.32, logicalH - 325); ctx.lineTo(logicalW * 0.78, logicalH - 325); ctx.stroke();
    }
    
    ctx.strokeStyle = 'rgba(0,0,0,0.4)'; ctx.lineWidth = 3;
    ctx.beginPath(); ctx.moveTo(logicalW * 0.55, logicalH - 350); ctx.lineTo(logicalW * 0.2, logicalH - 130); ctx.stroke();
    ctx.beginPath(); ctx.moveTo(logicalW * 0.58, logicalH - 350); ctx.lineTo(logicalW * 0.9, logicalH - 130); ctx.stroke();

    let helmX = logicalW * 0.25, helmY = logicalH - 70;
    let standGrad = ctx.createLinearGradient(helmX - 15, 0, helmX + 15, 0);
    standGrad.addColorStop(0, '#1a0f05'); standGrad.addColorStop(0.5, '#422412'); standGrad.addColorStop(1, '#0d0702');
    ctx.fillStyle = standGrad;
    ctx.beginPath(); ctx.moveTo(helmX - 15, helmY - 50); ctx.lineTo(helmX + 15, helmY - 50);
    ctx.lineTo(helmX + 25, logicalH); ctx.lineTo(helmX - 25, logicalH); ctx.fill();
    
    ctx.beginPath(); ctx.arc(helmX, helmY - 50, 50, 0, Math.PI*2); 
    ctx.strokeStyle = '#110802'; ctx.lineWidth = 16; ctx.stroke(); 
    ctx.strokeStyle = '#4a2e15'; ctx.lineWidth = 12; ctx.stroke(); 
    
    ctx.fillStyle = '#4a2e15';
    for(let i=0; i<8; i++) { 
        let ang = i * Math.PI/4 + sh.roll * 1.5;
        let cx = helmX + Math.cos(ang)*50;
        let cy = helmY - 50 + Math.sin(ang)*50;
        
        ctx.beginPath(); ctx.moveTo(helmX, helmY - 50); ctx.lineTo(cx, cy);
        ctx.lineWidth = 6; ctx.strokeStyle = '#2b190a'; ctx.stroke();
        
        ctx.beginPath(); ctx.moveTo(cx, cy);
        ctx.lineTo(helmX + Math.cos(ang)*65, helmY - 50 + Math.sin(ang)*65);
        ctx.lineWidth = 8; ctx.strokeStyle = '#4a2e15'; ctx.lineCap = 'round'; ctx.stroke();
    }
    ctx.fillStyle = '#b89451';
    ctx.beginPath(); ctx.arc(helmX, helmY - 50, 10, 0, Math.PI*2); ctx.fill();

    ctx.restore();
  }
"""
content = content.replace(content[content.find(start_marker):content.find(end_marker)], stern_new)


# Replace drawFirstPersonCenter
start_marker = "  function drawFirstPersonCenter() {"
end_marker = "  function drawFirstPersonCabin() {"
center_new = """  function drawFirstPersonCenter() {
    ctx.save();
    ctx.translate(0, sh.bobY * 1.2);
    ctx.rotate(sh.roll * 0.3);

    let deckY = logicalH - 70;
    let deckGrad = ctx.createLinearGradient(0, deckY, 0, logicalH);
    deckGrad.addColorStop(0, '#221309'); deckGrad.addColorStop(1, '#3a2311');
    ctx.fillStyle = deckGrad; ctx.fillRect(0, deckY, logicalW, 70);
    
    ctx.strokeStyle = '#110802'; ctx.lineWidth = 2;
    for(let y = deckY + 10; y < logicalH; y += 15) {
        ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(logicalW, y); ctx.stroke();
    }

    ctx.fillStyle = '#110802'; ctx.fillRect(0, deckY - 15, logicalW, 15);
    
    ctx.fillStyle = '#1a0f05';
    for (let x = 30; x < logicalW + 50; x += 160) {
        ctx.fillStyle = 'rgba(0,0,0,0.5)';
        ctx.beginPath(); ctx.moveTo(x + 20, deckY); ctx.lineTo(x + 50, deckY + 30); ctx.lineTo(x + 10, deckY + 30); ctx.lineTo(x - 20, deckY); ctx.fill();
        let pGrad = ctx.createLinearGradient(x-15, 0, x+15, 0);
        pGrad.addColorStop(0, '#2b190a'); pGrad.addColorStop(0.5, '#4a2e15'); pGrad.addColorStop(1, '#110802');
        ctx.fillStyle = pGrad; ctx.fillRect(x - 15, deckY - 140, 30, 140);
        ctx.fillStyle = '#110802';
        ctx.fillRect(x - 18, deckY - 145, 36, 10); ctx.fillRect(x - 18, deckY - 30, 36, 10);
    }

    let railGrad = ctx.createLinearGradient(0, deckY - 160, 0, deckY - 130);
    railGrad.addColorStop(0, '#5c351b'); railGrad.addColorStop(0.5, '#3a1f0f'); railGrad.addColorStop(1, '#110802');
    ctx.fillStyle = railGrad; ctx.fillRect(0, deckY - 160, logicalW, 30);

    let cx = logicalW * 0.65, cy = deckY - 30;
    ctx.fillStyle = '#422412'; ctx.fillRect(cx - 40, cy, 80, 40);
    ctx.fillStyle = '#221105'; ctx.fillRect(cx - 30, cy + 10, 60, 20); 
    ctx.fillStyle = '#110802';
    ctx.beginPath(); ctx.arc(cx - 30, cy + 40, 15, 0, Math.PI*2); ctx.fill();
    ctx.beginPath(); ctx.arc(cx + 30, cy + 40, 15, 0, Math.PI*2); ctx.fill();
    ctx.fillStyle = '#3a200a';
    ctx.beginPath(); ctx.arc(cx - 30, cy + 40, 12, 0, Math.PI*2); ctx.fill();
    ctx.beginPath(); ctx.arc(cx + 30, cy + 40, 12, 0, Math.PI*2); ctx.fill();

    let barrelGrad = ctx.createLinearGradient(cx - 20, 0, cx + 20, 0);
    barrelGrad.addColorStop(0, '#111'); barrelGrad.addColorStop(0.3, '#555'); barrelGrad.addColorStop(0.6, '#222'); barrelGrad.addColorStop(1, '#050505');
    ctx.fillStyle = barrelGrad;
    ctx.beginPath(); ctx.moveTo(cx - 25, cy + 10); ctx.lineTo(cx - 15, deckY - 150); ctx.lineTo(cx + 15, deckY - 150); ctx.lineTo(cx + 25, cy + 10); ctx.fill();
    
    ctx.fillStyle = '#333'; ctx.beginPath(); ctx.ellipse(cx, deckY - 150, 18, 8, 0, 0, Math.PI*2); ctx.fill();
    ctx.fillStyle = '#050505'; ctx.beginPath(); ctx.ellipse(cx, deckY - 150, 12, 5, 0, 0, Math.PI*2); ctx.fill();
    ctx.fillStyle = '#111';
    ctx.beginPath(); ctx.ellipse(cx, deckY - 100, 21, 6, 0, 0, Math.PI*2); ctx.fill();
    ctx.beginPath(); ctx.ellipse(cx, deckY - 50, 24, 8, 0, 0, Math.PI*2); ctx.fill();

    let bx = logicalW * 0.25, by = deckY - 60;
    let bGrad = ctx.createLinearGradient(bx - 35, 0, bx + 35, 0);
    bGrad.addColorStop(0, '#1a0f05'); bGrad.addColorStop(0.5, '#4a2a14'); bGrad.addColorStop(1, '#0a0501');
    ctx.fillStyle = bGrad;
    ctx.beginPath(); ctx.moveTo(bx - 25, by - 50); ctx.quadraticCurveTo(bx - 40, by, bx - 25, by + 50);
    ctx.lineTo(bx + 25, by + 50); ctx.quadraticCurveTo(bx + 40, by, bx + 25, by - 50); ctx.fill();
    ctx.fillStyle = '#3a200a'; ctx.beginPath(); ctx.ellipse(bx, by - 50, 25, 8, 0, 0, Math.PI*2); ctx.fill();
    
    ctx.strokeStyle = '#111'; ctx.lineWidth = 6;
    ctx.beginPath(); ctx.moveTo(bx - 32, by - 25); ctx.quadraticCurveTo(bx, by - 15, bx + 32, by - 25); ctx.stroke();
    ctx.beginPath(); ctx.moveTo(bx - 34, by); ctx.quadraticCurveTo(bx, by + 10, bx + 34, by); ctx.stroke();
    ctx.beginPath(); ctx.moveTo(bx - 32, by + 25); ctx.quadraticCurveTo(bx, by + 35, bx + 32, by + 25); ctx.stroke();
    
    ctx.strokeStyle = '#1a0f05'; ctx.lineWidth = 2;
    for(let i=-2; i<=2; i++) {
        ctx.beginPath(); ctx.moveTo(bx + i*10, by - 50); ctx.quadraticCurveTo(bx + i*13, by, bx + i*10, by + 50); ctx.stroke();
    }
    ctx.restore();
  }
"""
content = content.replace(content[content.find(start_marker):content.find(end_marker)], center_new)


# Replace drawFirstPersonCabin
start_marker = "  function drawFirstPersonCabin() {"
end_marker = "  function loop(timestamp) {"
cabin_new = """  function drawFirstPersonCabin() {
    ctx.save();
    ctx.translate(0, sh.bobY * 0.5);
    ctx.rotate(sh.roll * 0.1);
    
    let woodGrad = ctx.createLinearGradient(0, 0, logicalW, logicalH);
    woodGrad.addColorStop(0, '#311a0c'); woodGrad.addColorStop(0.5, '#221105'); woodGrad.addColorStop(1, '#110802');
    
    const f = 50;
    ctx.fillStyle = woodGrad;
    ctx.fillRect(0, 0, logicalW, f); ctx.fillRect(0, logicalH - f * 2.5, logicalW, f * 2.5);
    ctx.fillRect(0, 0, f, logicalH); ctx.fillRect(logicalW - f, 0, f, logicalH);
    
    ctx.fillRect(logicalW * 0.33 - 12, 0, 24, logicalH); ctx.fillRect(logicalW * 0.66 - 12, 0, 24, logicalH);
    ctx.fillRect(0, logicalH * 0.45 - 12, logicalW, 24);

    ctx.lineWidth = 4;
    function bevel(x, y, w, h) {
       ctx.strokeStyle = '#000'; ctx.strokeRect(x, y, w, h);
       ctx.strokeStyle = '#4a2b15';
       ctx.beginPath(); ctx.moveTo(x+2, y+h-2); ctx.lineTo(x+2, y+2); ctx.lineTo(x+w-2, y+2); ctx.stroke();
       ctx.strokeStyle = '#0a0501';
       ctx.beginPath(); ctx.moveTo(x+w-2, y+2); ctx.lineTo(x+w-2, y+h-2); ctx.lineTo(x+2, y+h-2); ctx.stroke();
    }
    bevel(f, f, logicalW*0.33 - 12 - f, logicalH*0.45 - 12 - f);
    bevel(logicalW*0.33 + 12, f, logicalW*0.33 - 24, logicalH*0.45 - 12 - f);
    bevel(logicalW*0.66 + 12, f, logicalW - f - (logicalW*0.66 + 12), logicalH*0.45 - 12 - f);
    bevel(f, logicalH*0.45 + 12, logicalW*0.33 - 12 - f, logicalH - f*2.5 - (logicalH*0.45 + 12));
    bevel(logicalW*0.33 + 12, logicalH*0.45 + 12, logicalW*0.33 - 24, logicalH - f*2.5 - (logicalH*0.45 + 12));
    bevel(logicalW*0.66 + 12, logicalH*0.45 + 12, logicalW - f - (logicalW*0.66 + 12), logicalH - f*2.5 - (logicalH*0.45 + 12));

    let deskY = logicalH - 80;
    let deskGrad = ctx.createLinearGradient(0, deskY, 0, logicalH);
    deskGrad.addColorStop(0, '#422412'); deskGrad.addColorStop(1, '#1b0e06');
    ctx.fillStyle = deskGrad; ctx.fillRect(0, deskY, logicalW, 80);
    ctx.fillStyle = '#5c351b'; ctx.fillRect(0, deskY, logicalW, 4);
    
    ctx.fillStyle = '#e8d5a7';
    ctx.beginPath(); ctx.moveTo(logicalW * 0.35, deskY + 10); ctx.lineTo(logicalW * 0.65, deskY + 10);
    ctx.lineTo(logicalW * 0.72, logicalH - 10); ctx.lineTo(logicalW * 0.28, logicalH - 10); ctx.fill();
    ctx.strokeStyle = '#c4ae7e'; ctx.lineWidth = 2;
    ctx.beginPath(); ctx.moveTo(logicalW * 0.35, deskY + 30);
    ctx.quadraticCurveTo(logicalW * 0.45, deskY + 40, logicalW * 0.42, deskY + 60);
    ctx.quadraticCurveTo(logicalW * 0.5, deskY + 65, logicalW * 0.55, deskY + 50); ctx.stroke();
    ctx.strokeStyle = '#9c3131'; ctx.beginPath(); ctx.arc(logicalW * 0.62, logicalH - 35, 12, 0, Math.PI*2); ctx.stroke();
    
    ctx.fillStyle = '#0a0a0a'; ctx.beginPath(); ctx.ellipse(logicalW * 0.75, logicalH - 30, 10, 15, 0, 0, Math.PI*2); ctx.fill();
    ctx.fillStyle = '#222'; ctx.beginPath(); ctx.ellipse(logicalW * 0.75, logicalH - 45, 6, 3, 0, 0, Math.PI*2); ctx.fill();
    ctx.fillStyle = '#f4f4f4';
    ctx.beginPath(); ctx.moveTo(logicalW * 0.75, logicalH - 45); 
    ctx.quadraticCurveTo(logicalW * 0.72, logicalH - 80, logicalW * 0.8, logicalH - 90);
    ctx.quadraticCurveTo(logicalW * 0.77, logicalH - 70, logicalW * 0.76, logicalH - 45); ctx.fill();
    
    let candleX = logicalW * 0.22, candleY = logicalH - 45;
    ctx.fillStyle = '#e5e0d8'; ctx.fillRect(candleX - 8, candleY - 30, 16, 30);
    ctx.fillStyle = '#b89451'; ctx.beginPath(); ctx.ellipse(candleX, candleY, 20, 8, 0, 0, Math.PI*2); ctx.fill();
    ctx.fillStyle = '#ffcc00';
    ctx.beginPath(); ctx.moveTo(candleX, candleY - 30);
    ctx.quadraticCurveTo(candleX + 5, candleY - 40, candleX, candleY - 45);
    ctx.quadraticCurveTo(candleX - 5, candleY - 40, candleX, candleY - 30); ctx.fill();
    let glow = ctx.createRadialGradient(candleX, candleY - 35, 0, candleX, candleY - 35, 100);
    glow.addColorStop(0, 'rgba(255, 150, 0, 0.4)'); glow.addColorStop(1, 'rgba(255, 150, 0, 0)');
    ctx.fillStyle = glow; ctx.beginPath(); ctx.arc(candleX, candleY - 35, 100, 0, Math.PI*2); ctx.fill();

    ctx.restore();
  }
"""
content = content.replace(content[content.find(start_marker):content.find(end_marker)], cabin_new)

with open('assets/ocean.js', 'w') as f:
    f.write(content)

