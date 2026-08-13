<?php

namespace App\Services\Dashboard;

use App\Services\AppSettings\AppSettingService;
use Carbon\CarbonImmutable;

class DailyInspirationService
{
    private const MESSAGES = [
        ['theme' => 'Progress', 'text' => 'Small improvements, repeated consistently, become remarkable results.'],
        ['theme' => 'Teamwork', 'text' => 'Make the work lighter for someone today, and the whole team moves faster.'],
        ['theme' => 'Focus', 'text' => 'Give your best attention to the next important thing.'],
        ['theme' => 'Growth', 'text' => 'A useful lesson is progress, even when the first attempt is imperfect.'],
        ['theme' => 'Ownership', 'text' => 'Leave every task, process, and conversation a little better than you found it.'],
        ['theme' => 'Clarity', 'text' => 'Clear priorities create calm, confident work.'],
        ['theme' => 'Service', 'text' => 'Excellent work begins with understanding who it helps.'],
        ['theme' => 'Momentum', 'text' => 'Start with what is possible now; momentum will reveal the next step.'],
        ['theme' => 'Collaboration', 'text' => 'The strongest ideas improve when people feel safe enough to contribute.'],
        ['theme' => 'Craft', 'text' => 'Care in the small details is how trust is built.'],
        ['theme' => 'Perspective', 'text' => 'A difficult day can still contain one meaningful win.'],
        ['theme' => 'Courage', 'text' => 'Ask the question, share the idea, and take the thoughtful first step.'],
        ['theme' => 'Balance', 'text' => 'Sustainable effort turns good work into lasting work.'],
        ['theme' => 'Kindness', 'text' => 'Professional kindness costs little and changes the quality of a workplace.'],
        ['theme' => 'Purpose', 'text' => "Connect today's task to the person or outcome it serves."],
        ['theme' => 'Learning', 'text' => 'Curiosity turns everyday work into continuous improvement.'],
        ['theme' => 'Consistency', 'text' => 'Reliable progress is built on ordinary days like today.'],
        ['theme' => 'Leadership', 'text' => 'Leadership is often a quiet choice to bring clarity and help others succeed.'],
        ['theme' => 'Quality', 'text' => 'Do the work carefully enough that your future self will thank you.'],
        ['theme' => 'Connection', 'text' => 'A short, honest conversation can prevent a long misunderstanding.'],
        ['theme' => 'Resilience', 'text' => 'Adjust the plan when needed, but keep the purpose in view.'],
        ['theme' => 'Recognition', 'text' => 'Notice good work and say it out loud.'],
        ['theme' => 'Impact', 'text' => 'Choose the task that creates the most useful difference, not merely the most activity.'],
        ['theme' => 'Trust', 'text' => 'Keep the small commitments; they are the foundation of strong teams.'],
    ];

    public function __construct(private readonly AppSettingService $settings)
    {
    }

    public function forToday(): array
    {
        $timezone = (string) $this->settings->get('organization.timezone', config('app.timezone'));
        $today = CarbonImmutable::now($timezone);
        $index = abs(crc32($today->format('Y-m-d'))) % count(self::MESSAGES);

        return array_merge(self::MESSAGES[$index], [
            'date' => $today->toDateString(),
            'timezone' => $timezone,
        ]);
    }
}
