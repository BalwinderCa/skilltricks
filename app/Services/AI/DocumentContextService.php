<?php

namespace App\Services\AI;

use App\Models\Document;

/**
 * Builds the company-document context blocks that are injected into AI
 * system messages. Centralises all document-fetching and text-budget logic
 * so controllers and other services never touch Document queries directly.
 */
class DocumentContextService
{
    /**
     * Fetch a user's parsed, completed documents ordered newest-first.
     */
    public function forUser($user)
    {
        return Document::where('user_id', $user->id)
            ->whereNotNull('parsed_text')
            ->where('parse_status', 'completed')
            ->latest()
            ->get();
    }

    /**
     * Build the full "COMPANY DOCUMENTS CONTEXT" block for a system message.
     * Uses per-document summaries (or capped parsed_text) and enforces a hard
     * total character budget so prompt cost stays bounded.
     */
    public function buildContext($documents, int $maxChars = 24000): string
    {
        if ($documents->count() === 0) {
            return '';
        }

        $perDoc  = max(800, intdiv($maxChars, max($documents->count(), 1)));
        $budget  = $maxChars;
        $context = "\n\n--- COMPANY DOCUMENTS CONTEXT ---\n"
            . "The following are summaries of uploaded company documents. "
            . "Use this context to provide accurate, company-specific responses:\n\n";

        foreach ($documents as $doc) {
            if ($budget <= 0) {
                break;
            }

            $text = $this->documentText($doc, min($perDoc, $budget));

            if ($text === '') {
                continue;
            }

            $budget  -= mb_strlen($text);
            $context .= "--- Document: {$doc->name} (Type: {$doc->file_type}) ---\n{$text}\n\n";
        }

        return $context . "--- END COMPANY DOCUMENTS CONTEXT ---\n";
    }

    /**
     * Build a compact "names-only" list for follow-up messages where the full
     * corpus was already sent earlier in the session.
     */
    public function buildNamesList($documents): string
    {
        if ($documents->count() === 0) {
            return '';
        }

        $list = "\n\nAvailable Documents:\n";

        foreach ($documents as $doc) {
            $list .= "- {$doc->name} ({$doc->file_type})\n";
        }

        return $list;
    }

    /**
     * Build a GoalSync system message, appending the full document context.
     * Pass a custom $base to override the default persona.
     */
    public function buildSystemMessage($user, string $base = 'You are a strategy assistant. Respond only using structured ChatGPT-style text with emojis and clean formatting based on the GoalSync method.'): string
    {
        $documents = $this->forUser($user);
        $context   = $this->buildContext($documents);

        return $base . ($context !== '' ? $context : '');
    }

    /**
     * Extract usable text from a single document.
     * Prefers the compact stored summary; falls back to (capped) parsed_text.
     */
    public function documentText($doc, int $maxChars = 6000): string
    {
        $text = !empty($doc->summary) ? $doc->summary : (string) $doc->parsed_text;

        return mb_substr(trim($text), 0, $maxChars);
    }
}
