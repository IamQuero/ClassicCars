<template>
  <div class="vacio">
    <h1>Aquí no hay nada</h1>
    <p>La página que buscas no existe o el anuncio ya no está disponible.</p>
    <RouterLink class="boton" :to="{ name: 'catalogo' }">Volver al catálogo</RouterLink>
  </div>
</template>
