<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        /* Márgenes de página (reserva para header/footer) */
        @page {
            margin: 100px 50px;
        }

        body {
            font-family: DejaVu Sans, sans-serif; /* buena compatibilidad con DOMPDF */
            font-size: 12px;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }


        /* Header fijo (como lo tenías) */
        header {
            position: fixed;
            top: -80px; /* tu valor original */
            left: 0;
            right: 0;
            height: 100px;
        }

        /* Footer fijo (como lo tenías) */
        footer {
            position: fixed;
            bottom: -60px;
            left: 0;
            right: 0;
            height: 50px;
            text-align: center;
            line-height: 20px;
            font-size: 12px;
        }

        /* Contenido principal */
        .content {
            margin-top: 20px;
            /* no usar flex en DOMPDF */
        }

        .container {
            width: 100%;
            padding: 0 5px;
        }

        /* --- SECCIÓN SUPERIOR: usar tabla para alinear imagen + datos --- */
        .top-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            table-layout: fixed;
        }

        .top-table td {
            vertical-align: top;
            padding: 4px;
        }

        .top-image {
            /* el ancho se adapta; usa max-width para evitar overflow */
            width: 60%;
        }

        .top-image img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            display: block;
        }

        .datos-generales {
            width: 40%;
        }

        .datos-generales .box {
            border: 1px solid #ccc;
        }

        .datos-generales h3 {
            background: #c00;
            color: #fff;
            margin: 0;
            padding: 6px;
            font-size: 14px;
            text-align: center;
        }

        .datos-generales table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .datos-generales td {
            border: 1px solid #fff;
            padding: 4px;
        }

        /* Secciones inferiores */
        .section {
            margin-top: 10px;
        }

        .section h3 {
            background: #c00;
            color: #fff;
            margin: 0;
            padding: 6px;
            font-size: 14px;
            text-align: center;
        }

        .info-bien {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .info-bien td {
            border: 1px solid #fff;
            padding: 4px;
            vertical-align: top;
        }

        .small-text {
            font-size: 10px;
        }

        /* Ajustes visuales footer table */
        .footer-table {
            border-top: 1px solid #ccc;
            font-family: Arial, sans-serif;
            font-size: 10px;
            font-weight: bold;
            border-collapse: collapse;
            margin-top: 5px;
            width: 100%;
        }

        .footer-table td {
            padding: 4px;
        }
        .gris1{
           background: #aaaaaa;
            color: #000;
            font-weight: 700; 
        }
        .gris2{
            background: #d0d0d0;
        }

    </style>
</head>
<body>

    <!-- HEADER (tal como lo tenías) -->
    <header >
        <table width="100%" style="border-collapse: collapse;">
            <tr>
                {{-- Logo izquierda --}}
                <td style="width: 25%; background-color: #1d1d1d; padding: 5px; border: 1px solid #000;">
                    <img src="{{ public_path('imagenes/logo.png') }}" alt="Logo" style="height: 50px;">
                </td>

                {{-- Título centrado --}}
                <td style="width: 50%; text-align: center; border: 1px solid #000;">
                    <strong style="font-size: 25px;">INFORME DEL AVALÚO</strong><br>
                    <span style="font-size: 20px;">Normas Técnicas Sectoriales</span>
                </td>

                {{-- Caja derecha con 3 secciones --}}
                <td style="width: 25%; vertical-align: top; border: 1px solid #000;">
                    <table style="width: 100%; font-size: 10px; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 5px; text-align: right;">
                                <strong>Versión 01</strong> <span>{{ \Carbon\Carbon::parse($avaluo->fecha_inspeccion)->format('d/m/y') }}</span>
                            </td>
                        </tr>

                        <tr>
                            <td style="border-top: 1px solid #000; padding: 5px; text-align: right;">
                                El evaluador S.A.S
                            </td>
                        </tr>

                        <tr>
                            <td style="border: 2px solid red; padding: 5px; font-size: 16px; font-weight: bold; text-align: center; color: #000;">
                                {{$ingreso->placa}}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </header>

    <!-- FOOTER (tal como lo tenías) -->
    <footer>
       <table class="footer-table">
            <tr>
                <td style="width: 33%; text-align: left; color: #000;">
                    +57 316 309 7345
                </td>
                <td style="width: 34%; text-align: center;">
                    <span style="background-color: #d71920; color: white; padding: 2px 10px; border-radius: 4px; font-size: 11px;">
                        Bogotá D.C - Colombia
                    </span>
                </td>
                <td style="width: 33%; text-align: right; color: #7a7a7a; font-weight: normal;">
                    www.elevaluador.co
                </td>
            </tr>
        </table>
    </footer>

    <!-- CONTENIDO -->
    <div class="content">
        <div class="container">

            <!-- SECCIÓN SUPERIOR: imagen + datos generales (ahora con tabla para DOMPDF) -->
            <table class="top-table" >
                <tr>
                    <!-- Imagen (izquierda) -->
                    <td class="top-image" style="padding-right:7px;width:45%;">
                        @php
                            $imageforntalImage = $ingreso->images->where('categoria', 'frontal')->first();
                        @endphp

                        @if($imageforntalImage)
                            <img src="{{ public_path($imageforntalImage->path) }}" 
                                alt="Matrícula" 
                                style="width:353px; height:356px; object-fit:cover;">
                        @else
                            <img src="{{ public_path('imagenes/vehiculo.png') }}" 
                                alt="Vehículo"
                                style="width:353px; height:356px; object-fit:cover;">
                        @endif
                    </td>


                    <!-- Datos Generales (derecha) -->
                    <td class="datos-generales" style="width:48%;">
                        <div class="box">
                            <h3>Datos Generales</h3>
                            <table >
                                <tr>
                                    <td style="width:50%;" class="gris1">Solicitante</td>
                                    <td class="gris2">{{$ingreso->solicitante}}</td> 
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="gris1">Documento Solicitante</td>
                                    <td class="gris2">{{$ingreso->documento_solicitante}}</td> 
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="gris1">Placa</td>
                                    <td class="gris2">{{$ingreso->placa}}</td> 
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="gris1">Ubicación Activo</td>
                                    <td class="gris2">{{$ingreso->ubicacion_activo}}</td> 
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="gris1">Fecha Solicitud</td>
                                    <td class="gris2">{{\Carbon\Carbon::parse($ingreso->created_at)->format('Y-m-d')}}</td> 
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="gris1">Fecha Inspección</td>
                                    <td class="gris2">{{\Carbon\Carbon::parse($avaluo->fecha_inspeccion)->format('Y-m-d')}}</td> 
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="gris1">Fecha Informe</td>
                                    <td class="gris2">{{ now()->format('d/m/Y') }}</td> 
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="gris1">Objeto del avalúo</td>
                                    <td class="gris2">{{$ingreso->objeto_avaluo}}</td> 
                                </tr>
                                <tr>
                                    <td style="width:50%;" class="gris1">Código Interno/Móvil</td>
                                    <td class="gris2">{{$ingreso->codigo_interno_movil}}</td> 
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- INFORMACIÓN DEL BIEN (igual que tenías) -->
            <div class="section">
                <h3>Información del Bien</h3>
                <table class="info-bien" >
                    <tr>
                        <td style="width:40%;border-right: none;padding: 7px 0px;">
                            <table style="width:100%;border-collapse: collapse; border: solid 1px #fff;">
                                <tr>
                                  <td style="width:50%;" class="gris1">Tipo de Propiedad</td> 
                                  <td class="gris2">{{$ingreso->tipo_propiedad}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="gris1">Fecha Matricula</td> 
                                  <td class="gris2">{{$ingreso->fecha_expedicion_licencia}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="gris1">Móvil</td> 
                                  <td class="gris2">{{$ingreso->movil}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="gris1">Marca</td> 
                                  <td class="gris2">{{$ingreso->marca}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="gris1">Línea</td> 
                                  <td class="gris2">{{$ingreso->linea}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="gris1">Clase</td> 
                                  <td class="gris2">{{$ingreso->clase}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="gris1">Tipo Carrocería</td> 
                                  <td class="gris2">{{$ingreso->tipo_carroceria}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="gris1">Categoría</td> 
                                  <td class="gris2">{{$ingreso->categoria}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="gris1">Color</td> 
                                  <td class="gris2">{{$ingreso->color}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;"class="gris1">Cilindraje</td> 
                                  <td class="gris2">{{$ingreso->cilindraje}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="gris1">Modelo</td> 
                                  <td class="gris2">{{$ingreso->modelo}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="gris1">Kilometraje</td> 
                                  <td class="gris2">{{$ingreso->kilometraje}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;"class="gris1" >Caja de Cambios</td> 
                                  <td class="gris2">{{$ingreso->caja_cambios}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="gris1">Tipo de Tracción</td> 
                                  <td class="gris2">{{$ingreso->tipo_traccion}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="gris1">Número Pasajeros</td> 
                                  <td class="gris2">{{$ingreso->numero_pasajeros}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="gris1">Capacidad de Carga</td> 
                                  <td class="gris2">{{$ingreso->capacidad_carga}}</td> 
                                </tr>
                            </table>
                        </td>
                        <td style="width:60%;border-left: none;padding: 7px 0px 15px 15px;">
                            <table style="width:100%;border-collapse: collapse;">
                                <tr>
                                    <td class="gris1">Llanta</td>
                                    <td class="gris1">Izquierda</td>
                                    <td class="gris1">Derecha</td>
                                </tr>
                                <tr>
                                    <td class="gris1">Delantera</td>
                                    <td class="gris2">{{$avaluo->llanta_delantera_izquierda}}%</td>
                                    <td class="gris2">{{$avaluo->llanta_delantera_derecha}}%</td>
                                </tr>
                                <tr>
                                    <td class="gris1">Trasera</td>
                                    <td class="gris2">{{$avaluo->llanta_trasera_izquierda}}%</td>
                                    <td class="gris2">{{$avaluo->llanta_trasera_derecha}}%</td>
                                </tr>
                                <tr>
                                    <td class="gris1">Repuesto</td>
                                    <td class="gris2" colspan="2">{{$avaluo->llanta_repuesto}}%</td>
                                    
                                </tr>
                                <tr>
                                    <td colspan="3" style="height: 14px;"></td>                                    
                                </tr>
                                <tr>
                                    <td colspan="2"></td>
                                    <td class="gris1">Regrabado</td>                                    
                                </tr>
                                <tr>
                                    <td class="gris1">Número de Chasis</td>
                                    <td class="gris2">{{$ingreso->numero_chasis}}</td>
                                    <td class="gris2"></td>
                                </tr>
                                <tr>
                                    <td class="gris1">Número de Serie</td>
                                    <td class="gris2">{{$ingreso->numero_serie}}</td>
                                    <td class="gris2"></td>
                                </tr>
                                <tr>
                                    <td class="gris1">Número de Motor</td>
                                    <td class="gris2">{{$ingreso->numero_motor}}</td>
                                    <td class="gris2"></td>
                                </tr>
                                <tr>
                                    <td class="gris1">Nacionalidad</td>
                                    <td class="gris2">{{$ingreso->nacionalidad}}</td>
                                    <td class="gris2"></td>
                                </tr>
                                <tr>
                                    <td class="gris1">Propietario</td>
                                    <td class="gris2">{{$ingreso->propietario}}</td>
                                    <td class="gris2"></td>
                                </tr>
                                <tr>
                                    <td class="gris1">Empresa de Afiliación</td>
                                    <td class="gris2">{{$ingreso->empresa_afiliacion}}</td>
                                    <td class="gris2"></td>
                                </tr>
                                <tr>
                                    <td colspan="3" style="height: 14px;"></td>                                    
                                </tr>
                                <tr>
                                    <td></td>
                                    <td class="gris1">Fecha de Vencimiento/td>  
                                    <td class="gris1">Vigente</td>                                        
                                </tr>
                                <tr>
                                    <td class="gris1">Soat</td>
                                    <td class="gris2">{{$ingreso->fecha_vencimiento_soat}}</td>
                                    <td class="gris2">
                                        {{$ingreso->estado_soat}}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="gris1">RTM</td>
                                    <td class="gris2">{{$ingreso->fecha_vencimiento_rtm}}</td>
                                    <td class="gris2">{{$ingreso->estado_rtm}}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </div>

            <!-- (Aquí puedes seguir agregando las secciones que necesites) -->

            <!-- ... TU CÓDIGO DE LA PRIMERA HOJA QUEDA IGUAL ... -->

        <!-- Salto de página para la nueva hoja -->
        <div style="page-break-before: always;"></div>

<!-- NUEVA HOJA -->
<div class="content">
    <div class="container">

        <!-- TÍTULO -->
        <div class="section">
            <h3>Tipo y Definición del Valor</h3>
            <table class="info-bien">
                <tr>
                    <td style="font-size: 11px; text-align: justify;">
                        <strong>3.1 Base de Valuación</strong><br><br>
                        Al realizar el proceso de avalúo se tienen en cuenta las siguientes características 
                        para determinar el valor razonable del equipo a intervenir, la información para 
                        realizar la valoración se obtuvo de las fuentes relacionadas a continuación:
                        <ul>
                            <li>Concesionarios de nuevos y usados</li>
                            <li>Clasificados de sitios web</li>
                            <li>Revistas especializadas</li>
                            <li>Mercado internacional</li>
                        </ul>
                    </td>
                </tr>
            </table>
        </div>

        <!-- METODOLOGÍA -->
        <div class="section">
            <h3>Metodología Valuatoria</h3>
            

            <p style="font-size: 11px; text-align: justify; margin: 5px 0;">
                <strong>4.1 Método de enfoque de costo</strong><br>
                El método usado para el cálculo de datos es por valor de reposición a nuevo, con valor depreciado, 
                tanto para Estructura, motor y equipamientos, los cuales se muestran a continuación:
            </p>

            <!-- TABLA PRINCIPAL -->
            <table style="width:100%; border-collapse: collapse; font-size: 11px;">
                <tr>
                    <td style="border: 1px solid #fff; padding: 5px; width: 50%;" class="gris1">Fecha Matricula</td>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris2">{{$ingreso->fecha_expedicion_licencia}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris1">Fecha Inspección</td>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris2">{{$avaluo->fecha_inspeccion}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris1">Vida Útil Probable (meses)</td>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris2">{{$avaluo->vida_util_probable}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris1">Vida Usada (años)</td>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris2">{{$avaluo->vida_usada_anos}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris1">Vida Usada (meses)</td>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris2">{{$avaluo->vida_usada_meses}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris1">Vida Útil Remanente (meses)</td>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris2">{{$avaluo->vida_util_remate}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris1">Vida Útil (años)</td>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris2">{{$avaluo->vida_util_anos}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris1">Antigüedad</td>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris2">{{$avaluo->antiguedad}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris1">Vida Útil</td>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris2">{{$avaluo->vida_util}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris1">Valor de Reposición</td>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris2">${{number_format($avaluo->valor_reposicion, 0, 0, '.')}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris1">Valor Residual</td>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris2">${{number_format($avaluo->valor_residual, 0, 0, '.')}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris1">Estado de Conservación</td>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris2">{{$avaluo->estado_conservacion}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris1">x</td>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris2">{{$avaluo->x}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris1">K</td>
                    <td style="border: 1px solid #fff; padding: 5px;" class="gris2">{{$avaluo->k}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #fff; padding: 5px;">Valor Razonable</td>
                    <td style="background: #c00; color: #fff; font-weight: bold; text-align: center; border: 1px solid #fff; padding: 5px;">${{number_format($avaluo->valor_resonable, 0, 0, '.')}}</td>
                </tr>
            </table>


            <p style="font-size: 11px; text-align: justify; margin-top: 8px;">
                Se menciona que el equipo avaluado presenta baja comercialización en el país y según el estado 
                de la máquina se establece un estado de conservación de {{$avaluo->estado_conservacion}} debido a que su conservación 
                necesita reparaciones importantes.
            </p>
        </div>
    </div>
</div>

<div style="page-break-before: always;"></div>

<!-- LIMITACIÓN O POSIBLES FUENTES DE ERROR -->
 <div class="content">
    <div class="container">
        <div style="margin-top: 30px;" style="page-break-before: always; font-family: Arial, sans-serif; font-size: 13px; color:#000;">
            <h3 style="margin-top: 20px;background: #c00; color: #fff; margin: 0; padding: 6px; font-size: 14px; text-align: center;">
                Limitación o Posible Fuentes de Error 
            </h3>

            <ul style="font-size: 12px; margin-top: 10px; padding-left: 20px;">
                @foreach($avaluo->limitaciones as $item)
                <li>
                {{$item->texto}}
                </li>
                @endforeach
                
            </ul>
        </div>
    </div>
</div>

<!-- ================= HOJA 2 ================= -->
<div style="page-break-before: always; font-family: Arial, sans-serif; font-size: 13px; color:#000;">

    <!-- Título rojo -->
    <div style="background-color:#d71920; color:#fff; font-weight:bold; text-align:center; padding:6px; font-size:14px;margin-top: 30px;">
        Consideraciones Finales del Avalúo
    </div>

    <!-- Capacidad Transportadora -->
    <p style="margin:12px 0; font-weight:bold;">6.1 Capacidad Transportadora</p>
    <p>{{$avaluo->capacidad_transportadora}}</p>
    

    <!-- Tabla de Valores -->
    <p style="margin:12px 0; font-weight:bold;">6.2 Tabla de Valores</p>
    <p>El siguiente cuadro presenta los valores relacionados en el avalúo, los cuales se explican a continuación:</p>

    <table width="100%" cellspacing="0" cellpadding="6" style="border-collapse:collapse; margin-top:10px;">
        <thead>
            <tr>
                <th style="background-color:#d71920; color:#fff; text-align:center; border:1px solid #fff;">Detalles</th>
                <th style="background-color:#d71920; color:#fff; text-align:center; border:1px solid #fff;">Valores</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="background-color:#d9d9d9; border:1px solid #fff;">Valor Razonable</td>
                <td style="background-color:#d9d9d9; border:1px solid #fff;">${{number_format($avaluo->valor_razonable, 0, 0, '.')}}</td>
            </tr>
            <tr>
                <td style="background-color:#d9d9d9; border:1px solid #fff;">Valor Carrocería (+)</td>
                <td style="background-color:#d9d9d9; border:1px solid #fff;">${{number_format($avaluo->valor_carroceria, 0, 0, '.')}}</td>
            </tr>
            <tr>
                <td style="background-color:#d9d9d9; border:1px solid #fff;">Valor Reparaciones (-) (mano de obra + repuestos)</td>
                <td style="background-color:#d9d9d9; border:1px solid #fff;">${{number_format($avaluo->valor_reparaciones, 0, 0, '.')}}</td>
            </tr>
            <tr>
                <td style="background-color:#d9d9d9; border:1px solid #fff;">Valor Llantas (-)</td>
                <td style="background-color:#d9d9d9; border:1px solid #fff;">${{number_format($avaluo->valor_llantas, 0, 0, '.')}}</td>
            </tr>
            <tr>
                <td style="background-color:#d9d9d9; border:1px solid #fff;">Valor Pintura (-)</td>
                <td style="background-color:#d9d9d9; border:1px solid #fff;">${{number_format($avaluo->valor_pintura, 0, 0, '.')}}</td>
            </tr>
            <tr>
                <td style="background-color:#d9d9d9; border:1px solid #fff;">Valor Overhaul Motor (-)</td>
                <td style="background-color:#d9d9d9; border:1px solid #fff;">${{number_format($avaluo->valor_overhaul_motor, 0, 0, '.')}}</td>
            </tr>
            <tr>
                <td style="background-color:#d9d9d9; border:1px solid #fff;">Valor Accesorios o Adecuaciones (+)</td>
                <td style="background-color:#d9d9d9; border:1px solid #fff;">${{number_format($avaluo->valor_accesorios, 0, 0, '.')}}</td>
            </tr>
            <tr>
                <td style="background-color:#d9d9d9; border:1px solid #fff;">Factor Demérito % (-)</td>
                <td style="background-color:#d9d9d9; border:1px solid #fff;">${{number_format($avaluo->factor_demerito, 0, 0, '.')}}</td>
            </tr>
            <tr>
                <td style="background-color:#d9d9d9; border:1px solid #fff;">Índice de Reparabilidad Mínimo (%)</td>
                <td style="background-color:#d9d9d9; border:1px solid #fff;">{{$avaluo->indice_responsabilidad_minimo}}</td>
            </tr>
        </tbody>
    </table>

    <!-- Avalúo Comercial -->
    <div style="background-color:#d71920; color:#fff; font-weight:bold; text-align:center; padding:6px; margin-top:12px;">
       Valor Avalúo
    </div>
    <table width="100%" cellspacing="0" cellpadding="8" style="border-collapse:collapse; margin-top:0;">
        <tr>
            <td style="background-color:#f2f2f2; text-align:center; font-weight:bold; border:1px solid #ccc;">
                El avalúo del bien es de ${{number_format($avaluo->avaluo_total, 0, 0, '.')}} Pesos M/Cte.
            </td>
        </tr>
    </table>

    <!-- Vigencia del Avalúo -->
    <div style="background-color:#d71920; color:#fff; font-weight:bold; text-align:center; padding:6px; margin-top:12px;">
        Vigencia del Avalúo
    </div>
    <p style="font-size:12px; margin-top:6px;">
        De acuerdo con el Numeral 7 del Artículo 2 del Decreto N° 422 de Marzo 08 de 2000 y con el Artículo 19 del Decreto N° 1420 del 24 de Junio de 1998, expedidos por el Ministerio del Desarrollo Económico, el presente avalúo comercial tiene una vigencia de un (1) año, contado desde la fecha de su expedición, siempre y cuando las condiciones físicas del bien mueble valuado no sufra cambios significativos, así como tampoco se presenten variaciones representativas de las condiciones del mercado mobiliario.
    </p>
    <div style="background-color:#d71920; color:#fff; font-weight:bold; text-align:center; padding:6px; margin-top:20px;">
        Marco Legal
    </div>
    <p style="font-size:12px; margin-top:6px;">
       El presente avalúo comercial está bajo los lineamientos legales contemplados en las Normas Técnicas Sectoriales: NTS S 03, NTS S 04, NTS M 04; la Guía Técnica Sectorial: GTS G02, GTS E 03; NIIF 13.

El valuador no será responsable por aspectos de naturaleza legal que afecten el bien, a la propiedad valuada o al título legal de la misma.

El valuador no revelará información sobre esta valoración a nadie distinto de la persona natural o jurídica que solicitó el encargo valuatorio y solo lo hará con autorización escrita de esta, salvo en el caso en que el informe sea requerido por autoridad competente.
<br>
<strong>NOTA:<strong> La información contenida en este informe no podrá ser publicada total o parcialmente, así mismo, tampoco los nombres ni sus afiliaciones profesionales de los valuadores, sin consentimiento escrito del valuador (En concordancia con la Norma Técnica Sectorial NTS S 03 numeral 7.1.9 de fecha 2009-09-10).
    </p>
</div>


<!-- ================= HOJA 3 ================= -->
<div style="page-break-before: always; font-family: Arial, sans-serif; font-size: 13px; color:#000;">

    <!-- RESPONSABILIDAD DEL AVALUADOR -->
    <div style="background-color:#d71920; color:#fff; font-weight:bold; text-align:center; padding:6px; font-size:14px;margin-top: 30px;">
        Responsabilidad del Avaluador
    </div>
    <ul style="margin:10px 0; padding-left:20px;">
        <li>El avaluador no será responsable por aspectos de naturaleza legal que afecten el bien, a la propiedad valuada o al título legal de la misma.</li>
        <li>El avaluador no revelará información sobre esta valoración a nadie distinto de la persona natural o jurídica que solicitó el encargo valuatorio y solo lo hará con la autorización escrita de ésta, salvo en el caso en que el informe sea requerido por una autoridad competente.</li>
        <li>El avaluador pone de manifiesto que no tiene ningún tipo de relación directa o indirecta con el solicitante o propietario del bien objeto de valuación y no tiene conflicto de intereses.</li>
    </ul>

    <!-- RIESGO E INCERTIDUMBRE -->
    <div style="background-color:#d71920; color:#fff; font-weight:bold; text-align:center; padding:6px; font-size:14px; margin-top:16px;">
        Riesgo e Incertidumbre
    </div>
    <p style="margin-top:8px; text-align:justify;">
        Al momento de la vista se presenta factura de venta {{$avaluo->no_factura}}, declaración de importación {{$avaluo->declaracion_importacion}}, 
        fue importado el día {{$avaluo->fecha_importacion}}, presenta registro de maquinaria: {{$avaluo->registro_maquinaria}}, y GPS {{$avaluo->gps}}.
    </p>
    <p style="text-align:justify;">
        Presenta Certificado de Libertad y Tradición con el fin de poder validar el historial de propietarios, medidas
        cautelares, limitaciones, gravámenes y la titularidad actual.
    </p>

    <!-- CLAUSULAS DE PROHIBICIÓN -->
    <div style="background-color:#d71920; color:#fff; font-weight:bold; text-align:center; padding:6px; font-size:14px; margin-top:16px;">
        Cláusulas de Prohibición de Publicación del Informe
    </div>
    <p style="margin-top:8px; text-align:justify;">
        El presente informe valuatorio es de carácter confidencial y su finalidad está definida en este documento,
        por lo tanto, queda expresamente prohibida, cualquier otra utilización, la publicación de parte o la totalidad
        del informe de valuación, cualquier referencia al mismo, a las cifras de avaluación, al nombre y afiliaciones
        profesionales del avaluador, sin el consentimiento de éste.
    </p>

    <!-- FUENTES DE CONSULTA -->
    <div style="background-color:#d71920; color:#fff; font-weight:bold; text-align:center; padding:6px; font-size:14px; margin-top:16px;">
        Fuentes de Consulta
    </div>
    <ul style="margin:10px 0; padding-left:20px;">
        <li>Licencia de tránsito</li>
        <li>Consulta Runt</li>
        <li>Consultas Fasecolda</li>
    </ul>
</div>

<!-- ================= HOJA 4 ================= -->
<div style="page-break-before: always; font-family: Arial, sans-serif; font-size: 13px; color:#000;">

    <!-- TÍTULO PRINCIPAL -->
    <div style="background-color:#d71920; color:#fff; font-weight:bold; text-align:center; padding:6px; font-size:14px; margin-bottom:12px;margin-top: 30px;">
        Conceptos y Definiciones
    </div>

    <!-- FILA 1 -->
    <table style="width:100%; border-collapse:collapse; margin-bottom:12px;">
        <tr>
            <td style="width:50%; border:1px solid #ccc; vertical-align:top;">
                <div style="background:#d71920; color:#fff; font-weight:bold; padding:5px;">Valor Razonable (Automotor y/o Carrocería)</div>
                <div style="padding:6px; text-align:justify;">
                    Es el valor por el cual un vehículo puede venderse en la fecha de valuación entre partes dispuestas, en una transacción libre y debidamente informada. 
                    Se consideran también el kilometraje y los accesorios o adaptaciones no originales que afecten su valor.
                </div>
            </td>
            <td style="width:50%; border:1px solid #ccc; vertical-align:top;">
                <div style="background:#d71920; color:#fff; font-weight:bold; padding:5px;">Valor Accesorios o Adecuaciones</div>
                <div style="padding:6px; text-align:justify;">
                    Son accesorios especiales que generen una variación importante en el valor razonable del vehículo. 
                    Se consideran elementos como blindajes, adecuaciones, sistemas de audio y video, entre otros, que afecten significativamente sus condiciones originales.
                </div>
            </td>
        </tr>
    </table>

    <!-- FILA 2 -->
    <table style="width:100%; border-collapse:collapse; margin-bottom:12px;">
        <tr>
            <td style="width:50%; border:1px solid #ccc; vertical-align:top;">
                <div style="background:#d71920; color:#fff; font-weight:bold; padding:5px;">Valores Repuestos + Mano de Obra</div>
                <div style="padding:6px; text-align:justify;">
                    Es la cuantía necesaria para que el vehículo retorne a sus condiciones originales de fábrica. 
                    Esta valoración se determina a partir de temparios estandarizados de reparación de piezas, considerando la marca, modelo y nivel de afectación del vehículo.
                </div>
            </td>
            <td style="width:50%; border:1px solid #ccc; vertical-align:top;">
                <div style="background:#d71920; color:#fff; font-weight:bold; padding:5px;">Valor Llantas</div>
                <div style="padding:6px; text-align:justify;">
                    Es el valor promedio de mercado del vehículo, estimado según sus especificaciones generales de marca, referencia, versión y estado general observado 
                    al momento de la inspección técnica.
                </div>
            </td>
        </tr>
    </table>

    <!-- FILA 3 -->
    <table style="width:100%; border-collapse:collapse; margin-bottom:12px;">
        <tr>
            <td style="width:50%; border:1px solid #ccc; vertical-align:top;">
                <div style="background:#d71920; color:#fff; font-weight:bold; padding:5px;">Valores Pintura</div>
                <div style="padding:6px; text-align:justify;">
                    Es el valor aproximado de la pintura general del vehículo aplicada sobre una superficie óptima.
                </div>
            </td>
            <td style="width:50%; border:1px solid #ccc; vertical-align:top;">
                <div style="background:#d71920; color:#fff; font-weight:bold; padding:5px;">Valor Overhaul</div>
                <div style="padding:6px; text-align:justify;">
                    Corresponde al valor para poner en funcionamiento un motor partiendo de un mantenimiento preventivo.
                </div>
            </td>
        </tr>
    </table>

    <!-- FILA 4 -->
    <table style="width:100%; border-collapse:collapse; margin-bottom:12px;">
        <tr>
            <td style="width:50%; border:1px solid #ccc; vertical-align:top;">
                <div style="background:#d71920; color:#fff; font-weight:bold; padding:5px;">Índice de Reparabilidad Mínimo</div>
                <div style="padding:6px; text-align:justify;">
                    Corresponde a la fracción en porcentaje (%) del valor razonable del vehículo que costaría una reparación, 
                    estimada en piezas y mano de obra para que el vehículo se mantenga en condiciones funcionales. 
                    Se considera que cuando este porcentaje supere un índice aproximado del 75%, corresponde a que el vehículo se considera no reparable.
                </div>
            </td>
            <td style="width:50%; border:1px solid #ccc; vertical-align:top;">
                <div style="background:#d71920; color:#fff; font-weight:bold; padding:5px;">Valor Total Chatarra (Automotor y/o Carrocería)</div>
                <div style="padding:6px; text-align:justify;">
                    Solo se aplica al vehículo presentado daños graves que afectan su seguridad y funcionalidad. 
                    En este caso, se establece un valor como chatarra. 
                    El cálculo se basa en el peso de los elementos metálicos aprovechables.
                </div>
            </td>
        </tr>
    </table>
    <table class="tabla-bottom">
                    <tr>
                        <td width="50%">
                        @php
                            $imagefirma_inspector = $ingreso->images->where('categoria', 'firma_evaluador')->first();
                        @endphp

                        @if($imagefirma_inspector)
                            <img src="{{ public_path($imagefirma_inspector->path) }}" 
                                alt="Matrícula" 
                                style="height:140px; object-fit:cover;">
                                <hr>                       
                        @endif
                            {{$user->name}} <br>
                            {{$user->profesion}} <br>
                            TP.{{$user->tarjeta_profecional}} <br>
                            R.A.A. {{$user->r_aa}}
                        </td>

                        <td width="50%" align="center">
                            
                        </td>
                    </tr>
                </table>

</div>

<!-- ================= HOJA 5 ================= -->
<div style="page-break-before: always; font-family: Arial, sans-serif; font-size: 12px; color:#000;">

    <!-- TÍTULO PRINCIPAL -->
    <div style="background-color:#d71920; color:#fff; font-weight:bold; text-align:center; padding:6px; font-size:14px; margin-bottom:12px;margin-top: 30px;">
        Informe Fotográfico
    </div>

    <!-- TABLA DE FOTOS -->
    <table style="width:100%; border-collapse:collapse; text-align:center;">
        <tr>
            <td style="width:50%; padding:8px; vertical-align:top;">
                <div style="border:1px solid #000; height:190px; overflow: hidden;">
                    @foreach($ingreso->images as $image)
                        @if($image->categoria == 'frontal')
                            @php
                                $path = public_path($image->path);
                                $type = pathinfo($path, PATHINFO_EXTENSION);
                                $data = file_get_contents($path);
                                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                            @endphp
                            <div style="width:100%; height:190px; 
                                        background-image: url('{{ $image->path }}');
                                        background-size: cover;
                                        background-position: center center;">
                            </div>                                                       
                            @break
                        @endif
                    @endforeach
                </div>
                <div style="background:#d71920; color:#fff; font-weight:bold; padding:4px; margin-top:2px;">Frontal</div>
            </td>

            <td style="width:50%; padding:8px; vertical-align:top;">
                <div style="border:1px solid #000; height:190px; overflow: hidden;">
                    @foreach($ingreso->images as $imaged)
                        @if($imaged->categoria == 'derecha')
                            @php
                                $path = public_path($imaged->path);
                                $type = pathinfo($path, PATHINFO_EXTENSION);
                                $data = file_get_contents($path);
                                $base64d = 'data:image/' . $type . ';base64,' . base64_encode($data);
                            @endphp
                            <div style="width:100%; height:190px; 
                                        background-image: url('{{ $imaged->path  }}');
                                        background-size: cover;
                                        background-position: center center;">
                            </div>
                            {{$path}} 
                            @break
                        @endif
                    @endforeach
                </div>
                <div style="background:#d71920; color:#fff; font-weight:bold; padding:4px; margin-top:2px;">Derecha</div>
            </td>
        </tr>
        <tr>
            <td style="width:50%; padding:8px; vertical-align:top;">
                <div style="border:1px solid #000; height:190px; overflow: hidden;">
                    @foreach($ingreso->images as $imagei)
                        @if($imagei->categoria == 'izquierda')
                            @php
                                $path = public_path($imagei->path);
                                $type = pathinfo($path, PATHINFO_EXTENSION);
                                $data = file_get_contents($path);
                                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                            @endphp
                            <div style="width:100%; height:190px; 
                                        background-image: url('{{ $imagei->path }}');
                                        background-size: cover;
                                        background-position: center center;">
                            </div>
                            @break
                        @endif
                    @endforeach
                </div>
                <div style="background:#d71920; color:#fff; font-weight:bold; padding:4px; margin-top:2px;">Izquierda</div>
            </td>
            <td style="width:50%; padding:8px; vertical-align:top;">
                <div style="border:1px solid #000; height:190px; overflow: hidden;">
                    @foreach($ingreso->images as $image)
                        @if($image->categoria == 'trasera')
                            @php
                                $path = public_path($image->path);
                                $type = pathinfo($path, PATHINFO_EXTENSION);
                                $data = file_get_contents($path);
                                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                            @endphp
                            <div style="width:100%; height:190px; 
                                        background-image: url('{{ $image->path }}');
                                        background-size: cover;
                                        background-position: center center;">
                            </div>
                            @break
                        @endif
                    @endforeach
                </div>
                <div style="background:#d71920; color:#fff; font-weight:bold; padding:4px; margin-top:2px;">Trasera</div>
            </td>
        </tr>
        <tr>
           @php
                // Filtrar imágenes con categoría 'motor' y tomar máximo 2
                $motorImages = $ingreso->images->where('categoria', 'motor')->take(2);
            @endphp

            @foreach($motorImages as $image)
                @php
                    $path = public_path($image->path);
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data = file_get_contents($path);
                    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                @endphp
                <td style="width:50%; padding:8px; vertical-align:top;">
                    <div style="border:1px solid #000; height:190px; overflow: hidden;">
                        <img src="{{ $image->path }}" alt="Motor" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div style="background:#d71920; color:#fff; font-weight:bold; padding:4px; margin-top:2px;">Motor</div>
                </td>
            @endforeach

            {{-- Si hay menos de 2 imágenes motor, completar espacios vacíos --}}
            @for ($i = $motorImages->count(); $i < 2; $i++)
                <td style="width:50%; padding:8px; vertical-align:top;">
                    <div style="border:1px solid #000; height:190px; overflow: hidden;">
                        <p style="text-align:center; line-height:190px; color:#999;">Sin imagen</p>
                    </div>
                    <div style="background:#d71920; color:#fff; font-weight:bold; padding:4px; margin-top:2px;">Motor</div>
                </td>
            @endfor

        </tr>
    </table>

</div>

<!-- ================= HOJA 6 ================= -->
<div style="page-break-before: always; font-family: Arial, sans-serif; font-size: 12px; color:#000;">

    <!-- TÍTULO PRINCIPAL -->
    <div style="background-color:#d71920; color:#fff; font-weight:bold; text-align:center; padding:6px; font-size:14px; margin-bottom:12px;margin-top: 30px;">
        Informe Fotográfico
    </div>

    <!-- TABLA DE FOTOS -->
    <table style="width:100%; border-collapse:collapse; text-align:center;">
        <tr>            
            @php
                // Filtrar imágenes con categoría 'sistema_identificacion' y tomar máximo 2
                $sistemaImages = $ingreso->images->where('categoria', 'sistema_identificacion')->take(2);
            @endphp

            @foreach($sistemaImages as $image)
                @php
                    $path = public_path($image->path);
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data = file_get_contents($path);
                    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                @endphp
                <td style="width:50%; padding:8px; vertical-align:top;">
                    <div style="border:1px solid #000; height:190px; overflow: hidden;">
                        <img src="{{ $image->path }}" alt="Sistema identificacion" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div style="background:#d71920; color:#fff; font-weight:bold; padding:4px; margin-top:2px;">Sistemas de Identificación</div>
                </td>
            @endforeach

            {{-- Si hay menos de 2 imágenes sistema, completar espacios vacíos --}}
            @for ($i = $sistemaImages->count(); $i < 2; $i++)
                <td style="width:50%; padding:8px; vertical-align:top;">
                    <div style="border:1px solid #000; height:190px; overflow: hidden;">
                        <p style="text-align:center; line-height:190px; color:#999;">Sin imagen</p>
                    </div>
                    <div style="background:#d71920; color:#fff; font-weight:bold; padding:4px; margin-top:2px;">Sistemas de Identificación</div>
                </td>
            @endfor
        </tr>
        <tr>           
            @php
                // Filtrar imágenes con categoría 'habitaculo' y tomar máximo 2
                $habitaculoImages = $ingreso->images->where('categoria', 'habitaculo')->take(2);
            @endphp

            @foreach($habitaculoImages as $image)
                @php
                    $path = public_path($image->path);
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data = file_get_contents($path);
                    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                @endphp
                <td style="width:50%; padding:8px; vertical-align:top;">
                    <div style="border:1px solid #000; height:190px; overflow: hidden;">
                        <img src="{{ $image->path }}" alt="habitaculo identificacion" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div style="background:#d71920; color:#fff; font-weight:bold; padding:4px; margin-top:2px;">Habitaculo</div>
                </td>
            @endforeach

            {{-- Si hay menos de 2 imágenes habitaculo, completar espacios vacíos --}}
            @for ($i = $habitaculoImages->count(); $i < 2; $i++)
                <td style="width:50%; padding:8px; vertical-align:top;">
                    <div style="border:1px solid #000; height:190px; overflow: hidden;">
                        <p style="text-align:center; line-height:190px; color:#999;">Sin imagen</p>
                    </div>
                    <div style="background:#d71920; color:#fff; font-weight:bold; padding:4px; margin-top:2px;">Habitaculo</div>
                </td>
            @endfor
        </tr>
        <tr>            
             @php
                // Filtrar imágenes con categoría 'baul' y tomar máximo 2
                $baulImages = $ingreso->images->where('categoria', 'baul')->take(2);
            @endphp

            @foreach($baulImages as $image)
                @php
                    $path = public_path($image->path);
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data = file_get_contents($path);
                    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                @endphp
                <td style="width:50%; padding:8px; vertical-align:top;">
                    <div style="border:1px solid #000; height:190px; overflow: hidden;">
                        <img src="{{ $image->path }}" alt="baul" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div style="background:#d71920; color:#fff; font-weight:bold; padding:4px; margin-top:2px;">Baul</div>
                </td>
            @endforeach

            {{-- Si hay menos de 2 imágenes baul, completar espacios vacíos --}}
            @for ($i = $baulImages->count(); $i < 2; $i++)
                <td style="width:50%; padding:8px; vertical-align:top;">
                    <div style="border:1px solid #000; height:190px; overflow: hidden;">
                        <p style="text-align:center; line-height:190px; color:#999;">Sin imagen</p>
                    </div>
                    <div style="background:#d71920; color:#fff; font-weight:bold; padding:4px; margin-top:2px;">Baul</div>
                </td>
            @endfor
        </tr>
    </table>

</div>

<!-- ================= HOJA 7 ================= -->
<div style="page-break-before: always; font-family: Arial, sans-serif; font-size: 12px; color:#000;">

    <!-- TÍTULO PRINCIPAL -->
    <div style="background-color:#d71920; color:#fff; font-weight:bold; text-align:center; padding:6px; font-size:14px; margin-bottom:12px;margin-top: 30px;">
        Informe Fotográfico
    </div>

    <!-- TABLA DE FOTOS -->
    <table style="width:100%; border-collapse:collapse; text-align:center;">
        <tr>
            <td style="width:50%; padding:8px; vertical-align:top;">
                <div style="border:1px solid #000; height:190px; overflow: hidden;">
                    @foreach($ingreso->images as $image)
                        @if($image->categoria == 'llanta_delantera_izq')
                            @php
                                $path = public_path($image->path);
                                $type = pathinfo($path, PATHINFO_EXTENSION);
                                $data = file_get_contents($path);
                                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                            @endphp
                            <div style="width:100%; height:190px; 
                                        background-image: url('{{ $image->path }}');
                                        background-size: cover;
                                        background-position: center center;">
                            </div>
                            @break
                        @endif
                    @endforeach
                </div>
                <div style="background:#d71920; color:#fff; font-weight:bold; padding:4px; margin-top:2px;">Llanda Delantera Izquierda</div>
            </td>
            <td style="width:50%; padding:8px; vertical-align:top;">
                <div style="border:1px solid #000; height:190px; overflow: hidden;">
                    @foreach($ingreso->images as $image)
                        @if($image->categoria == 'llanta_delantera_der')
                            @php
                                $path = public_path($image->path);
                                $type = pathinfo($path, PATHINFO_EXTENSION);
                                $data = file_get_contents($path);
                                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                            @endphp
                            <div style="width:100%; height:190px; 
                                        background-image: url('{{ $image->path }}');
                                        background-size: cover;
                                        background-position: center center;">
                            </div>
                            @break
                        @endif
                    @endforeach
                </div>
                <div style="background:#d71920; color:#fff; font-weight:bold; padding:4px; margin-top:2px;">Llanda Delantera Derecha</div>
            </td>
        </tr>
        <tr>
            <td style="width:50%; padding:8px; vertical-align:top;">
                <div style="border:1px solid #000; height:190px; overflow: hidden;">
                    @foreach($ingreso->images as $image)
                        @if($image->categoria == 'llantas_trasera_izq')
                            @php
                                $path = public_path($image->path);
                                $type = pathinfo($path, PATHINFO_EXTENSION);
                                $data = file_get_contents($path);
                                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                            @endphp
                            <div style="width:100%; height:190px; 
                                        background-image: url('{{ $image->path }}');
                                        background-size: cover;
                                        background-position: center center;">
                            </div>
                            @break
                        @endif
                    @endforeach
                </div>
                <div style="background:#d71920; color:#fff; font-weight:bold; padding:4px; margin-top:2px;">Llanta Trasera Izquierda</div>
            </td>
            <td style="width:50%; padding:8px; vertical-align:top;">
                <div style="border:1px solid #000; height:190px; overflow: hidden;">
                    @foreach($ingreso->images as $image)
                        @if($image->categoria == 'llantas_trasera_der')
                            @php
                                $path = public_path($image->path);
                                $type = pathinfo($path, PATHINFO_EXTENSION);
                                $data = file_get_contents($path);
                                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                            @endphp
                            <div style="width:100%; height:190px; 
                                        background-image: url('{{ $image->path }}');
                                        background-size: cover;
                                        background-position: center center;">
                            </div>
                            @break
                        @endif
                    @endforeach
                </div>
                <div style="background:#d71920; color:#fff; font-weight:bold; padding:4px; margin-top:2px;">Llanta Trasera Derecha</div>
            </td>
        </tr>
        <tr>            
            @php
                // Filtrar imágenes con categoría 'parte_baja' y tomar máximo 2
                $partebajaImages = $ingreso->images->where('categoria', 'parte_baja')->take(2);
            @endphp

            @foreach($partebajaImages as $image)
                @php
                    $path = public_path($image->path);
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data = file_get_contents($path);
                    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                @endphp
                <td style="width:50%; padding:8px; vertical-align:top;">
                    <div style="border:1px solid #000; height:190px; overflow: hidden;">
                        <img src="{{ $image->path }}" alt="Parte Baja" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div style="background:#d71920; color:#fff; font-weight:bold; padding:4px; margin-top:2px;">Parte Baja</div>
                </td>
            @endforeach

            {{-- Si hay menos de 2 imágenes partebaja, completar espacios vacíos --}}
            @for ($i = $partebajaImages->count(); $i < 2; $i++)
                <td style="width:50%; padding:8px; vertical-align:top;">
                    <div style="border:1px solid #000; height:190px; overflow: hidden;">
                        <p style="text-align:center; line-height:190px; color:#999;">Sin imagen</p>
                    </div>
                    <div style="background:#d71920; color:#fff; font-weight:bold; padding:4px; margin-top:2px;">Parte Baja</div>
                </td>
            @endfor
        </tr>
    </table>

</div>

<!-- ================= HOJA 8 ================= -->
<div style="page-break-before: always; font-family: Arial, sans-serif; font-size: 12px; color:#000;">

    <!-- TÍTULO PRINCIPAL -->
    <div style="background-color:#d71920; color:#fff; font-weight:bold; text-align:center; padding:6px; font-size:14px; margin-bottom:12px;margin-top: 30px;">
        Informe Fotográfico
    </div>

    <!-- TABLA DE FOTOS -->
    <table style="width:100%; border-collapse:collapse; text-align:center;">
      
@php
    // Filtrar imágenes con categoría 'extra', tomar máximo 6 y resetear índices
    $extraImages = $ingreso->images->where('categoria', 'extra')->take(6)->values();

    // Rellenar con nulos si hay menos de 6
    $totalImages = $extraImages->count();
    for ($i = $totalImages; $i < 6; $i++) {
        $extraImages->push(null);
    }
@endphp

@for ($i = 0; $i < 6; $i += 2)
    <tr>
        @for ($j = 0; $j < 2; $j++)
            @php
                $image = $extraImages[$i + $j];
            @endphp
            <td style="width:50%; padding:8px; vertical-align:top;">
                <div style="border:1px solid #000; height:190px; overflow: hidden;">
                    @if ($image)
                        @php
                            $path = public_path($image->path);
                            $type = pathinfo($path, PATHINFO_EXTENSION);
                            $data = file_get_contents($path);
                            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                        @endphp
                        <img src="{{ $image->path }}" alt="Parte Baja" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <p style="text-align:center; line-height:190px; color:#999;">Sin imagen</p>
                    @endif
                </div>
                <div style="background:#d71920; color:#fff; font-weight:bold; padding:4px; margin-top:2px;">Extra</div>
            </td>
        @endfor
    </tr>
@endfor

    </table>

</div>




        </div>
    </div>

</body>
</html>
