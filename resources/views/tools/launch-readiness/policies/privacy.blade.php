@extends('tools.launch-readiness.policies.layout', ['policyTitle' => 'Privacy Policy'])

@section('meta_description', 'How the Website Launch Readiness Checker handles scans, accounts, and cookies.')

@section('policy')
    <section>
        <h2>What we store</h2>
        <p>We store the URL you submit, the scan results, and the scan time. We use session cookies to keep you signed in and a guest identifier to enforce scan limits. If you sign in, we store your email address and display name. If you use Google sign-in, we also store your Google account ID and profile picture when available.</p>
    </section>

    <section>
        <h2>Who can see a scan</h2>
        <p>Anyone with the report link can open it. Turning on “Save to public history” also adds the website and its results to Public Scans.</p>
        <p class="mt-2">When you submit a product to Software on the Web, we use its website URL to run or look up a scan. New, completed submission scans appear in Public Scans with the URL, scan time, and results. Previously unlisted reports stay unlisted.</p>
    </section>

    <section>
        <h2>Deleting your data</h2>
        <p>You can delete saved scans from your dashboard or delete your tool account and its scans in Settings. For guest scans or other privacy requests, email <a href="mailto:{{ config('agent_access.contact') }}">{{ config('agent_access.contact') }}</a>.</p>
        <p class="mt-2">See the <a href="{{ route('legal') }}#cookie-policy">Software on the Web cookie policy</a> for more about site cookies.</p>
    </section>
@endsection
