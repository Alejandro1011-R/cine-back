<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Modules\Catalog\Infrastructure\Persistence\Models\Actor;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Genero;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Pelicula;
use App\Modules\Identity\Infrastructure\Persistence\Models\Cliente;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CatalogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Usuario
    {
        Cliente::create(['ci' => '99999999999', 'correo' => 'admin@test.com']);

        return Usuario::create([
            'ci' => '99999999999',
            'nombre_s' => 'Admin',
            'contrasena' => 'x',
            'codigo' => '99999999999',
            'rol' => 'Admin',
        ]);
    }

    // === Actores ===

    public function test_crud_actores(): void
    {
        $admin = $this->admin();

        // Create
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/actores', ['nombre_a' => 'Tom Hanks']);
        $response->assertStatus(201);
        $id = $response->json('id_a');

        // Index
        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/actores');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');

        // Show
        $response = $this->actingAs($admin, 'sanctum')->getJson("/api/actores/{$id}");
        $response->assertOk();
        $response->assertJsonFragment(['nombre_a' => 'Tom Hanks']);

        // Update
        $response = $this->actingAs($admin, 'sanctum')->putJson("/api/actores/{$id}", ['nombre_a' => 'Tom Hanks Jr.']);
        $response->assertStatus(204);

        $actor = Actor::find($id);
        $this->assertEquals('Tom Hanks Jr.', $actor->nombre_a);

        // Delete
        $response = $this->actingAs($admin, 'sanctum')->deleteJson("/api/actores/{$id}");
        $response->assertStatus(204);
        $this->assertSoftDeleted('actores', ['id_a' => $id]);
    }

    // === Géneros ===

    public function test_crud_generos(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/generos', ['nombre_g' => 'Acción']);
        $response->assertStatus(201);
        $id = $response->json('id_g');

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/generos');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');

        $response = $this->actingAs($admin, 'sanctum')->getJson("/api/generos/{$id}");
        $response->assertOk();

        $response = $this->actingAs($admin, 'sanctum')->putJson("/api/generos/{$id}", ['nombre_g' => 'Aventura']);
        $response->assertStatus(204);

        $response = $this->actingAs($admin, 'sanctum')->deleteJson("/api/generos/{$id}");
        $response->assertStatus(204);
    }

    // === Películas ===

    public function test_crear_pelicula_con_actores_y_generos(): void
    {
        $admin = $this->admin();
        $actor = Actor::create(['nombre_a' => 'Actor 1']);
        $genero = Genero::create(['nombre_g' => 'Drama']);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/peliculas', [
            'titulo' => 'El Padrino',
            'sinopsis' => 'Una historia de mafia',
            'anno' => 1972,
            'duracion' => 175,
            'nacionalidad' => 1,
            'actor_ids' => [$actor->id_a],
            'genero_ids' => [$genero->id_g],
        ]);

        $response->assertStatus(201);

        $pelicula = Pelicula::with(['actores', 'generos'])->first();
        $this->assertCount(1, $pelicula->actores);
        $this->assertCount(1, $pelicula->generos);
        $this->assertEquals('Actor 1', $pelicula->actores->first()->nombre_a);
    }

    public function test_actualizar_pelicula(): void
    {
        $admin = $this->admin();
        $pelicula = Pelicula::create([
            'titulo' => 'Original',
            'duracion' => 120,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->putJson("/api/peliculas/{$pelicula->id_p}", [
            'titulo' => 'Modificada',
            'duracion' => 130,
        ]);

        $response->assertStatus(204);

        $pelicula->refresh();
        $this->assertEquals('Modificada', $pelicula->titulo);
        $this->assertEquals(130, $pelicula->duracion);
    }

    public function test_listar_peliculas_con_actores_y_generos(): void
    {
        $admin = $this->admin();
        $actor = Actor::create(['nombre_a' => 'Actor']);
        $genero = Genero::create(['nombre_g' => 'Comedia']);

        $pelicula = Pelicula::create(['titulo' => 'Film', 'duracion' => 90]);
        $pelicula->actores()->attach($actor->id_a);
        $pelicula->generos()->attach($genero->id_g);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/peliculas');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['actores' => ['Actor']]);
        $response->assertJsonFragment(['generos' => ['Comedia']]);
    }
}
