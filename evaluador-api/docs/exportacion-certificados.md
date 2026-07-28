# Exportación masiva de certificados ZIP

La descarga masiva se procesa con colas de Laravel para que el navegador no tenga que esperar a que se generen todos los PDF.

## Configuración requerida

1. Mantén una cola real en el ambiente donde se use la exportación:

```env
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=7500
```

2. Ejecuta las migraciones para que existan las tablas `jobs` y `failed_jobs`:

```bash
php artisan migrate
```

3. Levanta un worker de cola. En local puedes dejarlo en una terminal aparte:

```bash
php artisan queue:work database --queue=default --timeout=7200 --tries=1
```

4. Configura correo. En local, `MAIL_MAILER=log` no envía emails reales: escribe el contenido en `storage/logs/laravel.log`. Para recibirlo en una bandeja usa SMTP, Mailtrap, SES u otro proveedor:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=usuario
MAIL_PASSWORD=secreto
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="El Evaluador"
```

5. Publica el disco público para que el enlace enviado por correo apunte a archivos accesibles:

```bash
php artisan storage:link
```

También valida que `APP_URL` tenga la URL pública correcta del API, porque esa URL se usa para construir el enlace de descarga.

## Rendimiento

El job procesa los registros por lotes pequeños, genera cada PDF con las mismas vistas que la descarga individual y agrega archivos temporales al ZIP para reducir memoria. Las gráficas se cachean por avalúo durante 24 horas, por lo que los siguientes ZIP del mismo lote evitan llamadas repetidas a QuickChart.

Si una exportación falla, revisa:

```bash
php artisan queue:failed
php artisan queue:retry all
```

Y consulta `storage/logs/laravel.log` para ver placas omitidas, errores de generación o problemas de correo.
