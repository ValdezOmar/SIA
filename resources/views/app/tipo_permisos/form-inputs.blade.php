@php $editing = isset($tipoPermiso) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.text
            name="nombre_permiso"
            label="Nombre del Permiso"
            :value="old('nombre_permiso', ($editing ? $tipoPermiso->nombre_permiso : 'Personal'))"
            maxlength="255"
            placeholder="Nombre del Permiso"
            required
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.select name="tipo_permiso" label="Tipo Permiso">
            @php $selected = old('tipo_permiso', ($editing ? $tipoPermiso->tipo_permiso : '')) @endphp
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="tiempo_permitido"
            label="Tiempo Permitido"
            :value="old('tiempo_permitido', ($editing ? $tipoPermiso->tiempo_permitido : ''))"
            maxlength="255"
            placeholder="Tiempo Permitido"
            required
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.textarea
            name="descripcion_permiso"
            label="Descripcion Permiso"
            maxlength="255"
            required
            >{{ old('descripcion_permiso', ($editing ?
            $tipoPermiso->descripcion_permiso : '')) }}</x-inputs.textarea
        >
    </x-inputs.group>
</div>
