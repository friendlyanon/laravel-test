<form id="edit_form" method="POST" action="{{ $actionRoute }}">
    @csrf
    {{ $id }}

    <div class="form-group row">
        <label for="name" class="col-md-4 col-form-label text-md-right">{{ __('Name') }}</label>

        <div class="col-md-6">
            <input id="name" type="text" class="form-control" name="name"
                   value="{{ $name }}" required autocomplete="name" autofocus>
        </div>
    </div>

    <div class="form-group row">
        <label for="description" class="col-md-4 col-form-label text-md-right">{{ __('Description') }}</label>

        <div class="col-md-6">
                        <textarea id="description" class="form-control" name="description"
                        >{{ $description }}</textarea>
        </div>
    </div>

    <div class="form-group row">
        <label for="state" class="col-md-4 col-form-label text-md-right">{{ __('State') }}</label>

        <div class="col-md-6">
            <select id="state" name="state">
                {{ $states }}
            </select>
        </div>
    </div>

    <div class="form-group row">
        <label for="assignees" class="col-md-4 col-form-label text-md-right">{{ __('Assignees') }}</label>

        <div class="col-md-6">
            <noscript>
                {{ __('Please enter assignees\' data in the following format') }}:
                <pre>name{{ "\n" }}email</pre>
                {{ __('Assignees\' entries should be separated by a newline.') }}
                <br><br>
            </noscript>
            <textarea id="assignees" class="form-control" name="assignees"
            >{{ trim($assignees) }}</textarea>
            <div style="display: none;">
                <p>{{ __('To delete an assignee, just leave their row empty.') }}</p>
                <table id="assignee_table" class="table">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                    <tr>
                        <td colspan="3">
                            <button class="btn btn-primary">{{ __('Add new') }}</button>
                        </td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="form-group row mb-0">
        <div class="col-md-6 offset-md-4">
            <button type="submit" class="btn btn-primary">
                {{ __('Save changes') }}
            </button>

            <a href="{{ $cancelRoute }}"
               class="btn btn-secondary">
                {{ __('Cancel') }}
            </a>
        </div>
    </div>
</form>
