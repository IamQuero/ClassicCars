<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAuth } from '@/stores/auth'

const auth = useAuth()
const route = useRoute()
const router = useRouter()

const formulario = reactive({ email: '', password: '' })
const errores = ref({})
const errorGeneral = ref(null)
const enviando = ref(false)

async function enviar() {
  enviando.value = true
  errores.value = {}
  errorGeneral.value = null

  try {
    await auth.entrar({ ...formulario })
    router.push(route.query.volverA ?? { name: 'catalogo' })
  } catch (error) {
    if (error.esValidacion) errores.value = error.errores
    else errorGeneral.value = error.message
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <div class="estrecho">
    <h1>Entrar</h1>

    <form class="tarjeta" @submit.prevent="enviar">
      <div class="tarjeta-cuerpo pila">
        <p v-if="errorGeneral" class="aviso aviso-error">{{ errorGeneral }}</p>

        <div class="campo">
          <label for="email">Email</label>
          <input id="email" v-model="formulario.email" type="email" autocomplete="email" required />
          <p v-if="errores.email" class="campo-error">{{ errores.email[0] }}</p>
        </div>

        <div class="campo">
          <label for="password">Contraseña</label>
          <input
            id="password"
            v-model="formulario.password"
            type="password"
            autocomplete="current-password"
            required
          />
          <p v-if="errores.password" class="campo-error">{{ errores.password[0] }}</p>
        </div>

        <button class="boton" type="submit" :disabled="enviando">
          {{ enviando ? 'Entrando…' : 'Entrar' }}
        </button>

        <p class="texto-pequeno texto-suave sin-margen">
          <RouterLink :to="{ name: 'recuperar' }">He olvidado la contraseña</RouterLink>
          ·
          <RouterLink :to="{ name: 'registro' }">Crear una cuenta</RouterLink>
        </p>
      </div>
    </form>
  </div>
</template>

<style scoped>
.estrecho { max-width: 440px; margin: 0 auto; }
.sin-margen { margin: 0; }
</style>
