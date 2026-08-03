import re

with open('assets/ocean.js', 'r') as f:
    content = f.read()

# 1. Bow View: Fix railing posts and remove floating bowsprit
bow_post_old = """    ctx.strokeStyle = '#2b1b0d'; ctx.lineWidth = 16;
    for (let x = 40; x < W + 50; x += 160) {"""
bow_post_new = """    ctx.strokeStyle = '#2b1b0d'; ctx.lineWidth = 16;
    for (let x = 40; x < W * 0.7; x += 160) {"""
content = content.replace(bow_post_old, bow_post_new)

bowsprit_old = """    let bowGrad = ctx.createLinearGradient(W - 40, H - 280, W + 150, H - 400);
    bowGrad.addColorStop(0, '#22150a'); bowGrad.addColorStop(1, '#4a2e15');
    ctx.strokeStyle = bowGrad; ctx.lineWidth = 28; ctx.lineCap = 'round';
    ctx.beginPath(); ctx.moveTo(W - 40, H - 280); ctx.lineTo(W + 160, H - 410); ctx.stroke();"""
bowsprit_new = ""
content = content.replace(bowsprit_old, bowsprit_new)


# 2. Stern View: Fix floating starboard deck by converging deck to center and drawing symmetrical railings
stern_old = """    let deckGrad = ctx.createLinearGradient(0, logicalH - 120, 0, logicalH);
    deckGrad.addColorStop(0, '#1c1006');
    deckGrad.addColorStop(1, '#3a2311');
    ctx.fillStyle = deckGrad;
    ctx.beginPath();
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
stern_new = """    let deckGrad = ctx.createLinearGradient(0, logicalH - 160, 0, logicalH);
    deckGrad.addColorStop(0, '#1c1006');
    deckGrad.addColorStop(1, '#3a2311');
    ctx.fillStyle = deckGrad;
    ctx.beginPath();
    ctx.moveTo(-150, logicalH + 200); ctx.lineTo(-150, logicalH - 60);
    ctx.lineTo(logicalW * 0.5, logicalH - 160); // bow tip in center
    ctx.lineTo(logicalW + 150, logicalH - 60);
    ctx.lineTo(logicalW + 150, logicalH + 200);
    ctx.fill();

    // Port Railing (Left)
    ctx.strokeStyle = '#2b1b0d'; ctx.lineWidth = 12; ctx.lineCap = 'round';
    ctx.beginPath(); ctx.moveTo(-150, logicalH - 90); ctx.lineTo(logicalW * 0.5, logicalH - 175); ctx.stroke();
    ctx.strokeStyle = '#1a0f05'; ctx.lineWidth = 16;
    ctx.beginPath(); ctx.moveTo(-150, logicalH - 120); ctx.lineTo(logicalW * 0.5, logicalH - 195); ctx.stroke();

    // Starboard Railing (Right)
    ctx.strokeStyle = '#2b1b0d'; ctx.lineWidth = 12;
    ctx.beginPath(); ctx.moveTo(logicalW * 0.5, logicalH - 175); ctx.lineTo(logicalW + 150, logicalH - 90); ctx.stroke();
    ctx.strokeStyle = '#1a0f05'; ctx.lineWidth = 16;
    ctx.beginPath(); ctx.moveTo(logicalW * 0.5, logicalH - 195); ctx.lineTo(logicalW + 150, logicalH - 120); ctx.stroke();

    // Deck lines
    ctx.strokeStyle = '#110802'; ctx.lineWidth = 3;
    for(let i=1; i<10; i++) {
        let xBottom = -150 + (logicalW + 300) * (i/10);
        let xTop = logicalW * 0.5; 
        ctx.beginPath(); ctx.moveTo(xBottom, logicalH + 200); ctx.lineTo(xTop, logicalH - 160); ctx.stroke();
    }"""
content = content.replace(stern_old, stern_new)

stern_mast_old = """    ctx.fillStyle = '#221105';
    ctx.fillRect(logicalW * 0.55, logicalH - 400, 30, 300);"""
stern_mast_new = """    ctx.fillStyle = '#221105';
    ctx.fillRect(logicalW * 0.5 - 15, logicalH - 400, 30, 250);"""
content = content.replace(stern_mast_old, stern_mast_new)

stern_sail_old = """       let billow = Math.sin(t * 1.5) * 15 * sh.sailAmt;
       ctx.beginPath();
       ctx.moveTo(logicalW * 0.35, logicalH - 320);
       ctx.quadraticCurveTo(logicalW * 0.55 + billow, logicalH - 280, logicalW * 0.75, logicalH - 320);
       ctx.quadraticCurveTo(logicalW * 0.8 + billow, logicalH - 220, logicalW * 0.75, logicalH - 140);
       ctx.quadraticCurveTo(logicalW * 0.55 + billow*1.5, logicalH - 100, logicalW * 0.35, logicalH - 140);
       ctx.quadraticCurveTo(logicalW * 0.3 + billow, logicalH - 220, logicalW * 0.35, logicalH - 320);
       ctx.fill();
       
       
       
       ctx.strokeStyle = '#221105'; ctx.lineWidth = 8; ctx.lineCap = 'round';
       ctx.beginPath(); ctx.moveTo(logicalW * 0.32, logicalH - 325); ctx.lineTo(logicalW * 0.78, logicalH - 325); ctx.stroke();"""
stern_sail_new = """       let billow = Math.sin(t * 1.5) * 15 * sh.sailAmt;
       ctx.beginPath();
       ctx.moveTo(logicalW * 0.25, logicalH - 320);
       ctx.quadraticCurveTo(logicalW * 0.5 + billow, logicalH - 280, logicalW * 0.75, logicalH - 320);
       ctx.quadraticCurveTo(logicalW * 0.8 + billow, logicalH - 220, logicalW * 0.75, logicalH - 140);
       ctx.quadraticCurveTo(logicalW * 0.5 + billow*1.5, logicalH - 100, logicalW * 0.25, logicalH - 140);
       ctx.quadraticCurveTo(logicalW * 0.2 + billow, logicalH - 220, logicalW * 0.25, logicalH - 320);
       ctx.fill();
       
       ctx.strokeStyle = '#221105'; ctx.lineWidth = 8; ctx.lineCap = 'round';
       ctx.beginPath(); ctx.moveTo(logicalW * 0.22, logicalH - 325); ctx.lineTo(logicalW * 0.78, logicalH - 325); ctx.stroke();"""
content = content.replace(stern_sail_old, stern_sail_new)

stern_wheel_old = """    let wx = logicalW * 0.3, wy = logicalH - 80;"""
stern_wheel_new = """    let wx = logicalW * 0.5, wy = logicalH - 80;"""
content = content.replace(stern_wheel_old, stern_wheel_new)


# 3. Center View: Shift barrels so they don't visually align with railing posts and look like antennas
center_barrels_old = """    drawBarrel(logicalW * 0.2, deckY - 50);
    drawBarrel(logicalW * 0.35, deckY - 45);"""
center_barrels_new = """    drawBarrel(logicalW * 0.28, deckY - 50);
    drawBarrel(logicalW * 0.45, deckY - 45);"""
content = content.replace(center_barrels_old, center_barrels_new)

with open('assets/ocean.js', 'w') as f:
    f.write(content)

