<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$organization->name" subtitle="Client organization record and support actions.">
            <x-slot name="actions">
                <a href="{{ route('platform.clients.index') }}" class="btn-secondary">Back to clients</a>
                <form method="POST" action="{{ route('platform.clients.enter', $organization) }}">
                    @csrf
                    <button type="submit" class="btn-primary">Enter organization</button>
                </form>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="panel lg:col-span-1">
            <div class="panel-header">
                <h2 class="panel-title">Organization details</h2>
            </div>
            <div class="panel-body space-y-4">
                <div>
                    <div class="meta-label">Identifier</div>
                    <div class="meta-value">{{ $organization->slug }}</div>
                </div>
                <div>
                    <div class="meta-label">Parent company</div>
                    <div class="meta-value">{{ $organization->parent?->name ?? 'None' }}</div>
                </div>
                <div>
                    <div class="meta-label">Status</div>
                    <div class="meta-value">
                        <span @class(['badge', 'badge-success' => $organization->is_active, 'badge-muted' => ! $organization->is_active])>
                            {{ $organization->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
                <div>
                    <div class="meta-label">Client manager</div>
                    <div class="meta-value">{{ $organization->clientManager?->name ?? 'None assigned' }}</div>
                </div>
                @if ($organization->children->isNotEmpty())
                    <div>
                        <div class="meta-label">Subsidiaries</div>
                        <ul class="mt-1 list-disc space-y-1 pl-5 text-sm text-enterprise-700">
                            @foreach ($organization->children as $child)
                                <li>{{ $child->name }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        <div @class(['panel', 'lg:col-span-2' => ! $canManageClients, 'lg:col-span-1' => $canManageClients])>
            <div class="panel-header">
                <h2 class="panel-title">Assigned users</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th class="text-right">Support</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($organization->roleAssignments as $assignment)
                            <tr>
                                <td class="font-medium text-enterprise-900">{{ $assignment->user->name }}</td>
                                <td class="text-enterprise-600">{{ $assignment->user->email }}</td>
                                <td class="text-enterprise-600">{{ $assignment->role->name }}</td>
                                <td class="text-right space-x-3">
                                    @if ($canManageClients)
                                        <a href="{{ route('platform.clients.users.edit', [$organization, $assignment->user]) }}" class="link-action">Edit</a>
                                    @endif
                                    <form method="POST" action="{{ route('platform.impersonation.store', $assignment->user) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="link-action">Start support session</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-10 text-center text-enterprise-500">No users assigned to this organization.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($canManageClients)
            <div class="panel lg:col-span-1">
                <div class="panel-header">
                    <h2 class="panel-title">Add team member</h2>
                </div>
                <form method="POST" action="{{ route('platform.clients.users.store', $organization) }}" class="panel-body space-y-4">
                    @csrf
                    <p class="text-sm text-enterprise-600">
                        Provision additional administrators or employee accounts for this client.
                    </p>
                    <div>
                        <x-input-label for="name" value="Full name" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="email" value="Work email" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="password" value="Temporary password" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="role_id" value="Organization role" />
                        <select id="role_id" name="role_id" class="mt-1 block w-full">
                            @foreach ($organizationRoles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('role_id')" class="mt-2" />
                    </div>
                    <x-primary-button>Add member</x-primary-button>
                </form>
            </div>
        @endif
    </div>

    @if ($canManageClients)
        <div class="panel mt-5">
            <div class="panel-header">
                <h2 class="panel-title">Client manager</h2>
            </div>
            <form method="POST" action="{{ route('platform.clients.client-manager.update', $organization) }}" class="panel-body space-y-4">
                @csrf
                @method('PATCH')
                <p class="text-sm text-enterprise-600">
                    Assign a Saffhire client manager to this organization. New report requests requiring review are automatically assigned to this person, but individual requests can still be reassigned.
                </p>
                <div>
                    <x-input-label for="client_manager_id" value="Client manager" />
                    <select id="client_manager_id" name="client_manager_id" class="mt-1 block w-full max-w-md">
                        <option value="">No client manager assigned</option>
                        @foreach ($clientManagers as $manager)
                            <option value="{{ $manager->id }}" @selected(old('client_manager_id', $organization->client_manager_id) == $manager->id)>
                                {{ $manager->name }} ({{ $manager->email }})
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('client_manager_id')" class="mt-2" />
                </div>
                <x-primary-button>Save client manager</x-primary-button>
            </form>
        </div>
    @endif

    @if ($canManageCatalog ? $allPackages->isNotEmpty() : $assignedPackages->isNotEmpty())
        <div class="panel mt-5">
            <div class="panel-header">
                <h2 class="panel-title">Assigned packages</h2>
            </div>
            @if ($canManageCatalog)
                <form method="POST" action="{{ route('platform.clients.package-prices.update', $organization) }}">
                    @csrf
                    @method('PATCH')
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Assigned</th>
                                    <th>Package</th>
                                    <th>Base price</th>
                                    <th>Client override</th>
                                    <th>Effective price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($allPackages as $package)
                                    @php
                                        $isAssigned = in_array($package->id, $assignedPackageIds, true);
                                        $override = $package->organizationPrices->first();
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="hidden" name="packages[{{ $loop->index }}][screening_package_id]" value="{{ $package->id }}">
                                            <input
                                                type="checkbox"
                                                name="packages[{{ $loop->index }}][assigned]"
                                                value="1"
                                                class="rounded border-enterprise-300 text-brand-600 focus:ring-brand-500"
                                                @checked(old('packages.'.$loop->index.'.assigned', $isAssigned))
                                            />
                                        </td>
                                        <td class="font-medium text-enterprise-900">{{ $package->name }}</td>
                                        <td class="text-enterprise-600">{{ $package->formattedBasePrice() }}</td>
                                        <td>
                                            <x-text-input
                                                name="packages[{{ $loop->index }}][price]"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                class="block w-full max-w-[10rem]"
                                                :value="old('packages.'.$loop->index.'.price', $override?->price)"
                                                placeholder="Use base price"
                                            />
                                        </td>
                                        <td class="text-enterprise-600">
                                            {{ $isAssigned ? $package->formattedPriceForOrganization($organization) : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-enterprise-200 px-4 py-3">
                        <p class="mb-3 text-sm text-enterprise-600">
                            Check packages to make them available to this client. Leave the override blank to use the base price.
                        </p>
                        <x-primary-button>Save assignments and pricing</x-primary-button>
                    </div>
                </form>
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Package</th>
                                <th>Base price</th>
                                <th>Client price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($assignedPackages as $package)
                                <tr>
                                    <td class="font-medium text-enterprise-900">{{ $package->name }}</td>
                                    <td class="text-enterprise-600">{{ $package->formattedBasePrice() }}</td>
                                    <td class="text-enterprise-600">{{ $package->formattedPriceForOrganization($organization) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @elseif ($canManageCatalog)
        <div class="panel mt-5">
            <div class="panel-header">
                <h2 class="panel-title">Assigned packages</h2>
            </div>
            <div class="panel-body text-sm text-enterprise-600">
                No active packages are available. Create a package first, then assign it to this client.
            </div>
        </div>
    @endif

    @if ($canManageCatalog && $searchTypes->isNotEmpty())
        <div class="panel mt-5">
            <div class="panel-header">
                <h2 class="panel-title">Search review overrides</h2>
            </div>
            <form method="POST" action="{{ route('platform.clients.search-review-settings.update', $organization) }}">
                @csrf
                @method('PATCH')
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Search type</th>
                                <th>Default behavior</th>
                                <th>Client override</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($searchTypes as $searchType)
                                @php
                                    $override = $searchTypeSettings->get($searchType->id);
                                    $selected = old('settings.'.$loop->index.'.requires_review_before_submit', $override === null ? 'default' : ($override->requires_review_before_submit ? '1' : '0'));
                                @endphp
                                <tr>
                                    <td class="font-medium text-enterprise-900">{{ $searchType->name }}</td>
                                    <td class="text-enterprise-600">{{ $searchType->requires_review_before_submit ? 'Require review' : 'Auto-submit' }}</td>
                                    <td>
                                        <input type="hidden" name="settings[{{ $loop->index }}][search_type_id]" value="{{ $searchType->id }}">
                                        <select name="settings[{{ $loop->index }}][requires_review_before_submit]" class="block w-full max-w-xs">
                                            <option value="default" @selected($selected === 'default')>Use search default</option>
                                            <option value="1" @selected($selected === '1')>Require review</option>
                                            <option value="0" @selected($selected === '0')>Auto-submit</option>
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-enterprise-200 px-4 py-3">
                    <p class="mb-3 text-sm text-enterprise-600">Override whether each search requires Saffhire review before vendor submission for this client.</p>
                    <x-primary-button>Save review overrides</x-primary-button>
                </div>
            </form>
        </div>
    @endif

    @if ($canManageCatalog)
        <div class="panel mt-5">
            <div class="panel-header flex flex-wrap items-center justify-between gap-3">
                <h2 class="panel-title">Candidate intake questions</h2>
                <form method="POST" action="{{ route('platform.clients.candidate-questions.defaults', $organization) }}">
                    @csrf
                    <button type="submit" class="btn-secondary">Load default questions</button>
                </form>
            </div>
            <div class="panel-body space-y-4">
                <p class="text-sm text-enterprise-600">
                    Configure the questions candidates answer after a report request is submitted for this client.
                    Defaults cover legal name, date of birth, phone, aliases, address history, and work history.
                </p>

                @if ($candidateQuestions->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Question</th>
                                    <th>Type</th>
                                    <th>Required</th>
                                    <th>Active</th>
                                    <th>Order</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($candidateQuestions as $question)
                                    <tr>
                                        <td class="align-top">
                                            <form method="POST" action="{{ route('platform.clients.candidate-questions.update', [$organization, $question]) }}" class="space-y-2">
                                                @csrf
                                                @method('PATCH')
                                                <x-text-input name="label" class="block w-full" :value="old('label', $question->label)" required />
                                                <textarea name="help_text" rows="2" class="block w-full rounded-md border-enterprise-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Help text (optional)">{{ old('help_text', $question->help_text) }}</textarea>
                                                <select name="field_type" class="block w-full max-w-xs">
                                                    @foreach ($questionTypes as $type)
                                                        <option value="{{ $type->value }}" @selected(old('field_type', $question->field_type->value) === $type->value)>{{ $type->label() }}</option>
                                                    @endforeach
                                                </select>
                                                @if ($question->field_type === \App\Enums\CandidateFormQuestionType::Select || old('field_type') === 'select')
                                                    <textarea name="options_text" rows="3" class="block w-full rounded-md border-enterprise-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="One option per line">{{ old('options_text', implode("\n", $question->optionList())) }}</textarea>
                                                @else
                                                    <textarea name="options_text" rows="2" class="block w-full rounded-md border-enterprise-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Dropdown options (one per line, for dropdown type only)">{{ old('options_text', implode("\n", $question->optionList())) }}</textarea>
                                                @endif
                                                <div class="flex flex-wrap items-center gap-4 text-sm">
                                                    <label class="inline-flex items-center gap-2">
                                                        <input type="checkbox" name="is_required" value="1" class="rounded border-enterprise-300 text-brand-600 focus:ring-brand-500" @checked(old('is_required', $question->is_required)) />
                                                        Required
                                                    </label>
                                                    <label class="inline-flex items-center gap-2">
                                                        <input type="checkbox" name="is_active" value="1" class="rounded border-enterprise-300 text-brand-600 focus:ring-brand-500" @checked(old('is_active', $question->is_active)) />
                                                        Active
                                                    </label>
                                                    <x-text-input name="sort_order" type="number" min="0" class="w-24" :value="old('sort_order', $question->sort_order)" />
                                                    <x-primary-button>Save</x-primary-button>
                                                </div>
                                            </form>
                                        </td>
                                        <td class="align-top text-enterprise-600">{{ $question->field_type->label() }}</td>
                                        <td class="align-top text-enterprise-600">{{ $question->is_required ? 'Yes' : 'No' }}</td>
                                        <td class="align-top text-enterprise-600">{{ $question->is_active ? 'Yes' : 'No' }}</td>
                                        <td class="align-top text-enterprise-600">{{ $question->sort_order }}</td>
                                        <td class="align-top text-right">
                                            <form method="POST" action="{{ route('platform.clients.candidate-questions.destroy', [$organization, $question]) }}" onsubmit="return confirm('Remove this question?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="link-action">Remove</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-enterprise-500">No questions configured yet. Load defaults or add a custom question below.</p>
                @endif

                <form method="POST" action="{{ route('platform.clients.candidate-questions.store', $organization) }}" class="space-y-3 rounded-md border border-enterprise-200 p-4">
                    @csrf
                    <h3 class="text-sm font-semibold text-enterprise-900">Add question</h3>
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <x-input-label for="new_question_label" value="Label" />
                            <x-text-input id="new_question_label" name="label" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <x-input-label for="new_question_type" value="Type" />
                            <select id="new_question_type" name="field_type" class="mt-1 block w-full" required>
                                @foreach ($questionTypes as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="new_question_help" value="Help text (optional)" />
                            <x-text-input id="new_question_help" name="help_text" class="mt-1 block w-full" />
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="new_question_options" value="Dropdown options (one per line)" />
                            <textarea id="new_question_options" name="options_text" rows="3" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"></textarea>
                        </div>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_required" value="1" class="rounded border-enterprise-300 text-brand-600 focus:ring-brand-500" checked />
                        Required
                    </label>
                    <div>
                        <x-primary-button>Add question</x-primary-button>
                    </div>
                </form>
            </div>
        </div>

        <div class="panel mt-5">
            <div class="panel-header">
                <h2 class="panel-title">Compliance &amp; authorization documents</h2>
            </div>
            <div class="panel-body space-y-4">
                <p class="text-sm text-enterprise-600">
                    Upload authorization forms, FCRA notices, and other compliance documents for this client.
                    Documents marked for acknowledgment are presented on the candidate intake form. Where else they appear can be decided later.
                </p>

                @if ($complianceDocuments->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Document</th>
                                    <th>Type</th>
                                    <th>Ack.</th>
                                    <th>Active</th>
                                    <th>File</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($complianceDocuments as $document)
                                    <tr>
                                        <td class="align-top">
                                            <form method="POST" action="{{ route('platform.clients.compliance-documents.update', [$organization, $document]) }}" class="space-y-2">
                                                @csrf
                                                @method('PATCH')
                                                <x-text-input name="name" class="block w-full" :value="old('name', $document->name)" required />
                                                <textarea name="description" rows="2" class="block w-full rounded-md border-enterprise-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Description (optional)">{{ old('description', $document->description) }}</textarea>
                                                <select name="document_type" class="block w-full max-w-xs">
                                                    @foreach ($documentTypes as $type)
                                                        <option value="{{ $type->value }}" @selected(old('document_type', $document->document_type->value) === $type->value)>{{ $type->label() }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="flex flex-wrap items-center gap-4 text-sm">
                                                    <label class="inline-flex items-center gap-2">
                                                        <input type="checkbox" name="require_acknowledgment" value="1" class="rounded border-enterprise-300 text-brand-600 focus:ring-brand-500" @checked(old('require_acknowledgment', $document->require_acknowledgment)) />
                                                        Require acknowledgment
                                                    </label>
                                                    <label class="inline-flex items-center gap-2">
                                                        <input type="checkbox" name="is_active" value="1" class="rounded border-enterprise-300 text-brand-600 focus:ring-brand-500" @checked(old('is_active', $document->is_active)) />
                                                        Active
                                                    </label>
                                                    <x-text-input name="sort_order" type="number" min="0" class="w-24" :value="old('sort_order', $document->sort_order)" />
                                                    <x-primary-button>Save</x-primary-button>
                                                </div>
                                            </form>
                                        </td>
                                        <td class="align-top text-enterprise-600">{{ $document->document_type->label() }}</td>
                                        <td class="align-top text-enterprise-600">{{ $document->require_acknowledgment ? 'Yes' : 'No' }}</td>
                                        <td class="align-top text-enterprise-600">{{ $document->is_active ? 'Yes' : 'No' }}</td>
                                        <td class="align-top text-sm text-enterprise-600">
                                            <a href="{{ route('platform.clients.compliance-documents.download', [$organization, $document]) }}" class="link-action">{{ $document->original_filename }}</a>
                                            <div>{{ $document->formattedFileSize() }}</div>
                                        </td>
                                        <td class="align-top text-right">
                                            <form method="POST" action="{{ route('platform.clients.compliance-documents.destroy', [$organization, $document]) }}" onsubmit="return confirm('Remove this document?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="link-action">Remove</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-enterprise-500">No compliance documents uploaded yet.</p>
                @endif

                <form method="POST" action="{{ route('platform.clients.compliance-documents.store', $organization) }}" enctype="multipart/form-data" class="space-y-3 rounded-md border border-enterprise-200 p-4">
                    @csrf
                    <h3 class="text-sm font-semibold text-enterprise-900">Upload document</h3>
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <x-input-label for="document_name" value="Display name" />
                            <x-text-input id="document_name" name="name" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <x-input-label for="document_type" value="Document type" />
                            <select id="document_type" name="document_type" class="mt-1 block w-full" required>
                                @foreach ($documentTypes as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="document_description" value="Description (optional)" />
                            <x-text-input id="document_description" name="description" class="mt-1 block w-full" />
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="document_file" value="File (PDF, Word, image, or text — max 10MB)" />
                            <input id="document_file" name="document" type="file" class="mt-1 block w-full text-sm" required />
                            <x-input-error :messages="$errors->get('document')" class="mt-2" />
                        </div>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="require_acknowledgment" value="1" class="rounded border-enterprise-300 text-brand-600 focus:ring-brand-500" checked />
                        Require candidate acknowledgment on intake form
                    </label>
                    <div>
                        <x-primary-button>Upload document</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-app-layout>
