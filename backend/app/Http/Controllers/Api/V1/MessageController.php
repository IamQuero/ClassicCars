<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreMessageRequest;
use App\Http\Resources\V1\MessageResource;
use App\Models\Listing;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MessageController extends Controller
{
    /** Escribe sobre un anuncio. El vendedor responde indicando a quién. */
    public function store(StoreMessageRequest $request, Listing $listing): JsonResponse
    {
        if ($listing->status === 'draft') {
            throw new NotFoundHttpException;
        }

        $autor = $request->user();
        $destinatario = $autor->id === $listing->seller_id
            ? $this->compradorDeLaConversacion($request, $listing)
            : $listing->seller_id;

        $mensaje = Message::create([
            'sender_id' => $autor->id,
            'receiver_id' => $destinatario,
            'listing_id' => $listing->id,
            'message' => $request->validated('message'),
        ]);

        $mensaje->load(['sender', 'receiver']);

        return (new MessageResource($mensaje))->response()->setStatusCode(201);
    }

    /** Una entrada por conversación (anuncio + interlocutor) con su último mensaje. */
    public function conversations(Request $request): JsonResponse
    {
        $yo = $request->user()->id;

        $mensajes = Message::query()
            ->with(['sender', 'receiver', 'listing.car', 'listing.photos'])
            ->where(fn ($q) => $q->where('sender_id', $yo)->orWhere('receiver_id', $yo))
            ->latest('id')
            ->get();

        $sinLeer = Message::query()
            ->where('receiver_id', $yo)
            ->whereNull('read_at')
            ->get(['listing_id', 'sender_id'])
            ->countBy(fn (Message $m) => $m->listing_id.'-'.$m->sender_id);

        $conversaciones = $mensajes
            ->groupBy(fn (Message $m) => $m->listing_id.'-'.min($m->sender_id, $m->receiver_id).'-'.max($m->sender_id, $m->receiver_id))
            ->map(function ($grupo) use ($yo, $sinLeer, $request) {
                /** @var Message $ultimo */
                $ultimo = $grupo->first();
                $otro = $ultimo->sender_id === $yo ? $ultimo->receiver : $ultimo->sender;

                return [
                    'listing' => [
                        'id' => $ultimo->listing->id,
                        'title' => $ultimo->listing->car->brand.' '.$ultimo->listing->car->model,
                    ],
                    'with' => [
                        'id' => $otro->id,
                        'name' => $otro->name,
                    ],
                    'last_message' => (new MessageResource($ultimo))->toArray($request),
                    'unread' => $sinLeer->get($ultimo->listing_id.'-'.$otro->id, 0),
                ];
            })
            ->values();

        return response()->json(['data' => $conversaciones]);
    }

    /** El hilo con un interlocutor sobre un anuncio; al abrirlo se marca como leído. */
    public function thread(Request $request, Listing $listing, User $user): AnonymousResourceCollection
    {
        $yo = $request->user()->id;

        Message::where('listing_id', $listing->id)
            ->where('sender_id', $user->id)
            ->where('receiver_id', $yo)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $mensajes = Message::query()
            ->with(['sender', 'receiver'])
            ->where('listing_id', $listing->id)
            ->whereIn('sender_id', [$yo, $user->id])
            ->whereIn('receiver_id', [$yo, $user->id])
            ->oldest('id')
            ->paginate(30);

        return MessageResource::collection($mensajes);
    }

    /**
     * El vendedor solo puede responder a alguien que ya le haya escrito por
     * ese anuncio: así no se puede usar la API para escribir a desconocidos.
     */
    private function compradorDeLaConversacion(Request $request, Listing $listing): int
    {
        $destinatario = (int) $request->input('receiver_id');

        $existe = Message::where('listing_id', $listing->id)
            ->where('sender_id', $destinatario)
            ->where('receiver_id', $request->user()->id)
            ->exists();

        if (! $existe) {
            throw ValidationException::withMessages([
                'receiver_id' => 'Como vendedor solo puedes responder a quien te haya escrito por este anuncio.',
            ]);
        }

        return $destinatario;
    }
}
