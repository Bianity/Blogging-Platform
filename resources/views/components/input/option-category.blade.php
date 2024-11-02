<option value="{{ $category->id }}">
    {{ $name }} / {{ $category->name }}
</option>

@foreach($category->children as $child)
    @include('components.input.option-category', ['name' => "{$name} / {$category->name}", 'category' => $child])
@endforeach