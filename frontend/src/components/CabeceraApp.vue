<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import { useAuth } from '@/stores/auth'

const auth = useAuth()
const router = useRouter()
const menuAbierto = ref(false)

async function salir() {
  await auth.salir()
  menuAbierto.value = false
  router.push({ name: 'catalogo' })
}
</script>

<template>
  <header class="cabecera">
    <div class="contenedor barra">
      <RouterLink :to="{ name: 'catalogo' }" class="marca">ClassicCars</RouterLink>

      <button
        class="boton boton-secundario boton-pequeno menu-movil"
        type="button"
        :aria-expanded="menuAbierto"
        @click="menuAbierto = !menuAbierto"
      >
        Menú
      </button>

      <nav class="navegacion" :class="{ abierta: menuAbierto }" @click="menuAbierto = false">
        <RouterLink :to="{ name: 'catalogo' }">Catálogo</RouterLink>

        <template v-if="auth.autenticado">
          <RouterLink :to="{ name: 'favoritos' }">Favoritos</RouterLink>
          <RouterLink :to="{ name: 'mensajes' }">Mensajes</RouterLink>
          <RouterLink v-if="auth.puedeVender" :to="{ name: 'mis-anuncios' }">
            Mis anuncios
          </RouterLink>
          <RouterLink :to="{ name: 'perfil' }">{{ auth.usuario.name }}</RouterLink>
          <button class="boton boton-secundario boton-pequeno" type="button" @click="salir">
            Salir
          </button>
        </template>

        <template v-else>
          <RouterLink :to="{ name: 'acceso' }">Entrar</RouterLink>
          <RouterLink class="boton boton-pequeno" :to="{ name: 'registro' }">Crear cuenta</RouterLink>
        </template>
      </nav>
    </div>
  </header>
</template>

<style scoped>
.cabecera {
  background: var(--color-superficie);
  border-bottom: 1px solid var(--color-borde);
  position: sticky;
  top: 0;
  z-index: 10;
}

.barra {
  min-height: var(--altura-cabecera);
  display: flex;
  align-items: center;
  gap: var(--espacio-4);
  flex-wrap: wrap;
}

.marca {
  font-family: var(--fuente-titulos);
  font-size: var(--texto-xl);
  font-weight: var(--peso-fuerte);
  color: var(--color-texto);
}

.marca:hover { text-decoration: none; color: var(--color-primario); }

.navegacion {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: var(--espacio-4);
}

.navegacion a { color: var(--color-texto-suave); font-size: var(--texto-sm); }
.navegacion a:hover { color: var(--color-primario); }
.navegacion a.router-link-active { color: var(--color-primario); font-weight: var(--peso-medio); }
.navegacion a.boton { color: var(--color-texto-inverso); }

.menu-movil { display: none; margin-left: auto; }

@media (max-width: 720px) {
  .menu-movil { display: inline-flex; }

  .navegacion {
    display: none;
    width: 100%;
    margin: 0;
    padding-bottom: var(--espacio-4);
    flex-direction: column;
    align-items: flex-start;
  }

  .navegacion.abierta { display: flex; }
}
</style>
