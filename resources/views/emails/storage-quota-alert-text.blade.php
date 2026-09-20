[{{ $levelLabel }}] {!! __('messages.email_storage_quota_title') !!}

{!! __('messages.email_storage_quota_intro', ['app' => config('app.name'), 'percent' => $percent]) !!}

{!! __('messages.email_storage_quota_used_label') !!}: {{ $used }}
{!! __('messages.email_storage_quota_total_label') !!}: {{ $total }}

{!! __('messages.email_storage_quota_consequence') !!}

{!! __('messages.email_storage_quota_action') !!}

--
{!! __('messages.email_footer', ['app' => config('app.name')]) !!}
