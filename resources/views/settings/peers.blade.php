@extends('layouts.app')

@section('title', 'Sync Peers')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">Sync Peers</h1>

    <form method="POST" action="{{ route('settings.peers.store') }}" class="card p-4 mb-6">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" name="name" placeholder="Name (optional)" class="input">
            <input type="text" name="ip_address" placeholder="IP address (e.g. 192.168.1.11)" required class="input">
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" checked class="rounded">
                <label>Active</label>
                <button type="submit" class="btn-primary ml-auto">Add Peer</button>
            </div>
        </div>
    </form>

    <div class="card overflow-hidden">
        <table class="w-full">
            <thead class="bg-slate-800">
                <tr><th>Name</th><th>IP</th><th>Status</th><th>Last seen</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                @foreach($peers as $peer)
                <tr class="border-b border-slate-800">
                    <td class="p-3">{{ $peer->name ?? '—' }}</td>
                    <td class="p-3">{{ $peer->ip_address }}</td>
                    <td class="p-3">
                        <span class="badge {{ $peer->is_active ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                            {{ $peer->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="p-3">{{ $peer->last_seen ? $peer->last_seen->diffForHumans() : 'never' }}</td>
                    <td class="p-3 text-right">
                        <button onclick="editPeer({{ $peer->id }}, '{{ $peer->name }}', '{{ $peer->ip_address }}', {{ $peer->is_active ? 1 : 0 }})"
                                class="text-blue-400 hover:underline text-sm mr-2">Edit</button>
                        <form method="POST" action="{{ route('settings.peers.destroy', $peer) }}" class="inline" onsubmit="return confirm('Remove this peer?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-400 hover:underline text-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
function editPeer(id, name, ip, isActive) {
    const html = `
        <div id="editPeerModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50">
            <div class="card p-6 w-96">
                <h3 class="text-lg font-bold mb-4">Edit Peer</h3>
                <form method="POST" action="/settings/peers/${id}">
                    @csrf @method('PUT')
                    <input type="text" name="name" value="${name ? name.replace(/"/g, '&quot;') : ''}" placeholder="Name" class="input w-full mb-3">
                    <input type="text" name="ip_address" value="${ip}" required class="input w-full mb-3">
                    <label class="flex items-center gap-2 mb-4">
                        <input type="checkbox" name="is_active" value="1" ${isActive ? 'checked' : ''}>
                        <span>Active</span>
                    </label>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="closeEditModal()" class="btn-secondary">Cancel</button>
                        <button type="submit" class="btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', html);
}
function closeEditModal() { document.getElementById('editPeerModal')?.remove(); }
</script>
@endsection