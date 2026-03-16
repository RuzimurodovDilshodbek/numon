<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Lesson;
use Carbon\Carbon;

class LessonGeneratorService
{
    public function generateForNextMonth(Group $group): int
    {
        return $this->generateForMonth($group, now()->startOfMonth());
    }

    public function generateForMonth(Group $group, Carbon $month): int
    {
        $templates = $group->scheduleTemplates()->where('is_active', true)->get();

        if ($templates->isEmpty()) {
            return 0;
        }

        $created = 0;
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $current = $start->copy();

        while ($current <= $end) {
            // Carbon: 0=Sunday, 1=Monday... bizning: 0=Monday
            $ourDayOfWeek = ($current->dayOfWeek === 0) ? 6 : $current->dayOfWeek - 1;

            $template = $templates->firstWhere('day_of_week', $ourDayOfWeek);

            if ($template) {
                $lesson = Lesson::firstOrCreate(
                    [
                        'group_id' => $group->id,
                        'lesson_date' => $current->toDateString(),
                        'start_time' => $template->start_time,
                    ],
                    [
                        'end_time' => $template->end_time,
                        'status' => 'scheduled',
                        'is_auto_generated' => true,
                        'zoom_link' => $group->zoom_link,
                    ]
                );

                if ($lesson->wasRecentlyCreated) {
                    $created++;
                }
            }

            $current->addDay();
        }

        return $created;
    }
}
