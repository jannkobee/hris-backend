<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\TrainingEnrollment;
use App\Services\Notifications\AppNotificationService;
use App\Tenancy\TenantContext;
use Illuminate\Console\Command;

class SendTrainingExpiryReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'training:send-expiry-reminders {--days=30 : Alert window before certificate expiry}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send in-app reminders for training certificates nearing expiry.';

    /**
     * Execute the console command.
     */
    public function handle(TenantContext $tenantContext, AppNotificationService $notifications): int
    {
        $days = max(1, (int) $this->option('days'));
        $count = 0;
        Organization::query()->where('status', Organization::STATUS_ACTIVE)->get()->each(function (Organization $organization) use ($tenantContext, $notifications, $days, &$count): void {
            $tenantContext->run($organization, function () use ($notifications, $days, &$count): void {
                TrainingEnrollment::query()->where('status', 'completed')->whereNotNull('certificate_expires_on')->whereBetween('certificate_expires_on', [now()->toDateString(), now()->addDays($days)->toDateString()])->each(function (TrainingEnrollment $enrollment) use ($notifications, &$count): void {
                    $employee = $enrollment->employee;
                    if (! $employee?->user) {
                        return;
                    }
                    $exists = \App\Models\AppNotification::query()->where('user_id', $employee->user->getKey())->where('type', 'training_certificate_expiry')->where('data->enrollment_id', $enrollment->getKey())->whereDate('created_at', today())->exists();
                    if (! $exists) {
                        $notifications->send($employee->user, 'training_certificate_expiry', 'Training certificate expiry', 'Your training certificate expires on '.$enrollment->certificate_expires_on->format('M j, Y').'.', ['enrollment_id' => $enrollment->getKey()]);
                        $count++;
                    }
                });
            });
        });
        $this->info("Sent {$count} training certificate reminder(s).");

        return self::SUCCESS;
    }
}
