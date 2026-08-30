<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\SavedReport;
use App\Services\Reporting\OperationalReportService;
use App\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Throwable;

class DeliverScheduledReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:deliver';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Email due saved reports to their configured recipients.';

    /**
     * Execute the console command.
     */
    public function handle(TenantContext $tenantContext, OperationalReportService $reports): int
    {
        $delivered = 0;
        $failed = 0;

        Organization::query()->where('status', Organization::STATUS_ACTIVE)->each(
            function (Organization $organization) use ($tenantContext, $reports, &$delivered, &$failed): void {
                $tenantContext->run($organization, function () use ($organization, $reports, &$delivered, &$failed): void {
                    SavedReport::query()
                        ->whereNotNull('delivery_frequency')
                        ->whereNotNull('next_delivery_at')
                        ->where('next_delivery_at', '<=', now())
                        ->each(function (SavedReport $savedReport) use ($organization, $reports, &$delivered, &$failed): void {
                            try {
                                $to = now($organization->timezone ?: config('app.timezone'))->toDateString();
                                $from = now($organization->timezone ?: config('app.timezone'))
                                    ->subDays(max(1, (int) $savedReport->delivery_period_days) - 1)->toDateString();
                                $report = $reports->run($savedReport->report_type, $from, $to);
                                $csv = $this->csv($report);
                                $filename = str($savedReport->name)->slug()->append('-'.$to.'.csv')->toString();

                                Mail::raw(
                                    "Attached is the scheduled {$savedReport->name} report for {$from} through {$to}.",
                                    function (Message $message) use ($savedReport, $csv, $filename): void {
                                        $message->to($savedReport->delivery_recipients)
                                            ->subject('Scheduled report: '.$savedReport->name)
                                            ->attachData($csv, $filename, ['mime' => 'text/csv']);
                                    }
                                );

                                $savedReport->update([
                                    'last_delivered_at' => now(),
                                    'last_delivery_error' => null,
                                    'next_delivery_at' => $this->nextDelivery($savedReport->delivery_frequency),
                                ]);
                                $delivered++;
                            } catch (Throwable $exception) {
                                report($exception);
                                $savedReport->update(['last_delivery_error' => $exception->getMessage()]);
                                $failed++;
                            }
                        });
                });
            }
        );

        $this->info("Scheduled reports delivered: {$delivered}; failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function csv(array $report): string
    {
        $handle = fopen('php://temp', 'w+b');
        fputcsv($handle, $report['columns']);
        foreach ($report['rows'] as $row) {
            fputcsv($handle, array_map(fn (string $column) => $row[$column] ?? null, $report['columns']));
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return (string) $csv;
    }

    private function nextDelivery(string $frequency)
    {
        return match ($frequency) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonthNoOverflow(),
        };
    }
}
