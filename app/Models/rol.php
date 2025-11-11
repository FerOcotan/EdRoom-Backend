<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class rol extends Model
{
    //
    use HasFactory;

    
    protected $table = 'rol';          // Nombre de la tabla
    protected $primaryKey = 'idrol';  // Nombre de la clave primaria
    public $timestamps = true;      // Para ver cuando se crea el registro


    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    // Definir tipo de datos 
    protected $casts = [
        'idrol' => 'integer',
        'nombre' => 'string',
        'descripcion' => 'string',
    ];
    
}
