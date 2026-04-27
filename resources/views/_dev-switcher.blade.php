@php
    $accounts = [
        ['email' => 'admissions@example.com', 'label' => 'Admission', 'role' => 'admission', 'bg' => '#d97706', 'hover' => '#b45309'],
        ['email' => 'staff1@example.com',      'label' => 'Staff 1',  'role' => 'staff1',    'bg' => '#2563eb', 'hover' => '#1d4ed8'],
        ['email' => 'staff2@example.com',       'label' => 'Staff 2', 'role' => 'staff2',    'bg' => '#7c3aed', 'hover' => '#6d28d9'],
        ['email' => 'admin@example.com',        'label' => 'Admin',   'role' => 'admin',     'bg' => '#be123c', 'hover' => '#9f1239'],
    ];
    $currentEmail = auth()->user()->email;
@endphp

<div style="
    position: fixed;
    bottom: 0; left: 0; right: 0;
    z-index: 99999;
    background: rgba(10,10,20,0.97);
    border-top: 1px solid #374151;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    font-family: ui-monospace, monospace;
    font-size: 11px;
">
    <span style="color:#6b7280; text-transform:uppercase; letter-spacing:.1em; white-space:nowrap;">⚡ dev</span>

    @foreach($accounts as $acct)
    <form method="POST" action="{{ route('dev.login') }}" style="margin:0">
        @csrf
        <input type="hidden" name="email" value="{{ $acct['email'] }}">
        <button type="submit" style="
            background: {{ $acct['bg'] }};
            color: white;
            border: {{ $currentEmail === $acct['email'] ? '2px solid white' : '2px solid transparent' }};
            border-radius: 5px;
            padding: 4px 12px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            opacity: {{ $currentEmail === $acct['email'] ? '1' : '0.65' }};
            transition: opacity .15s;
        ">{{ $acct['label'] }}{{ $currentEmail === $acct['email'] ? ' ✓' : '' }}</button>
    </form>
    @endforeach

    <span style="margin-left:auto; color:#6b7280; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:200px;">
        {{ auth()->user()->name }} · {{ auth()->user()->role }}
    </span>
</div>
<div style="height:40px"></div>
