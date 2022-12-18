@php $editing = isset($filePersonal) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.text
            name="nombres"
            label="Nombres"
            :value="old('nombres', ($editing ? $filePersonal->nombres : ''))"
            maxlength="255"
            placeholder="Inrese los Nombres"
            required
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.text
            name="apellidos"
            label="Apellidos"
            :value="old('apellidos', ($editing ? $filePersonal->apellidos : ''))"
            maxlength="255"
            placeholder="Ingrese los Apellidos"
            required
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.date
            name="fecha_nacimiento"
            label="Fecha Nacimiento"
            value="{{ old('fecha_nacimiento', ($editing ? optional($filePersonal->fecha_nacimiento)->format('Y-m-d') : '')) }}"
            max="255"
        ></x-inputs.date>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.text
            name="CI"
            label="CI / DNI"
            :value="old('CI', ($editing ? $filePersonal->CI : ''))"
            maxlength="255"
            placeholder="Ingrese el documento de identidad"
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="direccion"
            label="Direccion"
            :value="old('direccion', ($editing ? $filePersonal->direccion : ''))"
            maxlength="255"
            placeholder="Ingrese la Direccion"
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="email_personal"
            label="Email Personal"
            :value="old('email_personal', ($editing ? $filePersonal->email_personal : ''))"
            maxlength="255"
            placeholder="Email Personal"
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.text
            name="telefono_1"
            label="Telefono Personal"
            :value="old('telefono_1', ($editing ? $filePersonal->telefono_1 : ''))"
            maxlength="255"
            placeholder="Ingrese el Telefono Celular"
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.text
            name="telefono_2"
            label="Telefono Fijo"
            :value="old('telefono_2', ($editing ? $filePersonal->telefono_2 : ''))"
            maxlength="255"
            placeholder="Ingrese el Telefono Fijo"
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.textarea
            name="presentacion"
            label="Presentacion Personal"
            maxlength="255"
            >{{ old('presentacion', ($editing ? $filePersonal->presentacion :
            '')) }}</x-inputs.textarea
        >
    </x-inputs.group>
</div>
