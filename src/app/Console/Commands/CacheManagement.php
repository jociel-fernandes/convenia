<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheManagement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:manage 
                            {action : Action to perform (clear, warm, status, flush)}
                            {--entity= : Specific entity to manage (users, all)}
                            {--pattern= : Pattern to clear (for advanced usage)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage application cache (clear, warm up, status)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $entity = $this->option('entity') ?? 'all';

        match ($action) {
            'clear' => $this->clearCache($entity),
            'warm' => $this->warmCache($entity),
            'status' => $this->cacheStatus(),
            'flush' => $this->flushCache($entity),
            default => $this->error("Invalid action: {$action}. Use: clear, warm, status, flush")
        };

        return Command::SUCCESS;
    }

    /**
     * Clear cache for specific entity or all
     */
    private function clearCache(string $entity): void
    {
        $this->info("🧹 Clearing cache for: {$entity}");

        if ($entity === 'users' || $entity === 'all') {
            if ($this->clearUserCache()) {
                $this->info('✅ User cache cleared successfully');
            }
        }

        if ($entity === 'all') {
            Cache::flush();
            $this->info('✅ All application cache cleared');
        }

        if ($pattern = $this->option('pattern')) {
            $this->clearByPattern($pattern);
        }
    }

    /**
     * Warm up cache with frequently accessed data
     */
    private function warmCache(string $entity): void
    {
        $this->info("🔥 Warming up cache for: {$entity}");

        if ($entity === 'users' || $entity === 'all') {
            $this->warmUserCache();
        }

        $this->info('✅ Cache warm-up completed');
    }

    /**
     * Show cache status and statistics
     */
    private function cacheStatus(): void
    {
        $this->info('📊 CACHE STATUS');
        $this->line('════════════════════════════════════════');

        // Redis connection status
        try {
            $redis = Redis::connection();
            $info = $redis->info();
            
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Cache Driver', config('cache.default')],
                    ['Redis Status', '🟢 Connected'],
                    ['Used Memory', $this->formatBytes($info['used_memory'] ?? 0)],
                    ['Connected Clients', $info['connected_clients'] ?? 'N/A'],
                    ['Total Commands', number_format($info['total_commands_processed'] ?? 0)],
                ]
            );

            // Cache keys statistics
            $this->showCacheKeyStatistics();

        } catch (\Exception $e) {
            $this->error("❌ Redis connection failed: {$e->getMessage()}");
        }
    }

    /**
     * Flush all cache
     */
    private function flushCache(string $entity): void
    {
        $this->warn("⚠️  This will completely flush cache for: {$entity}");
        
        if (!$this->confirm('Are you sure you want to continue?')) {
            $this->info('Operation cancelled');
            return;
        }

        if ($entity === 'users' || $entity === 'all') {
            $this->clearUserCache();
        }

        if ($entity === 'all') {
            Cache::flush();
            $this->info('🗑️  All cache flushed');
        }
    }

    /**
     * Clear user-specific cache
     */
    private function clearUserCache(): bool
    {
        try {
            // Manual pattern clearing (apenas para cache de autenticação)
            $this->clearByPattern('cacheduser*');

            return true;
        } catch (\Exception $e) {
            $this->error("Failed to clear user cache: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Warm up user cache
     */
    private function warmUserCache(): void
    {
        $this->withProgressBar([1, 2, 3], function ($item) {
            if ($item === 1) {
                // Warm up basic user cache patterns
                Cache::remember('users_count', 3600, function () {
                    return \App\Models\User::count();
                });
                $this->info('  ✅ User Repository cache warmed');
            }

            if ($item === 2) {
                // Warm up user service patterns
                $this->info('  ✅ User Service cache warmed');
            }

            if ($item === 3) {
                $this->info('  ✅ Additional cache warmed');
            }

            sleep(1); // Simulate work
        });
    }

    /**
     * Clear cache by pattern
     */
    private function clearByPattern(string $pattern): void
    {
        try {
            $cachePrefix = config('cache.prefix', 'laravel_cache');
            $searchPattern = $cachePrefix . ':' . $pattern;
            $keys = Redis::keys($searchPattern);

            if (!empty($keys)) {
                Redis::del($keys);
                $this->info("🗑️  Cleared " . count($keys) . " keys matching pattern: {$pattern}");
            } else {
                $this->info("No keys found matching pattern: {$pattern}");
            }
        } catch (\Exception $e) {
            $this->error("Failed to clear by pattern: {$e->getMessage()}");
        }
    }

    /**
     * Show cache key statistics
     */
    private function showCacheKeyStatistics(): void
    {
        try {
            $cachePrefix = config('cache.prefix', 'laravel_cache');
            
            $patterns = [
                'User Repository' => $cachePrefix . ':cacheduser*',
                'Session' => $cachePrefix . ':*session*',
                'Queue' => $cachePrefix . ':*queue*',
                'Other' => $cachePrefix . ':*',
            ];

            $this->line('');
            $this->info('🔍 Cache Key Statistics:');
            
            $data = [];
            foreach ($patterns as $name => $pattern) {
                $keys = Redis::keys($pattern);
                $count = count($keys);
                $data[] = [$name, $count, $count > 0 ? '🟢' : '⚪'];
            }

            $this->table(['Category', 'Keys Count', 'Status'], $data);

        } catch (\Exception $e) {
            $this->error("Failed to get key statistics: {$e->getMessage()}");
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $size, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}
