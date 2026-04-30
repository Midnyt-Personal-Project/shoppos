<?php

namespace App\Http\Controllers;

use App\Models\Peer;
use Illuminate\Http\Request;

class PeerController extends Controller
{
    public function index()
    {
        $peers = Peer::orderBy('ip_address')->get();
        return view('settings.peers', compact('peers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'ip_address' => 'required|ip',
            'is_active' => 'boolean',
        ]);
        Peer::create($data);
        return redirect()->back()->with('success', 'Peer added.');
    }

    public function update(Request $request, Peer $peer)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'ip_address' => 'required|ip',
            'is_active' => 'boolean',
        ]);
        $peer->update($data);
        return redirect()->back()->with('success', 'Peer updated.');
    }

    public function destroy(Peer $peer)
    {
        $peer->delete();
        return redirect()->back()->with('success', 'Peer removed.');
    }
}