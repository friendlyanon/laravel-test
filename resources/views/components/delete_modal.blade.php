<div class="modal fade" id="{{ $id }}" tabindex="-1" role="dialog" aria-labelledby="{{ $id }}Title"
     aria-hidden="true">
    <div id="delete_modal_localizations">
        <span class="success">{{ __('Success') }}</span>
        <span class="deleting">{{ __('project_delete.deleting') }}</span>
    </div>
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $id }}LongTitle">{{ $title }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {{ $slot }}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal"
                        id="delete_button">{{ __('Delete') }}</button>
            </div>
        </div>
    </div>
</div>
