<?php

namespace Webkul\Cms\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Statamic\Contracts\Forms\Submission;
use Throwable;
use Webkul\Cms\Models\CmsUser;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\PipelineRepository;
use Webkul\Lead\Repositories\SourceRepository;
use Webkul\Lead\Repositories\TypeRepository;

class StatamicLeadIntegration
{
    public function __construct(
        protected CmsSettingsManager $settingsManager,
        protected LeadRepository $leadRepository,
        protected PipelineRepository $pipelineRepository,
        protected SourceRepository $sourceRepository,
        protected TypeRepository $typeRepository,
    ) {}

    /**
     * Convert a configured Statamic form submission into a Krayin lead.
     *
     * The CRM lead and its processing record are committed in the same
     * transaction. Re-delivery of the same Statamic submission is idempotent.
     */
    public function handle(Submission $submission): void
    {
        $formHandle = $submission->form()->handle();

        if (! config("cms.forms_to_crm.{$formHandle}", false)) {
            return;
        }

        $payload = $this->normalizePayload($submission->data()->all());

        $settings = $this->settingsManager->current();

        if (! $settings->default_lead_owner_id) {
            throw ValidationException::withMessages([
                'message' => trans('cms::app.forms.owner_required'),
            ]);
        }

        try {
            DB::transaction(function () use (
                $submission,
                $formHandle,
                $payload,
                $settings
            ) {
                DB::table('cms_submission_logs')->insertOrIgnore([
                    'form_handle'   => $formHandle,
                    'submission_id' => (string) $submission->id(),
                    'status'        => 'processing',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                $log = DB::table('cms_submission_logs')
                    ->where('form_handle', $formHandle)
                    ->where('submission_id', (string) $submission->id())
                    ->lockForUpdate()
                    ->first();

                if ($log && $log->status === 'completed') {
                    return;
                }

                DB::table('cms_submission_logs')
                    ->where('id', $log->id)
                    ->update([
                        'status'        => 'processing',
                        'error_message' => null,
                        'updated_at'    => now(),
                    ]);

                $owner = CmsUser::query()
                    ->whereKey($settings->default_lead_owner_id)
                    ->where('status', 1)
                    ->first();

                if (! $owner) {
                    throw new \RuntimeException('The configured website lead owner is inactive or no longer exists.');
                }

                $pipeline = $this->pipelineRepository->getDefaultPipeline();

                if (! $pipeline) {
                    throw new \RuntimeException('No CRM lead pipeline is configured.');
                }

                $stage = $pipeline->stages()->first();

                if (! $stage) {
                    throw new \RuntimeException('The default CRM lead pipeline has no stages.');
                }

                $source = $this->sourceRepository->findOneByField('name', 'Web Form')
                    ?: $this->sourceRepository->first();

                $type = $this->typeRepository->first();

                if (! $source || ! $type) {
                    throw new \RuntimeException('CRM lead source or lead type is not configured.');
                }

                $person = $this->findPersonByEmail($payload['email']);

                $personData = $person
                    ? ['id' => $person->id]
                    : [
                        'name' => $payload['name'],
                        'emails' => [[
                            'value' => $payload['email'],
                            'label' => 'work',
                        ]],
                        'contact_numbers' => $payload['phone']
                            ? [[
                                'value' => $payload['phone'],
                                'label' => 'work',
                            ]]
                            : [],
                        'organization_name' => $payload['company'] ?: null,
                        'entity_type' => 'persons',
                        'user_id' => $owner->id,
                    ];

                $lead = $this->leadRepository->create([
                    'title' => $payload['subject']
                        ?: 'Website enquiry - '.$payload['name'],
                    'description' => $payload['message'],
                    'lead_value' => 0,
                    'status' => 1,
                    'entity_type' => 'leads',
                    'user_id' => $owner->id,
                    'person' => $personData,
                    'lead_pipeline_id' => $pipeline->id,
                    'lead_pipeline_stage_id' => $stage->id,
                    'lead_source_id' => $source->id,
                    'lead_type_id' => $type->id,
                ]);

                DB::table('cms_submission_logs')
                    ->where('id', $log->id)
                    ->update([
                        'status'        => 'completed',
                        'crm_lead_id'   => $lead->id,
                        'error_message' => null,
                        'processed_at'  => now(),
                        'updated_at'    => now(),
                    ]);
            });
        } catch (Throwable $exception) {
            DB::table('cms_submission_logs')->updateOrInsert(
                [
                    'form_handle' => $formHandle,
                    'submission_id' => (string) $submission->id(),
                ],
                [
                    'status' => 'failed',
                    'error_message' => mb_substr($exception->getMessage(), 0, 2000),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            Log::error('Statamic form could not be converted into a Krayin lead.', [
                'form_handle' => $formHandle,
                'submission_id' => (string) $submission->id(),
                'exception' => $exception::class,
            ]);

            throw ValidationException::withMessages([
                'message' => trans('cms::app.forms.processing_failed'),
            ]);
        }
    }

    /**
     * Only accept the public contact form's intended fields.
     */
    protected function normalizePayload(array $data): array
    {
        $payload = [
            'name' => trim((string) ($data['name'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')),
            'phone' => trim((string) ($data['phone'] ?? '')),
            'company' => trim((string) ($data['company'] ?? '')),
            'subject' => trim((string) ($data['subject'] ?? '')),
            'message' => trim((string) ($data['message'] ?? '')),
        ];

        if (
            $payload['name'] === ''
            || ! filter_var($payload['email'], FILTER_VALIDATE_EMAIL)
            || $payload['message'] === ''
        ) {
            throw ValidationException::withMessages([
                'message' => trans('cms::app.forms.invalid_submission'),
            ]);
        }

        return $payload;
    }

    /**
     * Match emails exactly, without regard to case.
     *
     * The SQL LIKE is only used to narrow the candidates; the decoded JSON
     * value is compared exactly to avoid wildcard-based contact collisions.
     */
    protected function findPersonByEmail(string $email)
    {
        $needle = mb_strtolower($email);

        return app(\Webkul\Contact\Repositories\PersonRepository::class)
            ->getModel()
            ->whereRaw(
                'LOWER(CAST(emails AS CHAR)) LIKE ?',
                ['%'.addcslashes($needle, '%_\\').'%']
            )
            ->get()
            ->first(fn ($person) => collect($person->emails)->contains(
                fn ($personEmail) => mb_strtolower($personEmail['value'] ?? '') === $needle
            ));
    }
}
