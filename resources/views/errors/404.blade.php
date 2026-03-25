@extends('errors.layout')

@section('code', '404')
@section('title', __('messages.error_404_title'))
@section('message', __('messages.error_404_message'))

@section('icon')
<x-icon.sad-face class="w-8 h-8 text-gray-400 dark:text-gray-500" />
@endsection
