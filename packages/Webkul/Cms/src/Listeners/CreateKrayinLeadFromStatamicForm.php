<?php

namespace Webkul\Cms\Listeners;

use Statamic\Events\FormSubmitted;
use Webkul\Cms\Services\StatamicLeadIntegration;

class CreateKrayinLeadFromStatamicForm
{
    public function __construct(
        protected StatamicLeadIntegration $integration
    ) {}

    public function handle(FormSubmitted $event): void
    {
        $this->integration->handle($event->submission);
    }
}
