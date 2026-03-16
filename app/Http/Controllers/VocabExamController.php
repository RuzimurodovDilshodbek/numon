<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VocabularyTask;
use App\Models\VocabularyAttempt;
use App\Models\VocabularyAttemptAnswer;
use App\Models\Task;
use App\Models\TaskSubmission;
use Illuminate\Support\Facades\Cache;

class VocabExamController extends Controller
{
    public function show(string $token)
    {
        $data = Cache::get("vocab_exam_{$token}");

        if (!$data) {
            return view('vocab-exam.expired');
        }

        $task = Task::with('vocabularyTask.vocabularyList.words')->find($data['task_id']);
        $vocabTask = $task?->vocabularyTask;

        if (!$vocabTask) {
            abort(404, 'Vazifa topilmadi');
        }

        $words = $vocabTask->vocabularyList->words;

        if ($vocabTask->random_order) {
            $words = $words->shuffle();
        }

        $attemptNumber = VocabularyAttempt::where('vocabulary_task_id', $vocabTask->id)
            ->where('student_id', $data['student_id'])
            ->count() + 1;

        return view('vocab-exam.index', [
            'token'            => $token,
            'task'             => $task,
            'vocabTask'        => $vocabTask,
            'words'            => $words,
            'passPrecent'      => $vocabTask->pass_percent,
            'timeLimitMinutes' => $vocabTask->time_limit_minutes,
            'attemptNumber'    => $attemptNumber,
        ]);
    }

    public function submit(Request $request, string $token)
    {
        $data = Cache::get("vocab_exam_{$token}");

        if (!$data) {
            return response()->json(['error' => "Token muddati o'tgan"], 422);
        }

        $validated = $request->validate([
            'answers'              => 'required|array',
            'answers.*.word_id'    => 'required|integer',
            'answers.*.answer'     => 'nullable|string',
            'answers.*.time_taken' => 'required|integer',
            'face_log'             => 'nullable|array',
            'started_at'           => 'required|string',
        ]);

        $task     = Task::with('vocabularyTask.vocabularyList.words')->find($data['task_id']);
        $vocabTask = $task->vocabularyTask;
        $words    = $vocabTask->vocabularyList->words->keyBy('id');

        $totalWords  = count($validated['answers']);
        $correctWords = 0;
        $answerRecords = [];

        foreach ($validated['answers'] as $answerData) {
            $word = $words[$answerData['word_id']] ?? null;
            if (!$word) continue;

            $isCorrect = $this->checkAnswer(
                $answerData['answer'] ?? '',
                $word->uzbek_translation
            );

            if ($isCorrect) $correctWords++;

            $answerRecords[] = [
                'vocabulary_word_id'  => $word->id,
                'student_answer'      => $answerData['answer'] ?? '',
                'is_correct'          => $isCorrect,
                'time_taken_seconds'  => (int) $answerData['time_taken'],
            ];
        }

        $scorePercent = $totalWords > 0
            ? round($correctWords / $totalWords * 100, 2)
            : 0;

        $isPassed = $scorePercent >= $vocabTask->pass_percent;

        $attempt = VocabularyAttempt::create([
            'vocabulary_task_id' => $vocabTask->id,
            'student_id'         => $data['student_id'],
            'started_at'         => $validated['started_at'],
            'finished_at'        => now(),
            'total_words'        => $totalWords,
            'correct_words'      => $correctWords,
            'score_percent'      => $scorePercent,
            'is_passed'          => $isPassed,
            'attempt_number'     => VocabularyAttempt::where('vocabulary_task_id', $vocabTask->id)
                                        ->where('student_id', $data['student_id'])->count(),
            'metadata'           => [
                'face_log'   => $validated['face_log'] ?? [],
                'user_agent' => $request->userAgent(),
            ],
        ]);

        foreach ($answerRecords as &$record) {
            $record['vocabulary_attempt_id'] = $attempt->id;
            $record['created_at']  = now();
            $record['updated_at']  = now();
        }
        VocabularyAttemptAnswer::insert($answerRecords);

        // TaskSubmission yangilash
        TaskSubmission::updateOrCreate(
            ['task_id' => $task->id, 'student_id' => $data['student_id']],
            [
                'status'       => 'graded',
                'score'        => (int) $scorePercent,
                'submitted_at' => now(),
                'graded_at'    => now(),
            ]
        );

        // Token o'chirish (bir marta ishlatish)
        Cache::forget("vocab_exam_{$token}");

        return response()->json([
            'passed'       => $isPassed,
            'score_percent' => $scorePercent,
            'correct'      => $correctWords,
            'total'        => $totalWords,
            'pass_percent' => $vocabTask->pass_percent,
        ]);
    }

    private function checkAnswer(string $given, string $correct): bool
    {
        if (empty(trim($given))) return false;

        $given   = mb_strtolower(trim($given));
        $correct = mb_strtolower(trim($correct));

        if ($given === $correct) return true;

        // 85% mos kelsa ham to'g'ri (mayda xatolar uchun)
        similar_text($given, $correct, $percent);
        return $percent >= 85;
    }
}
