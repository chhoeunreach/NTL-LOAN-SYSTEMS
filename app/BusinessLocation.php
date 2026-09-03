<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;

class BusinessLocation extends Model
{
    /**
     * The database connection used by the model.
     *
     * @var string
     */
    protected $connection = 'mysql_loan';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'loan_business_locations';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    /**
     * Return list of locations for a business
     *
     * @param  int  $business_id
     * @param  bool  $show_all = false
     * @param  array  $receipt_printer_type_attribute =
     * @return array
     */
    public static function forDropdown($business_id, $show_all = false, $receipt_printer_type_attribute = false, $append_id = true, $check_permission = true)
    {
        $query = BusinessLocation::Active();

        if ($check_permission) {
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('id', $permitted_locations);
            }
        }

        if ($append_id) {
            $query->select(
                DB::raw("IF(location_code IS NULL OR location_code='', name, CONCAT(name, ' (', location_code, ')')) AS name"),
                'id'
            );
        }

        $result = $query->get();

        $locations = $result->pluck('name', 'id');

        if ($show_all) {
            $locations->prepend(__('report.all_locations'), '');
        }

        return $locations;
    }

    /**
     * Scope a query to only include active location.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->whereNull('deleted_at');
    }

    public function getLocationAddressAttribute()
    {
        $location = $this;
        $address = [];
        if (! empty($location->address)) {
            $address[] = $location->address;
        }
        if (! empty($location->phone)) {
            $address[] = $location->phone;
        }

        return implode(', ', $address);
    }
}