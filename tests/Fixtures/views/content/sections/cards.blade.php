<section class="cards {{ $classes }}" data-variant="{{ $variant }}">
    <h2>{{ $data['title'] }}</h2>
    {!! $data['intro'] !!}
    @foreach($data['cards'] as $card)
        <article>{{ $card['title'] }}@if($card['link']) <a href="{{ $card['link']['url'] }}">{{ $card['link']['label'] }}</a>@endif</article>
    @endforeach
</section>
