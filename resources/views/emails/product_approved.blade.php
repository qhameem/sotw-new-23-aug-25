@component('mail::message')
{{-- Logo (replace with your actual logo URL) --}}
{{-- <img src="{{ asset('path/to/your/logo.png') }}" alt="{{ config('app.name') }} Logo" style="display: block; margin-bottom: 20px; max-width: 150px;"> --}}

{{-- Salutation --}}
# Hi {{ $userFirstName }},

We're excited to let you know that your product submission, **"{{ $productName }}"**, has been approved!

**Product Details:**
*   **Product Name:** {{ $productName }}
*   **Submitted On:** {{ $submissionDate }}
*   **Approved On:** {{ $approvalDate }}

You can view your approved product here:
@component('mail::button', ['url' => $productViewLink, 'color' => 'primary']) {{-- 'primary' color will use the theme color --}}
View Product
@endcomponent

@if(isset($dashboardLink))
You can also visit your dashboard for more details:
@component('mail::button', ['url' => $dashboardLink])
Go to Dashboard
@endcomponent
@endif

Thanks for being a part of our community!

Sincerely,
The {{ config('app.name') }} Team

{{-- Unsubscribe Link --}}
@slot('subcopy')
If you no longer wish to receive these notifications, you can update your preferences in your account settings or [unsubscribe here]( {{-- TODO: Add unsubscribe link --}} ).
@endslot
@endcomponent
