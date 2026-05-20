<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Services\AiAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AiAssistantController extends Controller
{
    /**
     * Roles allowed to use the AI assistant.
     */
    private const ALLOWED_ROLES = ['administrator', 'program_manager'];

    public function __construct(private AiAssistantService $aiService) {}

    // ────────────────────────────────────────────────────────────────────
    // Auth
    // ────────────────────────────────────────────────────────────────────

    public function showLogin(): Response|RedirectResponse
    {
        if (Auth::check() && Auth::user()->hasAnyRole(self::ALLOWED_ROLES)) {
            return redirect()->route('ai.chat');
        }

        return Inertia::render('Ai/Login', [
            'status' => session('status'),
        ]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        if (! Auth::user()->hasAnyRole(self::ALLOWED_ROLES)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'username' => 'You are not authorized to access the AI Assistant.',
            ])->onlyInput('username');
        }

        $request->session()->regenerate();

        return redirect()->route('ai.chat');
    }

    public function logout(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Inertia::location(route('ai.login'));
    }

    // ────────────────────────────────────────────────────────────────────
    // Chat UI
    // ────────────────────────────────────────────────────────────────────

    public function showChat(): Response
    {
        $this->ensureAllowed();

        return Inertia::render('Ai/Chat', [
            'aiStatus' => self::getSharedStatus(),
        ]);
    }

    /**
     * Provider/model badge info for the layout.
     */
    public static function getSharedStatus(): array
    {
        $provider = (string) config('services.ai.provider', 'groq');
        return [
            'provider' => $provider,
            'model'    => (string) config('services.ai.' . $provider . '.model', ''),
        ];
    }

    // ────────────────────────────────────────────────────────────────────
    // Conversations REST
    // ────────────────────────────────────────────────────────────────────

    public function listConversations(): JsonResponse
    {
        $this->ensureAllowed();

        $userId = Auth::id();

        $items = AiConversation::where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get(['id', 'title', 'updated_at']);

        $usageToday = AiMessage::where('role', 'user')
            ->whereDate('created_at', now()->toDateString())
            ->whereIn('conversation_id', AiConversation::where('user_id', $userId)->pluck('id'))
            ->count();

        return response()->json([
            'conversations' => $items,
            'usage_today'   => $usageToday,
        ]);
    }

    public function showConversation(int $id): JsonResponse
    {
        $this->ensureAllowed();

        $convo = AiConversation::with('messages:id,conversation_id,role,content,created_at')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json(['conversation' => $convo]);
    }

    public function deleteConversation(int $id): JsonResponse
    {
        $this->ensureAllowed();

        AiConversation::where('user_id', Auth::id())->where('id', $id)->delete();

        return response()->json(['ok' => true]);
    }

    // ────────────────────────────────────────────────────────────────────
    // Send message (with persistence)
    // ────────────────────────────────────────────────────────────────────

    public function sendMessage(Request $request): JsonResponse
    {
        $this->ensureAllowed();

        $validated = $request->validate([
            'message'         => ['required', 'string', 'max:4000'],
            'conversation_id' => ['nullable', 'integer', 'exists:ai_conversations,id'],
        ]);

        $userId = Auth::id();

        $convo = null;
        if (! empty($validated['conversation_id'])) {
            $convo = AiConversation::where('user_id', $userId)
                ->where('id', $validated['conversation_id'])
                ->first();
        }
        if (! $convo) {
            $convo = AiConversation::create([
                'user_id' => $userId,
                'title'   => Str::limit(trim($validated['message']), 60, '…'),
            ]);
        }

        AiMessage::create([
            'conversation_id' => $convo->id,
            'role'            => 'user',
            'content'         => $validated['message'],
            'created_at'      => now(),
        ]);

        $history = $convo->messages()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['role', 'content'])
            ->reverse()
            ->values()
            ->map(fn($m) => ['role' => $m->role, 'content' => $m->content])
            ->toArray();

        $reply = $this->aiService->chat($history);

        AiMessage::create([
            'conversation_id' => $convo->id,
            'role'            => 'assistant',
            'content'         => $reply,
            'created_at'      => now(),
        ]);

        $convo->touch();

        return response()->json([
            'conversation_id' => $convo->id,
            'title'           => $convo->title,
            'reply'           => $reply,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────

    private function ensureAllowed(): void
    {
        abort_unless(Auth::user()?->hasAnyRole(self::ALLOWED_ROLES), 403);
    }
}
