@php $editing = isset($area) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.text
            name="nombre_area"
            label="Nombre del Area"
            :value="old('nombre_area', ($editing ? $area->nombre_area : ''))"
            maxlength="255"
            placeholder="Introduzca el Nombre del Area"
            required
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.text
            name="prefijo_cite"
            label="Prefijo del Cite"
            :value="old('prefijo_cite', ($editing ? $area->prefijo_cite : ''))"
            maxlength="255"
            placeholder="Prefijo del Cite"
            required
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.textarea
            name="descripcion_area"
            label="Descripcion del Area"
            maxlength="255"
            >{{ old('descripcion_area', ($editing ? $area->descripcion_area :
            '')) }}</x-inputs.textarea
        >
    </x-inputs.group>
</div>
