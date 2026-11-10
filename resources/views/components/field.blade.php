@props(['label', 'name'])

<div {{ $attributes }}>
    <label for="f-{{ $name }}" class="field-label">{{ $label }}</label>
    {{ $slot }}
    @error($name)
        <p class="field-error">{{ $message }}</p>
    @enderror
</div>
