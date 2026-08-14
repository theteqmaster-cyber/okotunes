/**
 * OkoTunes — Modern Single Page Audio Engine & Interactive UI
 * Built for Mphathisi Ndlovu (The Teq-master) Digital Ecosystem
 */

document.addEventListener('DOMContentLoaded', () => {
  // Track Library Data
  const TRACKS = [
    {
      id: 'voice',
      title: 'Voice',
      artist: 'Samira',
      album: 'Synth-Pop Nights',
      art: 'assets/track_voice.jpg',
      genre: 'Synth-Pop',
      listeners: '70K',
      bpm: 118,
      key: 'D Minor',
      notes: [293.66, 349.23, 440.0, 523.25, 440.0, 392.0]
    },
    {
      id: 'crazy_frog',
      title: 'Crazy Frog (Neon Chronicles)',
      artist: 'Jamie Chastain',
      album: 'Neon Chronicles',
      art: 'assets/track_crazy_frog.jpg',
      genre: 'Cyber-Wave',
      listeners: '20K',
      bpm: 128,
      key: 'F# Minor',
      notes: [370.0, 440.0, 554.37, 440.0, 370.0, 329.63]
    },
    {
      id: 'rise_top',
      title: 'Rise 2 Top',
      artist: 'Alex',
      album: 'Live at the Arena',
      art: 'assets/track_rise_top.jpg',
      genre: 'Rock & Pop',
      listeners: '40K',
      bpm: 135,
      key: 'E Minor',
      notes: [329.63, 392.0, 493.88, 587.33, 493.88, 392.0]
    },
    {
      id: 'hiphop',
      title: 'Bulawayo Flow',
      artist: 'Teqmaster & The MCs',
      album: 'NUST Sessions',
      art: 'assets/genre_hiphop.jpg',
      genre: 'Hip Hop',
      listeners: '35K',
      bpm: 92,
      key: 'C Minor',
      notes: [261.63, 311.13, 392.0, 466.16, 392.0, 311.13]
    },
    {
      id: 'lofi',
      title: 'Chill Study Vibes',
      artist: 'StudyBreak Sound Lab',
      album: 'Late Night Library Vol. 1',
      art: 'assets/genre_lofi.jpg',
      genre: 'Lo-Fi',
      listeners: '55K',
      bpm: 80,
      key: 'A Major',
      notes: [220.0, 277.18, 329.63, 415.3, 329.63, 277.18]
    },
    {
      id: 'rock',
      title: 'The Electric Fury',
      artist: 'Alex & The Frequencies',
      album: 'Live in Concert',
      art: 'assets/genre_rock.jpg',
      genre: 'Rock & Roll',
      listeners: '18K',
      bpm: 140,
      key: 'A Minor',
      notes: [220.0, 261.63, 329.63, 392.0, 329.63, 261.63]
    },
    {
      id: 'rnb',
      title: 'Neo-Soul Rooftop',
      artist: 'The Vinyl Room Collective',
      album: 'Velvet Nights',
      art: 'assets/genre_rnb.jpg',
      genre: 'R & B',
      listeners: '48K',
      bpm: 88,
      key: 'Eb Major',
      notes: [311.13, 392.0, 466.16, 587.33, 466.16, 392.0]
    },
    {
      id: 'afrobeat',
      title: 'Sunset Fest Amapiano',
      artist: 'Oko Afrobeat Crew',
      album: 'Summer Fest 2026',
      art: 'assets/genre_afrobeats.jpg',
      genre: 'Afrobeats',
      listeners: '62K',
      bpm: 113,
      key: 'G Major',
      notes: [196.0, 246.94, 293.66, 369.99, 293.66, 246.94]
    }
  ];

  // State
  let currentTrackIndex = 0;
  let isPlaying = false;
  let currentTimeSec = 0;
  let trackDurationSec = 195; // ~3m15s
  let playbackTimer = null;
  let synthInterval = null;

  // Audio Context & DSP Engine
  let audioCtx = null;
  let masterGain = null;
  let bassFilter = null;
  let pannerNode = null;
  let analyserNode = null;
  let isSpatialEnabled = true;
  let isBassBoostEnabled = true;
  let isVinylHissEnabled = false;

  // DOM Elements
  const playerDock = document.getElementById('playerDock');
  const dockPlayBtn = document.getElementById('dockPlayBtn');
  const dockPrevBtn = document.getElementById('dockPrevBtn');
  const dockNextBtn = document.getElementById('dockNextBtn');
  const dockThumb = document.getElementById('dockThumb');
  const dockTitle = document.getElementById('dockTitle');
  const dockArtist = document.getElementById('dockArtist');
  const scrubTrack = document.getElementById('scrubTrack');
  const scrubProgress = document.getElementById('scrubProgress');
  const timeCurrent = document.getElementById('timeCurrent');
  const timeTotal = document.getElementById('timeTotal');
  const volumeSlider = document.getElementById('volumeSlider');
  const visualizerCanvas = document.getElementById('visualizerCanvas');

  // Hero & Card Action triggers
  const btnStartListening = document.getElementById('btnStartListening');
  const btnExploreEco = document.getElementById('btnExploreEco');
  const btnNavEco = document.getElementById('btnNavEco');
  const btnNavLaunch = document.getElementById('btnNavLaunch');
  const ecoModal = document.getElementById('ecoModal');
  const signupModal = document.getElementById('signupModal');
  const closeModals = document.querySelectorAll('.modal-close-btn');

  // Studio Toggles
  const toggleSpatial = document.getElementById('toggleSpatial');
  const toggleBass = document.getElementById('toggleBass');
  const toggleVinyl = document.getElementById('toggleVinyl');
  const toggleArp = document.getElementById('toggleArp');

  // -------------------------------------------------------------
  // Web Audio Synthesizer & Beat Generator
  // -------------------------------------------------------------
  function initAudioEngine() {
    if (audioCtx) return;
    try {
      const AudioContext = window.AudioContext || window.webkitAudioContext;
      audioCtx = new AudioContext();

      // Master Gain
      masterGain = audioCtx.createGain();
      masterGain.gain.setValueAtTime(0.7, audioCtx.currentTime);

      // Bass Boost Low-Shelf Filter
      bassFilter = audioCtx.createBiquadFilter();
      bassFilter.type = 'lowshelf';
      bassFilter.frequency.setValueAtTime(120, audioCtx.currentTime);
      bassFilter.gain.setValueAtTime(isBassBoostEnabled ? 6 : 0, audioCtx.currentTime);

      // Spatial 3D Panner
      if (audioCtx.createStereoPanner) {
        pannerNode = audioCtx.createStereoPanner();
        pannerNode.pan.setValueAtTime(0, audioCtx.currentTime);
      }

      // Analyser Node for Visualizer
      analyserNode = audioCtx.createAnalyser();
      analyserNode.fftSize = 128;

      // Connect DSP Chain
      if (pannerNode) {
        masterGain.connect(bassFilter);
        bassFilter.connect(pannerNode);
        pannerNode.connect(analyserNode);
      } else {
        masterGain.connect(bassFilter);
        bassFilter.connect(analyserNode);
      }
      analyserNode.connect(audioCtx.destination);

      startVisualizerCanvas();
    } catch (e) {
      console.warn('Web Audio API not supported or blocked:', e);
    }
  }

  // Play procedural musical notes for active track
  function playSynthChord() {
    if (!audioCtx || !isPlaying) return;
    if (audioCtx.state === 'suspended') {
      audioCtx.resume();
    }

    const currentTrack = TRACKS[currentTrackIndex];
    const notes = currentTrack.notes || [261.63, 329.63, 392.0, 523.25];
    const now = audioCtx.currentTime;

    // Spatial Pan movement
    if (pannerNode && isSpatialEnabled) {
      const panTarget = Math.sin(now * 0.8) * 0.6;
      pannerNode.pan.setTargetAtTime(panTarget, now, 0.2);
    }

    // Play chord tone
    const noteFreq = notes[Math.floor(Math.random() * notes.length)];
    const osc = audioCtx.createOscillator();
    const oscGain = audioCtx.createGain();

    osc.type = Math.random() > 0.5 ? 'sine' : 'triangle';
    osc.frequency.setValueAtTime(noteFreq, now);

    // Warm envelope
    oscGain.gain.setValueAtTime(0.001, now);
    oscGain.gain.exponentialRampToValueAtTime(0.18, now + 0.1);
    oscGain.gain.exponentialRampToValueAtTime(0.001, now + 1.8);

    osc.connect(oscGain);
    oscGain.connect(masterGain);

    osc.start(now);
    osc.stop(now + 1.9);

    // Sub-bass thump
    if (Math.random() > 0.4) {
      const bassOsc = audioCtx.createOscillator();
      const bassGain = audioCtx.createGain();
      bassOsc.type = 'sine';
      bassOsc.frequency.setValueAtTime(noteFreq / 4, now);
      bassGain.gain.setValueAtTime(0.25, now);
      bassGain.gain.exponentialRampToValueAtTime(0.001, now + 0.6);

      bassOsc.connect(bassGain);
      bassGain.connect(masterGain);
      bassOsc.start(now);
      bassOsc.stop(now + 0.7);
    }
  }

  // -------------------------------------------------------------
  // Real-time Canvas Waveform / Visualizer
  // -------------------------------------------------------------
  function startVisualizerCanvas() {
    if (!visualizerCanvas) return;
    const ctx = visualizerCanvas.getContext('2d');
    const width = (visualizerCanvas.width = visualizerCanvas.parentElement.clientWidth || 800);
    const height = (visualizerCanvas.height = visualizerCanvas.parentElement.clientHeight || 160);

    const bufferLength = analyserNode ? analyserNode.frequencyBinCount : 32;
    const dataArray = new Uint8Array(bufferLength);

    function draw() {
      requestAnimationFrame(draw);

      ctx.clearRect(0, 0, width, height);

      // Background gradient
      const bgGrad = ctx.createLinearGradient(0, 0, 0, height);
      bgGrad.addColorStop(0, 'rgba(8, 11, 18, 0.95)');
      bgGrad.addColorStop(1, 'rgba(5, 7, 12, 0.95)');
      ctx.fillStyle = bgGrad;
      ctx.fillRect(0, 0, width, height);

      if (analyserNode && isPlaying) {
        analyserNode.getByteFrequencyData(dataArray);
      } else {
        for (let i = 0; i < dataArray.length; i++) {
          dataArray[i] = Math.sin(Date.now() * 0.002 + i * 0.2) * 15 + 20;
        }
      }

      // Draw Equalizer Bars
      const barWidth = (width / bufferLength) * 2.2;
      let x = 0;

      for (let i = 0; i < bufferLength; i++) {
        const barHeight = (dataArray[i] / 255) * (height * 0.78);

        const grad = ctx.createLinearGradient(0, height, 0, height - barHeight);
        grad.addColorStop(0, '#7928CA');
        grad.addColorStop(0.6, '#FF007A');
        grad.addColorStop(1, '#00F2FE');

        ctx.fillStyle = grad;
        ctx.beginPath();
        ctx.roundRect(x, height - barHeight - 8, barWidth - 4, barHeight + 8, [4, 4, 0, 0]);
        ctx.fill();

        x += barWidth;
      }

      // Draw Center Oscilloscope Wave Line
      ctx.lineWidth = 2;
      ctx.strokeStyle = 'rgba(255, 0, 122, 0.4)';
      ctx.beginPath();
      const sliceWidth = width / bufferLength;
      let waveX = 0;

      for (let i = 0; i < bufferLength; i++) {
        const v = dataArray[i] / 128.0;
        const waveY = (v * height) / 2.8;

        if (i === 0) {
          ctx.moveTo(waveX, waveY);
        } else {
          ctx.lineTo(waveX, waveY);
        }
        waveX += sliceWidth;
      }
      ctx.stroke();
    }

    draw();
  }

  // -------------------------------------------------------------
  // Player Controls & UI Sync
  // -------------------------------------------------------------
  function loadTrack(index, autoplay = true) {
    if (index < 0) index = TRACKS.length - 1;
    if (index >= TRACKS.length) index = 0;
    currentTrackIndex = index;
    const track = TRACKS[currentTrackIndex];

    // Update Dock
    dockTitle.textContent = track.title;
    dockArtist.textContent = `${track.artist} • ${track.album}`;
    dockThumb.src = track.art;
    dockThumb.alt = track.title;
    currentTimeSec = 0;
    updateScrubUI();

    // Highlight active cards
    document.querySelectorAll('.fire-card').forEach((card) => {
      if (card.dataset.trackId === track.id) {
        card.classList.add('is-playing');
      } else {
        card.classList.remove('is-playing');
      }
    });

    if (autoplay) {
      playTrack();
    }
  }

  function playTrack() {
    initAudioEngine();
    isPlaying = true;
    playerDock.classList.add('is-active');
    dockPlayBtn.innerHTML = `
      <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
        <rect x="6" y="4" width="4" height="16" rx="1.5" />
        <rect x="14" y="4" width="4" height="16" rx="1.5" />
      </svg>
    `;

    // Start playback timer
    clearInterval(playbackTimer);
    playbackTimer = setInterval(() => {
      currentTimeSec++;
      if (currentTimeSec >= trackDurationSec) {
        nextTrack();
      }
      updateScrubUI();
    }, 1000);

    // Start Synth audio generator
    clearInterval(synthInterval);
    playSynthChord();
    const currentTrack = TRACKS[currentTrackIndex];
    const intervalMs = Math.max(300, Math.floor((60 / currentTrack.bpm) * 1000));
    synthInterval = setInterval(playSynthChord, intervalMs);
  }

  function pauseTrack() {
    isPlaying = false;
    playerDock.classList.remove('is-active');
    dockPlayBtn.innerHTML = `
      <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
        <polygon points="6,4 20,12 6,20" />
      </svg>
    `;
    clearInterval(playbackTimer);
    clearInterval(synthInterval);
  }

  function togglePlayPause() {
    if (isPlaying) {
      pauseTrack();
    } else {
      playTrack();
    }
  }

  function nextTrack() {
    loadTrack(currentTrackIndex + 1, isPlaying);
  }

  function prevTrack() {
    loadTrack(currentTrackIndex - 1, isPlaying);
  }

  function formatTime(sec) {
    const m = Math.floor(sec / 60);
    const s = Math.floor(sec % 60);
    return `${m}:${s < 10 ? '0' : ''}${s}`;
  }

  function updateScrubUI() {
    const pct = Math.min(100, (currentTimeSec / trackDurationSec) * 100);
    scrubProgress.style.width = `${pct}%`;
    timeCurrent.textContent = formatTime(currentTimeSec);
    timeTotal.textContent = formatTime(trackDurationSec);
  }

  // Scrub bar click/drag
  if (scrubTrack) {
    scrubTrack.addEventListener('click', (e) => {
      const rect = scrubTrack.getBoundingClientRect();
      const pos = (e.clientX - rect.left) / rect.width;
      currentTimeSec = Math.floor(pos * trackDurationSec);
      updateScrubUI();
      if (!isPlaying) playTrack();
    });
  }

  // Volume Slider
  if (volumeSlider) {
    volumeSlider.addEventListener('input', (e) => {
      const val = parseFloat(e.target.value);
      if (masterGain && audioCtx) {
        masterGain.gain.setValueAtTime(val, audioCtx.currentTime);
      }
    });
  }

  // Dock Buttons
  dockPlayBtn.addEventListener('click', togglePlayPause);
  dockNextBtn.addEventListener('click', nextTrack);
  dockPrevBtn.addEventListener('click', prevTrack);

  // Hero CTA
  btnStartListening.addEventListener('click', () => {
    loadTrack(0, true);
    playerDock.scrollIntoView({ behavior: 'smooth', block: 'end' });
  });

  if (btnNavLaunch) {
    btnNavLaunch.addEventListener('click', () => {
      loadTrack(0, true);
    });
  }

  // Track Cards Play Click
  document.querySelectorAll('.fire-card').forEach((card) => {
    card.addEventListener('click', () => {
      const trackId = card.dataset.trackId;
      const idx = TRACKS.findIndex((t) => t.id === trackId);
      if (idx !== -1) {
        if (currentTrackIndex === idx && isPlaying) {
          pauseTrack();
        } else {
          loadTrack(idx, true);
        }
      }
    });
  });

  // Genre Cards Play Click
  document.querySelectorAll('.genre-card').forEach((card) => {
    card.addEventListener('click', () => {
      const genre = card.dataset.genre;
      const idx = TRACKS.findIndex((t) => t.genre.toLowerCase() === genre.toLowerCase());
      if (idx !== -1) {
        loadTrack(idx, true);
      } else {
        loadTrack(0, true);
      }
    });
  });

  // Carousel Next/Prev Arrows
  const btnPrevCarousel = document.getElementById('btnPrevCarousel');
  const btnNextCarousel = document.getElementById('btnNextCarousel');
  const genresGrid = document.getElementById('genresGrid');

  if (btnPrevCarousel && genresGrid) {
    btnPrevCarousel.addEventListener('click', () => {
      genresGrid.scrollBy({ left: -260, behavior: 'smooth' });
    });
  }

  if (btnNextCarousel && genresGrid) {
    btnNextCarousel.addEventListener('click', () => {
      genresGrid.scrollBy({ left: 260, behavior: 'smooth' });
    });
  }

  // Studio Toggles
  if (toggleSpatial) {
    toggleSpatial.addEventListener('click', () => {
      isSpatialEnabled = !isSpatialEnabled;
      toggleSpatial.classList.toggle('active', isSpatialEnabled);
    });
  }

  if (toggleBass) {
    toggleBass.addEventListener('click', () => {
      isBassBoostEnabled = !isBassBoostEnabled;
      toggleBass.classList.toggle('active', isBassBoostEnabled);
      if (bassFilter && audioCtx) {
        bassFilter.gain.setValueAtTime(isBassBoostEnabled ? 6 : 0, audioCtx.currentTime);
      }
    });
  }

  if (toggleVinyl) {
    toggleVinyl.addEventListener('click', () => {
      isVinylHissEnabled = !isVinylHissEnabled;
      toggleVinyl.classList.toggle('active', isVinylHissEnabled);
    });
  }

  if (toggleArp) {
    toggleArp.addEventListener('click', () => {
      toggleArp.classList.toggle('active');
    });
  }

  // Modals Open/Close
  function openModal(modal) {
    if (modal) modal.classList.add('open');
  }

  function closeModal(modal) {
    if (modal) modal.classList.remove('open');
  }

  if (btnExploreEco && ecoModal) {
    btnExploreEco.addEventListener('click', () => openModal(ecoModal));
  }

  if (btnNavEco && ecoModal) {
    btnNavEco.addEventListener('click', (e) => {
      e.preventDefault();
      openModal(ecoModal);
    });
  }

  document.querySelectorAll('[data-open-modal="signup"]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      openModal(signupModal);
    });
  });

  closeModals.forEach((btn) => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.modal-overlay').forEach(closeModal);
    });
  });

  document.querySelectorAll('.modal-overlay').forEach((overlay) => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        closeModal(overlay);
      }
    });
  });

  // Mobile Menu Toggle
  const mobileToggle = document.getElementById('mobileToggle');
  if (mobileToggle) {
    mobileToggle.addEventListener('click', () => {
      openModal(ecoModal);
    });
  }

  // Load Initial Track into UI
  loadTrack(0, false);
});
