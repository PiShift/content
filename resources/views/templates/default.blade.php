@extends(config('content.layout'))

@section('meta_title', $page->seoTitle())

@if($page->seoDescription())
    @section('meta_description', $page->seoDescription())
@endif

@section('content')
    <x-content::sections :page="$page" />
@endsection
