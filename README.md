# QR School - Sistema de Asistencia con Códigos QR

Un sistema de gestión de asistencia para entornos educativos que utiliza códigos QR para registrar la asistencia de estudiantes de manera rápida y eficiente.

## Características

- **Gestión de usuarios**: Administradores y profesores
- **Gestión de estudiantes**: Información personal, asignación a grupos
- **Generación de códigos QR**: Códigos únicos para cada estudiante
- **Registro de asistencia**: Escaneo de códigos QR mediante dispositivos móviles
- **Reportes**: Generación de informes por estudiante, grupo y fecha
- **Importación masiva**: Carga de estudiantes mediante archivos Excel o CSV

## Requisitos

- PHP 7.4 o superior
- MySQL/MariaDB
- Extensiones PHP: 
  - mbstring
  - xml
  - gd
  - pdo_mysql
  - curl
  - zip (recomendado)
- Composer (para gestión de dependencias)

## Instalación

### 1. Configuración de la base de datos

1. Crea una base de datos MySQL e importa el archivo `gestion.sql` para establecer la estructura inicial
2. Configura los datos de conexión en el archivo `.env`:

```
DB_SERVER=localhost
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
DB_NAME=nombre_base_datos
```

### 2. Instalación de dependencias

Ejecuta el siguiente comando en la raíz del proyecto:

```bash
composer install
```

### 3. Configuración del servidor

Para instalar en un servidor local (XAMPP, WAMP, etc.):
1. Coloca los archivos en el directorio htdocs o www
2. Accede a través de http://localhost/nombre-directorio

Para instalar en un servidor remoto:
1. Sube todos los archivos mediante FTP
2. Asegúrate de que los permisos de archivos y carpetas sean correctos (755 para directorios, 644 para archivos)
3. Si tu servidor no tiene acceso SSH para ejecutar Composer:
   - Ejecuta `composer install` localmente
   - Sube la carpeta `vendor` generada al servidor

### 4. Verificación

Accede a `check_phpspreadsheet.php` para verificar que las dependencias están correctamente instaladas.

## Uso del sistema

### Acceso al sistema
- Usuario administrador por defecto:
  - Correo: usuario@gmail.com
  - Contraseña: xxxxxxx

### Funcionalidades principales
- **Administrador**: Gestión de grupos, estudiantes, profesores y reportes
- **Profesor**: Registro de asistencia mediante escaneo de códigos QR y reportes

## Solución de problemas

### Error de conexión a la base de datos
Si recibes un error como `Host 'XXX.XXX.XXX.XXX' is not allowed to connect to this MySQL server`:
1. Verifica que los datos de conexión en `.env` sean correctos
2. Para desarrollo local, asegúrate de usar 'localhost' como DB_SERVER
3. Para servidores remotos, confirma que tu IP esté autorizada para conectarse

### Error al importar estudiantes
Si la importación de estudiantes desde Excel no funciona:
1. Verifica que PHPSpreadsheet esté instalado correctamente ejecutando `check_phpspreadsheet.php`
2. Asegúrate de que el formato del archivo de importación sea correcto
3. Considera usar archivos CSV que suelen tener mejor compatibilidad

## Licencia

Este proyecto está licenciado bajo la Licencia MIT - ver el archivo LICENSE para más detalles.

## Contacto

Para soporte o consultas, contactar a scharss@gmail.com

