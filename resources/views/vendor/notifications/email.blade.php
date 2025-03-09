<x-mail::message>
    # Hello!

    We received a request to reset your account password.

    @component('mail::button', ['url' => $actionUrl, 'color' => 'success'])
    Reset Password
    @endcomponent

    This password reset link will expire in **{{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60) }} minutes**.

    If you did not request a password reset, no further action is required.

    Best regards,
    {{ config('app.name') }}

    <x-slot:subcopy>
        If you're having trouble clicking the "Reset Password" button, copy and paste the following URL into your browser:
        <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
    </x-slot:subcopy>
</x-mail::message>
