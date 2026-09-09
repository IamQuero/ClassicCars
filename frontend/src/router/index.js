import { createRouter, createWebHistory } from 'vue-router'

import { leerToken } from '@/api/client'
import { useAuth } from '@/stores/auth'

const rutas = [
  { path: '/', name: 'catalogo', component: () => import('@/views/CatalogoView.vue') },
  {
    path: '/anuncios/:id',
    name: 'anuncio',
    component: () => import('@/views/AnuncioView.vue'),
    props: true,
  },
  { path: '/acceso', name: 'acceso', component: () => import('@/views/AccesoView.vue'), meta: { soloInvitados: true } },
  { path: '/registro', name: 'registro', component: () => import('@/views/RegistroView.vue'), meta: { soloInvitados: true } },
  {
    path: '/recuperar',
    name: 'recuperar',
    component: () => import('@/views/RecuperarView.vue'),
    meta: { soloInvitados: true },
  },
  {
    path: '/reset-password',
    name: 'restablecer',
    component: () => import('@/views/RestablecerView.vue'),
    meta: { soloInvitados: true },
  },
  { path: '/favoritos', name: 'favoritos', component: () => import('@/views/FavoritosView.vue'), meta: { privada: true } },
  {
    path: '/mis-anuncios',
    name: 'mis-anuncios',
    component: () => import('@/views/MisAnunciosView.vue'),
    meta: { privada: true },
  },
  {
    path: '/mis-anuncios/nuevo',
    name: 'anuncio-nuevo',
    component: () => import('@/views/AnuncioFormularioView.vue'),
    meta: { privada: true },
  },
  {
    path: '/mis-anuncios/:id/editar',
    name: 'anuncio-editar',
    component: () => import('@/views/AnuncioFormularioView.vue'),
    props: true,
    meta: { privada: true },
  },
  {
    path: '/mensajes',
    name: 'mensajes',
    component: () => import('@/views/MensajesView.vue'),
    meta: { privada: true },
  },
  {
    path: '/mensajes/:anuncioId/:usuarioId',
    name: 'hilo',
    component: () => import('@/views/HiloView.vue'),
    props: true,
    meta: { privada: true },
  },
  { path: '/perfil', name: 'perfil', component: () => import('@/views/PerfilView.vue'), meta: { privada: true } },
  { path: '/:pathMatch(.*)*', name: 'no-encontrado', component: () => import('@/views/NoEncontradoView.vue') },
]

export const router = createRouter({
  history: createWebHistory(),
  routes: rutas,
  scrollBehavior: (a, b, guardada) => guardada ?? { top: 0 },
})

router.beforeEach(async (destino) => {
  const auth = useAuth()

  // Con un token guardado hay que resolver la sesión antes de decidir.
  if (leerToken() && !auth.autenticado) await auth.recuperarSesion()

  if (destino.meta.privada && !auth.autenticado) {
    return { name: 'acceso', query: { volverA: destino.fullPath } }
  }

  if (destino.meta.soloInvitados && auth.autenticado) {
    return { name: 'catalogo' }
  }

  return true
})
