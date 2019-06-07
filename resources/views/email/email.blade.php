<pre>
{{ __('Hello!') }}

    {{ __('email.' . $action) }}@if(!is_null($extra))@foreach($extra as $key => $value)
        {{ $key }}: {{ $value }}@endforeach @endif

    {{ __('ProjectEmail') }}: {{ $projectName }}
</pre>
