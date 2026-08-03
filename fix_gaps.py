import re

with open('assets/ocean.js', 'r') as f:
    content = f.read()

# Fix drawFirstPersonBow
bow_old = """    ctx.moveTo(-20, H); ctx.lineTo(-20, H - 60); ctx.lineTo(W * 0.5, H - 60);
    ctx.quadraticCurveTo(W * 0.9, H - 60, W + 20, H - 250);
    ctx.lineTo(W + 20, H); ctx.fill();

    ctx.strokeStyle = '#110802'; ctx.lineWidth = 2;
    for(let i=0; i<10; i++) {
        let xBottom = -20 + (W*0.6 + 20) * (i/10);
        let xTop = -20 + (W*0.5 + 20) * (i/10);
        ctx.beginPath(); ctx.moveTo(xBottom, H); ctx.lineTo(xTop, H - 60); ctx.stroke();
    }"""
bow_new = """    ctx.moveTo(-20, H + 200); ctx.lineTo(-20, H - 60); ctx.lineTo(W * 0.5, H - 60);
    ctx.quadraticCurveTo(W * 0.9, H - 60, W + 20, H - 250);
    ctx.lineTo(W + 20, H + 200); ctx.fill();

    ctx.strokeStyle = '#110802'; ctx.lineWidth = 2;
    for(let i=0; i<10; i++) {
        let xBottom = -20 + (W*0.6 + 20) * (i/10);
        let xTop = -20 + (W*0.5 + 20) * (i/10);
        ctx.beginPath(); ctx.moveTo(xBottom, H + 200); ctx.lineTo(xTop, H - 60); ctx.stroke();
    }"""
content = content.replace(bow_old, bow_new)

# Fix drawFirstPersonStern
stern_old = """    ctx.beginPath();
    ctx.moveTo(-50, logicalH); ctx.lineTo(-50, logicalH - 80);
    ctx.lineTo(logicalW * 0.7, logicalH - 130); ctx.lineTo(logicalW + 50, logicalH - 130); ctx.lineTo(logicalW + 50, logicalH); ctx.fill();

    ctx.strokeStyle = '#110802'; ctx.lineWidth = 2;
    for(let i=1; i<10; i++) {
        let xBottom = -50 + (logicalW + 100) * (i/10);
        let xTop = logicalW * 0.7 + (logicalW * 0.3 + 50) * (i/10); 
        ctx.beginPath(); ctx.moveTo(xBottom, logicalH); ctx.lineTo(xTop, logicalH - 130); ctx.stroke();
    }"""
stern_new = """    ctx.beginPath();
    ctx.moveTo(-150, logicalH + 200); ctx.lineTo(-150, logicalH - 80);
    ctx.lineTo(logicalW * 0.85, logicalH - 130); // tip of bow
    ctx.lineTo(logicalW * 0.85, logicalH + 200); // drop down to cover gap
    ctx.fill();

    ctx.strokeStyle = '#110802'; ctx.lineWidth = 2;
    for(let i=1; i<10; i++) {
        let xBottom = -150 + (logicalW * 1.1 + 150) * (i/10);
        let xTop = logicalW * 0.85 * (i/10); 
        ctx.beginPath(); ctx.moveTo(xBottom, logicalH + 200); ctx.lineTo(xTop, logicalH - 130); ctx.stroke();
    }"""
content = content.replace(stern_old, stern_new)

# Fix ropes in Stern
ropes_old = """    ctx.strokeStyle = 'rgba(0,0,0,0.4)'; ctx.lineWidth = 3;
    ctx.beginPath(); ctx.moveTo(logicalW * 0.55, logicalH - 350); ctx.lineTo(logicalW * 0.2, logicalH - 130); ctx.stroke();
    ctx.beginPath(); ctx.moveTo(logicalW * 0.58, logicalH - 350); ctx.lineTo(logicalW * 0.9, logicalH - 130); ctx.stroke();"""
ropes_new = """    ctx.strokeStyle = 'rgba(0,0,0,0.4)'; ctx.lineWidth = 3;
    ctx.beginPath(); ctx.moveTo(logicalW * 0.55, logicalH - 350); ctx.lineTo(logicalW * 0.2, logicalH - 100); ctx.stroke();
    ctx.beginPath(); ctx.moveTo(logicalW * 0.58, logicalH - 350); ctx.lineTo(logicalW * 0.8, logicalH - 120); ctx.stroke();"""
content = content.replace(ropes_old, ropes_new)

# Fix drawFirstPersonCenter
center_old = """    let deckY = logicalH - 70;
    let deckGrad = ctx.createLinearGradient(0, deckY, 0, logicalH);
    deckGrad.addColorStop(0, '#221309'); deckGrad.addColorStop(1, '#3a2311');
    ctx.fillStyle = deckGrad; ctx.fillRect(0, deckY, logicalW, 70);
    
    ctx.strokeStyle = '#110802'; ctx.lineWidth = 2;
    for(let y = deckY + 10; y < logicalH; y += 15) {
        ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(logicalW, y); ctx.stroke();
    }

    ctx.fillStyle = '#110802'; ctx.fillRect(0, deckY - 15, logicalW, 15);
    
    ctx.fillStyle = '#1a0f05';
    for (let x = 30; x < logicalW + 50; x += 160) {"""
center_new = """    let deckY = logicalH - 70;
    let deckGrad = ctx.createLinearGradient(0, deckY, 0, logicalH);
    deckGrad.addColorStop(0, '#221309'); deckGrad.addColorStop(1, '#3a2311');
    ctx.fillStyle = deckGrad; ctx.fillRect(-50, deckY, logicalW + 100, 200);
    
    ctx.strokeStyle = '#110802'; ctx.lineWidth = 2;
    for(let y = deckY + 10; y < logicalH + 150; y += 15) {
        ctx.beginPath(); ctx.moveTo(-50, y); ctx.lineTo(logicalW + 100, y); ctx.stroke();
    }

    ctx.fillStyle = '#110802'; ctx.fillRect(-50, deckY - 15, logicalW + 100, 15);
    
    ctx.fillStyle = '#1a0f05';
    for (let x = -130; x < logicalW + 200; x += 160) {"""
content = content.replace(center_old, center_new)

# Fix rail top in Center
rail_old = """    let railGrad = ctx.createLinearGradient(0, deckY - 160, 0, deckY - 130);
    railGrad.addColorStop(0, '#5c351b'); railGrad.addColorStop(0.5, '#3a1f0f'); railGrad.addColorStop(1, '#110802');
    ctx.fillStyle = railGrad; ctx.fillRect(0, deckY - 160, logicalW, 30);"""
rail_new = """    let railGrad = ctx.createLinearGradient(0, deckY - 160, 0, deckY - 130);
    railGrad.addColorStop(0, '#5c351b'); railGrad.addColorStop(0.5, '#3a1f0f'); railGrad.addColorStop(1, '#110802');
    ctx.fillStyle = railGrad; ctx.fillRect(-50, deckY - 160, logicalW + 100, 30);"""
content = content.replace(rail_old, rail_new)

# Fix drawFirstPersonCabin
cabin_old = """    let woodGrad = ctx.createLinearGradient(0, 0, logicalW, logicalH);
    woodGrad.addColorStop(0, '#311a0c'); woodGrad.addColorStop(0.5, '#221105'); woodGrad.addColorStop(1, '#110802');
    
    const f = 50;
    ctx.fillStyle = woodGrad;
    ctx.fillRect(0, 0, logicalW, f); ctx.fillRect(0, logicalH - f * 2.5, logicalW, f * 2.5);
    ctx.fillRect(0, 0, f, logicalH); ctx.fillRect(logicalW - f, 0, f, logicalH);"""
cabin_new = """    let woodGrad = ctx.createLinearGradient(0, 0, logicalW, logicalH);
    woodGrad.addColorStop(0, '#311a0c'); woodGrad.addColorStop(0.5, '#221105'); woodGrad.addColorStop(1, '#110802');
    
    const f = 50;
    ctx.fillStyle = woodGrad;
    ctx.fillRect(-100, -100, logicalW + 200, f + 100); // top extending up
    ctx.fillRect(-100, logicalH - f * 2.5, logicalW + 200, f * 2.5 + 200); // bottom extending down
    ctx.fillRect(-100, -100, f + 100, logicalH + 200); // left extending left
    ctx.fillRect(logicalW - f, -100, f + 100, logicalH + 200); // right extending right"""
content = content.replace(cabin_old, cabin_new)

# Desk and inner elements in cabin
desk_old = """    let deskY = logicalH - 80;
    let deskGrad = ctx.createLinearGradient(0, deskY, 0, logicalH);
    deskGrad.addColorStop(0, '#422412'); deskGrad.addColorStop(1, '#1b0e06');
    ctx.fillStyle = deskGrad; ctx.fillRect(0, deskY, logicalW, 80);
    ctx.fillStyle = '#5c351b'; ctx.fillRect(0, deskY, logicalW, 4);"""
desk_new = """    let deskY = logicalH - 80;
    let deskGrad = ctx.createLinearGradient(0, deskY, 0, logicalH);
    deskGrad.addColorStop(0, '#422412'); deskGrad.addColorStop(1, '#1b0e06');
    ctx.fillStyle = deskGrad; ctx.fillRect(-100, deskY, logicalW + 200, 200); // extend down
    ctx.fillStyle = '#5c351b'; ctx.fillRect(-100, deskY, logicalW + 200, 4);"""
content = content.replace(desk_old, desk_new)

with open('assets/ocean.js', 'w') as f:
    f.write(content)

