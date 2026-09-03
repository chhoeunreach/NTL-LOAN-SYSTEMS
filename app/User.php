<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasRoles;
    use Notifiable;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'username',
        'email',
        'password',
        'business_id',
        'allow_login',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function can($abilities, $arguments = []): bool
    {
        if ($this->hasRole('Admin')) {
            return true;
        }

        return parent::can($abilities, $arguments);
    }

    /**
     * Return the locations a user is permitted to access.
     *
     * @return string|array ("all" when the user may access every location)
     */
    public function permitted_locations($business_id = null)
    {
        $user = $this;

        if ($user->can('access_all_locations')) {
            return 'all';
        }

        $permitted_locations = [];
        try {
            $all_locations = BusinessLocation::Active()->get();
        } catch (\Throwable $e) {
            $all_locations = collect();
        }

        $permissions = $user->permissions->pluck('name')->all();
        foreach ($all_locations as $location) {
            if (in_array('location.'.$location->id, $permissions)) {
                $permitted_locations[] = $location->id;
            }
        }

        return $permitted_locations;
    }

    public static function forDropdown($business_id, $prepend_none = true, $include_commission_agents = false, $prepend_all = false, $check_location_permission = false)
    {
        $users = DB::table('users')
            ->selectRaw("id, TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) as name")
            ->when(! empty($business_id), fn ($query) => $query->where('business_id', $business_id))
            ->orderBy('first_name')
            ->pluck('name', 'id');

        if ($prepend_none) {
            $users->prepend(__('lang_v1.none'), '');
        }

        if ($prepend_all) {
            $users->prepend(__('report.all'), '');
        }

        return $users;
    }
}
