# Instrucciones de Instalación

Para asegurar que la importación masiva de estudiantes funcione correctamente tanto en entorno local como en el servidor, sigue estos pasos:

## Requisitos previos

1. PHP 7.4 o superior
2. Extensión mbstring activada
3. Extensión xml activada
4. Extensión zip activada (recomendado pero ya no es obligatorio)
5. Composer instalado (https://getcomposer.org/)

## Pasos de instalación

### 1. Instalación de dependencias mediante Composer

Una vez subido el código al servidor, accede vía SSH y ejecuta:

```bash
cd /ruta/a/tu/proyecto
composer install
```

Si no tienes acceso SSH pero sí tienes acceso FTP/SFTP, puedes hacer lo siguiente:

1. Ejecuta Composer localmente en tu equipo:
   ```bash
   composer install
   ```

2. Sube toda la carpeta `vendor` generada a tu servidor mediante FTP/SFTP.

### 2. Verificación de la instalación

Para verificar que PHPSpreadsheet está correctamente instalado y configurado, accede a:

```
https://tudominio.com/check_phpspreadsheet.php
```

Deberías ver un mensaje indicando que "PHPSpreadsheet está correctamente instalado y disponible".

### 3. Configuración del servidor (si es necesario)

Si estás en un hosting compartido como Hostgator y encuentras problemas, puedes intentar:

1. Crear/editar el archivo `.htaccess` en la raíz de tu proyecto y añadir:

   ```
   php_value upload_max_filesize 10M
   php_value post_max_size 10M
   php_value max_execution_time 300
   php_value memory_limit 256M
   ```

2. Contactar con el soporte de Hostgator para asegurarte de que las extensiones requeridas (mbstring, xml) están habilitadas en tu cuenta.

## Solución de problemas

Si después de seguir todos los pasos sigues teniendo problemas, verifica:

1. El archivo `check_phpspreadsheet.php` para identificar problemas específicos.
2. Los logs de error de PHP (si tienes acceso).
3. Que los permisos de las carpetas sean correctos (generalmente 755 para directorios y 644 para archivos).

## Alternativas

Si continúas teniendo problemas con la subida de archivos Excel, recuerda que también puedes utilizar archivos CSV que suelen ser más compatibles con diferentes entornos. 