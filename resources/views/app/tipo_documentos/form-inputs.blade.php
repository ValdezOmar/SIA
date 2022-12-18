@php $editing = isset($tipoDocumento) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="tipo_documento"
            label="Tipo Documento"
            :value="old('tipo_documento', ($editing ? $tipoDocumento->tipo_documento : ''))"
            maxlength="255"
            placeholder="Tipo Documento"
            required
        ></x-inputs.text>
    </x-inputs.group>
</div>
