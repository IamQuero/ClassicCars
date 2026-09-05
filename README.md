# ClassicCars
A little project where I'll be able to learn more about some programming languages and to extend my knowledge across diferent ways of developing.

## API v1

Base: `/api/v1`. Respuestas JSON; los errores de validación devuelven `422`.

### Autenticación (Laravel Sanctum, tokens Bearer)

| Método | Ruta | Auth | Descripción |
| --- | --- | --- | --- |
| POST | `/register` | — | Alta de usuario. Devuelve `user` y `token`. Campos: `name`, `email`, `password` (+`password_confirmation`), `role` opcional (`buyer` por defecto). |
| POST | `/login` | — | Devuelve `user` y `token`. |
| POST | `/logout` | sí | Borra el token con el que se llama. |
| GET | `/me` | sí | Usuario autenticado. |

Las rutas privadas se llaman con la cabecera `Authorization: Bearer <token>`.

### Anuncios

| Método | Ruta | Descripción |
| --- | --- | --- |
| GET | `/listings` | Listado paginado de anuncios publicados. |
| GET | `/listings/{id}` | Detalle de un anuncio. |

Filtros de `GET /listings`: `brand`, `model` (búsqueda parcial, sin distinguir
mayúsculas), `fuel`, `transmission`, `price_min`, `price_max`, `year_min`,
`year_max`, `mileage_max`. Orden con `sort` (`recent` por defecto, `price_asc`,
`price_desc`, `year_desc`) y tamaño de página con `per_page` (1-50, 15 por defecto).

El CRUD de anuncios (crear, editar, borrar), las fotos, los favoritos y la
mensajería todavía no están implementados.

## Desarrollo

```bash
cd backend
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
php artisan test
```

Los tests usan SQLite en memoria; el entorno del devcontainer usa PostgreSQL.
