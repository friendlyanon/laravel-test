@component('components.card')
    @slot('header')
        <a href="{{ route('projects.show', $project->id) }}">
            {{ $project->name }}
        </a>
    @endslot
    <h5 class="card-title">{{ __('Project state') }}: {{ __('project_filter.' . $project->state) }}</h5>

    <p>
        @php($userCount = $project->getAssignedUserCount())
        {{ trans_choice('project_show.assigned', $userCount, ['count' => $userCount]) }}
    </p>

    @auth
        <a href="{{ route('projects.edit_form', ['id' => $project->id]) }}"
           class="btn btn-success">
            {{ __('Edit') }}
        </a>
        <form method="POST"
              action="{{ route('projects.delete_nojs', ['id' => $project->id]) }}"
              class="delete_form">
            @csrf
            <button type="submit" class="btn btn-danger delete-modal"
                    data-id="{{ $project->id }}">
                {{ __('Delete') }}
            </button>
        </form>
    @endauth
@endcomponent
