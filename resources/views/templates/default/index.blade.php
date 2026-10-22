@extends('templates.default.layouts.app')
@section('content')
@if(!empty($hero->title))
@include('templates.default.partials.hero', ['hero' => $hero])
@endif

@if(!empty($stats))
@include('templates.default.partials.stats', ['stats' => $stats])
@endif

@if(!empty($about->title))
@include('templates.default.partials.about', ['about' => $about])
@endif

@if($courses->count())
@include('templates.default.partials.courses', ['courses' => $courses])
@endif

@include('templates.default.partials.news', ['posts' => $posts])

@include('templates.default.partials.contact')
@endsection