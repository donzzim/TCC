<nav>
    <ul>
        @foreach ($items as $item)
        <li>
            <a href="{{ url($item->url) }}" @if($item->open_in_new_tab) target="_blank" @endif>
                {{ $item->label }}
            </a>

            {{-- Se houver filhos, renderiza um submenu --}}
            @if ($item->children->isNotEmpty())
            {{-- Para menus mais complexos, considere usar um componente Blade recursivo --}}
            <ul>
                @foreach ($item->children as $child)
                <li>
                    <a href="{{ url($child->url) }}" @if($child->open_in_new_tab) target="_blank" @endif>
                        {{ $child->label }}
                    </a>
                </li>
                @endforeach
            </ul>
            @endif
        </li>
        @endforeach
    </ul>
</nav>