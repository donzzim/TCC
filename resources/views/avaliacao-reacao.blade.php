<x-layouts.portal>
<div class="max-w-3xl p-4 mx-auto my-10 bg-white rounded-lg shadow-sm">
    <h1 class="mb-4 text-3xl font-bold">{{ $form->name }}</h1>
    <h1 class="mb-4 text-2xl">{{ $curso->titulo }}</h1>

    @if(session('success'))
        <div class="p-3 mb-4 text-green-800 bg-green-100 rounded">
            {{ session('success') }}
        </div>
    @endif
    @if($preenchido)
        <div class="p-6 mb-4 text-center text-gray-700 bg-yellow-100 rounded">
           O formulário de avaliação deste curso já foi preenchido.
        </div>
    @else
        <form method="POST" action="{{ route('forms.submit', $form->slug) }}">
            @csrf
            <input type="hidden" name="aluno" value="{{ $aluno->uuid }}" />

            @foreach($form->fields as $field)
                <div class="p-5 mb-5 border-2 rounded-md border-slate-200">
                    <label class="block mb-1 text-lg font-semibold">
                        {{ $field->label }} @if($field->required)<span class="text-red-700">*</span>@endif
                    </label>

                    @switch($field->type)
                        @case('text')
                            <input type="text" id="field_{{ $field->id }}" name="field_{{ $field->id }}" class="w-full p-2 border rounded" class="@error('title') is-invalid @enderror" />
                            @error("field_{$field->id}")
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                            @break

                        @case('textarea')
                            <textarea id="field_{{ $field->id }}" name="field_{{ $field->id }}" class="w-full p-2 border rounded"></textarea>
                            @error("field_{$field->id}")
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                            @break

                        @case('select')
                            <select id="field_{{ $field->id }}" name="field_{{ $field->id }}" class="w-full p-2 border rounded">
                                <option value="">selecione uma opção</option>
                            @foreach($field->options as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                            @error("field_{$field->id}")
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                            @break

                        @case('select-multiple')
                            <select id="field_{{ $field->id }}" name="field_{{ $field->id }}" class="w-full p-2 border rounded" multiple="multiple">
                                <option value="">selecione uma opção</option>
                            @foreach($field->options as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                            @error("field_{$field->id}")
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                            @break

                        @case('radio')
                            <div class="flex">
                                @foreach($field->options as $option)
                                <div class="flex items-center me-4">
                                    <input type="radio" id="field_{{ $field->id }}" name="field_{{ $field->id }}" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" value="{{ $option }}">
                                    <label for="field_{{ $field->id }}" class="text-sm font-medium text-gray-900 ms-2 dark:text-gray-300">{{ $option }}</label>
                                </div>
                                @endforeach
                            </div>
                            @error("field_{$field->id}")
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                            @break

                        @case('checkbox')
                            <div class="flex">
                            @foreach($field->options as $option)
                            <div class="flex items-center mb-4">
                                <input type="checkbox" id="field_{{ $field->id }}" name="field_{{ $field->id }}" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" value="{{ $option }}">
                                <label for="field_{{ $field->id }}" class="text-sm font-medium text-gray-900 ms-2 dark:text-gray-300">{{ $option }}</label>
                            </div>
                            @endforeach
                            </div>
                            @error("field_{$field->id}")
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                            @break

                    @endswitch

                    @isset($errors)
                        @error('fields.' . $field->id)
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    @endisset

                </div>
            @endforeach

            <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded">
                Enviar
            </button>
        </form>
    @endif
</div>
</x-layouts.portal>
