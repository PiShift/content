@if($page)
    @foreach($page->sections() as $section)
        {!! $section->html() !!}
    @endforeach
@endif
