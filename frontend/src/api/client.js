/**
 * Cliente HTTP de la API. Un único sitio donde se decide la URL base, se
 * adjunta el token y se traducen los errores de Laravel a algo que los
 * componentes puedan mostrar sin repetir lógica.
 */

const BASE = import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api/v1'

const CLAVE_TOKEN = 'classiccars_token'

export function leerToken() {
  return localStorage.getItem(CLAVE_TOKEN)
}

export function guardarToken(token) {
  if (token) localStorage.setItem(CLAVE_TOKEN, token)
  else localStorage.removeItem(CLAVE_TOKEN)
}

/** Error de la API con el status y los errores de validación ya separados. */
export class ApiError extends Error {
  constructor(status, cuerpo) {
    super(cuerpo?.message ?? 'Ha fallado la petición')
    this.status = status
    this.errores = cuerpo?.errors ?? {}
  }

  /** Primer mensaje de un campo concreto, para pintarlo bajo el input. */
  errorDe(campo) {
    return this.errores[campo]?.[0] ?? null
  }

  get esValidacion() {
    return this.status === 422
  }

  get esNoAutenticado() {
    return this.status === 401
  }
}

async function peticion(metodo, ruta, { cuerpo, params, esFormulario = false } = {}) {
  const url = new URL(`${BASE}${ruta}`)

  Object.entries(params ?? {}).forEach(([clave, valor]) => {
    if (valor !== null && valor !== undefined && valor !== '') {
      url.searchParams.set(clave, valor)
    }
  })

  const cabeceras = { Accept: 'application/json' }
  const token = leerToken()
  if (token) cabeceras.Authorization = `Bearer ${token}`
  if (cuerpo && !esFormulario) cabeceras['Content-Type'] = 'application/json'

  const respuesta = await fetch(url, {
    method: metodo,
    headers: cabeceras,
    body: esFormulario ? cuerpo : cuerpo ? JSON.stringify(cuerpo) : undefined,
  })

  if (respuesta.status === 204) return null

  const datos = await respuesta.json().catch(() => ({}))

  if (!respuesta.ok) throw new ApiError(respuesta.status, datos)

  return datos
}

export const api = {
  get: (ruta, params) => peticion('GET', ruta, { params }),
  post: (ruta, cuerpo) => peticion('POST', ruta, { cuerpo }),
  patch: (ruta, cuerpo) => peticion('PATCH', ruta, { cuerpo }),
  put: (ruta, cuerpo) => peticion('PUT', ruta, { cuerpo }),
  delete: (ruta) => peticion('DELETE', ruta),
  subir: (ruta, formData) => peticion('POST', ruta, { cuerpo: formData, esFormulario: true }),
}
