<select name="semestre" {{ $attributes->merge(['class' => 'form-control']) }}>
    <option value="">Seleccionar semestre...</option>
    @foreach(['I','II','III','IV','V','VI'] as $s)
        <option value="{{ $s }} Semestre" {{ $selected === $s.' Semestre' ? 'selected' : '' }}>
            {{ $s }} Semestre
        </option>
    @endforeach
</select>
