<x-filament-panels::page>
    @php
        $editUrl = \App\Filament\Resources\Contacts\ContactResource::getUrl('edit', ['record' => $record]);
        $initials = strtoupper(
            \Illuminate\Support\Str::substr($record->first_name ?? '?', 0, 1)
            . \Illuminate\Support\Str::substr($record->last_name ?? '', 0, 1)
        );
    @endphp

    <div class="grid grid-cols-1 gap-6 md:grid-cols-12">
        {{-- Left column: contact card --}}
        <aside class="md:col-span-4 lg:col-span-3">
            <div class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                {{-- Avatar (clickable to edit) --}}
                <a href="{{ $editUrl }}" class="block w-fit mx-auto">
                    @if (filled($record->avatar))
                        <img
                            src="{{ $record->avatar }}"
                            alt="{{ $record->first_name }} {{ $record->last_name }}"
                            class="w-24 h-24 rounded-full object-cover ring-2 ring-gray-200 dark:ring-white/10 hover:ring-primary-500 transition"
                        />
                    @else
                        <div class="w-24 h-24 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-2xl font-semibold flex items-center justify-center ring-2 ring-gray-200 dark:ring-white/10 hover:ring-primary-500 transition">
                            {{ $initials ?: '?' }}
                        </div>
                    @endif
                </a>

                {{-- Name (clickable to edit) --}}
                <div class="text-center">
                    <a
                        href="{{ $editUrl }}"
                        class="text-lg font-semibold text-gray-950 hover:text-primary-600 dark:text-white dark:hover:text-primary-400"
                    >
                        {{ trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? '')) ?: 'Contact #' . $record->id }}
                    </a>
                </div>

                {{-- Primary email --}}
                @if ($record->primaryEmail)
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <x-filament::icon icon="heroicon-o-envelope" class="h-4 w-4 shrink-0" />
                        <a href="mailto:{{ $record->primaryEmail->email }}" class="hover:text-primary-600 dark:hover:text-primary-400 break-all">
                            {{ $record->primaryEmail->email }}
                        </a>
                    </div>
                @endif

                {{-- Primary phone --}}
                @if ($record->primaryPhone)
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <x-filament::icon icon="heroicon-o-phone" class="h-4 w-4 shrink-0" />
                        <span>{{ $record->primaryPhone->phone }}</span>
                    </div>
                @endif

                <hr class="border-gray-200 dark:border-white/10" />

                {{-- Meta --}}
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Owner</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $record->user?->name ?? '—' }}</dd>
                    </div>

                    @if ($record->agency)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Agency</dt>
                            <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $record->agency->name }}</dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Created</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $record->created_at?->format('Y-m-d H:i') }}</dd>
                    </div>
                </dl>
            </div>
        </aside>

        {{-- Right column: note input + timeline --}}
        <div class="md:col-span-8 lg:col-span-9 space-y-6">
            {{-- Add note --}}
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Add a note</h3>

                <textarea
                    wire:model="noteBody"
                    rows="3"
                    placeholder="Write a note about this contact..."
                    class="block w-full rounded-lg border-gray-300 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white sm:text-sm"
                ></textarea>

                @error('noteBody')
                    <p class="mt-2 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                @enderror

                <div class="mt-3 flex justify-end">
                    <button
                        type="button"
                        wire:click="submitNote"
                        wire:loading.attr="disabled"
                        wire:target="submitNote"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 disabled:opacity-50"
                    >
                        <x-filament::icon icon="heroicon-m-paper-airplane" class="h-4 w-4" wire:loading.remove wire:target="submitNote" />
                        <x-filament::loading-indicator class="h-4 w-4" wire:loading wire:target="submitNote" />
                        Save note
                    </button>
                </div>
            </section>

            {{-- Timeline --}}
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Timeline</h3>

                @if ($this->events->isEmpty())
                    <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        No events yet. Add a note above to start the conversation.
                    </p>
                @else
                    <ol class="relative space-y-6 border-l border-gray-200 dark:border-white/10 pl-6">
                        @foreach ($this->events as $event)
                            <li class="relative">
                                <span class="absolute -left-[31px] top-1 grid h-5 w-5 place-items-center rounded-full bg-primary-500 ring-4 ring-white dark:ring-gray-900">
                                    <x-filament::icon
                                        icon="{{ $event->eventable_type === \App\Models\Note::class ? 'heroicon-m-pencil-square' : 'heroicon-m-bolt' }}"
                                        class="h-3 w-3 text-white"
                                    />
                                </span>

                                <div class="flex flex-wrap items-baseline justify-between gap-x-2 gap-y-1">
                                    <div class="text-sm">
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ class_basename($event->eventable_type) }}
                                        </span>
                                        @if ($event->user)
                                            <span class="text-gray-500 dark:text-gray-400">
                                                by {{ $event->user->name }}
                                            </span>
                                        @endif
                                    </div>
                                    <time class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ ($event->occurred_at ?? $event->created_at)?->format('Y-m-d H:i') }}
                                    </time>
                                </div>

                                @if ($event->eventable instanceof \App\Models\Note)
                                    <div class="mt-2 whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{{ $event->eventable->body }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        </div>
    </div>
</x-filament-panels::page>
