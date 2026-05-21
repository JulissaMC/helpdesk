# Soporte360 Helpdesk

Sistema web ligero para registrar, clasificar y dar seguimiento a tickets de soporte técnico. Está diseñado para demostrar experiencia en soporte, diagnóstico de equipos, administración de usuarios y desarrollo web con PHP.

## Qué demuestra este proyecto

- Desarrollo web con PHP, HTML y CSS.
- Manejo de sesiones y autenticación por roles.
- CRUD de tickets con persistencia en SQLite mediante PDO.
- Dashboard con métricas operativas para soporte técnico.
- Buenas prácticas básicas: separación de responsabilidades, sanitización de salida y estructura clara.

## Funcionalidades

- Login con usuarios de prueba.
- Roles: administrador, técnico y cliente.
- Creación de tickets de soporte.
- Cambio de estado: Abierto, En progreso, Resuelto.
- Métricas de tickets por estado.
- Base de datos SQLite generada automáticamente.

## Usuarios de prueba

| Rol | Email | Contraseña |
|---|---|---|
| Administrador | admin@soporte360.local | admin123 |
| Técnico | tecnico@soporte360.local | tecnico123 |
| Cliente | cliente@soporte360.local | cliente123 |

## Requisitos

- PHP 8 o superior.
- Extensión PDO SQLite habilitada.

## Ejecución local

```bash
php -S localhost:8000 -t public
```

Abre:

```text
http://localhost:8000
```

## Estructura

```text
public/              Entrada pública de la aplicación
src/                 Configuración, base de datos y autenticación
storage/             SQLite generado automáticamente
.github/workflows/  Validación CI de sintaxis PHP
```

## Próximas mejoras

- Adjuntar evidencias del equipo reparado.
- Agregar filtros por prioridad.
- Enviar notificaciones por correo.
- Agregar pruebas automatizadas de rutas.
