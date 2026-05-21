<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #222; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #777; padding: 4px; font-size: 11px; }
        h3 { margin: 10px 0 8px; text-align: center; }
        .section { color: #1f6f8b; font-weight: 700; margin: 12px 0 6px; }
    </style>
</head>
<body>
    <h3>AJUSTE VALOR BASE KILOGRAMO DE CHATARRA</h3>
    <p>Por medio de este documento, se busca actualizar el valor base de subasta del kilogramo (kg.) de chatarra.</p>
    <p>El perito <b>{{ $row['evaluador'] ?? 'N/A' }}</b> recomienda que el vehículo relacionado se comercialice en calidad de <b>CHATARRA</b> con un peso estimado de <b>{{ $row['pesoChatarraKg'] ?? '-' }}</b> Kg.</p>

    <table>
        <tr><td><b>Placa:</b> {{ $row['placa'] ?? 'N/A' }}</td><td><b>Clase:</b> {{ $row['clase'] ?? 'N/A' }}</td><td><b>Servicio:</b> </td></tr>
        <tr><td><b>Marca:</b> {{ $row['marca'] ?? 'N/A' }}</td><td><b>Línea:</b> {{ $row['linea'] ?? 'N/A' }}</td><td><b>Modelo:</b> {{ $row['modelo'] ?? 'N/A' }}</td></tr>
        <tr><td><b>Carrocería:</b> {{ $row['carroceria'] ?? 'N/A' }}</td><td><b>Motor:</b> </td><td><b>Cilindraje:</b> {{ $row['cilindraje'] ?? 'N/A' }}</td></tr>
        <tr><td><b>Serie:</b> {{ $row['serie'] ?? 'N/A' }}</td><td><b>Chasis:</b> {{ $row['chasis'] ?? 'N/A' }}</td><td><b>VIN:</b> {{ $row['vin'] ?? 'N/A' }}</td></tr>
    </table>
</body>
</html>
