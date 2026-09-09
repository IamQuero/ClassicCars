<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import { useAuth } from '@/stores/auth'

const auth = useAuth()
const router = useRouter()

const formulario = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'buyer',
})

const errores = ref({})
const errorGeneral = ref(null)
const enviando = ref(false)

async function enviar() {
  enviando.value = true
  errores.value = {}
  errorGeneral.value = null

  try {
    await auth.registrarse({ ...formulario })
    router.push({ name: 'catalogo' })
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
    <h1>Crear cuenta</h1>

    <form class="tarjeta" @submit.prevent="enviar">
      <div class="tarjeta-cuerpo pila">
        <p v-if="errorGeneral" class="aviso aviso-error">{{ errorGeneral }}</p>

        <div class="campo">
          <label for="name">Nombre</label>
          <input id="name" v-model="formulario.name" type="text" autocomplete="name" required />
          <p v-if="errores.name" class="campo-error">{{ errores.name[0] }}</p>
        </div>

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
            autocomplete="new-password"
            required
          />
          <p v-if="errores.password" class="campo-error">{{ errores.password[0] }}</p>
        </div>

        <div class="campo">
          <label for="password2">Repite la contraseña</label>
          <input
            id="password2"
            v-model="formulario.password_confirmation"
            type="password"
            autocomplete="new-password"
            required
          />
        </div>

        <div class="campo">
          <label for="role">¿Qué vas a hacer aquí?</label>
          <select id="role" v-model="formulario.role">
            <option value="buyer">Buscar coche</option>
            <option value="seller">Vender mi coche</option>
            <option value="professional">Soy profesional</option>
          </select>
          <p class="texto-pequeno texto-suave">Puedes cambiarlo después desde tu perfil.</p>
        </div>

        <button class="boton" type="submit" :disabled="enviando">
          {{ enviando ? 'Creando…' : 'Crear cuenta' }}
        </button>

        <p class="texto-pequeno texto-suave sin-margen">
          ¿Ya tienes cuenta? <RouterLink :to="{ name: 'acceso' }">Entrar</RouterLink>
        </p>
      </div>
    </form>
  </div>
</template>

<style scoped>
.estrecho { max-width: 440px; margin: 0 auto; }
.sin-margen { margin: 0; }
</style>
