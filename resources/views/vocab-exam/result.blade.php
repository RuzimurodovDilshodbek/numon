<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Natija</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f5f5; text-align: center; }
        .result-card { background: white; border-radius: 12px; padding: 40px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .score { font-size: 4em; font-weight: bold; }
        .passed { color: #16a34a; }
        .failed { color: #dc2626; }
        .badge { font-size: 1.2em; padding: 8px 20px; border-radius: 20px; display: inline-block; margin-top: 12px; }
        .badge.passed { background: #dcfce7; color: #16a34a; }
        .badge.failed { background: #fee2e2; color: #dc2626; }
        .answers { text-align: left; margin-top: 24px; }
        .answer-row { padding: 8px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; gap: 8px; }
        .correct { color: #16a34a; }
        .wrong { color: #dc2626; }
    </style>
</head>
<body>
    <div class="result-card">
        <h1>📊 Natija</h1>

        <div class="score {{ $attempt->is_passed ? 'passed' : 'failed' }}">
            {{ $attempt->score_percent }}%
        </div>

        <div class="badge {{ $attempt->is_passed ? 'passed' : 'failed' }}">
            {{ $attempt->is_passed ? '✅ O\'tdingiz!' : '❌ O\'tolmadingiz' }}
        </div>

        <p style="color:#6b7280;margin-top:16px;">
            {{ $attempt->correct_words }} / {{ $attempt->total_words }} to'g'ri &nbsp;|&nbsp;
            Tugatildi: {{ $attempt->finished_at->format('d.m.Y H:i') }}
        </p>

        <div class="answers">
            <h3>Javoblar:</h3>
            @foreach($attempt->answers as $answer)
            <div class="answer-row">
                <span>{{ $answer->word->word }}</span>
                <span class="{{ $answer->is_correct ? 'correct' : 'wrong' }}">
                    {{ $answer->student_answer ?: '—' }}
                    @if(!$answer->is_correct)
                        → {{ $answer->word->translation }}
                    @endif
                </span>
            </div>
            @endforeach
        </div>
    </div>
</body>
</html>
