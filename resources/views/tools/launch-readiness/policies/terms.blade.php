@extends('tools.launch-readiness.policies.layout', ['policyTitle' => 'Terms and Conditions'])

@section('meta_description', 'Terms for using the Website Launch Readiness Checker.')

@section('policy')
    <section>
        <h2>Using the checker</h2>
        <p>Scan only websites you own or have permission to check. Keep passwords, access tokens, and private links out of submitted URLs. Do not try to bypass scan limits or disrupt the service.</p>
    </section>

    <section>
        <h2>Reports and sharing</h2>
        <p>A report shows what the checker found when it ran. It can miss issues, so check important findings yourself before you launch. Anyone with a report link can open it. Turning on “Save to public history” also lists the scan on Public Scans.</p>
    </section>

    <section>
        <h2>Questions</h2>
        <p>Email <a href="mailto:{{ config('agent_access.contact') }}">{{ config('agent_access.contact') }}</a> for help with the checker.</p>
    </section>
@endsection
