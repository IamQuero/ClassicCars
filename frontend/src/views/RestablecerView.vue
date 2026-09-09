<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { autenticacion } from '@/api/recursos'

const route = useRoute()
const router = useRouter()

// El token y el email vienen en el enlace del correo.
const formulario = reactive({
  token: route.query.token ?? '',
  email: route.query.email ?? '',
  password: '',
  password_confirmation: '',
})

const errores = ref({})
const errorGeneral = ref(null)
const enviando = ref(false)

async function enviar() {
  enviando.value = true
  errores.value = {}
  errorGeneral.value = null

  try {
    await autenticacion.restablecerContrasena({ ...formulario })
    router.push({ name: 'acceso', query: { restablecida: '1' } })
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
    <h1>Nueva contraseña</h1>

    <form class="tarjeta" @submit.prevent="enviar">
      <div class="tarjeta-cuerpo pila">
        <p v-if="errorGeneral" class="aviso aviso-error">{{ errorGeneral }}</p>
        <p v-if="errores.email" class="aviso aviso-error">{{ errores.email[0] }}</p>

        <p v-if="!formulario.token" class="aviso aviso-error">
          Falta el token del enlace. Vuelve a pedir el correo de recuperación.
        </p>

        <div class="campo">
          <label for="email">Email</label>
          <input id="email" v-model="formulario.email" type="email" required />
        </div>

        <div class="campo">
          <label for="password">Nueva contraseña</label>
          <input
            id="password"
            v-model="formulario.password"
            type="password"
            autocomplete="new-password"
            required
          />
          <p v-if="errores.password" class="campo-error">{{ errores.password[0] }}</p>
        </div>

        <div class="campo">
          <label for="password2">Repítela</label>
          <input
            id="password2"
            v-model="formulario.password_confirmation"
            type="password"
            autocomplete="new-password"
            required
          />
        </div>

        <button class="boton" type="submit" :disabled="enviando || !formulario.token">
          {{ enviando ? 'Guardando…' : 'Guardar contraseña' }}
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.estrecho { max-width: 440px; margin: 0 auto; }
</style>
