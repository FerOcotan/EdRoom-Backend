<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class soliestudiante extends Model
{
    use HasFactory;

    protected $table = 'soliestudiante';
    protected $primaryKey = 'idsoliestudiante';
    public $timestamps = false;

    protected $fillable = [
        'idestudiante',
        'idcurso',
        'fecha',
        'idestado',
    ];

    protected $casts = [
        'idsoliestudiante' => 'integer',
        'idestudiante' => 'integer',
        'idcurso' => 'integer',
        'fecha' => 'datetime',
        'idestado' => 'integer',
    ];

    public function estudiante() {
        return $this->belongsTo(User::class, 'idestudiante', 'id');
    }

    public function curso() {
        return $this->belongsTo(curso::class, 'idcurso', 'idcurso');
    }

    public function estado() {
        return $this->belongsTo(estado::class, 'idestado', 'idestado');
    }
}
