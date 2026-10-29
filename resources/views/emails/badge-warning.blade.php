<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: #f43f5e;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }

        .content {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-top: none;
            padding: 24px;
            border-radius: 0 0 8px 8px;
        }

        .badge-code {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 16px;
            margin: 16px 0;
            font-family: monospace;
            font-size: 13px;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .btn {
            display: inline-block;
            background: #f43f5e;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 16px;
        }

        .warning {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            padding: 12px;
            border-radius: 6px;
            margin: 16px 0;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1 style="margin:0;font-size:20px;">Badge Verification Failed</h1>
    </div>
    <div class="content">
        <p>Hi,</p>
        <p>Badge verification failed for <strong>{{ $product->name }}</strong>.</p>
        <p>{{ $failureMessage ?? 'We could not verify the Software on the Web badge on your website. Please check the badge and its backlink.' }}</p>

        <div class="warning">
            <strong>Important:</strong>
            @if($wasPublished)
                Your listing has been unpublished because badge verification failed.
            @else
                Your listing cannot be published until badge verification succeeds.
            @endif
        </div>

        <p>To keep your listing active, please ensure the following HTML snippet is placed on your website:</p>

        <div class="badge-code">&lt;a href="{{ url('/') }}" rel="dofollow"&gt;
            &lt;img src="{{ url('/images/badge.png') }}" alt="Featured on Software on the Web"&gt;
            &lt;/a&gt;</div>

        <p>After fixing the issue, contact us or wait for the next automatic check.
            @if($wasPublished)
                If your listing was unpublished, contact us to restore publication after verification succeeds.
            @endif</p>

        <a href="{{ url('/product/' . $product->slug) }}" class="btn">View Your Listing</a>

        <p style="margin-top:24px;color:#6b7280;font-size:13px;">If you believe this is an error, please contact us by
            replying to this email.</p>
    </div>
</body>

</html>
