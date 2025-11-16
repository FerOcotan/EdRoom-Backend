<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Solicitud de ingreso - EDROOM</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background:#f7f7f7; margin:0; padding:20px; color:#222;">
	<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
		<tr>
			<td align="center">
				<table width="600" cellpadding="0" cellspacing="0" role="presentation" style="background:#ffffff; padding:20px; border-radius:6px; box-shadow:0 2px 6px rgba(0,0,0,0.06);">
					
					<!-- ENCABEZADO -->
					<tr>
						<td align="center" style="padding-bottom:18px;">
							<div style="font-size:34px; font-weight:700; letter-spacing:2px;">
								<span style="color:#ff914d;">ED</span><span style="color:#291958;">ROOM</span>
							</div>
							<div style="font-size:14px; color:#5b825d; font-weight:600; margin-top:6px;">Tu aula, siempre lista</div>
						</td>
					</tr>

					<!-- CONTENIDO -->
					<tr>
						<td>
							<p style="margin:0 0 6px 0;">Estimado/a,</p>

							<p style="margin:0 0 6px 0;">Le informamos que el estudiante <strong>{{ $requesterName }}</strong> con correo <a href="mailto:{{ $requesterEmail }}" style="color:inherit; text-decoration:none; font-weight:700;">{{ $requesterEmail }}</a> ha solicitado unirse al curso:</p>

							<p style="font-size:16px; font-weight:600; color:#111; margin:8px 0 10px 0;">{{ $classTitle }}</p>

						<!-- NOTAS -->
							@if(!empty($notes))
							<p style="background:#f1f1f1; padding:10px; border-radius:4px; white-space:pre-wrap; margin:0 0 8px 0;">{{ $notes }}</p>
							@endif

							<!-- BOTÓN -->
							@if(!empty($joinUrl))
							<p style="margin:10px 0 6px 0;">Para ver las solicitudes pendientes, ingrese con el siguiente botón:</p>

							<p style="text-align:center; margin:10px 0;">
								<a href="{{ $joinUrl }}" style="background:#5b825d; color:#ffffff; padding:10px 18px; border-radius:4px; text-decoration:none; display:inline-block;">Ver solicitudes de ingreso</a>
							</p>
							@endif

							<p style="margin-top:22px;">
								Atentamente,<br>
								Equipo EDROOM
							</p>
						</td>
					</tr>

					<!-- FOOTER -->
					<tr>
						<td style="padding-top:12px; text-align:center; font-size:12px; color:#777;">
							<p style="margin:0;">
								Este correo fue generado automáticamente. Por favor, no responda a este mensaje.
							</p>
						</td>
					</tr>

				</table>
			</td>
		</tr>
	</table>
</body>
</html>
