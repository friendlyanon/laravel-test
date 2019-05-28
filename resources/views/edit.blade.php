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
                {{ __('Editing') }} {{ $project->name }}
            @endslot
            @component('components.project_form', [
                'actionRoute' => route('projects.edit', ['id' => $project->id]),
                'name' => $project->name,
                'description' => $project->description,
                'cancelRoute' => route('projects.show', ['id' => $project->id]),
            ])
                @slot('id')
                    <input type="hidden" name="id" value="{{ $project->id }}"/>
                @endslot
                @slot('states')
                    @foreach(\App\Project::states as $state)
                        <option value="{{ $state }}"
                                @if($project->state === $state)
                                selected
                            @endif>
                            {{ Lang::get('project_filter.' . $state) }}
                        </option>
                    @endforeach
                @endslot
                @slot('assignees')
                    @php($first = true)
                    @foreach($project->getAssignedUsers() as $assignee)
                        {{
                            $first ? ($first = false) || '' : "\n\n"
                        }}{{
                            $assignee->name
                        }}{{ "\n" }}{{
                            $assignee->email
                        }}
                    @endforeach
                @endslot
            @endcomponent
        @endcomponent
    @endcomponent
@endsection
