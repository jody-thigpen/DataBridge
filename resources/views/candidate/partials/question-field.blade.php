@php
    use App\Enums\CandidateFormQuestionType;
    $fieldName = 'answers['.$question->field_key.']';
    $oldValue = old('answers.'.$question->field_key);
@endphp

@if ($question->field_type === CandidateFormQuestionType::Text)
    <x-text-input
        :name="$fieldName"
        class="mt-1 block w-full"
        :value="$oldValue"
        :required="$question->is_required"
    />
@elseif ($question->field_type === CandidateFormQuestionType::Textarea)
    <textarea
        name="{{ $fieldName }}"
        rows="4"
        class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
        @required($question->is_required)
    >{{ $oldValue }}</textarea>
@elseif ($question->field_type === CandidateFormQuestionType::Date)
    <x-text-input
        type="date"
        :name="$fieldName"
        class="mt-1 block w-full max-w-xs"
        :value="$oldValue"
        :required="$question->is_required"
    />
@elseif ($question->field_type === CandidateFormQuestionType::Select)
    <select
        name="{{ $fieldName }}"
        class="mt-1 block w-full max-w-md rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
        @required($question->is_required)
    >
        <option value="">Select an option</option>
        @foreach ($question->optionList() as $option)
            <option value="{{ $option }}" @selected($oldValue === $option)>{{ $option }}</option>
        @endforeach
    </select>
@elseif ($question->field_type === CandidateFormQuestionType::YesNo)
    <select
        name="{{ $fieldName }}"
        class="mt-1 block w-full max-w-xs rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
        @required($question->is_required)
    >
        <option value="">Select</option>
        <option value="yes" @selected($oldValue === 'yes')>Yes</option>
        <option value="no" @selected($oldValue === 'no')>No</option>
    </select>
@elseif ($question->field_type === CandidateFormQuestionType::AddressHistory)
    <div
        x-data="{
            rows: {{ Js::from(old('answers.'.$question->field_key, [['line1'=>'','line2'=>'','city'=>'','state'=>'','postal'=>'','from'=>'','to'=>'']])) }}
        }"
        class="space-y-4"
    >
        <template x-for="(row, index) in rows" :key="index">
            <div class="rounded-md border border-enterprise-200 p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-medium text-enterprise-800" x-text="'Address ' + (index + 1)"></div>
                    <button type="button" class="link-action text-sm" x-show="rows.length > 1" @click="rows.splice(index, 1)">Remove</button>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-input-label value="Street address" />
                        <input type="text" :name="'answers[{{ $question->field_key }}][' + index + '][line1]'" x-model="row.line1" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label value="Apartment / suite (optional)" />
                        <input type="text" :name="'answers[{{ $question->field_key }}][' + index + '][line2]'" x-model="row.line2" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                    <div>
                        <x-input-label value="City" />
                        <input type="text" :name="'answers[{{ $question->field_key }}][' + index + '][city]'" x-model="row.city" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                    <div>
                        <x-input-label value="State" />
                        <input type="text" :name="'answers[{{ $question->field_key }}][' + index + '][state]'" x-model="row.state" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                    <div>
                        <x-input-label value="Postal code" />
                        <input type="text" :name="'answers[{{ $question->field_key }}][' + index + '][postal]'" x-model="row.postal" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                    <div>
                        <x-input-label value="From (month/year)" />
                        <input type="month" :name="'answers[{{ $question->field_key }}][' + index + '][from]'" x-model="row.from" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                    <div>
                        <x-input-label value="To (month/year, blank if current)" />
                        <input type="month" :name="'answers[{{ $question->field_key }}][' + index + '][to]'" x-model="row.to" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                </div>
            </div>
        </template>
        <button type="button" class="btn-secondary" @click="rows.push({line1:'',line2:'',city:'',state:'',postal:'',from:'',to:''})">
            Add another address
        </button>
    </div>
@elseif ($question->field_type === CandidateFormQuestionType::WorkHistory)
    <div
        x-data="{
            rows: {{ Js::from(old('answers.'.$question->field_key, [['employer'=>'','title'=>'','city'=>'','state'=>'','from'=>'','to'=>'','reason_for_leaving'=>'']])) }}
        }"
        class="space-y-4"
    >
        <template x-for="(row, index) in rows" :key="index">
            <div class="rounded-md border border-enterprise-200 p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-medium text-enterprise-800" x-text="'Employer ' + (index + 1)"></div>
                    <button type="button" class="link-action text-sm" x-show="rows.length > 1" @click="rows.splice(index, 1)">Remove</button>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <x-input-label value="Employer name" />
                        <input type="text" :name="'answers[{{ $question->field_key }}][' + index + '][employer]'" x-model="row.employer" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                    <div>
                        <x-input-label value="Job title" />
                        <input type="text" :name="'answers[{{ $question->field_key }}][' + index + '][title]'" x-model="row.title" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                    <div>
                        <x-input-label value="City" />
                        <input type="text" :name="'answers[{{ $question->field_key }}][' + index + '][city]'" x-model="row.city" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                    <div>
                        <x-input-label value="State" />
                        <input type="text" :name="'answers[{{ $question->field_key }}][' + index + '][state]'" x-model="row.state" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                    <div>
                        <x-input-label value="From (month/year)" />
                        <input type="month" :name="'answers[{{ $question->field_key }}][' + index + '][from]'" x-model="row.from" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                    <div>
                        <x-input-label value="To (month/year, blank if current)" />
                        <input type="month" :name="'answers[{{ $question->field_key }}][' + index + '][to]'" x-model="row.to" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label value="Reason for leaving (optional)" />
                        <input type="text" :name="'answers[{{ $question->field_key }}][' + index + '][reason_for_leaving]'" x-model="row.reason_for_leaving" class="mt-1 block w-full rounded-md border-enterprise-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                    </div>
                </div>
            </div>
        </template>
        <button type="button" class="btn-secondary" @click="rows.push({employer:'',title:'',city:'',state:'',from:'',to:'',reason_for_leaving:''})">
            Add another employer
        </button>
    </div>
@endif
