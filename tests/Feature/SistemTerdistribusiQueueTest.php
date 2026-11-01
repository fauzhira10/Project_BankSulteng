<?php

use App\Jobs\ProsesEtlDataWarehouseJob;
use App\Jobs\ProsesTambangBlockchainJob;
use App\Models\User;
use App\Services\BlockchainAuditService;
use App\Services\DataWarehouseEtlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('job penambangan blockchain dan etl dapat dikirim ke antrean pekerja terdistribusi', function () {
    Queue::fake();

    // 1. Dispatch Job Blockchain ke Antrean
    ProsesTambangBlockchainJob::dispatch();
    Queue::assertPushed(ProsesTambangBlockchainJob::class);

    // 2. Dispatch Job ETL ke Antrean
    ProsesEtlDataWarehouseJob::dispatch(false);
    Queue::assertPushed(ProsesEtlDataWarehouseJob::class);
});

test('tombol antrean async di controller blockchain berhasil mengirim job ke antrean', function () {
    Queue::fake();

    $admin = User::factory()->create([
        'username' => 'admin_uji',
        'role'     => 'admin',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.blockchain.tambang', ['async' => 1]));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    Queue::assertPushed(ProsesTambangBlockchainJob::class);
});

test('job penambangan blockchain berhasil dieksekusi oleh queue worker', function () {
    $mockBlockchain = mock(BlockchainAuditService::class);
    $mockBlockchain->shouldReceive('tambang')->once()->andReturn([]);

    app()->instance(BlockchainAuditService::class, $mockBlockchain);

    $job = new ProsesTambangBlockchainJob();
    app()->call([$job, 'handle']);

    expect(true)->toBeTrue();
});
