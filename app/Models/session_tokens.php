<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class session_tokens extends Model
{
    //
    use HasFactory;

    // Nombre de la tabla
    protected $table = 'session_tokens';
    // Clave primaria
    protected $primaryKey = 'id';

    // Indica que la clave es auto-incremental y de tipo entero sin signo
    public $incrementing = true;
    protected $keyType = 'int';

    // Permitir timestamps (created_at y updated_at)
    public $timestamps = true;

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
    ];

    // Cast de tipos
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relación con el modelo User (si existe)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
}
