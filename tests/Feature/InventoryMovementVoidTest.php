<?php

use App\Domain\Inventory\InventoryService;
use App\Models\InventoryMovement;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeUserForVoidRegression(): User
{
    return User::query()->create([
        'name' => 'Void Regression Tester',
        'email' => 'void-regression@example.test',
        'password' => 'test-only-password',
    ]);
}

function makeItemTypeForVoidRegression(): ItemType
{
    $item = Item::query()->create([
        'code' => 'VOID_TEST_ITEM',
        'name' => 'Void Test Item',
        'unit' => 'kg',
    ]);

    return ItemType::query()->create([
        'item_id' => $item->id,
        'type_key' => '0.20',
        'label' => '0.20 mm',
        'diameter_mm' => '0.20',
    ]);
}

test('deleted stock in is retained as void and excluded from balance and dashboard', function () {
    $type = makeItemTypeForVoidRegression();
    $service = app(InventoryService::class);
    $move = $service->recordIn($type->id, 8.25, now(), 'QA-VOID-IN');

    expect((float) $type->fresh()->balance_kg)->toBe(8.25);

    $this->actingAs(makeUserForVoidRegression())
        ->postJson("/app/moves/{$move->id}/delete")
        ->assertOk()
        ->assertJsonPath('ok', true);

    $retained = InventoryMovement::query()->find($move->id);
    expect($retained)->not->toBeNull();
    expect($retained->meta['voided_at'] ?? null)->not->toBeNull();
    expect($retained->meta['void_reason'] ?? null)->toBe('deleted');
    expect((float) $type->fresh()->balance_kg)->toBe(0.0);
    expect($service->balanceKg($type->id))->toBe(0.0);

    $this->withHeader('X-PJAX', '1')
        ->get('/app/history')
        ->assertOk()
        ->assertDontSee('QA-VOID-IN');

    $this->withHeader('X-PJAX', '1')
        ->get('/app/history?show_voided=1')
        ->assertOk()
        ->assertSee('QA-VOID-IN')
        ->assertSee('Dibatalkan pada:');

    $this->withHeader('X-PJAX', '1')
        ->get('/app/home')
        ->assertOk()
        ->assertViewHas('summary', fn (array $summary) => $summary['today_in_kg'] === 0.0)
        ->assertViewHas('charts', fn (array $charts) => array_sum($charts['inOut']['in']) === 0.0);
});

test('stock out cancellation restores balance once and cannot be repeated or edited', function () {
    $type = makeItemTypeForVoidRegression();
    $service = app(InventoryService::class);
    $service->recordIn($type->id, 10.0);
    $out = $service->recordOut($type->id, 4.0);

    $this->actingAs(makeUserForVoidRegression());

    $this->postJson("/app/moves/{$out->id}/delete")
        ->assertOk()
        ->assertJsonPath('ok', true);

    expect((float) $type->fresh()->balance_kg)->toBe(10.0);
    expect($service->balanceKg($type->id))->toBe(10.0);
    expect(InventoryMovement::query()->find($out->id))->not->toBeNull();

    $this->postJson("/app/moves/{$out->id}/delete")
        ->assertStatus(422)
        ->assertJsonPath('ok', false);

    $this->postJson("/app/moves/{$out->id}/update", ['qty_kg' => '2.00'])
        ->assertStatus(422)
        ->assertJsonPath('ok', false);

    expect((float) $type->fresh()->balance_kg)->toBe(10.0);
    expect(InventoryMovement::query()->count())->toBe(2);
});

test('an in movement cannot be cancelled when later out movements still consume its stock', function () {
    $type = makeItemTypeForVoidRegression();
    $service = app(InventoryService::class);
    $in = $service->recordIn($type->id, 5.0);
    $service->recordOut($type->id, 3.0);

    $this->actingAs(makeUserForVoidRegression())
        ->postJson("/app/moves/{$in->id}/delete")
        ->assertStatus(422)
        ->assertJsonPath('ok', false);

    expect((float) $type->fresh()->balance_kg)->toBe(2.0);
    expect(InventoryMovement::query()->findOrFail($in->id)->meta['voided_at'] ?? null)->toBeNull();
    expect(InventoryMovement::query()->count())->toBe(2);
});
