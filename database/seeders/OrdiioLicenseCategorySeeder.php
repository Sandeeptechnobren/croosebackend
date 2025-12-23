<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrdiioLicenseCategory;
use Illuminate\Support\Str;

class OrdiioLicenseCategorySeeder extends Seeder
{
    public function run()
    {
        $entries = [
            ['name'=>'Creator','region'=>'africa','stripe_price_id'=>'price_1S01JaPHX6P7B0mlYzWMWVaX','license_cost'=>0,'currency'=>'usd','duration'=>'1 year'],
            ['name'=>'Creator','region'=>'general','stripe_price_id'=>'price_1S00PGPHX6P7B0mlAzA6It0v','license_cost'=>0,'currency'=>'usd','duration'=>'1 year'],
            ['name'=>'Freelancer','region'=>'africa','stripe_price_id'=>'price_1S01IUPHX6P7B0mlI0apY9jb','license_cost'=>0,'currency'=>'usd','duration'=>'1 year'],
            ['name'=>'Freelancer','region'=>'general','stripe_price_id'=>'price_1S00VePHX6P7B0mlZuX5ezmM','license_cost'=>0,'currency'=>'usd','duration'=>'1 year'],
            ['name'=>'Brand','region'=>'africa','stripe_price_id'=>'price_1S01HRPHX6P7B0ml6eFtZJrk','license_cost'=>0,'currency'=>'usd','duration'=>'1 year'],
            ['name'=>'Brand','region'=>'general','stripe_price_id'=>'price_1S00WIPHX6P7B0mlaLmiBeHB','license_cost'=>0,'currency'=>'usd','duration'=>'1 year'],
            ['name'=>'Broadcast','region'=>'africa','stripe_price_id'=>'price_1S01GXPHX6P7B0mlJZuISueu','license_cost'=>0,'currency'=>'usd','duration'=>'1 year'],
            ['name'=>'Broadcast','region'=>'general','stripe_price_id'=>'price_1S00XAPHX6P7B0ml74TWApPZ','license_cost'=>0,'currency'=>'usd','duration'=>'1 year']
        ];
        foreach ($entries as $e) {
            OrdiioLicenseCategory::updateOrCreate(
                ['name'=>$e['name'],'region'=>$e['region']],
                ['uuid'=>Str::uuid(),'stripe_price_id'=>$e['stripe_price_id'],'license_cost'=>$e['license_cost'],'currency'=>$e['currency'],'duration'=>$e['duration']]
            );
        }
    }
}
