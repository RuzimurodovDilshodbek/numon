<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lug'at imtihoni</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; }
        .card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .word { font-size: 1.2em; font-weight: bold; color: #2563eb; }
        input[type=text] { width: 100%; padding: 8px; margin-top: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 1em; box-sizing: border-box; }
        button[type=submit] { background: #2563eb; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-size: 1em; cursor: pointer; width: 100%; margin-top: 16px; }
        button[type=submit]:hover { background: #1d4ed8; }
        .timer { background: #fef3c7; padding: 8px 16px; border-radius: 6px; margin-bottom: 16px; font-weight: bold; }
        .counter { color: #6b7280; margin-bottom: 8px; }
    </style>
</head>
<body>
    <h1>📝 Lug'at imtihoni</h1>
    <p>{{ $vocabTask->vocabularyList->name }}</p>

    @if($vocabTask->time_limit_minutes)
    <div class="timer" id="timer">⏱ Qolgan vaqt: <span id="time-left">—</span></div>
    @endif

    <p class="counter">Jami {{ $words->count() }} ta so'z</p>

    <form method="POST" action="{{ route('vocab-exam.submit', [$attempt, 'token' => $token]) }}">
        @csrf
        @foreach($words as $word)
        <div class="card">
            <div class="word">{{ $word->word }}</div>
            @if($word->example_sentence)
            <div style="color:#6b7280;font-size:0.9em;margin-top:4px;">{{ $word->example_sentence }}</div>
            @endif
            <input type="text" name="answers[{{ $word->id }}]" placeholder="Tarjimasini yozing..." required>
        </div>
        @endforeach
        <button type="submit">✅ Topshirish</button>
    </form>

    @if($vocabTask->time_limit_minutes)
    <script>
        const startedAt = new Date("{{ $attempt->started_at }}").getTime();
        const limitMs = {{ $vocabTask->time_limit_minutes }} * 60 * 1000;
        const form = document.querySelector('form');

        function tick() {
            const elapsed = Date.now() - startedAt;
            const left = limitMs - elapsed;
            if (left <= 0) {
                document.getElementById('time-left').textContent = '0:00';
                form.submit();
                return;
            }
            const m = Math.floor(left / 60000);
            const s = Math.floor((left % 60000) / 1000);
            document.getElementById('time-left').textContent = m + ':' + String(s).padStart(2, '0');
        }
        tick();
        setInterval(tick, 1000);
    </script>
    @endif
</body>
</html>
