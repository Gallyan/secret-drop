@extends('errors.layout')

@section('code', '500')
@section('title', __('messages.error_server_title'))
@section('message', __('messages.error_server_message'))

@section('icon')
<x-icon.exclamation-circle class="w-8 h-8 text-red-400 dark:text-red-500" />
@endsection
