<?php

namespace Webkul\StatamicIntegration\Providers;

use Webkul\Core\Providers\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Statamic owns its content models. Krayin-specific integration models
     * will be registered here when the integration needs its own records.
     */
    protected $models = [];
}