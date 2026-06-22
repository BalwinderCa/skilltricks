<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\AI\DocumentSummaryService;
use Illuminate\Console\Command;

/**
 * Backfill compact summaries for already-parsed documents that don't have one
 * yet. The chat injects these summaries instead of full parsed_text, so running
 * this is what realises the token savings for documents uploaded before the
 * summary feature existed.
 */
class DocumentsSummarize extends Command
{
    protected $signature = 'documents:summarize {--force : Re-summarize documents that already have a summary}';

    protected $description = 'Generate compact AI summaries for parsed documents (used as bounded chat context)';

    public function handle(DocumentSummaryService $summarizer): int
    {
        $query = Document::where('parse_status', 'completed')
            ->whereNotNull('parsed_text');

        if (!$this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('summary')->orWhere('summary', '');
            });
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('No documents need summarizing.');
            return self::SUCCESS;
        }

        $this->info("Summarizing {$total} document(s)...");
        $bar = $this->output->createProgressBar($total);
        $ok = 0;
        $failed = 0;

        $query->orderBy('id')->chunkById(25, function ($documents) use ($summarizer, $bar, &$ok, &$failed) {
            foreach ($documents as $document) {
                $summary = $summarizer->summarize(
                    (string) $document->parsed_text,
                    (string) $document->name,
                    (string) $document->file_type
                );

                if (!empty($summary)) {
                    $document->summary = $summary;
                    $document->summary_status = 'completed';
                    $ok++;
                } else {
                    $document->summary_status = 'failed';
                    $failed++;
                }
                $document->save();
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Summarized: {$ok}, failed: {$failed}.");

        return self::SUCCESS;
    }
}
