{{-- Local-only developer role switcher. Never shown in production. --}}
<div class="bg-gray-900 p-4 rounded-lg shadow-lg">
    <h4 class="text-white text-xs font-bold uppercase tracking-wider mb-3">Developer Quick Switch</h4>
    <div class="flex flex-wrap gap-3">
        <form action="{{ route('dev.login') }}" method="POST">
            @csrf
            <input type="hidden" name="email" value="admissions@unikl.edu.my">
            <button type="submit"
                    class="bg-gray-600 hover:bg-gray-500 text-white px-3 py-1 rounded text-xs {{ auth()->user()->role === 'admission' ? 'ring-2 ring-white' : '' }}">
                Become: Admission
            </button>
        </form>
        <form action="{{ route('dev.login') }}" method="POST">
            @csrf
            <input type="hidden" name="email" value="staff1@unikl.edu.my">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1 rounded text-xs {{ auth()->user()->role === 'staff1' ? 'ring-2 ring-white' : '' }}">
                Become: Staff 1
            </button>
        </form>
        <form action="{{ route('dev.login') }}" method="POST">
            @csrf
            <input type="hidden" name="email" value="staff2@unikl.edu.my">
            <button type="submit"
                    class="bg-purple-600 hover:bg-purple-500 text-white px-3 py-1 rounded text-xs {{ auth()->user()->role === 'staff2' ? 'ring-2 ring-white' : '' }}">
                Become: Staff 2
            </button>
        </form>
        <form action="{{ route('dev.login') }}" method="POST">
            @csrf
            <input type="hidden" name="email" value="admin@unikl.edu.my">
            <button type="submit"
                    class="bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded text-xs {{ auth()->user()->role === 'admin' ? 'ring-2 ring-white' : '' }}">
                Become: Admin
            </button>
        </form>
    </div>
    <p class="text-gray-400 text-[10px] mt-2 italic">Current role: {{ auth()->user()->role }}</p>
</div>
