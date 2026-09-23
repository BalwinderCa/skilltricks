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

    /** Enough for any real organization's roster; a guard, not a design limit. */
    private const ORG_MAX_ROSTER_LINES = 60;

    /**
     * The author's organization's role names, as the prompt should show them:
     * sanitised and capped like the roster block. Empty when there are none.
     *
     * @return array<int, string>
     */
    public function roleNamesFor($user): array
    {
        $org = optional($user)->organization;
        if (! $org) {
            return [];
        }

        return $org->roles()->orderBy('name')->limit(self::ORG_MAX_ROSTER_LINES)->pluck('name')
            ->map(fn ($name) => $this->sanitiseForPrompt((string) $name))
            ->filter()->values()->all();
    }

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
        $org = optional($user)->organization;
        $version = optional($org)->activeContext;
        $profile = $version->profile ?? [];

        // The roster stands on its own: an organization that has never been
        // calibrated still has roles and departments, and "how many roles are
        // there" has a real answer that must not come from an uploaded document.
        $roster = $org ? $this->orgRosterLines($org) : '';

        if ($roster === '' && empty($profile)) {
            return '';
        }

        $block = "\n\n--- ORGANIZATIONAL CONTEXT (ACTIVE BASELINE) ---\n".$roster;

        // Who is asking, in the organization's own vocabulary. Sanitised like
        // every other value rendered here: role names are typed by the owner, so
        // an unescaped one could close this fenced block and carry on as
        // instructions.
        if ($role = optional($user)->orgRole) {
            $block .= 'Asking member role: '
                .mb_substr($this->sanitiseForPrompt((string) $role->name), 0, self::ORG_FIELD_CHARS)."\n";
        }

        foreach ($profile === [] ? [] : [
            'role' => ['Declared by', self::ORG_FIELD_CHARS],
            'scale' => ['Organizational scale', self::ORG_FIELD_CHARS],
            'governance' => ['Governance model', self::ORG_FIELD_CHARS],
        ] as $key => [$label, $limit]) {
            if (! empty($profile[$key])) {
                $block .= $label.': '.mb_substr($this->sanitiseForPrompt((string) $profile[$key]), 0, $limit)."\n";
            }
        }

        if (! empty($profile['frictions']) && is_array($profile['frictions'])) {
            $block .= "Key execution friction:\n";

            foreach (array_slice($profile['frictions'], 0, self::ORG_MAX_FRICTIONS) as $friction) {
                $block .= '- '.mb_substr($this->sanitiseForPrompt((string) $friction), 0, self::ORG_FIELD_CHARS)."\n";
            }
        }

        return $block."--- END ORGANIZATIONAL CONTEXT ---\n";
    }

    /**
     * The organization's actual roles and departments.
     *
     * This exists so questions like "how many roles are there" and "which
     * department are they in" are answered from org_roles and departments rather
     * than from an uploaded document or the model's own idea of what roles a
     * company has -- which is why the section says so in as many words.
     *
     * Names are owner-typed, so every one goes through the same sanitiser as the
     * profile values: this is a fenced region, and a role called
     * "--- END ORGANIZATIONAL CONTEXT ---" would otherwise walk straight out of it.
     */
    private function orgRosterLines($org): string
    {
        $roles = $org->roles()->orderBy('name')->pluck('name');
        $departments = $org->departments()->with('members.orgRole')->orderBy('name')->get();

        if ($roles->isEmpty() && $departments->isEmpty()) {
            return '';
        }

        $lines = "These are the organization's own roles and departments, and they are\n"
            ."authoritative: answer anything about roles, how many there are, or which\n"
            ."department someone is in from this list, never from an uploaded document.\n";

        if ($roles->isNotEmpty()) {
            $named = $roles->take(self::ORG_MAX_ROSTER_LINES)
                ->map(fn ($name) => $this->sanitiseForPrompt((string) $name));

            $lines .= 'Roles defined ('.$roles->count().'): '.$named->implode(', ')."\n";
        }

        if ($departments->isNotEmpty()) {
            $lines .= 'Departments ('.$departments->count()."):\n";

            foreach ($departments->take(self::ORG_MAX_ROSTER_LINES) as $department) {
                // Which roles actually sit in this department -- the join the
                // schema does not make directly, since a role belongs to the
                // organization and a department belongs to its members.
                $held = $department->members
                    ->map(fn ($member) => $member->orgRole?->name)
                    ->filter()
                    ->unique()
                    ->map(fn ($name) => $this->sanitiseForPrompt((string) $name))
                    ->values();

                $lines .= '- '.$this->sanitiseForPrompt((string) $department->name)
                    .' — '.$department->members->count().' '
                    .($department->members->count() === 1 ? 'member' : 'members')
                    .($held->isEmpty() ? '' : '; roles: '.$held->implode(', '))
                    ."\n";
            }
        }

        return $lines;
    }

    /**
     * Neutralise a stored profile value before it is rendered into a prompt.
     *
     * The block is a fenced region in the system prompt. A value carrying a
     * newline plus its own "---" fence would escape into the surrounding
     * instructions, so both are neutralised before the value is rendered.
     * The interview prompt is hardened too, but this is the render site: these
     * rows are append-only and also written by the backfill, so a value that
     * never passed through the agent still lands here.
     */
    private function sanitiseForPrompt(string $value): string
    {
        return trim((string) preg_replace('/-{3,}/', '--', str_replace(["\r", "\n"], ' ', $value)));
    }

    /**
     * Build a GoalSync system message, appending the full document context.
     * Pass a custom $base to override the default persona.
     */
    public function buildSystemMessage($user, string $base = 'You are a strategy assistant. Respond only using structured ChatGPT-style text with emojis and clean formatting based on the GoalSync method.'): string
    {
        $documents = $this->forUser($user);
        $context = $this->buildContext($documents);

        return $base.$this->orgContextBlock($user).$context;
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
