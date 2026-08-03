import re

with open('assets/ocean.js', 'r') as f:
    content = f.read()

# 1. Anchor speed in update()
# Find: sh.anchorAmt = Math.max(0, 1 - tp * 4);
# Replace with: sh.anchorAmt = Math.max(0, 1 - tp * 1.5);
content = content.replace("sh.anchorAmt = Math.max(0, 1 - tp * 4);", "sh.anchorAmt = Math.max(0, 1 - tp * 1.5);")
# Find: sh.anchorAmt = Math.min(1, tp * 2.2);
# Replace with: sh.anchorAmt = Math.min(1, tp * 1.5);
content = content.replace("sh.anchorAmt = Math.min(1, tp * 2.2);", "sh.anchorAmt = Math.min(1, tp * 1.5);")


# 2. Bow view cleanup
bow_old = """    ctx.strokeStyle = 'rgba(0,0,0,0.2)'; ctx.lineWidth = 1;
    for(let i=0; i<50; i++) {
        let p1 = Math.random(), p2 = p1 + (Math.random()*0.1 - 0.05);
        let xB = -20 + (W*0.6 + 20) * p1, xT = -20 + (W*0.5 + 20) * p2;
        ctx.beginPath(); ctx.moveTo(xB, H + 200); ctx.lineTo(xT, H - 60); ctx.stroke();
    }
    ctx.strokeStyle = '#110802'; ctx.lineWidth = 3;
    for(let i=0; i<10; i++) {
        let xBottom = -20 + (W*0.6 + 20) * (i/10);
        let xTop = -20 + (W*0.5 + 20) * (i/10);
        ctx.beginPath(); ctx.moveTo(xBottom, H + 200); ctx.lineTo(xTop, H - 60); ctx.stroke();
        ctx.fillStyle = '#0a0a0a';
        ctx.beginPath(); ctx.arc(xBottom + (W*0.6+20)*0.05, H - 10, 3, 0, Math.PI*2); ctx.fill();
        ctx.beginPath(); ctx.arc(xBottom + (W*0.6+20)*0.05, H - 35, 3, 0, Math.PI*2); ctx.fill();
    }
    for(let i=0; i<6; i++) {
        let xBottom = W*0.6 + (W*0.4 + 20) * (i/6);
        let xTop = W*0.5 + (W*0.4 + 20) * (i/6);
        let yTop = (H - 60) * (1 - Math.pow(i/6, 2)) + (H - 250) * Math.pow(i/6, 2);
        ctx.beginPath(); ctx.moveTo(xBottom, H); ctx.lineTo(xTop, yTop); ctx.stroke();
    }
    
    let rx = W * 0.45, ry = H - 20;
    ctx.strokeStyle = '#c4a47c'; ctx.lineWidth = 5;
    for(let r=0; r<18; r+=3) {
        ctx.beginPath(); ctx.ellipse(rx, ry, 35 - r, 12 - r*0.3, 0, 0, Math.PI*2); ctx.stroke();
    }
    ctx.beginPath(); ctx.moveTo(rx, ry); ctx.quadraticCurveTo(rx + 30, ry + 15, rx + 60, ry + 5); ctx.stroke();"""

bow_new = """    ctx.strokeStyle = '#110802'; ctx.lineWidth = 3;
    for(let i=0; i<8; i++) {
        let xBottom = -20 + (W*0.6 + 20) * (i/8);
        let xTop = -20 + (W*0.5 + 20) * (i/8);
        ctx.beginPath(); ctx.moveTo(xBottom, H + 200); ctx.lineTo(xTop, H - 60); ctx.stroke();
        ctx.fillStyle = '#0a0a0a';
        ctx.beginPath(); ctx.arc(xBottom + 5, H - 10, 2, 0, Math.PI*2); ctx.fill();
    }"""
content = content.replace(bow_old, bow_new)

bow_ropes_old = """    ctx.strokeStyle = 'rgba(0,0,0,0.5)'; ctx.lineWidth = 4;
    ctx.beginPath(); ctx.moveTo(W - 100, H - 200); ctx.lineTo(W + 150, H - 500); ctx.stroke();
    ctx.beginPath(); ctx.moveTo(W * 0.5, H - 180); ctx.lineTo(W + 100, H - 500); ctx.stroke();"""
bow_ropes_new = ""
content = content.replace(bow_ropes_old, bow_ropes_new)


# 3. Stern View - Add side railings, remove Captain M text
stern_deck_old = """    ctx.beginPath();
    ctx.moveTo(-150, logicalH + 200); ctx.lineTo(-150, logicalH - 80);
    ctx.lineTo(logicalW * 0.85, logicalH - 130); // tip of bow
    ctx.lineTo(logicalW * 0.85, logicalH + 200); // drop down to cover gap
    ctx.fill();

    ctx.strokeStyle = 'rgba(0,0,0,0.2)'; ctx.lineWidth = 1;
    for(let i=0; i<40; i++) {
        let p1 = Math.random(), p2 = p1 + (Math.random()*0.1 - 0.05);
        let xB = -150 + (logicalW * 1.1 + 150) * p1, xT = logicalW * 0.85 * p2; 
        ctx.beginPath(); ctx.moveTo(xB, logicalH + 200); ctx.lineTo(xT, logicalH - 130); ctx.stroke();
    }
    ctx.strokeStyle = '#110802'; ctx.lineWidth = 3;
    for(let i=1; i<10; i++) {
        let xBottom = -150 + (logicalW * 1.1 + 150) * (i/10);
        let xTop = logicalW * 0.85 * (i/10); 
        ctx.beginPath(); ctx.moveTo(xBottom, logicalH + 200); ctx.lineTo(xTop, logicalH - 130); ctx.stroke();
        ctx.fillStyle = '#0a0a0a';
        ctx.beginPath(); ctx.arc(xBottom + 10, logicalH - 15, 3, 0, Math.PI*2); ctx.fill();
        ctx.beginPath(); ctx.arc(xBottom + 18, logicalH - 45, 3, 0, Math.PI*2); ctx.fill();
    }"""
stern_deck_new = """    ctx.beginPath();
    ctx.moveTo(-150, logicalH + 200); ctx.lineTo(-150, logicalH - 80);
    ctx.lineTo(logicalW * 0.85, logicalH - 130); // tip of bow
    ctx.lineTo(logicalW * 0.85, logicalH + 200); // drop down to cover gap
    ctx.fill();

    // Port Railing (Left Side)
    ctx.strokeStyle = '#2b1b0d'; ctx.lineWidth = 12; ctx.lineCap = 'round';
    ctx.beginPath(); ctx.moveTo(-150, logicalH - 110); ctx.lineTo(logicalW * 0.85, logicalH - 145); ctx.stroke();
    ctx.strokeStyle = '#1a0f05'; ctx.lineWidth = 16;
    ctx.beginPath(); ctx.moveTo(-150, logicalH - 140); ctx.lineTo(logicalW * 0.85, logicalH - 165); ctx.stroke();
    
    // Starboard Railing (Right Side)
    ctx.strokeStyle = '#2b1b0d'; ctx.lineWidth = 12;
    ctx.beginPath(); ctx.moveTo(logicalW * 0.85, logicalH - 145); ctx.lineTo(logicalW + 150, logicalH - 110); ctx.stroke();
    ctx.strokeStyle = '#1a0f05'; ctx.lineWidth = 16;
    ctx.beginPath(); ctx.moveTo(logicalW * 0.85, logicalH - 165); ctx.lineTo(logicalW + 150, logicalH - 140); ctx.stroke();

    ctx.strokeStyle = '#110802'; ctx.lineWidth = 3;
    for(let i=1; i<10; i++) {
        let xBottom = -150 + (logicalW * 1.1 + 150) * (i/10);
        let xTop = logicalW * 0.85 * (i/10); 
        ctx.beginPath(); ctx.moveTo(xBottom, logicalH + 200); ctx.lineTo(xTop, logicalH - 130); ctx.stroke();
    }"""
content = content.replace(stern_deck_old, stern_deck_new)

stern_text_old = """       ctx.save();
       let cx = logicalW * 0.55 + billow * 0.8;
       let cy = logicalH - 210 + billow * 0.2;
       ctx.translate(cx, cy);
       ctx.globalAlpha = sh.sailAmt;
       ctx.font = 'bold 42px "Times New Roman", serif';
       ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
       ctx.fillStyle = '#d4af37';
       ctx.shadowColor = 'rgba(0,0,0,0.8)'; ctx.shadowBlur = 8;
       ctx.fillText("Captain M", 0, 0);
       ctx.restore();"""
stern_text_new = ""
content = content.replace(stern_text_old, stern_text_new)


# 4. Center View - Draw two upright barrels
center_barrel_old = """    let bx = logicalW * 0.25, by = deckY - 60;
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
    }"""
center_barrel_new = """    function drawBarrel(bx, by) {
        ctx.fillStyle = 'rgba(0,0,0,0.6)';
        ctx.beginPath(); ctx.ellipse(bx, by + 45, 35, 10, 0, 0, Math.PI*2); ctx.fill();
        
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
    }
    drawBarrel(logicalW * 0.2, deckY - 50);
    drawBarrel(logicalW * 0.35, deckY - 45);"""
content = content.replace(center_barrel_old, center_barrel_new)

with open('assets/ocean.js', 'w') as f:
    f.write(content)

