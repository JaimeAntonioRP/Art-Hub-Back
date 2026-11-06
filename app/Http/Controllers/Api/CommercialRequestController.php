<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Artwork;
use App\Models\CommercialRequest;
use App\Models\User;
use App\Notifications\CommercialRequestReceived;
use App\Notifications\NewCommercialRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Solicitud de información o intención de compra sobre una obra (§14).
 * No hay pago en línea: el equipo de ArtHub contacta al interesado.
 */
class CommercialRequestController extends Controller
{
    public function store(Request $request, int $artworkId): JsonResponse
    {
        $artwork = Artwork::published()->findOrFail($artworkId);
        abort_if($artwork->is_sample, 422, 'Esta es una obra de muestra de dominio público: no está a la venta.');

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:40',
            'type' => ['required', Rule::in(CommercialRequest::TYPES)],
            'message' => 'nullable|string|max:2000',
            'consent' => 'accepted',
            // Campo trampa: los bots lo completan, las personas no lo ven.
            'website' => 'prohibited',
        ], [
            'name.required' => 'Escribe tu nombre.',
            'email.required' => 'Escribe tu correo para poder responderte.',
            'email.email' => 'Revisa el correo: parece incompleto.',
            'type.*' => 'Elige si quieres información o comprar la obra.',
            '*.max' => 'Es demasiado largo (máximo :max caracteres).',
            'consent.accepted' => 'Debes aceptar que usemos tus datos para responder a tu solicitud.',
            'website.prohibited' => 'No pudimos procesar la solicitud.',
        ]);

        $commercialRequest = $artwork->commercialRequests()->create([
            ...collect($data)->except(['consent', 'website'])->all(),
            'user_id' => auth('sanctum')->id(),
        ]);

        $admins = User::where('role', User::ROLE_ADMIN)->where('is_active', true)->get();
        // La solicitud ya quedó guardada: si el correo falla, se registra el error y el cliente
        // igual recibe la confirmación (el equipo la ve en el panel).
        try {
            Notification::send($admins, new NewCommercialRequest($commercialRequest));
            if ($commercialRequest->type === 'intencion_compra' && ($sales = config('arthub.sales_email'))) {
                Notification::route('mail', $sales)->notify(new NewCommercialRequest($commercialRequest));
            }
            Notification::route('mail', $commercialRequest->email)->notify(new CommercialRequestReceived($commercialRequest));
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' => 'Recibimos tu solicitud. Te escribiremos pronto.',
        ], 201);
    }
}
