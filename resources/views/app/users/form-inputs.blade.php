@php $editing = isset($user) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.select name="file_personal_id" label="File Personal" required>
            @php $selected = old('file_personal_id', ($editing ? $user->file_personal_id : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the File Personal</option>
            @foreach($filePersonal as $value => $label)
            <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }} >{{ $label }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.checkbox
            name="activo"
            label="Activo"
            :checked="old('activo', ($editing ? $user->activo : 0))"
        ></x-inputs.checkbox>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.select name="cargo_id" label="Cargo" required>
            @php $selected = old('cargo_id', ($editing ? $user->cargo_id : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the Cargo</option>
            @foreach($cargos as $value => $label)
            <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }} >{{ $label }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.text
            name="telefono_int"
            label="Telefono Int"
            :value="old('telefono_int', ($editing ? $user->telefono_int : ''))"
            maxlength="255"
            placeholder="Telefono Int"
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="name"
            label="Name"
            :value="old('name', ($editing ? $user->name : ''))"
            maxlength="255"
            placeholder="Name"
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.email
            name="email"
            label="Email Corporativo"
            :value="old('email', ($editing ? $user->email : ''))"
            maxlength="255"
            placeholder="Ingrese el Email de la Empresa"
            required
        ></x-inputs.email>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.password
            name="password"
            label="Password"
            maxlength="255"
            placeholder="Password"
            :required="!$editing"
        ></x-inputs.password>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.date
            name="fecha_alta"
            label="Fecha Alta"
            value="{{ old('fecha_alta', ($editing ? optional($user->fecha_alta)->format('d-m-Y') : '')) }}"
            max="255"
            required
        >now()</x-inputs.date>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.date
            name="fecha_baja"
            label="Fecha Baja"
            value="{{ old('fecha_baja', ($editing ? optional($user->fecha_baja)->format('d-m-Y') : '')) }}"
            max="255"
        ></x-inputs.date>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.date
            name="fecha_cambio"
            label="Fecha Cambio"
            value="{{ old('fecha_cambio', ($editing ? optional($user->fecha_cambio)->format('Y-m-d') : '')) }}"
            max="255"
        ></x-inputs.date>
    </x-inputs.group>
</div>
