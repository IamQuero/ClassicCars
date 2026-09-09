import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import { guardarToken, leerToken } from '@/api/client'
import { autenticacion } from '@/api/recursos'

export const useAuth = defineStore('auth', () => {
  const usuario = ref(null)
  const cargando = ref(false)

  const autenticado = computed(() => usuario.value !== null)
  const puedeVender = computed(() =>
    ['seller', 'professional'].includes(usuario.value?.role),
  )

  /** Recupera la sesión al arrancar la app si hay un token guardado. */
  async function recuperarSesion() {
    if (!leerToken() || usuario.value) return

    cargando.value = true
    try {
      usuario.value = (await autenticacion.yo()).data
    } catch {
      // Token caducado o revocado: se descarta sin molestar al usuario.
      guardarToken(null)
    } finally {
      cargando.value = false
    }
  }

  async function entrar(credenciales) {
    const respuesta = await autenticacion.acceso(credenciales)
    guardarToken(respuesta.token)
    usuario.value = respuesta.user
  }

  async function registrarse(datos) {
    const respuesta = await autenticacion.registro(datos)
    guardarToken(respuesta.token)
    usuario.value = respuesta.user
  }

  async function salir() {
    try {
      await autenticacion.salida()
    } finally {
      guardarToken(null)
      usuario.value = null
    }
  }

  function actualizarUsuario(datos) {
    usuario.value = datos
  }

  return {
    usuario,
    cargando,
    autenticado,
    puedeVender,
    recuperarSesion,
    entrar,
    registrarse,
    salir,
    actualizarUsuario,
  }
})
