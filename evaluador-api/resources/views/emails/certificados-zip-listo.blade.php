<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ZIP de certificados listo</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <p>Hola {{ $userName }},</p>

    @if ($downloadUrl)
        <p>Tu exportación de certificados ya fue procesada en segundo plano.</p>
        <ul>
            <li><strong>Archivo:</strong> {{ $zipFileName }}</li>
            <li><strong>PDF generados:</strong> {{ $totalRegistros }}</li>
            <li><strong>Alcance:</strong> {{ $exportaTodosFiltrados ? 'todos los filtrados' : 'selección manual' }}</li>
            @if ($filtro !== '')
                <li><strong>Filtro aplicado:</strong> {{ $filtro }}</li>
            @endif
        </ul>
        <p>
            Descarga el ZIP desde esta ruta:<br>
            <a href="{{ $downloadUrl }}">{{ $downloadUrl }}</a>
        </p>
    @else
        <p>No fue posible generar un ZIP descargable para la solicitud de certificados.</p>
        <p>Revisa los detalles y vuelve a intentarlo con un lote más pequeño o un filtro más específico.</p>
    @endif

    @if (!empty($errores))
        <p><strong>Observaciones:</strong></p>
        <ul>
            @foreach ($errores as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <p>Saludos.</p>
</body>
</html>
