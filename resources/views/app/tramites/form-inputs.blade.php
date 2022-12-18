@php $editing = isset($tramite) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.text
            name="hoja_ruta"
            label="Hoja Ruta"
            :value="old('hoja_ruta', ($editing ? $tramite->hoja_ruta : ''))"
            maxlength="50"
            placeholder="Hoja Ruta"
            required
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.select name="recepcion_user_id" label="User" required>
            @php $selected = old('recepcion_user_id', ($editing ? $tramite->recepcion_user_id : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the User</option>
            @foreach($users as $value => $label)
            <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }} >{{ $label }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.datetime
            name="fecha_ingreso"
            label="Fecha Ingreso"
            value="{{ old('fecha_ingreso', ($editing ? optional($tramite->fecha_ingreso)->format('Y-m-d\TH:i:s') : 'now()')) }}"
            max="255"
            required
        ></x-inputs.datetime>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.checkbox
            name="hr_ext"
            label="Hr Ext"
            :checked="old('hr_ext', ($editing ? $tramite->hr_ext : 0))"
        ></x-inputs.checkbox>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.select
            name="remitente_externo_id"
            label="Remitente Externo"
            required
        >
            @php $selected = old('remitente_externo_id', ($editing ? $tramite->remitente_externo_id : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the Remitente Externo</option>
            @foreach($remitenteExternos as $value => $label)
            <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }} >{{ $label }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.text
            name="cite_ext"
            label="Cite Ext"
            :value="old('cite_ext', ($editing ? $tramite->cite_ext : ''))"
            maxlength="255"
            placeholder="Cite Ext"
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.textarea name="asunto_ext" label="Asunto Ext" maxlength="255"
            >{{ old('asunto_ext', ($editing ? $tramite->asunto_ext : ''))
            }}</x-inputs.textarea
        >
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.select
            name="remitente_interno_id"
            label="Remitente Interno"
            required
        >
            @php $selected = old('remitente_interno_id', ($editing ? $tramite->remitente_interno_id : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the User</option>
            @foreach($users as $value => $label)
            <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }} >{{ $label }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.select name="cite_interno_id" label="Cite Interno">
            @php $selected = old('cite_interno_id', ($editing ? $tramite->cite_interno_id : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the Cite Interno</option>
            @foreach($citeInternos as $value => $label)
            <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }} >{{ $label }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="asunto_int"
            label="Asunto Int"
            :value="old('asunto_int', ($editing ? $tramite->asunto_int : ''))"
            maxlength="255"
            placeholder="Asunto Int"
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="tipo_documento_id" label="Tipo Documento">
            @php $selected = old('tipo_documento_id', ($editing ? $tramite->tipo_documento_id : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the Tipo Documento</option>
            @foreach($tipoDocumentos as $value => $label)
            <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }} >{{ $label }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="estado"
            label="Estado"
            :value="old('estado', ($editing ? $tramite->estado : ''))"
            maxlength="5"
            placeholder="Estado"
            required
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.checkbox
            name="fusionado"
            label="Fusionado"
            :checked="old('fusionado', ($editing ? $tramite->fusionado : 0))"
        ></x-inputs.checkbox>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="hr_fusionado"
            label="Hr Fusionado"
            :value="old('hr_fusionado', ($editing ? $tramite->hr_fusionado : ''))"
            maxlength="255"
            placeholder="Hr Fusionado"
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="hr_fusionado" label="Hr Fusionado">
            @php $selected = old('hr_fusionado', ($editing ? $tramite->hr_fusionado : '')) @endphp
        </x-inputs.select>
    </x-inputs.group>
</div>
