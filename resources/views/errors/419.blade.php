@extends('errors.layout')

@section('code', '419')
@section('title', __('messages.error_419_title'))
@section('message', __('messages.error_419_message'))

@section('icon')
<x-icon.clock class="w-8 h-8 text-amber-400 dark:text-amber-500" />
@endsection
