@extends('layouts.app')

@section('content')
    @component('components.container')
        @component('components.card')
            @slot('header')
                {{ __('Verify Your Email Address') }}
            @endslot

            @if (session('resent'))
                <div class="alert alert-success" role="alert">
                    {{ __('A fresh verification link has been sent to your email address.') }}
                </div>
            @endif

            {{ __('Before proceeding, please check your email for a verification link.') }}
            {{ __('If you did not receive the email') }}, <a
                href="{{ route('verification.resend') }}">{{ __('click here to request another') }}</a>.
        @endcomponent
    @endcomponent
@endsection
