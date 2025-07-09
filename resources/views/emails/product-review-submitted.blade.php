<x-mail::message>
# New Product Review Submission

A new product review has been submitted.

**Product URL:** [{{ $productReview->product_url }}]({{ $productReview->product_url }})
**Product Creator:** {{ $productReview->product_creator }}
**Email:** {{ $productReview->email }}

**Access Instructions:**
{{ $productReview->access_instructions }}

**Other Instructions:**
{{ $productReview->other_instructions }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>