@extends(config('content.layout'))

@section('meta_title', (string) $page->seoTitle())

@if($page->seoDescription())
    @section('meta_description', (string) $page->seoDescription())
@endif

@section('content')
    <x-content::sections :page="$page" />
@endsection
