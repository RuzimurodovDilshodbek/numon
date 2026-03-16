<!DOCTYPE html>
<html lang="uz">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Lug'at Imtihoni</title>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.11.0/dist/tf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface@0.1.0/dist/blazeface.js"></script>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; background: #0f172a; color: #f8fafc; min-height: 100vh; }

  .header { background: #1e293b; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #334155; }
  .header h1 { font-size: 16px; font-weight: 600; }
  .progress-text { font-size: 13px; color: #94a3b8; }

  .camera-wrapper { position: relative; width: 100%; max-width: 320px; margin: 16px auto 0; border-radius: 12px; overflow: hidden; background: #1e293b; }
  #camera { width: 100%; height: 180px; object-fit: cover; display: block; transform: scaleX(-1); }
  #face-status { position: absolute; top: 8px; right: 8px; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
  .face-ok { background: #22c55e; color: white; }
  .face-missing { background: #ef4444; color: white; }

  .question-card { background: #1e293b; border-radius: 16px; margin: 16px; padding: 24px; text-align: center; border: 1px solid #334155; }
  .word-label { font-size: 13px; color: #94a3b8; margin-bottom: 8px; }
  .turkish-word { font-size: 32px; font-weight: 700; color: #60a5fa; letter-spacing: -0.5px; }
  .example { font-size: 13px; color: #64748b; margin-top: 8px; font-style: italic; }

  .answer-section { padding: 0 16px 16px; }
  #answer-input {
    width: 100%; padding: 14px 16px; border-radius: 12px;
    background: #1e293b; border: 2px solid #334155; color: #f8fafc;
    font-size: 18px; text-align: center; outline: none;
    transition: border-color 0.2s;
  }
  #answer-input:focus { border-color: #60a5fa; }
  #answer-input.correct { border-color: #22c55e; background: #052e16; }
  #answer-input.wrong { border-color: #ef4444; background: #2d0000; }

  .btn { width: 100%; padding: 14px; border-radius: 12px; font-size: 16px; font-weight: 600; border: none; cursor: pointer; transition: opacity 0.2s; }
  .btn:active { opacity: 0.8; }
  .btn-primary { background: #3b82f6; color: white; }
  .btn-success { background: #22c55e; color: white; }

  .timer { text-align: center; font-size: 24px; font-weight: 700; color: #f59e0b; padding: 8px 0; }
  .timer.warning { color: #ef4444; }

  .result-screen { display: none; padding: 32px 16px; text-align: center; }
  .result-emoji { font-size: 64px; margin-bottom: 16px; }
  .result-score { font-size: 48px; font-weight: 800; margin-bottom: 8px; }
  .result-pass { color: #22c55e; }
  .result-fail { color: #ef4444; }
  .result-message { font-size: 18px; color: #94a3b8; }

  .progress-bar { height: 4px; background: #334155; border-radius: 2px; margin: 0 16px; }
  .progress-fill { height: 100%; background: #3b82f6; border-radius: 2px; transition: width 0.3s; }

  .face-warning { background: #7c2d12; border: 1px solid #c2410c; border-radius: 8px; margin: 0 16px 8px; padding: 8px 12px; font-size: 13px; color: #fed7aa; display: none; text-align: center; }
</style>
</head>
<body>

<div class="header">
  <h1>{{ $task->title }}</h1>
  <span class="progress-text" id="progress-text">0 / {{ count($words) }}</span>
</div>

@if($timeLimitMinutes)
<div class="timer" id="timer">{{ str_pad($timeLimitMinutes, 2, '0', STR_PAD_LEFT) }}:00</div>
@endif

<div class="progress-bar">
  <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
</div>

<div class="camera-wrapper">
  <video id="camera" autoplay muted playsinline></video>
  <span class="face-status face-missing" id="face-status">Yuz yo'q</span>
</div>

<div class="face-warning" id="face-warning">⚠️ Yuzingiz ko'rinmayapti! Kamerani to'g'rilang.</div>

<div class="question-card" id="question-card">
  <div class="word-label">Turk so'z — O'zbekcha ma'nosini yozing</div>
  <div class="turkish-word" id="current-word">Yuklanmoqda...</div>
  <div class="example" id="current-example"></div>
</div>

<div class="answer-section">
  <input type="text" id="answer-input" placeholder="O'zbekcha yozing..." autocomplete="off" autocorrect="off">
</div>

<div style="padding: 0 16px 24px">
  <button class="btn btn-primary" id="submit-btn" onclick="submitAnswer()">Tasdiqlash</button>
</div>

<div class="result-screen" id="result-screen">
  <div class="result-emoji" id="result-emoji">🎉</div>
  <div class="result-score" id="result-score"></div>
  <div class="result-message" id="result-message"></div>
</div>

<script>
const words = @json($words->values());
const passPercent = {{ $passPrecent }};
const timeLimitSeconds = {{ $timeLimitMinutes ? $timeLimitMinutes * 60 : 'null' }};
const token = "{{ $token }}";

let currentIndex = 0;
let answers = [];
let faceLog = [];
let questionStartTime = Date.now();
let examStartTime = new Date().toISOString();
let faceModel = null;
let faceCheckInterval = null;
let timerInterval = null;
let faceOk = true;
let faceWarningStart = null;

// Camera
async function initCamera() {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
    document.getElementById('camera').srcObject = stream;
    await loadFaceModel();
  } catch(e) {
    console.log('Camera error:', e);
  }
}

async function loadFaceModel() {
  try {
    faceModel = await blazeface.load();
    startFaceCheck();
  } catch(e) {
    console.log('Face model error:', e);
  }
}

function startFaceCheck() {
  faceCheckInterval = setInterval(async () => {
    if (!faceModel) return;
    const video = document.getElementById('camera');
    try {
      const predictions = await faceModel.estimateFaces(video, false);
      const hasFace = predictions.length > 0;
      const status = document.getElementById('face-status');
      const warning = document.getElementById('face-warning');

      if (hasFace) {
        status.textContent = '✓ Yuz aniqlandi';
        status.className = 'face-status face-ok';
        warning.style.display = 'none';
        faceOk = true;
        faceWarningStart = null;
      } else {
        status.textContent = '! Yuz yo\'q';
        status.className = 'face-status face-missing';
        warning.style.display = 'block';
        if (!faceWarningStart) faceWarningStart = Date.now();
        faceOk = false;
        faceLog.push({ time: Date.now(), event: 'face_missing', word_index: currentIndex });
      }
    } catch(e) {}
  }, 2000);
}

// Timer
function startTimer() {
  if (!timeLimitSeconds) return;
  let remaining = timeLimitSeconds;
  const timerEl = document.getElementById('timer');

  timerInterval = setInterval(() => {
    remaining--;
    const m = Math.floor(remaining / 60);
    const s = remaining % 60;
    timerEl.textContent = `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    if (remaining <= 60) timerEl.className = 'timer warning';
    if (remaining <= 0) {
      clearInterval(timerInterval);
      finishExam();
    }
  }, 1000);
}

// Questions
function showQuestion() {
  if (currentIndex >= words.length) {
    finishExam();
    return;
  }

  const word = words[currentIndex];
  document.getElementById('current-word').textContent = word.turkish_word;
  document.getElementById('current-example').textContent = word.example_sentence || '';
  document.getElementById('answer-input').value = '';
  document.getElementById('answer-input').className = '';
  document.getElementById('answer-input').focus();
  document.getElementById('progress-text').textContent = `${currentIndex + 1} / ${words.length}`;
  document.getElementById('progress-fill').style.width = `${(currentIndex / words.length) * 100}%`;
  questionStartTime = Date.now();
}

function submitAnswer() {
  const word = words[currentIndex];
  const answer = document.getElementById('answer-input').value.trim();
  const timeTaken = Math.round((Date.now() - questionStartTime) / 1000);

  answers.push({
    word_id: word.id,
    answer: answer,
    time_taken: timeTaken,
  });

  currentIndex++;

  if (currentIndex >= words.length) {
    finishExam();
  } else {
    setTimeout(showQuestion, 200);
  }
}

async function finishExam() {
  clearInterval(faceCheckInterval);
  clearInterval(timerInterval);

  document.getElementById('question-card').style.display = 'none';
  document.querySelector('.answer-section').style.display = 'none';
  document.querySelector('[onclick="submitAnswer()"]').style.display = 'none';
  document.querySelector('.camera-wrapper').style.display = 'none';

  try {
    const response = await fetch(`/vocab-exam/${token}/submit`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({
        answers,
        face_log: faceLog,
        started_at: examStartTime,
      }),
    });

    const result = await response.json();
    showResult(result);
  } catch(e) {
    document.getElementById('result-screen').style.display = 'block';
    document.getElementById('result-message').textContent = 'Tarmoq xatosi. Qaytadan urinib ko\'ring.';
  }
}

function showResult(result) {
  const screen = document.getElementById('result-screen');
  screen.style.display = 'block';

  document.getElementById('result-emoji').textContent = result.passed ? '🎉' : '📚';
  document.getElementById('result-score').textContent = `${result.score_percent}%`;
  document.getElementById('result-score').className = `result-score ${result.passed ? 'result-pass' : 'result-fail'}`;
  document.getElementById('result-message').textContent = result.passed
    ? `Tabriklaymiz! ${result.correct}/${result.total} so'z to'g'ri.`
    : `${result.correct}/${result.total} so'z to'g'ri. O'tish uchun ${result.pass_percent}% kerak edi.`;
}

// Enter key
document.getElementById('answer-input').addEventListener('keypress', (e) => {
  if (e.key === 'Enter') submitAnswer();
});

// Start
initCamera();
startTimer();
showQuestion();
</script>
</body>
</html>
