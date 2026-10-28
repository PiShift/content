@if($page)
    @foreach($page->sections() as $section)
        @if($section->viewExists())
            @if($page->isPreview)
                <div data-content-section="{{ $section->id }}" data-content-type="{{ $section->type->key() }}">{!! $section->render() !!}</div>
            @else
                {!! $section->render() !!}
            @endif
        @elseif(config('app.debug'))
            <!-- content: missing view {{ $section->type->view() }} -->
        @endif
    @endforeach
@endif
