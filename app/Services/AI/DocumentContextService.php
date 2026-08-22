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

        $perDoc = max(800, intdiv($maxChars, max($documents->count(), 1)));
        $budget = $maxChars;
        $context = "\n\n--- COMPANY DOCUMENTS CONTEXT ---\n"
            .'The following are summaries of uploaded company documents. '
            ."Use this context to provide accurate, company-specific responses:\n\n";

        foreach ($documents as $doc) {
            if ($budget <= 0) {
                break;
            }

            $text = $this->documentText($doc, min($perDoc, $budget));

            if ($text === '') {
                continue;
            }

            $budget -= mb_strlen($text);
            $context .= "--- Document: {$doc->name} (Type: {$doc->file_type}) ---\n{$text}\n\n";
        }

        return $context."--- END COMPANY DOCUMENTS CONTEXT ---\n";
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

    /** Per-field character cap for the org block. */
    private const ORG_FIELD_CHARS = 300;

    /** Most friction points rendered into a prompt. */
    private const ORG_MAX_FRICTIONS = 5;

    /**
     * Build the "ORGANIZATIONAL CONTEXT" block for a user's organization.
     *
     * The active baseline is the one declared by the highest-ranking calibrated
     * member — the executive vision is the working truth for everyone
     * downstream. Returns an empty string when there is nothing to say, exactly
     * as SearchUserChat::additionalContextBlock() does.
     *
     * Bounded on purpose. This block goes into EVERY system message on EVERY
     * turn, unlike document text which is sent in full only on the first
     * message. Without a cap, one long governance answer would inflate every
     * request that organization ever makes, on a paid API. Truncation is at
     * render time only — the full text stays in org_context_versions, so the
     * persistence guarantee is untouched.
     */
    public function orgContextBlock($user): string
    {
        $version = optional(optional($user)->organization)->activeContext;

        if (! $version || empty($version->profile)) {
            return '';
        }

        $profile = $version->profile;

        $block = "\n\n--- ORGANIZATIONAL CONTEXT (ACTIVE BASELINE) ---\n";

        foreach ([
            'role' => ['Declared by', self::ORG_FIELD_CHARS],
            'scale' => ['Organizational scale', self::ORG_FIELD_CHARS],
            'governance' => ['Governance model', self::ORG_FIELD_CHARS],
        ] as $key => [$label, $limit]) {
            if (! empty($profile[$key])) {
                $block .= $label.': '.mb_substr((string) $profile[$key], 0, $limit)."\n";
            }
        }

        if (! empty($profile['frictions']) && is_array($profile['frictions'])) {
            $block .= "Key execution friction:\n";

            foreach (array_slice($profile['frictions'], 0, self::ORG_MAX_FRICTIONS) as $friction) {
                $block .= '- '.mb_substr((string) $friction, 0, self::ORG_FIELD_CHARS)."\n";
            }
        }

        return $block."--- END ORGANIZATIONAL CONTEXT ---\n";
    }

    /**
     * Build a GoalSync system message, appending the full document context.
     * Pass a custom $base to override the default persona.
     */
    public function buildSystemMessage($user, string $base = 'You are a strategy assistant. Respond only using structured ChatGPT-style text with emojis and clean formatting based on the GoalSync method.'): string
    {
        $documents = $this->forUser($user);
        $context = $this->buildContext($documents);

        return $base.$this->orgContextBlock($user).($context !== '' ? $context : '');
    }

    /**
     * Extract usable text from a single document.
     * Prefers the compact stored summary; falls back to (capped) parsed_text.
     */
    public function documentText($doc, int $maxChars = 6000): string
    {
        $text = ! empty($doc->summary) ? $doc->summary : (string) $doc->parsed_text;

        return mb_substr(trim($text), 0, $maxChars);
    }
}
