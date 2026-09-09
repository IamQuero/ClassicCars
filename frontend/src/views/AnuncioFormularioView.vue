<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import { anuncios as apiAnuncios, fotos as apiFotos } from '@/api/recursos'
import { CAMBIOS, COMBUSTIBLES } from '@/utilidades/formato'

const props = defineProps({ id: { type: [String, Number], default: null } })

const router = useRouter()
const editando = computed(() => props.id !== null)

const formulario = reactive({
  price: '',
  status: 'draft',
  car: {
    brand: '',
    model: '',
    generation: '',
    year: '',
    mileage: '',
    engine: '',
    horsepower: '',
    transmission: 'manual',
    fuel: 'petrol',
    description: '',
  },
})

const fotosDelAnuncio = ref([])
const errores = ref({})
const errorGeneral = ref(null)
const guardando = ref(false)
const cargando = ref(false)
const subiendo = ref(false)

async function cargar() {
  if (!editando.value) return

  cargando.value = true
  try {
    const anuncio = (await apiAnuncios.ver(props.id)).data
    formulario.price = anuncio.price
    formulario.status = anuncio.status
    Object.keys(formulario.car).forEach((campo) => {
      formulario.car[campo] = anuncio.car[campo] ?? ''
    })
    fotosDelAnuncio.value = anuncio.photos ?? []
  } catch (error) {
    errorGeneral.value = error.message
  } finally {
    cargando.value = false
  }
}

/** Los campos opcionales vacíos no se envían: el backend los quiere ausentes. */
function payload() {
  const coche = Object.fromEntries(
    Object.entries(formulario.car).filter(([, valor]) => valor !== '' && valor !== null),
  )

  return { price: formulario.price, status: formulario.status, car: coche }
}

async function guardar() {
  guardando.value = true
  errores.value = {}
  errorGeneral.value = null

  try {
    if (editando.value) {
      await apiAnuncios.editar(props.id, payload())
      router.push({ name: 'anuncio', params: { id: props.id } })
    } else {
      const creado = (await apiAnuncios.crear(payload())).data
      // Recién creado no tiene fotos: se queda aquí para poder subirlas.
      router.replace({ name: 'anuncio-editar', params: { id: creado.id } })
    }
  } catch (error) {
    if (error.esValidacion) errores.value = error.errores
    else errorGeneral.value = error.message
  } finally {
    guardando.value = false
  }
}

async function subirFotos(evento) {
  const archivos = [...evento.target.files]
  if (!archivos.length) return

  subiendo.value = true
  errorGeneral.value = null

  try {
    for (const archivo of archivos) {
      const foto = (await apiFotos.subir(props.id, archivo)).data
      fotosDelAnuncio.value.push(foto)
    }
  } catch (error) {
    errorGeneral.value = error.errorDe('photo') ?? error.message
  } finally {
    subiendo.value = false
    evento.target.value = ''
  }
}

async function borrarFoto(foto) {
  const copia = [...fotosDelAnuncio.value]
  fotosDelAnuncio.value = fotosDelAnuncio.value.filter((f) => f.id !== foto.id)

  try {
    await apiFotos.borrar(props.id, foto.id)
  } catch (error) {
    fotosDelAnuncio.value = copia
    errorGeneral.value = error.message
  }
}

/** Mover una foto reordena el anuncio entero: la API pide la lista completa. */
async function mover(indice, salto) {
  const destino = indice + salto
  if (destino < 0 || destino >= fotosDelAnuncio.value.length) return

  const copia = [...fotosDelAnuncio.value]
  const orden = [...fotosDelAnuncio.value]
  ;[orden[indice], orden[destino]] = [orden[destino], orden[indice]]
  fotosDelAnuncio.value = orden

  try {
    await apiFotos.reordenar(props.id, orden.map((f) => f.id))
  } catch (error) {
    fotosDelAnuncio.value = copia
    errorGeneral.value = error.message
  }
}

const errorDe = (campo) => errores.value[campo]?.[0]

onMounted(cargar)
</script>

<template>
  <h1>{{ editando ? 'Editar anuncio' : 'Publicar anuncio' }}</h1>

  <p v-if="cargando" class="vacio">Cargando…</p>

  <div v-else class="columnas">
    <form class="tarjeta" @submit.prevent="guardar">
      <div class="tarjeta-cuerpo pila">
        <p v-if="errorGeneral" class="aviso aviso-error">{{ errorGeneral }}</p>

        <h3 class="sin-margen">El coche</h3>

        <div class="rejilla-campos">
          <div class="campo">
            <label for="marca">Marca</label>
            <input id="marca" v-model="formulario.car.brand" type="text" required />
            <p v-if="errorDe('car.brand')" class="campo-error">{{ errorDe('car.brand') }}</p>
          </div>

          <div class="campo">
            <label for="modelo">Modelo</label>
            <input id="modelo" v-model="formulario.car.model" type="text" required />
            <p v-if="errorDe('car.model')" class="campo-error">{{ errorDe('car.model') }}</p>
          </div>
        </div>

        <div class="rejilla-campos">
          <div class="campo">
            <label for="anyo">Año</label>
            <input id="anyo" v-model="formulario.car.year" type="number" min="1900" required />
            <p v-if="errorDe('car.year')" class="campo-error">{{ errorDe('car.year') }}</p>
          </div>

          <div class="campo">
            <label for="km">Kilómetros</label>
            <input id="km" v-model="formulario.car.mileage" type="number" min="0" required />
            <p v-if="errorDe('car.mileage')" class="campo-error">{{ errorDe('car.mileage') }}</p>
          </div>
        </div>

        <div class="rejilla-campos">
          <div class="campo">
            <label for="combustible">Combustible</label>
            <select id="combustible" v-model="formulario.car.fuel">
              <option v-for="(texto, clave) in COMBUSTIBLES" :key="clave" :value="clave">{{ texto }}</option>
            </select>
          </div>

          <div class="campo">
            <label for="cambio">Cambio</label>
            <select id="cambio" v-model="formulario.car.transmission">
              <option v-for="(texto, clave) in CAMBIOS" :key="clave" :value="clave">{{ texto }}</option>
            </select>
          </div>
        </div>

        <div class="rejilla-campos">
          <div class="campo">
            <label for="generacion">Generación <span class="texto-suave">(opcional)</span></label>
            <input id="generacion" v-model="formulario.car.generation" type="text" />
          </div>

          <div class="campo">
            <label for="motor">Motor <span class="texto-suave">(opcional)</span></label>
            <input id="motor" v-model="formulario.car.engine" type="text" placeholder="2.5" />
          </div>

          <div class="campo">
            <label for="cv">Potencia (CV) <span class="texto-suave">(opcional)</span></label>
            <input id="cv" v-model="formulario.car.horsepower" type="number" min="1" />
          </div>
        </div>

        <div class="campo">
          <label for="descripcion">Descripción</label>
          <textarea
            id="descripcion"
            v-model="formulario.car.description"
            placeholder="Historial, restauraciones, detalles a tener en cuenta…"
          />
          <p v-if="errorDe('car.description')" class="campo-error">{{ errorDe('car.description') }}</p>
        </div>

        <h3 class="sin-margen">El anuncio</h3>

        <div class="rejilla-campos">
          <div class="campo">
            <label for="precio">Precio (€)</label>
            <input id="precio" v-model="formulario.price" type="number" min="0" step="100" required />
            <p v-if="errorDe('price')" class="campo-error">{{ errorDe('price') }}</p>
          </div>

          <div class="campo">
            <label for="estado">Estado</label>
            <select id="estado" v-model="formulario.status">
              <option value="draft">Borrador (solo tú lo ves)</option>
              <option value="published">Publicado</option>
              <option v-if="editando" value="sold">Vendido</option>
            </select>
          </div>
        </div>

        <div class="fila">
          <button class="boton" type="submit" :disabled="guardando">
            {{ guardando ? 'Guardando…' : editando ? 'Guardar cambios' : 'Crear anuncio' }}
          </button>
          <RouterLink class="boton boton-secundario" :to="{ name: 'mis-anuncios' }">Cancelar</RouterLink>
        </div>
      </div>
    </form>

    <aside class="tarjeta">
      <div class="tarjeta-cuerpo pila">
        <h3 class="sin-margen">Fotos</h3>

        <p v-if="!editando" class="texto-pequeno texto-suave sin-margen">
          Podrás subir fotos en cuanto guardes el anuncio.
        </p>

        <template v-else>
          <p class="texto-pequeno texto-suave sin-margen">
            La primera foto es la portada. Máximo 15, hasta 5 MB cada una.
          </p>

          <ul v-if="fotosDelAnuncio.length" class="lista-fotos">
            <li v-for="(foto, indice) in fotosDelAnuncio" :key="foto.id" class="foto">
              <img :src="foto.thumbnail_url" alt="" />

              <div class="fila acciones-foto">
                <button
                  class="boton boton-secundario boton-pequeno"
                  type="button"
                  :disabled="indice === 0"
                  title="Subir"
                  @click="mover(indice, -1)"
                >
                  ↑
                </button>
                <button
                  class="boton boton-secundario boton-pequeno"
                  type="button"
                  :disabled="indice === fotosDelAnuncio.length - 1"
                  title="Bajar"
                  @click="mover(indice, 1)"
                >
                  ↓
                </button>
                <button
                  class="boton boton-peligro boton-pequeno fila-fin"
                  type="button"
                  @click="borrarFoto(foto)"
                >
                  Borrar
                </button>
              </div>
            </li>
          </ul>

          <div class="campo">
            <label for="fotos">Añadir fotos</label>
            <input
              id="fotos"
              type="file"
              accept="image/jpeg,image/png,image/webp"
              multiple
              :disabled="subiendo || fotosDelAnuncio.length >= 15"
              @change="subirFotos"
            />
            <p v-if="subiendo" class="texto-pequeno texto-suave">Subiendo…</p>
          </div>
        </template>
      </div>
    </aside>
  </div>
</template>

<style scoped>
.columnas {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: var(--espacio-6);
  align-items: start;
}

.sin-margen { margin: 0; }

.lista-fotos { list-style: none; margin: 0; padding: 0; display: grid; gap: var(--espacio-3); }

.foto { display: grid; gap: var(--espacio-2); }
.foto img { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; border-radius: var(--radio-md); }

.acciones-foto { gap: var(--espacio-2); }

@media (max-width: 880px) {
  .columnas { grid-template-columns: 1fr; }
}
</style>
