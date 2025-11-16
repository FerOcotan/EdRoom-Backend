<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\JoinClassRequestMail;

class ClassJoinController extends Controller
{
    // Si luego quieres protegerlo con tu middleware, se puede:
    // public function __construct()
    // {
    //     $this->middleware('beeart');
    // }

    /**
     * Recibe POST /api/classes/{classId}/join-request
     */
    public function store(Request $request, string $classId)
    {
        // 1) Validar payload que viene del frontend
        $data = $request->validate([
            'requester_name'  => 'required|string|max:120',
            'requester_email' => 'required|email',
            'class_title'     => 'required|string|max:180',
            'notes'           => 'nullable|string|max:1000',
            'join_url'        => 'nullable|url',
        ]);

        // 2) Buscar el curso y el creador (docente) en la BD
        //    Tabla: curso
        //    FK: curso.idusuario -> users.id -> users.email
        $course = DB::table('curso')
            ->join('users', 'curso.idusuario', '=', 'users.id')
            ->where('curso.idcurso', $classId)
            ->select(
                'curso.idcurso',
                'curso.nombre as curso_nombre',
                'users.email as teacher_email',
                'users.name as teacher_name'
            )
            ->first();

        if (!$course) {
            return response()->json([
                'ok'      => false,
                'message' => 'No se encontró el curso indicado.',
            ], 404);
        }

        // 3) Destinatario principal: el docente creador del curso
        $to = $course->teacher_email ?? null;

        // 4) Fallback: si por alguna razón no tiene email,
        //    usamos MAIL_CLASS_REQUEST_TO como respaldo
        if (empty($to)) {
            $to = config('mail.class_request_to', env('MAIL_CLASS_REQUEST_TO'));
        }

        if (empty($to)) {
            return response()->json([
                'ok'      => false,
                'message' => 'No se pudo determinar el destinatario del correo (teacher_email o MAIL_CLASS_REQUEST_TO).',
            ], 500);
        }

        // Loguear destinatario y payload para depuración (revisar storage/logs/laravel.log)
        try {
            Log::info('join-request: enviando solicitud de unión', [
                'class_id' => $classId,
                'to'       => $to,
                'payload'  => $data,
            ]);
        } catch (\Throwable $e) {
            // No bloquear el flujo si el log falla por alguna razón
        }

        // 5) Enviar correo
        // Forzar que el enlace del correo lleve siempre a la página de solicitudes
        // en el frontend (no redirigir directamente al curso). Se toma la base
        // desde la configuración 'app.frontend_url' / env FRONTEND_URL.
        $frontendBase = config('app.frontend_url', env('FRONTEND_URL')) ?: config('app.url');
        $joinUrl = rtrim($frontendBase, '/') . '/solicitudes';

        try {
            Mail::to($to)->send(new JoinClassRequestMail(
                $data['requester_name'],
                $data['requester_email'],
                $data['class_title'],  // título que viene del front
                $classId,
                $data['notes'] ?? null,
                $joinUrl
            ));
        } catch (\Throwable $e) {
            Log::error('join-request mail error', [
                'class_id' => $classId,
                'to'       => $to,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'No se pudo enviar el correo (revisa configuración de mail).',
                'detail'  => $e->getMessage(), // si quieres, quita esto en producción
            ], 500);
        }

        return response()->json([
            'ok'      => true,
            'message' => 'Solicitud enviada correctamente al docente.',
        ]);
    }
}
