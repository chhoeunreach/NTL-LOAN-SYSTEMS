@extends('loanmanagement::layouts.app')

@section('title', 'Roles')

@section('loan_css')
    @include('standalone.partials.admin_ui_css')
@endsection

@section('content_body')
<div class="pos-admin-page">
    <div class="pos-page-head">
        <div class="pos-page-title">
            <h1>Roles</h1>
            <p>Control staff access by assigning grouped system permissions.</p>
        </div>
        <div class="pos-action-row">
            @can('user.view')
                <a href="{{ route('users.index') }}" class="btn btn-default btn-sm"><i class="fa fa-users"></i> Users</a>
            @endcan
            @can('roles.create')
                <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add Role</a>
            @endcan
        </div>
    </div>

    <div class="pos-stat-strip">
        <div class="pos-stat"><span>Total Roles</span><strong>{{ number_format($stats['total'] ?? 0) }}</strong></div>
        <div class="pos-stat"><span>Assigned Users</span><strong>{{ number_format($stats['assigned'] ?? 0) }}</strong></div>
        <div class="pos-stat"><span>Permissions</span><strong>{{ number_format($stats['permissions'] ?? 0) }}</strong></div>
    </div>

    <div class="pos-panel">
        <div class="pos-panel-head">
            <h3>Role List</h3>
            <span class="pos-muted">{{ number_format($roles->total()) }} result{{ $roles->total() === 1 ? '' : 's' }}</span>
        </div>
        <div class="pos-panel-body">
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover pos-data-table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Users</th>
                        <th>Permissions</th>
                        <th style="width:160px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                        <tr>
                            <td>
                                <div class="pos-user-cell">
                                    <span class="pos-avatar"><i class="fa fa-shield"></i></span>
                                    <div>
                                        <strong>{{ $role->name }}</strong>
                                        <span>Guard: {{ $role->guard_name }}</span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="pos-badge pos-badge-muted">{{ number_format($role->users_count) }}</span></td>
                            <td><span class="pos-badge pos-badge-success">{{ number_format($role->permissions_count) }}</span></td>
                            <td>
                                @can('roles.update')
                                    <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i> Edit</a>
                                @endcan
                                @can('roles.delete')
                                    <form method="POST" action="{{ route('roles.destroy', $role->id) }}" style="display:inline;" onsubmit="return confirm('Delete this role?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-xs btn-danger" type="submit" {{ $role->users_count ? 'disabled' : '' }}><i class="fa fa-trash"></i> Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">No roles found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $roles->links() }}
        </div>
    </div>
</div>
@endsection
