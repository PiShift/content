<?php

namespace PiShift\Content\Fields;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PiShift\Content\Support\Localizer;

/**
 * A file uploaded straight to a disk (often a private one), outside the media library:
 * e.g. documents processed by a job. The stored value is the file's path on that disk.
 */
class Upload extends Field
{
    protected string $disk = 'local';

    protected string $directory = 'uploads';

    /** @var list<string> */
    protected array $accept = [];

    /** Maximum size in kilobytes. */
    protected int $maxSize = 10240;

    protected ?string $fileNameAttribute = null;

    public function disk(string $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    public function directory(string $directory): static
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * @param  list<string>  $mimeTypes
     */
    public function accept(array $mimeTypes): static
    {
        $this->accept = $mimeTypes;

        return $this;
    }

    public function maxSize(int $kilobytes): static
    {
        $this->maxSize = $kilobytes;

        return $this;
    }

    /**
     * Also store the uploaded file's original name in this attribute.
     */
    public function storeFileNameIn(string $attribute): static
    {
        $this->fileNameAttribute = $attribute;

        return $this;
    }

    public function getFileNameAttribute(): ?string
    {
        return $this->fileNameAttribute;
    }

    public function type(): string
    {
        return 'upload';
    }

    /**
     * Store a new upload and return its path.
     */
    public function store(UploadedFile $file): string
    {
        return $file->store($this->directory, $this->disk);
    }

    public function delete(?string $path): void
    {
        if (filled($path)) {
            Storage::disk($this->disk)->delete($path);
        }
    }

    /**
     * A new file, or the current path sent back unchanged.
     */
    protected function valueRules(): array
    {
        $fileRules = ['file', 'max:'.$this->maxSize, ...($this->accept !== [] ? ['mimetypes:'.implode(',', $this->accept)] : [])];

        return [function (string $attribute, mixed $value, Closure $fail) use ($fileRules): void {
            if (is_string($value)) {
                return;
            }

            $validator = validator(['file' => $value], ['file' => $fileRules]);

            if ($validator->fails()) {
                $fail($validator->errors()->first('file'));
            }
        }];
    }

    protected function resolveValue(mixed $value, Localizer $localizer): mixed
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    protected function extraSchema(): array
    {
        return ['accept' => $this->accept, 'maxSize' => $this->maxSize];
    }
}
