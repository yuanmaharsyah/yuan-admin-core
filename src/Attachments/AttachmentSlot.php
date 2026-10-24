<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Attachments;

/**
 * One named place for files on a record (HasAttachments::attachmentSlots()):
 * a single file or many, optionally required, with its own types and size
 * limit (defaults: `admin-core.attachments`).
 */
final readonly class AttachmentSlot
{
    /**
     * @param  list<string>  $mimes  Allowed extensions, without the dot.
     */
    private function __construct(
        public string $label,
        public bool $multiple,
        public bool $required,
        public array $mimes,
        public int $maxKb,
    ) {}

    public static function single(string $label): self
    {
        return new self($label, false, false, self::defaultMimes(), config()->integer('admin-core.attachments.max_kb'));
    }

    public static function multiple(string $label): self
    {
        return new self($label, true, false, self::defaultMimes(), config()->integer('admin-core.attachments.max_kb'));
    }

    /** A create needs a file; an edit needs one unless the slot already holds one. */
    public function required(): self
    {
        return new self($this->label, $this->multiple, true, $this->mimes, $this->maxKb);
    }

    /**
     * @param  list<string>  $mimes
     */
    public function mimes(array $mimes): self
    {
        return new self($this->label, $this->multiple, $this->required, $mimes, $this->maxKb);
    }

    public function maxKb(int $maxKb): self
    {
        return new self($this->label, $this->multiple, $this->required, $this->mimes, $maxKb);
    }

    /**
     * Rules for `files.{key}` (and `files.{key}.*` when multiple). `$filled`:
     * the slot already holds a file, so a required slot may stay empty.
     *
     * @return array<string, list<string>>
     */
    public function rules(string $key, bool $filled): array
    {
        $presence = $this->required && ! $filled ? 'required' : 'nullable';
        $file = ['file', 'mimes:'.implode(',', $this->mimes), 'max:'.$this->maxKb];

        return $this->multiple
            ? ["files.{$key}" => [$presence, 'array'], "files.{$key}.*" => $file]
            : ["files.{$key}" => [$presence, ...$file]];
    }

    /**
     * @return list<string>
     */
    private static function defaultMimes(): array
    {
        /** @var list<string> */
        return config()->array('admin-core.attachments.mimes');
    }
}
