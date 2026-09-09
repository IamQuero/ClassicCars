/**
 * Las llamadas a la API agrupadas por recurso. Los componentes llaman a estas
 * funciones y nunca construyen rutas a mano.
 */

import { api } from './client'

export const autenticacion = {
  registro: (datos) => api.post('/register', datos),
  acceso: (datos) => api.post('/login', datos),
  salida: () => api.post('/logout'),
  yo: () => api.get('/me'),
  editarPerfil: (datos) => api.patch('/me', datos),
  olvideContrasena: (email) => api.post('/forgot-password', { email }),
  restablecerContrasena: (datos) => api.post('/reset-password', datos),
}

export const anuncios = {
  listar: (filtros) => api.get('/listings', filtros),
  filtrosDisponibles: () => api.get('/listings/filters'),
  ver: (id) => api.get(`/listings/${id}`),
  crear: (datos) => api.post('/listings', datos),
  editar: (id, datos) => api.patch(`/listings/${id}`, datos),
  retirar: (id) => api.delete(`/listings/${id}`),
  mios: (params) => api.get('/me/listings', params),
}

export const fotos = {
  subir: (anuncioId, archivo, tipo) => {
    const formulario = new FormData()
    formulario.append('photo', archivo)
    if (tipo) formulario.append('type', tipo)
    return api.subir(`/listings/${anuncioId}/photos`, formulario)
  },
  reordenar: (anuncioId, ids) => api.put(`/listings/${anuncioId}/photos/order`, { photos: ids }),
  borrar: (anuncioId, fotoId) => api.delete(`/listings/${anuncioId}/photos/${fotoId}`),
}

export const favoritos = {
  listar: (params) => api.get('/me/favorites', params),
  guardar: (anuncioId) => api.post(`/listings/${anuncioId}/favorite`),
  quitar: (anuncioId) => api.delete(`/listings/${anuncioId}/favorite`),
}

export const mensajes = {
  enviar: (anuncioId, mensaje, destinatarioId) =>
    api.post(`/listings/${anuncioId}/messages`, {
      message: mensaje,
      ...(destinatarioId ? { receiver_id: destinatarioId } : {}),
    }),
  conversaciones: () => api.get('/me/conversations'),
  hilo: (anuncioId, usuarioId) => api.get(`/me/conversations/${anuncioId}/${usuarioId}`),
}

export const coches = {
  historialDePrecios: (cocheId) => api.get(`/cars/${cocheId}/price-history`),
}
