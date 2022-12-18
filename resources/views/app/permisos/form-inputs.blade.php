@php $editing = isset($permisos) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="tipo_permiso_id" label="Tipo Permiso" required>
            @php $selected = old('tipo_permiso_id', ($editing ? $permisos->tipo_permiso_id : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the Tipo Permiso</option>
            @foreach($tipoPermisos as $value => $label)
            <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }} >{{ $label }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.datetime
            name="fecha_inicio"
            label="Fecha Inicio"
            value="{{ old('fecha_inicio', ($editing ? optional($permisos->fecha_inicio)->format('Y-m-d\TH:i:s') : '')) }}"
            max="255"
            required
        ></x-inputs.datetime>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.datetime
            name="fecha_fin"
            label="Fecha Fin"
            value="{{ old('fecha_fin', ($editing ? optional($permisos->fecha_fin)->format('Y-m-d\TH:i:s') : '')) }}"
            max="255"
            required
        ></x-inputs.datetime>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="descripcion"
            label="Descripcion"
            :value="old('descripcion', ($editing ? $permisos->descripcion : ''))"
            maxlength="255"
            placeholder="Descripcion"
            required
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="descripcion_rechazo"
            label="Descripcion Rechazo"
            :value="old('descripcion_rechazo', ($editing ? $permisos->descripcion_rechazo : ''))"
            maxlength="255"
            placeholder="Descripcion Rechazo"
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="permisosable_id"
            label="Permisosable Id"
            :value="old('permisosable_id', ($editing ? $permisos->permisosable_id : ''))"
            maxlength="255"
            placeholder="Permisosable Id"
            required
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="permisosable_type"
            label="Permisosable Type"
            :value="old('permisosable_type', ($editing ? $permisos->permisosable_type : ''))"
            maxlength="255"
            placeholder="Permisosable Type"
            required
        ></x-inputs.text>
    </x-inputs.group>
</div>
