@extends('layouts.app')

@section('head')
    <script src="{{ asset('js/delete.js') }}" defer></script>
@endsection

@section('content')
    @auth
        @component('components.delete_modal', ['id' => 'delete_modal', 'title' => __('project_delete.confirm')])
            {{ __('project_delete.delete_confirm_message') }}
        @endcomponent
        <form id="delete_form" method="POST" action="{{ route('projects.delete') }}">
            <input type="hidden" name="id" value=""/>
            @csrf
        </form>
    @endauth
    @component('components.container')
        @component('components.card')
            @slot('header')
                Projects
            @endslot

            @auth
                <p>
                    <a href="{{ route('projects.create_form') }}"
                       class="btn btn-primary">
                        {{ __('project_create.create_button') }}
                    </a>
                </p>
            @endauth
            <p>
                {{ __('Filter') }}:
                @php($first = true)
                @foreach(\App\Project::STATES as $state)
                    @if($first)
                        @php($first = false)
                    @else
                        |
                    @endif
                    @php($state_class = request()->has('state') && request('state') === $state ?
                            'current-project-filter' : '')
                    <a href="?state={{ $state }}" class="{{ $state_class }}"
                    >{{ __('project_filter.' . $state) }}</a>
                @endforeach
                |
                <a href="{{ route('projects.index') }}"
                   @if(!request()->has('state')) class="current-project-filter" @endif
                >{{ __('project_filter.all') }}</a>
            </p>

            {{ $projects->links() }}
            @foreach($projects as $project)
                @component('components.project_card', ['project' => $project])
                @endcomponent
                <br>
            @endforeach
            {{ $projects->links() }}
        @endcomponent
    @endcomponent
@endsection
