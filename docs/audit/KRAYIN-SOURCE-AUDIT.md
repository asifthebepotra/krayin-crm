# Krayin CRM Source Audit for Statamic Integration

**Repository:** asifthebepotra/krayin-crm  
**Branch:** 2.2  
**Audit date:** 2026-11-15  
**Scope:** Read-only source inspection through the connected GitHub repository. No source files modified and no local Composer installation executed.

## Repository identity and baseline

- Repository is public; connected account has admin-level permissions.
- Default branch is `2.2`.
- composer.json declares PHP `^8.3`, Laravel `^12.0`, Sanctum `^4.0` and local PSR-4 packages under `packages/Webkul/*`.
- Composer platform PHP is pinned to `8.3.30`.
- Inspected composer.lock pins Laravel Framework `v12.71.0`, Sanctum `v4.3.1`, Guzzle `7.16.1`, Flysystem `3.36.0`, Symfony Console and HttpFoundation `v7.4.23`.
- Dev dependencies include `krayin/krayin-package-generator: dev-master`; review this for reproducible audit setup.

## Source findings

1. **Bootstrap:** `bootstrap/providers.php` manually registers Krayin and Webkul package providers. Verify Statamic installation/provider discovery against this list and Composer Laravel package discovery.
2. **Admin routing:** `Webkul\\Admin\\Providers\\AdminServiceProvider` registers admin routes behind `web`, `admin_locale`, and `user` middleware, using configurable `app.admin_path`. Front routes use `web` and `admin_locale`.
3. **Authentication:** `config/auth.php` sets default guard to `user`, with session driver and Eloquent provider model `Webkul\\User\\Models\\User`.
4. **Krayin user model:** Extends Laravel `Authenticatable`, uses Sanctum `HasApiTokens`, and exposes `role()` and `groups()` relationships. Its `hasPermission()` relies on the associated role's permission array.
5. **Krayin Bouncer:** Admin middleware checks the `user` guard, active status, role availability and route-name ACL. It fails closed for unmapped privileged routes. Do not wrap Statamic CP in this middleware by default.
6. **Roles:** `Webkul\\User\\Models\\Role` stores `permission_type` and casts `permissions` to an array.
7. **Lead creation:** `Webkul\\Lead\\Repositories\\LeadRepository::create()` can create/link a person, set owner, apply default pipeline/stage, save custom attributes and associate products.
8. **Existing WebForm:** Public routes are registered under `/web-forms`. `WebFormController::formStore()` restricts payload fields to configured exposed attributes, finds a person by exact case-insensitive email, creates a person/lead through repositories, selects pipeline/stage/source/type and dispatches lead events. Its request validates configured attributes.
9. **WebForm security nuance:** Public form submission is not directly reusable as a service without extracting request-dependent logic. It includes HTTP request mutation and controller concerns. A domain/application service or adapter is needed for Statamic submissions; do not make internal HTTP calls back into the application.
10. **Session:** Laravel session defaults to file, 120-minute lifetime, HttpOnly and SameSite=Lax. Shared auth feasibility must be tested with Statamic's actual user provider and CP middleware.
11. **Admin URL:** `config/app.php` defines `admin_path` from `APP_ADMIN_PATH`, default `admin`; do not hardcode the prefix.
12. **WebForm ACL/menu:** WebForm has its own menu and ACL entries under settings; preserve these during package integration.

## Preliminary architecture implications

- Keep Krayin's `user` guard and Bouncer intact.
- Run Statamic Control Panel on its native routes and authorization path; do not attach Krayin's route middleware to `/cp`.
- Prototype Statamic user-provider integration against the Krayin User model. If Statamic's user contract and permission model require independent Statamic users, use explicit identity mapping and secure session integration rather than copying passwords.
- Reuse the existing LeadRepository and CRM business rules, but extract/reuse domain-level lead intake rather than invoking WebFormController or depending on global request mutation.
- Keep Statamic form submission records and maintain a separate integration processing record for retries and idempotency.
- Confirm exact package route, ACL and menu paths before implementation.

## Compatibility status

**Not yet passed.** This document records GitHub source inspection only. The connector does not execute Composer or boot this repository. Required next steps in a PHP environment:

1. Clone branch `2.2` and record exact HEAD SHA.
2. Run baseline `composer install`, `composer validate`, `composer check-platform-reqs`, and existing tests.
3. Add the exact Statamic package release in a separate audit branch, preserving existing Composer scripts.
4. Review `composer update statamic/cms -W --dry-run` before actual installation.
5. Inspect the resolved Inertia version against Statamic 6 compatibility requirements.
6. Run package discovery, migrations, route list, route cache, CP login, content publishing, public rendering and CRM regression tests.
7. Produce `COMPATIBILITY-DECISION.md` with GO / CONDITIONAL GO / NO-GO.

## Source excerpts

## packages/Webkul/Admin/src/Routes/Admin/auth-routes.php

```php
<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Controllers\User\ForgotPasswordController;
use Webkul\Admin\Http\Controllers\User\ResetPasswordController;
use Webkul\Admin\Http\Controllers\User\SessionController;

Route::withoutMiddleware(['user'])->group(function () {
    /**
     * Redirect route.
     */
    Route::get('/', [Controller::class, 'redirectToLogin']);

    /**
     * Session routes.
     */
    Route::controller(SessionController::class)->group(function () {
        Route::prefix('login')->group(function () {
            Route::get('', 'create')->name('admin.session.create');

            /**
             * Throttled inside the controller instead of here, counting only failed attempts, so a
             * correct sign-in never consumes the budget.
             */
            Route::post('', 'store')->name('admin.session.store');
        });

        Route::middleware(['user'])->group(function () {
            Route::delete('logout', 'destroy')->name('admin.session.destroy');
        });
    });

    /**
     * Forgot password routes.
     */
    Route::controller(ForgotPasswordController::class)->prefix('forget-password')->group(function () {
        Route::get('', 'create')->name('admin.forgot_password.create');

        Route::middleware(['throttle:5,1'])->post('', 'store')->name('admin.forgot_password.store');
    });

    /**
     * Reset password routes.
     */
    Route::controller(ResetPasswordController::class)->prefix('reset-password')->group(function () {
        Route::get('{token}', 'create')->name('admin.reset_password.create');

        Route::middleware(['throttle:5,1'])->post('', 'store')->name('admin.reset_password.store');
    });
});

```

## packages/Webkul/Admin/src/Routes/Admin/leads-routes.php

```php
<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Lead\ActivityController;
use Webkul\Admin\Http\Controllers\Lead\EmailController;
use Webkul\Admin\Http\Controllers\Lead\LeadController;
use Webkul\Admin\Http\Controllers\Lead\QuoteController;
use Webkul\Admin\Http\Controllers\Lead\TagController;

Route::controller(LeadController::class)->prefix('leads')->group(function () {
    Route::get('', 'index')->name('admin.leads.index');

    Route::get('create', 'create')->name('admin.leads.create');

    Route::post('create', 'store')->name('admin.leads.store');

    Route::post('create-by-ai', 'createByAI')->name('admin.leads.create_by_ai');

    Route::get('view/{id}', 'view')->name('admin.leads.view');

    Route::get('edit/{id}', 'edit')->name('admin.leads.edit');

    Route::put('edit/{id}', 'update')->name('admin.leads.update');

    Route::put('attributes/edit/{id}', 'updateAttributes')->name('admin.leads.attributes.update');

    Route::put('stage/edit/{id}', 'updateStage')->name('admin.leads.stage.update');

    Route::get('search', 'search')->name('admin.leads.search');

    Route::delete('{id}', 'destroy')->name('admin.leads.delete');

    Route::post('mass-update', 'massUpdate')->name('admin.leads.mass_update');

    Route::post('mass-destroy', 'massDestroy')->name('admin.leads.mass_delete');

    Route::get('get/{pipeline_id?}', 'get')->name('admin.leads.get');

    Route::delete('product/{lead_id}', 'removeProduct')->name('admin.leads.product.remove');

    Route::put('product/{lead_id}', 'addProduct')->name('admin.leads.product.add');

    Route::get('kanban/look-up', [LeadController::class, 'kanbanLookup'])->name('admin.leads.kanban.look_up');

    Route::controller(ActivityController::class)->prefix('{id}/activities')->group(function () {
        Route::get('', 'index')->name('admin.leads.activities.index');
    });

    Route::controller(TagController::class)->prefix('{id}/tags')->group(function () {
        Route::post('', 'attach')->name('admin.leads.tags.attach');

        Route::delete('', 'detach')->name('admin.leads.tags.detach');
    });

    Route::controller(EmailController::class)->prefix('{id}/emails')->group(function () {
        Route::post('', 'store')->name('admin.leads.emails.store');

        Route::delete('', 'detach')->name('admin.leads.emails.detach');
    });

    Route::controller(QuoteController::class)->prefix('quotes')->group(function () {
        Route::post('{quote_id}/mail', 'mail')->name('admin.leads.quotes.mail');

        Route::delete('{quote_id}', 'delete')->name('admin.leads.quotes.delete');
    });
});

```

## packages/Webkul/WebForm/src/Http/Controllers/WebFormController.php

```php
<?php

namespace Webkul\WebForm\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\PipelineRepository;
use Webkul\Lead\Repositories\SourceRepository;
use Webkul\Lead\Repositories\TypeRepository;
use Webkul\WebForm\Http\Requests\WebForm;
use Webkul\WebForm\Repositories\WebFormRepository;

class WebFormController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected WebFormRepository $webFormRepository,
        protected PersonRepository $personRepository,
        protected LeadRepository $leadRepository,
        protected PipelineRepository $pipelineRepository,
        protected SourceRepository $sourceRepository,
        protected TypeRepository $typeRepository,
    ) {}

    /**
     * Remove the specified email template from storage.
     */
    public function formJS(string $formId): Response
    {
        $webForm = $this->webFormRepository->findOneByField('form_id', $formId);

        return response()->view('web_form::settings.web-forms.embed', compact('webForm'))
            ->header('Content-Type', 'text/javascript');
    }

    /**
     * The attributes the admin placed on this form, keyed by entity type, as the codes a submission
     * is allowed to write.
     *
     * The codes in `$restrictedWebFormAttributeCodes` can never be exposed on a form, so they are
     * already absent from this relation; the controller sets those values itself.
     */
    protected function exposedAttributes($webForm): array
    {
        $exposed = ['leads' => [], 'persons' => []];

        foreach ($webForm->attributes as $webFormAttribute) {
            $attribute = $webFormAttribute->attribute;

            if (! $attribute) {
                continue;
            }

            if (! array_key_exists($attribute->entity_type, $exposed)) {
                continue;
            }

            $exposed[$attribute->entity_type][] = $attribute->code;
        }

        return $exposed;
    }

    /**
     * Reduce the `leads` and `persons` payloads to the codes this form exposes.
     *
     * The form request derives its rules from the request keys, so trimming the request here also
     * stops an unexposed attribute from pulling in a validation rule of its own — including the
     * uniqueness check that otherwise answers whether a given email or phone is already a contact.
     */
    protected function restrictSubmissionToExposedAttributes(array $exposedAttributes): void
    {
        foreach ($exposedAttributes as $entityType => $codes) {
            $submitted = request($entityType);

            if (! is_array($submitted)) {
                continue;
            }

            request()->request->set($entityType, Arr::only($submitted, $codes));
        }
    }

    /**
     * Resolve the contact a submission belongs to by its email address.
     *
     * The address is matched exactly. A `LIKE '%...%'` match let a submitter hand over a bare SQL
     * wildcard (or any substring) and be merged into an unrelated contact's record, and made the
     * endpoint an oracle for which addresses exist.
     */
    protected function findPersonBySubmittedEmail()
    {
        $email = request('persons.emails.0.value');

        if (
            ! is_string($email)
            || ! filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            return null;
        }

        /**
         * Matched without regard to case: an address is the same address however it is typed, and
         * `whereJsonContains` compares the stored json byte for byte. A submission differing only
         * in case therefore found nobody and went on to create a second contact for the same
         * person — which the unique id built from that address then rejected outright.
         *
         * The comparison is narrowed by the json search first so the lower-casing is applied to
         * the candidates rather than to every contact in the table.
         */
        $needle = mb_strtolower($email);

        /**
         * The candidates are narrowed in the database and then compared in PHP.
         *
         * The comparison is not left to SQL because the obvious way to write it, `JSON_SEARCH`,
         * takes its argument as a LIKE pattern: an address containing `%` would match every
         * contact sharing the rest of it, letting an unauthenticated caller merge their submission
         * into someone else's record and learn that the record exists. Comparing the decoded
         * values here keeps the match exact, whatever the address contains.
         */
        return $this->personRepository
            ->getModel()
            ->whereRaw('LOWER(CAST(emails AS CHAR)) LIKE ?', ['%'.addcslashes($needle, '%_\\').'%'])
            ->get()
            ->first(fn ($person) => collect($person->emails)->contains(
                fn ($personEmail) => mb_strtolower($personEmail['value'] ?? '') === $needle
            ));
    }

    /**
     * Remove the specified email template from storage.
     */
    public function formStore(int $id): JsonResponse
    {
        $webForm = $this->webFormRepository->findOrFail($id);

        /**
         * A submission may only write the attributes the admin actually placed on this form. The
         * request body is attacker-controlled on this public route, so anything outside that
         * allow-list — `user_id`, `status`, `lost_reason`, `closed_at`, a `persons[id]` pointing at
         * someone else's contact, or an unexposed custom attribute — is dropped before it reaches
         * the repositories.
         */
        $exposedAttributes = $this->exposedAttributes($webForm);

        $this->restrictSubmissionToExposedAttributes($exposedAttributes);

        request()->query->remove('email_id');

        request()->request->remove('email_id');

        $person = $this->findPersonBySubmittedEmail();

        if ($person) {
            request()->request->add(['persons' => array_merge(request('persons') ?? [], ['id' => $person->id])]);
        }

        app(WebForm::class);

        if ($webForm->create_lead) {
            request()->request->add(['entity_type' => 'leads']);

            Event::dispatch('lead.create.before');

            $data = request('leads');

            $data['entity_type'] = 'leads';

            $data['person'] = request('persons');

            $data['status'] = 1;

            /**
             * The pipeline is configured on the web form by the admin, so the default pipeline is
             * only used when the web form does not point to one.
             */
            $pipeline = $webForm->lead_pipeline_id
                ? $this->pipelineRepository->find($webForm->lead_pipeline_id)
                : null;

            if (! $pipeline) {
                $pipeline = $this->pipelineRepository->getDefaultPipeline();
            }

            $stage = $pipeline->stages()->first();

            $data['lead_pipeline_id'] = $pipeline->id;

            $data['lead_pipeline_stage_id'] = $stage->id;

            $data['title'] = request('leads.title') ?: 'Lead From Web Form';

            $data['lead_value'] = request('leads.lead_value') ?: 0;

            if (! request('leads.lead_source_id')) {
                $source = $this->sourceRepository->findOneByField('name', 'Web Form');

                if (! $source) {
                    $source = $this->sourceRepository->first();
                }

                $data['lead_source_id'] = $source->id;
            }

            $data['lead_type_id'] = request('leads.lead_type_id') ?: $this->typeRepository->first()->id;

            $lead = $this->leadRepository->create($data);

            Event::dispatch('lead.create.after', $lead);
        } else {
            if (! $person) {
                Event::dispatch('contacts.person.create.before');

                $data = request('persons');

                request()->request->add(['entity_type' => 'persons']);

                $data['entity_type'] = 'persons';

                $person = $this->personRepository->create($data);

                Event::dispatch('contacts.person.create.after', $person);
            }
        }

        if ($webForm->submit_success_action == 'message') {
            return response()->json([
                'message' => $webForm->submit_success_content,
            ], 200);
        } else {
            return response()->json([
                'redirect' => $webForm->submit_success_content,
            ], 301);
        }
    }

    /**
     * Remove the specified email template from storage.
     */
    public function preview(string $id): View
    {
        $webForm = $this->webFormRepository->findOneByField('form_id', $id);

        if (is_null($webForm)) {
            abort(404);
        }

        return view('web_form::settings.web-forms.preview', compact('webForm'));
    }

    /**
     * Preview the web form from datagrid.
     */
    public function view(int $id): View
    {
        $webForm = $this->webFormRepository->findOneByField('id', $id);

        request()->merge(['id' => $webForm->form_id]);

        if (is_null($webForm)) {
            abort(404);
        }

        return view('web_form::settings.web-forms.preview', compact('webForm'));
    }

    /**
     * Find the person having exactly the given email, ignoring the case.
     *
     * The query only narrows down the candidates, the exact comparison is done on the decoded
     * emails so a part of an address (or a domain) never matches somebody else's contact.
     */
    protected function findPersonByEmail(string $email)
    {
        $email = mb_strtolower(trim($email));

        return $this->personRepository
            ->getModel()
            ->whereRaw('LOWER(emails) LIKE ?', ['%'.addcslashes($email, '\\%_').'%'])
            ->get()
            ->first(fn ($person) => collect($person->emails)->contains(
                fn ($personEmail) => mb_strtolower(trim($personEmail['value'] ?? '')) === $email
            ));
    }
}

```

## packages/Webkul/WebForm/src/Http/Requests/WebForm.php

```php
<?php

namespace Webkul\WebForm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Repositories\AttributeValueRepository;
use Webkul\Core\Contracts\Validations\Decimal;
use Webkul\WebForm\Rules\PhoneNumber;

class WebForm extends FormRequest
{
    /**
     * @var array
     */
    protected $rules = [];

    /**
     * Create a new form request instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected AttributeValueRepository $attributeValueRepository
    ) {}

    /**
     * Determine if the product is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        foreach (['leads', 'persons'] as $key => $entityType) {
            $attributes = $this->attributeRepository->scopeQuery(function ($query) use ($entityType) {
                $attributeCodes = $entityType == 'persons'
                    ? array_keys(request('persons') ?? [])
                    : array_keys(request('leads') ?? []);

                $query = $query->whereIn('code', $attributeCodes)
                    ->where('entity_type', $entityType);

                return $query;
            })->get();

            foreach ($attributes as $attribute) {
                $attribute->code = $entityType.'.'.$attribute->code;

                $validations = [];

                if ($attribute->type == 'boolean') {
                    continue;
                } elseif ($attribute->type == 'address') {
                    if (! $attribute->is_required) {
                        continue;
                    }

                    $validations = [
                        $attribute->code.'.address' => 'required',
                        $attribute->code.'.country' => 'required',
                        $attribute->code.'.state' => 'required',
                        $attribute->code.'.city' => 'required',
                        $attribute->code.'.postcode' => 'required',
                    ];
                } elseif ($attribute->type == 'email') {
                    $validations = [
                        $attribute->code => [$attribute->is_required ? 'required' : 'nullable'],
                        $attribute->code.'.*.value' => [$attribute->is_required ? 'required' : 'nullable', 'email'],
                        $attribute->code.'.*.label' => $attribute->is_required ? 'required' : 'nullable',
                    ];
                } elseif ($attribute->type == 'phone') {
                    $validations = [
                        $attribute->code => [$attribute->is_required ? 'required' : 'nullable'],
                        $attribute->code.'.*.value' => [$attribute->is_required ? 'required' : 'nullable', new PhoneNumber],
                        $attribute->code.'.*.label' => $attribute->is_required ? 'required' : 'nullable',
                    ];
                } elseif (in_array($attribute->type, ['file', 'image'], true)) {
                    $validations[$attribute->code] = [
                        $attribute->is_required ? 'required' : 'nullable',
                        'file',
                        'mimes:'.($attribute->type === 'image'
                            ? 'bmp,gif,jpeg,jpg,png,svg,webp'
                            : 'bmp,gif,jpeg,jpg,png,svg,webp,pdf,doc,docx,xls,xlsx,csv,txt'),
                        'max:2048',
                    ];
                } else {
                    $validations[$attribute->code] = [$attribute->is_required ? 'required' : 'nullable'];

                    if ($attribute->type == 'text' && $attribute->validation) {
                        array_push($validations[$attribute->code],
                            $attribute->validation == 'decimal'
                            ? new Decimal
                            : $attribute->validation
                        );
                    }

                    if ($attribute->type == 'price') {
                        array_push($validations[$attribute->code], new Decimal);
                    }
                }

                /**
                 * Uniqueness is deliberately not enforced on this route.
                 *
                 * The submission is unauthenticated and unthrottled, so a rule that answers "this
                 * value already exists" turns the endpoint into an oracle: a caller can walk a list
                 * of addresses or phone numbers and learn which ones belong to the CRM's contacts.
                 * A repeat value here is a returning visitor rather than an error — the controller
                 * resolves them by email and merges the submission into that contact — so the check
                 * is dropped instead of being reported. The admin forms keep it.
                 */
                $this->rules = array_merge($this->rules, $validations);
            }
        }

        /**
         * A submission has to carry a contact.
         *
         * The rules above are derived from the fields the payload names, so an empty body produced
         * no rules at all: it passed validation untouched and failed instead on the database, as a
         * server error, while trying to store a contact with nothing in it.
         *
         * Only the contact itself is required. A form may collect a phone number and no address,
         * so an email is demanded only once the payload offers one — and the loop above has
         * already given it the `email` rule in that case.
         */
        $this->rules['persons'] = ['required', 'array'];

        if (blank(request('persons.emails.0.value')) && blank(request('persons.contact_numbers.0.value'))) {
            $this->rules['persons.emails.0.value'] = ['required', 'email'];
        }

        return $this->rules;
    }
}

```

## packages/Webkul/Lead/src/Repositories/LeadRepository.php

```php
<?php

namespace Webkul\Lead\Repositories;

use Carbon\Carbon;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Repositories\AttributeValueRepository;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Core\Eloquent\Repository;
use Webkul\Lead\Contracts\Lead;

class LeadRepository extends Repository
{
    /**
     * Searchable fields.
     */
    protected $fieldSearchable = [
        'title',
        'lead_value',
        'status',
        'user_id',
        'user.name',
        'person_id',
        'person.name',
        'lead_source_id',
        'lead_type_id',
        'lead_pipeline_id',
        'lead_pipeline_stage_id',
        'created_at',
        'closed_at',
        'expected_close_date',
    ];

    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        protected StageRepository $stageRepository,
        protected PersonRepository $personRepository,
        protected ProductRepository $productRepository,
        protected AttributeRepository $attributeRepository,
        protected AttributeValueRepository $attributeValueRepository,
        Container $container
    ) {
        parent::__construct($container);
    }

    /**
     * Specify model class name.
     *
     * @return mixed
     */
    public function model()
    {
        return Lead::class;
    }

    /**
     * Get leads query.
     *
     * @param  int  $pipelineId
     * @param  int  $pipelineStageId
     * @param  string  $term
     * @param  string  $createdAtRange
     * @return mixed
     */
    public function getLeadsQuery($pipelineId, $pipelineStageId, $term, $createdAtRange)
    {
        return $this->with([
            'attribute_values',
            'pipeline',
            'stage',
        ])->scopeQuery(function ($query) use ($pipelineId, $pipelineStageId, $term, $createdAtRange) {
            return $query->select(
                'leads.id as id',
                'leads.created_at as created_at',
                'title',
                'lead_value',
                'persons.name as person_name',
                'leads.person_id as person_id',
                'lead_pipelines.id as lead_pipeline_id',
                'lead_pipeline_stages.name as status',
                'lead_pipeline_stages.id as lead_pipeline_stage_id'
            )
                ->addSelect(DB::raw('DATEDIFF('.DB::getTablePrefix().'leads.created_at + INTERVAL lead_pipelines.rotten_days DAY, now()) as rotten_days'))
                ->leftJoin('persons', 'leads.person_id', '=', 'persons.id')
                ->leftJoin('lead_pipelines', 'leads.lead_pipeline_id', '=', 'lead_pipelines.id')
                ->leftJoin('lead_pipeline_stages', 'leads.lead_pipeline_stage_id', '=', 'lead_pipeline_stages.id')
                ->where('title', 'like', "%$term%")
                ->where('leads.lead_pipeline_id', $pipelineId)
                ->where('leads.lead_pipeline_stage_id', $pipelineStageId)
                ->when($createdAtRange, function ($query) use ($createdAtRange) {
                    return $query->whereBetween('leads.created_at', $createdAtRange);
                })
                ->where(function ($query) {
                    if ($userIds = bouncer()->getAuthorizedUserIds()) {
                        $query->whereIn('leads.user_id', $userIds);
                    }
                });
        });
    }

    /**
     * Create.
     *
     * @return Lead
     */
    public function create(array $data)
    {
        /**
         * If a person is provided, create or update the person and set the `person_id`.
         */
        if (isset($data['person'])) {
            if (! empty($data['person']['id'])) {
                $person = $this->personRepository->findOrFail($data['person']['id']);
            } else {
                /**
                 * Assign the person to the lead owner (falling back to the current user) so that the
                 * person is not created with a null `user_id`, which would otherwise hide it from the
                 * person listing for users restricted to group/individual data scope.
                 */
                $person = $this->personRepository->create(array_merge($data['person'], [
                    'entity_type' => 'persons',
                    'user_id' => $data['user_id'] ?? auth()->guard('user')->id(),
                ]));
            }

            $data['person_id'] = $person->id;
        }

        if (empty($data['expected_close_date'])) {
            $data['expected_close_date'] = null;
        }

        $lead = parent::create(array_merge([
            'lead_pipeline_id' => 1,
            'lead_pipeline_stage_id' => 1,
        ], $data));

        $this->attributeValueRepository->save(array_merge($data, [
            'entity_id' => $lead->id,
        ]));

        if (isset($data['products'])) {
            foreach ($data['products'] as $product) {
                $this->productRepository->create(array_merge($product, [
                    'lead_id' => $lead->id,
                    'amount' => $product['price'] * $product['quantity'],
                ]));
            }
        }

        return $lead;
    }

    /**
     * Update.
     *
     * @param  int  $id
     * @param  array|Collection  $attributes
     * @return Lead
     */
    public function update(array $data, $id, $attributes = [])
    {
        /**
         * If a person is provided, create or update the person and set the `person_id`.
         * Be cautious, as a lead can be updated without providing person data.
         * For example, in the lead Kanban section, when switching stages, only the stage will be updated.
         */
        if (isset($data['person'])) {
            if (! empty($data['person']['id'])) {
                $person = $this->personRepository->findOrFail($data['person']['id']);
            } else {
                /**
                 * Assign the person to the lead owner (falling back to the current user) so that the
                 * person is not created with a null `user_id`, which would otherwise hide it from the
                 * person listing for users restricted to group/individual data scope.
                 */
                $person = $this->personRepository->create(array_merge($data['person'], [
                    'entity_type' => 'persons',
                    'user_id' => $data['user_id'] ?? auth()->guard('user')->id(),
                ]));
            }

            $data['person_id'] = $person->id;
        }

        if (isset($data['lead_pipeline_stage_id'])) {
            $stage = $this->stageRepository->find($data['lead_pipeline_stage_id']);

            if (in_array($stage->code, ['won', 'lost'])) {
                $data['closed_at'] = $data['closed_at'] ?? Carbon::now();
            } else {
                $data['closed_at'] = null;
            }
        }

        if (array_key_exists('expected_close_date', $data) && empty($data['expected_close_date'])) {
            $data['expected_close_date'] = null;
        }

        $lead = parent::update($data, $id);

        /**
         * If attributes are provided, only save the provided attributes and return.
         * A collection of attributes may also be provided, which will be treated as valid,
         * regardless of whether it is empty or not.
         */
        if (! empty($attributes)) {
            /**
             * If attributes are provided as an array, then fetch the attributes from the database;
             * otherwise, use the provided collection of attributes.
             */
            if (is_array($attributes)) {
                $conditions = ['entity_type' => $data['entity_type']];

                if (isset($data['quick_add'])) {
                    $conditions['quick_add'] = 1;
                }

                $attributes = $this->attributeRepository->where($conditions)
                    ->whereIn('code', $attributes)
                    ->get();
            }

            $this->attributeValueRepository->save(array_merge($data, [
                'entity_id' => $lead->id,
            ]), $attributes);

            return $lead;
        }

        $this->attributeValueRepository->save(array_merge($data, [
            'entity_id' => $lead->id,
        ]));

        $previousProductIds = $lead->products()->pluck('id');

        if (isset($data['products'])) {
            foreach ($data['products'] as $productId => $productInputs) {
                if (Str::contains($productId, 'product_')) {
                    $this->productRepository->create(array_merge([
                        'lead_id' => $lead->id,
                    ], $productInputs));
                } else {
                    if (is_numeric($index = $previousProductIds->search($productId))) {
                        $previousProductIds->forget($index);
                    }

                    $this->productRepository->update($productInputs, $productId);
                }
            }
        }

        foreach ($previousProductIds as $productId) {
            $this->productRepository->delete($productId);
        }

        return $lead;
    }
}

```

## packages/Webkul/Contact/src/Repositories/PersonRepository.php

```php
<?php

namespace Webkul\Contact\Repositories;

use Illuminate\Container\Container;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Repositories\AttributeValueRepository;
use Webkul\Contact\Contracts\Person;
use Webkul\Core\Eloquent\Repository;

class PersonRepository extends Repository
{
    /**
     * Searchable fields.
     */
    protected $fieldSearchable = [
        'name',
        'emails',
        'contact_numbers',
        'organization_id',
        'job_title',
        'organization.name',
        'user_id',
        'user.name',
    ];

    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected AttributeValueRepository $attributeValueRepository,
        protected OrganizationRepository $organizationRepository,
        Container $container
    ) {
        parent::__construct($container);
    }

    /**
     * Specify model class name.
     *
     * @return mixed
     */
    public function model()
    {
        return Person::class;
    }

    /**
     * Create.
     *
     * @return Person
     */
    public function create(array $data)
    {
        $data = $this->sanitizeRequestedPersonData($data);

        if (! empty($data['organization_name'])) {
            $organization = $this->fetchOrCreateOrganizationByName($data['organization_name']);

            $data['organization_id'] = $organization->id;
        }

        if (isset($data['user_id'])) {
            $data['user_id'] = $data['user_id'] ?: null;
        }

        $person = parent::create($data);

        $this->attributeValueRepository->save(array_merge($data, [
            'entity_id' => $person->id,
        ]));

        return $person;
    }

    /**
     * Update.
     *
     * @return Person
     */
    public function update(array $data, $id, $attributes = [])
    {
        $data = $this->sanitizeRequestedPersonData($data);

        $data['user_id'] = empty($data['user_id']) ? null : $data['user_id'];

        if (! empty($data['organization_name'])) {
            $organization = $this->fetchOrCreateOrganizationByName($data['organization_name']);

            $data['organization_id'] = $organization->id;

            unset($data['organization_name']);
        }

        $person = parent::update($data, $id);

        /**
         * If attributes are provided then only save the provided attributes and return.
         */
        if (! empty($attributes)) {
            $conditions = ['entity_type' => $data['entity_type']];

            if (isset($data['quick_add'])) {
                $conditions['quick_add'] = 1;
            }

            $attributes = $this->attributeRepository->where($conditions)
                ->whereIn('code', $attributes)
                ->get();

            $this->attributeValueRepository->save(array_merge($data, [
                'entity_id' => $person->id,
            ]), $attributes);

            return $person;
        }

        $this->attributeValueRepository->save(array_merge($data, [
            'entity_id' => $person->id,
        ]));

        return $person;
    }

    /**
     * Retrieves customers count based on date.
     *
     * @return int
     */
    public function getCustomerCount($startDate, $endDate)
    {
        return $this
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->count();
    }

    /**
     * Fetch or create an organization.
     */
    public function fetchOrCreateOrganizationByName(string $organizationName)
    {
        $organization = $this->organizationRepository->findOneWhere([
            'name' => $organizationName,
        ]);

        return $organization ?: $this->organizationRepository->create([
            'entity_type' => 'organizations',
            'name' => $organizationName,
        ]);
    }

    /**
     * Sanitize requested person data and return the clean array.
     */
    private function sanitizeRequestedPersonData(array $data): array
    {
        if (
            array_key_exists('organization_id', $data)
            && empty($data['organization_id'])
        ) {
            $data['organization_id'] = null;
        }

        $uniqueIdParts = array_filter([
            $data['user_id'] ?? null,
            $data['organization_id'] ?? null,
            $data['emails'][0]['value'] ?? null,
        ]);

        $data['unique_id'] = implode('|', $uniqueIdParts);

        if (isset($data['contact_numbers'])) {
            $data['contact_numbers'] = collect($data['contact_numbers'])
                ->filter(fn ($number) => ! is_null($number['value'] ?? null))
                ->values()
                ->toArray();

            if (! empty($data['contact_numbers'])) {
                $data['unique_id'] .= '|'.$data['contact_numbers'][0]['value'];
            }
        }

        return $data;
    }
}

```

## packages/Webkul/User/src/Models/User.php

```php
<?php

namespace Webkul\User\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Webkul\User\Contracts\User as UserContract;

class User extends Authenticatable implements UserContract
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'image',
        'password',
        'api_token',
        'role_id',
        'status',
        'view_permission',
        'created_by',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'api_token',
        'remember_token',
    ];

    /**
     * Get image url for the product image.
     */
    public function image_url()
    {
        if (! $this->image) {
            return;
        }

        return Storage::url($this->image);
    }

    /**
     * Get image url for the product image.
     */
    public function getImageUrlAttribute()
    {
        return $this->image_url();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        $array['image_url'] = $this->image_url;

        return $array;
    }

    /**
     * Get the role that owns the user.
     */
    public function role()
    {
        return $this->belongsTo(RoleProxy::modelClass());
    }

    /**
     * The groups that belong to the user.
     */
    public function groups()
    {
        return $this->belongsToMany(GroupProxy::modelClass(), 'user_groups');
    }

    /**
     * Checks if user has permission to perform certain action.
     *
     * @param  string  $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        if ($this->role->permission_type == 'custom' && ! $this->role->permissions) {
            return false;
        }

        return in_array($permission, $this->role->permissions);
    }
}

```

## packages/Webkul/User/src/Models/Role.php

```php
<?php

namespace Webkul\User\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Contracts\Role as RoleContract;

class Role extends Model implements RoleContract
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'permission_type',
        'permissions',
        'created_by',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Get the users.
     */
    public function users()
    {
        return $this->hasMany(UserProxy::modelClass());
    }
}

```

## packages/Webkul/Admin/src/Http/Middleware/Bouncer.php

```php
<?php

namespace Webkul\Admin\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class Bouncer
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, \Closure $next, $guard = 'user')
    {
        if (! auth()->guard($guard)->check()) {
            return redirect()->route('admin.session.create');
        }

        /**
         * If user status is changed by admin. Then session should be
         * logged out.
         */
        if (! (bool) auth()->guard($guard)->user()->status) {
            $this->terminateSession($guard);

            return redirect()->route('admin.session.create');
        }

        /**
         * If somehow the user deleted all permissions, then it should be
         * auto logged out and need to contact the administrator again.
         */
        if ($this->isPermissionsEmpty()) {
            $this->terminateSession($guard);

            return redirect()->route('admin.session.create');
        }

        return $next($request);
    }

    /**
     * Log the user out and tear the session down, then flash the reason.
     *
     * The session is invalidated and its token rotated so nothing the revoked
     * session held survives; the message is flashed afterwards because
     * invalidating would otherwise discard it.
     */
    protected function terminateSession($guard): void
    {
        auth()->guard($guard)->logout();

        session()->invalidate();

        session()->regenerateToken();

        session()->flash('error', trans('admin::app.errors.401'));
    }

    /**
     * Check for user, if they have empty permissions or not except admin.
     *
     * @return bool
     */
    public function isPermissionsEmpty()
    {
        if (! $role = auth()->guard('user')->user()->role) {
            abort(401, 'This action is unauthorized.');
        }

        if ($role->permission_type === 'all') {
            return false;
        }

        if ($role->permission_type !== 'all' && empty($role->permissions)) {
            return true;
        }

        $this->checkIfAuthorized();

        return false;
    }

    /**
     * Check authorization.
     *
     * @return void
     */
    public function checkIfAuthorized()
    {
        $routeName = Route::currentRouteName();

        if (! $routeName) {
            return;
        }

        $roles = acl()->getRoles();

        /**
         * Exact ACL mapping.
         */
        if (isset($roles[$routeName])) {
            bouncer()->allow($roles[$routeName]);

            return;
        }

        /**
         * Public and self-service routes that any authenticated user may reach.
         */
        if ($this->isAllowlistedRoute($routeName)) {
            return;
        }

        /**
         * Fail closed: authorize against the nearest mapped ancestor feature, otherwise deny.
         */
        if (! $this->isAuthorizedByFeature($routeName, $roles)) {
            abort(401, 'This action is unauthorized.');
        }
    }

    /**
     * Routes every authenticated user may reach regardless of their role — authentication,
     * self-service account management, and generic UI helpers exposing no privileged data.
     */
    protected function isAllowlistedRoute(string $routeName): bool
    {
        return in_array($routeName, [
            'admin.session.create',
            'admin.session.store',
            'admin.session.destroy',
            'admin.forgot_password.create',
            'admin.forgot_password.store',
            'admin.reset_password.create',
            'admin.reset_password.store',
            'admin.user.account.edit',
            'admin.user.account.update',

            /**
             * Every user has notifications, whatever their role, and each request is scoped to the
             * acting user's own id inside the controller rather than by a permission.
             */
            'admin.notifications.index',
            'admin.notifications.read',
            'admin.notifications.read_all',
            'admin.datagrid.look_up',
            'admin.datagrid.saved_filters.index',
            'admin.datagrid.saved_filters.store',
            'admin.datagrid.saved_filters.update',
            'admin.datagrid.saved_filters.destroy',
            'admin.tinymce.upload',
            'admin.settings.web_forms.form_js',
            'admin.settings.web_forms.form_store',
            'admin.settings.web_forms.preview',
            'admin.mail.inbound_parse',
        ], true);
    }

    /**
     * Authorize an unmapped route against the permission of its nearest mapped ancestor feature.
     * Walks up the dotted route name until a mapped feature is found, then requires the user to hold
     * at least one permission within that feature.
     */
    protected function isAuthorizedByFeature(string $routeName, $roles): bool
    {
        $segments = explode('.', $routeName);

        while (count($segments) > 2) {
            array_pop($segments);

            $prefix = implode('.', $segments).'.';

            $featureKeys = collect($roles)
                ->filter(fn ($key, $route) => str_starts_with($route, $prefix))
                ->values();

            if ($featureKeys->isEmpty()) {
                continue;
            }

            $user = auth()->guard('user')->user();

            return $featureKeys->contains(fn ($key) => $user->hasPermission($key));
        }

        return false;
    }
}

```

## packages/Webkul/Admin/src/Providers/AdminServiceProvider.php

```php
<?php

namespace Webkul\Admin\Providers;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Admin\Bouncer;
use Webkul\Admin\Exceptions\Handler;
use Webkul\Admin\Http\Middleware\Bouncer as BouncerMiddleware;
use Webkul\Admin\Http\Middleware\Locale;
use Webkul\Admin\Http\Middleware\SanitizeUrl;
use Webkul\Admin\Http\Middleware\VerifyInboundEmailToken;
use Webkul\Contact\Models\Organization;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Product\Models\Product;
use Webkul\Quote\Models\Quote;
use Webkul\Warehouse\Models\Warehouse;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(Router $router): void
    {
        $router->aliasMiddleware('user', BouncerMiddleware::class);

        $router->aliasMiddleware('admin_locale', Locale::class);

        $router->aliasMiddleware('sanitize_url', SanitizeUrl::class);

        $router->aliasMiddleware('inbound_email_token', VerifyInboundEmailToken::class);

        include __DIR__.'/../Http/helpers.php';

        Route::middleware(['web', 'admin_locale', 'user'])
            ->prefix(config('app.admin_path'))
            ->group(__DIR__.'/../Routes/Admin/web.php');

        Route::middleware(['web', 'admin_locale'])
            ->group(__DIR__.'/../Routes/Front/web.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'admin');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'admin');

        Blade::anonymousComponentPath(__DIR__.'/../Resources/views/components', 'admin');

        $this->app->bind(ExceptionHandler::class, Handler::class);

        Relation::morphMap([
            'leads' => Lead::class,
            'organizations' => Organization::class,
            'persons' => Person::class,
            'products' => Product::class,
            'quotes' => Quote::class,
            'warehouses' => Warehouse::class,
        ]);

        $this->app->register(EventServiceProvider::class);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerFacades();

        $this->registerConfig();
    }

    /**
     * Register Bouncer as a singleton.
     */
    protected function registerFacades(): void
    {
        $loader = AliasLoader::getInstance();

        $loader->alias('Bouncer', \Webkul\Admin\Facades\Bouncer::class);

        $this->app->singleton('bouncer', function () {
            return new Bouncer;
        });
    }

    /**
     * Register package config.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/core_config.php', 'core_config');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/attribute_lookups.php', 'attribute_lookups');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/attribute_entity_types.php', 'attribute_entity_types');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/support.php', 'support');
    }
}

```

## packages/Webkul/WebForm/src/Routes/routes.php

```php
<?php

use Illuminate\Support\Facades\Route;
use Webkul\WebForm\Http\Controllers\WebFormController;

Route::controller(WebFormController::class)->middleware(['web', 'admin_locale'])->prefix('web-forms')->group(function () {
    Route::get('forms/{id}/form.js', 'formJS')->name('admin.settings.web_forms.form_js');

    Route::get('forms/{id}/form.html', 'preview')->name('admin.settings.web_forms.preview');

    Route::post('forms/{id}', 'formStore')->name('admin.settings.web_forms.form_store');

    Route::group(['middleware' => ['user']], function () {
        Route::get('form/{id}/form.html', 'view')->name('admin.settings.web_forms.view');
    });
});

```

## config/auth.php

```php
<?php

use Webkul\User\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option controls the default authentication "guard" and password
    | reset options for your application. You may change these defaults
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => 'user',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | here which uses session storage and the Eloquent user provider.
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | Supported: "session", "token"
    |
    */

    'guards' => [
        'user' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | If you have multiple user tables or models you may configure multiple
    | sources which represent each model / table. These sources may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => User::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | You may specify multiple password reset configurations if you have more
    | than one user table or model in the application and you want to have
    | separate password reset settings based on the specific user types.
    |
    | The expire time is the number of minutes that the reset token should be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'user_password_resets',
            'expire' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the amount of seconds before a password confirmation
    | times out and the user is prompted to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => 10800,

];

```

## config/app.php

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
     */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
     */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
     */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
     */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Admin URL
    |--------------------------------------------------------------------------
    |
    | This URL suffix is used to define the admin url for example
    | admin/ or backend/
    |
     */

    'admin_path' => env('APP_ADMIN_PATH', 'admin'),

    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
     */

    'timezone' => env('APP_TIMEZONE', 'Asia/Kolkata'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
     */

    'locale' => env('APP_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Available Locales Configuration
    |--------------------------------------------------------------------------
    |
    | The application available locale determines the supported locales
    | by application
    |
     */

    'available_locales' => [
        'ar' => 'Arabic',
        'en' => 'English',
        'es' => 'Español',
        'fa' => 'Persian',
        'ja' => '日本語',
        'ko' => '한국어',
        'pt_BR' => 'Portuguese',
        'tr' => 'Türkçe',
        'vi' => 'Vietnamese',
        'zh_CN' => '简体中文',
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
     */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
     */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Base Currency Code
    |--------------------------------------------------------------------------
    |
    | Here you may specify the base currency code for your application.
    |
     */

    'currency' => env('APP_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
     */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
     */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],
];

```

## config/session.php

```php
<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Session Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default session "driver" that will be used on
    | requests. By default, we will use the lightweight native driver but
    | you may specify any of the other wonderful drivers provided here.
    |
    | Supported: "file", "cookie", "database", "apc",
    |            "memcached", "redis", "dynamodb", "array"
    |
    */

    'driver' => env('SESSION_DRIVER', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of minutes that you wish the session
    | to be allowed to remain idle before it expires. If you want them
    | to immediately expire on the browser closing, set that option.
    |
    */

    'lifetime' => env('SESSION_LIFETIME', 120),

    'expire_on_close' => false,

    /*
    |--------------------------------------------------------------------------
    | Session Encryption
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify that all of your session data
    | should be encrypted before it is stored. All encryption will be run
    | automatically by Laravel and you can use the Session like normal.
    |
    */

    'encrypt' => false,

    /*
    |--------------------------------------------------------------------------
    | Session File Location
    |--------------------------------------------------------------------------
    |
    | When using the native session driver, we need a location where session
    | files may be stored. A default has been set for you but a different
    | location may be specified. This is only needed for file sessions.
    |
    */

    'files' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Connection
    |--------------------------------------------------------------------------
    |
    | When using the "database" or "redis" session drivers, you may specify a
    | connection that should be used to manage these sessions. This should
    | correspond to a connection in your database configuration options.
    |
    */

    'connection' => env('SESSION_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | Session Database Table
    |--------------------------------------------------------------------------
    |
    | When using the "database" session driver, you may specify the table we
    | should use to manage the sessions. Of course, a sensible default is
    | provided for you; however, you are free to change this as needed.
    |
    */

    'table' => 'sessions',

    /*
    |--------------------------------------------------------------------------
    | Session Cache Store
    |--------------------------------------------------------------------------
    |
    | While using one of the framework's cache driven session backends you may
    | list a cache store that should be used for these sessions. This value
    | must match with one of the application's configured cache "stores".
    |
    | Affects: "apc", "dynamodb", "memcached", "redis"
    |
    */

    'store' => env('SESSION_STORE', null),

    /*
    |--------------------------------------------------------------------------
    | Session Sweeping Lottery
    |--------------------------------------------------------------------------
    |
    | Some session drivers must manually sweep their storage location to get
    | rid of old sessions from storage. Here are the chances that it will
    | happen on a given request. By default, the odds are 2 out of 100.
    |
    */

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Name
    |--------------------------------------------------------------------------
    |
    | Here you may change the name of the cookie used to identify a session
    | instance by ID. The name specified here will get used every time a
    | new session cookie is created by the framework for every driver.
    |
    */

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_session'
    ),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Path
    |--------------------------------------------------------------------------
    |
    | The session cookie path determines the path for which the cookie will
    | be regarded as available. Typically, this will be the root path of
    | your application but you are free to change this when necessary.
    |
    */

    'path' => '/',

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Domain
    |--------------------------------------------------------------------------
    |
    | Here you may change the domain of the cookie used to identify a session
    | in your application. This will determine which domains the cookie is
    | available to in your application. A sensible default has been set.
    |
    */

    'domain' => env('SESSION_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | HTTPS Only Cookies
    |--------------------------------------------------------------------------
    |
    | By setting this option to true, session cookies will only be sent back
    | to the server if the browser has a HTTPS connection. This will keep
    | the cookie from being sent to you if it can not be done securely.
    |
    */

    'secure' => env('SESSION_SECURE_COOKIE'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Access Only
    |--------------------------------------------------------------------------
    |
    | Setting this value to true will prevent JavaScript from accessing the
    | value of the cookie and the cookie will only be accessible through
    | the HTTP protocol. You are free to modify this option if needed.
    |
    */

    'http_only' => true,

    /*
    |--------------------------------------------------------------------------
    | Same-Site Cookies
    |--------------------------------------------------------------------------
    |
    | This option determines how your cookies behave when cross-site requests
    | take place, and can be used to mitigate CSRF attacks. By default, we
    | will set this value to "lax" since this is a secure default value.
    |
    | Supported: "lax", "strict", "none", null
    |
    */

    'same_site' => 'lax',

];

```

## composer.json

```php
{
    "name": "krayin/laravel-crm",
    "type": "project",
    "description": "Krayin CRM",
    "keywords": [
        "framework",
        "laravel"
    ],
    "license": "MIT",
    "require": {
        "php": "^8.3",
        "barryvdh/laravel-dompdf": "^3.1",
        "diglactic/laravel-breadcrumbs": "^10.0",
        "enshrined/svg-sanitize": "^1.0.0",
        "google/apiclient": "^2.18",
        "guzzlehttp/guzzle": "^7.8",
        "khaled.alshamaa/ar-php": "^7.0",
        "konekt/concord": "^1.17",
        "laravel/framework": "^12.0",
        "laravel/reverb": "^1.12",
        "laravel/sanctum": "^4.0",
        "laravel/tinker": "^2.10",
        "laravel/ui": "^4.6",
        "league/fractal": "^0.21.0",
        "maatwebsite/excel": "^3.1",
        "mpdf/mpdf": "^8.2",
        "phpoffice/phpspreadsheet": "^1.30.6",
        "prettus/l5-repository": "^3.0",
        "pusher/pusher-php-server": "^7.3",
        "smalot/pdfparser": "^2.11",
        "webklex/laravel-imap": "^6.0"
    },
    "require-dev": {
        "barryvdh/laravel-debugbar": "^3.14",
        "fakerphp/faker": "^1.23",
        "krayin/krayin-package-generator": "dev-master",
        "laravel/pint": "^1.18",
        "laravel/sail": "^1.38",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.0",
        "larastan/larastan": "^3.14",
        "pestphp/pest": "^3.0",
        "pestphp/pest-plugin-laravel": "^3.0",
        "phpstan/phpstan": "^2.3",
        "phpunit/phpunit": "^11.0",
        "rector/rector": "^2.8",
        "spatie/laravel-ignition": "^2.8"
    },
    "config": {
        "optimize-autoloader": true,
        "platform": {
            "php": "8.3.30"
        },
        "preferred-install": "dist",
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true
        }
    },
    "extra": {
        "laravel": {
            "dont-discover": []
        }
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/",
            "Webkul\\Activity\\": "packages/Webkul/Activity/src",
            "Webkul\\Admin\\": "packages/Webkul/Admin/src",
            "Webkul\\Attribute\\": "packages/Webkul/Attribute/src",
            "Webkul\\Contact\\": "packages/Webkul/Contact/src",
            "Webkul\\Core\\": "packages/Webkul/Core/src",
            "Webkul\\DataGrid\\": "packages/Webkul/DataGrid/src",
            "Webkul\\DataTransfer\\": "packages/Webkul/DataTransfer/src",
            "Webkul\\Email\\": "packages/Webkul/Email/src",
            "Webkul\\EmailTemplate\\": "packages/Webkul/EmailTemplate/src",
            "Webkul\\GoogleContact\\": "packages/Webkul/GoogleContact/src",
            "Webkul\\Marketing\\": "packages/Webkul/Marketing/src",
            "Webkul\\Installer\\": "packages/Webkul/Installer/src",
            "Webkul\\Lead\\": "packages/Webkul/Lead/src",
            "Webkul\\Product\\": "packages/Webkul/Product/src",
            "Webkul\\Quote\\": "packages/Webkul/Quote/src",
            "Webkul\\Tag\\": "packages/Webkul/Tag/src",
            "Webkul\\User\\": "packages/Webkul/User/src",
            "Webkul\\Warehouse\\": "packages/Webkul/Warehouse/src",
            "Webkul\\WebForm\\": "packages/Webkul/WebForm/src",
            "Webkul\\Automation\\": "packages/Webkul/Automation/src"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "repositories": [
        {
            "type": "path",
            "url": "packages/*/*",
            "options": {
                "symlink": true
            }
        }
    ],
    "minimum-stability": "stable",
    "prefer-stable": true,
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ],
        "post-root-package-install": [
            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
        ],
        "post-create-project-cmd": [
            "@php artisan key:generate --ansi"
        ]
    }
}

```

