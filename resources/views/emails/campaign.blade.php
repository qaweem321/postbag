<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#18181b;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:24px 12px;">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:8px;">
<tr><td style="padding:32px;font-size:16px;line-height:1.6;">
{!! $body !!}
</td></tr>
</table>
<p style="max-width:600px;margin:16px auto 0;font-size:12px;line-height:1.5;color:#71717a;text-align:center;">
You receive this because you subscribed to {{ $listName }}.
<a href="{{ $unsubscribeUrl }}" style="color:#71717a;">Unsubscribe</a>
</p>
@if ($pixelUrl)
<img src="{{ $pixelUrl }}" width="1" height="1" alt="" style="display:block;border:0;width:1px;height:1px;">
@endif
</td></tr>
</table>
</body>
</html>
