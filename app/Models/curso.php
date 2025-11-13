<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class curso extends Model
{
    use HasFactory;

    protected $table = 'curso';
    protected $primaryKey = 'idcurso';
    public $timestamps = false;

    protected $fillable = [
        'idusuario',
        'nombre',
        'descripcion',
        'maximoest',
        'estuinscritos',
        'idestado',
    ];

    protected $casts = [
        'idcurso' => 'integer',
        'idusuario' => 'integer',
        'nombre' => 'string',
        'descripcion' => 'string',
        'maximoest' => 'integer',
        'estuinscritos' => 'integer',
        'idestado' => 'integer',
    ];

    // Relaciones
    public function user() {
        return $this->belongsTo(User::class, 'idusuario', 'id');
    }

    public function estado() {
        return $this->belongsTo(estado::class, 'idestado', 'idestado');
    }

    public function clases() {
        return $this->hasMany(clase::class, 'idcurso', 'idcurso');
    }
}
