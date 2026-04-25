# Cine Back

Backend para una plataforma de cine con reserva y compra de entradas.

El proyecto permite gestionar usuarios, roles, cines, salas, butacas, sesiones, peliculas, descuentos, compras y pagos simulados. Tambien incluye control para evitar dobles reservas de butacas, auditoria de acciones importantes y documentacion de la API.

La aplicacion esta organizada como un monolito modular por contextos de negocio:

```text
Identity
Catalog
Cinema
Ticketing
```

Cada modulo separa controladores, validaciones, acciones de aplicacion, modelos e infraestructura para mantener el codigo ordenado y facil de mantener.

## Documentacion API

La especificacion esta en:

```text
docs/openapi.yaml
```

## Ejecutar

```bash
composer install
php artisan migrate --seed
php artisan test
```
