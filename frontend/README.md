# Frontend de ClassicCars

Vue 3 + Vite, sin framework de CSS. Consume la API v1 de `backend/`.

## Arrancar

```bash
npm install
cp .env.example .env      # apunta VITE_API_URL a tu API
npm run dev               # http://localhost:5173
```

El backend tiene que estar escuchando y su `FRONTEND_URL` debe incluir el
origen desde el que abras la web: `localhost` y `127.0.0.1` son orígenes
distintos para el navegador y CORS los trata por separado.

```bash
npm run build       # compila a dist/
npm run lint        # eslint con corrección automática
npm run lint:check  # solo comprueba
```

## Los colores y el aspecto: `src/styles/theme.css`

**Todo lo visual sale de ese archivo.** Ningún componente escribe un color a
mano. Está organizado en tres capas:

1. **Colores base** — la paleta cruda (`--gris-500`, `--marca-500`…).
2. **Roles** — para qué se usa cada color (`--color-primario`,
   `--color-texto-suave`…). Los componentes usan siempre roles.
3. **Modo oscuro** — solo redefine roles.

Para cambiar la identidad de la web normalmente basta con tocar los colores de
marca de la sección 1. Ahí mismo están también las tipografías, el espaciado,
los radios y las sombras.

`src/styles/base.css` tiene las clases compartidas (`.tarjeta`, `.boton`,
`.campo`, `.aviso`…), construidas sobre esas variables.

## Cómo está organizado

```
src/
  api/client.js      Fetch con token, errores de Laravel traducidos a ApiError
  api/recursos.js    Las llamadas agrupadas por recurso
  stores/auth.js     Sesión: entrar, salir, recuperar el usuario del token
  router/index.js    Rutas y guardas (privada / soloInvitados)
  utilidades/        Formato de euros, fechas y traducción de enums
  components/        Piezas reutilizables
  views/             Una por pantalla
```

El token se guarda en `localStorage`. Al arrancar, si hay token, la guarda del
router resuelve la sesión antes de decidir si te deja pasar.

## Pantallas

Catálogo con filtros en la URL · detalle con galería e historial de precios ·
entrar, registro y recuperación de contraseña · favoritos · mis anuncios con
alta, edición y gestión de fotos · mensajes con bandeja e hilos · perfil.

## Pendiente

- Tests de frontend (Vitest + Testing Library).
- El aspecto es funcional y neutro a propósito: los colores y la identidad
  visual están por decidir.
