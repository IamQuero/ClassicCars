<script setup>
import { ref } from 'vue'

import { autenticacion } from '@/api/recursos'

const email = ref('')
const enviado = ref(false)
const error = ref(null)
const enviando = ref(false)

async function enviar() {
  enviando.value = true
  error.value = null

  try {
    await autenticacion.olvideContrasena(email.value)
    enviado.value = true
  } catch (err) {
    error.value = err.errorDe('email') ?? err.message
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <div class="estrecho">
    <h1>Recuperar contraseña</h1>

    <form class="tarjeta" @submit.prevent="enviar">
      <div class="tarjeta-cuerpo pila">
        <p v-if="enviado" class="aviso aviso-exito">
          Si ese email está registrado, recibirás un enlace para restablecer la contraseña.
        </p>

        <template v-else>
          <p class="texto-pequeno texto-suave sin-margen">
            Te enviaremos un enlace para elegir una contraseña nueva.
          </p>

          <div class="campo">
            <label for="email">Email</label>
            <input id="email" v-model="email" type="email" autocomplete="email" required />
            <p v-if="error" class="campo-error">{{ error }}</p>
          </div>

          <button class="boton" type="submit" :disabled="enviando">
            {{ enviando ? 'Enviando…' : 'Enviar enlace' }}
          </button>
        </template>

        <p class="texto-pequeno texto-suave sin-margen">
          <RouterLink :to="{ name: 'acceso' }">Volver a entrar</RouterLink>
        </p>
      </div>
    </form>
  </div>
</template>

<style scoped>
.estrecho { max-width: 440px; margin: 0 auto; }
.sin-margen { margin: 0; }
</style>
