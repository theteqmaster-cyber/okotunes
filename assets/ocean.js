// ═══════════════════════════════════════════════════════════════
// OCEAN JOURNEY — Pirate Ship Canvas Animation for Notak Music Hub
// States: ANCHORED → DEPARTING → SAILING → ARRIVING → ANCHORED
// ═══════════════════════════════════════════════════════════════
const OceanScene = (() => {
  const S = { ANCHORED: 0, DEPARTING: 1, SAILING: 2, ARRIVING: 3 };
  let cv, ctx, raf, t = 0, state = S.ANCHORED, tp = 0, lastTime = 0;
  let worldX = 0;
  let logicalW = 600, logicalH = 190;
  let isZenMode = false;
  let perspective = 0; // 0=side, 1=bow, 2=stern, 3=center, 4=cabin
  let sh = { bobY: 0, bobPh: 0, roll: 0, sailAmt: 0, anchorAmt: 1, spd: 0, targetSpd: 0, startStopSpd: 0 };
  let sea = { waveH: 0.4, choppy: 0, wind: 0.3 };
  let gustT = 0, gustDur = 6, waveT = 0, waveDur = 9;
  let clouds = [], birds = [], islands = [], floorItems = [], marineLife = [];
  const islandTypes = ['tropical', 'pine', 'jungle', 'barren', 'atoll', 'clan_hill', 'clan_plateu', 'clan_rain'];

  function genIslandFeats(type) {
    let f = [];
    const rnd = () => Math.random();
    if (type === 'tropical') {
      let n = 2 + Math.floor(rnd()*3);
      for(let i=0; i<n; i++) f.push({ x: (rnd()-0.5)*0.6, type: 'palm' });
    } else if (type === 'pine') {
      let n = 4 + Math.floor(rnd()*5);
      for(let i=0; i<n; i++) f.push({ x: (rnd()-0.5)*0.7, type: 'pine' });
    } else if (type === 'jungle') {
      let n = 5 + Math.floor(rnd()*5);
      for(let i=0; i<n; i++) f.push({ x: (rnd()-0.5)*0.8, type: 'jungle' });
    } else if (type === 'atoll') {
      if (rnd() > 0.3) f.push({ x: (rnd()-0.5)*0.4, type: 'palm' });
    } else if (type === 'barren') {
      let n = 3 + Math.floor(rnd()*4);
      for(let i=0; i<n; i++) f.push({ x: (rnd()-0.5)*0.7, type: 'rock' });
    } else if (type === 'clan_hill') {
      f.push({ x: 0, type: 'chief_hut', clan: 'hill' });
      f.push({ x: -0.25, type: 'hut' });
      f.push({ x: 0.25, type: 'hut' });
      f.push({ x: -0.4, type: 'canoe', flip: false });
      f.push({ x: 0.4, type: 'canoe', flip: true });
      f.push({ x: -0.15, type: 'pine' });
    } else if (type === 'clan_plateu') {
      f.push({ x: -0.1, type: 'chief_hut', clan: 'plateu' });
      f.push({ x: 0.2, type: 'hut' });
      f.push({ x: -0.3, type: 'hut' });
      f.push({ x: 0.35, type: 'palm' });
      f.push({ x: -0.4, type: 'canoe', flip: false });
    } else if (type === 'clan_rain') {
      f.push({ x: 0, type: 'chief_hut', clan: 'rain' });
      f.push({ x: -0.3, type: 'hut' });
      f.push({ x: 0.3, type: 'hut' });
      f.push({ x: -0.15, type: 'jungle' });
      f.push({ x: 0.15, type: 'jungle' });
      f.push({ x: -0.45, type: 'jungle' });
      f.push({ x: 0.45, type: 'jungle' });
      f.push({ x: -0.2, type: 'canoe', flip: false });
      f.push({ x: 0.2, type: 'canoe', flip: true });
    }
    return f.sort((a,b) => Math.abs(b.x) - Math.abs(a.x)); 
  }

  function init() {
    cv = document.getElementById('ocean-canvas');
    if (!cv) return;
    ctx = cv.getContext('2d');
    resize();
    clouds = Array.from({ length: 7 }, () => mkCloud(Math.random() * logicalW));
    islands = [
      { wx: 900,  sc: 1.0, type: 'tropical', feats: genIslandFeats('tropical') },
      { wx: 2400, sc: 1.0, type: 'clan_rain', feats: genIslandFeats('clan_rain') },
      { wx: 4200, sc: 1.1, type: 'pine', feats: genIslandFeats('pine') },
      { wx: 5800, sc: 1.2, type: 'clan_hill', feats: genIslandFeats('clan_hill') },
    ];
    sh = { bobY: 0, bobPh: 0, roll: 0, sailAmt: 0, anchorAmt: 1, spd: 0, targetSpd: 0, startStopSpd: 0 };
    state = S.ANCHORED; tp = 0; lastTime = 0;
    floorItems = []; marineLife = [];
    if (raf) cancelAnimationFrame(raf);
    loop(performance.now());

    const au = document.getElementById('audio-el');
    if (au) {
      au.addEventListener('play',  () => { if (state === S.ANCHORED || state === S.ARRIVING) { state = S.DEPARTING; tp = 0; } });
      au.addEventListener('pause', () => { if (state === S.SAILING  || state === S.DEPARTING) { state = S.ARRIVING; tp = 0; sh.startStopSpd = sh.spd; } });
      au.addEventListener('ended', () => { if (state === S.SAILING  || state === S.DEPARTING) { state = S.ARRIVING; tp = 0; sh.startStopSpd = sh.spd; } });
    }
  }

  function resize() {
    if (!cv) return;
    const p = cv.parentElement;
    logicalW = isZenMode ? window.innerWidth : (p ? p.clientWidth : 600) || 600;
    logicalH = isZenMode ? window.innerHeight : (p ? p.clientHeight : 190) || 190;
    const dpr = window.devicePixelRatio || 1;
    cv.width = logicalW * dpr;
    cv.height = logicalH * dpr;
    cv.style.width = logicalW + 'px';
    cv.style.height = logicalH + 'px';
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }

  function mkCloud(x) {
    return { x, y: 10 + Math.random() * 42, spd: 0.12 + Math.random() * 0.22,
             w: 55 + Math.random() * 75, h: 17 + Math.random() * 20,
             op: 0.32 + Math.random() * 0.38 };
  }

  function mkBird(x) {
    return { x, y: 14 + Math.random() * 54, spd: 0.9 + Math.random() * 1.7,
             ph: Math.random() * Math.PI * 2, sz: 3 + Math.random() * 3.5 };
  }

  function wave(x, off, amp, freq, parallax) {
    const phase1 = x + worldX * parallax + t * 40;
    const phase2 = x + worldX * parallax * 0.62 + t * 55;
    return Math.sin((phase1 + off) * freq) * amp
         + Math.sin((phase2 + off * 1.37) * freq * 1.73) * amp * 0.32;
  }

  function update(dtScale = 1) {
    t += 0.016 * dtScale;

    if (state === S.DEPARTING) {
      tp = Math.min(1, tp + 0.0025 * dtScale);
      sh.sailAmt   = Math.min(1, tp * 2.2);
      sh.anchorAmt = Math.max(0, 1 - tp * 1.5);
      let smoothTp = tp * tp * (3 - 2 * tp);
      sh.targetSpd = smoothTp * 3.0;
      sea.waveH = 0.4 + smoothTp * 0.8;
      if (tp >= 1) { state = S.SAILING; sea.choppy = 0.22; sea.wind = 0.5; }
    } else if (state === S.SAILING) {
      if (sea.targetWind === undefined) {
          sea.targetWind = sea.wind;
          sea.targetChoppy = sea.choppy;
          sea.targetWaveH = sea.waveH;
      }
      
      gustT += 0.016 * dtScale; waveT += 0.016 * dtScale;
      if (gustT > gustDur) { 
          gustT = 0; 
          gustDur = 8 + Math.random() * 15; 
          sea.targetWind = 0.1 + Math.random() * 0.8; 
      }
      if (waveT > waveDur) { 
          waveT = 0; 
          waveDur = 10 + Math.random() * 25; 
          let r = Math.random();
          if (r < 0.25) { // Calm tide
              sea.targetChoppy = 0.05 + Math.random() * 0.15; 
              sea.targetWaveH = 0.2 + Math.random() * 0.3;
          } else if (r < 0.8) { // Normal tide
              sea.targetChoppy = 0.2 + Math.random() * 0.4; 
              sea.targetWaveH = 0.5 + Math.random() * 1.0;
          } else { // High tide / rough sea
              sea.targetChoppy = 0.7 + Math.random() * 0.5; 
              sea.targetWaveH = 1.8 + Math.random() * 1.5;
          }
      }
      
      // Smooth interpolation
      sea.wind += (sea.targetWind - sea.wind) * 0.005 * dtScale;
      sea.choppy += (sea.targetChoppy - sea.choppy) * 0.003 * dtScale;
      sea.waveH += (sea.targetWaveH - sea.waveH) * 0.002 * dtScale;
      
      // Add slight unpredictable sways
      sea.wind += Math.sin(t * 0.4) * 0.001 * dtScale;
      sea.choppy += Math.cos(t * 0.27) * 0.001 * dtScale;
      
      // Ensure positive values
      sea.wind = Math.max(0.05, sea.wind);
      sea.choppy = Math.max(0.05, sea.choppy);
      sea.waveH = Math.max(0.2, sea.waveH);

      sh.targetSpd = 4.0 + sea.wind * 6.5; 
    } else if (state === S.ARRIVING) {
      tp = Math.min(1, tp + 0.0028 * dtScale);
      sh.sailAmt   = Math.max(0, 1 - tp * 2.2);
      sh.anchorAmt = Math.min(1, tp * 1.5);
      let smoothTp = tp * tp * (3 - 2 * tp);
      sh.targetSpd = sh.startStopSpd * (1 - smoothTp);
      sea.choppy = Math.max(0, sea.choppy - 0.005 * dtScale);
      sea.waveH  = Math.max(0.4, sea.waveH - 0.004 * dtScale);
      if (tp >= 1) { 
        state = S.ANCHORED; sh.spd = 0; sh.targetSpd = 0; 
        sh.sailAmt = 0; sh.anchorAmt = 1; sea.waveH = 0.4; sea.choppy = 0; 
      }
    } else if (state === S.ANCHORED) {
      sh.targetSpd = 0;
    }

    let accel = 0.015 * dtScale;
    if (state === S.SAILING) accel = 0.02 * dtScale;
    if (state === S.ARRIVING) accel = 0.035 * dtScale;
    
    sh.spd += (sh.targetSpd - sh.spd) * accel;
    if (sh.spd < 0.001) sh.spd = 0;

    worldX += sh.spd * 0.44 * dtScale;

    let hasWhale = false;
    marineLife.forEach(m => {
       m.x -= m.spd * dtScale;
       m.ph += 0.1 * dtScale;
       if (m.type === 'whale') {
           hasWhale = true;
           m.yOffset = Math.sin(m.ph * 0.2) * 8;
       } else if (m.type === 'dolphin') {
           m.yOffset = Math.sin(m.ph * 0.5) * 15; 
       } else {
           m.yOffset = Math.sin(m.ph) * 2;
       }
    });
    marineLife = marineLife.filter(m => m.x > -150);

    if (hasWhale && state === S.SAILING) {
      sea.choppy = Math.min(1.2, sea.choppy + 0.05 * dtScale);
      sea.waveH = Math.min(3.5, sea.waveH + 0.02 * dtScale);
    }

    const bobF = 0.33 + sea.choppy * 0.52;
    const bobA = 2.2 + sea.waveH * 6.5;
    sh.bobPh += bobF * 0.05 * dtScale;
    sh.bobY  = Math.sin(sh.bobPh) * bobA + Math.sin(sh.bobPh * 1.82 + 0.55) * bobA * 0.33;
    sh.roll  = Math.sin(sh.bobPh * 0.78) * sea.choppy * 0.072 + Math.sin(sh.bobPh * 1.43) * 0.016;

    const cSpd = 0.22 + sea.wind * 0.38;
    clouds.forEach(c => { c.x -= c.spd * cSpd * dtScale; if (c.x < -c.w - 40) { c.x = logicalW + 50; c.y = 10 + Math.random() * 42; } });

    if (Math.random() < 0.004 * dtScale && birds.length < 6 && state === S.SAILING) birds.push(mkBird(logicalW + 20));
    birds.forEach(b => { b.x -= b.spd * dtScale; b.ph += 0.1 * dtScale; });
    birds = birds.filter(b => b.x > -40);

    if (Math.random() < 0.015 * dtScale && floorItems.length < 12 && state === S.SAILING) {
      floorItems.push({
        wx: worldX * 0.44 + logicalW + 100,
        type: Math.random() < 0.7 ? 'weed' : (Math.random() < 0.5 ? 'crab' : 'turtle'),
        sz: 0.8 + Math.random()*0.6,
        ph: Math.random()*Math.PI*2
      });
    }
    floorItems = floorItems.filter(f => (f.wx - worldX * 0.44) > -100);

    if (Math.random() < 0.008 * dtScale && marineLife.length < 8 && state === S.SAILING) {
       let r = Math.random();
       let type = 'fish', num = 1;
       if (r < 0.05) { type = 'whale'; }
       else if (r < 0.20) { type = 'shark'; num = 1 + Math.floor(Math.random()*2); }
       else if (r < 0.40) { type = 'dolphin'; num = 2 + Math.floor(Math.random()*3); }
       else { type = 'fish'; num = 3 + Math.floor(Math.random()*5); }
       
       let sy = logicalH * 0.52 + 20 + Math.random()*(logicalH * 0.48 - 60);
       for(let i=0; i<num; i++) {
         marineLife.push({
           type: type,
           x: logicalW + 50 + i*18 + Math.random()*25,
           y: sy + (Math.random()-0.5)*25,
           spd: (type==='whale'? 0.6 : (type==='shark'? 1.2 : (type==='dolphin'? 2.2 : 0.8))) + Math.random()*0.5,
           ph: Math.random()*Math.PI*2,
           sz: type==='whale'? 3.0 : (type==='shark'? 1.2 : (type==='dolphin'? 1.0 : 0.5)),
           yOffset: 0
         });
       }
    }

    const last = islands[islands.length - 1];
    if (worldX * 0.26 + logicalW > last.wx - 700) {
      let tpe = islandTypes[Math.floor(Math.random() * islandTypes.length)];
      islands.push({ 
        wx: last.wx + 1200 + Math.random() * 1200, 
        sc: 0.8 + Math.random() * 0.6,
        type: tpe,
        feats: genIslandFeats(tpe)
      });
    }
  }

  function drawSky() {
    const W = logicalW, H = logicalH, wy = H * 0.52;
    const sg = ctx.createLinearGradient(0, 0, 0, wy);
    sg.addColorStop(0, '#04020f'); sg.addColorStop(0.45, '#0c051e'); sg.addColorStop(1, '#160838');
    ctx.fillStyle = sg; ctx.fillRect(0, 0, W, wy);
    const hg = ctx.createLinearGradient(0, wy - 30, 0, wy);
    hg.addColorStop(0, 'transparent'); hg.addColorStop(1, 'rgba(100,30,180,0.16)');
    ctx.fillStyle = hg; ctx.fillRect(0, wy - 30, W, 30);
    const star = [[.07,.06],[.17,.13],[.29,.04],[.44,.09],[.58,.03],[.72,.12],[.86,.06],
                  [.11,.21],[.40,.18],[.65,.20],[.80,.08],[.93,.15],[.24,.27],[.53,.25]];
    star.forEach(([sx, sy]) => {
      ctx.globalAlpha = (0.38 + 0.62 * Math.sin(t * 1.9 + sx * 9.2)) * 0.82;
      ctx.fillStyle = '#fff'; ctx.beginPath();
      ctx.arc(sx * W, sy * wy, 0.85, 0, Math.PI * 2); ctx.fill();
    });
    ctx.globalAlpha = 1;
    const mx = W * 0.84, my = wy * 0.21;
    const mg = ctx.createRadialGradient(mx, my, 0, mx, my, 28);
    mg.addColorStop(0, 'rgba(230,210,255,0.17)'); mg.addColorStop(1, 'transparent');
    ctx.fillStyle = mg; ctx.fillRect(mx - 28, my - 28, 56, 56);
    ctx.fillStyle = '#ede8ff'; ctx.beginPath(); ctx.arc(mx, my, 10.5, 0, Math.PI * 2); ctx.fill();
    ctx.fillStyle = '#0c051e';  ctx.beginPath(); ctx.arc(mx + 4, my - 1, 8.5, 0, Math.PI * 2); ctx.fill();
    for (let i = 0; i < 4; i++) {
      ctx.fillStyle = `rgba(190,165,255,${0.055 - i * 0.01})`;
      ctx.fillRect(mx - (13 - i * 3), wy + 7 + i * 8, 26 - i * 6, 3);
    }
  }

  function drawClouds() {
    clouds.forEach(c => {
      ctx.save(); ctx.globalAlpha = c.op * 0.62;
      ctx.fillStyle = 'rgba(172,148,235,1)';
      ctx.beginPath();
      ctx.ellipse(c.x,             c.y,            c.w * 0.52, c.h * 0.56, 0, 0, Math.PI * 2);
      ctx.ellipse(c.x - c.w * 0.3, c.y + c.h * 0.12, c.w * 0.33, c.h * 0.5, 0, 0, Math.PI * 2);
      ctx.ellipse(c.x + c.w * 0.3, c.y + c.h * 0.1,  c.w * 0.29, c.h * 0.46, 0, 0, Math.PI * 2);
      ctx.fill(); ctx.restore();
    });
  }

  function drawBirds() {
    birds.forEach(b => {
      const flap = Math.sin(b.ph) * b.sz * 0.65;
      ctx.strokeStyle = 'rgba(195,170,255,0.68)'; ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.moveTo(b.x - b.sz, b.y - flap);
      ctx.quadraticCurveTo(b.x, b.y, b.x + b.sz, b.y - flap);
      ctx.stroke();
    });
  }

  function drawPalm(tx, ty, sc) {
    ctx.strokeStyle = '#4a2e0e'; ctx.lineWidth = 2.5 * sc; ctx.lineCap = 'round';
    ctx.beginPath(); ctx.moveTo(tx, ty + 4*sc);
    ctx.bezierCurveTo(tx + 5*sc, ty - 10*sc, tx - 3*sc, ty - 22*sc, tx - 1, ty - 30*sc); ctx.stroke();
    ctx.strokeStyle = '#2a5a18'; ctx.lineWidth = 1.8 * sc;
    for (let i = 0; i < 5; i++) {
      const ang = -Math.PI * 0.22 + i * 0.32 + Math.sin(t * 0.7 + tx) * 0.1;
      ctx.beginPath(); ctx.moveTo(tx - 1, ty - 30*sc);
      ctx.lineTo(tx - 1 + Math.cos(ang) * 18 * sc, ty - 30 * sc + Math.sin(ang) * 10 * sc); ctx.stroke();
    }
  }

  function drawPine(tx, ty, sc) {
    ctx.fillStyle = '#113311';
    ctx.beginPath(); ctx.moveTo(tx, ty - 26*sc);
    ctx.lineTo(tx - 9*sc, ty); ctx.lineTo(tx + 9*sc, ty); ctx.fill();
    ctx.fillStyle = '#0a220a';
    ctx.beginPath(); ctx.moveTo(tx, ty - 26*sc);
    ctx.lineTo(tx, ty); ctx.lineTo(tx + 9*sc, ty); ctx.fill();
  }

  function drawJungleTree(tx, ty, sc) {
    ctx.strokeStyle = '#3a200a'; ctx.lineWidth = 3 * sc; ctx.lineCap = 'round';
    ctx.beginPath(); ctx.moveTo(tx, ty + 2*sc); ctx.lineTo(tx, ty - 14*sc); ctx.stroke();
    ctx.fillStyle = '#184a18';
    ctx.beginPath(); ctx.arc(tx, ty - 18*sc, 12*sc, 0, Math.PI*2); ctx.fill();
    ctx.fillStyle = '#113811';
    ctx.beginPath(); ctx.arc(tx - 3*sc, ty - 21*sc, 9*sc, 0, Math.PI*2); ctx.fill();
  }

  function drawHut(hx, hy, sc, isChief, clan) {
    let w = (isChief ? 28 : 18) * sc;
    let h = (isChief ? 20 : 12) * sc;
    ctx.fillStyle = '#bfa58a'; 
    ctx.fillRect(hx - w/2, hy - h, w, h);
    ctx.fillStyle = '#3a200a'; 
    ctx.beginPath(); ctx.arc(hx, hy - h/2, w/6, Math.PI, 0); ctx.lineTo(hx+w/6, hy); ctx.lineTo(hx-w/6, hy); ctx.fill();
    ctx.fillStyle = '#6b4f1f'; 
    ctx.beginPath();
    ctx.moveTo(hx - w*0.65, hy - h);
    ctx.lineTo(hx, hy - h - h*0.8);
    ctx.lineTo(hx + w*0.65, hy - h);
    ctx.closePath(); ctx.fill();
    ctx.strokeStyle = '#523a13'; ctx.lineWidth = 1;
    ctx.beginPath(); ctx.moveTo(hx, hy - h - h*0.8); ctx.lineTo(hx - w*0.3, hy - h); ctx.stroke();
    ctx.beginPath(); ctx.moveTo(hx, hy - h - h*0.8); ctx.lineTo(hx + w*0.3, hy - h); ctx.stroke();
    
    if (isChief && clan) {
      let fh = 25 * sc;
      ctx.strokeStyle = '#222'; ctx.lineWidth = 1.5;
      ctx.beginPath(); ctx.moveTo(hx, hy - h - h*0.8); ctx.lineTo(hx, hy - h - h*0.8 - fh); ctx.stroke();
      let fcol = clan === 'hill' ? '#ff4444' : (clan === 'plateu' ? '#44cc44' : '#4466ff');
      ctx.fillStyle = fcol;
      let fw = Math.sin(t*3)*5*sc; 
      ctx.beginPath();
      ctx.moveTo(hx, hy - h - h*0.8 - fh);
      ctx.lineTo(hx + 18*sc + fw, hy - h - h*0.8 - fh + 5*sc);
      ctx.lineTo(hx, hy - h - h*0.8 - fh + 12*sc);
      ctx.fill();
    }
  }

  function drawCanoe(cx, cy, sc, flip) {
    ctx.fillStyle = '#5c3a21';
    ctx.beginPath();
    let dir = flip ? -1 : 1;
    ctx.moveTo(cx - 12*sc*dir, cy - 1*sc);
    ctx.lineTo(cx + 12*sc*dir, cy - 1*sc);
    ctx.quadraticCurveTo(cx + 6*sc*dir, cy + 4*sc, cx - 10*sc*dir, cy + 3*sc);
    ctx.fill();
  }

  function drawIsland(isl) {
    const W = logicalW, H = logicalH, wy = H * 0.52;
    const sx = isl.wx - worldX * 0.26;
    if (sx < -300 || sx > W + 300) return;
    
    let iW = 110 * isl.sc;
    let iH = 46 * isl.sc;
    if (isl.type.startsWith('clan_')) { 
        iW *= 2.2; 
        if (isl.type === 'clan_hill') iH *= 1.8;
        if (isl.type === 'clan_plateu') iH *= 1.2;
        if (isl.type === 'clan_rain') iH *= 0.5;
    } 
    if (isl.type === 'atoll' || isl.type === 'barren') { iH *= 0.4; }
    if (isl.type === 'rocky') { iH *= 1.15; }

    const ig = ctx.createLinearGradient(sx, wy - iH, sx, wy + 8);
    if (isl.type === 'rocky' || isl.type === 'barren') {
      ig.addColorStop(0, '#1c1c28'); ig.addColorStop(0.75, '#12121c'); ig.addColorStop(1, '#0a0a12');
    } else {
      ig.addColorStop(0, '#183618'); ig.addColorStop(0.75, '#0c220c'); ig.addColorStop(1, '#07100a');
    }
    ctx.fillStyle = ig;
    ctx.beginPath();
    ctx.moveTo(sx - iW * 0.5, wy + 6);
    
    if (isl.type === 'rocky') {
       ctx.lineTo(sx - iW*0.25, wy - iH*0.7);
       ctx.lineTo(sx - iW*0.1, wy - iH*1.1);
       ctx.lineTo(sx + iW*0.15, wy - iH*0.8);
       ctx.lineTo(sx + iW*0.35, wy - iH*0.4);
       ctx.lineTo(sx + iW*0.5, wy + 6);
    } else if (isl.type === 'clan_hill') {
       ctx.bezierCurveTo(sx - iW*0.4, wy, sx - iW*0.2, wy - iH*1.1, sx, wy - iH*1.1);
       ctx.bezierCurveTo(sx + iW*0.2, wy - iH*1.1, sx + iW*0.4, wy, sx + iW*0.5, wy + 6);
    } else if (isl.type === 'clan_plateu') {
       ctx.lineTo(sx - iW*0.35, wy - iH*0.8);
       ctx.lineTo(sx - iW*0.2, wy - iH);
       ctx.lineTo(sx + iW*0.2, wy - iH);
       ctx.lineTo(sx + iW*0.35, wy - iH*0.8);
       ctx.lineTo(sx + iW*0.5, wy + 6);
    } else if (isl.type === 'clan_rain') {
       ctx.bezierCurveTo(sx - iW*0.4, wy, sx - iW*0.2, wy - iH, sx, wy - iH);
       ctx.bezierCurveTo(sx + iW*0.2, wy - iH, sx + iW*0.4, wy, sx + iW*0.5, wy + 6);
    } else {
       ctx.bezierCurveTo(sx - iW * 0.38, wy - iH * 0.28, sx - iW * 0.06, wy - iH, sx, wy - iH * 1.05);
       ctx.bezierCurveTo(sx + iW * 0.11,  wy - iH * 1.16, sx + iW * 0.36, wy - iH * 0.42, sx + iW * 0.5, wy + 6);
    }
    ctx.closePath(); ctx.fill();

    if (isl.type !== 'rocky') {
      ctx.fillStyle = 'rgba(155,120,55,0.32)';
      ctx.beginPath(); ctx.ellipse(sx, wy + 4, iW * 0.42, 5.5, 0, 0, Math.PI * 2); ctx.fill();
    }

    if (isl.feats) {
      isl.feats.forEach(feat => {
        let fx = sx + feat.x * iW;
        let hr = Math.cos(feat.x * Math.PI); 
        if (hr < 0) hr = 0;
        
        let fy = wy + 4 - (iH * hr * 0.95);
        if (isl.type === 'clan_hill') fy = wy + 4 - (iH * Math.pow(hr, 1.5) * 1.0);
        else if (isl.type === 'clan_plateu') {
            if (Math.abs(feat.x) < 0.2) fy = wy + 4 - iH;
            else fy = wy + 4 - (iH * (1 - (Math.abs(feat.x)-0.2)*2.5));
        }
        else if (isl.type === 'clan_rain') fy = wy + 4 - (iH * hr);
        else if (isl.type === 'rocky') fy = wy + 4 - (iH * hr * 1.1);
        
        if (feat.type === 'canoe') fy = wy + 6;
        
        let sc = isl.sc;
        if (feat.type === 'palm') drawPalm(fx, fy, sc);
        else if (feat.type === 'pine') drawPine(fx, fy, sc);
        else if (feat.type === 'jungle') drawJungleTree(fx, fy, sc);
        else if (feat.type === 'hut') drawHut(fx, fy, sc, false, feat.clan);
        else if (feat.type === 'chief_hut') drawHut(fx, fy, sc, true, feat.clan);
        else if (feat.type === 'canoe') drawCanoe(fx, fy, sc, feat.flip);
        else if (feat.type === 'rock') {
           ctx.fillStyle = '#1c1c28';
           ctx.beginPath(); ctx.ellipse(fx, fy, 6*sc, 4*sc, 0, 0, Math.PI*2); ctx.fill();
        }
      });
    }
  }

  function drawOceanUnder() {
    const W = logicalW, H = logicalH, wy = H * 0.52;
    const wg = ctx.createLinearGradient(0, wy, 0, H);
    wg.addColorStop(0, 'rgba(10,4,42,0.9)'); wg.addColorStop(1, 'rgba(3,2,16,0.95)');
    ctx.fillStyle = wg; ctx.fillRect(0, wy, W, H - wy);
  }

  function drawFloorItems() {
    const H = logicalH;
    floorItems.forEach(f => {
      let x = f.wx - worldX * 0.44;
      let y = H - 5;
      ctx.save(); ctx.translate(x, y);
      if (f.type === 'weed') {
        ctx.strokeStyle = '#2d8a4e'; ctx.lineWidth = 2 * f.sz; ctx.lineCap = 'round';
        ctx.beginPath(); ctx.moveTo(0, 0); 
        ctx.quadraticCurveTo(Math.sin(t*2 + f.ph)*10*f.sz, -15*f.sz, Math.sin(t*2 + f.ph)*5*f.sz, -30*f.sz); 
        ctx.stroke();
        ctx.beginPath(); ctx.moveTo(4, 0); 
        ctx.quadraticCurveTo(Math.sin(t*2.2 + f.ph)*8*f.sz, -10*f.sz, Math.sin(t*2.2 + f.ph)*8*f.sz, -20*f.sz); 
        ctx.stroke();
      } else if (f.type === 'crab') {
        ctx.fillStyle = '#cc4444';
        let walk = Math.sin(t*5 + f.ph)*3;
        ctx.translate(walk, -4*f.sz);
        ctx.beginPath(); ctx.ellipse(0, 0, 6*f.sz, 4*f.sz, 0, 0, Math.PI*2); ctx.fill();
        ctx.beginPath(); ctx.moveTo(-4*f.sz, 0); ctx.lineTo(-8*f.sz, -5*f.sz); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(4*f.sz, 0); ctx.lineTo(8*f.sz, -5*f.sz); ctx.stroke();
      } else if (f.type === 'turtle') {
        ctx.fillStyle = '#2d5a2d';
        let swim = Math.sin(t*1.5 + f.ph)*2;
        ctx.translate(0, -6*f.sz + swim);
        ctx.beginPath(); ctx.arc(0, 0, 8*f.sz, Math.PI, 0); ctx.fill(); 
        ctx.fillStyle = '#4a7a4a';
        ctx.beginPath(); ctx.arc(-9*f.sz, 2*f.sz, 3*f.sz, 0, Math.PI*2); ctx.fill(); 
      }
      ctx.restore();
    });
  }

  function drawMarineLife() {
    marineLife.forEach(m => {
      let x = m.x, y = m.y + m.yOffset;
      ctx.save(); ctx.translate(x, y);
      if (m.type === 'fish') {
        ctx.fillStyle = '#aaddff';
        ctx.beginPath(); ctx.ellipse(0, 0, 4*m.sz, 2*m.sz, 0, 0, Math.PI*2); ctx.fill();
        ctx.beginPath(); ctx.moveTo(4*m.sz, 0); ctx.lineTo(7*m.sz, -2*m.sz); ctx.lineTo(7*m.sz, 2*m.sz); ctx.fill();
      } else if (m.type === 'shark') {
        ctx.fillStyle = '#667788';
        ctx.beginPath(); ctx.ellipse(0, 0, 15*m.sz, 5*m.sz, 0, 0, Math.PI*2); ctx.fill();
        ctx.beginPath(); ctx.moveTo(-5*m.sz, -4*m.sz); ctx.lineTo(-2*m.sz, -10*m.sz); ctx.lineTo(2*m.sz, -4*m.sz); ctx.fill();
        let tailSwing = Math.sin(m.ph * 2) * 3 * m.sz;
        ctx.beginPath(); ctx.moveTo(12*m.sz, 0); ctx.lineTo(20*m.sz, -5*m.sz + tailSwing); ctx.lineTo(20*m.sz, 5*m.sz + tailSwing); ctx.fill();
      } else if (m.type === 'dolphin') {
        ctx.fillStyle = '#88aadd';
        let arch = Math.sin(m.ph*0.5)*0.2; 
        ctx.rotate(arch);
        ctx.beginPath(); ctx.ellipse(0, 0, 12*m.sz, 4*m.sz, 0, 0, Math.PI*2); ctx.fill();
        ctx.beginPath(); ctx.moveTo(-2*m.sz, -3*m.sz); ctx.lineTo(1*m.sz, -8*m.sz); ctx.lineTo(4*m.sz, -3*m.sz); ctx.fill(); 
        ctx.beginPath(); ctx.moveTo(10*m.sz, 0); ctx.lineTo(16*m.sz, -3*m.sz); ctx.lineTo(16*m.sz, 3*m.sz); ctx.fill(); 
      } else if (m.type === 'whale') {
        ctx.fillStyle = '#223344';
        ctx.beginPath(); ctx.ellipse(0, 0, 30*m.sz, 12*m.sz, 0, 0, Math.PI*2); ctx.fill();
        let tailSwing = Math.sin(m.ph) * 4 * m.sz;
        ctx.beginPath(); ctx.moveTo(25*m.sz, 0); ctx.lineTo(35*m.sz, -8*m.sz + tailSwing); ctx.lineTo(35*m.sz, 8*m.sz + tailSwing); ctx.fill();
        if (Math.sin(m.ph) > 0.8) {
          ctx.fillStyle = 'rgba(255,255,255,0.4)';
          ctx.beginPath(); ctx.ellipse(-15*m.sz, -15*m.sz, 2*m.sz, 8*m.sz, 0, 0, Math.PI*2); ctx.fill();
        }
      }
      ctx.restore();
    });
  }

  function drawOceanWaves() {
    const W = logicalW, H = logicalH, wy = H * 0.52;
    const layers = [
      { off: 0,   amp: 2.0 * sea.waveH, freq: 0.013, parallax: 0.22, fill: 'rgba(16,6,50,0.5)' },
      { off: 260, amp: 3.8 * sea.waveH, freq: 0.019, parallax: 0.42, fill: 'rgba(10,4,38,0.55)' },
      { off: 530, amp: 5.2 * sea.waveH, freq: 0.025, parallax: 0.64, fill: 'rgba(24,8,62,0.65)' },
    ];
    layers.forEach(l => {
      ctx.beginPath(); ctx.moveTo(0, wy);
      for (let x = 0; x <= W; x += 2) ctx.lineTo(x, wy + wave(x, l.off, l.amp, l.freq, l.parallax));
      ctx.lineTo(W, H); ctx.lineTo(0, H); ctx.closePath();
      ctx.fillStyle = l.fill; ctx.fill();
    });
    ctx.strokeStyle = 'rgba(130,88,240,0.15)'; ctx.lineWidth = 1;
    for (let li = 0; li < 3; li++) {
      ctx.beginPath();
      for (let x = 0; x <= W; x += 4) {
        const y = wy + 4 + li * 9 + wave(x, li * 175, 3.2 * sea.waveH, 0.02 + li * 0.004, 0.4);
        x === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
      }
      ctx.stroke();
    }
  }

  function drawShip() {
    if (perspective === 1) { drawFirstPersonBow(); return; }
    if (perspective === 2) { drawFirstPersonStern(); return; }
    if (perspective === 3) { drawFirstPersonCenter(); return; }
    if (perspective === 4) { drawFirstPersonCabin(); return; }
    const W = logicalW, H = logicalH, wy = H * 0.52;
    const sx = W * 0.32, sy = wy + sh.bobY;
    ctx.save();
    ctx.translate(sx, sy);
    ctx.rotate(sh.roll);

    if (sh.anchorAmt > 0.04) {
      const adrop = sh.anchorAmt * 26;
      const aOp = sh.anchorAmt * 0.72;
      ctx.strokeStyle = `rgba(155,130,210,${aOp})`; ctx.lineWidth = 1.4;
      ctx.setLineDash([3, 3]);
      ctx.beginPath(); ctx.moveTo(0, 9); ctx.lineTo(0, 9 + adrop); ctx.stroke();
      ctx.setLineDash([]);
      const ay = 9 + adrop;
      ctx.strokeStyle = `rgba(175,145,225,${aOp})`; ctx.lineWidth = 1.5;
      ctx.beginPath(); ctx.arc(0, ay, 4.5, 0, Math.PI * 2); ctx.stroke();
      ctx.beginPath(); ctx.moveTo(0, ay + 4.5); ctx.lineTo(0, ay + 11); ctx.stroke();
      ctx.beginPath(); ctx.moveTo(-5, ay + 10); ctx.lineTo(5, ay + 10); ctx.stroke();
      ctx.beginPath(); ctx.moveTo(-5, ay + 10); ctx.quadraticCurveTo(-8, ay + 14, -5.5, ay + 17); ctx.stroke();
      ctx.beginPath(); ctx.moveTo(5,  ay + 10); ctx.quadraticCurveTo(8,  ay + 14, 5.5,  ay + 17); ctx.stroke();
    }

    ctx.strokeStyle = '#634320'; ctx.lineWidth = 2; ctx.lineCap = 'round';
    ctx.beginPath(); ctx.moveTo(-18, -12); ctx.lineTo(-18, -55); ctx.stroke(); 
    ctx.lineWidth = 1.5; ctx.beginPath(); ctx.moveTo(-32, -42); ctx.lineTo(-4, -42); ctx.stroke(); 
    ctx.strokeStyle = '#7a5828'; ctx.lineWidth = 2.4;
    ctx.beginPath(); ctx.moveTo(6, -10); ctx.lineTo(6, -75); ctx.stroke(); 
    ctx.lineWidth = 2; ctx.beginPath(); ctx.moveTo(-14, -58); ctx.lineTo(26, -58); ctx.stroke(); 
    ctx.lineWidth = 2;
    ctx.beginPath(); ctx.moveTo(35, -12); ctx.lineTo(55, -22); ctx.stroke(); 
    ctx.strokeStyle = 'rgba(255,255,255,0.15)'; ctx.lineWidth = 0.5;
    ctx.beginPath(); ctx.moveTo(6, -75); ctx.lineTo(55, -22); ctx.stroke();
    ctx.beginPath(); ctx.moveTo(6, -75); ctx.lineTo(-18, -55); ctx.stroke();
    ctx.beginPath(); ctx.moveTo(-18, -55); ctx.lineTo(-40, -18); ctx.stroke();

    if (sh.sailAmt > 0.01) {
      const sailH_main = sh.sailAmt * 38;
      const sailH_miz = sh.sailAmt * 24;
      const billow = Math.sin(t * (1.4 + sea.wind)) * (3.5 + sea.wind * 6.5) * sh.sailAmt;
      const sailG = ctx.createLinearGradient(0, -60, 0, -20);
      sailG.addColorStop(0, `rgba(180,150,255,${0.85 * sh.sailAmt})`);
      sailG.addColorStop(1, `rgba(100,60,190,${0.65 * sh.sailAmt})`);
      ctx.fillStyle = sailG;
      ctx.strokeStyle = `rgba(120,80,200,${0.4 * sh.sailAmt})`;
      ctx.lineWidth = 0.5;

      ctx.beginPath();
      ctx.moveTo(-30, -42);
      ctx.quadraticCurveTo(-30 + billow*0.4, -42 + sailH_miz*0.5, -26, -42 + sailH_miz);
      ctx.lineTo(-10, -42 + sailH_miz);
      ctx.quadraticCurveTo(-6 + billow*0.4, -42 + sailH_miz*0.5, -6, -42);
      ctx.fill(); ctx.stroke();

      ctx.beginPath();
      ctx.moveTo(-12, -58);
      ctx.quadraticCurveTo(-12 + billow*0.5, -58 + sailH_main*0.5, -8, -58 + sailH_main);
      ctx.lineTo(20, -58 + sailH_main);
      ctx.quadraticCurveTo(24 + billow*0.8, -58 + sailH_main*0.5, 24, -58);
      ctx.fill(); ctx.stroke();
      
      ctx.beginPath();
      ctx.moveTo(8, -55);
      ctx.quadraticCurveTo(25 + billow*0.4, -35, 48, -18);
      ctx.lineTo(28, -8);
      ctx.quadraticCurveTo(18 + billow*0.2, -30, 8, -55);
      ctx.fill();
    }
    
    if (sh.sailAmt < 0.99) {
      ctx.fillStyle = `rgba(90,60,145,${(1 - sh.sailAmt) * 0.5})`;
      ctx.beginPath(); ctx.rect(-30, -44, 24, 4); ctx.fill();
      ctx.beginPath(); ctx.rect(-12, -60, 36, 5); ctx.fill();
    }

    const flagWave = Math.sin(t * 2.8 + sh.roll * 4) * 4 * (0.15 + sea.wind * 0.85);
    ctx.fillStyle = 'rgba(200,30,50,0.85)';
    ctx.beginPath();
    ctx.moveTo(6, -74);
    ctx.lineTo(19, -70 + flagWave * 0.5);
    ctx.lineTo(6, -66 + flagWave);
    ctx.closePath(); ctx.fill();
    ctx.fillStyle = 'rgba(255,255,255,0.8)';
    ctx.beginPath(); ctx.arc(10, -70 + flagWave * 0.38, 1.5, 0, Math.PI * 2); ctx.fill();

    const hg = ctx.createLinearGradient(-40, -18, -40, 16);
    hg.addColorStop(0, '#2b1b47'); hg.addColorStop(1, '#130a24');
    ctx.fillStyle = hg;
    ctx.beginPath();
    ctx.moveTo(-40, -18); 
    ctx.lineTo(-38, 8); 
    ctx.quadraticCurveTo(-20, 16, 5, 16); 
    ctx.quadraticCurveTo(28, 16, 40, -2);
    ctx.lineTo(44, -10); 
    ctx.quadraticCurveTo(20, -6, -20, -10); 
    ctx.lineTo(-40, -18);
    ctx.closePath(); ctx.fill();
    ctx.strokeStyle = 'rgba(90,50,150,0.5)'; ctx.lineWidth = 1; ctx.stroke();

    ctx.strokeStyle = 'rgba(150,100,240,0.15)'; ctx.lineWidth = 0.5;
    ctx.beginPath(); ctx.moveTo(-38, -6); ctx.quadraticCurveTo(-10, -2, 38, -6); ctx.stroke();
    ctx.beginPath(); ctx.moveTo(-38, 2); ctx.quadraticCurveTo(-10, 6, 32, 2); ctx.stroke();

    ctx.fillStyle = 'rgba(240,200,100,0.6)';
    for (let i = 0; i < 3; i++) {
      ctx.fillRect(-37, -15 + i*4.5, 3.5, 3);
    }
    
    ctx.strokeStyle = 'rgba(200,150,50,0.6)'; ctx.lineWidth = 1.5;
    ctx.beginPath(); ctx.moveTo(-40, -18); ctx.lineTo(-20, -10); ctx.stroke();

    for (let i = 0; i < 4; i++) {
      let cx = -20 + i * 14;
      let cy = 0;
      ctx.fillStyle = 'rgba(0,0,0,0.7)';
      ctx.fillRect(cx - 3, cy - 3, 6, 6); 
      ctx.fillStyle = '#333';
      ctx.fillRect(cx - 1, cy - 1, 7, 2); 
    }
    
    ctx.fillStyle = 'rgba(200,150,50,0.8)';
    ctx.beginPath(); ctx.arc(43, -9, 2, 0, Math.PI*2); ctx.fill();

    ctx.restore();
  }

  function drawFirstPersonBow() {
    const W = logicalW, H = logicalH;
    ctx.save();
    ctx.translate(0, sh.bobY * 1.5);
    ctx.rotate(sh.roll * 0.2);

    let deckGrad = ctx.createLinearGradient(0, H - 150, 0, H);
    deckGrad.addColorStop(0, '#1c1006');
    deckGrad.addColorStop(1, '#3a2311');
    ctx.fillStyle = deckGrad;
    ctx.beginPath();
    ctx.moveTo(-20, H + 200); ctx.lineTo(-20, H - 60); ctx.lineTo(W * 0.5, H - 60);
    ctx.quadraticCurveTo(W * 0.9, H - 60, W + 20, H - 250);
    ctx.lineTo(W + 20, H + 200); ctx.fill();

    ctx.strokeStyle = '#110802'; ctx.lineWidth = 3;
    for(let i=0; i<8; i++) {
        let xBottom = -20 + (W*0.6 + 20) * (i/8);
        let xTop = -20 + (W*0.5 + 20) * (i/8);
        ctx.beginPath(); ctx.moveTo(xBottom, H + 200); ctx.lineTo(xTop, H - 60); ctx.stroke();
        ctx.fillStyle = '#0a0a0a';
        ctx.beginPath(); ctx.arc(xBottom + 5, H - 10, 2, 0, Math.PI*2); ctx.fill();
    }

    ctx.strokeStyle = '#382515'; ctx.lineWidth = 24; ctx.lineCap = 'round';
    ctx.beginPath(); ctx.moveTo(-20, H - 180); ctx.lineTo(W * 0.5, H - 180);
    ctx.quadraticCurveTo(W * 0.95, H - 180, W + 40, H - 380); ctx.stroke();
    
    ctx.strokeStyle = '#5c351b'; ctx.lineWidth = 6;
    ctx.beginPath(); ctx.moveTo(-20, H - 188); ctx.lineTo(W * 0.5, H - 188);
    ctx.quadraticCurveTo(W * 0.93, H - 188, W + 36, H - 382); ctx.stroke();

    ctx.strokeStyle = '#2b1b0d'; ctx.lineWidth = 16;
    for (let x = 40; x < W * 0.7; x += 160) {
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





    ctx.restore();
  }
  function drawFirstPersonStern() {
    ctx.save();
    ctx.translate(0, sh.bobY * 1.5);
    ctx.rotate(sh.roll * 0.2);

    let deckGrad = ctx.createLinearGradient(0, logicalH - 160, 0, logicalH);
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
    }

    let mastGrad = ctx.createLinearGradient(logicalW * 0.55, 0, logicalW * 0.55 + 30, 0);
    mastGrad.addColorStop(0, '#1c1006'); mastGrad.addColorStop(0.5, '#4a2a14'); mastGrad.addColorStop(1, '#110802');
    ctx.fillStyle = mastGrad; ctx.fillRect(logicalW * 0.55, logicalH - 400, 30, 300);
    ctx.fillStyle = '#221105'; ctx.fillRect(logicalW * 0.53, logicalH - 400, 34, 15);

    if (sh.sailAmt > 0.01) {
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
       

       
       ctx.strokeStyle = '#221105'; ctx.lineWidth = 8; ctx.lineCap = 'round';
       ctx.beginPath(); ctx.moveTo(logicalW * 0.32, logicalH - 325); ctx.lineTo(logicalW * 0.78, logicalH - 325); ctx.stroke();
    }
    
    ctx.strokeStyle = 'rgba(0,0,0,0.4)'; ctx.lineWidth = 3;
    ctx.beginPath(); ctx.moveTo(logicalW * 0.55, logicalH - 350); ctx.lineTo(logicalW * 0.2, logicalH - 100); ctx.stroke();
    ctx.beginPath(); ctx.moveTo(logicalW * 0.58, logicalH - 350); ctx.lineTo(logicalW * 0.8, logicalH - 120); ctx.stroke();

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
  function drawFirstPersonCenter() {
    ctx.save();
    ctx.translate(0, sh.bobY * 1.2);
    ctx.rotate(sh.roll * 0.3);

    let deckY = logicalH - 70;
    let deckGrad = ctx.createLinearGradient(0, deckY, 0, logicalH);
    deckGrad.addColorStop(0, '#221309'); deckGrad.addColorStop(1, '#3a2311');
    ctx.fillStyle = deckGrad; ctx.fillRect(-50, deckY, logicalW + 100, 200);
    
    ctx.strokeStyle = '#110802'; ctx.lineWidth = 2;
    for(let y = deckY + 10; y < logicalH + 150; y += 15) {
        ctx.beginPath(); ctx.moveTo(-50, y); ctx.lineTo(logicalW + 100, y); ctx.stroke();
    }

    ctx.fillStyle = '#110802'; ctx.fillRect(-50, deckY - 15, logicalW + 100, 15);
    
    ctx.fillStyle = '#1a0f05';
    for (let x = -130; x < logicalW + 200; x += 160) {
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
    ctx.fillStyle = railGrad; ctx.fillRect(-50, deckY - 160, logicalW + 100, 30);

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

    function drawBarrel(bx, by) {
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
    drawBarrel(logicalW * 0.28, deckY - 50);
    drawBarrel(logicalW * 0.45, deckY - 45);
    ctx.restore();
  }
  function drawFirstPersonCabin() {
    ctx.save();
    ctx.translate(0, sh.bobY * 0.5);
    ctx.rotate(sh.roll * 0.1);
    
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

    let woodGrad = ctx.createLinearGradient(0, 0, logicalW, logicalH);
    woodGrad.addColorStop(0, '#311a0c'); woodGrad.addColorStop(0.5, '#221105'); woodGrad.addColorStop(1, '#110802');
    
    const f = 50;
    ctx.fillStyle = woodGrad;
    ctx.fillRect(-100, -100, logicalW + 200, f + 100); // top extending up
    ctx.fillRect(-100, logicalH - f * 2.5, logicalW + 200, f * 2.5 + 200); // bottom extending down
    ctx.fillRect(-100, -100, f + 100, logicalH + 200); // left extending left
    ctx.fillRect(logicalW - f, -100, f + 100, logicalH + 200); // right extending right
    
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
    ctx.fillStyle = deskGrad; ctx.fillRect(-100, deskY, logicalW + 200, 200); // extend down
    ctx.fillStyle = '#5c351b'; ctx.fillRect(-100, deskY, logicalW + 200, 4);
    
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
  function loop(timestamp) {
    if (!cv) return;
    if (!lastTime) lastTime = timestamp || performance.now();
    let dt = (timestamp || performance.now()) - lastTime;
    if (dt > 100) dt = 100; 
    lastTime = timestamp || performance.now();
    let dtScale = dt / 16.666;

    update(dtScale);
    ctx.clearRect(0, 0, logicalW, logicalH);
    drawSky();
    drawClouds();
    islands.forEach(drawIsland);
    drawOceanUnder();
    drawFloorItems();
    drawMarineLife();
    drawOceanWaves();
    drawBirds();
    drawShip();
    raf = requestAnimationFrame(loop);
  }

  window.addEventListener('resize', resize);

  function setZenMode(val) {
    isZenMode = val;
  }

  
  function cyclePerspective() { perspective = (perspective + 1) % 5; }
  function setPerspective(p) { perspective = p; }
  function getPerspective() { return perspective; }

  return { init, setZenMode, cyclePerspective, setPerspective, getPerspective };

})();
