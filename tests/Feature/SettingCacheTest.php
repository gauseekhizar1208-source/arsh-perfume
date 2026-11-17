<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;

class SettingCacheTest extends FeatureTestCase
{
    public function test_repeated_settings_reads_use_one_database_cache_lookup(): void
    {
        config(['cache.default' => 'database']);
        Setting::set('store_name', 'Perfume Store');
        Cache::store('database')->forever(Setting::CACHE_KEY, ['store_name' => 'Perfume Store']);

        DB::enableQueryLog();
        DB::flushQueryLog();
        for ($i = 0; $i < 50; $i++) {
            $this->assertSame('Perfume Store', Setting::get('store_name'));
        }
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(1, $queries);
        $this->assertStringContainsString('cache', $queries[0]['query']);
    }

    public function test_editing_or_deleting_a_setting_invalidates_memoized_values(): void
    {
        Setting::set('custom_setting', 'Before');
        $this->assertSame('Before', Setting::get('custom_setting'));
        Setting::set('custom_setting', 'After');
        $this->assertSame('After', Setting::get('custom_setting'));
        Setting::where('key', 'custom_setting')->firstOrFail()->delete();
        $this->assertSame('Fallback', Setting::get('custom_setting', 'Fallback'));
    }
}
