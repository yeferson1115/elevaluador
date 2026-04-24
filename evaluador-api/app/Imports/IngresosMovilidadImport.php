<?php

namespace App\Imports;

use App\Models\Ingreso;
use App\Models\Avaluo;
use App\Models\User;
use App\Models\Inspeccion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class IngresosMovilidadImport implements ToCollection, WithHeadingRow
{
    private const LIMITACIONES_POR_DEFECTO = [
        'Las condiciones climatológicas, ambientales, de iluminación y de almacenamiento del vehículo al momento de la inspección pueden influir en la apreciación del estado físico y estético de los componentes, constituyéndose en una limitación inherente al proceso de inspección técnica.',
        'La inspección técnica se limita a una evaluación visual y funcional de los sistemas, subconjuntos y componentes del vehículo que se encuentran accesibles y ensamblados, sin realizar desarmes parciales o totales, los cuales se encuentran fuera del alcance del presente avalúo.',
        'El bien objeto del presente avalúo se encuentra sometido a gastos continuos derivados de su permanencia en patios oficiales y/o privados.',
        'El presente avalúo se realizó sin efectuar desarmes, pruebas invasivas ni intervenciones mecánicas, limitándose la verificación del motor a una inspección visual externa. Por lo anterior, no se puede determinar el estado real de los componentes internos ni de los sistemas asociados.',
        'No se realiza validación de los sistemas de identificación del vehículo ni consulta de antecedentes judiciales, constituyéndose esta condición como una limitación del presente avalúo técnico.',
        'El método usado para el cálculo de datos es el de comparación de mercado. Este método consiste en reunir datos de varias fuentes del mercado local como lo son: concesionarios de nuevos y usados, clasificados en sitios web y revistas especialistas; una vez consultadas estas fuentes se sigue el proceso de valoración.',
        'Moto en estado regular con focos de oxidación en chasis, estructura asiento, tubo de exhosto, barras delanteras y piezas metálicas, motor posiblemente bloqueado por falta de mantenimiento.',
        'El índice de reparabilidad mínimo supera el 90% del valor comercial del vehículo, teniendo en cuenta esto se calcula valor de charra por peso mermado ajustado por el valor de chatarra vigente para compra. El concepto de valor adoptado para el avalúo es VALOR CHATARRA.',
    ];

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            DB::transaction(function () use ($row) {
                $placa = $this->normalizePlaca($row['placa'] ?? null);

                $ingresoData = [
                    'tiposervicio' => 'Sec Bogota',
                    'placa' => $placa,
                    'marca' => $row['marca'] ?? null,
                    'linea' => $row['linea'] ?? null,
                    'tipo_carroceria' => $row['tipo_carroceria'] ?? null,
                    'modelo' => $row['modelo'] ?? null,
                    'cilindraje' => $row['cilindraje'] ?? null,
                    'color' => $row['color'] ?? null,
                    'numero_motor' => $row['numero_motor'] ?? null,
                    'numero_chasis' => $row['numero_de_chasis'] ?? null,
                    'clase' => $row['clase'] ?? null,
                    'numeroVin' => $row['numero_vin'] ?? null,
                    'tipo_servicio_vehiculo' => $row['tipo_de_servicio'] ?? null,
                    'fecha_solicitud' => $this->parseExcelDate($this->valueFromAliases($row, ['fecha_solicitud'])),
                    'fecha_inspeccion' => $this->parseExcelDate($this->valueFromAliases($row, ['fecha_inspeccion', 'fecha_avaluo'])),
                    'estado_registro_runt' => $row['estado_runt'] ?? null,
                    'organismo_transito' => $this->valueFromAliases($row, ['organismo_de_transito', 'organismo_transito']),
                    'fecha_ingreso' => $this->parseExcelDate($this->valueFromAliases($row, ['fecha_ingreso'])),
                    'caja_cambios' => $row['caja_de_cambios'] ?? ($row['caja'] ?? null),
                    'estado' => 'En Inspección',
                ];

                $ingreso = $placa
                    ? Ingreso::firstOrNew([
                        'placa' => $placa,
                        'tiposervicio' => 'Sec Bogota',
                    ])
                    : new Ingreso();

                $ingreso->fill($ingresoData);
                $ingreso->save();

                $avaluadorName = $row['avaluador'] ?? null;
                $avaluadorId = null;

                if ($avaluadorName) {
                    $user = User::where('name', 'like', '%' . trim($avaluadorName) . '%')->first();
                    $avaluadorId = $user ? $user->id : null;
                }

                $avaluo = Avaluo::where('ingreso_id', $ingreso->id)->first();

                if (!$avaluo) {
                    $ultimoAvaluo = Avaluo::whereHas('ingreso', function ($q) {
                        $q->where('tiposervicio', 'Sec Bogota');
                    })->orderBy('code_movilidad', 'DESC')->first();

                    $avaluo = new Avaluo();
                    $avaluo->ingreso_id = $ingreso->id;
                    $avaluo->code_movilidad = $ultimoAvaluo ? ($ultimoAvaluo->code_movilidad + 1) : 1;
                }

                $avaluo->fill([
                    'fecha_inspeccion' => $this->parseExcelDate($this->valueFromAliases($row, ['fecha_inspeccion', 'fecha_avaluo'])),
                    'fecha_inmovilizacion' => $this->parseExcelDate($this->valueFromAliases($row, ['fecha_ingreso_a_patios', 'ingreso_a_patios', 'fecha_inmovilizacion'])),
                    'chatarra' => $row['estado_del_activo'] ?? null,
                    'valor_razonable' => $row['valor_razonable'] ?? null,
                    'avaluo_total' => $row['valor_total'] ?? null,
                    'valor_chatarra_kg' => $row['valor_chatarra_kg'] ?? null,
                    'peso_chatarra_kg' => $row['peso_chatarra_kg'] ?? null,
                    'observaciones' => $row['observaciones'] ?? null,
                    'codigo_fasecolda' => $this->valueFromAliases($row, ['codigo_fasecolda', 'cod_fasecolda', 'fasecolda']),
                    'ubicacion' => $row['ubicacion'] ?? null,
                    'avaluador' => $avaluadorName,
                    'user_id' => $avaluadorId,
                ]);
                $avaluo->save();

                $limitacionUno = $this->valueFromAliases($row, ['limitacion_1', 'limitacion1', 'limitacion']);
                $avaluo->limitaciones()->delete();

                $limitacionesFinales = collect(self::LIMITACIONES_POR_DEFECTO);
                if ($limitacionUno !== null) {
                    $limitacionesFinales->prepend($limitacionUno);
                }

                foreach (
                    $limitacionesFinales
                        ->map(fn ($texto) => trim((string) $texto))
                        ->filter(fn ($texto) => $texto !== '')
                        ->unique()
                        ->values() as $textoLimitacion
                ) {
                    $avaluo->limitaciones()->create(['texto' => $textoLimitacion]);
                }

                $inspectorName = $row['inspector'] ?? null;
                $inspectorId = null;

                if ($inspectorName) {
                    $user = User::where('name', 'like', '%' . trim($inspectorName) . '%')->first();
                    $inspectorId = $user ? $user->id : null;
                }

                /*
                $inspeccion = Inspeccion::firstOrNew(['ingreso_id' => $ingreso->id]);
                $inspeccion->fill([
                    'ciudad' => $row['ciudad'] ?? null,
                    'novedades_inspeccion' => $row['observaciones_inspector'] ?? null,
                    'cod_fasecolda' => $row['codigo_fasecolda'] ?? null,
                    'valor_accesorios' => $row['valor_accesorios_o_adecuaciones'] ?? null,
                    'kilometraje' => $row['kilometraje'] ?? null,
                    'servicio' => $row['servicio'] ?? null,
                    'color' => $row['color'] ?? null,
                    'inspector' => $inspectorName,
                    'user_id' => $inspectorId,
                ]);
                $inspeccion->save();
                */
            });
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

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null;
            }

            foreach (['d/m/Y', 'd-m-Y', 'Y-m-d'] as $format) {
                try {
                    return Carbon::createFromFormat($format, $value)->format('Y-m-d');
                } catch (\Exception $e) {
                    // Intentar siguiente formato
                }
            }
        }

        // Si es texto => intentar con Carbon libre
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function normalizePlaca($placa): ?string
    {
        if ($placa === null) {
            return null;
        }

        $placa = trim((string) $placa);

        return $placa === '' ? null : strtoupper($placa);
    }

    private function valueFromAliases($row, array $aliases): ?string
    {
        foreach ($aliases as $alias) {
            if (!isset($row[$alias])) {
                continue;
            }

            $value = trim((string) $row[$alias]);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
