<?php

namespace Tests\Feature;

use App\Filament\Resources\Artists\Pages\EditArtist;
use App\Filament\Resources\Artists\Pages\ListArtists;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Artist;
use App\Models\User;
use App\Notifications\ArtistProfileReviewed;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Filament::setCurrentPanel('admin');
    }

    private function pendingArtist(): Artist
    {
        $user = User::factory()->create(['role' => User::ROLE_ARTISTA]);

        return $user->artist()->create(['name' => $user->name, 'slug' => 'ana', 'status' => 'pendiente']);
    }

    public function test_only_active_admins_enter_the_admin_panel(): void
    {
        $this->actingAs($this->admin)->get('/admin')->assertOk();
        $this->actingAs($this->admin)->get('/admin/artistas')->assertOk();
        $this->actingAs($this->admin)->get('/admin/usuarios')->assertOk();

        $artist = User::factory()->create(['role' => User::ROLE_ARTISTA]);
        $this->actingAs($artist)->get('/admin')->assertForbidden();

        $buyer = User::factory()->create(['role' => User::ROLE_COMPRADOR]);
        $this->actingAs($buyer)->get('/admin')->assertForbidden();
        $this->actingAs($buyer)->get('/artista/mi-perfil')->assertForbidden();

        $inactive = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => false]);
        $this->actingAs($inactive)->get('/admin')->assertForbidden();
    }

    public function test_admin_approves_a_pending_artist_and_the_artist_is_notified(): void
    {
        Notification::fake();
        $artist = $this->pendingArtist();

        $this->actingAs($this->admin);
        Livewire::test(ListArtists::class)
            ->assertCanSeeTableRecords([$artist])
            ->callAction(TestAction::make('approve')->table($artist))
            ->assertNotified();

        $this->assertSame('aprobado', $artist->fresh()->status);
        Notification::assertSentTo($artist->user, ArtistProfileReviewed::class);
    }

    public function test_observing_requires_a_note_and_stores_it(): void
    {
        Notification::fake();
        $artist = $this->pendingArtist();
        $this->actingAs($this->admin);

        Livewire::test(ListArtists::class)
            ->callAction(TestAction::make('observe')->table($artist), data: ['note' => ''])
            ->assertHasActionErrors(['note' => 'required']);
        $this->assertSame('pendiente', $artist->fresh()->status);

        Livewire::test(ListArtists::class)
            ->callAction(TestAction::make('observe')->table($artist), data: ['note' => 'Falta tu biografía.'])
            ->assertHasNoActionErrors();

        $artist->refresh();
        $this->assertSame('observado', $artist->status);
        $this->assertSame('Falta tu biografía.', $artist->reviewNotes()->value('body'));
        $this->assertSame($this->admin->id, $artist->reviewNotes()->value('author_id'));
        Notification::assertSentTo($artist->user, ArtistProfileReviewed::class,
            fn (ArtistProfileReviewed $n) => $n->note === 'Falta tu biografía.');
    }

    public function test_review_actions_follow_the_allowed_transitions(): void
    {
        $artist = $this->pendingArtist();
        $this->actingAs($this->admin);

        Livewire::test(EditArtist::class, ['record' => $artist->getKey()])
            ->assertActionVisible('approve')
            ->assertActionVisible('observe')
            ->assertActionVisible('reject')
            ->assertActionHidden('deactivate')
            ->callAction('approve')
            ->assertActionHidden('approve')
            ->assertActionVisible('deactivate')
            ->callAction('deactivate');

        $this->assertSame('inactivo', $artist->fresh()->status);
    }

    public function test_admin_cannot_demote_or_deactivate_themselves(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(EditUser::class, ['record' => $this->admin->getKey()])
            ->assertFormFieldDisabled('role')
            ->assertFormFieldDisabled('is_active');
    }

    public function test_admin_deactivates_another_user_from_the_list(): void
    {
        $buyer = User::factory()->create(['role' => User::ROLE_COMPRADOR]);
        $this->actingAs($this->admin);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$buyer, $this->admin])
            ->call('updateTableColumnState', 'is_active', (string) $buyer->getKey(), false);

        $this->assertFalse($buyer->fresh()->is_active);
    }
}
