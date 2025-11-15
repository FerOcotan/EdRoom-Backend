
@component('mail::message')
# Nueva solicitud de ingreso a la clase

El estudiante **{{ $requesterName }}** ({{ $requesterEmail }}) ha solicitado unirse a la clase:

> **{{ $classTitle }}**  
> ID de clase: **{{ $classId }}**

@if(!empty($notes))
### Mensaje del estudiante

> {{ $notes }}
@endif

@if(!empty($joinUrl))
Puedes ver los detalles de la clase o gestionar la solicitud aquí:

@component('mail::button', ['url' => $joinUrl])
Ver clase en EdRoom
@endcomponent
@endif

Saludos,  
El equipo de **EdRoom**
@endcomponent
