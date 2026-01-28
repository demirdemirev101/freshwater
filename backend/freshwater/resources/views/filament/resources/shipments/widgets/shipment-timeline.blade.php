<x-filament::section>
    <x-slot name="heading">
        История на доставката
    </x-slot>

    @if($record->tracking_events && count($record->tracking_events) > 0)
        <ol class="relative border-l border-gray-200 dark:border-gray-700 ml-3">
            @foreach(array_reverse($record->tracking_events) as $event)
                <li class="mb-10 ml-6">
                    <span class="absolute flex items-center justify-center w-6 h-6 bg-blue-100 rounded-full -left-3 ring-8 ring-white dark:ring-gray-900 dark:bg-blue-900">
                        <svg class="w-3 h-3 text-blue-800 dark:text-blue-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                        </svg>
                    </span>
                    <h3 class="flex items-center mb-1 text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $event['statusNameBG'] ?? $event['statusCode'] }}
                    </h3>
                    <time class="block mb-2 text-sm font-normal leading-none text-gray-400 dark:text-gray-500">
                        {{ \Carbon\Carbon::parse($event['dateTime'])->format('d.m.Y H:i') }}
                    </time>
                    @if(!empty($event['description']))
                        <p class="mb-4 text-base font-normal text-gray-500 dark:text-gray-400">
                            {{ $event['description'] }}
                        </p>
                    @endif
                </li>
            @endforeach
        </ol>
    @elseif($record->error_message)
        <div class="rounded-md bg-red-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Грешка</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p>{{ $record->error_message }}</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
            <p class="mt-2">Няма налична информация за проследяване</p>
        </div>
    @endif

    @if($record->carrier_payload || $record->carrier_response)
        <div class="mt-6 border-t pt-6">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Техническа информация</h3>
            
            @if($record->carrier_payload)
                <details class="mb-3">
                    <summary class="cursor-pointer text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                        Изпратени данни
                    </summary>
                    <pre class="mt-2 text-xs bg-gray-50 dark:bg-gray-800 p-3 rounded overflow-auto">{{ json_encode($record->carrier_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </details>
            @endif

            @if($record->carrier_response)
                <details>
                    <summary class="cursor-pointer text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                        Отговор от Еконт
                    </summary>
                    <pre class="mt-2 text-xs bg-gray-50 dark:bg-gray-800 p-3 rounded overflow-auto">{{ json_encode($record->carrier_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </details>
            @endif
        </div>
    @endif
</x-filament::section>