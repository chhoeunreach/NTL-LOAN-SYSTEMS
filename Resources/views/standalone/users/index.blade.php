@extends('loanmanagement::layouts.app')

@section('title', 'Users')

@section('loan_css')
    @include('standalone.partials.admin_ui_css')
@endsection

@section('content_body')
<div class="pos-admin-page">
    <div class="pos-page-head">
        <div class="pos-page-title">
            <h1>Users</h1>
            <p>Manage staff access, login status, and assigned roles.</p>
        </div>
        <div class="pos-action-row">
            @can('roles.view')
                <a href="{{ route('roles.index') }}" class="btn btn-default btn-sm"><i class="fa fa-shield"></i> Roles</a>
            @endcan
            @can('user.create')
                <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add User</a>
            @endcan
        </div>
    </div>

    <div class="pos-stat-strip">
        <div class="pos-stat"><span>Total Users</span><strong>{{ number_format($stats['total'] ?? 0) }}</strong></div>
        <div class="pos-stat"><span>Active</span><strong>{{ number_format($stats['active'] ?? 0) }}</strong></div>
        <div class="pos-stat"><span>Inactive</span><strong>{{ number_format($stats['inactive'] ?? 0) }}</strong></div>
        <div class="pos-stat"><span>Login Enabled</span><strong>{{ number_format($stats['login_enabled'] ?? 0) }}</strong></div>
    </div>

    <div class="pos-panel">
        <div class="pos-panel-head">
            <h3>User List</h3>
            <span class="pos-muted">{{ number_format($users->total()) }} result{{ $users->total() === 1 ? '' : 's' }}</span>
        </div>
        <div class="pos-panel-body">
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="GET" action="{{ route('users.index') }}" class="pos-filter-grid">
            <div><input class="form-control" name="name" placeholder="Search name" value="{{ request('name') }}"></div>
            <div><input class="form-control" name="username" placeholder="Username" value="{{ request('username') }}"></div>
            <div><input class="form-control" name="email" placeholder="Email address" value="{{ request('email') }}"></div>
            <div>
                <select class="form-control" name="status">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <button class="btn btn-default" type="submit"><i class="fa fa-search"></i> Filter</button>
            <a href="{{ route('users.index') }}" class="btn btn-link">Reset</a>
        </form>

        <div class="table-responsive">
            <table class="table table-hover pos-data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Login</th>
                        <th>Status</th>
                        <th style="width:230px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $displayName = $user->name ?: trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
                            $initial = strtoupper(mb_substr($displayName ?: $user->username, 0, 1));
                        @endphp
                        <tr>
                            <td>
                                <div class="pos-user-cell">
                                    <span class="pos-avatar">{{ $initial }}</span>
                                    <div>
                                        <strong>{{ $displayName ?: '-' }}</strong>
                                        <span>{{ $user->username }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->relationLoaded('roles') ? ($user->roles->pluck('name')->implode(', ') ?: '-') : '-' }}</td>
                            <td>
                                <span class="pos-badge {{ !empty($user->allow_login) ? 'pos-badge-success' : 'pos-badge-muted' }}">
                                    {{ !empty($user->allow_login) ? 'Allowed' : 'Blocked' }}
                                </span>
                            </td>
                            <td>
                                <span class="pos-badge {{ ($user->status ?? 'active') === 'active' ? 'pos-badge-success' : 'pos-badge-muted' }}">
                                    {{ ucfirst($user->status ?? 'active') }}
                                </span>
                            </td>
                            <td>
                                @can('user.update')
                                    <a href="{{ route('users.edit', $user->id) }}" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i> Edit</a>
                                    @if(auth()->id() !== $user->id)
                                        <form method="POST" action="{{ route('users.toggle-status', $user->id) }}" style="display:inline;">
                                            @csrf
                                            <button class="btn btn-xs btn-warning" type="submit">
                                                <i class="fa fa-power-off"></i> {{ ($user->status ?? 'active') === 'active' ? 'Disable' : 'Enable' }}
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                                @can('user.delete')
                                    @if(auth()->id() !== $user->id)
                                        <form method="POST" action="{{ route('users.destroy', $user->id) }}" style="display:inline;" onsubmit="return confirm('Delete this user?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-xs btn-danger" type="submit"><i class="fa fa-trash"></i> Delete</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
