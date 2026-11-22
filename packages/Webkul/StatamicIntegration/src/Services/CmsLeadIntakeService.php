<?php

namespace Webkul\StatamicIntegration\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\PipelineRepository;
use Webkul\Lead\Repositories\SourceRepository;
use Webkul\Lead\Repositories\TypeRepository;

class CmsLeadIntakeService
{
    public function __construct(
        protected PersonRepository $personRepository,
        protected LeadRepository $leadRepository,
        protected PipelineRepository $pipelineRepository,
        protected SourceRepository $sourceRepository,
        protected TypeRepository $typeRepository,
    ) {}

    /**
     * Create a Krayin lead from a validated Statamic form payload.
     *
     * Krayin remains the source of truth for the CRM person and lead records.
     * This service does not call the WebForm HTTP controller.
     */
    public function createLeadFromSubmission(string $formHandle, string $submissionId, array $payload): int
    {
        $settings = config("statamic_integration.forms.{$formHandle}");

        if (! is_array($settings) || ! ($settings['enabled'] ?? false) || ! ($settings['create_lead'] ?? false)) {
            throw new RuntimeException("CRM lead integration is not enabled for Statamic form [{$formHandle}].");
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $phone = trim((string) ($payload['phone'] ?? ''));
        $company = trim((string) ($payload['company'] ?? ''));
        $message = trim((string) ($payload['message'] ?? ''));

        if ($name === '') {
            throw new RuntimeException('The Statamic form did not provide a contact name.');
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('The Statamic form did not provide a valid email address.');
        }

        $ownerId = $this->resolveOwnerId($settings);
        $pipeline = $this->resolvePipeline($settings);
        $stage = $pipeline->stages()->orderBy('sort_order')->first();

        if (! $stage) {
            throw new RuntimeException("Krayin pipeline [{$pipeline->name}] has no stages.");
        }

        $source = $this->resolveSource($settings);
        $type = $this->resolveType($settings);
        $person = $this->findPersonByExactEmail($email);

        $personData = [
            'entity_type' => 'persons',
            'name'        => $name,
            'emails'      => [
                [
                    'label' => 'work',
                    'value' => $email,
                ],
            ],
            'user_id' => $ownerId,
        ];

        if ($phone !== '') {
            $personData['contact_numbers'] = [
                [
                    'label' => 'work',
                    'value' => $phone,
                ],
            ];
        }

        if ($company !== '') {
            $personData['organization_name'] = $company;
        }

        if ($person) {
            $personData['id'] = $person->id;
        }

        $leadTitlePrefix = trim((string) ($settings['lead_title_prefix'] ?? 'Website enquiry'));

        $leadData = [
            'entity_type'              => 'leads',
            'title'                    => trim($leadTitlePrefix.' - '.$name),
            'description'              => $message,
            'lead_value'               => 0,
            'status'                   => 1,
            'user_id'                  => $ownerId,
            'lead_pipeline_id'         => $pipeline->id,
            'lead_pipeline_stage_id'   => $stage->id,
            'lead_source_id'           => $source->id,
            'lead_type_id'             => $type->id,
            'person'                   => $personData,
        ];

        Event::dispatch('lead.create.before');

        $lead = $this->leadRepository->create($leadData);

        Event::dispatch('lead.create.after', $lead);

        return (int) $lead->id;
    }

    protected function findPersonByExactEmail(string $email)
    {
        $needle = mb_strtolower($email);

        return $this->personRepository
            ->getModel()
            ->whereRaw(
                'LOWER(CAST(emails AS CHAR)) LIKE ?',
                ['%'.addcslashes($needle, '%_\\').'%']
            )
            ->get()
            ->first(fn ($person) => collect($person->emails)->contains(
                fn ($personEmail) => mb_strtolower(trim($personEmail['value'] ?? '')) === $needle
            ));
    }

    protected function resolveOwnerId(array $settings): int
    {
        $configuredId = $settings['owner_id'] ?? null;

        if ($configuredId && DB::table('users')->where('id', $configuredId)->where('status', 1)->exists()) {
            return (int) $configuredId;
        }

        $administratorId = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('users.status', 1)
            ->where('roles.permission_type', 'all')
            ->orderBy('users.id')
            ->value('users.id');

        if ($administratorId) {
            return (int) $administratorId;
        }

        $userId = DB::table('users')
            ->where('status', 1)
            ->orderBy('id')
            ->value('id');

        if (! $userId) {
            throw new RuntimeException('No active Krayin user is available to own website leads.');
        }

        return (int) $userId;
    }

    protected function resolvePipeline(array $settings)
    {
        $pipelineId = $settings['pipeline_id'] ?? null;

        $pipeline = $pipelineId
            ? $this->pipelineRepository->find($pipelineId)
            : $this->pipelineRepository->getDefaultPipeline();

        $pipeline = $pipeline ?: $this->pipelineRepository->first();

        if (! $pipeline) {
            throw new RuntimeException('No Krayin lead pipeline is configured.');
        }

        return $pipeline;
    }

    protected function resolveSource(array $settings)
    {
        $sourceId = $settings['source_id'] ?? null;

        $source = $sourceId
            ? $this->sourceRepository->find($sourceId)
            : null;

        if (! $source) {
            $sourceName = $settings['source_name'] ?? 'Website';

            $source = $this->sourceRepository->findOneByField('name', $sourceName)
                ?: $this->sourceRepository->first();
        }

        if (! $source) {
            throw new RuntimeException('No Krayin lead source is available. Create a lead source in CRM settings.');
        }

        return $source;
    }

    protected function resolveType(array $settings)
    {
        $typeId = $settings['type_id'] ?? null;

        $type = $typeId
            ? $this->typeRepository->find($typeId)
            : null;

        if (! $type) {
            $typeName = $settings['type_name'] ?? null;

            $type = $typeName
                ? $this->typeRepository->findOneByField('name', $typeName)
                : null;
        }

        $type = $type ?: $this->typeRepository->first();

        if (! $type) {
            throw new RuntimeException('No Krayin lead type is available. Create a lead type in CRM settings.');
        }

        return $type;
    }
}