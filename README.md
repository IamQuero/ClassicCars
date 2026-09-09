# ClassicCars

Marketplace de coches clásicos. Proyecto personal para coger ritmo de trabajo
real: Git y ramas, Laravel, API REST versionada, tests y CI.

- `backend/` — API REST en Laravel 12 (PHP 8.2).
- `frontend/` — Vue 3 + Vite que la consume. Ver `frontend/README.md`,
  especialmente `src/styles/theme.css`, que es donde vive todo lo visual.

## Puesta en marcha

```bash
cd backend
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan storage:link          # para servir las fotos subidas
php artisan serve --host=0.0.0.0 --port=8000
```

El devcontainer levanta PHP 8.2 y PostgreSQL 16. Los tests usan SQLite en
memoria, así que corren sin base de datos externa:

```bash
php artisan test          # 95 tests
./vendor/bin/pint         # estilo de código
```

El seeder deja un usuario fijo, `adrian@classiccars.test` (contraseña
`password`), 30 anuncios con fotos, favoritos, mensajes y un BMW E30 con tres
anuncios a distinto precio para probar el historial.

Con el backend en marcha, el frontend se arranca aparte:

```bash
cd frontend
npm install
cp .env.example .env
npm run dev               # http://localhost:5173
```

`APP_URL` del backend tiene que coincidir con la URL por la que se sirve la
API: las fotos se devuelven con esa base.

## API v1

Base: `/api/v1`. Todo JSON. Los errores de validación devuelven `422` y las
rutas privadas necesitan la cabecera `Authorization: Bearer <token>`.

Límites: 5 peticiones por minuto en registro y login (por email e IP) y 60 por
minuto en el resto de la API.

### Autenticación (Laravel Sanctum)

| Método | Ruta | Auth | Descripción |
| --- | --- | --- | --- |
| POST | `/register` | — | Alta de usuario. Devuelve `user` y `token`. Campos: `name`, `email`, `password` (+`password_confirmation`), `role` opcional (`buyer` por defecto). |
| POST | `/login` | — | Devuelve `user` y `token`. |
| POST | `/logout` | sí | Borra el token con el que se llama. |
| GET | `/me` | sí | Usuario autenticado. |
| PATCH | `/me` | sí | Edita `name`, `email`, `role` y `password`. Cambiar la contraseña exige `current_password` y cierra el resto de sesiones. |
| POST | `/forgot-password` | — | Envía el enlace de recuperación al frontend. Responde igual exista o no el email. |
| POST | `/reset-password` | — | Restablece con `token`, `email` y `password`. Invalida todas las sesiones. |

Roles: `buyer`, `seller`, `professional`. Solo `seller` y `professional`
publican anuncios.

### Anuncios

| Método | Ruta | Auth | Descripción |
| --- | --- | --- | --- |
| GET | `/listings` | — | Listado paginado de anuncios publicados. |
| GET | `/listings/{id}` | — | Detalle. Los borradores solo los ve su vendedor. |
| POST | `/listings` | sí | Publica un anuncio junto con su coche. Solo vendedores. |
| PATCH | `/listings/{id}` | sí | Edita precio, estado y datos del coche. Solo el dueño. |
| DELETE | `/listings/{id}` | sí | Retira el anuncio (`status = expired`), no borra la fila. Solo el dueño. |
| GET | `/me/listings` | sí | Anuncios propios, borradores incluidos. Filtro `?status=`. |
| GET | `/listings/filters` | — | Marcas y modelos disponibles y rangos de precio, año y kilómetros, para poblar los filtros. |

Filtros de `GET /listings`: `brand`, `model` (búsqueda parcial, sin distinguir
mayúsculas), `fuel`, `transmission`, `price_min`, `price_max`, `year_min`,
`year_max`, `mileage_max`. Orden con `sort` (`recent` por defecto, `price_asc`,
`price_desc`, `year_desc`) y tamaño de página con `per_page` (1-50, 15 por
defecto).

Cuerpo de `POST /listings`: `price`, `status` opcional (`draft` por defecto),
`expires_at` opcional y un objeto `car` con `brand`, `model`, `year`, `mileage`,
`transmission` y `fuel` obligatorios (`generation`, `engine`, `horsepower` y
`description` opcionales). `PATCH` acepta los mismos campos, todos opcionales.
`published_at` se sella la primera vez que el anuncio pasa a `published` y ya no
se vuelve a tocar.

Cuando hay usuario autenticado, cada anuncio incluye `is_favorite`. Cada foto trae
`url` (original) y `thumbnail_url` para las parrillas.

Un anuncio deja de listarse en cuanto pasa su `expires_at`, sin esperar al cron.
El comando `php artisan listings:expire` (programado a las 03:00) pone en
`expired` los que ya han caducado.

### Fotos

| Método | Ruta | Auth | Descripción |
| --- | --- | --- | --- |
| POST | `/listings/{id}/photos` | sí | Sube una foto (`photo`, multipart; `type` opcional). Máximo 15 por anuncio, 5 MB, jpg/png/webp. Genera miniatura de 480 px. |
| PUT | `/listings/{id}/photos/order` | sí | Reordena: `photos` con todos los ids del anuncio en el orden deseado. |
| DELETE | `/listings/{id}/photos/{photo}` | sí | Borra la foto y su fichero. |

### Favoritos

| Método | Ruta | Auth | Descripción |
| --- | --- | --- | --- |
| POST | `/listings/{id}/favorite` | sí | Guarda el anuncio (idempotente). |
| DELETE | `/listings/{id}/favorite` | sí | Lo quita. |
| GET | `/me/favorites` | sí | Favoritos del usuario, paginados. |

### Mensajes

| Método | Ruta | Auth | Descripción |
| --- | --- | --- | --- |
| POST | `/listings/{id}/messages` | sí | Escribe sobre un anuncio. El vendedor responde con `receiver_id`, y solo a quien ya le haya escrito. |
| GET | `/me/conversations` | sí | Una entrada por anuncio e interlocutor: último mensaje y pendientes de leer. |
| GET | `/me/conversations/{listing}/{user}` | sí | El hilo completo. Al abrirlo marca como leídos los mensajes recibidos. |

### Historial de precios

| Método | Ruta | Auth | Descripción |
| --- | --- | --- | --- |
| GET | `/cars/{id}/price-history` | — | Anuncios publicados de ese coche en orden cronológico, con resumen (`count`, `min`, `max`, `first`, `last`). |

## Pendiente

- Estilo visual del frontend (colores e identidad).
- Tests de frontend.
- Los datos del coche son compartidos por todos sus anuncios: editarlos desde
  un anuncio reescribe también los históricos. Si el historial crece en
  importancia, habrá que congelar una copia por anuncio.
- Verificación de email (la recuperación de contraseña ya está).
- Rol de administrador y moderación.
- Despliegue a producción.

Nota: retirar un anuncio no borra sus fotos del disco, a propósito: el anuncio
puede volver a publicarse. Los ficheros se eliminan al borrar la foto.
