<?php

namespace Tests\Feature;

use App\Filament\Artista\Resources\Artworks\Pages\EditArtwork as ArtistEditArtwork;
use App\Filament\RelationManagers\ArtworkOwnershipsRelationManager;
use App\Filament\RelationManagers\ArtworkSalesRelationManager;
use App\Filament\Resources\Artworks\Pages\EditArtwork as AdminEditArtwork;
use App\Filament\Resources\CommercialRequests\Pages\EditCommercialRequest;
use App\Filament\Resources\Sales\Pages\ListSales;
use App\Models\Artist;
use App\Models\Artwork;
use App\Models\Sale;
use App\Models\User;
use DomainException;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Semana 7: propietarios (§25) y ventas (§26). */
class SaleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Artwork $artwork;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $artist = Artist::create(['name' => 'Rosa Huamán', 'slug' => 'rosa', 'origin' => 'Cusco, Perú', 'status' => Artist::STATUS_APROBADO]);
        $this->artwork = Artwork::create([
            'artist_id' => $artist->id, 'title' => 'Apus al amanecer', 'price' => 4500, 'currency' => 'USD',
            'status' => Artwork::STATUS_PUBLICADO,
        ]);
    }

    private function sale(string $type, array $data = []): Sale
    {
        return Sale::register($this->artwork->fresh(), [
            'type' => $type, 'date' => '2026-10-20', 'party_name' => 'Lucía Quispe', 'party_email' => 'lucia@example.com',
            'final_price' => $type === Sale::TYPE_TRANSFERENCIA ? null : 4200, 'currency' => 'USD', ...$data,
        ], $this->admin);
    }

    public function test_a_sale_marks_the_artwork_sold_and_changes_the_owner(): void
    {
        $sale = $this->sale(Sale::TYPE_VENTA);

        $artwork = $this->artwork->fresh();
        $this->assertSame('vendida', $artwork->availability);
        $this->assertSame('disponible', $sale->availability_before);
        $this->assertSame('4200.00', $sale->final_price);

        $owners = $artwork->ownerships()->orderBy('id')->get();
        $this->assertSame(['Rosa Huamán', 'Lucía Quispe'], $owners->pluck('owner_name')->all());
        $this->assertSame(['artista', 'venta'], $owners->pluck('origin')->all());
        $this->assertSame('2026-10-20', $owners[0]->to_date->toDateString()); // el artista deja de ser propietario ese día
        $this->assertNull($owners[1]->to_date);
        $this->assertSame('Lucía Quispe', $artwork->currentOwnership->owner_name);

        $events = $artwork->history()->pluck('type')->all();
        $this->assertContains('disponibilidad_cambiada', $events);
        $event = $artwork->history()->where('type', 'venta_registrada')->sole();
        $this->assertSame(['fecha' => '2026-10-20'], $event->payload); // sin datos del comprador
    }

    public function test_reserve_then_sell_then_transfer(): void
    {
        $this->sale(Sale::TYPE_RESERVA);
        $this->assertSame('reservada', $this->artwork->fresh()->availability);
        $this->assertSame(0, $this->artwork->ownerships()->count()); // reservar no cambia el propietario

        $this->sale(Sale::TYPE_VENTA);
        $this->sale(Sale::TYPE_TRANSFERENCIA, ['party_name' => 'Museo de Arte de Lima', 'date' => '2026-10-21']);

        $artwork = $this->artwork->fresh();
        $this->assertSame('vendida', $artwork->availability);
        $this->assertSame(['artista', 'venta', 'transferencia'], $artwork->ownerships()->orderBy('id')->pluck('origin')->all());
        $this->assertSame('Museo de Arte de Lima', $artwork->currentOwnership->owner_name);
        $this->assertSame(1, $artwork->ownerships()->whereNull('to_date')->count());
    }

    public function test_rules_follow_the_availability(): void
    {
        $this->assertSame([Sale::TYPE_RESERVA, Sale::TYPE_VENTA], Sale::allowedTypesFor($this->artwork));

        try {
            $this->sale(Sale::TYPE_TRANSFERENCIA);
            $this->fail('Una obra disponible no se transfiere: se vende.');
        } catch (DomainException) {
        }

        $this->sale(Sale::TYPE_VENTA);
        $this->assertSame([Sale::TYPE_TRANSFERENCIA], Sale::allowedTypesFor($this->artwork->fresh()));

        $this->expectException(DomainException::class);
        $this->sale(Sale::TYPE_RESERVA);
    }

    public function test_cancelling_undoes_the_last_record(): void
    {
        $this->sale(Sale::TYPE_RESERVA);
        $sale = $this->sale(Sale::TYPE_VENTA);
        $reserve = $this->artwork->sales()->where('type', 'reserva')->sole();

        try {
            $reserve->cancel($this->admin, 'Error');
            $this->fail('Solo se anula el último registro vigente.');
        } catch (DomainException) {
        }

        $sale->cancel($this->admin, 'Se cayó el pago');

        $artwork = $this->artwork->fresh();
        $this->assertSame('reservada', $artwork->availability); // vuelve a como estaba antes de la venta
        $this->assertSame(['Rosa Huamán'], $artwork->ownerships()->pluck('owner_name')->all());
        $this->assertNull($artwork->currentOwnership->to_date);
        $this->assertTrue($sale->fresh()->isCancelled());
        $this->assertSame('Se cayó el pago', $artwork->history()->where('type', 'registro_anulado')->sole()->payload['motivo']);

        $reserve->fresh()->cancel($this->admin, 'El cliente desistió'); // ahora sí es el último vigente
        $this->assertSame('disponible', $this->artwork->fresh()->availability);
    }

    public function test_admin_registers_a_sale_from_the_artwork(): void
    {
        Filament::setCurrentPanel('admin');
        $this->actingAs($this->admin);
        $manager = fn () => Livewire::test(ArtworkSalesRelationManager::class, ['ownerRecord' => $this->artwork, 'pageClass' => AdminEditArtwork::class]);

        $manager()
            ->callAction(TestAction::make('register')->table(), data: ['type' => 'venta', 'party_name' => '', 'final_price' => null])
            ->assertHasActionErrors(['party_name' => 'required', 'final_price' => 'required']);

        $manager()
            ->mountAction(TestAction::make('register')->table())
            ->assertSchemaStateSet(['currency' => 'USD', 'final_price' => '4500.00', 'date' => now()->toDateString()])
            ->setActionData(['type' => 'venta', 'party_name' => 'Lucía Quispe', 'final_price' => 4300])
            ->callMountedAction()
            ->assertHasNoActionErrors()
            ->assertNotified('Venta registrada');

        $this->assertSame('vendida', $this->artwork->fresh()->availability);
        $sale = $this->artwork->sales()->sole();

        $manager()
            ->callAction(TestAction::make('cancel')->table($sale), data: ['reason' => 'Error de tipeo'])
            ->assertHasNoActionErrors();
        $this->assertSame('disponible', $this->artwork->fresh()->availability);

        Livewire::test(ListSales::class)
            ->filterTable('cancelled_at', true)
            ->assertCanSeeTableRecords([$sale]);
        $this->get('/admin/ventas')->assertOk();
    }

    public function test_a_confirmed_request_becomes_a_sale_with_its_data(): void
    {
        Filament::setCurrentPanel('admin');
        $this->actingAs($this->admin);
        $request = $this->artwork->commercialRequests()->create([
            'name' => 'Lucía Quispe', 'email' => 'lucia@example.com', 'phone' => '999', 'type' => 'intencion_compra',
        ]);

        Livewire::test(EditCommercialRequest::class, ['record' => $request->id])
            ->mountAction('registerSale')
            ->assertSchemaStateSet(['type' => 'venta', 'party_name' => 'Lucía Quispe', 'party_email' => 'lucia@example.com', 'party_phone' => '999'])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $sale = $this->artwork->sales()->sole();
        $this->assertTrue($sale->commercialRequest->is($request));
        $this->assertSame('confirmada', $request->fresh()->status);
        $this->assertSame('Lucía Quispe', $this->artwork->fresh()->currentOwnership->owner_name);
    }

    public function test_artists_do_not_see_buyers_or_owners(): void
    {
        $this->sale(Sale::TYPE_VENTA);
        $artistUser = User::factory()->create(['role' => User::ROLE_ARTISTA]);
        $this->artwork->artist->update(['user_id' => $artistUser->id]);

        Filament::setCurrentPanel('artista');
        $this->actingAs($artistUser);

        $this->assertFalse(ArtworkSalesRelationManager::canViewForRecord($this->artwork, ArtistEditArtwork::class));
        $this->assertFalse(ArtworkOwnershipsRelationManager::canViewForRecord($this->artwork, ArtistEditArtwork::class));
        $this->actingAs($artistUser)->get('/admin/ventas')->assertForbidden();
    }
}
