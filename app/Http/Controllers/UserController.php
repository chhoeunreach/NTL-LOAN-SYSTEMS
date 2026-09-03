<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeUserAccess('user.view');

        $hasRoleTables = Schema::hasTable('roles') && Schema::hasTable('model_has_roles');
        $baseQuery = User::query()
            ->when($hasRoleTables, fn ($q) => $q->with('roles'))
            ->when(Schema::hasColumn('users', 'business_id'), fn ($q) => $q->where('business_id', $this->businessId()));

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => Schema::hasColumn('users', 'status') ? (clone $baseQuery)->where('status', 'active')->count() : 0,
            'inactive' => Schema::hasColumn('users', 'status') ? (clone $baseQuery)->where('status', 'inactive')->count() : 0,
            'login_enabled' => Schema::hasColumn('users', 'allow_login') ? (clone $baseQuery)->where('allow_login', 1)->count() : 0,
        ];

        $query = (clone $baseQuery)->orderByDesc('id');

        foreach (['name', 'username', 'email', 'status'] as $field) {
            if ($request->filled($field)) {
                $value = $request->input($field);
                $field === 'status'
                    ? $query->where($field, $value)
                    : $query->where($field, 'like', '%'.$value.'%');
            }
        }

        $users = $query->paginate(20)->appends($request->query());

        return view('standalone.users.index', compact('users', 'stats'));
    }

    public function create()
    {
        $this->authorizeUserAccess('user.create');

        return view('standalone.users.create', [
            'roles' => $this->rolesForSelect(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeUserAccess('user.create');

        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'username' => 'required|string|max:191|unique:users,username',
            'email' => 'required|email|max:191|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'business_id' => 'nullable|integer|min:1',
            'allow_login' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
            'role' => 'nullable|integer|exists:roles,id',
        ]);

        $user = User::create([
            'name' => trim($data['first_name'].' '.($data['last_name'] ?? '')),
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name'] ?? ''),
            'username' => trim($data['username']),
            'email' => trim($data['email']),
            'password' => Hash::make($data['password']),
            'business_id' => $data['business_id'] ?? (auth()->user()->business_id ?? 1),
            'allow_login' => $request->boolean('allow_login'),
            'status' => $data['status'],
        ]);

        $this->syncRole($user, $data['role'] ?? null);

        return redirect()->route('users.index')
            ->with('status', ['success' => 1, 'msg' => 'User created successfully.']);
    }

    public function edit(User $user)
    {
        $this->authorizeUserAccess('user.update');
        $this->abortIfOutsideBusiness($user);

        return view('standalone.users.edit', [
            'userRow' => Schema::hasTable('model_has_roles') ? $user->load('roles') : $user,
            'roles' => $this->rolesForSelect(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeUserAccess('user.update');
        $this->abortIfOutsideBusiness($user);

        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'username' => 'required|string|max:191|unique:users,username,'.$user->id,
            'email' => 'required|email|max:191|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'business_id' => 'nullable|integer|min:1',
            'allow_login' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
            'role' => 'nullable|integer|exists:roles,id',
        ]);

        $payload = [
            'name' => trim($data['first_name'].' '.($data['last_name'] ?? '')),
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name'] ?? ''),
            'username' => trim($data['username']),
            'email' => trim($data['email']),
            'business_id' => $data['business_id'] ?? ($user->business_id ?: 1),
            'allow_login' => $request->boolean('allow_login'),
            'status' => $data['status'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);
        $this->syncRole($user, $data['role'] ?? null);

        return redirect()->route('users.index')
            ->with('status', ['success' => 1, 'msg' => 'User updated successfully.']);
    }

    public function destroy(User $user)
    {
        $this->authorizeUserAccess('user.delete');
        $this->abortIfOutsideBusiness($user);

        abort_if(auth()->id() === $user->id, 422, 'You cannot delete your own account.');

        $user->delete();

        return redirect()->route('users.index')
            ->with('status', ['success' => 1, 'msg' => 'User deleted successfully.']);
    }

    public function toggleStatus(User $user)
    {
        $this->authorizeUserAccess('user.update');
        $this->abortIfOutsideBusiness($user);

        abort_if(auth()->id() === $user->id, 422, 'You cannot disable your own account.');

        $user->update([
            'status' => ($user->status ?? 'active') === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('status', ['success' => 1, 'msg' => 'User status updated.']);
    }

    public function resetPassword(Request $request, User $user)
    {
        $this->authorizeUserAccess('user.update');
        $this->abortIfOutsideBusiness($user);

        $data = $request->validate([
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user->update(['password' => Hash::make($data['new_password'])]);

        return back()->with('status', ['success' => 1, 'msg' => 'Password reset successfully.']);
    }

    protected function authorizeUserAccess(string $permission): void
    {
        abort_unless(auth()->check() && auth()->user()->can($permission), 403, 'Unauthorized action.');
    }

    protected function rolesForSelect()
    {
        return Schema::hasTable('roles')
            ? Role::query()
                ->when(Schema::hasColumn('roles', 'business_id'), fn ($query) => $query->where('business_id', $this->businessId()))
                ->orderBy('name')
                ->pluck('name', 'id')
            : collect();
    }

    protected function syncRole(User $user, $roleId): void
    {
        if (! Schema::hasTable('model_has_roles')) {
            return;
        }

        if (! $roleId || ! Schema::hasTable('roles')) {
            $user->syncRoles([]);
            return;
        }

        $role = Role::query()
            ->whereKey($roleId)
            ->when(Schema::hasColumn('roles', 'business_id'), fn ($query) => $query->where('business_id', $this->businessId()))
            ->first();

        if ($role) {
            $user->syncRoles([$role]);
        }
    }

    protected function businessId(): int
    {
        return (int) (session('user.business_id') ?? auth()->user()->business_id ?? 1);
    }

    protected function abortIfOutsideBusiness(User $user): void
    {
        if (Schema::hasColumn('users', 'business_id')) {
            abort_unless((int) $user->business_id === $this->businessId(), 404);
        }
    }
}
