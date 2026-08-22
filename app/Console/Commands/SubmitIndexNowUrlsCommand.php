<?php

namespace App\Console\Commands;

use App\Support\PublicUrls;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/** Notifies the IndexNow API that public pages have been added, updated or removed. */
class SubmitIndexNowUrlsCommand extends Command
{
    /** The protocol accepts at most 10 000 URLs per request. */
    private const MAX_URLS_PER_BATCH = 10000;

    protected $signature = 'indexnow:submit
                            {--url=* : Submit only these absolute URLs instead of every public page}
                            {--dry-run : Show what would be submitted without calling the API}';

    protected $description = 'Notify IndexNow (Bing, Yandex, Seznam, Naver, Yep) that public pages changed';

    public function handle(): int
    {
        $key = (string) config('services.indexnow.key');

        if ($key === '') {
            $this->warn('IndexNow key is not configured: set INDEXNOW_KEY in your environment.');

            return Command::FAILURE;
        }

        $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

        if ($host === '') {
            $this->warn('Cannot determine the site host: check APP_URL in your environment.');

            return Command::FAILURE;
        }

        $urls = $this->urlsToSubmit();

        if ($urls === []) {
            $this->info('No URL to submit.');

            return Command::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->info(($dryRun ? '[DRY RUN] ' : '')."Submitting {$this->countLabel($urls)} to IndexNow for host {$host}.");

        foreach ($urls as $url) {
            $this->line("  {$url}");
        }

        if ($dryRun) {
            $this->info('[DRY RUN] Nothing was sent.');

            return Command::SUCCESS;
        }

        $failed = false;

        foreach (array_chunk($urls, self::MAX_URLS_PER_BATCH) as $batch) {
            if (! $this->submitBatch($key, $host, $batch)) {
                $failed = true;
            }
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @param  array<int, string>  $urls
     */
    private function submitBatch(string $key, string $host, array $urls): bool
    {
        $payload = [
            'host' => $host,
            'key' => $key,
            'keyLocation' => url("/{$key}.txt"),
            'urlList' => array_values($urls),
        ];

        try {
            $response = Http::asJson()
                ->contentType('application/json; charset=utf-8')
                ->connectTimeout(5)
                ->timeout(15)
                ->retry(
                    2,
                    500,
                    fn (\Throwable $exception): bool => $exception instanceof ConnectionException,
                    throw: false
                )
                ->post((string) config('services.indexnow.endpoint'), $payload);
        } catch (ConnectionException $exception) {
            $this->warn("Could not reach the IndexNow endpoint: {$exception->getMessage()}");

            return false;
        }

        return $this->reportStatus($response, count($urls));
    }

    private function reportStatus(Response $response, int $submitted): bool
    {
        $status = $response->status();

        if ($status === 200) {
            $this->info("IndexNow accepted {$submitted} URLs (HTTP 200).");

            return true;
        }

        if ($status === 202) {
            $this->info("IndexNow received {$submitted} URLs (HTTP 202): key validation is pending.");

            return true;
        }

        if ($status === 400) {
            $this->warn('IndexNow rejected the request as malformed (HTTP 400).');
        }

        if ($status === 403) {
            $this->warn('IndexNow could not validate the key (HTTP 403): check that the key file is publicly reachable.');
        }

        if ($status === 422) {
            $this->warn('IndexNow rejected the URLs (HTTP 422): they do not belong to the host, or the key does not match the expected format.');
        }

        if ($status === 429) {
            $this->warn('IndexNow is rate limiting the submissions (HTTP 429).'.$this->retryAfterHint($response));
        }

        if (! in_array($status, [400, 403, 422, 429], true)) {
            $this->warn("IndexNow returned an unexpected status (HTTP {$status}).");
        }

        return false;
    }

    /** Retry-After holds either a number of seconds or an HTTP date (RFC 9110), so only seconds get the unit. */
    private function retryAfterHint(Response $response): string
    {
        $retryAfter = trim($response->header('Retry-After'));

        if ($retryAfter === '') {
            return '';
        }

        if (ctype_digit($retryAfter)) {
            return " Retry after {$retryAfter} seconds.";
        }

        return " Retry after {$retryAfter}.";
    }

    /**
     * @return array<int, string>
     */
    private function urlsToSubmit(): array
    {
        /** @var array<int, string> $explicitUrls */
        $explicitUrls = (array) $this->option('url');

        $explicitUrls = array_values(array_filter(array_map(
            fn (string $url): string => trim($url),
            $explicitUrls
        )));

        if ($explicitUrls !== []) {
            return $explicitUrls;
        }

        return PublicUrls::all();
    }

    /**
     * @param  array<int, string>  $urls
     */
    private function countLabel(array $urls): string
    {
        $count = count($urls);

        return $count === 1 ? '1 URL' : "{$count} URLs";
    }
}
