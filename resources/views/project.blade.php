@extends('layouts.app')

@section('head')
    <script src="{{ asset('js/delete.js') }}" defer></script>
@endsection

@section('content')
    @auth
        @component('components.delete_modal', ['id' => 'delete_modal', 'title' => __('project_delete.confirm')])
            {{ __('project_delete.delete_confirm_message') }}
        @endcomponent
        <form id="delete_form" method="POST" action="{{ route('projects.delete') }}"
              data-show="{{ route('projects.index') }}">
            <input type="hidden" name="id" value=""/>
            @csrf
        </form>
    @endauth
    @component('components.container')
        @component('components.card')
            @slot('header')
                {{ $project->name }}
            @endslot
            @auth
                <div class="project_buttons">
                    <a href="{{ route('projects.edit_form', ['id' => $project->id]) }}"
                       class="btn btn-success">
                        {{ __('project_show.edit') }}
                    </a>
                    <form method="POST"
                          action="{{ route('projects.delete_nojs', ['id' => $project->id]) }}"
                          class="delete_form">
                        @csrf
                        <button type="submit" class="btn btn-danger delete-modal"
                                data-id="{{ $project->id }}">
                            {{ __('project_show.delete') }}
                        </button>
                    </form>
                </div>
            @endauth
            <h4>{{ __('ProjectEmail') }}: {{ __('project_filter.' . $project->state) }}</h4>
            <h5>{{ __('project_show.description_heading') }}:</h5>
            <p>{{ $project->description }}</p>
            <h5>{{ __('project_show.assignees_heading') }}:</h5>
            <table class="table">
                <thead>
                <tr>
                    <th>{{ __('project_show.name') }}</th>
                    <th>{{ __('project_show.email') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($project->getAssignedUsers() as $assignee)
                    <tr>
                        <td>{{ $assignee->name }}</td>
                        <td>{{ $assignee->email }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endcomponent
    @endcomponent
@endsection
