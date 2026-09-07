<?php

namespace Tests\Feature\Api\V1;

use App\Models\Listing;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function anuncioPropio(): array
    {
        $seller = User::factory()->create(['role' => 'seller']);
        Sanctum::actingAs($seller);

        return [$seller, Listing::factory()->create(['seller_id' => $seller->id])];
    }

    public function test_el_vendedor_sube_una_foto_a_su_anuncio(): void
    {
        [, $listing] = $this->anuncioPropio();

        $response = $this->postJson("/api/v1/listings/{$listing->id}/photos", [
            'photo' => UploadedFile::fake()->image('frontal.jpg'),
            'type' => 'exterior',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'exterior')
            ->assertJsonStructure(['data' => ['id', 'path', 'url', 'type', 'order']]);

        Storage::disk('public')->assertExists($response->json('data.path'));
        $this->assertDatabaseCount('photos', 1);
    }

    public function test_las_fotos_se_numeran_de_forma_correlativa(): void
    {
        [, $listing] = $this->anuncioPropio();

        foreach (range(1, 3) as $i) {
            $this->postJson("/api/v1/listings/{$listing->id}/photos", [
                'photo' => UploadedFile::fake()->image("foto-{$i}.jpg"),
            ])->assertCreated();
        }

        $this->assertSame([0, 1, 2], $listing->photos()->pluck('order')->all());
    }

    public function test_rechaza_archivos_que_no_son_imagenes_o_pesan_demasiado(): void
    {
        [, $listing] = $this->anuncioPropio();

        $this->postJson("/api/v1/listings/{$listing->id}/photos", [
            'photo' => UploadedFile::fake()->create('manual.pdf', 100, 'application/pdf'),
        ])->assertStatus(422)->assertJsonValidationErrors('photo');

        $this->postJson("/api/v1/listings/{$listing->id}/photos", [
            'photo' => UploadedFile::fake()->image('enorme.jpg')->size(6000),
        ])->assertStatus(422)->assertJsonValidationErrors('photo');

        $this->assertDatabaseCount('photos', 0);
    }

    public function test_no_se_pueden_subir_mas_de_quince_fotos(): void
    {
        [, $listing] = $this->anuncioPropio();
        Photo::factory(15)->create(['listing_id' => $listing->id]);

        $this->postJson("/api/v1/listings/{$listing->id}/photos", [
            'photo' => UploadedFile::fake()->image('la-dieciseis.jpg'),
        ])->assertStatus(422);

        $this->assertDatabaseCount('photos', 15);
    }

    public function test_otro_vendedor_no_puede_subir_fotos_a_un_anuncio_ajeno(): void
    {
        $listing = Listing::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => 'seller']));

        $this->postJson("/api/v1/listings/{$listing->id}/photos", [
            'photo' => UploadedFile::fake()->image('intrusa.jpg'),
        ])->assertForbidden();
    }

    public function test_un_invitado_no_puede_subir_fotos(): void
    {
        $listing = Listing::factory()->create();

        $this->postJson("/api/v1/listings/{$listing->id}/photos", [
            'photo' => UploadedFile::fake()->image('intrusa.jpg'),
        ])->assertUnauthorized();
    }

    public function test_el_vendedor_borra_una_foto_y_desaparece_del_disco(): void
    {
        [, $listing] = $this->anuncioPropio();
        $path = $this->postJson("/api/v1/listings/{$listing->id}/photos", [
            'photo' => UploadedFile::fake()->image('frontal.jpg'),
        ])->json('data.path');

        $photo = Photo::first();

        $this->deleteJson("/api/v1/listings/{$listing->id}/photos/{$photo->id}")->assertOk();

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseCount('photos', 0);
    }

    public function test_no_se_puede_borrar_una_foto_de_otro_anuncio(): void
    {
        [, $mio] = $this->anuncioPropio();
        $ajena = Photo::factory()->create();

        $this->deleteJson("/api/v1/listings/{$mio->id}/photos/{$ajena->id}")->assertNotFound();

        $this->assertDatabaseHas('photos', ['id' => $ajena->id]);
    }

    public function test_el_vendedor_reordena_las_fotos_de_su_anuncio(): void
    {
        [, $listing] = $this->anuncioPropio();
        $fotos = Photo::factory(3)->create(['listing_id' => $listing->id]);
        $nuevoOrden = [$fotos[2]->id, $fotos[0]->id, $fotos[1]->id];

        $this->putJson("/api/v1/listings/{$listing->id}/photos/order", ['photos' => $nuevoOrden])
            ->assertOk()
            ->assertJsonPath('data.0.id', $nuevoOrden[0])
            ->assertJsonPath('data.1.id', $nuevoOrden[1])
            ->assertJsonPath('data.2.id', $nuevoOrden[2]);
    }

    public function test_reordenar_exige_enviar_todas_las_fotos_del_anuncio(): void
    {
        [, $listing] = $this->anuncioPropio();
        $fotos = Photo::factory(3)->create(['listing_id' => $listing->id]);

        $this->putJson("/api/v1/listings/{$listing->id}/photos/order", [
            'photos' => [$fotos[0]->id, $fotos[1]->id],
        ])->assertStatus(422);
    }

    public function test_las_fotos_del_anuncio_salen_ordenadas_en_el_detalle(): void
    {
        $listing = Listing::factory()->create();
        $segunda = Photo::factory()->create(['listing_id' => $listing->id, 'order' => 1]);
        $primera = Photo::factory()->create(['listing_id' => $listing->id, 'order' => 0]);

        $this->getJson("/api/v1/listings/{$listing->id}")
            ->assertOk()
            ->assertJsonPath('data.photos.0.id', $primera->id)
            ->assertJsonPath('data.photos.1.id', $segunda->id);
    }
}
