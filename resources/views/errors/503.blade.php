@extends('errors.layout')

@section('code', '503')
@section('title', __('messages.error_503_title'))
@section('message', __('messages.error_503_message'))

@section('icon')
<x-icon.settings class="w-8 h-8 text-amber-400 dark:text-amber-500" />
@endsection
