<?php

namespace App\Imports;

use App\Models\Ingreso;
use App\Models\Avaluo;
use App\Models\User;
use App\Models\Inspeccion;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;


class IngresosMovilidadImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        
        foreach ($rows as $row) {
            // 1️⃣ Crear el ingreso
            
            $ingreso = Ingreso::create([
                'tiposervicio'              =>'Sec Bogota',
                'placa'                    => $row['placa'] ?? null,
                'marca'                    => $row['marca'] ?? null,
                'linea'                    => $row['linea'] ?? null,
                'tipo_carroceria'          => $row['tipo_carroceria'] ?? null,
                'modelo'                   => $row['modelo'] ?? null,
                'cilindraje'               => $row['cilindraje'] ?? null,
                'color'                    => $row['color'] ?? null,
                'numero_motor'             => $row['numero_motor'] ?? null,
                'numero_chasis'            => $row['numero_de_chasis'] ?? null,
                'clase'                    => $row['clase'] ?? null,                
                'numeroVin'                => $row['numero_vin'] ?? null,
                'tipo_servicio_vehiculo'   => $row['tipo_de_servicio'] ?? null,
                'fecha_solicitud'=> $this->parseExcelDate($row['fecha_solicitud'] ?? null),
                'fecha_inspeccion'=> $this->parseExcelDate($row['fecha_inspeccion'] ?? null),
                'estado_registro_runt'   => $row['estado_runt'] ?? null,
                'organismo_transito'   => $row['organismo_de_transito'] ?? null,
                'fecha_ingreso'=> $this->parseExcelDate($row['fecha_ingreso'] ?? null),
                'estado'                   => 'En Inspección',
            ]);

            // 🔎 Buscar avaluador
            $avaluadorName = $row['avaluador'] ?? null;
            $avaluadorId   = null;

            if ($avaluadorName) {
                $user = User::where('name', 'like', '%' . trim($avaluadorName) . '%')->first();
                $avaluadorId = $user ? $user->id : null;
            }
            $ultimoAvaluo = Avaluo::whereHas('ingreso', function ($q) {
                $q->where('tiposervicio', 'Sec Bogota');
            })->orderBy('code_movilidad', 'DESC')->first();
            $nuevoCodeMovilidad = $ultimoAvaluo ? ($ultimoAvaluo->code_movilidad + 1) : 1;

            // 2️⃣ Crear el avaluo relacionado
            Avaluo::create([
                'ingreso_id'=> $ingreso->id,
                'fecha_inspeccion' => $this->parseExcelDate($row['fecha_inspeccion'] ?? null),
                'chatarra'=> $row['estado_del_activo'] ?? null,
                'valor_razonable'=> $row['valor_razonable'] ?? null,
                'avaluo_total' => $row['valor_total'] ?? null,
                'valor_chatarra_kg' => $row['valor_chatarra_kg'] ?? null,
                'peso_chatarra_kg'=> $row['peso_chatarra_kg'] ?? null,   
                'observaciones'   => $row['observaciones'] ?? null,  
                'ubicacion'  => $row['ubicacion'] ?? null,         
                'avaluador'=> $avaluadorName,
                'user_id'=> $avaluadorId,
                'code_movilidad' => $nuevoCodeMovilidad,
            ]);

            // 🔎 Buscar inspector
            $inspectorName = $row['inspector'] ?? null;
            $inspectorId   = null;

            if ($inspectorName) {
                $user = User::where('name', 'like', '%' . trim($inspectorName) . '%')->first();
                $inspectorId = $user ? $user->id : null;
            }

            // 3️⃣ Crear inspección
            /*Inspeccion::create([
                'ingreso_id'=> $ingreso->id,
                'ciudad' => $row['ciudad'] ?? null,
                'novedades_inspeccion'=> $row['observaciones_inspector'] ?? null,
                'cod_fasecolda' => $row['codigo_fasecolda'] ?? null,
                'valor_accesorios'=> $row['valor_accesorios_o_adecuaciones'] ?? null,
                'kilometraje' => $row['kilometraje'] ?? null,
                'servicio' => $row['servicio'] ?? null,
                'color'=> $row['color'] ?? null,
                'inspector'=> $inspectorName,            
                'user_id'=>$inspectorId
            ]);*/
        }
    }


    private function parseExcelDate($value)
    {
        if (empty($value)) {
            return null;
        }

        // Si es un número => serial de Excel
        if (is_numeric($value)) {
            return Date::excelToDateTimeObject($value)->format('Y-m-d');
        }

        // Si es texto => intentar con Carbon
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

}
