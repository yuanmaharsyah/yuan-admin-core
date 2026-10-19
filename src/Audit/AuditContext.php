<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Audit;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Where an audit event came from (spec §7.1): `web`, `cli` (with the command)
 * or `queue` (with the job). Scoped, so it resets per request and per job.
 */
final class AuditContext
{
    private ?string $job = null;

    private ?string $command = null;

    private ?string $requestId = null;

    public function __construct(private readonly Application $app) {}

    public function startJob(string $class): void
    {
        $this->job = $class;
    }

    public function endJob(): void
    {
        $this->job = null;
    }

    public function startCommand(string $name): void
    {
        $this->command = $name;
    }

    /**
     * @return array{ip: ?string, user_agent: ?string, route: ?string, request_id: string, channel: string, job?: string, command?: string}
     */
    public function toArray(): array
    {
        $request = $this->app->make(Request::class);
        $route = $request->route();
        $channel = match (true) {
            $this->job !== null => 'queue',
            $route === null && $this->app->runningInConsole() => 'cli',
            default => 'web',
        };
        $web = $channel === 'web';

        return [
            'ip' => $web ? $request->ip() : null,
            'user_agent' => $web ? $request->userAgent() : null,
            'route' => $web && is_object($route) ? ($route->getName() ?? $route->uri()) : null,
            'request_id' => $this->requestId ??= $this->incomingRequestId($request) ?? (string) Str::uuid(),
            'channel' => $channel,
            ...match ($channel) {
                'queue' => ['job' => (string) $this->job],
                'cli' => $this->command === null ? [] : ['command' => $this->command],
                default => [],
            },
        ];
    }

    private function incomingRequestId(Request $request): ?string
    {
        $id = $request->header('X-Request-Id');

        return is_string($id) && preg_match('/^[A-Za-z0-9._-]{1,64}$/', $id) === 1 ? $id : null;
    }
}
