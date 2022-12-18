@php $editing = isset($remitenteExterno) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="nombre_completo"
            label="Nombre Completo"
            :value="old('nombre_completo', ($editing ? $remitenteExterno->nombre_completo : ''))"
            maxlength="255"
            placeholder="Nombre Completo"
            required
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="cargo_empresa"
            label="Cargo Empresa"
            :value="old('cargo_empresa', ($editing ? $remitenteExterno->cargo_empresa : ''))"
            maxlength="255"
            placeholder="Cargo Empresa"
            required
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.text
            name="telefono_1"
            label="Telefono 1"
            :value="old('telefono_1', ($editing ? $remitenteExterno->telefono_1 : ''))"
            maxlength="255"
            placeholder="Telefono 1"
            required
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.text
            name="telefono_2"
            label="Telefono 2"
            :value="old('telefono_2', ($editing ? $remitenteExterno->telefono_2 : ''))"
            maxlength="255"
            placeholder="Telefono 2"
            required
        ></x-inputs.text>
    </x-inputs.group>
</div>
