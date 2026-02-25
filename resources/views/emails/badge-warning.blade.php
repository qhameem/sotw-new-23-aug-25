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
        <h1 style="margin:0;font-size:20px;">⚠️ Badge Verification Warning</h1>
    </div>
    <div class="content">
        <p>Hi,</p>
        <p>We noticed that the <strong>Software on the Web</strong> badge is missing from your website for
            <strong>{{ $product->name }}</strong>.</p>

        <div class="warning">
            <strong>⚠️ Important:</strong> If the badge remains missing, your listing will be unpublished after a grace
            period.
        </div>

        <p>To keep your listing active, please ensure the following HTML snippet is placed on your website:</p>

        <div class="badge-code">&lt;a href="{{ url('/product/' . $product->slug) }}" rel="dofollow"&gt;
            &lt;img src="{{ url('/images/badge.png') }}" alt="Featured on Software on the Web"&gt;
            &lt;/a&gt;</div>

        <p>If you've already added the badge, please allow up to a week for our next verification check.</p>

        <a href="{{ url('/product/' . $product->slug) }}" class="btn">View Your Listing</a>

        <p style="margin-top:24px;color:#6b7280;font-size:13px;">If you believe this is an error, please contact us by
            replying to this email.</p>
    </div>
</body>

</html>