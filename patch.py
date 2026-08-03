import re

with open('assets/ocean.js', 'r') as f:
    content = f.read()

# 1. State changes
content = content.replace('let isZenMode = false;', 'let isZenMode = false;\n  let perspective = 0; // 0=side, 1=bow, 2=stern, 3=center, 4=cabin')

# 2. Draw Ship changes
draw_ship_old = """  function drawShip() {
    if (isZenMode) {
      drawFirstPersonBow();
      return;
    }"""
draw_ship_new = """  function drawShip() {
    if (perspective === 1) { drawFirstPersonBow(); return; }
    if (perspective === 2) { drawFirstPersonStern(); return; }
    if (perspective === 3) { drawFirstPersonCenter(); return; }
    if (perspective === 4) { drawFirstPersonCabin(); return; }"""
content = content.replace(draw_ship_old, draw_ship_new)

# 3. Wave improvements
waves_old = """  function drawOceanWaves() {
    const wy = logicalH * 0.52;
    const layers = [
      { off: 0,   amp: 2.0 * sea.waveH, freq: 0.013, parallax: 0.22, fill: 'rgba(16,6,50,0.5)' },
      { off: 260, amp: 3.8 * sea.waveH, freq: 0.019, parallax: 0.42, fill: 'rgba(10,4,38,0.55)' },
      { off: 530, amp: 5.2 * sea.waveH, freq: 0.025, parallax: 0.64, fill: 'rgba(24,8,62,0.65)' },
    ];
    layers.forEach(l => {
      ctx.beginPath(); ctx.moveTo(0, wy);
      for (let x = 0; x <= logicalW; x += 2) ctx.lineTo(x, wy + wave(x, l.off, l.amp, l.freq, l.parallax));
      ctx.lineTo(logicalW, logicalH); ctx.lineTo(0, logicalH); ctx.closePath();
      ctx.fillStyle = l.fill; ctx.fill();
    });
    ctx.strokeStyle = 'rgba(130,88,240,0.15)'; ctx.lineWidth = 1;
    for (let li = 0; li < 3; li++) {
      ctx.beginPath();
      for (let x = 0; x <= logicalW; x += 4) {
        const y = wy + 4 + li * 9 + wave(x, li * 175, 3.2 * sea.waveH, 0.02 + li * 0.004, 0.4);
        x === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
      }
      ctx.stroke();
    }
  }"""
waves_new = """  function drawOceanWaves() {
    const wy = logicalH * 0.52;
    const layers = [
      { off: 0,   amp: 2.0 * sea.waveH, freq: 0.013, parallax: 0.22, fill: 'rgba(16,6,50,0.5)', crest: 'rgba(90,60,160,0.2)' },
      { off: 260, amp: 3.8 * sea.waveH, freq: 0.019, parallax: 0.42, fill: 'rgba(10,4,38,0.55)', crest: 'rgba(110,80,180,0.25)' },
      { off: 530, amp: 5.2 * sea.waveH, freq: 0.025, parallax: 0.64, fill: 'rgba(24,8,62,0.65)', crest: 'rgba(140,100,210,0.3)' },
    ];
    layers.forEach((l, idx) => {
      ctx.beginPath(); ctx.moveTo(0, wy);
      let pts = [];
      for (let x = 0; x <= logicalW; x += 4) {
        let y = wy + wave(x, l.off, l.amp, l.freq, l.parallax) + (idx * 15);
        ctx.lineTo(x, y);
        pts.push({x, y});
      }
      ctx.lineTo(logicalW, logicalH); ctx.lineTo(0, logicalH); ctx.closePath();
      let grad = ctx.createLinearGradient(0, wy, 0, logicalH);
      grad.addColorStop(0, l.fill);
      grad.addColorStop(1, 'rgba(4,2,20,0.9)');
      ctx.fillStyle = grad; ctx.fill();
      
      // crests
      ctx.beginPath();
      ctx.moveTo(pts[0].x, pts[0].y);
      pts.forEach(p => ctx.lineTo(p.x, p.y));
      ctx.strokeStyle = l.crest;
      ctx.lineWidth = 2 + idx;
      ctx.stroke();
    });
  }"""
content = content.replace(waves_old, waves_new)

# 4. Modify drawFirstPersonBow lantern and position
lantern_old = """    // Add a lantern on the closest post to the front
    let lanX = logicalW * 0.5 - 80;
    let lanY = logicalH - 180;
    
    // Lantern pole
    ctx.fillStyle = '#111';
    ctx.fillRect(lanX - 3, lanY - 60, 6, 60);
    
    // Lantern body
    ctx.fillStyle = '#222';
    ctx.fillRect(lanX - 12, lanY - 80, 24, 25);
    ctx.fillStyle = 'rgba(255, 200, 50, 0.8)';
    ctx.fillRect(lanX - 8, lanY - 76, 16, 17);
    // Glow
    let glow = ctx.createRadialGradient(lanX, lanY - 68, 0, lanX, lanY - 68, 80);
    glow.addColorStop(0, 'rgba(255, 200, 50, 0.4)');
    glow.addColorStop(1, 'rgba(255, 200, 50, 0)');
    ctx.fillStyle = glow;
    ctx.fillRect(lanX - 80, lanY - 148, 160, 160);"""
lantern_new = """    // Add a lantern that hides/shows with bobbing
    let lanX = logicalW * 0.3 - 50; 
    let lanY = logicalH + 15; // base is offscreen mostly
    
    // Lantern body 
    ctx.fillStyle = '#222';
    ctx.fillRect(lanX - 12, lanY, 24, 25);
    ctx.fillStyle = 'rgba(255, 200, 50, 0.8)';
    ctx.fillRect(lanX - 8, lanY + 4, 16, 17);
    // Glow
    let glow = ctx.createRadialGradient(lanX, lanY + 12, 0, lanX, lanY + 12, 80);
    glow.addColorStop(0, 'rgba(255, 200, 50, 0.5)');
    glow.addColorStop(1, 'rgba(255, 200, 50, 0)');
    ctx.fillStyle = glow;
    ctx.fillRect(lanX - 80, lanY - 68, 160, 160);"""
content = content.replace(lantern_old, lantern_new)

# 5. Add new perspective functions
new_views = """
  function drawFirstPersonStern() {
    ctx.save();
    ctx.translate(0, sh.bobY * 1.5);
    ctx.rotate(sh.roll * 0.2);
    ctx.fillStyle = '#1e140a';
    ctx.beginPath();
    ctx.moveTo(-50, logicalH);
    ctx.lineTo(-50, logicalH - 80);
    ctx.lineTo(logicalW * 0.7, logicalH - 120);
    ctx.lineTo(logicalW + 50, logicalH - 120);
    ctx.lineTo(logicalW + 50, logicalH);
    ctx.fill();
    let helmX = logicalW * 0.2, helmY = logicalH - 60;
    ctx.fillStyle = '#3a2311';
    ctx.fillRect(helmX - 10, helmY - 50, 20, 80);
    ctx.beginPath(); ctx.arc(helmX, helmY - 50, 45, 0, Math.PI*2); 
    ctx.strokeStyle = '#4a2e15'; ctx.lineWidth = 12; ctx.stroke();
    for(let i=0; i<8; i++) {
        let ang = i * Math.PI/4 + sh.roll;
        ctx.beginPath(); ctx.moveTo(helmX, helmY - 50);
        ctx.lineTo(helmX + Math.cos(ang)*55, helmY - 50 + Math.sin(ang)*55);
        ctx.lineWidth = 6; ctx.stroke();
    }
    ctx.fillStyle = '#22150a';
    ctx.fillRect(logicalW * 0.55, logicalH - 350, 25, 250);
    if (sh.sailAmt > 0.01) {
       ctx.fillStyle = `rgba(180,150,255,${0.8 * sh.sailAmt})`;
       ctx.beginPath();
       ctx.moveTo(logicalW * 0.45, logicalH - 300);
       ctx.quadraticCurveTo(logicalW * 0.55 + 40, logicalH - 250, logicalW * 0.65, logicalH - 300);
       ctx.lineTo(logicalW * 0.65, logicalH - 150);
       ctx.quadraticCurveTo(logicalW * 0.55 + 40, logicalH - 100, logicalW * 0.45, logicalH - 150);
       ctx.fill();
    }
    ctx.strokeStyle = '#2b1b0d'; ctx.lineWidth = 20;
    ctx.beginPath(); ctx.moveTo(-50, logicalH - 150); ctx.lineTo(helmX + 40, logicalH - 120); ctx.stroke();
    ctx.lineWidth = 10;
    for(let x = -20; x < helmX + 30; x+=60) {
        ctx.beginPath(); ctx.moveTo(x, logicalH - 150 + (x+50)*0.3); ctx.lineTo(x, logicalH); ctx.stroke();
    }
    ctx.restore();
  }

  function drawFirstPersonCenter() {
    ctx.save();
    ctx.translate(0, sh.bobY * 1.2);
    ctx.rotate(sh.roll * 0.4);
    ctx.fillStyle = '#1e140a';
    ctx.fillRect(0, logicalH - 50, logicalW, 50);
    ctx.strokeStyle = '#382515'; ctx.lineWidth = 24;
    ctx.beginPath(); ctx.moveTo(0, logicalH - 180); ctx.lineTo(logicalW, logicalH - 180); ctx.stroke();
    ctx.strokeStyle = '#2b1b0d'; ctx.lineWidth = 16;
    for (let x = 20; x < logicalW + 50; x += 180) {
        ctx.beginPath(); ctx.moveTo(x, logicalH - 180); ctx.lineTo(x, logicalH); ctx.stroke();
    }
    ctx.fillStyle = '#111';
    ctx.beginPath();
    ctx.moveTo(logicalW * 0.7 - 25, logicalH - 50);
    ctx.lineTo(logicalW * 0.7 - 15, logicalH - 150);
    ctx.lineTo(logicalW * 0.7 + 15, logicalH - 150);
    ctx.lineTo(logicalW * 0.7 + 25, logicalH - 50);
    ctx.fill();
    ctx.fillStyle = '#0a0a0a';
    ctx.beginPath(); ctx.ellipse(logicalW * 0.7, logicalH - 150, 20, 10, 0, 0, Math.PI*2); ctx.fill();
    ctx.fillStyle = '#4a3320';
    ctx.fillRect(logicalW * 0.3, logicalH - 100, 60, 80);
    ctx.strokeStyle = '#111'; ctx.lineWidth = 4;
    ctx.beginPath(); ctx.moveTo(logicalW * 0.3, logicalH - 80); ctx.lineTo(logicalW * 0.3 + 60, logicalH - 80); ctx.stroke();
    ctx.beginPath(); ctx.moveTo(logicalW * 0.3, logicalH - 40); ctx.lineTo(logicalW * 0.3 + 60, logicalH - 40); ctx.stroke();
    ctx.restore();
  }

  function drawFirstPersonCabin() {
    ctx.save();
    ctx.translate(0, sh.bobY * 0.5);
    ctx.rotate(sh.roll * 0.1);
    const frameSize = 40;
    ctx.fillStyle = '#221105';
    ctx.fillRect(0, 0, logicalW, frameSize);
    ctx.fillRect(0, logicalH - frameSize * 2, logicalW, frameSize * 2);
    ctx.fillRect(0, 0, frameSize, logicalH);
    ctx.fillRect(logicalW - frameSize, 0, frameSize, logicalH);
    ctx.fillRect(logicalW * 0.33 - 10, 0, 20, logicalH);
    ctx.fillRect(logicalW * 0.66 - 10, 0, 20, logicalH);
    ctx.fillRect(0, logicalH * 0.4 - 10, logicalW, 20);
    ctx.fillStyle = '#3a200a';
    ctx.fillRect(0, logicalH - 60, logicalW, 60);
    ctx.fillStyle = '#e8d8b8';
    ctx.beginPath();
    ctx.moveTo(logicalW * 0.4, logicalH - 50);
    ctx.lineTo(logicalW * 0.6, logicalH - 50);
    ctx.lineTo(logicalW * 0.65, logicalH - 10);
    ctx.lineTo(logicalW * 0.35, logicalH - 10);
    ctx.fill();
    ctx.fillStyle = '#111';
    ctx.fillRect(logicalW * 0.68, logicalH - 30, 15, 20);
    ctx.fillStyle = '#eee';
    ctx.fillRect(logicalW * 0.68 + 5, logicalH - 45, 2, 25);
    ctx.restore();
  }
"""

content = content.replace('function loop(timestamp)', new_views + '\n  function loop(timestamp)')

# 6. Export perspective functions
exports_old = "return { init, setZenMode };"
exports_new = """
  function cyclePerspective() { perspective = (perspective + 1) % 5; }
  function setPerspective(p) { perspective = p; }
  function getPerspective() { return perspective; }

  return { init, setZenMode, cyclePerspective, setPerspective, getPerspective };
"""
content = content.replace(exports_old, exports_new)


with open('assets/ocean.js', 'w') as f:
    f.write(content)

