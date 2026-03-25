@extends('errors.layout')

@section('code', '403')
@section('title', __('messages.error_403_title'))
@section('message', __('messages.error_403_message'))

@section('icon')
<x-icon.ban class="w-8 h-8 text-red-400 dark:text-red-500" />
@endsection
