import re

with open('assets/ocean.js', 'r') as f:
    content = f.read()

# 1. Captain's Cabin - Add Anchor
cabin_old = """    ctx.rotate(sh.roll * 0.1);
    
    let woodGrad = ctx.createLinearGradient(0, 0, logicalW, logicalH);"""
cabin_new = """    ctx.rotate(sh.roll * 0.1);
    
    // Anchor passing window
    let ax = logicalW * 0.85;
    let ay = -100 + sh.anchorAmt * (logicalH + 300); 
    
    ctx.strokeStyle = '#333'; ctx.lineWidth = 10;
    ctx.beginPath(); ctx.moveTo(ax, -200); ctx.lineTo(ax, ay); ctx.stroke();
    ctx.strokeStyle = '#555'; ctx.lineWidth = 4;
    for(let y = -200; y < ay; y += 15) {
        ctx.beginPath(); ctx.ellipse(ax, y, 6, 12, 0, 0, Math.PI*2); ctx.stroke();
    }
    
    ctx.fillStyle = '#222';
    ctx.fillRect(ax - 5, ay, 10, 80); 
    ctx.fillRect(ax - 30, ay + 15, 60, 10); 
    ctx.beginPath(); ctx.arc(ax, ay + 80, 40, 0, Math.PI);
    ctx.lineWidth = 12; ctx.strokeStyle = '#222'; ctx.stroke();
    ctx.beginPath(); ctx.moveTo(ax + 40, ay + 80); ctx.lineTo(ax + 55, ay + 65); ctx.lineTo(ax + 25, ay + 65); ctx.fill();
    ctx.beginPath(); ctx.moveTo(ax - 40, ay + 80); ctx.lineTo(ax - 55, ay + 65); ctx.lineTo(ax - 25, ay + 65); ctx.fill();

    let woodGrad = ctx.createLinearGradient(0, 0, logicalW, logicalH);"""
content = content.replace(cabin_old, cabin_new)


# 2. Bow View - Craftsmanship (Wood grain, nails, rope coil)
bow_old = """    ctx.strokeStyle = '#110802'; ctx.lineWidth = 2;
    for(let i=0; i<10; i++) {
        let xBottom = -20 + (W*0.6 + 20) * (i/10);
        let xTop = -20 + (W*0.5 + 20) * (i/10);
        ctx.beginPath(); ctx.moveTo(xBottom, H + 200); ctx.lineTo(xTop, H - 60); ctx.stroke();
    }
    for(let i=0; i<6; i++) {
        let xBottom = W*0.6 + (W*0.4 + 20) * (i/6);
        let xTop = W*0.5 + (W*0.4 + 20) * (i/6);
        let yTop = (H - 60) * (1 - Math.pow(i/6, 2)) + (H - 250) * Math.pow(i/6, 2);
        ctx.beginPath(); ctx.moveTo(xBottom, H); ctx.lineTo(xTop, yTop); ctx.stroke();
    }"""
bow_new = """    ctx.strokeStyle = 'rgba(0,0,0,0.2)'; ctx.lineWidth = 1;
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
content = content.replace(bow_old, bow_new)

# 3. Stern View - Sail color, text, deck wood
stern_old = """    if (sh.sailAmt > 0.01) {
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
    }"""
stern_new = """    if (sh.sailAmt > 0.01) {
       let sailGrad = ctx.createLinearGradient(logicalW * 0.4, 0, logicalW * 0.7, 0);
       sailGrad.addColorStop(0, `rgba(25,25,25,${sh.sailAmt})`);
       sailGrad.addColorStop(0.5, `rgba(45,45,45,${sh.sailAmt})`);
       sailGrad.addColorStop(1, `rgba(15,15,15,${sh.sailAmt})`);
       ctx.fillStyle = sailGrad;
       
       let billow = Math.sin(t * 1.5) * 15 * sh.sailAmt;
       ctx.beginPath();
       ctx.moveTo(logicalW * 0.35, logicalH - 320);
       ctx.quadraticCurveTo(logicalW * 0.55 + billow, logicalH - 280, logicalW * 0.75, logicalH - 320);
       ctx.quadraticCurveTo(logicalW * 0.8 + billow, logicalH - 220, logicalW * 0.75, logicalH - 140);
       ctx.quadraticCurveTo(logicalW * 0.55 + billow*1.5, logicalH - 100, logicalW * 0.35, logicalH - 140);
       ctx.quadraticCurveTo(logicalW * 0.3 + billow, logicalH - 220, logicalW * 0.35, logicalH - 320);
       ctx.fill();
       
       ctx.save();
       let cx = logicalW * 0.55 + billow * 0.8;
       let cy = logicalH - 210 + billow * 0.2;
       ctx.translate(cx, cy);
       ctx.globalAlpha = sh.sailAmt;
       ctx.font = 'bold 42px "Times New Roman", serif';
       ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
       ctx.fillStyle = '#d4af37';
       ctx.shadowColor = 'rgba(0,0,0,0.8)'; ctx.shadowBlur = 8;
       ctx.fillText("Captain M", 0, 0);
       ctx.restore();
       
       ctx.strokeStyle = '#221105'; ctx.lineWidth = 8; ctx.lineCap = 'round';
       ctx.beginPath(); ctx.moveTo(logicalW * 0.32, logicalH - 325); ctx.lineTo(logicalW * 0.78, logicalH - 325); ctx.stroke();
    }"""
content = content.replace(stern_old, stern_new)

stern_deck_old = """    ctx.strokeStyle = '#110802'; ctx.lineWidth = 2;
    for(let i=1; i<10; i++) {
        let xBottom = -150 + (logicalW * 1.1 + 150) * (i/10);
        let xTop = logicalW * 0.85 * (i/10); 
        ctx.beginPath(); ctx.moveTo(xBottom, logicalH + 200); ctx.lineTo(xTop, logicalH - 130); ctx.stroke();
    }"""
stern_deck_new = """    ctx.strokeStyle = 'rgba(0,0,0,0.2)'; ctx.lineWidth = 1;
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
content = content.replace(stern_deck_old, stern_deck_new)

with open('assets/ocean.js', 'w') as f:
    f.write(content)

