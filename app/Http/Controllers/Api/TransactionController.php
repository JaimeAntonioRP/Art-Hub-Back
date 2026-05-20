<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequest;
use App\Models\Artwork;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    public function purchase(PurchaseRequest $request): JsonResponse
    {
        $artwork = Artwork::findOrFail($request->input('artwork_id'));

        if ($artwork->status !== 'available') {
            return response()->json([
                'message' => 'This artwork is not available for purchase.',
            ], 409);
        }

        $artwork->update(['status' => 'sold']);

        $blockchainTxId = '0x' . Str::random(64);

        if ($artwork->certificate) {
            $artwork->certificate->update([
                'blockchain_tx_id' => $blockchainTxId,
            ]);
        }

        return response()->json([
            'message' => 'Purchase completed successfully.',
            'artwork' => $artwork->fresh()->load('certificate'),
            'blockchain_tx_id' => $blockchainTxId,
        ]);
    }
}
