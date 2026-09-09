<script setup>
import { reactive, ref } from 'vue'

import { autenticacion } from '@/api/recursos'
import { useAuth } from '@/stores/auth'

const auth = useAuth()

const datos = reactive({
  name: auth.usuario?.name ?? '',
  email: auth.usuario?.email ?? '',
  role: auth.usuario?.role ?? 'buyer',
})

const contrasena = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const errores = ref({})
const errorGeneral = ref(null)
const guardado = ref(null)
const guardando = ref(false)

async function guardar(campos, mensaje) {
  guardando.value = true
  errores.value = {}
  errorGeneral.value = null
  guardado.value = null

  try {
    const respuesta = await autenticacion.editarPerfil(campos)
    auth.actualizarUsuario(respuesta.data)
    guardado.value = mensaje
    return true
  } catch (error) {
    if (error.esValidacion) errores.value = error.errores
    else errorGeneral.value = error.message
    return false
  } finally {
    guardando.value = false
  }
}

const guardarDatos = () => guardar({ ...datos }, 'Datos actualizados.')

async function guardarContrasena() {
  const hecho = await guardar({ ...contrasena }, 'Contraseña actualizada. Las demás sesiones se han cerrado.')

  if (hecho) {
    contrasena.current_password = ''
    contrasena.password = ''
    contrasena.password_confirmation = ''
  }
}

const errorDe = (campo) => errores.value[campo]?.[0]
</script>

<template>
  <h1>Mi perfil</h1>

  <div class="estrecho pila">
    <p v-if="guardado" class="aviso aviso-exito">{{ guardado }}</p>
    <p v-if="errorGeneral" class="aviso aviso-error">{{ errorGeneral }}</p>

    <form class="tarjeta" @submit.prevent="guardarDatos">
      <div class="tarjeta-cuerpo pila">
        <h3 class="sin-margen">Datos</h3>

        <div class="campo">
          <label for="name">Nombre</label>
          <input id="name" v-model="datos.name" type="text" required />
          <p v-if="errorDe('name')" class="campo-error">{{ errorDe('name') }}</p>
        </div>

        <div class="campo">
          <label for="email">Email</label>
          <input id="email" v-model="datos.email" type="email" required />
          <p v-if="errorDe('email')" class="campo-error">{{ errorDe('email') }}</p>
        </div>

        <div class="campo">
          <label for="role">Tipo de cuenta</label>
          <select id="role" v-model="datos.role">
            <option value="buyer">Comprador</option>
            <option value="seller">Vendedor</option>
            <option value="professional">Profesional</option>
          </select>
          <p class="texto-pequeno texto-suave">Solo los vendedores y profesionales pueden publicar.</p>
        </div>

        <button class="boton" type="submit" :disabled="guardando">Guardar datos</button>
      </div>
    </form>

    <form class="tarjeta" @submit.prevent="guardarContrasena">
      <div class="tarjeta-cuerpo pila">
        <h3 class="sin-margen">Cambiar contraseña</h3>

        <div class="campo">
          <label for="actual">Contraseña actual</label>
          <input
            id="actual"
            v-model="contrasena.current_password"
            type="password"
            autocomplete="current-password"
          />
          <p v-if="errorDe('current_password')" class="campo-error">
            {{ errorDe('current_password') }}
          </p>
        </div>

        <div class="campo">
          <label for="nueva">Contraseña nueva</label>
          <input id="nueva" v-model="contrasena.password" type="password" autocomplete="new-password" />
          <p v-if="errorDe('password')" class="campo-error">{{ errorDe('password') }}</p>
        </div>

        <div class="campo">
          <label for="repite">Repítela</label>
          <input
            id="repite"
            v-model="contrasena.password_confirmation"
            type="password"
            autocomplete="new-password"
          />
        </div>

        <button
          class="boton"
          type="submit"
          :disabled="guardando || !contrasena.password || !contrasena.current_password"
        >
          Cambiar contraseña
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.estrecho { max-width: 520px; }
.sin-margen { margin: 0; }
</style>
