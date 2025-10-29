<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class MonitorQueues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:monitor 
                            {--interval=5 : Interval in seconds to refresh the monitor}
                            {--once : Run once and exit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor queue status in real-time';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $interval = $this->option('interval');
        $once = $this->option('once');

        do {
            $this->clearScreen();
            $this->displayHeader();
            $this->displayQueueStats();
            $this->displayRecentJobs();

            if ($once) {
                break;
            }

            sleep($interval);
        } while (true);

        return Command::SUCCESS;
    }

    private function clearScreen(): void
    {
        if (!$this->option('once')) {
            system('clear');
        }
    }

    private function displayHeader(): void
    {
        $this->info('═══════════════════════════════════════════════════════════════════');
        $this->info('                         QUEUE MONITOR - CONVENIA                    ');
        $this->info('═══════════════════════════════════════════════════════════════════');
        $this->line('Updated: ' . now()->format('Y-m-d H:i:s'));
        $this->line('');
    }

    private function displayQueueStats(): void
    {
        $redis = Redis::connection();
        
        $queues = [
            'default' => $redis->llen('queues:default'),
            'emails' => $redis->llen('queues:emails'),
        ];

        $this->info('📊 QUEUE STATUS:');
        $this->table(
            ['Queue Name', 'Pending Jobs', 'Status'],
            [
                ['default', $queues['default'], $this->getQueueStatus($queues['default'])],
                ['emails', $queues['emails'], $this->getQueueStatus($queues['emails'])],
            ]
        );

        // Database job stats
        $totalJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();

        $this->info('📈 DATABASE STATS:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Jobs in DB', $totalJobs],
                ['Failed Jobs', $failedJobs],
            ]
        );
    }

    private function displayRecentJobs(): void
    {
        $recentJobs = DB::table('jobs')
            ->select('queue', 'payload', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if ($recentJobs->isNotEmpty()) {
            $this->info('📋 RECENT JOBS:');
            $jobs = $recentJobs->map(function ($job) {
                $payload = json_decode($job->payload, true);
                return [
                    'Queue' => $job->queue,
                    'Job' => $payload['displayName'] ?? 'Unknown',
                    'Created' => $job->created_at,
                ];
            });

            $this->table(['Queue', 'Job', 'Created'], $jobs->toArray());
        } else {
            $this->info('📋 No recent jobs in database');
        }

        // Recent failed jobs
        $failedJobs = DB::table('failed_jobs')
            ->select('queue', 'payload', 'failed_at')
            ->orderBy('failed_at', 'desc')
            ->limit(3)
            ->get();

        if ($failedJobs->isNotEmpty()) {
            $this->error('❌ RECENT FAILED JOBS:');
            $jobs = $failedJobs->map(function ($job) {
                $payload = json_decode($job->payload, true);
                return [
                    'Queue' => $job->queue,
                    'Job' => $payload['displayName'] ?? 'Unknown',
                    'Failed' => $job->failed_at,
                ];
            });

            $this->table(['Queue', 'Job', 'Failed'], $jobs->toArray());
        }
    }

    private function getQueueStatus(int $count): string
    {
        if ($count === 0) {
            return '✅ Empty';
        } elseif ($count <= 5) {
            return '🟡 Low';
        } elseif ($count <= 20) {
            return '🟠 Medium';
        } else {
            return '🔴 High';
        }
    }
}
