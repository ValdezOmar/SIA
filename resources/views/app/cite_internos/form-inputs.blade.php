@php $editing = isset($citeInterno) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.text
            name="cite_interno"
            label="Cite Interno"
            :value="old('cite_interno', ($editing ? $citeInterno->cite_interno : ''))"
            maxlength="255"
            placeholder="Cite Interno"
            required
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-lg-6">
        <x-inputs.text
            name="area"
            label="Area"
            :value="old('area', '')"
            maxlength="255"
            placeholder="Area"
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="asunto"
            label="Asunto"
            :value="old('asunto', ($editing ? $citeInterno->asunto : ''))"
            maxlength="255"
            placeholder="Asunto"
            required
        ></x-inputs.text>
    </x-inputs.group>
</div>
