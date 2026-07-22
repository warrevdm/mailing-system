<?php

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function buildMailHtml(string $name, string $bikeType, string $pickupNote = ''): string
{
    $safeName = e($name);
    $safeBikeType = e($bikeType);
    $safeNote = nl2br(e($pickupNote));
    $noteBlock = $pickupNote !== ''
        ? '<tr><td style="padding:0 32px 24px;"><div style="padding:16px 18px;background:#f4f7f2;border-left:4px solid #60bb46;border-radius:8px;color:#243229;font-size:15px;line-height:1.6;"><strong>Extra informatie</strong><br>' . $safeNote . '</div></td></tr>'
        : '';

    $bookingUrl = e(BOOKING_URL);

    return <<<HTML
<!doctype html>
<html lang="nl-BE">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Je fiets staat klaar</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f2;font-family:Arial,Helvetica,sans-serif;color:#172019;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f4f2;padding:24px 12px;">
<tr>
<td align="center">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 8px 24px rgba(20,35,24,.08);">
    <tr>
        <td style="background:#172019;padding:28px 32px;">
            <div style="font-size:13px;letter-spacing:1.7px;text-transform:uppercase;color:#9bd889;font-weight:700;">Aerts Action Bike</div>
            <h1 style="margin:10px 0 0;color:#ffffff;font-size:30px;line-height:1.2;">Je fiets staat klaar</h1>
        </td>
    </tr>
    <tr>
        <td style="padding:32px 32px 18px;font-size:16px;line-height:1.7;color:#263229;">
            Dag {$safeName},
            <br><br>
            Goed nieuws: je <strong>{$safeBikeType}</strong> staat klaar voor afhaling bij Aerts Action Bike.
            <br><br>
            Plan hieronder eenvoudig een afhaalmoment in. Zo kunnen we voldoende tijd voorzien om alles rustig samen te overlopen.
        </td>
    </tr>
    <tr>
        <td align="center" style="padding:8px 32px 30px;">
            <a href="{$bookingUrl}" target="_blank" style="display:inline-block;background:#60bb46;color:#102012;text-decoration:none;font-weight:700;font-size:16px;padding:15px 24px;border-radius:8px;">Plan je afhaalmoment</a>
        </td>
    </tr>
    {$noteBlock}
    <tr>
        <td style="padding:0 32px 30px;font-size:15px;line-height:1.7;color:#455148;">
            Heb je nog een vraag? Antwoord gerust op deze mail of neem contact op met onze winkel.
            <br><br>
            Sportieve groeten,<br>
            <strong>Team Aerts Action Bike</strong>
        </td>
    </tr>
    <tr>
        <td style="background:#eef1ed;padding:22px 32px;font-size:13px;line-height:1.6;color:#5b665e;">
            Aerts Action Bike<br>
            Kapellensteenweg 394, 2920 Kalmthout<br>
            +32 (0)3 666 97 01 · www.aertsactionbike.be
        </td>
    </tr>
</table>
</td>
</tr>
</table>
</body>
</html>
HTML;
}

function buildMailText(string $name, string $bikeType, string $pickupNote = ''): string
{
    $text = "Dag {$name},\n\nGoed nieuws: je {$bikeType} staat klaar voor afhaling bij Aerts Action Bike.\n\nPlan je afhaalmoment via:\n" . BOOKING_URL . "\n";

    if ($pickupNote !== '') {
        $text .= "\nExtra informatie:\n{$pickupNote}\n";
    }

    return $text . "\nHeb je nog een vraag? Antwoord gerust op deze mail.\n\nSportieve groeten,\nTeam Aerts Action Bike\nKapellensteenweg 394, 2920 Kalmthout\n+32 (0)3 666 97 01";
}
