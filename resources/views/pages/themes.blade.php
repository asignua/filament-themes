@php
    $manager = $this->manager();
    $cards = $manager->cards();
    $colorSetting = $manager->colorSetting();
    $canChangeColor = $manager->canChangeColor();
    $plugin = \Asignua\FilamentThemes\ThemesPlugin::get();
@endphp

<x-filament-panels::page>
    @if ($manager->isGlobal())
        <x-filament::callout icon="heroicon-o-information-circle" color="warning" :heading="__('filament-themes::filament-themes.global_notice')" />
    @endif

    <x-filament::section :heading="__('filament-themes::filament-themes.theme_heading')" :description="__('filament-themes::filament-themes.theme_description')">
        <div class="fi-themes-grid" role="group" aria-label="{{ __('filament-themes::filament-themes.theme_heading') }}">
            @foreach ($cards as $card)
                @php($s = $card['shades'])

                <button
                    type="button"
                    wire:click="selectTheme('{{ $card['name'] }}')"
                    wire:loading.attr="disabled"
                    class="fi-themes-card"
                    aria-pressed="{{ $card['current'] ? 'true' : 'false' }}"
                    data-theme-card="{{ $card['name'] }}"
                >
                    <span
                        class="fi-themes-preview"
                        aria-hidden="true"
                        style="
                            --fi-themes-light: {{ $s['light'] }};
                            --fi-themes-light-line: {{ $s['light-line'] }};
                            --fi-themes-dark: {{ $s['dark'] }};
                            --fi-themes-dark-line: {{ $s['dark-line'] }};
                            --fi-themes-primary: {{ $s['primary'] }};
                            --fi-themes-primary-soft: {{ $s['primary-soft'] }};
                        "
                    >
                        <span class="fi-themes-half fi-themes-half-light">
                            <span class="fi-themes-bar"></span>
                            <span class="fi-themes-line"></span>
                            <span class="fi-themes-line fi-themes-line-short"></span>
                            <span class="fi-themes-btn"></span>
                        </span>
                        <span class="fi-themes-half fi-themes-half-dark">
                            <span class="fi-themes-bar"></span>
                            <span class="fi-themes-line"></span>
                            <span class="fi-themes-line fi-themes-line-short"></span>
                            <span class="fi-themes-btn"></span>
                        </span>
                    </span>

                    <span class="fi-themes-meta">
                        <span class="fi-themes-name">{{ $card['label'] }}</span>
                        @if ($card['current'])
                            <span class="fi-themes-current">{{ __('filament-themes::filament-themes.current') }}</span>
                        @endif
                    </span>
                    <span class="fi-themes-dots" aria-hidden="true">
                        <i style="background: {{ $s['primary'] }}"></i>
                        <i style="background: {{ $s['success'] }}"></i>
                        <i style="background: {{ $s['warning'] }}"></i>
                        <i style="background: {{ $s['danger'] }}"></i>
                    </span>
                    @if (filled($description = __('filament-themes::filament-themes.descriptions.'.$card['name'])) && $description !== 'filament-themes::filament-themes.descriptions.'.$card['name'])
                        <span class="fi-themes-description">{{ $description }}</span>
                    @endif
                </button>
            @endforeach
        </div>
    </x-filament::section>

    @if ($canChangeColor)
        <x-filament::section :heading="__('filament-themes::filament-themes.color_heading')" :description="__('filament-themes::filament-themes.color_description')">
            <div class="fi-themes-swatches" role="group" aria-label="{{ __('filament-themes::filament-themes.color_heading') }}">
                @foreach ($manager->swatches() as $name => $shade)
                    <button
                        type="button"
                        wire:click="selectColor('{{ $name }}')"
                        class="fi-themes-swatch"
                        style="background: {{ $shade }}"
                        title="{{ ucfirst($name) }}"
                        aria-label="{{ ucfirst($name) }}"
                        aria-pressed="{{ $colorSetting === $name ? 'true' : 'false' }}"
                        data-color="{{ $name }}"
                    ></button>
                @endforeach
            </div>

            @if ($plugin->allowsCustomColor())
                <form wire:submit="applyCustomColor" class="fi-themes-custom">
                    <label for="fi-themes-custom-color">{{ __('filament-themes::filament-themes.custom_color') }}</label>
                    <input type="color" id="fi-themes-custom-color" wire:model.live="customColor" />
                    <x-filament::input.wrapper :valid="! $errors->has('customColor')">
                        <x-filament::input type="text" wire:model="customColor" maxlength="7" placeholder="#3366ff" aria-label="{{ __('filament-themes::filament-themes.custom_color') }}" />
                    </x-filament::input.wrapper>
                    <x-filament::button type="submit">{{ __('filament-themes::filament-themes.apply') }}</x-filament::button>
                </form>
                @error('customColor')
                    <p class="fi-themes-error" role="alert">{{ $message }}</p>
                @enderror
            @endif

            @if ($colorSetting !== null)
                <div class="fi-themes-reset">
                    <x-filament::button color="gray" size="sm" wire:click="resetColor">
                        {{ __('filament-themes::filament-themes.reset') }}
                    </x-filament::button>
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
