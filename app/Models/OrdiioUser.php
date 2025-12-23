<<<<<<< HEAD
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Cashier\Billable;
class OrdiioUser extends Model
{
    use HasApiTokens, HasFactory, Notifiable,Billable;
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'is_subscriber',
        'company_type',
        'valid_from',
        'valid_to',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
}
||||||| parent of b872fe7 (Live code)
=======
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Cashier\Billable;
class OrdiioUser extends Model
{
    use HasApiTokens, HasFactory, Notifiable,Billable;
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'is_subscriber',
        'company_type',
        'valid_from',
        'valid_to',
        'type',
        'status',
        'verification_token',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
}
>>>>>>> b872fe7 (Live code)
