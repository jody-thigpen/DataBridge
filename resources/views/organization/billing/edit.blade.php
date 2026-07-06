<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Billing & payments"
            :subtitle="'Subscription and payment method management for ' . $organization->name"
        />
    </x-slot>

    <div class="panel max-w-3xl">
        <div class="panel-body space-y-4">
            <p class="text-sm leading-relaxed text-enterprise-600">
                Payment card and bank account data is stored exclusively with the payment provider to maintain PCI compliance.
                DataBridge retains only billing references, invoice history, and subscription status.
            </p>
            <div class="rounded border border-dashed border-enterprise-300 bg-enterprise-50 px-6 py-10 text-center">
                <div class="text-sm font-semibold text-enterprise-900">Billing provider integration pending</div>
                <p class="mx-auto mt-2 max-w-lg text-sm text-enterprise-600">
                    Auto-renewal, invoice history, and payment method updates will be available through a secure hosted billing portal.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
