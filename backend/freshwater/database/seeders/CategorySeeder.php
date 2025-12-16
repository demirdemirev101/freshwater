<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | ROOT CATEGORIES
        |--------------------------------------------------------------------------
        */
        $home = Category::create([
            'name' => 'За дома',
            'slug' => 'za-doma',
            'parent_id' => null,
        ]);

        $business = Category::create([
            'name' => 'За бизнеса',
            'slug' => 'za-biznesa',
            'parent_id' => null,
        ]);

        /*
        |--------------------------------------------------------------------------
        | ЗА ДОМА – НИВО 2
        |--------------------------------------------------------------------------
        */
        Category::create([
            'name' => 'Системи за вода',
            'slug' => 'za-doma-sistemi-za-voda',
            'parent_id' => $home->id,
        ]);

         Category::create([
            'name' => 'Водородна вода',
            'slug' => 'za-doma-vodorodna-voda',
            'parent_id' => $home->id,
        ]);

        $homeAccessories = Category::create([
            'name' => 'Аксесоари',
            'slug' => 'za-doma-aksesoari',
            'parent_id' => $home->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | ЗА ДОМА → АКСЕСОАРИ – НИВО 3
        |--------------------------------------------------------------------------
        */
        Category::create([
            'name' => 'Бутилки',
            'slug' => 'za-doma-butilki',
            'parent_id' => $homeAccessories->id,
        ]);

        Category::create([
            'name' => 'Смесители',
            'slug' => 'za-doma-smesiteli',
            'parent_id' => $homeAccessories->id,
        ]);

        Category::create([
            'name' => 'Филтри и консумативи',
            'slug' => 'za-doma-filtri-i-konsumativi',
            'parent_id' => $homeAccessories->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | ЗА БИЗНЕСА – НИВО 2
        |--------------------------------------------------------------------------
        */
        Category::create([
            'name' => 'Системи за вода',
            'slug' => 'za-biznesa-sistemi-za-voda',
            'parent_id' => $business->id,
        ]);

        Category::create([
            'name' => 'ХоРеКа',
            'slug' => 'za-biznesa-horeka',
            'parent_id' => $business->id,
        ]);

        $businessAccessories = Category::create([
            'name' => 'Аксесоари',
            'slug' => 'za-biznesa-aksesoari',
            'parent_id' => $business->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | ЗА БИЗНЕСА → АКСЕСОАРИ – НИВО 3
        |--------------------------------------------------------------------------
        */
        Category::create([
            'name' => 'Филтри и консумативи',
            'slug' => 'za-biznesa-filtri-i-konsumativi',
            'parent_id' => $businessAccessories->id,
        ]);

        Category::create([
            'name' => 'Бутилки',
            'slug' => 'za-biznesa-butilki',
            'parent_id' => $businessAccessories->id,
        ]);

        Category::create([
            'name' => 'Водни колони',
            'slug' => 'za-biznesa-vodni-koloni',
            'parent_id' => $businessAccessories->id,
        ]);

        Category::create([
            'name' => 'Шкафове',
            'slug' => 'za-biznesa-shkafove',
            'parent_id' => $businessAccessories->id,
        ]);

        Category::create([
            'name' => 'Допълнително оборудване',
            'slug' => 'za-biznesa-dopalnitelno-oborudvane',
            'parent_id' => $businessAccessories->id,
        ]);
    }
}
