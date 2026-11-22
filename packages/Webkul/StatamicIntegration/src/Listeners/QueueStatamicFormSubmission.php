<?php

namespace Webkul\StatamicIntegration\Listeners;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Statamic\Events\SubmissionCreated;
use Webkul\StatamicIntegration\Jobs\ProcessStatamicFormSubmission;
use Throwable;

class QueueStatamicFormSubmission
{
    public function handle(SubmissionCreated $event): void
    {
        $submission = $event->submission;
        $formHandle = $submission->form()->handle();
        $settings = config("statamic_integration.forms.{$formHandle}");

        if (! is_array($settings) || ! ($settings['enabled'] ?? false) || ! ($settings['create_lead'] ?? false)) {
            return;
        }

        $source = $submission->data()->all();
        $fieldMap = $settings['field_map'] ?? [];

        $payload = [];

        foreach (['name', 'email', 'phone', 'company', 'message'] as $target) {
            $fieldHandle = $fieldMap[$target] ?? $target;
            $value = Arr::get($source, $fieldHandle);

            if (is_scalar($value) || $value === null) {
                $payload[$target] = is_string($value) ? trim($value) : $value;
            }
        }

        try {
            ProcessStatamicFormSubmission::dispatch(
                $formHandle,
                (string) $submission->id(),
                $payload
            );
        } catch (Throwable $exception) {
            /*
             * The visitor's Statamic submission is already accepted. Record
             * the queue dispatch problem without turning a valid form submit
             * into a front-end 500 response.
             */
            Log::error('Unable to queue Statamic to Krayin form integration.', [
                'form' => $formHandle,
                'submission_id' => (string) $submission->id(),
                'error' => $exception->getMessage(),
            ]);
        }
    }
}