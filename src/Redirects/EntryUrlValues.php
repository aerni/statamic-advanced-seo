<?php

namespace Aerni\AdvancedSeo\Redirects;

/**
 * Previous slug, date, and entry data for an entry URL.
 * A null slug, date, or data means that part is absent.
 */
readonly class EntryUrlValues
{
    /**
     * @param  array<string, mixed>|null  $data
     */
    public function __construct(
        public ?string $slug = null,
        public ?string $date = null,
        public ?array $data = null,
    ) {}

    /**
     * Values on this object that differ from the given ones.
     * Null when nothing differs. Unchanged slug, date, and data stay null.
     */
    public function diff(EntryUrlValues $values): ?self
    {
        $slug = $this->slug !== $values->slug ? $this->slug : null;
        $date = $this->date !== null && $this->date !== $values->date ? $this->date : null;

        $data = null;

        foreach ($this->data ?? [] as $token => $value) {
            if ($value != ($values->data[$token] ?? null)) {
                $data[$token] = $value;
            }
        }

        if ($slug === null && $date === null && $data === null) {
            return null;
        }

        return new self($slug, $date, $data);
    }
}
