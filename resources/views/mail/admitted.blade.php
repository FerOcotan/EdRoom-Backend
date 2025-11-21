<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admisión a curso - EDROOM</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background:#f7f7f7; margin:0; padding:20px; color:#222;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="background:#ffffff; padding:20px; border-radius:6px; box-shadow:0 2px 6px rgba(0,0,0,0.06);">

                    <tr>
                        <td align="center" style="padding-bottom:18px;">
                            <div style="font-size:34px; font-weight:700; letter-spacing:2px;">
                                <span style="color:#ff914d;">ED</span><span style="color:#291958;">ROOM</span>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <p style="margin:0 0 6px 0;">Hola <strong>{{ $studentName }}</strong>,</p>

                            <p style="margin:0 0 6px 0;">¡Felicidades! Has sido admitido/a al curso:</p>

                            <p style="font-size:16px; font-weight:600; color:#111; margin:8px 0 10px 0;">{{ $classTitle }}</p>

                            @if(!empty($classUrl))
                            <p style="margin:10px 0 6px 0;">Puedes acceder al curso desde el siguiente enlace:</p>
                            <p style="text-align:center; margin:10px 0;"><a href="{{ $classUrl }}" style="background:#5b825d; color:#ffffff; padding:10px 18px; border-radius:4px; text-decoration:none; display:inline-block;">Ir al curso</a></p>
                            @endif

                            @if(!empty($notes))
                            <p style="background:#f1f1f1; padding:10px; border-radius:4px; white-space:pre-wrap; margin:0 0 8px 0;">{{ $notes }}</p>
                            @endif

                            <p style="margin-top:22px;">Atentamente,<br>Equipo EDROOM</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding-top:12px; text-align:center; font-size:12px; color:#777;">
                            <p style="margin:0;">Este correo fue generado automáticamente. Por favor, no responda a este mensaje.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
