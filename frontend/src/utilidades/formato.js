/** Formateo pensado para un usuario español: euros, miles y fechas. */

const euros = new Intl.NumberFormat('es-ES', {
  style: 'currency',
  currency: 'EUR',
  maximumFractionDigits: 0,
})

const numero = new Intl.NumberFormat('es-ES')

const fechaCorta = new Intl.DateTimeFormat('es-ES', {
  day: 'numeric',
  month: 'short',
  year: 'numeric',
})

const fechaConHora = new Intl.DateTimeFormat('es-ES', {
  day: 'numeric',
  month: 'short',
  hour: '2-digit',
  minute: '2-digit',
})

export const enEuros = (valor) => (valor === null || valor === undefined ? '—' : euros.format(valor))
export const enMiles = (valor) => (valor === null || valor === undefined ? '—' : numero.format(valor))
export const enFecha = (iso) => (iso ? fechaCorta.format(new Date(iso)) : '—')
export const enFechaYHora = (iso) => (iso ? fechaConHora.format(new Date(iso)) : '—')

export const COMBUSTIBLES = {
  petrol: 'Gasolina',
  diesel: 'Diésel',
  electric: 'Eléctrico',
  hybrid: 'Híbrido',
}

export const CAMBIOS = {
  manual: 'Manual',
  automatic: 'Automático',
}

export const ESTADOS = {
  draft: 'Borrador',
  published: 'Publicado',
  sold: 'Vendido',
  expired: 'Retirado',
}

export const ORDENES = {
  recent: 'Más recientes',
  price_asc: 'Precio: de menor a mayor',
  price_desc: 'Precio: de mayor a menor',
  year_desc: 'Año: más nuevos primero',
}
