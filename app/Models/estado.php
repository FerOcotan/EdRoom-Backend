<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class estado extends Model
{
    use HasFactory;

    // Nombre de la tabla
    protected $table = 'estado';

    // Clave primaria personalizada
    protected $primaryKey = 'idestado';

    // Si no usas timestamps (created_at, updated_at)
    public $timestamps = false;

    // Campos que pueden ser asignados en masa
    protected $fillable = [
        'estado',
        'descripcion',
    ];

    // Cast de tipos (convierte automáticamente al acceder o guardar)
    protected $casts = [
        'idestado' => 'integer',
        'estado' => 'string',
        'descripcion' => 'string',
    ];
    
}
