<?php

namespace App\Console\Commands;

use Illuminate\Cache\RedisStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Predis\Client as PredisClient;
use Redis as PhpRedis;

/**
 * Safety net for the listening-heartbeat keys in Redis.
 *
 * Every heartbeat key is written with a TTL (lock: ~1 minute; daily
 * counters: until just after the WIB day ends), so they normally vanish on
 * their own. This removes any that somehow lost their TTL and would
 * otherwise live forever.
 */
class PruneRedisKeys extends Command
{
    protected $signature = 'oz:prune-redis-keys';

    protected $description = 'Remove stale listening-heartbeat keys (no TTL) from the Redis cache';

    /** Key families written by RecordListeningHeartbeatAction. */
    private const PATTERNS = ['heartbeat:user:*', 'listening:user:*', 'listened:user:*'];

    public function handle(): int
    {
        $store = Cache::getStore();

        if (! $store instanceof RedisStore) {
            $this->components->info('Cache store is not Redis; nothing to prune.');

            return self::SUCCESS;
        }

        $client = $store->connection()->client();
        $prefix = (string) config('database.redis.options.prefix').$store->getPrefix();
        $removed = 0;

        foreach (self::PATTERNS as $pattern) {
            $cursor = '0';

            do {
                [$cursor, $keys] = $this->scan($client, $cursor, $prefix.$pattern);

                foreach ($keys as $key) {
                    // -1 = exists without an expiry.
                    if ((int) $this->raw($client, ['TTL', $key]) === -1) {
                        $this->raw($client, ['DEL', $key]);
                        $removed++;
                    }
                }
            } while ($cursor !== '0');
        }

        $this->components->info("Removed {$removed} stale heartbeat key(s).");

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: list<string>}
     */
    private function scan(mixed $client, string $cursor, string $match): array
    {
        $result = $this->raw($client, ['SCAN', $cursor, 'MATCH', $match, 'COUNT', '500']);

        return is_array($result) && count($result) === 2
            ? [(string) $result[0], array_values(array_map('strval', (array) $result[1]))]
            : ['0', []];
    }

    /**
     * Raw commands bypass client-side key prefixing, so keys from SCAN are used as-is.
     *
     * @param  list<string>  $command
     */
    private function raw(mixed $client, array $command): mixed
    {
        return match (true) {
            $client instanceof PredisClient => $client->executeRaw($command),
            $client instanceof PhpRedis => $client->rawCommand(...$command),
            default => null,
        };
    }
}
