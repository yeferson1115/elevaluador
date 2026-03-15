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
            border: 1px solid #000;
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
        .back-rojo{
           background: #c00;
            color: #fff;
            font-weight: 700; 
            font-size: 10px;
        }
        .blanco{
            background: #fff;
            font-size: 10px;
        }
        .bg-gris{
                background: #484848;
                color: #fff;
                text-align: center;
                font-size: 14px;
        }
        .bg-gris1{
                background: #5e5e5eff;
                color: #fff;
                font-weight: 700; 
                font-size: 10px;
        }
        .table-b-negro{
            border: 1px solid #000;
            padding: 4px;
            vertical-align: top;
            border-collapse: collapse;

        }
        .table-b-negro td{
          border: 1px solid #000;  
        }

        .caja {
            border: 1px solid #000;
            height: 200px; /* ajusta la altura de la caja */
            margin: 20px;
        }

        .footer-caja {
            background-color: #b40000;
            color: #fff;
            font-weight: bold;
            text-align: center;
            padding: 5px;
        }

        .tabla-bottom {
            width: 100%;
            margin: 20px;
            font-size: 11px;
        }

        .tabla-bottom td {
            vertical-align: top;
            padding: 5px;
        }

        .firma {
            margin-top: 40px;
            text-align: center;
        }

        .firma-linea {
            border-top: 1px solid #000;
            width: 200px;
            margin: 0 auto;
        }

        .firma-label {
            margin-top: 5px;
            color: #b40000;
            font-weight: bold;
        }

        .caja-m0 {
            border: 1px solid #000;
            height:180px; /* ajusta la altura de la caja */
        }
        .indicador{
            font-size: 11px; 
            font-weight: bold;
            color: #fff;
            padding: 3px 8px;
            border-radius: 4px;
        }
        .bueno{
           background: #28a745; 
        }
        .aceptable{
           background: #f0ad4e; 
        }
        .malo{
           background: #dc3545; 
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
                    <strong style="font-size: 25px;">CERTIFICACION DE INSPECCIÓN</strong><br>                    
                </td>

                {{-- Caja derecha con 3 secciones --}}
                <td style="width: 25%; vertical-align: top; border: 1px solid #000;">
                    <table style="width: 100%; font-size: 10px; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 5px; text-align: right;">
                                <strong>Fecha</strong> <span>{{ \Carbon\Carbon::parse($inspec->updated_at)->format('d/m/y') }}</span>
                            </td>
                        </tr>

                        <tr>
                            <td style="border-top: 1px solid #000; padding: 5px; text-align: right;">
                                <strong>No. Servicio</strong> {{$ingreso->id}}
                            </td>
                        </tr>

                        <tr>
                            <td style="border: 2px solid #000000;padding: 5px;font-size: 16px;font-weight: bold; text-align: center; color: #000;background: #fcfc08;">
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

        <!--Información del Bien-->
        <div class="section" style="margin-top: 20px;">
                <h3>Información del Bien</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="width:50%;border-right: none;padding: 7px 0px;">
                            <table class="info-bien"  style="width:100%;border-collapse: collapse; border: solid 1px #fff;">
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Clase</td> 
                                  <td class="blanco">{{$ingreso->clase}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Marca</td> 
                                  <td class="blanco">{{$ingreso->marca}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Tipo</td> 
                                  <td class="blanco">{{$ingreso->tipo_propiedad}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Carroceria</td> 
                                  <td class="blanco">{{$ingreso->tipo_carroceria}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Modelo</td> 
                                  <td class="blanco">{{$ingreso->modelo}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Nacionalidad</td> 
                                  <td class="blanco">{{$ingreso->nacionalidad}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Tipo Caja</td> 
                                  <td class="blanco">{{$ingreso->caja_cambios}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Cilindraje</td> 
                                  <td class="blanco">{{$ingreso->cilindraje}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Aseguradora</td> 
                                  <td class="blanco">{{$inspec->aseguradora}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Intermediaria</td> 
                                  <td class="blanco">{{$inspec->intermediaria}}</td> 
                                </tr>                                
                            </table>
                        </td>
                        <td style="width:50%;border-left: none;padding: 7px 0px 15px 15px;">
                            <table class="info-bien"  style="width:100%;border-collapse: collapse; border: solid 1px #fff;">
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Combustible</td> 
                                  <td class="blanco">{{$inspec->combustible}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Tipo de Pintura</td> 
                                  <td class="blanco">{{$inspec->tipo_pintura}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Servicio</td> 
                                  <td class="blanco">{{$inspec->servicio}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Kilometraje</td> 
                                  <td class="blanco">{{$inspec->kilometraje}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Color</td> 
                                  <td class="blanco">{{$inspec->color}}<</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">No. Cahasis</td> 
                                  <td class="blanco">{{$ingreso->numero_chasis}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">No. Serial</td> 
                                  <td class="blanco">{{$ingreso->numero_serie}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">No. Motor</td> 
                                  <td class="blanco">{{$ingreso->numero_motor}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Sucursal</td> 
                                  <td class="blanco"></td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Clave</td> 
                                  <td class="blanco"></td> 
                                </tr>  
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Identificación</td> 
                                  <td class="blanco">{{$ingreso->documento_solicitante}}</td> 
                                </tr>                               
                            </table>
                        </td>
                    </tr>

                </table>
                <table style="border-collapse: collapse;">
                    <tr>
                        <td style="border: solid 1px #000; background: #000; color: #fff;padding: 7px;">No. Inspección</td>
                        <td style="border: solid 1px #000;padding: 7px;">{{$ingreso->id}}</td>
                    </tr>
                </table>
                <table style="width: 100%;">
                    <tr>
                        <td style="width:50%;border-right: none;padding: 7px 0px;">
                            <table class="info-bien"  style="width:100%;border-collapse: collapse; border: solid 1px #fff;">
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Centro de Inspección</td> 
                                  <td class="blanco">{{$inspec->centro_inspeccion}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Turno</td> 
                                  <td class="blanco">{{$inspec->turno}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Solicitado Por</td> 
                                  <td class="blanco">{{$ingreso->solicitante}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Servicio Solicitado</td> 
                                  <td class="blanco">{{$ingreso->tiposervicio}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Varlor Mercado</td> 
                                  <td class="blanco">{{$inspec->valor_mercado}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Valor El Evaluador</td> 
                                  <td class="blanco">{{$inspec->valor_evaluador}}</td> 
                                </tr>                                                                                      
                            </table>
                        </td>
                        <td style="width:50%;border-left: none;padding: 7px 0px 15px 15px;">
                            <table class="info-bien"  style="width:100%;border-collapse: collapse; border: solid 1px #fff;">
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Valor Accesorios</td> 
                                  <td class="blanco">{{$inspec->valor_accesorios}}</td> 
                                </tr> 
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Cod. Fasecolda</td> 
                                  <td class="blanco">{{$inspec->cod_fasecolda}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Valor Fasecollda</td> 
                                  <td class="blanco">{{$inspec->valor_fasecolda}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Resultado</td> 
                                  <td class="blanco">{{$inspec->resultado}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Intermediario</td> 
                                  <td class="blanco">{{$inspec->intermediario}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Cliente</td> 
                                  <td class="blanco">{{$ingreso->solicitante}}</td> 
                                </tr>
                                                              
                            </table>
                        </td>
                    </tr>

                </table>
                <p style="border: solid 2px #c00;padding: 7px;"><strong>Novedades de Inspección: </strong> {{$inspec->novedades_inspeccion}}</p>
        </div>

        <div style="page-break-before: always;"></div>

            <div class="section" style="margin-top: 20px;">
                <h3>Revisión Visual</h3>

                <!-- Tabla con imágenes y etiquetas -->
                <table  cellspacing="0" cellpadding="5" style="text-align: center; width: 100%; table-layout: fixed;">
                    <tr>
                        <!-- Carro top (2 filas) -->
                        <td rowspan="2" style="width: 20%;">
                            <img src="{{ public_path('imagenes/carro_top.png') }}" alt="Vista Superior" style="width: 100%; height: auto;"><br>
                            
                            @foreach($inspec->inspeccionVisual as $visual)
                                @if($visual->zona == 'vista-superior')
                                    @switch($visual->estado)
                                        @case('Bueno')
                                            <span class="indicador bueno">✔️ Bueno</span>
                                            @break

                                        @case('Aceptable')
                                            <span class="indicador aceptable">⚠️ Aceptable</span>
                                            @break

                                        @case('Malo')
                                            <span class="indicador malo">❌ Malo</span>
                                            @break
                                    @endswitch
                                @endif
                            @endforeach

                        </td>

                        <!-- Vista lateral derecha (más grande, ocupa 2 columnas) -->
                        <td colspan="2" style="width: 40%;">
                            <img src="{{ public_path('imagenes/vista_lateral_der.png') }}" alt="Vista Lateral Derecha" style="width: 100%; height: auto;"><br>
                            @foreach($inspec->inspeccionVisual as $visual)
                                @if($visual->zona == 'vista-lateral-der')
                                    @switch($visual->estado)
                                        @case('Bueno')
                                            <span class="indicador bueno">✔️ Bueno</span>
                                            @break

                                        @case('Aceptable')
                                            <span class="indicador aceptable">⚠️ Aceptable</span>
                                            @break

                                        @case('Malo')
                                            <span class="indicador malo">❌ Malo</span>
                                            @break
                                    @endswitch
                                @endif
                            @endforeach
                        </td>

                        <!-- Frontal -->
                        <td style="width: 20%;">
                            <img src="{{ public_path('imagenes/frontal.png') }}" alt="Vista Frontal" style="width: 100%; height: auto;"><br>
                            @foreach($inspec->inspeccionVisual as $visual)
                                @if($visual->zona == 'vista-frontal')
                                    @switch($visual->estado)
                                        @case('Bueno')
                                            <span class="indicador bueno">✔️ Bueno</span>
                                            @break

                                        @case('Aceptable')
                                            <span class="indicador aceptable">⚠️ Aceptable</span>
                                            @break

                                        @case('Malo')
                                            <span class="indicador malo">❌ Malo</span>
                                            @break
                                    @endswitch
                                @endif
                            @endforeach
                        </td>

                        <!-- Chasis (2 filas) -->
                        <td rowspan="2" style="width: 20%;">
                            <img src="{{ public_path('imagenes/chasis.png') }}" alt="Vista Inferior" style="width: 100%; height: auto;">
                            @foreach($inspec->inspeccionVisual as $visual)
                                @if($visual->zona == 'vista-inferior')
                                    @switch($visual->estado)
                                        @case('Bueno')
                                            <span class="indicador bueno">✔️ Bueno</span>
                                            @break

                                        @case('Aceptable')
                                            <span class="indicador aceptable">⚠️ Aceptable</span>
                                            @break

                                        @case('Malo')
                                            <span class="indicador malo">❌ Malo</span>
                                            @break
                                    @endswitch
                                @endif
                            @endforeach
                        </td>
                    </tr>

                    <tr>
                        <!-- Vista lateral izquierda (más grande, ocupa 2 columnas) -->
                        <td colspan="2" style="width: 40%;">
                            <img src="{{ public_path('imagenes/vista_lateral_izq.png') }}" alt="Vista Lateral Izquierda" style="width: 100%; height: auto;"><br>
                            @foreach($inspec->inspeccionVisual as $visual)
                                @if($visual->zona == 'vista-lateral-izq')
                                    @switch($visual->estado)
                                        @case('Bueno')
                                            <span class="indicador bueno">✔️ Bueno</span>
                                            @break

                                        @case('Aceptable')
                                            <span class="indicador aceptable">⚠️ Aceptable</span>
                                            @break

                                        @case('Malo')
                                            <span class="indicador malo">❌ Malo</span>
                                            @break
                                    @endswitch
                                @endif
                            @endforeach
                        </td>

                        <!-- Trasera -->
                        <td style="width: 20%;">
                            <img src="{{ public_path('imagenes/trasera.png') }}" alt="Vista Trasera" style="width: 100%; height: auto;"><br>
                            @foreach($inspec->inspeccionVisual as $visual)
                                @if($visual->zona == 'vista-trasera')
                                    @switch($visual->estado)
                                        @case('Bueno')
                                            <span class="indicador bueno">✔️ Bueno</span>
                                            @break

                                        @case('Aceptable')
                                            <span class="indicador aceptable">⚠️ Aceptable</span>
                                            @break

                                        @case('Malo')
                                            <span class="indicador malo">❌ Malo</span>
                                            @break
                                    @endswitch
                                @endif
                            @endforeach
                        </td>
                    </tr>
                </table>

            <!-- Leyenda de estados -->
                <table style="width: 100%; margin: 10px 0; font-size: 12px; border-collapse: collapse; text-align: center;">
                    <tr style="background: #606060;">
                        <td style="width: 33%;padding: 10px;">
                            <span style="background: #28a745; color: #fff; padding: 3px 8px; border-radius: 4px;">
                                ✔️ Bueno 90% - 100%
                            </span>
                        </td>
                        <td style="width: 33%;padding: 10px;">
                            <span style="background: #f0ad4e; color: #fff; padding: 3px 8px; border-radius: 4px;">
                                ⚠️ Aceptable 60% - 89%
                            </span>
                        </td>
                        <td style="width: 33%;padding: 10px;">
                            <span style="background: #dc3545; color: #fff; padding: 3px 8px; border-radius: 4px;">
                                X Malo 0% - 59%
                            </span>
                        </td>
                    </tr>
                </table>

                @php
    // Tomamos todos los campos relevantes en un array asociativo
    $campos = [
        'paragolpes_delantero' => 'Paragolpes delantero',
        'soporte_paragolpes_der' => 'Soporte paragolpes derecho',
        'soporte_paragolpes_izq' => 'Soporte paragolpes izquierdo',
        'rejilla_paragolpes' => 'Rejilla paragolpes',
        'capo' => 'Capó',
        'bisagra_capo' => 'Bisagra capó',
        'persiana' => 'Persiana',
        'unidad_farola_der' => 'Unidad Farola Derecha',
        'unidad_farola_izq' => 'Unidad Farola Izquierda',
        'luz_posicion_der' => 'Luz posición Derecha',
        'luz_posicion_izq' => 'Luz posición Izquierda',
        'exploradora_der' => 'Exploradora Derecha',
        'exploradora_izq' => 'Exploradora Izquierda',
        'cocuyo_der' => 'Cocuyo Derecho',
        'cocuyo_izq' => 'Cocuyo Izquierdo',
        'paragolpes_trasero' => 'Paragolpes trasero',
        'soporte_paragolpes_tras' => 'Soporte paragolpes trasero',
        'tapa_baul_compuerta' => 'Tapa baúl / Compuerta',
        'panel_trasero' => 'Panel trasero',
        'piso_baul' => 'Piso baúl',
        'stop_der' => 'Stop Derecho',
        'stop_izq' => 'Stop Izquierdo',
        'stop_compuerta_der' => 'Stop compuerta Derecho',
        'stop_compuerta_izq' => 'Stop compuerta Izquierdo',
        'tercer_stop' => 'Tercer stop',
        'tapizado_capota' => 'Tapizado capota',
        'alfombra_piso' => 'Alfombra piso',
        'tapizado_puerta_delantera_der' => 'Tapizado puerta delantera Derecha',
        'tapizado_puerta_delantera_izq' => 'Tapizado puerta delantera Izquierda',
        'tapizado_puerta_trasera_der' => 'Tapizado puerta trasera Derecha',
        'tapizado_puerta_trasera_izq' => 'Tapizado puerta trasera Izquierda',
        'tapizado_paral_parabrisas_der' => 'Tapizado paral parabrisas Derecho',
        'tapizado_paral_parabrisas_izq' => 'Tapizado paral parabrisas Izquierdo',
        'tapizado_paral_central_der' => 'Tapizado paral central Derecho',
        'tapizado_paral_central_izq' => 'Tapizado paral central Izquierdo',
        'tapizado_baul_der' => 'Tapizado baúl Derecho',
        'tapizado_baul_izq' => 'Tapizado baúl Izquierdo',
        'abullonado_millare' => 'Abullonado millaré',
        'consola_central' => 'Consola central',
        'mecanismo_elevavidrios_principal' => 'Mecanismo elevavidrios principal',
        'elevavidrios_puerta_delantera_der' => 'Elevavidrios puerta delantera Derecha',
        'elevavidrios_puerta_delantera_izq' => 'Elevavidrios puerta delantera Izquierda',
        'elevavidrios_puerta_trasera_der' => 'Elevavidrios puerta trasera Derecha',
        'elevavidrios_puerta_trasera_izq' => 'Elevavidrios puerta trasera Izquierda',
        'caja_direccion' => 'Caja dirección',
        'brazo_direccion' => 'Brazo dirección',
        'terminal_direccion' => 'Terminal dirección',
        'motor' => 'Motor',
        'caja_de_velocidades' => 'Caja de velocidades',
        'traccion_doble' => 'Tracción doble',
        'modulo_ECM_ECU_PCM' => 'Módulo ECM/ECU/PCM',
        'bomba_inyección' => 'Bomba de inyección',
        'turbo' => 'Turbo',
        'alternador' => 'Alternador',
        'caja_direccion_mec' => 'Caja dirección mecánica',
        'bateria' => 'Batería',
        'sistema_exhosto' => 'Sistema exhosto',
        'catalizador' => 'Catalizador',
        'embrague_termico' => 'Embrague térmico',
        'eje_delantero' => 'Eje delantero',
        'instalacion_electrica_motor' => 'Instalación eléctrica motor',
    ];

    // Filtramos los campos que tienen contenido
    $valores = [];
    foreach ($campos as $campo => $titulo) {
        $valor = $inspec->inspeccionRevisionVisualPuntoLiviano->$campo ?? null;
        if (!empty($valor)) {
            $valores[] = ['titulo' => $titulo, 'valor' => $valor];
        }
    }

    // Dividimos el array en 2 columnas de igual tamaño
    $mitad = ceil(count($valores) / 2);
    $columna1 = array_slice($valores, 0, $mitad);
    $columna2 = array_slice($valores, $mitad);
@endphp

<table style="width: 100%; margin-top: 15px; border-collapse: collapse;">
    <tr>
        <td style="width: 50%; vertical-align: top; padding: 10px;">
            <ul>
                @foreach($columna1 as $item)
                    <li><strong>{{ $item['titulo'] }}:</strong> {{ $item['valor'] }}</li>
                @endforeach
            </ul>
        </td>
        <td style="width: 50%; vertical-align: top; padding: 10px;">
            <ul>
                @foreach($columna2 as $item)
                    <li><strong>{{ $item['titulo'] }}:</strong> {{ $item['valor'] }}</li>
                @endforeach
            </ul>
        </td>
    </tr>
</table>


                

            </div>


            <div style="page-break-before: always;"></div>

            @php
    // Obtenemos todos los campos y valores dinámicos
    $puntos = [
        'Radiador' => $inspec->inspeccionRevisionVisualPuntoLiviano->radiador ?? null,
        'Condensador' => $inspec->inspeccionRevisionVisualPuntoLiviano->condensador ?? null,
        'Tijera' => $inspec->inspeccionRevisionVisualPuntoLiviano->tijera ?? null,
        'Portamangueta' => $inspec->inspeccionRevisionVisualPuntoLiviano->portamangueta ?? null,
        'Amortiguador delantero Derecho' => $inspec->inspeccionRevisionVisualPuntoLiviano->amortiguador_delantero_der ?? null,
        'Amortiguador delantero Izquierdo' => $inspec->inspeccionRevisionVisualPuntoLiviano->amortiguador_delantero_izq ?? null,
        'Muelle delantero Derecho' => $inspec->inspeccionRevisionVisualPuntoLiviano->muelle_delantero_der ?? null,
        'Muelle delantero Izquierdo' => $inspec->inspeccionRevisionVisualPuntoLiviano->muelle_delantero_izq ?? null,
        'Muelle trasero Derecho' => $inspec->inspeccionRevisionVisualPuntoLiviano->muelle_trasero_der ?? null,
        'Muelle trasero Izquierdo' => $inspec->inspeccionRevisionVisualPuntoLiviano->muelle_trasero_izq ?? null,
        'Amortiguador trasero Derecho' => $inspec->inspeccionRevisionVisualPuntoLiviano->amortiguador_trasero_der ?? null,
        'Amortiguador trasero Izquierdo' => $inspec->inspeccionRevisionVisualPuntoLiviano->amortiguador_trasero_izq ?? null,
        'Puente delantero' => $inspec->inspeccionRevisionVisualPuntoLiviano->puente_delantero ?? null,
        'Cuna motor' => $inspec->inspeccionRevisionVisualPuntoLiviano->cuna_motor ?? null,
        'Puente trasero' => $inspec->inspeccionRevisionVisualPuntoLiviano->puente_trasero ?? null,
        'Suspensión multilink trasera' => $inspec->inspeccionRevisionVisualPuntoLiviano->suspension_multilink_trasera ?? null,
        'Punta chasis delantera Derecha' => $inspec->inspeccionRevisionVisualPuntoLiviano->punta_chasis_delantera_der ?? null,
        'Punta chasis delantera Izquierda' => $inspec->inspeccionRevisionVisualPuntoLiviano->punta_chasis_delantera_izq ?? null,
        'Punta chasis trasera Derecha' => $inspec->inspeccionRevisionVisualPuntoLiviano->punta_chasis_trasera_der ?? null,
        'Punta chasis trasera Izquierda' => $inspec->inspeccionRevisionVisualPuntoLiviano->punta_chasis_trasera_izq ?? null,
        'Viga chasis' => $inspec->inspeccionRevisionVisualPuntoLiviano->viga_chasis ?? null,
        'Traviesa chasis' => $inspec->inspeccionRevisionVisualPuntoLiviano->traviesa_chasis ?? null,
        'Piso habitáculo' => $inspec->inspeccionRevisionVisualPuntoLiviano->piso_habitaculo ?? null,
        'Panorámico delantero' => $inspec->inspeccionRevisionVisualPuntoLiviano->panoramico_delantero ?? null,
        'Panorámico trasero' => $inspec->inspeccionRevisionVisualPuntoLiviano->panoramico_trasero ?? null,
        'Vidrio puerta delantera Derecha' => $inspec->inspeccionRevisionVisualPuntoLiviano->vidrio_puerta_delantera_der ?? null,
        'Vidrio puerta delantera Izquierda' => $inspec->inspeccionRevisionVisualPuntoLiviano->vidrio_puerta_delantera_izq ?? null,
        'Vidrio puerta trasera Derecha' => $inspec->inspeccionRevisionVisualPuntoLiviano->vidrio_puerta_trasera_der ?? null,
        'Vidrio puerta trasera Izquierda' => $inspec->inspeccionRevisionVisualPuntoLiviano->vidrio_puerta_trasera_izq ?? null,
        'Capota' => $inspec->inspeccionRevisionVisualPuntoLiviano->capota ?? null,
        'Antena capota' => $inspec->inspeccionRevisionVisualPuntoLiviano->antena_capota ?? null,
        'Guardafango delantero Derecho' => $inspec->inspeccionRevisionVisualPuntoLiviano->guardafango_der ?? null,
        'Guardafango delantero Izquierdo' => $inspec->inspeccionRevisionVisualPuntoLiviano->guardafango_izq ?? null,
        'Puerta delantera Derecha' => $inspec->inspeccionRevisionVisualPuntoLiviano->puerta_delantera_der ?? null,
        'Puerta delantera Izquierda' => $inspec->inspeccionRevisionVisualPuntoLiviano->puerta_delantera_izq ?? null,
        'Puerta trasera Derecha' => $inspec->inspeccionRevisionVisualPuntoLiviano->puerta_trasera_der ?? null,
        'Puerta trasera Izquierda' => $inspec->inspeccionRevisionVisualPuntoLiviano->puerta_trasera_izq ?? null,
        'Costado Derecho' => $inspec->inspeccionRevisionVisualPuntoLiviano->costado_der ?? null,
        'Costado Izquierdo' => $inspec->inspeccionRevisionVisualPuntoLiviano->costado_izq ?? null,
        'Paral puerta Derecha' => $inspec->inspeccionRevisionVisualPuntoLiviano->paral_puerta_der ?? null,
        'Paral puerta Izquierda' => $inspec->inspeccionRevisionVisualPuntoLiviano->paral_puerta_izq ?? null,
        'Estribo Derecho' => $inspec->inspeccionRevisionVisualPuntoLiviano->estribo_der ?? null,
        'Estribo Izquierdo' => $inspec->inspeccionRevisionVisualPuntoLiviano->estribo_izq ?? null,
        'Paral central Derecho' => $inspec->inspeccionRevisionVisualPuntoLiviano->paral_central_der ?? null,
        'Paral central Izquierdo' => $inspec->inspeccionRevisionVisualPuntoLiviano->paral_central_izq ?? null,
    ];

    // Filtramos solo los que tengan valor
    $puntos = array_filter($puntos, fn($v) => $v !== null && $v !== '');

    // Partimos el array en dos mitades iguales
    $mitad = ceil(count($puntos) / 2);
    $col1 = array_slice($puntos, 0, $mitad, true);
    $col2 = array_slice($puntos, $mitad, null, true);
@endphp

<div class="section" style="margin-top: 20px;">
    <h3>Revisión Visual</h3>
    <table style="width: 100%; margin-top: 15px; border-collapse: collapse;">
        <tr>
            <td style="width: 50%; padding: 10px; vertical-align: top;">
                <ul>
                    @foreach($col1 as $label => $valor)
                        <li><strong>{{ $label }}:</strong> {{ $valor }}</li>
                    @endforeach
                </ul>
            </td>
            <td style="width: 50%; padding: 10px; vertical-align: top;">
                <ul>
                    @foreach($col2 as $label => $valor)
                        <li><strong>{{ $label }}:</strong> {{ $valor }}</li>
                    @endforeach
                </ul>
            </td>
        </tr>
    </table>
</div>





            <div style="page-break-before: always;"></div>

            <div class="section" style="margin-top: 20px;">
                <h3>Revisión Visual</h3>
                <table  cellspacing="0" cellpadding="6" style="width: 100%; border-collapse: collapse; text-align: center; font-family: DejaVu Sans, sans-serif; font-size: 11px;">

                    <!-- FILA 1 -->
                    <tr>
                        <!-- Alineación -->
                        <td style="width: 50%; vertical-align: top;">
                            <div style="height: 180px;">
                                <img src="{{ public_path('imagenes/alineacion.png') }}" alt="Alineación" style="max-height: 100px; margin-bottom: 5px;">
                                <div>Desviación del vehículo por km 
                                            @switch($inspec->inspeccionRevisionVisual->desviacion_km)
                                                @case('Bueno 90% - 100%')
                                                    <span class="indicador bueno">✔️ Bueno</span>
                                                    @break

                                                @case('Aceptable 60% - 89%')
                                                    <span class="indicador aceptable">⚠️ Aceptable</span>
                                                    @break

                                                @case('Malo 0% - 59%')
                                                    <span class="indicador malo">X Malo</span>
                                                    @break
                                            @endswitch                                        
                                    </div>
                                <div style="margin-top: 10px; text-align: center; font-weight: bold; background: #b00; color: #fff; padding: 3px;">Alineación</div>
                            </div>
                        </td>

                        <!-- Pintura -->
                        <td style="width: 50%; vertical-align: top;">
                            <div style="height: 180px;">
                                <div style="height: 120px; border: 1px solid #999; margin-bottom: 5px;">
                                    @switch($inspec->inspeccionRevisionVisual->pintura)
                                                @case('Bueno 90% - 100%')
                                                    <span style="margin-top: 40px;display: block;width: 50px;margin-left: 40%;" class="indicador bueno">✔️ Bueno</span>
                                                    @break

                                                @case('Aceptable 60% - 89%')
                                                    <span style="margin-top: 40px;display: block;width: 50px;margin-left: 40%;" class="indicador aceptable">⚠️ Aceptable</span>
                                                    @break

                                                @case('Malo 0% - 59%')
                                                    <span style="margin-top: 40px;display: block;width: 50px;margin-left: 40%;" class="indicador malo">X Malo</span>
                                                    @break
                                            @endswitch  
                                </div>
                                <div style="margin-top: 10px; text-align: center; font-weight: bold; background: #b00; color: #fff; padding: 3px;">Pintura</div>
                            </div>
                        </td>
                    </tr>

                    <!-- FILA 2 -->
                    <tr>
                        <!-- Ruedas -->
                        <td style="width: 50%; vertical-align: top;">
                            <div style="height:240px;">
                                <table style="width: 100%; text-align: center; border: none;height: 180px;">
                                    <tr>
                                        <td>
                                            <div style="text-align: left;">TRASERAS<br>
                                            @switch($inspec->inspeccionRevisionVisual->ruedas_traseras)
                                                @case('Bueno 90% - 100%')
                                                    <span class="indicador bueno">✔️ Bueno</span>
                                                    @break

                                                @case('Aceptable 60% - 89%')
                                                    <span class="indicador aceptable">⚠️ Aceptable</span>
                                                    @break

                                                @case('Malo 0% - 59%')
                                                    <span class="indicador malo">X Malo</span>
                                                    @break
                                            @endswitch   
                                            </div>
                                        </td>
                                        <td>
                                             <img src="{{ public_path('imagenes/rueda_rasera.png') }}" alt="Rueda Trasera" style="max-height: 60px;">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <img src="{{ public_path('imagenes/rueda_delantera.png') }}" alt="Rueda Delantera" style="max-height: 60px;">
                                        </td>
                                        <td>
                                             <div style="text-align: left; margin-top: 10px;">DELANTERAS<br>
                                              @switch($inspec->inspeccionRevisionVisual->ruedas_delanteras)
                                                @case('Bueno 90% - 100%')
                                                    <span class="indicador bueno">✔️ Bueno</span>
                                                    @break

                                                @case('Aceptable 60% - 89%')
                                                    <span class="indicador aceptable">⚠️ Aceptable</span>
                                                    @break

                                                @case('Malo 0% - 59%')
                                                    <span class="indicador malo">X Malo</span>
                                                    @break
                                            @endswitch
                                            </div>
                                        </td>
                                    </tr>
                                </table> 
                                
                                <div style="margin-top: 5px; text-align: center; font-weight: bold; background: #b00; color: #fff; padding: 3px;">Ruedas</div>
                            </div>
                        </td>

                        <!-- Vida útil de las llantas -->
                        <td style="width: 50%; vertical-align: top;">
                            <div style="height: 240px;">
                                <table style="width: 100%; text-align: center; border: none;">
                                    <tr>
                                        <td>DEL IZQ<br><img src="{{ public_path('imagenes/llanta.png') }}" style="max-height: 50px;"><br>
                                        @switch($inspec->inspeccionRevisionVisual->llanta_del_izq)
                                                @case('Bueno 90% - 100%')
                                                    <span class="indicador bueno">✔️ Bueno</span>
                                                    @break

                                                @case('Aceptable 60% - 89%')
                                                    <span class="indicador aceptable">⚠️ Aceptable</span>
                                                    @break

                                                @case('Malo 0% - 59%')
                                                    <span class="indicador malo">X Malo</span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td>DEL DER<br><img src="{{ public_path('imagenes/llanta.png') }}" style="max-height: 50px;"><br>
                                        @switch($inspec->inspeccionRevisionVisual->llanta_del_der)
                                                @case('Bueno 90% - 100%')
                                                    <span class="indicador bueno">✔️ Bueno</span>
                                                    @break

                                                @case('Aceptable 60% - 89%')
                                                    <span class="indicador aceptable">⚠️ Aceptable</span>
                                                    @break

                                                @case('Malo 0% - 59%')
                                                    <span class="indicador malo">X Malo</span>
                                                    @break
                                            @endswitch
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>TRA IZQ<br><img src="{{ public_path('imagenes/llanta.png') }}" style="max-height: 50px;"><br>
                                        @switch($inspec->inspeccionRevisionVisual->llanta_tras_izq)
                                                @case('Bueno 90% - 100%')
                                                    <span class="indicador bueno">✔️ Bueno</span>
                                                    @break

                                                @case('Aceptable 60% - 89%')
                                                    <span class="indicador aceptable">⚠️ Aceptable</span>
                                                    @break

                                                @case('Malo 0% - 59%')
                                                    <span class="indicador malo">X Malo</span>
                                                    @break
                                            @endswitch<
                                        </td>
                                        <td>TRA DER<br><img src="{{ public_path('imagenes/llanta.png') }}" style="max-height: 50px;"><br>
                                        @switch($inspec->inspeccionRevisionVisual->llanta_tras_der)
                                                @case('Bueno 90% - 100%')
                                                    <span class="indicador bueno">✔️ Bueno</span>
                                                    @break

                                                @case('Aceptable 60% - 89%')
                                                    <span class="indicador aceptable">⚠️ Aceptable</span>
                                                    @break

                                                @case('Malo 0% - 59%')
                                                    <span class="indicador malo">X Malo</span>
                                                    @break
                                            @endswitch
                                        </td>
                                    </tr>
                                </table>
                                <div style="margin-top: 5px; text-align: center; font-weight: bold; background: #b00; color: #fff; padding: 3px;">Vida Útil de las Llantas</div>
                            </div>
                        </td>
                    </tr>

                    <!-- FILA 3 -->
                    <tr>
                        <!-- Freno de Mano -->
                        <td style="width: 50%; vertical-align: top;">
                            <div style="height: 240px;">
                                <img src="{{ public_path('imagenes/freno_mano.png') }}" alt="Freno de Mano" style="max-height: 80px; margin-bottom: 5px;">
                                <br>@switch($inspec->inspeccionRevisionVisual->freno_mano)
                                                @case('Bueno 90% - 100%')
                                                    <span class="indicador bueno">✔️ Bueno</span>
                                                    @break

                                                @case('Aceptable 60% - 89%')
                                                    <span class="indicador aceptable">⚠️ Aceptable</span>
                                                    @break

                                                @case('Malo 0% - 59%')
                                                    <span class="indicador malo">X Malo</span>
                                                    @break
                                            @endswitch <br><br><br>
                                <div style="margin-top: 10px; text-align: center; font-weight: bold; background: #b00; color: #fff; padding: 3px;">Freno de Mano %</div>
                            </div>
                        </td>

                        <!-- Eficacia de la suspensión -->
                        <td style="width: 50%; vertical-align: top;">
                            <div style="height: 240px;">
                                <img src="{{ public_path('imagenes/suspension.png') }}" alt="Suspensión" style="max-height: 90px; margin-bottom: 5px;"><br>
                                <table style="width: 100%; text-align: center; border: none;">
                                    <tr>
                                        <td>
                                            TRASERA<br>
                                            @switch($inspec->inspeccionRevisionVisual->suspension_tras)
                                                @case('Bueno 90% - 100%')
                                                    <span class="indicador bueno">✔️ Bueno</span>
                                                    @break

                                                @case('Aceptable 60% - 89%')
                                                    <span class="indicador aceptable">⚠️ Aceptable</span>
                                                    @break

                                                @case('Malo 0% - 59%')
                                                    <span class="indicador malo">❌ Malo</span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td>
                                            DELANTERA<br>
                                            @switch($inspec->inspeccionRevisionVisual->suspension_delantera)
                                                @case('Bueno 90% - 100%')
                                                    <span class="indicador bueno">✔️ Bueno</span>
                                                    @break

                                                @case('Aceptable 60% - 89%')
                                                    <span class="indicador aceptable">⚠️ Aceptable</span>
                                                    @break

                                                @case('Malo 0% - 59%')
                                                    <span class="indicador malo">❌ Malo</span>
                                                    @break
                                            @endswitch
                                        </td>
                                    </tr>
                                </table>
                                <div style="margin-top: 10px; text-align: center; font-weight: bold; background: #b00; color: #fff; padding: 3px;">Eficacia de la Suspensión</div>
                            </div>
                        </td>
                    </tr>

                </table>

                <table style="width: 100%; margin: 10px 0; font-size: 12px; border-collapse: collapse; text-align: center;">
                    <tr style="background: #606060;">
                        <td style="width: 33%;padding: 10px;">
                            <span style="background: #28a745; color: #fff; padding: 3px 8px; border-radius: 4px;">
                                ✔️ Bueno 90% - 100%
                            </span>
                        </td>
                        <td style="width: 33%;padding: 10px;">
                            <span style="background: #f0ad4e; color: #fff; padding: 3px 8px; border-radius: 4px;">
                                ⚠️ Aceptable 60% - 89%
                            </span>
                        </td>
                        <td style="width: 33%;padding: 10px;">
                            <span style="background: #dc3545; color: #fff; padding: 3px 8px; border-radius: 4px;">
                                X Malo 0% - 59%
                            </span>
                        </td>
                    </tr>
                </table>

            </div>

        <div style="page-break-before: always;"></div>

        <div class="section" style="margin-top: 20px;">
                <h3 style="margin-bottom: 7px;">Revisión Punto a Punto</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="width:50%;">
                            <h3>Mecánica</h3>
                            <table style="width: 100%;" class="table-b-negro">
                                <tr>
                                    <td class="bg-gris" style="width:60%;">Item</td>
                                    <td class="bg-gris" style="width:40%;">Resultado</td>
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Kilometraje</td> 
                                  <td class="blanco">{{$inspec->kilometraje}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Funcionamiento A/A</td> 
                                  <td class="blanco">{{$inspec->inspeccionMecanica->funcionamiento_a_a}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Nivel Aceite Dirección Hidraulica</td> 
                                  <td class="blanco">{{$inspec->inspeccionMecanica->nivel_aceite_direccion_hidraulica}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Nivel Aceite Motor</td> 
                                  <td class="blanco">{{$inspec->inspeccionMecanica->nivel_aceite_motor}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Nivel Agua Limpiavidrios</td> 
                                  <td class="blanco">{{$inspec->inspeccionMecanica->nivel_agua_limpiavidrios}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Nivel Liquido Frenos</td> 
                                  <td class="blanco">{{$inspec->inspeccionMecanica->nivel_liquido_frenos}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Nivel Liquido Embrague</td> 
                                  <td class="blanco">{{$inspec->inspeccionMecanica->nivel_liquido_embrague}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Nivel Refrigerante Motor</td> 
                                  <td class="blanco">{{$inspec->inspeccionMecanica->nivel_refrigerante_motor}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Soportes Caja de Velocidades</td> 
                                  <td class="blanco">{{$inspec->inspeccionMecanica->soportes_caja_velocidades}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Viscosidad Aceite de Motor</td> 
                                  <td class="blanco">{{$inspec->inspeccionMecanica->viscosidad_aceite_motor}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Estado Cables Instalación de Alta</td> 
                                  <td class="blanco">{{$inspec->inspeccionMecanica->estado_cables_instalacion_alta}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Estado Carcasa Caja de Velocidades</td> 
                                  <td class="blanco">{{$inspec->inspeccionMecanica->estado_carcasa_caja_velocidades}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Estado Correas</td> 
                                  <td class="blanco">{{$inspec->inspeccionMecanica->estado_correas}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Estado Externo Batería</td> 
                                  <td class="blanco">{{$inspec->inspeccionMecanica->estado_externo_bateria}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Estado Filtro Aire</td> 
                                  <td class="blanco">{{$inspec->inspeccionMecanica->estado_filtro_aire}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Estado Mangueras Radiador</td> 
                                  <td class="blanco">{{$inspec->inspeccionMecanica->estado_manqgueras_radiador}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Estado Radiador A/A</td> 
                                  <td class="blanco">{{$inspec->inspeccionMecanica->estado_radiador_a_a}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Estado Radiador</td> 
                                  <td class="blanco">{{$inspec->inspeccionMecanica->estado_radiador}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Estado Soporte Motor</td> 
                                  <td class="blanco">{{$inspec->inspeccionMecanica->estado_soporte_motor}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Tensión Correas</td> 
                                  <td class="blanco">{{$inspec->inspeccionMecanica->tension_correas}}</td> 
                                </tr>
                                

                            </table>

                            <h3 style="margin-top: 25px;">Luces</h3>
                            <table style="width: 100%;" class="table-b-negro">
                                <tr>
                                    <td class="bg-gris" style="width:60%;">Item</td>
                                    <td class="bg-gris" style="width:40%;">Resultado</td>
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Direccionales</td> 
                                  <td class="blanco">{{$inspec->inspeccionLuces->direccionales}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Luces Altas</td> 
                                  <td class="blanco">{{$inspec->inspeccionLuces->luces_altas}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Luces Bajas</td> 
                                  <td class="blanco">{{$inspec->inspeccionLuces->luces_bajas}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Luces Exploradoras</td> 
                                  <td class="blanco">{{$inspec->inspeccionLuces->luces_exploradoras}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Luces Frenos</td> 
                                  <td class="blanco">{{$inspec->inspeccionLuces->luces_frenos}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Luces Medias</td> 
                                  <td class="blanco">{{$inspec->inspeccionLuces->lueces_medias}}</td> 
                                </tr>  
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Luces Parqueo</td> 
                                  <td class="blanco">{{$inspec->inspeccionLuces->luces_parqueo}}</td> 
                                </tr>  
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Luces Placa</td> 
                                  <td class="blanco">{{$inspec->inspeccionLuces->luces_placa}}</td> 
                                </tr> 
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Luces de Reversa</td> 
                                  <td class="blanco">{{$inspec->inspeccionLuces->luces_reversa}}</td> 
                                </tr>                                
                            </table>

                        </td>
                        <td style="width:50%;">
                            <h3>Tapiceria</h3>
                            <table style="width: 100%;" class="table-b-negro">
                                <tr>
                                    <td class="bg-gris" style="width:60%;">Item</td>
                                    <td class="bg-gris" style="width:40%;">Resultado</td>
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Estado Timon</td> 
                                  <td class="blanco">{{$inspec->inspeccionTapiceria->estado_timon}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Estado Tapizados Puertas</td> 
                                  <td class="blanco">{{$inspec->inspeccionTapiceria->estados_tapizados_puerta}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Estado Tapicería Asientos</td> 
                                  <td class="blanco">{{$inspec->inspeccionTapiceria->estados_tapizado_asientos}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Estado Tapiceria Techo</td> 
                                  <td class="blanco">{{$inspec->inspeccionTapiceria->estado_tapiceria_techo}}</td> 
                                </tr> 
                            </table>

                            <h3 style="margin-top: 25px;">Funcionamiento</h3>
                            <table style="width: 100%;" class="table-b-negro">
                                <tr>
                                    <td class="bg-gris" style="width:60%;">Item</td>
                                    <td class="bg-gris" style="width:40%;">Resultado</td>
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Asientos Delanteros</td> 
                                  <td class="blanco">{{$inspec->inspeccionFuncionamiento->asientos_delantero}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Bocina</td> 
                                  <td class="blanco">{{$inspec->inspeccionFuncionamiento->bocina}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Calefacción</td> 
                                  <td class="blanco">{{$inspec->inspeccionFuncionamiento->calefaccion}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Desempañador</td> 
                                  <td class="blanco">{{$inspec->inspeccionFuncionamiento->desempanador}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Encendedor</td> 
                                  <td class="blanco">{{$inspec->inspeccionFuncionamiento->ecendedor}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Espejos Eléctricos</td> 
                                  <td class="blanco">{{$inspec->inspeccionFuncionamiento->espejos_electricos}}</td> 
                                </tr>  
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Limpriablisas Del</td> 
                                  <td class="blanco">{{$inspec->inspeccionFuncionamiento->limpiabrisas_del}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Limpriablisas Tras.</td> 
                                  <td class="blanco">{{$inspec->inspeccionFuncionamiento->limpiabrisas_tra}}</td> 
                                </tr>    
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Luz Interior</td> 
                                  <td class="blanco">{{$inspec->inspeccionFuncionamiento->luz_interior}}</td> 
                                </tr> 
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Radio</td> 
                                  <td class="blanco">{{$inspec->inspeccionFuncionamiento->radio}}</td> 
                                </tr> 
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Encendido Arranque</td> 
                                  <td class="blanco">{{$inspec->inspeccionFuncionamiento->encendido_arranque}}</td> 
                                </tr> 
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Tacómetro</td> 
                                  <td class="blanco">{{$inspec->inspeccionFuncionamiento->tacometro}}</td> 
                                </tr> 
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Techo Corredizo</td> 
                                  <td class="blanco">{{$inspec->inspeccionFuncionamiento->techo_corredizo}}</td> 
                                </tr> 
                                <tr>
                                  <td style="width:60%;" class="bg-gris1">Velocímetro</td> 
                                  <td class="blanco">{{$inspec->inspeccionFuncionamiento->velocimetro}}</td> 
                                </tr> 
                            </table>



                        </td>
                    </tr>
                </table>
            </div>

            <div style="page-break-before: always;"></div>

            <div class="section" style="margin-top: 20px;">
                <h3 style="margin-bottom: 15px;">Revisión Punto a Punto</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="width:50%;">
                            <h3>Parte Baja</h3>
                            <table style="width: 100%;" class="table-b-negro">
                                <tr>
                                    <td class="bg-gris" style="width:50%;">Item</td>
                                    <td class="bg-gris" style="width:50%;">Resultado</td>
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Carter</td> 
                                  <td class="blanco">{{$inspec->inspeccionParteBaja->carter}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Cauchos Suspensión</td> 
                                  <td class="blanco">{{$inspec->inspeccionParteBaja->cauchos_suspension}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Guardapolvos Caja Dirección</td> 
                                  <td class="blanco">{{$inspec->inspeccionParteBaja->guardapolvos_caja_direccion}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Guardapolvos Ejes</td> 
                                  <td class="blanco">{{$inspec->inspeccionParteBaja->guardapolvos_eje}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Protectores Inferiores</td> 
                                  <td class="blanco">{{$inspec->inspeccionParteBaja->protectores_inferiores}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Estado Catalizador</td> 
                                  <td class="blanco">{{$inspec->inspeccionParteBaja->estado_catalizador}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Estado Silenciador Escape</td> 
                                  <td class="blanco">{{$inspec->inspeccionParteBaja->estado_silenciador_escape}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Estado Tijeras</td> 
                                  <td class="blanco">{{$inspec->inspeccionParteBaja->estado_tijeras}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Estado Tuberias Frenos</td> 
                                  <td class="blanco">{{$inspec->inspeccionParteBaja->estado_tuberias_frenos}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Estado Tubo Exhosto</td> 
                                  <td class="blanco">{{$inspec->inspeccionParteBaja->estado_tubo_exhosto}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Fuga Aceite Caja de Velocidades</td> 
                                  <td class="blanco">{{$inspec->inspeccionParteBaja->fuga_aceite_caja_velocidades}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Fuga Dirección Hidráulica</td> 
                                  <td class="blanco">{{$inspec->inspeccionParteBaja->fuga_direccion_hidraulica}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Fuga Aceite Motor</td> 
                                  <td class="blanco">{{$inspec->inspeccionParteBaja->fuga_aceite_motor}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Fuga Amortiguaderes</td> 
                                  <td class="blanco">{{$inspec->inspeccionParteBaja->fuga_amortiguadores}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Fuga Liquido Embrague</td> 
                                  <td class="blanco">{{$inspec->inspeccionParteBaja->fuga_liquido_embrague}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Fuga Liquido de Frenos</td> 
                                  <td class="blanco">{{$inspec->inspeccionParteBaja->fuga_liquido_frenos}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Fuga Combustible Tanque</td> 
                                  <td class="blanco">{{$inspec->inspeccionParteBaja->fuga_combustible_tanque}}</td> 
                                </tr>
                                
                                

                            </table>


                        </td>
                        <td style="width:50%;">
                            <h3>Exterior</h3>
                            <table style="width: 100%;" class="table-b-negro">
                                <tr>
                                    <td class="bg-gris" style="width:50%;">Item</td>
                                    <td class="bg-gris" style="width:50%;">Resultado</td>
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Vidrios</td> 
                                  <td class="blanco">{{$inspec->inspeccionExterior->vidrios}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Tapicería y Accesorios</td> 
                                  <td class="blanco">{{$inspec->inspeccionExterior->tapiceria_accesorios}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Fugas Fluidos</td> 
                                  <td class="blanco">{{$inspec->inspeccionExterior->fugas_fluidos}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Ajuste Cierre Capó</td> 
                                  <td class="blanco">{{$inspec->inspeccionExterior->ajuste_cierre_capo}}</td> 
                                </tr> 
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Ajuste Cierre Puertas Delantera Izq</td> 
                                  <td class="blanco">{{$inspec->inspeccionExterior->ajuste_cierre_puestas_delantera_izq}}</td> 
                                </tr> 
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Ajuste Cierre Puertas Delantera Der</td> 
                                  <td class="blanco">{{$inspec->inspeccionExterior->ajuste_cierre_puestas_delantera_der}}</td> 
                                </tr> 
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Ajuste Cierre Puertas Trasera Izq</td> 
                                  <td class="blanco">{{$inspec->inspeccionExterior->ajuste_cierre_puertas_trasera_izq}}</td> 
                                </tr> 
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Ajuste Cierre Puertas Trasera Der</td> 
                                  <td class="blanco">{{$inspec->inspeccionExterior->ajuste_cierre_puertas_trasera_der}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Ajuste Cierre Tapa Baúl/compuerta</td> 
                                  <td class="blanco">{{$inspec->inspeccionExterior->ajuste_cierre_tapa_baul_compuerta}}</td> 
                                </tr> 
                            </table>

                            <h3 style="margin-top: 25px;">Indicadores</h3>
                            <table style="width: 100%;" class="table-b-negro">
                                <tr>
                                    <td class="bg-gris" style="width:50%;">Item</td>
                                    <td class="bg-gris" style="width:50%;">Resultado</td>
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Testigo ABS</td> 
                                  <td class="blanco">{{$inspec->inspeccionIndicadores->testigo_abs}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Testigo Aceite</td> 
                                  <td class="blanco">{{$inspec->inspeccionIndicadores->testigo_aceite}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Testigo Check Engine</td> 
                                  <td class="blanco">{{$inspec->inspeccionIndicadores->testigo_check_engine}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Testigo Frenos</td> 
                                  <td class="blanco">{{$inspec->inspeccionIndicadores->testigo_frenos}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Testigo Combustible</td> 
                                  <td class="blanco">{{$inspec->inspeccionIndicadores->testigo_combustible}}</td> 
                                </tr>
                                
                            </table>



                        </td>
                    </tr>
                </table>
            </div>

            <div style="page-break-before: always;"></div>

            <div class="section" style="margin-top: 20px;">
                <h3 style="margin-top: 15px;">Accesorios</h3>
                <table style="width: 100%;" class="table-b-negro">
                    <tr>
                        <td class="bg-gris">Descripción</td>
                        <td class="bg-gris">Marca - Refeencia</td>
                        <td class="bg-gris">Cant</td>
                        <td class="bg-gris">Valor</td>
                    </tr>
                    @foreach($inspec->inspeccionAccesorios as $accesorio)
                    <tr>
                        <td>{{$accesorio->decripcion}}</td>
                        <td>{{$accesorio->marca_ref}}</td>
                        <td>{{$accesorio->cantidad}}</td>
                        <td>{{$accesorio->valor}}</td>
                    </tr>
                    @endforeach
                </table>
            </div>

            

        <!-- Fuerza nueva página -->
        <div style="page-break-before: always; font-family: Arial, sans-serif; font-size: 12px;">

            <!-- Encabezado -->
            <table width="100%" style="margin-bottom: 10px;margin-top:20px;">
                <tr>
                    <td style="color:#c00; font-weight:bold;width: 60%;">Ciudad: {{$inspec->ciudad}}</td>
                    <td style="color:#c00;width: 40%;"></td>
                </tr>
                <tr>
                    <td style="color:#c00; font-weight:bold;width: 70%;" >Fecha / Hora Expedición:{{ \Carbon\Carbon::now()->format('d/m/y H:i') }}</td>
                    <td style="color:#c00;width: 30%;">Rad:{{$inspec->id}}</td>
                </tr>
            </table>

            <!-- Información Solicitante y Automotor -->
            <table width="100%" cellpadding="4" cellspacing="0" style="border-collapse: collapse; margin-bottom: 15px;">
                <tr>
                    <td style="width: 50%;">
                        <table width="100%" style="border-collapse: collapse;border: solid 1px #000;">
                            <tr>
                                <td colspan="2" class="bg-gris">Información Solicitante</td>
                            </tr>                            
                            <tr>
                                <td style="width: 50%;border: solid 1px #000;">Nombres y Apellidos</td>
                                <td style="width: 50%;border: solid 1px #000;">{{$ingreso->solicitante}}</td>
                            </tr>
                            <tr>
                                <td style="width: 50%;border: solid 1px #000;">Número de Documento</td>
                                <td style="width: 50%;border: solid 1px #000;">{{$ingreso->documento_solicitante}}</td>
                            </tr>
                            <tr>
                                <td style="width: 50%;border: solid 1px #000;">Dirección de Residencia</td>
                                <td style="width: 50%;border: solid 1px #000;">{{$ingreso->direccion_solicitante}}</td>
                            </tr>
                            <tr>
                                <td style="width: 50%;border: solid 1px #000;">Teléfono</td>
                                <td style="width: 50%;border: solid 1px #000;">{{$ingreso->telefono_solicitante}}</td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 50%;">
                        <table width="100%" style="border-collapse: collapse;border: solid 1px #000;">
                            <tr>
                                <td colspan="2" class="bg-gris">Información Automotor</td>
                            </tr>
                            <tr>
                                <td style="width: 50%;border: solid 1px #000;">Clase</td>
                                <td style="width: 50%;border: solid 1px #000;">{{$ingreso->clase}}</td>
                            </tr>
                            <tr>
                                <td style="width: 50%;border: solid 1px #000;">Marca</td>
                                <td style="width: 50%;border: solid 1px #000;">{{$ingreso->marca}}</td>
                            </tr>
                            <tr>
                                <td style="width: 50%;border: solid 1px #000;">Placas</td>
                                <td style="width: 50%;border: solid 1px #000;">{{$ingreso->placa}}</td>
                            </tr>
                            <tr>
                                <td style="width: 50%;border: solid 1px #000;">Modelo</td>
                                <td style="width: 50%;border: solid 1px #000;">{{$ingreso->modelo}}</td>
                            </tr>
                            <tr>
                                <td style="width: 50%;border: solid 1px #000;">Tipo</td>
                                <td style="width: 50%;border: solid 1px #000;">{{$ingreso->tipo_propiedad}}</td>
                            </tr>
                        </table>
                    </td>
                </tr> 
            </table>

            <!-- Motor, Serial, Chasis -->
            <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse: collapse; margin-bottom: 15px;">
                <tr>
                    <td style="background:#c00; color:#fff; width:15%;">Motor</td>
                    <td style="border:1px solid #c00; height:40px;">{{$ingreso->numero_motor}}</td>
                </tr>
                <tr>
                    <td style="background:#c00; color:#fff;">Serial</td>
                    <td style="border:1px solid #c00; height:40px;">{{$ingreso->numero_serie}}</td>
                </tr>
                <tr>
                    <td style="background:#c00; color:#fff;">Chasis</td>
                    <td style="border:1px solid #c00; height:40px;">{{$ingreso->numero_chasis}}</td>
                </tr>
            </table>

            <!-- Campos de texto -->
            <p>Los sistemas de Identificación que posee en la actualidad se dictaminan:/p>
            <p>Observaciones:{{$inspec->observaciones}}</p>
            <p>Se Expide Para: {{$inspec->expide_para}}</p>                      

            <!-- Nota -->
            <p style="font-size:10px; margin-top:20px; text-align:justify;">
                <b>Nota:</b> Cualquier borrón o enmendadura no aclarada anula este documento y la responsabilidad se asume únicamente hasta la fecha de su expedición.
                <b>EL DOCUMENTO NO TENDRÁ VALOR SI FALTA ALGUNA DE SUS PÁGINAS. YA QUE ES INTEGRAL</b>
            </p>

        </div>

        <div style="page-break-before: always;"></div>

            <div class="section" style="margin-top: 20px;">
                <h3 style="margin-bottom: 15px;">Ficha Técnica</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="width:50%;">                            
                            <table style="width: 100%;" class="table-b-negro">
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Marca Vehículo</td> 
                                  <td class="blanco">{{$ingreso->marca}}<</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Clase Vehículo</td> 
                                  <td class="blanco">{{$ingreso->clase}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Linea Vehículo</td> 
                                  <td class="blanco">{{$ingreso->linea}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Cilindraje</td> 
                                  <td class="blanco">{{$ingreso->cilindraje}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Color</td> 
                                  <td class="blanco">{{$ingreso->color}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Año Modelo</td> 
                                  <td class="blanco">{{$ingreso->modelo}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Combustible</td> 
                                  <td class="blanco">{{$inspec->combustible}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Tipo Carroceria</td> 
                                  <td class="blanco">{{$ingreso->tipo_carroceria}}</td> 
                                </tr>
                            </table>


                        </td>
                        <td style="width:50%;">
                            
                           <table style="width: 100%;" class="table-b-negro">
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Cantidad de ejes</td> 
                                  <td class="blanco">{{$ingreso->cantidad_ejes}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Número de Chasis</td> 
                                  <td class="blanco">{{$ingreso->numero_chasis}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Numero de Serie</td> 
                                  <td class="blanco">{{$ingreso->numero_serie}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Numero de VIN</td> 
                                  <td class="blanco">{{$ingreso->numeroVin}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Numero de Motor</td> 
                                  <td class="blanco">{{$ingreso->numero_motor}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Capacidad de Carga</td> 
                                  <td class="blanco">{{$ingreso->capacidad_carga}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Peso Bruto Vehicular</td> 
                                  <td class="blanco">{{$ingreso->peso_bruto}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Importado o Nacional</td> 
                                  <td class="blanco">{{$ingreso->nacionalidad}}</td> 
                                </tr>
                            </table>


                        </td>
                    </tr>
                </table>
                <h3 style="margin-bottom: 15px;">Documentos del Vehículo</h3>
                <h3 style="margin-top: 15px;">Licencia de Tránsito</h3>
                <table style="width: 100%;" class="table-b-negro">
                    <tr>
                        <td class="bg-gris">Ciudad Registro</td>
                        <td class="bg-gris">Licencia de Tránsito</td>
                        <td class="bg-gris">Fecha Expedición</td>
                        <td class="bg-gris">Organismo de Tránsito</td>
                    </tr>                   
                    <tr>
                        <td>{{$ingreso->ciudad_registro}}</td>
                        <td>{{$ingreso->no_licencia}}</td>
                        <td>{{$ingreso->fecha_expedicion_licencia}}</td>
                        <td>{{$ingreso->organismo_transito}}</td>
                    </tr>                    
                </table>

                <h3 style="margin-top: 15px;">Seguro Obligatorio de Accidentes de Tránsito</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="width:50%;">                            
                            <table style="width: 100%;" class="table-b-negro">
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Poliza Soat</td> 
                                  <td class="blanco">{{$ingreso->soat}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Fecha de Expedición</td> 
                                  <td class="blanco">{{$ingreso->fecha_expedicion_soat}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Fecha inicio Vigencia</td> 
                                  <td class="blanco">{{$ingreso->fecha_inicio_vigencia_soat}}</td> 
                                </tr>                                
                            </table>
                        </td>
                        <td style="width:50%;">                            
                           <table style="width: 100%;" class="table-b-negro">
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Fecha de Vencimiento</td> 
                                  <td class="blanco">{{$ingreso->fecha_vencimiento_soat}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Entidad que expide</td> 
                                  <td class="blanco">{{$ingreso->entidad_expide_soat}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Estado Soat</td> 
                                  <td class="blanco">{{$ingreso->estado_soat}}</td> 
                                </tr>                                
                            </table>
                        </td>
                    </tr>
                </table>

                <h3 style="margin-top: 15px;">Revisión Tecno Mecánica</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="width:50%;">                            
                            <table style="width: 100%;" class="table-b-negro">                               
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Fecha de Expedición</td> 
                                  <td class="blanco">{{ \Carbon\Carbon::parse($ingreso->fecha_vencimiento_rtm)->subYear()->format('Y-m-d') }}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Fecha Vencimiento</td> 
                                  <td class="blanco">{{$ingreso->fecha_vencimiento_rtm}}</td> 
                                </tr>                                
                            </table>
                        </td>
                        <td style="width:50%;">                            
                           <table style="width: 100%;" class="table-b-negro">
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Centro de Revisión</td> 
                                  <td class="blanco">{{$ingreso->centro_revision_rtm}}</td> 
                                </tr>                                
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Estado RTM</td> 
                                  <td class="blanco">{{$ingreso->estado_rtm}}</td> 
                                </tr>                                
                            </table>
                        </td>
                    </tr>
                </table>

                <h3 style="margin-top: 15px;">Estado del Vehiculo ante el Runt</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="width:50%;">                            
                            <table style="width: 100%;" class="table-b-negro">                               
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Fecha Matricula Inicial</td> 
                                  <td class="blanco">{{$ingreso->fecha_inicial_matricula}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Estado de Matricula</td> 
                                  <td class="blanco">{{$ingreso->estado_matricula}}</td> 
                                </tr>   
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Traslados de Matricula</td> 
                                  <td class="blanco">{{$ingreso->traslados_matricula}}</td> 
                                </tr> 
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Tipo de Servicio</td> 
                                  <td class="blanco">{{$ingreso->tipo_servicio_vehiculo}}</td> 
                                </tr> 
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Cambios Tipo de Servicio</td> 
                                  <td class="blanco">{{$ingreso->cambios_tipo_servicio}}</td> 
                                </tr> 
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Fecha Ultimo Cambio de tipo</td> 
                                  <td class="blanco">{{$ingreso->fecha_ult_cambio_servicio}}</td> 
                                </tr>                              
                            </table>
                        </td>
                        <td style="width:50%;">                            
                           <table style="width: 100%;" class="table-b-negro">
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Cambio de Color Historica</td> 
                                  <td class="blanco">{{$ingreso->cambio_color_historica}}</td> 
                                </tr>                                
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Fecha de ultimo Cambio Color</td> 
                                  <td class="blanco">{{$ingreso->fecha_ult_cambio_color}}</td> 
                                </tr> 
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Color al Cual Fue Modificado</td> 
                                  <td class="blanco">{{$ingreso->color_cambiado}}</td> 
                                </tr> 
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Cambios de Blindaje</td> 
                                  <td class="blanco">{{$ingreso->cambios_blindaje}}</td> 
                                </tr> 
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Fecha de Blindaje/desblindaje</td> 
                                  <td class="blanco">{{$ingreso->fecha_cambio_blindaje}}</td> 
                                </tr> 
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Repotenciado</td> 
                                  <td class="blanco">{{$ingreso->repotenciado}}</td> 
                                </tr>                                
                            </table>
                        </td>
                    </tr>
                </table>

            </div>

        <div style="page-break-before: always;"></div>

        <div class="section" style="margin-top: 20px;">
                <h3 style="margin-bottom: 15px;">Novedades Vehículo</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="width:50%;">                            
                            <table style="width: 100%;" class="table-b-negro">
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Tine Gravamenes</td> 
                                  <td class="blanco">{{$ingreso->tiene_gravamedes}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Tiene Prenda</td> 
                                  <td class="blanco">{{$ingreso->tiene_prenda}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Nombre Propietario</td> 
                                  <td class="blanco">{{$ingreso->propietario}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Regrabado Numero Motor</td> 
                                  <td class="blanco">{{$ingreso->regrabado_no_motor}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Regrabado Numero Chasis</td> 
                                  <td class="blanco">{{$ingreso->regrabado_no_chasis}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Regrabado Numero Serie</td> 
                                  <td class="blanco">{{$ingreso->regrabado_no_serie}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Regrabado Numero VIN</td> 
                                  <td class="blanco">{{$ingreso->regrabado_no_vin}}</td> 
                                </tr>                               
                            </table>


                        </td>
                        <td style="width:50%;">
                            
                           <table style="width: 100%;" class="table-b-negro">
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Limitación a la Propiedad</td> 
                                  <td class="blanco">{{$ingreso->limitacion_propiedad}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Numero Documento Proceso</td> 
                                  <td class="blanco">{{$ingreso->numero_doc_proceso}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Entidad Jurídica</td> 
                                  <td class="blanco">{{$ingreso->entidad_juridica}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Tipo Documento Demandante</td> 
                                  <td class="blanco">{{$ingreso->tipo_doc_demandante}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Numero Identificación Demandante</td> 
                                  <td class="blanco">{{$ingreso->no_identificacion_demandante}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Fecha Expedición</td> 
                                  <td class="blanco">{{$ingreso->fecha_expedicion_novedad}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Fecha Radicación</td> 
                                  <td class="blanco">{{$ingreso->fecha_radicacion}}</td> 
                                </tr>                               
                            </table>


                        </td>
                    </tr>
                </table>

                <h3 style="margin-top: 15px;">Historico Propietarios</h3> 
                @if($ingreso->historicoPropietarios) 
                @foreach($ingreso->historicoPropietarios as $propietario)
                <table style="width: 100%;">
                    <tr>
                        <td style="width:50%;">                            
                            <table style="width: 100%;" class="table-b-negro">
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Nombres/Empresa</td> 
                                  <td class="blanco">{{$propietario->nombre_empresa}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Tipo de Identificacion</td> 
                                  <td class="blanco">{{$propietario->tipo_identificacion}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Fecha inicio de Propiedad</td> 
                                  <td class="blanco">{{$propietario->fecha_inicio}}</td> 
                                </tr>                                
                            </table>
                        </td>
                        <td style="width:50%;">                            
                           <table style="width: 100%;" class="table-b-negro">
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Tipo de Propietario</td> 
                                  <td class="blanco">{{$propietario->tipo_propietario}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Numero de Identificación</td> 
                                  <td class="blanco">{{$propietario->numero_identificacion}}</td> 
                                </tr>
                                <tr>
                                  <td style="width:50%;" class="bg-gris1">Estado</td> 
                                  <td class="blanco">{{$propietario->estado}}</td> 
                                </tr>                                
                            </table>
                        </td>
                    </tr>
                </table> 
                @endforeach    
                @endif   
                      

                <!-- Caja grande -->
                <div class="caja">
                  <!-- Contenedor con altura fija -->
                  <div style="height:174px; text-align:center; vertical-align:middle;">
                      @php
                          $matriculaImage = $ingreso->images->where('categoria', 'matricula')->first();
                      @endphp

                      @if($matriculaImage)
                          <img src="{{ public_path($matriculaImage->path) }}" 
                              alt="Matrícula" 
                              style="max-height:174px; max-width:100%;">
                      @else
                          <span>Sin matrícula</span>
                      @endif
                  </div>

                  <!-- footer rojo -->
                  <div class="footer-caja">Licencia de Transito</div>
              </div>


                <!-- Información inferior -->
                <table class="tabla-bottom">
                    <tr>
                        <td width="50%">
                        @php
                            $imagefirma_inspector = $ingreso->images->where('categoria', 'firma_inspector')->first();
                        @endphp

                        @if($imagefirma_inspector)
                            <img src="{{ public_path($imagefirma_inspector->path) }}" 
                                alt="Matrícula" 
                                style="height:180px; object-fit:cover;">
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
                                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                            @endphp
                            <div style="width:100%; height:190px; 
                                        background-image: url('{{ $imaged->path }}');
                                        background-size: cover;
                                        background-position: center center;">
                            </div>
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
                                        background-image: url('{{ $imagei->path}}');
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

</body>
</html>
