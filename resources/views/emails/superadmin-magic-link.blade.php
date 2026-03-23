@extends('emails.layouts.magic-link')

@section('title', __('messages.email_superadmin_subject'))

@section('gradient-start', '#d97706')
@section('gradient-end', '#ea580c')

@section('badge')
            <span class="badge">{{ __('messages.superadmin_title') }}</span>
@endsection

@section('intro', __('messages.email_superadmin_intro'))
@section('button-text', __('messages.email_superadmin_button'))
