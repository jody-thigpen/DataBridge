<?php

namespace Database\Seeders;

use App\Enums\SearchTypeCode;
use App\Models\DataSource;
use App\Models\SearchType;
use Illuminate\Database\Seeder;

class SearchTypeSeeder extends Seeder
{
    public function run(): void
    {
        $dataSource = DataSource::query()->where('slug', 'informdata-monitoring')->first();

        if ($dataSource === null) {
            return;
        }

        $definitions = [
            [
                'code' => SearchTypeCode::CountyCriminal,
                'sort_order' => 10,
            ],
            [
                'code' => SearchTypeCode::NationalCriminal,
                'sort_order' => 20,
            ],
            [
                'code' => SearchTypeCode::SocialSecurityTrace,
                'sort_order' => 30,
            ],
        ];

        foreach ($definitions as $definition) {
            $code = $definition['code'];

            SearchType::query()->updateOrCreate(
                ['code' => $code->value],
                [
                    'data_source_id' => $dataSource->id,
                    'name' => $code->label(),
                    'slug' => str_replace('_', '-', $code->value),
                    'description' => $code->defaultDescription(),
                    'sort_order' => $definition['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
