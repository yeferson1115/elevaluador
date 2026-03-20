<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe Avalúo</title>
    <style>
        @page {
            size: legal portrait;
            margin: 120px 40px 60px 40px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #000;
        }

        /* === ENCABEZADO FIJO === */
        header {
            position: fixed;
            top: -80px;
            left: 0;
            right: 0;
            height: 70px;
            margin-bottom: 50px !important;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo-left {
            width: 50%;
            text-align: left;
        }

        .logo-left img {
            height: 65px;
            max-width: 200px;
        }

        .info-right {
            width: 50%;
            text-align: right;
        }

        .info-right h2 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }

        .info-right p {
            margin: 0;
            font-size: 14px;
        }

        /* ===== GENERAL ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
            margin-bottom: 8px;
        }

        th, td {
            border: 1px solid #555;
            padding: 3px 4px;
            text-align: left;
            vertical-align: middle;
            font-size: 10px;
        }

        h3 {
            text-align: center;
            background: #f2f2f2;
            padding: 5px;
            border: 1px solid #ccc;
            margin-top: 15px;
            margin-bottom: 0;
        }

        .page-break { page-break-before: always; }

        /* === CONTENEDOR DE IMÁGENES - 5 POR FILA === */
        .img-grid-container {
            width: 100%;
            margin-top: 5px;
        }

        .img-row {
            width: 100%;
            display: block;
            margin-bottom: 10px;
            clear: both;
        }

        .img-item {
            width: 19.2%;
            float: left;
            margin-right: 1%;
            text-align: center;
        }

        .img-item:last-child {
            margin-right: 0;
        }

        .img-item img {
            width: 100%;
            height: 115px;
            object-fit: cover;
            border: 1px solid #ccc;
            margin-bottom: 3px;
        }

        .img-caption {
            font-size: 9px;
            color: #555;
            margin-top: 2px;
        }

        /* === FIRMA === */
        .firma-container {
            margin-top: 20px;
            text-align: center;
            clear: both;
            padding-top: 10px;
            border-top: 1px solid #ccc;
        }

        .firma-container img {
            height: 80px;
            margin-bottom: 5px;
            max-width: 150px;
        }

        /* === TABLA DETALLES === */
        .tabla-detalles {
            font-size: 10px;
            margin-top: 10px;
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-detalles th, .tabla-detalles td {
            border: 1px solid #999;
            padding: 3px 4px;
            text-align: left;
        }

        .tabla-detalles th {
            background-color: #8b8b8b;
            color: #fff;
            text-align: center;
        }

        .tabla-detalles td:nth-child(odd) {
            background-color: #f2f2f2;
        }

        /* === ESTIMACIÓN CON GRÁFICA === */
        .tabla-estimacion {
            width: 100%;
            border: none;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .tabla-estimacion td {
            border: none;
            vertical-align: top;
        }

        /* Contenedor para gráfica */
        .grafica-wrapper {
            width: 100%;
            padding: 5px;
        }

        .grafica-container {
            width: 100%;
            text-align: center;
        }

        .grafica-container img {
            max-width: 100%;
            height: auto;
            max-height: 300px;
            object-fit: contain;
        }

        /* === FOOTER FIJO === */
        footer {
            position: fixed;
            bottom: -60px;
            left: 0;
            right: 0;
            height: 105px;
            text-align: center;
            font-size: 11px;
            border-top: 1px solid #000;
            padding-top: 5px;
        }

        .footer-content {
            text-align: center;
            line-height: 1.2;
        }

        /* === LIMPIAR FLOTADOS === */
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        /* Estilo para valores monetarios */
        .valor-monetario {
            text-align: right;
            white-space: nowrap;
        }

    </style>
</head>
<body>

<!-- === ENCABEZADO === -->
<header>
    <table class="header-table">
        <tr>
            <td class="logo-left">
                @if(file_exists(public_path('logos/AlcadiaSDM_Bogota_Verde.png')))
                <img src="{{ public_path('logos/AlcadiaSDM_Bogota_Verde.png') }}" alt="Logo Izquierda">
                @endif
            </td>
            <td class="info-right">
                <h2>INFORME AVALÚO</h2>
                <p><strong>{{ $avaluo->inicial}}{{ $avaluo->consecutivo}}</strong></p>
            </td>
        </tr>
    </table>    
</header>

<!-- === CONTENIDO === -->
<div class="clearfix" style="margin-top: 20px;">
    <div>
        <h3>Ficha Técnica</h3>
        <table>
            <tr>
                <td><strong>Fecha:</strong></td>
                <td>{{ $ingreso->fecha_inspeccion ?? '' }}</td>
                <td><strong>Carrocería:</strong></td>
                <td>{{ $ingreso->tipo_carroceria ?? '' }}</td>
                <td><strong>Kilometraje:</strong></td>
                <td style="border-right: 1px solid #555;">{{ $ingreso->kilometraje ?? '' }}</td>                
            </tr>
            <tr>
                <td><strong>Placa:</strong></td>
                <td>{{ $ingreso->placa ?? '' }}</td>
                <td><strong>Cilindraje:</strong></td>
                <td>{{ $ingreso->cilindraje ?? '' }}</td>
                <td><strong>Caja:</strong></td>
                <td>{{ $ingreso->caja_cambios ?? '' }}</td>                
            </tr>
            <tr>
                <td><strong>Clase:</strong></td>
                <td>{{ $ingreso->clase ?? '' }}</td>
                <td><strong>N° Motor:</strong></td>
                <td>{{ $ingreso->numero_motor ?? '' }}</td>
                <td><strong>Número Ejes:</strong></td>
                <td>{{ $ingreso->cantidad_ejes ?? '' }}</td>
            </tr>
            <tr>
                <td><strong>Marca:</strong></td>
                <td>{{ $ingreso->marca ?? '' }}</td>
                <td><strong>N° Chasis:</strong></td>
                <td>{{ $ingreso->numero_chasis ?? '' }}</td>
                <td><strong>Peso vacío:</strong></td>
                <td>{{ $ingreso->peso_bruto ?? '' }}</td>
            </tr>
            <tr>
                <td><strong>Línea:</strong></td>
                <td>{{ $ingreso->linea ?? '' }}</td>                
                <td><strong>N° Serie:</strong></td>
                <td>{{ $ingreso->numero_serie ?? '' }}</td>
                <td><strong>Peso Mermado:</strong></td>
                <td>{{ $ingreso->peso_mermado ?? '' }}</td>
            </tr>
            <tr>
                <td><strong>Modelo:</strong></td>
                <td>{{ $ingreso->modelo ?? '' }}</td>
                <td><strong>VIN:</strong></td>
                <td>{{ $ingreso->numeroVin ?? '' }}</td>
                <td><strong>Ubicación:</strong></td>
                <td>{{ $avaluo->ubicacion ?? '' }}</td> 
            </tr>
            <tr>
                <td><strong>Color:</strong></td>
                <td>{{ $ingreso->color ?? '' }}</td>
                <td><strong>Capacidad Psj:</strong></td>
                <td>{{ $ingreso->numero_pasajeros ?? '' }}</td>
                <td><strong>Estado Runt:</strong></td>
                <td>{{ $ingreso->estado_registro_runt ?? '' }}</td>                
            </tr>
            <tr>
                <td><strong>Servicio:</strong></td>
                <td>{{ $ingreso->tipo_servicio_vehiculo ?? '' }}</td>
                <td><strong>Capacidad ton.:</strong></td>
                <td>{{ $ingreso->capacidad_ton ?? '' }}</td> 
                <td><strong>Diagnóstico:</strong></td>
                <td>{{ $avaluo->observaciones ?? '' }}</td>
            </tr>           
        </table>

        <h3>Registro Fotográfico</h3>
        <div class="img-grid-container">
            @php
            // Filtrar imágenes excluyendo las de firma
            $imagenes = $ingreso->images
                ->whereNotIn('categoria', ['firma_evaluador', 'Firma Inspector'])
                ->values();
            
            $totalImagenes = $imagenes->count();
            $imagenesPorFila = 5;
            $filas = ceil($totalImagenes / $imagenesPorFila);
            @endphp

            @for($fila = 0; $fila < $filas; $fila++)
                <div class="img-row clearfix">
                    @for($i = 0; $i < $imagenesPorFila; $i++)
                        @php
                            $indice = ($fila * $imagenesPorFila) + $i;
                        @endphp
                        
                        @if($indice < $totalImagenes)
                            <div class="img-item">
                                @php
                                    $img = $imagenes[$indice];
                                    // Verificar si el archivo existe
                                    $rutaImagen = public_path($img->path);
                                    if(file_exists($rutaImagen)) {
                                        $type = pathinfo($rutaImagen, PATHINFO_EXTENSION);
                                        $data = file_get_contents($rutaImagen);
                                        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                                    } else {
                                        $base64 = '';
                                    }
                                @endphp
                                
                                @if($base64)
                                <img src="{{ $base64 }}" alt="Foto vehículo">
                                @endif
                            </div>
                        @else
                            <div class="img-item">
                                <!-- Espacio vacío para mantener el layout -->
                            </div>
                        @endif
                    @endfor
                </div>
            @endfor
        </div>
         <h3>Estado Automotor</h3>
        <table>
            <tr>
                <th>Ítem</th><th>Estado</th><th>Valor</th>
                <th>Ítem</th><th>Estado</th><th>Valor</th>
            </tr>
            <tr>
                <td>Latonería</td>
                <td>{{ $avaluo->latoneria_estado ?? '' }}</td>
                <td class="valor-monetario">${{number_format($avaluo->latoneria_valor, 0, 0, '.')}}</td>
                <td>Pintura</td>
                <td>{{ $avaluo->pintura_estado ?? '' }}</td>
                <td class="valor-monetario">${{number_format($avaluo->valor_pintura, 0, 0, '.')}}</td>
            </tr>
            <tr>
                <td>Motor</td>
                <td>{{ $avaluo->motor_estado ?? '' }}</td>
                <td class="valor-monetario">${{number_format($avaluo->motor_valor, 0, 0, '.')}}</td>
                <td>Chasis</td>
                <td>{{ $avaluo->chasis_estado ?? '' }}</td>
                <td class="valor-monetario">${{number_format($avaluo->chasis_valor, 0, 0, '.')}}</td>
            </tr>
            <tr>
                <td>Tapicería</td>
                <td>{{ $avaluo->tapiceria_estado ?? '' }}</td>
                <td class="valor-monetario">${{number_format($avaluo->tapiceria_valor, 0, 0, '.')}}</td>
                <td>Refrigeración</td>
                <td>{{ $avaluo->refrigeracion_estado ?? '' }}</td>
                <td class="valor-monetario">${{number_format($avaluo->refrigeracion_valor, 0, 0, '.')}}</td>
            </tr>
            <tr>
                <td>Sis. Eléctrico</td>
                <td>{{ $avaluo->electrico_estado ?? '' }}</td>
                <td class="valor-monetario">${{number_format($avaluo->electrico_valor, 0, 0, '.')}}</td>
                <td>Llantas</td>
                <td>{{ $avaluo->llantas_estado ?? '' }}</td>
                <td class="valor-monetario">${{number_format($avaluo->valor_llantas, 0, 0, '.')}}</td>
            </tr>
            <tr>
                <td>Trasmisión</td>
                <td>{{ $avaluo->transmision_estado ?? '' }}</td>
                <td class="valor-monetario">${{number_format($avaluo->transmision_valor, 0, 0, '.')}}</td>
                <td>Vidrios</td>
                <td>{{ $avaluo->vidrios_estado ?? '' }}</td>
                <td class="valor-monetario">${{number_format($avaluo->vidrios_valor , 0, 0, '.')}}</td>
            </tr>
            <tr>
                <td>Tanque Combustible</td>
                <td>{{ $avaluo->tanque_estado ?? '' }}</td>
                <td class="valor-monetario">${{number_format($avaluo->tanque_valor, 0, 0, '.')}}</td>
                <td>Batería</td>
                <td>{{ $avaluo->bateria_estado ?? '' }}</td>
                <td class="valor-monetario">${{number_format($avaluo->bateria_valor, 0, 0, '.')}}</td>
            </tr>
            <tr>
                <td>Llave</td>
                <td>{{ $avaluo->llave_estado ?? '' }}</td>
                <td class="valor-monetario">${{number_format($avaluo->llave_valor, 0, 0, '.')}}</td>
                <td>Sis. Frenos</td>
                <td>{{ $avaluo->frenos_estado ?? '' }}</td>
                <td class="valor-monetario">${{number_format($avaluo->frenos_valor, 0, 0, '.')}}</td>
            </tr>
        </table>

        <h3>Estimación de Valor</h3>

        <table class="tabla-estimacion">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    @if($graficaPath && file_exists(public_path('graficas/' . $graficaPath)))
                    <div class="grafica-wrapper">
                        <div class="grafica-container">
                            <img src="{{ public_path('graficas/' . $graficaPath) }}" alt="Gráfica">
                        </div>
                    </div>
                    @endif
                </td>
                <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                    <table class="tabla-detalles">
                        <thead>
                            <tr><th>Detalles</th><th style="text-align: right;">Valores</th></tr>
                        </thead>
                        <tbody>
                            @php
                                $total_componentes = 
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
                                    ($avaluo->frenos_valor ?? 0)+
                                    ($avaluo->llave_valor ?? 0);

                                $valor_comercial = ($avaluo->valor_razonable ?? 0) * ($avaluo->factor_demerito ?? 1);

                                $gastos = 
                                    ($avaluo->valor_faltantes ?? 0) +
                                    ($avaluo->valor_RTM ?? 0) +
                                    ($avaluo->valor_SOAT ?? 0) +
                                    ($total_componentes ?? 0);

                                if ($gastos > 0 && $valor_comercial > 0) {
                                    $indice_reparabilidad = round($gastos / $valor_comercial , 4);
                                } else {
                                    $indice_reparabilidad = 0;
                                }
                            @endphp

                            <tr><td>Valor Razonable</td><td style="text-align: right;">${{number_format($avaluo->valor_razonable, 0, 0, '.')}}</td></tr>
                            <tr><td>Factor Demérito % (-)</td><td style="text-align: right;">{{$avaluo->factor_demerito}}</td></tr>
                            <tr><td>Valor Comercial</td><td style="text-align: right;">${{number_format($avaluo->valor_razonable*$avaluo->factor_demerito, 0, 0, '.')}}</td></tr>
                            <tr><td>Valor Faltantes</td><td style="text-align: right;">${{number_format($avaluo->valor_faltantes, 0, 0, '.')}}</td></tr>
                            <tr><td>Valor Tecno Mecánica</td><td style="text-align: right;">${{number_format($avaluo->valor_RTM, 0, 0, '.')}}</td></tr>
                            <tr><td>Valor SOAT</td><td style="text-align: right;">${{number_format($avaluo->valor_SOAT, 0, 0, '.')}}</td></tr>
                            <tr><td>Valor Gastos, Repuestos y Mano de Obra</td><td style="text-align: right;">${{number_format($avaluo->valor_faltantes+$avaluo->valor_RTM+$avaluo->valor_SOAT+$total_componentes, 0, 0, '.')}}</td></tr>
                            <tr><td>Índice Reparabilidad (%)</td><td style="text-align: right;">{{ number_format($indice_reparabilidad*100, 1, ',', '.') }}%</td></tr>
                            <tr>
                                <td>Peso Chatarra Kg</td>
                                <td style="text-align: right;">
                                    @if(!empty($avaluo->peso_chatarra_kg) && $avaluo->peso_chatarra_kg > 0)
                                        {{ $avaluo->peso_chatarra_kg }} Kg.
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>

                            <tr>
                                <td>Valor Chatarra Kg</td>
                                <td style="text-align: right;">
                                    @if(!empty($avaluo->valor_chatarra_kg) && $avaluo->valor_chatarra_kg > 0)
                                        ${{ number_format($avaluo->valor_chatarra_kg, 0, 0, '.') }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>

                            <tr>
                                <td>Valor Total Chatarra</td>
                                <td style="text-align: right;">
                                    @php
                                        $valor_total_chatarra = ($avaluo->peso_chatarra_kg ?? 0) * ($avaluo->valor_chatarra_kg ?? 0);
                                    @endphp
                                    @if($valor_total_chatarra > 0)
                                        ${{ number_format($valor_total_chatarra, 0, 0, '.') }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        


       

        
        
        
        @php
            $avaluo_estimado = 
                ($avaluo->valor_razonable * ($avaluo->factor_demerito ?? 1))
                - (
                    ($avaluo->valor_faltantes ?? 0) +
                    ($avaluo->valor_RTM ?? 0) +
                    ($avaluo->valor_SOAT ?? 0) +
                    ($total_componentes ?? 0)
                );
             if (!function_exists('numeroALetras')) {
                        function numeroALetras($numero) {
                            $formatter = new NumberFormatter('es', NumberFormatter::SPELLOUT);
                            return strtoupper($formatter->format($numero)) . ' PESOS';
                        }
            }
            
            if($avaluo->peso_chatarra_kg > 0 && $avaluo->valor_chatarra_kg > 0){
                $avaluo_estimado = $avaluo->peso_chatarra_kg * $avaluo->valor_chatarra_kg;
            }

            $valor_letras = numeroALetras(round($avaluo_estimado));
        @endphp
        
        <table style="margin-top: 15px;">       
            <tr><td><strong>Avalúo Estimado:</strong></td><td class="valor-monetario">${{ number_format($avaluo_estimado, 0, ',', '.') }}</td></tr>
            <tr><td><strong>En letras:</strong></td><td>{{ $valor_letras ?? '' }}</td></tr>
            
        </table>
         <h3>Marco Legal</h3>
        <p>El presente avalúo comercial se elabora de conformidad con los lineamientos establecidos en las
Normas Técnicas Sectoriales NTS S02, NTS S03, NTS M04 y NTS S04, así como en las Guías Técnicas
Sectoriales GTS E03 y GTS G02.</p>
        <h3>Vigencia de avaluó</h3>
        <p>De acuerdo con el Numeral 7 del Artículo 2 del Decreto N° 422 de Marzo 08 de 2000 y con el Artículo 19 del Decreto N° 1420 del 24 de Junio de 1998, expedidos por el Ministerio del Desarrollo Económico, el presente avalúo comercial tiene una vigencia de un (1) año, contado desde la fecha de su expedición, siempre y cuando las condiciones físicas del bien mueble valuado no sufra cambios significativos, así como tampoco se presenten variaciones representativas de las condiciones del mercado mobiliario</p>
        <h3>Limitación o Posible Fuentes de Error </h3>
        <ul style="font-size: 12px; margin-top: 10px; padding-left: 20px;">
                @foreach($avaluo->limitaciones as $item)
                <li>
                {{$item->texto}}
                </li>
                @endforeach
                
            </ul>
            
            <p>Firma:</p>
@php
    // Definir el array de evaluadores con la información y el nombre del archivo de firma
    $avaluadores = [
        'Mauricio Garcia' => [
            'nombre'    => 'Gilbert Mauricio García Orozco',
            'profesion' => 'Ingeniero Mecánico',
            'raa'       => 'AVAL-80542954',
            'email'     => 'gmgarcia@movilidadbogota.gov.co',
            'contrato'  => 'Contrato 2025-3366',
            'firma'     => 'mauricio.jpeg'  // Nombre del archivo de firma
        ],
        'Jhonny Rodríguez' => [
            'nombre'    => 'Jhonny Steven Rodríguez Hoyos',
            'profesion' => 'Ingeniero Mecánico',
            'raa'       => 'AVAL-1015415553',
            'email'     => 'jsrodriguezh@movilidadbogota.gov.co',
            'contrato'  => 'Contrato 2025-3363',
            'firma'     => 'Jhonny.jpeg'  // Nombre del archivo de firma
        ],
        'Ivan Mora' => [
            'nombre'    => 'Iván Darío Mora Mora',
            'profesion' => 'Ingeniero Mecánico',
            'raa'       => 'AVAL-1026270378',
            'email'     => 'imora@movilidadbogota.gov.co',
            'contrato'  => 'Contrato 2025-3365',
            'firma'     => 'Ivan.jpg'  // Nombre del archivo de firma
        ],
        'Lenin Ariza' => [
            'nombre'    => 'Lenin Alexander Ariza Triana',
            'profesion' => 'Ingeniero Mecánico',
            'raa'       => 'AVAL-80387508',
            'email'     => 'larizat@movilidadbogota.gov.co',
            'contrato'  => 'Contrato 2026-512',
            'firma'     => 'lenin.jpeg'  // Nombre del archivo de firma
        ],
        'German Galvis' => [
            'nombre'    => 'German David Galvis Rivas',
            'profesion' => 'Ingeniero Mecánico',
            'raa'       => 'AVAL-1014251981',
            'email'     => 'ggalvis@movilidadbogota.gov.co',
            'contrato'  => 'Contrato 2026-1020',
            'firma'     => 'german.jpeg'  // Nombre del archivo de firma (ajusta si es necesario)
        ],
    ];
    
    // Obtener el evaluador del avalúo
    $eval = $avaluadores[$avaluo->evaluador] ?? null;
@endphp

@if($eval)
    @php
        // Ruta de la firma en la carpeta public/firmas
        $rutaFirma = public_path('firmas/' . $eval['firma']);
        
        // Verificar si el archivo existe
        if (file_exists($rutaFirma)) {
            $typeFirma = pathinfo($rutaFirma, PATHINFO_EXTENSION);
            $dataFirma = file_get_contents($rutaFirma);
            $base64Firma = 'data:image/' . $typeFirma . ';base64,' . base64_encode($dataFirma);
            $firmaExiste = true;
        } else {
            $firmaExiste = false;
        }
    @endphp
    
    @if($firmaExiste)
        <img src="{{ $base64Firma }}" alt="Firma evaluador" style="max-height: 120px;">
    @else
        <p style="color: #999; font-style: italic;">Firma no disponible</p>
    @endif
    
    <div style="margin-top: 8px;">
        <strong>{{ $eval['nombre'] }}</strong><br>
        {{ $eval['profesion'] }}<br>
        R.A.A. {{ $eval['raa'] }}<br>
        {{ $eval['email'] }}<br>
        @if(!empty($eval['contrato']))
            {{ $eval['contrato'] }}
        @endif
    </div>
@else
    <p style="color: #999; font-style: italic;">Evaluador no identificado</p>
@endif
       
    </div>
</div>

<footer>
    <div class="footer-content">
        
             <p style="margin: 0;font-size: 10px;">Secretaria Distrital de Movilidad</p>
             <p style="margin: 0;font-size: 10px;">Calle 13 # 37 - 35</p>
             <p style="margin: 0;font-size: 10px;">Teléfono: (1) 364 9400</p>
             <p style="margin: 0;font-size: 10px;">www.movilidadbogota.gov.co</p>
             <p style="margin: 0;font-size: 10px;">Información: Linea 195</p>
             <p style="margin: 0;font-size: 10px;">Para la SDM la transparencia es fundamental. Reporte hechos de soborno en www.movilidadbogota.gov.co</p>
    </div>
</footer>

</body>
</html>