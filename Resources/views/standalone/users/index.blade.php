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
                <a href="{{ route('users.import-template') }}" class="btn btn-default btn-sm"><i class="fa fa-download"></i> Template</a>
                <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#usersImportModal"><i class="fa fa-upload"></i> Import</button>
            @endcan
            @can('user.view')
                <a href="{{ route('users.export') }}" class="btn btn-default btn-sm"><i class="fa fa-file-excel-o"></i> Export</a>
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
        @php
            $status = session('status');
            $statusMessage = is_array($status) ? data_get($status, 'msg') : $status;
            $statusSuccess = is_array($status) ? data_get($status, 'success', 1) : 1;
        @endphp
        @if($statusMessage)
            <div class="alert alert-{{ $statusSuccess ? 'success' : 'warning' }}">{{ $statusMessage }}</div>
        @endif

        <form method="GET" action="{{ route('users.index') }}" class="pos-filter-grid pos-filter-grid-users">
            <div><input class="form-control" name="name" placeholder="Search name" value="{{ request('name') }}"></div>
            <div><input class="form-control" name="username" placeholder="Username" value="{{ request('username') }}"></div>
            <div><input class="form-control" name="email" placeholder="Email address" value="{{ request('email') }}"></div>
            <div>
                <select class="form-control" name="role">
                    <option value="">All Roles</option>
                    @foreach($roles as $id => $name)
                        <option value="{{ $id }}" {{ (string) request('role') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
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

    @can('user.create')
        <div class="modal fade" id="usersImportModal" tabindex="-1" role="dialog" aria-labelledby="usersImportModalLabel">
            <div class="modal-dialog" role="document">
                <form method="POST" action="{{ route('users.import') }}" enctype="multipart/form-data" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="usersImportModalLabel"><i class="fa fa-upload"></i> Import Users</h4>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            Upload a CSV file using the downloaded template. Roles are matched by role name.
                        </div>
                        <div class="form-group">
                            <label>CSV File</label>
                            <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Import Mode</label>
                                    <select name="mode" class="form-control">
                                        <option value="insert">Insert Only</option>
                                        <option value="update">Update Existing</option>
                                        <option value="upsert">Insert &amp; Update</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Default Password</label>
                                    <input type="text" name="default_password" class="form-control" value="12345678" minlength="6">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> Import Users</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
</div>
@endsection
