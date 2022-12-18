@php $editing = isset($derivacion) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.select name="tramite_id" label="Tramite" required>
            @php $selected = old('tramite_id', ($editing ? $derivacion->tramite_id : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the Tramite</option>
            @foreach($tramites as $value => $label)
            <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }} >{{ $label }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.select name="remitente_id" label="User">
            @php $selected = old('remitente_id', ($editing ? $derivacion->remitente_id : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the User</option>
            @foreach($users as $value => $label)
            <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }} >{{ $label }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="destinatario_id"
            label="Destinatario Id"
            :value="old('destinatario_id', ($editing ? $derivacion->destinatario_id : ''))"
            maxlength="255"
            placeholder="Destinatario Id"
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="proveido"
            label="Proveido"
            :value="old('proveido', ($editing ? $derivacion->proveido : ''))"
            maxlength="255"
            placeholder="Proveido"
            required
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.datetime
            name="fecha_derivacion"
            label="Fecha Derivacion"
            value="{{ old('fecha_derivacion', ($editing ? optional($derivacion->fecha_derivacion)->format('Y-m-d\TH:i:s') : '')) }}"
            max="255"
        ></x-inputs.datetime>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.datetime
            name="fecha_recepcion"
            label="Fecha Recepcion"
            value="{{ old('fecha_recepcion', ($editing ? optional($derivacion->fecha_recepcion)->format('Y-m-d\TH:i:s') : '')) }}"
            max="255"
        ></x-inputs.datetime>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.datetime
            name="fecha_rechazo"
            label="Fecha Rechazo"
            value="{{ old('fecha_rechazo', ($editing ? optional($derivacion->fecha_rechazo)->format('Y-m-d\TH:i:s') : '')) }}"
            max="255"
        ></x-inputs.datetime>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.datetime
            name="fecha_anulado"
            label="Fecha Anulado"
            value="{{ old('fecha_anulado', ($editing ? optional($derivacion->fecha_anulado)->format('Y-m-d\TH:i:s') : '')) }}"
            max="255"
        ></x-inputs.datetime>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.datetime
            name="fecha_archivo"
            label="Fecha Archivo"
            value="{{ old('fecha_archivo', ($editing ? optional($derivacion->fecha_archivo)->format('Y-m-d\TH:i:s') : '')) }}"
            max="255"
        ></x-inputs.datetime>
    </x-inputs.group>
</div>
