<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">

    <style>
        @page {
            margin: 20px;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #111;
            font-size: 12px;
        }

        .logo {
            text-align: center;
            margin-bottom: 8px;
        }

        .logo img {
            width: 260px;
        }

        .title {
            text-align: center;
            font-weight: 700;
            font-size: 23px;
            letter-spacing: .4px;
            margin: 0 0 10px;
        }

        p {
            margin: 0 0 8px;
            line-height: 1.15;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        th,
        td {
            border: 1px solid #777;
            padding: 2px 4px;
            font-size: 11px;
            vertical-align: middle;
            line-height: 1.05;
        }

        th {
            font-weight: 700;
            text-align: center;
        }

        .section {
            color: #2a6f92;
            font-size: 24px;
            font-weight: 700;
            margin: 14px 0 6px;
        }

        .mt {
            margin-top: 12px;
        }

        .firma-container {
            margin-top: 35px;
            text-align: left;
        }

        .firma-img {
            max-height: 120px;
            margin-bottom: 5px;
        }

        .firma-texto {
            font-size: 11px;
            line-height: 1.4;
        }
    </style>
</head>

<body>

@php

    use Carbon\Carbon;

    $logoPath = public_path('logos/AlcadiaSDM_Bogota_Verde.png');

    $factor = number_format((float) ($row['factorSubasta'] ?? 0), 2, '.', '');

    /*
    |--------------------------------------------------------------------------
    | Evaluadores
    |--------------------------------------------------------------------------
    */

    $avaluadores = [

        'Mauricio Garcia' => [
            'nombre'    => 'Gilbert Mauricio García Orozco',
            'profesion' => 'Ingeniero Mecánico',
            'raa'       => 'AVAL-80542954',
            'email'     => 'gmgarcia@movilidadbogota.gov.co',
            'contrato'  => 'Contrato 2025-3366',
            'firma'     => 'mauricio.jpeg'
        ],

        'Jhonny Rodríguez' => [
            'nombre'    => 'Jhonny Steven Rodríguez Hoyos',
            'profesion' => 'Ingeniero Mecánico',
            'raa'       => 'AVAL-1015415553',
            'email'     => 'jsrodriguezh@movilidadbogota.gov.co',
            'contrato'  => 'Contrato 2025-3363',
            'firma'     => 'Jhonny.jpeg'
        ],

        'Ivan Mora' => [
            'nombre'    => 'Iván Darío Mora Mora',
            'profesion' => 'Ingeniero Mecánico',
            'raa'       => 'AVAL-1026270378',
            'email'     => 'imora@movilidadbogota.gov.co',
            'contrato'  => 'Contrato 2025-3365',
            'firma'     => 'Ivan.jpg'
        ],

        'Lenin Ariza' => [
            'nombre'    => 'Lenin Alexander Ariza Triana',
            'profesion' => 'Ingeniero Mecánico',
            'raa'       => 'AVAL-80387508',
            'email'     => 'larizat@movilidadbogota.gov.co',
            'contrato'  => 'Contrato 2026-512',
            'firma'     => 'lenin.jpeg'
        ],

        'German Galvis' => [
            'nombre'    => 'German David Galvis Rivas',
            'profesion' => 'Ingeniero Mecánico',
            'raa'       => 'AVAL-1014251981',
            'email'     => 'ggalvis@movilidadbogota.gov.co',
            'contrato'  => 'Contrato 2026-1020',
            'firma'     => 'german.jpeg'
        ],
    ];

    /*
    |--------------------------------------------------------------------------
    | Evaluador actual
    |--------------------------------------------------------------------------
    */

    $eval = $avaluadores[$row['evaluador']] ?? null;

    /*
    |--------------------------------------------------------------------------
    | Fecha actual
    |--------------------------------------------------------------------------
    */

    Carbon::setLocale('es');

    $fechaActual = Carbon::now();

    $dia = $fechaActual->translatedFormat('d');
    $mes = $fechaActual->translatedFormat('F');
    $anio = $fechaActual->translatedFormat('Y');

@endphp

<div class="logo">
    <img src="{{ $logoPath }}" alt="Logo Secretaría de Movilidad">
</div>

<h3 class="title">
    AJUSTE VALOR BASE KILOGRAMO DE CHATARRA
</h3>

<p>
    Por medio de este documento, se busca actualizar el valor base de subasta del kilogramo (kg.) de chatarra, para el lote de automotores de la subasta 28 que fueron declarados por Chatarra. Según estudios realizados por los Peritos de la Secretaría distrital de Movilidad en cumplimiento de la ley 1730 de 2014.
</p>

<p>
    El perito
    <b>{{ $row['evaluador'] ?? 'N/A' }}</b>

    con Registro Avaluador
    <b>{{ $eval['raa'] ?? 'N/A' }}</b>

    @if(!empty($eval['contrato']))
        identificado con <b>{{ $eval['contrato'] }}</b>
    @endif

    emitió concepto mediante dictamen número del {{ $row['avaluo']['inicial'] }}{{ $row['avaluo']['consecutivo'] }}, en el cual recomienda que el vehículo que se relaciona a continuación debe ser comercializado en calidad de <b>CHATARRA</b> con un peso estimado de <b>{{ $row['pesoChatarraKg'] ?? '-' }}</b> Kg.
</p>

<table>
    <tr>
        <td><b>Placa:</b> {{ $row['placa'] ?? '' }}</td>
        <td><b>Clase:</b> {{ $row['clase'] ?? '' }}</td>
        <td><b>Servicio:</b></td>
    </tr>

    <tr>
        <td><b>Marca:</b> {{ $row['marca'] ?? '' }}</td>
        <td><b>Línea:</b> {{ $row['linea'] ?? '' }}</td>
        <td><b>Modelo:</b> {{ $row['modelo'] ?? '' }}</td>
    </tr>

    <tr>
        <td><b>Carrocería:</b> {{ $row['carroceria'] ?? '' }}</td>
        <td><b>Motor:</b></td>
        <td><b>Cilindraje:</b> {{ $row['cilindraje'] ?? '' }}</td>
    </tr>

    <tr>
        <td><b>Serie:</b> {{ $row['serie'] ?? '' }}</td>
        <td><b>Chasis:</b> {{ $row['chasis'] ?? '' }}</td>
        <td><b>VIN:</b> {{ $row['vin'] ?? '' }}</td>
    </tr>
</table>

<div class="section">
    Estimación Valor Kg Chatarra
</div>

<p>
    Para este cálculo se usó el método de mercado con el fin de determinar el valor del kilogramo (kg.) de chatarra, promediarlo y posteriormente afectarlo por un factor de subasta con el fin de determinar el valor base de venta.
</p>

<table>
    <tr>
        <th>MATERIAL</th>
        <th>{{ $row['nombreChatarreria1'] ?? 'CHATARRERIA 1' }}</th>
        <th>{{ $row['nombreChatarreria2'] ?? 'CHATARRERIA 2' }}</th>
        <th>{{ $row['nombreChatarreria3'] ?? 'CHATARRERIA 3' }}</th>
        <th>{{ $row['nombreChatarreria4'] ?? 'CHATARRERIA 4' }}</th>
        <th>PROMEDIO</th>
    </tr>

    <tr>
        <td>CHATARRA</td>

        <td>{{ number_format((float) ($row['chatarreria1'] ?? 0), 2, '.', '') }}</td>

        <td>{{ number_format((float) ($row['chatarreria2'] ?? 0), 2, '.', '') }}</td>

        <td>{{ number_format((float) ($row['chatarreria3'] ?? 0), 2, '.', '') }}</td>

        <td>{{ number_format((float) ($row['chatarreria4'] ?? 0), 2, '.', '') }}</td>

        <td>{{ number_format((float) ($row['promedio'] ?? 0), 2, '.', '') }}</td>
    </tr>

    <tr>
        <td colspan="4"></td>
        <td><b>FACTOR SUBASTA</b></td>
        <td>{{ $factor }}</td>
    </tr>

    <tr>
        <td colspan="4"></td>
        <td><b>TOTAL</b></td>
        <td>{{ number_format((float) ($row['total'] ?? 0), 2, '.', '') }}</td>
    </tr>
</table>

<div class="section mt">
    Ajuste valor vehículo
</div>

<table>
    <tr>
        <th>PLACA</th>
        <th>PESO CHATARRA Kg.</th>
        <th>VALOR CHATARRA Kg.</th>
        <th>AVALÚO ESTIMADO SUBASTA</th>
    </tr>

    <tr>
        <td>{{ $row['placa'] ?? '' }}</td>

        <td>{{ $row['pesoChatarraKg'] ?? '-' }}</td>

        <td>{{ number_format((float) ($row['total'] ?? 0), 2, '.', '') }}</td>

        <td>
            {{ number_format((float) (($row['total'] ?? 0) * ($row['pesoChatarraKg'] ?? 0)), 2, '.', '') }}
        </td>
    </tr>
</table>

<p style="font-size: 11px;">
    Valor redondeado a miles
</p>

<div class="section">
    Vigencia del avalúo
</div>

<p>
    El valor estimado del presente avalúo está calculado a la fecha de medición y se considera que tiene una vigencia de un (1) año; siempre que las condiciones económicas, políticas, características particulares y otras que puedan afectar el valor comercial del bien se conserven.
</p>

<p>
    Se emite el presente concepto de avalúo a los
    <b>{{ $dia }}</b>
    días del mes de
    <b>{{ ucfirst($mes) }}</b>
    de
    <b>{{ $anio }}</b>.
    <br>
    Cordialmente,
</p>

{{-- ========================================================= --}}
{{-- FIRMA --}}
{{-- ========================================================= --}}

@if($eval)

    @php

        $rutaFirma = public_path('firmas/' . $eval['firma']);

        if (file_exists($rutaFirma)) {

            $typeFirma = pathinfo($rutaFirma, PATHINFO_EXTENSION);

            $dataFirma = file_get_contents($rutaFirma);

            $base64Firma = 'data:image/' . $typeFirma . ';base64,' . base64_encode($dataFirma);

            $firmaExiste = true;

        } else {

            $firmaExiste = false;
        }

    @endphp

    <div class="firma-container">

        @if($firmaExiste)

            <img
                src="{{ $base64Firma }}"
                alt="Firma evaluador"
                class="firma-img"
            >

        @else

            <p style="color:#999;font-style:italic;">
                Firma no disponible
            </p>

        @endif

        <div class="firma-texto">

            <strong>{{ $eval['nombre'] }}</strong><br>

            {{ $eval['profesion'] }}<br>

            {{ $eval['raa'] }}<br>

            {{ $eval['email'] }}<br>

            @if(!empty($eval['contrato']))
                {{ $eval['contrato'] }}
            @endif

        </div>

    </div>

@else

    <p style="color:#999;font-style:italic;">
        Evaluador no identificado
    </p>

@endif

</body>
</html>