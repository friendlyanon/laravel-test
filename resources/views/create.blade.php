@extends('layouts.app')

@section('head')
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/css/bootstrap-select.min.css">

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/js/bootstrap-select.min.js" defer></script>

    <script src="{{ asset('js/edit.js') }}" defer></script>
@endsection

@section('content')
    @component('components.container')
        @component('components.card')
            @slot('header')
                {{ __('project_create.title') }}
            @endslot
            @component('components.project_form', [
                'actionRoute' => route('projects.create'),
                'name' => '',
                'description' => '',
                'cancelRoute' => route('projects.index'),
            ])
                @slot('id')
                @endslot
                @slot('states')
                    @foreach(\App\Project::STATES as $state)
                        <option value="{{ $state }}">
                            {{ __('project_filter.' . $state) }}
                        </option>
                    @endforeach
                @endslot
                @slot('assignees')
                @endslot
            @endcomponent
        @endcomponent
    @endcomponent
@endsection
