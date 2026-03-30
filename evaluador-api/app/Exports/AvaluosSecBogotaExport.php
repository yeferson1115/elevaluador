<?php

namespace App\Exports;

use App\Models\Ingreso;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AvaluosSecBogotaExport implements FromQuery, WithHeadings, WithMapping
{
    protected $filtro;
    protected $tiposervicio;

    public function __construct($filtro, $tiposervicio)
    {
        $this->filtro = $filtro;
        $this->tiposervicio = $tiposervicio;
    }

    public function query()
    {
        $filtro = trim((string) ($this->filtro ?? ''));
        $normalizedFilter = mb_strtoupper($filtro);
        $plateTerms = $this->extractPlateTerms($filtro);
        $allTermsArePlates = $this->allTermsArePlates($filtro);

        $query = Ingreso::with('avaluo')
            ->where('tiposervicio', $this->tiposervicio)
            ->whereHas('avaluo', function ($q) {
                $q->whereNotNull('file')
                  ->where('file', '!=', '');
            });

        if ($filtro) {
            if ($allTermsArePlates && count($plateTerms) > 1) {
                $query->whereIn(DB::raw('UPPER(placa)'), $plateTerms);
                return $query;
            }

            $query->where(function ($q) use ($filtro, $normalizedFilter) {
                $q->whereRaw('UPPER(placa) LIKE ?', ['%' . $normalizedFilter . '%'])
                  ->orWhereRaw('UPPER(marca) LIKE ?', ['%' . $normalizedFilter . '%'])
                  ->orWhere('solicitante', 'like', '%' . $filtro . '%')
                  ->orWhere('ubicacion_activo', 'like', '%' . $filtro . '%');
            });
        }

        return $query;
    }

    private function extractPlateTerms(string $search): array
    {
        return collect(preg_split('/[\s,;]+/u', mb_strtoupper($search)) ?: [])
            ->map(fn ($term) => trim($term))
            ->filter(fn ($term) => $term !== '')
            ->filter(fn ($term) => preg_match('/^[A-Z0-9-]{5,10}$/', $term))
            ->unique()
            ->values()
            ->all();
    }

    private function allTermsArePlates(string $search): bool
    {
        $terms = collect(preg_split('/[\s,;]+/u', mb_strtoupper($search)) ?: [])
            ->map(fn ($term) => trim($term))
            ->filter(fn ($term) => $term !== '')
            ->values();

        return $terms->isNotEmpty()
            && $terms->every(fn ($term) => preg_match('/^[A-Z0-9-]{5,10}$/', $term));
    }

    public function headings(): array
    {
        return [
            'Item',
            'AVALUO', // Código del avalúo
            'PLACAS',
            'FECHA',
            'VIGENTES',
            'AVALUADOR',
            'PATIO',
            'INGRESO A PATIOS',
            'ESTADO_VEHICULO',
            'TIPOSERVICIO',
            'CLASIFICACION',
            'MARCA',
            'LINEA',
            'CARROCERIA',
            'MODELO',
            'COLOR',
            'NRO_SERIE',
            'NRO_CHASIS',
            'VIN',
            'NRO_MOTOR',
            'CILINDRAJE',
            'COMBUSTIBLE',
            'CAPACIDAD_CARGA',
            'PUERTAS',
            'NRO_EJES',
            'CAJA',
            'KILOMETRAJE',
            'AUTORIDAD_TRANSITO',
            'LAT',
            'PINT',
            'TAP',
            'MOT',
            'CHAS',
            'CAJA_COMPONENTE',
            'TRANS',
            'FRENOS',
            'REFRI',
            'ELEC',
            'COMB',
            'BATERIA',
            'LLANTAS',
            'LLAVE',
            'VLR REPARACION',
            '%',
            'REPOSICIÓN',
            'MERCADO',
            'VALOR BUENO',
            'FUENTE',
            'CONCEPTO TECNICO',
            'PESO',            
            'AVALUO',
            'OBSERVACION'
        ];
    }

    public function map($ingreso): array
    {
        $valorTotal = 0;
        $valorRazonable = 0;
        $valorChatarraKg = 0;
        $pesoChatarraKg = 0;
        $avaluoCodigo = '';
        $item = 0;
        
        if ($ingreso->avaluo) {
            $avaluo = $ingreso->avaluo;
            
            // Generar código del avalúo (inicial + consecutivo)
            $avaluoCodigo = ($avaluo->inicial ?? '') . ($avaluo->consecutivo ?? '');
            
            // Calcular total_componentes
            $totalComponentes = 
                ($avaluo->latoneria_valor ?? 0) +
                ($avaluo->valor_pintura ?? 0) +
                ($avaluo->motor_valor ?? 0) +
                ($avaluo->chasis_valor ?? 0) +
                ($avaluo->tapiceria_valor ?? 0) +
                ($avaluo->refrigeracion_valor ?? 0) +
                ($avaluo->electrico_valor ?? 0) +
                ($avaluo->valor_llantas ?? 0) +
                ($avaluo->transmision_valor ?? 0) +
                ($avaluo->vidrios_valor ?? 0) +
                ($avaluo->tanque_valor ?? 0) +
                ($avaluo->bateria_valor ?? 0) +
                ($avaluo->frenos_valor ?? 0) +
                ($avaluo->llave_valor ?? 0);
            
                $gastos = ($avaluo->valor_faltantes ?? 0) +
                ($avaluo->valor_RTM ?? 0) +
                ($avaluo->valor_SOAT ?? 0) +
                ($total_componentes ?? 0);

            $gastostotales = ($avaluo->valor_faltantes ?? 0) +
                ($avaluo->valor_RTM ?? 0) +
                ($avaluo->valor_SOAT ?? 0) +
                ($totalComponentes ?? 0);
            
            // Obtener valores
            $valorRazonable = $avaluo->valor_razonable ?? 0;
            $valorChatarraKg = $avaluo->valor_chatarra_kg ?? 0;
            $pesoChatarraKg = $avaluo->peso_chatarra_kg ?? 0;
            
            // Calcular valor total
            if ($pesoChatarraKg > 0 && $valorChatarraKg > 0) {
                $valorTotal = $pesoChatarraKg * $valorChatarraKg;
            } else {
                $factorDemerito = $avaluo->factor_demerito ?? 1;
                $valorFaltantes = $avaluo->valor_faltantes ?? 0;
                $valorRTM = $avaluo->valor_RTM ?? 0;
                $valorSOAT = $avaluo->valor_SOAT ?? 0;
                
                $valorTotal = 
                    ($valorRazonable * $factorDemerito)
                    - ($valorFaltantes + $valorRTM + $valorSOAT + $totalComponentes);
            }
          
            // Calcular avalúo por peso (Factor 0,9)
            $avaluoPorPeso = 0;
            if ($pesoChatarraKg > 0) {
                $avaluoPorPeso = $pesoChatarraKg * ($valorChatarraKg * 0.9);
            }
            $totalFinal = ($totalComponentes + $gastos) ?? 0;

            $valor_comercial = ($avaluo->valor_razonable ?? 0) * ($avaluo->factor_demerito ?? 1);
            if ($gastostotales > 0 && $valor_comercial > 0) {
                $indice_reparabilidad = round($gastostotales / $valor_comercial , 4);
            } else {
                $indice_reparabilidad = 0;
            }
            $porcentaje=$indice_reparabilidad *100;
        }
        
        return [
            // Item (podrías usar un contador, pero aquí uso ID)
            $ingreso->id ?? '',
            
            // Código del avalúo
            $avaluoCodigo,
            
            // PLACAS
            $ingreso->placa ?? '',
            
            // FECHA (usar fecha solicitud)
            $ingreso->fecha_inspeccion ?? '',
            
            // VIGENTES (¿días vigentes?)
            '', // Dejar vacío por ahora
            
            // ACCION (usar acción del avalúo)
            $ingreso->avaluo->evaluador ?? 'AVALUAR',
            
            // PATIO
            $ingreso->avaluo->ubicacion ?? '',
            
            // INGRESO A PATIOS
            $ingreso->fecha_solicitud ?? '',
            
            // ESTADO_VEHICULO
            $ingreso->estado_registro_runt ?? '',
            
            // TIPOSERVICIO
            $ingreso->tipo_servicio_vehiculo ?? '',
            
            // CLASIFICACION (usar clase del vehículo)
            $ingreso->clase ?? '',
            
            // MARCA
            $ingreso->marca ?? '',
            
            // LINEA
            $ingreso->linea ?? '',
            
            // CARROCERIA
            $ingreso->tipo_carroceria ?? '',
            
            // MODELO
            $ingreso->modelo ?? '',
            
            // COLOR
            $ingreso->color ?? '',
            
            // NRO_SERIE
            $ingreso->numero_serie ?? '',
            
            // NRO_CHASIS
            $ingreso->numero_chasis ?? '',
            
            // VIN
            $ingreso->numeroVin ?? '',
            
            // NRO_MOTOR
            $ingreso->numero_motor ?? '',
            
            // CILINDRAJE
            $ingreso->cilindraje ?? '',
            
            // COMBUSTIBLE
            $ingreso->tipo_combustible ?? '', // Asegúrate de tener este campo
            
            // CAPACIDAD_CARGA
            $ingreso->capacidad_carga ?? '',
            
            // PUERTAS
            $ingreso->numero_puertas ?? '', // Asegúrate de tener este campo
            
            // NRO_EJES
            $ingreso->cantidad_ejes ?? '',
            
            // CAJA
            $ingreso->caja_cambios ?? '',
            
            // KILOMETRAJE
            $ingreso->kilometraje ?? '',
            
            // AUTORIDAD_TRANSITO
            $ingreso->organismo_transito ?? '',
            
            // LAT (Valor latonería)
            $ingreso->avaluo->latoneria_estado ?? '',
            
            // PINT (Valor pintura)
            $ingreso->avaluo->pintura_estado ?? '',
            
            // TAP (Valor tapicería)
            $ingreso->avaluo->tapiceria_estado ?? '',
            
            // MOT (Valor motor)
            $ingreso->avaluo->motor_estado ?? '',
            
            // CHAS (Valor chasis)
            $ingreso->avaluo->chasis_estado ?? '',
            
            // CAJA_COMPONENTE (Valor caja cambios como componente)
            $ingreso->avaluo->caja_componente_estado ?? '',
            
            // TRANS (Valor transmisión)
            $ingreso->avaluo->transmision_estado ?? '',
            
            // FRENOS (Valor frenos)
            $ingreso->avaluo->frenos_estado ?? '',
            
            // REFRI (Valor refrigeración)
            $ingreso->avaluo->refrigeracion_estado ?? '',
            
            // ELEC (Valor eléctrico)
            $ingreso->avaluo->electrico_estado ?? '',
            
            // COMB (Valor combustible)
            $ingreso->avaluo->tanque_estado ?? '',
            
            // BATERIA (Valor batería)
            $ingreso->avaluo->bateria_estado ?? '',
            
            // LLANTAS (Valor llantas)
            $ingreso->avaluo->llantas_estado ?? '',
            
            // LLAVE (Valor llave)
            $ingreso->avaluo->llave_estado ?? '',
            
            // VLR REPARACION (Valor de reparación total)
            $totalFinal  ?? 0,
            
            // % (Porcentaje del valor total vs valor razonable)
            $porcentaje ?? 0,
            
            // REPOSICIÓN (Valor de reposición)
            $ingreso->avaluo->valor_reposicion ?? '',
            
            // MERCADO (Valor de mercado)
            $ingreso->avaluo->valor_mercado ?? '',
            
            // VALOR BUENO
            $valorRazonable,
            
            // FUENTE (Fuente de la información)
            $ingreso->avaluo->fuente_informacion ?? '',
            
            // CONCEPTO TECNICO
            $ingreso->avaluo->concepto_tecnico ?? '',
            
            // PESO (Peso chatarra)
            $pesoChatarraKg,           
            
            // AVALUO (Valor total final)
            $valorTotal,
            
            // OBSERVACION
            $ingreso->avaluo->observaciones ?? ''
        ];
    }
}
