<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\POS\CloseSessionRequest;
use App\Http\Requests\POS\OpenSessionRequest;
use App\Models\Outlet;
use App\Models\PosSession;
use App\Services\PosSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function __construct(private PosSessionService $sessionService) {}

    public function index(): View
    {
        $user = auth()->user();
        $outlets = Outlet::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->get();

        $sessions = PosSession::whereIn('outlet_id', $outlets->pluck('id'))
            ->with(['outlet', 'user', 'closedByUser'])
            ->orderByDesc('opened_at')
            ->paginate(15);

        return view('pos.sessions.index', [
            'sessions' => $sessions,
            'outlets' => $outlets,
        ]);
    }

    public function openForm(): View
    {
        $user = auth()->user();
        $outlets = Outlet::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->get();

        return view('pos.sessions.open', [
            'outlets' => $outlets,
        ]);
    }

    public function open(OpenSessionRequest $request): RedirectResponse
    {
        try {
            $session = $this->sessionService->openSession(
                $request->outlet_id,
                auth()->id(),
                $request->opening_cash,
                $request->opening_notes
            );

            return redirect()->route('pos.index')
                ->with('success', 'Session opened successfully. Session: '.$session->session_number);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function closeForm(PosSession $session): View
    {
        $this->authorizeSession($session);

        if (! $session->isOpen()) {
            abort(403, 'Session is already closed.');
        }

        $report = $this->sessionService->getSettlementReport($session);

        return view('pos.sessions.close', [
            'session' => $session,
            'report' => $report,
        ]);
    }

    public function close(CloseSessionRequest $request, PosSession $session): RedirectResponse
    {
        $this->authorizeSession($session);

        try {
            $this->sessionService->closeSession(
                $session,
                $request->closing_cash,
                auth()->id(),
                $request->closing_notes
            );

            return redirect()->route('pos.sessions.report', $session)
                ->with('success', 'Session closed successfully.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function report(PosSession $session): View
    {
        $this->authorizeSession($session);

        $report = $this->sessionService->getSettlementReport($session);

        return view('pos.sessions.report', [
            'session' => $session,
            'report' => $report,
        ]);
    }

    private function authorizeSession(PosSession $session): void
    {
        $user = auth()->user();
        $outlet = Outlet::find($session->outlet_id);

        if (! $outlet || $outlet->tenant_id !== $user->tenant_id) {
            abort(403);
        }
    }
}
