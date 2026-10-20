<?php
declare(strict_types=1);

namespace Beaver\Plugins\Frankei\Frankei;

class Finding
{
    public function __construct(
        public string  $scanner,
        public string  $category,
        public string  $severity,
        public string  $title,
        public string  $description = '',
        public ?string $table       = null,
        public ?string $column      = null,
        public string  $impact      = '',
        public string  $suggestion  = '',
        public string  $reference   = '',
        public array   $data        = [],
        public bool    $attention   = false,
    ) {}

    public function toArray(): array
    {
        return [
            'scanner'     => $this->scanner,
            'category'    => $this->category,
            'severity'    => $this->severity,
            'title'       => $this->title,
            'description' => $this->description,
            'table'       => $this->table,
            'column'      => $this->column,
            'impact'      => $this->impact,
            'suggestion'  => $this->suggestion,
            'reference'   => $this->reference,
            'data'        => $this->data,
            'attention'   => $this->attention,
        ];
    }
}
