<?php

namespace Webkul\StatamicIntegration\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;
use Webkul\StatamicIntegration\Services\CmsLeadIntakeService;

class ProcessStatamicFormSubmission implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public int $uniqueFor = 86400;

    public function __construct(
        public string $formHandle,
        public string $submissionId,
        public array $payload,
    ) {}

    public function uniqueId(): string
    {
        return 'statamic-form-submission:'.$this->submissionId;
    }

    public function handle(CmsLeadIntakeService $leadIntake): void
    {
        $log = DB::table('cms_submission_logs')
            ->where('statamic_submission_id', $this->submissionId)
            ->first();

        if ($log && $log->status === 'completed') {
            return;
        }

        if (! $log) {
            DB::table('cms_submission_logs')->insert([
                'statamic_submission_id' => $this->submissionId,
                'form_handle'            => $this->formHandle,
                'status'                 => 'pending',
                'attempts'               => 0,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
        }

        DB::table('cms_submission_logs')
            ->where('statamic_submission_id', $this->submissionId)
            ->update([
                'status'      => 'processing',
                'attempts'    => DB::raw('attempts + 1'),
                'error_message' => null,
                'updated_at'  => now(),
            ]);

        try {
            $leadId = $leadIntake->createLeadFromSubmission(
                $this->formHandle,
                $this->submissionId,
                $this->payload
            );

            DB::table('cms_submission_logs')
                ->where('statamic_submission_id', $this->submissionId)
                ->update([
                    'crm_lead_id' => $leadId,
                    'status'      => 'completed',
                    'processed_at'=> now(),
                    'error_message' => null,
                    'updated_at'  => now(),
                ]);
        } catch (Throwable $exception) {
            DB::table('cms_submission_logs')
                ->where('statamic_submission_id', $this->submissionId)
                ->update([
                    'status'        => 'failed',
                    'error_message' => mb_substr($exception->getMessage(), 0, 60000),
                    'updated_at'    => now(),
                ]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        DB::table('cms_submission_logs')
            ->where('statamic_submission_id', $this->submissionId)
            ->update([
                'status'        => 'failed',
                'error_message' => $exception
                    ? mb_substr($exception->getMessage(), 0, 60000)
                    : 'The queued job failed without an exception message.',
                'updated_at' => now(),
            ]);
    }
}