<?php

namespace App\Imports;

use App\Models\FasecoldaValor;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class FasecoldaImport implements ToCollection
{
    protected $codigoFasecolda;
    
    public function __construct($codigoFasecolda)
    {
        $this->codigoFasecolda = $codigoFasecolda;
    }
    
    public function collection(Collection $rows)
    {
        // Saltar la primera fila (encabezados)
        foreach ($rows->skip(1) as $row) {
            // Procesar CLASIFICADOS (columnas A-B)
            if (!empty($row[0]) && !empty($row[1])) {
                FasecoldaValor::create([
                    'codigo_fasecolda' => $this->codigoFasecolda,
                    'tipo' => 'clasificado',
                    'modelo' => $row[0],
                    'valor' => $row[1]
                ]);
            }
            
            // Procesar CORREGIDOS (columnas C-D)
            if (!empty($row[2]) && !empty($row[3])) {
                FasecoldaValor::create([
                    'codigo_fasecolda' => $this->codigoFasecolda,
                    'tipo' => 'corregido',
                    'modelo' => $row[2],
                    'valor' => $row[3]
                ]);
            }
        }
    }
}