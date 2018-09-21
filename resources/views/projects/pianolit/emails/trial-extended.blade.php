@component('mail::message')
# Hello {{$user->first_name}}

We've added 7 more days to your trial period! It now ends on <strong>{{$user->trial_ends_at->toFormattedDateString()}}</strong>. Enjoy :)

Thanks,<br>
{{ config('app.name') }}
@endcomponent
