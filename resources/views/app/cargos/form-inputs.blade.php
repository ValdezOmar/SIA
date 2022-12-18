@php $editing = isset($cargo) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.text
            name="nombre_cargo"
            label="Nombre del Cargo"
            :value="old('nombre_cargo', ($editing ? $cargo->nombre_cargo : ''))"
            maxlength="255"
            placeholder="Ingrese el Nombre del Cargo"
            required
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.select name="area_id" label="Area" required>
            @php $selected = old('area_id', ($editing ? $cargo->area_id : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the Area</option>
            @foreach($areas as $value => $label)
            <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }} >{{ $label }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.textarea
            name="descripcion_funciones"
            label="Descripcion deFunciones"
            maxlength="255"
            >{{ old('descripcion_funciones', ($editing ?
            $cargo->descripcion_funciones : '')) }}</x-inputs.textarea
        >
    </x-inputs.group>
</div>
