<x-mail::message>
# {{ __('api.emails.welcome_title', ['name' => $user->name]) }}

{{ __('api.emails.welcome_message', ['name' => $user->name, 'company' => config('app.name')]) }}

## {{ __('api.emails.your_account_details') }}

**{{ __('api.emails.name') }}:** {{ $user->name }}  
**{{ __('api.emails.email') }}:** {{ $user->email }}  
**{{ __('api.emails.role') }}:** {{ $user->roles->first()?->name === 'manager' ? __('api.emails.manager') : __('api.emails.collaborator') }}

{{ __('api.emails.welcome_footer') }}

{{ __('api.emails.regards') }},<br>
{{ config('app.name') }}
</x-mail::message>
